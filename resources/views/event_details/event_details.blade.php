@php
    /** @var \App\Models\Event $event */
    use Carbon\Carbon;

    $pageTitle = 'Event Details';

    /* --------------------------------------------------------
       STATUS BADGE
    -------------------------------------------------------- */
    $statusClass = match($event->status) {
        'planned'   => 'bg-secondary',
        'ongoing'   => 'bg-info',
        'completed' => 'bg-success',
        'cancelled' => 'bg-danger',
        default     => 'bg-secondary',
    };

    /* --------------------------------------------------------
       DATE / TIME (SAFE CARBON PARSING)
    -------------------------------------------------------- */
    $startDT = $event->start_datetime ? Carbon::parse($event->start_datetime) : null;
    $endDT   = $event->end_datetime   ? Carbon::parse($event->end_datetime)   : null;

    $startTime = $event->start_time ? Carbon::parse($event->start_time) : null;
    $endTime   = $event->end_time   ? Carbon::parse($event->end_time)   : null;

    // Date label
    $dateLabel = $startDT
        ? $startDT->format('F d, Y')
        : 'Date TBA';

    // Time label
    if ($startDT && $endDT) {
        $timeLabel = $startDT->format('h:i A') . ' - ' . $endDT->format('h:i A');
    } elseif ($startDT) {
        $timeLabel = $startDT->format('h:i A');
    } else {
        $timeLabel = 'Time TBA';
    }

    // Day-of-week for schedule matching
    $eventDayName = $startDT?->format('l') ?? '';

    // Time range in 24h format for schedule matching
    $eventTime24 =
        ($startTime?->format('H:i') ?? '00:00') .
        '-' .
        ($endTime?->format('H:i') ?? '00:00');

    /* --------------------------------------------------------
       LOCATION
    -------------------------------------------------------- */
    $barangay      = $event->location?->barangay ?? 'No barangay set';
    $district      = $event->location?->district_id ?? $event->district_id;
    $districtLabel = $district ? "District $district" : "No district set";

    /* --------------------------------------------------------
       EVENT TYPE
    -------------------------------------------------------- */
    $eventTypeLabel = $event->eventType?->label ?? 'Uncategorized';
    $eventTypeIcon  = $event->eventType?->icon_class ?? 'fa-solid fa-calendar';

    /* --------------------------------------------------------
       EXPECTED VOLUNTEERS → JS ARRAY
    -------------------------------------------------------- */
    $attendeesJs = $event->expectedVolunteers
        ->filter(fn($ev) => $ev->volunteer)
        ->map(function ($ev) {
            $v = $ev->volunteer;

            $avatar = $v->profile_picture_path
                ? asset('storage/' . ltrim($v->profile_picture_path, '/'))
                : asset('storage/defaults/default_user.png');

            return [
                'id'         => $v->volunteer_id,
                'name'       => $v->full_name,
                'course'     => optional($v->course)->course_name,
                'profile_pic'=> $avatar,
            ];
        })
        ->values();
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

