<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // 🔥 IMPORTANT: Use admin_accounts table
    protected $table = 'admin_accounts';

    protected $fillable = [
    'full_name',
    'email',
    'username',
    'contact_number',
    'password',
    'role',
    'profile_picture',
    'status',
];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
