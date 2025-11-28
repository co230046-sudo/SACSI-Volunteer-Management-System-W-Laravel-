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

                       
                        </div>
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
            </div> <!-- .container -->
        </div> <!-- .outer-card -->
    </div>
</section>
</body>


<!-- Load Bootstrap JS bundle (needed for modal)-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/volunteer_list/js/script.js') }}"></script> 

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ url('/volunteers') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="addStudentModalLabel">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label">Full name <span class="text-danger">*</span></label>
                                <input name="full_name" required type="text" class="form-control" placeholder="Juan Dela Cruz">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">ID number</label>
                                    <input name="id_number" type="text" class="form-control" placeholder="230123">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Year level</label>
                                    <input name="year_level" type="text" class="form-control" placeholder="3">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Email</label>
                                    <input name="email" type="email" class="form-control" placeholder="juan@adzu.edu.com">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Contact number</label>
                                    <input name="contact_number" type="text" class="form-control" placeholder="09xxxxxxxxx">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Emergency contact</label>
                                <input name="emergency_contact" type="text" class="form-control" placeholder="Parent / Guardian contact">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Facebook messenger</label>
                                <input name="fb_messenger" type="text" class="form-control" value="No FB messenger">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes"></textarea>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3 text-center">
                                <label class="form-label d-block">Profile picture</label>
                                <img src="/storage/defaults/default_user.png" alt="avatar" class="img-fluid rounded mb-2" style="max-height:120px;">
                                <input name="profile_picture" type="file" accept="image/*" class="form-control">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Course</label>
                                @if(isset($courses) && count($courses))
                                    <select name="course_id" class="form-select">
                                        <option value="">-- Select course --</option>
                                        @foreach($courses as $c)
                                            <option value="{{ $c->course_id }}">{{ $c->course_name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input name="course_name" type="text" class="form-control" placeholder="Course name (optional)">
                                @endif
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Barangay</label>
                                <input name="barangay" type="text" class="form-control" placeholder="Barangay">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">District</label>
                                <input name="district" type="text" class="form-control" placeholder="District">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" selected>Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Save student</button>
                </div>
            </form>
        </div>
    </div>
</div>

</html>

