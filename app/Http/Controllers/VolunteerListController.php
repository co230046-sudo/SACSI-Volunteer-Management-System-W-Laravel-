<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VolunteerProfile;
use App\Models\Course;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Services\FactLogger;

class VolunteerListController extends Controller
{
    private array $DAYS = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

    private FactLogger $factLogger;

    public function __construct(FactLogger $factLogger)
    {
        $this->factLogger = $factLogger;
    }

    // Volunteer List page
    public function index(Request $request)
    {
        // Build course abbreviations
        $majorWords = [
            'Bachelor','Science','Arts','Education','Engineering',
            'Technology','Accountancy','Business','Management',
            'Communication','Media','New','Computer'
        ];

        $courses = Course::orderBy('course_name')->get()->map(function($c) use ($majorWords) {
            $abbr = '';
            foreach (explode(' ', $c->course_name) as $word) {
                if (in_array($word, $majorWords)) {
                    $abbr .= strtoupper($word[0]);
                }
            }
            $c->abbr = $abbr;
            return $c;
        });

        // Filter dropdowns
        $barangays = Location::query()
            ->pluck('barangay')
            ->filter()
            ->map(fn($b) => trim((string)$b))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $districts = Location::query()
            ->pluck('district_id')
            ->filter()
            ->map(fn($d) => (string)$d)
            ->unique()
            ->sort()
            ->values()
            ->map(fn($id) => (object)[
                'district_id'   => $id,
                'district_name' => "District $id",
            ]);

        // Add-volunteer barangay dropdown (with district mapping)
        $locations = Location::orderBy('barangay')->get(['location_id','barangay','district_id']);

        // Batch dropdown (real values)
        $batches = VolunteerProfile::query()
            ->whereNotNull('batch_year')
            ->where('batch_year', '!=', '')
            ->distinct()
            ->orderByDesc('batch_year')
            ->pluck('batch_year')
            ->map(fn($y) => trim((string)$y))
            ->filter()
            ->values();

        return view('volunteer_list.volunteer_list', compact(
            'courses',
            'barangays',
            'districts',
            'locations',
            'batches'
        ));
    }

    // Manual add volunteer (modal)
    public function store(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return back()
                ->with('error_modal', "❌ Admin not authenticated.")
                ->with('error_modal_entries', [[
                    'row'    => '-',
                    'name'   => 'Authentication Failure',
                    'status' => 'invalid',
                    'issues' => ['Admin guard returned null user.'],
                ]])
                ->withInput();
        }

        $normalizeUrl = function (?string $raw): string {
            $raw = trim((string)$raw);
            if ($raw === '') return '';
            if (!preg_match('~^[a-zA-Z][a-zA-Z0-9+\-.]*://~', $raw)) {
                $raw = 'https://' . $raw;
            }
            return $raw;
        };

        $isAllowedFbHost = function (string $host): bool {
            $h = strtolower(trim($host));
            $h = preg_replace('~^www\.~', '', $h);

            if ($h === 'facebook.com' || str_ends_with($h, '.facebook.com')) return true;
            if ($h === 'fb.com'       || str_ends_with($h, '.fb.com'))       return true;
            if ($h === 'm.me') return true;
            if ($h === 'messenger.com' || str_ends_with($h, '.messenger.com')) return true;

            return false;
        };

        $validated = $request->validate([
            'full_name'         => ['required','regex:/^[A-Za-zÑñ\s\.\'-]+$/u','max:255'],
            'id_number'         => ['required','regex:/^\d{6,7}$/'],
            'course_id'         => ['required','exists:courses,course_id'],
            'year_level'        => ['required','in:1,2,3,4'],
            'batch_number'      => ['required','string','max:50'],

            'contact_number'    => ['required','regex:/^(09\d{9}|\+639\d{9})$/'],
            'emergency_contact' => ['required','regex:/^(09\d{9}|\+639\d{9})$/','different:contact_number'],
            'email'             => [
                'required',
                'email',
                'regex:/^[A-Za-z0-9._%+-]+@(gmail\.com|adzu\.edu\.ph)$/i',
            ],

            'fb_messenger' => ['required','string','max:500'],

            'barangay'          => ['required','string'],
            'district'          => ['required'],
            'status'            => ['nullable','in:active,inactive'],
            'class_schedule'    => ['nullable','string'],

            'profile_picture'   => ['nullable','image','mimes:jpg,jpeg,png'],
        ],[
            'emergency_contact.different' => 'Emergency contact must be different from the contact number.',
            'batch_number.required'       => 'Please enter a batch (e.g., 2025).',
        ]);

