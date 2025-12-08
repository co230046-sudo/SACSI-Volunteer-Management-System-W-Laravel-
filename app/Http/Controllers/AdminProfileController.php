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
        // ✅ ALWAYS use guard directly
        if (!auth('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $currentAdmin = auth('admin')->user();

        // ✅ Load selected admin or self
        if ($id !== null) {
            $admin = AdminAccount::findOrFail($id);
        } else {
            $admin = AdminAccount::findOrFail($currentAdmin->admin_id);
        }

        // ✅ Super admin check
        $isSuperAdmin = preg_match('/super/i', $currentAdmin->role);

        // ✅ Load all admins only for super admins
        $allAdmins = $isSuperAdmin
            ? AdminAccount::orderBy('full_name')->get()
            : collect([]);

        // ✅ SAFE log merging (prevents crashes)
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
       UPDATE ADMIN PROFILE (AUTO LOGGING)
    ================================ */
    public function update(Request $request)
    {
        auth()->setDefaultDriver('admin');
        auth()->shouldUse('admin');

        $currentAdmin = auth()->user();
        $isSuperAdmin = preg_match('/super/i', $currentAdmin->role);

        // Super admin can update any admin
        if ($isSuperAdmin && $request->has('admin_id')) {
            $admin = AdminAccount::find($request->admin_id) ?? $currentAdmin;
        } else {
            $admin = $currentAdmin;
        }

        // Validation
        $request->validate([
            'full_name'      => 'required|string|max:255',
            'email'          => 'required|email|max:255',
            'contact_number' => 'nullable|string|max:50',
            'password'       => 'nullable|min:6',
        ]);

        /* ================================
           ✅ AUTO LOG CHANGES
        ================================ */

        // ✅ NAME CHANGE
        if ($admin->full_name !== $request->full_name) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Name Changed',
                'details'     => 'From "' . $admin->full_name . '" to "' . $request->full_name . '"'
            ]);
        }

        // ✅ EMAIL CHANGE
        if ($admin->email !== $request->email) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Email Changed',
                'details'     => 'From "' . $admin->email . '" to "' . $request->email . '"'
            ]);
        }

        // ✅ CONTACT CHANGE
        if ($admin->contact_number !== $request->contact_number) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Contact Updated',
                'details'     => 'From "' . ($admin->contact_number ?? 'None') . '" to "' . ($request->contact_number ?? 'None') . '"'
            ]);
        }

        // ✅ USERNAME CHANGE (SUPER ADMIN)
        if ($isSuperAdmin && $request->filled('username') && $admin->username !== $request->username) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Username Changed',
                'details'     => 'From "' . $admin->username . '" to "' . $request->username . '"'
            ]);
        }

        // ✅ ROLE CHANGE (SUPER ADMIN)
        if ($isSuperAdmin && $request->filled('role') && $admin->role !== $request->role) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Role Changed',
                'details'     => 'From "' . $admin->role . '" to "' . $request->role . '"'
            ]);
        }

        // ✅ PASSWORD UPDATE (SECURE)
        if ($request->filled('password')) {
            FactLog::create([
                'admin_id'    => $currentAdmin->admin_id,
                'entity_type' => 'Admin Profile',
                'entity_id'   => $admin->admin_id,
                'action'      => 'Password Updated',
                'details'     => 'Password was updated'
            ]);

            $admin->password = Hash::make($request->password);
        }

        // ✅ PROFILE PHOTO UPDATE
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
           ✅ FINAL DATA UPDATE
        ================================ */
        $admin->full_name      = $request->full_name;
        $admin->email          = $request->email;
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
       AJAX → GET LOGS FOR MODAL
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
       ✅ AJAX → VIEW ADMIN PROFILE (MODAL)
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
