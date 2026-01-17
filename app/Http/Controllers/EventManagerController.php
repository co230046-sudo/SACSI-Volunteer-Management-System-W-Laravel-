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
    // Event Manager page
    public function index(Request $request)
    {
        $now = now('Asia/Manila');

        $base = Event::query()->with('location');

        $cancelledEvents = (clone $base)
            ->where('status', 'cancelled')
            ->orderBy('start_datetime', 'desc')
            ->get();

        $nonCancelled = (clone $base)
            ->where('status', '!=', 'cancelled');

        $upcomingEvents = (clone $nonCancelled)
            ->where('start_datetime', '>', $now)
            ->orderBy('start_datetime', 'asc')
            ->get();

        $ongoingEvents = (clone $nonCancelled)
            ->where('start_datetime', '<=', $now)
            ->where(function ($q) use ($now) {
                $q->whereNull('end_datetime')
                    ->orWhere('end_datetime', '>=', $now);
            })
            ->orderBy('start_datetime', 'asc')
            ->get();

        $completedEvents = (clone $nonCancelled)
            ->whereNotNull('end_datetime')
            ->where('end_datetime', '<', $now)
            ->orderBy('end_datetime', 'desc')
            ->get();

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
                    ->filter(fn ($a) => !empty($a))
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

    // Bulk delete events
    public function bulkDestroy(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return back()->withErrors(['auth' => 'Authentication failed.']);
        }

        $ids = (array) $request->input('event_ids', []);
        $ids = array_values(array_unique(array_filter($ids, fn ($v) => $v !== null && $v !== '')));

        if (count($ids) === 0) {
            return back()->with('error', 'Nothing selected to delete.');
        }

        $events = Event::with('location')->whereIn('event_id', $ids)->get();

        if ($events->isEmpty()) {
            return back()->with('error', 'No events were deleted. They may already be gone.');
        }

        $deleted = 0;

        DB::transaction(function () use ($events, $admin, &$deleted) {
            $adminId = $admin->admin_id ?? null;
            $adminUsername = $admin->username ?? ($admin->name ?? null);

            $count = $events->count();

            // Only per-event delete logs (no bulk summary log)
            foreach ($events as $event) {
                $title = $event->title ?? 'Untitled Event';
                $code  = $event->event_code ?? '—';

                $start = $event->start_datetime ? $event->start_datetime->format('M d, Y h:i A') : '—';
                $end   = $event->end_datetime ? $event->end_datetime->format('M d, Y h:i A') : '—';

                $venue = $event->venue ?? '—';
                $district = $event->location?->district_id ?? $event->district_id ?? '—';
                $barangay = $event->location?->barangay ?? '—';

                $summary = 'Deleted Event - “' . $title . '” (Code: ' . $code . ')';

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
                        'method' => $count > 1 ? 'bulk_delete' : 'delete',
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
                                'method' => $count > 1 ? 'bulk_delete' : 'delete',
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

    // Payload helpers
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
