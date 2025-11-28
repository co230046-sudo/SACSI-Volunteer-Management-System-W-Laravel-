@php
    $pageTitle = 'Volunteer Lists';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer List</title>
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('assets/volunteer_list/css/Volunteer_List.css') }}">

    <!-- Boostrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">

</head>
<body class="page--volunteer-list">

{{-- Loader & Navbar --}}
@include('layouts.page_loader')
@include('layouts.navbar')

<section class="page-section">

    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">

       @include('layouts.search_bar.volunteer_list_search_bar')
        <!-- Add Student Button -->
        <button class="btn btn-danger add-student-trigger"
                data-bs-toggle="modal"
                data-bs-target="#addStudentModal">
            <i class="fa fa-plus"></i> Add Student
        </button>

    </div>

    <!-- outer-card provides the shadow/card look while section remains full-page -->
    <div class="outer-card">
        <div class="container my-4">
            <!-- Grid Section -->
            <div class="row justify-content-center">
                <div class="col-lg-10 col-md-12">
                    <div class="grid-container">

                        <!-- Cards grid (JS will populate this) -->
                        <div id="cards-grid" class="cards-grid"></div>

                        <!-- Count label -->
                        <div id="grid-count" class="grid-count mt-2"></div>

                        <!-- Navigation Arrows -->
                        <div class="navigation d-flex flex-row justify-content-end align-items-center gap-3 mt-3">

                            <button id="arrow-up" class="btn arrow-btn" aria-pressed="false">
                                <svg viewBox="0 0 79 79" width="40" height="40" aria-hidden="true">
                                    <path class="arrow-path"
                                          d="M60.649 33.8088C64.1842 29.5165 61.1262 23.0417 55.5633 23.0417H23.4367C17.8737 23.0417 14.8191 29.5165 18.3543 33.8088L34.421 53.3185C35.0386 54.0686 35.8146 54.6727 36.6933 55.0875C37.572 55.5022 38.5316 55.7173 39.5033 55.7173C40.475 55.7173 41.4346 55.5022 42.3133 55.0875C43.192 54.6727 43.968 54.0686 44.5856 53.3185L60.649 33.8088Z"
                                          fill="#888888" transform="rotate(180 39.5 39.5)">
                                    </path>
                                </svg>
                            </button>

                            <button id="arrow-down" class="btn arrow-btn" aria-pressed="false">
                                <svg viewBox="0 0 79 79" width="40" height="40" aria-hidden="true">
                                    <path class="arrow-path"
                                          d="M60.649 33.8088C64.1842 29.5165 61.1262 23.0417 55.5633 23.0417H23.4367C17.8737 23.0417 14.8191 29.5165 18.3543 33.8088L34.421 53.3185C35.0386 54.0686 35.8146 54.6727 36.6933 55.0875C37.572 55.5022 38.5316 55.7173 39.5033 55.7173C40.475 55.7173 41.4346 55.5022 42.3133 55.0875C43.192 54.6727 43.968 54.0686 44.5856 53.3185L60.649 33.8088Z"
                                          fill="#888888">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div> <!-- .container -->
        </div> <!-- .outer-card -->
    </div>
</section>
</body>


<!-- Load Bootstrap JS bundle (needed for modal)-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/volunteer_list/js/script.js') }}"></script> 

</html>

