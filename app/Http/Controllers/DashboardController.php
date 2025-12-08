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

    // ✅ VOLUNTEERS PER YEAR LEVEL
    $volunteersPerLevel = VolunteerProfile::selectRaw('year_level, COUNT(*) as total')
        ->groupBy('year_level')
        ->orderBy('year_level')
        ->pluck('total', 'year_level');

    // ✅ ✅ ✅ EVENT COUNTS (MATCHES EVENT MANAGER STATUS)
    $upcomingEvents  = Event::where('status', 'planned')->count();   // ✅ FIXED
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

    // ✅ EVENTS THIS MONTH
    $eventsThisMonth = Event::selectRaw('DAY(created_at) as day, COUNT(*) as total')
        ->whereMonth('created_at', now()->month)
        ->orderBy('day')
        ->groupBy('day')
        ->pluck('total', 'day');

    // ✅ ACTIVITY LOGS
    $activityLogs = ActivityLog::latest()->take(10)->get();

    return view('admin.dashboard', compact(
        'totalVolunteers',
        'volunteersPerLevel',
        'upcomingEvents',
        'completedEvents',
        'cancelledEvents',
        'topVolunteers',
        'recentVolunteers',
        'eventsThisMonth',
        'activityLogs'
    ));
}

}
