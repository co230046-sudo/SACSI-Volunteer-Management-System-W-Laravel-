<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminAccount;
use App\Models\AdminAuthenticateLog;
use App\Models\FactLog;
use App\Models\FactType;
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
    ============================ */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|email',
            'password' => 'required|string',
        ], [
            'username.required' => 'Please enter your email.',
            'username.email' => 'Please enter a valid email address.',
            'password.required' => 'Please enter your password.',
        ]);

        $credentials = [
            'email' => $request->username,
            'password' => $request->password,
        ];

        $ip = $request->ip();
        $admin = AdminAccount::where('email', $request->username)->first();

        if (Auth::guard('admin')->attempt($credentials)) {
            $request->session()->regenerate();

            // Successful login log
            AdminAuthenticateLog::create([
                'admin_id' => $admin?->admin_id,
                'ip_address' => $ip,
                'status' => 'success',
                'reason' => null,
                'login_time' => now(),
            ]);

            $this->logFact(
                'authentication',
                $admin?->admin_id,
                'admin_accounts',
                $admin?->admin_id,
                'login',
                'Admin logged in successfully'
            );

            return redirect()->route('home')->with('success', 'Welcome back, Admin!');
        }

        // Failed login log
        AdminAuthenticateLog::create([
            'admin_id' => $admin?->admin_id,
            'ip_address' => $ip,
            'status' => 'failed',
            'reason' => 'Incorrect email or password',
            'login_time' => now(),
        ]);

        $this->logFact(
            'authentication',
            $admin?->admin_id,
            'admin_accounts',
            $admin?->admin_id,
            'failed_login',
            'Incorrect email or password'
        );

        return back()
            ->withInput($request->only('username'))
            ->with('login_error', 'Incorrect email or password.');
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
            'username' => 'required|string|max:100|unique:admin_accounts,username',
            'email' => 'required|email|unique:admin_accounts,email',
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
            'profile_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'role' => 'required|in:super_admin,admin',
        ], [
            'username.required' => 'Please enter your full name.',
            'email.unique' => 'This email is already registered.',
            'password.confirmed' => 'Passwords do not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.regex' => 'Password must contain at least one uppercase letter and one number.',
            'profile_picture.required' => 'Please upload a profile picture.',
            'profile_picture.image' => 'Only JPG, JPEG, or PNG files are allowed.',
            'role.required' => 'Please select a role.',
            'role.in' => 'Selected role is invalid.',
        ]);

        $profilePath = $request->file('profile_picture')->store('profile_pictures', 'public');

        $admin = AdminAccount::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'profile_picture' => $profilePath,
            'role' => $request->role,
            'status' => 'active',
        ]);

        Auth::guard('admin')->login($admin);

        // Log registration
        AdminAuthenticateLog::create([
            'admin_id' => $admin->admin_id,
            'ip_address' => $request->ip(),
            'status' => 'success',
            'reason' => 'Registration and auto-login',
            'login_time' => now(),
        ]);

        $this->logFact(
            'authentication',
            $admin->admin_id,
            'admin_accounts',
            $admin->admin_id,
            'register',
            'Admin registered and auto-logged in'
        );

        return redirect()->route('home')->with('success', 'Registration successful! Welcome!');
    }

    /* ===========================
       HANDLE LOGOUT
    ============================ */
    public function logout(Request $request)
    {
        $adminId = Auth::guard('admin')->id();
        $ip = $request->ip();

        // Log logout
        AdminAuthenticateLog::create([
            'admin_id' => $adminId,
            'ip_address' => $ip,
            'status' => 'success',
            'reason' => 'Logged out',
            'login_time' => now(),
        ]);

        $this->logFact(
            'authentication',
            $adminId,
            'admin_accounts',
            $adminId,
            'logout',
            'Admin logged out successfully'
        );

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        session()->forget(['invalidEntries', 'validEntries', 'last_deleted_entries']);

        return redirect()->route('auth.login')->with('success', 'You have been logged out.');
    }

    /* ===========================
       HELPER: LOG TO FACT_LOGS
    ============================ */
    private function logFact($factTypeName, $adminId, $entityType, $entityId, $action, $details = null)
    {
        $factType = FactType::firstOrCreate(
            ['type_name' => $factTypeName],
            ['description' => $factTypeName]
        );

        FactLog::create([
            'fact_type_id' => $factType->fact_type_id,
            'admin_id' => $adminId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'details' => $details,
            'timestamp' => now(),
        ]);
    }
}
