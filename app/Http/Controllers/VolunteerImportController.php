<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Models\VolunteerProfile;
use App\Models\ImportLog;
use App\Models\FactLog;
use App\Models\Course;
use App\Models\Location;

use App\Services\FactLogger;

class VolunteerImportController extends Controller
{
    protected FactLogger $factLogger;

    public function __construct(FactLogger $factLogger)
    {
        $this->factLogger = $factLogger;
    }

    public function index()
    {
        $validEntries     = session('validEntries', []);
        $invalidEntries   = session('invalidEntries', []);
        $duplicateEntries = session('duplicateEntries', []); // ✅ FIX: load duplicates too
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
        foreach ($duplicateEntries as &$entry) {
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
            'duplicateEntries', // ✅ pass to blade if needed later
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

            $context = stream_context_create([
                "http" => ["timeout" => 8]
            ]);

            $contents = @file_get_contents($url, false, $context);

            if (!$contents) {
                return 'defaults/default_user.png';
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->buffer($contents);

            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
                return 'defaults/default_user.png';
            }

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

    /* ============================================================
       ✅ PATCH HELPERS (new, minimal, used only for your requested formats)
       ============================================================ */

    private function qFile(?string $fileName): string
    {
        $fileName = trim((string)$fileName);
        return $fileName === '' ? '"N/A"' : '"' . $fileName . '"';
    }

    private function fmtCounts(int $valid, int $invalid, int $dup): string
    {
        return "{$valid} valid, {$invalid} invalid, {$dup} duplicates.";
    }

    private function remarkPreview(string $fileName, int $valid, int $invalid, int $dup): string
    {
        return 'Imported ' . $this->qFile($fileName) . ' - ' . $this->fmtCounts($valid, $invalid, $dup);
    }

    private function remarkReset(string $fileName, int $valid, int $invalid, int $dup): string
    {
        return 'Removed/Reset ' . $this->qFile($fileName) . ' - ' . $this->fmtCounts($valid, $invalid, $dup);
    }

    private function remarkValidateSave(string $fileName, int $savedCount, string $adminName): string
    {
        return 'Imported ' . $savedCount . ' Volunteer Entries - Created by ' . $adminName . ' from File Name: ' . $this->qFile($fileName) . '.';
    }

    private function remarkAbandonedLoggedOut(string $fileName, string $adminName): string
    {
        // You explicitly requested this wording for "abandoned"
        return 'Failed to Save Import - "' . $adminName . '" logged out before completing import File Name: ' . $this->qFile($fileName) . '.';
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

        // ✅ PATCH: Mark previous preview as abandoned (requested exact format)
        $previousId = session('import_log_id');
        if ($previousId) {
            $previousLog = ImportLog::find($previousId);
            if ($previousLog && $previousLog->status === 'Pending') {
                $prevFile = $previousLog->file_name ?? $filename;

                $previousLog->update([
                    'status'  => 'Abandoned',
                    'remarks' => $this->remarkAbandonedLoggedOut($prevFile, $adminName),
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
            // ✅ PATCH: keep your flow, but use requested format
            $importLog->update([
                'remarks' => $this->remarkPreview($filename, 0, 0, 0),
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

            // if column count mismatch, mark as invalid
            if (count($row) !== count($header)) {
                $errors = $errors ?? [];
                $errors['csv_format'] = 'Column mismatch: row does not match header count.';
            }

            $data['row_number'] = $i + 2;
            $uniqueKey = strtolower($data['email'] ?? $data['full_name'] ?? 'row_' . $i);

            if (in_array($uniqueKey, $seenKeys, true)) {
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

        // ✅ PATCH: ImportLog remarks in requested format
        $importLog->update([
            'total_records'   => count($rows),
            'valid_count'     => count($valid),
            'invalid_count'   => count($invalid),
            'duplicate_count' => count($duplicates),
            'status'          => 'Pending',
            'remarks'         => $this->remarkPreview($filename, count($valid), count($invalid), count($duplicates)),
        ]);

        // ✅ PATCH: FactLog details in requested format
        if ($admin) {
            $this->logFact(
                'Preview Import',
                $admin->admin_id,
                'Volunteer Import',
                $importLog->import_id,
                'Previewed',
                $this->remarkPreview($filename, count($valid), count($invalid), count($duplicates))
            );
        }

        // Modal details HTML (unchanged)
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

        $encodedDetails = base64_encode($details);

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

        if (!preg_match('/^\d{1,2}:\d{2}$/', $start)) return null;
        if (!preg_match('/^\d{1,2}:\d{2}$/', $end))   return null;

        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $end));

        if ($sh < 0 || $sh > 23 || $eh < 0 || $eh > 23) return null;
        if ($sm < 0 || $sm > 59 || $em < 0 || $em > 59) return null;

        $startMin = $sh * 60 + $sm;
        $endMin   = $eh * 60 + $em;

        if ($endMin <= $startMin) {
            if ($sh >= 12 && $eh >= 1 && $eh <= 7) {
                $eh     += 12;
                $endMin  = $eh * 60 + $em;
            }
        }

        if ($endMin <= $startMin) {
            return null;
        }

        return (object)[
            'start' => $startMin,
            'end'   => $endMin,
        ];
    }

    private function rangesOverlap($a, $b)
    {
        return $a->start < $b->end && $b->start < $a->end;
    }

    private function smartMatchBarangay(?string $raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $needle = strtolower(preg_replace('/\s+/', ' ', $raw));

        static $locationCache = null;

        if ($locationCache === null) {
            $rows = DB::table('locations')
                ->select('barangay', 'district_id')
                ->get();

            $locationCache = [];
            foreach ($rows as $row) {
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
            if ($loc->normalized === $needle) {
                return (object)[
                    'barangay'    => $loc->barangay,
                    'district_id' => $loc->district_id,
                ];
            }

            $dist = levenshtein($needle, $loc->normalized);

            if ($dist < $bestDist) {
                $bestDist = $dist;
                $best = $loc;
            }
        }

        $len = strlen($needle);
        $threshold = max(2, (int) floor($len / 4));

        if ($best && $bestDist <= $threshold) {
            return (object)[
                'barangay'    => $best->barangay,
                'district_id' => $best->district_id,
            ];
        }

        return null;
    }

    private function normalizeRow(array $row, array $header): array
    {
        $mapping = [
            'full_name' => 'full_name', 'fullname' => 'full_name', 'full name' => 'full_name',
            'first name' => 'first_name', 'firstname' => 'first_name',
            'middle name' => 'middle_name', 'middlename' => 'middle_name',
            'last name' => 'last_name', 'lastname' => 'last_name', 'surname' => 'last_name',

            'id number' => 'id_number', 'school id' => 'id_number',
            'school id number' => 'id_number', 'id' => 'id_number',

            'contact number' => 'contact_number', 'contact_number' => 'contact_number',
            'phone' => 'contact_number', 'phone number' => 'contact_number',
            'contact no' => 'contact_number', 'contact #' => 'contact_number',

            'emergency number' => 'emergency_contact',
            'emergency_contact' => 'emergency_contact',
            'emergency contact' => 'emergency_contact',
            'emergency contact number' => 'emergency_contact',
            'emergency no' => 'emergency_contact',
            'emergency #' => 'emergency_contact',

            'email address' => 'email', 'email' => 'email',
            'school email address' => 'email', 'school email' => 'email',
            'adzu email' => 'email', 'email add' => 'email',

            'fb link' => 'fb_messenger', 'facebook profile link' => 'fb_messenger',
            'messenger' => 'fb_messenger', 'fb' => 'fb_messenger',

            'barangay' => 'barangay', 'brgy' => 'barangay',
            'district' => 'district',

            'course' => 'course', 'strand' => 'course', 'program' => 'course',

            'year' => 'year_level', 'year level' => 'year_level', 'yearlevel' => 'year_level',
            'year_level' => 'year_level',

            'batch' => 'batch_year',
            'batch number' => 'batch_year',
            'batch no' => 'batch_year',
            'cohort' => 'batch_year',

            'monday schedule' => 'monday', 'monday' => 'monday',
            'tuesday schedule' => 'tuesday', 'tuesday' => 'tuesday',
            'wednesday schedule' => 'wednesday', 'wednesday' => 'wednesday',
            'thursday schedule' => 'thursday', 'thursday' => 'thursday',
            'friday schedule' => 'friday', 'friday' => 'friday',
            'saturday schedule' => 'saturday', 'saturday' => 'saturday',

            'certificates' => 'certificates',
            'certificate uploads' => 'certificates',

            'profile picture' => 'profile_picture',
            'profile_photo' => 'profile_picture',
            'google drive link to your profile picture (jpg or png)' => 'profile_picture',
        ];

        $normalized = [];

        $fixTime = function ($t) {
            $t = trim($t);
            return preg_match('/^\d{1,2}$/', $t) ? $t . ":00" : $t;
        };

        $cleanSchedule = function ($raw) use ($fixTime) {
            if (!$raw) return [];

            $raw = preg_replace('/\[[MAE]\]/i', '', $raw);
            $raw = str_replace(['–', ';', ','], ['-', ' ', ' '], $raw);
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

        foreach ($header as $i => $colRaw) {
            $keyRaw = strtolower(trim((string) $colRaw));
            $value  = isset($row[$i]) ? trim((string)$row[$i]) : '';

            if (in_array($keyRaw, ['timestamp', 'time submitted', 'date'], true)) continue;

            if (preg_match('/do you have.*class\?/i', $keyRaw)) {
                continue;
            }

            $mapped =
                ($mapping[$keyRaw] ?? null)
                ?? ($mapping[preg_replace('/\s+/', ' ', $keyRaw)] ?? null)
                ?? ($mapping[str_replace([' ', '-'], '_', $keyRaw)] ?? null);

            if (in_array($mapped, $scheduleDays, true)) {
                if (!preg_match('/\d/', $value)) continue;

                $slots = $cleanSchedule($value);
                if (!empty($slots)) {
                    $normalized[$mapped] = array_values(array_unique(
                        array_merge($normalized[$mapped], $slots)
                    ));
                }
                continue;
            }

            if ($mapped === 'profile_picture') {
                if ($value !== '') {
                    $converted = $this->convertDriveLinkToDownloadUrl($value);
                    $localPath = $this->downloadDriveImage($converted);
                    $normalized['profile_picture'] = $value;
                    $normalized['profile_picture_local'] = $localPath;
                }
                continue;
            }

            if ($mapped) {
                $normalized[$mapped] = $value;
            }
        }

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

        if (!empty($normalized['barangay'])) {
            $cleanBrgy = ucwords(strtolower(trim($normalized['barangay'])));
            $match = $this->smartMatchBarangay($cleanBrgy);
            if ($match) {
                $normalized['barangay'] = $match->barangay;
                $normalized['district'] = $match->district_id;
            }
        }

        if (!empty($normalized['course'])) {
            $c = trim($normalized['course']);
            $db = DB::table('courses')
                ->whereRaw('LOWER(course_name)=?', [strtolower($c)])
                ->value('course_name');
            $normalized['course'] = $db ?? ucwords(strtolower($c));
        }

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
         * Accepts: 2023, 23, 23-24, Batch 23-24, etc.
         * Rule: if it's a range like 23-24 => use starting year (2023)
         */
        if (!empty($normalized['batch_year'])) {

            $raw = (string) $normalized['batch_year'];

            // ✅ detect range like 23-24 (or 23/24, 23 24)
            if (preg_match('/\b(\d{2})\D+(\d{2})\b/', $raw, $m)) {
                $normalized['batch_year'] = 2000 + (int)$m[1]; // 23-24 => 2023
            } else {

                $digits = preg_replace('/\D+/', '', $raw);

                if (preg_match('/^20\d{2}$/', $digits)) {
                    $normalized['batch_year'] = (int)$digits;        // 2023
                } elseif (preg_match('/^(\d{2})$/', $digits, $m)) {
                    $normalized['batch_year'] = 2000 + (int)$m[1];    // 23 => 2023
                } else {
                    $normalized['batch_year'] = null; // weird input
                }
            }
        }

        if (empty($normalized['batch_year']) && !empty($normalized['id_number'])) {
            $id = preg_replace('/\D+/', '', (string)$normalized['id_number']);

            if (preg_match('/^(\d{2})\d{4,5}$/', $id, $m)) {
                $yy = (int)$m[1];
                $normalized['batch_year'] = 2000 + $yy;
            }
        }

        $out = [];
        foreach (['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day) {
            $slots = $normalized[strtolower($day)] ?? [];
            $out[] = $day . ': ' . (empty($slots) ? 'No Class' : implode(' ', $slots));
        }
        $normalized['class_schedule'] = implode(' ', $out);

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

        foreach (['contact_number', 'emergency_contact'] as $field) {
            if (!empty($normalized[$field])) {
                $normalized[$field] = preg_replace('/[^\d+]/', '', $normalized[$field]);
            }
        }

        return $normalized;
    }

    private function validateRow(array $data)
    {
        $errors = [];

        if (empty($data['full_name']) ||
            !preg_match("/^[A-Za-zÑñ\s\.\'-]+$/u", $data['full_name'])) {
            $errors['full_name'] = 'Full Name is required and only letters allowed.';
        }

        if (empty($data['batch_year'])) {
            $errors['batch_year'] = 'Batch year is required.';
        } elseif (!preg_match('/^20\d{2}$/', (string)$data['batch_year'])) {
            $errors['batch_year'] = 'Batch year must be a 4-digit year like 2023.';
        }

        if (empty($data['id_number']) ||
            !preg_match('/^\d{6,7}$/', (string)$data['id_number'])) {
            $errors['id_number'] = 'School ID must be 6 or 7 digits.';
        }

        if (empty($data['course']) ||
            !preg_match('/^[A-Za-z\s]+$/u', $data['course'])) {
            $errors['course'] = 'Course is required.';
        }

        if (empty($data['year_level']) ||
            !in_array((string)$data['year_level'], ['1', '2', '3', '4'], true)) {
            $errors['year_level'] = 'Year must be 1–4.';
        }

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

        if (empty($data['email']) ||
            !filter_var($data['email'], FILTER_VALIDATE_EMAIL) ||
            !preg_match('/@(gmail\.com|adzu\.edu\.ph)$/i', $data['email'])) {
            $errors['email'] = 'Email must end with @gmail.com or @adzu.edu.ph.';
        }

        $fb = trim((string)($data['fb_messenger'] ?? ''));
        if ($fb !== '') {
            $ok = filter_var($fb, FILTER_VALIDATE_URL);
            $host = strtolower(parse_url($fb, PHP_URL_HOST) ?: '');

            $allowedHosts = [
                'facebook.com', 'www.facebook.com', 'fb.com', 'www.fb.com',
                'm.facebook.com', 'fb.me', 'm.me'
            ];
            if (!$ok || !in_array($host, $allowedHosts, true)) {
                $errors['fb_messenger'] = 'FB/Messenger must be a valid Facebook/Messenger link.';
            }
        }

        if (empty(trim($data['barangay'] ?? ''))) {
            $errors['barangay'] = 'Barangay is required.';
        } else {
            $match = $this->smartMatchBarangay($data['barangay']);
            if (!$match) {
                $errors['barangay'] = "Invalid barangay: '{$data['barangay']}'";
            }
        }

        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $day) {
            $slots = $data[$day] ?? [];

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
     * ✅ NEW: Keep monday..saturday arrays in sync with class_schedule string
     * (validateRow uses day arrays; without this, schedule edits "save" but never validate)
     */
    private function syncDayArraysFromClassSchedule(array &$entry): void
    {
        $schedule = (string)($entry['class_schedule'] ?? '');
        $parts = $this->scheduleStringToDayArrays($schedule);

        foreach (['monday','tuesday','wednesday','thursday','friday','saturday'] as $day) {
            $entry[$day] = $parts[$day] ?? [];
        }
    }

    public function updateVolunteerEntry(Request $request, $index, $type)
    {
        // --- UNCHANGED (your code continues exactly) ---
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

        if (!empty($input['batch_year'])) {

            $raw = (string) $input['batch_year'];

            if (preg_match('/\b(\d{2})\D+(\d{2})\b/', $raw, $m)) {
                $input['batch_year'] = 2000 + (int)$m[1]; // 23-24 => 2023
            } else {

                $digits = preg_replace('/\D+/', '', $raw);

                if (preg_match('/^20\d{2}$/', $digits)) {
                    $input['batch_year'] = (int)$digits;
                } elseif (preg_match('/^(\d{2})$/', $digits, $m)) {
                    $input['batch_year'] = 2000 + (int)$m[1];
                } else {
                    $input['batch_year'] = null; // let validator complain
                }
            }
        }

        if (!empty($input['batch_year'])) {
            $digits = preg_replace('/\D+/', '', (string) $input['batch_year']);

            if (strlen($digits) === 4) {
                $input['batch_year'] = (int) $digits;
            } elseif (strlen($digits) === 2) {
                $input['batch_year'] = 2000 + (int) $digits;
            } else {
                $input['batch_year'] = null;
            }
        }

        $validator = \Validator::make($input, [
            'full_name'         => ['required','regex:/^[A-Za-zÑñ\s\.\'-]+$/u','max:255'],
            'id_number'         => ['required','regex:/^\d{6,7}$/'],
            'course'            => 'required|string|max:100',
            'year_level'        => ['required','in:1,2,3,4'],
            'contact_number'    => ['required','regex:/^(09\d{9}|\+639\d{9})$/'],
            'emergency_contact' => ['required','regex:/^(09\d{9}|\+639\d{9})$/'],
            'email'             => ['required','email','regex:/^[A-Za-z0-9._%+-]+@(gmail\.com|adzu\.edu\.ph)$/i'],
            'fb_messenger'      => ['nullable'],
            'barangay'          => ['required'],
            'district'          => ['required'],
            'class_schedule'    => ['required','string','regex:/^[\w\s,:()\.\-\/]+$/'],
            'batch_year'        => ['nullable','digits:4','integer','min:2000','max:2100'],
        ],[
            'year_level.in'           => 'Year must be 1, 2, 3, or 4.',
            'district.required'       => 'No district selected.',
            'barangay.required'       => 'No barangay selected.',
            'class_schedule.required' => 'Class schedule is required.',
            'class_schedule.regex'    => 'Class schedule contains invalid characters.',
            'batch_year.digits'       => 'Batch year must be a 4-digit year like 2023.',
            'batch_year.min'          => 'Batch year is too early.',
            'batch_year.max'          => 'Batch year is too far in the future.',
        ]);

        $errors = $validator->fails() ? $validator->errors()->toArray() : [];

        // ✅ FIX: recompute schedule errors from current schedule (do not preserve stale ones)
        $currentSchedule = (string)($entries[$index]['class_schedule'] ?? $before['class_schedule'] ?? '');
        if (array_key_exists('class_schedule', $input) && empty($errors['class_schedule'])) {
            $currentSchedule = (string)$input['class_schedule'];
        }

        $scheduleErrors = $this->scheduleErrorsFromString($currentSchedule);

        // remove stale day errors then apply fresh schedule errors
        foreach (['monday','tuesday','wednesday','thursday','friday','saturday'] as $day) {
            unset($errors[$day]);
        }
        foreach ($scheduleErrors as $day => $msgs) {
            if (!empty($msgs)) {
                $errors[$day] = $msgs;
            }
        }

        if (!empty($input['fb_messenger'])) {
            $fb = $input['fb_messenger'];
            if (!filter_var($fb, FILTER_VALIDATE_URL) ||
                stripos(parse_url($fb, PHP_URL_HOST) ?: '', 'facebook.com') === false)
            {
                $errors['fb_messenger'] = ['FB/Messenger must be a valid Facebook link'];
            }
        }

        if (!empty($input['barangay'])) {
            $districtId = $input['district'] ?? null;

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

        $editableFields = [
            'full_name',
            'id_number',
            'course',
            'year_level',
            'batch_year',
            'contact_number',
            'emergency_contact',
            'email',
            'fb_messenger',
            'barangay',
            'district',
            'district_id',
            'college',
            'class_schedule',
        ];

        $compareFields = [
            'full_name',
            'id_number',
            'course',
            'year_level',
            'batch_year',
            'contact_number',
            'emergency_contact',
            'email',
            'fb_messenger',
            'barangay',
            'district',
            'class_schedule',
        ];

        $beforeCompare = [];
        $inputCompare  = [];

        foreach ($compareFields as $f) {
            $old = $before[$f] ?? '';
            $new = $input[$f]  ?? '';

            if ($f === 'district') {
                $old = preg_replace('/\D/', '', (string)$old);
                $new = preg_replace('/\D/', '', (string)$new);
            }

            if ($f === 'class_schedule') {
                $old = preg_replace('/\s+/', ' ', trim((string)$old));
                $new = preg_replace('/\s+/', ' ', trim((string)$new));
            }

            if ($f === 'batch_year') {
                $old = ($old === '' || $old === null) ? null : (int)$old;
                $new = ($new === '' || $new === null) ? null : (int)$new;
            } else {
                $old = preg_replace('/\s+/', ' ', trim((string)$old));
                $new = preg_replace('/\s+/', ' ', trim((string)$new));
            }

            $beforeCompare[$f] = $old;
            $inputCompare[$f]  = $new;
        }

        $updatedFields = [];
        foreach ($inputCompare as $field => $newVal) {
            if (isset($errors[$field])) continue;
            $oldVal = $beforeCompare[$field] ?? null;

            if ($newVal !== $oldVal) {
                $updatedFields[$field] = $input[$field] ?? null;
            }
        }

        foreach ($editableFields as $field) {
            if (!array_key_exists($field, $input)) continue;
            if (isset($errors[$field])) continue;
            if (!array_key_exists($field, $updatedFields)) continue;

            $entries[$index][$field] = $input[$field];
        }

        if (array_key_exists('class_schedule', $updatedFields)) {
            $this->syncDayArraysFromClassSchedule($entries[$index]);
        }

        $entries[$index]['errors'] = $errors;

        if (empty($errors)) {
            unset($entries[$index]['errors']);
        }

        session([$type . 'Entries' => $entries]);

        if (!empty($entries[$index]['volunteer_id']) && !empty($updatedFields)) {
            if ($vol = VolunteerProfile::find($entries[$index]['volunteer_id'])) {
                $vol->update(array_merge($updatedFields, ['status' => 'active']));
            }
        }

        $adminId = Auth::guard('admin')->id();
        $labels = [
            'full_name'         => 'Full Name',
            'id_number'         => 'School ID',
            'course'            => 'Course',
            'year_level'        => 'Year Level',
            'batch_year'        => 'Batch Year',
            'contact_number'    => 'Contact #',
            'emergency_contact' => 'Emergency #',
            'email'             => 'Email',
            'fb_messenger'      => 'FB/Messenger',
            'barangay'          => 'Barangay',
            'district'          => 'District',
            'class_schedule'    => 'Class Schedule',
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

            $entryName = trim((string)($entries[$index]['full_name'] ?? $before['full_name'] ?? $entry['full_name'] ?? 'Unknown'));

            $this->logFact(
                'Update Entry',
                $adminId,
                isset($entries[$index]['volunteer_id']) ? 'VolunteerProfile' : 'Volunteer Import',
                $entityId,
                'Updated',
                "Updated Entry #" . ($index + 1) . " – {$entryName}: " . implode(', ', $fieldDetails)
            );

        }

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
            $newDisplay = $entries[$index][$field] ?? ($input[$field] ?? '');

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
                $block .= "
                    <span style='margin-top:2px;'>&#9888;</span>
                    <span>
                        <span style='color:#B2000C; font-weight:600;'>Error:</span>
                        <span style='color:#B2000C;'> {$errorMsg}</span>
                    </span>
                ";
            } elseif ($wasChanged) {
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

        $title = !empty($updatedFields)
            ? "✔ Changes Saved #{$row}"
            : "✔ No Changes Made #{$row}";

        $flash = "
            <strong style='color:#28a745;'>{$title}</strong>
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

    // --- moveInvalidToValid, moveValidToInvalid, deleteEntries, undoDelete UNCHANGED ---
    // (Your code remains as-is exactly from here...)

    public function moveInvalidToValid(Request $request)
    {
        // ... UNCHANGED ...
        $invalid = session('invalidEntries', []);
        $valid   = session('validEntries', []);
        $adminId = Auth::guard('admin')->user()->admin_id ?? null;

        $selected = (array) $request->input('selected_invalid', []);
        $selected = array_values(array_unique(array_map('intval', $selected)));
        sort($selected);

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

            $entry['origin_bucket']   = 'invalid';
            $entry['origin_index']    = $index;
            $entry['origin_entry_no'] = $index + 1;

            $this->syncDayArraysFromClassSchedule($entry);

            $reErrors = $this->validateRow($entry);
            if (!empty($reErrors)) {
                $failed[] = [
                    'index'  => $index + 1,
                    'name'   => $name,
                    'errors' => $reErrors,
                ];
                continue;
            }

            $moved[] = $entry;
            unset($invalid[$index]);
        }

        session([
            'invalidEntries' => array_values($invalid),
            'validEntries'   => array_merge($valid, $moved),
        ]);

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

        if (!empty($failed)) {

            $failedCount = count($failed);
            $movedCount  = count($moved);

            $flash = "
                <strong style='color:#d38b00;'>⚠️ Partially moved</strong>
                <span style='color:#666;'>— moved {$movedCount}, failed {$failedCount}</span>
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
                ->with('redirect_anchor', '#import-Section-valid');
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

    public function moveValidToInvalid(Request $request, $index)
    {
        // ... UNCHANGED (your original code) ...
        $valid   = session('validEntries', []);
        $invalid = session('invalidEntries', []);
        $adminId = Auth::guard('admin')->user()->admin_id ?? null;

        $index = (int)$index;

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

        $entry['origin_bucket']   = 'valid';
        $entry['origin_index']    = $index;
        $entry['origin_entry_no'] = $index + 1;

        unset($valid[$index]);
        $invalid[] = $entry;

        session([
            'validEntries'   => array_values($valid),
            'invalidEntries' => array_values($invalid),
        ]);

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

    public function deleteEntries(Request $request)
    {
        // ... UNCHANGED (your original code) ...
        $tableType = $request->input('table_type');
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
                    if (!isset($entries[$index])) continue;

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

        if (empty($deletedData)) {
            return back()
                ->with('success', 'No entries deleted.')
                ->with('last_updated_table', $tableType);
        }

        session([
            'deletedEntriesUndo' => [
                'tableType'  => $tableType,
                'data'       => $deletedData,
                'timestamp'  => now(),
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

            $detailsHtmlRaw = implode('<br>', $formatted);
            $detailsAttr    = e($detailsHtmlRaw);

            $message .= "
                <a href='#'
                class='deleted-details-link'
                data-details=\"{$detailsAttr}\"
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

    public function undoDelete(Request $request)
    {
        // ... UNCHANGED (your original code) ...
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
                foreach ($data as $item) {

                    if (ImportLog::where('import_id', $item['import_id'])->exists()) {
                        continue;
                    }

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

        $message = "
        <div style='display:flex; align-items:center; flex-wrap:wrap; gap:14px;
                    font-size:1.05rem; font-weight:600; color:#333;'>
            <span style='color:#28a745;'>♻️ Restored {$total} entr" . ($total > 1 ? "ies" : "y") . "</span>
        ";

        if ($total === 1) {

            $message .= "<span>{$formatted[0]}</span>";

        } else {

            $detailsHtmlRaw = implode('<br>', $formatted);
            $detailsAttr    = e($detailsHtmlRaw);

            $message .= "
                <a href='#'
                class='restored-details-link'
                data-details=\"{$detailsAttr}\"
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
                        'row'    => '-',
                        'name'   => 'Authentication Failure',
                        'status' => 'invalid',
                        'issues' => ['Admin guard returned null user.'],
                    ]
                ]);
        }

        $adminId   = $admin->admin_id;
        $adminName = $admin->name ?? $admin->username ?? "Unknown Admin";

        // ✅ PATCH: HARD GUARD so import_id never becomes NULL
        $previewId  = session('import_log_id');
        $previewLog = $previewId ? ImportLog::find($previewId) : null;

        if (!$previewId || !$previewLog) {

            $msg = $this->remarkAbandonedLoggedOut($fileName, $adminName);

            $this->logFact(
                'Import Failed',
                $adminId,
                'Volunteer Import',
                null,
                'Failed',
                $msg
            );

            return back()
                ->with('error_modal', "❌ Import session expired or missing. Please preview the CSV again.")
                ->with('error_modal_entries', [
                    [
                        'row'    => '-',
                        'name'   => 'Import Session Missing',
                        'status' => 'invalid',
                        'issues' => [$msg],
                    ]
                ]);
        }

        // ✅ If session has invalid entries, block immediately
        if (!empty($invalidEntries)) {

            $entryList = array_map(function ($item) {
                $row  = $item['row_number'] ?? '?';
                $name = $item['full_name'] ?? 'Unknown';

                $issues = [];
                $errs = $item['errors'] ?? null;
                if (is_array($errs)) {
                    foreach ($errs as $field => $msgs) {
                        foreach ((array)$msgs as $m) {
                            $issues[] = ucwords(str_replace('_', ' ', (string)$field)) . ": " . (string)$m;
                        }
                    }
                }

                if (empty($issues)) $issues[] = "Invalid data found in this entry.";

                return [
                    'row'    => $row,
                    'name'   => $name,
                    'status' => 'invalid',
                    'issues' => $issues,
                ];
            }, $invalidEntries);

            $rows = implode(', ', array_filter(array_map(function ($x) {
                return $x['row_number'] ?? null;
            }, $invalidEntries)));

            return back()
                ->with('error_modal', "❌ Cannot upload. Invalid entries found in row(s): <strong>{$rows}</strong>.")
                ->with('error_modal_entries', $entryList);
        }

        if (empty($selectedIndexes)) {
            return back()
                ->with('error_modal', "❌ No verified entries selected to save.")
                ->with('error_modal_entries', [
                    [
                        'row'    => '-',
                        'name'   => 'No Selection',
                        'status' => 'invalid',
                        'issues' => ['No rows were selected (selected_valid[] was empty).'],
                    ]
                ]);
        }

        // ✅ Build entriesToSave (synced schedules)
        $entriesToSave = [];
        foreach ($selectedIndexes as $index) {

            if (!isset($validEntries[$index])) continue;

            $entry = $validEntries[$index];

            // ✅ sync schedule arrays for validation
            $this->syncDayArraysFromClassSchedule($entry);

            $localErrors = $this->validateRow($entry);
            if ($localErrors) {

                $rowNumber = $entry['row_number'] ?? ($index + 1);

                $issues = [];
                foreach ($localErrors as $field => $msgs) {
                    foreach ((array)$msgs as $m) {
                        $issues[] = ucwords(str_replace('_', ' ', (string)$field)) . ": " . (string)$m;
                    }
                }

                return back()
                    ->with('error_modal', "❌ Validation failed for row <strong>{$rowNumber}</strong>.")
                    ->with('error_modal_entries', [
                        [
                            'row'    => $rowNumber,
                            'name'   => $entry['full_name'] ?? 'Unknown',
                            'status' => 'invalid',
                            'issues' => $issues ?: ['Invalid entry.'],
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
                        'row'    => '-',
                        'name'   => 'No Valid Entries',
                        'status' => 'invalid',
                        'issues' => ['No valid entries were collected for saving.'],
                    ]
                ]);
        }

        // ✅ Detect duplicates BEFORE saving
        $statusList  = [];
        $hasBlocking = false;

        foreach ($entriesToSave as $pack) {

            $entry = $pack['data'];
            $row   = $entry['row_number'] ?? ($pack['index'] + 1);
            $name  = $entry['full_name'] ?? 'Unknown';

            $email = trim((string)($entry['email'] ?? ''));
            $idnum = trim((string)($entry['id_number'] ?? ''));

            $emailExists = $email !== '' ? VolunteerProfile::where('email', $email)->exists() : false;
            $idExists    = $idnum !== '' ? VolunteerProfile::where('id_number', $idnum)->exists() : false;

            if ($emailExists || $idExists) {
                $hasBlocking = true;

                $issues = [];
                $issues[] = "Volunteer Already Exist";
                if ($emailExists) $issues[] = "Email already exists: {$email}";
                if ($idExists)    $issues[] = "School ID already exists: {$idnum}";

                $statusList[] = [
                    'row'    => $row,
                    'name'   => $name,
                    'status' => 'duplicate',
                    'issues' => $issues,
                ];
            } else {
                $statusList[] = [
                    'row'    => $row,
                    'name'   => $name,
                    'status' => 'good',
                    'issues' => [],
                ];
            }
        }

        if ($hasBlocking) {
            return back()
                ->with('error_modal', "❌ Upload blocked. Some selected entries already exist in the database.")
                ->with('error_modal_entries', $statusList);
        }

        $entriesToActuallySave = array_values(array_filter($entriesToSave, function ($pack) use ($statusList) {
            $row = $pack['data']['row_number'] ?? ($pack['index'] + 1);
            foreach ($statusList as $s) {
                if ((string)$s['row'] === (string)$row && ($s['status'] ?? '') !== 'good') {
                    return false;
                }
            }
            return true;
        }));

        // ✅ collect created volunteers for post-commit FactLog (prevents "only first log" bug)
        $createdVolunteers = [];

        try {

            DB::transaction(function () use (
                $entriesToActuallySave,
                $adminId,
                $adminName,
                $fileName,
                $previewId,
                &$createdVolunteers
            ) {

                $previewLog = ImportLog::find($previewId);

                // ✅ ImportLog remarks in requested format for Validate & Save
                if ($previewLog) {
                    $previewLog->update([
                        'status'          => 'Completed',
                        'total_records'   => count($entriesToActuallySave),
                        'valid_count'     => count($entriesToActuallySave),
                        'invalid_count'   => 0,
                        'duplicate_count' => 0,
                        'remarks'         => $this->remarkValidateSave($fileName, count($entriesToActuallySave), $adminName),
                    ]);
                }

                foreach ($entriesToActuallySave as $entryData) {

                    $entry = $entryData['data'];
                    $index = $entryData['index'];

                    $courseName = preg_replace('/\s+/', ' ', trim($entry['course'] ?? ''));
                    $courseId = Course::whereRaw('LOWER(TRIM(course_name)) = ?', [
                        strtolower($courseName)
                    ])->value('course_id');

                    $barangay    = $entry['barangay'] ?? null;
                    $locationId  = $barangay ? Location::where('barangay', $barangay)->value('location_id') : null;
                    $location    = $locationId ? Location::find($locationId) : null;

                    $driveUrl   = $entry['profile_picture'] ?? null;
                    $converted  = $this->convertDriveLinkToDownloadUrl($driveUrl);
                    $localPath  = $this->downloadDriveImage($converted);

                    $volunteer = VolunteerProfile::create([
                        'import_id'           => $previewId, // ✅ guaranteed non-null now
                        'full_name'           => $entry['full_name'],
                        'id_number'           => $entry['id_number'] ?? "TEMP-" . uniqid(),
                        'course_id'           => $courseId,
                        'year_level'          => $entry['year_level'],
                        'batch_year'          => !empty($entry['batch_year']) ? (int)$entry['batch_year'] : null,
                        'contact_number'      => $entry['contact_number'],
                        'emergency_contact'   => $entry['emergency_contact'],
                        'email'               => $entry['email'],
                        'fb_messenger'        => $entry['fb_messenger'],
                        'location_id'         => $locationId,
                        'barangay'            => $location->barangay ?? null,
                        'district'            => $location->district_id ?? null,
                        'class_schedule'      => $entry['class_schedule'],
                        'profile_picture_url' => $driveUrl ?: null,
                        'profile_picture_path'=> $localPath ?: 'defaults/default_user.png',
                        'status'              => 'active',
                    ]);

                    // ✅ collect for post-commit FactLog
                    $createdVolunteers[] = [
                        'volunteer_id' => $volunteer->volunteer_id,
                        'index'        => $index,
                        'full_name'    => $entry['full_name'] ?? 'Unknown',
                    ];
                }
            });

            // ✅ POST-COMMIT: FactLog every saved entry (bulk)
            foreach ($createdVolunteers as $v) {
                $this->logFact(
                    'Import Verified',
                    $adminId,
                    'VolunteerProfile',
                    (int)$v['volunteer_id'],
                    'Imported',
                    "Imported Volunteer Entry #" . ((int)$v['index'] + 1) . " – " . ($v['full_name'] ?? 'Unknown')
                );
            }

            // ✅ main FactLog summary in requested format
            $summary = $this->remarkValidateSave($fileName, count($entriesToActuallySave), $adminName);
            $this->logFact(
                'Import Completed',
                $adminId,
                'Volunteer Import',
                (int)$previewId,
                'Completed',
                $summary
            );

            session()->forget([
                'validEntries',
                'invalidEntries',
                'duplicateEntries',
                'uploaded_file_name',
                'uploaded_file_path',
                'csv_imported',
                'import_log_id'
            ]);

            $count     = count($entriesToActuallySave);
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

            $technical = $e->getMessage() . "\n\n" . $e->getTraceAsString();

            return back()
                ->with('error_modal', "<strong style='color:#B2000C;'>⚠️ Import failed.</strong><br>A database/system error occurred while saving.")
                ->with('error_modal_technical', $technical)
                ->with('error_modal_entries', [
                    [
                        'row'    => '-',
                        'name'   => 'System Error',
                        'status' => 'invalid',
                        'issues' => ['A system/database error occurred. Check Technical Details if needed.'],
                    ]
                ]);
        }
    }

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

        if ($originalImportId) {
            $originalLog = ImportLog::find($originalImportId);

            if ($originalLog && $originalLog->status === 'Pending') {
                // keep as Cancelled, but you can keep your cancel remark if you want;
                // the actual Reset log below uses your requested Reset format.
                $originalLog->update([
                    'admin_id'        => $originalLog->admin_id ?: $currentAdminId,
                    'total_records'   => $originalLog->total_records ?: $totalCleared,
                    'valid_count'     => $originalLog->valid_count ?: $validCount,
                    'invalid_count'   => $originalLog->invalid_count ?: $invalidCount,
                    'duplicate_count' => $originalLog->duplicate_count ?: $duplicateCount,
                    'status'          => 'Cancelled',
                    'remarks'         => "Import preview was cancelled by {$adminName} on {$formattedTime}.",
                ]);
            }
        }

        // ✅ PATCH: Reset log remarks in requested format
        $resetLog = ImportLog::create([
            'file_name'       => $fileName,
            'admin_id'        => $currentAdminId,
            'total_records'   => $totalCleared,
            'valid_count'     => $validCount,
            'invalid_count'   => $invalidCount,
            'duplicate_count' => $duplicateCount,
            'status'          => 'Reset',
            'remarks'         => $this->remarkReset($fileName, $validCount, $invalidCount, $duplicateCount),
        ]);

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

        $modal = "
            <div style='font-size:1.02rem; line-height:1.65; color:#333;'>
                <div style='font-size:1.35rem; font-weight:800; color:#28a745; margin-bottom:8px;'>
                    ✅ Reset Completed
                </div>
                <div style='border-bottom:1px solid #e6e6e6; margin:10px 0 14px;'></div>

                <div style='margin-bottom:10px;'>
                    <strong>Performed by:</strong> <span style='color:#007bff; font-weight:700;'>{$adminName}</span><br>
                    <strong>Time:</strong> {$formattedTime}
                </div>

                <div style='margin:12px 0; padding:12px 14px; border-radius:12px;
                            background:rgba(178,0,12,.06); border:1px solid rgba(178,0,12,.18);'>
                    <div style='display:flex; justify-content:space-between; gap:10px; margin-bottom:8px;'>
                        <span style='font-weight:800;'>Total rows cleared</span>
                        <span style='font-weight:900; color:#B2000C;'>{$totalCleared}</span>
                    </div>

                    <div style='display:flex; flex-wrap:wrap; gap:8px;'>
                        <span style='display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px;
                                     font-size:.86rem; font-weight:800; color:#1f7a39;
                                     border:1px solid rgba(40,167,69,.25); background:rgba(40,167,69,.08);'>
                            ✅ Valid: {$validCount}
                        </span>

                        <span style='display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px;
                                     font-size:.86rem; font-weight:800; color:#B2000C;
                                     border:1px solid rgba(178,0,12,.25); background:rgba(178,0,12,.08);'>
                            ❌ Invalid: {$invalidCount}
                        </span>

                        <span style='display:inline-flex; align-items:center; gap:6px; padding:6px 10px; border-radius:999px;
                                     font-size:.86rem; font-weight:800; color:#a56b00;
                                     border:1px solid rgba(211,139,0,.25); background:rgba(211,139,0,.10);'>
                            ⚠️ Duplicates: {$duplicateCount}
                        </span>
                    </div>
                </div>

                <div style='margin-top:10px;'>
                    <strong>Reset Log ID:</strong>
                    <span style='color:#B2000C; font-weight:800;'>{$resetLog->import_id}</span>
                </div>
            </div>
        ";

        $encoded = base64_encode($modal);

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

        // ✅ PATCH: FactLog reset details in requested format
        $this->logFact(
            'Reset Import Preview',
            $currentAdminId,
            'Volunteer Import',
            $resetLog->import_id,
            'Reset',
            $this->remarkReset($fileName, $validCount, $invalidCount, $duplicateCount)
        );

        return redirect()
            ->route('volunteer.import.index')
            ->with('success', $flash)
            ->with('resetDetails', $encoded)
            ->with('show_success_modal', true)
            ->with('success_modal_title', 'Reset Completed')
            ->with('success_modal_subtitle', 'Import preview cleared successfully.')
            ->with('success_modal_message', $modal);
    }

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

    private function scheduleStringToDayArrays(string $schedule): array
    {
        $days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
        $out = [];
        foreach ($days as $day) $out[strtolower($day)] = [];

        foreach ($days as $day) {
            if (preg_match("/{$day}:\s*(.*?)(?=(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|$))/is", $schedule, $m)) {
                $raw = trim($m[1]);
                $raw = str_ireplace('No Class', '', $raw);
                $raw = preg_replace('/\s+/', ' ', $raw);
                $out[strtolower($day)] = $raw ? array_values(array_filter(explode(' ', $raw))) : [];
            }
        }
        return $out;
    }

    private function scheduleErrorsFromString(string $schedule): array
    {
        $schedule = trim(preg_replace('/\s+/', ' ', (string) $schedule));
        if ($schedule === '') return [];

        $parts = $this->scheduleStringToDayArrays($schedule);

        $errors = [];
        foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'] as $day) {
            $slots = $parts[$day] ?? [];
            if (!is_array($slots)) {
                $slots = trim((string) $slots) !== '' ? [trim((string) $slots)] : [];
            }

            $parsed = [];
            foreach ($slots as $slot) {
                $slot = trim((string) $slot);
                if ($slot === '') continue;

                $range = $this->parseTimeRange($slot);
                if (!$range) {
                    $errors[$day][] = "Invalid time format '{$slot}'";
                    continue;
                }

                foreach ($parsed as $p) {
                    if ($this->rangesOverlap($range, $p['range'])) {
                        $errors[$day][] = "Conflict: '{$slot}' overlaps '{$p['raw']}'";
                    }
                }

                $parsed[] = ['raw' => $slot, 'range' => $range];
            }
        }

        return $errors;
    }

    public function updateSchedule(Request $request, $id)
    {
        // --- UNCHANGED (your original updateSchedule) ---
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

            $newParts = [];
            $reformattedCells = [];

            foreach ($days as $day) {
                $newParts[$day] = [];

                foreach ($newPartsRaw[$day] as $idx => $val) {

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

            $entries[$id]['class_schedule'] = trim($scheduleString);

            $this->syncDayArraysFromClassSchedule($entries[$id]);

            $existingErrors = (isset($entries[$id]['errors']) && is_array($entries[$id]['errors']))
                ? $entries[$id]['errors']
                : [];

            foreach ($days as $day) {
                $k = strtolower($day);
                unset($existingErrors[$k]);
            }

            $freshScheduleErrors = $this->scheduleErrorsFromString((string) $entries[$id]['class_schedule']);
            foreach ($freshScheduleErrors as $k => $msgs) {
                if (!empty($msgs)) $existingErrors[$k] = $msgs;
            }

            if (empty($existingErrors)) unset($entries[$id]['errors']);
            else $entries[$id]['errors'] = $existingErrors;

            session([$type . 'Entries' => $entries]);

            $rowNumber = $id + 1;
            $resolved  = $this->resolveEntryData($entries[$id], $rowNumber);
            $name      = $resolved['name'];

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

            $fullMessage = "
                <div style='font-size:1.02rem; line-height:1.55;'>
                    <div style='font-size:1.15rem; font-weight:800; margin-bottom:8px;'>
                        Entry #{$rowNumber} — " . e($name) . "
                    </div>
                    <div style='border-bottom:1px solid #e6e6e6; margin:10px 0 14px;'></div>
            ";

            foreach ($days as $day) {
                $added       = $dayChanges[$day]['added'];
                $removed     = $dayChanges[$day]['removed'];
                $reformatted = $reformattedCells[$day] ?? [];

                $fullMessage .= "<div style='margin-bottom:10px;'><strong>{$day}:</strong><br>";

                if (!$added && !$removed && empty($reformatted)) {
                    $fullMessage .= "&#8505;&#65039; No changes made<br>";
                }

                if ($added) {
                    $fullMessage .=
                        "&#10004; <span style='color:#007bff;'>Added: "
                        . implode(', ', array_map($formatTime, $added))
                        . "</span><br>";
                }

                if ($removed) {
                    $fullMessage .=
                        "&#9888; <span style='color:#B2000C;'>Removed: "
                        . implode(', ', array_map($formatTime, $removed))
                        . "</span><br>";
                }

                if ($reformatted) {
                    $fullMessage .=
                        "&#8505;&#65039; <span style='color:#d38b00;'>Reformatted: "
                        . implode(', ', array_map(
                            fn($c) => $formatTime($c['from']) . " → " . $formatTime($c['to']),
                            $reformatted
                        ))
                        . "</span><br>";
                }

                $fullMessage .= "</div><div style='border-bottom:1px solid #f0f0f0; margin:8px 0;'></div>";
            }

            $fullMessage .= "</div>";

            $encodedDetails = base64_encode($fullMessage);

            $title = $changesMade
                ? "Schedule saved"
                : "Schedule saved (no changes detected)";

            $flashMessage = "
                <strong style='color:#28a745;'>{$title}</strong>
                <span style='color:#28a745; font-weight:600;'> — Entry #{$rowNumber} " . e($name) . "</span>
                &nbsp;|&nbsp;
                <span class='update-details-link'
                    data-details=\"{$encodedDetails}\"
                    style='color:#007bff; cursor:pointer; text-decoration:none;'>
                    Show More
                </span>
            ";

            $adminId = auth()->guard('admin')->id() ?? null;

            $logLine = "Update Class Schedule Entry #{$rowNumber} — {$name}";

            $this->logFact(
                'Update Schedule',
                $adminId,
                'Volunteer Import',
                $entry['volunteer_id'] ?? $rowNumber,
                $changesMade ? 'Updated' : 'No Change',
                $logLine
            );

            return redirect()->back()
                ->with('success', $flashMessage)
                ->with('success_schedule', $fullMessage)
                ->with('updateDetails', $encodedDetails)
                ->with('last_updated_table', $type)
                ->with('last_updated_index', $id);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function resolveEntryData(array $entry, int $fallbackRowNum): array
    {
        // ... UNCHANGED ...
        $normalized = [];
        foreach ($entry as $key => $value) {
            $nk = strtolower(trim($key));
            $nk = str_replace(['_', '-', '  '], [' ', ' ', ' '], $nk);
            $normalized[$nk] = $value;
        }

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

        if (!$name) {
            $fn = $normalized['first'] ?? $normalized['first name'] ?? '';
            $ln = $normalized['last']  ?? $normalized['last name']  ?? '';
            $name = trim("$fn $ln");
        }

        if (!$name) $name = "Unknown (Row {$fallbackRowNum})";

        $volunteerId = null;
        foreach (['volunteer_id','id','volunteer id'] as $key) {
            if (isset($normalized[$key]) && is_numeric($normalized[$key])) {
                $volunteerId = (int)$normalized[$key];
                break;
            }
        }

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

    public function updatePicture(Request $request)
    {
        // ... UNCHANGED (your original code) ...
        $request->validate([
            'index'       => 'required|integer',
            'type'        => 'required|in:valid,invalid',
            'set_default' => 'nullable|boolean',
        ]);

        $type  = $request->type;
        $index = (int) $request->index;

        $sessionKey = $type . 'Entries';
        $entries    = session($sessionKey, []);

        if (!isset($entries[$index])) {
            return back()->withErrors(['file' => 'Entry not found.']);
        }

        $resolve     = $this->resolveEntryData($entries[$index], $index + 1);
        $name        = $resolve['name'];
        $volunteerId = $resolve['volunteer_id'];
        $oldPath     = $resolve['local'];

        $defaultPath = "defaults/default_user.png";
        $rowNum      = $index + 1;
        $adminId     = auth()->guard('admin')->id() ?? null;

        if ($request->boolean('set_default')) {

            if ($oldPath === $defaultPath) {
                $this->logFact(
                    'Reset Profile Picture',
                    $adminId,
                    'Volunteer Import',
                    $volunteerId ?? $rowNum,
                    'No Change',
                    "Reset Profile Picture Entry #{$rowNum} — {$name}"
                );

                // ✅ PATCH: build details payload + embed into Show Details + store updateDetails
                $detailsHtml = "
                    <strong style='color:#007bff;'>No Changes Made #{$rowNum} {$name}</strong><br>
                    Already using the default profile picture.
                ";
                $encodedDetails = base64_encode($detailsHtml);

                return back()
                    ->with('success', "No Changes Made #{$rowNum} {$name} |
                        <span class='show-modal-details'
                            data-details=\"{$encodedDetails}\"
                            style='color:#007bff; cursor:pointer; text-decoration:none;'>
                            Show Details
                        </span>")
                    ->with('updateDetails', $encodedDetails)
                    ->with('success_schedule', $detailsHtml)
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
                $adminId,
                'Volunteer Import',
                $volunteerId ?? $rowNum,
                'Updated',
                "Reset Profile Picture Entry #{$rowNum} — {$name}"
            );

            // ✅ PATCH
            $detailsHtml = "
                <strong style='color:#007bff;'>Profile Picture Reset #{$rowNum} {$name}</strong><br>
                Default profile picture applied successfully.
            ";
            $encodedDetails = base64_encode($detailsHtml);

            return back()
                ->with('success', "Profile Picture Reset #{$rowNum} {$name} |
                    <span class='show-modal-details'
                        data-details=\"{$encodedDetails}\"
                        style='color:#007bff; cursor:pointer; text-decoration:none;'>
                        Show Details
                    </span>")
                ->with('updateDetails', $encodedDetails)
                ->with('success_schedule', $detailsHtml)
                ->with('last_updated_table', $type)
                ->with('last_updated_index', $index);
        }

        if ($request->has('file') && !$request->hasFile('file')) {
            $err = $_FILES['file']['error'] ?? null;

            $msg = match ($err) {
                UPLOAD_ERR_INI_SIZE => 'The file is too large for the server (upload_max_filesize).',
                UPLOAD_ERR_FORM_SIZE => 'The file is too large for this form.',
                UPLOAD_ERR_PARTIAL => 'The file upload was interrupted (partial upload).',
                UPLOAD_ERR_NO_TMP_DIR => 'Server misconfigured: missing temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Server error: failed to write file to disk.',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by a PHP extension.',
                default => 'The file failed to upload. (PHP rejected the upload before Laravel received it.)',
            };

            return back()->withErrors(['file' => $msg])
                ->with('last_updated_table', $type)
                ->with('last_updated_index', $index);
        }

        if (!$request->hasFile('file')) {

            $this->logFact(
                'Update Profile Picture',
                $adminId,
                'Volunteer Import',
                $volunteerId ?? $rowNum,
                'No Change',
                "Update Profile Picture Entry #{$rowNum} — {$name}"
            );

            // ✅ PATCH
            $detailsHtml = "
                <strong style='color:#007bff;'>No Changes Made #{$rowNum} {$name}</strong><br>
                No new picture was provided.
            ";
            $encodedDetails = base64_encode($detailsHtml);

            return back()
                ->with('success', "No Changes Made #{$rowNum} {$name} |
                    <span class='show-modal-details'
                        data-details=\"{$encodedDetails}\"
                        style='color:#007bff; cursor:pointer; text-decoration:none;'>
                        Show Details
                    </span>")
                ->with('updateDetails', $encodedDetails)
                ->with('success_schedule', $detailsHtml)
                ->with('last_updated_table', $type)
                ->with('last_updated_index', $index);
        }

        $request->validate([
            'file' => 'required|image|mimes:jpg,jpeg,png|max:51200',
        ]);

        $uploaded = $request->file('file');

        if (!$uploaded->isValid()) {
            $this->logFact(
                'Update Profile Picture',
                $adminId,
                'Volunteer Import',
                $volunteerId ?? $rowNum,
                'Failed',
                "Update Profile Picture Entry #{$rowNum} — {$name}"
            );

            return back()->withErrors([
                'file' => $this->friendlyUploadErrorMessage($uploaded) ?? 'Upload failed (invalid file).'
            ])->with('last_updated_table', $type)
            ->with('last_updated_index', $index);
        }

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
            $adminId,
            'Volunteer Import',
            $volunteerId ?? $rowNum,
            'Updated',
            "Update Profile Picture Entry #{$rowNum} — {$name}"
        );

        // ✅ PATCH
        $detailsHtml = "
            <strong style='color:#007bff;'>Updated Profile Picture #{$rowNum} {$name}</strong><br><br>
            <strong>New File:</strong> {$uploaded->getClientOriginalName()}<br>
            <strong>Stored As:</strong> {$path}
        ";
        $encodedDetails = base64_encode($detailsHtml);

        return back()
            ->with('success', "Updated Profile Picture #{$rowNum} {$name} |
                <span class='show-modal-details'
                    data-details=\"{$encodedDetails}\"
                    style='color:#007bff; cursor:pointer; text-decoration:none;'>
                    Show Details
                </span>")
            ->with('updateDetails', $encodedDetails)
            ->with('success_schedule', $detailsHtml)
            ->with('last_updated_table', $type)
            ->with('last_updated_index', $index);
    }

    private function friendlyUploadErrorMessage($file): ?string
    {
        if (!$file) {
            return "The file failed to upload. This is usually caused by server limits (upload_max_filesize/post_max_size) or missing temp permissions.";
        }

        $err = method_exists($file, 'getError') ? $file->getError() : null;

        return match ($err) {
            UPLOAD_ERR_INI_SIZE   => "The file is too large for the server (upload_max_filesize).",
            UPLOAD_ERR_FORM_SIZE  => "The file is too large for the form.",
            UPLOAD_ERR_PARTIAL    => "The file upload was interrupted (partial upload).",
            UPLOAD_ERR_NO_FILE    => "No file was uploaded.",
            UPLOAD_ERR_NO_TMP_DIR => "Server misconfigured: missing temporary upload folder.",
            UPLOAD_ERR_CANT_WRITE => "Server can’t write uploads to disk (permissions).",
            UPLOAD_ERR_EXTENSION  => "A PHP extension stopped the upload.",
            default               => null,
        };
    }

    public function setDefaultPicture(Request $request)
    {
        // ... UNCHANGED (your original code) ...
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

        $resolve     = $this->resolveEntryData($entries[$index], $index + 1);
        $name        = $resolve['name'];
        $volunteerId = $resolve['volunteer_id'];
        $current     = $resolve['local'];

        $default = "defaults/default_user.png";
        $rowNum  = $index + 1;
        $adminId = auth()->guard('admin')->id() ?? null;

        if ($current === $default) {

            $this->logFact(
                'Reset Profile Picture',
                $adminId,
                'Volunteer Import',
                $volunteerId ?? $rowNum,
                'No Change',
                "Reset Profile Picture Entry #{$rowNum} — {$name}"
            );

            // ✅ PATCH
            $detailsHtml = "
                <strong style='color:#007bff;'>No Changes Made #{$rowNum} {$name}</strong><br>
                Already using the default profile picture.
            ";
            $encodedDetails = base64_encode($detailsHtml);

            return back()
                ->with('success', "No Changes Made #{$rowNum} {$name} |
                    <span class='show-modal-details'
                        data-details=\"{$encodedDetails}\"
                        style='color:#007bff; cursor:pointer; text-decoration:none;'>
                        Show Details
                    </span>")
                ->with('updateDetails', $encodedDetails)
                ->with('success_schedule', $detailsHtml)
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
            $adminId,
            'Volunteer Import',
            $volunteerId ?? $rowNum,
            'Updated',
            "Reset Profile Picture Entry #{$rowNum} — {$name}"
        );

        // ✅ PATCH
        $detailsHtml = "
            <strong style='color:#007bff;'>Profile Picture Reset #{$rowNum} {$name}</strong><br>
            Default profile picture applied successfully.
        ";
        $encodedDetails = base64_encode($detailsHtml);

        return back()
            ->with('success', "Profile Picture Reset #{$rowNum} {$name} |
                <span class='show-modal-details'
                    data-details=\"{$encodedDetails}\"
                    style='color:#007bff; cursor:pointer; text-decoration:none;'>
                    Show Details
                </span>")
            ->with('updateDetails', $encodedDetails)
            ->with('success_schedule', $detailsHtml)
            ->with('last_updated_table', $type)
            ->with('last_updated_index', $index);
    }

    private function cleanLogText(?string $html): string
    {
        $t = html_entity_decode((string) $html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = strip_tags($t);

        $t = preg_replace('/\b(show\s+(more|details|full)|view\s+details|undo|show\s+missing|open|click)\b/i', '', $t);

        $t = str_replace(['|', '•'], ' ', $t);
        $t = preg_replace('/\s+/', ' ', $t);

        return trim($t, " \t\n\r\0\x0B-–—:;,.|");
    }

    private function fileLabel(?string $pathOrUrl): string
    {
        $s = trim((string) $pathOrUrl);
        if ($s === '') return 'N/A';

        $parsed = parse_url($s);
        if (is_array($parsed) && isset($parsed['path'])) {
            $s = $parsed['path'];
        }

        $base = basename($s);
        return $base !== '' ? $base : 'N/A';
    }

    private function logFact(
        string $factType,
        $adminId = null,
        $entity = null,
        ?int $entityId = null,
        ?string $action = null,
        $details = null
    ): FactLog {
        return $this->factLogger->log(
            $factType,
            $action,
            $entity,
            $entityId,
            $details,
            is_numeric($adminId) ? (int)$adminId : null
        );
    }
}
