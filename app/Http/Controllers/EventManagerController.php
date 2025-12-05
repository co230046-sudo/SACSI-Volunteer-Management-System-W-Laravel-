<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Location;
use Illuminate\Http\Request;

class EventManagerController extends Controller
{
    public function index(Request $request)
    {
        // ✅ Return all events (JS will paginate/filter/sort; no refresh needed)
        $upcomingEvents = Event::query()
            ->with('location')
            ->where('status', 'planned')
            ->orderBy('start_datetime', 'asc')
            ->get();

        $ongoingEvents = Event::query()
            ->with('location')
            ->where('status', 'ongoing')
            ->orderBy('start_datetime', 'asc')
            ->get();

        $completedEvents = Event::query()
            ->with('location')
            ->where('status', 'completed')
            ->orderBy('start_datetime', 'desc')
            ->get();

        $cancelledEvents = Event::query()
            ->with('location')
            ->where('status', 'cancelled')
            ->orderBy('start_datetime', 'desc')
            ->get();

        // ✅ Only District 1 and 2 + barangays (used by JS autosuggest + auto district)
        $locations = Location::query()
            ->select('district_id', 'barangay')
            ->whereIn('district_id', [1, 2])
            ->whereNotNull('barangay')
            ->where('barangay', '<>', '')
            ->orderBy('district_id')
            ->orderBy('barangay')
            ->get();

        $barangaysByDistrict = $locations
            ->groupBy('district_id')
            ->map(fn ($items) => $items->pluck('barangay')->values()->all())
            ->toArray();

        $defaultTab = $request->query('tab', 'planned');

        return view('manage_event.event_manager', compact(
            'upcomingEvents',
            'ongoingEvents',
            'completedEvents',
            'cancelledEvents',
            'defaultTab',
            'barangaysByDistrict'
        ));
    }

    public function bulkDestroy(Request $request)
    {
        $ids = (array) $request->input('event_ids', []);
        $ids = array_values(array_filter($ids, fn ($v) => $v !== null && $v !== ''));

        if (count($ids) === 0) {
            return back()->with('error', 'Nothing selected to delete.');
        }

        $deleted = Event::whereIn('event_id', $ids)->delete();

        if ($deleted <= 0) {
            return back()->with('error', 'No events were deleted. They may already be gone.');
        }

        return back()->with('success', "Deleted {$deleted} event(s) successfully.");
    }
}
