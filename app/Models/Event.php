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
    'event_code',
    'title',
    'description',
    'venue',
    'location_id',
    'district_id',
    'event_type_id',
    'start_datetime',
    'end_datetime',
    'max_volunteers',
    'status',
    'created_by',
    ];


    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime'   => 'datetime',

        // ❌ remove these casts: they collide with your accessors below
        // 'start_time'     => 'datetime:H:i',
        // 'end_time'       => 'datetime:H:i',
    ];

    /* ==========================================
       ACCESSORS (VERY IMPORTANT)
    ========================================== */

    public function getStartTimeAttribute()
    {
        return $this->start_datetime
            ? $this->start_datetime->format('H:i')
            : null;
    }

    public function getEndTimeAttribute()
    {
        return $this->end_datetime
            ? $this->end_datetime->format('H:i')
            : null;
    }

    public function getEventDayAttribute()
    {
        return $this->start_datetime
            ? $this->start_datetime->format('l')
            : null;
    }

    /* ==========================================
       RELATIONSHIPS
    ========================================== */

    public function creator()
    {
        return $this->belongsTo(AdminAccount::class, 'created_by', 'admin_id');
    }

    public function attendances()
    {
        return $this->hasMany(EventAttendance::class, 'event_id', 'event_id');
    }

    public function feedbacks()
    {
        return $this->hasMany(EventFeedback::class, 'event_id', 'event_id');
    }

    public function logs()
    {
        return $this->hasMany(EventLog::class, 'event_id', 'event_id');
    }

    public function attendanceImports()
    {
        return $this->hasMany(AttendanceImportLog::class, 'event_id', 'event_id');
    }

    public function organizers()
    {
        return $this->hasMany(EventOrganizer::class, 'event_id', 'event_id');
    }

    public function expectedVolunteers()
    {
        return $this->hasMany(EventExpectedVolunteer::class, 'event_id', 'event_id');
    }

    public function volunteers()
    {
        return $this->hasManyThrough(
            VolunteerProfile::class,
            EventExpectedVolunteer::class,
            'event_id',
            'volunteer_id',
            'event_id',
            'volunteer_id'
        );
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class, 'event_type_id', 'event_type_id');
    }
}
