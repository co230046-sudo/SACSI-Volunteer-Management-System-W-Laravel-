<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SACSI Volunteer Management System</title>

  <link rel="stylesheet" href="{{ asset('assets/homepage/css/homepage.css') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    .tooltip {
      position: fixed;
      background-color: #333;
      color: #fff;
      padding: 6px 10px;
      border-radius: 6px;
      font-size: 14px;
      pointer-events: none;
      white-space: nowrap;
      opacity: 0;
      transform: translate(-50%, -120%);
      transition: opacity 0.15s ease;
      z-index: 9999;
    }

    /* ✅ Overlay panel anchored to right-panel */
    .right-panel { position: relative; }

    /* ✅ Make Filter & Sort panel overlay (doesn't push content) */
    #hpPanel.hp-panel{
      position: absolute;
      right: 16px;
      top: 78px; /* tweak if needed */
      width: min(720px, calc(100% - 32px));
      z-index: 80;
    }

    /* ✅ Barangay search inside dropdown menu (sticky header) */
    .hp-ddMenu .hp-ddSearchWrap{
      position: sticky;
      top: 0;
      z-index: 2;
      background: #fff;
      padding: 8px;
      border-bottom: 1px solid rgba(16,24,40,.10);
      border-top-left-radius: 12px;
      border-top-right-radius: 12px;
      margin: -6px -6px 6px; /* align with menu padding */
    }
    .hp-ddMenu .hp-ddSearch{
      display:flex;
      align-items:center;
      gap: 10px;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid rgba(178,58,69,.18);
      background:#f9fafb;
    }
    .hp-ddMenu .hp-ddSearch input{
      border: 0;
      outline: 0;
      background: transparent;
      width: 100%;
      font-weight: 850;
    }
    .hp-ddMenu .hp-ddSearch i{
      color: rgba(143,42,51,1);
      opacity: .95;
    }
  </style>
