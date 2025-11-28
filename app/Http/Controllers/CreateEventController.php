<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use App\Models\EventType;
use App\Models\EventOrganizer;
use App\Models\FactLog;

class CreateEventController extends Controller
{
    public function create()
    {
        return view('create_event.create_event', [
            'eventTypes' => EventType::orderBy('label')->get(),
            'locations'  => Location::orderBy('barangay')->get(),
        ]);
    }

    public function store(Request $request)
    {
        // Store form temporarily
        session()->put('event_form_data', $request->all());

        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',   // FIXED
            'venue'          => 'nullable|string|max:255',  // FIXED

            'location_id'    => 'required|integer|exists:locations,location_id',
            'district_id'    => 'nullable|integer',

            'event_type_id'  => 'nullable|integer|exists:event_types,event_type_id',

            'start_datetime' => 'required|date',
            'end_datetime'   => 'nullable|date|after_or_equal:start_datetime',

            'organizers.name'      => 'array|max:3',
            'organizers.email'     => 'array|max:3',
            'organizers.contact'   => 'array|max:3',

            'organizers.name.*'    => 'nullable|string|max:255',
            'organizers.email.*'   => 'nullable|email|max:255',
            'organizers.contact.*' => 'nullable|string|max:255',
        ]);

        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return back()->with('error_modal', 'Authentication failed.');
        }

        /* ==============================
        CREATE EVENT
        ===============================*/
        $event = Event::create([
            'title'          => $request->title,
            'description'    => $request->description,
            'venue'          => $request->venue,
            'location_id'    => $request->location_id,
            'district_id'    => $request->district_id,
            'event_type_id'  => $request->event_type_id,
            'start_datetime' => $request->start_datetime,
            'end_datetime'   => $request->end_datetime,
            'status'         => 'planned',
            'created_by'     => $admin->admin_id,
        ]);

        /* ==============================
        SAVE ORGANIZERS
        ===============================*/
        if ($request->has('organizers.name')) {

            $names    = $request->organizers['name']    ?? [];
            $emails   = $request->organizers['email']   ?? [];
            $contacts = $request->organizers['contact'] ?? [];

            foreach ($names as $i => $name) {

                $name = trim($name);
                if (!$name) continue;

                EventOrganizer::create([
                    'event_id' => $event->event_id,
                    'name'     => $name,
                    'email'    => $emails[$i]   ?? null,
                    'contact'  => $contacts[$i] ?? null,
                ]);
            }
        }

        /* ==============================
        FACT LOG ENTRY
        ===============================*/
        $this->logFact(
            'Event Created',
            $admin->admin_id,
            $event,
            null,
            'Create',
            "Admin {$admin->username} created a new event titled “{$event->title}” (Event ID: {$event->event_id})."
        );


        /* ==============================
        CLEAR SAVED FORM DATA
        ==============================*/
        session()->forget('event_form_data');

        /* ==============================
        SUCCESS & REDIRECT TO EVENT DETAILS
        ==============================*/
        return redirect()
            ->route('event.details.show', $event->event_id)
            ->with('submit_success', "
                <div style='font-size:1.05rem; line-height:1.55;'>
                    ✔ <strong style='color:#28a745;'>Event Created Successfully</strong><br>
                    <strong>{$request->title}</strong> has been added.
                </div>
            ");

    }

    /**
     * Centralized FactLog helper with auto entity type inference
    */
    private function logFact(
        string $factType,
        $adminId = null,
        $entity = null,
        ?int $entityId = null,
        ?string $action = null,
        $details = null
    ): FactLog {

        $admin = Auth::guard('admin')->user();
        $adminId = is_numeric($adminId) ? (int)$adminId : ($admin->admin_id ?? null);

        $encodedDetails = is_array($details) || is_object($details)
            ? json_encode($details, JSON_UNESCAPED_UNICODE)
            : (string) $details;

        if (is_object($entity)) {
            $entityType = class_basename($entity);
            $modelKey = method_exists($entity, 'getKey') ? $entity->getKey() : null;
            $entityId = $entityId ?? $modelKey;
        } elseif (is_string($entity)) {
            $entityType = $entity;
        } else {
            $entityType = 'Unknown';
        }

        return FactLog::create([
            'fact_type'   => $factType,
            'admin_id'    => $adminId,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'action'      => $action,
            'details'     => $encodedDetails,
        ]);
    }
}
