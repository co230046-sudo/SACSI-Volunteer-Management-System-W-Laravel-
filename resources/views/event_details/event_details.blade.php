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

    $dateLabel = $startDT ? $startDT->format('F d, Y') : 'Date TBA';

    if ($startDT && $endDT) {
        $timeLabel = $startDT->format('h:i A') . ' - ' . $endDT->format('h:i A');
    } elseif ($startDT) {
        $timeLabel = $startDT->format('h:i A');
    } else {
        $timeLabel = 'Time TBA';
    }

    $barangay      = $event->location?->barangay ?? 'No barangay set';
    $district      = $event->location?->district_id ?? $event->district_id;
    $districtLabel = $district ? "District $district" : "No district set";

    $eventTypeLabel = $event->eventType?->label ?? 'Uncategorized';
    $eventTypeIcon  = $event->eventType?->icon_class ?? 'fa-solid fa-calendar';

    $eventCode = $event->event_code ?? '—';

    // max volunteers logic (0/NULL => unlimited)
    $maxVolunteers = isset($event->max_volunteers) && (int)$event->max_volunteers > 0
        ? (int)$event->max_volunteers
        : null;

    // Data prepared by controller patch:
    $expectedCount     = $expectedCount ?? $event->expectedVolunteers?->count() ?? 0;
    $actualCount       = $actualCount ?? 0;
    $presentCount      = $presentCount ?? 0;
    $lateCount         = $lateCount ?? 0;
    $walkInCount       = $walkInCount ?? 0;

    $attendeesExpectedJs = $attendeesExpectedJs ?? collect();
    $attendeesActualJs   = $attendeesActualJs ?? collect();

    // default tab: if there is any actual attendance, show Attendance tab first
    $defaultTab = ($actualCount > 0) ? 'actual' : 'expected';

    // ✅ default avatar used across JS templates
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

<main class="container my-4">
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
                                <i class="fa-regular fa-calendar"></i>{{ strtoupper($dateLabel) }}
                            </span>
                            <span class="badge">
                                <i class="fa-regular fa-clock"></i>{{ $timeLabel }}
                            </span>
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
                </div>

                <div class="details-boxes mt-3">
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
            </div>

            {{-- ORGANIZERS --}}
            <section class="mb-4">
                <div class="card organizers-wrap p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="h5 mb-0 organizers-title">
                            <i class="fa-solid fa-users-gear me-2"></i>Organizers
                        </h3>
                        <span class="badge bg-light text-dark">{{ $event->organizers->count() }}</span>
                    </div>

                    <div class="row gy-2">
                        @forelse($event->organizers as $org)
                            <div class="col-md-6">
                                <div class="organizer-card d-flex rounded">
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
                            <div class="col-12">
                                <div class="text-muted small">No organizers listed.</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        {{-- RIGHT COLUMN --}}
        <div class="col-lg-5 col-md-12">
            <div class="card ra-card">

                {{-- Top row: Tabs LEFT, Stat RIGHT --}}
                <div class="ra-topbar">
                    <div class="ra-tabs ra-tabs--top">
                        <button type="button" class="ra-tab" data-tab="expected">Roster</button>
                        <button type="button" class="ra-tab" data-tab="actual">Attendance</button>
                    </div>

                    <div class="ra-stat" id="raStat"></div>
                </div>

                <div class="ra-sep"></div>

                {{-- controls --}}
                <div class="ra-controls">
                    <div class="ra-filters ra-filters--row1">
                        <div class="search-pill ra-search">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="list-search" class="search-input"
                                   placeholder="Search name / course / email" autocomplete="off">
                        </div>

                        {{-- Course filter (from roster) --}}
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

                        {{-- Sort --}}
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

                    <div class="ra-hint" id="raHint">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>
                            <b>Roster</b> = planned volunteers • <b>Attendance</b> = actual check-ins (walk-ins can appear only in Attendance)
                        </span>
                        <button type="button" class="ra-hint-x" id="raHintClose" aria-label="Close hint">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                {{-- body --}}
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

{{-- ADD VOLUNTEERS MODAL (kept) --}}
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

{{-- RESULT MODAL (kept) --}}
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

@if(session('submit_success'))
    <script>window.__BOOT_SUCCESS = @json(session('submit_success'));</script>
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ============================================================
   BOOT DATA
============================================================ */
const CSRF_TOKEN = @json(csrf_token());

const EXPECTED = @json($attendeesExpectedJs);
const ACTUAL   = @json($attendeesActualJs);

