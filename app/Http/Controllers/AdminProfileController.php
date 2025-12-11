<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminAccount;
use App\Models\FactLog;
use Illuminate\Support\Facades\Hash;

class AdminProfileController extends Controller
{
    /* ================================
       VIEW ADMIN PROFILE (PAGE VIEW)
    ================================ */
    public function index(Request $request, $id = null)
    {
        if (!auth('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $currentAdmin = auth('admin')->user();

        if ($id !== null) {
            $admin = AdminAccount::findOrFail($id);
        } else {
            $admin = AdminAccount::findOrFail($currentAdmin->admin_id);
        }

        $isSuperAdmin = preg_match('/super/i', $currentAdmin->role);

        $allAdmins = $isSuperAdmin
            ? AdminAccount::orderBy('full_name')->get()
            : collect([]);

        $logs = collect()
            ->merge($admin->authenticateLogs ?? collect())
            ->merge($admin->importLogs ?? collect())
            ->merge($admin->eventLogs ?? collect())
            ->merge($admin->attendanceImportLogs ?? collect())
            ->merge($admin->factLogs ?? collect())
            ->sortByDesc('created_at')
            ->values();

        return view('admin.profile', compact(
            'admin',
            'logs',
            'allAdmins',
            'currentAdmin'
        ));
    }

    /* ================================
       ✅ UPDATE ADMIN PROFILE (FULL FIX)
    ================================ */
    public function update(Request $request)
    {
        auth()->shouldUse('admin');
        $currentAdmin = auth()->user();
        $isSuperAdmin = preg_match('/super/i', $currentAdmin->role);

        if ($isSuperAdmin && $request->has('admin_id')) {
            $admin = AdminAccount::find($request->admin_id) ?? $currentAdmin;
        } else {
            $admin = $currentAdmin;
        }

        // ✅ VALIDATION
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'contact_number' => 'nullable|string|max:50',

            // ✅ Password modal validation
            'current_password' => 'nullable',
            'new_password' => 'nullable|min:6|confirmed',
        ]);

        /* ================================
           ✅ AUTO CHANGE LOGGING
        ================================ */

        if ($admin->full_name !== $request->full_name) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Name Changed',
                'details'     => 'From "' . $admin->full_name . '" to "' . $request->full_name . '"'
            ]);
        }

        if ($admin->email !== $request->email) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Email Changed',
                'details'     => 'From "' . $admin->email . '" to "' . $request->email . '"'
            ]);
        }

        if ($admin->contact_number !== $request->contact_number) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Contact Updated',
                'details'     => 'From "' . ($admin->contact_number ?? 'None') . '" to "' . ($request->contact_number ?? 'None') . '"'
            ]);
        }

        if ($isSuperAdmin && $request->filled('username') && $admin->username !== $request->username) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Username Changed',
                'details'     => 'From "' . $admin->username . '" to "' . $request->username . '"'
            ]);
        }

        if ($isSuperAdmin && $request->filled('role') && $admin->role !== $request->role) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Role Changed',
                'details'     => 'From "' . $admin->role . '" to "' . $request->role . '"'
            ]);
        }

        /* ================================
           ✅ SECURE PASSWORD UPDATE (MODAL)
        ================================ */
        if ($request->filled('current_password') || $request->filled('new_password')) {

            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:6|confirmed',
            ]);

            if (!Hash::check($request->current_password, $admin->password)) {
                return back()->withErrors([
                    'current_password' => 'Current password is incorrect.'
                ]);
            }

            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Password Updated',
                'details'     => 'Password was updated securely'
            ]);

            $admin->password = Hash::make($request->new_password);
        }

        /* ================================
           ✅ PROFILE PHOTO UPDATE
        ================================ */
        if ($request->hasFile('photo')) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Profile Photo Updated',
                'details'     => 'Profile photo was changed'
            ]);

            $path = $request->file('photo')->store('admin_photos', 'public');
            $admin->profile_picture = $path;
        }

        /* ================================
           ✅ FINAL DATA SAVE
        ================================ */
        $admin->full_name = $request->full_name;
        $admin->email = $request->email;
        $admin->contact_number = $request->contact_number;

        if ($isSuperAdmin && $request->filled('username')) {
            $admin->username = $request->username;
        }

        if ($isSuperAdmin && $request->filled('role')) {
            $admin->role = $request->role;
        }

        $admin->save();

        return back()->with('success', 'Profile Updated Successfully!');
    }

    /* ================================
       ✅ AJAX → GET LOGS
    ================================ */
    public function getLogs($id)
    {
        auth()->shouldUse('admin');

        $admin = AdminAccount::find($id);

        if (!$admin) {
            return response()->json([
                'name' => 'Unknown Admin',
                'logs' => []
            ]);
        }

        $logs = $admin->authenticateLogs
            ->merge($admin->importLogs)
            ->merge($admin->eventLogs)
            ->merge($admin->attendanceImportLogs)
            ->merge($admin->factLogs)
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'name' => $admin->full_name,
            'logs' => $logs
        ]);
    }

    /* ================================
       ✅ AJAX → VIEW ADMIN PROFILE
    ================================ */
    public function viewProfile($id)
    {
        auth()->shouldUse('admin');

        $admin = AdminAccount::find($id);

        if (!$admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        return response()->json([
            'admin_id' => $admin->admin_id,
            'username' => $admin->username,
            'full_name' => $admin->full_name,
            'email' => $admin->email,
            'role' => $admin->role,
            'contact_number' => $admin->contact_number,
            'created_at' => $admin->created_at->format('M d, Y'),
            'profile_picture' => $admin->profile_picture
                ? asset('storage/'.$admin->profile_picture)
                : asset('assets/adminpic.png'),
        ]);
    }
}
