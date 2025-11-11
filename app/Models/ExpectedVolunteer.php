<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpectedVolunteer extends Model
{
    use HasFactory;

    protected $table = 'event_expected_volunteers';
    protected $primaryKey = 'id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'event_id',
        'volunteer_id',
        'status',
    ];

    // Relation: Belongs to Event
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }

    // Relation: Belongs to VolunteerProfile
    public function volunteer()
    {
        return $this->belongsTo(VolunteerProfile::class, 'volunteer_id', 'volunteer_id');
    }
}
