@include('layouts.navbar')
@include('layouts.page_loader')

<!-- BOOTSTRAP CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<style>
/* GENERAL LAYOUT */
#Student-Section {
    opacity: 1;
    padding-top: 20px;
}

/* DASHBOARD CARDS */
.dashboard-card {
    border-radius: 20px;
    padding: 25px;
    color: white;
    min-height: 160px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    box-shadow: 0px 5px 15px rgba(0,0,0,0.15);
    transition: 0.2s;
}
.dashboard-card:hover {
    transform: translateY(-4px);
    box-shadow: 0px 10px 22px rgba(0,0,0,0.25);
}

/* COLORS */
.bg-red { background: #C0392B; }
.bg-blue { background: #2C6EAD; }
.bg-teal { background: #16A085; }
.bg-orange { background: #D35400; }

/* SECTION TITLES */
.section-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #B30000;
    margin-bottom: 15px;
    display: flex;
    gap: 8px;
    align-items: center;
}

/* CHART + LIST CARDS */
.chart-card, .list-card {
    border-radius: 20px;
    padding: 20px;
    background: #ffffff;
    box-shadow: 0px 4px 14px rgba(0,0,0,0.12);
    height: 100%;
}

/* CHART FIXED HEIGHT BOX */
.fixed-chart {
    position: relative;
    width: 100%;
    height: 330px !important;  /* SAME HEIGHT for BOTH charts */
}

.chart-card canvas {
    width: 100% !important;
    height: 100% !important;
}

/* Placeholder faded look */
.placeholder-chart {
    opacity: 0.4;
}

/* List styling */
.list-item {
    padding: 12px 5px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    font-size: 0.95rem;
}
.list-item:last-child { border-bottom: none; }
.dashboard-card {
    cursor: pointer;
}

</style>


@php
$volunteersPerLevel = $volunteersPerLevel ?: collect(['No Data' => 0]);
$eventsThisMonth = $eventsThisMonth ?: collect(['No Data' => 0]);
$upcomingEvents = $upcomingEvents ?? 0;
$completedEvents = $completedEvents ?? 0;
$cancelledEvents = $cancelledEvents ?? 0;
$totalVolunteers = $totalVolunteers ?? 0;
@endphp


<section id="Student-Section">

<div class="container mt-4">

    <h2 class="section-title"><i class="fa fa-chart-line"></i> Dashboard Overview</h2>

    <!-- TOP CARDS -->
    <div class="row g-4">

            <div class="col-12 col-sm-6 col-lg-3">
            <a href="{{ route('volunteers.list') }}" class="text-decoration-none">
                <div class="dashboard-card bg-red text-center">
                    <i class="fa fa-users fa-3x mb-2"></i>
                    <h5>Total Volunteers</h5>
                    <h2>{{ $totalVolunteers }}</h2>
                </div>
            </a>
        </div>


       <div class="col-12 col-sm-6 col-lg-3">
            <a href="{{ route('events.manage') }}" class="text-decoration-none">
                <div class="dashboard-card bg-blue text-center">
                    <i class="fa fa-calendar-plus fa-3x mb-2"></i>
                    <h5>Upcoming Events</h5>
                    <h2>{{ $upcomingEvents }}</h2>
                </div>
            </a>
        </div>

       <div class="col-12 col-sm-6 col-lg-3">
        <a href="{{ route('events.manage', ['tab' => 'completed']) }}" class="text-decoration-none">
            <div class="dashboard-card bg-teal text-center">
                <i class="fa fa-calendar-check fa-3x mb-2"></i>
                <h5>Completed</h5>
                <h2>{{ $completedEvents }}</h2>
            </div>
        </a>
    </div>

        <div class="col-12 col-sm-6 col-lg-3">
        <a href="{{ route('events.manage', ['tab' => 'cancelled']) }}" class="text-decoration-none">
            <div class="dashboard-card bg-orange text-center">
                <i class="fa fa-calendar-times fa-3x mb-2"></i>
                <h5>Cancelled</h5>
                <h2>{{ $cancelledEvents }}</h2>
            </div>
        </a>
    </div>


    </div>

    <!-- =============================== -->
    <!--        CHARTS ROW (FIXED)       -->
    <!-- =============================== -->

    <div class="row g-4 mt-3">

        <!-- Left Chart: Volunteers Per Level -->
        <div class="col-md-6 d-flex">
            <div class="chart-card flex-fill">
                <h4 class="section-title"><i class="fa fa-chart-bar"></i> Volunteers Per Year Level</h4>

                <div class="fixed-chart">
                    <canvas id="studentsLevelChart"
                        class="{{ $volunteersPerLevel->sum() == 0 ? 'placeholder-chart' : '' }}">
                    </canvas>
                </div>

                @if($volunteersPerLevel->sum() == 0)
                    <p class="text-center text-muted mt-2">No volunteer data available.</p>
                @endif
            </div>
        </div>

        <!-- Right Chart: Event Status (Horizontal Bar) -->
        <div class="col-md-6 d-flex">
            <div class="chart-card flex-fill">
                <h4 class="section-title"><i class="fa fa-chart-area"></i> Event Status Breakdown</h4>

                <div class="fixed-chart">
                    <canvas id="eventsStatusBar"
                        class="{{ ($upcomingEvents+$completedEvents+$cancelledEvents)==0 ? 'placeholder-chart' : '' }}">
                    </canvas>
                </div>

                @if(($upcomingEvents+$completedEvents+$cancelledEvents)==0)
                    <p class="text-center text-muted mt-2">No event status available.</p>
                @endif
            </div>
        </div>

    </div>


    <!-- LISTS ROW -->
    <div class="row g-4 mt-3">

        <div class="col-md-6">
            <div class="list-card">
                <h4 class="section-title"><i class="fa fa-fire"></i> Most Active Volunteers</h4>

                @forelse ($topVolunteers as $v)
                    <div class="list-item">
                        <strong>{{ optional($v->profile)->name ?? 'Unknown' }}</strong>
                        <span>{{ $v->total }} activities</span>
                    </div>
                @empty
                    <p class="text-center text-muted p-3">No volunteer activity yet.</p>
                @endforelse
            </div>
        </div>

        <div class="col-md-6">
            <div class="list-card">
                <h4 class="section-title">
                    <i class="fa fa-user-clock"></i> Recently Registered Volunteers
                </h4>

                @forelse ($recentVolunteers as $s)
                    <div class="list-item">
                        <strong>{{ $s->full_name ?? $s->name ?? 'Unnamed Volunteer' }}</strong>
                        <span>{{ $s->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-center text-muted p-3">
                        No recent volunteer registrations.
                    </p>
                @endforelse
            </div>
        </div>



    <!-- EVENTS THIS MONTH -->
    <div class="row mt-4 mb-5">
        <div class="col-md-12">
            <div class="chart-card">
                <h4 class="section-title"><i class="fa fa-chart-line"></i> Events Trend This Month</h4>

                <div class="fixed-chart">
                    <canvas id="eventsMonthChart"></canvas>
                </div>

                @if($eventsThisMonth->sum() == 0)
                    <p class="text-center text-muted mt-2">No events recorded this month.</p>
                @endif
            </div>
        </div>
    </div>

</div>

</section>

<script>
console.log("Chart exists:", typeof Chart);
</script>


<!-- ✅ LOAD CHART.JS FIRST -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ✅ PASS LARAVEL DATA SAFELY TO JS -->
<script>
const volunteersPerLevelData = @json($volunteersPerLevel ?? []);
const eventsThisMonthData   = @json($eventsThisMonth ?? []);
const upcomingEvents  = {{ $upcomingEvents ?? 0 }};
const completedEvents = {{ $completedEvents ?? 0 }};
const cancelledEvents = {{ $cancelledEvents ?? 0 }};
</script>

<!-- ✅ MAIN CHART LOGIC -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    console.log("✅ Chart.js Loaded:", typeof Chart);
    console.log("✅ Volunteers:", volunteersPerLevelData);
    console.log("✅ Events Month:", eventsThisMonthData);

    /* ============================================================
       ✅ VOLUNTEERS PER YEAR LEVEL (BAR CHART)
    ============================================================ */

    let labels = Object.keys(volunteersPerLevelData);
    let values = Object.values(volunteersPerLevelData);

    if (labels.length === 0 || values.every(v => v === 0)) {
        labels = ["No Data"];
        values = [1];
    }

    new Chart(document.getElementById("studentsLevelChart"), {
        type: "bar",
        data: {
            labels: labels,
            datasets: [{
                label: "Volunteers",
                data: values,
                backgroundColor: "#C0392B",
                borderRadius: 12,
                barThickness: 60
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    /* ============================================================
       ✅ EVENT STATUS BREAKDOWN (HORIZONTAL BAR)
    ============================================================ */

    let eventValues = [upcomingEvents, completedEvents, cancelledEvents];

    if (eventValues.every(v => v === 0)) {
        eventValues = [1, 1, 1]; // prevents blank chart
    }

    new Chart(document.getElementById("eventsStatusBar"), {
        type: "bar",
        data: {
            labels: ["Upcoming", "Completed", "Cancelled"],
            datasets: [{
                label: "Events",
                data: eventValues,
                backgroundColor: ["#2C6EAD", "#16A085", "#D35400"],
                borderRadius: 10,
                barThickness: 28
            }]
        },
        options: {
            indexAxis: "y",
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });

    /* ============================================================
       ✅ EVENTS THIS MONTH (LINE CHART)
    ============================================================ */

    let monthLabels = Object.keys(eventsThisMonthData);
    let monthValues = Object.values(eventsThisMonthData);

    if (monthLabels.length === 0 || monthValues.every(v => v === 0)) {
        monthLabels = ["No Data"];
        monthValues = [1];
    }

    new Chart(document.getElementById("eventsMonthChart"), {
        type: "line",
        data: {
            labels: monthLabels,
            datasets: [{
                label: "Events",
                data: monthValues,
                borderColor: "#C0392B",
                backgroundColor: "rgba(192,57,43,0.2)",
                borderWidth: 3,
                pointRadius: 5,
                tension: 0.35,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });

});
</script>


