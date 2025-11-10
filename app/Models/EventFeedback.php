<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventFeedback extends Model
{
    use HasFactory;

    protected $table = 'event_feedbacks';
    protected $primaryKey = 'feedback_id';
    public $incrementing = true;
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'volunteer_id',
        'rating',
        'feedback_text',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
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
