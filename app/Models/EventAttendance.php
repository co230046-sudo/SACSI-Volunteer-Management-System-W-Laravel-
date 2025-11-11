<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAttendance extends Model
{
    use HasFactory;

    protected $table = 'event_attendances';
    protected $primaryKey = 'attendance_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'event_id',
        'volunteer_id',
        'status',        // matches the migration column
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
