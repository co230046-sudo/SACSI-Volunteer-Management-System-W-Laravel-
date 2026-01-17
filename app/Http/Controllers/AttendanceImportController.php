<?php

namespace App\Http\Controllers;

use App\Models\AttendanceImportLog;
use App\Models\Event;
use App\Models\EventAttendance;
use App\Models\EventFeedback;
use App\Models\VolunteerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use App\Services\FactLogger;

class AttendanceImportController extends Controller
{
    private const STATUS_PLANNED   = 'planned';
    private const STATUS_ONGOING   = 'ongoing';
    private const STATUS_COMPLETED = 'completed';
    private const STATUS_CANCELLED = 'cancelled';

    public function __construct(private FactLogger $factLogger)
    {
    }

    // Preview session storage is scoped per event
    private function previewSessionKey(Event $event): string
    {
        return 'attendance_preview_event_' . $event->event_id;
    }

    // Avatar URL fallback used in preview table
    private function volunteerAvatarUrl(?VolunteerProfile $v): string
    {
        if ($v && !empty($v->profile_picture_path)) {
            return asset('storage/' . ltrim((string)$v->profile_picture_path, '/'));
        }

        if ($v && !empty($v->avatar_url)) {
            return (string)$v->avatar_url;
        }

        return asset('storage/defaults/default_user.png');
    }

    // Summary format used by FactLogger
    private function attendanceSummary(string $action, Event $event): string
    {
        $title = trim((string)($event->title ?? ''));
        $code  = trim((string)($event->event_code ?? '—'));

        $safeTitle = $title !== '' ? $title : 'Untitled Event';
        return sprintf('%s Attendance - “%s” (Code: %s)', $action, $safeTitle, ($code !== '' ? $code : '—'));
    }

    // Fact logging for attendance import actions
    private function logAttendanceFact(string $type, string $action, Event $event, array $data = []): void
    {
        $admin = Auth::guard('admin')->user();
        $adminId = $admin?->admin_id;

        $this->factLogger->log(
            type: $type,
            action: $action,
            entity: $event,
            entityId: $event->event_id,
            details: [
                'summary' => $this->attendanceSummary($action, $event),
                'data' => array_merge([
                    'event' => [
                        'id' => $event->event_id,
                        'code' => $event->event_code,
                        'title' => $event->title,
                    ],
                ], $data),
            ],
            adminId: $adminId
        );
    }

    // Attendance import page
    public function index(Event $event)
    {
        $derivedStatus = $this->deriveStatus(
            $event->status,
            $event->start_datetime ? Carbon::parse($event->start_datetime) : null,
            $event->end_datetime ? Carbon::parse($event->end_datetime) : null,
            Carbon::now()
        );

        $event->status = $derivedStatus;

        $preview = session($this->previewSessionKey($event), null);

        return view('attendance_import.index', [
            'event'         => $event,
            'derivedStatus' => $derivedStatus,
            'preview'       => $preview,
            'success'       => session('success'),
            'error'         => session('error'),
        ]);
    }

    // Clear preview data
    public function reset(Event $event)
    {
        session()->forget($this->previewSessionKey($event));

        $this->logAttendanceFact(
            type: 'attendance.reset',
            action: 'Cleared',
            event: $event,
            data: [
                'preview_session_cleared' => true,
            ]
        );

        return redirect()->route('attendance.import.index', $event->event_id)
            ->with('success', 'Import preview cleared.');
    }

