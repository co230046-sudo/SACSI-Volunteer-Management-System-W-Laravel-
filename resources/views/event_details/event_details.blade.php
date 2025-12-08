@php
    /** @var \App\Models\Event $event */
    use Carbon\Carbon;

    $pageTitle = 'Event Details';

    $statusClass = match($event->status) {
        'planned'   => 'bg-status planned',
        'ongoing'   => 'bg-status ongoing',
        'completed' => 'bg-status completed',
        'cancelled' => 'bg-status cancelled',
        default     => 'bg-status planned',
    };

    $startDT = $event->start_datetime ? Carbon::parse($event->start_datetime) : null;
    $endDT   = $event->end_datetime   ? Carbon::parse($event->end_datetime)   : null;

    if ($startDT && $endDT) {
        if ($startDT->isSameDay($endDT)) {
            $dateLabel = $startDT->format('F d, Y');
        } else {
            if ($startDT->format('Y') === $endDT->format('Y')) {
                if ($startDT->format('m') === $endDT->format('m')) {
                    $dateLabel = $startDT->format('M d') . ' – ' . $endDT->format('d, Y');
                } else {
                    $dateLabel = $startDT->format('M d') . ' – ' . $endDT->format('M d, Y');
                }
            } else {
                $dateLabel = $startDT->format('M d, Y') . ' – ' . $endDT->format('M d, Y');
            }
        }
    } elseif ($startDT) {
        $dateLabel = $startDT->format('F d, Y');
    } else {
        $dateLabel = 'Date TBA';
    }

    $startDetailLabel = $startDT
        ? $startDT->format('M d, Y · h:i A')
        : 'Start date/time TBA';

    $endDetailLabel = $endDT
        ? $endDT->format('M d, Y · h:i A')
        : null;

    $barangay      = $event->location?->barangay ?? 'No barangay set';
    $district      = $event->location?->district_id ?? $event->district_id;
    $districtLabel = $district ? "District $district" : "No district set";

    $eventTypeLabel = $event->eventType?->label ?? 'Uncategorized';
    $eventTypeIcon  = $event->eventType?->icon_class ?? 'fa-solid fa-calendar';

    $eventCode = $event->event_code ?? '—';

    $maxVolunteers = isset($event->max_volunteers) && (int)$event->max_volunteers > 0
        ? (int)$event->max_volunteers
        : null;

    $expectedCount     = $expectedCount ?? $event->expectedVolunteers?->count() ?? 0;
    $actualCount       = $actualCount ?? 0;
    $presentCount      = $presentCount ?? 0;
    $lateCount         = $lateCount ?? 0;
    $walkInCount       = $walkInCount ?? 0;

    $attendeesExpectedJs = $attendeesExpectedJs ?? collect();
    $attendeesActualJs   = $attendeesActualJs ?? collect();

    $defaultTab = ($actualCount > 0) ? 'actual' : 'expected';

    $DEFAULT_AVATAR = asset('storage/defaults/default_user.png');
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $pageTitle }} – {{ $event->title }}</title>

    <link rel="stylesheet" href="{{ asset('assets/event_details/css/styles.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
@include('layouts.page_loader')
@include('layouts.navbar')
@include('layouts.back_button')

