<?php

namespace App\Http\Controllers;

use App\Models\FactLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SystemLogsController extends Controller
{
    public function index(Request $request)
    {
        $query = FactLog::query()->with(['admin']);

        /* ============================================================
        1) DATE FILTERS
        ============================================================ */
        $dateStart = $request->filled('date_start')
            ? Carbon::parse($request->date_start)->startOfDay()
            : null;

        $dateEnd = $request->filled('date_end')
            ? Carbon::parse($request->date_end)->endOfDay()
            : null;

        if ($dateStart) $query->where('timestamp', '>=', $dateStart);
        if ($dateEnd)   $query->where('timestamp', '<=', $dateEnd);

        /* ============================================================
        2) ACTION FILTER
        ============================================================ */
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        /* ============================================================
        3) CATEGORY FILTER (INFERRED)
        - We keep your original rules but make them a bit more robust.
        ============================================================ */
        if ($request->filled('category')) {
            $cat = $request->category;

            $query->where(function ($sub) use ($cat) {

                // AUTH
                if ($cat === 'auth') {
                    $sub->where(function ($q) {
                        $q->where('action', 'like', '%login%')
                          ->orWhere('action', 'like', '%logout%')
                          ->orWhere('action', 'like', '%failed_login%');
                    });
                    return;
                }

                // IMPORTS (GENERAL + SPECIFIC)
                if (in_array($cat, ['attendance_import', 'volunteer_import', 'import'], true)) {
                    $sub->where(function ($q) {
                        $q->where('action', 'like', '%import%')
                          ->orWhere('details', 'like', '%import%')
                          ->orWhere('entity_type', 'like', '%import%');
                    });

                    if ($cat === 'attendance_import') {
                        $sub->where(function ($q) {
                            $q->where('details', 'like', '%attendance%')
                              ->orWhere('details', 'like', '%ATT-%');
                        });
                    } elseif ($cat === 'volunteer_import') {
                        $sub->where(function ($q) {
                            $q->where('details', 'like', '%volunteer%')
                              ->orWhere('details', 'like', '%VOL-%');
                        });
                    }
                    return;
                }

                // EVENTS
                if ($cat === 'event') {
                    $sub->where(function ($q) {
                        $q->where('entity_type', 'like', '%event%')
                          ->orWhere('details', 'like', '%event%')
                          ->orWhere('details', 'like', '%organizer%')
                          ->orWhere('details', 'like', '%event type%');
                    });
                    return;
                }

                // VOLUNTEERS
                if ($cat === 'volunteer') {
                    $sub->where(function ($q) {
                        $q->where('entity_type', 'like', '%volunteer%')
                          ->orWhere('details', 'like', '%volunteer%');
                    });
                    return;
                }

                // ATTENDANCE
                if ($cat === 'attendance') {
                    $sub->where(function ($q) {
                        $q->where('entity_type', 'like', '%attendance%')
                          ->orWhere('details', 'like', '%attendance%');
                    });
                    return;
                }

                // SYSTEM = anything
                $sub->whereNotNull('fact_log_id');
            });
        }

        /* ============================================================
        4) SEARCH (SERVER SIDE)
        - Fix: AdminAccount uses full_name (not name)
        - Also search inside decoded JSON later via details_decoded field
        ============================================================ */
        if ($request->filled('q')) {
            $q = trim($request->q);

            $query->where(function ($sub) use ($q) {
                $sub->where('action', 'like', "%{$q}%")
                    ->orWhere('entity_type', 'like', "%{$q}%")
                    ->orWhere('entity_id', 'like', "%{$q}%")
                    ->orWhere('details', 'like', "%{$q}%")
                    ->orWhereHas('admin', function ($a) use ($q) {
                        $a->where('username', 'like', "%{$q}%")
                          ->orWhere('full_name', 'like', "%{$q}%"); // ✅ FIX
                    });
            });
        }

        $query->orderByDesc('timestamp');

        $perPage = (int)($request->get('per_page', 5));
        $perPage = max(3, min(15, $perPage));

        $logs = $query->paginate($perPage)->withQueryString();

        /* ============================================================
        5) DECODE DETAILS SAFELY FOR UI
        ✅ details_payload  = decoded JSON (if present)
        ✅ details_decoded  = clean human text for table cell (NO HTML / NO entities)
        ============================================================ */
        $cleanText = function (?string $text): string {
            $text = (string)$text;

            // decode HTML entities
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // remove tags if any got stored
            $text = strip_tags($text);

            // remove UI junk that should never be logged as plain text
            // (ex: " | Show More", "Show More", extra separators)
            $text = preg_replace('/\s*\|\s*show\s+more\s*$/i', '', $text);
            $text = preg_replace('/\bshow\s+more\b/i', '', $text);

            // normalize whitespace
            $text = preg_replace('/\s+/', ' ', trim($text));

            return $text;
        };

        $logs->getCollection()->transform(function ($log) use ($cleanText) {
            $raw  = is_string($log->details) ? $log->details : '';
            $trim = ltrim($raw);

            $decoded = null;

            // JSON sniff (safe)
            if ($trim !== '' && isset($trim[0]) && ($trim[0] === '{' || $trim[0] === '[')) {
                $decoded = json_decode($raw, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $decoded = null;
                }
            }

            if (is_array($decoded)) {
                $log->details_payload = $decoded;

                // Prefer summary, then type, then action; always clean it
                $human =
                    $decoded['summary']
                    ?? $decoded['type']
                    ?? ($decoded['action'] ?? null)
                    ?? '[log]';

                $log->details_decoded = $cleanText((string)$human);
            } else {
                $log->details_payload = null;
                $log->details_decoded = $cleanText($raw);
            }

            // ✅ If it ends with "Entry #X —" or "Entry #X:" add No Name
            if ($log->details_decoded !== '' && preg_match('/(entry\s*#\d+\s*(—|:)\s*)$/i', $log->details_decoded)) {
                $log->details_decoded .= ' No Name';
            }

            return $log;
        });

        /* ============================================================
        6) ACTION DROPDOWN VALUES
        ============================================================ */
        $availableActions = FactLog::query()
            ->whereNotNull('action')
            ->where('action', '!=', '')
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->values()
            ->all();

        $availableCategories = [
            'system'            => 'System',
            'auth'              => 'Authentication',
            'event'             => 'Event Management',
            'volunteer'         => 'Volunteer Management',
            'attendance'        => 'Attendance',
            'volunteer_import'  => 'Volunteer Import',
            'attendance_import' => 'Attendance Import',
            'import'            => 'Import (Other)',
        ];

        return view('system_logs.index', compact(
            'logs',
            'availableActions',
            'availableCategories',
            'perPage'
        ));
    }
}
