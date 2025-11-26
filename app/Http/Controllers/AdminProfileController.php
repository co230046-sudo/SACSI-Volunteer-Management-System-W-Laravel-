<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminProfileController extends Controller
{
    public function index()
    {
        // Logged-in admin user from admin_accounts table
        $admin = Auth::guard('admin')->user();

        return view('admin.profile', compact('admin'));
    }
}
