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

    public function getRouteKeyName()
    {
        return 'organizer_id';
    }

    // ✅ reusable directory: many events via pivot
    public function events()
    {
        return $this->belongsToMany(
            \App\Models\Event::class,
            'event_event_organizer',
            'organizer_id',
            'event_id'
        )->withTimestamps();
    }
}
