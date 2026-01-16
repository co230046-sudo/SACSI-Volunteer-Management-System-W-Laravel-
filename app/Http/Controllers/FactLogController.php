<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\FactLog;

// ✅ Use FactLogger service (single source of truth for writing logs)
use App\Services\FactLogger;

class FactLogController extends Controller
{
    private FactLogger $factLogger;

    public function __construct(FactLogger $factLogger)
    {
        $this->factLogger = $factLogger;
    }

    /**
     * Log an action into fact_logs
     *
     * NOTE:
     * - This is kept for compatibility (older code may still call it),
     *   but it now writes via FactLogger so logging stays consistent.
     *
     * @param string $factTypeName
     * @param string|null $entityType
     * @param int|null $entityId
     * @param string|null $action
     * @param string|null $details
     * @param int|null $importId (unused but kept for compatibility)
     */
    public function logAction(
        string $factTypeName,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $action = null,
        ?string $details = null,
        ?int $importId = null
    ): void {
        $admin = Auth::guard('admin')->user();

        $type = $factTypeName; // keep behavior
        $act  = $action ?? $factTypeName;

        // Prefer entityType, fallback to factTypeName
        $entity = $entityType ?? $factTypeName;
        $id     = $entityId;

        // Details can be plain string or JSON string; normalize to array for FactLogger
        $detailsArray = $this->normalizeDetailsToArray($details);

        $this->factLogger->log(
            $type,                          // type
            $act,                           // action
            $entity,                        // entity type
            $id,                            // entity id
            $detailsArray,                  // details (array)
            $admin?->admin_id ? (int)$admin->admin_id : null
        );
    }

    /**
     * Display list of logs (humanizes JSON details for UI)
     */
    public function index(Request $request)
    {
        $query = FactLog::with(['admin']);

        if ($request->filled('fact_type')) {
            $query->where('entity_type', 'like', '%' . $request->fact_type . '%');
        }

        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('details', 'like', "%$search%")
                  ->orWhere('action', 'like', "%$search%")
                  ->orWhere('entity_type', 'like', "%$search%");
            });
        }

        $logs = $query->orderBy('timestamp', 'desc')->paginate(20);

        // ✅ Add computed display fields (so Blade can show friendly text)
        $logs->getCollection()->transform(function ($log) {
            $log->details_text = $this->formatDetailsForHumans($log->details, $log->entity_type, $log->action);
            $log->action_text  = $this->formatActionForHumans($log->action);
            return $log;
        });

        return view('fact_logs.index', compact('logs'));
    }

    /**
     * Delete a specific fact log entry
     */
    public function destroy(int $id)
    {
        $log = FactLog::findOrFail($id);
        $admin = Auth::guard('admin')->user();

        if ($admin?->role !== 'super_admin') {
            return redirect()->back()->with('error', 'Only super admins can delete logs.');
        }

        $log->delete();

        // Log the deletion action via FactLogger
        $this->factLogger->log(
            'Fact Log Deleted',
            'Delete',
            'FactLog',
            $id,
            [
                'fact_log_id' => $id,
                'message' => "Fact log ID {$id} deleted by {$admin->username}",
            ],
            (int)$admin->admin_id
        );

        return redirect()->back()->with('success', 'Log deleted successfully.');
    }

    // ============================================================
    // Helpers
    // ============================================================

    /**
     * Convert $details (string|null) into array for FactLogger.
     * - If it's JSON, decode it.
     * - Else wrap into { message: "..."}
     */
    private function normalizeDetailsToArray(?string $details): array
    {
        $details = is_null($details) ? '' : (string)$details;
        $details = trim($details);

        if ($details === '') return [];

        // If it looks like JSON, try decoding
        if ($this->looksLikeJson($details)) {
            $decoded = json_decode($details, true);
            if (is_array($decoded)) return $decoded;
        }

        return ['message' => $details];
    }

    private function looksLikeJson(string $s): bool
    {
        $s = trim($s);
        return ($s !== '') && (
            (str_starts_with($s, '{') && str_ends_with($s, '}')) ||
            (str_starts_with($s, '[') && str_ends_with($s, ']'))
        );
    }

    /**
     * Turn stored details into a human-readable string for the table.
     * This is where your "Event Type Created" becomes "Created event type: Car Wash".
     */
    private function formatDetailsForHumans(?string $details, ?string $entityType, ?string $action): string
    {
        $details = is_null($details) ? '' : (string)$details;
        $detailsTrim = trim($details);

        // If not JSON, just return as-is
        if ($detailsTrim === '' || !$this->looksLikeJson($detailsTrim)) {
            return $detailsTrim;
        }

        $data = json_decode($detailsTrim, true);
        if (!is_array($data)) return $detailsTrim;

        // Common fields you’re logging
        $label    = $data['label'] ?? null;
        $oldLabel = $data['old_label'] ?? null;
        $newLabel = $data['new_label'] ?? null;

        $entityType = (string)($entityType ?? '');
        $action = (string)($action ?? '');

        // ✅ EventType create/delete/update friendly formatting
        if (stripos($entityType, 'eventtype') !== false || stripos($entityType, 'event_type') !== false) {
            if ($label && (stripos($action, 'create') !== false || stripos($action, 'created') !== false)) {
                return 'Created event type: ' . $label;
            }

            if ($label && (stripos($action, 'delete') !== false || stripos($action, 'deleted') !== false)) {
                return 'Deleted event type: ' . $label;
            }

            if ($oldLabel || $newLabel) {
                return 'Updated event type: ' . ($oldLabel ?? '—') . ' → ' . ($newLabel ?? '—');
            }

            // fallback if label missing
            return $data['message'] ?? 'Event type change';
        }

        // Generic fallback: try a message field, else show compact json
        if (isset($data['message']) && is_string($data['message'])) {
            return $data['message'];
        }

        // Compact display for unknown payloads
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function formatActionForHumans(?string $action): string
    {
        $action = (string)($action ?? '');
        if ($action === '') return '';

        // Optional: normalize casing
        // e.g. "event_type.created" -> "Create"
        $a = strtolower($action);

        if (str_contains($a, 'create')) return 'Create';
        if (str_contains($a, 'edit') || str_contains($a, 'update')) return 'Edit';
        if (str_contains($a, 'delete')) return 'Delete';

        return $action;
    }
}
