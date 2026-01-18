<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminAccount;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminProfileController extends Controller
{
    /* ============================================================
        VIEW ADMIN PROFILE (SELF OR OTHER)
    ============================================================ */
    public function index(Request $request, $id = null)
    {
        if (!auth('admin')->check()) {
            return redirect()->route('auth.login');
        }

        $currentAdmin = auth('admin')->user();

        // View own profile OR selected admin
        $admin = $id ? AdminAccount::findOrFail($id) : $currentAdmin;

        $isSuperAdmin = preg_match('/super/i', $currentAdmin->role);

        // Restrict non-super admins
        if (!$isSuperAdmin && $admin->admin_id != $currentAdmin->admin_id) {
            return redirect()->route('admin.profile.self')
                ->with('error', 'Only super admins can view other admin profiles.');
        }

        // Admin list for super admin only
        $allAdmins = $isSuperAdmin
            ? AdminAccount::orderBy('full_name')->get()
            : collect([]);

        return view('admin.profile', compact(
            'admin', 'allAdmins', 'currentAdmin'
        ));
    }

    /* ============================================================
        EDIT PROFILE PAGE
    ============================================================ */
    public function edit($id)
    {
        if (!auth('admin')->check()) {
            return redirect()->route('auth.login');
        }

        $currentAdmin = auth('admin')->user();
        $admin = AdminAccount::findOrFail($id);

        $isSuperAdmin = preg_match('/super/i', $currentAdmin->role);

        if (!$isSuperAdmin && $currentAdmin->admin_id != $admin->admin_id) {
            return redirect()->route('admin.profile.self')
                ->with('error', 'You are not allowed to edit this profile.');
        }

        return view('admin.edit_profile', compact('admin', 'currentAdmin'));
    }

    /* ============================================================
        UPDATE PROFILE
    ============================================================ */
    public function update(Request $request)
    {
        auth()->shouldUse('admin');
        $currentAdmin = auth('admin')->user();
        $isSuperAdmin = preg_match('/super/i', $currentAdmin->role);

        $admin = ($isSuperAdmin && $request->admin_id)
            ? AdminAccount::find($request->admin_id)
            : $currentAdmin;

        if (!$admin) {
            return back()->with('error', 'Admin not found.');
        }

        if (!$isSuperAdmin && $admin->admin_id != $currentAdmin->admin_id) {
            return back()->with('error', 'Only super admins can update other admin profiles.');
        }

        $request->validate([
            'full_name' => 'required|string|max:255',

            'email' => [
                'required', 'email',
                Rule::unique('admin_accounts', 'email')->ignore($admin->admin_id, 'admin_id'),
            ],

            'contact_number' => 'nullable|string|max:20',

            'username' => $isSuperAdmin ? [
                'required',
                Rule::unique('admin_accounts', 'username')->ignore($admin->admin_id, 'admin_id'),
            ] : 'nullable',

            'role' => $isSuperAdmin ? 'required|string' : 'nullable',

            'profile_picture' => 'nullable|mimes:jpg,jpeg,png|max:5120',
        ]);

        // SUPER ADMIN FIELDS
        if ($isSuperAdmin && $request->filled('username')) {
            $admin->username = $request->username;
        }

        if ($isSuperAdmin && $request->filled('role')) {
            $admin->role = $request->role;
        }

        // PASSWORD UPDATE
        if ($request->filled('current_password') || $request->filled('new_password')) {

            if ($admin->admin_id != $currentAdmin->admin_id) {
                return back()->with('error', 'You cannot change another admin’s password.');
            }

            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:8|confirmed',
            ]);

            if (!Hash::check($request->current_password, $admin->password)) {
                return back()->with('error', 'Current password is incorrect.');
            }

            $admin->password = Hash::make($request->new_password);
        }

        // PROFILE PICTURE
        if ($request->hasFile('profile_picture')) {
            $admin->profile_picture =
                $request->file('profile_picture')->store('admin_photos', 'public');
        }

        // SAVE PROFILE
        $admin->full_name = $request->full_name;
        $admin->email = $request->email;
        $admin->contact_number = $request->contact_number;

        $admin->save();

        return redirect()->route('admin.profile', $admin->admin_id)
            ->with('success', 'Profile updated successfully!');
    }

    /* ============================================================
        SUPER ADMIN LIST
    ============================================================ */
    public function adminList()
    {
        if (!auth('admin')->check()) {
            return redirect()->route('auth.login');
        }

        $currentAdmin = auth('admin')->user();

        if (!preg_match('/super/i', $currentAdmin->role)) {
            return redirect()->route('admin.profile.self')
                ->with('error', 'Only super admins can view all admin profiles.');
        }

        $admins = AdminAccount::orderBy('full_name')->get();

        return view('admin.admin_list', compact('admins', 'currentAdmin'));
    }

    /* ============================================================
        AJAX: VIEW PROFILE MODAL
    ============================================================ */
    public function viewProfile($id)
    {
        $admin = AdminAccount::find($id);

        if (!$admin) {
            return response()->json(['success' => false], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $admin->full_name,
                'username' => $admin->username,
                'email' => $admin->email,
                'contact_number' => $admin->contact_number,
                'role' => $admin->role,
                'profile_picture' =>
                    $admin->profile_picture
                        ? asset('storage/' . $admin->profile_picture)
                        : asset('assets/adminpic.png'),
            ]
        ]);
    }
}
