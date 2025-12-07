<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Location;
use App\Models\EventLog;
use App\Models\FactLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventManagerController extends Controller
{
    public function index(Request $request)
    {
        $now = now();

        // Base query with relation
        $base = Event::query()->with('location');

        // CANCELLED – always by explicit status
        $cancelledEvents = (clone $base)
            ->where('status', 'cancelled')
            ->orderBy('start_datetime', 'desc')
            ->get();

        // All NON-cancelled events – we'll split these by dates
        $nonCancelled = (clone $base)
            ->where('status', '!=', 'cancelled');

        // UPCOMING: starts in the future
        $upcomingEvents = (clone $nonCancelled)
            ->where('start_datetime', '>', $now)
            ->orderBy('start_datetime', 'asc')
            ->get();

        // ONGOING: already started, not yet finished (or no end)
        $ongoingEvents = (clone $nonCancelled)
            ->where('start_datetime', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_datetime')
                    ->orWhere('end_datetime', '>=', $now);
            })
            ->orderBy('start_datetime', 'asc')
            ->get();

        // COMPLETED: ended in the past
        $completedEvents = (clone $nonCancelled)
            ->whereNotNull('end_datetime')
            ->where('end_datetime', '<', $now)
            ->orderBy('end_datetime', 'desc')
            ->get();

        // ✅ Only District 1 and 2 barangays (used by JS autosuggest + auto district)
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
            ->map(fn($items) => $items->pluck('barangay')->values()->all())
            ->toArray();

        // Which tab should be active when page loads
        $defaultTab = $request->query('tab', 'planned');

        /*
        |--------------------------------------------------------------
        | Event Activity Log (EventLogs only) – DB side
        |   JS will do the actual filtering in the modal.
        |   These query params are kept so you *can* deep-link if needed.
        |--------------------------------------------------------------
        */
        $logAction    = $request->query('log_action');   // e.g. "Create", "Edit"
        $logSearch    = $request->query('log_search');   // free-text search
        $logStartDate = $request->query('log_start');    // yyyy-mm-dd
        $logEndDate   = $request->query('log_end');      // yyyy-mm-dd

        $eventLogsQuery = EventLog::with('admin');

        // Filter by action
        if (!empty($logAction)) {
            $eventLogsQuery->where('action', $logAction);
        }

        // Search in action + details
        if (!empty($logSearch)) {
            $s = '%' . $logSearch . '%';
            $eventLogsQuery->where(function ($q) use ($s) {
                $q->where('details', 'like', $s)
                    ->orWhere('action', 'like', $s);
            });
        }

        // Date range (inclusive)
        if (!empty($logStartDate)) {
            $start = \Carbon\Carbon::parse($logStartDate)->startOfDay();
            $eventLogsQuery->where('timestamp', '>=', $start);
        }

        if (!empty($logEndDate)) {
            $end = \Carbon\Carbon::parse($logEndDate)->endOfDay();
            $eventLogsQuery->where('timestamp', '<=', $end);
        }

        $eventLogs = $eventLogsQuery
            ->orderBy('timestamp', 'desc')
            ->limit(200)
            ->get();

        // Distinct actions for dropdown (+ make sure core actions always exist)
        $eventLogActions = collect(['Create', 'Edit', 'Cancel', 'Restore', 'Delete'])
            ->merge(
                EventLog::select('action')
                    ->distinct()
                    ->orderBy('action')
                    ->pluck('action')
                    ->filter(fn($a) => !empty($a))
            )
            ->unique()
            ->values();

        return view('manage_event.event_manager', compact(
            'upcomingEvents',
            'ongoingEvents',
            'completedEvents',
            'cancelledEvents',
            'defaultTab',
            'barangaysByDistrict',
            'eventLogs',
            'eventLogActions'
        ));
    }

    public function bulkDestroy(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $ids = (array)$request->input('event_ids', []);
        $ids = array_values(array_filter($ids, fn($v) => $v !== null && $v !== ''));

        if (count($ids) === 0) {
            return back()->with('error', 'Nothing selected to delete.');
        }

        // Get events so we can log details
        $events = Event::whereIn('event_id', $ids)->get();

        if ($events->isEmpty()) {
            return back()->with('error', 'No events were deleted. They may already be gone.');
        }

        $deleted = 0;

        DB::transaction(function () use ($events, $admin, &$deleted) {
            foreach ($events as $event) {
                // ✅ Human-readable EventLog (shown in Event Activity Log modal)
                EventLog::create([
                    'event_id'  => $event->event_id,
                    'admin_id'  => $admin->admin_id ?? null,
                    'action'    => 'Delete',
                    'details'   => sprintf(
                        'Deleted event "%s" (Event ID: %d, Code: %s) via bulk delete.',
                        $event->title ?? 'Untitled Event',
                        $event->event_id,
                        $event->event_code ?? '—'
                    ),
                    'timestamp' => now(),
                ]);

                // ✅ Silent FactLog (kept in DB, not shown in this UI yet)
                FactLog::create([
                    'admin_id'    => $admin->admin_id ?? null,
                    'entity_type' => 'Event',
                    'entity_id'   => $event->event_id,
                    'action'      => 'Bulk Delete',
                    'details'     => json_encode([
                        'event_id'   => $event->event_id,
                        'event_code' => $event->event_code,
                        'title'      => $event->title,
                    ], JSON_UNESCAPED_UNICODE),
                    'timestamp'   => now(),
                    'import_id'   => null,
                ]);

                $event->delete();
                $deleted++;
            }
        });

        return back()->with('success', "Deleted {$deleted} event(s) successfully.");
    }
}
