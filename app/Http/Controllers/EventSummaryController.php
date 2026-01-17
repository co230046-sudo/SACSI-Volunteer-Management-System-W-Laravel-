<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

use App\Models\Event;
use App\Models\EventFeedback;

class EventSummaryController extends Controller
{
    private const STATUS_PLANNED   = 'planned';
    private const STATUS_ONGOING   = 'ongoing';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_CANCELLED = 'cancelled';

    // Event summary page
    public function show(Request $request, Event $event)
    {
        // Common relations
        $event->load([
            'location',
            'eventType',
            'organizers',
        ]);

        // Attendance + expected roster
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

        // Status derive (read-only)
        $now   = Carbon::now();
        $start = $event->start_datetime ? Carbon::parse($event->start_datetime) : null;
        $end   = $event->end_datetime ? Carbon::parse($event->end_datetime) : null;

        $event->status = $this->deriveStatus($event->status, $start, $end, $now);

        // Expected roster
        $expectedRows  = $event->expectedVolunteers ?? collect();
        $expectedCount = (int) $expectedRows->count();

        // Actual attendance
        $attendanceRows = collect();
        if (Schema::hasTable('event_attendances')) {
            $attendanceRows = $event->attendances ?? collect();
        }

        $actualCount = (int) $attendanceRows->count();
        $hasAttendanceImport = $actualCount > 0;

        // attended = present / late / blank
        $attendedRows = $attendanceRows->filter(function ($att) {
            $s = strtolower((string) ($att->status ?? ''));
            return in_array($s, ['present', 'late', ''], true);
        });

        $attendedCount = (int) $attendedRows->count();
        $walkInCount   = (int) $attendanceRows->where('walk_in', 1)->count();

        // Absent = expected - attended (by volunteer_id overlap)
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

        $attendanceRate = $expectedCount > 0
            ? (int) round(($attendedCount / $expectedCount) * 100)
            : 0;

        // Top Year Level / Batch Year (based on attendedRows)
        $topYearLevel = $this->topGroupStat(
            $attendedRows,
            fn($att) => $att->volunteer?->year_level,
            fn($val) => $val ? "Year {$val}" : null,
            true
        );

        $topBatchYear = $this->topGroupStat(
            $attendedRows,
            fn($att) => $att->volunteer?->batch_year,
            fn($val) => $val ? "Batch {$val}" : null,
            true
        );

        // Chart mode
        $chartMode = $request->get('mode', 'actual');
        $chartMode = in_array($chartMode, ['actual', 'expected'], true) ? $chartMode : 'actual';

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
            $chartHint = 'Expected roster distribution';

            $chartData = $this->buildChartFromCounts([
                ['label' => 'Expected', 'count' => $expectedCount, 'color' => '#b23a45'],
                ['label' => 'Walk-in',  'count' => $walkInCount,   'color' => '#f59e0b'],
            ]);
        }

        // Feedbacks for comments drawer (no created_at in table)
        $feedbacks = collect();
        if (Schema::hasTable('event_feedbacks')) {
            $feedbacks = EventFeedback::query()
                ->where('event_id', $event->event_id)
                ->with(['volunteer'])
                ->orderByDesc('submitted_at')
                ->orderByDesc('feedback_id')
                ->limit(60)
                ->get()
                ->map(function ($fb) {
                    $vol = $fb->volunteer;

                    $name = $fb->full_name
                        ?? $vol?->full_name
                        ?? 'Unknown Volunteer';

                    $url = $vol ? route('volunteers.show', $vol->volunteer_id) : null;

                    $avatar = $vol?->avatar_url
                        ?? asset('storage/defaults/default_user.png');

                    $qa = [];
                    if (!is_null($fb->rating)) {
                        $qa[] = ['q' => 'Rating', 'a' => $fb->rating . '/5'];
                    }
                    if (!empty($fb->improve_next_time)) {
                        $qa[] = ['q' => 'Improve next time', 'a' => $fb->improve_next_time];
                    }
                    if (!empty($fb->issues_encountered)) {
                        $qa[] = ['q' => 'Issues encountered', 'a' => $fb->issues_encountered];
                    }
                    if (!empty($fb->other_comments)) {
                        $qa[] = ['q' => 'Other comments', 'a' => $fb->other_comments];
                    }

                    return (object) [
                        'volunteer_name' => $name,
                        'profile_url'    => $url,
                        'avatar'         => $avatar,
                        'qa'             => $qa,
                        'rating'         => $fb->rating ?? null,
                        'feedback_text'  => $fb->feedback_text ?? null,
                        'submitted_at'   => $fb->submitted_at ?? null,
                    ];
                });
        }

        return view('event_summary.event_summary', [
            'event' => $event,

            'expectedCount' => $expectedCount,
            'attendedCount' => $attendedCount,
            'attendanceRate' => $attendanceRate,

            'hasAttendanceImport' => $hasAttendanceImport,
            'attendanceImportedTotal' => $actualCount,

            'walkInCount' => $walkInCount,
            'absentCount' => $absentCount,

            'topYearLevel' => $topYearLevel,
            'topBatchYear' => $topBatchYear,

            'chartMode' => $chartMode,
            'chartHint' => $chartHint,
            'chartData' => $chartData,

            'feedbacks' => $feedbacks,
        ]);
    }

    // Top group stat from attended rows
    private function topGroupStat($attendedRows, callable $valueFn, callable $labelFn, bool $normalizeNumeric = false): array
    {
        $total = (int) $attendedRows->count();
        if ($total <= 0) {
            return ['label' => null, 'count' => 0, 'pct' => 0.0];
        }

        $counts = [];
        foreach ($attendedRows as $att) {
            if (empty($att->volunteer_id)) continue;

            $val = $valueFn($att);
            if ($val === null || $val === '') continue;

            if ($normalizeNumeric) {
                $val = (int) $val;
                if ($val <= 0) continue;
                $k = (string) $val;
            } else {
                $k = (string) $val;
                if (trim($k) === '') continue;
            }

            $counts[$k] = ($counts[$k] ?? 0) + 1;
        }

        if (empty($counts)) {
            return ['label' => null, 'count' => 0, 'pct' => 0.0];
        }

        arsort($counts);
        $topKey = array_key_first($counts);
        $topCount = (int) ($counts[$topKey] ?? 0);

        $labelVal = $normalizeNumeric ? (int) $topKey : $topKey;

        $label = $labelFn($labelVal);
        $pct = $total > 0 ? round(($topCount / $total) * 100, 1) : 0.0;

        return [
            'label' => $label ?: (string) $topKey,
            'count' => $topCount,
            'pct'   => $pct,
        ];
    }

    // Build chart data with percentages
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

        return array_values(array_filter($out, fn($x) => ($x['count'] ?? 0) > 0));
    }

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
