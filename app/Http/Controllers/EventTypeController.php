<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Models\EventType;
use App\Models\EventLog;
use App\Models\FactLog;

class EventTypeController extends Controller
{
    public function create()
    {
        return view('event_types.create'); // create this blade
    }

    public function store(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return back()->withErrors(['auth' => 'Authentication failed.'])->withInput();
        }

        $data = $request->validate([
            'label' => 'required|string|max:255|unique:event_types,label',
        ]);

        try {
            DB::beginTransaction();

            $eventType = EventType::create($data);

            // ✅ EventLog (event_id is null; details explains what happened)
            EventLog::create([
                'event_id' => null,
                'admin_id' => $admin->admin_id,
                'action'   => 'Create Event Type',
                'details'  => "Created event type “{$eventType->label}” (Event Type ID: {$eventType->event_type_id}).",
            ]);

            // ✅ FactLog (entity = EventType)
            FactLog::create([
                'admin_id'    => $admin->admin_id,
                'entity_type' => 'EventType',
                'entity_id'   => $eventType->event_type_id,
                'action'      => 'Create',
                'details'     => json_encode([
                    'event_type_id' => $eventType->event_type_id,
                    'label'         => $eventType->label,
                    'admin_id'      => $admin->admin_id,
                    'admin_username'=> $admin->username ?? null,
                ], JSON_UNESCAPED_UNICODE),
                'timestamp'   => now(),
            ]);

            DB::commit();

            return redirect()
                ->route('events.create')
                ->with('submit_success', 'Event type added.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['server' => 'Failed to add event type: ' . $e->getMessage()])->withInput();
        }
    }
}