<main class="container mt-5 mb-4 event-details-main">
    <div class="row g-4">

        {{-- LEFT COLUMN --}}
        <div class="col-lg-7 col-md-12">

            {{-- TOP EVENT CARD --}}
            <div class="event-top card mb-4">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div class="event-title-container flex-grow-1">

                        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                            <h1 class="event-title mb-0">{{ $event->title }}</h1>

                            <span class="badge bg-light text-dark d-inline-flex align-items-center">
                                <i class="{{ $eventTypeIcon }} me-1"></i>
                                {{ $eventTypeLabel }}
                            </span>

                            <span class="badge code-badge d-inline-flex align-items-center" title="Event Access Code">
                                <i class="fa-solid fa-key me-1"></i>
                                {{ $eventCode }}
                                <button type="button" class="btn btn-sm btn-copycode ms-2" id="copyEventCode" title="Copy code">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </span>
                        </div>

                        <div class="badges">
                            <span class="badge">
                                <i class="fa-solid fa-hourglass-start"></i>
                                <span class="badge-label">
                                    Starts: <strong>{{ $startDetailLabel }}</strong>
                                </span>
                            </span>

                            @if($endDetailLabel)
                                <span class="badge">
                                    <i class="fa-solid fa-hourglass-end"></i>
                                    <span class="badge-label">
                                        Ends: <strong>{{ $endDetailLabel }}</strong>
                                    </span>
                                </span>
                            @endif

                            <span class="badge">
                                <i class="fa-solid fa-location-dot"></i>{{ $barangay }}
                            </span>
                            <span class="badge">
                                <i class="fa-solid fa-map"></i>{{ $districtLabel }}
                            </span>
                            <span class="badge {{ $statusClass }} status-badge">
                                <i class="fa-solid fa-circle"></i>{{ ucfirst($event->status) }}
                            </span>
                        </div>
                    </div>

                    {{-- ACTIONS --}}
                    <div class="event-actions ms-auto">
                        <a href="{{ route('events.edit', $event->event_id) }}" class="btn btn-action">
                            <i class="fas fa-pen-to-square"></i> Edit
                        </a>

                        @if(Route::has('events.summary'))
                            <a href="{{ route('events.summary', $event->event_id) }}"
                               class="btn btn-action soft"
                               id="btnSummary"
                               data-event-status="{{ strtolower($event->status) }}"
                               data-has-attendance="{{ ($actualCount ?? 0) > 0 ? 1 : 0 }}">
                                <i class="fa-solid fa-file-lines"></i> Summary
                            </a>
                        @else
                            <button type="button" class="btn btn-action soft" disabled>
                                <i class="fa-solid fa-file-lines"></i> Summary
                            </button>
                        @endif

                        @if(Route::has('attendance.import.index'))
                            <a href="{{ route('attendance.import.index', $event->event_id) }}" class="btn btn-action soft">
                                <i class="fa-solid fa-upload"></i> Import Attendance
                            </a>
                        @else
                            <button type="button" class="btn btn-action soft" disabled>
                                <i class="fa-solid fa-upload"></i> Import Attendance
                            </button>
                        @endif

                        {{-- CANCEL / RESTORE --}}
                        @if(strtolower($event->status) === 'cancelled')
                            <button type="button" class="btn btn-action soft"
                                    data-bs-toggle="modal" data-bs-target="#restoreEventModal">
                                <i class="fa-solid fa-rotate-left"></i> Restore
                            </button>
                        @elseif(strtolower($event->status) !== 'completed')
                            <button type="button" class="btn btn-action soft"
                                    data-bs-toggle="modal" data-bs-target="#cancelEventModal">
                                <i class="fa-solid fa-ban"></i> Cancel
                            </button>
                        @endif

                        {{-- DELETE (hard delete) --}}
                        @if(Route::has('events.destroy'))
                            <button type="button"
                                    class="btn btn-action soft btn-delete-event"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteEventModal">
                                <i class="fa-solid fa-trash-can"></i> Delete
                            </button>
                        @endif
                    </div>

                    {{-- CANCEL MODAL --}}
                    <div class="modal fade" id="cancelEventModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <form method="POST" action="{{ route('events.cancel', $event->event_id) }}"
                                  class="modal-content modal-soft">
                                @csrf
                                <div class="modal-header modal-soft-header">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="modal-icon"><i class="fa-solid fa-ban"></i></div>
                                        <div>
                                            <h5 class="modal-title mb-0">Cancel Event</h5>
                                            <div class="small text-muted">Reason is required.</div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body modal-soft-body">
                                    <label class="form-label fw-semibold">Reason</label>
                                    <textarea name="reason" class="form-control" rows="3"
                                              minlength="3" maxlength="500" required
                                              placeholder="e.g. Venue unavailable, sudden weather, etc."></textarea>
                                </div>

                                <div class="modal-footer border-0 pt-0 px-3 pb-3 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-light btn-pill" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-brand btn-pill">
                                        <i class="fa-solid fa-ban me-1"></i> Confirm Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- RESTORE MODAL --}}
                    <div class="modal fade" id="restoreEventModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <form method="POST" action="{{ route('events.restore', $event->event_id) }}"
                                  class="modal-content modal-soft">
                                @csrf
                                <div class="modal-header modal-soft-header">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="modal-icon"><i class="fa-solid fa-rotate-left"></i></div>
                                        <div>
                                            <h5 class="modal-title mb-0">Restore Event</h5>
                                            <div class="small text-muted">Restores the event back to Planned.</div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body modal-soft-body">
                                    <label class="form-label fw-semibold">Reason (optional)</label>
                                    <textarea name="reason" class="form-control" rows="3"
                                              maxlength="500" placeholder="Optional note…"></textarea>
                                </div>

                                <div class="modal-footer border-0 pt-0 px-3 pb-3 d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-light btn-pill" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-brand btn-pill">
                                        <i class="fa-solid fa-rotate-left me-1"></i> Confirm Restore
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    {{-- DELETE MODAL --}}
                    @if(Route::has('events.destroy'))
                        <div class="modal fade" id="deleteEventModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="POST" action="{{ route('events.destroy', $event->event_id) }}"
                                      class="modal-content modal-soft">
                                    @csrf
                                    @method('DELETE')
                                    <div class="modal-header modal-soft-header">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="modal-icon"><i class="fa-solid fa-trash-can"></i></div>
                                            <div>
                                                <h5 class="modal-title mb-0">Delete Event</h5>
                                                <div class="small text-muted">
                                                    This permanently deletes the event and its roster. This action cannot be undone.
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body modal-soft-body">
                                        <p class="mb-2 fw-semibold">
                                            Are you sure you want to delete <span class="text-danger">"{{ $event->title }}"</span>?
                                        </p>
                                        <p class="small text-muted mb-0">
                                            Consider cancelling instead if you still want to keep the record for reporting.
                                        </p>
                                    </div>

                                    <div class="modal-footer border-0 pt-0 px-3 pb-3 d-flex justify-content-end gap-2">
                                        <button type="button" class="btn btn-light btn-pill" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-danger btn-pill">
                                            <i class="fa-solid fa-trash-can me-1"></i> Delete Event
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif

                </div>

                {{-- INFO + ORGANIZERS --}}
                <div class="details-section mt-4 pt-2">
                    <hr class="details-divider details-divider-top">

                    <div class="details-boxes">
                        <div class="info-block">
                            <div class="info-block-title">
                                <i class="fa-regular fa-rectangle-list"></i> Description
                            </div>
                            <div class="info-block-body">
                                {{ $event->description ?: '—' }}
                            </div>
                        </div>

                        <div class="info-block">
                            <div class="info-block-title">
                                <i class="fa-solid fa-building"></i> Venue
                            </div>
                            <div class="info-block-body">
                                {{ $event->venue ?: '—' }}
                            </div>
                        </div>
                    </div>

                    <hr class="details-divider">

                    <div class="details-section-heading mb-2">
                        <i class="fa-solid fa-users-gear details-section-icon"></i>
                        <span class="details-section-title">Organizers</span>
                    </div>

                    @forelse($event->organizers as $org)
                        <div class="organizer-row">
                            <div class="organizer-card d-flex">
                                <div class="avatar-circle me-2">
                                    <i class="fa-solid fa-user text-white"></i>
                                </div>
                                <div>
                                    <div class="fw-semibold organizer-name">{{ $org->name }}</div>
                                    <div class="small text-muted">
                                        <i class="fa-regular fa-envelope me-1 organizer-ico"></i>{{ $org->email ?? '—' }}
                                    </div>
                                    <div class="small text-muted">
                                        <i class="fa-solid fa-phone me-1 organizer-ico"></i>{{ $org->contact ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <span class="text-muted small">No organizers listed.</span>
                    @endforelse
                </div>

            </div>
        </div>

        {{-- RIGHT COLUMN --}}
        <div class="col-lg-5 col-md-12">
            <div class="card ra-card">

                <div class="ra-topbar">
                    <div class="ra-tabs ra-tabs--top">
                        <button type="button" class="ra-tab" data-tab="expected">Roster</button>
                        <button type="button" class="ra-tab" data-tab="actual">Attendance</button>
                    </div>

                    <div class="ra-stat" id="raStat"></div>
                </div>

                <div class="ra-sep"></div>

                <div class="ra-controls">
                    <div class="ra-filters ra-filters--row1">
                        <div class="search-wrap">
                            <div class="search-pill ra-search">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="list-search" class="search-input"
                                       placeholder="Search name / course / email" autocomplete="off">
                            </div>
                            <div class="search-suggest" id="search-suggest"></div>
                        </div>

                        <div class="dd dd-short" id="dd-course">
                            <button class="dd-trigger" type="button">
                                <i class="fa-solid fa-graduation-cap"></i>
                                <span class="dd-label" id="course-label">All Courses</span>
                                <i class="fa-solid fa-chevron-down dd-caret"></i>
                            </button>
                            <div class="dd-menu" id="course-menu">
                                <button class="dd-item is-active" type="button" data-value="">
                                    <i class="fa-solid fa-layer-group"></i> All Courses
                                </button>
                            </div>
                            <input type="hidden" id="course" value="">
                        </div>

                        <div class="dd dd-short" id="dd-sort">
                            <button class="dd-trigger" type="button">
                                <i class="fa-solid fa-arrow-down-a-z"></i>
                                <span class="dd-label" id="sort-label">Name A – Z</span>
                                <i class="fa-solid fa-chevron-down dd-caret"></i>
                            </button>
                            <div class="dd-menu" id="sort-menu">
                                <button class="dd-item is-active" type="button" data-value="name_asc">
                                    <i class="fa-solid fa-arrow-down-a-z"></i> Name A – Z
                                </button>
                                <button class="dd-item" type="button" data-value="name_desc">
                                    <i class="fa-solid fa-arrow-up-z-a"></i> Name Z – A
                                </button>
                            </div>
                            <input type="hidden" id="sort" value="name_asc">
                        </div>
                    </div>

                    <div class="ra-filters ra-filters--row2">
                        <div class="status-filter-group" id="status-filter-group">
                            <button type="button" class="status-pill is-active" data-status="">
                                <span class="dot"></span><span>All</span>
                            </button>
                            <button type="button" class="status-pill" data-status="present">
                                <span class="dot"></span><span>Present</span>
                            </button>
                            <button type="button" class="status-pill" data-status="late">
                                <span class="dot"></span><span>Late</span>
                            </button>
                            <button type="button" class="status-pill" data-status="absent">
                                <span class="dot"></span><span>Absent</span>
                            </button>
                            <button type="button" class="status-pill" data-status="walk_in">
                                <span class="dot"></span><span>Walk-in</span>
                            </button>
                        </div>
                    </div>

                    <div class="ra-hint" id="raHint">
                        <i class="fa-solid fa-circle-info ra-hint-icon"></i>
                        <div class="ra-hint-text">
                            <div><b>Roster</b> – planned volunteers.</div>
                            <div><b>Attendance</b> – actual check-ins (late check-ins still count as present; walk-ins only appear in Attendance).</div>
                        </div>
                        <button type="button" class="ra-hint-x" id="raHintClose" aria-label="Close hint">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <div class="ra-body">

                    {{-- ROSTER --}}
                    <div class="tab-panel" data-panel="expected">
                        <div class="cards-grid expected-grid" id="grid-expected"></div>

                        <div class="navigation d-flex justify-content-center align-items-center mt-3 gap-3">
                            <button id="exp-left" class="btn pager-btn" type="button">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <button id="exp-right" class="btn pager-btn" type="button">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <button class="btn btn-brand" data-bs-toggle="modal" data-bs-target="#addStudentModal" type="button">
                                <i class="fa-solid fa-user-plus"></i> Add Volunteers
                            </button>
                        </div>
                    </div>

                    {{-- ATTENDANCE --}}
                    <div class="tab-panel" data-panel="actual">
                        <div class="cards-grid expected-grid" id="grid-actual"></div>

                        <div class="navigation d-flex justify-content-center align-items-center mt-3 gap-3">
                            <button id="act-left" class="btn pager-btn" type="button">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <button id="act-right" class="btn pager-btn" type="button">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>
</main>

{{-- ADD VOLUNTEERS MODAL --}}
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content modal-soft">
            <div class="modal-header modal-soft-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="modal-icon"><i class="fa-solid fa-user-plus"></i></div>
                    <div>
                        <h5 class="modal-title mb-0">Add Volunteers</h5>
                        <div class="small text-muted">Pick schedule-compatible volunteers and save.</div>
                    </div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body modal-soft-body">
                <div class="add-student-modal">
                    <div class="split">
                        <div class="left-list">
                            <div class="split-head">
                                <div class="split-title">
                                    <div class="fw-semibold">Available</div>
                                    <div class="small text-muted">Schedule-compatible volunteers</div>
                                </div>

                                <div class="modal-filters">
                                    <div class="search-pill">
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <input type="text" id="modal-search" class="search-input" placeholder="Name / course" autocomplete="off">
                                    </div>
                                </div>
                            </div>

                            <div id="available-volunteers-list" class="list-scroll"></div>
                        </div>

                        <div class="right-list">
                            <div class="split-head right">
                                <div>
                                    <div class="fw-semibold">Selected</div>
                                    <div class="small text-muted">Review before saving</div>
                                </div>
                                <span class="pill-count" id="selected-count">0</span>
                            </div>

                            <div class="selected-list list-scroll d-flex flex-column" id="selected-list"></div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-end gap-2">
                        <button id="add-selected-btn" class="btn btn-outline-brand btn-pill" type="button">
                            <i class="fa-solid fa-right-to-bracket"></i> Move to Selected
                        </button>
                        <button class="btn btn-light btn-pill" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button id="save-student-btn" class="btn btn-brand btn-pill" type="button">
                            <i class="fa-solid fa-floppy-disk"></i> Save
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

{{-- RESULT MODAL --}}
<div class="modal fade" id="actionResultModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content result-modal">
            <div class="modal-body">
                <div class="result-head">
                    <div class="result-icon success" id="resultIcon">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div class="result-title" id="resultTitle">Success</div>
                </div>

                <hr class="result-hr">
                <div class="result-sub" id="resultSub">Done.</div>

                <div class="result-actions">
                    <button type="button" class="btn btn-brand btn-pill" data-bs-dismiss="modal">
                        <i class="fa-solid fa-check me-1"></i> OK
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
window.__EVENT_DETAILS_BOOT = {
    csrfToken: @json(csrf_token()),
    expected: @json($attendeesExpectedJs),
    actual: @json($attendeesActualJs),
    defaultTab: @json($defaultTab),
    defaultAvatar: @json($DEFAULT_AVATAR),
    presentCount: @json($presentCount),
    lateCount: @json($lateCount),
    walkInCount: @json($walkInCount),
    maxVolunteers: @json($maxVolunteers),

    addVolUrl: @json(route('events.expectedVolunteers.add', $event->event_id)),
    volDataUrl: @json(route('volunteers.data')),
    removeExpectedUrlTemplate: @json(route('events.expectedVolunteers.remove', ['event' => $event->event_id, 'volunteer_id' => '__VID__'])),
    volunteerShowUrlTemplate: @json(route('volunteers.show', '__VID__')),

    bootSuccess: @json(session('submit_success')),
    summaryNotice: null,

    eventCode: @json($eventCode),
    eventStatus: @json(strtolower($event->status)),
    hasAttendanceImport: @json(($actualCount ?? 0) > 0),
};
</script>

<script src="{{ asset('assets/event_details/js/script.js') }}"></script>

</body>
</html>
