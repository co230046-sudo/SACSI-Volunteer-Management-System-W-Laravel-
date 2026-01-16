<?php $pageTitle = "Event Manager"; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Event Manager</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;700;800;900&display=swap" rel="stylesheet" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <link rel="stylesheet" href="{{ asset('assets/event_manager/css/event_manager.css') }}">
</head>

<body>
  @include('layouts.page_loader')
  @include('layouts.navbar')
  @include('layouts.back_button')

  @php
    // ✅ Districts locked to 1 and 2 (requested)
    $districts = [1, 2];

    // ✅ Flatten all barangays from barangaysByDistrict (controller provides this)
    $allBarangays = collect($barangaysByDistrict ?? [])
      ->flatten()
      ->filter(fn($b) => is_string($b) && trim($b) !== '')
      ->unique()
      ->sort()
      ->values()
      ->all();

    use Illuminate\Support\Str;

    // ✅ Success payload may be string OR array
    $successPayload = session('success') ?? session('submit_success');
    $successIsArray = is_array($successPayload);
  @endphp

  <div class="em-wrap">
    <div class="em-card"
         id="emRoot"
         data-default-tab="{{ $defaultTab ?? 'planned' }}"
         data-barangays-by-district='@json($barangaysByDistrict ?? [])'
    >

      <header class="em-head">
        <div class="em-head-left">
          <div class="em-kicker">
            <i class="fa-regular fa-calendar-days"></i>
            Event Manager
          </div>

          <h1 class="em-h1">Pre &amp; Post Events</h1>

          <div class="em-controls em-controls--anchor">

            {{-- MAIN SEARCH with autosuggest dropdown --}}
            <div class="em-search em-search--suggest" role="search">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input id="emSearch" type="text" placeholder="Search title, venue, barangay, district, code…" autocomplete="off" />
              <button class="em-search-clear" id="emSearchClear" type="button" aria-label="Clear search">
                <i class="fa-solid fa-xmark"></i>
              </button>
              <div class="em-suggest-box em-suggest-box--main" id="emMainSuggest" hidden></div>
            </div>

            <button class="em-pill em-pill--brand" type="button" id="emFilterToggle" aria-expanded="false">
              <i class="fa-solid fa-sliders"></i> Filter &amp; Sort
              <i class="fa-solid fa-chevron-down ms-1"></i>
            </button>

            <button class="em-pill em-pill--soft" type="button" id="emCopyBtn">
              <i class="fa-regular fa-copy"></i> Copy List
            </button>

            {{-- Event Log button --}}
            <button
              id="emLogBtn"
              type="button"
              class="em-pill em-pill--soft"
              data-bs-toggle="modal"
              data-bs-target="#emActivityModal">
              <i class="fa-regular fa-clock"></i>
              Event Log
            </button>

            <button class="em-pill em-pill--danger" type="button" id="emBulkDeleteBtn">
              <i class="fa-regular fa-trash-can"></i>
              Delete Selected
              <span class="em-bulk-count" id="emSelectedCount">0</span>
            </button>

            {{-- ✅ Overlay Panel --}}
            <div class="em-panel em-panel--overlay" id="emPanel" hidden>
              <div class="em-panel-grid--twoCol">

                {{-- Column 1: Sort + Month + Day + Time --}}
                <div class="em-panel-col">

                  <div class="em-field">
                    <label class="em-label"><i class="fa-solid fa-arrow-up-wide-short"></i> Sort by</label>
                    <div class="em-dd hpLike" data-dd="sort">
                      <button class="em-ddBtn" type="button">
                        <span data-dd-text>Sort by Date (Soonest)</span>
                        <i class="fa-solid fa-chevron-down"></i>
                      </button>
                      <div class="em-ddMenu" data-dd-menu>
                        <button class="em-ddItem" type="button" data-value="date_asc">Sort by Date (Soonest)</button>
                        <button class="em-ddItem" type="button" data-value="date_desc">Sort by Date (Latest)</button>
                        <button class="em-ddItem" type="button" data-value="title_asc">Sort by Title (A–Z)</button>
                        <button class="em-ddItem" type="button" data-value="title_desc">Sort by Title (Z–A)</button>
                      </div>
                    </div>
                  </div>

                  <div class="em-field">
                    <label class="em-label"><i class="fa-regular fa-calendar"></i> Filter by Month</label>
                    <div class="em-dd hpLike" data-dd="month">
                      <button class="em-ddBtn" type="button">
                        <span data-dd-text>All Months</span>
                        <i class="fa-solid fa-chevron-down"></i>
                      </button>
                      <div class="em-ddMenu" data-dd-menu>
                        <button class="em-ddItem" type="button" data-value="">All Months</button>
                        @for($m=1;$m<=12;$m++)
                          <button class="em-ddItem" type="button" data-value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</button>
                        @endfor
                      </div>
                    </div>
                  </div>

                  <div class="em-field">
                    <label class="em-label"><i class="fa-regular fa-calendar-check"></i> Filter by Day</label>
                    <div class="em-dd hpLike" data-dd="day">
                      <button class="em-ddBtn" type="button">
                        <span data-dd-text>All Days</span>
                        <i class="fa-solid fa-chevron-down"></i>
                      </button>
                      <div class="em-ddMenu" data-dd-menu>
                        <button class="em-ddItem" type="button" data-value="">All Days</button>
                        @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d)
                          <button class="em-ddItem" type="button" data-value="{{ $d }}">{{ $d }}</button>
                        @endforeach
                      </div>
                    </div>
                  </div>

                  <div class="em-field em-field--inline2">
                    <div class="em-field">
                      <label class="em-label"><i class="fa-regular fa-sun"></i> Time Group</label>
                      <div class="em-dd hpLike" data-dd="timegroup">
                        <button class="em-ddBtn" type="button">
                          <span data-dd-text>All Times</span>
                          <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="em-ddMenu" data-dd-menu>
                          <button class="em-ddItem" type="button" data-value="">All Times</button>
                          <button class="em-ddItem" type="button" data-value="AM">Morning (AM)</button>
                          <button class="em-ddItem" type="button" data-value="PM">Afternoon/Evening (PM)</button>
                        </div>
                      </div>
                    </div>

                    <div class="em-field">
                      <label class="em-label"><i class="fa-regular fa-clock"></i> Time Slot</label>
                      <div class="em-dd hpLike" data-dd="timeslot">
                        <button class="em-ddBtn" type="button">
                          <span data-dd-text>All Time Slots</span>
                          <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <div class="em-ddMenu" data-dd-menu id="emTimeSlotMenu">
                          <button class="em-ddItem" type="button" data-value="">All Time Slots</button>
                          {{-- JS populates --}}
                        </div>
                      </div>
                    </div>
                  </div>

                </div>

                {{-- Column 2: District + Barangay Search/Suggest --}}
                <div class="em-panel-col">
                  <div class="em-field">
                    <label class="em-label"><i class="fa-solid fa-map-location-dot"></i> Filter by District</label>
                    <div class="em-dd hpLike" data-dd="district">
                      <button class="em-ddBtn" type="button">
                        <span data-dd-text>All Districts</span>
                        <i class="fa-solid fa-chevron-down"></i>
                      </button>
                      <div class="em-ddMenu" data-dd-menu id="emDistrictMenu">
                        <button class="em-ddItem" type="button" data-value="">All Districts</button>
                        @foreach($districts as $dist)
                          <button class="em-ddItem" type="button" data-value="{{ $dist }}">District {{ $dist }}</button>
                        @endforeach
                      </div>
                    </div>
                  </div>

                  <div class="em-field">
                    <label class="em-label"><i class="fa-solid fa-map"></i> Filter by Barangay</label>

                    <div class="em-suggest">
                      <div class="em-suggest-input">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input id="emBarangaySearch" type="text" placeholder="Search barangay…" autocomplete="off" />
                        <button class="em-suggest-clear" id="emBarangayClear" type="button" aria-label="Clear barangay search">
                          <i class="fa-solid fa-xmark"></i>
                        </button>
                      </div>

                      <div class="em-suggest-box" id="emBarangaySuggest" hidden></div>

                      <button class="em-pill em-pill--soft em-pill--mini mt-2" type="button" id="emBarangaySelected" hidden>
                        <i class="fa-solid fa-location-dot"></i>
                        <span id="emBarangaySelectedText"></span>
                        <i class="fa-solid fa-xmark ms-1"></i>
                      </button>
                    </div>

                    <div class="text-muted small mt-2" style="font-weight:800;">
                      Picking a barangay will auto-select its district.
                    </div>
                  </div>
                </div>

                <div class="em-panel-actions--row">
                  <button class="em-btn2" type="button" id="emReset">Reset</button>
                  <button class="em-btn2 em-btn2--primary" type="button" id="emApply">Apply</button>
                </div>

              </div>
            </div>
          </div>

        </div>

        {{-- Total --}}
        <div class="em-head-right">
          <div class="em-pill em-pill--stat" aria-label="Total event count">
            <i class="fa-solid fa-layer-group"></i>
            Total:
            <strong id="emTotalCount">
              {{ $upcomingEvents->count() + $ongoingEvents->count() + $completedEvents->count() + $cancelledEvents->count() }}
            </strong>
          </div>
        </div>
      </header>

      {{-- Tabs --}}
      <nav class="em-tabs" role="tablist" aria-label="Event status tabs">
        <button class="em-tab" type="button" data-tab="planned" aria-selected="false">
          <i class="fa-regular fa-calendar-check"></i>
          Upcoming
          <span class="em-count" id="emCountPlanned">{{ $upcomingEvents->count() }}</span>
        </button>

        <button class="em-tab" type="button" data-tab="ongoing" aria-selected="false">
          <i class="fa-solid fa-hourglass-half"></i>
          Ongoing
          <span class="em-count" id="emCountOngoing">{{ $ongoingEvents->count() }}</span>
        </button>

        <button class="em-tab" type="button" data-tab="completed" aria-selected="false">
          <i class="fa-regular fa-circle-check"></i>
          Completed
          <span class="em-count" id="emCountCompleted">{{ $completedEvents->count() }}</span>
        </button>

        <button class="em-tab" type="button" data-tab="cancelled" aria-selected="false">
          <i class="fa-solid fa-ban"></i>
          Cancelled
          <span class="em-count" id="emCountCancelled">{{ $cancelledEvents->count() }}</span>
        </button>

        {{-- NEW EVENT pill --}}
        <a href="{{ route('events.create') }}"
          class="em-tab em-tab--new"
          data-tooltip="Create and post a new volunteer event.">
          <i class="fa-regular fa-calendar-plus"></i>
          <span>New Event</span>
        </a>

        <button class="em-tab em-tab--selectall ms-auto" type="button" id="emSelectAllBtn" aria-label="Select all visible events">
          <i class="fa-regular fa-square-check"></i>
          Select All (Tab)
        </button>
      </nav>

      <main class="em-content">

        @php
          function emCard($event, $status){
            $start = $event->start_datetime ? \Carbon\Carbon::parse($event->start_datetime) : null;
            $end   = $event->end_datetime ? \Carbon\Carbon::parse($event->end_datetime) : null;

            $dateText = 'Date TBA';
            $timeText = 'Time TBA';
            $dayName  = $start ? $start->format('l') : '';

            if ($start && $end) {
              if ($start->isSameDay($end)) {
                $dateText = $start->format('M d, Y');
                $timeText = $start->format('h:i A') . ' - ' . $end->format('h:i A');
              } else {
                $dateText = $start->format('M d, Y') . ' – ' . $end->format('M d, Y');
                $timeText = $start->format('M d, Y h:i A') . ' – ' . $end->format('M d, Y h:i A');
              }
            } elseif ($start) {
              $dateText = $start->format('M d, Y');
              $timeText = $start->format('h:i A');
            }

            $venue = $event->venue ?: '—';
            $code  = $event->event_code ?: '—';

            $barangay = $event->location?->barangay ?? '';
            $districtId = $event->location?->district_id ?? $event->district_id;
            $districtLabel = $districtId ? "District {$districtId}" : '';

            $month = $start ? (int)$start->format('n') : 0;

            $startMin = $start ? ((int)$start->format('H') * 60 + (int)$start->format('i')) : -1;
            $endMin   = $end   ? ((int)$end->format('H') * 60 + (int)$end->format('i'))   : -1;

            $ts = $start ? $start->timestamp : 0;

            $searchHaystack = implode(' | ', array_filter([
              $event->title,
              $venue,
              $code,
              $barangay,
              $districtLabel,
              $dateText,
              $timeText,
              $dayName,
            ]));

            $statusLabel = ucfirst($status);
            $eventId = $event->event_id ?? $event->id;
        @endphp

        <article class="em-event em-event-card"
                data-id="{{ $eventId }}"
                data-detail-url="{{ route('event.details.show', $event->event_id) }}"
                data-search="{{ $searchHaystack }}"
                data-title="{{ $event->title ?? 'Untitled Event' }}"
                data-date="{{ $dateText }}"
                data-time="{{ $timeText }}"
                data-venue="{{ $venue }}"
                data-code="{{ $code }}"
                data-status="{{ $statusLabel }}"
                data-status-key="{{ $status }}"
                data-district="{{ $districtId ? (string)$districtId : '' }}"
                data-barangay="{{ $barangay }}"
                data-month="{{ $month }}"
                data-day="{{ $dayName }}"
                data-sort-ts="{{ $ts }}"
                data-start-min="{{ $startMin }}"
                data-end-min="{{ $endMin }}"
        >
          <div class="em-event-top">
            <label class="em-select" title="Select for bulk delete">
              <input type="checkbox"
                    class="em-check"
                    value="{{ $eventId }}"
                    data-title="{{ $event->title ?? 'Untitled Event' }}"
                    aria-label="Select event {{ $event->title ?? 'Untitled Event' }}">
              <span class="em-check-ui" aria-hidden="true"></span>
            </label>

            <h3 class="em-event-title">{{ $event->title ?? 'Untitled Event' }}</h3>

            <span class="em-badge {{ $status }}">
              <span class="dot"></span> {{ $statusLabel }}
            </span>
          </div>

          <div class="em-event-body">
            <div class="em-meta">
              <div class="em-meta-row">
                <i class="fa-regular fa-calendar"></i>
                {{ $dateText }}
                @if($dayName)
                  <span class="em-day-pill">{{ $dayName }}</span>
                @endif
              </div>
              <div class="em-meta-row"><i class="fa-regular fa-clock"></i> {{ $timeText }}</div>
              <div class="em-meta-row">
                <i class="fa-solid fa-location-dot"></i>
                <span class="em-venue">{{ $venue }}</span>
                @if($districtLabel)
                  <span class="em-chip">{{ $districtLabel }}</span>
                @endif
              </div>
              @if($barangay)
                <div class="em-meta-row"><i class="fa-solid fa-map"></i> {{ $barangay }}</div>
              @endif
              <div class="em-meta-row"><i class="fa-solid fa-hashtag"></i> Code: <strong>{{ $code }}</strong></div>
            </div>

            <div class="em-actions">
              <a class="em-btn em-btn--primary" href="{{ route('event.details.show', $event->event_id) }}">
                <i class="fa-regular fa-eye"></i> View
              </a>

              @php
                $hasSummary = ($status === 'completed') || ($event->attendances_count ?? 0) > 0;
              @endphp

              @if($hasSummary)
                <a class="em-btn" href="{{ route('events.summary', $event->event_id) }}">
                  <i class="fa-regular fa-file-lines"></i> Summary
                </a>
              @endif
            </div>
          </div>
        </article>

        @php } @endphp

        {{-- Planned --}}
        <section class="em-pane" data-pane="planned" hidden>
          <div class="em-grid">
            @foreach($upcomingEvents as $event)
              @php emCard($event,'planned'); @endphp
            @endforeach
          </div>

          <div class="em-empty" data-empty hidden>
            <div class="em-empty-title">No upcoming events.</div>
            <div class="em-empty-sub">Planned events will appear here.</div>
          </div>

          <nav class="em-pagination" aria-label="Upcoming pagination">
            <ul class="pagination mb-0 em-pageArrows">
              <li class="page-item"><button class="page-link" type="button" data-page-prev><i class="fa-solid fa-chevron-left"></i></button></li>
              <li class="page-item disabled"><span class="page-link em-pageInfo" data-pageinfo>1 / 1</span></li>
              <li class="page-item"><button class="page-link" type="button" data-page-next><i class="fa-solid fa-chevron-right"></i></button></li>
            </ul>
          </nav>
        </section>

        {{-- Ongoing --}}
        <section class="em-pane" data-pane="ongoing" hidden>
          <div class="em-grid">
            @foreach($ongoingEvents as $event)
              @php emCard($event,'ongoing'); @endphp
            @endforeach
          </div>

          <div class="em-empty" data-empty hidden>
            <div class="em-empty-title">No ongoing events.</div>
            <div class="em-empty-sub">Events currently running will appear here.</div>
          </div>

          <nav class="em-pagination" aria-label="Ongoing pagination">
            <ul class="pagination mb-0 em-pageArrows">
              <li class="page-item"><button class="page-link" type="button" data-page-prev><i class="fa-solid fa-chevron-left"></i></button></li>
              <li class="page-item disabled"><span class="page-link em-pageInfo" data-pageinfo>1 / 1</span></li>
              <li class="page-item"><button class="page-link" type="button" data-page-next><i class="fa-solid fa-chevron-right"></i></button></li>
            </ul>
          </nav>
        </section>

        {{-- Completed --}}
        <section class="em-pane" data-pane="completed" hidden>
          <div class="em-grid">
            @foreach($completedEvents as $event)
              @php emCard($event,'completed'); @endphp
            @endforeach
          </div>

          <div class="em-empty" data-empty hidden>
            <div class="em-empty-title">No completed events yet.</div>
            <div class="em-empty-sub">Finished events will appear here.</div>
          </div>

          <nav class="em-pagination" aria-label="Completed pagination">
            <ul class="pagination mb-0 em-pageArrows">
              <li class="page-item"><button class="page-link" type="button" data-page-prev><i class="fa-solid fa-chevron-left"></i></button></li>
              <li class="page-item disabled"><span class="page-link em-pageInfo" data-pageinfo>1 / 1</span></li>
              <li class="page-item"><button class="page-link" type="button" data-page-next><i class="fa-solid fa-chevron-right"></i></button></li>
            </ul>
          </nav>
        </section>

        {{-- Cancelled --}}
        <section class="em-pane" data-pane="cancelled" hidden>
          <div class="em-grid">
            @foreach($cancelledEvents as $event)
              @php emCard($event,'cancelled'); @endphp
            @endforeach
          </div>

          <div class="em-empty" data-empty hidden>
            <div class="em-empty-title">No cancelled events.</div>
            <div class="em-empty-sub">Cancelled events will appear here.</div>
          </div>

          <nav class="em-pagination" aria-label="Cancelled pagination">
            <ul class="pagination mb-0 em-pageArrows">
              <li class="page-item"><button class="page-link" type="button" data-page-prev><i class="fa-solid fa-chevron-left"></i></button></li>
              <li class="page-item disabled"><span class="page-link em-pageInfo" data-pageinfo>1 / 1</span></li>
              <li class="page-item"><button class="page-link" type="button" data-page-next><i class="fa-solid fa-chevron-right"></i></button></li>
            </ul>
          </nav>
        </section>
      </main>
    </div>
  </div>

  <div class="em-toast" id="emToast" aria-live="polite"></div>

  {{-- Modals --}}
  <div class="modal fade" id="emModalConfirm" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content em-modal">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa-regular fa-trash-can me-2"></i> Confirm delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="em-confirm-lead">
            You are about to delete <strong><span id="emConfirmCount">0</span></strong> event(s).
          </div>
          <div class="text-muted small mt-1">This action cannot be undone.</div>
          <div class="em-confirm-list mt-3" id="emConfirmList"></div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>

          {{-- ✅ KEEP ONLY ONE bulk delete form (inside modal) --}}
          <form method="POST" action="{{ route('events.bulkDestroy') }}" id="emBulkDeleteForm" class="m-0">
            @csrf
            @method('DELETE')
            <div id="emBulkHiddenInputs"></div>
            <button type="submit" class="btn btn-danger" id="emConfirmDeleteBtn">Delete</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- ✅ Success modal now renders array payload (event details) --}}
  <div class="modal fade" id="emModalSuccess" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content em-modal">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fa-regular fa-circle-check me-2"></i>
            {{ $successIsArray ? ($successPayload['title'] ?? 'Success') : 'Success' }}
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          @if($successIsArray)
            <div class="fw-semibold mb-1">{{ $successPayload['message'] ?? 'Done.' }}</div>

            @php
              $et = $successPayload['event_title'] ?? null;
              $ec = $successPayload['event_code'] ?? null;
              $ed = $successPayload['event_date'] ?? null;
            @endphp

            @if($et || $ec || $ed)
              <div class="small text-muted">
                @if($et) <div><b>Title:</b> {{ $et }}</div> @endif
                @if($ec) <div><b>Code:</b> {{ $ec }}</div> @endif
                @if($ed) <div><b>Date:</b> {{ $ed }}</div> @endif
              </div>
            @endif
          @else
            {{ $successPayload ?? 'Done.' }}
          @endif
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="emModalNotice" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content em-modal">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            <span id="emNoticeTitle">Notice</span>
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="emNoticeBody">—</div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  {{-- ✅ Event Activity Log Modal (EventLogs only) --}}
  <div class="modal fade" id="emActivityModal" tabindex="-1" aria-labelledby="emActivityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable em-activity-dialog">
      <div class="modal-content">

        <div class="modal-header align-items-center">
          <div class="d-flex align-items-center gap-2">
            <h5 class="modal-title" id="emActivityLabel" style="margin:0;">
              <i class="fa-regular fa-clock me-2"></i>Activity Log
            </h5>
          </div>

          <div class="ms-auto d-flex align-items-center gap-3" style="flex-wrap:wrap;">

            <div class="d-flex align-items-center gap-2">
              <span class="small text-muted" style="font-weight:900;">Rows</span>

              <div class="em-dd hpLike" data-dd="log_rows">
                <button class="em-ddBtn" type="button">
                  <span data-dd-text>10</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>

                <div class="em-ddMenu" data-dd-menu>
                  <button class="em-ddItem" type="button" data-value="5">5</button>
                  <button class="em-ddItem" type="button" data-value="10">10</button>
                </div>
              </div>

              <input type="hidden" id="emLogRowsValue" value="10">
            </div>

            <div class="d-flex align-items-center gap-2" id="emLogPager">
              <button type="button" class="btn btn-outline-secondary btn-sm" id="emLogPrev">
                <i class="fa-solid fa-chevron-left"></i>
              </button>

              <span class="small text-muted" style="font-weight:900;" id="emLogPageInfo">1 / 1</span>

              <button type="button" class="btn btn-outline-secondary btn-sm" id="emLogNext">
                <i class="fa-solid fa-chevron-right"></i>
              </button>
            </div>

          </div>

          <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <form id="emLogFilterForm" class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <label class="form-label mb-1 small fw-semibold">Start date</label>
              <input type="date" name="log_start" class="form-control form-control-sm" value="{{ request('log_start') }}">
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label mb-1 small fw-semibold">End date</label>
              <input type="date" name="log_end" class="form-control form-control-sm" value="{{ request('log_end') }}">
            </div>

            <div class="col-12 col-md-4">
              <label class="form-label mb-1 small fw-semibold">Action</label>

              <div class="em-dd hpLike w-100" data-dd="log_action">
                <button class="em-ddBtn w-100 justify-content-between" type="button">
                  <span data-dd-text>All actions</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>

                <div class="em-ddMenu w-100" data-dd-menu>
                  <button class="em-ddItem" type="button" data-value="">All actions</button>
                  @foreach($eventLogActions as $action)
                    <button class="em-ddItem" type="button" data-value="{{ $action }}">{{ $action }}</button>
                  @endforeach
                </div>
              </div>

              <input type="hidden" name="log_action" id="emLogActionValue" value="{{ request('log_action') }}">
            </div>

            <div class="col-12">
              <label class="form-label mb-1 small fw-semibold">Search</label>

              <div class="input-group input-group-sm" style="margin-top:0;">
                <span class="input-group-text">
                  <i class="fa-solid fa-magnifying-glass"></i>
                </span>

                <input
                  id="emLogSearch"
                  type="text"
                  name="log_search"
                  class="form-control"
                  placeholder="Search by user, event, barangay, etc…"
                  autocomplete="off"
                  value="{{ request('log_search') }}"
                />

                <button class="btn btn-outline-secondary" id="emLogSearchClear" type="button" aria-label="Clear search">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </div>
          </form>

          <div class="table-responsive">
            <table class="table table-sm align-middle em-log-table">
              <thead class="table-light">
                <tr>
                  <th style="width: 170px;">Date &amp; Time</th>
                  <th style="width: 120px;">Action</th>
                  <th style="width: 150px;">User</th>
                  <th>Details</th>
                </tr>
              </thead>
              <tbody>
                @foreach(($eventLogs ?? []) as $log)
                  @php
                    $ts = $log->timestamp ?? $log->created_at ?? null;
                    $when = $ts ? $ts->format('Y-m-d H:i') : '';
                    $dateOnly = $ts ? $ts->format('Y-m-d') : '';
                    $adminName = optional($log->admin)->name ?? optional($log->admin)->username ?? '—';

                    $rawDetails = $log->details ?? '';
                    $decoded = null;

                    if (is_string($rawDetails) && trim($rawDetails) !== '') {
                      $decoded = json_decode($rawDetails, true);
                      if (json_last_error() !== JSON_ERROR_NONE) $decoded = null;
                    }

                    $evTitle = is_array($decoded) ? ($decoded['event']['title'] ?? null) : null;
                    $evCode  = is_array($decoded) ? ($decoded['event']['code'] ?? null) : null;

                    if ($evTitle && $evCode) {
                      $display = "Event: {$evTitle} (Code: {$evCode}) — {$log->action}";
                    } elseif ($evTitle) {
                      $display = "Event: {$evTitle} — {$log->action}";
                    } else {
                      $summary = is_array($decoded) ? ($decoded['summary'] ?? null) : null;
                      $display = $summary ?: (is_string($rawDetails) && trim($rawDetails) !== '' ? $rawDetails : '—');
                    }

                    $searchText = trim($when.' '.$log->action.' '.$adminName.' '.$display);
                  @endphp

                  <tr class="em-log-row"
                      data-action="{{ $log->action }}"
                      data-date="{{ $dateOnly }}"
                      data-search="{{ Str::lower($searchText) }}">
                    <td>{{ $when }}</td>
                    <td>{{ $log->action }}</td>
                    <td>{{ $adminName }}</td>
                    <td>
                      <div class="small text-muted" style="font-weight:800;">
                        {{ $display }}
                      </div>

                      @if(is_array($decoded))
                        <details class="mt-1">
                          <summary class="small" style="cursor:pointer; font-weight:800;">View JSON</summary>
                          <pre class="small mb-0" style="white-space:pre-wrap;">{{ json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                        </details>
                      @endif
                    </td>
                  </tr>
                @endforeach

              </tbody>
            </table>
          </div>

        </div>

        <div class="modal-footer">
          <button id="emLogResetBtn" type="button" class="btn btn-outline-secondary btn-sm">Reset</button>
          <button type="submit" form="emLogFilterForm" class="btn btn-primary btn-sm">Apply</button>
        </div>
      </div>
    </div>
  </div>

  {{-- ✅ Server flags now safely support array success using JSON --}}
  <div id="emServerFlags"
    data-has-success="{{ (session()->has('success') || session()->has('submit_success')) ? '1' : '0' }}"
    data-success-json='@json(session("success") ?? session("submit_success"))'
    data-has-error="{{ session()->has('error') ? '1' : '0' }}"
    data-error-msg="{{ session("error") ? e(session("error")) : "" }}"
    hidden>
  </div>

  {{-- Nothing to copy --}}
  <div class="modal fade" id="emModalNoCopy" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content em-modal">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            Nothing to copy
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          There are no visible or selected events to copy. Try selecting events or adjusting your filters.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Nothing to delete --}}
  <div class="modal fade" id="emModalNoDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content em-modal">
        <div class="modal-header">
          <h5 class="modal-title">
            <i class="fa-solid fa-triangle-exclamation me-2"></i>
            Nothing to delete
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          No events are selected. Please tick at least one checkbox before using bulk delete or select-all actions.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>

  @if(request('show_log_modal') === '1')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('emActivityModal');
        if (el && window.bootstrap) {
          var modal = bootstrap.Modal.getOrCreateInstance(el);
          modal.show();
        }
      });
    </script>
  @endif

  {{-- Data payloads for JS --}}
  <script>
    window.EM_DISTRICTS = @json($districts);
    window.EM_BARANGAYS_BY_DISTRICT = @json($barangaysByDistrict ?? []);
    window.EM_ALL_BARANGAYS = @json($allBarangays);
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('assets/event_manager/js/event_manager.js') }}"></script>
</body>
</html>
