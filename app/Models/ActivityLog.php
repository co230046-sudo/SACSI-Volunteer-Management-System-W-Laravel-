<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

ActivityLog::create([
    'title' => 'Database Backup & Maintenance',
    'description' => 'System backup completed successfully'
]);

ActivityLog::create([
    'title' => 'User Account Creation: John Doe',
    'description' => 'Admin created a new user account'
]);

