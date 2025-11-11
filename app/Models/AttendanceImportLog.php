<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceImportLog extends Model
{
    use HasFactory;

    protected $table = 'attendance_import_logs';
    protected $primaryKey = 'import_id';
    public $incrementing = true;
    public $timestamps = false; // Using import_date instead

    protected $fillable = [
        'event_id',
        'admin_id',
        'filename',
        'total_records',
        'valid_count',
        'invalid_count',
        'import_date',
        'remarks',
    ];

    protected $casts = [
        'import_date' => 'datetime',
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
