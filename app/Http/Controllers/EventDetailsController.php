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
            'attendances.volunteer.course',
        ])->findOrFail($eventId);

        $defaultAvatar = asset('storage/defaults/default_user.png');

        // ============================================================
        // ROSTER (Expected Volunteers)
        // ============================================================
        $expectedRows = $event->expectedVolunteers ?? collect();

        $attendeesExpectedJs = $expectedRows
            ->filter(fn($ev) => $ev->volunteer)
            ->map(function ($ev) use ($defaultAvatar) {
                $v = $ev->volunteer;

                $avatar = $defaultAvatar;
                if (!empty($v->profile_picture_path)) {
                    $avatar = $this->toPublicStorageUrl((string)$v->profile_picture_path) ?? $defaultAvatar;
                } elseif (!empty($v->profile_picture_url)) {
                    $avatar = (string)$v->profile_picture_url;
                }

                return [
                    'id'          => $v->volunteer_id,
                    'name'        => $v->full_name,
                    'course'      => optional($v->course)->course_name,
                    'email'       => $v->school_email ?? $v->email ?? null,
                    'school_id'   => $v->school_id ?? $v->id_number ?? null,
                    'profile_pic' => $avatar,
                    'profile_url' => route('volunteers.show', $v->volunteer_id),
                ];
            })
            ->values();

        $expectedCount = $expectedRows->count();

        // ============================================================
        // Status derive
        // ============================================================
        $now   = Carbon::now();
        $start = $event->start_datetime ? Carbon::parse($event->start_datetime) : null;
        $end   = $event->end_datetime   ? Carbon::parse($event->end_datetime)   : null;

        $derivedStatus = $this->deriveStatus($event->status, $start, $end, $now);
        $event->status = $derivedStatus;

        // ============================================================
        // ACTUAL ATTENDANCE (EventAttendance, eager-loaded)
        // ============================================================
        $attendanceRows = collect();
        if (Schema::hasTable('event_attendances')) {
            $attendanceRows = $event->attendances ?? collect();
        }

        $actualCount = $attendanceRows->count();

        // late is merged into present
        $attendedRows = $attendanceRows->filter(function ($att) {
            $s = strtolower((string)($att->status ?? ''));
            return in_array($s, ['present', 'late', ''], true);
        });

        $presentCount = $attendedRows->count();
        $lateCount    = 0; // we no longer expose late separately
        $walkInCount  = $attendanceRows->where('walk_in', 1)->count();

        $attendeesActualJs = collect();

        // Map of attendance rows by volunteer_id for roster lookups
        $attendanceByVolunteer = $attendanceRows
            ->whereNotNull('volunteer_id')
            ->keyBy('volunteer_id');

        // 1) Everyone on the roster -> Present / Absent
        foreach ($expectedRows as $ev) {
            $vol = $ev->volunteer;
            if (!$vol) {
                continue;
            }

            $volunteerId = $vol->volunteer_id;

            $att = $attendanceByVolunteer->get($volunteerId);
            $status = 'absent';
            $walkIn = false;
            $email = $vol->school_email ?? $vol->email ?? null;
            $schoolId = $vol->school_id ?? $vol->id_number ?? null;
            $sourceLabel = 'No check-in';

            if ($att) {
                $statusRaw = strtolower((string)($att->status ?? 'present'));

                // Normalize 'late' -> 'present' for UI
                if (in_array($statusRaw, ['present', 'late', ''], true)) {
                    $status = 'present';
                } else {
                    $status = $statusRaw;
                }

                $walkIn = (bool)($att->walk_in ?? false);

                if (!empty($att->school_email)) {
                    $email = $att->school_email;
                }
                if (!empty($att->school_id)) {
                    $schoolId = $att->school_id;
                }

                $sourceLabel = $this->formatAttendanceSource($att);
            }

            $avatar = $defaultAvatar;
            if (!empty($vol->profile_picture_path)) {
                $avatar = $this->toPublicStorageUrl((string)$vol->profile_picture_path) ?? $defaultAvatar;
            } elseif (!empty($vol->profile_picture_url)) {
                $avatar = (string)$vol->profile_picture_url;
            }

            $attendeesActualJs->push([
                'id'          => $volunteerId,
                'name'        => $vol->full_name,
                'course'      => optional($vol->course)->course_name,
                'email'       => $email,
                'school_id'   => $schoolId,
                'status'      => $status, // 'present' or 'absent'
                'walk_in'     => $walkIn,
                'source'      => $sourceLabel,
                'profile_pic' => $avatar,
                'profile_url' => route('volunteers.show', $volunteerId),
            ]);
        }

        // 2) Walk-ins: rows not tied to roster (or volunteer_id null)
        $rosterVolunteerIds = $expectedRows
            ->pluck('volunteer_id')
            ->filter()
            ->map(fn($id) => (int)$id)
            ->unique()
            ->values()
            ->all();

        $walkIns = $attendanceRows
            ->filter(function ($att) use ($rosterVolunteerIds) {
                if (!$att->volunteer_id) return true;
                return !in_array((int)$att->volunteer_id, $rosterVolunteerIds, true);
            });

        foreach ($walkIns as $att) {
            $vol = $att->volunteer; // may be null if we couldn't resolve

            $statusRaw = strtolower((string)($att->status ?? 'present'));
            $status = in_array($statusRaw, ['present', 'late', ''], true) ? 'present' : $statusRaw;

            $volunteerId = $vol?->volunteer_id ?? null;

            $email    = $att->school_email
                ?? ($vol->school_email ?? $vol->email ?? null);
            $schoolId = $att->school_id
                ?? ($vol->school_id ?? $vol->id_number ?? null);

            $avatar = $defaultAvatar;
            if ($vol) {
                if (!empty($vol->profile_picture_path)) {
                    $avatar = $this->toPublicStorageUrl((string)$vol->profile_picture_path) ?? $defaultAvatar;
                } elseif (!empty($vol->profile_picture_url)) {
                    $avatar = (string)$vol->profile_picture_url;
                }
            }

            $attendeesActualJs->push([
                'id'          => $volunteerId ?: ('walkin_' . ($att->attendance_id ?? uniqid())),
                'name'        => $vol?->full_name ?? $att->full_name ?? 'Walk-in',
                'course'      => optional($vol?->course)->course_name,
                'email'       => $email,
                'school_id'   => $schoolId,
                'status'      => $status,           // 'present' or custom
                'walk_in'     => true,
                'source'      => $this->formatAttendanceSource($att),
                'profile_pic' => $avatar,
                'profile_url' => $volunteerId ? route('volunteers.show', $volunteerId) : null,
            ]);
        }

        $attendeesActualJs = $attendeesActualJs->values();

        $absentCount = $attendeesActualJs->where('status', 'absent')->count();

        // ============================================================
        // Attendance UI gating
        // ============================================================
        $attendanceEnabled = in_array($derivedStatus, [self::STATUS_ONGOING, self::STATUS_COMPLETED], true);
        if ($actualCount > 0) {
            $attendanceEnabled = true;
        }

        $attendanceUi = [
            'enabled' => $attendanceEnabled,
            'message' => $attendanceEnabled
                ? null
                : 'Attendance is disabled for upcoming events. It becomes available when the event starts (or after an attendance import).',
        ];

        // Simple flag for "has attendance" used by JS gating
        $hasAttendanceImport = $actualCount > 0;

        // Max volunteers (optional column)
        $maxVolunteers = Schema::hasColumn('events', 'max_volunteers')
            ? ($event->max_volunteers ?? null)
            : null;

        // Default tab: Attendance if we have any actual attendance; otherwise Roster
        $defaultTab = $actualCount > 0 ? 'actual' : 'expected';

        // Per-event logs for the Event Details page
        $eventLogs = EventLog::where('event_id', $event->event_id)
            ->orderBy('timestamp', 'desc')
            ->get();

        $factLogs = FactLog::where('entity_type', 'Event')
            ->where('entity_id', $event->event_id)
            ->orderBy('timestamp', 'desc')
            ->limit(50)
            ->get();

        return view(self::EVENT_DETAILS_VIEW, compact(
            'event',
            'attendeesExpectedJs',
            'attendeesActualJs',
            'expectedCount',
            'actualCount',
            'presentCount',
            'lateCount',
            'walkInCount',
            'absentCount',
            'attendanceUi',
            'hasAttendanceImport',
            'maxVolunteers',
            'defaultTab',
            'eventLogs',
            'factLogs'
        ));
    }

    /**
     * Converts profile_picture_path into a public URL.
     */
    private function toPublicStorageUrl(string $path): ?string
    {
        $p = trim($path);
        if ($p === '') return null;

        $p = str_replace('\\', '/', $p);

        if (preg_match('~^https?://~i', $p)) return $p;

        $needle = '/storage/app/public/';
        if (stripos($p, $needle) !== false) {
            $p = substr($p, stripos($p, $needle) + strlen($needle));
        }

        if (stripos($p, '/public/') !== false && stripos($p, $needle) === false) {
            $p = substr($p, stripos($p, '/public/') + strlen('/public/'));
        }

        $p = ltrim($p, '/');

        return asset('storage/' . $p);
    }

    /**
     * Small helper to render a readable source/when label for attendance pills.
     */
    private function formatAttendanceSource($attendance): ?string
    {
        try {
            if (!empty($attendance->attendance_time)) {
                $dt = $attendance->attendance_time instanceof Carbon
                    ? $attendance->attendance_time
                    : Carbon::parse($attendance->attendance_time);

                return $dt->format('M d, Y · h:i A');
            }
        } catch (\Throwable $e) {
            // fall back to raw source
        }

        return $attendance->source ?? null;
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

            // Event log keeps the human-readable reason
            $this->logEvent(
                $event->event_id,
                $admin->admin_id,
                'Cancel',
                "Cancelled event. Reason: {$reason}"
            );

            // Fact log just records the action (no reason dump)
            $this->logFact(
                $admin->admin_id,
                $event,
                'Cancel',
                'Event cancelled.'
            );

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

            // Event log with optional reason
            $this->logEvent(
                $event->event_id,
                $admin->admin_id,
                'Restore',
                $details
            );

            // Fact log only records the action
            $this->logFact(
                $admin->admin_id,
                $event,
                'Restore',
                'Event restored.'
            );

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
