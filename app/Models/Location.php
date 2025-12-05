<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = 'locations';
    protected $primaryKey = 'location_id';

    protected $fillable = [
        'district_id',
        'zone_name',   // optional if column exists
        'barangay',
        'status',      // optional if column exists
    ];

    public function volunteerProfiles()
    {
        return $this->hasMany(VolunteerProfile::class, 'location_id', 'location_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'location_id', 'location_id');
    }
}
