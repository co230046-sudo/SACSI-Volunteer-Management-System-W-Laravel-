<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAttendance extends Model
{
    protected $table = 'event_attendances';
    protected $primaryKey = 'attendance_id';
    protected $fillable = ['event_id','volunteer_id','status','attendance_time'];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function volunteer()
    {
        return $this->belongsTo(VolunteerProfile::class, 'volunteer_id');
    }
}
