@include('layouts.navbar')
@include('layouts.page_loader')
@include('layouts.back_button')

<!-- BOOTSTRAP CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<style>

/* ============================================
   GLOBAL WRAPPER + PAGE SPACING
============================================ */
#Student-Section {
    opacity: 1;
    padding-top: 40px;
    margin-top: 20px;
}

body {
    background: #f4f6f9 !important;
    font-family: 'Nunito', sans-serif;
}

/* ============================================
   DASHBOARD CARDS – PREMIUM DESIGN
============================================ */
.dashboard-card {
    border-radius: 22px;
    padding: 28px 20px;
    min-height: 170px;

    color: white;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;

    cursor: pointer;
    transition: 0.25s ease-in-out;

    box-shadow:
        0 4px 12px rgba(0,0,0,0.12),
        0 6px 20px rgba(0,0,0,0.10);
}

.dashboard-card:hover {
    transform: translateY(-6px) scale(1.03);
    box-shadow:
        0 14px 28px rgba(0, 0, 0, 0.22),
        0 10px 10px rgba(0, 0, 0, 0.12);
}

.dashboard-card h5 {
    font-size: 1.05rem;
    margin-bottom: 5px;
    opacity: 0.9;
}

.dashboard-card h2 {
    font-size: 2.3rem;
    font-weight: 800;
    margin: 0;
}