console.log("ACTUAL[0] keys:", ACTUAL[0] ? Object.keys(ACTUAL[0]) : null);
console.log("ACTUAL[0]:", ACTUAL[0]);

const DEFAULT_TAB = @json($defaultTab);

// ✅ shared fallback avatar
const DEFAULT_AVATAR = @json($DEFAULT_AVATAR);

// counts from controller (trusted)
const PRESENT_COUNT  = Number(@json($presentCount));
const LATE_COUNT     = Number(@json($lateCount));
const MAX_VOLUNTEERS = @json($maxVolunteers); // null => unlimited

/* Existing endpoints used by your modal logic */
const ADD_VOL_URL  = @json(route('events.expectedVolunteers.add', $event->event_id));
const VOL_DATA_URL = @json(route('volunteers.data'));
const REMOVE_EXPECTED_URL_TEMPLATE = @json(route('events.expectedVolunteers.remove', [
  'event' => $event->event_id,
  'volunteer_id' => '__VID__'
]));

/* ============================================================
   UTIL
============================================================ */
const q  = (sel, root = document) => root.querySelector(sel);
const qa = (sel, root = document) => Array.from(root.querySelectorAll(sel));
const norm = s => (s ?? "").toString().trim().toLowerCase();

function escapeHtml(str){
  return (str ?? "").toString()
    .replaceAll("&","&amp;")
    .replaceAll("<","&lt;")
    .replaceAll(">","&gt;")
    .replaceAll('"',"&quot;")
    .replaceAll("'","&#039;");
}

function openResultModal(title, text, mode = "success") {
  const modalEl = q("#actionResultModal");
  if (!modalEl) return;

  q("#resultTitle").textContent = title || (mode === "error" ? "Error" : "Success");
  q("#resultSub").textContent   = text || "";

  const iconBox = q("#resultIcon");
  iconBox.classList.remove("success", "error");

  if (mode === "error") {
    iconBox.classList.add("error");
    iconBox.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i>';
  } else {
    iconBox.classList.add("success");
    iconBox.innerHTML = '<i class="fa-solid fa-check"></i>';
  }

  bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

/* ============================================================
   DROPDOWNS (sort + course)
============================================================ */
function closeAllDropdowns(){ qa(".dd.open").forEach(dd => dd.classList.remove("open")); }
document.addEventListener("click", () => closeAllDropdowns());

function setupDropdown(dd){
  if (!dd) return;
  const trigger = dd.querySelector(".dd-trigger");
  trigger?.addEventListener("click", (e) => {
    e.stopPropagation();
    const willOpen = !dd.classList.contains("open");
    closeAllDropdowns();
    dd.classList.toggle("open", willOpen);
  });

  dd.addEventListener("click", (e) => {
    const item = e.target.closest(".dd-item");
    if (!item) return;

    dd.querySelectorAll(".dd-item").forEach(x => x.classList.remove("is-active"));
    item.classList.add("is-active");

    const hidden = dd.querySelector('input[type="hidden"]');
    const label  = dd.querySelector(".dd-label");
    const val = item.getAttribute("data-value") ?? "";

    if (hidden) hidden.value = val;
    if (label)  label.textContent = item.textContent.replace(/\s+/g," ").trim();

    dd.classList.remove("open");
    renderActiveTab(1);
    updateTopStat();
  });
}

/* Build course filter options from roster courses */
function initCourseFilter(){
  const menu = q("#course-menu");
  if (!menu) return;

  const courses = new Set();
  EXPECTED.forEach(v => {
    const c = (v.course ?? "").toString().trim();
    if (c) courses.add(c);
  });

  qa("#course-menu .dd-item").slice(1).forEach(el => el.remove());

  Array.from(courses).sort((a,b) => a.localeCompare(b)).forEach(c => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "dd-item";
    btn.setAttribute("data-value", c);
    btn.innerHTML = `<i class="fa-solid fa-graduation-cap"></i> ${escapeHtml(c)}`;
    menu.appendChild(btn);
  });

  q("#dd-course")?.classList.toggle("is-empty", courses.size === 0);
}

/* ============================================================
   TABS + TOP STAT (Present + Absent)
============================================================ */
let activeTab = DEFAULT_TAB; // "expected" | "actual"

/**
 * Option A:
 * If there are ZERO attendance rows yet (no import/check-ins),
 * do NOT label roster people as absent.
 */
