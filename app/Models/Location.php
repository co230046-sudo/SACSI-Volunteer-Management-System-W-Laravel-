<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';
    protected $primaryKey = 'location_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'district',
        'barangay',
        'status',
    ];

    // Relation: Volunteers
    public function volunteerProfiles()
    {
        return $this->hasMany(VolunteerProfile::class, 'location_id', 'location_id');
    }

    // Relation: Events
    public function events()
    {
        return $this->hasMany(Event::class, 'location_id', 'location_id');
    }

    // Relation: Location logs
    public function locationLogs()
    {
        return $this->hasMany(LocationLog::class, 'location_id', 'location_id');
    }
}