    // Upload CSV and build preview buckets
    public function preview(Request $request, Event $event)
    {
        $derivedStatus = $this->deriveStatus(
            $event->status,
            $event->start_datetime ? Carbon::parse($event->start_datetime) : null,
            $event->end_datetime ? Carbon::parse($event->end_datetime) : null,
            Carbon::now()
        );

        if ($derivedStatus !== self::STATUS_COMPLETED) {
            $this->logAttendanceFact(
                type: 'attendance.preview.blocked',
                action: 'Blocked Attendance Preview',
                event: $event,
                data: [
                    'derived_status' => $derivedStatus,
                ]
            );

            return redirect()->route('attendance.import.index', $event->event_id)
                ->with('error', "Attendance import is only allowed when event status is COMPLETED. Current status: " . strtoupper($derivedStatus));
        }

        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $file = $request->file('csv_file');
        if (!$file || !$file->isValid()) {
            $this->logAttendanceFact(
                type: 'attendance.preview.failed',
                action: 'Failed Attendance Preview',
                event: $event,
                data: [
                    'reason' => 'invalid_upload_or_missing',
                ]
            );

            return redirect()->route('attendance.import.index', $event->event_id)
                ->with('error', 'Invalid file upload. Please try again.');
        }

        $path = $file->getRealPath();
        $filename = $file->getClientOriginalName();

        try {
            [$rows, $header, $normalizedHeader] = $this->readCsv($path);
        } catch (\Throwable $e) {
            $this->logAttendanceFact(
                type: 'attendance.preview.failed',
                action: 'Failed Attendance Preview',
                event: $event,
                data: [
                    'filename' => $filename,
                    'reason'   => 'csv_read_failed',
                    'error'    => $e->getMessage(),
                ]
            );

            return redirect()->route('attendance.import.index', $event->event_id)
                ->with('error', 'Could not read the CSV. Make sure it is a valid export from Google Forms.');
        }

        if (count($rows) === 0) {
            $this->logAttendanceFact(
                type: 'attendance.preview.failed',
                action: 'Failed Attendance Preview',
                event: $event,
                data: [
                    'filename' => $filename,
                    'reason'   => 'empty_csv',
                ]
            );

            return redirect()->route('attendance.import.index', $event->event_id)
                ->with('error', 'The uploaded CSV has no rows.');
        }

        $requiredKeys = [
            'event_code',
            'full_name',
            'school_id',
            'school_email',
            'attendance_confirmation',
        ];

        $missing = [];
        foreach ($requiredKeys as $k) {
            if (!in_array($k, $normalizedHeader, true)) $missing[] = $k;
        }

        if (!empty($missing)) {
            $this->logAttendanceFact(
                type: 'attendance.preview.failed',
                action: 'Failed Attendance Preview',
                event: $event,
                data: [
                    'filename' => $filename,
                    'reason'   => 'missing_headers',
                    'missing'  => $missing,
                    'header'   => $header,
                ]
            );

            $nice = implode(', ', array_map(fn($m) => strtoupper(str_replace('_', ' ', $m)), $missing));

            return redirect()->route('attendance.import.index', $event->event_id)
                ->with('error', "CSV header mismatch. Missing required columns: {$nice}. Please use the Google Forms CSV template/export.");
        }

        $batch = 'ATT-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6));
        $result = $this->validateRows($event, $rows);

        session([
            $this->previewSessionKey($event) => [
                'batch' => $batch,
                'filename' => $filename,
                'header' => $header,
                'normalized_header' => $normalizedHeader,
                'total' => count($rows),

                'valid' => $result['valid'],
                'invalid' => $result['invalid'],
                'duplicate' => $result['duplicate'],
                'already_imported' => $result['already_imported'],

                'counts' => $result['counts'],
            ],
        ]);

        $this->logAttendanceFact(
            type: 'attendance.preview',
            action: 'Previewed',
            event: $event,
            data: [
                'filename' => $filename,
                'batch'    => $batch,
                'counts'   => $result['counts'],
            ]
        );

        return redirect()->route('attendance.import.index', $event->event_id)
            ->with('success', 'Preview ready. Review rows, edit/delete if needed, then Save Import.');
    }

