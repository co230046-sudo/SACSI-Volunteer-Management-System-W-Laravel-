<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use App\Models\VolunteerProfile;
use App\Models\ImportLog;
use App\Models\FactLog;
use App\Models\Course;
use App\Models\Location;

class VolunteerImportController extends Controller
{

    public function index()
    {
        $validEntries = session('validEntries', []);
        $invalidEntries = session('invalidEntries', []);
        $uploadedFileName = session('uploaded_file_name', null);
        $uploadedFilePath = session('uploaded_file_path', null);

        // Ensure class_schedule exists in all entries
        foreach ($validEntries as &$entry) {
            if (!isset($entry['class_schedule'])) {
                $entry['class_schedule'] = 'No class schedule';
            }
        }
        foreach ($invalidEntries as &$entry) {
            if (!isset($entry['class_schedule'])) {
                $entry['class_schedule'] = 'No class schedule';
            }
        }

        // Dynamic dropdown data
        $courses = Course::orderBy('course_name')->get();

        $barangays = Location::orderBy('barangay')->get();

        $districts = Location::select('district_id')
            ->distinct()
            ->orderBy('district_id')
            ->get();

        // Import logs
        $importLogs = ImportLog::orderBy('created_at', 'desc')->get();

        return view('volunteer_import.volunteer_import', compact(
            'validEntries',
            'invalidEntries',
            'uploadedFileName',
            'uploadedFilePath',
            'importLogs',
            'courses',
            'barangays',
            'districts'
        ));
    }

    /**
     * Convert Google Drive (Google Forms) link → direct download link
     */
    private function convertDriveLinkToDownloadUrl($url)
    {
        if (!$url) return '';

        // Pattern: ?id=FILEID
        if (preg_match('/id=([^&]+)/', $url, $m)) {
            return "https://drive.google.com/uc?export=download&id={$m[1]}";
        }

        // Pattern: /d/FILEID/
        if (preg_match('#/d/([^/]+)/#', $url, $m)) {
            return "https://drive.google.com/uc?export=download&id={$m[1]}";
        }

        return $url; // fallback
    }

