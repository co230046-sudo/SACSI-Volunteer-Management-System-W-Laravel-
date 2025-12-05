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
        'event_code',
        'volunteer_id',
        'full_name',
        'school_id',
        'school_email',
        'rating',
        'improve_next_time',
        'issues_encountered',
        'other_comments',
        'feedback_text',     // keep for backward compatibility if you want
        'submitted_at',
        'import_batch',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function event() { return $this->belongsTo(Event::class, 'event_id', 'event_id'); }
    public function volunteer() { return $this->belongsTo(VolunteerProfile::class, 'volunteer_id', 'volunteer_id'); }
}
