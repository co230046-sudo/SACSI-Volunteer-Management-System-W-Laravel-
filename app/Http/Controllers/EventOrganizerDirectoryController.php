<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EventOrganizer;
use App\Models\EventLog;
use App\Services\FactLogger;

class EventOrganizerDirectoryController extends Controller
{
    // Organizer directory search (AJAX)
    public function index(Request $request)
    {
        $q = trim((string) $request->get('search', ''));

        $rows = EventOrganizer::query()
            ->select('organizer_id', 'name', 'email', 'contact')
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('contact', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get()
            ->unique(function ($r) {
                return strtolower(trim($r->name)) . '|'
                    . strtolower(trim($r->email ?? '')) . '|'
                    . strtolower(trim($r->contact ?? ''));
            })
            ->values();

        return response()->json($rows);
    }

    // Update organizer (AJAX)
    public function update(Request $request, EventOrganizer $organizer, FactLogger $factLogger)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return response()->json(['message' => 'Unauthorized.'], 401);

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['nullable', 'email', 'max:255'],
            'contact' => ['nullable', 'string', 'max:50'],
        ]);

        $before = $organizer->only(['organizer_id', 'name', 'email', 'contact']);

        $organizer->update($data);

        $after = $organizer->only(['organizer_id', 'name', 'email', 'contact']);

        EventLog::create([
            'event_id' => null,
            'admin_id' => $admin->admin_id,
            'action'   => 'Organizer Directory Update',
            'details'  => "Updated organizer #{$organizer->organizer_id}: {$before['name']} → {$after['name']}",
        ]);

        $factLogger->log(
            type: 'event_organizer.updated',
            action: 'Edit',
            entity: $organizer,
            entityId: $organizer->organizer_id,
            details: [
                'summary' => 'Edited Event Type - "' . ($after['name'] ?? 'Unknown') . '"',
                'data' => [
                    'before' => $before,
                    'after'  => $after,
                ],
            ],
            adminId: $admin->admin_id
        );

        return response()->json($organizer);
    }

    // Delete organizer (AJAX)
    public function destroy(EventOrganizer $organizer, FactLogger $factLogger)
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) return response()->json(['message' => 'Unauthorized.'], 401);

        $before = $organizer->only(['organizer_id', 'name', 'email', 'contact']);

        $organizerId   = $organizer->organizer_id;
        $organizerName = $organizer->name;

        $organizer->delete();

        EventLog::create([
            'event_id' => null,
            'admin_id' => $admin->admin_id,
            'action'   => 'Organizer Directory Delete',
            'details'  => "Deleted organizer #{$organizerId}: {$organizerName}",
        ]);

        $factLogger->log(
            type: 'event_organizer.deleted',
            action: 'Delete',
            entity: 'EventOrganizer',
            entityId: $organizerId,
            details: [
                'summary' => 'Deleted Event Type - "' . ($organizerName ?? 'Unknown') . '"',
                'data' => [
                    'deleted' => $before,
                ],
            ],
            adminId: $admin->admin_id
        );

        return response()->json(['ok' => true]);
    }
}
