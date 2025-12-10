<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Location;
use App\Models\EventLog;
use App\Models\FactLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventManagerController extends Controller
{
    public function index(Request $request)
    {
        // ✅ Make sure your time comparisons match your scheduler + app timezone
        $now = now('Asia/Manila');

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
        |--------------------------------------------------------------
        */
        $logAction    = $request->query('log_action');
        $logSearch    = $request->query('log_search');
        $logStartDate = $request->query('log_start');
        $logEndDate   = $request->query('log_end');

        $eventLogsQuery = EventLog::with('admin');

        if (!empty($logAction)) {
            $eventLogsQuery->where('action', $logAction);
        }

        if (!empty($logSearch)) {
            $s = '%' . $logSearch . '%';
            $eventLogsQuery->where(function ($q) use ($s) {
                $q->where('details', 'like', $s)
                    ->orWhere('action', 'like', $s);
            });
        }

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

        $eventLogActions = collect(['Create', 'Edit', 'Cancel', 'Restore', 'Delete', 'Bulk Delete'])
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
        if (!$admin) {
            return back()->withErrors(['auth' => 'Authentication failed.']);
        }

        $ids = (array) $request->input('event_ids', []);
        $ids = array_values(array_unique(array_filter($ids, fn($v) => $v !== null && $v !== '')));

        if (count($ids) === 0) {
            return back()->with('error', 'Nothing selected to delete.');
        }

        // Load events + location (for readable logs)
        $events = Event::with('location')->whereIn('event_id', $ids)->get();

        if ($events->isEmpty()) {
            return back()->with('error', 'No events were deleted. They may already be gone.');
        }

        $deleted = 0;

        DB::transaction(function () use ($events, $admin, &$deleted) {
            $adminName = $admin->name ?? $admin->username ?? ('Admin #' . ($admin->admin_id ?? '—'));

            $summaryTitles = $events->pluck('title')->filter()->take(10)->values()->all();
            $summaryLine = count($events) > 10
                ? implode(', ', $summaryTitles) . ' (and ' . (count($events) - 10) . ' more)'
                : implode(', ', $summaryTitles);

            $bulkDetails = 'Bulk delete executed by ' . $adminName . '. '
                . 'Deleted ' . $events->count() . ' event(s). '
                . 'Event IDs: ' . implode(', ', $events->pluck('event_id')->all()) . '. '
                . ($summaryLine ? 'Titles: ' . $summaryLine . '.' : '');

            EventLog::create([
                'event_id'  => $events->first()->event_id,
                'admin_id'  => $admin->admin_id ?? null,
                'action'    => 'Bulk Delete',
                'details'   => $bulkDetails,
                'timestamp' => now(),
            ]);

            FactLog::create([
                'admin_id'    => $admin->admin_id ?? null,
                'entity_type' => 'Event',
                'entity_id'   => null,
                'action'      => 'Bulk Delete',
                'details'     => $bulkDetails,
                'timestamp'   => now(),
            ]);

            foreach ($events as $event) {
                $title = $event->title ?? 'Untitled Event';
                $code  = $event->event_code ?? '—';

                $start = $event->start_datetime ? $event->start_datetime->format('M d, Y h:i A') : '—';
                $end   = $event->end_datetime ? $event->end_datetime->format('M d, Y h:i A') : '—';

                $venue = $event->venue ?? '—';
                $district = $event->location?->district_id ?? $event->district_id ?? '—';
                $barangay = $event->location?->barangay ?? '—';

                $details = 'Event permanently deleted. '
                    . 'Title: "' . $title . '". '
                    . 'Code: ' . $code . '. '
                    . 'Event ID: ' . $event->event_id . '. '
                    . 'Start: ' . $start . '. '
                    . 'End: ' . $end . '. '
                    . 'Venue: ' . $venue . '. '
                    . 'Barangay: ' . $barangay . '. '
                    . 'District: ' . $district . '. '
                    . 'Method: Bulk delete. '
                    . 'Deleted by: ' . $adminName . '.';

                EventLog::create([
                    'event_id'  => $event->event_id,
                    'admin_id'  => $admin->admin_id ?? null,
                    'action'    => 'Delete',
                    'details'   => $details,
                    'timestamp' => now(),
                ]);

                FactLog::create([
                    'admin_id'    => $admin->admin_id ?? null,
                    'entity_type' => 'Event',
                    'entity_id'   => $event->event_id,
                    'action'      => 'Delete',
                    'details'     => $details,
                    'timestamp'   => now(),
                ]);

                $event->delete();
                $deleted++;
            }
        });

        return back()->with('success', "Deleted {$deleted} event(s) successfully.");
    }
}
