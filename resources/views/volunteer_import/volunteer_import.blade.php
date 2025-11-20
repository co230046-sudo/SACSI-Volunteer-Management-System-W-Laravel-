@php
    $pageTitle = 'Volunteer Imports';
    
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Import CSV, Invalid Entries & Import Logs</title>

    {{-- Styles --}}
    <link rel="stylesheet" href="{{ asset('assets/volunteer_import/css/volunteer_import.css') }}">

    {{-- Bootstrap & Font Awesome --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    {{-- Loader & Navbar --}}
    @include('layouts.page_loader')
    @include('layouts.navbar')

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

                            @if(session()->has('validEntries') || session()->has('invalidEntries'))
                                <button type="button" 
                                        class="btn btn-outline-warning import-btn" 
                                        id="openResetModal"
                                        title="Clear all imported entries from preview">
                                    <i class="fa-solid fa-rotate-left me-1"></i> Clear Imports
                                </button>
                            @endif
                        </div>

                        <hr class="red-hr">

                        {{-- Data Table --}}
                        <form action="{{ route('volunteer.import.moveInvalidToValid') }}" method="POST">
                            @csrf
                            <div class="data-table-container">

                                {{-- Action Message --}}
                                <div class="action-message {{ session('success') ? 'text-success' : 'd-none' }}">
                                    <span class="message-text">{!! session('success') !!}</span>
                                    <button type="button" class="close-message-btn">&times;</button>
                                </div>

                                <div class="table-controls mb-0">
                                    <div class="table-actions d-flex align-items-center justify-content-center gap-2">
                                        <h3>Invalid Entries</h3>
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
                                
                                <div class="table-responsive mt-3">
                                    <table id="invalid-entries-table" class="table table-hover table-striped volunteer-table">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" class="select-all-invalid"></th>
                                                <th>#</th>
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
                                                <th>Class Schedule</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(!empty($invalidEntries) && count($invalidEntries) > 0)
                                                @foreach ($invalidEntries as $index => $entry)
                                                    @php
                                                        $hasErrors = isset($entry['errors']) && count($entry['errors']) > 0;
                                                        $missingRequired = false;
                                                        foreach (['full_name','id_number','course','year_level','contact_number','email','barangay','district'] as $requiredField) {
                                                            if (empty($entry[$requiredField])) $missingRequired = true;
                                                        }

                                                        $rowClass = $hasErrors ? 'invalid-row' : ($missingRequired ? 'invalid-row-light' : '');

                                                        $scheduleValue = trim($entry['class_schedule'] ?? '');
                                                        $scheduleValid = !empty($scheduleValue) && strlen($scheduleValue) > 4;

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
                                                            'district' => 'District'
                                                        ];

                                                        $truncatedFields = ['full_name','course','email','fb_messenger','barangay','district'];
                                                    @endphp

                                                    <tr class="{{ $rowClass }}">
                                                        <td><input type="checkbox" name="selected_invalid[]" value="{{ $index }}"></td>
                                                        <td>{{ $index + 1 }}</td>

                                                        @foreach ($columns as $key => $label)
                                                            @php
                                                                $value = trim($entry[$key] ?? '');
                                                                $isTruncated = in_array($key, $truncatedFields);
                                                                $displayVal = (strlen($value) > 20 && $isTruncated) ? substr($value, 0, 20).'...' : $value;

                                                                $errors = $entry['errors'][$key] ?? [];
                                                                $errors = is_array($errors) ? $errors : [$errors];
                                                                $tooltip = '';

                                                                if (!empty($errors)) {
                                                                    $tooltip = implode('<br>', array_map(fn($e)=>e($e), $errors));
                                                                    if (empty($value)) $tooltip = "Missing $label<br>".$tooltip;
                                                                }

                                                                $tooltipText = $tooltip ?: (!empty($value) ? $value : "No $label");
                                                            @endphp

                                                            {{-- ⭐ FIXED DISTRICT DISPLAY --}}
                                                                @if($key === 'district')
                                                                    @php
                                                                        $districtId = trim($entry['district'] ?? '');

                                                                        // Prevent "District District 1"
                                                                        if (stripos($districtId, 'district') !== false) {
                                                                            $districtName = $districtId;
                                                                        } else {
                                                                            $districtName = "District " . $districtId;
                                                                        }
                                                                    @endphp

                                                                    <td data-value="{{ strtolower($districtName) }}">
                                                                        {{ $districtName }}
                                                                    </td>
                                                                @else

                                                                {{-- ORIGINAL DISPLAY --}}
                                                                <td
                                                                    data-value="{{ $value }}"
                                                                    @if($tooltipText)
                                                                        @if(!empty($errors))
                                                                            class="text-danger fw-semibold invalid-cell"
                                                                        @elseif($isTruncated)
                                                                            class="text-truncate"
                                                                            style="max-width: 150px;"
                                                                        @endif
                                                                        data-bs-toggle="tooltip"
                                                                        data-bs-html="true"
                                                                        title="{!! $tooltipText !!}"
                                                                    @endif
                                                                >
                                                                    {{ $displayVal ?: "No $label" }}
                                                                </td>
                                                            @endif
                                                        @endforeach

                                                        {{-- Schedule --}}
                                                        <td>
                                                            @php
                                                                $buttonText = $scheduleValid ? 'Schedule' : 'No Class Schedule';
                                                                $displaySchedule = $scheduleValid ? $scheduleValue : 'No Class Schedule';
                                                            @endphp

                                                            <button type="button"
                                                                class="btn btn-sm {{ $scheduleValid ? 'btn-success' : 'btn-danger' }}"
                                                                onclick="openScheduleModal(
                                                                    `{!! nl2br(e($displaySchedule)) !!}`,
                                                                    'invalid',
                                                                    '{{ $index }}'
                                                                )">
                                                                {{ $buttonText }}
                                                            </button>
                                                        </td>

                                                        {{-- Actions --}}
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                                    onclick="setLastUsedTable('invalid', '{{ $index }}'); openEditVolunteerModal('invalid', '{{ $index }}')">
                                                                <i class="fa-solid fa-user-edit"></i> Edit
                                                            </button>

                                                            <button type="button" class="btn btn-sm btn-outline-secondary move-btn"
                                                                    onclick="submitMoveToValid(this)">
                                                                <i class="fa-solid fa-arrow-right"></i> Validate
                                                            </button>
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

                                            <tr class="no-search-results d-none">
                                                <td colspan="14" class="text-center text-muted py-4">
                                                    <i class="fa-solid fa-magnifying-glass fa-lg me-2"></i>No results found.
                                                </td>
                                            </tr>
                                            </tbody>
                                    </table>
                                </div>

                                <div class="submit-section">
                                    <button type="button" class="btn btn-danger submit-database" id="openMoveModalBtn"
                                        @if(count($invalidEntries) === 0 || empty($selectedBarangay)) disabled @endif
                                        data-bs-toggle="tooltip"
                                        title="Move all invalid entries to verified entries">
                                        Move to All Invalid Entries
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </main>
            </div>
        </section>

        {{-- 2. Submit Valid Entries to DB --}}
        <section id="import-Section-valid">
    <div class="database-container">
        <main class="database-main">
            <div class="import-section">

                {{-- Header --}}
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

                {{-- ⭐ FORM NOW WRAPS THE ENTIRE TABLE --}}
                <form action="{{ route('volunteer.import.validateSave') }}" method="POST">
                    @csrf

                    <div class="data-table-container">

                        {{-- Action Message --}}
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

                        <div class="table-responsive mt-3">
                            <table id="valid-entries-table" class="table table-hover table-striped volunteer-table">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" class="select-all-valid"></th>
                                        <th>#</th>
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
                                        <th>Class Schedule</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @if(!empty($validEntries) && count($validEntries) > 0)
                                        @foreach ($validEntries as $index => $entry)
                                            <tr class="valid-entry">
                                                <td>
                                                    <input type="checkbox"
                                                        name="selected_valid[]"
                                                        value="{{ $index }}"
                                                        data-id-number="{{ $entry['id_number'] ?? '' }}">
                                                </td>
                                                <td>{{ $index + 1 }}</td>

                                                @php
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
                                                        'class_schedule' => 'Class Schedule',
                                                    ];
                                                    $truncatedFields = ['full_name','course','email','fb_messenger','barangay','district'];
                                                @endphp

                                                @foreach ($columns as $key => $label)
                                                    @php
                                                        $value = trim($entry[$key] ?? '');
                                                        $isTruncated = in_array($key,$truncatedFields);
                                                        $displayVal = (strlen($value) > 20 && $isTruncated)
                                                            ? substr($value,0,20).'...' : $value;
                                                    @endphp

                                                    {{-- ⭐ FIXED DISTRICT DISPLAY --}}
                                                    @if($key === 'district')
                                                        @php
                                                            $districtId = trim($entry['district'] ?? '');

                                                            // Prevent "District District 1"
                                                            if (stripos($districtId, 'district') !== false) {
                                                                $districtName = $districtId;
                                                            } else {
                                                                $districtName = "District " . $districtId;
                                                            }
                                                        @endphp

                                                        <td data-value="{{ strtolower($districtName) }}">
                                                            {{ $districtName }}
                                                        </td>


                                                    {{-- CLASS SCHEDULE --}}
                                                    @elseif($key === 'class_schedule')
                                                        <td data-value="{{ trim($entry['class_schedule'] ?? '') }}">
                                                            @php
                                                                $scheduleValid = !empty(trim($entry['class_schedule'] ?? ''));
                                                                $buttonText = $scheduleValid ? 'Schedule' : 'No Class Schedule';
                                                                $displaySchedule = $scheduleValid ? $entry['class_schedule'] : 'No Class Schedule';
                                                            @endphp

                                                            <button type="button"
                                                                    class="btn btn-sm {{ $scheduleValid ? 'btn-success' : 'btn-danger' }}"
                                                                    onclick="openScheduleModal(
                                                                        `{!! nl2br(e($displaySchedule)) !!}`,
                                                                        'valid',
                                                                        '{{ $index }}'
                                                                    )">
                                                                {{ $buttonText }}
                                                            </button>
                                                        </td>

                                                    {{-- TRUNCATED --}}
                                                    @elseif($isTruncated)
                                                        <td class="text-truncate" style="max-width:150px;"
                                                            data-value="{{ $value }}"
                                                            data-bs-toggle="tooltip"
                                                            title="{{ $value ?: 'No '.$label }}">
                                                            {{ $displayVal ?: "No $label" }}
                                                        </td>

                                                    {{-- NORMAL --}}
                                                    @else
                                                        <td data-value="{{ $value }}">
                                                            {{ $displayVal ?: "No $label" }}
                                                        </td>

                                                    @endif
                                                @endforeach

                                                {{-- ACTION BUTTONS --}}
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                                            onclick="setLastUsedTable('valid','{{ $index }}'); openEditVolunteerModal('valid','{{ $index }}')">
                                                        <i class="fa-solid fa-user-edit"></i> Edit
                                                    </button>

                                                    <button type="button" class="btn btn-sm btn-outline-secondary move-invalid-btn"
                                                            onclick="moveToInvalid('{{ $index }}')">
                                                        <i class="fa-solid fa-arrow-left"></i> Move to Invalid
                                                    </button>
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

                                    <tr class="no-search-results d-none">
                                        <td colspan="14" class="text-center text-muted py-4">
                                            <i class="fa-solid fa-magnifying-glass fa-lg me-2"></i>No results found.
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </div>

                        <div class="submit-section">
                            @php
                                $validEntries = session('validEntries', []);
                                $hasValidEntries = count($validEntries) > 0;
                            @endphp

                            @if($hasValidEntries)
                                <button type="button" class="btn btn-danger submit-database" id="openSubmitModalBtn"
                                        data-bs-toggle="tooltip"
                                        title="Submit all verified entries to the database">
                                    <i class="fa-solid fa-database"></i> Submit
                                </button>
                            @endif
                        </div>

                    </div> {{-- END data-table-container --}}
                </form> {{-- ⭐ FORM NOW ENDS HERE --}}

            </div>
        </main>
    </div>
