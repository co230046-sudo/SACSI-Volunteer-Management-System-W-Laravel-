<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventOrganizer extends Model
{
    protected $table = 'event_organizers';
    protected $primaryKey = 'organizer_id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'email',
        'contact',
    ];

    // Optional: route model binding key
    public function getRouteKeyName()
    {
        return 'organizer_id';
    }

    /**
     * Many organizers can belong to many events
     * Pivot table: event_event_organizer
     */
    public function events()
    {
        return $this->belongsToMany(
            Event::class,
            'event_event_organizer',
            'organizer_id',
            'event_id'
        )->withTimestamps();
    }
}
