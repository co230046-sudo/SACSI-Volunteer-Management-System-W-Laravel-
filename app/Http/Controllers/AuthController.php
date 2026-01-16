<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminAccount;
use App\Models\AdminAuthenticateLog;
use App\Models\ImportLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// ✅ Centralized fact logger
use App\Services\FactLogger;

class AuthController extends Controller
{
    public function __construct(private FactLogger $factLogger)
    {
    }

    /* ===========================
       HELPERS (FactLog summary format)
       Required format:
       - Login Successful - "David"
       - Logout Successful - "David"
       - Registration Successful - "David"
       - Login Failed - "attempt"
    ============================ */

    private function quoted(?string $value, string $fallback = 'Unknown'): string
    {
        $v = trim((string)($value ?? ''));
        if ($v === '') $v = $fallback;
        return "\"{$v}\"";
    }

    private function summaryLoginSuccess(?string $username): string
    {
        return 'Login Successful - ' . $this->quoted($username);
    }

    private function summaryLogoutSuccess(?string $username): string
    {
        return 'Logout Successful - ' . $this->quoted($username);
    }

    private function summaryRegisterSuccess(?string $username): string
    {
        return 'Registration Successful - ' . $this->quoted($username);
    }

    private function summaryFailedLogin(?string $attemptedLoginField): string
    {
        return 'Login Failed - ' . $this->quoted($attemptedLoginField, '(blank)');
    }

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
            'username' => 'required|string',
            'password' => 'required|string',
        ], [
            'username.required' => 'Please enter your username, email, or full name.',
            'password.required' => 'Please enter your password.',
        ]);

        $loginField = $request->input('username');
        $password   = $request->input('password');
        $ip         = $request->ip();

        // Attempt to find admin by username, email, or full_name
        $admin = AdminAccount::where('username', $loginField)
            ->orWhere('email', $loginField)
            ->orWhere('full_name', $loginField)
            ->first();

        // ✅ SUCCESS
        if ($admin && Hash::check($password, $admin->password)) {

            Auth::guard('admin')->login($admin);
            $request->session()->regenerate();

            AdminAuthenticateLog::create([
                'admin_id'   => $admin->admin_id,
                'ip_address' => $ip,
                'status'     => 'success',
                'reason'     => null,
                'login_time' => now(),
            ]);

            // ✅ Centralized FactLog
            $this->factLogger->log(
                type: 'auth.login',
                action: 'login',
                entity: $admin,
                entityId: $admin->admin_id,
                details: [
                    'summary' => $this->summaryLoginSuccess($admin->username),
                    'data' => [
                        'login_field' => $loginField,
                        'status'      => 'success',
                    ],
                ],
                adminId: $admin->admin_id
            );

            return redirect()->route('home')
                ->with('success', 'Welcome back, ' . ($admin->full_name ?? 'Admin') . '!');
        }

        // ❌ FAILED
        AdminAuthenticateLog::create([
            'admin_id'   => $admin?->admin_id,
            'ip_address' => $ip,
            'status'     => 'failed',
            'reason'     => 'Incorrect credentials',
            'login_time' => now(),
        ]);

        // ✅ Centralized FactLog (admin may be null)
        $this->factLogger->log(
            type: 'auth.failed_login',
            action: 'failed_login',
            entity: $admin ?: 'AdminAccount',
            entityId: $admin?->admin_id,
            details: [
                'summary' => $this->summaryFailedLogin($loginField),
                'data' => [
                    'login_field'       => $loginField,
                    'status'            => 'failed',
                    'reason'            => 'Incorrect credentials',
                    'resolved_admin_id' => $admin?->admin_id,
                ],
            ],
            adminId: $admin?->admin_id
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
            'username'  => 'required|string|max:100|unique:admin_accounts,username',
            'email'     => [
                'required',
                'email',
                'unique:admin_accounts,email',
                'regex:/@(gmail\.com|adzu\.edu\.ph)$/i'
            ],
            'password'  => [
                'required',
                'confirmed',
                'min:8',
                'regex:/^(?=.*[A-Z])(?=.*\d).+$/',
            ],
            'profile_picture' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'role'            => 'required|in:super_admin,admin',
        ], [
            'full_name.required' => 'Please enter your full name.',
            'username.required'  => 'Please enter your username.',
            'username.unique'    => 'This username is already taken.',
            'email.required'     => 'Please enter your email.',
            'email.email'        => 'Please enter a valid email address.',
            'email.unique'       => 'This email is already registered.',
            'email.regex'        => 'Only @gmail.com or @adzu.edu.ph emails are allowed.',
            'password.required'  => 'Please enter your password.',
            'password.confirmed' => 'Passwords do not match.',
            'password.min'       => 'Password must be at least 8 characters.',
            'password.regex'     => 'Password must include at least one uppercase letter and one number.',
            'profile_picture.required' => 'Please upload a profile picture.',
            'profile_picture.image'    => 'Only JPG, JPEG, or PNG files are allowed.',
            'role.required'      => 'Please select a role.',
            'role.in'            => 'Selected role is invalid.',
        ]);

        $profilePath = $request->file('profile_picture')->store('profile_pictures/admin', 'public');

        $admin = null;

        DB::transaction(function () use ($request, $profilePath, &$admin) {
            $admin = AdminAccount::create([
                'full_name'       => $request->full_name,
                'username'        => $request->username,
                'email'           => $request->email,
                'password'        => Hash::make($request->password),
                'profile_picture' => $profilePath,
                'role'            => $request->role,
                'status'          => 'active',
            ]);

            Auth::guard('admin')->login($admin);
        });

        AdminAuthenticateLog::create([
            'admin_id'   => $admin->admin_id,
            'ip_address' => $request->ip(),
            'status'     => 'success',
            'reason'     => 'Registration and auto-login',
            'login_time' => now(),
        ]);

        // ✅ Centralized FactLog
        $this->factLogger->log(
            type: 'auth.register',
            action: 'register',
            entity: $admin,
            entityId: $admin->admin_id,
            details: [
                'summary' => $this->summaryRegisterSuccess($admin->username),
                'data' => [
                    'email'  => $admin->email,
                    'role'   => $admin->role,
                    'status' => 'success',
                ],
            ],
            adminId: $admin->admin_id
        );

        return redirect()->route('home')
            ->with('success', 'Registration successful! Welcome, ' . $admin->full_name . '!');
    }

    /* ===========================
       HANDLE LOGOUT
    ============================ */
    public function logout(Request $request)
    {
        $admin   = Auth::guard('admin')->user();
        $adminId = $admin?->admin_id;
        $ip      = $request->ip();

        if ($adminId) {
            AdminAuthenticateLog::create([
                'admin_id'   => $adminId,
                'ip_address' => $ip,
                'status'     => 'success',
                'reason'     => 'Logged out',
                'login_time' => now(),
            ]);

            // ✅ Centralized FactLog
            $this->factLogger->log(
                type: 'auth.logout',
                action: 'logout',
                entity: $admin,
                entityId: $adminId,
                details: [
                    'summary' => $this->summaryLogoutSuccess($admin?->username),
                    'data' => [
                        'status' => 'success',
                    ],
                ],
                adminId: $adminId
            );

            // Keep your behavior (mark pending imports abandoned)
            ImportLog::where('admin_id', $adminId)
                ->where('status', 'Pending')
                ->update([
                    'status'   => 'Abandoned',
                    'admin_id' => $adminId,
                    'remarks'  => "{$admin->username} logged out before completing import."
                ]);
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

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
}
