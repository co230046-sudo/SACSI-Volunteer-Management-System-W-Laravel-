<?php $pageTitle = 'Event Summary Report'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $pageTitle }}</title>

  <link rel="stylesheet" href="{{ asset('assets/event_summary/css/styles.css') }}">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body>
  @include('layouts.page_loader')
  @include('layouts.navbar')
  @include('layouts.back_button')

  {{-- toast --}}
  <div class="toast-lite" id="toastLite" aria-live="polite" aria-atomic="true"></div>

  @php
    $status = strtolower($event->status ?? 'planned');
    $statusLabel = ucfirst($status);

    $expectedCount  = (int)($expectedCount ?? 0);
    $attendedCount  = (int)($attendedCount ?? 0);
    $attendanceRate = (int)($attendanceRate ?? 0);

    $capacity = (!empty($maxVolunteers) && (int)$maxVolunteers > 0) ? (int)$maxVolunteers : null;
    $capacityUsed = $capacityUsed ?? null;

    $mode = ($chartMode ?? 'actual');
    $mode = in_array($mode, ['expected','actual'], true) ? $mode : 'actual';
    $modeLabel = $mode === 'actual' ? 'Attended' : 'Expected';

    $hasAttendanceImport = (bool)($hasAttendanceImport ?? false);
    $importedTotal = $attendanceImportedTotal ?? null;

    // Attendees tile denominator:
    // If capacity exists, show attended/capacity; else attended/expected
    $attendeesDenominator = !is_null($capacity) ? $capacity : $expectedCount;
    $attendeesSubtitle = !is_null($capacity) ? 'present / max volunteers' : 'present / expected list';
    $attendeesTitle = 'Attendees';

    $eventSummaryMeta = [
      'title' => $event->title ?? '',
      'event_code' => $event->event_code ?? '',
      'status' => $event->status ?? 'planned',
      'date' => $event->start_datetime?->format('Y-m-d') ?? '',
      'start_time' => $event->start_datetime?->format('H:i') ?? '',
      'end_time' => $event->end_datetime?->format('H:i') ?? '',
      'venue' => $event->venue ?? '',
      'barangay' => $event->location?->barangay ?? '',
      'district' => $event->district_id ?? '',
      'expected' => $expectedCount,
      'attended' => $attendedCount,
      'attendance_rate' => $attendanceRate,
      'capacity' => $capacity,
      'capacity_used' => $capacityUsed,
      'has_attendance_import' => (bool)$hasAttendanceImport,
      'attendance_imported_total' => $importedTotal,
      'chart_mode' => $mode,
      'generated_at' => now()->format('Y-m-d H:i:s'),
    ];
  @endphp

  <section class="summary-wrap">
    <div class="summary-grid--split">

      {{-- LEFT: SUMMARY CARD --}}
      <div class="summary-col--main">
        <div class="summary-card" id="summaryCard">

          {{-- TOP BAR --}}
            <header class="summary-top">
            <div class="top-left">
                <div class="page-kicker">
                <i class="fa-regular fa-file-lines"></i>
                Event Summary Report
                </div>

                <div class="top-actions-row">
                <div class="title-and-details">
                    <div class="title-row">
                      <h1 class="event-h1">{{ $event->title ?? 'Untitled Event' }}</h1>

                      <span class="status-badge status-{{ $status }}">
                          <span class="dot"></span>
                          {{ $statusLabel }}
                      </span>

                      <span class="mini-badge mini-badge--ok">
                          <i class="fa-solid fa-cloud-arrow-up"></i> Attendance imported
                      </span>
                    </div>

                    <div class="chips">
                    <div class="chip">
                        <i class="fa-regular fa-calendar"></i>
                        {{ $event->start_datetime?->format('F d, Y') ?? 'Date TBA' }}
                    </div>

                    <div class="chip">
                        <i class="fa-regular fa-clock"></i>
                        @if($event->start_datetime && $event->end_datetime)
                        {{ $event->start_datetime->format('h:i A') }} - {{ $event->end_datetime->format('h:i A') }}
                        @elseif($event->start_datetime)
                        {{ $event->start_datetime->format('h:i A') }}
                        @else
                        Time TBA
                        @endif
                    </div>

                    <div class="chip">
                        <i class="fa-solid fa-location-dot"></i>
                        {{ $event->venue ?: '—' }}
                    </div>

                    </div>

                    <div class="subchips">
                      <span class="subchip">
                          <i class="fa-solid fa-map"></i>
                          {{ $event->location?->barangay ?? 'No barangay set' }}
                      </span>
                      <span class="subchip">
                          <i class="fa-solid fa-building"></i>
                          {{ $event->district_id ? "District {$event->district_id}" : 'No district set' }}
                      </span>
                      @if(!is_null($capacity))
                          <span class="subchip">
                          <i class="fa-solid fa-user-group"></i>
                          Max: {{ $capacity }}
                          </span>
                      @endif

                      <button type="button"
                              class="chip chip-btn"
                              id="eventCodeCopy"
                              data-code="{{ $event->event_code ?? '' }}"
                              title="Copy Event Code">
                          <i class="fa-solid fa-hashtag"></i>
                          Code: <strong>{{ $event->event_code ?? '—' }}</strong>
                          <span class="chip-ico"><i class="fa-regular fa-copy"></i></span>
                      </button>
                    </div>
                </div>

                {{-- RIGHT ACTIONS --}}
                <div class="top-right">
                    <div class="export">
                    <button type="button" class="btn-export" id="exportBtn" aria-expanded="false">
                        <i class="fa-solid fa-arrow-up-from-bracket"></i>
                        Export
                        <i class="fa-solid fa-chevron-down export-caret"></i>
                    </button>

                    <div class="export-menu" id="exportMenu" role="menu" aria-hidden="true">
                        <button type="button" class="export-item" id="exportPrint">
                        <i class="fa-solid fa-print"></i> Print Report
                        </button>
                        <button type="button" class="export-item" id="exportPDF">
                        <i class="fa-regular fa-file-pdf"></i> Download PDF
                        </button>
                        <button type="button" class="export-item" id="exportCSV">
                        <i class="fa-solid fa-file-csv"></i> Export CSV
                        </button>
                    </div>
                    </div>

                    <a class="btn-softlink" href="{{ route('event.details.show', $event->event_id) }}">
                    <i class="fa-regular fa-eye"></i> View Event
                    </a>
                </div>
                </div>
            </div>
            </header>


          {{-- EXPORT TARGET --}}
          <div id="summaryReport">
            <div id="printArea">

              <div class="print-only print-head">
                <div class="print-title">{{ $event->title ?? 'Untitled Event' }}</div>
                <div class="print-sub">
                  Code: {{ $event->event_code ?? '—' }} •
                  Status: {{ $statusLabel }} •
                  Generated: {{ now()->format('M d, Y h:i A') }}
                </div>
              </div>

              <div class="summary-grid">
                <aside class="summary-left">
                  <div class="panel">
                    <div class="panel-head">
                      <div class="panel-title"><i class="fa-solid fa-chart-pie"></i> Distribution</div>
                      <div class="panel-sub text-muted small" id="chartHint">
                        {{ $chartHint ?? 'Based on imported attendance (present)' }}
                      </div>
                    </div>

                    <div class="chart-shell">
                      <div class="chart" aria-label="Volunteer distribution chart"></div>
                      <div class="chart-center">
                        <div class="chart-center-top" id="chartCenterTop">—</div>
                        <div class="chart-center-sub" id="chartCenterSub">{{ $modeLabel }}</div>
                      </div>
                      <div class="chart-tooltip" id="chartTooltip"></div>
                    </div>

                    <div class="chart-legend" id="chartLegend"></div>

                    {{-- ✅ SERVER RENDERS THIS. JS must NOT overwrite. --}}
                    <div class="chart-note text-muted small" id="chartNote">
                      <div class="note-row">
                        <strong>Roster:</strong>
                        <span>{{ $expectedCount }} expected • {{ $attendedCount }} attended • {{ $attendanceRate }}%</span>
                      </div>
                      @if(!is_null($capacity))
                        <div class="note-row">
                          <strong>Capacity:</strong>
                          <span>{{ $expectedCount }}/{{ $capacity }} • {{ $capacityUsed ?? 0 }}% used</span>
                        </div>
                      @endif
                    </div>
                  </div>
                </aside>

                <main class="summary-right">
                  <div class="panel">
                    <div class="panel-head">
                      <div class="panel-title"><i class="fa-solid fa-gauge-high"></i> Overview</div>
                      <div class="panel-sub text-muted small">Quick stats for this event</div>
                    </div>

                    <div class="stats-row stats-row--tight">
                      <div class="stat-box stat-box--tight stat-box--primary">
                        <div class="stat-top"><i class="fa-solid fa-users"></i> {{ $attendeesTitle }}</div>
                        <div class="stat-value">{{ $attendedCount }}/{{ $attendeesDenominator }}</div>
                        <div class="stat-sub text-muted small">{{ $attendeesSubtitle }}</div>
                      </div>

                      <div class="stat-box stat-box--tight">
                        <div class="stat-top"><i class="fa-solid fa-chart-line"></i> Attendance Rate</div>
                        <div class="stat-value">{{ $attendanceRate }}%</div>
                        <div class="stat-sub text-muted small">based on expected list</div>
                      </div>

                      @if(!is_null($capacity))
                        <div class="stat-box stat-box--tight">
                          <div class="stat-top"><i class="fa-solid fa-user-group"></i> Capacity</div>
                          <div class="stat-value">{{ $expectedCount }}/{{ $capacity }}</div>
                          <div class="stat-sub text-muted small">expected / max volunteers</div>
                        </div>

                        <div class="stat-box stat-box--tight">
                          <div class="stat-top"><i class="fa-solid fa-battery-half"></i> Capacity Used</div>
                          <div class="stat-value">{{ (int)($capacityUsed ?? 0) }}%</div>
                          <div class="stat-sub text-muted small">based on expected vs max</div>
                        </div>
                      @endif
                    </div>
                  </div>
                </main>
              </div>

            </div>
          </div>

        </div>
      </div>

      {{-- RIGHT: COMMENTS --}}
        {{-- RIGHT: COMMENTS --}}
