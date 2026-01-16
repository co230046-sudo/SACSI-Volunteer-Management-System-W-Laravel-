<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAttendance extends Model
{
    protected $table = 'event_attendances';
    protected $primaryKey = 'attendance_id';
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'event_code',
        'volunteer_id',
        'full_name',
        'school_id',
        'school_email',
        'status',
        'source',
        'walk_in',
        'attendance_time',
        'import_batch',
    ];

    protected $casts = [
        'walk_in'         => 'boolean',
        'attendance_time' => 'datetime',
        'import_batch'    => 'string',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    public function volunteer()
    {
        return $this->belongsTo(VolunteerProfile::class, 'volunteer_id', 'volunteer_id');
    }
}
