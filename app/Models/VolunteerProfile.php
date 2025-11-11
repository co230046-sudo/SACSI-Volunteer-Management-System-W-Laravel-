<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VolunteerProfile extends Model
{
    use HasFactory;

    protected $table = 'volunteer_profiles';
    protected $primaryKey = 'volunteer_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'import_id',
        'location_id',
        'course_id', // updated to match schema
        'full_name',
        'id_number',
        'school_id',
        'year_level',
        'email',
        'contact_number',
        'emergency_contact',
        'fb_messenger',
        'certificates',
        'class_schedule',
        'status',
        'notes',
    ];

    // Relation: Import log
    public function importLog()
    {
        return $this->belongsTo(ImportLog::class, 'import_id', 'import_id');
    }

    // Relation: Location
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    // Relation: Course
    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    // Relation: Event Attendances
    public function eventAttendances()
    {
        return $this->hasMany(EventAttendance::class, 'volunteer_id', 'volunteer_id');
    }

    // Relation: Event Feedbacks
    public function eventFeedbacks()
    {
        return $this->hasMany(EventFeedback::class, 'volunteer_id', 'volunteer_id');
    }
}
