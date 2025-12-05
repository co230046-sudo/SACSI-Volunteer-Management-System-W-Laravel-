@php
  $start = $event->start_datetime ? \Illuminate\Support\Carbon::parse($event->start_datetime) : null;
  $end   = $event->end_datetime ? \Illuminate\Support\Carbon::parse($event->end_datetime) : null;

  $dateText = $start ? $start->format('M d, Y') : 'Date TBA';

  $timeText = 'Time TBA';
  if($start && $end) $timeText = $start->format('h:i A') . ' - ' . $end->format('h:i A');
  elseif($start)     $timeText = $start->format('h:i A');

  $venue = $event->venue ?: '—';
  $code  = $event->event_code ?: '—';

  $barangay = trim((string) ($event->location?->barangay ?? ''));
  $districtId = (string) ($event->location?->district_id ?? $event->district_id ?? '');
  $districtLabel = $districtId !== '' ? "District {$districtId}" : '';

  $monthVal = $start ? (string) $start->month : '';

  $searchHaystack = implode(' | ', array_filter([
    $event->title,
    $venue,
    $code,
    $barangay,
    $districtLabel,
    $dateText,
    $timeText,
  ]));

  $statusLabel = ucfirst($status);
  $eventId = $event->event_id ?? $event->id;
  $sortTs = $start ? $start->timestamp : 0;
@endphp

<article class="em-event em-event-card"
  data-id="{{ $eventId }}"
  data-search="{{ $searchHaystack }}"
  data-title="{{ $event->title }}"
  data-sort-ts="{{ $sortTs }}"
  data-district="{{ $districtId }}"
  data-barangay="{{ strtolower($barangay) }}"
  data-month="{{ $monthVal }}"
  data-date="{{ $dateText }}"
  data-time="{{ $timeText }}"
  data-venue="{{ $venue }}"
  data-code="{{ $code }}"
  data-status="{{ $statusLabel }}"
>
  <div class="em-event-top">
    <label class="em-select" title="Select for bulk delete">
      <input type="checkbox"
        class="em-check"
        value="{{ $eventId }}"
        data-title="{{ $event->title }}"
        aria-label="Select event {{ $event->title }}">
      <span class="em-check-ui" aria-hidden="true"></span>
    </label>

    <h3 class="em-event-title">{{ $event->title ?? 'Untitled Event' }}</h3>

    <span class="em-badge {{ $status }}">
      <span class="dot"></span> {{ $statusLabel }}
    </span>
  </div>

  <div class="em-event-body">
    <div class="em-meta">
      <div class="em-meta-row"><i class="fa-regular fa-calendar"></i> {{ $dateText }}</div>
      <div class="em-meta-row"><i class="fa-regular fa-clock"></i> {{ $timeText }}</div>

      <div class="em-meta-row">
        <i class="fa-solid fa-location-dot"></i>
        <span>{{ $venue }}</span>
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

      @if(\Illuminate\Support\Facades\Route::has('events.summary'))
        <a class="em-btn" href="{{ route('events.summary', $event->event_id) }}">
          <i class="fa-regular fa-file-lines"></i> Summary
        </a>
      @endif
    </div>
  </div>
</article>
