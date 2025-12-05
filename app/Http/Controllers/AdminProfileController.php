<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminAccount;
use Illuminate\Support\Facades\Hash;

class AdminProfileController extends Controller
{
    /* ================================
       VIEW ADMIN PROFILE
    ================================= */
public function index(Request $request, $id = null)
{
    auth()->setDefaultDriver('admin');
    auth()->shouldUse('admin');

    $currentAdmin = auth()->user();

    // If URL includes /admin/profile/{id}, ALWAYS load that admin
    if ($id !== null) {
        $admin = AdminAccount::find($id);

        // If admin does not exist → 404
        if (!$admin) {
            abort(404, "Admin not found");
        }
    } 
    else {
        // /admin/profile → load self
        $admin = AdminAccount::find($currentAdmin->admin_id);
    }

    // Detect super admin
    $isSuperAdmin = preg_match('/super/i', $currentAdmin->role);

    // Load all admins only for super admins
    $allAdmins = $isSuperAdmin
        ? AdminAccount::orderBy('full_name')->get()
        : collect([]);

    // Merge logs
    $logs = $admin->authenticateLogs
        ->merge($admin->importLogs)
        ->merge($admin->eventLogs)
        ->merge($admin->attendanceImportLogs)
        ->merge($admin->factLogs)
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
       UPDATE ADMIN PROFILE
    ================================= */
    public function update(Request $request)
    {
        // Force admin guard for updates too
        auth()->setDefaultDriver('admin');
        auth()->shouldUse('admin');

        $currentAdmin = auth()->user();
        $isSuperAdmin = preg_match('/super/i', $currentAdmin->role);

        // SUPER ADMIN CAN MODIFY ANY ACCOUNT
        if ($isSuperAdmin && $request->has('admin_id')) {
            $admin = AdminAccount::find($request->admin_id);
            if (!$admin) {
                $admin = $currentAdmin;
            }
        } else {
            $admin = $currentAdmin;
        }

        // VALIDATION
        $request->validate([
            'full_name'       => 'required|string|max:255',
            'email'           => 'required|email|max:255',
            'contact_number'  => 'nullable|string|max:50',
            'password'        => 'nullable|min:6',
        ]);

        // UPDATE FIELDS
        $admin->full_name      = $request->full_name;
        $admin->email          = $request->email;
        $admin->contact_number = $request->contact_number;

        // SUPER ADMIN ONLY FIELDS
        if ($isSuperAdmin) {
            if ($request->filled('username')) {
                $admin->username = $request->username;
            }
            if ($request->filled('role')) {
                $admin->role = $request->role;
            }
        }

        // UPDATE PASSWORD IF INPUT
        if ($request->filled('password')) {
            $admin->password = Hash::make($request->password);
        }

        // PROFILE PICTURE
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('admin_photos', 'public');
            $admin->profile_picture = $path;
        }

        $admin->save();

        return back()->with('success', 'Profile Updated Successfully!');
    }

    /* ================================
       AJAX → GET LOGS FOR MODAL
    ================================= */
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
}