<main class="container my-4">

    <div class="row g-4">

        {{-- LEFT COLUMN --}}
        <div class="col-lg-7 col-md-12">

            <div class="event-top card mb-4">

                <div class="d-flex justify-content-between align-items-start flex-wrap">

                    <div class="event-title-container flex-grow-1">
                        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                            <h1 class="event-title mb-0">{{ $event->title }}</h1>

                            <span class="badge bg-light text-dark d-inline-flex align-items-center">
                                <i class="{{ $eventTypeIcon }} me-1"></i>
                                {{ $eventTypeLabel }}
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

                    {{-- ACTION BUTTONS --}}
                    <div class="event-actions">
                        <a href="#" class="btn btn-action">
                            <i class="fas fa-pen-to-square"></i> Edit Event
                        </a>
                        <a href="#" class="btn btn-action btn-summary">
                            <i class="fa-solid fa-file-lines"></i> Summary
                        </a>
                        <a href="#" class="btn btn-action btn-import">
                            <i class="fa-solid fa-upload"></i> Import
                        </a>
                    </div>
                </div>

                @if($event->description)
                    <h6 class="text-muted mt-3 mb-1">Description</h6>
                    <p>{{ $event->description }}</p>
                @endif

                @if($event->venue)
                    <h6 class="text-muted mt-3 mb-1">Venue</h6>
                    <p><i class="fa-solid fa-building me-1"></i>{{ $event->venue }}</p>
                @endif

            </div>

            {{-- ORGANIZERS --}}
            <section class="mb-4">
                <div class="card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h3 class="h5 mb-0">
                            <i class="fa-solid fa-users-gear me-2"></i>Organizers
                        </h3>
                        <span class="badge bg-light text-dark">{{ $event->organizers->count() }}</span>
                    </div>

                    <div class="row gy-2">
                        @foreach($event->organizers as $org)
                            <div class="col-md-6">
                                <div class="organizer-card d-flex rounded">
                                    <div class="avatar-circle me-2">
                                        <i class="fa-solid fa-user text-white"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold organizer-name">{{ $org->name }}</div>
                                        <div class="small text-muted">
                                            <i class="fa-regular fa-envelope me-1"></i>{{ $org->email }}
                                        </div>
                                        <div class="small text-muted">
                                            <i class="fa-solid fa-phone me-1"></i>{{ $org->contact }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>
            </section>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="col-lg-5 col-md-12">
            <div class="card attendees-card">

                <div class="d-flex justify-content-between align-items-center attendees-header">
                    <h3 class="h5 mb-0">
                        <i class="fa-solid fa-user-check me-2"></i>Expected Volunteers
                    </h3>

                    <div class="text-end">
                        <div class="text-muted small mb-1">
                            Showing <span id="attendee-count">{{ $attendeesJs->count() }}</span>
                        </div>
                        <input
                            type="text"
                            id="expected-search"
                            class="form-control form-control-sm expected-search-input"
                            placeholder="Search expected..."
                        >
                    </div>
                </div>

                <div class="cards-grid" id="cards-grid"></div>

                <div class="navigation d-flex justify-content-center align-items-center mt-3 gap-3">
                    <button id="arrow-left" class="btn pager-btn">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button id="arrow-right" class="btn pager-btn">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>

                <div class="text-center mt-3">
                    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        <i class="fa-solid fa-user-plus"></i> Add Volunteers
                    </button>
                </div>

            </div>
        </div>

    </div>

</main>

{{-- ============================================
     ADD STUDENT MODAL
============================================ --}}
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add Attendees</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="add-student-modal">
                    <div class="split d-flex flex-wrap">

                        {{-- LEFT LIST --}}
                        <div class="left-list flex-fill">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Available People</h6>
                                <input
                                    type="text"
                                    id="modal-search"
                                    class="form-control form-control-sm modal-search-input"
                                    placeholder="Search volunteers..."
                                >
                            </div>
                            <div id="available-volunteers-list" class="list-scroll"></div>
                        </div>

                        {{-- RIGHT LIST --}}
                        <div class="right-list flex-fill ms-3 mt-3 mt-lg-0">
                            <h6>Selected in this modal</h6>

                            <div class="selected-list list-scroll d-flex flex-column">
                                <div class="empty small text-muted">No one added yet.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-end gap-2">
                        <button id="add-selected-btn" class="btn" type="button">Add selected</button>
                        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button id="save-student-btn" class="btn btn-danger" type="button">Save</button>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ============================================================
   EXPORT EVENT INFO FOR API
============================================================ */
window.EVENT_DAY  = @json($eventDayName);
window.EVENT_TIME = @json($eventTime24);
const ADD_VOL_URL = @json(route('events.addVolunteers', $event->event_id));
const VOL_DATA_URL = @json(route('volunteers.data'));

/* ============================================================
   HELPERS
============================================================ */
const q  = (sel, root = document) => root.querySelector(sel);
const qa = (sel, root = document) => Array.from(root.querySelectorAll(sel));

/* ============================================================
   EXPECTED VOLUNTEERS GRID (RIGHT PANEL)
============================================================ */
const ATTENDEES = @json($attendeesJs);
let filteredAttendees = ATTENDEES.slice();
let currentPage       = 1;
const itemsPerPage    = 12;

function renderGrid(page = 1) {
    const grid = q("#cards-grid");
    if (!grid) return;

    const total   = filteredAttendees.length;
    const maxPage = Math.max(1, Math.ceil(total / itemsPerPage));
    const safePage = Math.max(1, Math.min(page, maxPage));
    currentPage = safePage;

    grid.innerHTML = "";

    const start = (safePage - 1) * itemsPerPage;
    const slice = filteredAttendees.slice(start, start + itemsPerPage);

    slice.forEach(v => {
        const a = document.createElement("a");
        a.className = "student-card";
        a.dataset.id = v.id;
        a.href = "#";

        a.innerHTML = `
            <img src="${v.profile_pic}" class="avatar" alt="">
            <div class="meta">
                <div class="name">${v.name}</div>
                <div class="course">${v.course || '—'}</div>
            </div>
        `;

        grid.appendChild(a);
    });

    const countEl = q("#attendee-count");
    if (countEl) countEl.textContent = total;

    const leftBtn  = q("#arrow-left");
    const rightBtn = q("#arrow-right");
    if (leftBtn)  leftBtn.disabled  = safePage <= 1;
    if (rightBtn) rightBtn.disabled = safePage >= maxPage;
}

function applyExpectedFilter(term) {
    const t = term.trim().toLowerCase();
    if (!t) {
        filteredAttendees = ATTENDEES.slice();
    } else {
        filteredAttendees = ATTENDEES.filter(v => {
            const name   = (v.name   || '').toLowerCase();
            const course = (v.course || '').toLowerCase();
            return name.includes(t) || course.includes(t);
        });
    }
    renderGrid(1);
}

/* ============================================================
   MODAL: LOAD AVAILABLE VOLUNTEERS + SEARCH
============================================================ */
let AVAILABLE_VOLUNTEERS = [];

function renderAvailableList(term = "") {
    const list = q("#available-volunteers-list");
    if (!list) return;

    const t = term.trim().toLowerCase();
    list.innerHTML = "";

    const filtered = AVAILABLE_VOLUNTEERS.filter(v => {
        if (!t) return true;
        const name     = (v.full_name || '').toLowerCase();
        const course   = (v.course && v.course.course_name ? v.course.course_name : '').toLowerCase();
        const barangay = (v.barangay || '').toLowerCase();
        const district = (v.district !== null && v.district !== undefined) ? String(v.district) : '';
        return (
            name.includes(t) ||
            course.includes(t) ||
            barangay.includes(t) ||
            district.includes(t)
        );
    });

    if (filtered.length === 0) {
        list.innerHTML = '<div class="small text-muted py-2">No volunteers match your search.</div>';
        return;
    }

    filtered.forEach(v => {
        const avatar = v.avatar_url || '/storage/defaults/default_user.png';

        const div = document.createElement("div");
        div.className = "student-card modal-student d-flex align-items-center";
        div.dataset.id = v.volunteer_id;

        div.innerHTML = `
            <input type="checkbox"
                   class="form-check-input me-2 available-check"
                   data-id="${v.volunteer_id}">
            <img src="${avatar}" class="avatar me-2" alt="">
            <div class="meta">
                <div class="name">${v.full_name}</div>
                <div class="course small text-muted">
                    ${(v.course && v.course.course_name) ? v.course.course_name : '—'}
                </div>
            </div>
        `;

        list.appendChild(div);
    });
}

async function loadAvailableVolunteers() {
    try {
        const url = new URL(VOL_DATA_URL);
        if (window.EVENT_DAY)  url.searchParams.set("day", window.EVENT_DAY);
        if (window.EVENT_TIME) url.searchParams.set("schedule_day", window.EVENT_TIME);
        url.searchParams.set("per_page", 500);

        const res = await fetch(url.toString(), {
            headers: { "Accept": "application/json" }
        });

        if (!res.ok) throw new Error("Failed to load volunteers");

        const json = await res.json();
        AVAILABLE_VOLUNTEERS = json.data || [];

        const modalSearch = q("#modal-search");
        const term = modalSearch ? modalSearch.value : "";
        renderAvailableList(term);

    } catch (err) {
        console.error("Error loading volunteers:", err);
        const list = q("#available-volunteers-list");
        if (list) {
            list.innerHTML = '<div class="small text-danger py-2">Error loading volunteers.</div>';
        }
    }
}

/* ============================================================
   SAVE TO SERVER
============================================================ */
async function saveToServer(volunteerIDs) {
    const res = await fetch(ADD_VOL_URL, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": @json(csrf_token())
        },
        body: JSON.stringify({ volunteer_ids: volunteerIDs })
    });

    return await res.json();
}

