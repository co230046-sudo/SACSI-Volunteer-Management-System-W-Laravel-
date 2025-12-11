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
        // ✅ TOTAL VOLUNTEERS
        $totalVolunteers = VolunteerProfile::count();

        // ✅ VOLUNTEERS PER YEAR LEVEL (this also acts as "batch" categories)
        $volunteersPerLevel = VolunteerProfile::selectRaw('year_level, COUNT(*) as total')
            ->groupBy('year_level')
            ->orderBy('year_level')
            ->pluck('total', 'year_level');

        // ✅ EVENT COUNTS (MATCHES EVENT MANAGER STATUS)
        $upcomingEvents  = Event::where('status', 'planned')->count();   // upcoming
        $completedEvents = Event::where('status', 'completed')->count();
        $cancelledEvents = Event::where('status', 'cancelled')->count();

        // ✅ MOST ACTIVE VOLUNTEERS
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

        // ✅ RECENT VOLUNTEERS
        $recentVolunteers = VolunteerProfile::latest()->take(5)->get();

        // ✅ EVENTS THIS MONTH (STILL KEPT IF YOU WANT IT FOR CSV OR OTHER USES)
        $eventsThisMonth = Event::selectRaw('DAY(created_at) as day, COUNT(*) as total')
            ->whereMonth('created_at', now()->month)
            ->orderBy('day')
            ->groupBy('day')
            ->pluck('total', 'day');

        // ✅ ACTIVITY LOGS
        $activityLogs = ActivityLog::latest()->take(10)->get();

        // ✅ BATCH PARTICIPATION BY MONTH
        // "Batch" is interpreted here as the volunteer's year_level
        $batchParticipationByMonth = collect();

        if (DB::getSchemaBuilder()->hasTable('event_volunteers')) {
            // Join events + pivot + volunteer_profiles
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

            // Transform into: [ 'Jan 2025' => [ '1st Year' => 5, '2nd Year' => 3, ... ], ... ]
            $grouped = [];

            foreach ($rawBatch as $row) {
                $month = $row->month_label ?: 'Unknown Month';
                $batch = $row->batch ?: 'Unknown Batch';

                if (! isset($grouped[$month])) {
                    $grouped[$month] = [];
                }

                $grouped[$month][$batch] = (int) $row->total;
            }

            $batchParticipationByMonth = collect($grouped);
        }

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
            'batchParticipationByMonth' // ✅ pass to Blade
        ));
    }
}
