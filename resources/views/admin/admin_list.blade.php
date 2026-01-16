@include('layouts.navbar')
@include('layouts.page_loader')
@include('layouts.back_button')

<!-- BOOTSTRAP + ICONS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ============================================================
   GOOGLE FONT
============================================================ */
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900;950&display=swap');

/* -----------------------------------------
   PAGE BACKGROUND & LAYOUT
----------------------------------------- */
body {
    background: #f4f6fa !important;
    font-family: 'Nunito', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.45;
    color: #1f2933;
    margin-top: 90px !important;
}

/* Center main content */
.center-wrapper {
    max-width: 1200px;
    margin: 0 auto;
}

/* -----------------------------------------
   CARD STYLE
----------------------------------------- */
.card {
    border-radius: 18px;
    border: none;
    background: #ffffff;
    box-shadow: 0 6px 20px rgba(0,0,0,0.10);
    transition: 0.25s ease;
}
.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0,0,0,0.15);
}

/* -----------------------------------------
   TABLE STYLE
----------------------------------------- */
.table {
    width: 100% !important;
    margin: 0;
    border-radius: 12px;
    overflow: hidden;
    font-size: .95rem;
    font-weight: 700;
}
.table thead th {
    background: #b3263a;
    color: #fff;
    padding: 16px;
    text-align: center;
    font-size: .95rem;
    font-weight: 900;
}
.table tbody td {
    padding: 14px 12px;
    text-align: center;
    font-size: .95rem;
    font-weight: 700;
}
.table-hover tbody tr:hover {
    background: #fdf1f3 !important;
}

/* -----------------------------------------
   ROLE BADGES
----------------------------------------- */
.badge {
    padding: 6px 12px;
    font-size: .8rem;
    border-radius: 10px;
    font-weight: 900;
    letter-spacing: .2px;
}
.badge.super-admin { background: #b3263a !important; }
.badge.admin { background: #1e88e5 !important; }

/* -----------------------------------------
   BUTTONS
----------------------------------------- */
.btn-primary {
    background: #b3263a !important;
    border-radius: 10px;
    font-weight: 900;
    font-size: .9rem;
    letter-spacing: .2px;
}
.btn-primary:hover {
    background: #9f1f32 !important;
}

/* -----------------------------------------
   MODAL STYLING
----------------------------------------- */
#viewAdminModal .modal-content {
    border-radius: 18px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    border: none;
    font-size: .95rem;
}
#viewAdminModal .modal-header {
    background: #b3263a;
    color: white;
    font-weight: 900;
    font-size: 1.1rem;
}
#viewAdminModal .btn-close {
    filter: brightness(0) invert(1);
}

/* Profile image */
#viewProfileImage {
    border: 4px solid #b3263a;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.15);
}

/* -----------------------------------------
   LOGS
----------------------------------------- */
.activity-log-box {
    background: #f7f7f9;
    border-radius: 12px;
    padding: 15px;
    max-height: 280px;
    overflow-y: auto;
}

.activity-item {
    padding: 10px;
    border-left: 4px solid #b3263a;
    background: #fff;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: .9rem;
    font-weight: 700;
}

.activity-item small {
    color: #777;
    font-size: .8rem;
    font-weight: 700;
}

/* -----------------------------------------
   FIX BUTTON ALIGNMENT
----------------------------------------- */
.flex-nowrap {
    white-space: nowrap !important;
}

.gap-3 {
    gap: 1rem !important;
}


</style>


<!-- ===================================================
     PAGE CONTENT
======================================================= -->

