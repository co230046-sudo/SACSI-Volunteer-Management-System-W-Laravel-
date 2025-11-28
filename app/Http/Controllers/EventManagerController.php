<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EventManagerController extends Controller
{
    public function index()
    {
        /* ============================
           LOAD EVENTS BY STATUS
        ============================ */

        $upcomingEvents = Event::where('status', 'planned')
            ->orderBy('start_datetime', 'asc')
            ->get();

        $ongoingEvents = Event::where('status', 'ongoing')
            ->orderBy('start_datetime', 'asc')
            ->get();

        $completedEvents = Event::where('status', 'completed')
            ->orderBy('start_datetime', 'desc')
            ->get();

        $cancelledEvents = Event::where('status', 'cancelled')
            ->orderBy('start_datetime', 'desc')
            ->get();


        return view('manage_event.manage_event', compact(
            'upcomingEvents',
            'ongoingEvents',
            'completedEvents',
            'cancelledEvents'
        ));
    }
}
