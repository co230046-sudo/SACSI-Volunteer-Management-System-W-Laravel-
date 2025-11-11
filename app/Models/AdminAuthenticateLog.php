<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminAuthenticateLog extends Model
{
    use HasFactory;

    protected $table = 'admin_authenticate_logs';
    protected $primaryKey = 'log_id';
    public $incrementing = true;
    public $timestamps = true; // Enable created_at & updated_at

    protected $fillable = [
        'admin_id',
        'login_time', // semantic timestamp
        'ip_address',
        'status',
        'reason',
    ];

    protected $casts = [
        'login_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relation: Belongs to AdminAccount
    public function admin()
    {
        return $this->belongsTo(AdminAccount::class, 'admin_id', 'admin_id');
    }
}
