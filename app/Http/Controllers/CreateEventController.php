<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use App\Models\Event;
use App\Models\Location;
use App\Models\EventType;
use App\Models\EventOrganizer;
use App\Models\EventExpectedVolunteer;
use App\Models\EventLog;
use App\Models\FactLog;
use Carbon\Carbon;

// add at top
use App\Services\FactLogger;

class CreateEventController extends Controller
{
    private FactLogger $factLogger;

    public function __construct(FactLogger $factLogger)
    {
        $this->factLogger = $factLogger;
    }

    // Views / constants
    private const EVENT_DETAILS_VIEW = 'event_details.event_details';

    // Event statuses
    private const STATUS_PLANNED   = 'planned';
    private const STATUS_ONGOING   = 'ongoing';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_CANCELLED = 'cancelled';

    // Create Event (page)
    public function create()
    {
        return view('create_event.create_event', [
            'eventTypes' => EventType::orderBy('label')->get(),
            'locations'  => Location::orderBy('barangay')->get(),
            'event'      => null,
            'isEdit'     => false,
        ]);
    }

    // Redirect to event details page
    public function show(Event $event)
    {
        return redirect()->route('event.details.show', $event->event_id);
    }

    // Store Event
    public function store(Request $request)
    {
        session()->put('event_form_data', $request->all());

        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'venue'          => 'nullable|string|max:255',

            'location_id'    => 'required|integer|exists:locations,location_id',
            'district_id'    => 'nullable|integer',

            'event_type_id'  => 'required|integer|exists:event_types,event_type_id',

            'start_datetime' => 'required|date',
            'end_datetime'   => 'required|date|after:start_datetime',

            'max_volunteers' => 'nullable|integer|min:0',

            'organizers.name'      => 'required|array|min:1|max:3',
            'organizers.email'     => 'nullable|array|max:3',
            'organizers.contact'   => 'nullable|array|max:3',

            'organizers.name.*'    => 'nullable|string|max:255',
            'organizers.email.*'   => 'nullable|email|max:255',
            'organizers.contact.*' => 'nullable|string|max:255',

            'force_create'   => 'nullable|in:0,1',
        ]);

        $normalizedOrganizers = $this->normalizeOrganizerInput($request);

        if (count($normalizedOrganizers) < 1) {
            return back()->withErrors(['organizers.name' => 'Please provide at least one organizer name.'])->withInput();
        }

        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return back()->withErrors(['auth' => 'Authentication failed.'])->withInput();
        }

        // Duplicate check (allow override with force_create=1)
        $force = $request->input('force_create') === '1';
        $possibleDup = Event::query()
            ->where('title', $request->title)
            ->where('location_id', $request->location_id)
            ->where('start_datetime', $request->start_datetime)
            ->first();

        if ($possibleDup && !$force) {
            $barangayName = Location::where('location_id', $possibleDup->location_id)->value('barangay');

            $startStr = $possibleDup->start_datetime
                ? Carbon::parse($possibleDup->start_datetime)->format('Y-m-d H:i')
                : '';

            return back()
                ->with('duplicate_event', [
                    'title'    => $possibleDup->title,
                    'start'    => $startStr,
                    'barangay' => $barangayName ?? ('ID ' . $possibleDup->location_id),
                ])
                ->withInput();
        }

        $eventCode = $this->generateUniqueEventCode();

        try {
            DB::beginTransaction();

            $eventData = [
                'event_code'     => $eventCode,
                'title'          => $request->title,
                'description'    => $request->description,
                'venue'          => $request->venue,
                'location_id'    => $request->location_id,
                'district_id'    => $request->district_id,
                'event_type_id'  => $request->event_type_id,
                'start_datetime' => $request->start_datetime,
                'end_datetime'   => $request->end_datetime,
                'status'         => self::STATUS_PLANNED,
                'created_by'     => $admin->admin_id,
            ];

            if (Schema::hasColumn('events', 'max_volunteers')) {
                $eventData['max_volunteers'] = $request->input('max_volunteers');
            }

            $event = Event::create($eventData);

            $organizerIds = [];

            foreach ($normalizedOrganizers as $org) {

                $email = $org['email'] ? strtolower($org['email']) : null;
                $name  = strtolower(trim($org['name']));

                $organizer = EventOrganizer::where(function ($q) use ($email, $name) {
                    if ($email) {
                        $q->whereRaw('LOWER(TRIM(email)) = ?', [$email]);
                    } else {
                        $q->whereRaw('LOWER(TRIM(name)) = ?', [$name]);
                    }
                })->first();


                if (!$organizer) {
                    $organizer = EventOrganizer::create([
                        'name'    => $org['name'],
                        'email' => $org['email'] ?: null,
                        'contact' => $org['contact'] ?: null,
                    ]);
                }

                $organizerIds[] = $organizer->organizer_id;
            }

            $event->organizers()->sync($organizerIds);


            // Logs
            $summary = 'Created Event - "' . $event->title . '" (Code: ' . $event->event_code . ')';

            $eventLogPayload = $this->eventPayload(
                type: 'event.created',
                summary: $summary,
                event: $event,
                adminId: $admin->admin_id,
                adminUsername: $admin->username,
                data: [
                    'input' => [
                        'title' => (string)$request->title,
                        'location_id' => (int)$request->location_id,
                        'district_id' => $request->district_id ? (int)$request->district_id : null,
                        'event_type_id' => (int)$request->event_type_id,
                        'start_datetime' => (string)$request->start_datetime,
                        'end_datetime' => (string)$request->end_datetime,
                        'venue' => $request->venue,
                        'max_volunteers' => Schema::hasColumn('events', 'max_volunteers') ? $request->input('max_volunteers') : null,
                        'organizers' => $normalizedOrganizers,
                    ],
                ]
            );

            $this->logEvent($event->event_id, $admin->admin_id, 'Create', $eventLogPayload);

            $factPayload = $this->factPayload(
                type: 'event.created',
                summary: $summary,
                adminId: $admin->admin_id,
                adminUsername: $admin->username,
                data: [
                    'event' => [
                        'id' => $event->event_id,
                        'code' => $event->event_code,
                        'title' => $event->title,
                    ],
                ]
            );

            $this->logFact($admin->admin_id, $event, 'Create', $factPayload);

            DB::commit();
            session()->forget('event_form_data');

            return redirect()
                ->route('event.details.show', $event->event_id)
                ->with('submit_success', "Event created. Code: {$event->event_code}");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['server' => 'Failed to create event: ' . $e->getMessage()])->withInput();
        }
    }

    // Edit Event (page)
    public function edit(Event $event)
    {
        $event->load(['organizers']);

        return view('create_event.create_event', [
            'eventTypes' => EventType::orderBy('label')->get(),
            'locations'  => Location::orderBy('barangay')->get(),
            'event'      => $event,
            'isEdit'     => true,
        ]);
    }

    // Update Event
    public function update(Request $request, Event $event)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return back()->withErrors(['auth' => 'Authentication failed.'])->withInput();

        $event->load(['organizers']);

        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'venue'          => 'nullable|string|max:255',
            'location_id'    => 'required|integer|exists:locations,location_id',
            'district_id'    => 'nullable|integer',
            'event_type_id'  => 'required|integer|exists:event_types,event_type_id',
            'start_datetime' => 'required|date',
            'end_datetime'   => 'required|date|after:start_datetime',
            'max_volunteers' => 'nullable|integer|min:0',

            'organizers.name'      => 'required|array|min:1|max:3',
            'organizers.email'     => 'nullable|array|max:3',
            'organizers.contact'   => 'nullable|array|max:3',
            'organizers.name.*'    => 'nullable|string|max:255',
            'organizers.email.*'   => 'nullable|email|max:255',
            'organizers.contact.*' => 'nullable|string|max:255',
        ]);

        $normalizedOrganizers = $this->normalizeOrganizerInput($request);

        if (count($normalizedOrganizers) < 1) {
            return back()->withErrors(['organizers.name' => 'Please provide at least one organizer name.'])->withInput();
        }

        try {
            DB::beginTransaction();

            // ---------------- BEFORE SNAPSHOT ----------------
            $before = $event->only([
                'title','description','venue','location_id','district_id',
                'event_type_id','start_datetime','end_datetime','max_volunteers'
            ]);

            $beforeOrganizers = $event->organizers->pluck('name')->map(fn($v)=>trim($v))->toArray();

            // ---------------- SAVE EVENT ----------------
            $event->fill([
                'title'          => $request->title,
                'description'    => $request->description,
                'venue'          => $request->venue,
                'location_id'    => $request->location_id,
                'district_id'    => $request->district_id,
                'event_type_id'  => $request->event_type_id,
                'start_datetime' => $request->start_datetime,
                'end_datetime'   => $request->end_datetime,
            ]);

            if (Schema::hasColumn('events', 'max_volunteers')) {
                $event->max_volunteers = $request->input('max_volunteers');
            }

            if (!in_array(strtolower((string)$event->status), [self::STATUS_CANCELLED, self::STATUS_COMPLETED], true)) {
                $event->status = self::STATUS_PLANNED;
            }

            $event->save();

            // ---------------- ORGANIZERS ----------------
            $organizerIds = [];

            foreach ($normalizedOrganizers as $org) {

                $email = $org['email'] ? strtolower($org['email']) : null;
                $name  = strtolower(trim($org['name']));

                $organizer = EventOrganizer::where(function ($q) use ($email, $name) {
                    if ($email) {
                        $q->whereRaw('LOWER(TRIM(email)) = ?', [$email]);
                    } else {
                        $q->whereRaw('LOWER(TRIM(name)) = ?', [$name]);
                    }
                })->first();

                if (!$organizer) {
                    $organizer = EventOrganizer::create([
                        'name'    => $org['name'],
                        'email'   => $org['email'] ?: null,
                        'contact' => $org['contact'] ?: null,
                    ]);
                }

                $organizerIds[] = $organizer->organizer_id;
            }

            $event->organizers()->sync($organizerIds);

            // ---------------- AFTER SNAPSHOT ----------------
            $event->load('organizers');

            $after = $event->only([
                'title','description','venue','location_id','district_id',
                'event_type_id','start_datetime','end_datetime','max_volunteers'
            ]);

            $afterOrganizers = $event->organizers->pluck('name')->map(fn($v)=>trim($v))->toArray();

            // ---------------- ONE-LINE DIFF ----------------
            $parts = [];

            // Organizer diff
            foreach (array_diff($afterOrganizers, $beforeOrganizers) as $n) {
                $parts[] = 'added organizer "' . $n . '"';
            }
            foreach (array_diff($beforeOrganizers, $afterOrganizers) as $n) {
                $parts[] = 'removed organizer "' . $n . '"';
            }

            // Event type
            if ($before['event_type_id'] != $after['event_type_id']) {
                $old = EventType::where('event_type_id',$before['event_type_id'])->value('label');
                $new = EventType::where('event_type_id',$after['event_type_id'])->value('label');
                $parts[] = 'event type ' . $old . ' → ' . $new;
            }

            // Dates
            if ($before['start_datetime'] != $after['start_datetime']) {
                $parts[] = 'start date '
                    . Carbon::parse($before['start_datetime'])->format('M j')
                    . ' → '
                    . Carbon::parse($after['start_datetime'])->format('M j');
            }

            if ($before['end_datetime'] != $after['end_datetime']) {
                $parts[] = 'end date '
                    . Carbon::parse($before['end_datetime'])->format('M j')
                    . ' → '
                    . Carbon::parse($after['end_datetime'])->format('M j');
            }

            // Simple text fields
            foreach (['title','venue','description'] as $f) {
                if (($before[$f] ?? null) != ($after[$f] ?? null)) {
                    $parts[] = $f . ' updated';
                }
            }

            // ---------------- SUMMARY ----------------
            $summary = 'Updated Event "' . $event->title . '" (Code: ' . $event->event_code . ')';

            if ($parts) {
                $summary .= ': ' . implode(', ', $parts);
            }

            // ---------------- LOGGING ----------------
            $this->logEvent($event->event_id, $admin->admin_id, 'Edit', [
                'before'=>$before,
                'after'=>$after,
                'organizers'=>$normalizedOrganizers
            ]);

            $this->factLogger->log(
                'event.updated',
                'Edit',
                $event,
                $event->event_id,
                [
                    'summary' => $summary,
                    'data' => [
                        'before'=>$before,
                        'after'=>$after,
                        'organizers_before'=>$beforeOrganizers,
                        'organizers_after'=>$afterOrganizers,
                    ]
                ]
            );

            DB::commit();

            return redirect()
                ->route('event.details.show', $event->event_id)
                ->with('submit_success', 'Event updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['server' => 'Failed to update event: ' . $e->getMessage()])->withInput();
        }
    }

    // Add expected volunteers (AJAX)
    public function addVolunteers(Request $request, Event $event)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        $data = $request->validate([
            'volunteer_ids'   => 'required|array|min:1',
            'volunteer_ids.*' => 'integer|exists:volunteer_profiles,volunteer_id',
        ]);

        $ids = array_values(array_unique(array_map('intval', $data['volunteer_ids'])));

        try {
            DB::beginTransaction();

            if (Schema::hasColumn('events', 'max_volunteers') && $event->max_volunteers !== null) {
                $current   = EventExpectedVolunteer::where('event_id', $event->event_id)->count();
                $remaining = max(0, (int)$event->max_volunteers - $current);

                if (count($ids) > $remaining) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Max volunteers reached. You can only add {$remaining} more.",
                    ], 422);
                }
            }

            $already = EventExpectedVolunteer::where('event_id', $event->event_id)
                ->whereIn('volunteer_id', $ids)
                ->pluck('volunteer_id')
                ->map(fn($v) => (int)$v)
                ->toArray();

            $toInsert = array_values(array_diff($ids, $already));

            foreach ($toInsert as $vid) {
                EventExpectedVolunteer::create([
                    'event_id'     => $event->event_id,
                    'volunteer_id' => $vid,
                    'status'       => 'expected',
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'added'   => count($toInsert),
                'skipped' => count($already),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to add volunteers: ' . $e->getMessage()], 500);
        }
    }

    // Remove expected volunteer (AJAX)
    public function removeExpectedVolunteer(Event $event, $volunteerId)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);

        $volunteerId = (int)$volunteerId;

        try {
            DB::beginTransaction();

            $deleted = EventExpectedVolunteer::where('event_id', $event->event_id)
                ->where('volunteer_id', $volunteerId)
                ->delete();

            if (!$deleted) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Volunteer not found for this event.'], 404);
            }

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to remove volunteer: ' . $e->getMessage()], 500);
        }
    }

    // Organizer input cleanup (max 3, de-dupe)
    private function normalizeOrganizerInput(Request $request): array
    {
        $names    = (array) $request->input('organizers.name', []);
        $emails   = (array) $request->input('organizers.email', []);
        $contacts = (array) $request->input('organizers.contact', []);

        $out = [];
        $seen = [];

        foreach ($names as $i => $nameRaw) {
            $name = trim((string) $nameRaw);
            if ($name === '') continue;

            $emailRaw = $emails[$i] ?? null;
            $email = is_string($emailRaw) ? trim($emailRaw) : null;
            $email = ($email === '') ? null : $email;

            $contactRaw = $contacts[$i] ?? null;
            $contact = is_string($contactRaw) ? trim($contactRaw) : null;
            $contact = ($contact === '') ? null : $contact;

            if ($email) {
                $key = 'email:' . mb_strtolower($email);
            } else {
                $normName = preg_replace('/\s+/', ' ', mb_strtolower($name));
                $key = 'name:' . $normName;
            }

            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $out[] = [
                'name'    => $name,
                'email'   => $email,
                'contact' => $contact,
            ];
        }

        return array_slice($out, 0, 3);
    }

    // Event code generator (e.g. ABC-123)
    private function generateUniqueEventCode(): string
    {
        do {
            $letters = $this->randomLetters(3);
            $digits  = random_int(100, 999);
            $code    = "{$letters}-{$digits}";
        } while (Event::where('event_code', $code)->exists());

        return $code;
    }

    private function randomLetters(int $len): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $out;
    }

    // EventLog writer (details supports string or array payload)
    private function logEvent(int $eventId, ?int $adminId, string $action, $details = null): void
    {
        $encoded = null;

        if (is_array($details) || is_object($details)) {
            $encoded = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $encoded = $details;
        }

        EventLog::create([
            'event_id' => $eventId,
            'admin_id' => $adminId,
            'action'   => $action,
            'details'  => $encoded,
        ]);
    }

    // FactLog writer (details supports string or array payload)
    private function logFact(?int $adminId, $entity, ?string $action = null, $details = null): FactLog
    {
        $admin   = Auth::guard('admin')->user();
        $adminId = is_numeric($adminId) ? (int)$adminId : ($admin->admin_id ?? null);

        $entityType = 'Unknown';
        $entityId   = null;

        if (is_object($entity)) {
            $entityType = class_basename($entity);
            $entityId   = method_exists($entity, 'getKey') ? $entity->getKey() : null;
        } elseif (is_string($entity)) {
            $entityType = $entity;
        }

        if (is_array($details) || is_object($details)) {
            $encodedDetails = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            $encodedDetails = is_null($details) ? '' : (string)$details;
        }

        return FactLog::create([
            'admin_id'    => $adminId,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'action'      => $action,
            'details'     => $encodedDetails,
            'timestamp'   => now(),
        ]);
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
                'ua' => substr((string)request()->userAgent(), 0, 255),
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