        $fullNameRaw = trim((string)($validated['full_name'] ?? ''));
        $idnum       = trim((string)($validated['id_number'] ?? ''));
        $emailRaw    = trim((string)($validated['email'] ?? ''));
        $email       = strtolower($emailRaw);

        $fbRaw  = trim((string)($validated['fb_messenger'] ?? ''));
        $fbNorm = strtolower(rtrim($fbRaw, "/"));

        $idExists = $idnum !== ''
            ? VolunteerProfile::where('id_number', $idnum)->exists()
            : false;

        $emailExists = $email !== ''
            ? VolunteerProfile::whereRaw('LOWER(email) = ?', [$email])->exists()
            : false;

        $nameExists = false;
        if ($fullNameRaw !== '') {
            $fullNameNorm = strtolower(preg_replace('/\s+/', ' ', $fullNameRaw));

            $candidates = VolunteerProfile::query()
                ->select('full_name')
                ->whereRaw('LOWER(full_name) LIKE ?', ['%' . strtolower(substr($fullNameNorm, 0, 30)) . '%'])
                ->limit(80)
                ->get();

            foreach ($candidates as $cand) {
                $candNorm = strtolower(preg_replace('/\s+/', ' ', trim((string)$cand->full_name)));
                if ($candNorm === $fullNameNorm) {
                    $nameExists = true;
                    break;
                }
            }

            if (!$nameExists) {
                $nameExists = VolunteerProfile::whereRaw('LOWER(TRIM(full_name)) = ?', [strtolower($fullNameRaw)])->exists();
            }
        }

        $fbExists = false;
        if ($fbNorm !== '' && $fbNorm !== 'no fb messenger') {
            $fbExists = VolunteerProfile::query()
                ->whereNotNull('fb_messenger')
                ->where('fb_messenger', '!=', '')
                ->whereRaw('LOWER(fb_messenger) = ? OR LOWER(fb_messenger) = ?', [$fbNorm, $fbNorm . '/'])
                ->exists();
        }

        if ($nameExists || $idExists || $emailExists || $fbExists) {

            $issues = ["Volunteer Already Exists"];
            $fieldErrors = [];

            if ($nameExists) {
                $issues[] = "Full name already exists: {$fullNameRaw}";
                $fieldErrors['full_name'] = 'That full name is already registered.';
            }
            if ($idExists) {
                $issues[] = "School ID already exists: {$idnum}";
                $fieldErrors['id_number'] = 'That School ID is already registered.';
            }
            if ($emailExists) {
                $issues[] = "Email already exists: {$emailRaw}";
                $fieldErrors['email'] = 'That email is already registered.';
            }
            if ($fbExists) {
                $issues[] = "FB / Messenger already exists: {$fbRaw}";
                $fieldErrors['fb_messenger'] = 'That FB / Messenger link is already registered.';
            }

            $this->logFact(
                'Volunteer Create Blocked',
                $admin->admin_id,
                'VolunteerProfile',
                null,
                'Duplicate',
                "Blocked duplicate add attempt – {$fullNameRaw} ({$idnum} / {$emailRaw})"
            );

            return back()
                ->withErrors($fieldErrors)
                ->with('error_modal', "❌ Cannot save. Duplicate volunteer detected.")
                ->with('error_modal_entries', [[
                    'row'    => '-',
                    'name'   => $fullNameRaw ?: 'Unknown',
                    'status' => 'duplicate',
                    'issues' => $issues,
                ]])
                ->withInput();
        }

        if (!empty($validated['fb_messenger'])) {
            $normalized = $normalizeUrl($validated['fb_messenger']);
            $host = parse_url($normalized, PHP_URL_HOST) ?? '';

            $ok = filter_var($normalized, FILTER_VALIDATE_URL) && $isAllowedFbHost($host);

            if (!$ok) {
                return back()
                    ->withErrors(['fb_messenger' => 'FB / Messenger must be a valid Facebook/Messenger link (facebook.com, fb.com, m.me, messenger.com).'])
                    ->withInput();
            }

            $validated['fb_messenger'] = $normalized;
        }

        $districtId = (int)$validated['district'];

        $location = Location::where('barangay', $validated['barangay'])
            ->where('district_id', $districtId)
            ->first();

        if (!$location) {
            return back()
                ->withErrors([
                    'barangay' => 'Selected barangay and district do not match any known location.',
                ])
                ->withInput();
        }