    // Update a row inside the preview session
    public function updatePreviewRow(Request $request, Event $event)
    {
        $derivedStatus = $this->deriveStatus(
            $event->status,
            $event->start_datetime ? Carbon::parse($event->start_datetime) : null,
            $event->end_datetime ? Carbon::parse($event->end_datetime) : null,
            Carbon::now()
        );

        if ($derivedStatus !== self::STATUS_COMPLETED) {
            return response()->json(['ok' => false, 'message' => 'Import preview editing is only allowed when event is COMPLETED.'], 422);
        }

        $previewKey = $this->previewSessionKey($event);
        $preview = session($previewKey);
        if (!$preview) {
            return response()->json(['ok' => false, 'message' => 'No preview found. Upload a CSV first.'], 422);
        }

        $data = $request->validate([
            'row' => ['required', 'integer', 'min:2'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'school_id' => ['nullable', 'string', 'max:64'],
            'school_email' => ['nullable', 'string', 'max:255'],
            'attendance_confirmation' => ['required', 'in:Present,Walk-in'],
        ]);

        $rowNumber = (int)$data['row'];

        $updated = false;
        foreach (['valid', 'invalid', 'already_imported'] as $bucket) {
            $rows = $preview[$bucket] ?? [];
            foreach ($rows as $idx => $r) {
                if ((int)($r['row'] ?? 0) !== $rowNumber) continue;

                $r['full_name'] = $this->cleanName($data['full_name'] ?? $r['full_name'] ?? null);
                $r['school_id'] = $this->cleanSchoolId($data['school_id'] ?? $r['school_id'] ?? null);
                $r['school_email'] = $this->cleanEmail($data['school_email'] ?? $r['school_email'] ?? null);

                $confirm = $data['attendance_confirmation'] === 'Walk-in' ? 'Walk-in' : 'Present';
                $r['attendance_confirmation'] = $confirm;
                $r['walk_in'] = ($confirm === 'Walk-in');

                $r = $this->recheckEditedPayloadRow($event, $r);

                $rows[$idx] = $r;
                $preview[$bucket] = $rows;
                $updated = true;
                break 2;
            }
        }

        if (!$updated) {
            return response()->json(['ok' => false, 'message' => 'Row not found in preview.'], 404);
        }

        $preview = $this->rebucketAndRecount($preview, $event);
        session([$previewKey => $preview]);

        $this->logAttendanceFact(
            type: 'attendance.preview_row.updated',
            action: 'Updated Attendance Preview',
            event: $event,
            data: [
                'row' => $rowNumber,
            ]
        );

        return response()->json(['ok' => true, 'preview' => $preview]);
    }

    // Delete a row from the preview session
    public function deletePreviewRow(Request $request, Event $event)
    {
        $derivedStatus = $this->deriveStatus(
            $event->status,
            $event->start_datetime ? Carbon::parse($event->start_datetime) : null,
            $event->end_datetime ? Carbon::parse($event->end_datetime) : null,
            Carbon::now()
        );

        if ($derivedStatus !== self::STATUS_COMPLETED) {
            return response()->json(['ok' => false, 'message' => 'Import preview deleting is only allowed when event is COMPLETED.'], 422);
        }

        $previewKey = $this->previewSessionKey($event);
        $preview = session($previewKey);
        if (!$preview) {
            return response()->json(['ok' => false, 'message' => 'No preview found. Upload a CSV first.'], 422);
        }

        $data = $request->validate([
            'row' => ['required', 'integer', 'min:2'],
            'bucket' => ['nullable', 'in:valid,invalid,duplicate,already_imported'],
        ]);

        $rowNumber = (int)$data['row'];
        $bucket = $data['bucket'] ?? null;

        $buckets = $bucket ? [$bucket] : ['valid', 'invalid', 'duplicate', 'already_imported'];

        $removed = false;
        foreach ($buckets as $b) {
            $rows = $preview[$b] ?? [];
            $new = [];
            foreach ($rows as $r) {
                if ((int)($r['row'] ?? 0) === $rowNumber) {
                    $removed = true;
                    continue;
                }
                $new[] = $r;
            }
            $preview[$b] = $new;
            if ($removed) break;
        }

        if (!$removed) {
            return response()->json(['ok' => false, 'message' => 'Row not found in preview.'], 404);
        }

        $preview = $this->rebucketAndRecount($preview, $event);
        session([$previewKey => $preview]);

        $this->logAttendanceFact(
            type: 'attendance.preview_row.deleted',
            action: 'Deleted Attendance Preview',
            event: $event,
            data: [
                'row' => $rowNumber,
                'bucket' => $bucket,
            ]
        );

        return response()->json(['ok' => true, 'preview' => $preview]);
    }

    // Save attendance rows into the database
    public function commit(Request $request, Event $event)
    {
        $derivedStatus = $this->deriveStatus(
            $event->status,
            $event->start_datetime ? Carbon::parse($event->start_datetime) : null,
            $event->end_datetime ? Carbon::parse($event->end_datetime) : null,
            Carbon::now()
        );

        if ($derivedStatus !== self::STATUS_COMPLETED) {
            $this->logAttendanceFact(
                type: 'attendance.commit.blocked',
                action: 'Blocked Attendance Save',
                event: $event,
                data: [
                    'derived_status' => $derivedStatus,
                ]
            );

            return redirect()->route('attendance.import.index', $event->event_id)
                ->with('error', "Attendance import is only allowed when event status is COMPLETED. Current status: " . strtoupper($derivedStatus));
        }

        $previewKey = $this->previewSessionKey($event);
        $preview = session($previewKey);
        if (!$preview) {
            return redirect()->route('attendance.import.index', $event->event_id)
                ->with('error', 'No preview data found. Please upload a CSV first.');
        }

        $adminId = Auth::guard('admin')->id();
        $batch = $preview['batch'];

        $validRows = $preview['valid'] ?? [];
        $invalidRows = $preview['invalid'] ?? [];
        $duplicateRows = $preview['duplicate'] ?? [];
        $alreadyImportedRows = $preview['already_imported'] ?? [];

        if (count($validRows) <= 0) {
            return redirect()->route('attendance.import.index', $event->event_id)
                ->with('error', 'No valid rows to import. Fix invalid rows (or edit them) then try again.');
        }

        $walkInCount = 0;
        $skippedAlreadyImported = 0;

        DB::beginTransaction();
        try {
            foreach ($validRows as $r) {
                $schoolId = $this->cleanSchoolId($r['school_id'] ?? null);
                $email = $this->cleanEmail($r['school_email'] ?? null);
                $isWalkIn = (bool)($r['walk_in'] ?? false);

                $volunteerId = null;

                if (!$isWalkIn) {
                    $v = $this->findVolunteer($schoolId, $email);
                    $volunteerId = $v?->volunteer_id;
                }

                if ($this->attendanceAlreadyExists($event->event_id, $volunteerId, $schoolId, $email, $r['full_name'] ?? null)) {
                    $skippedAlreadyImported++;
                    continue;
                }

                $attendancePayload = [
                    'event_id' => $event->event_id,
                    'event_code' => $r['event_code'] ?? ($event->event_code ?? null),
                    'volunteer_id' => $volunteerId,
                    'full_name' => $r['full_name'] ?? null,
                    'school_id' => $schoolId,
                    'school_email' => $email,
                    'status' => 'present',
                    'source' => 'import',
                    'walk_in' => $isWalkIn,
                    'attendance_time' => $r['submitted_at'] ?? now(),
                    'import_batch' => $batch,
                ];

                if ($isWalkIn) $walkInCount++;

                EventAttendance::create($attendancePayload);

                $hasAnyFeedback = (
                    ($r['rating'] ?? null) !== null ||
                    !empty($r['improve_next_time']) ||
                    !empty($r['issues_encountered']) ||
                    !empty($r['other_comments']) ||
                    !empty($r['feedback_text'])
                );

                if ($hasAnyFeedback) {
                    if (!$this->feedbackAlreadyExists($event->event_id, $volunteerId, $schoolId, $email, $r['full_name'] ?? null)) {
                        EventFeedback::create([
                            'event_id' => $event->event_id,
                            'event_code' => $r['event_code'] ?? ($event->event_code ?? null),
                            'volunteer_id' => $volunteerId,
                            'full_name' => $r['full_name'] ?? null,
                            'school_id' => $schoolId,
                            'school_email' => $email,
                            'rating' => $r['rating'] ?? null,
                            'improve_next_time' => $r['improve_next_time'] ?? null,
                            'issues_encountered' => $r['issues_encountered'] ?? null,
                            'other_comments' => $r['other_comments'] ?? null,
                            'feedback_text' => $r['feedback_text'] ?? null,
                            'submitted_at' => $r['submitted_at'] ?? now(),
                            'import_batch' => $batch,
                        ]);
                    }
                }
            }

            AttendanceImportLog::create([
                'event_id'        => $event->event_id,
                'admin_id'        => $adminId,
                'filename'        => $preview['filename'] ?? 'attendance.csv',
                'import_batch'    => $batch,
                'total_records'   => $preview['total'] ?? 0,
                'valid_count'     => count($validRows),
                'invalid_count'   => count($invalidRows),
                'duplicate_count' => count($duplicateRows),
                'walk_in_count'   => $walkInCount,
                'import_date'     => now(),
                'remarks'         => $this->buildRemarks($preview, $skippedAlreadyImported),
            ]);

            $this->logAttendanceFact(
                type: 'attendance.commit',
                action: 'Saved',
                event: $event,
                data: [
                    'batch'                    => $batch,
                    'counts'                   => $preview['counts'] ?? null,
                    'walk_ins'                 => $walkInCount,
                    'skipped_already_imported' => $skippedAlreadyImported,
                    'filename'                 => $preview['filename'] ?? null,
                ]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->logAttendanceFact(
                type: 'attendance.commit.failed',
                action: 'Failed Attendance Save',
                event: $event,
                data: [
                    'batch' => $batch,
                    'error' => $e->getMessage(),
                ]
            );

            return redirect()->route('attendance.import.index', $event->event_id)
                ->with('error', 'Import failed: ' . $e->getMessage());
        }

        session()->forget($previewKey);

        $msg = 'Attendance import saved successfully.';
        if ($skippedAlreadyImported > 0) {
            $msg .= " Skipped {$skippedAlreadyImported} already-imported attendee(s).";
        }

        return redirect()->route('attendance.import.index', $event->event_id)
            ->with('success', $msg);
    }

    // Status calculation based on stored status + time window
    private function deriveStatus(?string $stored, ?Carbon $start, ?Carbon $end, Carbon $now): string
    {
        $stored = strtolower((string)($stored ?? self::STATUS_PLANNED));

        if (in_array($stored, [self::STATUS_CANCELLED, self::STATUS_COMPLETED], true)) {
            return $stored;
        }

        if (!$start || !$end) return self::STATUS_PLANNED;
        if ($now->lt($start)) return self::STATUS_PLANNED;
        if ($now->betweenIncluded($start, $end)) return self::STATUS_ONGOING;

        return self::STATUS_COMPLETED;
    }

    // CSV parsing
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) throw new \RuntimeException('Failed to open CSV.');

        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            fclose($handle);
            throw new \RuntimeException('CSV has no header row.');
        }