/* Stronger gradient versions for your colors */
.bg-red {
    background: linear-gradient(135deg, #c0392b, #e74c3c);
}
.bg-blue {
    background: linear-gradient(135deg, #2C6EAD, #1B4F72);
}
.bg-teal {
    background: linear-gradient(135deg, #16A085, #117864);
}
.bg-orange {
    background: linear-gradient(135deg, #D35400, #E67E22);
}

/* ============================================
   SECTION TITLES
============================================ */
.section-title {
    font-size: 1.35rem;
    font-weight: 800;
    color: #b3263a;
    margin-bottom: 18px;
    display: flex;
    gap: 10px;
    align-items: center;
}

/* ============================================
   CHART + LIST CARD UPGRADE
============================================ */
.chart-card, .list-card {
    border-radius: 22px;
    background: #ffffff;
    padding: 24px;

    box-shadow:
        0 5px 18px rgba(0,0,0,0.12),
        0 8px 32px rgba(0,0,0,0.06);

    transition: 0.20s ease-in-out;
}

.chart-card:hover,
.list-card:hover {
    transform: translateY(-5px);
    box-shadow:
        0 14px 34px rgba(0,0,0,0.18),
        0 10px 12px rgba(0,0,0,0.08);
}

/* ============================================
   FIXED CHART AREA
============================================ */
.fixed-chart {
    position: relative;
    width: 100%;
    height: 330px !important;
}

.chart-card canvas {
    width: 100% !important;
    height: 100% !important;
}

.placeholder-chart {
    opacity: 0.3;
    filter: grayscale(100%);
}

/* ============================================
   LIST ITEMS
============================================ */
.list-item {
    padding: 14px 8px;
    border-bottom: 1px solid #eee;

    display: flex;
    justify-content: space-between;

    font-size: 1rem;
    font-weight: 600;
}

.list-item:hover {
    background: #f8f9fa;
}

.list-item:last-child {
    border-bottom: none;
}

/* ============================================
   BUTTON IMPROVEMENTS
============================================ */
.btn {
    font-weight: 700 !important;
    padding: 10px 16px !important;
    border-radius: 10px !important;
}

.btn i {
    margin-right: 6px;
}

/* Print and CSV buttons hover */
.btn-danger:hover,
.btn-success:hover,
.btn-primary:hover {
    transform: translateY(-2px);
}

/* ============================================
   MOBILE RESPONSIVENESS IMPROVED
============================================ */
@media (max-width: 768px) {
    .dashboard-card {
        min-height: 150px;
    }
    .dashboard-card h2 {
        font-size: 1.8rem;
    }
    .section-title {
        font-size: 1.2rem;
    }
}

</style>


@php
    // Normalize data structures
    $volunteersPerLevel        = collect($volunteersPerLevel ?? ['No Data' => 0]);
    $eventsThisMonth           = collect($eventsThisMonth ?? ['No Data' => 0]); // still used for CSV if needed
    $batchParticipationByMonth = collect($batchParticipationByMonth ?? []);     // month => [batch => count]

    $upcomingEvents  = $upcomingEvents  ?? 0;
    $completedEvents = $completedEvents ?? 0;
    $cancelledEvents = $cancelledEvents ?? 0;
    $totalVolunteers = $totalVolunteers ?? 0;

    // Check if batch participation has any actual counts
    $hasBatchParticipationData = $batchParticipationByMonth->flatten()->sum() > 0;
@endphp

<section id="Student-Section">
    <div class="container mt-4">

        <h2 class="section-title"><i class="fa fa-chart-line"></i> Dashboard Overview</h2>
        <div class="d-flex gap-2 mb-3">
            <button class="btn btn-danger" onclick="printDashboard()">
                <i class="fa fa-print"></i> Print
            </button>

            <button class="btn btn-success" onclick="downloadCSV()">
                <i class="fa fa-file-csv"></i> Export CSV
            </button>
        </div>

        <!-- TOP CARDS -->
        <div class="row g-4">

            <div class="col-12 col-sm-6 col-lg-3">
                <a href="{{ route('volunteers.list') }}" class="text-decoration-none">
                    <div class="dashboard-card bg-red text-center">
                        <i class="fa fa-users fa-3x mb-2"></i>
                        <h5>Total Volunteers</h5>
                        <h2 id="totalVolunteersCount">{{ $totalVolunteers }}</h2>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <a href="{{ route('events.manage') }}" class="text-decoration-none">
                    <div class="dashboard-card bg-blue text-center">
                        <i class="fa fa-calendar-plus fa-3x mb-2"></i>
                        <h5>Upcoming Events</h5>
                        <h2 id="upcomingEventsCount">{{ $upcomingEvents }}</h2>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <a href="{{ route('events.manage', ['tab' => 'completed']) }}" class="text-decoration-none">
                    <div class="dashboard-card bg-teal text-center">
                        <i class="fa fa-calendar-check fa-3x mb-2"></i>
                        <h5>Completed</h5>
                        <h2 id="completedEventsCount">{{ $completedEvents }}</h2>
                    </div>
                </a>
            </div>

            <div class="col-12 col-sm-6 col-lg-3">
                <a href="{{ route('events.manage', ['tab' => 'cancelled']) }}" class="text-decoration-none">
                    <div class="dashboard-card bg-orange text-center">
                        <i class="fa fa-calendar-times fa-3x mb-2"></i>
                        <h5>Cancelled</h5>
                        <h2 id="cancelledEventsCount">{{ $cancelledEvents }}</h2>
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
                            class="{{ ($upcomingEvents + $completedEvents + $cancelledEvents) == 0 ? 'placeholder-chart' : '' }}">
                        </canvas>
                    </div>

                    @if(($upcomingEvents + $completedEvents + $cancelledEvents) == 0)
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

        </div> <!-- end LISTS ROW -->

        <!-- BATCH PARTICIPATION BY MONTH -->
        
</section>

<!-- ✅ LOAD CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- ✅ PASS LARAVEL DATA SAFELY TO JS -->
<script>
const volunteersPerLevelData   = @json($volunteersPerLevel);
const eventsThisMonthData      = @json($eventsThisMonth); // kept for CSV / reference
const batchParticipationData   = @json($batchParticipationByMonth);
const upcomingEvents           = {{ $upcomingEvents }};
const completedEvents          = {{ $completedEvents }};
const cancelledEvents          = {{ $cancelledEvents }};
</script>

<!-- ✅ MAIN CHART LOGIC -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    console.log("✅ Chart.js Loaded:", typeof Chart);
    console.log("✅ Volunteers:", volunteersPerLevelData);
    console.log("✅ Events Month (raw):", eventsThisMonthData);
    console.log("✅ Batch Participation Data:", batchParticipationData);

    /* ============================================================
       ✅ VOLUNTEERS PER YEAR LEVEL (BAR CHART)
    ============================================================ */
    let labels = Object.keys(volunteersPerLevelData || {});
    let values = Object.values(volunteersPerLevelData || {});

    if (labels.length === 0 || values.every(v => v === 0)) {
        labels = ["No Data"];
        values = [1];
    }

    const studentsLevelChart = new Chart(document.getElementById("studentsLevelChart"), {
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

    const eventsStatusBar = new Chart(document.getElementById("eventsStatusBar"), {
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
       ✅ BATCH PARTICIPATION BY MONTH (MULTI-LINE + TOGGLE FILTERS)
       Shape expected:
       {
         "Jan 2025":  { "1st Year": 10, "2nd Year": 5 },
         "Feb 2025":  { "1st Year": 8,  "2nd Year": 12 },
         ...
       }
    ============================================================ */
    let monthLabels = Object.keys(batchParticipationData || {});
    let batchNamesSet = new Set();

    // Collect all batch names across all months
    monthLabels.forEach(month => {
        const monthData = batchParticipationData[month] || {};
        Object.keys(monthData).forEach(batch => batchNamesSet.add(batch));
    });

    let batchNames = Array.from(batchNamesSet);

    // Handle case with no data
    if (monthLabels.length === 0) {
        monthLabels = ["No Data"];
    }
    if (batchNames.length === 0) {
        batchNames = ["No Batch"];
    }

    // Generate a dataset per batch
    const colors = [
        "#C0392B",
        "#2C6EAD",
        "#16A085",
        "#D35400",
        "#8E44AD",
        "#27AE60"
    ];

    const batchDatasets = batchNames.map((batch, index) => {
        const color = colors[index % colors.length];

        return {
            label: batch,
            data: monthLabels.map(month => {
                const monthData = batchParticipationData[month] || {};
                const value = monthData[batch] ?? 0;
                return value;
            }),
            borderColor: color,
            backgroundColor: "transparent",   // no fill
            borderWidth: 3,
            pointRadius: 5,
            tension: 0.35,
            fill: false
        };
    });

    const monthChartCtx = document.getElementById("eventsMonthChart").getContext("2d");

    const eventsMonthChart = new Chart(monthChartCtx, {
        type: "line",
        data: {
            labels: monthLabels,
            datasets: batchDatasets
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }, // we will use buttons instead of legend
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.dataset.label || "";
                            const value = context.raw ?? 0;
                            return `${label}: ${value} participants`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    /* ============================================================
       ✅ BATCH TOGGLE FILTER BUTTONS (OPTION A)
    ============================================================ */
    const toggleContainer = document.getElementById("batchToggleButtons");

    batchNames.forEach((batch, index) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.classList.add("btn", "btn-sm", "btn-outline-primary");
        btn.textContent = batch;

        btn.addEventListener("click", () => {
            const dataset = eventsMonthChart.data.datasets[index];
            dataset.hidden = !dataset.hidden;

            // Toggle button style
            btn.classList.toggle("btn-outline-primary");
            btn.classList.toggle("btn-primary");

            eventsMonthChart.update();
        });

        toggleContainer.appendChild(btn);
    });

});

/* ===============================
   ✅ PRINT DASHBOARD
================================ */
function printDashboard() {
    window.print();
}

/* ===============================
   ✅ EXPORT TO CSV
================================ */
function downloadCSV() {
    let csv = [];
    csv.push(["Dashboard Report"]);
    csv.push(["Generated:", new Date().toLocaleString()]);
    csv.push([]);

    csv.push(["Metric", "Value"]);
    csv.push(["Total Volunteers", {{ $totalVolunteers }}]);
    csv.push(["Upcoming Events", {{ $upcomingEvents }}]);
    csv.push(["Completed Events", {{ $completedEvents }}]);
    csv.push(["Cancelled Events", {{ $cancelledEvents }}]);
    csv.push([]);

    // Volunteers per year level
    csv.push(["Volunteers Per Year Level"]);
    csv.push(["Year Level", "Total"]);

    const levelData = @json($volunteersPerLevel);
    Object.entries(levelData).forEach(([key, value]) => {
        csv.push([key, value]);
    });

    csv.push([]);

    // Events This Month (kept if you still use this data)
    csv.push(["Events This Month"]);
    csv.push(["Month", "Total"]);

    const monthData = @json($eventsThisMonth);
    Object.entries(monthData).forEach(([key, value]) => {
        csv.push([key, value]);
    });

    csv.push([]);

    // NEW: Batch Participation by Month
    csv.push(["Batch Participation by Month"]);
    csv.push(["Month", "Batch", "Total"]);

    const batchData = @json($batchParticipationByMonth);
    Object.entries(batchData).forEach(([month, batches]) => {
        Object.entries(batches).forEach(([batch, value]) => {
            csv.push([month, batch, value]);
        });
    });

    const csvContent = csv.map(e => e.join(",")).join("\n");

    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);

    const a = document.createElement("a");
    a.href = url;
    a.download = "dashboard_report.csv";
    a.click();
}

/* ===============================
   ✅ DOWNLOAD FOR APPLE NUMBERS
   (Uses CSV which Numbers opens natively)
================================ */
function downloadNumbers() {
    downloadCSV(); // Numbers opens CSV perfectly
}
</script>
