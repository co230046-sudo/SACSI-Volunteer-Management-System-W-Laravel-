<?php 
    $pageTitle = "Event Manager"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Manager - Pre & Post Events</title>

    <!-- Main CSS (Your updated version preserved exactly) -->
    <style>
        /* ======== YOUR CSS — UNCHANGED (PASTED EXACTLY AS GIVEN) ======== */
        /* Reset and Base Styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-snap-type: y mandatory; scroll-behavior: smooth; }
        body {
            margin: 0; height: 100vh; overflow: hidden; padding-top: 60px;
            display: flex; flex-direction: column;
        }

        section {
            min-height: calc(100vh - 80px);
            height: auto;
            scroll-snap-align: start;
            display: flex; flex-direction: column;
            position: relative;
            overflow-y: auto; overflow-x: hidden;
            padding: 1rem 0;
        }

        #UpcomingEvents-Section, #OngoingEvents-Section, #CompletedEvent-Section, #CancelledEvent-Section {
            display: block; opacity: 1; visibility: visible; overflow-y: auto;
        }

        .database-container { flex: 1; background: #fff; padding: 0 1rem; display: flex; flex-direction: column; }
        .database-main { height: 800px; padding: 1rem; max-width: 1650px; width: 100%; margin: 0 auto; display: flex; flex-direction: column; }

        .import-section {
            position: relative; background: #fff; border-radius: 15px;
            padding: 2rem; box-shadow: 0 0 20px rgba(0,0,0,0.25); flex: 1; display: flex; flex-direction: column;
        }

        .top-right-actions {
            position: absolute; top: 20px; right: 30px; display: flex; gap: 10px; z-index: 10;
        }

        @media(max-width: 600px) {
            .top-right-actions { flex-direction: column; gap: 8px; right: 15px; top: 15px; }
        }

        .section-title { display:flex; align-items:center; gap:10px; color:#c41e3a; font-size:1.8rem; font-weight:600; margin:0 0 8px 0; }
        .section-title i { color:#e6202e; font-size:1.8rem; }
        .section-title:hover i { transform:scale(1.1); text-shadow:0 0 10px rgba(230,32,46,0.6); }

        .red-hr { border:none; height:2px; background:#B2000C; margin:0; }

        .panel { background:#fff; border-radius:12px; padding:1.5rem; max-height:80vh; overflow-y:auto; }

        .event { width:100%; border-collapse:collapse; background:#fff; margin-top:10px; }
        .event-box {
            display: table; width: calc(100% - 2rem); margin: 0 1rem 30px 1rem;
            border-radius: 8px; overflow:hidden;
            box-shadow:0 4px 10px rgba(0,0,0,0.18);
            transition:0.25s ease;
        }
        .event-box:hover { box-shadow:0 6px 16px rgba(178,0,12,0.966); outline:2px solid #b2000c; outline-offset:8px; }

        .event-title { background:#B2000C; color:#fff; padding:10px; font-weight:bold; text-align:center; border-radius:10px 10px 0 0; }
        .event-title h3 { margin:0; font-size: clamp(1rem,2vw,1.5rem); }

        .detail-box { background:#f0f0f0; padding:0.75rem; text-align:center; }
        .detail-box:hover { background:#e7c9c9; }

        .detail-link {
            background:#B2000C; color:white; padding:8px 14px; border-radius:8px; text-decoration:none; font-weight:bold;
            transition:0.3s;
        }
        .detail-link:hover { background:#d60012; transform:scale(1.05); }

        /* Search bar */
        .event-search {
            width: 260px;
            padding: 8px 12px;
            border: 2px solid #b2000c;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 0.9rem;
        }
    </style>
<style>
.copy-btn, .print-btn {
  background-color: #B2000C;
  color: #fff;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: bold;
  transition: all 0.3s ease;
}

.copy-btn:hover, .print-btn:hover {
  background-color: #d60012;
  transform: scale(1.05);
}

.copy-btn i {
  margin-right: 5px;
  transition: transform 0.3s ease;
}

.copy-btn.copied i {
  animation: pop 0.3s ease forwards;
}