</section>


        {{-- Bootstrap tooltip initialization --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        </script>

        <style>/* Highlight valid entries */
        .valid-entry {
            background-color: #e0f7e0;  /* Light green */
        }

        /* Shorten FB/Messenger links */
        .text-truncate {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Adjust button color for Schedule */
        .btn-success {
            background-color: #28a745;  /* Green */
            border-color: #28a745;
        }

        .btn-danger {
            background-color: #dc3545;  /* Red */
            border-color: #dc3545;
        }

        .btn-sm {
            font-size: 0.875rem;  /* Smaller button size */
        }

        /* Optional: Tooltip styling (if needed) */
        [data-bs-toggle="tooltip"] {
            cursor: help;
            text-decoration: underline dotted;
        }

        /* Remove underline from table buttons / links */
            .volunteer-table button,
            .volunteer-table a {
                text-decoration: none !important;
            }

            /* Make long text truncate with ellipsis */
            .text-truncate {
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            [data-bs-toggle="tooltip"] {
            cursor: help;
            text-decoration: none;
        }
        </style>

        {{-- 3.IMPORT LOGS --}}
        <section id="importlog-Section">
            <div class="database-container">
                <main class="database-main"> 
                    <div class="import-section">

                        {{-- Header --}}
                        <div class="import-controls mb-3">
                            <h2 class="section-title"><i class="fas fa-history"></i> Import Logs</h2>
                        </div>

                        {{-- Action Message --}}
                        <div class="action-message {{ session('success') ? 'text-success' : 'd-none' }}">
                            <span class="message-text">{!! session('success') !!}</span>
                            <button type="button" class="close-message-btn">&times;</button>
                        </div>

                        <hr class="red-hr">

                        <div class="data-table-container">
                            <div class="table-controls mb-0">
                                <div class="table-actions d-flex align-items-center justify-content-center gap-2">
                                    <h3>Import History</h3>

                                    {{-- Edit Table Toggle --}}
                                    <button type="button" class="toggle-edit-btn btn btn-outline-secondary btn-sm">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit Table
                                    </button>

                                    {{-- Hidden Actions --}}
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
                                            <th style="min-width: 300px;">Remarks</th> <!-- wider column -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($importLogs as $log)
                                            <tr class="align-middle">
                                                <td><input type="checkbox" name="selected_logs[]" value="{{ $log->import_id }}"></td>
                                                <td data-value="{{ $log->import_id }}">{{ $log->import_id }}</td>

                                                {{-- File Name --}}
                                                <td class="text-truncate" style="max-width: 220px;" title="{{ $log->file_name }}"
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

                                                {{-- Color-coded badges --}}
                                                <td data-value="{{ $log->valid_count }}"><span class="badge bg-success">{{ $log->valid_count }}</span></td>
                                                <td data-value="{{ $log->invalid_count }}"><span class="badge bg-danger">{{ $log->invalid_count }}</span></td>
                                                <td data-value="{{ $log->duplicate_count }}"><span class="badge bg-warning text-dark">{{ $log->duplicate_count }}</span></td>

                                                {{-- Status with badges --}}
                                                @php
                                                    $statusClass = match(strtolower($log->status)) {
                                                        'completed' => 'bg-success',
                                                        'failed' => 'bg-danger',
                                                        'partial' => 'bg-warning text-dark',
                                                        default => 'bg-secondary'
                                                    };
                                                @endphp

                                                <td data-value="{{ strtolower($log->status) }}">
                                                    <span class="badge {{ $statusClass }}">{{ ucfirst(strtolower($log->status)) }}</span>
                                                </td>


                                                {{-- Expanded Remarks --}}
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
                                        <tr class="no-search-results d-none">
                                            <td colspan="14" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-magnifying-glass fa-lg me-2"></i>
                                                <span>No results found.</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </main>
            </div>
        </section>

        {{-- Hidden Delete Form (for all tables) --}}
        <form id="globalDeleteForm" method="POST" style="display:none;">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
        </form>
        {{-- Hidden form for moving invalid entries to verified --}}
        <form id="moveToVerifiedForm" action="{{ route('volunteer.import.moveInvalidToValid') }}" method="POST" style="display:none;">
            @csrf
        </form>

    </div>
    
    {{-- Modals --}}
    @include('layouts.modals.guides.volunteer_import.import_guide_modal')
    @include('layouts.modals.guides.volunteer_import.valid_entries_modal')
    @include('layouts.modals.submit.volunteer_import.reset_import_modal')
    @include('layouts.modals.submit.volunteer_import.edit_volunteer_modal')
    @include('layouts.modals.submit.volunteer_import.generic_message_modal')
    @include('layouts.modals.submit.volunteer_import.delete_message_modal')
    @include('layouts.modals.submit.volunteer_import.transfer_invalid_entries_modal')
    @include('layouts.modals.submit.volunteer_import.submit_valid_entries_modal')
    @include('layouts.modals.submit.volunteer_import.view_schedule_modal')
    
    
    
    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/volunteer_import/js/volunteer_import.js') }}"></script>

    {{-- Remember Last Used Section --}}
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const persistKey = 'lastUsedTable';
        const persistenceCount = 2; // Number of reloads to persist

        @if(session('last_updated_table') && session('last_updated_indices'))
            const table = "{{ session('last_updated_table') }}";
            const indices = @json(session('last_updated_indices'));

            // Store in sessionStorage with persistence counter
            sessionStorage.setItem(persistKey, JSON.stringify({ type: table, index: indices, remaining: persistenceCount }));

            // Highlight affected rows immediately
            indices.forEach(i => {
                const tbl = document.getElementById(table + '-entries-table');
                if (tbl) {
                    const row = tbl.querySelectorAll('tbody tr')[i];
                    if (row) {
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        row.style.backgroundColor = '#fff3cd';
                        setTimeout(() => row.style.backgroundColor = '', 2000);
                    }
                }
            });
        @endif

        // Read from sessionStorage for persistence if Laravel flash is missing
        let stored = sessionStorage.getItem(persistKey);
        if(stored){
            try {
                stored = JSON.parse(stored);

                if(stored.remaining > 0){
                    stored.remaining--;
                    sessionStorage.setItem(persistKey, JSON.stringify(stored));

                    const tbl = document.getElementById(stored.type + '-entries-table');
                    if(tbl && stored.index){
                        stored.index.forEach(i => {
                            const row = tbl.querySelectorAll('tbody tr')[i];
                            if(row){
                                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                row.style.transition = "background-color 0.5s";
                                row.style.backgroundColor = '#fff3cd';
                                setTimeout(() => row.style.backgroundColor = '', 2000);
                            }
                        });
                    }
                } else {
                    sessionStorage.removeItem(persistKey);
                }
            } catch(e){
                sessionStorage.removeItem(persistKey);
            }
        }

        // Initialize global JS helper
        window.lastUsedTable = stored || { type: null, index: null };
    });

    // Helper to set last used table manually (for JS actions like edit modal)
    function setLastUsedTable(type, index){
        window.lastUsedTable.type = type;
        window.lastUsedTable.index = index;
        sessionStorage.setItem('lastUsedTable', JSON.stringify({ type, index, remaining: 2 }));
    }
    </script>



    <script>
document.addEventListener('DOMContentLoaded', () => {
    const deleteModal = document.getElementById('deleteModal');
    const deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
    const deleteCancelBtn = document.getElementById('deleteCancelBtn');
    let pendingDelete = null;

    // -----------------------------
    // Show message for a specific section
    // -----------------------------
    function showMessage(sectionId, message, type = 'info', autoHide = true) {
        const section = document.getElementById(sectionId);
        if (!section) return;
        const msgDiv = section.querySelector('.action-message');
        if (!msgDiv) return;

        const textSpan = msgDiv.querySelector('.message-text');
        textSpan.innerHTML = message;

        msgDiv.className = 'action-message'; // reset classes
        if(type === 'success') msgDiv.classList.add('text-success');
        else if(type === 'error') msgDiv.classList.add('text-error');
        else msgDiv.classList.add('text-info');

        msgDiv.classList.remove('d-none');

        // Auto-hide after 6 seconds
        if(autoHide) {
            setTimeout(() => {
                msgDiv.classList.add('d-none');
            }, 6000);
        }
    }

    // -----------------------------
    // Handle close button click for all messages
    // -----------------------------
    document.querySelectorAll('.close-message-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const msgDiv = btn.closest('.action-message');
            if(msgDiv) msgDiv.classList.add('d-none');
        });
    });

    // -----------------------------
    // Toggle edit mode
    // -----------------------------
    document.querySelectorAll('.toggle-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.data-table-container');
            container.classList.toggle('edit-mode');
            const hiddenActions = btn.closest('.table-actions').querySelector('.hidden-actions');
            hiddenActions?.classList.toggle('visible');
            btn.classList.toggle('active');
        });
    });

    // -----------------------------
    // Select All checkbox headers
    // -----------------------------
    ['invalid','valid'].forEach(type => {
        const headerCb = document.querySelector(`.select-all-${type}`);
        if (!headerCb) return;
        const table = document.getElementById(`${type}-entries-table`);
        if (!table) return;

        headerCb.addEventListener('change', () => {
            table.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => cb.checked = headerCb.checked);
        });

        table.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', () => {
                const allChecked = Array.from(table.querySelectorAll('tbody input[type="checkbox"]')).every(c => c.checked);
                headerCb.checked = allChecked;
            });
        });
    });

    // -----------------------------
    // Select All toggle button
    // -----------------------------
    document.querySelectorAll('.select-all-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.data-table-container');
            const checkboxes = container.querySelectorAll('tbody input[type="checkbox"]');
            if (!checkboxes.length) {
                showMessage(container.closest('section').id, 'No rows available', 'error');
                return;
            }
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
        });
    });

    // -----------------------------
    // Copy selected rows
    // -----------------------------
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.data-table-container');
            const sectionId = container.closest('section').id;
            const selected = Array.from(container.querySelectorAll('tbody input[type="checkbox"]:checked'));
            if (!selected.length) {
                showMessage(sectionId, 'No rows selected', 'error');
                return;
            }

            let text = '';
            selected.forEach(cb => {
                const row = cb.closest('tr');
                const cells = Array.from(row.querySelectorAll('td'));
                text += cells.slice(1, -1).map(c => c.innerText.trim()).join('\t') + '\n';
            });

            navigator.clipboard.writeText(text)
                .then(() => {
                    showMessage(sectionId, `✔ Copied ${selected.length} row(s)`, 'success');

                    // Temporary button feedback
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '✔ Copied';
                    setTimeout(() => btn.innerHTML = originalText, 1500);
                })
                .catch(err => showMessage(sectionId, `❌ Failed to copy: ${err}`, 'error'));
        });
    });

    // -----------------------------
    // Delete selected rows
    // -----------------------------
    function showNoRowsSelected(sectionId) { showMessage(sectionId, 'No rows selected', 'error'); }

    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.data-table-container');
            const sectionId = container.closest('section').id;
            const selected = Array.from(container.querySelectorAll('tbody input[type="checkbox"]:checked')).map(cb => cb.value);
            if (!selected.length) { showNoRowsSelected(sectionId); return; }

            pendingDelete = { action: btn.dataset.action, tableType: btn.dataset.tableType, selected, container };
            if (deleteModal) deleteModal.style.display = 'flex';
        });
    });

    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', () => {
            if (!pendingDelete) return;
            const { action, tableType, selected } = pendingDelete;
            const form = document.getElementById('globalDeleteForm');
            form.action = action;
            form.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}">
                              <input type="hidden" name="table_type" value="${tableType}">`;
            selected.forEach(val => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected[]';
                input.value = val;
                form.appendChild(input);
            });
            form.submit();
            if (deleteModal) deleteModal.style.display = 'none';
            pendingDelete = null;
        });
    }

    if (deleteCancelBtn) {
        deleteCancelBtn.addEventListener('click', () => {
            if (deleteModal) deleteModal.style.display = 'none';
            pendingDelete = null;
        });
    }
});
</script>


</body>
</html>
