<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VolunteerProfile;
use App\Models\Course;
use App\Models\Location;
use Illuminate\Support\Facades\Storage;

class VolunteerListController extends Controller
{
    private array $DAYS = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

    public function index(Request $request)
    {
        // Build course abbreviations like your original logic
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

        // For filters (list)
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

        // For Add-Volunteer barangay dropdown (with district mapping)
        $locations = Location::orderBy('barangay')->get(['location_id','barangay','district_id']);

        return view('volunteer_list.volunteer_list', compact(
            'courses',
            'barangays',
            'districts',
            'locations'
        ));
    }

    /**
     * Manual Add Volunteer (from modal)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name'         => ['required','regex:/^[A-Za-zÑñ\s\.\'-]+$/u','max:255'],
            'id_number'         => ['required','regex:/^\d{6,7}$/','unique:volunteer_profiles,id_number'],
            'course_id'         => ['required','exists:courses,course_id'],
            'year_level'        => ['required','in:1,2,3,4'],
            'contact_number'    => ['required','regex:/^(09\d{9}|\+639\d{9})$/'],
            'emergency_contact' => ['required','regex:/^(09\d{9}|\+639\d{9})$/','different:contact_number'],
            'email'             => [
                'required',
                'email',
                'regex:/^[A-Za-z0-9._%+-]+@(gmail\.com|adzu\.edu\.ph)$/i',
                'unique:volunteer_profiles,email',
            ],
            'fb_messenger'      => ['nullable','string'],
            'barangay'          => ['required','string'],
            'district'          => ['required'],
            'status'            => ['nullable','in:active,inactive'],
            'class_schedule'    => ['nullable','string'],
            'profile_picture'   => ['nullable','image','mimes:jpg,jpeg,png','max:4096'],
        ],[
            'id_number.unique'   => 'That School ID is already registered.',
            'email.unique'       => 'That email is already registered.',
            'emergency_contact.different' => 'Emergency contact must be different from the contact number.',
        ]);

        // Optional FB / Messenger – must be a Facebook URL if present
        if (!empty($validated['fb_messenger'])) {
            $fb = $validated['fb_messenger'];
            $host = parse_url($fb, PHP_URL_HOST) ?? '';
            if (!filter_var($fb, FILTER_VALIDATE_URL) ||
                stripos($host, 'facebook.com') === false) {

                return back()
                    ->withErrors(['fb_messenger' => 'FB / Messenger link must be a valid Facebook URL.'])
                    ->withInput();
            }
        }

        // Resolve location (barangay + district must exist in locations table)
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

        // Profile picture upload
        $profilePath = 'defaults/default_user.png';
        $profileUrl  = null;

        if ($request->hasFile('profile_picture')) {
            $profilePath = $request->file('profile_picture')
                ->store('profile_pictures/volunteers', 'public');
        }

        // Class schedule – default if blank
        $schedule = trim($validated['class_schedule'] ?? '');
        if ($schedule === '') {
            $schedule = 'No class schedule';
        }

        VolunteerProfile::create([
            'import_id'            => null,
            'location_id'          => $location->location_id,
            'course_id'            => $validated['course_id'],
            'full_name'            => $validated['full_name'],
            'id_number'            => $validated['id_number'],
            'year_level'           => $validated['year_level'],
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

        return redirect()
            ->back()
            ->with('success', 'Volunteer added successfully.');
    }

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

    /**
     * Convert stored path to public storage URL
     */
    private function toPublicStorageUrl(?string $path): ?string
    {
        $p = trim((string)$path);
        if ($p === '') return null;

        $p = str_replace('\\', '/', $p);

        // Already a URL
        if (preg_match('~^https?://~i', $p)) {
            return $p;
        }

        // Strip absolute path up to /storage/app/public/
        $needle = '/storage/app/public/';
        if (stripos($p, $needle) !== false) {
            $p = substr($p, stripos($p, $needle) + strlen($needle));
        }

        // Strip /public/ variant
        if (stripos($p, '/public/') !== false && stripos($p, $needle) === false) {
            $p = substr($p, stripos($p, '/public/') + strlen('/public/'));
        }

        $p = ltrim($p, '/');

        return asset('storage/' . $p);
    }

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
        $selectedDay   = $request->query('day');
        $selectedBlock = $request->query('schedule_day');
        $status       = $request->query('status');

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
        );

        $scheduleMode = ($selectedDay || $selectedBlock) ? true : false; // (can remove if you want)

        // Search (ALWAYS allowed, even when day/time filters are used)
        if ($search !== '') {
            $query->where(function($q) use ($search, $searchRaw) {
            $like = "%{$search}%";

            // name / barangay
            $q->whereRaw("LOWER(full_name) LIKE ?", [$like])
            ->orWhereRaw("LOWER(barangay) LIKE ?", [$like])

            // email
            ->orWhereRaw("LOWER(email) LIKE ?", [$like])

            // phone numbers (use raw input, not lowercased)
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
        // - day only   => volunteers with NO class blocks that day (free all day)
        // - time only  => volunteers that NEVER have a class overlapping that time
        // - day+time   => volunteers free at that time on that specific day
        if ($selectedDay || $parsedRange) {
            $items = $items->filter(function ($v) use ($selectedDay, $parsedRange) {
                $blocksByDay = $this->extractScheduleByDay($v->class_schedule);

                // 1) DAY ONLY: free all day = no class ranges for that day
                if ($selectedDay && !$parsedRange) {
                    $ranges = $blocksByDay[$selectedDay] ?? [];
                    return count($ranges) === 0;
                }

                // 2) TIME ONLY: volunteer never has class overlapping that time on any day
                if (!$selectedDay && $parsedRange) {
                    foreach ($blocksByDay as $ranges) {
                        foreach ($ranges as $block) {
                            if ($this->overlaps($parsedRange, $block)) {
                                // has at least one overlapping class somewhere in the week → NOT available
                                return false;
                            }
                        }
                    }
                    // no overlap on any day → always free at that time
                    return true;
                }

                // 3) DAY + TIME: free at that time ON that specific day
                if ($selectedDay && $parsedRange) {
                    $ranges = $blocksByDay[$selectedDay] ?? [];
                    foreach ($ranges as $block) {
                        if ($this->overlaps($parsedRange, $block)) {
                            // has class overlapping → NOT available
                            return false;
                        }
                    }
                    // no overlapping block → available at that day+time
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
}
