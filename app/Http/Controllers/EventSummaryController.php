<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

use App\Models\Event;

class EventSummaryController extends Controller
{
    private const STATUS_PLANNED   = 'planned';
    private const STATUS_ONGOING   = 'ongoing';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_CANCELLED = 'cancelled';

    /**
     * Show Event Summary page.
     * Route should be:
     *   GET /events/{event:event_id}/summary  -> name: events.summary
     */
    public function show(Request $request, Event $event)
    {
        // Load what the summary page commonly needs.
        // (Does NOT modify your DB; read-only.)
        $event->load([
            'location',
            'eventType',
            'organizers',
        ]);

        // If attendance table/relationship exists, load it.
        // Your EventDetailsController checks Schema::hasTable('event_attendances'), so we do same.
        if (Schema::hasTable('event_attendances')) {
            $event->load([
                'attendances.volunteer.course',
                'expectedVolunteers.volunteer.course',
            ]);
        } else {
            $event->load([
                'expectedVolunteers.volunteer.course',
            ]);
        }

        // ----------------------------
        // Status derive (same idea as EventDetailsController)
        // ----------------------------
        $now   = Carbon::now();
        $start = $event->start_datetime ? Carbon::parse($event->start_datetime) : null;
        $end   = $event->end_datetime   ? Carbon::parse($event->end_datetime)   : null;

        $derivedStatus = $this->deriveStatus($event->status, $start, $end, $now);
        $event->status = $derivedStatus;

        // ----------------------------
        // Expected (Roster)
        // ----------------------------
        $expectedRows  = $event->expectedVolunteers ?? collect();
        $expectedCount = (int) $expectedRows->count();

        // ----------------------------
        // Actual attendance
        // ----------------------------
        $attendanceRows = collect();
        if (Schema::hasTable('event_attendances')) {
            $attendanceRows = $event->attendances ?? collect();
        }

        $actualCount = (int) $attendanceRows->count();
        $hasAttendanceImport = $actualCount > 0;

        // Treat "present" and "late" as attended (same as you do in details)
        $attendedRows = $attendanceRows->filter(function ($att) {
            $s = strtolower((string) ($att->status ?? ''));
            return in_array($s, ['present', 'late', ''], true);
        });

        $presentCount = (int) $attendedRows->count();

        // Walk-ins (based on walk_in flag in your details controller)
        $walkInCount = (int) $attendanceRows->where('walk_in', 1)->count();

        // Absent = expected roster - those who attended (based on volunteer_id overlap)
        $attendedVolunteerIds = $attendedRows
            ->whereNotNull('volunteer_id')
            ->pluck('volunteer_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $expectedVolunteerIds = $expectedRows
            ->pluck('volunteer_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $absentCount = (int) $expectedVolunteerIds
            ->diff($attendedVolunteerIds)
            ->count();

        // For the summary tiles you currently use:
        $attendedCount = $presentCount; // attended = present+late merged
        $attendanceRate = $expectedCount > 0
            ? (int) round(($attendedCount / $expectedCount) * 100)
            : 0;

        // ----------------------------
        // Capacity (optional; your blade currently supports it)
        // ----------------------------
        $maxVolunteers = Schema::hasColumn('events', 'max_volunteers')
            ? ($event->max_volunteers ?? null)
            : null;

        $capacityUsed = null;
        if (!empty($maxVolunteers) && (int) $maxVolunteers > 0) {
            $capacityUsed = (int) round(($expectedCount / (int) $maxVolunteers) * 100);
        }

        // ----------------------------
        // Chart mode + hint
        // ----------------------------
        $chartMode = $request->get('mode', 'actual'); // "actual" or "expected"
        $chartMode = in_array($chartMode, ['actual', 'expected'], true) ? $chartMode : 'actual';

        // Build distribution data:
        // - "actual" distribution: Present / Absent / Walk-in
        // - "expected" distribution: Expected / (optional) something else
        // NOTE: Your JS expects items with label, count, percentage, color.
        if ($chartMode === 'actual') {
            $chartHint = $hasAttendanceImport
                ? 'Based on imported attendance (present/absent + walk-ins)'
                : 'No attendance import yet — showing roster-based approximation';

            $chartData = $this->buildChartFromCounts([
                ['label' => 'Attended', 'count' => $attendedCount, 'color' => '#1f9d55'],
                ['label' => 'Absent',   'count' => $absentCount,   'color' => '#dc3545'],
                ['label' => 'Walk-in',  'count' => $walkInCount,   'color' => '#f59e0b'],
            ]);
        } else {
            // Placeholder batch/year concept (as you asked) — for now just a note.
            // We keep chart meaningful without inventing data.
            $chartHint = 'Expected roster distribution (batch/year placeholder coming soon)';

            $chartData = $this->buildChartFromCounts([
                ['label' => 'Expected', 'count' => $expectedCount, 'color' => '#b23a45'],
                ['label' => 'Walk-in',  'count' => $walkInCount,   'color' => '#f59e0b'],
            ]);
        }

        // For blade meta:
        $attendanceImportedTotal = $actualCount;

        // IMPORTANT: return your summary view (update the view path if yours is different)
        // Based on your blade, you likely have: resources/views/event_summary/event_summary.blade.php
        return view('event_summary.event_summary', compact(
            'event',
            'expectedCount',
            'attendedCount',
            'attendanceRate',
            'hasAttendanceImport',
            'attendanceImportedTotal',
            'chartMode',
            'chartHint',
            'chartData',
            'maxVolunteers',
            'capacityUsed'
        ));
    }

    /**
     * Build chart array with percentage computed from counts.
     */
    private function buildChartFromCounts(array $items): array
    {
        $total = 0;
        foreach ($items as $it) {
            $total += max(0, (int) ($it['count'] ?? 0));
        }

        $out = [];
        foreach ($items as $it) {
            $count = max(0, (int) ($it['count'] ?? 0));
            $pct = $total > 0 ? round(($count / $total) * 100, 1) : 0;

            $out[] = [
                'label'      => (string) ($it['label'] ?? 'Unknown'),
                'count'      => $count,
                'percentage' => $pct,
                'color'      => (string) ($it['color'] ?? '#9ca3af'),
            ];
        }

        // Remove zero slices so the conic-gradient doesn’t get weird
        return array_values(array_filter($out, fn($x) => ($x['count'] ?? 0) > 0));
    }

    /**
     * Same logic style as your EventDetailsController deriveStatus.
     */
    private function deriveStatus(?string $stored, ?Carbon $start, ?Carbon $end, Carbon $now): string
    {
        $stored = strtolower((string) ($stored ?? self::STATUS_PLANNED));

        if (in_array($stored, [self::STATUS_CANCELLED, self::STATUS_COMPLETED], true)) {
            return $stored;
        }

        if (!$start || !$end) return self::STATUS_PLANNED;

        if ($now->lt($start)) return self::STATUS_PLANNED;
        if ($now->betweenIncluded($start, $end)) return self::STATUS_ONGOING;

        return self::STATUS_COMPLETED;
    }
}