function computeAbsent(){
  if (ACTUAL.length === 0) return 0;

  const rosterCount = EXPECTED.length;

  const checkedInNonWalkIn = ACTUAL.filter(a => {
    const w = a.walk_in;
    return !(w === true || w === 1 || w === "1");
  }).length;

  return Math.max(0, rosterCount - checkedInNonWalkIn);
}

function updateTopStat(){
  const stat = q("#raStat");
  if (!stat) return;

  if (activeTab === "expected") {
    const expected = EXPECTED.length;
    if (MAX_VOLUNTEERS) {
      stat.innerHTML = `<span class="ra-stat-pill">Expected <b>${expected}</b> / <b>${MAX_VOLUNTEERS}</b></span>`;
    } else {
      stat.innerHTML = `<span class="ra-stat-pill">Expected <b>${expected}</b></span>`;
    }
  } else {
    const absent = computeAbsent();
    stat.innerHTML = `
      <span class="ra-stat-pill ra-stat-pill--ok">Present <b>${PRESENT_COUNT}</b></span>
      <span class="ra-stat-pill ra-stat-pill--neutral">Absent <b>${absent}</b></span>
      <span class="ra-stat-pill ra-stat-pill--warn">Late <b>${LATE_COUNT}</b></span>
    `;
  }
}

function setTab(tab){
  activeTab = tab;

  qa(".ra-tab").forEach(b => b.classList.toggle("is-active", b.dataset.tab === tab));
  qa(".tab-panel").forEach(p => p.classList.toggle("is-active", p.dataset.panel === tab));

  renderActiveTab(1);
  updateTopStat();
}

qa(".ra-tab").forEach(btn => btn.addEventListener("click", () => setTab(btn.dataset.tab)));

/* ============================================================
   LIST RENDER
============================================================ */
let expPage = 1, actPage = 1;
const ITEMS_PER_PAGE = 10;

function sortItems(items){
  const sortVal = q("#sort")?.value || "name_asc";
  const arr = items.slice();
  arr.sort((a,b) => {
    const an = norm(a.name);
    const bn = norm(b.name);
    return sortVal === "name_desc" ? bn.localeCompare(an) : an.localeCompare(bn);
  });
  return arr;
}

function filterItems(items){
  const term = norm(q("#list-search")?.value);
  const courseVal = (q("#course")?.value ?? "").toString().trim();

  return items.filter(v => {
    let okTerm = true;
    if (term) {
      const blob = [v.name, v.course, v.email, v.school_id, v.status, v.source]
        .filter(Boolean).join(" ").toLowerCase();
      okTerm = blob.includes(term);
    }

    let okCourse = true;
    if (courseVal && activeTab === "expected") {
      okCourse = ((v.course ?? "").toString().trim() === courseVal);
    }
    return okTerm && okCourse;
  });
}

  function resolveAvatarUrl(v){
  const pic = (v?.profile_pic ?? "").toString().trim();
  if (pic) return pic;
  return DEFAULT_AVATAR;
}



function renderGrid({items, gridEl, page, leftBtn, rightBtn, type}){
  const filtered = sortItems(filterItems(items));
  const total = filtered.length;
  const maxPage = Math.max(1, Math.ceil(total / ITEMS_PER_PAGE));
  const safePage = Math.max(1, Math.min(page, maxPage));

  gridEl.innerHTML = "";

  const start = (safePage - 1) * ITEMS_PER_PAGE;
  const slice = filtered.slice(start, start + ITEMS_PER_PAGE);

  if (slice.length === 0){
    const msg = (type === "expected") ? `No volunteers found.` : `No attendance records found.`;
    const sub = (type === "expected")
      ? `Try a different search/filter, or add volunteers to the roster.`
      : `Try a different search, or import attendance from the left-side action.`;

    gridEl.innerHTML = `
      <div class="expected-empty">
        <div class="expected-empty-ico"><i class="fa-regular fa-user"></i></div>
        <div class="expected-empty-title">${msg}</div>
        <div class="expected-empty-sub">${sub}</div>
      </div>
    `;
  } else {
    slice.forEach(v => {
      const card = document.createElement("div");
      card.className = "student-card " + (type === 'actual' ? "student-card--actual" : "");
      card.dataset.id = v.id ?? "";

      const isLate = String(v.status || '').toLowerCase() === 'late';
      const rightMeta = (type === 'actual')
        ? `<div class="meta-right">
              <span class="pill-sm ${isLate ? 'pill-sm--warn' : 'pill-sm--good'}">${escapeHtml((v.status || 'present').toUpperCase())}</span>
              ${v.walk_in ? `<span class="pill-sm pill-sm--neutral">WALK-IN</span>` : ``}
              ${v.source ? `<span class="pill-sm pill-sm--neutral">${escapeHtml(String(v.source).toUpperCase())}</span>` : ``}
           </div>`
        : `<button type="button" class="btn btn-sm btn-card-remove ms-auto"
                  data-remove-expected="${v.id}" title="Remove from roster">
              <i class="fa-solid fa-xmark"></i>
           </button>`;

      const avatar = resolveAvatarUrl(v);
      card.innerHTML = `
        <img
          src="${escapeHtml(avatar)}"
          class="avatar"
          alt=""
          onerror="this.onerror=null;this.src='${escapeHtml(DEFAULT_AVATAR)}';"
        >
        <div class="meta">
          ${v.profile_url
            ? `<a class="name" href="${escapeHtml(v.profile_url)}">${escapeHtml(v.name || '—')}</a>`
            : `<div class="name">${escapeHtml(v.name || '—')}</div>`
          }
          <div class="course">${escapeHtml(v.course || v.email || '—')}</div>
        </div>
        ${rightMeta}
      `;
      gridEl.appendChild(card);
    });
  }

  leftBtn.disabled = safePage <= 1;
  rightBtn.disabled = safePage >= maxPage;

  return safePage;
}

