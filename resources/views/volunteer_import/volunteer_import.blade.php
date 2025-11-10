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
    <link rel="stylesheet" href="{{ asset('css/Reusable-Searchbar+Filter.css') }}">

    {{-- Bootstrap & Font Awesome --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    {{-- Loader & Navbar --}}
    @include('layouts.page_loader')
    @include('layouts.navbar')

    <div class="scroll-container">

        {{-- 1.IMPORT & VALIDATION --}}

        <section id="import-Section">
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
                        
                        {{-- File Upload + Reset on same row --}}
                        <div class="import-controls d-flex align-items-center gap-2">
                           {{-- CSV Upload Form --}}
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
                           {{-- Reset / Clear All Import Previews Button --}}
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

                        <div class="action-message {{ session('success') ? 'text-success' : 'd-none' }}">
                            <span class="message-text">{!! session('success') !!}</span>
                            <button type="button" class="close-message-btn">&times;</button>
                        </div>



                        {{-- Data Table --}}
                        <form action="{{ route('volunteer.import.moveInvalidToValid') }}" method="POST">
                            @csrf
                            <div class="data-table-container" >
                                <div class="table-controls mb-0" >
                                   
                                    <div class="table-actions d-flex align-items-center justify-content-center gap-2">
                                        <h3>Invalid Entries</h3>

                                        {{-- Edit Table Toggle --}}
                                        <button type="button" class="toggle-edit-btn btn btn-outline-secondary btn-sm">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit Table
                                        </button>

                                        {{-- Hidden Actions --}}
                                        <div class="hidden-actions">
                                            <!-- Select All -->
                                            <button type="button" class="btn btn-outline-primary btn-sm select-all-btn">
                                                <i class="fa-solid fa-check-double"></i> Select All
                                            </button>

                                            <!-- Delete Selected -->
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm delete-btn"
                                                    data-action="{{ route('volunteer.deleteEntries') }}"
                                                    data-table-type="invalid">
                                                <i class="fa-solid fa-trash-can"></i> Delete
                                            </button>

                                            <!-- Copy Selected -->
                                            <button type="button" class="btn btn-outline-success btn-sm copy-btn">
                                                <i class="fa-solid fa-copy"></i> Copy
                                            </button>
                                        </div>
                                    </div>
                                </div>

                               <div class="table-responsive mt-3">
                                    <table id="invalid-entries-table" class="table table-hover volunteer-table">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" class="select-all-invalid"></th>
                                                <th>#</th> <!-- Row number -->
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
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @if(!empty($invalidEntries) && count($invalidEntries) > 0)
                                                @foreach ($invalidEntries as $index => $entry)
                                                    <tr>
                                                        <td>
                                                            <input type="checkbox" name="selected_invalid[]" value="{{ $index }}">
                                                        </td>
                                                        <td>{{ $index + 1 }}</td>

                                                            @foreach ([
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
                                                            ] as $key => $label)
                                                                @php
                                                                    $value = $entry[$key] ?? '';
                                                                    $tooltip = '';

                                                                    if (isset($entry['errors'][$key]) && count((array)$entry['errors'][$key]) > 0) {
                                                                        $errorsArray = is_array($entry['errors'][$key]) ? $entry['errors'][$key] : [$entry['errors'][$key]];

                                                                        // Human-readable messages
                                                                        $readableErrors = [];
                                                                        foreach ($errorsArray as $err) {
                                                                            if ($key == 'full_name') $readableErrors[] = 'Invalid Name (letters, spaces, and punctuation only)';
                                                                            elseif ($key == 'id_number') $readableErrors[] = 'Invalid School ID (6-7 digits)';
                                                                            elseif ($key == 'year_level') $readableErrors[] = 'Invalid Year (must be 1-6)';
                                                                            elseif ($key == 'contact_number') $readableErrors[] = 'Invalid Contact Number (09XXXXXXXXX or +639XXXXXXXXX)';
                                                                            elseif ($key == 'emergency_contact') $readableErrors[] = 'Invalid Emergency Number (09XXXXXXXXX or +639XXXXXXXXX)';
                                                                            elseif ($key == 'email') $readableErrors[] = 'Invalid Email (must be @gmail.com or @adzu.edu.ph)';
                                                                            elseif ($key == 'fb_messenger') $readableErrors[] = 'Invalid FB/Messenger URL';
                                                                            elseif ($key == 'barangay') $readableErrors[] = 'Invalid Barangay (must be text, max 255 chars)';
                                                                            elseif ($key == 'district') $readableErrors[] = 'Invalid District (must be 1 or 2)';
                                                                            else $readableErrors[] = $err;
                                                                        }

                                                                        if (empty($value)) {
                                                                            array_unshift($readableErrors, "Missing {$label}");
                                                                        }

                                                                        $tooltip = implode('<br>', $readableErrors);
                                                                    }
                                                                @endphp
                                                                <td
                                                                    @if(!empty($tooltip))
                                                                        class="text-danger fw-semibold"
                                                                        data-bs-toggle="tooltip"
                                                                        data-bs-html="true"
                                                                        data-bs-placement="top"
                                                                        title="{!! $tooltip !!}"
                                                                        style="cursor: help; text-decoration: underline dotted;"
                                                                    @endif
                                                                >
                                                                    {{ empty($value) ? 'No '.$label : $value }}
                                                                </td>
                                                            @endforeach
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
                                                    <td colspan="13" class="text-center text-muted py-4">
                                                        <i class="fa-solid fa-file-import fa-lg me-2"></i>
                                                        <span>No invalid entries yet.</span>
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Initialize Bootstrap tooltips --}}
                                <script>
                                    document.addEventListener('DOMContentLoaded', function () {
                                        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                                        tooltipTriggerList.map(function (tooltipTriggerEl) {
                                            return new bootstrap.Tooltip(tooltipTriggerEl);
                                        });
                                    });
                                </script>


                                <div class="submit-section">
                                    @if(count($invalidEntries) > 0)
                                        <button type="button" class="btn btn-danger submit-database" id="openMoveModalBtn">
                                            Move to All Invalid Entries
                                        </button>
                                    @else
                                        <button type="button" class="btn btn-danger submit-database" disabled>
                                            Move to All Invalid Entries
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </form>
                    </div>
                </main>
            </div>
        </section>

        {{-- 2.Submit Valid Entries to DB --}}
        <section id="import-Section">
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

                        <div class="action-message {{ session('success') ? 'text-success' : 'd-none' }}">
                            <span class="message-text">{!! session('success') !!}</span>
                            <button type="button" class="close-message-btn">&times;</button>
                        </div>


                        {{-- Data Table --}}
                        <form action="{{ route('volunteer.import.validateSave') }}" method="POST">
                            @csrf
                            <div class="data-table-container">
                                <div class="table-controls mb-0">
                                    <div class="table-actions d-flex align-items-center justify-content-center gap-2">
                                        <h3>Valid Entries</h3>

                                        {{-- Edit Table Toggle --}}
                                        <button type="button" class="toggle-edit-btn btn btn-outline-secondary btn-sm">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit Table
                                        </button>

                                        {{-- Hidden Actions --}}
                                        <div class="hidden-actions">
                                            <!-- Select All -->
                                            <button type="button" class="btn btn-outline-primary btn-sm select-all-btn">
                                                <i class="fa-solid fa-check-double"></i> Select All
                                            </button>

                                            <!-- Delete Selected -->
                                            <button type="button" 
                                                    class="btn btn-outline-danger btn-sm delete-btn"
                                                    data-action="{{ route('volunteer.deleteEntries') }}"
                                                    data-table-type="valid">
                                                <i class="fa-solid fa-trash-can"></i> Delete
                                            </button>

                                            <!-- Copy Selected -->
                                            <button type="button" class="btn btn-outline-success btn-sm copy-btn">
                                                <i class="fa-solid fa-copy"></i> Copy
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive mt-3">
                                    <table id="valid-entries-table" class="table table-hover volunteer-table">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" class="select-all-invalid"></th>
                                                <th>#</th> <!-- Row number -->
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
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
    @if(!empty($validEntries) && count($validEntries) > 0)
        @foreach ($validEntries as $index => $entry)
            <tr>
                <td><input type="checkbox" name="selected_valid[]" value="{{ $index }}"></td>
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
                    ];
                @endphp
                @foreach ($columns as $key => $label)
                    <td>
                        @if(empty($entry[$key]))
                            <p class="text-danger fw-semibold">No {{ $label }}</p>
                        @else
                            {{ $entry[$key] }}
                        @endif
                    </td>
                @endforeach
                <td>
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            onclick="setLastUsedTable('valid', '{{ $index }}'); openEditVolunteerModal('valid', '{{ $index }}')">
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
            <td colspan="13" class="text-center text-muted py-4">
                <i class="fa-solid fa-check-circle fa-lg me-2"></i>
                <span>No verified entries yet.</span>
            </td>
        </tr>
    @endif
