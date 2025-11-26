<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACSI Volunteer Management System</title>

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('assets/homepage/css/homepage.css') }}">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome Icons -->
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
    </style>
</head>
<body>
    {{-- Universal Navbar --}}
    @extends('layouts.navbar')
    {{-- Page Loader --}}
    @include('layouts.page_loader')

    <div class="custom-container">

        {{-- Left Panel --}}
        <div class="left-panel">
            <a href="{{ route('volunteer.import.index') }}" class="card volunteer-import" data-tooltip="Upload volunteer lists from external files.">
                <i class="fa-solid fa-upload fa-3x"></i>
                <span>Volunteer Import</span>
            </a>

            <a href="{{ route('volunteers.list') }}" class="card volunteers" data-tooltip="View and manage all registered volunteers.">
                <i class="fa-solid fa-user-graduate fa-3x"></i>
                <span>Volunteers</span>
            </a>

            <a href="{{ route('events.create') }}" class="card new-event" data-tooltip="Create and post a new volunteer event.">
                <i class="fas fa-calendar-plus"></i>
                <span>New Event</span>
            </a>

            <a href="{{ route('events.manage') }}" class="card manage-events" data-tooltip="Edit, update, or delete existing events.">
                <i class="fa-solid fa-calendar-days fa-3x"></i>
                <span>Manage Events</span>
            </a>
        </div>

        {{-- Right Panel --}}
        <div class="right-panel">
            <div class="event-section">
                {{-- Ongoing Events --}}
                <h1 class="section-title">
                    <i class="fas fa-hourglass-half"></i>
                    <span>Ongoing Events</span>
                </h1>

                <hr class="section-divider">
                <div class="event-section-inner">
                    @forelse($ongoingEvents as $event)
                        <div class="event-card">
                            <div class="event-header">
                                <h3>{{ $event->title }}</h3>
                            </div>

                            <div class="event-details">

                                {{-- DATE RANGE --}}
                                <p>
                                    <i class="fas fa-calendar-alt"></i>
                                    {{ \Carbon\Carbon::parse($event->start_datetime)->format('F j, Y') }}
                                    –
                                    {{ \Carbon\Carbon::parse($event->end_datetime)->format('F j, Y') }}
                                </p>

                                {{-- TIME RANGE --}}
                                <p>
                                    <i class="fas fa-clock"></i>
                                    {{ $event->start_datetime ? \Carbon\Carbon::parse($event->start_datetime)->format('h:i A') : '' }}
                                    -
                                    {{ $event->end_datetime ? \Carbon\Carbon::parse($event->end_datetime)->format('h:i A') : '' }}
                                </p>

                                {{-- LOCATION --}}
                                <p>
                                    <i class="fas fa-map-marker-alt"></i>
                                    {{ $event->location?->barangay ?? 'No location' }}
                                </p>

                                {{-- VOLUNTEER COUNT --}}
                                <p>
                                    <i class="fas fa-users"></i>
                                    <strong>{{ $event->expectedVolunteers->count() }} Volunteers Expected</strong>
                                </p>

                                {{-- DETAILS PAGE BUTTON --}}
                                <a class="detail-link" href="{{ route('event.details.show', $event->event_id) }}">
                                    See Details
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="no-events">
                            <i class="fas fa-hourglass-half me-2"></i>No ongoing events
                        </p>
                    @endforelse

                </div>
            </div>

            
            <div class="event-section">
                {{-- Upcoming Events --}}
                <h1 class="section-title">
                    <i class="fas fa-calendar-check"></i>
                    <span>Upcoming Events</span>
                </h1>

                <hr class="section-divider">
                <div class="event-section-inner">
                    @forelse($upcomingEvents as $event)
                        <div class="event-card">
                            <div class="event-header">
                                <h3>{{ $event->title }}</h3>
                            </div>

                            <div class="event-details">

                                <p>
                                    <i class="fas fa-calendar-alt"></i>
                                    {{ \Carbon\Carbon::parse($event->start_datetime)->format('F j, Y') }}
                                </p>

                                <p>
                                    <i class="fas fa-clock"></i>
                                    {{ $event->start_datetime ? \Carbon\Carbon::parse($event->start_datetime)->format('h:i A') : '' }}
                                    -
                                    {{ $event->end_datetime ? \Carbon\Carbon::parse($event->end_datetime)->format('h:i A') : '' }}
                                </p>

                                <p>
                                    <i class="fas fa-map-marker-alt"></i>
                                    {{ $event->location?->barangay ?? 'No location' }}
                                </p>

                                <p>
                                    <i class="fas fa-users"></i>
                                    <strong>{{ $event->expectedVolunteers->count() }} Expected</strong>
                                </p>

                                <a class="detail-link" href="{{ route('event.details.show', $event->event_id) }}">
                        See Details
                    </a>
                            </div>
                        </div>
                    @empty
                        <p class="no-events">
                            <i class="fas fa-calendar-times me-2"></i>No upcoming events.
                        </p>
                    @endforelse

                </div>
            </div>
        </div>
    </div>

    <!-- Mouse Tooltip Logic -->
    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const tooltip = document.createElement("div");
        tooltip.classList.add("tooltip");
        document.body.appendChild(tooltip);

        document.querySelectorAll(".card").forEach(card => {
            card.addEventListener("mousemove", e => {
                const text = card.getAttribute("data-tooltip");
                if (text) {
                    tooltip.textContent = text;
                    tooltip.style.opacity = 1;
                    tooltip.style.left = e.pageX + "px";
                    tooltip.style.top = e.pageY - 20 + "px";
                }
            });
            card.addEventListener("mouseleave", () => {
                tooltip.style.opacity = 0;
            });
        });
    });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
