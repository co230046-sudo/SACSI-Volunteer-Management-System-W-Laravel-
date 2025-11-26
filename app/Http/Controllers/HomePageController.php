<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Carbon\Carbon;

class HomePageController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        /* ============================================================
           ONGOING EVENTS 
           start_datetime <= now <= end_datetime
        ============================================================ */
        $ongoingEvents = Event::with(['location'])
            ->whereNotNull('start_datetime')
            ->whereNotNull('end_datetime')
            ->where('start_datetime', '<=', $now)
            ->where('end_datetime', '>=', $now)
            ->orderBy('start_datetime', 'asc')
            ->get();

        /* ============================================================
           UPCOMING EVENTS 
           start_datetime > now
        ============================================================ */
        $upcomingEvents = Event::with(['location'])
            ->whereNotNull('start_datetime')
            ->where('start_datetime', '>', $now)
            ->orderBy('start_datetime', 'asc')
            ->get();

        return view('homepage.homepage', compact('ongoingEvents', 'upcomingEvents'));
    }
}
