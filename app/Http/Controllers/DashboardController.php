<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        /* ===========================================================
            PROTECT AGAINST MISSING TABLES
        =========================================================== */
        if (!Schema::hasTable('volunteer_profiles')) {
            return view('dashboard.dashboard', [
                'totalVolunteers' => 0,
                'activeVolunteers' => 0,
                'growthRate' => 0,
                'averageAttendance' => 0,
                'eventSuccessRate' => 0,
                'eventStatus' => ['upcoming' => 0, 'completed' => 0, 'cancelled' => 0],
                'volunteersByCourse' => collect([]),
                'yearLevels' => collect([]),
            ]);
        }

        /* ===========================================================
            TOTAL VOLUNTEERS
        =========================================================== */
        $totalVolunteers = DB::table('volunteer_profiles')->count();

        /* ===========================================================
            ACTIVE VOLUNTEERS
        =========================================================== */
        $activeVolunteers = Schema::hasColumn('volunteer_profiles', 'status')
            ? DB::table('volunteer_profiles')->where('status', 'active')->count()
            : 0;

        /* ===========================================================
            GROWTH RATE (last 30 days)
        =========================================================== */
        $monthCount = DB::table('volunteer_profiles')
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $prevMonthCount = DB::table('volunteer_profiles')
            ->whereBetween('created_at', [
                now()->subDays(60),
                now()->subDays(30)
            ])
            ->count();

        $growthRate = $prevMonthCount > 0
            ? round((($monthCount - $prevMonthCount) / $prevMonthCount) * 100)
            : 0;

        /* ===========================================================
            AVERAGE ATTENDANCE (event_attendances)
        =========================================================== */
        $averageAttendance = 0;

        if (Schema::hasTable('event_attendances')) {
            if (Schema::hasColumn('event_attendances', 'percentage')) {
                $averageAttendance = DB::table('event_attendances')->avg('percentage') ?? 0;
            } elseif (Schema::hasColumn('event_attendances', 'attendance_rate')) {
                $averageAttendance = DB::table('event_attendances')->avg('attendance_rate') ?? 0;
            }
        }

        /* ===========================================================
            EVENT SUCCESS RATE (events)
        =========================================================== */
        $eventSuccessRate = 0;
        $eventStatus = ['upcoming' => 0, 'completed' => 0, 'cancelled' => 0];

        if (Schema::hasTable('events')) {

            $eventStatus['upcoming'] = DB::table('events')->where('status', 'upcoming')->count();
            $eventStatus['completed'] = DB::table('events')->where('status', 'completed')->count();
            $eventStatus['cancelled'] = DB::table('events')->where('status', 'cancelled')->count();

            $totalEvents = DB::table('events')->count();

            $eventSuccessRate = $totalEvents > 0
                ? round(($eventStatus['completed'] / $totalEvents) * 100)
                : 0;
        }

        /* ===========================================================
            VOLUNTEERS BY COURSE (smart auto-detection)
        =========================================================== */
        $groupColumn = 'year_level'; // fallback

        foreach (['course', 'strand', 'school'] as $possible) {
            if (Schema::hasColumn('volunteer_profiles', $possible)) {
                $groupColumn = $possible;
                break;
            }
        }

        $volunteersByCourse = DB::table('volunteer_profiles')
            ->select("$groupColumn as label", DB::raw('COUNT(*) as total'))
            ->groupBy($groupColumn)
            ->get();

        /* ===========================================================
            YEAR LEVEL DISTRIBUTION
        =========================================================== */
        $yearLevels = Schema::hasColumn('volunteer_profiles', 'year_level')
            ? DB::table('volunteer_profiles')
                ->select('year_level', DB::raw('COUNT(*) as total'))
                ->groupBy('year_level')
                ->get()
            : collect([]);

        /* ===========================================================
            RETURN TO DASHBOARD VIEW
        =========================================================== */
        return view('dashboard.dashboard', compact(
            'totalVolunteers',
            'activeVolunteers',
            'growthRate',
            'averageAttendance',
            'eventSuccessRate',
            'eventStatus',
            'volunteersByCourse',
            'yearLevels'
        ));
    }
}
