<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

use App\Models\Event;
use App\Models\EventLog;
use App\Models\FactLog;

class EventDetailsController extends Controller
{
    private const EVENT_DETAILS_VIEW = 'event_details.event_details';

    private const STATUS_PLANNED   = 'planned';
    private const STATUS_ONGOING   = 'ongoing';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_CANCELLED = 'cancelled';

        public function show($eventId)
        {
            $event = Event::with([
                'location',
                'eventType',
                'creator',
                'organizers',
                'expectedVolunteers.volunteer.course',
            ])->findOrFail($eventId);

            $defaultAvatar = asset('storage/defaults/default_user.png');

            // ============================================================
            // Expected list (Roster)
            // ============================================================
            $attendeesExpectedJs = $event->expectedVolunteers
                ->filter(fn($ev) => $ev->volunteer)
                ->map(function ($ev) use ($defaultAvatar) {
                    $v = $ev->volunteer;

                    $avatar = !empty($v->profile_picture_path)
                        ? asset('storage/' . ltrim($v->profile_picture_path, '/'))
                        : (!empty($v->profile_picture_url) ? $v->profile_picture_url : $defaultAvatar);

                    return [
                        'id'          => $v->volunteer_id,
                        'name'        => $v->full_name,
                        'course'      => optional($v->course)->course_name,
                        'profile_pic' => $avatar,
                        'profile_url' => route('volunteers.show', $v->volunteer_id),
                    ];
                })
                ->values();

            $expectedCount = $event->expectedVolunteers->count();

            // ============================================================
            // Status derive
            // ============================================================
            $now   = Carbon::now();
            $start = $event->start_datetime ? Carbon::parse($event->start_datetime) : null;
            $end   = $event->end_datetime ? Carbon::parse($event->end_datetime) : null;

            $derivedStatus = $this->deriveStatus($event->status, $start, $end, $now);

            // ============================================================
        // Actual attendance ✅ match in PHP with priority
        // ============================================================
        $attendeesActualJs = collect();
        $actualCount = $presentCount = $lateCount = $walkInCount = 0;

        $attendanceEnabled = in_array($derivedStatus, [self::STATUS_ONGOING, self::STATUS_COMPLETED], true);

        if (Schema::hasTable('event_attendances')) {
            try {
                $rows = DB::table('event_attendances')
                    ->where('event_id', $event->event_id)
                    ->get();

                if ($rows->count() > 0) $attendanceEnabled = true;

                $actualCount  = $rows->count();
                $presentCount = $rows->where('status', 'present')->count();
                $lateCount    = $rows->where('status', 'late')->count();
                $walkInCount  = $rows->where('walk_in', 1)->count();

                // Pull identifiers from attendance
                $volunteerIds = $rows->pluck('volunteer_id')
                    ->filter(fn($x) => $x !== null && $x !== '')
                    ->map(fn($x) => trim((string)$x))
                    ->filter(fn($x) => is_numeric($x))
                    ->map(fn($x) => (int)$x)
                    ->unique()
                    ->values();

                $schoolIds = $rows->pluck('school_id')
                    ->filter(fn($x) => $x !== null && $x !== '')
                    ->map(fn($x) => trim((string)$x))
                    ->unique()
                    ->values();

                $emails = $rows->pluck('school_email')
                    ->filter(fn($x) => $x !== null && $x !== '')
                    ->map(fn($x) => strtolower(trim((string)$x)))
                    ->unique()
                    ->values();

                // Fetch profiles into 3 maps
                $profilesByVolunteerId = collect();
                $profilesByIdNumber    = collect();
                $profilesByEmail       = collect();

                if (Schema::hasTable('volunteer_profiles')) {
                    if ($volunteerIds->count()) {
                        $profilesByVolunteerId = DB::table('volunteer_profiles')
                            ->whereIn('volunteer_id', $volunteerIds->all())
                            ->get()
                            ->keyBy('volunteer_id');
                    }

                    if ($schoolIds->count()) {
                        $profilesByIdNumber = DB::table('volunteer_profiles')
                            ->whereIn('id_number', $schoolIds->all())
                            ->get()
                            ->keyBy(fn($p) => trim((string)$p->id_number));
                    }

                    if ($emails->count()) {
                        // IMPORTANT: don't do whereIn(DB::raw('LOWER(email)')) - it can be finicky.
                        // Instead, fetch candidates by email and key them normalized in PHP.
                        $rowsByEmail = DB::table('volunteer_profiles')
                            ->whereIn('email', $emails->all()) // assumes stored emails are same-case; your SQL shows it is.
                            ->get();

                        $profilesByEmail = $rowsByEmail->keyBy(fn($p) => strtolower(trim((string)$p->email)));
                    }
                }

                $attendeesActualJs = $rows->map(function ($r) use ($defaultAvatar, $profilesByVolunteerId, $profilesByIdNumber, $profilesByEmail) {
                    $rawVolunteerId = trim((string)($r->volunteer_id ?? ''));
                    $rawSchoolId    = trim((string)($r->school_id ?? ''));
                    $rawEmail       = strtolower(trim((string)($r->school_email ?? '')));

                    // 1) volunteer_id
                    $vp = null;
                    if ($rawVolunteerId !== '' && is_numeric($rawVolunteerId)) {
                        $vp = $profilesByVolunteerId[(int)$rawVolunteerId] ?? null;
                    }
                    // 2) school_id -> id_number
                    if (!$vp && $rawSchoolId !== '') {
                        $vp = $profilesByIdNumber[$rawSchoolId] ?? null;
                    }
                    // 3) email
                    if (!$vp && $rawEmail !== '') {
                        $vp = $profilesByEmail[$rawEmail] ?? null;
                    }

                    $vid = null;
                    if ($vp && !empty($vp->volunteer_id)) $vid = (int)$vp->volunteer_id;
                    elseif ($rawVolunteerId !== '' && is_numeric($rawVolunteerId)) $vid = (int)$rawVolunteerId;

                    $avatar = $defaultAvatar;
                    if ($vp && !empty($vp->profile_picture_path)) {
                        $avatar = asset('storage/' . ltrim(str_replace('\\','/', (string)$vp->profile_picture_path), '/'));
                    } elseif ($vp && !empty($vp->profile_picture_url)) {
                        $avatar = (string)$vp->profile_picture_url;
                    }

                    return [
                        'id'          => $vid ?: ('walkin_' . ($r->attendance_id ?? uniqid())),
                        'name'        => ($vp->full_name ?? null) ?: ($r->full_name ?? '—'),
                        'course'      => null,
                        'email'       => $r->school_email ?? null,
                        'school_id'   => $r->school_id ?? null,
                        'status'      => $r->status ?? 'present',
                        'source'      => $r->source ?? null,
                        'walk_in'     => (bool)($r->walk_in ?? false),
                        'profile_pic' => $avatar,
                        'profile_url' => $vid ? route('volunteers.show', $vid) : null,
                    ];
                })->values();

            } catch (\Throwable $e) {
                $attendeesActualJs = collect();
                $actualCount = $presentCount = $lateCount = $walkInCount = 0;
            }
        }


        $event->status = $derivedStatus;

        $attendanceUi = [
            'enabled' => $attendanceEnabled,
            'message' => $attendanceEnabled
                ? null
                : 'Attendance is disabled for upcoming events. It becomes available when the event starts (or after an attendance import).',
        ];

        return view(self::EVENT_DETAILS_VIEW, compact(
            'event',
            'attendeesExpectedJs',
            'attendeesActualJs',
            'expectedCount',
            'actualCount',
            'presentCount',
            'lateCount',
            'walkInCount',
            'attendanceUi'
        ));
    }



