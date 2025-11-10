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

    protected $fillable = [
        'username',
        'email',
        'password',
        'profile_picture',
        'role',
        'full_name',
        'status',
    ];

    protected $hidden = ['password'];

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
}
