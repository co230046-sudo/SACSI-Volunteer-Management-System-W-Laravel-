<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Location;
use Carbon\Carbon;

class HomePageController extends Controller
{
    // Homepage
    public function index()
    {
        $now = Carbon::now();

        // Ongoing events
        $ongoingEvents = Event::query()
            ->with('location')
            ->withCount('expectedVolunteers')
            ->whereNotNull('start_datetime')
            ->where('start_datetime', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_datetime')
                    ->orWhere('end_datetime', '>=', $now);
            })
            ->orderBy('start_datetime', 'asc')
            ->get();

        // Upcoming events
        $upcomingEvents = Event::query()
            ->with('location')
            ->withCount('expectedVolunteers')
            ->whereNotNull('start_datetime')
            ->where('start_datetime', '>', $now)
            ->orderBy('start_datetime', 'asc')
            ->get();

        // Locations (for filters / dropdowns)
        $locations = Location::query()
            ->select('location_id', 'district_id', 'barangay')
            ->whereNotNull('barangay')
            ->orderBy('district_id')
            ->orderBy('barangay')
            ->get();

        $locationsForJs = $locations->map(function ($l) {
            return [
                'id'          => $l->location_id,
                'district_id' => (string) $l->district_id,
                'barangay'    => $l->barangay,
            ];
        })->values()->all();

        $barangaysByDistrict = $locations->groupBy('district_id')->toArray();

        return view('homepage.homepage', compact(
            'ongoingEvents',
            'upcomingEvents',
            'locationsForJs',
            'barangaysByDistrict'
        ));
    }
}