</head>
<body>
  @include('layouts.navbar')
  @include('layouts.page_loader')

  <div class="custom-container">

    {{-- Left Panel --}}
    <div class="left-panel">
      <a href="{{ route('volunteer.import.index') }}"
         class="card volunteer-import"
         data-tooltip="Upload volunteer lists from external files.">
        <i class="fa-solid fa-upload"></i>
        <span>Volunteer Import</span>
      </a>

      <a href="{{ route('volunteers.list') }}"
         class="card volunteers"
         data-tooltip="View and manage all registered volunteers.">
        <i class="fa-solid fa-user-graduate"></i>
        <span>Volunteers</span>
      </a>

      <a href="{{ route('events.create') }}"
         class="card new-event"
         data-tooltip="Create and post a new volunteer event.">
        <i class="fas fa-calendar-plus"></i>
        <span>New Event</span>
      </a>

      <a href="{{ route('events.manage') }}"
         class="card manage-events"
         data-tooltip="Edit, update, or delete existing events.">
        <i class="fa-solid fa-calendar-days"></i>
        <span>Manage Events</span>
      </a>
    </div>

    {{-- Right Panel --}}
    <div class="right-panel"
         id="hpRoot"
         data-default-tab="ongoing"
         data-barangays='@json($barangaysByDistrict)'>

      {{-- TOP ROW --}}
      <div class="hp-top">
        <div class="hp-tabs" role="tablist">
          <button type="button" class="hp-tab is-active" data-tab="ongoing" aria-selected="true">
            <i class="fas fa-hourglass-half"></i>
            Ongoing <span class="hp-pill">{{ $ongoingEvents->count() }}</span>
          </button>
          <button type="button" class="hp-tab" data-tab="upcoming" aria-selected="false">
            <i class="fas fa-calendar-check"></i>
            Upcoming <span class="hp-pill">{{ $upcomingEvents->count() }}</span>
          </button>
        </div>

        <div class="hp-actions">
          {{-- ✅ Search w/ autosuggest --}}
          <div class="hp-search hp-search--suggest" id="hpSearchWrap">
            <button class="hp-searchBtn" id="hpSearchBtn" type="button" aria-label="Search">
              <i class="fa-solid fa-magnifying-glass"></i>
            </button>

            <input id="hpSearch" type="text"
                   placeholder="Search title, barangay, date, time…"
                   autocomplete="off">

            <button class="hp-clearX" id="hpSearchClear" type="button" aria-label="Clear search">
              <i class="fa-solid fa-xmark"></i>
            </button>

            {{-- ✅ Main autosuggest dropdown --}}
            <div class="hp-suggestBox hp-suggestBox--main" id="hpMainSuggest" hidden></div>
          </div>

          <button type="button" class="hp-filterBtn" id="hpFilterToggle" aria-expanded="false">
            <i class="fa-solid fa-sliders"></i>
            Filter &amp; Sort
            <i class="fa-solid fa-chevron-down hp-chev"></i>
          </button>
        </div>
      </div>

      {{-- Filter & Sort Panel OVERLAY --}}
      <div class="hp-panel" id="hpPanel" hidden>
        <div class="hp-panelGrid">

          {{-- Sort --}}
          <div class="hp-field">
            <div class="hp-fieldLabel">
              <i class="fa-solid fa-arrow-down-wide-short"></i>
              Sort by
            </div>

            <div class="hp-dd" data-dd="sort">
              <button class="hp-ddBtn" type="button">
                <span class="hp-ddText" data-dd-text>Sort by Date (Soonest)</span>
                <i class="fa-solid fa-chevron-down"></i>
              </button>
              <div class="hp-ddMenu" data-dd-menu>
                <button type="button" class="hp-ddItem" data-value="date_asc">Sort by Date (Soonest)</button>
                <button type="button" class="hp-ddItem" data-value="date_desc">Sort by Date (Latest)</button>

                {{-- ✅ NEW --}}
                <button type="button" class="hp-ddItem" data-value="time_asc">Sort by Time (Soonest)</button>
                <button type="button" class="hp-ddItem" data-value="time_desc">Sort by Time (Latest)</button>
                <button type="button" class="hp-ddItem" data-value="week_asc">Sort by Week (Soonest)</button>
                <button type="button" class="hp-ddItem" data-value="week_desc">Sort by Week (Latest)</button>

                <button type="button" class="hp-ddItem" data-value="title_asc">Sort by Title (A–Z)</button>
                <button type="button" class="hp-ddItem" data-value="title_desc">Sort by Title (Z–A)</button>
              </div>
            </div>
          </div>

          {{-- District --}}
          <div class="hp-field">
            <div class="hp-fieldLabel">
              <i class="fa-solid fa-house-chimney"></i>
              Filter by District
            </div>

            <div class="hp-dd" data-dd="district">
              <button class="hp-ddBtn" type="button">
                <span class="hp-ddText" data-dd-text>All Districts</span>
                <i class="fa-solid fa-chevron-down"></i>
              </button>
              <div class="hp-ddMenu" data-dd-menu>
                <button type="button" class="hp-ddItem" data-value="">All Districts</button>
                <button type="button" class="hp-ddItem" data-value="1">District I</button>
                <button type="button" class="hp-ddItem" data-value="2">District II</button>
              </div>
            </div>
          </div>

          {{-- Barangay --}}
          <div class="hp-field">
            <div class="hp-fieldLabel">
              <i class="fa-solid fa-location-dot"></i>
              Filter by Barangay
            </div>

            <div class="hp-dd" data-dd="barangay">
              <button class="hp-ddBtn" type="button">
                <span class="hp-ddText" data-dd-text>All Barangays</span>
                <i class="fa-solid fa-chevron-down"></i>
              </button>

              <div class="hp-ddMenu" data-dd-menu id="hpBarangayMenu">
                {{-- injected by JS --}}
              </div>
            </div>
          </div>

          {{-- Month --}}
          <div class="hp-field">
            <div class="hp-fieldLabel">
              <i class="fa-solid fa-calendar"></i>
              Filter by Month
            </div>

            <div class="hp-dd" data-dd="month">
              <button class="hp-ddBtn" type="button">
                <span class="hp-ddText" data-dd-text>All Months</span>
                <i class="fa-solid fa-chevron-down"></i>
              </button>
              <div class="hp-ddMenu" data-dd-menu>
                <button type="button" class="hp-ddItem" data-value="">All Months</button>
                <button type="button" class="hp-ddItem" data-value="01">Jan</button>
                <button type="button" class="hp-ddItem" data-value="02">Feb</button>
                <button type="button" class="hp-ddItem" data-value="03">Mar</button>
                <button type="button" class="hp-ddItem" data-value="04">Apr</button>
                <button type="button" class="hp-ddItem" data-value="05">May</button>
                <button type="button" class="hp-ddItem" data-value="06">Jun</button>
                <button type="button" class="hp-ddItem" data-value="07">Jul</button>
                <button type="button" class="hp-ddItem" data-value="08">Aug</button>
                <button type="button" class="hp-ddItem" data-value="09">Sep</button>
                <button type="button" class="hp-ddItem" data-value="10">Oct</button>
                <button type="button" class="hp-ddItem" data-value="11">Nov</button>
                <button type="button" class="hp-ddItem" data-value="12">Dec</button>
              </div>
            </div>
          </div>

        </div>

        <div class="hp-panelActions">
          <button type="button" class="hp-reset" id="hpReset">Reset</button>
          <button type="button" class="hp-apply" id="hpApply">Apply</button>
        </div>
      </div>

      {{-- Pane: ONGOING --}}
      <section class="hp-pane" data-pane="ongoing">
        <div class="hp-section">
          <div class="hp-sectionHead">
            <div class="hp-sectionIcon"><i class="fas fa-hourglass-half"></i></div>
            <div>
              <div class="hp-sectionTitle">Ongoing Events</div>
              <div class="hp-sectionSub">Currently active events</div>
            </div>
          </div>

          <div class="hp-divider"></div>

          <div class="hp-list" id="hpOngoingList">
            @forelse ($ongoingEvents as $event)
              @php
                $start = $event->start_datetime ? \Carbon\Carbon::parse($event->start_datetime) : null;
                $end   = $event->end_datetime ? \Carbon\Carbon::parse($event->end_datetime) : null;

                $dateText = ($start && $end)
                  ? ($start->format('F j, Y') . ' – ' . $end->format('F j, Y'))
                  : ($start ? $start->format('F j, Y') : 'Date TBA');

                $dayName = $start ? $start->format('l') : '';

                $timeText = 'Time TBA';
                if ($start && $end) $timeText = $start->format('h:i A') . ' - ' . $end->format('h:i A');
                elseif ($start)     $timeText = $start->format('h:i A');

                $barangay = $event->location?->barangay ?? 'No barangay';
                $districtId = (string) ($event->location?->district_id ?? '');
                $districtLabel = $districtId === '1' ? 'District I' : ($districtId === '2' ? 'District II' : 'No district');

                $title = $event->title ?? 'Untitled Event';

                $hay = strtolower($title.' | '.$barangay.' | '.$districtLabel.' | '.$dateText.' | '.$dayName.' | '.$timeText);

                $sortDate = $start ? $start->timestamp : 0;
                $monthNum = $start ? $start->format('m') : '';

                $startMin = $start ? ((int)$start->format('H') * 60 + (int)$start->format('i')) : -1;

                $weekKey = $start ? $start->format('o-\WW') : ''; // ISO year-week, ex: 2025-W02
              @endphp

              <div class="event-card hp-event"
                   data-title="{{ e($title) }}"
                   data-date="{{ $sortDate }}"
                   data-date-text="{{ e($dateText) }}"
                   data-day="{{ e($dayName) }}"
                   data-time-text="{{ e($timeText) }}"
                   data-start-min="{{ $startMin }}"
                   data-week="{{ e($weekKey) }}"
                   data-month="{{ $monthNum }}"
                   data-district="{{ $districtId }}"
                   data-barangay="{{ e(strtolower($barangay)) }}"
                   data-hay="{{ e($hay) }}">
                <div class="event-header">
                  <h3>{{ $title }}</h3>
                </div>

                <div class="event-details">
                  <div class="ev-row">
                    <p>
                      <i class="fas fa-calendar-alt"></i>
                      {{ $dateText }}
                      @if($dayName)
                        <span class="hp-day-pill">{{ $dayName }}</span>
                      @endif
                    </p>

                    <p>
                      <i class="fas fa-clock"></i>
                      {{ $timeText }}
                    </p>

                    <p class="ev-loc">
                      <i class="fas fa-map-marker-alt"></i>
                      <span class="ev-barangay">{{ $barangay }}</span>
                      <span class="ev-district">{{ $districtLabel }}</span>
                    </p>

                    <p>
                      <i class="fas fa-users"></i>
                      <strong>{{ $event->expected_volunteers_count }} Volunteers Expected</strong>
                    </p>
                  </div>

                  <a class="detail-link" href="{{ route('event.details.show', $event->event_id) }}">
                    <i class="fa-regular fa-eye me-1"></i> See Details
                  </a>
                </div>
              </div>
            @empty
              <div class="hp-empty">
                <i class="fas fa-hourglass-half me-2"></i>No ongoing events
              </div>
            @endforelse
          </div>
        </div>
      </section>

      {{-- Pane: UPCOMING --}}
      <section class="hp-pane" data-pane="upcoming" hidden>
        <div class="hp-section">
          <div class="hp-sectionHead">
            <div class="hp-sectionIcon"><i class="fas fa-calendar-check"></i></div>
            <div>
              <div class="hp-sectionTitle">Upcoming Events</div>
              <div class="hp-sectionSub">Events scheduled soon</div>
            </div>
          </div>

          <div class="hp-divider"></div>

          <div class="hp-list" id="hpUpcomingList">
            @forelse ($upcomingEvents as $event)
              @php
                $start = $event->start_datetime ? \Carbon\Carbon::parse($event->start_datetime) : null;
                $end   = $event->end_datetime ? \Carbon\Carbon::parse($event->end_datetime) : null;

                $dateText = $start ? $start->format('F j, Y') : 'Date TBA';
                $dayName  = $start ? $start->format('l') : '';

                $timeText = 'Time TBA';
                if ($start && $end) $timeText = $start->format('h:i A') . ' - ' . $end->format('h:i A');
                elseif ($start)     $timeText = $start->format('h:i A');

                $barangay = $event->location?->barangay ?? 'No barangay';
                $districtId = (string) ($event->location?->district_id ?? '');
                $districtLabel = $districtId === '1' ? 'District I' : ($districtId === '2' ? 'District II' : 'No district');

                $title = $event->title ?? 'Untitled Event';

                $hay = strtolower($title.' | '.$barangay.' | '.$districtLabel.' | '.$dateText.' | '.$dayName.' | '.$timeText);

                $sortDate = $start ? $start->timestamp : 0;
                $monthNum = $start ? $start->format('m') : '';

                $startMin = $start ? ((int)$start->format('H') * 60 + (int)$start->format('i')) : -1;
                $weekKey = $start ? $start->format('o-\WW') : '';
              @endphp

              <div class="event-card hp-event"
                   data-title="{{ e($title) }}"
                   data-date="{{ $sortDate }}"
                   data-date-text="{{ e($dateText) }}"
                   data-day="{{ e($dayName) }}"
                   data-time-text="{{ e($timeText) }}"
                   data-start-min="{{ $startMin }}"
                   data-week="{{ e($weekKey) }}"
                   data-month="{{ $monthNum }}"
                   data-district="{{ $districtId }}"
                   data-barangay="{{ e(strtolower($barangay)) }}"
                   data-hay="{{ e($hay) }}">
                <div class="event-header">
                  <h3>{{ $title }}</h3>
                </div>

                <div class="event-details">
                  <div class="ev-row">
                    <p>
                      <i class="fas fa-calendar-alt"></i>
                      {{ $dateText }}
                      @if($dayName)
                        <span class="hp-day-pill">{{ $dayName }}</span>
                      @endif
                    </p>

                    <p><i class="fas fa-clock"></i> {{ $timeText }}</p>

                    <p class="ev-loc">
                      <i class="fas fa-map-marker-alt"></i>
                      <span class="ev-barangay">{{ $barangay }}</span>
                      <span class="ev-district">{{ $districtLabel }}</span>
                    </p>

                    <p><i class="fas fa-users"></i>
                      <strong>{{ $event->expected_volunteers_count }} Volunteers Expected</strong>
                    </p>
                  </div>

                  <a class="detail-link" href="{{ route('event.details.show', $event->event_id) }}">
                    <i class="fa-regular fa-eye me-1"></i> See Details
                  </a>
                </div>
              </div>
            @empty
              <div class="hp-empty">
                <i class="fas fa-calendar-times me-2"></i>No upcoming events.
              </div>
            @endforelse
          </div>
        </div>
      </section>

    </div>
  </div>

  <script>
    window.LOCATIONS = @json($locationsForJs);
    window.BARANGAYS_BY_DISTRICT = @json($barangaysByDistrict);
  </script>

  {{-- Tooltip Logic (keep inline) --}}
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      const tooltip = document.createElement("div");
      tooltip.classList.add("tooltip");
      document.body.appendChild(tooltip);

      document.querySelectorAll(".left-panel .card").forEach(card => {
        card.addEventListener("mousemove", e => {
          const text = card.getAttribute("data-tooltip");
          if (text) {
            tooltip.textContent = text;
            tooltip.style.opacity = 1;
            tooltip.style.left = e.pageX + "px";
            tooltip.style.top = (e.pageY - 20) + "px";
          }
        });
        card.addEventListener("mouseleave", () => tooltip.style.opacity = 0);
      });
    });
  </script>

  {{-- Homepage JS (external) --}}
  <script src="{{ asset('assets/js/script.js') }}"></script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