        $profilePath = 'defaults/default_user.png';
        $profileUrl  = null;

        if ($request->hasFile('profile_picture')) {
            $profilePath = $request->file('profile_picture')
                ->store('profile_pictures/volunteers', 'public');
        }

        $schedule = trim($validated['class_schedule'] ?? '');
        if ($schedule === '') {
            $schedule = 'No class schedule';
        }

        $conflicts = $this->scheduleHasOverlap($schedule);
        if (!empty($conflicts)) {
            $days = implode(', ', array_keys($conflicts));
            return back()
                ->withErrors([
                    'class_schedule' => "Overlapping class schedule detected on: {$days}. Please fix the schedule."
                ])
                ->withInput();
        }

        try {
            $volunteer = null;

            DB::transaction(function () use ($validated, $location, $profileUrl, $profilePath, $schedule, $admin, &$volunteer) {

                $volunteer = VolunteerProfile::create([
                    'import_id'            => null,
                    'location_id'          => $location->location_id,
                    'course_id'            => $validated['course_id'],
                    'full_name'            => $validated['full_name'],
                    'id_number'            => $validated['id_number'],
                    'year_level'           => $validated['year_level'],
                    'batch_year'           => trim((string)$validated['batch_number']),
                    'email'                => $validated['email'],
                    'contact_number'       => $validated['contact_number'],
                    'emergency_contact'    => $validated['emergency_contact'],
                    'fb_messenger'         => $validated['fb_messenger'] ?: 'No FB messenger',
                    'barangay'             => $location->barangay,
                    'district'             => $location->district_id,
                    'profile_picture_url'  => $profileUrl,
                    'profile_picture_path' => $profilePath,
                    'certificates'         => 'No certificates',
                    'class_schedule'       => $schedule,
                    'notes'                => 'No notes',
                    'status'               => $validated['status'] ?? 'active',
                ]);

                $this->logFact(
                    'Volunteer Created',
                    $admin->admin_id,
                    'VolunteerProfile',
                    $volunteer->volunteer_id,
                    'Created',
                    "Added volunteer – {$volunteer->full_name} ({$volunteer->id_number})"
                );
            });

            return redirect()->back()
                ->with('success', 'Volunteer saved successfully.')
                ->with('vlAddVolunteerName', $volunteer->full_name)
                ->with('vlAddVolunteerIdNumber', $volunteer->id_number)
                ->with('vlAddSavedAtIso', $volunteer->created_at
                    ? $volunteer->created_at->toIso8601String()
                    : now()->toIso8601String()
                );

        } catch (\Throwable $e) {

            Log::error("Volunteer add failed!", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            $this->logFact(
                'Volunteer Create Failed',
                $admin->admin_id,
                'VolunteerProfile',
                null,
                'Failed',
                [
                    'summary' => 'Exception thrown while creating volunteer',
                    'error'   => $e->getMessage(),
                ]
            );

            return back()
                ->with('error_modal', "<strong style='color:#B2000C;'>⚠️ Save failed.</strong><br>A database/system error occurred while saving.")
                ->with('error_modal_technical', $e->getMessage() . "\n\n" . $e->getTraceAsString())
                ->with('error_modal_entries', [[
                    'row'    => '-',
                    'name'   => $validated['full_name'] ?? 'System Error',
                    'status' => 'invalid',
                    'issues' => ['A system/database error occurred. Check Technical Details if needed.'],
                ]])
                ->withInput();
        }
    }

    // Schedule helpers
    private function parseRangeStr(string $range): ?array
    {
        $range = preg_replace('/\s+/', '', $range);
        if (!preg_match('/^(\d{1,2}):(\d{2})-(\d{1,2}):(\d{2})$/', $range, $m)) {
            return null;
        }

        $start = ((int)$m[1]) * 60 + (int)$m[2];
        $end   = ((int)$m[3]) * 60 + (int)$m[4];

        return $end > $start ? [$start, $end] : null;
    }

    private function extractScheduleByDay(?string $schedule): array
    {
        $output = [];
        foreach ($this->DAYS as $d) {
            $output[$d] = [];
        }
        if (!$schedule) return $output;

        foreach ($this->DAYS as $day) {
            if (!preg_match(
                "/{$day}:\s*(.*?)(?=(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|$))/is",
                $schedule,
                $m
            )) {
                continue;
            }

            $content = trim($m[1]);
            if (stripos($content, 'No Class') !== false) continue;

            if (preg_match_all('/\d{1,2}:\d{2}\s*-\s*\d{1,2}:\d{2}/', $content, $found)) {
                foreach ($found[0] as $range) {
                    $parsed = $this->parseRangeStr($range);
                    if ($parsed) {
                        $output[$day][] = $parsed;
                    }
                }
            }
        }

        return $output;
    }

