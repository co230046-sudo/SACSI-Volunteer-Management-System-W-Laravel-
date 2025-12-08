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
    // If file is: resources/views/event_details/event_details.blade.php
    private const EVENT_DETAILS_VIEW = 'event_details.event_details';

    /**
     * Centralized status enum values (DB enum: planned|ongoing|completed|cancelled)
     * UI can show "Upcoming" but DB keeps "planned".
     */
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

    /**
     * We now treat EventDetailsController@show as the single source of truth
     * for the event details page. This method simply redirects there so we
     * avoid duplicate logic in two controllers.
     */
    public function show(Event $event)
    {
        return redirect()->route('event.details.show', $event->event_id);
    }

    /* =========================================================
       CREATE EVENT
    ========================================================= */
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

        // At least one organizer name
        $names = $request->input('organizers.name', []);
        $hasAtLeastOne = false;
        foreach ($names as $n) {
            if (is_string($n) && trim($n) !== '') { $hasAtLeastOne = true; break; }
        }
        if (!$hasAtLeastOne) {
            return back()->withErrors(['organizers.name' => 'Please provide at least one organizer name.'])->withInput();
        }

        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return back()->withErrors(['auth' => 'Authentication failed.'])->withInput();
        }

        // Duplicate warning: title + location_id + start_datetime
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

            // save organizers
            $emails   = $request->input('organizers.email', []);
            $contacts = $request->input('organizers.contact', []);

            foreach ($names as $i => $name) {
                $name = trim((string)$name);
                if ($name === '') continue;

                EventOrganizer::create([
                    'event_id' => $event->event_id,
                    'name'     => $name,
                    'email'    => isset($emails[$i]) ? ($emails[$i] ?: null) : null,
                    'contact'  => isset($contacts[$i]) ? ($contacts[$i] ?: null) : null,
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

    /* =========================================================
       EDIT / UPDATE EVENT
    ========================================================= */
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

        $names = $request->input('organizers.name', []);
        $hasAtLeastOne = false;
        foreach ($names as $n) {
            if (is_string($n) && trim($n) !== '') { $hasAtLeastOne = true; break; }
        }
        if (!$hasAtLeastOne) {
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

            // Status is not edited here (dedicated "cancel/complete" actions should manage it).
            if (!in_array(strtolower((string)$event->status), [self::STATUS_CANCELLED, self::STATUS_COMPLETED], true)) {
                $event->status = self::STATUS_PLANNED;
            }

            $event->save();

            EventOrganizer::where('event_id', $event->event_id)->delete();

            $emails   = $request->input('organizers.email', []);
            $contacts = $request->input('organizers.contact', []);

            foreach ($names as $i => $name) {
                $name = trim((string)$name);
                if ($name === '') continue;

                EventOrganizer::create([
                    'event_id' => $event->event_id,
                    'name'     => $name,
                    'email'    => isset($emails[$i]) ? ($emails[$i] ?: null) : null,
                    'contact'  => isset($contacts[$i]) ? ($contacts[$i] ?: null) : null,
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

    /* =========================================================
       EXPECTED VOLUNTEERS (AJAX)
    ========================================================= */
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

    /* =========================================================
       HELPERS
    ========================================================= */
    private function deriveStatus(?string $stored, ?Carbon $start, ?Carbon $end, Carbon $now): string
    {
        $stored = strtolower((string)($stored ?? self::STATUS_PLANNED));

        // Hard states always win.
        if (in_array($stored, [self::STATUS_CANCELLED, self::STATUS_COMPLETED], true)) {
            return $stored;
        }

        if (!$start || !$end) return self::STATUS_PLANNED;

        if ($now->lt($start)) return self::STATUS_PLANNED;
        if ($now->betweenIncluded($start, $end)) return self::STATUS_ONGOING;
        return self::STATUS_COMPLETED;
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

    /**
     * Log summary view safely (once per admin/event/day) so it doesn't spam FactLog.
     */
    private function logSummaryViewOncePerDay(Event $event, string $chartMode, string $chartHint): void
    {
        try {
            $admin = Auth::guard('admin')->user();
            if (!$admin) return;

            $alreadyLogged = FactLog::where('admin_id', $admin->admin_id)
                ->where('entity_type', 'Event')
                ->where('entity_id', $event->event_id)
                ->where('action', 'View Summary')
                ->whereDate('timestamp', now()->toDateString())
                ->exists();

            if ($alreadyLogged) return;

            $this->logFact($admin->admin_id, $event, 'View Summary', [
                'event_id'   => $event->event_id,
                'event_code' => $event->event_code ?? null,
                'title'      => $event->title ?? null,
                'chart_mode' => $chartMode,
                'chart_hint' => $chartHint,
            ]);

        } catch (\Throwable $e) {
            // ignore logging errors so summary page still loads
        }
    }

    /* =========================================================
       SUMMARY (late is treated as present)
    ========================================================= */
    public function summary(Event $event)
    {
        $event->load([
            'location',
            'eventType',
            'feedbacks.volunteer',
        ]);

        $now   = Carbon::now();
        $start = $event->start_datetime ? Carbon::parse($event->start_datetime) : null;
        $end   = $event->end_datetime   ? Carbon::parse($event->end_datetime)   : null;

        $derivedStatus = $this->deriveStatus($event->status, $start, $end, $now);
        $event->status = $derivedStatus;

        // 1) Only completed events can view summary
        if ($derivedStatus !== self::STATUS_COMPLETED) {
            return redirect()
                ->route('event.details.show', $event->event_id)
                ->with('summary_notice', 'Event Summary is only available once the event is completed.');
        }

        // 2) Attendance table must exist
        if (!Schema::hasTable('event_attendances')) {
            return redirect()
                ->route('event.details.show', $event->event_id)
                ->with('summary_notice', 'Event Summary is unavailable because the attendance table is missing.');
        }

        // 3) Attendance must be imported for THIS event (and code must match if present)
        $eventCode = strtoupper(trim((string)($event->event_code ?? '')));

        $rowsAll = DB::table('event_attendances')
            ->where('event_id', $event->event_id)
            ->get();

        if ($rowsAll->isEmpty()) {
            return redirect()
                ->route('event.details.show', $event->event_id)
                ->with('summary_notice', 'Event Summary is locked until attendance is imported for this event.');
        }

        $rows = $rowsAll;

        if ($eventCode !== '') {
            $rows = $rowsAll->filter(function ($r) use ($eventCode) {
                return strtoupper(trim((string)($r->event_code ?? ''))) === $eventCode;
            })->values();

            if ($rows->isEmpty()) {
                return redirect()
                    ->route('event.details.show', $event->event_id)
                    ->with('summary_notice', 'Attendance was imported, but it belongs to a different Event Code. Please import the correct attendance for this event.');
            }
        }

        // ============================================================
        // Expected count (Roster)
        // ============================================================
        $expectedCount = 0;
        if (method_exists($event, 'expectedVolunteers')) {
            $expectedCount = (int) $event->expectedVolunteers()->count();
        }

        // ============================================================
        // Attendance counts (late -> present)
        // ============================================================
        $hasAttendanceImport     = true;
        $attendanceImportedTotal = $rows->count();

        $attendedRows = $rows->filter(function ($r) {
            $s = strtolower((string)($r->status ?? ''));
            return in_array($s, ['present', 'late', ''], true);
        });

        $presentCount = $attendedRows->count();
        $lateCount    = 0; // we no longer expose late separately
        $walkInCount  = $rows->where('walk_in', 1)->count();

        $attendedCount = $presentCount;

        // Attendance Rate: attended / expected
        $attendanceRate = ($expectedCount > 0)
            ? (int) round(($attendedCount / $expectedCount) * 100)
            : 0;

        $maxVolunteers = (!empty($event->max_volunteers) && (int)$event->max_volunteers > 0)
            ? (int)$event->max_volunteers
            : null;

        // Capacity Used: based on attended seats used.
        $capacityUsed = (!is_null($maxVolunteers) && $maxVolunteers > 0)
            ? (int) round(($attendedCount / $maxVolunteers) * 100)
            : null;

        // ============================================================
        // Chart mode + hint
        // ============================================================
        $chartMode = 'actual';
        $chartHint = 'Based on imported attendance (present)';

        // ============================================================
        // Volunteer profiles for year-level distribution
        // ============================================================
        $volunteerIds = $rows->pluck('volunteer_id')->filter()->unique()->values();
        $profilesById = collect();

        if ($volunteerIds->count() > 0 && Schema::hasTable('volunteer_profiles')) {
            $profilesById = DB::table('volunteer_profiles')
                ->whereIn('volunteer_id', $volunteerIds)
                ->get()
                ->keyBy('volunteer_id');
        }

        $yearCounts = [];
        foreach ($attendedRows as $r) {
            $v = $r->volunteer_id ? ($profilesById[$r->volunteer_id] ?? null) : null;
            $yl = $v ? trim((string)($v->year_level ?? '')) : '';
            $key = $yl !== '' ? $yl : 'Unknown';
            $yearCounts[$key] = ($yearCounts[$key] ?? 0) + 1;
        }

        $labelFor = function (string $key) {
            $k = strtolower(trim($key));
            return match (true) {
                in_array($k, ['1','1st','1st year'], true) => '1st Year',
                in_array($k, ['2','2nd','2nd year'], true) => '2nd Year',
                in_array($k, ['3','3rd','3rd year'], true) => '3rd Year',
                in_array($k, ['4','4th','4th year'], true) => '4th Year',
                in_array($k, ['grade 11','g11','11'], true) => 'Grade 11',
                in_array($k, ['grade 12','g12','12'], true) => 'Grade 12',
                $k === 'unknown' => 'Unknown',
                default => $key,
            };
        };

        // brand palette
        $palette = ['#b23a45', '#2563eb', '#16a34a', '#f59e0b', '#7c3aed', '#0ea5e9', '#64748b'];

        arsort($yearCounts);
        $totalForChart = array_sum($yearCounts);

        $chartData = [];
        $i = 0;
        foreach ($yearCounts as $key => $count) {
            $pct = $totalForChart > 0 ? round(($count / $totalForChart) * 100, 1) : 0;
            $chartData[] = [
                'label'      => $labelFor((string)$key),
                'count'      => (int)$count,
                'percentage' => (float)$pct,
                'color'      => $palette[$i % count($palette)],
            ];
            $i++;
        }

        // ============================================================
        // Feedback parsing + avatar + profile URL + ORDERED Q/A
        // ============================================================
        $defaultAvatar = asset('storage/defaults/default_user.png');

        $questionOrder = [
            'like'     => 'What did you like about this event?',
            'improve'  => 'What should we improve on next time?',
            'issues'   => 'Any issues you encountered during the event?',
            'comments' => 'Any other comments?',
        ];

        $extractAnswers = function (string $raw) {
            $raw = trim($raw);

            // Normalize common label variants into one-per-line
            $normalized = preg_replace('/\s*(What did you like about this event\?|Like:)\s*/i', "\nLIKE: ", $raw);
            $normalized = preg_replace('/\s*(What should we improve on next time\?|Improve next time:|Improve:)\s*/i', "\nIMPROVE: ", $normalized);
            $normalized = preg_replace('/\s*(Any issues you encountered during the event\?|Issues:)\s*/i', "\nISSUES: ", $normalized);
            $normalized = preg_replace('/\s*(Any other comments\?|Comments:)\s*/i', "\nCOMMENTS: ", $normalized);

            $parts = array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $normalized))));

            $out = [
                'like' => null, 'improve' => null, 'issues' => null, 'comments' => null
            ];

            foreach ($parts as $p) {
                if (preg_match('/^LIKE:\s*(.*)$/i', $p, $m))       $out['like'] = trim($m[1] ?? '');
                elseif (preg_match('/^IMPROVE:\s*(.*)$/i', $p, $m)) $out['improve'] = trim($m[1] ?? '');
                elseif (preg_match('/^ISSUES:\s*(.*)$/i', $p, $m))  $out['issues'] = trim($m[1] ?? '');
                elseif (preg_match('/^COMMENTS:\s*(.*)$/i', $p, $m))$out['comments'] = trim($m[1] ?? '');
            }

            // If nothing matched, treat as general comment
            $hasAny = collect($out)->contains(fn($v) => is_string($v) && trim($v) !== '');
            if (!$hasAny && $raw !== '') {
                $out['comments'] = $raw;
            }

            // Clean empty -> null
            foreach ($out as $k => $v) {
                $v = is_string($v) ? trim($v) : $v;
                $out[$k] = ($v === '' ? null : $v);
            }

            return $out;
        };

        $feedbacks = $event->feedbacks
            ->filter(fn($fb) => is_string($fb->feedback_text) && trim($fb->feedback_text) !== '')
            ->map(function ($fb) use ($defaultAvatar, $questionOrder, $extractAnswers) {
                $v = $fb->volunteer;

                // avatar resolve
                $avatar = $defaultAvatar;
                if ($v) {
                    if (!empty($v->profile_picture_path)) {
                        $avatar = asset('storage/' . ltrim(str_replace('\\', '/', (string)$v->profile_picture_path), '/'));
                    } elseif (!empty($v->profile_picture_url)) {
                        $avatar = (string)$v->profile_picture_url;
                    }
                }

                // profile link
                $profileUrl = $v ? route('volunteers.show', $v->volunteer_id) : null;

                // extract ordered answers
                $answers = $extractAnswers((string)$fb->feedback_text);

                $qa = collect($questionOrder)->map(function ($qText, $key) use ($answers) {
                    $a = $answers[$key] ?? null;
                    return [
                        'q' => $qText,
                        'a' => ($a !== null && trim((string)$a) !== '') ? $a : 'None.',
                    ];
                })->values()->all();

                return (object)[
                    'id'            => $fb->id ?? null,
                    'rating'        => $fb->rating ?? null,
                    'submitted_at'  => $fb->submitted_at ?? null,
                    'created_at'    => $fb->created_at ?? null,
                    'volunteer_name'=> $v?->full_name ?? 'Unknown Volunteer',
                    'avatar'        => $avatar,
                    'profile_url'   => $profileUrl,
                    'qa'            => $qa,
                ];
            })
            ->values();

        $this->logSummaryViewOncePerDay($event, $chartMode, $chartHint);

        return view('event_summary.event_summary', compact(
            'event',
            'expectedCount',
            'attendedCount',
            'attendanceRate',
            'maxVolunteers',
            'capacityUsed',
            'chartData',
            'feedbacks',
            'chartMode',
            'chartHint',
            'hasAttendanceImport',
            'attendanceImportedTotal',
            'presentCount',
            'lateCount',
            'walkInCount'
        ));
    }
}
