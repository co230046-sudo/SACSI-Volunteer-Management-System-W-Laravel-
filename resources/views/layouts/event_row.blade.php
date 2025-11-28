
<td class="detail-box">
    <i class="fas fa-calendar-alt"></i>
    {{ \Carbon\Carbon::parse($event->start_datetime)->format('F d, Y') }}
</td>

<td class="detail-box">
    <i class="fas fa-clock"></i>
    {{ \Carbon\Carbon::parse($event->start_datetime)->format('h:i A') }} -
    {{ \Carbon\Carbon::parse($event->end_datetime)->format('h:i A') }}
</td>

<td class="detail-box">
    <i class="fas fa-map-marker-alt"></i>
    {{ $event->venue ?? 'No venue' }}
</td>

<td class="detail-box">
    <i class="fas fa-users"></i>
    <strong>{{ $event->expectedVolunteers->count() }} Volunteers</strong>
</td>

<td class="detail-box">
    <a class="detail-link" href="{{ route('event.details.show', $event->event_id) }}">
        See Details
    </a>
</td>

