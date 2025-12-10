<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventOrganizer extends Model
{
    // App\Models\EventOrganizer.php
    protected $table = 'event_organizers';
    protected $primaryKey = 'organizer_id';
    public $timestamps = false; // if your table doesn't have created_at/updated_at

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'contact',
    ];
    
    public function getRouteKeyName()
    {
        return 'organizer_id';
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id', 'event_id');
    }
}