        $header = array_map(fn($h) => trim((string)$h), $rawHeader);
        $normalizedHeader = array_map([$this, 'normalizeHeader'], $header);

        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            if (count(array_filter($data, fn($v) => trim((string)$v) !== '')) === 0) continue;

            $row = [];
            foreach ($normalizedHeader as $i => $key) {
                $row[$key] = isset($data[$i]) ? trim((string)$data[$i]) : null;
            }
            $rows[] = $row;
        }

        fclose($handle);
        return [$rows, $header, $normalizedHeader];
    }

    private function normalizeHeader(string $h): string
    {
        $h = strtolower(trim($h));
        $h = preg_replace('/\s+/', ' ', $h);

        $map = [
            'timestamp' => 'timestamp',
            'event access code' => 'event_code',
            'event code' => 'event_code',
            'full name' => 'full_name',
            'school id' => 'school_id',
            'id' => 'school_id',
            'school email address' => 'school_email',
            'email' => 'school_email',
            'attendance confirmation' => 'attendance_confirmation',
            'course' => 'course',
            'how would you rate this event?' => 'rating',
            'what should we improve on next time?' => 'improve_next_time',
            'any issues you encountered during the event?' => 'issues_encountered',
            'any other comments?' => 'other_comments',
        ];

        return $map[$h] ?? Str::slug($h, '_');
    }

    private function validateRows(Event $event, array $rows): array
    {
        $valid = [];
        $invalid = [];
        $duplicate = [];
        $alreadyImported = [];

        $seenKeys = [];

        foreach ($rows as $i => $r) {
            $errors = [];

            $eventCode = $this->cleanEventCode($r['event_code'] ?? null);
            $fullName  = $this->cleanName($r['full_name'] ?? null);
            $schoolId  = $this->cleanSchoolId($r['school_id'] ?? null);
            $email     = $this->cleanEmail($r['school_email'] ?? null);
            $confirmRaw = strtolower(trim((string)($r['attendance_confirmation'] ?? '')));

            $isPresent = in_array($confirmRaw, ['present'], true);
            $isWalkIn  = in_array($confirmRaw, ['walk-in', 'walkin', 'walk in'], true);

            if (!$eventCode) $errors['event_code'][] = 'Missing Event Access Code.';
            if (!$fullName)  $errors['full_name'][] = 'Missing Full Name.';
            if (!$schoolId)  $errors['school_id'][] = 'Missing School ID.';
            if (!$email)     $errors['school_email'][] = 'Missing School Email Address.';
            if (!$isPresent && !$isWalkIn) $errors['attendance_confirmation'][] = 'Attendance Confirmation must be Present or Walk-in.';

            if ($eventCode && ($event->event_code ?? null) && $eventCode !== $event->event_code) {
                $errors['event_code'][] = 'Event code does not match this event.';
            }

            $rating = null;
            if (isset($r['rating']) && $r['rating'] !== '') {
                $rating = (int)preg_replace('/\D+/', '', (string)$r['rating']);
                if ($rating < 1 || $rating > 5) $errors['rating'][] = 'Rating must be 1-5.';
            }

            $submittedAt = null;
            if (!empty($r['timestamp'])) {
                try { $submittedAt = Carbon::parse($r['timestamp']); } catch (\Throwable $e) {}
            }

            $dedupKey = strtolower(($eventCode ?: '') . '|' . ($schoolId ?: ''));
            if ($eventCode && $schoolId) {
                if (isset($seenKeys[$dedupKey])) {
                    $duplicate[] = [
                        'row' => $i + 2,
                        'data' => $r,
                        'reason' => 'Duplicate in file (same event_code + school_id).',
                    ];
                    continue;
                }
                $seenKeys[$dedupKey] = true;
            }

            $volunteer = null;
            $volunteerId = null;
            $avatarUrl = null;
            $profileUrl = null;
            $courseName = null;

            if (!$isWalkIn && empty($errors)) {
                $volunteer = $this->findVolunteer($schoolId, $email);
                $volunteerId = $volunteer?->volunteer_id;

                if ($volunteer) {
                    $avatarUrl = $this->volunteerAvatarUrl($volunteer);
                    $profileUrl = route('volunteers.show', $volunteer->volunteer_id);
                    $courseName = optional($volunteer->course)->course_name;
                }
            }

            $payload = [
                'row' => $i + 2,
                'event_code' => $eventCode,
                'full_name' => $fullName,
                'school_id' => $schoolId,
                'school_email' => $email,
                'course' => $this->cleanText($r['course'] ?? null) ?? $courseName,
                'attendance_confirmation' => $isWalkIn ? 'Walk-in' : 'Present',
                'walk_in' => $isWalkIn,
                'volunteer_id' => $volunteerId,
                'avatar_url' => $avatarUrl,
                'profile_url' => $profileUrl,
                'rating' => $rating,
                'improve_next_time' => $this->cleanText($r['improve_next_time'] ?? null),
                'issues_encountered' => $this->cleanText($r['issues_encountered'] ?? null),
                'other_comments' => $this->cleanText($r['other_comments'] ?? null),
                'feedback_text' => null,
                'submitted_at' => $submittedAt,
                'errors' => $errors,
            ];

            $payload['feedback_text'] = $this->combineFeedback($payload);

            if (!empty($errors)) {
                $invalid[] = $payload;
                continue;
            }

            $exists = $this->attendanceAlreadyExists($event->event_id, $volunteerId, $schoolId, $email, $fullName);
            if ($exists) {
                $payload['already_imported'] = true;
                $payload['already_imported_reason'] = $volunteerId
                    ? 'Already imported (same event + volunteer_id).'
                    : 'Already imported (same event + school_id/email).';

                $alreadyImported[] = $payload;
                continue;
            }

            $valid[] = $payload;
        }

        return [
            'valid' => $valid,
            'invalid' => $invalid,
            'duplicate' => $duplicate,
            'already_imported' => $alreadyImported,
            'counts' => [
                'total' => count($rows),
                'valid' => count($valid),
                'invalid' => count($invalid),
                'duplicate' => count($duplicate),
                'already_imported' => count($alreadyImported),
            ],
        ];
    }

    private function findVolunteer(?string $schoolId, ?string $email): ?VolunteerProfile
    {
        $schoolId = $schoolId ? trim($schoolId) : null;
        $email = $email ? strtolower(trim($email)) : null;

        $idCandidates = ['id_number', 'school_id'];
        $emailCandidates = ['email', 'school_email'];

        if ($schoolId) {
            foreach ($idCandidates as $col) {
                if (Schema::hasColumn('volunteer_profiles', $col)) {
                    $v = VolunteerProfile::with('course')->where($col, $schoolId)->first();
                    if ($v) return $v;
                }
            }
        }

        if ($email) {
            foreach ($emailCandidates as $col) {
                if (Schema::hasColumn('volunteer_profiles', $col)) {
                    $v = VolunteerProfile::with('course')->where($col, $email)->first();
                    if ($v) return $v;
                }
            }
        }

        return null;
    }

    private function attendanceAlreadyExists(int $eventId, ?int $volunteerId, ?string $schoolId, ?string $email, ?string $fullName): bool
    {
        $q = EventAttendance::query()->where('event_id', $eventId);

        if ($volunteerId) return (clone $q)->where('volunteer_id', $volunteerId)->exists();
        if ($schoolId)    return (clone $q)->where('school_id', $schoolId)->exists();
        if ($email)       return (clone $q)->where('school_email', $email)->exists();
        if ($fullName)    return (clone $q)->where('full_name', $fullName)->exists();

        return false;
    }

    private function feedbackAlreadyExists(int $eventId, ?int $volunteerId, ?string $schoolId, ?string $email, ?string $fullName): bool
    {
        $q = EventFeedback::query()->where('event_id', $eventId);

        if ($volunteerId) return (clone $q)->where('volunteer_id', $volunteerId)->exists();
        if ($schoolId)    return (clone $q)->where('school_id', $schoolId)->exists();
        if ($email)       return (clone $q)->where('school_email', $email)->exists();
        if ($fullName)    return (clone $q)->where('full_name', $fullName)->exists();

        return false;
    }

    private function buildRemarks(array $preview, int $skippedAlreadyImported = 0): string
    {
        $c = $preview['counts'] ?? [];
        $batch = $preview['batch'] ?? '';
        $file = $preview['filename'] ?? '';

        return "Batch: {$batch}\nFile: {$file}\nTotal: " . ($c['total'] ?? 0)
            . "\nValid: " . ($c['valid'] ?? 0)
            . "\nInvalid: " . ($c['invalid'] ?? 0)
            . "\nDuplicates (File): " . ($c['duplicate'] ?? 0)
            . "\nAlready Imported (DB): " . ($c['already_imported'] ?? 0)
            . "\nSkipped on Commit (DB): " . $skippedAlreadyImported;
    }

    private function combineFeedback(array $r): ?string
    {
        $parts = [];
        if (!empty($r['improve_next_time'])) $parts[] = "Improve next time: " . $r['improve_next_time'];
        if (!empty($r['issues_encountered'])) $parts[] = "Issues: " . $r['issues_encountered'];
        if (!empty($r['other_comments'])) $parts[] = "Comments: " . $r['other_comments'];
        return $parts ? implode("\n", $parts) : null;
    }

    private function cleanEventCode(?string $v): ?string
    {
        $v = trim((string)$v);
        return $v !== '' ? $v : null;
    }

    private function cleanName(?string $v): ?string
    {
        $v = trim(preg_replace('/\s+/', ' ', (string)$v));
        return $v !== '' ? $v : null;
    }

    private function cleanSchoolId(?string $v): ?string
    {
        $v = trim((string)$v);
        $v = preg_replace('/[^a-zA-Z0-9\-]/', '', $v);
        return $v !== '' ? $v : null;
    }

    private function cleanEmail(?string $v): ?string
    {
        $v = strtolower(trim((string)$v));
        return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
    }

    private function cleanText(?string $v): ?string
    {
        $v = trim((string)$v);
        return $v !== '' ? $v : null;
    }

    private function recheckEditedPayloadRow(Event $event, array $r): array
    {
        $errors = [];

        $eventCode = $this->cleanEventCode($r['event_code'] ?? null);
        $fullName  = $this->cleanName($r['full_name'] ?? null);
        $schoolId  = $this->cleanSchoolId($r['school_id'] ?? null);
        $email     = $this->cleanEmail($r['school_email'] ?? null);

        $confirm = strtolower(trim((string)($r['attendance_confirmation'] ?? '')));
        $isPresent = in_array($confirm, ['present'], true);
        $isWalkIn = in_array($confirm, ['walk-in', 'walkin', 'walk in'], true);

        if (!$eventCode) $errors['event_code'][] = 'Missing Event Access Code.';
        if (!$fullName)  $errors['full_name'][] = 'Missing Full Name.';
        if (!$schoolId)  $errors['school_id'][] = 'Missing School ID.';
        if (!$email)     $errors['school_email'][] = 'Missing School Email Address.';
        if (!$isPresent && !$isWalkIn) $errors['attendance_confirmation'][] = 'Attendance Confirmation must be Present or Walk-in.';

        if ($eventCode && ($event->event_code ?? null) && $eventCode !== $event->event_code) {
            $errors['event_code'][] = 'Event code does not match this event.';
        }

        $r['event_code'] = $eventCode;
        $r['full_name'] = $fullName;
        $r['school_id'] = $schoolId;
        $r['school_email'] = $email;
        $r['walk_in'] = $isWalkIn;
        $r['attendance_confirmation'] = $isWalkIn ? 'Walk-in' : 'Present';

        $r['volunteer_id'] = null;
        $r['avatar_url'] = null;
        $r['profile_url'] = null;

        if (!$isWalkIn && empty($errors)) {
            $v = $this->findVolunteer($schoolId, $email);
            if ($v) {
                $r['volunteer_id'] = $v->volunteer_id;
                $r['avatar_url'] = $this->volunteerAvatarUrl($v);
                $r['profile_url'] = route('volunteers.show', $v->volunteer_id);
                $r['course'] = $r['course'] ?? optional($v->course)->course_name;
            }
        }

        $r['errors'] = $errors;
        $r['feedback_text'] = $this->combineFeedback($r);

        $r['already_imported'] = false;
        if (empty($errors)) {
            $r['already_imported'] = $this->attendanceAlreadyExists(
                $event->event_id,
                $r['volunteer_id'] ?? null,
                $schoolId,
                $email,
                $fullName
            );
            if ($r['already_imported']) {
                $r['already_imported_reason'] = ($r['volunteer_id'] ?? null)
                    ? 'Already imported (same event + volunteer_id).'
                    : 'Already imported (same event + school_id/email).';
            }
        }

        return $r;
    }

    private function rebucketAndRecount(array $preview, Event $event): array
    {
        $valid = [];
        $invalid = [];
        $alreadyImported = [];

        $pool = array_merge($preview['valid'] ?? [], $preview['invalid'] ?? [], $preview['already_imported'] ?? []);

        foreach ($pool as $r) {
            if (!empty($r['errors'])) {
                $invalid[] = $r;
                continue;
            }

            $exists = $this->attendanceAlreadyExists(
                $event->event_id,
                $r['volunteer_id'] ?? null,
                $this->cleanSchoolId($r['school_id'] ?? null),
                $this->cleanEmail($r['school_email'] ?? null),
                $this->cleanName($r['full_name'] ?? null)
            );

            if ($exists) {
                $r['already_imported'] = true;
                $r['already_imported_reason'] = ($r['volunteer_id'] ?? null)
                    ? 'Already imported (same event + volunteer_id).'
                    : 'Already imported (same event + school_id/email).';
                $alreadyImported[] = $r;
            } else {
                $r['already_imported'] = false;
                unset($r['already_imported_reason']);
                $valid[] = $r;
            }
        }

        $preview['valid'] = array_values($valid);
        $preview['invalid'] = array_values($invalid);
        $preview['already_imported'] = array_values($alreadyImported);

        $preview['counts'] = [
            'total' => ((int)($preview['total'] ?? 0)),
            'valid' => count($preview['valid']),
            'invalid' => count($preview['invalid']),
            'duplicate' => count($preview['duplicate'] ?? []),
            'already_imported' => count($preview['already_imported']),
        ];

        return $preview;
    }
}
