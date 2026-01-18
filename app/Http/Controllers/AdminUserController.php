<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AdminAccount;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    /**
     * Register a new admin account
     */
    public function store(Request $request)
    {
        auth()->shouldUse('admin');

        $currentAdmin = auth('admin')->user();

        // Only super admins can register new admins
        if (!preg_match('/super/i', $currentAdmin->role)) {
            abort(403, 'Unauthorized action.');
        }

        // Validation
        $request->validate([
            'full_name'       => 'required|string|max:255',
            'email'           => 'required|email|unique:admin_accounts,email',
            'username'        => 'required|string|max:100|unique:admin_accounts,username',
            'contact_number'  => 'nullable|string|max:20',
            'password'        => 'required|min:8|confirmed',
            'role'            => 'required|string',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png,tif,tiff|max:5120',
        ]);

        // Upload profile picture
        $profilePath = null;

        if ($request->hasFile('profile_picture')) {
            $profilePath = $request->file('profile_picture')
                ->store('admin_photos', 'public');
        }

        // Create admin
        AdminAccount::create([
            'full_name'       => $request->full_name,
            'username'        => $request->username,
            'email'           => $request->email,
            'contact_number'  => $request->contact_number,
            'password'        => Hash::make($request->password),
            'role'            => $request->role,
            'profile_picture' => $profilePath,
            'status'          => 'active',
        ]);

        // Return back to profile page with success
        return back()->with('success', 'Admin account created successfully!');
    }

    /**
     * Change password (optional)
     */
    public function changePassword(Request $request)
    {
        auth()->shouldUse('admin');

        $request->validate([
            'current_password'          => 'required',
            'new_password'              => 'required|min:8|confirmed',
        ]);

        $user = Auth::guard('admin')->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'Current password is incorrect.',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Password updated successfully!');
    }
}