<aside class="summary-col--side">
  <details class="comments-drawer" id="commentsDrawer" open>
    <summary class="panel panel--summary">
      <div class="panel-head panel-head--clickable" style="margin:0;">
        <div class="panel-title"><i class="fa-regular fa-comment-dots"></i> Comments</div>
        <div class="panel-sub text-muted small">Feedback from volunteers</div>
        <i class="fa-solid fa-chevron-down panel-chev"></i>
      </div>
    </summary>

    <div class="panel comments-panel">
      <div class="comments-body">
        @forelse(($feedbacks ?? []) as $fb)
          @php
            $name = $fb->volunteer_name ?? ($fb->volunteer?->full_name ?? 'Unknown Volunteer');
            $url  = $fb->profile_url ?? null;
            $img  = $fb->avatar ?? asset('storage/defaults/default_user.png');
            $qa   = is_array($fb->qa ?? null) ? $fb->qa : [];
            $ts   = $fb->submitted_at ?? $fb->created_at ?? null;
          @endphp

          {{-- collapsible per volunteer --}}
          <details class="comment-item">
            <summary class="comment-summary">
              <div class="comment-head">
                @if(!empty($url))
                  <a class="comment-avatar" href="{{ $url }}" title="View profile">
                    <img src="{{ $img }}"
                         alt="{{ $name }}"
                         loading="lazy"
                         onerror="this.onerror=null;this.src='{{ asset('storage/defaults/default_user.png') }}';">
                  </a>
                @else
                  <div class="comment-avatar" aria-hidden="true">
                    <img src="{{ $img }}"
                         alt=""
                         loading="lazy"
                         onerror="this.onerror=null;this.src='{{ asset('storage/defaults/default_user.png') }}';">
                  </div>
                @endif

                <div class="comment-head-main">
                  <div class="comment-title-row">
                    <div class="comment-name">
                      @if(!empty($url))
                        <a href="{{ $url }}" class="comment-name-link">{{ $name }}</a>
                      @else
                        {{ $name }}
                      @endif
                    </div>

                    <i class="fa-solid fa-chevron-down comment-item-chev" aria-hidden="true"></i>
                  </div>

                  <div class="comment-meta">
                    @if(!is_null($fb->rating))
                      <span class="rating-pill">
                        <i class="fa-solid fa-star"></i> {{ $fb->rating }}/5
                      </span>
                    @endif

                    @if($ts)
                      <span class="time text-muted">
                        {{ \Illuminate\Support\Carbon::parse($ts)->format('M d, Y h:i A') }}
                      </span>
                    @endif
                  </div>

                  <div class="comment-preview text-muted small">Comments</div>
                </div>
              </div>
            </summary>

            <div class="comment-detail">
              @if(!empty($qa))
                <div class="comment-qa">
                  @foreach($qa as $row)
                    @php
                      $q = $row['q'] ?? null;
                      $a = $row['a'] ?? null;
                    @endphp
                    @continue(empty($q) && empty($a))

                    <div class="qa-row">
                      <div class="qa-q">{{ $q ? rtrim($q, ':') . ':' : '—' }}</div>
                      <div class="qa-a">{{ (is_null($a) || trim((string)$a)==='') ? 'None.' : $a }}</div>
                    </div>
                  @endforeach
                </div>
              @else
                <div class="comment-text">
                  {{ $fb->feedback_text ?? '—' }}
                </div>
              @endif
            </div>
          </details>

        @empty
          <div class="empty-comment">
            <div class="empty-ico"><i class="fa-regular fa-face-smile"></i></div>
            <div class="empty-title">No comments yet.</div>
            <div class="empty-sub text-muted small">Feedback will appear here after the event.</div>
          </div>
        @endforelse
      </div>
    </div>
  </details>
