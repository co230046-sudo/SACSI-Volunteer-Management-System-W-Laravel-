{{-- Declar Page Title --}}
@php
    $pageTitle = 'Volunteer Imports';

    // ===== helpers for counts (Invalid table pills) =====
    $invalidEntries = $invalidEntries ?? session('invalidEntries', []);
    $validEntries   = $validEntries ?? session('validEntries', []);

    // ----------------------------
    // Accurate detection helpers
    // ----------------------------
    $isDefaultPhoto = function($entry) {
        $local = trim((string)($entry['profile_picture_local'] ?? ''));
        // your controller uses this as fallback
        return $local === 'defaults/default_user.png';
    };

    $hasRealPhoto = function($entry) use ($isDefaultPhoto) {
        $local = trim((string)($entry['profile_picture_local'] ?? ''));
        $url   = trim((string)($entry['profile_picture'] ?? ''));
        // consider default as missing; either local non-default or a url counts as photo
        if ($local && !$isDefaultPhoto($entry)) return true;
        if ($url) return true;
        return false;
    };

    $scheduleLooksEmpty = function($entry) {
        $s = preg_replace('/\s+/', ' ', trim((string)($entry['class_schedule'] ?? '')));
        if ($s === '' || strcasecmp($s, 'No class schedule') === 0) return true;

        // your normalizeRow builds: "Monday: No Class Tuesday: No Class ..."
        // treat as empty if ALL days are "No Class"
        $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $allNoClass = true;
        foreach ($days as $d) {
            if (stripos($s, $d . ':') === false) { $allNoClass = false; break; }
            if (preg_match('/' . preg_quote($d, '/') . ':\s*[^N].*/i', $s) && preg_match('/\d/', $s)) {
                $allNoClass = false;
                break;
            }
        }

        // simplest + reliable: if schedule contains ANY digit, assume has a slot
        if (preg_match('/\d/', $s)) return false;

        return $allNoClass;
    };

    $invalidReadyCount = 0;
    $invalidNeedsCount = 0;

    foreach ($invalidEntries as $e) {
        $hasErrors = !empty($e['errors']) && count($e['errors']) > 0;

        $missingRequired = false;
        foreach (['full_name','id_number','course','year_level','contact_number','email','barangay','district'] as $requiredField) {
            if (empty(trim($e[$requiredField] ?? ''))) { $missingRequired = true; break; }
        }

        $scheduleOk = !$scheduleLooksEmpty($e);
        $photoOk    = $hasRealPhoto($e);

        $isReady = !$hasErrors && !$missingRequired && $scheduleOk && $photoOk;

        if ($isReady) $invalidReadyCount++;
        else $invalidNeedsCount++;
    }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Import CSV, Invalid Entries & Import Logs</title>

    <link rel="stylesheet" href="{{ asset('assets/volunteer_import/css/volunteer_import.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta id="scrollToInvalid" content="{{ session('scrollToInvalid') ? '1' : '0' }}">
    <meta id="lastUpdatedTable" content="{{ session('last_updated_table') ?? '' }}">
    <meta id="lastUpdatedIndices" content='@json(session("last_updated_indices") ?? [])'>

    <style>
      /* tiny header inside dropdown */
      .entry-actions-header{
        display:flex; align-items:center; justify-content:space-between;
        padding:.35rem .5rem; gap:.5rem;
      }
      .entry-actions-header .title{ font-size:.82rem; font-weight:700; color:#6c757d; letter-spacing:.02em; }
      .entry-actions-header .btn{
        padding:.15rem .4rem; line-height:1; border-radius:.5rem;
      }
    </style>
</head>

<body>
    {{-- Loader & Navbar --}}
    @include('layouts.page_loader')
    @include('layouts.navbar')
    @include('layouts.quicknav_volunteer_imports')
    @include('layouts.back_button')

    <div class="scroll-container">
        {{-- 1. IMPORT & VALIDATION --}}
        <section id="import-Section-invalid">
            <div class="database-container">
                <main class="database-main">
                    <div class="import-section">

                        {{-- Header --}}
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
                                    {{-- Choose File Button + File Path Span --}}
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

                            {{-- Reset Button --}}
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
                            {{-- Action Message --}}
                            <div class="action-message {{ session('success') ? 'text-success' : 'd-none' }}">
                                <span class="message-text">{!! session('success') !!}</span>
                                <button type="button" class="close-message-btn">&times;</button>
                            </div>

                            {{-- Table Controls --}}
                            <div class="table-controls mb-0">
                                <div class="table-actions d-flex align-items-center justify-content-center gap-2">
                                    <h3>Invalid Entries</h3>

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
                                                    $name = trim($entry['full_name'] ?? '') ?: 'Unknown';

                                                    $hasErrors = !empty($entry['errors']) && count($entry['errors']) > 0;

                                                    $missingFields = [];
                                                    foreach ([
                                                        'full_name' => 'Name',
                                                        'id_number' => 'School ID',
                                                        'course' => 'Course',
                                                        'year_level' => 'Year',
                                                        'contact_number' => 'Contact #',
                                                        'email' => 'Email',
                                                        'barangay' => 'Barangay',
                                                        'district' => 'District',
                                                    ] as $k => $label) {
                                                        if (empty(trim($entry[$k] ?? ''))) $missingFields[] = $label;
                                                    }

                                                    $scheduleOk = !$scheduleLooksEmpty($entry);
                                                    $hasPic     = $hasRealPhoto($entry);

                                                    $isReady = !$hasErrors && empty($missingFields) && $scheduleOk && $hasPic;

                                                    $rowAccentClass = $isReady ? 'row-ok' : 'row-warn';

                                                    $reasons = [];
                                                    if (!empty($missingFields)) $reasons[] = "Missing: " . implode(', ', $missingFields);
                                                    if (!$scheduleOk) $reasons[] = "Empty Schedule (Pending)";
                                                    if (!$hasPic) $reasons[] = "No Photo (Pending)";
                                                    if ($hasErrors) $reasons[] = "Has validation errors";

                                                    $statusTooltip = $isReady
                                                        ? "Ready — no missing fields, schedule and photo are present."
                                                        : implode(' • ', $reasons);

                                                    $picSrc = !empty($entry['profile_picture_local'])
                                                        ? asset('storage/' . $entry['profile_picture_local'])
                                                        : ($entry['profile_picture'] ?? null);

                                                    $columns = [
                                                        'full_name' => 'Name',
                                                        'id_number' => 'School ID',
                                                        'course' => 'Course',
                                                        'year_level' => 'Year',
                                                        'contact_number' => 'Contact #',
                                                        'email' => 'Email',
                                                        'emergency_contact' => 'Emergency #',
                                                        'fb_messenger' => 'FB/Messenger',
                                                        'barangay' => 'Barangay',
                                                        'district' => 'District',
                                                    ];
                                                    $truncatedFields = ['full_name','course','email','fb_messenger','barangay','district'];

                                                    // indicators
                                                    $missingSchedule = !$scheduleOk;
                                                    $missingPhoto    = !$hasPic;
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
                                                            $displayVal = strlen($value) > 20 && $isTruncated ? substr($value, 0, 20).'...' : $value;

                                                            $errs = $entry['errors'][$key] ?? [];
                                                            $errs = is_array($errs) ? $errs : [$errs];

                                                            $tooltip = '';
                                                            if (!empty($errs)) {
                                                                $tooltip = implode('<br>', array_map(fn($e)=>e($e), $errs));
                                                            }
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

                                                    {{-- ✅ ACTIONS dropdown --}}
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

                                                                {{-- ✅ NOTE: entry-type MUST be invalid here --}}
                                                                <span class="ind-pill {{ $missingSchedule ? 'warn' : 'ok' }}"
                                                                    data-bs-toggle="tooltip"
                                                                    data-bs-title="{{ $missingSchedule ? 'Empty Schedule' : 'Schedule OK' }}"
                                                                    data-action="open-schedule"
                                                                    data-entry-type="invalid"
                                                                    data-entry-index="{{ $index }}"
                                                                    data-schedule-html="{!! e(nl2br(e($entry['class_schedule'] ?? ''))) !!}">
                                                                    <i class="fa-solid fa-calendar-days"></i>
                                                                </span>

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
                                                                {{-- ✅ CLOSE BUTTON (only closes THIS dropdown) --}}
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

                                                                <button type="button" class="dropdown-item action-edit"
                                                                        onclick="setLastUsedTable('invalid','{{ $index }}'); openEditVolunteerModal('invalid','{{ $index }}')">
                                                                    <i class="fa-solid fa-user-pen"></i>
                                                                    <span>Edit</span>
                                                                </button>

                                                                <button type="button" class="dropdown-item action-schedule"
                                                                        onclick="openScheduleModal(`{!! nl2br(e($entry['class_schedule'] ?? '')) !!}`, 'invalid', '{{ $index }}')">
                                                                    <i class="fa-solid fa-calendar-days"></i>
                                                                    <span>Schedule</span>
                                                                    @if($missingSchedule)
                                                                        <span class="ms-auto tag-warn">Empty</span>
                                                                    @endif
                                                                </button>

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

                                                                {{-- ✅ transfer: invalid -> valid (single) --}}
                                                                @if($isReady)
                                                                    <button type="button"
                                                                            class="dropdown-item action-transfer pill-transfer"
                                                                            data-index="{{ $index }}"
                                                                            onclick="submitMoveToValid(this)">
                                                                        <i class="fa-solid fa-arrow-right"></i>
                                                                        <span>Transfer to Verified</span>
                                                                    </button>
                                                                @else
                                                                    {{-- IMPORTANT: use a BUTTON so it doesn't overlay / steal clicks weirdly --}}
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

                            {{-- Move Selected to Verified --}}
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

        {{-- 2. VERIFIED ENTRIES --}}
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
                                                        $name = trim($entry['full_name'] ?? '') ?: 'Unknown';

                                                        $scheduleOk = !$scheduleLooksEmpty($entry);
                                                        $hasPic     = $hasRealPhoto($entry);

                                                        $picSrc = !empty($entry['profile_picture_local'])
                                                            ? asset('storage/' . $entry['profile_picture_local'])
                                                            : ($entry['profile_picture'] ?? null);

                                                        $columns = [
                                                            'full_name' => 'Name',
                                                            'id_number' => 'School ID',
                                                            'course' => 'Course',
                                                            'year_level' => 'Year',
                                                            'contact_number' => 'Contact #',
                                                            'email' => 'Email',
                                                            'emergency_contact' => 'Emergency #',
                                                            'fb_messenger' => 'FB/Messenger',
                                                            'barangay' => 'Barangay',
                                                            'district' => 'District',
                                                        ];
                                                        $truncatedFields = ['full_name','course','email','fb_messenger','barangay','district'];

                                                        $missingSchedule = !$scheduleOk;
                                                        $missingPhoto    = !$hasPic;
                                                    @endphp

                                                    <tr class="valid-entry row-ok">
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
                                                                $displayVal = strlen($value) > 20 && $isTruncated ? substr($value, 0, 20).'...' : $value;
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

                                                        {{-- ✅ ACTIONS dropdown --}}
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

                                                                    <span class="ind-pill {{ $missingSchedule ? 'warn' : 'ok' }}"
                                                                        data-bs-toggle="tooltip"
                                                                        data-bs-title="{{ $missingSchedule ? 'Empty Schedule' : 'Schedule OK' }}"
                                                                        data-action="open-schedule"
                                                                        data-entry-type="valid"
                                                                        data-entry-index="{{ $index }}"
                                                                        data-schedule-html="{!! e(nl2br(e($entry['class_schedule'] ?? ''))) !!}">
                                                                        <i class="fa-solid fa-calendar-days"></i>
                                                                    </span>

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
                                                                    {{-- ✅ CLOSE BUTTON (only closes THIS dropdown) --}}
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

                                                                    <button type="button" class="dropdown-item action-edit"
                                                                            onclick="setLastUsedTable('valid','{{ $index }}'); openEditVolunteerModal('valid','{{ $index }}')">
                                                                        <i class="fa-solid fa-user-pen"></i>
                                                                        <span>Edit</span>
                                                                    </button>

                                                                    <button type="button" class="dropdown-item action-schedule"
                                                                            onclick="openScheduleModal(`{!! nl2br(e($entry['class_schedule'] ?? '')) !!}`, 'valid', '{{ $index }}')">
                                                                        <i class="fa-solid fa-calendar-days"></i>
                                                                        <span>Schedule</span>
                                                                        @if($missingSchedule)
                                                                            <span class="ms-auto tag-warn">Empty</span>
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

                                                                    {{-- ✅ move valid -> invalid (single) --}}
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

        {{-- 3. IMPORT LOGS --}}
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

    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script src="{{ asset('assets/volunteer_import/js/scroll_to_last_table_used.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('assets/modals/css/modal.css') }}">
    <script src="{{ asset('assets/modals/js/modal.js') }}"></script>
    <script src="{{ asset('assets/volunteer_import/js/table_actions.js') }}"></script>

    {{-- ✅ Dropdown pop-out + tooltips (fixed + portal) --}}
    <script>
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

      // NOTE: keep the "last" refs only for positioning, not for closing everything.
      let lastDropdownToggle = null;
      let lastDropdownMenu = null;
      let reopenAfterModal = false;

      function clamp(n, min, max){ return Math.max(min, Math.min(max, n)); }

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

      function restoreMenuFromBody(toggleBtn){
          const wrap = toggleBtn?.closest?.('.entry-actions');
          if (!wrap) return;
          const pid = toggleBtn?.dataset?.placeholderId;
          if (!pid) return;

          const menu = document.querySelector(`.entry-actions-menu[data-portaled="1"][data-placeholder-id="${pid}"]`)
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

      // ✅ close ONLY ONE dropdown (the one you clicked)
      function closeOneEntryDropdown(toggleBtn){
          const inst = bootstrap.Dropdown.getInstance(toggleBtn) || bootstrap.Dropdown.getOrCreateInstance(toggleBtn, { autoClose: 'outside' });
          inst.hide();
      }
      window.closeOneEntryDropdown = closeOneEntryDropdown;

      // keep this for when you MUST close all (ex: open modal)
      function closeAllEntryDropdowns() {
          document.querySelectorAll('.entry-actions .entry-actions-btn[aria-expanded="true"]').forEach(btn => {
              const inst = bootstrap.Dropdown.getInstance(btn);
              if (inst) inst.hide();
          });
          lastDropdownToggle = null;
          lastDropdownMenu = null;
      }
      window.closeAllEntryDropdowns = closeAllEntryDropdowns;

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

          document.addEventListener('hidden.bs.dropdown', function (e) {
              const toggleBtn = e.relatedTarget || e.target?.querySelector?.('.entry-actions-btn');
              if (!toggleBtn) return;
              restoreMenuFromBody(toggleBtn);
          });

          window.addEventListener('resize', () => {
              if (lastDropdownToggle && lastDropdownMenu?.classList.contains('show')) {
                  positionMenuFixedOnce(lastDropdownToggle);
              }
          });

          // Close dropdowns before any bootstrap modal opens, then restore if needed
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

      // ✅ CLOSE BUTTON INSIDE DROPDOWN: only closes that dropdown
      document.addEventListener('click', function (e) {
          const btn = e.target.closest('[data-action="close-dropdown"]');
          if (!btn) return;
          e.preventDefault();
          e.stopPropagation();
          const menu = btn.closest('.entry-actions-menu');
          const wrap = menu ? document.querySelector(`.entry-actions-menu.show[data-placeholder-id="${menu.dataset.placeholderId}"]`) : null;
          const toggle = document.querySelector(`.entry-actions-btn[data-placeholder-id="${menu?.dataset?.placeholderId || ''}"]`)
                    || btn.closest('.entry-actions')?.querySelector('.entry-actions-btn');
          if (toggle) closeOneEntryDropdown(toggle);
      }, true);

      // ✅ Mini icon click => open schedule/photo modals without fighting dropdown portal
      document.addEventListener('click', function (e) {
        const pill = e.target.closest('.ind-pill[data-action]');
        if (!pill) return;

        e.preventDefault();
        e.stopPropagation();

        try {
          // only close its dropdown, not all
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
