<?php

namespace App\Http\Controllers;

use App\Models\VolunteerProfile;
use App\Models\Event;
use Illuminate\Support\Facades\DB;

class ApiDashboardController extends Controller
{
    public function fetch()
    {
        return response()->json([

            'totalVolunteers' => VolunteerProfile::count(),

            'eventStats' => [
                'upcoming'  => Event::where('status','planned')->count(),
                'completed' => Event::where('status','completed')->count(),
                'cancelled' => Event::where('status','cancelled')->count(),
            ],

            'volunteersPerLevel' => VolunteerProfile::selectRaw('year_level, COUNT(*) as total')
                ->groupBy('year_level')
                ->orderBy('year_level')
                ->pluck('total', 'year_level'),

            'batchParticipation' => $this->getBatchParticipation(),

        ]);
    }


    private function getBatchParticipation()
    {
        $raw = DB::table('event_volunteers as ev')
            ->join('events as e', 'ev.event_id', '=', 'e.id')
            ->join('volunteer_profiles as vp', 'ev.volunteer_profile_id', '=', 'vp.id')
            ->selectRaw("
                DATE_FORMAT(e.created_at, '%b %Y') as month_label,
                vp.year_level as batch,
                COUNT(*) as total
            ")
            ->groupBy('month_label', 'batch')
            ->orderBy('month_label')
            ->get();

        $output = [];
        foreach ($raw as $row) {
            $output[$row->month_label][$row->batch] = (int)$row->total;
        }
        return $output;
    }
}
