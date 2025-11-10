<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';
    protected $primaryKey = 'event_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'title',
        'description',
        'venue',
        'location_id',
        'start_datetime',
        'end_datetime',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    // Relation: creator admin
    public function creator()
    {
        return $this->belongsTo(AdminAccount::class, 'created_by', 'admin_id');
    }

    // Relation: Event attendance records
    public function attendances()
    {
        return $this->hasMany(EventAttendance::class, 'event_id', 'event_id');
    }

    // Relation: Event feedbacks
    public function feedbacks()
    {
        return $this->hasMany(EventFeedback::class, 'event_id', 'event_id');
    }

    // Relation: Logs
    public function logs()
    {
        return $this->hasMany(EventLog::class, 'event_id', 'event_id');
    }

    // Relation: Attendance import logs
    public function attendanceImports()
    {
        return $this->hasMany(AttendanceImportLog::class, 'event_id', 'event_id');
    }

    // Relation: Location
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }
}