<div class="container mt-4 mb-5 center-wrapper">

    <!-- TOP HEADER BAR -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-nowrap">

        <div class="d-flex align-items-center gap-3">
        <h2 class="fw-bold m-0">
                <i class="fa fa-users-cog"></i> Admin Profiles
            </h2>
            

           

        </div>

        <!-- Register Button (Right Side) -->
        <a href="{{ route('admin.register') }}" 
           class="btn btn-success btn-lg px-4" 
           style="white-space: nowrap;">
            <i class="fa fa-user-plus"></i> Register Admin
        </a>

    </div>


    <div class="card">
        <div class="card-body p-0">

            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th style="width:110px;">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($admins as $admin)
                    <tr>
                        <td>{{ $admin->full_name }}</td>
                        <td>{{ $admin->username }}</td>
                        <td>{{ $admin->email }}</td>

                        <td>
                            <span class="badge {{ strtolower($admin->role) === 'super_admin' ? 'super-admin' : 'admin' }}">
                                {{ ucfirst(str_replace('_', ' ', $admin->role)) }}
                            </span>
                        </td>

                        <td>
                            <!-- ❗ FIXED: removed trailing space that caused %20 error -->
                            <button class="btn btn-primary btn-sm w-100"
                                onclick="openAdminModal({{ $admin->admin_id }})">
                                <i class="fa fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>

            </table>

        </div>
    </div>

</div>


<!-- ===================================================
     ADMIN PROFILE MODAL (AJAX)
======================================================= -->
<div class="modal fade" id="viewAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-user text-white"></i> Admin Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center py-4">

                <!-- LOADING -->
                <div id="loadingSpinner">
                    <i class="fa fa-spinner fa-spin fa-2x text-danger"></i>
                    <p class="mt-2">Loading...</p>
                </div>

                <!-- DETAILS -->
                <div id="adminDetails" class="d-none">

                    <img id="viewProfileImage" class="rounded-circle mb-3"
                         style="width:130px; height:130px; object-fit:cover;">

                    <h4 id="viewName" class="fw-bold mb-1"></h4>
                    <p id="viewRole" class="badge bg-danger"></p>

                    <hr>

                    <p><strong>Username:</strong> <span id="viewUsername"></span></p>
                    <p><strong>Email:</strong> <span id="viewEmail"></span></p>
                    <p><strong>Contact:</strong> <span id="viewContact"></span></p>

                    <hr>

                    <h5 class="fw-bold mt-3">Activity Logs</h5>
                    <div id="activityLogBox" class="activity-log-box">
                        <p class="text-muted">Loading logs...</p>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>


<!-- ===================================================
     JAVASCRIPT (AJAX LOADER)
