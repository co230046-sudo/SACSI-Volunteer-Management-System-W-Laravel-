<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAttendance extends Model
{
    use HasFactory;

    protected $table = 'event_attendance';
    protected $primaryKey = 'attendance_id'; // assuming your PK is named like this
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'event_id',
        'volunteer_id',
        'attendance_status',
        'hours_rendered',
        'remarks',
        'certificate_link',
    ];

    // Relation: Event
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    // Relation: Volunteer
    public function volunteer()
    {
        return $this->belongsTo(VolunteerProfile::class, 'volunteer_id', 'volunteer_id');
    }
}
