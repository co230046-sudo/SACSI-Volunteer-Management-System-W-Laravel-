<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\EventType;
use App\Models\EventLog;
use App\Models\FactLog;

class EventTypeController extends Controller
{
    public function create()
    {
        return view('event_types.create');
    }

    public function store(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return back()->withErrors(['auth' => 'Authentication failed.'])->withInput();
        }

        $data = $request->validate([
            'label' => [
                'required', 'string', 'max:100', 'unique:event_types,label',
                'regex:/^(?=.*[A-Za-z])[A-Za-z0-9][A-Za-z0-9\s\-\(\)\.&\/]{2,99}$/'
            ],
        ], [
            'label.regex' => 'Please use a valid event type name (letters required; no random keyboard spam).'
        ]);

        try {
            return DB::transaction(function () use ($data, $admin) {

                $label = trim($data['label']);

                $baseKey = Str::slug($label, '_');
                $baseKey = $baseKey !== '' ? $baseKey : 'type';

                $typeKey = $baseKey;
                $n = 2;
                while (EventType::where('type_key', $typeKey)->exists()) {
                    $typeKey = $baseKey . '_' . $n;
                    $n++;
                }

                $eventType = EventType::create([
                    'type_key'   => $typeKey,
                    'label'      => $label,
                    'icon_class' => null,
                ]);

                EventLog::create([
                    'event_id'  => null,
                    'admin_id'  => $admin->admin_id,
                    'action'    => 'Create Event Type',
                    'details'   => 'Created event type "' . $eventType->label . '" (ID: ' . $eventType->event_type_id . ', Key: ' . $eventType->type_key . ').',
                    'timestamp' => now(),
                ]);

                FactLog::create([
                    'admin_id'    => $admin->admin_id,
                    'entity_type' => 'EventType',
                    'entity_id'   => $eventType->event_type_id,
                    'action'      => 'Create',
                    'details'     => json_encode([
                        'event_type_id'  => $eventType->event_type_id,
                        'type_key'       => $eventType->type_key,
                        'label'          => $eventType->label,
                        'admin_id'       => $admin->admin_id,
                        'admin_username' => $admin->username ?? null,
                    ], JSON_UNESCAPED_UNICODE),
                    'timestamp'   => now(),
                ]);

                return redirect()
                    ->route('events.create')
                    ->with('submit_success', $eventType->label);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['server' => 'Failed to add event type: ' . $e->getMessage()])->withInput();
        }
    }

    // =========================
    // NEW: JSON list for modal
    // =========================
    public function indexJson(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return response()->json(['message' => 'Unauthorized'], 401);

        $q = trim((string)$request->get('search', ''));

        $types = EventType::query()
            ->select('event_type_id', 'type_key', 'label')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where('label', 'like', "%{$q}%")
                   ->orWhere('type_key', 'like', "%{$q}%");
            })
            ->orderBy('label')
            ->limit(200)
            ->get();

        return response()->json($types);
    }

    // =========================
    // NEW: Update label
    // =========================
    public function update(Request $request, EventType $eventType)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return response()->json(['message' => 'Unauthorized'], 401);

        $data = $request->validate([
            'label' => [
                'required','string','max:100',
                'unique:event_types,label,' . $eventType->event_type_id . ',event_type_id',
                'regex:/^(?=.*[A-Za-z])[A-Za-z0-9][A-Za-z0-9\s\-\(\)\.&\/]{2,99}$/'
            ],
        ], [
            'label.regex' => 'Please use a valid event type name (letters required; no random keyboard spam).'
        ]);

        try {
            return DB::transaction(function () use ($data, $admin, $eventType) {
                $oldLabel = $eventType->label;
                $newLabel = trim($data['label']);

                // Optional: keep type_key stable (recommended). If you want it to update too, tell me.
                $eventType->label = $newLabel;
                $eventType->save();

                EventLog::create([
                    'event_id'  => null,
                    'admin_id'  => $admin->admin_id,
                    'action'    => 'Edit Event Type',
                    'details'   => 'Edited event type ID ' . $eventType->event_type_id . ' from "' . $oldLabel . '" to "' . $newLabel . '".',
                    'timestamp' => now(),
                ]);

                FactLog::create([
                    'admin_id'    => $admin->admin_id,
                    'entity_type' => 'EventType',
                    'entity_id'   => $eventType->event_type_id,
                    'action'      => 'Edit',
                    'details'     => json_encode([
                        'event_type_id' => $eventType->event_type_id,
                        'old_label'     => $oldLabel,
                        'new_label'     => $newLabel,
                        'admin_id'      => $admin->admin_id,
                        'admin_username'=> $admin->username ?? null,
                    ], JSON_UNESCAPED_UNICODE),
                    'timestamp'   => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'event_type_id' => $eventType->event_type_id,
                    'label' => $eventType->label,
                    'type_key' => $eventType->type_key,
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Update failed: ' . $e->getMessage()], 422);
        }
    }

    // =========================
    // NEW: Delete type
    // =========================
    public function destroy(EventType $eventType)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return response()->json(['message' => 'Unauthorized'], 401);

        // Safety: if events reference this type, block deletion
        $inUse = \App\Models\Event::where('event_type_id', $eventType->event_type_id)->exists();
        if ($inUse) {
            return response()->json([
                'message' => 'Cannot delete this type because it is used by existing events.'
            ], 409);
        }

        try {
            return DB::transaction(function () use ($admin, $eventType) {
                $label = $eventType->label;
                $id = $eventType->event_type_id;
                $key = $eventType->type_key;

                $eventType->delete();

                EventLog::create([
                    'event_id'  => null,
                    'admin_id'  => $admin->admin_id,
                    'action'    => 'Delete Event Type',
                    'details'   => 'Deleted event type "' . $label . '" (ID: ' . $id . ', Key: ' . $key . ').',
                    'timestamp' => now(),
                ]);

                FactLog::create([
                    'admin_id'    => $admin->admin_id,
                    'entity_type' => 'EventType',
                    'entity_id'   => $id,
                    'action'      => 'Delete',
                    'details'     => json_encode([
                        'event_type_id' => $id,
                        'type_key'      => $key,
                        'label'         => $label,
                        'admin_id'      => $admin->admin_id,
                        'admin_username'=> $admin->username ?? null,
                    ], JSON_UNESCAPED_UNICODE),
                    'timestamp'   => now(),
                ]);

                return response()->json(['success' => true]);
            });
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Delete failed: ' . $e->getMessage()], 422);
        }
    }
}
