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
        // Validation
        $request->validate([
            'full_name'       => 'required|string|max:255',
            'email'           => 'required|email|unique:admin_accounts,email',
            'username'        => 'required|string|unique:admin_accounts,username',
            'contact_number'  => 'nullable|string|max:20',
            'password'        => 'required|confirmed|min:8',  // You enforce 8-char rule on UI
            'role'            => 'required|string',
            'profile_picture' => 'nullable|mimes:jpg,jpeg,png,tif,tiff|max:5120',
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

        // Redirect back to registration page WITH SUCCESS
        return redirect()
            ->route('admin.register')
            ->with('success', 'Admin account created successfully!');
    }


    /**
     * Change password (optional)
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'          => 'required',
            'new_password'              => 'required|min:8',
            'new_password_confirmation' => 'required|same:new_password',
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
