<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventExpectedVolunteer extends Model
{
    protected $table = 'event_expected_volunteers';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'event_id',
        'volunteer_id',
        'status',
    ];

    protected $casts = [
        'event_id' => 'integer',
        'volunteer_id' => 'integer',
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