======================================================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function openAdminModal(id) {
    document.getElementById("loadingSpinner").classList.remove("d-none");
    document.getElementById("adminDetails").classList.add("d-none");

    let modal = new bootstrap.Modal(document.getElementById('viewAdminModal'));
    modal.show();

    fetch(`/admin/profile/view/${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;

            const d = res.data;

            document.getElementById("viewProfileImage").src = d.profile_picture;
            document.getElementById("viewName").innerText = d.full_name;
            document.getElementById("viewUsername").innerText = d.username;
            document.getElementById("viewEmail").innerText = d.email;
            document.getElementById("viewContact").innerText = d.contact_number;
            document.getElementById("viewRole").innerText = d.role;

            // Activity Logs
            let logBox = document.getElementById("activityLogBox");
            logBox.innerHTML = "";

            if (d.logs && d.logs.length > 0) {
                d.logs.forEach(log => {
                    logBox.innerHTML += `
                        <div class="activity-item">
                            <strong>${log.action}</strong><br>
                            <small>${log.details}</small><br>
                            <small>${log.created_at}</small>
                        </div>
                    `;
                });
            } else {
                logBox.innerHTML = `<p class="text-muted text-center">No activity logs found.</p>`;
            }

            document.getElementById("loadingSpinner").classList.add("d-none");
            document.getElementById("adminDetails").classList.remove("d-none");
        })
        .catch(() => {
            document.getElementById("loadingSpinner").innerHTML =
                `<p class="text-danger">Failed to load profile.</p>`;
        });
}
</script>

@include('layouts.navbar')
@include('layouts.page_loader')
@include('layouts.back_button')

<!-- BOOTSTRAP + ICONS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ============================================================
   GOOGLE FONT
============================================================ */
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@600;700;800;900;950&display=swap');

/* -----------------------------------------
   PAGE BACKGROUND & LAYOUT
----------------------------------------- */
body {
    background: #f4f6fa !important;
    font-family: 'Nunito', system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.45;
    color: #1f2933;
    margin-top: 90px !important;
}

/* Center main content */
.center-wrapper {
    max-width: 1200px;
    margin: 0 auto;
}

/* -----------------------------------------
   CARD STYLE
----------------------------------------- */
.card {
    border-radius: 18px;
    border: none;
    background: #ffffff;
    box-shadow: 0 6px 20px rgba(0,0,0,0.10);
    transition: 0.25s ease;
}
.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0,0,0,0.15);
}

/* -----------------------------------------
   TABLE STYLE
----------------------------------------- */
.table {
    width: 100% !important;
    margin: 0;
    border-radius: 12px;
    overflow: hidden;
    font-size: .95rem;
    font-weight: 700;
}
.table thead th {
    background: #b3263a;
    color: #fff;
    padding: 16px;
    text-align: center;
    font-size: .95rem;
    font-weight: 900;
}
.table tbody td {
    padding: 14px 12px;
    text-align: center;
    font-size: .95rem;
    font-weight: 700;
}
.table-hover tbody tr:hover {
    background: #fdf1f3 !important;
}

/* -----------------------------------------
   ROLE BADGES
----------------------------------------- */
.badge {
    padding: 6px 12px;
    font-size: .8rem;
    border-radius: 10px;
    font-weight: 900;
    letter-spacing: .2px;
}
.badge.super-admin { background: #b3263a !important; }
.badge.admin { background: #1e88e5 !important; }

/* -----------------------------------------
   BUTTONS
----------------------------------------- */
.btn-primary {
    background: #b3263a !important;
    border-radius: 10px;
    font-weight: 900;
    font-size: .9rem;
    letter-spacing: .2px;
}
.btn-primary:hover {
    background: #9f1f32 !important;
}

/* -----------------------------------------
   MODAL STYLING
----------------------------------------- */
#viewAdminModal .modal-content {
    border-radius: 18px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
    border: none;
    font-size: .95rem;
}
#viewAdminModal .modal-header {
    background: #b3263a;
    color: white;
    font-weight: 900;
    font-size: 1.1rem;
}
#viewAdminModal .btn-close {
    filter: brightness(0) invert(1);
}

/* Profile image */
#viewProfileImage {
    border: 4px solid #b3263a;
    box-shadow: 0px 4px 10px rgba(0,0,0,0.15);
}

/* -----------------------------------------
   LOGS
----------------------------------------- */
.activity-log-box {
    background: #f7f7f9;
    border-radius: 12px;
    padding: 15px;
    max-height: 280px;
    overflow-y: auto;
}

.activity-item {
    padding: 10px;
    border-left: 4px solid #b3263a;
    background: #fff;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: .9rem;
    font-weight: 700;
}

.activity-item small {
    color: #777;
    font-size: .8rem;
    font-weight: 700;
}

/* -----------------------------------------
   FIX BUTTON ALIGNMENT
----------------------------------------- */
.flex-nowrap {
    white-space: nowrap !important;
}

.gap-3 {
    gap: 1rem !important;
}


</style>


<!-- ===================================================
     PAGE CONTENT
======================================================= -->

<div class="container mt-4 mb-5 center-wrapper">

    <!-- TOP HEADER BAR -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-nowrap">

        <div class="d-flex align-items-center gap-3">
        <h2 class="fw-bold m-0">
                <i class="fa fa-users-cog"></i> Admin Profiles
            </h2>
            

           

        </div>

        <!-- Register Button (Right Side) -->
        <a href="{{ route('admin.register') }}" 
           class="btn btn-success btn-lg px-4" 
           style="white-space: nowrap;">
            <i class="fa fa-user-plus"></i> Register Admin
        </a>

    </div>


    <div class="card">
        <div class="card-body p-0">

            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th style="width:110px;">Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($admins as $admin)
                    <tr>
                        <td>{{ $admin->full_name }}</td>
                        <td>{{ $admin->username }}</td>
                        <td>{{ $admin->email }}</td>

                        <td>
                            <span class="badge {{ strtolower($admin->role) === 'super_admin' ? 'super-admin' : 'admin' }}">
                                {{ ucfirst(str_replace('_', ' ', $admin->role)) }}
                            </span>
                        </td>

                        <td>
                            <!-- ❗ FIXED: removed trailing space that caused %20 error -->
                            <button class="btn btn-primary btn-sm w-100"
                                onclick="openAdminModal({{ $admin->admin_id }})">
                                <i class="fa fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>

            </table>

        </div>
    </div>

</div>


<!-- ===================================================
     ADMIN PROFILE MODAL (AJAX)
======================================================= -->
<div class="modal fade" id="viewAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-user text-white"></i> Admin Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center py-4">

                <!-- LOADING -->
                <div id="loadingSpinner">
                    <i class="fa fa-spinner fa-spin fa-2x text-danger"></i>
                    <p class="mt-2">Loading...</p>
                </div>

                <!-- DETAILS -->
                <div id="adminDetails" class="d-none">

                    <img id="viewProfileImage" class="rounded-circle mb-3"
                         style="width:130px; height:130px; object-fit:cover;">

                    <h4 id="viewName" class="fw-bold mb-1"></h4>
                    <p id="viewRole" class="badge bg-danger"></p>

                    <hr>

                    <p><strong>Username:</strong> <span id="viewUsername"></span></p>
                    <p><strong>Email:</strong> <span id="viewEmail"></span></p>
                    <p><strong>Contact:</strong> <span id="viewContact"></span></p>

                    <hr>

                    <h5 class="fw-bold mt-3">Activity Logs</h5>
                    <div id="activityLogBox" class="activity-log-box">
                        <p class="text-muted">Loading logs...</p>
                    </div>

                </div>

            </div>

        </div>
    </div>
</div>


<!-- ===================================================
     JAVASCRIPT (AJAX LOADER)
======================================================= -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function openAdminModal(id) {
    document.getElementById("loadingSpinner").classList.remove("d-none");
    document.getElementById("adminDetails").classList.add("d-none");

    let modal = new bootstrap.Modal(document.getElementById('viewAdminModal'));
    modal.show();

    fetch(`/admin/profile/view/${id}`)
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;

            const d = res.data;

            document.getElementById("viewProfileImage").src = d.profile_picture;
            document.getElementById("viewName").innerText = d.name;
            document.getElementById("viewUsername").innerText = d.username;
            document.getElementById("viewEmail").innerText = d.email;
            document.getElementById("viewContact").innerText = d.contact_number;
            document.getElementById("viewRole").innerText = d.role;

            // Activity Logs
            let logBox = document.getElementById("activityLogBox");
            logBox.innerHTML = "";

            if (d.logs && d.logs.length > 0) {
                d.logs.forEach(log => {
                    logBox.innerHTML += `
                        <div class="activity-item">
                            <strong>${log.action}</strong><br>
                            <small>${log.details}</small><br>
                            <small>${log.created_at}</small>
                        </div>
                    `;
                });
            } else {
                logBox.innerHTML = `<p class="text-muted text-center">No activity logs found.</p>`;
            }

            document.getElementById("loadingSpinner").classList.add("d-none");
            document.getElementById("adminDetails").classList.remove("d-none");
        })
        .catch(() => {
            document.getElementById("loadingSpinner").innerHTML =
                `<p class="text-danger">Failed to load profile.</p>`;
        });
}
</script>
