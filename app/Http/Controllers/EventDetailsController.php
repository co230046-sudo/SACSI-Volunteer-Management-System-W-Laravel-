<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;  // ⬅️ REQUIRED — you forgot this
use App\Models\Event;
use App\Models\EventOrganizer;
use App\Models\EventExpectedVolunteer;
use App\Models\EventAttendance;

class EventDetailsController extends Controller
{
    public function show($event_id)
    {
        $event = Event::with([
            'location',
            'eventType',
            'organizers',
            'expectedVolunteers.volunteer',
            'attendances.volunteer',
        ])->findOrFail($event_id);

        return view('event_details.event_details', compact('event'));
    }

    public function addVolunteers(Request $request, $event_id)
    {
        $request->validate([
            'volunteer_ids' => 'required|array',
            'volunteer_ids.*' => 'integer|exists:volunteers,volunteer_id', // FIXED
        ]);

        foreach ($request->volunteer_ids as $vid) {

            EventExpectedVolunteer::firstOrCreate([
                'event_id'     => $event_id,
                'volunteer_id' => $vid,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Volunteers added successfully',
        ]);
    }

}
