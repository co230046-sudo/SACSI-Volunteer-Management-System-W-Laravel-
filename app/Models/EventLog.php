<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventLog extends Model
{
    use HasFactory;

    protected $table = 'event_logs';
    protected $primaryKey = 'log_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'admin_id',
        'action',
        'details',
        'timestamp',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];

    // Relation: Belongs to Event
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    // Relation: Belongs to AdminAccount
    public function admin()
    {
        return $this->belongsTo(AdminAccount::class, 'admin_id', 'admin_id');
    }
}
