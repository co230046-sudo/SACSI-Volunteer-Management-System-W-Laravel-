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
            <a href="/volunteer_list" class="text-decoration-none">
                <div class="dashboard-card bg-red text-center">
                    <i class="fa fa-users fa-3x mb-2"></i>
                    <h5>Total Volunteers</h5>
                    <h2>{{ $totalVolunteers }}</h2>
                </div>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <a href="/manage_event" class="text-decoration-none">
                <div class="dashboard-card bg-blue text-center">
                    <i class="fa fa-calendar-plus fa-3x mb-2"></i>
                    <h5>Upcoming Events</h5>
                    <h2>{{ $upcomingEvents }}</h2>
                </div>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <a href="/manage_event" class="text-decoration-none">
                <div class="dashboard-card bg-teal text-center">
                    <i class="fa fa-calendar-check fa-3x mb-2"></i>
                    <h5>Completed</h5>
                    <h2>{{ $completedEvents }}</h2>
                </div>
            </a>
        </div>

        <div class="col-12 col-sm-6 col-lg-3">
            <a href="/manage_event" class="text-decoration-none">
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
                <h4 class="section-title"><i class="fa fa-user-clock"></i> Recently Registered Volunteers</h4>

                @forelse ($recentVolunteers as $s)
                    <div class="list-item">
                        <strong>{{ $s->name }}</strong>
                        <span>{{ $s->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-center text-muted p-3">No recent volunteer registrations.</p>
                @endforelse
            </div>
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


<!-- CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const volunteersPerLevelData = @json($volunteersPerLevel);
const eventsThisMonthData = @json($eventsThisMonth);

new Chart(document.getElementById('studentsLevelChart'), {
    type: 'bar',
    data: {
        labels: Object.keys(volunteersPerLevelData),
        datasets: [{
            label: 'Volunteers',
            data: Object.values(volunteersPerLevelData),
            backgroundColor: ['#C0392B','#16A085','#2C6EAD','#D35400'],
            borderRadius: 10
        }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
});

new Chart(document.getElementById('eventsStatusBar'), {
    type: 'bar',
    data: {
        labels: ['Upcoming', 'Completed', 'Cancelled'],
        datasets: [{
            label: 'Events',
            data: [{{ $upcomingEvents }}, {{ $completedEvents }}, {{ $cancelledEvents }}],
            backgroundColor: ['#2C6EAD','#16A085','#D35400'],
            borderRadius: 8
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } }
    }
});

new Chart(document.getElementById('eventsMonthChart'), {
    type: 'line',
    data: {
        labels: Object.keys(eventsThisMonthData),
        datasets: [{
            label: 'Events',
            data: Object.values(eventsThisMonthData),
            borderColor: '#C0392B',
            backgroundColor: 'rgba(192,57,43,0.18)',
            borderWidth: 3,
            pointRadius: 4,
            tension: 0.35,
            fill: true
        }]
    }
});
</script>
