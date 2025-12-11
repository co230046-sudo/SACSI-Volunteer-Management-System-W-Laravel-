<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'full_name'       => 'required|string|max:255',
            'email'           => 'required|email|unique:admin_accounts,email',
            'username'        => 'required|string|unique:admin_accounts,username',
            'contact_number'  => 'nullable|string|max:20',
            'password'        => 'required|confirmed|min:6',
            'role'            => 'required|string',

            // allow tif / tiff
            'profile_picture' => 'nullable|mimes:jpg,jpeg,png,tif,tiff|max:5120',
        ]);

        $profilePath = null;

        if ($request->hasFile('profile_picture')) {
            $profilePath = $request->file('profile_picture')
                ->store('profile_pictures/admin', 'public');
        }

        User::create([
            'full_name'       => $request->full_name,
            'username'        => $request->username,
            'email'           => $request->email,
            'contact_number'  => $request->contact_number,
            'password'        => Hash::make($request->password),
            'role'            => $request->role,
            'profile_picture' => $profilePath,
            'status'          => 'active',
        ]);

        return back()->with('success', 'Admin account registered successfully!');
    }



    /* ============================================================
       ✅ CHANGE PASSWORD CONTROLLER METHOD
    ============================================================ */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'          => 'required',
            'new_password'              => 'required|min:6',
            'new_password_confirmation' => 'required|same:new_password',
        ]);

        $user = Auth::user();

        // ❌ Old password incorrect
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => '❌ Current password is incorrect.'
            ]);
        }

        // ✅ Update password
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Password updated successfully!');
    }

}