    /**
     * Converts profile_picture_path into a public URL.
     * Accepts:
     * - "profile_pictures/volunteers/x.jpg"
     * - "/profile_pictures/volunteers/x.jpg"
     * - "C:\xampp\htdocs\...\storage\app\public\profile_pictures\volunteers\x.jpg"
     */
    private function toPublicStorageUrl(string $path): ?string
    {
        $p = trim($path);
        if ($p === '') return null;

        // normalize slashes
        $p = str_replace('\\', '/', $p);

        // If it's already a URL, just return it
        if (preg_match('~^https?://~i', $p)) return $p;

        // If someone stored an absolute Windows path, strip everything up to "/storage/app/public/"
        $needle = '/storage/app/public/';
        if (stripos($p, $needle) !== false) {
            $p = substr($p, stripos($p, $needle) + strlen($needle));
        }

        // Also support stripping from "/public/" if that’s what got stored
        if (stripos($p, '/public/') !== false && stripos($p, $needle) === false) {
            $p = substr($p, stripos($p, '/public/') + strlen('/public/'));
        }

        $p = ltrim($p, '/');

        return asset('storage/' . $p);
    }

    /**
     * Cancel event with a required reason
     */
    public function cancel(Request $request, $eventId)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return back()->withErrors(['auth' => 'Authentication failed.']);

