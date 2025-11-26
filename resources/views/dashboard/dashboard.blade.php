{{-- resources/views/dashboard/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SACSI Volunteer Dashboard</title>

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('assets/dashboard/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/dashboard/bootstrap-5.0.2-dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Chart / PDF Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>

    {{-- UNIVERSAL NAVBAR --}}
    @include('layouts.navbar')

    <!-- Back Button -->
    <a href="{{ url()->previous() }}" class="btn btn-light"
       style="position:absolute; left:20px; top:90px; z-index:1000; font-weight:bold;">
        <i class="fa fa-arrow-left"></i> Back
    </a>

    <!-- ===== DASHBOARD GRID CONTAINER ===== -->
    <div class="parent" style="margin-top:140px;">

        <!-- ===== CHART #1: Course Category ===== -->
        <div class="div1">
            <canvas id="classCategoryChart"></canvas>
        </div>

        <!-- ===== CHART #2: Event Type ===== -->
        <div class="div2">
            <canvas id="eventTypeChart"></canvas>
        </div>

        <!-- ===== CHART #3: Event Status ===== -->
        <div class="div3">
            <canvas id="eventChart"></canvas>
        </div>

        <!-- ===== METRIC BOXES ===== -->
        <div id="total-volunteers-container" class="dashboard-item">
            <div class="item-title">Total Volunteers</div>
            <div class="item-value">{{ $totalVolunteers }}</div>
        </div>

        <div id="active-volunteers-container" class="dashboard-item">
            <div class="item-title">Active Volunteers</div>
            <div class="item-value">{{ $activeVolunteers }}</div>
        </div>

        <div id="growth-rate-container" class="dashboard-item">
            <div class="item-title">Growth Rate</div>
            <div class="item-value">{{ $growthRate }}%</div>
        </div>

        <div id="average-attendance-container" class="dashboard-item">
            <div class="item-title">Avg Attendance</div>
            <div class="item-value">{{ round($averageAttendance) }}%</div>
        </div>

        <div id="event-success-rate-container" class="dashboard-item">
            <div class="item-title">Event Success Rate</div>
            <div class="item-value">{{ $eventSuccessRate }}%</div>
        </div>

        <!-- ===== ACTION BOX ===== -->
        <div class="dashboard-item" id="actions-item-container">
            <div class="item-title">
                <i class="fa-solid fa-gear"></i> Dashboard Actions
            </div>

            <div class="dropdown" id="actions-dropdown-container">
                <button class="control-btn dropdown-toggle" id="actionsDropdownToggle">
                    <i class="fa-solid fa-gear"></i> Print & Export Options
                </button>

                <div class="dropdown-menu" id="actionsDropdownMenu">

                    <div class="dropdown-section">
                        <label class="dropdown-label">Sort By Year:</label>
                        <select id="year-filter" class="dropdown-select">
                            <option disabled selected>Select Year</option>
                            <option>2025</option>
                            <option>2024</option>
                            <option>2023</option>
                        </select>
                    </div>

                    <div class="dropdown-divider"></div>

                    <a href="#" class="dropdown-item" id="printDashboard">
                        <i class="fa-solid fa-print"></i> Print Data Summary
                    </a>

                    <a href="#" class="dropdown-item" id="exportExcelPlaceholder">
                        <i class="fa-solid fa-file-excel"></i> Export as CSV
                    </a>

                    <a href="#" class="dropdown-item" id="exportPdf">
                        <i class="fa-solid fa-file-pdf"></i> Export as PDF
                    </a>

                </div>
            </div>
        </div>

    </div> <!-- END parent -->

    <!-- ===== JS DATA PASSED FROM LARAVEL ===== -->
    <script>
        window.dashboardData = {
            eventStatus: {
                upcoming: {{ $eventStatus['upcoming'] }},
                completed: {{ $eventStatus['completed'] }},
                cancelled: {{ $eventStatus['cancelled'] }},
            },
            volunteersByCourse: {
                labels: {!! json_encode($volunteersByCourse->pluck('label')) !!},
                totals: {!! json_encode($volunteersByCourse->pluck('total')) !!}
            },
            yearLevels: {
                labels: {!! json_encode($yearLevels->pluck('year_level')) !!},
                totals: {!! json_encode($yearLevels->pluck('total')) !!}
            }
        };
    </script>

    <!-- DASHBOARD JS -->
    <script src="{{ asset('assets/dashboard/script.js') }}"></script>
    <script src="{{ asset('assets/dashboard/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js') }}"></script>

</body>
</html>