function renderActiveTab(page = 1){
  if (activeTab === "expected"){
    expPage = renderGrid({
      items: EXPECTED,
      gridEl: q("#grid-expected"),
      page,
      leftBtn: q("#exp-left"),
      rightBtn: q("#exp-right"),
      type: "expected"
    });
  } else {
    actPage = renderGrid({
      items: ACTUAL,
      gridEl: q("#grid-actual"),
      page,
      leftBtn: q("#act-left"),
      rightBtn: q("#act-right"),
      type: "actual"
    });
  }
}

/* ============================================================
   PAGINATION EVENTS
============================================================ */
q("#exp-left")?.addEventListener("click", () => (setTab("expected"), renderActiveTab(expPage - 1)));
q("#exp-right")?.addEventListener("click", () => (setTab("expected"), renderActiveTab(expPage + 1)));
q("#act-left")?.addEventListener("click", () => (setTab("actual"), renderActiveTab(actPage - 1)));
q("#act-right")?.addEventListener("click", () => (setTab("actual"), renderActiveTab(actPage + 1)));

q("#list-search")?.addEventListener("input", () => renderActiveTab(1));

/* ============================================================
   REMOVE EXPECTED
============================================================ */
async function removeExpectedVolunteer(vid) {
  const url = REMOVE_EXPECTED_URL_TEMPLATE.replace('__VID__', String(vid));
  const res = await fetch(url, {
    method: "DELETE",
    headers: { "Accept": "application/json", "X-CSRF-TOKEN": CSRF_TOKEN }
  });
  const text = await res.text();
  let json = {};
  try { json = JSON.parse(text); } catch { json = { success: false, message: text }; }
  if (!res.ok || json.success !== true) throw new Error(json.message || "Failed to remove volunteer.");
  return true;
}

document.addEventListener("click", async (e) => {
  const btn = e.target.closest("[data-remove-expected]");
  if (!btn) return;
  const vid = Number(btn.getAttribute("data-remove-expected"));
  if (!vid) return;

  try {
    await removeExpectedVolunteer(vid);

    const idx = EXPECTED.findIndex(x => Number(x.id) === vid);
    if (idx !== -1) EXPECTED.splice(idx, 1);

    initCourseFilter();
    renderActiveTab(1);
    updateTopStat();
    openResultModal("Removed", "Volunteer removed from roster.");
  } catch (err) {
    openResultModal("Error", err.message || "Failed to remove volunteer.", "error");
  }
});

/* ============================================================
   DOM READY
============================================================ */
let AVAILABLE_VOLUNTEERS = [];

function ensureSelectedEmptyState(){
  const selectedList = q("#selected-list");
  if (!selectedList) return;
  if (selectedList.querySelector(".student-card")) return;

  selectedList.innerHTML = `
    <div class="empty-state">
      <div class="empty-ico"><i class="fa-solid fa-user-plus"></i></div>
      <div class="empty-title">No one selected</div>
      <div class="empty-sub">Select from the left, then click “Move to Selected”.</div>
    </div>
  `;
  q("#selected-count").textContent = "0";
}