/* ============================================================
   DOM READY – BIND EVENTS
============================================================ */
document.addEventListener("DOMContentLoaded", () => {
    // Initial right-panel render
    renderGrid(1);

    const leftBtn        = q("#arrow-left");
    const rightBtn       = q("#arrow-right");
    const expectedSearch = q("#expected-search");
    const modalEl        = q("#addStudentModal");
    const modalSearchInp = q("#modal-search");
    const addSelectedBtn = q("#add-selected-btn");
    const saveBtn        = q("#save-student-btn");

    // Pagination
    if (leftBtn) {
        leftBtn.addEventListener("click", () => {
            if (currentPage > 1) renderGrid(currentPage - 1);
        });
    }

    if (rightBtn) {
        rightBtn.addEventListener("click", () => {
            const maxPage = Math.max(1, Math.ceil(filteredAttendees.length / itemsPerPage));
            if (currentPage < maxPage) renderGrid(currentPage + 1);
        });
    }

    // Search expected panel
    if (expectedSearch) {
        expectedSearch.addEventListener("input", () => {
            applyExpectedFilter(expectedSearch.value);
        });
    }

    // Modal show → load volunteers
    if (modalEl) {
        modalEl.addEventListener("shown.bs.modal", loadAvailableVolunteers);
    }

    // Modal search filter
    if (modalSearchInp) {
        modalSearchInp.addEventListener("input", () => {
            renderAvailableList(modalSearchInp.value);
        });
    }

    // Add selected volunteers (left → right)
    if (addSelectedBtn) {
        addSelectedBtn.addEventListener("click", () => {
            const checks       = qa(".available-check");
            const selectedList = q(".selected-list");
            const empty        = q(".selected-list .empty");

            if (!selectedList) return;
            if (empty) empty.remove();

            checks.forEach(cb => {
                if (!cb.checked) return;

                const card = cb.closest(".student-card");
                const id   = cb.dataset.id;
                if (!card || !id) return;

                // skip if already in right list
                if (q(`.selected-list .student-card[data-id="${id}"]`)) return;

                cb.remove();

                const removeBtn = document.createElement("button");
                removeBtn.className = "btn btn-sm btn-outline-secondary ms-auto remove-added";
                removeBtn.type = "button";
                removeBtn.textContent = "Remove";
                card.appendChild(removeBtn);

                selectedList.appendChild(card);
            });
        });
    }

    // Remove from right list → back to left list
    document.addEventListener("click", e => {
        if (!e.target.classList.contains("remove-added")) return;

        const card = e.target.closest(".student-card");
        if (!card) return;
        const id = card.dataset.id;

        e.target.remove();

        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.className = "form-check-input me-2 available-check";
        checkbox.dataset.id = id;

        card.insertBefore(checkbox, card.firstChild);

        const list = q("#available-volunteers-list");
        if (list) list.appendChild(card);

        const selList = q(".selected-list");
        if (selList && !selList.querySelector(".student-card")) {
            selList.innerHTML = '<div class="empty small text-muted">No one added yet.</div>';
        }
    });

    // SAVE BUTTON – send to backend + update right panel
    if (saveBtn) {
        saveBtn.addEventListener("click", async () => {
            const selectedCards = qa(".selected-list .student-card");
            if (selectedCards.length === 0) {
                alert("Please add volunteers first.");
                return;
            }

            const ids = selectedCards
                .map(c => Number(c.dataset.id))
                .filter(Boolean);

            try {
                const res = await saveToServer(ids);

                if (!res || !res.success) {
                    alert("Error saving volunteers.");
                    return;
                }

                // Update right panel data
                selectedCards.forEach(card => {
                    const id = Number(card.dataset.id);
                    if (!ATTENDEES.find(v => Number(v.id) === id)) {
                        ATTENDEES.push({
                            id,
                            name: card.querySelector(".name")?.textContent ?? "",
                            course: card.querySelector(".course")?.textContent ?? "",
                            profile_pic: card.querySelector("img")?.src
                                ?? "/storage/defaults/default_user.png"
                        });
                    }
                });

                applyExpectedFilter(expectedSearch ? expectedSearch.value : "");

                // Close modal
                if (modalEl) {
                    const instance = bootstrap.Modal.getInstance(modalEl);
                    if (instance) instance.hide();
                }

                const selList = q(".selected-list");
                if (selList) {
                    selList.innerHTML = '<div class="empty small text-muted">No one added yet.</div>';
                }

            } catch (err) {
                console.error(err);
                alert("Unexpected error saving volunteers.");
            }
        });
    }
});
</script>

</body>
</html>
