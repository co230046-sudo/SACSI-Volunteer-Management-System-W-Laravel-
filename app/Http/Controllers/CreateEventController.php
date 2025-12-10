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

class CreateEventController extends Controller
{
    private const EVENT_DETAILS_VIEW = 'event_details.event_details';

    private const STATUS_PLANNED   = 'planned';
    private const STATUS_ONGOING   = 'ongoing';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_CANCELLED = 'cancelled';

    public function create()
    {
        return view('create_event.create_event', [
            'eventTypes' => EventType::orderBy('label')->get(),
            'locations'  => Location::orderBy('barangay')->get(),
            'event'      => null,
            'isEdit'     => false,
        ]);
    }

    public function show(Event $event)
    {
        return redirect()->route('event.details.show', $event->event_id);
    }

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

            foreach ($normalizedOrganizers as $org) {
                EventOrganizer::create([
                    'event_id' => $event->event_id,
                    'name'     => $org['name'],
                    'email'    => $org['email'],
                    'contact'  => $org['contact'],
                ]);
            }

            $this->logEvent($event->event_id, $admin->admin_id, 'Create', "Created event “{$event->title}” (Code: {$event->event_code}).");
            $this->logFact($admin->admin_id, $event, 'Create', "Admin {$admin->username} created event “{$event->title}” (Event ID: {$event->event_id}, Code: {$event->event_code}).");

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

            EventOrganizer::where('event_id', $event->event_id)->delete();

            foreach ($normalizedOrganizers as $org) {
                EventOrganizer::create([
                    'event_id' => $event->event_id,
                    'name'     => $org['name'],
                    'email'    => $org['email'],
                    'contact'  => $org['contact'],
                ]);
            }

            $this->logEvent($event->event_id, $admin->admin_id, 'Edit', "Edited event “{$event->title}” (ID: {$event->event_id}).");
            $this->logFact($admin->admin_id, $event, 'Edit', [
                'event_id'       => $event->event_id,
                'updated_fields' => array_keys($request->all()),
            ]);

            DB::commit();

            return redirect()
                ->route('event.details.show', $event->event_id)
                ->with('submit_success', 'Event updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['server' => 'Failed to update event: ' . $e->getMessage()])->withInput();
        }
    }

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

    private function logEvent(int $eventId, ?int $adminId, string $action, ?string $details = null): void
    {
        EventLog::create([
            'event_id' => $eventId,
            'admin_id' => $adminId,
            'action'   => $action,
            'details'  => $details,
        ]);
    }

    private function logFact(?int $adminId, $entity, ?string $action = null, $details = null): FactLog
    {
        $admin   = Auth::guard('admin')->user();
        $adminId = is_numeric($adminId) ? (int)$adminId : ($admin->admin_id ?? null);

        $encodedDetails = is_array($details) || is_object($details)
            ? json_encode($details, JSON_UNESCAPED_UNICODE)
            : (string)$details;

        $entityType = 'Unknown';
        $entityId   = null;

        if (is_object($entity)) {
            $entityType = class_basename($entity);
            $entityId   = method_exists($entity, 'getKey') ? $entity->getKey() : null;
        } elseif (is_string($entity)) {
            $entityType = $entity;
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
}
