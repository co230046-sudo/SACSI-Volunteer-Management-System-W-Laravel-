@php
    $pageTitle = 'Volunteer Profile';

    // Safe helpers
    $status = strtolower((string)($volunteer->status ?? 'active'));
    $isActive = $status === 'active';

    // --- Schedule parsing (display-only) ---
    $raw = trim((string)($volunteer->class_schedule ?? ''));
    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $schedule = array_fill_keys($days, []);

    if ($raw !== '') {
        foreach ($days as $day) {
            if (preg_match("/$day:\s*(.*?)(?=(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|$))/is", $raw, $m)) {
                $content = trim($m[1] ?? '');
                if (strtolower($content) !== 'no class' && $content !== '') {
                    $schedule[$day] = array_values(array_filter(preg_split('/\s+/', $content)));
                }
            }
        }
    }

    $formatTime = function($time) {
        $time = trim((string)$time);
        [$h,$m] = array_pad(explode(':', $time), 2, '00');
        $h = intval($h);
        $amp = $h >= 12 ? 'PM' : 'AM';
        $h12 = ($h % 12) ?: 12;
        return $h12 . ':' . str_pad((string)$m,2,'0') . ' ' . $amp;
    };

    // --- Event History ---
    $attendances = $eventHistory ?? collect();

    // Hard defaults
    $courses   = $courses   ?? collect();
    $barangays = $barangays ?? collect();
    $districts = $districts ?? collect();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Profile</title>

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('assets/volunteer_list/css/Volunteer_List.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/volunteer_profile/css/volunteer_profile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Reusable-Searchbar+Filter.css') }}">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .profile-photo{
            width: 200px !important;
            height: 200px !important;
            object-fit: cover !important;
            object-position: center !important;
        }
        .event-item { cursor: pointer; }
        .event-item a { position: relative; z-index: 2; }
    </style>
</head>

<body>

@include('layouts.page_loader')
@include('layouts.navbar')
@include('layouts.back_button')

