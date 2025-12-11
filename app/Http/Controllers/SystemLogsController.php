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

        // Dates
        $dateStart = $request->filled('date_start') ? Carbon::parse($request->date_start)->startOfDay() : null;
        $dateEnd   = $request->filled('date_end')   ? Carbon::parse($request->date_end)->endOfDay()   : null;

        if ($dateStart) $query->where('timestamp', '>=', $dateStart);
        if ($dateEnd)   $query->where('timestamp', '<=', $dateEnd);

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Category filter (inferred)
        if ($request->filled('category')) {
            $cat = $request->category;

            $query->where(function ($sub) use ($cat) {
                if ($cat === 'auth') {
                    $sub->where(function ($q) {
                        $q->where('action', 'like', '%login%')
                          ->orWhere('action', 'like', '%logout%')
                          ->orWhere('action', 'like', '%failed_login%');
                    });
                    return;
                }

                if (in_array($cat, ['attendance_import','volunteer_import','import'], true)) {
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

                if ($cat === 'event') {
                    $sub->where(function ($q) {
                        $q->where('entity_type', 'like', '%event%')
                          ->orWhere('details', 'like', '%event%')
                          ->orWhere('details', 'like', '%organizer%');
                    });
                    return;
                }

                if ($cat === 'volunteer') {
                    $sub->where('entity_type', 'like', '%volunteer%');
                    return;
                }

                if ($cat === 'attendance') {
                    $sub->where('entity_type', 'like', '%attendance%');
                    return;
                }

                // system = anything
                $sub->whereNotNull('fact_log_id');
            });
        }

        // Search (server-side)
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('action', 'like', "%{$q}%")
                    ->orWhere('entity_type', 'like', "%{$q}%")
                    ->orWhere('entity_id', 'like', "%{$q}%")
                    ->orWhere('details', 'like', "%{$q}%")
                    ->orWhereHas('admin', function ($a) use ($q) {
                        $a->where('username', 'like', "%{$q}%")
                          ->orWhere('name', 'like', "%{$q}%");
                    });
            });
        }

        $query->orderByDesc('timestamp');

        $perPage = (int)($request->get('per_page', 5));
        $perPage = max(3, min(15, $perPage)); // allow smaller if you want

        $logs = $query->paginate($perPage)->withQueryString();

        // ✅ Decode HTML entities ONCE so UI/JS get clean JSON/strings
        $logs->getCollection()->transform(function ($log) {
            $log->details_decoded = is_string($log->details)
                ? html_entity_decode($log->details, ENT_QUOTES | ENT_HTML5, 'UTF-8')
                : '';
            return $log;
        });

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
