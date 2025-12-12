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
        'course_id',
        'full_name',
        'id_number',
        'year_level',
        'batch_year',   
        'email',
        'contact_number',
        'emergency_contact',
        'fb_messenger',
        'barangay',
        'district',

        'profile_picture_url',
        'profile_picture_path',

        'certificates',
        'class_schedule',
        'notes',
        'status',
    ];

    protected $casts = [
        'batch_year' => 'integer',
    ];

    /**
     * Make avatar_url show in JSON responses automatically
     */
    protected $appends = ['avatar_url'];

    /**
     * Avatar Accessor
     */
    public function getAvatarUrlAttribute()
    {
        // 1. Prefer LOCAL PATH (if file exists)
        if (!empty($this->profile_picture_path)) {
            return asset('storage/' . ltrim($this->profile_picture_path, '/'));
        }

        // 2. Otherwise use STORED URL
        if (!empty($this->profile_picture_url)) {
            return $this->profile_picture_url;
        }

        // 3. Default
        return asset('storage/defaults/default_user.png');
    }



    public function importLog()
    {
        return $this->belongsTo(ImportLog::class, 'import_id', 'import_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id', 'course_id');
    }

    public function eventAttendances()
    {
        return $this->hasMany(EventAttendance::class, 'volunteer_id', 'volunteer_id');
    }

    public function eventFeedbacks()
    {
        return $this->hasMany(EventFeedback::class, 'volunteer_id', 'volunteer_id');
    }
}