</tbody>

                                    </table>
                                </div>


                               <div class="submit-section">
                                    @if(count($validEntries) > 0)
                                        <button type="button" class="btn btn-danger submit-database" id="openSubmitModalBtn">
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

        {{-- 3.IMPORT LOGS --}}
        <section id="importlog-Section">
            <div class="database-container">
                <main class="database-main"> 
                    <div class="import-section">
                        {{-- Header --}}
                        <div class="import-controls">
                            <h2 class="section-title"><i class="fas fa-history"></i> Import Logs</h2>
                        </div>

                        <div class="action-message {{ session('success') ? 'text-success' : 'd-none' }}">
                            <span class="message-text">{!! session('success') !!}</span>
                            <button type="button" class="close-message-btn">&times;</button>
                        </div>



                        <hr class="red-hr">

                        <div class="data-table-container">
                            <div class="table-controls mb-0">
                                <div class="table-actions d-flex align-items-center justify-content-center gap-2">
                                    <h3>Valid Entries</h3>

                                    {{-- Edit Table Toggle --}}
                                    <button type="button" class="toggle-edit-btn btn btn-outline-secondary btn-sm">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit Table
                                    </button>
                                    {{-- Hidden Actions --}}
                                    <div class="hidden-actions">
                                        <!-- Select All -->
                                        <button type="button" class="btn btn-outline-primary btn-sm select-all-btn">
                                            <i class="fa-solid fa-check-double"></i> Select All
                                        </button>

                                        <!-- Delete Selected -->
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm delete-btn"
                                                data-action="{{ route('volunteer.deleteEntries') }}"
                                                data-table-type="logs">
                                            <i class="fa-solid fa-trash-can"></i> Delete
                                        </button>

                                        <!-- Copy Selected -->
                                        <button type="button" class="btn btn-outline-success btn-sm copy-btn">
                                            <i class="fa-solid fa-copy"></i> Copy
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="table-responsive mt-3">
                                <table id="import-logs-table" class="table table-hover volunteer-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th><input type="checkbox" class="select-all-checkbox"></th>
                                            <th>#</th> <!-- Row number -->
                                            <th>File Name</th>
                                            <th>Uploaded By</th>
                                            <th>Uploaded At</th>
                                            <th>Total Records</th>
                                            <th>Valid</th>
                                            <th>Invalid</th>
                                            <th>Duplicate</th>
                                            <th>Status</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($importLogs as $log)
                                            <tr>
                                                <td><input type="checkbox" name="selected_logs[]" value="{{ $log->import_id }}"></td>
                                                <td>{{ $log->import_id }}</td>
                                                <td>{{ $log->file_name }}</td>
                                                <td>{{ $log->admin->name ?? $log->admin->username ?? 'Unknown' }}</td>
                                                <td>{{ optional($log->import_date ?? $log->created_at)->format('M d, Y h:i A') ?? '-' }}</td>
                                                <td>{{ $log->total_records }}</td>
                                                <td>{{ $log->valid_count }}</td>
                                                <td>{{ $log->invalid_count }}</td>
                                                <td>{{ $log->duplicate_count }}</td>
                                                <td>{{ $log->status }}</td>
                                                <td>{{ $log->remarks ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="12" class="text-center text-muted py-4">
                                                    <i class="fa-solid fa-folder-open fa-lg me-2"></i>
                                                    <span>No import logs found.</span>
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

        {{-- Hidden Delete Form (for all tables) --}}
        <form id="globalDeleteForm" method="POST" style="display:none;">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
        </form>
        {{-- Hidden form for moving invalid entries to verified --}}
        <form id="moveToVerifiedForm" action="{{ route('volunteer.import.moveInvalidToValid') }}" method="POST" style="display:none;">
            @csrf
        </form>
        {{-- Hidden form for submission --}}
        <form id="submitVerifiedForm" action="" method="POST" style="display:none;">
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
    
    {{-- Scripts --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/volunteer_import/js/volunteer_import.js') }}"></script>

    {{-- Remember Last Used Section --}}
    <script>
        window.lastUsedTable = { type: null, index: null };

        function setLastUsedTable(type, index) {
            window.lastUsedTable.type = type;
            window.lastUsedTable.index = index;

            // Optional: store in sessionStorage so it persists across reload
            sessionStorage.setItem('lastUsedTable', JSON.stringify({ type, index }));
        }

        // On page load, read from sessionStorage (but do NOT auto-open modal)
        document.addEventListener('DOMContentLoaded', () => {
            const stored = sessionStorage.getItem('lastUsedTable');
            if (stored) {
                window.lastUsedTable = JSON.parse(stored);

                // Scroll to last updated row
                const { type, index } = window.lastUsedTable;
                if (type && index !== null) {
                    const table = document.getElementById(type + '-entries-table');
                    if (table) {
                        const row = table.querySelectorAll('tbody tr')[index];
                        if (row) {
                            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            row.style.backgroundColor = '#fff3cd';
                            setTimeout(() => row.style.backgroundColor = '', 2000);
                        }
                    }
                }
            }
        });
    </script>

        {{-- Select All checkbox --}}
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Target the header checkbox
                const selectAllHeader = document.querySelector('.select-all-invalid');
                if (!selectAllHeader) return;

                // When the header checkbox is clicked
                selectAllHeader.addEventListener('change', () => {
                    const table = selectAllHeader.closest('table');
                    if (!table) return;

                    // Get all body checkboxes in this table
                    const checkboxes = table.querySelectorAll('tbody input[type="checkbox"]');

                    // Set each checkbox to match the header
                    checkboxes.forEach(cb => cb.checked = selectAllHeader.checked);
                });

                // Optional: keep header checkbox in sync when any body checkbox changes
                const table = selectAllHeader.closest('table');
                if (table) {
                    table.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => {
                        cb.addEventListener('change', () => {
                            const allChecked = Array.from(table.querySelectorAll('tbody input[type="checkbox"]'))
                                                    .every(c => c.checked);
                            selectAllHeader.checked = allChecked;
                        });
                    });
                }
            });
        </script>

        <script>
document.addEventListener('DOMContentLoaded', () => {
    const deleteModal = document.getElementById('deleteModal');
    const deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
    const deleteCancelBtn = document.getElementById('deleteCancelBtn');
    let pendingDelete = null;

    // -----------------------------
    // Utility: Show message in a table container
    // -----------------------------
    function showMessage(container, message, type = 'info', persist = false) {
        if (!container) return;
        const msgDiv = container.querySelector('.action-message');
        if (!msgDiv) return;

        const textSpan = msgDiv.querySelector('.message-text');
        textSpan.innerHTML = message;

        // Reset classes
        msgDiv.className = 'action-message';
        if (type === 'success') msgDiv.classList.add('text-success');
        else if (type === 'error') msgDiv.classList.add('text-error');
        else msgDiv.classList.add('text-info');

        msgDiv.classList.remove('d-none');

        // Persist in sessionStorage if needed
        if (persist) {
            const table = container.querySelector('table');
            if (table?.id) {
                sessionStorage.setItem(`lastTableMessage-${table.id}`, JSON.stringify({ message, type }));
            }
        }
    }

    // -----------------------------
    // Restore persistent messages
    // -----------------------------
    document.querySelectorAll('.data-table-container').forEach(container => {
        const table = container.querySelector('table');
        if (!table?.id) return;
        const stored = sessionStorage.getItem(`lastTableMessage-${table.id}`);
        if (stored) {
            const { message, type } = JSON.parse(stored);
            showMessage(container, message, type);
        }
    });

    // -----------------------------
    // Close message button
    // -----------------------------
    document.addEventListener('click', e => {
        if (e.target.classList.contains('close-message-btn')) {
            const msgDiv = e.target.closest('.action-message');
            if (!msgDiv) return;
            msgDiv.classList.add('d-none');

            const container = e.target.closest('.data-table-container');
            const table = container?.querySelector('table');
            if (table?.id) sessionStorage.removeItem(`lastTableMessage-${table.id}`);
        }
    });

    // -----------------------------
    // Toggle Edit Table
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
    // Select All Button
    // -----------------------------
    document.querySelectorAll('.select-all-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.data-table-container');
            const checkboxes = container.querySelectorAll('tbody input[type="checkbox"]');
            if (checkboxes.length === 0) {
                showMessage(container, 'No rows available', 'error');
                return;
            }
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
        });
    });

    // -----------------------------
    // Copy Selected Rows
    // -----------------------------
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.data-table-container');
            const selected = Array.from(container.querySelectorAll('tbody input[type="checkbox"]:checked'));

            if (selected.length === 0) {
                // DO NOT persist this temporary error
                showMessage(container, 'No rows selected', 'error', false);
                return;
            }

            let textToCopy = '';
            selected.forEach(cb => {
                const row = cb.closest('tr');
                const cells = Array.from(row.querySelectorAll('td'));
                textToCopy += cells.slice(1, -1).map(c => c.innerText.trim()).join('\t') + '\n';
            });

            navigator.clipboard.writeText(textToCopy)
                // DO NOT persist the copy success message
                .then(() => showMessage(container, `📋 Copied ${selected.length} row(s)`, 'success', false))
                .catch(err => showMessage(container, `❌ Failed to copy: ${err}`, 'error', false));
        });
    });

    // -----------------------------
    // Delete Selected Rows
    // -----------------------------
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.data-table-container');
            const selected = Array.from(container.querySelectorAll('tbody input[type="checkbox"]:checked'))
                                  .map(cb => cb.value);

            if (selected.length === 0) {
                // DO NOT persist this temporary error
                showMessage(container, 'No rows selected', 'error', false);
                return;
            }

            pendingDelete = {
                action: btn.dataset.action,
                tableType: btn.dataset.tableType,
                selected,
                container
            };
            if (deleteModal) deleteModal.style.display = 'flex';
        });
    });

    // -----------------------------
    // Confirm Delete
    // -----------------------------
    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', () => {
            if (!pendingDelete) return;

            const { action, tableType, selected, container } = pendingDelete;
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

    // -----------------------------
    // Cancel Delete
    // -----------------------------
    if (deleteCancelBtn) {
        deleteCancelBtn.addEventListener('click', () => {
            if (deleteModal) deleteModal.style.display = 'none';
            pendingDelete = null;
        });
    }
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // -----------------------------
    // Table message utilities
    // -----------------------------
    function showTableMessage(tableEl, message, type = 'info', persist = false) {
        if (!tableEl) return;
        const container = tableEl.closest('.data-table-container') || tableEl.parentElement;
        const msgDiv = container.querySelector('.action-message');
        if (!msgDiv) return;

        const textSpan = msgDiv.querySelector('.message-text');
        textSpan.innerHTML = message;

        msgDiv.className = 'action-message'; // reset classes
        msgDiv.classList.add(type); // 'success', 'error', 'info'
        msgDiv.classList.remove('d-none');

        const tableId = tableEl.id || 'default';
        if (persist) {
            sessionStorage.setItem(`lastTableMessage-${tableId}`, JSON.stringify({ message, type }));
        } else {
            sessionStorage.removeItem(`lastTableMessage-${tableId}`);
        }
    }

    function showTemporaryMessage(tableEl, message, type = 'success') {
        showTableMessage(tableEl, message, type, false);
    }

    // Restore table messages
    document.querySelectorAll('table.volunteer-table').forEach(table => {
        const tableId = table.id;
        const stored = sessionStorage.getItem(`lastTableMessage-${tableId}`);
        if (stored) {
            const { message, type } = JSON.parse(stored);
            showTableMessage(table, message, type, true); // Keep the message persistent
        }
    });

    // -----------------------------
    // Section message utilities
    // -----------------------------
    document.querySelectorAll('section').forEach(section => {
        const msgDiv = section.querySelector('.action-message');
        if (!msgDiv) return;

        const textSpan = msgDiv.querySelector('.message-text');
        const sectionId = section.id || section.dataset.sectionId || 'default';

        // Save server message if present
        const serverMessage = textSpan.innerHTML;
        if (serverMessage) {
            sessionStorage.setItem(`persistentMessage-${sectionId}`, serverMessage);
            msgDiv.classList.remove('d-none');
        }

        // Restore persisted message
        const storedMessage = sessionStorage.getItem(`persistentMessage-${sectionId}`);
        if (storedMessage) {
            textSpan.innerHTML = storedMessage;
            msgDiv.classList.remove('d-none');
        }

        // Close button
        const closeBtn = msgDiv.querySelector('.close-message-btn');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                msgDiv.classList.add('d-none');
                sessionStorage.removeItem(`persistentMessage-${sectionId}`);
            });
        }
    });

    // -----------------------------
    // Global message close handler
    // -----------------------------
    document.addEventListener('click', e => {
        if (e.target.classList.contains('close-message-btn')) {
            const msgDiv = e.target.closest('.action-message');
            if (!msgDiv) return;

            msgDiv.classList.add('d-none');
            const container = msgDiv.closest('.data-table-container');
            const table = container?.querySelector('table');
            if (table?.id) {
                sessionStorage.removeItem(`lastTableMessage-${table.id}`);
            }
        }
    });

    // -----------------------------
    // Show Message for No Rows Selected (Persistent)
    // -----------------------------
    function showNoRowsSelectedMessage(container, type = 'error') {
        const message = 'No rows selected';
        showTableMessage(container, message, type, true); // Persist the message
    }

    // -----------------------------
    // Copy Selected Rows
    // -----------------------------
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.data-table-container');
            const selected = Array.from(container.querySelectorAll('tbody input[type="checkbox"]:checked'));

            if (selected.length === 0) {
                // Persist "No rows selected" message
                showNoRowsSelectedMessage(container, 'error');
                return;
            }

            let textToCopy = '';
            selected.forEach(cb => {
                const row = cb.closest('tr');
                const cells = Array.from(row.querySelectorAll('td'));
                textToCopy += cells.slice(1, -1).map(c => c.innerText.trim()).join('\t') + '\n';
            });

            navigator.clipboard.writeText(textToCopy)
                .then(() => showTableMessage(container, `📋 Copied ${selected.length} row(s)`, 'success', false))
                .catch(err => showTableMessage(container, `❌ Failed to copy: ${err}`, 'error', false));
        });
    });

    // -----------------------------
    // Delete Selected Rows
    // -----------------------------
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const container = btn.closest('.data-table-container');
            const selected = Array.from(container.querySelectorAll('tbody input[type="checkbox"]:checked'))
                                  .map(cb => cb.value);

            if (selected.length === 0) {
                // Persist "No rows selected" message
                showNoRowsSelectedMessage(container, 'error');
                return;
            }

            pendingDelete = {
                action: btn.dataset.action,
                tableType: btn.dataset.tableType,
                selected,
                container
            };
            if (deleteModal) deleteModal.style.display = 'flex';
        });
    });

    // -----------------------------
    // Confirm Delete
    // -----------------------------
    if (deleteConfirmBtn) {
        deleteConfirmBtn.addEventListener('click', () => {
            if (!pendingDelete) return;

            const { action, tableType, selected, container } = pendingDelete;
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

    // -----------------------------
    // Cancel Delete
    // -----------------------------
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