    /**
     * Download Google Drive image (already converted URL)
     * - Returns local storage path (e.g. profile_pictures/volunteers/pp_xxx.jpg)
     * - OR 'defaults/default_user.png' if anything fails
     */
    private function downloadDriveImage($url)
    {
        try {
            if (!$url || $url === '__FOLDER__') {
                return 'defaults/default_user.png';
            }

            // Try downloading
            $context = stream_context_create([
                "http" => ["timeout" => 8]  // Prevent long hangs
            ]);

            $contents = @file_get_contents($url, false, $context);

            if (!$contents) {
                return 'defaults/default_user.png';
            }

            // Detect MIME type (image only)
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->buffer($contents);

            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
                return 'defaults/default_user.png';
            }

            // Ensure folder exists
            Storage::disk('public')->makeDirectory('profile_pictures/volunteers');

            $ext = match ($mime) {
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                default      => 'jpg'
            };

            $fileName = 'pp_' . uniqid() . '.' . $ext;

            Storage::disk('public')->put(
                'profile_pictures/volunteers/' . $fileName,
                $contents
            );

            return 'profile_pictures/volunteers/' . $fileName;
        } catch (\Exception $e) {
            Log::warning('Profile picture download failed: ' . $e->getMessage());
            return 'defaults/default_user.png';
        }
    }

    /**
     * Preview File
     */
    public function preview(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $filename = $file->getClientOriginalName();
        $path = $file->store('uploads', 'public');

        session([
            'uploaded_file_name' => $filename,
            'uploaded_file_path' => $path,
            'csv_imported'       => true,
        ]);

        $admin = Auth::guard('admin')->user();
        $adminName = $admin->name ?? $admin->username ?? "Unknown Admin";

        // Mark previous preview as abandoned
        $previousId = session('import_log_id');
        if ($previousId) {
            $previousLog = ImportLog::find($previousId);
            if ($previousLog && $previousLog->status === 'Pending') {
                $previousLog->update([
                    'status'  => 'Abandoned',
                    'remarks' => "Admin {$adminName} abandoned preview on " . now()->format('M d, Y h:i A'),
                ]);
            }
        }

        // New import log
        $importLog = ImportLog::create([
            'file_name'       => $filename,
            'admin_id'        => $admin->admin_id ?? null,
            'total_records'   => 0,
            'valid_count'     => 0,
            'invalid_count'   => 0,
            'duplicate_count' => 0,
            'status'          => 'Pending',
            'remarks'         => null,
        ]);

        session(['import_log_id' => $importLog->import_id]);

        $rows = array_map('str_getcsv', file($file->getRealPath()));
        if (empty($rows)) {
            $importLog->update([
                'remarks' => "Preview completed for Import #{$importLog->import_id}: No rows were found.",
                'total_records' => 0
            ]);
            return back()->with('error', 'CSV file is empty.');
        }

        $header = array_map('strtolower', array_map('trim', array_shift($rows)));

        $valid = [];
        $invalid = [];
        $duplicates = [];
        $seenKeys = [];

        foreach ($rows as $i => $row) {
            $data = $this->normalizeRow($row, $header);
            $errors = $this->validateRow($data);

            if (count($row) !== count($header)) {
                $errors = array_fill_keys(array_keys($data), true);
            }

            $data['row_number'] = $i + 2;
            $uniqueKey = strtolower($data['email'] ?? $data['full_name'] ?? 'row_' . $i);

            if (in_array($uniqueKey, $seenKeys)) {
                $duplicates[] = $data;
            } elseif (!empty($errors)) {
                $data['errors'] = $errors;
                $invalid[] = $data;
                $seenKeys[] = $uniqueKey;
            } else {
                $valid[] = $data;
                $seenKeys[] = $uniqueKey;
            }
        }

        // Fix picture fields
        $normalizePictureFields = function (&$arr) {
            foreach ($arr as &$e) {
                if (!isset($e['profile_picture_local'])) $e['profile_picture_local'] = '';
                if (!isset($e['profile_picture'])) $e['profile_picture'] = '';
            }
        };
        $normalizePictureFields($valid);
        $normalizePictureFields($invalid);
        $normalizePictureFields($duplicates);

        session([
            'validEntries'     => $valid,
            'invalidEntries'   => $invalid,
            'duplicateEntries' => $duplicates,
        ]);

        $importLog->update([
            'total_records'   => count($rows),
            'valid_count'     => count($valid),
            'invalid_count'   => count($invalid),
            'duplicate_count' => count($duplicates),
            'status'          => 'Pending',
            'remarks'         => "Preview summary: " . count($valid) . " valid, "
                . count($invalid) . " invalid, "
                . count($duplicates) . " duplicates.",
        ]);

        if ($admin) {
            $this->logFact(
                'Preview Import',
                $admin->admin_id,
                'Volunteer Import',
                $importLog->import_id,
                'Previewed',
                "Previewed CSV: " . count($valid) . " valid, "
                    . count($invalid) . " invalid, "
                    . count($duplicates) . " duplicates."
            );
        }

        // Modal details HTML
        $details = "
            <div style='font-size:1rem; line-height:1.55; color:#333;'>
                <strong style='color:#28a745;'>Valid:</strong> " . count($valid) . "<br>
                <strong style='color:#B2000C;'>Invalid:</strong> " . count($invalid) . "<br>
                <strong style='color:#d38b00;'>Duplicates:</strong> " . count($duplicates) . "<br><br>
        ";

        if (count($invalid) === 0 && count($valid) > 0) {
            $redirectAnchor = '#import-Section-valid';
            $details .= "
                <strong style='color:#28a745;'>All entries are valid.</strong><br>
                <a href='#import-Section-valid' style='color:#28a745; font-weight:600;'>View valid entries &rarr;</a>
            ";
        } elseif (count($valid) === 0 && count($invalid) > 0) {
            $redirectAnchor = '#import-Section-invalid';
            $details .= "
                <strong style='color:#B2000C;'>All entries are invalid.</strong><br>
                <a href='#import-Section-invalid' style='color:#B2000C; font-weight:600;'>View invalid entries &rarr;</a>
            ";
        } else {
            $redirectAnchor = '#import-Section-invalid';
            $details .= "
                <strong style='color:#d38b00;'>Some entries are invalid.</strong><br>
                <a href='#import-Section-invalid' style='color:#d38b00; font-weight:600;'>Go to invalid entries &rarr;</a>
            ";
        }

        $details .= "</div>";

        // Encode safely
        $encodedDetails = base64_encode($details);

        // Flash bar message
        $message = "
            <div style='display:flex; align-items:center; flex-wrap:wrap; gap:12px;
                        font-size:1.05rem; font-weight:600; margin-bottom:6px;'>

                <span style='color:#28a745;'>✅ " . count($valid) . " valid</span>
                <span style='color:#B2000C;'>❌ " . count($invalid) . " invalid</span>
                <span style='color:#d38b00;'>⚠️ " . count($duplicates) . " duplicates</span>

                <span style='color:#999;'>|</span>

                <a href='#'
                class='move-details-link'
                data-details=\"{$encodedDetails}\"
                style='color:#007bff; text-decoration:none; font-size:0.95rem;'>
                    Show Details
                </a>
            </div>
        ";

        return redirect(url()->previous() . $redirectAnchor)
            ->with('success', $message);
    }

    /**
     * Time Range Helper
     * Supports:
     *  - Standard HH:MM-HH:MM
     *  - 12:30-1:50 style ranges (treated as 12:30-13:50)
     */
    private function parseTimeRange($range)
    {
        $range = trim((string) $range);
        if ($range === '' || !str_contains($range, '-')) {
            return null;
        }

        [$start, $end] = explode('-', $range, 2);
        $start = trim($start);
        $end   = trim($end);

        // Must be HH:MM-HH:MM
        if (!preg_match('/^\d{1,2}:\d{2}$/', $start)) return null;
        if (!preg_match('/^\d{1,2}:\d{2}$/', $end))   return null;

        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));

        // Basic sanity: 0–23 hours, 0–59 minutes
        if ($sh < 0 || $sh > 23 || $eh < 0 || $eh > 23) return null;
        if ($sm < 0 || $sm > 59 || $em < 0 || $em > 59) return null;

        $startMin = $sh * 60 + $sm;
        $endMin   = $eh * 60 + $em;

        // Handle 12:30-1:50 (12-hour schedule, PM)
        if ($endMin <= $startMin) {
            // If start is noon or later (>=12) and end is a small hour (1–7),
            // assume end time is PM and add 12h.
            if ($sh >= 12 && $eh >= 1 && $eh <= 7) {
                $eh     += 12;
                $endMin  = $eh * 60 + $em;
            }
        }

        // Still invalid? Reject (end not after start)
        if ($endMin <= $startMin) {
            return null;
        }

        return (object)[
            'start' => $startMin,
            'end'   => $endMin,
        ];
    }

    /**
     * Schedule Overlap Checker
     */
    private function rangesOverlap($a, $b)
    {
        // Standard open-interval overlap
        return $a->start < $b->end && $b->start < $a->end;
    }

    /**
     * Try to smart-match a barangay string to locations table.
     * - Ignores case
     * - Collapses extra spaces
     * - Allows small misspellings via levenshtein distance
     *
     * @return object|null  (barangay, district_id)
     */
    private function smartMatchBarangay(?string $raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        // Normalize: lowercase, collapse spaces
        $needle = strtolower(preg_replace('/\s+/', ' ', $raw));

        // Cache locations in static variable to avoid repeated queries
        static $locationCache = null;

        if ($locationCache === null) {
            $rows = DB::table('locations')
                ->select('barangay', 'district_id')
                ->get();

            $locationCache = [];
            foreach ($rows as $row) {
                // skip rows with empty barangay to avoid weird matches
                if (!trim((string) $row->barangay)) {
                    continue;
                }

                $normalizedName = strtolower(preg_replace(
                    '/\s+/',
                    ' ',
                    trim((string) $row->barangay)
                ));

                $locationCache[] = (object)[
                    'barangay'     => $row->barangay,
                    'district_id'  => $row->district_id,
                    'normalized'   => $normalizedName,
                ];
            }
        }

        if (empty($locationCache)) {
            return null;
        }

        $best = null;
        $bestDist = PHP_INT_MAX;

        foreach ($locationCache as $loc) {
            // Exact normalized match → immediate win
            if ($loc->normalized === $needle) {
                return (object)[
                    'barangay'    => $loc->barangay,
                    'district_id' => $loc->district_id,
                ];
            }

            // Approximate match
            $dist = levenshtein($needle, $loc->normalized);

            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $loc;
            }
        }

        // Decide if "close enough"
        $len = strlen($needle);
        $threshold = max(2, (int) floor($len / 4)); // tune if needed

        if ($best && $bestDist <= $threshold) {
            return (object)[
                'barangay'    => $best->barangay,
                'district_id' => $best->district_id,
            ];
        }

        return null;
    }

    /**
     * normalizeRow
     */
    private function normalizeRow(array $row, array $header): array
    {
        /**
         * Flexible Header Mapping
         */
        $mapping = [
            // --- FULL NAME ---
            'full_name' => 'full_name', 'fullname' => 'full_name', 'full name' => 'full_name',
            'first name' => 'first_name', 'firstname' => 'first_name',
            'middle name' => 'middle_name', 'middlename' => 'middle_name',
            'last name' => 'last_name', 'lastname' => 'last_name', 'surname' => 'last_name',

            // --- SCHOOL ID ---
            'id number' => 'id_number', 'school id' => 'id_number',
            'school id number' => 'id_number', 'id' => 'id_number',

            // --- CONTACT NUMBER ---
            'contact number' => 'contact_number', 'contact_number' => 'contact_number',
            'phone' => 'contact_number', 'phone number' => 'contact_number',
            'contact no' => 'contact_number', 'contact #' => 'contact_number',

            // --- EMERGENCY CONTACT ---
            'emergency number' => 'emergency_contact',
            'emergency_contact' => 'emergency_contact',
            'emergency contact' => 'emergency_contact',
            'emergency contact number' => 'emergency_contact',
            'emergency no' => 'emergency_contact',
            'emergency #' => 'emergency_contact',

            // --- EMAIL ---
            'email address' => 'email', 'email' => 'email',
            'school email address' => 'email', 'school email' => 'email',
            'adzu email' => 'email', 'email add' => 'email',

            // --- FB ---
            'fb link' => 'fb_messenger', 'facebook profile link' => 'fb_messenger',
            'messenger' => 'fb_messenger', 'fb' => 'fb_messenger',

            // --- BARANGAY ---
            'barangay' => 'barangay', 'brgy' => 'barangay',
            'district' => 'district',

            // --- COURSE ---
            'course' => 'course', 'strand' => 'course', 'program' => 'course',

            // --- YEAR LEVEL ---
            'year' => 'year_level', 'year level' => 'year_level', 'yearlevel' => 'year_level',
            'year_level' => 'year_level',

            // --- BATCH / COHORT ---
            'batch' => 'batch_year',
            'batch number' => 'batch_year',
            'batch no' => 'batch_year',
            'cohort' => 'batch_year',
            
            // --- SCHEDULES ---
            'monday schedule' => 'monday', 'monday' => 'monday',
            'tuesday schedule' => 'tuesday', 'tuesday' => 'tuesday',
            'wednesday schedule' => 'wednesday', 'wednesday' => 'wednesday',
            'thursday schedule' => 'thursday', 'thursday' => 'thursday',
            'friday schedule' => 'friday', 'friday' => 'friday',
            'saturday schedule' => 'saturday', 'saturday' => 'saturday',

            // --- CERTIFICATES ---
            'certificates' => 'certificates',
            'certificate uploads' => 'certificates',

            // --- PROFILE PICTURE ---
            'profile picture' => 'profile_picture',
            'profile_photo' => 'profile_picture',
            'google drive link to your profile picture (jpg or png)' => 'profile_picture',
        ];

        $normalized = [];

        /**
         * Helpers
         */
        $fixTime = function ($t) {
            $t = trim($t);
            return preg_match('/^\d{1,2}$/', $t) ? $t . ":00" : $t;
        };

        $cleanSchedule = function ($raw) use ($fixTime) {
            if (!$raw) return [];

            $raw = preg_replace('/\[[MAE]\]/i', '', $raw);           // strip [M], [A], [E]
            $raw = str_replace(['–', ';', ','], ['-', ' ', ' '], $raw);  // normalize separators
            $raw = preg_replace('/\s+/', ' ', trim($raw));

            if ($raw === "" || stripos($raw, "no class") !== false) return [];

            $parts = explode(' ', $raw);

            return array_values(array_filter(array_map(function ($slot) use ($fixTime) {
                if (!str_contains($slot, '-')) return null;
                [$a, $b] = explode('-', $slot);
                return $fixTime($a) . "-" . $fixTime($b);
            }, $parts)));
        };

        $scheduleDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        foreach ($scheduleDays as $d) $normalized[$d] = [];

        /**
         * Process headers
         */
        foreach ($header as $i => $colRaw) {
            $keyRaw = strtolower(trim((string) $colRaw));
            $value  = isset($row[$i]) ? trim((string)$row[$i]) : '';

            // Ignore timestamp/date columns
            if (in_array($keyRaw, ['timestamp', 'time submitted', 'date'], true)) continue;

            // Ignore yes/no helper questions like "Do you have a Monday class?"
            if (preg_match('/do you have.*class\?/i', $keyRaw)) {
                continue;
            }

            // Map header → internal field
            $mapped =
                ($mapping[$keyRaw] ?? null)
                ?? ($mapping[preg_replace('/\s+/', ' ', $keyRaw)] ?? null)
                ?? ($mapping[str_replace([' ', '-'], '_', $keyRaw)] ?? null);

            // Schedule columns
            if (in_array($mapped, $scheduleDays, true)) {
                // Skip if there are absolutely no digits (e.g. all text)
                if (!preg_match('/\d/', $value)) continue;

                $slots = $cleanSchedule($value);
                if (!empty($slots)) {
                    $normalized[$mapped] = array_values(array_unique(
                        array_merge($normalized[$mapped], $slots)
                    ));
                }
                continue;
            }

            // Profile picture
            if ($mapped === 'profile_picture') {
                if ($value !== '') {
                    $converted = $this->convertDriveLinkToDownloadUrl($value);
                    $localPath = $this->downloadDriveImage($converted);
                    $normalized['profile_picture'] = $value;
                    $normalized['profile_picture_local'] = $localPath;
                }
                continue;
            }

            // Normal mapped fields
            if ($mapped) {
                $normalized[$mapped] = $value;
            }
        }

        /**
         * Merge names → full_name
         */
        $fn = $normalized['first_name'] ?? '';
        $mn = $normalized['middle_name'] ?? '';
        $ln = $normalized['last_name'] ?? '';

        if (empty($normalized['full_name']) && ($fn || $ln)) {
            $normalized['full_name'] =
                trim(
                    ucwords(strtolower($fn)) . ' ' .
                    ($mn ? strtoupper($mn[0]) . '. ' : '') .
                    ucwords(strtolower($ln))
                );
        }

        /**
         * Normalize Barangay (fuzzy match)
         */
        if (!empty($normalized['barangay'])) {
            $cleanBrgy = ucwords(strtolower(trim($normalized['barangay'])));
            $match = $this->smartMatchBarangay($cleanBrgy);
            if ($match) {
                $normalized['barangay'] = $match->barangay;
                $normalized['district'] = $match->district_id; // this is district_id semantically
            }
        }

        /**
         * Normalize Course
         */
        if (!empty($normalized['course'])) {
            $c = trim($normalized['course']);
            $db = DB::table('courses')
                ->whereRaw('LOWER(course_name)=?', [strtolower($c)])
                ->value('course_name');
            $normalized['course'] = $db ?? ucwords(strtolower($c));
        }

        /**
         * Normalize year level
         */
        if (!empty($normalized['year_level'])) {
            $yl = strtolower($normalized['year_level']);
            foreach (['1', '2', '3', '4'] as $n) {
                if (str_contains($yl, $n)) {
                    $normalized['year_level'] = $n;
                    break;
                }
            }
        }
        
        /**
         * Derive batch_year if missing or messy
         * Priority:
         *   1) Use explicit "Batch" field from CSV if present
         *   2) Else derive from School ID Number (first 2 digits → 20xx)
        */
        if (!empty($normalized['batch_year'])) {
            // Clean up whatever user typed: "Batch 2023", "2023", etc.
            $digits = preg_replace('/\D+/', '', (string)$normalized['batch_year']);
            if (strlen($digits) === 4) {
                $normalized['batch_year'] = (int)$digits;        // e.g. 2023
            } elseif (strlen($digits) === 2) {
                // assume 20xx (you can tweak logic if school uses different style)
                $normalized['batch_year'] = 2000 + (int)$digits; // e.g. "23" → 2023
            } else {
                $normalized['batch_year'] = null; // weird input, we just drop it
            }
        }

        // If still empty, derive from id_number
        if (empty($normalized['batch_year']) && !empty($normalized['id_number'])) {
            $id = preg_replace('/\D+/', '', (string)$normalized['id_number']);

            // pattern: YYxxxxx, e.g. 230279, 210232
            if (preg_match('/^(\d{2})\d{4,5}$/', $id, $m)) {
                $yy = (int)$m[1];      // 23 → 23, 21 → 21
                $normalized['batch_year'] = 2000 + $yy;  // 23 → 2023, 21 → 2021
            }
        }

        /**
         * Build final class schedule string
         */
        $out = [];
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day) {
            $slots = $normalized[strtolower($day)] ?? [];
            $out[] = $day . ': ' . (empty($slots) ? 'No Class' : implode(' ', $slots));
        }
        $normalized['class_schedule'] = implode(' ', $out);

        /**
         * Ensure required keys exist
         */
        foreach ([
            'full_name','id_number','email','contact_number','emergency_contact',
            'fb_messenger','barangay','district','course','year_level',
            'batch_year',
            'class_schedule','certificates','profile_picture','profile_picture_local'
        ] as $k) {
            if (!array_key_exists($k, $normalized)) {
                $normalized[$k] = '';
            }
        }


        /**
         * Normalize PH numbers
         */
        foreach (['contact_number', 'emergency_contact'] as $field) {
            if (!empty($normalized[$field])) {
                $normalized[$field] = preg_replace('/[^\d+]/', '', $normalized[$field]);
            }
        }

        return $normalized;
    }

    /**
     * Validate ONE normalized row
     */
    private function validateRow(array $data)
    {
        $errors = [];

        // Full Name
        if (empty($data['full_name']) ||
            !preg_match("/^[A-Za-zÑñ\s\.\'-]+$/u", $data['full_name'])) {
            $errors['full_name'] = 'Full Name is required and only letters allowed.';
        }

        // Batch Number
        if (!empty($data['batch_year']) &&
            !preg_match('/^20\d{2}$/', (string)$data['batch_year'])) {
            $errors['batch_year'] = 'Batch year must be a 4-digit year like 2023.';
        }

        // School ID
        if (empty($data['id_number']) ||
            !preg_match('/^\d{6,7}$/', (string)$data['id_number'])) {
            $errors['id_number'] = 'School ID must be 6 or 7 digits.';
        }

        // Course
        if (empty($data['course']) ||
            !preg_match('/^[A-Za-z\s]+$/u', $data['course'])) {
            $errors['course'] = 'Course is required.';
        }

        // Year
        if (empty($data['year_level']) ||
            !in_array((string)$data['year_level'], ['1', '2', '3', '4'], true)) {
            $errors['year_level'] = 'Year must be 1–4.';
        }

        // Numbers
        $cn = $data['contact_number'] ?? '';
        $ec = $data['emergency_contact'] ?? '';

        if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $cn)) {
            $errors['contact_number'] = 'Contact Number must be a valid PH number.';
        }

        if (!preg_match('/^(09\d{9}|\+639\d{9})$/', $ec)) {
            $errors['emergency_contact'] = 'Emergency Contact must be a valid PH number.';
        }

        if ($cn && $ec && $cn === $ec) {
            $errors['emergency_contact'] =
                'Emergency Contact must be DIFFERENT from Contact Number.';
        }

        // Email
        if (empty($data['email']) ||
            !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
            !preg_match('/@(gmail\.com|adzu\.edu\.ph)$/i', $data['email'])) {
            $errors['email'] = 'Email must end with @gmail.com or @adzu.edu.ph.';
        }

        // Barangay
        if (empty(trim($data['barangay'] ?? ''))) {
            $errors['barangay'] = 'Barangay is required.';
        } else {
            $match = $this->smartMatchBarangay($data['barangay']);
            if (!$match) {
                $errors['barangay'] = "Invalid barangay: '{$data['barangay']}'";
            }
        }

        // Schedules
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $day) {
            $slots = $data[$day] ?? [];

            // Safety: if somehow a string sneaks in, turn into single slot array
            if (!is_array($slots)) {
                $slots = trim((string)$slots) !== '' ? [trim((string)$slots)] : [];
            }

            $parsed = [];

            foreach ($slots as $slot) {
                $slot = trim((string)$slot);
                if ($slot === '') continue;

                $range = $this->parseTimeRange($slot);
                if (!$range) {
                    $errors[$day][] = "Invalid time format '$slot'";
                    continue;
                }

                foreach ($parsed as $p) {
                    if ($this->rangesOverlap($range, $p['range'])) {
                        $errors[$day][] = "Conflict: '$slot' overlaps '{$p['raw']}'";
                    }
                }

                $parsed[] = ['raw' => $slot, 'range' => $range];
            }
        }

        return empty($errors) ? null : $errors;
    }

   /**
     * Update / Correct Volunteer Fields
     */
    public function updateVolunteerEntry(Request $request, $index, $type)
    {
        /* ============================================================
        LOAD ENTRY + NORMALIZE INPUT
        ============================================================ */
        $entries = session($type . 'Entries', []);

        if (!isset($entries[$index])) {
            return back()->with('error', '⚠️ Entry not found.');
        }

        $entry  = $entries[$index];
        $before = $entry;
        $input  = array_map('trim', $request->all());

        if (isset($before['district'])) $before['district'] = preg_replace('/\D/', '', $before['district']);
        if (isset($input['district']))  $input['district']  = preg_replace('/\D/', '', $input['district']);

        if (isset($before['class_schedule'])) $before['class_schedule'] = preg_replace('/\s+/', ' ', trim($before['class_schedule']));
        if (isset($input['class_schedule']))  $input['class_schedule']  = preg_replace('/\s+/', ' ', trim($input['class_schedule']));

        foreach (['contact_number','emergency_contact'] as $field) {
            if (!empty($input[$field])) {
                $input[$field] = preg_replace('/[^\d+]/', '', $input[$field]);
            }
        }

        if (!empty($input['id_number'])) {
            $input['id_number'] = strtoupper($input['id_number']);
        }

        // 🔹 NEW: Normalize batch_year like in normalizeRow()
        if (!empty($input['batch_year'])) {
            $digits = preg_replace('/\D+/', '', (string) $input['batch_year']);

            if (strlen($digits) === 4) {
                $input['batch_year'] = (int) $digits;          // e.g. 2023
            } elseif (strlen($digits) === 2) {
                $input['batch_year'] = 2000 + (int) $digits;   // e.g. "23" → 2023
            } else {
                // weird input, treat as null so validation can catch if needed
                $input['batch_year'] = null;
            }
        }

        /* ============================================================
        VALIDATION
        ============================================================ */
        $validator = \Validator::make($input, [
            'full_name'        => ['required','regex:/^[A-Za-zÑñ\s\.\'-]+$/u','max:255'],
            'id_number'        => ['required','regex:/^\d{6,7}$/'],
            'course'           => 'required|string|max:100',
            'year_level'       => ['required','in:1,2,3,4'],
            'contact_number'   => ['required','regex:/^(09\d{9}|\+639\d{9})$/'],
            'emergency_contact'=> ['required','regex:/^(09\d{9}|\+639\d{9})$/'],
            'email'            => ['required','email','regex:/^[A-Za-z0-9._%+-]+@(gmail\.com|adzu\.edu\.ph)$/i'],
            'fb_messenger'     => ['nullable'],
            'barangay'         => ['required'],
            'district'         => ['required'],
            'class_schedule'   => ['required','string','regex:/^[\w\s,:()\.\-\/]+$/'],

            // 🔹 NEW: make batch_year optional but sane
            'batch_year'       => ['nullable','digits:4','integer','min:2000','max:2100'],
        ],[
            'year_level.in'        => 'Year must be 1, 2, 3, or 4.',
            'district.required'    => 'No district selected.',
            'barangay.required'    => 'No barangay selected.',
            'class_schedule.required' => 'Class schedule is required.',
            'class_schedule.regex' => 'Class schedule contains invalid characters.',
            'batch_year.digits'    => 'Batch year must be a 4-digit year like 2023.',
            'batch_year.min'       => 'Batch year is too early.',
            'batch_year.max'       => 'Batch year is too far in the future.',
        ]);

        $errors = $validator->fails() ? $validator->errors()->toArray() : [];

        if (!empty($input['fb_messenger'])) {
            $fb = $input['fb_messenger'];
            if (!filter_var($fb, FILTER_VALIDATE_URL) ||
                stripos(parse_url($fb, PHP_URL_HOST) ?: '', 'facebook.com') === false)
            {
                $errors['fb_messenger'] = ['FB/Messenger must be a valid Facebook link'];
            }
        }

        if (!empty($input['barangay'])) {
            $districtId = $input['district_id'] ?? null;

            if (!$districtId) {
                $errors['district'] = ['No district selected.'];
            } else {
                $exists = DB::table('locations')
                    ->where('barangay', $input['barangay'])
                    ->where('district_id', $districtId)
                    ->exists();

                if (!$exists) {
                    $errors['barangay'] = [
                        "Barangay \"{$input['barangay']}\" and District ID \"{$districtId}\" not found."
                    ];
                }
            }
        }

        /* ============================================================
        DETECT CHANGES
        ============================================================ */
        $updatedFields = [];

        foreach ($input as $field => $value) {

            if (isset($errors[$field])) continue;

            $old = $before[$field] ?? '';
            $new = $value;

            if ($field === 'district') {
                $old = preg_replace('/\D/', '', $old);
                $new = preg_replace('/\D/', '', $new);
            }

            if ($field === 'class_schedule') {
                $old = preg_replace('/\s+/', ' ', trim($old));
                $new = preg_replace('/\s+/', ' ', trim($new));
            }

            if ($new !== $old) {
                $updatedFields[$field] = $value;
            }

            $entries[$index][$field] = $value;
        }

        $entries[$index]['errors'] = $errors;
        session([$type . 'Entries' => $entries]);

        /* ============================================================
        UPDATE DB (IF LINKED)
        ============================================================ */
        if (!empty($entries[$index]['volunteer_id']) && !empty($updatedFields)) {
            if ($vol = VolunteerProfile::find($entries[$index]['volunteer_id'])) {
                $vol->update(array_merge($updatedFields, ['status' => 'active']));
            }
        }

        /* ============================================================
        LOG CHANGES
        ============================================================ */
        $adminId = Auth::guard('admin')->id();
        $labels = [
            'full_name'        => 'Full Name',
            'id_number'        => 'School ID',
            'course'           => 'Course',
            'year_level'       => 'Year Level',
            'contact_number'   => 'Contact #',
            'emergency_contact'=> 'Emergency #',
            'email'            => 'Email',
            'fb_messenger'     => 'FB/Messenger',
            'barangay'         => 'Barangay',
            'district'         => 'District',
            'class_schedule'   => 'Class Schedule',
            'batch_year'       => 'Batch Year',   // ✅ already good
        ];

        if ($adminId && !empty($updatedFields)) {

            $fieldDetails = [];
            foreach ($updatedFields as $field => $value) {
                if (isset($labels[$field])) {
                    $fieldDetails[] = "{$labels[$field]}='{$value}'";
                }
            }

            $entityId = $entries[$index]['volunteer_id']
                    ?? $entries[$index]['row_number']
                    ?? ($index + 1);

            $this->logFact(
                'Update Entry',
                $adminId,
                isset($entries[$index]['volunteer_id']) ? 'VolunteerProfile' : 'Volunteer Import',
                $entityId,
                'Updated',
                "Updated Entry #".($index+1).": " . implode(', ', $fieldDetails)
            );
        }

        /* ============================================================
        SUCCESS MODAL CONTENT (FIXED ICONS)
        ============================================================ */

        $row  = $index + 1;
        $name = htmlspecialchars($before['full_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');

        $block = "
            <div style='margin-bottom:8px;'>
                <strong style='color:#007bff; font-size:1.1rem;'>
                    Entry #{$row} for {$name}
                </strong>
            </div>
        ";

        foreach ($labels as $field => $niceLabel) {

            $oldDisplay = $entry[$field] ?? ($before[$field] ?? '');
            $newDisplay = $input[$field] ?? '';

            $oldSafe = htmlspecialchars((string)$oldDisplay, ENT_QUOTES, 'UTF-8');
            $newSafe = htmlspecialchars((string)$newDisplay, ENT_QUOTES, 'UTF-8');

            $hasError   = !empty($errors[$field] ?? []);
            $wasChanged = array_key_exists($field, $updatedFields);

            $errorMsg = $hasError
                ? htmlspecialchars(implode(', ', (array)$errors[$field]), ENT_QUOTES, 'UTF-8')
                : '';

            $block .= "
            <div style='margin-bottom:10px; font-size:0.98rem;'>
                <div style='font-weight:600; color:#333; margin-bottom:2px;'>
                    {$niceLabel}:
                </div>
                <div style='display:flex; align-items:flex-start; gap:6px;'>
            ";

            if ($hasError) {
                // ⚠️ ERROR
                $block .= "
                    <span style='margin-top:2px;'>&#9888;</span>
                    <span>
                        <span style='color:#B2000C; font-weight:600;'>Error:</span>
                        <span style='color:#B2000C;'> {$errorMsg}</span>
                    </span>
                ";
            } elseif ($wasChanged) {
                // ✔️ CHANGED
                $block .= "
                    <span style='margin-top:2px;'>&#10004;</span>
                    <span>
                        <span style='color:#28a745; font-weight:600;'>Changed:</span>
                        <span style='color:#555;'> {$oldSafe}</span>
                        <span style='color:#999; margin:0 4px;'>&rarr;</span>
                        <span style='color:#007bff; font-weight:600;'>{$newSafe}</span>
                    </span>
                ";
            } else {
                // ℹ️ NO CHANGE
                $block .= "
                    <span style='margin-top:2px;'>&#8505;&#65039;</span>
                    <span style='color:#444;'>No changes made</span>
                ";
            }

            $block .= "
                </div>
                <div style='border-bottom:1px solid #e0e0e0; margin:8px 0 4px;'></div>
            </div>";
        }

        $encodedDetails = base64_encode($block);

        /* ============================================================
        FLASH MESSAGE
        ============================================================ */

        // (Optional: you may want to change this text if changes were actually made,
        // but I'm keeping your original message here.)
        $flash = "
            <strong style='color:#28a745;'>✔ No Changes Made #{$row}</strong>
            <span style='color:#28a745; font-weight:600;'> – {$name}</span>
            &nbsp;|&nbsp;
            <span class='update-details-link'
                data-details=\"{$encodedDetails}\"
                style='color:#007bff; cursor:pointer; text-decoration:none;'>
                Show Details
            </span>
        ";

        return redirect()->route('volunteer.import.index')
            ->with('success', $flash)
            ->with('updateDetails', $encodedDetails)
            ->with('last_updated_table', $type)
            ->with('last_updated_index', $index);
    }


    /**
     * Move selected INVALID entries → VALID
     */
    public function moveInvalidToValid(Request $request)
    {
        $invalid = session('invalidEntries', []);
        $valid   = session('validEntries', []);
        $adminId = Auth::guard('admin')->user()->admin_id ?? null;

        $selected = (array) $request->input('selected_invalid', []);

        // normalize + unique + stable order
        $selected = array_values(array_unique(array_map('intval', $selected)));
        sort($selected);

        // ❌ NOTHING SELECTED
        if (empty($selected)) {

            $this->logFact(
                'Import Move',
                $adminId,
                'VolunteerImport',
                null,
                'Move Invalid → Valid — Failed',
                'No rows selected'
            );

            $flash = "
                <strong style='color:#B2000C;'>❌ Nothing moved</strong>
                &nbsp;|&nbsp;
                <span class='error-details-link'
                    style='color:#1565c0; cursor:pointer; text-decoration:none;'>
                    Show Details
                </span>
            ";

            $modal = "
                <div style='font-size:1.3rem; font-weight:700; color:#B2000C;'>Nothing Selected</div>
                <div style='border-bottom:1px solid #dcdcdc; margin:10px 0;'></div>
                <div style='display:flex; gap:6px; align-items:flex-start;'>
                    <span style='color:#007bff;'>ℹ️</span>
                    <span>No invalid entries were selected.</span>
                </div>
            ";

            return back()
                ->with('success', $flash)
                ->with('error', $flash)
                ->with('show_error_modal', true)
                ->with('error_modal_message', $modal)
                ->with('redirect_anchor', '#import-Section-invalid');
        }

        $moved  = [];
        $failed = [];

        foreach ($selected as $index) {

            if (!isset($invalid[$index])) continue;

            $entry = $invalid[$index];
            $name  = $entry['full_name'] ?? 'Unknown';

            // capture origin BEFORE unsetting / reindexing
            $entry['origin_bucket']   = 'invalid';
            $entry['origin_index']    = $index;        // 0-based
            $entry['origin_entry_no'] = $index + 1;    // 1-based display number

            // ❌ cannot move because still invalid
            if (!empty($entry['errors'])) {
                $failed[] = [
                    'index'  => $index + 1,
                    'name'   => $name,
                    'errors' => $entry['errors'],
                ];
                continue;
            }

            // ✅ move entry
            $moved[] = $entry;
            unset($invalid[$index]);
        }

        /**
         * ❌ SOME FAILED
         */
        if (!empty($failed)) {

            // log moved ones individually (since some did move)
            foreach ($moved as $e) {
                $name = $e['full_name'] ?? 'Unknown';

                $id = $e['volunteer_id']
                    ?? ($e['row_number'] ?? null)
                    ?? ($e['origin_entry_no'] ?? null);

                $fromNo = $e['origin_entry_no'] ?? '?';

                $this->logFact(
                    'Import Move',
                    $adminId,
                    'VolunteerImport',
                    $id,
                    'Moved Invalid → Valid',
                    "Moved Entry #{$fromNo} — {$name}"
                );
            }

            $count = count($failed);

            $flash = "
                <strong style='color:#B2000C;'>❌ Failed to move {$count} entr" . ($count > 1 ? "ies" : "y") . "</strong>
                &nbsp;|&nbsp;
                <span class='error-details-link'
                    style='color:#1565c0; cursor:pointer; text-decoration:none;'>
                    Show Details
                </span>
            ";

            $html = "
                <div style='font-size:1.3rem; font-weight:700; color:#B2000C;'>Entries That Cannot Be Moved</div>
                <div style='border-bottom:1px solid #dcdcdc; margin:10px 0;'></div>
                <div style='max-height:300px; overflow-y:auto; padding-right:6px;'>
            ";

            foreach ($failed as $i => $f) {
                $safe = htmlspecialchars($f['name'], ENT_QUOTES, 'UTF-8');
                $idx  = (int)$f['index'];

                $html .= "
                    <div style='margin-bottom:12px;'>
                        <div style='display:flex; gap:6px; align-items:center;'>
                            <span style='color:#B2000C;'>⚠️</span>
                            <span style='font-weight:600;'>Entry #{$idx} — {$safe}</span>
                            <span class='show-missing-link'
                                style='margin-left:auto; color:#1565c0; cursor:pointer;'
                                data-id='{$i}'>
                                Show Missing
                            </span>
                        </div>
                        <div style='border-bottom:1px solid #e6e6e6; margin-top:6px;'></div>
                    </div>
                ";
            }

            $html .= "</div>";

            return back()
                ->with('success', $flash)
                ->with('error', $flash)
                ->with('show_error_modal', true)
                ->with('error_modal_message', $html)
                ->with('failed_entries_json', $failed)
                ->with('redirect_anchor', '#import-Section-invalid');
        }

        /**
         * ✅ SAVE SUCCESSFULLY MOVED
         */
        session([
            'invalidEntries' => array_values($invalid),
            'validEntries'   => array_merge($valid, $moved),
        ]);

        // ✅ per-entry fact logs (NOT summary)
        foreach ($moved as $e) {
            $name = $e['full_name'] ?? 'Unknown';

            $id = $e['volunteer_id']
                ?? ($e['row_number'] ?? null)
                ?? ($e['origin_entry_no'] ?? null);

            $fromNo = $e['origin_entry_no'] ?? '?';

            $this->logFact(
                'Import Move',
                $adminId,
                'VolunteerImport',
                $id,
                'Moved Invalid → Valid',
                "Moved Entry #{$fromNo} — {$name}"
            );
        }

        $count = count($moved);

        $flash = "
            <strong style='color:#28a745;'>✔ Moved {$count} entr" . ($count > 1 ? "ies" : "y") . "</strong>
            &nbsp;|&nbsp;
            <span class='success-details-link'
                style='color:#1565c0; cursor:pointer; text-decoration:none;'>
                Show Details
            </span>
        ";

        // Modal: show ORIGINAL invalid entry number for each moved item
        $html = "
            <div style='font-size:1.3rem; font-weight:700;'>Moved Entries</div>
            <div style='border-bottom:1px solid #dcdcdc; margin:10px 0;'></div>
            <div style='max-height:300px; overflow-y:auto; padding-right:6px;'>
        ";

        foreach ($moved as $e) {
            $safe = htmlspecialchars($e['full_name'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
            $from = (int)($e['origin_entry_no'] ?? 0);

            $html .= "
                <div style='margin-bottom:10px; display:flex; gap:6px; align-items:center;'>
                    <span style='color:#28a745;'>✔</span>
                    <span style='font-weight:600;'>Entry #{$from} — {$safe}</span>
                </div>
                <div style='border-bottom:1px solid #e6e6e6; margin-bottom:10px;'></div>
            ";
        }

        $html .= "</div>";

        return back()
            ->with('success', $flash)
            ->with('show_success_modal', true)
            ->with('success_modal_message', $html)
            ->with('redirect_anchor', '#import-Section-valid');
    }

    /**
     * Move VALID → INVALID
     */
    public function moveValidToInvalid(Request $request, $index)
    {
        $valid   = session('validEntries', []);
        $invalid = session('invalidEntries', []);
        $adminId = Auth::guard('admin')->user()->admin_id ?? null;

        $index = (int)$index;

        // ❌ INVALID INDEX
        if (!isset($valid[$index])) {

            $this->logFact(
                'Import Move',
                $adminId,
                'VolunteerImport',
                null,
                'Move Valid → Invalid — Failed',
                'Entry not found'
            );

            $flash = "
                <strong style='color:#B2000C;'>❌ Entry not found</strong>
                &nbsp;|&nbsp;
                <span class='error-details-link'
                    style='color:#1565c0; cursor:pointer; text-decoration:none;'>
                    Show Details
                </span>
            ";

            $modal = "
                <div style='font-size:1.3rem; font-weight:700; color:#B2000C;'>Entry Not Found</div>
                <div style='border-bottom:1px solid #dcdcdc; margin:10px 0;'></div>
                <div style='display:flex; gap:6px; align-items:flex-start;'>
                    <span style='color:#007bff;'>ℹ️</span>
                    <span>The entry you attempted to move does not exist.</span>
                </div>
            ";

            return back()
                ->with('success', $flash)
                ->with('error', $flash)
                ->with('show_error_modal', true)
                ->with('error_modal_message', $modal)
                ->with('redirect_anchor', '#import-Section-valid');
        }

        $entry = $valid[$index];
        $name  = $entry['full_name'] ?? 'Unknown';

        // capture origin BEFORE unsetting / reindexing
        $entry['origin_bucket']   = 'valid';
        $entry['origin_index']    = $index;
        $entry['origin_entry_no'] = $index + 1;

        unset($valid[$index]);
        $invalid[] = $entry;

        session([
            'validEntries'   => array_values($valid),
            'invalidEntries' => array_values($invalid),
        ]);

        // ✅ per-entry log
        $id = $entry['volunteer_id']
            ?? ($entry['row_number'] ?? null)
            ?? ($entry['origin_entry_no'] ?? null);

        $fromNo = $entry['origin_entry_no'] ?? '?';

        $this->logFact(
            'Import Move',
            $adminId,
            'VolunteerImport',
            $id,
            'Moved Valid → Invalid',
            "Moved Entry #{$fromNo} — {$name}"
        );

        $flash = "
            <strong style='color:#28a745;'>✔ Moved Entry #{$fromNo}</strong>
            <span style='color:#28a745;'> — {$name}</span>
            &nbsp;|&nbsp;
            <span class='success-details-link'
                style='color:#1565c0; cursor:pointer; text-decoration:none;'>
                Show Details
            </span>
        ";

        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        $html = "
            <div style='font-size:1.3rem; font-weight:700;'>Moved Entry</div>
            <div style='border-bottom:1px solid #dcdcdc; margin:10px 0;'></div>
            <div style='display:flex; gap:6px; align-items:center;'>
                <span style='color:#28a745;'>✔</span>
                <span style='font-weight:600;'>Entry #{$fromNo} — {$safeName}</span>
            </div>
            <div style='border-bottom:1px solid #e6e6e6; margin:10px 0;'></div>
        ";

        return back()
            ->with('success', $flash)
            ->with('show_success_modal', true)
            ->with('success_modal_message', $html)
            ->with('redirect_anchor', '#import-Section-invalid');
    }

    /**
     * Delete Entries
     */
    public function deleteEntries(Request $request)
    {
        $tableType = $request->input('table_type'); // invalid / valid / logs
        $selected  = $request->input('selected', []);
        $adminId   = auth()->guard('admin')->id();

        if (empty($selected)) {
            return back()->with('error', 'ℹ️ No entries selected for deletion.');
        }

        $deletedData = [];

        switch ($tableType) {

            case 'invalid':
            case 'valid':
                $entries = session($tableType . 'Entries', []);
                foreach ($selected as $index) {
                    if (isset($entries[$index])) {

                        $deletedData[$index] = $entries[$index];
                        unset($entries[$index]);

                        $volunteerId = $deletedData[$index]['volunteer_id']
                                    ?? $deletedData[$index]['row_number']
                                    ?? null;

                        $name = $deletedData[$index]['full_name'] ?? 'No Name';

                        $this->logFact(
                            'Delete Entry',
                            $adminId,
                            'Volunteer Import',
                            $volunteerId,
                            'Deleted',
                            "Deleted Volunteer Entry #" . ($index + 1) . " {$name}"
                        );
                    }
                }
                session([$tableType . 'Entries' => array_values($entries)]);
                break;

            case 'logs':
                $deletedEntries = ImportLog::whereIn('import_id', $selected)->get();
                foreach ($deletedEntries as $entry) {
                    $deletedData[] = $entry->toArray();
                    $name = $entry->file_name ?? 'No Name';

                    $this->logFact(
                        'Delete Import Log',
                        $adminId,
                        'Volunteer Import',
                        $entry->import_id,
                        'Deleted',
                        "Deleted Import Log '{$name}' (ID {$entry->import_id})"
                    );
                }
                ImportLog::whereIn('import_id', $selected)->delete();
                break;

            default:
                return back()->with('error', '⚠️ Invalid table type.');
        }

        // ---------------------------------------------------------------------
        //                  FORMAT FLASH MESSAGE — ONE CLEAN LINE
        // ---------------------------------------------------------------------
        if (!empty($deletedData)) {

            session([
                'deletedEntriesUndo' => [
                    'tableType' => $tableType,
                    'data'      => $deletedData,
                    'timestamp' => now()
                ]
            ]);

            $formatted = [];
            foreach ($deletedData as $index => $item) {
                $name = $item['full_name'] ?? ($item['file_name'] ?? 'No Name');
                $formatted[] =
                    "Entry #" . ($index + 1) .
                    ": <span style='color:#B2000C; font-weight:600;'>{$name}</span>";
            }

            $total = count($formatted);

            // Begin message
            $message = "
            <div style='display:flex; align-items:center; flex-wrap:wrap; gap:14px;
                        font-size:1.05rem; font-weight:600; color:#333;'>

                <span style='color:#B2000C;'>🗑️ Deleted {$total} entr" . ($total > 1 ? "ies" : "y") . "</span>
            ";

            if ($total === 1) {

                $message .= "
                    <span>{$formatted[0]}</span>

                    <a href='" . route('volunteer.import.undo-delete') . "'
                    style='margin-left:6px; padding:4px 12px;
                            background:#007bff; color:#fff; border-radius:6px;
                            font-size:0.9rem; font-weight:600; text-decoration:none;'>
                        Undo
                    </a>
                ";

            } else {

                $detailsHtml = implode('<br>', $formatted);

                $message .= "
                    <a href='#'
                    class='deleted-details-link'
                    data-details=\"{$detailsHtml}\"
                    style='font-size:0.95rem; font-weight:600;
                            color:#007bff; text-decoration:none;'>
                        View details
                    </a>

                    <a href='" . route('volunteer.import.undo-delete') . "'
                    style='margin-left:6px; padding:4px 12px;
                            background:#007bff; color:#fff; border-radius:6px;
                            font-size:0.9rem; font-weight:600; text-decoration:none;'>
                        Undo
                    </a>
                ";
            }

            $message .= "</div>";

            return back()
                ->with('delete_success', $message)
                ->with('success', $message)
                ->with('last_updated_table', $tableType)
                ->with('last_updated_indices', $selected);
        }

        return back()
            ->with('success', 'No entries deleted.')
            ->with('last_updated_table', $tableType);
    }

    /**
     * Undo Deleted Entries
     */
    public function undoDelete(Request $request)
    {
        $deleted = session('deletedEntriesUndo');
        $adminId = auth()->guard('admin')->id();

        if (!$deleted || empty($deleted['data']) || !isset($deleted['tableType'])) {
            return back()->with('error', 'ℹ️ Nothing to undo.');
        }

        $tableType = $deleted['tableType'];
        $data      = $deleted['data'];

        switch ($tableType) {

            case 'invalid':
            case 'valid':
                $entries = session($tableType . 'Entries', []);
                foreach ($data as $index => $item) {

                    $entries[$index] = $item;

                    $volunteerId = $item['volunteer_id'] ?? $item['row_number'] ?? null;
                    $name        = $item['full_name'] ?? 'No Name';

                    $this->logFact(
                        'Restore Entry',
                        $adminId,
                        'Volunteer Import',
                        $volunteerId,
                        'Restored',
                        "Restored Volunteer Entry #" . ($index + 1) . " {$name}"
                    );
                }
                session([$tableType . 'Entries' => array_values($entries)]);
                break;

            case 'logs':
                foreach ($data as $index => $item) {

                    if (!ImportLog::where('import_id', $item['import_id'])->exists()) {

                        ImportLog::create($item);

                        $entityId = $item['import_id'] ?? null;
                        $name     = $item['file_name'] ?? 'No Name';

                        $this->logFact(
                            'Restore Import Log',
                            $adminId,
                            'Volunteer Import',
                            $entityId,
                            'Restored',
                            "Restored Import Log '{$name}' (ID {$entityId})"
                        );
                    }
                }
                break;

            default:
                return back()->with('error', '⚠️ Invalid table type for undo.');
        }

        session()->forget('deletedEntriesUndo');

        $formatted = [];
        foreach ($data as $index => $item) {
            $name = $item['full_name'] ?? ($item['file_name'] ?? 'No Name');

            $formatted[] =
                "Entry #" . ($index + 1) .
                ": <span style='color:#B2000C; font-weight:600;'>{$name}</span>";
        }

        $total = count($formatted);

        // ---------------------------------------------------------------------
        //                  CLEAN ONE-LINE RESTORE FLASH BAR
        // ---------------------------------------------------------------------
        $message = "
        <div style='display:flex; align-items:center; flex-wrap:wrap; gap:14px;
                    font-size:1.05rem; font-weight:600; color:#333;'>

            <span style='color:#28a745;'>♻️ Restored {$total} entr" . ($total > 1 ? "ies" : "y") . "</span>
        ";

        if ($total === 1) {

            $message .= "
                <span>{$formatted[0]}</span>
            ";

        } else {

            $detailsHtml = implode('<br>', $formatted);

            $message .= "
                <a href='#'
                class='restored-details-link'
                data-details=\"{$detailsHtml}\"
                style='font-size:0.95rem; font-weight:600;
                        color:#007bff; text-decoration:none;'>
                    View details
                </a>
            ";
        }

        $message .= "</div>";

        return back()
            ->with('undo_success', $message)
            ->with('success', $message)
            ->with('last_updated_table', $tableType)
            ->with('last_updated_indices', array_keys($data));
    }

    /**
     * Validate and Save Selected Valid Entries
     */
    public function validateAndSave(Request $request)
    {
        Log::info('DEBUG_SUBMIT: raw selected_valid input', ['raw' => $request->input('selected_valid', [])]);
        Log::info('DEBUG_SUBMIT: session validEntries count', ['count' => count(session('validEntries', []))]);
        Log::info('DEBUG_SUBMIT: session invalidEntries count', ['count' => count(session('invalidEntries', []))]);

        $selectedIndexes = array_values(array_unique(array_map('intval',
            (array)$request->input('selected_valid', [])
        )));

        $validEntries   = session('validEntries', []);
        $invalidEntries = session('invalidEntries', []);
        $fileName       = session('uploaded_file_name', 'N/A');

        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            return back()
                ->with('error_modal', "❌ Admin not authenticated.")
                ->with('error_modal_entries', [
                    [
                        'row' => '-',
                        'name' => 'Authentication Failure',
                        'details' => 'Admin guard returned null user.'
                    ]
                ]);
        }

        $adminId   = $admin->admin_id;
        $adminName = $admin->name ?? $admin->username ?? "Unknown Admin";

        // Block if invalid entries still exist
        if (!empty($invalidEntries)) {

            $entryList = array_map(function($item){
                return [
                    'row'    => $item['row_number'] ?? '?',
                    'name'   => $item['full_name'] ?? 'Unknown',
                    'details'=> json_encode($item, JSON_PRETTY_PRINT)
                ];
            }, $invalidEntries);

            $rows = implode(', ', array_column($invalidEntries, 'row_number'));

            return back()
                ->with('error_modal', "❌ Cannot upload. Invalid entries found in row(s): <strong>{$rows}</strong>.")
                ->with('error_modal_entries', $entryList);
        }

        if (empty($selectedIndexes)) {
            return back()
                ->with('error_modal', "❌ No verified entries selected to save.")
                ->with('error_modal_entries', [
                    [
                        'row' => '-',
                        'name' => 'No Selection',
                        'details' => 'selected_valid[] array was empty.'
                    ]
                ]);
        }

        $entriesToSave = [];
        foreach ($selectedIndexes as $index) {

            if (!isset($validEntries[$index])) continue;

            $entry = $validEntries[$index];

            if ($this->validateRow($entry)) {

                $rowNumber = $entry['row_number'] ?? $index;

                return back()
                    ->with('error_modal', "❌ Validation failed for row <strong>{$rowNumber}</strong>.")
                    ->with('error_modal_entries', [
                        [
                            'row' => $rowNumber,
                            'name' => $entry['full_name'] ?? 'Unknown',
                            'details' => json_encode($entry, JSON_PRETTY_PRINT)
                        ]
                    ]);
            }

            $entriesToSave[] = [
                'index' => $index,
                'data'  => $entry
            ];
        }

        if (empty($entriesToSave)) {
            return back()
                ->with('error_modal', "❌ No valid entries found to save.")
                ->with('error_modal_entries', [
                    [
                        'row' => '-',
                        'name' => 'No Valid Entries',
                        'details' => 'entriesToSave array was empty.'
                    ]
                ]);
        }

        // Save to DB
        try {

            DB::transaction(function () use ($entriesToSave, $adminId, $adminName, $fileName) {

                $previewId  = session('import_log_id');
                $previewLog = ImportLog::find($previewId);

                $timestamp = now()->format('M d, Y h:i A');

                if ($previewLog) {
                    $previewLog->update([
                        'status'          => 'Completed',
                        'total_records'   => count($entriesToSave),
                        'valid_count'     => count($entriesToSave),
                        'invalid_count'   => 0,
                        'duplicate_count' => 0,
                        'remarks'         =>
                            "Imported " . count($entriesToSave) . " row(s) on {$timestamp} by {$adminName}.<br>" .
                            "File: {$fileName}"
                    ]);
                }

                foreach ($entriesToSave as $entryData) {

                    $entry = $entryData['data'];
                    $index = $entryData['index'];

                    // Resolve Course ID
                    $courseName = preg_replace('/\s+/', ' ', trim($entry['course'] ?? ''));
                    $courseId = Course::whereRaw('LOWER(TRIM(course_name)) = ?', [
                        strtolower($courseName)
                    ])->value('course_id');

                    // Resolve Location
                    $barangay    = $entry['barangay'] ?? null;
                    $locationId  = $barangay ? Location::where('barangay', $barangay)->value('location_id') : null;
                    $location    = $locationId ? Location::find($locationId) : null;

                    // Profile picture: convert + download at SAVE time
                    $driveUrl   = $entry['profile_picture'] ?? null;
                    $converted  = $this->convertDriveLinkToDownloadUrl($driveUrl);
                    $localPath  = $this->downloadDriveImage($converted);

                    $volunteer = VolunteerProfile::create([
                        'import_id'      => $previewId,
                        'full_name'      => $entry['full_name'],
                        'id_number'      => $entry['id_number'] ?? "TEMP-" . uniqid(),
                        'course_id'      => $courseId,
                        'year_level'     => $entry['year_level'],
                        'batch_year'     => !empty($entry['batch_year']) ? (int)$entry['batch_year'] : null,
                        'contact_number' => $entry['contact_number'],
                        'emergency_contact' => $entry['emergency_contact'],
                        'email'          => $entry['email'],
                        'fb_messenger'   => $entry['fb_messenger'],
                        'location_id'    => $locationId,
                        'barangay'       => $location->barangay ?? null,
                        'district'       => $location->district_id ?? null,
                        'class_schedule' => $entry['class_schedule'],
                        'profile_picture_url'  => $driveUrl ?: null,
                        'profile_picture_path' => $localPath ?: 'defaults/default_user.png',
                        'status' => 'active',
                    ]);


                    $this->logFact(
                        'Import Verified',
                        $adminId,
                        'VolunteerProfile',
                        $volunteer->volunteer_id,
                        'Imported',
                        "Imported Volunteer Entry #" . ($index + 1) . " – {$entry['full_name']}"
                    );
                }
            });

            // Clear session after success
            session()->forget([
                'validEntries',
                'invalidEntries',
                'uploaded_file_name',
                'uploaded_file_path',
                'csv_imported',
                'import_log_id'
            ]);

            $count     = count($entriesToSave);
            $timestamp = now()->format('M d, Y h:i A');

            $message = "
                <div style='font-size:1.05rem; line-height:1.55;'>
                    <strong style='color:#28a745;'>✔ Successfully saved {$count} entries.</strong><br>
                    Completed on <strong>{$timestamp}</strong><br>
                    File: <strong>{$fileName}</strong>
                </div>
            ";

            return back()->with('submit_success', $message);

        } catch (\Exception $e) {

            Log::error("Import failed!", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString()
            ]);

            $msgLower = strtolower($e->getMessage());

            // 1) Friendly message for the main modal
            $friendlyMessage = "❌ Import failed unexpectedly.";
            $friendlyEntry   = "System Error";

            // 2) Friendly, NON-SQL detail text for the entry card
            $entryDetails = "A system error occurred while saving the entries. No entries were saved.";

            if (str_contains($msgLower, 'duplicate') || str_contains($msgLower, '1062')) {
                $friendlyMessage = "
                    <strong style='color:#B2000C;'>⚠️ Duplicate entries detected.</strong><br>
                    One or more School IDs or Emails already exist in the database.
                ";
                $friendlyEntry  = "Duplicate Entry";
                $entryDetails   = "One or more entries conflict with existing School ID or Email records.";
            } elseif (str_contains($msgLower, 'foreign key')) {
                $friendlyMessage = "
                    <strong style='color:#B2000C;'>⚠️ Invalid linked data.</strong><br>
                    One entry references a course or location that does not exist.
                ";
                $friendlyEntry  = "Invalid Reference";
                $entryDetails   = "An entry referenced a non-existent course or location in the database.";
            } elseif (str_contains($msgLower, 'sqlstate')) {
                $friendlyMessage = "
                    <strong style='color:#B2000C;'>⚠️ Database Error</strong><br>
                    A database issue occurred while saving the entries.
                ";
                $friendlyEntry  = "Database Error";
                $entryDetails   = "A database error occurred during import. Please try again or contact the administrator.";
            }

            // 3) Technical (SQL + stacktrace) ONLY for the Technical Details box
            $technical = $e->getMessage() . "\n\n" . $e->getTraceAsString();

            return back()
                ->with('error_modal', $friendlyMessage)                 // human readable
                ->with('error_modal_technical', $technical)             // raw SQL + trace
                ->with('error_modal_entries', [                         // NON-SQL card
                    [
                        'row'     => '-',
                        'name'    => $friendlyEntry,
                        'details' => $entryDetails
                    ]
                ]);
        }
    }

    /**
     * Reset Preview
     */
    public function resetImports(Request $request)
    {
        $validCount     = session()->has('validEntries') ? count(session('validEntries')) : 0;
        $invalidCount   = session()->has('invalidEntries') ? count(session('invalidEntries')) : 0;
        $duplicateCount = session()->has('duplicateEntries') ? count(session('duplicateEntries')) : 0;

        $totalCleared = $validCount + $invalidCount + $duplicateCount;

        $fileName         = session('uploaded_file_name', 'N/A');
        $originalImportId = session('import_log_id');

        $admin          = auth()->guard('admin')->user();
        $currentAdminId = $admin->admin_id ?? null;
        $adminName      = $admin->name ?? $admin->username ?? "Unknown Admin";
        $formattedTime  = now()->format('M d, Y h:i A');

        // Cancel parent import
        if ($originalImportId) {
            $originalLog = ImportLog::find($originalImportId);

            if ($originalLog && $originalLog->status === 'Pending') {
                $cancelRemark = "Import preview was cancelled by {$adminName} on {$formattedTime}.";

                $originalLog->update([
                    'admin_id'        => $originalLog->admin_id ?: $currentAdminId,
                    'total_records'   => $originalLog->total_records ?: $totalCleared,
                    'valid_count'     => $originalLog->valid_count ?: $validCount,
                    'invalid_count'   => $originalLog->invalid_count ?: $invalidCount,
                    'duplicate_count' => $originalLog->duplicate_count ?: $duplicateCount,
                    'status'          => 'Cancelled',
                    'remarks'         => $cancelRemark,
                ]);
            }
        }

        // Create reset log
        $resetLog = ImportLog::create([
            'file_name'       => $fileName,
            'admin_id'        => $currentAdminId,
            'total_records'   => $totalCleared,
            'valid_count'     => $validCount,
            'invalid_count'   => $invalidCount,
            'duplicate_count' => $duplicateCount,
            'status'          => 'Reset',
            'remarks'         => "Reset cleared {$totalCleared} row(s) on {$formattedTime} by {$adminName}.",
        ]);

        // Clear preview data
        session()->forget([
            'validEntries',
            'invalidEntries',
            'duplicateEntries',
            'uploaded_file_name',
            'uploaded_file_path',
            'csv_imported',
            'import_log_id',
            'lastUsedTable'
        ]);

        session()->flash('clearLastUsedTable', true);

        /** -----------------------------
         * FORMATTED MODAL CONTENT
         * ----------------------------- */
        $modal = "
            <div>
                <strong style='font-size:1.25rem; color:#28a745;'>Reset Completed</strong><br><br>

                <strong>Performed by:</strong> {$adminName}<br>
                <strong>Time:</strong> {$formattedTime}<br><br>

                <strong>Total rows cleared:</strong> 
                <span style='color:#B2000C;'>{$totalCleared}</span><br><br>

                <strong>Breakdown:</strong><br>
                <span style='color:#28a745; margin-left:15px;'>Valid: {$validCount}</span><br>
                <span style='color:#B2000C; margin-left:15px;'>Invalid: {$invalidCount}</span><br>
                <span style='color:#d38b00; margin-left:15px;'>Duplicates: {$duplicateCount}</span><br><br>

                <strong>Reset Log ID:</strong>
                <span style='color:#B2000C;'>{$resetLog->import_id}</span>
            </div>
        ";

        $encoded = base64_encode($modal);

        /** FLASH BAR */
        $flash = "
            <div style='display:flex; align-items:center; flex-wrap:wrap; gap:12px;
                        font-size:1.05rem; font-weight:600;'>

                <span style='color:#28a745;'>♻️ Reset successful</span>

                <span style='color:#999;'>|</span>

                <a href='#'
                class='reset-details-link'
                data-details=\"{$encoded}\"
                style='color:#007bff; text-decoration:none; font-size:0.95rem;'>
                Show Details
                </a>
            </div>
        ";

        /* -----------------------------------------------------------
        FACT LOG — Reset Import Preview (formatted summary)
        ----------------------------------------------------------- */
        $formattedSummary = "Reset Import Preview: {$validCount} valid, {$invalidCount} invalid, {$duplicateCount} duplicates (Total: {$totalCleared}).";

        $this->logFact(
            'Reset Import Preview',
            $currentAdminId,
            'Volunteer Import',
            $resetLog->import_id,
            'Reset',
            $formattedSummary
        );

        return redirect()
            ->route('volunteer.import.index')
            ->with('success', $flash)
            ->with('resetSuccess', $modal)
            ->with('resetDetails', $encoded);
    }
    /**
     * Check if Entries Already Exist in Database (table:volunteer_profile)
     */
    public function checkDuplicates(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([
                'duplicates' => [],
                'message'    => 'No IDs provided.'
            ]);
        }

        $existing = VolunteerProfile::whereIn('id_number', $ids)
                                    ->pluck('id_number')
                                    ->toArray();

        if (!empty($existing)) {
            return response()->json([
                'duplicates' => $existing,
                'message'    =>
                    "⚠️ Cannot submit. The following ID(s) already exist:<br>" .
                    "<strong>" . implode(', ', $existing) . "</strong>"
            ]);
        }

        return response()->json([
            'duplicates' => [],
            'message' => null
        ]);
    }

    /**
     * Update Class Schedule
     */
    public function updateSchedule(Request $request, $id)
    {
        try {
            $scheduleString = $request->input('schedule');
            $type = $request->input('type', 'valid');

            if (!$scheduleString || !is_string($scheduleString)) {
                return redirect()->back()->with('error', 'Invalid schedule data.');
            }

            $entries = session($type . 'Entries', []);

            if (!isset($entries[$id])) {
                return redirect()->back()->with('error', 'Entry not found in session.');
            }

            $entry = $entries[$id];
            $oldSchedule = $entry['class_schedule'] ?? '';

            $days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

            /* -------------------------
            NORMALIZE
            --------------------------*/
            $normalize = function($schedule) use ($days) {
                $result = [];
                foreach ($days as $day) {
                    if (preg_match("/{$day}:\s*(.*?)(?=(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|$))/is",
                        $schedule, $match)) {

                        $raw = trim($match[1]);
                        $raw = str_ireplace('No Class', '', $raw);
                        $raw = preg_replace('/\s+/', ' ', $raw);

                        $result[$day] = $raw ? explode(' ', $raw) : [];
                    } else {
                        $result[$day] = [];
                    }
                }
                return $result;
            };

            $oldParts    = $normalize($oldSchedule);
            $newPartsRaw = $normalize($scheduleString);

            $clean = fn($arr) => array_values(array_filter($arr, fn($v) => trim($v) !== ""));

            /* -------------------------
            TRACK CHANGES
            --------------------------*/
            $newParts = [];
            $reformattedCells = [];

            foreach ($days as $day) {
                $newParts[$day] = [];

                foreach ($newPartsRaw[$day] as $idx => $val) {

                    // auto-format x-x → x:00-x:00
                    $parts = explode('-', $val);
                    if (count($parts) === 2) {
                        $parts = array_map(fn($p) =>
                            preg_match('/^\d{1,2}$/', $p) ? $p . ':00' : $p,
                            $parts
                        );
                        $norm = implode('-', $parts);
                    } else {
                        $norm = $val;
                    }

                    $newParts[$day][$idx] = $norm;

                    $oldVal = $oldParts[$day][$idx] ?? null;
                    if ($oldVal && $norm && $oldVal !== $norm && !in_array($norm, $oldParts[$day] ?? [])) {
                        $reformattedCells[$day][] = ['from' => $oldVal, 'to' => $norm];
                    }
                }
            }

            /* -------------------------
            ADDED / REMOVED
            --------------------------*/
            $changesMade = false;
            $dayChanges = [];

            foreach ($days as $day) {
                $cleanNew = $clean($newParts[$day]);
                $cleanOld = $clean($oldParts[$day]);

                $added   = array_diff($cleanNew, $cleanOld);
                $removed = array_diff($cleanOld, $cleanNew);

                $dayChanges[$day] = [
                    'added'   => $added,
                    'removed' => $removed
                ];

                if ($added || $removed || !empty($reformattedCells[$day] ?? [])) {
                    $changesMade = true;
                }
            }

            /* -------------------------
            SAVE
            --------------------------*/
            // Update display string
            $entries[$id]['class_schedule'] = trim($scheduleString);

            // 🔹 NEW: update underlying per-day arrays used by validateRow()
            foreach ($days as $day) {
                $key = strtolower($day); // "Monday" → "monday"
                $entries[$id][$key] = $clean($newParts[$day]);
            }

            // 🔹 Optional: clear schedule-related errors for those days (if any)
            if (isset($entries[$id]['errors']) && is_array($entries[$id]['errors'])) {
                foreach ($days as $day) {
                    $k = strtolower($day);
                    if (isset($entries[$id]['errors'][$k])) {
                        unset($entries[$id]['errors'][$k]);
                    }
                }
            }

            session([$type . 'Entries' => $entries]);

            /* -------------------------
            ROW #
            --------------------------*/
            $visibleRowIndex = array_search($id, array_keys($entries));
            $rowNumber = $visibleRowIndex !== false ? $visibleRowIndex + 1 : ($id + 1);

            $name = $entry['full_name'] ?? "Volunteer";

            /* -------------------------
            TIME FORMATTER
            --------------------------*/
            $formatTime = function($range) {
                if (!str_contains($range, '-')) return $range;

                [$s, $e] = explode('-', $range);

                $toLabel = function($t) {
                    [$h, $m] = explode(':', $t);
                    $h = intval($h);
                    $suffix = $h < 12 ? "AM" : "PM";
                    if ($h > 12) $h -= 12;
                    if ($h == 0) $h = 12;
                    return "{$h}:{$m} {$suffix}";
                };

                return $toLabel($s) . "–" . $toLabel($e);
            };

            /* -------------------------
            BUILD FULL MODAL MESSAGE
            --------------------------*/

            // Build clean entry name using resolver
            $resolved = $this->resolveEntryData($entry, $rowNumber);
            $name = $resolved['name'];

            // FLASH BAR
            $flashMessage = "
                Update Class Schedule Entry #{$rowNumber} — {$name}
                | <span class='show-modal-details' 
                    style='color:#007bff; cursor:pointer;'>Show More</span>
                ";

            /* -------------------------
            MODAL BODY
            --------------------------*/

            $fullMessage = "
                <strong>Entry #{$rowNumber} for {$name}:</strong><br><br>
            ";

            foreach ($days as $day) {

                $added       = $dayChanges[$day]['added'];
                $removed     = $dayChanges[$day]['removed'];
                $reformatted = $reformattedCells[$day] ?? [];

                // Day label
                $fullMessage .= "<strong>{$day}:</strong><br>";

                if (!$added && !$removed && empty($reformatted)) {
                    $fullMessage .= "ℹ️ No changes made<br>";
                }

                if ($added) {
                    $fullMessage .=
                        "✅ <span style='color:#007bff;'>Added: "
                        . implode(', ', array_map($formatTime, $added))
                        . "</span><br>";
                }

                if ($removed) {
                    $fullMessage .=
                        "⚠️ <span style='color:red;'>Removed: "
                        . implode(', ', array_map($formatTime, $removed))
                        . "</span><br>";
                }

                if ($reformatted) {
                    $fullMessage .=
                        "ℹ️ <span style='color:orange;'>Reformatted: "
                        . implode(', ', array_map(
                            fn($c) => $formatTime($c['from']) . " → " . $formatTime($c['to']),
                            $reformatted
                        ))
                        . "</span><br>";
                }

                // Separator under each day
                $fullMessage .= "<hr style='margin: 8px 0; border-top: 1px solid #ccc;'>";
            }

            /* -------------------------
            LOG FACT
            --------------------------*/

            $adminId = auth()->guard('admin')->id() ?? null;

            $this->logFact(
                'Update Schedule',
                $adminId,
                'Volunteer Import',
                $entry['volunteer_id'] ?? $rowNumber,
                $changesMade ? 'Updated' : 'No Change',
                strip_tags($flashMessage)
            );

            /* -------------------------
            RETURN TO VIEW
            --------------------------*/
            return redirect()->back()
                ->with('success', $flashMessage)
                ->with('success_schedule', $fullMessage)  // <-- HTML preserved
                ->with('last_updated_table', $type)
                ->with('last_updated_index', $id);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * UNIVERSAL entry resolver — handles any CSV header
     */
    private function resolveEntryData(array $entry, int $fallbackRowNum): array
    {
        $normalized = [];
        foreach ($entry as $key => $value) {
            $nk = strtolower(trim($key));
            $nk = str_replace(['_', '-', '  '], [' ', ' ', ' '], $nk);
            $normalized[$nk] = $value;
        }

        // Possible name fields
        $nameFields = [
            'full name', 'fullname', 'full',
            'name',
            'first name', 'firstname', 'first',
            'last name', 'lastname', 'last'
        ];

        $name = null;
        foreach ($nameFields as $nf) {
            if (isset($normalized[$nf]) && !empty(trim($normalized[$nf]))) {
                $name = trim($normalized[$nf]);
                break;
            }
        }

        // fallback: combine first/last if available
        if (!$name) {
            $fn = $normalized['first'] ?? $normalized['first name'] ?? '';
            $ln = $normalized['last']  ?? $normalized['last name']  ?? '';
            $name = trim("$fn $ln");
        }

        if (!$name) $name = "Unknown (Row {$fallbackRowNum})";

        // volunteer id
        $volunteerId = null;
        foreach (['volunteer_id','id','volunteer id'] as $key) {
            if (isset($normalized[$key]) && is_numeric($normalized[$key])) {
                $volunteerId = (int)$normalized[$key];
                break;
            }
        }

        // images
        $pictureLocal = $normalized['profile picture local']
            ?? $normalized['picture local']
            ?? $normalized['local picture']
            ?? null;

        $pictureUrl = $normalized['profile picture url']
            ?? $normalized['picture url']
            ?? $normalized['image url']
            ?? null;

        $src = null;
        if ($pictureLocal) {
            $src = asset("storage/" . ltrim($pictureLocal, '/'));
        } elseif ($pictureUrl) {
            $src = $pictureUrl;
        }

        return [
            'name'         => $name,
            'volunteer_id' => $volunteerId,
            'local'        => $pictureLocal,
            'url'          => $pictureUrl,
            'src'          => $src,
        ];
    }

    /**
     * Update / Replace Profile Picture (manual upload in UI)
     */
    public function updatePicture(Request $request)
    {
        $request->validate([
            'index'       => 'required|integer',
            'type'        => 'required|in:valid,invalid',
            'file'        => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            'set_default' => 'nullable|boolean',
        ]);

        $type  = $request->type;
        $index = $request->index;

        $sessionKey = $type . 'Entries';
        $entries    = session($sessionKey, []);

        if (!isset($entries[$index])) {
            return back()->with('error', 'Entry not found.');
        }

        // ⭐ SAFE ENTRY RESOLUTION
        $resolve = $this->resolveEntryData($entries[$index], $index);
        $name        = $resolve['name'];
        $volunteerId = $resolve['volunteer_id'];
        $oldPath     = $resolve['local'];

        $defaultPath = "defaults/default_user.png";

        // Compute visible row #
        $visibleRowIndex = array_search($index, array_keys($entries));
        $rowNum = $visibleRowIndex !== false ? $visibleRowIndex + 1 : ($index + 1);

        /* ===========================================================
        CASE 1 — RESET TO DEFAULT
        ============================================================ */
        if ($request->boolean('set_default')) {

            if ($oldPath === $defaultPath) {
                return back()
                    ->with('success', "No Changes Made #{$rowNum} {$name} |
                        <span class='show-modal-details' style='color:#007bff; cursor:pointer;'>Show Details</span>")
                    ->with('success_schedule', "
                        <strong style='color:#007bff;'>No Changes Made #{$rowNum} {$name}</strong><br>
                        Already using the default profile picture.")
                    ->with('last_updated_table', $type)
                    ->with('last_updated_index', $index);
            }

            if ($oldPath && $oldPath !== $defaultPath) {
                Storage::disk('public')->delete(ltrim($oldPath, '/'));
            }

            $entries[$index]['profile_picture_local'] = $defaultPath;
            $entries[$index]['profile_picture'] = null;
            session([$sessionKey => $entries]);

            if ($volunteerId && ($v = VolunteerProfile::find($volunteerId))) {
                $v->update([
                    'profile_picture_path' => $defaultPath,
                    'profile_picture_url'  => null
                ]);
            }

            $this->logFact(
                'Reset Profile Picture',
                auth()->guard('admin')->id(),
                'Volunteer Import',
                $volunteerId ?? $rowNum,
                'Updated',
                "Reset Profile Picture for Entry #{$rowNum} – {$name}"
            );

            return back()
                ->with('success', "Profile Picture Reset #{$rowNum} {$name} |
                    <span class='show-modal-details' style='color:#007bff; cursor:pointer;'>Show Details</span>")
                ->with('success_schedule', "
                    <strong style='color:#007bff;'>Profile Picture Reset #{$rowNum} {$name}</strong><br>
                    Default profile picture applied successfully.")
                ->with('last_updated_table', $type)
                ->with('last_updated_index', $index);
        }

        /* ===========================================================
        CASE 2 — UPDATE WITH NEW IMAGE
        ============================================================ */
        if (!$request->hasFile('file')) {

            return back()
                ->with('success', "No Changes Made #{$rowNum} {$name} |
                    <span class='show-modal-details' style='color:#007bff; cursor:pointer;'>Show Details</span>")
                ->with('success_schedule', "
                    <strong style='color:#007bff;'>No Changes Made #{$rowNum} {$name}</strong><br>
                    No new picture was provided.")
                ->with('last_updated_table', $type)
                ->with('last_updated_index', $index);
        }

        $uploaded = $request->file('file');

        if ($oldPath && $oldPath !== $defaultPath) {
            Storage::disk('public')->delete(ltrim($oldPath, '/'));
        }

        $path = $uploaded->store('profile_pictures/volunteers', 'public');

        $entries[$index]['profile_picture_local'] = $path;
        $entries[$index]['profile_picture'] = null;
        session([$sessionKey => $entries]);

        if ($volunteerId && ($v = VolunteerProfile::find($volunteerId))) {
            $v->update([
                'profile_picture_path' => $path,
                'profile_picture_url'  => null
            ]);
        }

        $this->logFact(
            'Update Profile Picture',
            auth()->guard('admin')->id(),
            'Volunteer Import',
            $volunteerId ?? $rowNum,
            'Updated',
            "Updated Profile Picture for Entry #{$rowNum} – {$name}"
        );

        return back()
            ->with('success', "Updated Profile Picture #{$rowNum} {$name} |
                <span class='show-modal-details' style='color:#007bff; cursor:pointer;'>Show Details</span>")
            ->with('success_schedule', "
                <strong style='color:#007bff;'>Updated Profile Picture #{$rowNum} {$name}</strong><br><br>
                <strong>New File:</strong> {$uploaded->getClientOriginalName()}<br>
                <strong>Stored As:</strong> {$path}")
            ->with('last_updated_table', $type)
            ->with('last_updated_index', $index);
    }

    /**
     * Set Default Profile Picture
     */
    public function setDefaultPicture(Request $request)
    {
        $request->validate([
            'index' => 'required|integer',
            'type'  => 'required|in:valid,invalid',
        ]);

        $type  = $request->type;
        $index = $request->index;

        $sessionKey = $type . 'Entries';
        $entries    = session($sessionKey, []);

        if (!isset($entries[$index])) {
            return back()->with('error', 'Entry not found.');
        }

        $resolve = $this->resolveEntryData($entries[$index], $index);
        $name        = $resolve['name'];
        $volunteerId = $resolve['volunteer_id'];
        $current     = $resolve['local'];

        $default = "defaults/default_user.png";

        $visibleRowIndex = array_search($index, array_keys($entries));
        $rowNum = $visibleRowIndex !== false ? $visibleRowIndex + 1 : ($index + 1);

        if ($current === $default) {

            return back()
                ->with('success', "No Changes Made #{$rowNum} {$name} |
                    <span class='show-modal-details' style='color:#007bff; cursor:pointer;'>Show Details</span>")
                ->with('success_schedule', "
                    <strong style='color:#007bff;'>No Changes Made #{$rowNum} {$name}</strong><br>
                    Already using the default profile picture.")
                ->with('last_updated_table', $type)
                ->with('last_updated_index', $index);
        }

        if ($current && $current !== $default) {
            Storage::disk('public')->delete(ltrim($current, '/'));
        }

        $entries[$index]['profile_picture_local'] = $default;
        $entries[$index]['profile_picture'] = null;
        session([$sessionKey => $entries]);

        if ($volunteerId && ($v = VolunteerProfile::find($volunteerId))) {
            $v->update([
                'profile_picture_path' => $default,
                'profile_picture_url'  => null
            ]);
        }

        $this->logFact(
            'Reset Profile Picture',
            auth()->guard('admin')->id(),
            'Volunteer Import',
            $volunteerId ?? $rowNum,
            'Updated',
            "Reset Profile Picture for Entry #{$rowNum} – {$name}"
        );

        return back()
            ->with('success', "Profile Picture Reset #{$rowNum} {$name} |
                <span class='show-modal-details' style='color:#007bff; cursor:pointer;'>Show Details</span>")
            ->with('success_schedule', "
                <strong style='color:#007bff;'>Profile Picture Reset #{$rowNum} {$name}</strong><br>
                Default profile picture applied successfully.")
            ->with('last_updated_table', $type)
            ->with('last_updated_index', $index);
    }

    /**
     * Centralized FactLog helper using existing fact_logs schema.
     * Stores a structured JSON payload in `details`.
     */
        private function logFact(
        string $factType,
        $adminId = null,
        $entity = null,
        ?int $entityId = null,
        ?string $action = null,
        $details = null
    ): FactLog {
        // Resolve admin
        $admin = Auth::guard('admin')->user();

        $resolvedAdminId = is_numeric($adminId)
            ? (int)$adminId
            : ($admin->admin_id ?? null);

        $actor = [
            'admin_id' => $resolvedAdminId,
            'name'     => $admin->name ?? null,
            'username' => $admin->username ?? null,
        ];

        // Resolve entity type + id
        if (is_object($entity)) {
            $entityType = class_basename($entity);
            $modelKey   = method_exists($entity, 'getKey') ? $entity->getKey() : null;
            if ($entityId === null && $modelKey !== null) {
                $entityId = $modelKey;
            }
        } elseif (is_string($entity)) {
            $entityType = $entity;
        } else {
            $entityType = 'Unknown';
        }

        // Try to infer import_id (for JSON context only)
        $importId = null;
        if ($entity instanceof ImportLog) {
            $importId = $entityId ?? $entity->import_id ?? null;
        } else {
            $etLower = strtolower((string)$entityType);
            if (str_contains($etLower, 'import')) {
                $importId = $entityId;
            }
        }

        $timestamp = now();

        // Interpret $details: string → summary, array/object → data
        if (is_array($details) || is_object($details)) {
            $summary = is_array($details) && isset($details['summary'])
                ? (string)$details['summary']
                : null;
            $data = $details;
        } else {
            $summary = $details !== null ? (string)$details : null;
            $data = null;
        }

        $payload = [
            'version' => 1,
            'type'    => $factType,
            'summary' => $summary,
            'actor'   => $actor,
            'entity'  => [
                'type' => $entityType,
                'id'   => $entityId,
            ],
            'action' => $action,
            'data'   => $data,
            'at'     => $timestamp->toIso8601String(),
        ];

        if ($importId !== null) {
            $payload['context']['import_id'] = $importId;
        }

        return FactLog::create([
            'admin_id'    => $resolvedAdminId,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'action'      => $action,
            'details'     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'timestamp'   => $timestamp,
            // ❌ removed 'import_id' here because DB column doesn't exist
        ]);
    }

}
