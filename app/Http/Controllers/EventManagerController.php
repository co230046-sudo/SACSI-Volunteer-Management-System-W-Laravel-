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
        // ✅ Make sure time comparisons match your scheduler + app timezone
        // (I used Asia/Manila here since that’s what your project is using.)
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
        | NOTE: We keep the same filters, but the DETAILS are now normalized JSON
        | (same structure as CreateEventController + EventDetailsController).
        | Your UI can still show a clean "summary" but you also keep consistent data
        | for future developers.
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
            // For consistency, we always store actor in the normalized payload.
            $adminId = $admin->admin_id ?? null;
            $adminUsername = $admin->username ?? ($admin->name ?? null);
            $adminNameForText = $admin->name ?? $admin->username ?? ('Admin #' . ($adminId ?? '—'));

            // Small summary: avoid making it super long but keep it helpful
            $summaryTitles = $events->pluck('title')->filter()->take(10)->values()->all();
            $summaryLine = count($events) > 10
                ? implode(', ', $summaryTitles) . ' (and ' . (count($events) - 10) . ' more)'
                : implode(', ', $summaryTitles);

            // 👇 Normalized BULK payload (same "shape" as your other controllers)
            $bulkSummary = "Admin {$adminNameForText} bulk deleted {$events->count()} event(s).";

            $bulkPayload = $this->eventPayload(
                type: 'event.bulk_deleted',
                summary: $bulkSummary,
                event: $events->first(), // just to attach an event-like signature (id/code/title)
                adminId: $adminId,
                adminUsername: $adminUsername,
                data: [
                    // Extra info for devs (still student-ish + readable)
                    'count' => $events->count(),
                    'event_ids' => $events->pluck('event_id')->values()->all(),
                    'title_preview' => $summaryLine ?: null,
                    'method' => 'bulk_delete',
                ]
            );

            // Save bulk summary into EventLog (ties to the first event so it appears somewhere)
            EventLog::create([
                'event_id'  => $events->first()->event_id,
                'admin_id'  => $adminId,
                'action'    => 'Bulk Delete',
                'details'   => json_encode($bulkPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timestamp' => now(),
            ]);

            // Save bulk summary into FactLog (entity_id null means "bulk / global action")
            FactLog::create([
                'admin_id'    => $adminId,
                'entity_type' => 'Event',
                'entity_id'   => null,
                'action'      => 'Bulk Delete',
                'details'     => json_encode(
                    $this->factPayload(
                        type: 'event.bulk_deleted',
                        summary: $bulkSummary,
                        adminId: $adminId,
                        adminUsername: $adminUsername,
                        data: [
                            'count' => $events->count(),
                            'event_ids' => $events->pluck('event_id')->values()->all(),
                            'title_preview' => $summaryLine ?: null,
                            'method' => 'bulk_delete',
                        ]
                    ),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'timestamp'   => now(),
            ]);

            // Per-event delete logs (so when you open one event’s history, it still shows exactly what happened)
            foreach ($events as $event) {
                $title = $event->title ?? 'Untitled Event';
                $code  = $event->event_code ?? '—';

                $start = $event->start_datetime ? $event->start_datetime->format('M d, Y h:i A') : '—';
                $end   = $event->end_datetime ? $event->end_datetime->format('M d, Y h:i A') : '—';

                $venue = $event->venue ?? '—';
                $district = $event->location?->district_id ?? $event->district_id ?? '—';
                $barangay = $event->location?->barangay ?? '—';

                // Friendly “student-ish” summary, but still useful for devs
                $summary = "Admin {$adminNameForText} deleted event “{$title}” (Code: {$code}).";

                // Normalized per-event payload
                $payload = $this->eventPayload(
                    type: 'event.deleted',
                    summary: $summary,
                    event: $event,
                    adminId: $adminId,
                    adminUsername: $adminUsername,
                    data: [
                        'event' => [
                            'id' => $event->event_id,
                            'code' => $event->event_code,
                            'title' => $title,
                            'start' => $start !== '—' ? $start : null,
                            'end' => $end !== '—' ? $end : null,
                            'venue' => $venue !== '—' ? $venue : null,
                            'barangay' => $barangay !== '—' ? $barangay : null,
                            'district' => $district !== '—' ? $district : null,
                        ],
                        'method' => 'bulk_delete',
                    ]
                );

                EventLog::create([
                    'event_id'  => $event->event_id,
                    'admin_id'  => $adminId,
                    'action'    => 'Delete',
                    'details'   => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'timestamp' => now(),
                ]);

                FactLog::create([
                    'admin_id'    => $adminId,
                    'entity_type' => 'Event',
                    'entity_id'   => $event->event_id,
                    'action'      => 'Delete',
                    'details'     => json_encode(
                        $this->factPayload(
                            type: 'event.deleted',
                            summary: $summary,
                            adminId: $adminId,
                            adminUsername: $adminUsername,
                            data: [
                                'event' => [
                                    'id' => $event->event_id,
                                    'code' => $event->event_code,
                                    'title' => $title,
                                ],
                                'method' => 'bulk_delete',
                            ]
                        ),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'timestamp'   => now(),
                ]);

                $event->delete();
                $deleted++;
            }
        });

        return back()->with('success', "Deleted {$deleted} event(s) successfully.");
    }

    // ------------------------------------------------------------------
    // Normalized payload helpers (same style as CreateEventController / EventDetailsController)
    // ------------------------------------------------------------------

    private function factPayload(string $type, ?string $summary, ?int $adminId, ?string $adminUsername, array $data = []): array
    {
        return array_merge([
            'version' => 1,
            'type'    => $type,
            'summary' => $summary,
            'actor'   => [
                'admin_id' => $adminId,
                'username' => $adminUsername,
            ],
            // Keeping this small but helpful (future dev can still debug)
            'meta' => [
                'ip' => request()->ip(),
                'ua' => substr((string) request()->userAgent(), 0, 255),
            ],
            'at' => now()->toIso8601String(),
        ], $data);
    }

    private function eventPayload(string $type, ?string $summary, Event $event, ?int $adminId, ?string $adminUsername, array $data = []): array
    {
        return array_merge([
            'version' => 1,
            'type'    => $type,
            'summary' => $summary,
            'event'   => [
                'id'    => $event->event_id,
                'code'  => $event->event_code,
                'title' => $event->title,
            ],
            'actor' => [
                'admin_id' => $adminId,
                'username' => $adminUsername,
            ],
            'at' => now()->toIso8601String(),
        ], $data);
    }
}
