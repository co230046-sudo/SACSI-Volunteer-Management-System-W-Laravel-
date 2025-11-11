<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminAccount extends Authenticatable
{
    use Notifiable;

    protected $table = 'admin_accounts';
    protected $primaryKey = 'admin_id';
    public $incrementing = true;
    protected $keyType = 'int';

    // Enable automatic created_at and updated_at timestamps
    public $timestamps = true;

    protected $fillable = [
        'username',
        'email',
        'password',
        'profile_picture',
        'role',
        'full_name',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relationships

    public function authenticateLogs()
    {
        return $this->hasMany(AdminAuthenticateLog::class, 'admin_id', 'admin_id');
    }

    public function importLogs()
    {
        return $this->hasMany(ImportLog::class, 'admin_id', 'admin_id');
    }

    public function eventLogs()
    {
        return $this->hasMany(EventLog::class, 'admin_id', 'admin_id');
    }

    public function attendanceImportLogs()
    {
        return $this->hasMany(AttendanceImportLog::class, 'admin_id', 'admin_id');
    }

    public function factLogs()
    {
        return $this->hasMany(FactLog::class, 'admin_id', 'admin_id');
    }

    public function createdEvents()
    {
        return $this->hasMany(Event::class, 'created_by', 'admin_id');
    }
}
