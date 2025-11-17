<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminAccount;
use App\Models\AdminAuthenticateLog;
use App\Models\FactLog;
use App\Models\ImportLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /* ===========================
       SHOW LOGIN PAGE
    ============================ */
    public function showLogin()
    {
        return view('authentication.admin.login');
    }
    /* ===========================
   HANDLE LOGIN
=========================== */
public function login(Request $request)
{
    $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ], [
        'username.required' => 'Please enter your username, email, or full name.',
        'password.required' => 'Please enter your password.',
    ]);

    $loginField = $request->input('username');
    $password = $request->input('password');
    $ip = $request->ip();

    // Attempt to find admin by username, email, or full_name
    $admin = AdminAccount::where('username', $loginField)
        ->orWhere('email', $loginField)
        ->orWhere('full_name', $loginField)
        ->first();

    // Check password manually
    if ($admin && \Illuminate\Support\Facades\Hash::check($password, $admin->password)) {
        // Login the admin
        Auth::guard('admin')->login($admin);
        $request->session()->regenerate();

        // Log successful login
        AdminAuthenticateLog::create([
            'admin_id' => $admin->admin_id,
            'ip_address' => $ip,
            'status' => 'success',
            'reason' => null,
            'login_time' => now(),
        ]);

        $this->logFact(
            $admin->admin_id,
            'admin_accounts',
            $admin->admin_id,
            'login',
            'Admin logged in successfully'
        );

        return redirect()->route('home')
            ->with('success', 'Welcome back, ' . ($admin->full_name ?? 'Admin') . '!');
    }

    // Log failed login (admin_id may be null if user not found)
    AdminAuthenticateLog::create([
        'admin_id' => $admin?->admin_id,
        'ip_address' => $ip,
        'status' => 'failed',
        'reason' => 'Incorrect credentials',
        'login_time' => now(),
    ]);

    $this->logFact(
        $admin?->admin_id,
        'admin_accounts',
        $admin?->admin_id,
        'failed_login',
        'Incorrect username, email, or full name, or password'
    );

    return back()
        ->withInput($request->only('username'))
        ->with('login_error', 'Incorrect username, email, or full name, or password.');
}

    /* ===========================
       SHOW REGISTER PAGE
    ============================ */
    public function showRegister()
    {
        $roles = ['super_admin', 'admin'];
        return view('authentication.admin.register', compact('roles'));
    }

    /* ===========================
       HANDLE REGISTRATION
    ============================ */
    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:admin_accounts,username',
            'email' => [
                'required',
                'email',
                'unique:admin_accounts,email',
                'regex:/@(gmail\.com|adzu\.edu\.ph)$/i'
            ],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*\d).+$/',
            ],
            'profile_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'role' => 'required|in:super_admin,admin',
        ], [
            'full_name.required' => 'Please enter your full name.',
            'username.required' => 'Please enter your username.',
            'username.unique' => 'This username is already taken.',
            'email.required' => 'Please enter your email.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'email.regex' => 'Only @gmail.com or @adzu.edu.ph emails are allowed.',
            'password.required' => 'Please enter your password.',
            'password.confirmed' => 'Passwords do not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must include at least one uppercase letter and one number.',
            'profile_picture.required' => 'Please upload a profile picture.',
            'profile_picture.image' => 'Only JPG, JPEG, or PNG files are allowed.',
            'role.required' => 'Please select a role.',
            'role.in' => 'Selected role is invalid.',
        ]);

        $profilePath = $request->file('profile_picture')->store('profile_pictures', 'public');

        $admin = null;
        DB::transaction(function () use ($request, $profilePath, &$admin) {
            $admin = AdminAccount::create([
                'full_name' => $request->full_name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'profile_picture' => $profilePath,
                'role' => $request->role,
                'status' => 'active',
            ]);

            Auth::guard('admin')->login($admin);
        });

        AdminAuthenticateLog::create([
            'admin_id' => $admin->admin_id,
            'ip_address' => $request->ip(),
            'status' => 'success',
            'reason' => 'Registration and auto-login',
            'login_time' => now(),
        ]);

        $this->logFact(
            $admin->admin_id,
            'admin_accounts',
            $admin->admin_id,
            'register',
            'Admin registered and auto-logged in'
        );

        return redirect()->route('home')->with('success', 'Registration successful! Welcome, ' . $admin->full_name . '!');
    }

    /* ===========================
       HANDLE LOGOUT
    ============================ */
    public function logout(Request $request)
    {
        $adminId = Auth::guard('admin')->id();
        $ip = $request->ip();

        if ($adminId) {
            // 1️⃣ Log the admin logout in AdminAuthenticateLog
            AdminAuthenticateLog::create([
                'admin_id'   => $adminId,
                'ip_address' => $ip,
                'status'     => 'success',
                'reason'     => 'Logged out',
                'login_time' => now(),
            ]);

            // 2️⃣ Log fact
            $this->logFact(
                $adminId,
                'admin_accounts',
                $adminId,
                'logout',
                'Admin logged out successfully'
            );

            // 3️⃣ Mark any pending imports as Abandoned, preserve admin_id
            $admin = Auth::guard('admin')->user();
            ImportLog::where('admin_id', $admin->admin_id)
                    ->where('status', 'Pending')
                    ->update([
                        'status'  => 'Abandoned',
                        'admin_id'=> $admin->admin_id, // preserve the admin who started it
                        'remarks' => "Admin: {$admin->username} logged out before completing import."
                    ]);

        }

        // 4️⃣ Logout and invalidate session
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 5️⃣ Clear temporary import/session data
        session()->forget([
            'invalidEntries',
            'validEntries',
            'last_deleted_entries',
            'uploaded_file_name',
            'uploaded_file_path',
            'csv_imported',
            'import_log_id',
            'lastUsedTable'
        ]);

        return redirect()->route('auth.login')
                        ->with('success', 'You have been logged out.');
    }



    /* ===========================
       HELPER: LOG TO FACT_LOGS
    ============================ */
    private function logFact($adminId, $entityType, $entityId, $action, $details = null)
    {
        FactLog::create([
            'admin_id' => $adminId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'details' => $details,
            'timestamp' => now(),
        ]);
    }
}