</aside>


    </div>
  </section>

  {{-- Data for JS --}}
  <script>
    window.EVENT_SUMMARY_CHART = @json($chartData ?? []);
    window.EVENT_SUMMARY_TOTAL_EXPECTED = @json($expectedCount ?? 0);
    window.EVENT_SUMMARY_TOTAL_ATTENDED = @json($attendedCount ?? 0);
    window.EVENT_SUMMARY_META = @json($eventSummaryMeta);
    window.EVENT_SUMMARY_CHART_MODE = @json($chartMode ?? 'actual');
    window.EVENT_SUMMARY_CHART_HINT = @json($chartHint ?? 'Based on imported attendance (present)');
  </script>

  {{-- PDF LIBRARIES --}}
  <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js"></script>

  {{-- remember comments open/closed --}}
  <script>
    (function(){
      const el = document.getElementById('commentsDrawer');
      if (!el) return;
      const key = 'event_summary_comments_open';
      try {
        const saved = localStorage.getItem(key);
        if (saved === '0') el.removeAttribute('open');
        el.addEventListener('toggle', () => {
          localStorage.setItem(key, el.open ? '1' : '0');
        });
      } catch {}
    })();
  </script>

  <script src="{{ asset('assets/event_summary/js/script.js') }}"></script>
</body>
</html>