function updateSelectedCount(){
  q("#selected-count").textContent = String(qa("#selected-list .student-card").length);
}

async function loadAvailableVolunteers() {
  const list = q("#available-volunteers-list");
  try {
    const url = new URL(VOL_DATA_URL, window.location.origin);
    url.searchParams.set("per_page", "500");

    const res = await fetch(url.toString(), { headers: { "Accept": "application/json" } });
    if (!res.ok) throw new Error("Failed to load volunteers");

    const json = await res.json();
    AVAILABLE_VOLUNTEERS = json.data || [];

    renderAvailableList();
    ensureSelectedEmptyState();
  } catch (err) {
    console.error(err);
    if (list) list.innerHTML = '<div class="small text-danger py-2">Error loading volunteers.</div>';
  }
}

function resolveApiAvatar(v){
  // supports multiple possible API shapes without breaking:
  // - v.avatar_url (full URL)
  // - v.profile_picture_path (storage path)
  // - fallback
  const avatarUrl = (v?.avatar_url ?? "").toString().trim();
  if (avatarUrl) return avatarUrl;

  const p = (v?.profile_picture_path ?? "").toString().trim();
  if (p) {
    // if API returns "profiles/xxx.png" or "/profiles/xxx.png"
    const trimmed = p.replace(/^\/+/, "");
    return `/storage/${trimmed}`;
  }

  return DEFAULT_AVATAR;
}

function renderAvailableList(){
  const list = q("#available-volunteers-list");
  if (!list) return;

  const term = norm(q("#modal-search")?.value);
  const selectedIds = new Set(qa("#selected-list .student-card").map(el => Number(el.dataset.id)));

  let filtered = AVAILABLE_VOLUNTEERS
    .filter(v => !selectedIds.has(Number(v.volunteer_id)))
    .filter(v => {
      const name = norm(v.full_name);
      const course = norm(v.course?.course_name || "");
      return !term || name.includes(term) || course.includes(term);
    });

  filtered.sort((a,b) => norm(a.full_name).localeCompare(norm(b.full_name)));

  list.innerHTML = "";

  if (filtered.length === 0){
    list.innerHTML = `
      <div class="expected-empty" style="min-height: 220px;">
        <div class="expected-empty-ico"><i class="fa-regular fa-face-frown"></i></div>
        <div class="expected-empty-title">No volunteers found</div>
        <div class="expected-empty-sub">Try a different search.</div>
      </div>
    `;
    return;
  }

  filtered.forEach(v => {
    const avatar = resolveApiAvatar(v);
    const course = v.course?.course_name || "—";

    const div = document.createElement("div");
    div.className = "student-card modal-student d-flex align-items-center";
    div.dataset.id = v.volunteer_id;

    div.innerHTML = `
      <input type="checkbox" class="form-check-input me-2 available-check" data-id="${v.volunteer_id}">
      <img
        src="${escapeHtml(avatar)}"
        class="avatar me-2"
        alt=""
        onerror="this.onerror=null;this.src='${escapeHtml(DEFAULT_AVATAR)}';"
      >
      <div class="meta">
        <div class="name">${escapeHtml(v.full_name)}</div>
        <div class="course small text-muted">${escapeHtml(course)}</div>
      </div>
    `;
    list.appendChild(div);
  });
}

async function saveToServer(volunteerIDs) {
  const res = await fetch(ADD_VOL_URL, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json",
      "X-CSRF-TOKEN": CSRF_TOKEN
    },
    body: JSON.stringify({ volunteer_ids: volunteerIDs })
  });

  const text = await res.text();
  try { return { ok: res.ok, json: JSON.parse(text) }; }
  catch { return { ok: res.ok, json: { success: false, message: text } }; }
}