@keyframes pop {
  0% { transform: scale(0); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
}
    
    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
@include('layouts.page_loader')
@include('layouts.navbar')

<div class="scroll-container">

    {{-- ============================================
         REUSABLE SECTION COMPONENT
    ============================================= --}}
    @php
        $sections = [
            ['title' => 'Upcoming Events', 'icon' => 'fa-calendar-check', 'data' => $upcomingEvents, 'rowClass'=>'upcoming-event', 'tableId'=>'UpcomingEventsTable'],
            ['title' => 'Ongoing Events', 'icon' => 'fa-hourglass-half', 'data' => $ongoingEvents, 'rowClass'=>'ongoing-event', 'tableId'=>'OngoingEventsTable'],
            ['title' => 'Completed Events', 'icon' => 'fa-clipboard-check', 'data' => $completedEvents, 'rowClass'=>'completed-event', 'tableId'=>'CompletedEventsTable'],
            ['title' => 'Cancelled Events', 'icon' => 'fa-ban', 'data' => $cancelledEvents, 'rowClass'=>'cancelled-event', 'tableId'=>'CancelledEventsTable'],
        ];
    @endphp

    @foreach ($sections as $sec)
    <section id="{{ str_replace(' ', '', $sec['title']) }}-Section">

        <div class="database-container">
            <main class="database-main">
                <div class="import-section">

                    <h2 class="section-title">
                        <i class="fas {{ $sec['icon'] }}"></i>{{ $sec['title'] }}
                    </h2>

                    <!-- Search Bar -->
                    <input type="text" class="event-search"
                           placeholder="Search events..."
                           onkeyup="filterEvents('{{ $sec['tableId'] }}', this.value)">

                    <div class="top-right-actions">
                        <button class="copy-btn" onclick="copyTable('{{ $sec['tableId'] }}')">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button class="print-btn" onclick="printTable('{{ $sec['tableId'] }}')">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>

                    <div class="table-responsive">
                        <hr class="red-hr">

                        <div class="panel">
                            <div class="event-container">

                                <table id="{{ $sec['tableId'] }}" class="event event-table">

                                    @forelse ($sec['data'] as $event)
                                        <tbody class="event-box">
                                            <tr class="event-header">
                                                <th class="event-title" colspan="5">
                                                    <h3>{{ $event->title }}</h3>
                                                </th>
                                            </tr>

                                            <tr class="{{ $sec['rowClass'] }}">
                                                @include('layouts.event_row')
                                            </tr>
                                        </tbody>
                                    @empty
                                        <p class="no-events">No {{ strtolower($sec['title']) }}.</p>
                                    @endforelse

                                </table>

                            </div>
                        </div>

                    </div>
                </div>
            </main>
        </div>

    </section>
    @endforeach

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- ============================
        EVENT MANAGER JS
=============================== -->
<script>

function copyTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    let text = "";
    let n = 1;

    table.querySelectorAll(".event-box").forEach(box => {
        const title = box.querySelector(".event-title h3")?.innerText ?? "";
        const row = box.querySelector("tr:not(.event-header)");
        if (!row) return;

        const c = row.querySelectorAll(".detail-box");
        text += `${n}. ${title}\n   Date: ${c[0]?.innerText}\n   Time: ${c[1]?.innerText}\n   Venue: ${c[2]?.innerText}\n   Volunteers: ${c[3]?.innerText}\n\n`;
        n++;
    });

    navigator.clipboard.writeText(text.trim());
    alert("Copied!");
}

function printTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const title = table.closest(".import-section").querySelector(".section-title").innerText;

    let html = `
        <h2>${title}</h2>
        <table border="1" style="width:100%; border-collapse:collapse;">
            <tr><th>#</th><th>Event</th><th>Date</th><th>Time</th><th>Venue</th><th>Volunteers</th></tr>
    `;

    let n = 1;
    table.querySelectorAll(".event-box").forEach(box => {
        const name = box.querySelector(".event-title h3")?.innerText ?? "";
        const row = box.querySelector("tr:not(.event-header)");

        const cells = row.querySelectorAll(".detail-box");

        html += `
            <tr>
                <td>${n}</td>
                <td>${name}</td>
                <td>${cells[0]?.innerText}</td>
                <td>${cells[1]?.innerText}</td>
                <td>${cells[2]?.innerText}</td>
                <td>${cells[3]?.innerText}</td>
            </tr>
        `;
        n++;
    });

    html += `</table>`;

    const win = window.open("");
    win.document.write(html);
    win.print();
}

function filterEvents(tableId, query) {
    query = query.toLowerCase();
    const table = document.getElementById(tableId);

    table.querySelectorAll(".event-box").forEach(box => {
        const text = box.innerText.toLowerCase();
        box.style.display = text.includes(query) ? "" : "none";
    });
}

</script>

</body>
</html>