        $data = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ]);

        $reason = trim($data['reason']);
        $event = Event::findOrFail($eventId);

        $current = strtolower((string)($event->status ?? self::STATUS_PLANNED));
        if ($current === self::STATUS_CANCELLED) return back()->withErrors(['status' => 'This event is already cancelled.']);
        if ($current === self::STATUS_COMPLETED) return back()->withErrors(['status' => 'Completed events cannot be cancelled.']);

        try {
            DB::beginTransaction();

            $event->status = self::STATUS_CANCELLED;

            if (Schema::hasColumn('events', 'cancel_reason')) $event->cancel_reason = $reason;
            if (Schema::hasColumn('events', 'cancelled_at'))  $event->cancelled_at = now();
            if (Schema::hasColumn('events', 'cancelled_by'))  $event->cancelled_by = $admin->admin_id;

            $event->save();

            $this->logEvent($event->event_id, $admin->admin_id, 'Cancel', "Cancelled event. Reason: {$reason}");
            $this->logFact($admin->admin_id, $event, 'Cancel', [
                'event_id'   => $event->event_id,
                'event_code' => $event->event_code ?? null,
                'title'      => $event->title ?? null,
                'reason'     => $reason,
            ]);

            DB::commit();

            return redirect()
                ->route('event.details.show', $event->event_id)
                ->with('submit_success', 'Event cancelled successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['server' => 'Failed to cancel event: ' . $e->getMessage()]);
        }
    }

    /**
     * Restore a cancelled event back to planned
     */
    public function restore(Request $request, $eventId)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return back()->withErrors(['auth' => 'Authentication failed.']);

        $data = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);
        $reason = trim((string)($data['reason'] ?? ''));

        $event = Event::findOrFail($eventId);

        $current = strtolower((string)($event->status ?? self::STATUS_PLANNED));
        if ($current !== self::STATUS_CANCELLED) {
            return back()->withErrors(['status' => 'Only cancelled events can be restored.']);
        }

        try {
            DB::beginTransaction();

            $event->status = self::STATUS_PLANNED;

            if (Schema::hasColumn('events', 'cancel_reason')) $event->cancel_reason = null;
            if (Schema::hasColumn('events', 'cancelled_at'))  $event->cancelled_at = null;
            if (Schema::hasColumn('events', 'cancelled_by'))  $event->cancelled_by = null;

            $event->save();

            $details = $reason !== '' ? "Restored event. Reason: {$reason}" : "Restored event.";
            $this->logEvent($event->event_id, $admin->admin_id, 'Restore', $details);

            $this->logFact($admin->admin_id, $event, 'Restore', [
                'event_id'   => $event->event_id,
                'event_code' => $event->event_code ?? null,
                'title'      => $event->title ?? null,
                'reason'     => $reason !== '' ? $reason : null,
            ]);

            DB::commit();

            return redirect()
                ->route('event.details.show', $event->event_id)
                ->with('submit_success', 'Event restored to Planned.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['server' => 'Failed to restore event: ' . $e->getMessage()]);
        }
    }

    private function deriveStatus(?string $stored, ?Carbon $start, ?Carbon $end, Carbon $now): string
    {
        $stored = strtolower((string)($stored ?? self::STATUS_PLANNED));

        if (in_array($stored, [self::STATUS_CANCELLED, self::STATUS_COMPLETED], true)) {
            return $stored;
        }

        if (!$start || !$end) return self::STATUS_PLANNED;

        if ($now->lt($start)) return self::STATUS_PLANNED;
        if ($now->betweenIncluded($start, $end)) return self::STATUS_ONGOING;

        return self::STATUS_COMPLETED;
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
        $admin = Auth::guard('admin')->user();
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