document.addEventListener("DOMContentLoaded", () => {
  setupDropdown(q("#dd-sort"));
  setupDropdown(q("#dd-course"));

  initCourseFilter();

  setTab(DEFAULT_TAB);
  updateTopStat();

  if (window.__BOOT_SUCCESS) openResultModal("Success", window.__BOOT_SUCCESS, "success");

  q("#raHintClose")?.addEventListener("click", () => q("#raHint")?.remove());

  const modalEl = q("#addStudentModal");
  modalEl?.addEventListener("shown.bs.modal", () => {
    q("#selected-list").innerHTML = "";
    ensureSelectedEmptyState();
    loadAvailableVolunteers();
  });

  q("#modal-search")?.addEventListener("input", renderAvailableList);

  q("#add-selected-btn")?.addEventListener("click", () => {
    const selectedList = q("#selected-list");
    if (!selectedList) return;

    const checks = qa(".available-check").filter(x => x.checked);
    if (checks.length === 0) return;

    selectedList.querySelector(".empty-state")?.remove();

    checks.forEach(cb => {
      const card = cb.closest(".student-card");
      const id = cb.dataset.id;
      if (!card || !id) return;

      if (selectedList.querySelector(`.student-card[data-id="${id}"]`)) return;

      cb.remove();

      const removeBtn = document.createElement("button");
      removeBtn.className = "btn btn-sm btn-outline-secondary ms-auto remove-added";
      removeBtn.type = "button";
      removeBtn.innerHTML = `<i class="fa-solid fa-xmark"></i> Remove`;
      card.appendChild(removeBtn);

      selectedList.appendChild(card);
    });

    updateSelectedCount();
    renderAvailableList();
  });

  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".remove-added");
    if (!btn) return;
    const card = btn.closest(".student-card");
    if (!card) return;
    card.remove();
    updateSelectedCount();
    ensureSelectedEmptyState();
    renderAvailableList();
  });

  q("#save-student-btn")?.addEventListener("click", async () => {
    const selectedCards = qa("#selected-list .student-card");
    if (selectedCards.length === 0) {
      openResultModal("Nothing selected", "Please select volunteers first.", "error");
      return;
    }

    const ids = selectedCards.map(c => Number(c.dataset.id)).filter(Boolean);
    const { ok, json } = await saveToServer(ids);

    if (!ok || json.success !== true) {
      openResultModal("Error", json.message || "Failed to save volunteers.", "error");
      return;
    }

    selectedCards.forEach(card => {
      const id = Number(card.dataset.id);
      if (!EXPECTED.find(v => Number(v.id) === id)) {
        const imgSrc = card.querySelector("img")?.src || DEFAULT_AVATAR;

        EXPECTED.push({
          id,
          name: card.querySelector(".name")?.textContent ?? "",
          course: card.querySelector(".course")?.textContent ?? "",
          profile_pic: imgSrc,
          profile_url: "{{ route('volunteers.show', '__VID__') }}".replace('__VID__', String(id)),
        });
      }
    });

    initCourseFilter();
    updateTopStat();

    bootstrap.Modal.getInstance(modalEl)?.hide();

    setTab("expected");
    openResultModal("Saved", `Added ${json.added ?? 0} volunteer(s). Skipped ${json.skipped ?? 0}.`, "success");
  });
});
</script>

<script>
const EVENT_CODE = @json($eventCode);

document.addEventListener("DOMContentLoaded", () => {
  const copyBtn = document.getElementById("copyEventCode");
  if (!copyBtn) return;

  copyBtn.addEventListener("click", async (e) => {
    e.preventDefault();
    e.stopPropagation();

    const code = (EVENT_CODE || "").toString().trim();
    if (!code || code === "—") {
      openResultModal("No code", "This event has no access code to copy.", "error");
      return;
    }

    try {
      await navigator.clipboard.writeText(code);
      openResultModal("Copied", "Event code copied to clipboard.");
    } catch (err) {
      const ta = document.createElement("textarea");
      ta.value = code;
      ta.setAttribute("readonly", "");
      ta.style.position = "fixed";
      ta.style.left = "-9999px";
      document.body.appendChild(ta);
      ta.select();
      const ok = document.execCommand("copy");
      document.body.removeChild(ta);

      if (ok) openResultModal("Copied", "Event code copied to clipboard.");
      else openResultModal("Error", "Copy failed. Your browser blocked clipboard access.", "error");
    }
  });
});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const btn = document.getElementById("btnSummary");
  if (!btn) return;

  btn.addEventListener("click", (e) => {
    const status = (btn.dataset.eventStatus || "").toLowerCase();
    const hasAttendance = String(btn.dataset.hasAttendance || "0") === "1";

    if (status !== "completed") {
      e.preventDefault();
      openResultModal(
        "Summary unavailable",
        "Event Summary can only be viewed once the event is completed.",
        "error"
      );
      return;
    }

    if (!hasAttendance) {
      e.preventDefault();
      openResultModal(
        "Summary locked",
        "Event Summary is locked until attendance is imported for this event.",
        "error"
      );
      return;
    }
  });
});
</script>

</body>
</html>