<section id="Student-Section">
    <div class="container-fluid main-content py-4">
        <div class="student-section-wrapper">

            <!-- LEFT COLUMN -->
            <div class="left-col">
                <div class="left-section" style="background-color: #f2f5f8;">

                    <!-- PROFILE SECTION -->
                    <div class="profile-section p-3 border rounded mb-3">
                        <table class="table table-borderless w-100 mb-0">
                            <tbody>
                            <tr>

                                <!-- LEFT SIDE (Avatar + Name) -->
                                <td class="text-center align-middle" style="width:100%;">
                                    <img src="{{ $volunteer->avatar_url }}"
                                         alt="{{ e((string)($volunteer->full_name ?? 'Volunteer')) }}"
                                         class="profile-photo mb-2 border rounded-circle">

                                    <h2 class="volunteer-name mb-1">
                                        {{ $volunteer->full_name ?? '—' }}
                                    </h2>
                                </td>

                                <!-- RIGHT SIDE (Action Buttons) -->
                                <td class="align-middle position-relative">
                                    <div class="action-tools d-flex flex-column gap-2 position-absolute top-0 end-0 m-2">

                                        <!-- Status -->
                                        <div class="info-card d-flex align-items-center gap-2 px-2 py-1">
                                            <i class="fas {{ $isActive ? 'fa-check-circle' : 'fa-circle-xmark' }}"></i>
                                            <span class="status-text {{ $isActive ? 'active' : '' }}">
                                                {{ $isActive ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>

                                        <!-- Edit (opens modal) -->
                                        <button class="info-card" type="button" data-bs-toggle="modal" data-bs-target="#editVolunteerModal">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>

                                    </div>
                                </td>

                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- VOLUNTEER DETAILS -->
                    <div class="volunteer-details p-3 border rounded mb-3 position-relative">

                        <button class="copy-volunteer-btn" type="button" onclick="copyVolunteerData(this)">
                            Copy <i class="fas fa-copy"></i>
                        </button>

                        <h4 class="text-center mb-3">Volunteer Information</h4>

                        <table class="table table-borderless mb-0">
                            <tbody>

                            <tr>
                                <td>
                                    <div class="detail-card">
                                        <h6><i class="fas fa-graduation-cap"></i> Course</h6>
                                        <p>{{ $volunteer->course->course_name ?? '—' }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="detail-card">
                                        <h6><i class="fas fa-layer-group"></i> Year Level</h6>
                                        <p>{{ $volunteer->year_level ?? '—' }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="detail-card">
                                        <h6><i class="fas fa-user-graduate"></i> Batch</h6>
                                        <p>{{ $volunteer->batch_year ?? '—' }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="detail-card">
                                        <h6><i class="fas fa-phone"></i> Contact #</h6>
                                        <p>{{ $volunteer->contact_number ?? '—' }}</p>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <div class="detail-card">
                                        <h6><i class="fas fa-ambulance"></i> Emergency #</h6>
                                        <p>{{ $volunteer->emergency_contact ?? '—' }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="detail-card">
                                        <h6><i class="fas fa-envelope"></i> Email</h6>
                                        <p>{{ $volunteer->email ?? '—' }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="detail-card">
                                        <h6><i class="fas fa-map-marker-alt"></i> Barangay</h6>
                                        <p>{{ $volunteer->barangay ?? '—' }}</p>
                                    </div>
                                </td>
                                <td>
                                    <div class="detail-card">
                                        <h6><i class="fas fa-city"></i> District</h6>
                                        <p>{{ $volunteer->district ? "District {$volunteer->district}" : '—' }}</p>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <div class="detail-card">
                                        <h6><i class="fa-brands fa-facebook-messenger"></i> FB/Messenger</h6>
                                        <p>{{ $volunteer->fb_messenger ?? '—' }}</p>
                                    </div>
                                </td>
                                <td></td><td></td><td></td>
                            </tr>

                            </tbody>
                        </table>

                    </div>

                    <!-- WEEKLY SCHEDULE -->
                    <div class="schedule-section p-3 border rounded position-relative">

                        <button class="copy-schedule-btn" type="button" onclick="copySchedule(this)">
                            Copy <i class="fas fa-copy"></i>
                        </button>

                        <h4 class="text-center mb-3">Weekly Class Schedule</h4>

                        <table class="table table-bordered text-center mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>MON</th><th>TUE</th><th>WED</th><th>THU</th><th>FRI</th><th>SAT</th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr>
                                @foreach ($days as $day)
                                    <td>
                                        @forelse ($schedule[$day] as $slot)
                                            @php
                                                [$start,$end] = array_pad(explode('-', (string)$slot), 2, null);
                                                $start = $start ? trim($start) : null;
                                                $end   = $end ? trim($end) : null;
                                            @endphp

                                            @if($start && $end)
                                                <div class="time-slot">
                                                    {{ $formatTime($start) }} - {{ $formatTime($end) }}
                                                </div>
                                            @else
                                                <div class="text-muted small">No Class</div>
                                            @endif
                                        @empty
                                            <div class="text-muted small">No Class</div>
                                        @endforelse
                                    </td>
                                @endforeach
                            </tr>
                            </tbody>
                        </table>

                    </div>

                </div>
            </div>

            <!-- RIGHT COLUMN -->
            <div class="right-col">
                <div class="event-wrapper">
                    <div class="events-section p-3 border rounded">
                        <h4 class="events-title mb-3">Event History</h4>

                        <table class="table table-bordered mb-0 event-table">
                            <tbody>
                            @forelse($attendances as $a)
                                @php
                                    $ev = $a->event ?? null;

                                    $evTitle = $ev->title
                                        ?? ($a->event_code ? 'Event ' . $a->event_code : 'Unknown Event');

                                    $evCode = $ev->event_code ?? ($a->event_code ?? '—');
                                    $evId   = $ev->event_id ?? null;

                                    $when = null;
                                    if ($ev && $ev->start_datetime) {
                                        $when = \Carbon\Carbon::parse($ev->start_datetime)->format('M d, Y');
                                    } elseif ($a->attendance_time) {
                                        $when = \Carbon\Carbon::parse($a->attendance_time)->format('M d, Y');
                                    }

                                    $statusLabel = $a->status ? strtoupper((string)$a->status) : '—';
                                    $sourceLabel = $a->source ? strtoupper((string)$a->source) : '—';
                                @endphp

                                <tr class="event-item"
                                    @if($evId)
                                        onclick="goToEvent(event, '{{ route('event.details.show', $evId) }}')"
                                    @else
                                        style="cursor:default;"
                                    @endif
                                >
                                    <td class="event-name">
                                        @if($evId)
                                            <a class="event-link" href="{{ route('event.details.show', $evId) }}"
                                               onclick="event.stopPropagation();">
                                                {{ $evTitle }}
                                            </a>
                                        @else
                                            {{ $evTitle }}
                                        @endif

                                        <div class="event-meta">
                                            <span class="badge bg-light text-dark">Code: {{ $evCode }}</span>
                                            <span class="text-muted ms-2">{{ $when ?? '—' }}</span>
                                        </div>
                                    </td>

                                    <td class="event-status text-center">
                                        <span class="badge bg-secondary">{{ $statusLabel }}</span>
                                    </td>

                                    <td class="event-action text-center">
                                        <span class="badge bg-light text-dark">{{ $sourceLabel }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted text-center">No attendance records found.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>

                        @if(isset($eventHistory) && $eventHistory instanceof \Illuminate\Pagination\LengthAwarePaginator)
                            <div class="mt-3 d-flex justify-content-center">
                                {{ $eventHistory->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ✅ ONLY include the real modal(s). NO inline TEMP modal here. --}}
@include('layouts.modals.submit.volunteer_profile.edit_volunteer_modal')

<!-- Bootstrap (ONLY ONCE) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function goToEvent(e, url){
    const tag = (e?.target?.tagName || '').toLowerCase();
    if (tag === 'a' || tag === 'button' || e?.target?.closest('a,button')) return;
    window.location.href = url;
}
</script>

<script>
async function copyVolunteerData(button) {
    const cards = document.querySelectorAll('.volunteer-details .detail-card');
    if (!cards.length) return;

    let lines = [];
    let n = 1;

    cards.forEach(card => {
        const title = card.querySelector("h6")?.innerText?.trim() || "";
        const value = card.querySelector("p")?.innerText?.trim() || "";
        if (title && value) lines.push(`${n++}. ${title}: ${value}`);
    });

    const text = lines.join("\n");

    try {
        await navigator.clipboard.writeText(text);

        const original = button.innerHTML;
        button.innerHTML = `Copied <i class="fas fa-check"></i>`;
        button.disabled = true;
        setTimeout(() => {
            button.innerHTML = original;
            button.disabled = false;
        }, 1800);

    } catch (err) {
        console.error("Clipboard failed:", err);
        window.prompt("Copy manually:", text);
    }
}
</script>

<script>
async function copySchedule(button) {
    const table = document.querySelector('.schedule-section table');
    if (!table) return;

    let output = [];
    let count = 1;

    const days = [...table.querySelectorAll("thead th")].map(th => th.innerText.trim());
    const tds  = [...table.querySelectorAll("tbody tr td")];

    days.forEach((day, i) => {
        const cell = tds[i];
        const slots = [...cell.querySelectorAll(".time-slot")].map(s => s.innerText.trim());

        const line = slots.length
            ? `${count}. ${day}: ${slots.join(", ")}`
            : `${count}. ${day}: No Class`;

        output.push(line);
        count++;
    });

    const finalText = output.join("\n");

    try {
        await navigator.clipboard.writeText(finalText);

        const original = button.innerHTML;
        button.innerHTML = `Copied <i class="fas fa-check"></i>`;
        button.disabled = true;

        setTimeout(() => {
            button.innerHTML = original;
            button.disabled = false;
        }, 1800);

    } catch (err) {
        console.error("Clipboard failed:", err);
        window.prompt("Copy manually:", finalText);
    }
}
</script>

</body>
</html>
    