<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventAttendance extends Model
{
    protected $table = 'event_attendances';
    protected $primaryKey = 'attendance_id';
    public $timestamps = false; // your migration doesn't add created_at/updated_at

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
}
