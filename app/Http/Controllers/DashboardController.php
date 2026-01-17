<?php

namespace App\Http\Controllers;

use App\Models\VolunteerProfile;
use App\Models\Event;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        /* ============================================================
           ✅ TOTAL VOLUNTEERS
        ============================================================ */
        $totalVolunteers = VolunteerProfile::count();

        /* ============================================================
           ✅ VOLUNTEERS PER YEAR LEVEL
        ============================================================ */
        $volunteersPerLevel = VolunteerProfile::selectRaw('year_level, COUNT(*) as total')
            ->groupBy('year_level')
            ->orderBy('year_level')
            ->pluck('total', 'year_level');

        /* ============================================================
           ✅ EVENT COUNTS (MATCHES EVENT MANAGER STATUS)
        ============================================================ */
        $upcomingEvents  = Event::where('status', 'planned')->count();
        $completedEvents = Event::where('status', 'completed')->count();
        $cancelledEvents = Event::where('status', 'cancelled')->count();

        /* ============================================================
           ✅ MOST ACTIVE VOLUNTEERS
        ============================================================ */
        $topVolunteers = collect();

        if (DB::getSchemaBuilder()->hasTable('event_volunteers')) {
            $topVolunteers = DB::table('event_volunteers')
                ->select('volunteer_profile_id', DB::raw('COUNT(*) as total'))
                ->groupBy('volunteer_profile_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(function ($row) {
                    $row->profile = VolunteerProfile::find($row->volunteer_profile_id);
                    return $row;
                });
        }

        /* ============================================================
           ✅ RECENT VOLUNTEERS
        ============================================================ */
        $recentVolunteers = VolunteerProfile::latest()->take(5)->get();

        /* ============================================================
           ✅ EVENTS THIS MONTH
        ============================================================ */
        $eventsThisMonth = Event::selectRaw('DAY(created_at) as day, COUNT(*) as total')
            ->whereMonth('created_at', now()->month)
            ->orderBy('day')
            ->groupBy('day')
            ->pluck('total', 'day');

        /* ============================================================
           ✅ ACTIVITY LOGS
        ============================================================ */
        $activityLogs = ActivityLog::latest()->take(10)->get();

        /* ============================================================
           ✅ BATCH PARTICIPATION BY MONTH (RAW DATA)
        ============================================================ */
        $batchParticipationByMonth = collect();

        if (DB::getSchemaBuilder()->hasTable('event_volunteers')) {
            $rawBatch = DB::table('event_volunteers as ev')
                ->join('events as e', 'ev.event_id', '=', 'e.id')
                ->join('volunteer_profiles as vp', 'ev.volunteer_profile_id', '=', 'vp.id')
                ->selectRaw("
                    DATE_FORMAT(e.created_at, '%Y-%m') as ym,
                    DATE_FORMAT(e.created_at, '%b %Y') as month_label,
                    vp.year_level as batch,
                    COUNT(*) as total
                ")
                ->groupBy('ym', 'month_label', 'batch')
                ->orderBy('ym')
                ->get();

            // Convert to: [ 'Jan 2025' => ['1st Year'=>5, '2nd Year'=>3], ... ]
            $grouped = [];

            foreach ($rawBatch as $row) {
                $month = $row->month_label ?: 'Unknown Month';
                $batch = $row->batch ?: 'Unknown Batch';

                if (!isset($grouped[$month])) {
                    $grouped[$month] = [];
                }

                $grouped[$month][$batch] = (int)$row->total;
            }

            $batchParticipationByMonth = collect($grouped);
        }

        /* ============================================================
           ✅ FILLER DATA FOR BATCH PARTICIPATION BY MONTH
           Ensures charts ALWAYS show Jan–Dec + all batches
        ============================================================ */

        // Step 1: ALL MONTHS (current year)
        $allMonths = [];
        for ($m = 1; $m <= 12; $m++) {
            $label = date("M Y", strtotime(now()->year . "-$m-01"));
            $allMonths[$label] = [];
        }

        // Step 2: ALL BATCHES (distinct year levels)
        $allBatches = VolunteerProfile::select('year_level')
            ->distinct()
            ->pluck('year_level')
            ->filter()
            ->values()
            ->toArray();

        if (empty($allBatches)) {
            $allBatches = ['Unknown Batch'];
        }

        // Step 3: Merge real data + filler zero values
        $filledData = [];

        foreach ($allMonths as $month => $blank) {
            $filledData[$month] = [];

            foreach ($allBatches as $batch) {
                $filledData[$month][$batch] =
                    $batchParticipationByMonth[$month][$batch] ?? 0;
            }
        }

        $batchParticipationByMonth = collect($filledData);

        /* ============================================================
           RETURN VIEW
        ============================================================ */
        return view('admin.dashboard', compact(
            'totalVolunteers',
            'volunteersPerLevel',
            'upcomingEvents',
            'completedEvents',
            'cancelledEvents',
            'topVolunteers',
            'recentVolunteers',
            'eventsThisMonth',
            'activityLogs',
            'batchParticipationByMonth'
        ));
    }
}