    private function overlaps(array $a, array $b): bool
    {
        return !($a[1] <= $b[0] || $a[0] >= $b[1]);
    }

    private function scheduleHasOverlap(?string $schedule): array
    {
        $schedule = trim((string)$schedule);

        if ($schedule === '' || strcasecmp($schedule, 'No class schedule') === 0) {
            return [];
        }

        $byDay = $this->extractScheduleByDay($schedule);
        $conflicts = [];

        foreach ($this->DAYS as $day) {
            $ranges = $byDay[$day] ?? [];
            if (count($ranges) < 2) continue;

            usort($ranges, fn($a, $b) => $a[0] <=> $b[0]);

            $prev = $ranges[0];
            for ($i = 1; $i < count($ranges); $i++) {
                $cur = $ranges[$i];
                if ($this->overlaps($prev, $cur)) {
                    $conflicts[$day][] = $cur;
                    if ($cur[1] > $prev[1]) $prev = $cur;
                } else {
                    $prev = $cur;
                }
            }
        }

        return $conflicts;
    }

    // Volunteer cards data (AJAX)
    public function data(Request $request)
    {
        $perPage       = max(1, (int)$request->query('per_page', 12));
        $searchRaw     = (string)$request->query('search', '');
        $search        = strtolower(trim($searchRaw));

        $sort          = (string)$request->query('sort', 'name_asc');
        $courseId      = $request->query('course_id');
        $barangay      = $request->query('barangay');
        $district      = $request->query('district');
        $yearLevel     = $request->query('year_level');

        $batchYear     = $request->query('batch_year');

        $selectedDay   = $request->query('day');
        $selectedBlock = $request->query('schedule_day');
        $status        = $request->query('status');

        $parsedRange = null;
        if ($selectedBlock) {
            $clean = str_ireplace([' AM',' PM'], '', (string)$selectedBlock);
            $parsedRange = $this->parseRangeStr($clean);
        }

        $query = VolunteerProfile::with('course')->select(
            'volunteer_id',
            'full_name',
            'course_id',
            'year_level',
            'class_schedule',
            'barangay',
            'district',
            'profile_picture_url',
            'profile_picture_path',
            'email',
            'contact_number',
            'emergency_contact',
            'status',
            'batch_year'
        );

        // Search
        if ($search !== '') {
            $query->where(function($q) use ($search, $searchRaw) {
                $like = "%{$search}%";

                $q->whereRaw("LOWER(full_name) LIKE ?", [$like])
                  ->orWhereRaw("LOWER(barangay) LIKE ?", [$like])
                  ->orWhereRaw("LOWER(email) LIKE ?", [$like])
                  ->orWhere('contact_number', 'LIKE', "%{$searchRaw}%")
                  ->orWhere('emergency_contact', 'LIKE', "%{$searchRaw}%");

                if (in_array($search, ['1','district 1','d1'], true)) {
                    $q->orWhere('district', 1);
                }
                if (in_array($search, ['2','district 2','d2'], true)) {
                    $q->orWhere('district', 2);
                }

                $q->orWhereHas('course', function($qc) use ($like) {
                    $qc->whereRaw("LOWER(course_name) LIKE ?", [$like]);
                });

                $statusSearch = strtolower($search);
                if (in_array($statusSearch, ['active','inactive'], true)) {
                    $q->orWhere('status', $statusSearch);
                }
            });
        }

        // Filters
        if ($courseId && $courseId !== 'remove') {
            $query->where('course_id', $courseId);
        }
        if ($barangay && $barangay !== 'remove') {
            $query->where('barangay', $barangay);
        }
        if ($district && $district !== 'remove') {
            $query->where('district', $district);
        }
        if ($yearLevel && $yearLevel !== 'remove') {
            $query->where('year_level', $yearLevel);
        }
        if ($batchYear && $batchYear !== 'remove') {
            $query->where('batch_year', $batchYear);
        }
        if ($status && $status !== 'remove') {
            $query->where('status', $status);
        }

        // Sort
        switch ($sort) {
            case 'name_desc':
                $query->orderBy('full_name', 'desc');
                break;
            case 'year_asc':
                $query->orderBy('year_level', 'asc')->orderBy('full_name', 'asc');
                break;
            case 'year_desc':
                $query->orderBy('year_level', 'desc')->orderBy('full_name', 'asc');
                break;
            case 'district_asc':
                $query->orderBy('district', 'asc')->orderBy('full_name', 'asc');
                break;
            case 'district_desc':
                $query->orderBy('district', 'desc')->orderBy('full_name', 'asc');
                break;
            case 'name_asc':
            default:
                $query->orderBy('full_name', 'asc');
                break;
        }

        $items = $query->get();

        // Schedule availability filtering (in-memory)
        if ($selectedDay || $parsedRange) {
            $items = $items->filter(function ($v) use ($selectedDay, $parsedRange) {
                $blocksByDay = $this->extractScheduleByDay($v->class_schedule);

                if ($selectedDay && !$parsedRange) {
                    $ranges = $blocksByDay[$selectedDay] ?? [];
                    return count($ranges) === 0;
                }

                if (!$selectedDay && $parsedRange) {
                    foreach ($blocksByDay as $ranges) {
                        foreach ($ranges as $block) {
                            if ($this->overlaps($parsedRange, $block)) {
                                return false;
                            }
                        }
                    }
                    return true;
                }

                if ($selectedDay && $parsedRange) {
                    $ranges = $blocksByDay[$selectedDay] ?? [];
                    foreach ($ranges as $block) {
                        if ($this->overlaps($parsedRange, $block)) {
                            return false;
                        }
                    }
                    return true;
                }

                return true;
            })->values();
        }

        $total       = $items->count();
        $currentPage = max(1, (int)$request->query('page', 1));
        $lastPage    = max(1, (int)ceil($total / $perPage));

        $results = $items->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $defaultAvatar = asset('storage/defaults/default_user.png');

        return response()->json([
            'data' => $results->map(function ($item) use ($defaultAvatar) {

                $avatar = $defaultAvatar;

                if (!empty($item->profile_picture_path)) {
                    $resolved = $this->toPublicStorageUrl((string)$item->profile_picture_path);
                    if ($resolved) {
                        $avatar = $resolved;
                    }
                } elseif (!empty($item->profile_picture_url)) {
                    $avatar = (string)$item->profile_picture_url;
                }

                return [
                    'volunteer_id'       => $item->volunteer_id,
                    'full_name'          => $item->full_name,
                    'year_level'         => $item->year_level,
                    'class_schedule'     => $item->class_schedule,
                    'avatar_url'         => $avatar,
                    'course'             => $item->course ? [
                        'course_id'   => $item->course->course_id,
                        'course_name' => $item->course->course_name,
                        'abbr'        => $item->course->abbr ?? null,
                    ] : null,
                    'barangay'           => $item->barangay,
                    'district'           => $item->district,
                    'email'              => $item->email,
                    'contact_number'     => $item->contact_number,
                    'emergency_contact'  => $item->emergency_contact,
                    'status'             => $item->status,
                    'batch_year'         => $item->batch_year,
                ];
            }),
            'total'         => $total,
            'per_page'      => $perPage,
            'current_page'  => $currentPage,
            'last_page'     => $lastPage,
            'prev_page_url' => $currentPage > 1
                ? url("/volunteers/data?page=" . ($currentPage - 1))
                : null,
            'next_page_url' => $currentPage < $lastPage
                ? url("/volunteers/data?page=" . ($currentPage + 1))
                : null,
        ]);
    }

    // Path -> public url
    private function toPublicStorageUrl(?string $path): ?string
    {
        $p = trim((string)$path);
        if ($p === '') return null;

        $p = str_replace('\\', '/', $p);

        if (preg_match('~^https?://~i', $p)) {
            return $p;
        }

        $needle = '/storage/app/public/';
        if (stripos($p, $needle) !== false) {
            $p = substr($p, stripos($p, $needle) + strlen($needle));
        }

        if (stripos($p, '/public/') !== false && stripos($p, $needle) === false) {
            $p = substr($p, stripos($p, '/public/') + strlen('/public/'));
        }

        $p = ltrim($p, '/');

        return asset('storage/' . $p);
    }

    // Fact log helper
    private function logFact(
        string $type,
        $adminId = null,
        $entity = null,
        ?int $entityId = null,
        ?string $action = null,
        $details = null
    ): void {
        $adminId = is_numeric($adminId) ? (int)$adminId : null;

        $this->factLogger->log(
            $type,
            $action,
            $entity,
            $entityId,
            $details,
            $adminId
        );
    }
}
