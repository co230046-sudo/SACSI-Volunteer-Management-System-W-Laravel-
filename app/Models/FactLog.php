<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactLog extends Model
{
    use HasFactory;

    protected $table = 'fact_logs';
    protected $primaryKey = 'fact_log_id';
    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'entity_type',
        'entity_id',
        'action',
        'details',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    public function admin()
    {
        return $this->belongsTo(AdminAccount::class, 'admin_id', 'admin_id');
    }
}
