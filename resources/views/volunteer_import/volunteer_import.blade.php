{{-- Declar Page Title --}}
@php
    $pageTitle = 'Volunteer Imports';

    /**
     * ============================================================
     * QUICK NOTES (for future me)
     * - invalidEntries / validEntries are coming from session()
     * - A row is considered "Ready" if:
     *      - no validation errors
     *      - no missing required fields
     *      - schedule is NOT empty
     *      - schedule has NO overlaps
     *      - has a real profile picture (not default)
     * ============================================================
     */

    // ===== helpers for counts (Invalid table pills) =====
    $invalidEntries = $invalidEntries ?? session('invalidEntries', []);
    $validEntries   = $validEntries ?? session('validEntries', []);

    // ----------------------------
    // PHOTO helpers
    // ----------------------------

    // checks if local profile picture is using the system default placeholder
    $isDefaultPhoto = function($entry) {
        $local = trim((string)($entry['profile_picture_local'] ?? ''));
        return $local === 'defaults/default_user.png';
    };

    // photo is "real" if either:
    //  - local path exists and isn't the default placeholder
    //  - or a direct url exists
    $hasRealPhoto = function($entry) use ($isDefaultPhoto) {
        $local = trim((string)($entry['profile_picture_local'] ?? ''));
        $url   = trim((string)($entry['profile_picture'] ?? ''));

        if ($local && !$isDefaultPhoto($entry)) return true;
        if ($url) return true;

        return false;
    };

    // ----------------------------
    // SCHEDULE helpers
    // ----------------------------

    /**
     * Schedule is "empty" if:
     * - blank string
     * - "No class schedule"
     * - or all days are "No Class"
     * - OR schedule text has no digits at all
     */
    $scheduleLooksEmpty = function($entry) {
        $s = preg_replace('/\s+/', ' ', trim((string)($entry['class_schedule'] ?? '')));
        if ($s === '' || strcasecmp($s, 'No class schedule') === 0) return true;

        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

        // assume empty until proven not empty
        $allNoClass = true;

        foreach ($days as $d) {
            if (stripos($s, $d . ':') === false) {
                $allNoClass = false;
                break;
            }
        }

        // simplest reliable check: if schedule contains ANY digit, there is probably a time slot
        if (preg_match('/\d/', $s)) return false;

        return $allNoClass;
    };

    // -------------------------------------------------
    // Overlap checking (per day)
    // -------------------------------------------------

    /**
     * This map is the "allowed slots" we recognize.
     * Values are minutes from midnight, so we can compare overlaps.
     */
    $timeMap = [
        // Morning
        '7:30-8:20'   => ['start' => 450,  'end' => 500],
        '8:00-9:20'   => ['start' => 480,  'end' => 560],
        '8:00-10:50'  => ['start' => 480,  'end' => 650],
        '8:30-9:50'   => ['start' => 510,  'end' => 590],
        '8:30-11:30'  => ['start' => 510,  'end' => 690],
        '9:30-10:50'  => ['start' => 570,  'end' => 650],
        '11:00-12:20' => ['start' => 660,  'end' => 740],

        // Afternoon / Evening
        '12:30-1:50'  => ['start' => 750,  'end' => 830],
        '12:30-2:50'  => ['start' => 750,  'end' => 890],
        '2:00-3:20'   => ['start' => 840,  'end' => 920],
        '2:00-4:50'   => ['start' => 840,  'end' => 1010],
        '3:30-4:50'   => ['start' => 930,  'end' => 1010],
        '5:00-6:20'   => ['start' => 1020, 'end' => 1100],
        '6:30-7:20'   => ['start' => 1110, 'end' => 1160],
        '6:30-8:50'   => ['start' => 1110, 'end' => 1250],
        '7:30-8:50'   => ['start' => 1170, 'end' => 1250],
    ];

    /**
     * normalizeRange:
     * - removes commas/semicolons
     * - converts en dash (–) to normal dash (-)
     * - removes AM/PM words if present (so "7:30-8:20 AM" still works)
     * - returns a key that exists in $timeMap
     */
    $normalizeRange = function (string $str) use ($timeMap): string {
        $str = trim($str);
        if ($str === '') return '';

        // normalize some weird characters / spacing
        $str = str_replace('–', '-', $str); // en dash -> dash
        $str = preg_replace('/[,;]+/', ' ', $str);
        $str = preg_replace('/\b(AM|PM)\b/i', '', $str); // drop AM/PM tokens
        $str = preg_replace('/\s+/', ' ', trim($str));

        // allow "8:30 - 9:50"
        $parts = array_map('trim', explode('-', $str));
        if (count($parts) !== 2) return '';

        // If user somehow saved "8-9:20", force "8:00-9:20"
        $fix = function ($t) {
            return preg_match('/^\d{1,2}$/', $t) ? ($t . ':00') : $t;
        };

        $key = $fix($parts[0]) . '-' . $fix($parts[1]);

        return isset($timeMap[$key]) ? $key : '';
    };

    // returns the start/end array from timeMap or null if not recognized
    $parseRange = function (string $str) use ($timeMap, $normalizeRange): ?array {
        $key = $normalizeRange($str);
        if ($key === '' || !isset($timeMap[$key])) return null;
        return $timeMap[$key];
    };

    /**
     * hasScheduleConflicts:
     * - reads one entry's class_schedule string
     * - per day: collects recognized time ranges
     * - sorts them by start time
     * - checks if any start overlaps previous end
     */
    $hasScheduleConflicts = function ($entry) use ($parseRange): bool {
        $schedule = (string)($entry['class_schedule'] ?? '');
        $schedule = str_replace('–', '-', $schedule);
        $schedule = trim(preg_replace('/\s+/', ' ', $schedule));
        if ($schedule === '') return false;

        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

        foreach ($days as $day) {
            // grab that day's substring safely
            $pattern = '/' . preg_quote($day, '/') . ':(.*?)(?=Monday:|Tuesday:|Wednesday:|Thursday:|Friday:|Saturday:|$)/i';

            if (!preg_match($pattern, $schedule, $m)) continue;

            $raw = trim($m[1]);
            if ($raw === '' || stripos($raw, 'No Class') !== false) continue;

            // remove AM/PM tokens if they exist (prevents token split issues)
            $raw = preg_replace('/\b(AM|PM)\b/i', '', $raw);
            $raw = str_replace('–', '-', $raw);
            $raw = trim(preg_replace('/\s+/', ' ', $raw));

            // split by spaces (expected like: "8:00-9:20 9:30-10:50")
            $slots  = preg_split('/\s+/', $raw);
            $ranges = [];

            foreach ($slots as $slot) {
                $r = $parseRange($slot);
                if ($r) $ranges[] = $r;
            }

            if (count($ranges) < 2) continue;

            usort($ranges, fn($a, $b) => $a['start'] <=> $b['start']);

            $prev = $ranges[0];
            for ($i = 1; $i < count($ranges); $i++) {
                $cur = $ranges[$i];
                if ($cur['start'] < $prev['end']) {
                    return true; // overlap found
                }
                $prev = $cur;
            }
        }

        return false;
    };

    // -------------------------------------------------
    // Count pills: Ready / Needs Edit (Invalid table)
    // -------------------------------------------------
    $invalidReadyCount = 0;
    $invalidNeedsCount = 0;

    foreach ($invalidEntries as $entry) {
        $hasErrors = !empty($entry['errors']) && count($entry['errors']) > 0;

        $missingFields = [];
        foreach ([
            'full_name'      => 'Name',
            'id_number'      => 'School ID',
            'course'         => 'Course',
            'year_level'     => 'Year',
            'batch_year'     => 'Batch Year',
            'contact_number' => 'Contact #',
            'email'          => 'Email',
            'barangay'       => 'Barangay',
            'district'       => 'District',
        ] as $k => $label) {
            if (empty(trim($e[$k] ?? ''))) $missingFields[] = $label;
        }

        $scheduleIsEmpty     = $scheduleLooksEmpty($entry);
        $scheduleHasConflict = $hasScheduleConflicts($entry);
        $scheduleOk          = !$scheduleIsEmpty && !$scheduleHasConflict;

        $hasPic = $hasRealPhoto($entry);

        $isReady = !$hasErrors
            && empty($missingFields)
            && $scheduleOk
            && $hasPic;

        if ($isReady) $invalidReadyCount++;
        else $invalidNeedsCount++;
    }

    /* ===========================================================
       ✅ PATCH ONLY: Ensure these are always defined.
       - $columns is used by both Invalid and Valid tables
       - $truncatedFields is used for tooltip + truncation logic
       Keeping your existing per-row overrides intact.
    ============================================================ */
    $columns = $columns ?? [
        'full_name'         => 'Full Name',
        'id_number'         => 'School ID',
        'course'            => 'Course',
        'year_level'        => 'Year',
        'batch_year'        => 'Batch',
        'contact_number'    => 'Contact #',
        'email'             => 'Email',
        'emergency_contact' => 'Emergency #',
        'fb_messenger'      => 'FB/Messenger',
        'barangay'          => 'Barangay',
        'district'          => 'District',
    ];

    $truncatedFields = $truncatedFields ?? ['full_name','course','email','fb_messenger','barangay','district'];
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Import CSV, Invalid Entries & Import Logs</title>

    {{-- Main CSS for this page --}}
    <link rel="stylesheet" href="{{ asset('assets/volunteer_import/css/volunteer_import.css') }}">

    {{-- Bootstrap / Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('assets/layouts/modals/universal_feedback_modal.css') }}">

    {{-- CSRF + session helper meta --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta id="scrollToInvalid" content="{{ session('scrollToInvalid') ? '1' : '0' }}">
    <meta id="lastUpdatedTable" content="{{ session('last_updated_table') ?? '' }}">
    <meta id="lastUpdatedIndices" content='@json(session("last_updated_indices") ?? [])'>

    <style>
      /* Small header inside the Actions dropdown */
      .entry-actions-header{
        display:flex; align-items:center; justify-content:space-between;
        padding:.35rem .5rem; gap:.5rem;
      }
      .entry-actions-header .title{ font-size:.82rem; font-weight:700; color:#6c757d; letter-spacing:.02em; }
      .entry-actions-header .btn{ padding:.15rem .4rem; line-height:1; border-radius:.5rem; }
    </style>
</head>

<body>
    {{-- Loader & Navbar --}}
    @include('layouts.page_loader')
    @include('layouts.navbar')
    @include('layouts.quicknav_volunteer_imports')
    @include('layouts.back_button')

    
    <div class="scroll-container">

        {{-- =========================================================
            1) IMPORT & VALIDATION SECTION
            - Upload CSV
            - Show invalid preview table
            ========================================================= --}}
        <section id="import-Section-invalid">
            <div class="database-container">
                <main class="database-main">
                    <div class="import-section">

                        {{-- Page Header --}}
                        <div class="import-controls">
                            <h2 class="section-title"><i class="fas fa-tasks"></i> Import & Validation</h2>
                            <div class="action-buttons">
                                <button class="btn btn-outline-secondary import-btn" onclick="openModal('importHandlingModal1')">
                                    <i class="fas fa-book fa-xl"></i> Import & Validation Guide
                                </button>
                            </div>
                        </div>

                        {{-- File Upload + Reset --}}
                        <div class="import-controls d-flex align-items-center gap-2">
                            <form action="{{ route('volunteer.import.preview') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <div class="import-controls">

                                    {{-- Choose File button (input is hidden, button triggers it via JS elsewhere) --}}
                                    <div class="file-upload">
                                        <div class="input-group">
                                            <input type="file" name="csv_file" class="form-control d-none" id="file-upload" accept=".csv" required>
                                            <button class="btn btn-outline-secondary rounded-1" type="button" id="file-upload-button">
                                                <i class="fa-solid fa-file-csv me-2"></i> Choose File
                                            </button>
                                            <span class="file-path" id="file-path">
                                                {{ session('uploaded_file_name', 'No file chosen') }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Import Button --}}
                                    <div class="uploader-info">
                                        <input type="text" class="form-control" value="Uploading as {{ Auth::guard('admin')->user()->username ?? 'Guest' }}" readonly>
                                        @if(!session('csv_imported'))
                                            <button type="submit" class="btn btn-outline-secondary import-btn">
                                                <i class="fa-solid fa-upload"></i> Import
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </form>

                            {{-- Reset Button (clears preview tables from session) --}}
                            @if(session()->exists('validEntries') || session()->exists('invalidEntries'))
                                <button type="button"
                                        class="btn btn-outline-warning import-btn"
                                        id="openResetModal"
                                        title="Clear all imported entries from preview">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Clear Imports
                                </button>
                            @endif
                        </div>

                        <hr class="red-hr">

                        <div class="data-table-container">

                            {{-- Success message (if any) --}}
                            <div class="action-message {{ session('success') ? 'text-success' : 'd-none' }}">
                                <span class="message-text">{!! session('success') !!}</span>
                                <button type="button" class="close-message-btn">&times;</button>
                            </div>

                            {{-- Table Controls / Pills --}}
                            <div class="table-controls mb-0">
                                <div class="table-actions d-flex align-items-center justify-content-center gap-2">
                                    <h3>Invalid Entries</h3>

                                    {{-- Ready / Needs Edit summary pills --}}
                                    <span class="mini-badge ready"
                                          data-bs-toggle="tooltip"
                                          data-bs-title="Entries that are complete and can be validated">
                                        <i class="fa-solid fa-circle-check"></i>
                                        Ready {{ $invalidReadyCount }}
                                    </span>

                                    <span class="mini-badge needs"
                                          data-bs-toggle="tooltip"
                                          data-bs-title="Entries that still need fixes (missing fields / schedule / photo / errors)">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        Needs Edit {{ $invalidNeedsCount }}
                                    </span>

                                    <button type="button" class="toggle-edit-btn btn btn-outline-secondary btn-sm">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit Table
                                    </button>

                                    <div class="hidden-actions">
                                        <button type="button" class="btn btn-outline-primary btn-sm select-all-btn">
                                            <i class="fa-solid fa-check-double"></i> Select All
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm delete-btn"
                                                data-action="{{ route('volunteer.deleteEntries') }}"
                                                data-table-type="invalid">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm copy-btn">
                                            <i class="fa-solid fa-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>

                                {{-- search bar include --}}
                                @include('layouts.search_bar.universal_search_bar', [
                                    'tableId'   => 'invalid-entries-table',
                                    'type'      => 'invalid',
                                    'placeholder' => 'Search invalid entries...'
                                ])
                            </div>

                            {{-- Invalid Table --}}
                            <div class="table-responsive mt-3 table-responsive-safe">
                                <table id="invalid-entries-table" class="table table-hover table-striped volunteer-table align-middle">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" class="select-all-invalid"></th>
                                            <th>#</th>
                                            <th>Status</th>
                                            <th>Full Name</th>
                                            <th>School ID</th>
                                            <th>Course</th>
                                            <th>Year</th>
                                            <th>Batch</th>
                                            <th>Contact #</th>
                                            <th>Email</th>
                                            <th>Emergency #</th>
                                            <th>FB/Messenger</th>
                                            <th>Barangay</th>
                                            <th>District</th>
                                            <th style="min-width: 140px;">Actions</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @if(!empty($invalidEntries) && count($invalidEntries) > 0)
                                            @foreach ($invalidEntries as $index => $entry)
                                                @php
                                                    // Per-row status logic (same rules as the pill counts above)
                                                    $name = trim($entry['full_name'] ?? '') ?: 'Unknown';
                                                    $hasErrors = !empty($entry['errors']) && count($entry['errors']) > 0;

                                                    $missingFields = [];
                                                    foreach ([
                                                        'full_name'      => 'Name',
                                                        'id_number'      => 'School ID',
                                                        'course'         => 'Course',
                                                        'year_level'     => 'Year',
                                                        'batch_year'     => 'Batch Year',
                                                        'contact_number' => 'Contact #',
                                                        'email'          => 'Email',
                                                        'barangay'       => 'Barangay',
                                                        'district'       => 'District',
                                                    ] as $k => $label) {
                                                        if (empty(trim($entry[$k] ?? ''))) $missingFields[] = $label;
                                                    }

                                                    $scheduleIsEmpty     = $scheduleLooksEmpty($entry);
                                                    $scheduleHasConflict = $hasScheduleConflicts($entry);
                                                    $scheduleOk          = !$scheduleIsEmpty && !$scheduleHasConflict;

                                                    $hasPic = $hasRealPhoto($entry);

                                                    $isReady = !$hasErrors
                                                        && empty($missingFields)
                                                        && $scheduleOk
                                                        && $hasPic;

                                                    $rowAccentClass = $isReady ? 'row-ok' : 'row-warn';

                                                    // Build tooltip reason list (shows why it’s not ready)
                                                    $reasons = [];
                                                    if (!empty($missingFields)) $reasons[] = "Missing: " . implode(', ', $missingFields);
                                                    if ($scheduleIsEmpty) $reasons[] = "Empty Schedule (Pending)";
                                                    elseif ($scheduleHasConflict) $reasons[] = "Overlapping schedule(s)";
                                                    if (!$hasPic) $reasons[] = "No Photo (Pending)";
                                                    if ($hasErrors) $reasons[] = "Has validation errors";

                                                    $statusTooltip = $isReady
                                                        ? "Ready — no missing fields, schedule and photo are present."
                                                        : implode(' • ', $reasons);

                                                    $picSrc = !empty($entry['profile_picture_local'])
                                                        ? asset('storage/' . $entry['profile_picture_local'])
                                                        : ($entry['profile_picture'] ?? null);

                                                    $columns = [
                                                        'full_name'        => 'Name',
                                                        'id_number'        => 'School ID',
                                                        'course'           => 'Course',
                                                        'year_level'       => 'Year',
                                                        'batch_year'       => 'Batch',
                                                        'contact_number'   => 'Contact #',
                                                        'email'            => 'Email',
                                                        'emergency_contact'=> 'Emergency #',
                                                        'fb_messenger'     => 'FB/Messenger',
                                                        'barangay'         => 'Barangay',
                                                        'district'         => 'District',
                                                    ];

                                                    $truncatedFields = ['full_name','course','email','fb_messenger','barangay','district'];

                                                    // Indicator flags used for little icons on Actions button
                                                    $missingSchedule  = $scheduleIsEmpty;
                                                    $conflictSchedule = !$scheduleIsEmpty && $scheduleHasConflict;
                                                    $missingPhoto     = !$hasPic;
                                                @endphp


                                                <tr class="{{ $rowAccentClass }}">
                                                    <td><input type="checkbox" name="selected_invalid[]" value="{{ $index }}"></td>

                                                    <td>{{ $index + 1 }}</td>

                                                    <td>
                                                        <span class="status-pill {{ $isReady ? 'status-ok' : 'status-warn' }}"
                                                              data-bs-toggle="tooltip"
                                                              data-bs-title="{{ $statusTooltip }}">
                                                            <i class="fa-solid {{ $isReady ? 'fa-circle-check' : 'fa-triangle-exclamation' }}"></i>
                                                            {{ $isReady ? 'Ready' : 'Needs edits' }}
                                                        </span>
                                                    </td>

                                                    @foreach ($columns as $key => $label)
                                                        @php
                                                            $value = trim($entry[$key] ?? '');
                                                            $isTruncated = in_array($key, $truncatedFields);
                                                            $displayVal = (strlen($value) > 20 && $isTruncated) ? (substr($value, 0, 20).'...') : $value;

                                                            $errs = $entry['errors'][$key] ?? [];
                                                            $errs = is_array($errs) ? $errs : [$errs];

                                                            $tooltip = '';
                                                            if (!empty($errs)) $tooltip = implode('<br>', array_map(fn($e)=>e($e), $errs));
                                                            if (empty($value)) {
                                                                $base = "No $label";
                                                                $tooltip = $tooltip ? ($base . "<br>" . $tooltip) : $base;
                                                            }

                                                            $cellMissing = empty($value);
                                                        @endphp

                                                        @if($key === 'district')
                                                            @php
                                                                $districtId = trim($entry['district'] ?? '');
                                                                $districtName = stripos($districtId, 'district') !== false
                                                                    ? $districtId
                                                                    : ($districtId ? "District " . $districtId : "No District");
                                                            @endphp
                                                            <td class="{{ empty($districtId) ? 'cell-missing' : '' }}"
                                                                @if(!empty($tooltip))
                                                                    data-bs-toggle="tooltip" data-bs-html="true" title="{!! $tooltip !!}"
                                                                @endif>
                                                                {{ $districtName }}
                                                            </td>
                                                        @else
                                                            <td class="{{ $cellMissing ? 'cell-missing' : '' }} {{ $isTruncated ? 'text-truncate' : '' }}"
                                                                @if($isTruncated) style="max-width:150px;" @endif
                                                                @if(!empty($tooltip))
                                                                    data-bs-toggle="tooltip" data-bs-html="true" title="{!! $tooltip !!}"
                                                                @endif>
                                                                {{ $displayVal ?: "No $label" }}
                                                            </td>
                                                        @endif
                                                    @endforeach

                                                    {{-- ACTIONS dropdown --}}
                                                    <td class="actions-cell">
                                                        <div class="dropdown entry-actions">
                                                            <button
                                                                class="btn btn-sm btn-outline-secondary entry-actions-btn"
                                                                type="button"
                                                                data-bs-toggle="dropdown"
                                                                data-bs-auto-close="outside"
                                                                data-bs-display="static"
                                                                aria-expanded="false">
                                                                <i class="fa-solid fa-ellipsis-vertical me-1"></i> Actions

                                                                {{-- Indicator pills (small icons) --}}
                                                                {{-- Schedule pill: warns if empty OR overlapping --}}
                                                                <span class="ind-pill {{ ($missingSchedule || $conflictSchedule) ? 'warn' : 'ok' }}"
                                                                      data-bs-toggle="tooltip"
                                                                      data-bs-title="{{ $missingSchedule
                                                                            ? 'Empty Schedule'
                                                                            : ($conflictSchedule ? 'Schedule has overlaps' : 'Schedule OK') }}"
                                                                      data-action="open-schedule"
                                                                      data-entry-type="invalid"
                                                                      data-entry-index="{{ $index }}"
                                                                      data-schedule-html="{!! e(nl2br(e($entry['class_schedule'] ?? ''))) !!}">
                                                                    <i class="fa-solid fa-calendar-days"></i>
                                                                </span>

                                                                {{-- Photo pill --}}
                                                                <span class="ind-pill {{ $missingPhoto ? 'warn' : 'ok' }}"
                                                                      data-bs-toggle="tooltip"
                                                                      data-bs-title="{{ $missingPhoto ? 'No Photo / Default Photo' : 'Photo OK' }}"
                                                                      data-action="open-photo"
                                                                      data-entry-type="invalid"
                                                                      data-entry-index="{{ $index }}"
                                                                      data-vol-name="{{ addslashes($name) }}"
                                                                      data-picture-src="{{ $picSrc ? addslashes($picSrc) : '' }}">
                                                                    <i class="fa-solid fa-image"></i>
                                                                </span>
                                                            </button>

                                                            <div class="dropdown-menu entry-actions-menu dropdown-menu-end" role="menu">
                                                                {{-- Close dropdown --}}
                                                                <div class="entry-actions-header">
                                                                    <span class="title">ACTIONS</span>
                                                                    <button type="button"
                                                                            class="btn btn-light"
                                                                            data-action="close-dropdown"
                                                                            aria-label="Close">
                                                                        <i class="fa-solid fa-xmark"></i>
                                                                    </button>
                                                                </div>
                                                                <div class="dropdown-divider my-1"></div>

                                                                {{-- Edit (fixed: only ONE onclick) --}}
                                                                <button type="button" class="dropdown-item action-edit"
                                                                        onclick="setLastUsedTable('invalid','{{ $index }}'); openEditVolunteerModal('invalid','{{ $index }}', this)">
                                                                    <i class="fa-solid fa-user-pen"></i>
                                                                    <span>Edit</span>
                                                                </button>

                                                                {{-- View Schedule --}}
                                                                <button type="button" class="dropdown-item action-schedule"
                                                                        onclick="openScheduleModal(`{!! nl2br(e($entry['class_schedule'] ?? '')) !!}`, 'invalid', '{{ $index }}')">
                                                                    <i class="fa-solid fa-calendar-days"></i>
                                                                    <span>Schedule</span>
                                                                    @if($missingSchedule)
                                                                        <span class="ms-auto tag-warn">Empty</span>
                                                                    @elseif($conflictSchedule)
                                                                        <span class="ms-auto tag-warn">Overlap</span>
                                                                    @endif
                                                                </button>

                                                                {{-- View Photo --}}
                                                                <button type="button" class="dropdown-item action-photo"
                                                                        data-entry-index="{{ $index }}"
                                                                        data-entry-type="invalid"
                                                                        data-vol-name="{{ addslashes($name) }}"
                                                                        data-picture-src="{{ $picSrc ? addslashes($picSrc) : '' }}"
                                                                        onclick="openImageModalFromButton(this)">
                                                                    <i class="fa-solid fa-image"></i>
                                                                    <span>Photo</span>
                                                                    @if($missingPhoto)
                                                                        <span class="ms-auto tag-warn">Missing</span>
                                                                    @endif
                                                                </button>

                                                                <div class="dropdown-divider my-1"></div>

                                                                {{-- Transfer invalid -> valid --}}
                                                                @if($isReady)
                                                                    <button type="button"
                                                                            class="dropdown-item action-transfer pill-transfer"
                                                                            data-index="{{ $index }}"
                                                                            onclick="submitMoveToValid(this)">
                                                                        <i class="fa-solid fa-arrow-right"></i>
                                                                        <span>Transfer to Verified</span>
                                                                    </button>
                                                                @else
                                                                    <button type="button"
                                                                            class="dropdown-item action-transfer disabled"
                                                                            tabindex="-1"
                                                                            aria-disabled="true"
                                                                            data-bs-toggle="tooltip"
                                                                            data-bs-title="Cannot move invalid to valid — fix missing fields / schedule / photo first.">
                                                                        <i class="fa-solid fa-arrow-right"></i>
                                                                        <span>Transfer to Verified</span>
                                                                    </button>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="14" class="text-center text-muted py-4">
                                                    <i class="fa-solid fa-file-import fa-lg me-2"></i>No invalid entries yet.
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            {{-- Bulk move button --}}
                            <div class="submit-section">
                                <button type="button"
                                        class="btn btn-danger submit-database"
                                        id="openMoveModalBtn"
                                        data-bs-toggle="tooltip"
                                        title="Move selected ready entries to verified entries">
                                    Move Selected to Verified
                                </button>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </section>

        {{-- =========================================================
            2) VERIFIED ENTRIES SECTION
            - entries ready to be submitted to DB
            ========================================================= --}}
        <section id="import-Section-valid">
            <div class="database-container">
                <main class="database-main">
                    <div class="import-section">

                        <div class="import-header d-flex align-items-center justify-content-between mb-2">
                            <div class="import-controls">
                                <h2 class="section-title">
                                    <i class="fas fa-user-check"></i> Verified Entries
                                </h2>
                                <div class="action-buttons">
                                    <button class="btn btn-outline-secondary import-btn"
                                            onclick="closeModal('importHandlingModal1'); openModal('importHandlingModal2');">
                                        <i class="fas fa-book fa-xl"></i> Valid Entries Guide
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr class="red-hr">

                        <form action="{{ route('volunteer.import.validateSave') }}" method="POST">
                            @csrf

                            <div class="data-table-container">
                                <div class="action-message {{ session('success') ? 'text-success' : 'd-none' }}">
                                    <span class="message-text">{!! session('success') !!}</span>
                                    <button type="button" class="close-message-btn">&times;</button>
                                </div>

                                <div class="table-controls mb-0">
                                    <div class="table-actions d-flex align-items-center justify-content-center gap-2">
                                        <h3>Valid Entries</h3>

                                        <button type="button" class="toggle-edit-btn btn btn-outline-secondary btn-sm">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit Table
                                        </button>

                                        <div class="hidden-actions">
                                            <button type="button" class="btn btn-outline-primary btn-sm select-all-btn">
                                                <i class="fa-solid fa-check-double"></i> Select All
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm delete-btn"
                                                    data-action="{{ route('volunteer.deleteEntries') }}"
                                                    data-table-type="valid">
                                                <i class="fa-solid fa-trash-can"></i> Delete
                                            </button>
                                            <button type="button" class="btn btn-outline-success btn-sm copy-btn">
                                                <i class="fa-solid fa-copy"></i> Copy
                                            </button>
                                        </div>
                                    </div>

                                    @include('layouts.search_bar.universal_search_bar', [
                                        'tableId'   => 'valid-entries-table',
                                        'type'      => 'valid',
                                        'placeholder' => 'Search valid entries...'
                                    ])
                                </div>

                                <div class="table-responsive mt-3 table-responsive-safe">
                                    <table id="valid-entries-table" class="table table-hover table-striped volunteer-table align-middle">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" class="select-all-valid"></th>
                                                <th>#</th>
                                                <th>Status</th>
                                                <th>Full Name</th>
                                                <th>School ID</th>
                                                <th>Course</th>
                                                <th>Year</th>
                                                <th>Batch</th>
                                                <th>Contact #</th>
                                                <th>Email</th>
                                                <th>Emergency #</th>
                                                <th>FB/Messenger</th>
                                                <th>Barangay</th>
                                                <th>District</th>
                                                <th style="min-width: 140px;">Actions</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @if(!empty($validEntries) && count($validEntries) > 0)
                                                @foreach ($validEntries as $index => $entry)
                                                    @php
                                                        // Even if it's in "Valid", we still compute flags so UI indicators are consistent
                                                        $name = trim($entry['full_name'] ?? '') ?: 'Unknown';

                                                        $missingFields = [];
                                                        foreach ([
                                                            'full_name'      => 'Name',
                                                            'id_number'      => 'School ID',
                                                            'course'         => 'Course',
                                                            'year_level'     => 'Year',
                                                            'batch_year'     => 'Batch Year',
                                                            'contact_number' => 'Contact #',
                                                            'email'          => 'Email',
                                                            'barangay'       => 'Barangay',
                                                            'district'       => 'District',
                                                        ] as $k => $label) {
                                                            if (empty(trim($entry[$k] ?? ''))) $missingFields[] = $label;
                                                        }

                                                        $fieldsOk = empty($missingFields);

                                                        $scheduleIsEmpty     = $scheduleLooksEmpty($entry);
                                                        $scheduleHasConflict = $hasScheduleConflicts($entry);
                                                        $scheduleOk          = !$scheduleIsEmpty && !$scheduleHasConflict;

                                                        $hasPic = $hasRealPhoto($entry);

                                                    // ✅ PATCH ONLY: define image source in VALID loop too
                                                    $picSrc = !empty($entry['profile_picture_local'])
                                                        ? asset('storage/' . $entry['profile_picture_local'])
                                                        : ($entry['profile_picture'] ?? null);

                                                        $columns = [
                                                            'full_name'        => 'Name',
                                                            'id_number'        => 'School ID',
                                                            'course'           => 'Course',
                                                            'year_level'       => 'Year',
                                                            'batch_year'       => 'Batch',
                                                            'contact_number'   => 'Contact #',
                                                            'email'            => 'Email',
                                                            'emergency_contact'=> 'Emergency #',
                                                            'fb_messenger'     => 'FB/Messenger',
                                                            'barangay'         => 'Barangay',
                                                            'district'         => 'District',
                                                        ];

                                                        $truncatedFields = ['full_name','course','email','fb_messenger','barangay','district'];

                                                        $missingSchedule  = $scheduleIsEmpty;
                                                        $conflictSchedule = !$scheduleIsEmpty && $scheduleHasConflict;
                                                        $missingPhoto     = !$hasPic;
                                                    @endphp


                                                    <tr class="valid-entry {{ $rowAccentClass }}">
                                                        <td>
                                                            <input type="checkbox" name="selected_valid[]" value="{{ $index }}"
                                                                   data-id-number="{{ $entry['id_number'] ?? '' }}">
                                                        </td>

                                                        <td>{{ $index + 1 }}</td>

                                                        <td>
                                                            <span class="status-pill status-ok"
                                                                  data-bs-toggle="tooltip"
                                                                  data-bs-title="Verified entry">
                                                                <i class="fa-solid fa-circle-check"></i>
                                                                Verified
                                                            </span>
                                                        </td>

                                                        @foreach ($columns as $key => $label)
                                                            @php
                                                                $value = trim($entry[$key] ?? '');
                                                                $isTruncated = in_array($key, $truncatedFields);
                                                                $displayVal = (strlen($value) > 20 && $isTruncated) ? (substr($value, 0, 20).'...') : $value;
                                                            @endphp

                                                            @if($key === 'district')
                                                                @php
                                                                    $districtId = trim($entry['district'] ?? '');
                                                                    $districtName = stripos($districtId, 'district') !== false
                                                                        ? $districtId
                                                                        : ($districtId ? "District " . $districtId : "No District");
                                                                @endphp
                                                                <td>{{ $districtName }}</td>
                                                            @else
                                                                <td class="{{ $isTruncated ? 'text-truncate' : '' }}"
                                                                    @if($isTruncated) style="max-width:150px;" @endif
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="{{ $value ?: 'No '.$label }}">
                                                                    {{ $displayVal ?: "No $label" }}
                                                                </td>
                                                            @endif
                                                        @endforeach

                                                        {{-- ACTIONS dropdown --}}
                                                        <td class="actions-cell">
                                                            <div class="dropdown entry-actions">
                                                                <button
                                                                    class="btn btn-sm btn-outline-secondary entry-actions-btn"
                                                                    type="button"
                                                                    data-bs-toggle="dropdown"
                                                                    data-bs-auto-close="outside"
                                                                    data-bs-display="static"
                                                                    aria-expanded="false">
                                                                    <i class="fa-solid fa-ellipsis-vertical me-1"></i> Actions

                                                                    {{-- schedule indicator (fixed: ONLY ONE schedule pill) --}}
                                                                    <span class="ind-pill {{ ($missingSchedule || $conflictSchedule) ? 'warn' : 'ok' }}"
                                                                        data-bs-toggle="tooltip"
                                                                        data-bs-title="{{ $missingSchedule
                                                                                            ? 'Empty Schedule'
                                                                                            : ($conflictSchedule ? 'Schedule has overlaps' : 'Schedule OK') }}"
                                                                        data-action="open-schedule"
                                                                        data-entry-type="valid"
                                                                        data-entry-index="{{ $index }}"
                                                                        data-schedule-html="{!! e(nl2br(e($entry['class_schedule'] ?? ''))) !!}">
                                                                        <i class="fa-solid fa-calendar-days"></i>
                                                                    </span>

                                                                    {{-- photo indicator --}}
                                                                    <span class="ind-pill {{ $missingPhoto ? 'warn' : 'ok' }}"
                                                                        data-bs-toggle="tooltip"
                                                                        data-bs-title="{{ $missingPhoto ? 'No Photo / Default Photo' : 'Photo OK' }}"
                                                                        data-action="open-photo"
                                                                        data-entry-type="valid"
                                                                        data-entry-index="{{ $index }}"
                                                                        data-vol-name="{{ addslashes($name) }}"
                                                                        data-picture-src="{{ $picSrc ? addslashes($picSrc) : '' }}">
                                                                        <i class="fa-solid fa-image"></i>
                                                                    </span>
                                                                </button>

                                                                <div class="dropdown-menu entry-actions-menu dropdown-menu-end" role="menu">
                                                                    <div class="entry-actions-header">
                                                                        <span class="title">ACTIONS</span>
                                                                        <button type="button"
                                                                                class="btn btn-light"
                                                                                data-action="close-dropdown"
                                                                                aria-label="Close">
                                                                            <i class="fa-solid fa-xmark"></i>
                                                                        </button>
                                                                    </div>
                                                                    <div class="dropdown-divider my-1"></div>

                                                                    {{-- Edit: IMPORTANT: valid entry should call valid --}}
                                                                    <button type="button" class="dropdown-item action-edit"
                                                                        onclick="setLastUsedTable('valid','{{ $index }}'); openEditVolunteerModal('valid','{{ $index }}', this)">
                                                                        <i class="fa-solid fa-user-pen"></i>
                                                                        <span>Edit</span>
                                                                    </button>

                                                                    <button type="button" class="dropdown-item action-schedule"
                                                                            onclick="openScheduleModal(`{!! nl2br(e($entry['class_schedule'] ?? '')) !!}`, 'valid', '{{ $index }}')">
                                                                        <i class="fa-solid fa-calendar-days"></i>
                                                                        <span>Schedule</span>
                                                                        @if($missingSchedule)
                                                                            <span class="ms-auto tag-warn">Empty</span>
                                                                        @elseif($conflictSchedule)
                                                                            <span class="ms-auto tag-warn">Overlap</span>
                                                                        @endif
                                                                    </button>

                                                                    <button type="button" class="dropdown-item action-photo"
                                                                            data-entry-index="{{ $index }}"
                                                                            data-entry-type="valid"
                                                                            data-vol-name="{{ addslashes($name) }}"
                                                                            data-picture-src="{{ $picSrc ? addslashes($picSrc) : '' }}"
                                                                            onclick="openImageModalFromButton(this)">
                                                                        <i class="fa-solid fa-image"></i>
                                                                        <span>Photo</span>
                                                                        @if($missingPhoto)
                                                                            <span class="ms-auto tag-warn">Missing</span>
                                                                        @endif
                                                                    </button>

                                                                    <div class="dropdown-divider my-1"></div>

                                                                    {{-- move valid -> invalid (single) --}}
                                                                    <button type="button" class="dropdown-item action-transfer"
                                                                            data-bs-toggle="tooltip" data-bs-title="Transfer back to Invalid"
                                                                            onclick="moveValidToInvalid('{{ $index }}')">
                                                                        <i class="fa-solid fa-arrow-left"></i>
                                                                        <span>Transfer to Invalid</span>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="14" class="text-center text-muted py-4">
                                                        <i class="fa-solid fa-check-circle fa-lg me-2"></i>No verified entries yet.
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                                <div class="submit-section">
                                    @php $hasValidEntries = count($validEntries) > 0; @endphp
                                    @if($hasValidEntries)
                                        <button type="button" class="btn btn-danger submit-database" id="openSubmitModalBtn"
                                                data-bs-toggle="tooltip"
                                                title="Submit all verified entries to the database">
                                            <i class="fa-solid fa-database"></i> Submit
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </form>

                    </div>
                </main>
            </div>
        </section>

        {{-- =========================================================
            3) IMPORT LOGS SECTION
            - shows history of uploaded files + counts
            ========================================================= --}}
        <section id="importlog-Section">
            <div class="database-container">
                <main class="database-main">
                    <div class="import-section">

                        <div class="import-controls mb-3">
                            <h2 class="section-title"><i class="fas fa-history"></i> Import Logs</h2>
                        </div>

                        <hr class="red-hr">

                        <div class="data-table-container">
                            <div class="action-message {{ session('success') ? 'text-success' : 'd-none' }}">
                                <span class="message-text">{!! session('success') !!}</span>
                                <button type="button" class="close-message-btn">&times;</button>
                            </div>

                            <div class="table-controls mb-0">
                                <div class="table-actions d-flex align-items-center justify-content-center gap-2">
                                    <h3>Import History</h3>

                                    <button type="button" class="toggle-edit-btn btn btn-outline-secondary btn-sm">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit Table
                                    </button>

                                    <div class="hidden-actions">
                                        <button type="button" class="btn btn-outline-primary btn-sm select-all-btn">
                                            <i class="fa-solid fa-check-double"></i> Select All
                                        </button>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-sm delete-btn"
                                                data-action="{{ route('volunteer.deleteEntries') }}"
                                                data-table-type="logs">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>
                                        <button type="button" class="btn btn-outline-success btn-sm copy-btn">
                                            <i class="fa-solid fa-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>

                                @include('layouts.search_bar.universal_search_bar', [
                                    'tableId'   => 'import-logs-table',
                                    'type'      => 'import_logs',
                                    'placeholder' => 'Search import logs...'
                                ])
                            </div>

                            <div class="table-responsive mt-3">
                                <table id="import-logs-table" class="table table-hover table-striped volunteer-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th><input type="checkbox" class="select-all-checkbox"></th>
                                            <th>#</th>
                                            <th>File Name</th>
                                            <th>Uploaded By</th>
                                            <th>Uploaded At</th>
                                            <th>Total Records</th>
                                            <th>Valid</th>
                                            <th>Invalid</th>
                                            <th>Duplicate</th>
                                            <th>Status</th>
                                            <th style="min-width: 300px;">Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($importLogs as $log)
                                            @php
                                                $status = strtolower($log->status);
                                                $statusClass = match($status) {
                                                    'pending'   => 'bg-primary',
                                                    'completed' => 'bg-success',
                                                    'failed'    => 'bg-danger',
                                                    'partial'   => 'bg-warning text-dark',
                                                    'cancelled' => 'bg-secondary',
                                                    'reset'     => 'bg-purple text-white',
                                                    'abandoned' => 'bg-dark text-warning',
                                                    default     => 'bg-dark'
                                                };
                                            @endphp

                                            <tr class="align-middle">
                                                <td><input type="checkbox" name="selected_logs[]" value="{{ $log->import_id }}"></td>
                                                <td data-value="{{ $log->import_id }}">{{ $log->import_id }}</td>

                                                <td class="text-truncate" style="max-width: 220px;"
                                                    data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="{{ $log->file_name }}"
                                                    data-value="{{ $log->file_name }}">
                                                    {{ $log->file_name }}
                                                </td>

                                                <td data-value="{{ $log->admin->name ?? $log->admin->username ?? 'Unknown' }}">
                                                    {{ $log->admin->name ?? $log->admin->username ?? 'Unknown' }}
                                                </td>

                                                <td data-value="{{ optional($log->import_date ?? $log->created_at)->format('Y-m-d H:i:s') }}">
                                                    {{ optional($log->import_date ?? $log->created_at)->format('M d, Y h:i A') ?? '-' }}
                                                </td>

                                                <td data-value="{{ $log->total_records }}">{{ $log->total_records }}</td>
                                                <td data-value="{{ $log->valid_count }}"><span class="badge bg-success">{{ $log->valid_count }}</span></td>
                                                <td data-value="{{ $log->invalid_count }}"><span class="badge bg-danger">{{ $log->invalid_count }}</span></td>
                                                <td data-value="{{ $log->duplicate_count }}"><span class="badge bg-warning text-dark">{{ $log->duplicate_count }}</span></td>

                                                <td data-value="{{ $status }}">
                                                    <span class="badge {{ $statusClass }}">
                                                        {{ ucfirst($status) }}
                                                    </span>
                                                </td>

                                                <td style="white-space: pre-line; padding: 0.75rem; min-width: 300px;">
                                                    {{ $log->remarks ?? '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="11" class="text-center text-muted py-4">
                                                    <i class="fa-solid fa-folder-open fa-lg me-2"></i>
                                                    No import logs found.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </main>
            </div>
        </section>

        {{-- Hidden Global Delete Form --}}
        <form id="globalDeleteForm" method="POST" style="display:none;">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
        </form>

        {{-- Hidden form for moving invalid -> valid (single + bulk) --}}
        <form id="moveToVerifiedForm" action="{{ route('volunteer.import.moveInvalidToValid') }}" method="POST" style="display:none;">
            @csrf
        </form>
    </div>

    {{-- Modals --}}
    @include('layouts.modals.guides.volunteer_import.import_guide_modal')
    @include('layouts.modals.guides.volunteer_import.valid_entries_modal')

    @include('layouts.modals.submit.volunteer_import.reset_import_modal')
    @include('layouts.modals.submit.volunteer_import.edit_volunteer_modal')
    @include('layouts.modals.submit.volunteer_import.file_upload_modal')
    @include('layouts.modals.submit.volunteer_import.delete_message_modal')
    @include('layouts.modals.submit.volunteer_import.transfer_invalid_entries_modal')
    @include('layouts.modals.submit.volunteer_import.submit_valid_entries_modal')

    @include('layouts.modals.submit.volunteer_import.view_schedule_modal')
    @include('layouts.modals.submit.volunteer_import.view_profile_picture_modal')

    @include('layouts.modals.universal_feedback_modal')

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="{{ asset('assets/volunteer_import/js/scroll_to_last_table_used.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/modals/css/modal.css') }}">
    <script src="{{ asset('assets/modals/js/modal.js') }}"></script>
    <script src="{{ asset('assets/volunteer_import/js/table_actions.js') }}"></script>
    
<script src="{{ asset('assets/layouts/modals/universal_feedback_modal.js') }}"></script>

    {{-- =========================================================
        Dropdown pop-out + tooltips (fixed positioning)
        - This moves the dropdown menu to <body> so it won't be clipped by table overflow.
        - Also re-inits tooltips for dynamic content.
       ========================================================= --}}
<script>
  // Rebuild tooltips so they don’t glitch inside table/portal
  function initBootstrapTooltips(root = document) {
    const els = [].slice.call(root.querySelectorAll('[data-bs-toggle="tooltip"]'));
    els.forEach(el => {
      const existing = bootstrap.Tooltip.getInstance(el);
      if (existing) existing.dispose();
      new bootstrap.Tooltip(el, {
        trigger: 'hover focus',
        container: 'body',
        html: el.getAttribute('data-bs-html') === 'true',
        boundary: 'window'
      });
    });
  }

  // Used only for positioning / reopen behavior (not global state)
  let lastDropdownToggle = null;
  let lastDropdownMenu = null;
  let reopenAfterModal = false;

  function clamp(n, min, max){ return Math.max(min, Math.min(max, n)); }

  // Move dropdown menu to body (prevents clipping in responsive table)
  function portalMenuToBody(toggleBtn){
    const wrap = toggleBtn?.closest?.('.entry-actions');
    const menu = wrap?.querySelector?.('.entry-actions-menu');
    if (!wrap || !menu) return null;
    if (menu.dataset.portaled === '1') return menu;

    const ph = document.createElement('span');
    ph.className = 'entry-actions-placeholder';
    wrap.appendChild(ph);

    menu.dataset.portaled = '1';
    menu.dataset.placeholderId = (crypto?.randomUUID?.() || ('ph_' + Math.random().toString(16).slice(2)));
    ph.dataset.placeholderId = menu.dataset.placeholderId;

    document.body.appendChild(menu);
    toggleBtn.dataset.placeholderId = menu.dataset.placeholderId;

    return menu;
  }

  // Put menu back where it came from when dropdown closes
  function restoreMenuFromBody(toggleBtn){
      const wrap = toggleBtn?.closest?.('.entry-actions');
      if (!wrap) return;

      const pid = toggleBtn?.dataset?.placeholderId;
      if (!pid) return;

    const menu =
      document.querySelector(`.entry-actions-menu[data-portaled="1"][data-placeholder-id="${pid}"]`)
      || document.querySelector(`.entry-actions-menu[data-portaled="1"]`);

    const ph = wrap.querySelector(`.entry-actions-placeholder[data-placeholder-id="${pid}"]`);
    if (!menu || !ph) return;

    wrap.appendChild(menu);
    menu.removeAttribute('data-portaled');
    menu.style.position = '';
    menu.style.left = '';
    menu.style.top = '';
    menu.style.zIndex = '';
    ph.remove();
  }

  // Position dropdown menu so it stays inside viewport
  function positionMenuFixedOnce(toggleBtn) {
    const pid = toggleBtn?.dataset?.placeholderId;
    const menu = pid
      ? document.querySelector(`.entry-actions-menu.show[data-placeholder-id="${pid}"]`)
      : null;

    const fallback = toggleBtn?.closest?.('.entry-actions')?.querySelector?.('.entry-actions-menu');
    const m = menu || fallback;
    if (!toggleBtn || !m) return;
    if (!m.classList.contains('show')) return;

    const btnRect = toggleBtn.getBoundingClientRect();
    const menuRect = m.getBoundingClientRect();
    const margin = 10;
    const vw = window.innerWidth;
    const vh = window.innerHeight;

    let left = btnRect.right - menuRect.width;
    left = clamp(left, margin, vw - menuRect.width - margin);

    let top = btnRect.bottom + 8;
    if (top + menuRect.height > vh - margin) {
      top = btnRect.top - menuRect.height - 8;
    }
    top = clamp(top, margin, vh - menuRect.height - margin);

    m.style.position = 'fixed';
    m.style.left = left + 'px';
    m.style.top = top + 'px';
    m.style.zIndex = '99999';

    lastDropdownToggle = toggleBtn;
    lastDropdownMenu = m;
  }

  // Close only the dropdown of this button
  function closeOneEntryDropdown(toggleBtn){
    if (!toggleBtn) return;
    const inst = bootstrap.Dropdown.getInstance(toggleBtn)
      || bootstrap.Dropdown.getOrCreateInstance(toggleBtn, { autoClose: 'outside' });
    inst.hide();
  }
  window.closeOneEntryDropdown = closeOneEntryDropdown;

  // Close all dropdowns (useful on scroll / outside click / modal open)
  function closeAllEntryDropdowns() {
    document.querySelectorAll('.entry-actions .entry-actions-btn[aria-expanded="true"]').forEach(btn => {
      const inst = bootstrap.Dropdown.getInstance(btn);
      if (inst) inst.hide();
    });
    lastDropdownToggle = null;
    lastDropdownMenu = null;
  }
  window.closeAllEntryDropdowns = closeAllEntryDropdowns;

  // ====== SCROLL CLOSE (FIXED) ======
  // throttle via rAF so it doesn't spam on fast scroll
  let rafScroll = 0;

  function isToggleOffscreen(btn){
    if (!btn) return true;
    const r = btn.getBoundingClientRect();
    return (
      r.bottom < 0 ||
      r.top > window.innerHeight ||
      r.right < 0 ||
      r.left > window.innerWidth
    );
  }

  function closeIfToggleOffscreenThrottled(){
    if (!lastDropdownToggle) return;
    if (rafScroll) return;
    rafScroll = requestAnimationFrame(() => {
      rafScroll = 0;
      if (!lastDropdownToggle) return;
      if (isToggleOffscreen(lastDropdownToggle)) closeAllEntryDropdowns();
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initBootstrapTooltips();

    document.addEventListener('shown.bs.dropdown', function (e) {
      const toggleBtn = e.relatedTarget || e.target?.querySelector?.('.entry-actions-btn');
      if (!toggleBtn) return;

      const menu = portalMenuToBody(toggleBtn);
      if (menu) menu.dataset.placeholderId = toggleBtn.dataset.placeholderId;

      positionMenuFixedOnce(toggleBtn);
      initBootstrapTooltips();
    });

      // When dropdown opens: portal it + position it
      document.addEventListener('shown.bs.dropdown', function (e) {
          const toggleBtn = e.relatedTarget || e.target?.querySelector?.('.entry-actions-btn');
          if (!toggleBtn) return;

    window.addEventListener('resize', () => {
      if (lastDropdownToggle && lastDropdownMenu?.classList.contains('show')) {
        positionMenuFixedOnce(lastDropdownToggle);
      }
    });

    // ✅ 1) still keep window scroll (works if body scrolls)
    window.addEventListener('scroll', closeIfToggleOffscreenThrottled, { passive: true });

    // ✅ 2) add explicit listener for your real scroll container
    const scroller = document.querySelector('.scroll-container');
    if (scroller) {
      scroller.addEventListener('scroll', closeIfToggleOffscreenThrottled, { passive: true });
    }

    // ✅ 3) MOST IMPORTANT: capture ANY scroll from ANY overflow container (scroll doesn't bubble)
    document.addEventListener('scroll', closeIfToggleOffscreenThrottled, { passive: true, capture: true });

    // click outside -> close all
    document.addEventListener('click', (e) => {
      const insideMenu    = e.target.closest('.entry-actions-menu');
      const insideTrigger = e.target.closest('.entry-actions-btn');
      const insideWrap    = e.target.closest('.entry-actions');
      if (!insideMenu && !insideTrigger && !insideWrap) {
        closeAllEntryDropdowns();
      }
    });

    // close dropdowns before any modal opens
    document.querySelectorAll('.modal').forEach(modalEl => {
      modalEl.addEventListener('show.bs.modal', () => {
        reopenAfterModal = !!(lastDropdownMenu && lastDropdownMenu.classList.contains('show'));
        closeAllEntryDropdowns();
      });

      // When dropdown closes: restore it back
      document.addEventListener('hidden.bs.dropdown', function (e) {
          const toggleBtn = e.relatedTarget || e.target?.querySelector?.('.entry-actions-btn');
          if (!toggleBtn) return;
          restoreMenuFromBody(toggleBtn);
      });

      // Reposition if viewport changes
      window.addEventListener('resize', () => {
          if (lastDropdownToggle && lastDropdownMenu?.classList.contains('show')) {
              positionMenuFixedOnce(lastDropdownToggle);
          }
      });

      // Close all Actions dropdowns whenever page scrolls
      window.addEventListener('scroll', () => {
          closeAllEntryDropdowns();
      }, { passive: true });

      // Close dropdown when clicking outside
      document.addEventListener('click', (e) => {
          const insideMenu    = e.target.closest('.entry-actions-menu');
          const insideTrigger = e.target.closest('.entry-actions-btn');
          const insideWrap    = e.target.closest('.entry-actions');
          if (!insideMenu && !insideTrigger && !insideWrap) {
              closeAllEntryDropdowns();
          }
      });

      // If a modal opens, close dropdowns so they don't float above modal
      document.querySelectorAll('.modal').forEach(modalEl => {
          modalEl.addEventListener('show.bs.modal', () => {
              reopenAfterModal = !!(lastDropdownMenu && lastDropdownMenu.classList.contains('show'));
              closeAllEntryDropdowns();
          });

          modalEl.addEventListener('hidden.bs.modal', () => {
              if (reopenAfterModal && lastDropdownToggle) {
                  const inst = bootstrap.Dropdown.getOrCreateInstance(lastDropdownToggle, { autoClose: 'outside' });
                  inst.show();
              }
              reopenAfterModal = false;
          });
      });
    });
  });

  // Close button inside dropdown (X)
  document.addEventListener('click', function (e) {
      const btn = e.target.closest('[data-action="close-dropdown"]');
      if (!btn) return;

      e.preventDefault();
      e.stopPropagation();

      const menu = btn.closest('.entry-actions-menu');
      const toggle = document.querySelector(`.entry-actions-btn[data-placeholder-id="${menu?.dataset?.placeholderId || ''}"]`)
                || btn.closest('.entry-actions')?.querySelector('.entry-actions-btn');

      if (toggle) closeOneEntryDropdown(toggle);
  }, true);

  // Mini icon click => opens schedule/photo modals without fighting the dropdown portal
  document.addEventListener('click', function (e) {
    const pill = e.target.closest('.ind-pill[data-action]');
    if (!pill) return;

    e.preventDefault();
    e.stopPropagation();

    try {
      const toggle = pill.closest('.entry-actions')?.querySelector('.entry-actions-btn');
      if (toggle) closeOneEntryDropdown(toggle);

      const action = pill.dataset.action;

        if (action === 'open-schedule') {
          const html = pill.dataset.scheduleHtml || '';
          const type = pill.dataset.entryType || '';
          const idx  = pill.dataset.entryIndex || '';
          if (typeof window.openScheduleModal === 'function') {
              window.openScheduleModal(html, type, idx);
          }
          return;
        }
        return;
      }

      if (action === 'open-photo') {
        if (typeof window.openImageModalFromButton === 'function') {
          window.openImageModalFromButton(pill);
        }
        return;
      }
    } catch (err) {
      console.error('Mini icon modal open failed:', err);
    }
  }, true);
</script>


</body>
</html>
