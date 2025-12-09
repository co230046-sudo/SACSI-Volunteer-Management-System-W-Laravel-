@include('layouts.back_button')
@include('layouts.navbar')
@include('layouts.page_loader')

<link rel="stylesheet" href="{{ asset('assets/admin_profile/Admin_profile.css') }}">
<script src="{{ asset('assets/admin_profile/admin_profile.js') }}"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">


<section id="Student-Section" style="opacity:1;">

<style>
/* ================================
   GLOBAL — FIX WHITE BAR & SCROLL
================================ */
body.modal-open {
    overflow: hidden !important;
}

/* FULLSCREEN MODAL OVERLAY */
.styled-modal {
    position: fixed;
    inset: 0;
    width: 100vw !important;
    height: 100vh !important;
    background: rgba(0,0,0,0.55);
    display: none; 
    align-items: center;
    justify-content: center;
    z-index: 99999;
    animation: fadeIn .3s ease;
}

/* (Your original styles below) */
@keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}

.styled-modal-content {
    background: #fff;
    padding: 25px;
    width: 100%;
    max-width: 1100px;
    max-height: 90%;
    overflow-y: auto;
    border-radius: 15px;
    position: relative;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    animation: slideUp .35s ease;
}

@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to   { transform: translateY(0); opacity: 1; }
}

/* Rest of your CSS remains unchanged */


/* ================================
   MODAL CONTENT CONTAINER
================================ */
.styled-modal-content {
    background: #fff;
    padding: 25px;
    width: 100%;
    max-width: 1100px;
    max-height: 90%;
    overflow-y: auto;
    border-radius: 15px;
    position: relative;
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    animation: slideUp .35s ease;
    table-layout: fixed;
}

@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to   { transform: translateY(0); opacity: 1; }
}

/* ================================
   MODAL CLOSE BUTTON
================================ */
.modal-close {
    position: absolute;
    top: 12px;
    right: 18px;
    cursor: pointer;
    font-size: 22px;
    color: #b3263a;
    z-index: 100000; /* Always on top */
}

.modal-close * {
    pointer-events: none; /* Ensure the icon doesn't block clicks */
}

/* ================================
   MODAL HEADER
================================ */
.modal-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.modal-icon {
    font-size: 32px;
    color: #b3263a;
}

.modal-title {
    font-weight: 700;
    font-size: 26px;
    margin: 0;
}

/* ================================
   TABLE INSIDE MODAL
================================ */
.styled-table thead tr {
    background: ##b3263a;
    color: white;
    text-align: center;
}

.styled-table td,
.styled-table th {
    padding: 12px;
}

/* Role Tag */
.role-tag {
    background: #e8e8e8;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}

/* ================================
   BUTTONS INSIDE MODAL
================================ */
.actions-cell {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Logs Button */
.btn-log {
    background: #dc3545 !important;
    color: #fff !important;
    padding: 5px 12px;
    font-size: 0.85rem;
    border-radius: 6px;
    border: none;
    display: flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    transition: 0.2s ease;
}

.btn-log:hover {
    background: #b02a37 !important;
}

/* Profile Button */
.btn-view {
    background: #007bff !important;
    color: white !important;
    padding: 5px 12px;
    font-size: 0.85rem;
    border-radius: 6px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: 0.2s ease;
}

.btn-view:hover {
    background: #0056b3 !important;
}

/* ================================
   TOGGLE BUTTON (VIEW ADMIN LIST)
================================ */
.view-admin-btn {
    background: #b3263a;
    color: #fff;
    padding: 12px 20px;
    border-radius: 10px;
    font-weight: 600;
    border: none;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    transition: 0.3s ease;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}

.view-admin-btn:hover {
    background: #8A0000;
    transform: translateY(-2px);
}

.view-admin-btn i {
    font-size: 1.3rem;
}


</style>

<div class="container-fluid main-content py-4">
<div class="student-section-wrapper">

{{-- LEFT COLUMN --}}
<div class="left-col">
    <div class="left-section" style="background-color:#f2f5f8;">

        {{-- PROFILE SECTION --}}
        <div class="profile-section p-3 border rounded mb-3" id="profileSection">

            <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

                <table class="table table-borderless w-100 mb-0">
                    <tbody>
                        <tr>
                            {{-- LEFT SIDE PHOTO --}}
                            <td class="text-center align-middle" style="width:100%;">

                                <div style="display:flex; flex-direction:column; align-items:center;">

                                    {{-- Profile photo --}}
                                    <div class="profile-photo-wrapper" style="position:relative;">
                                        <img src="{{ $admin->profile_picture
                                                ? asset('storage/' . $admin->profile_picture) . '?v=' . time()
                                                : asset('assets/adminpic.png') }}"
                                             class="profile-photo mb-2 border rounded-circle"
                                             id="profileImage">
                                    </div>

                                    <input type="file" name="photo" id="profileUpload" accept="image/*" style="display:none;">

                                    <h2 class="volunteer-name mb-1" style="margin-top:10px;">
                                        {{ $admin->full_name }}
                                    </h2>

                                    <p class="volunteer-title mb-2">
                                        {{ $admin->role }}
                                    </p>
                                </div>
                            </td>

                            {{-- RIGHT SIDE BUTTONS --}}
                            <td class="align-middle position-relative">

                                <div class="action-tools card p-2 shadow-sm position-absolute top-0 end-0 m-2"
                                     style="border-radius:12px; background:white; width:140px;">

                                    <div class="info-card mb-2">
                                        <i class="fas fa-check-circle"></i>
                                        <span class="status-text active">Active</span>
                                    </div>

                                    <button type="button" onclick="printLeftColumn()" class="info-card mb-2">
                                        <i class="fas fa-print"></i> Print
                                    </button>

                                    <button type="button" id="editSaveBtn" class="info-card mb-2">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>

                                    <a href="https://facebook.com" target="_blank" class="info-card text-dark">
                                        <i class="fab fa-facebook"></i> FB
                                    </a>
                                </div>

                            </td>
                        </tr>
                    </tbody>
                </table>

                {{-- ADMIN DETAILS --}}
                <div class="volunteer-details p-3 border rounded mb-3 position-relative">

                    <button type="button" class="copy-volunteer-btn" onclick="copyVolunteerData(this)">
                        Copy <i class="fas fa-copy"></i>
                    </button>

                    <h4 class="text-center mb-3">Admin Details</h4>

                    <table class="table table-borderless mb-0">
                        <tbody>

                            <tr>
                                <td><div class="detail-card"><h6><i class="fas fa-id-card"></i> Admin ID</h6><p>{{ $admin->admin_id }}</p></div></td>

                                <td><div class="detail-card"><h6><i class="fas fa-user"></i> Username</h6><p>{{ $admin->username }}</p></div></td>

                                <td><div class="detail-card"><h6><i class="fas fa-envelope"></i> Email</h6><p>{{ $admin->email }}</p></div></td>

                                <td><div class="detail-card"><h6><i class="fas fa-key"></i> Password</h6><p>********</p></div></td>
                            </tr>

                            <tr>
                                <td><div class="detail-card"><h6><i class="fas fa-address-card"></i> Full Name</h6><p>{{ $admin->full_name }}</p></div></td>

                                <td><div class="detail-card"><h6><i class="fas fa-user-shield"></i> Role</h6><p>{{ $admin->role }}</p></div></td>

                                <td>
                                <div class="detail-card">
                                    <h6><i class="fas fa-phone"></i> Contact #</h6>
                                    <p>{{ !empty($admin->contact_number) ? $admin->contact_number : 'Not Provided' }}</p>
                                </div>
                                </td>


                                <td><div class="detail-card"><h6><i class="fas fa-calendar-alt"></i> Created</h6><p>{{ $admin->created_at->format('Y-m-d') }}</p></div></td>
                            </tr>

                        </tbody>
                    </table>

                </div>

            </form>

        </div>

    </div>
</div>

{{-- RIGHT COLUMN --}}
<div class="right-col">

    {{-- SUPER ADMIN BUTTON --}}
    @if(str_contains(strtolower($currentAdmin->role), 'super'))
    <div class="toggle-container mb-3 p-3 border rounded"
         style="background:white; box-shadow:0 0 10px rgba(0,0,0,0.08); text-align:center;">

        <button id="toggleButton" class="view-admin-btn">
            <i class="fas fa-id-card"></i> View Admin Profiles
        </button>
    </div>
@endif

{{-- ACTIVITY LOG --}}
<div class="events-section p-3 border rounded mt-4">
    <h4 class="events-title mb-3">Activity Log (DEBUG)</h4>

    <table class="table table-bordered mb-0 event-table">
        <thead>
            <tr>
                <th>Activity</th>
                <th>Date & Time</th>
            </tr>
        </thead>

        <tbody>
            @if(isset($logs) && $logs->count())
                @foreach($logs->take(8) as $log)

                    <tr>
                        <td>
                            {{ $log->title ?? $log->action ?? 'Activity Logged' }}
                        </td>
                        <td>
                           {{ $log->created_at 
                            ? \Carbon\Carbon::parse($log->created_at)->format('M d, Y - h:i A') 
                            : 'No Date' 
}}

                        </td>
                    </tr>
                @endforeach
            @else
                <tr>
                    <td colspan="2" class="text-center text-muted">
                        No activity logs found.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
<button class="system-log-btn" onclick="openAllLogsModal()">
    <i class="fas fa-list"></i> View All Activity Logs
</button>




{{-- ADMIN PROFILES MODAL --}}
<div id="adminProfilesModal" class="styled-modal">
    <div class="styled-modal-content">
        <span class="modal-close" onclick="closeAdminProfilesModal()"><i class="fas fa-times"></i></span>

        <div class="modal-header">
            <i class="fas fa-id-card modal-icon"></i>
            <h3 class="modal-title">Admin Accounts</h3>
        </div>

        <table class="table table-striped styled-table mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th style="width:150px">Actions</th>
                </tr>
            </thead>

            <tbody>
                @foreach($allAdmins as $acc)
                <tr>
                    <td>{{ $acc->admin_id }}</td>
                    <td>{{ $acc->full_name }}</td>
                    <td><span class="role-tag">{{ $acc->role }}</span></td>
                    <td>{{ $acc->email }}</td>

                 

                 <td class="actions-cell">

    <!-- LOGS BUTTON -->
    <button class="btn-log"
            onclick="openAdminLogsModal({{ $acc->admin_id }})">
        <i class="fas fa-book-open"></i> Logs
    </button>

    <!-- PROFILE BUTTON -->
    <button class="btn-view"
        data-url="{{ route('admin.profile.view', $acc->admin_id) }}"
        onclick="openAdminProfileModal(this)">
        <i class="fas fa-eye"></i> Profile
    </button>

</td>

                @endforeach
            </tbody>
        </table>

    </div>
</div>

{{-- LOGS MODAL (JS expects these IDs: modalAdminName, modalLogTable, adminLogsModal) --}}
<div id="adminLogsModal" class="styled-modal">
    <div class="styled-modal-content">

        <span class="modal-close" onclick="closeAdminLogsModal()"><i class="fas fa-times"></i></span>

        <div class="modal-header">
            <i class="fas fa-book-open modal-icon"></i>
            <h3 id="modalAdminName" class="modal-title">Activity Logs</h3>
        </div>

        <table class="table table-striped styled-table mt-3">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody id="modalLogTable">
                <!-- populated by openAdminLogsModal(id) -->
            </tbody>
        </table>

    </div>
</div>

<!-- ✅ ACTIVITY LOG MODAL -->
<!-- ✅ ALL ACTIVITY LOGS POP-UP MODAL -->
<div id="allActivityLogsModal" class="styled-modal">

    <div class="styled-modal-content activity-popup">

        <span class="modal-close" onclick="closeAllLogsModal()">
            <i class="fas fa-times"></i>
        </span>

        <div class="modal-header">
            <i class="fas fa-book modal-icon"></i>
            <h3 class="modal-title">All Activity Logs</h3>
        </div>

        <div class="activity-table-wrapper">
            <table class="table table-striped styled-table mt-3 mb-0">
                <thead>
                    <tr>
                        <th>Activity</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                {{ $log->title ?? $log->action ?? 'Activity Logged' }}
                            </td>
                            <td>
                                {{ $log->created_at 
                                    ? \Carbon\Carbon::parse($log->created_at)->format('M d, Y - h:i A') 
                                    : 'No Date' 
                                }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-muted py-4">
                                No activity logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
</div>
<!-- ✅ ADMIN PROFILE VIEW MODAL -->
<div id="adminProfileViewModal" class="styled-modal">
    <div class="styled-modal-content admin-profile-modal">

        <!-- CLOSE -->
        <span class="modal-close" onclick="closeAdminProfileModal()">
            <i class="fas fa-times"></i>
        </span>

        <!-- HEADER -->
        <div class="modal-header admin-profile-header">
            <i class="fas fa-user-shield modal-icon"></i>
            <h3 class="modal-title">Admin Profile</h3>
        </div>

        <!-- BODY -->
        <div id="adminProfileContent" class="p-4 text-center">

            <div class="text-muted py-5">
                <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                <div>Loading profile...</div>
            </div>

        </div>

    </div>
</div>





<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ============================================================
   ADMIN PROFILE MODALS & ACTIVITY LOG SYSTEM (FINAL FIX)
============================================================ */

/* ✅ OPEN ADMIN PROFILES MODAL */
document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("toggleButton");
    if (btn) {
        btn.onclick = () => openAdminProfilesModal();
    }
});

/* ✅ OPEN ADMIN PROFILES MODAL */
function openAdminProfilesModal() {
    document.body.classList.add("modal-open");
    document.getElementById("adminProfilesModal").style.display = "flex";
}

/* ✅ CLOSE ADMIN PROFILES MODAL */
function closeAdminProfilesModal() {
    document.body.classList.remove("modal-open");
    document.getElementById("adminProfilesModal").style.display = "none";
}

/* ✅ OPEN ADMIN LOGS MODAL (AJAX) */
function openAdminProfileModal(button) {

    const url = button.dataset.url;

    document.body.classList.add("modal-open");
    document.getElementById("adminProfileViewModal").style.display = "flex";

    fetch(url)
        .then(res => res.json())
        .then(data => {

            document.getElementById("adminProfileContent").innerHTML = `
                <div class="text-center mb-3">
                    <img src="${data.profile_picture}"
                         class="rounded-circle mb-2"
                         width="120" height="120">
                    <h4>${data.full_name}</h4>
                    <p class="text-muted">${data.role}</p>
                </div>

                <table class="table table-bordered">
                    <tr><th>Admin ID</th><td>${data.admin_id}</td></tr>
                    <tr><th>Username</th><td>${data.username}</td></tr>
                    <tr><th>Email</th><td>${data.email}</td></tr>
                    <tr><th>Contact</th><td>${data.contact_number}</td></tr>
                    <tr><th>Created</th><td>${data.created_at}</td></tr>
                </table>
            `;
        })
        .catch(err => {
            console.error("PROFILE LOAD ERROR:", err);
            document.getElementById("adminProfileContent").innerHTML = `
                <div class="text-danger text-center">Failed to load profile.</div>
            `;
        });
}
function openAdminLogsModal(id) {

    console.log("Opening logs for:", id); // ✅ debug proof

    const modal = document.getElementById("adminLogsModal");
    const body = document.getElementById("modalLogTable");
    const title = document.getElementById("modalAdminName");

    if (!modal || !body || !title) {
        alert("Logs modal elements missing!");
        return;
    }

    document.body.classList.add("modal-open");
    modal.style.display = "flex";

    fetch(`/admin/profile/logs/${id}`)
        .then(res => {
            if (!res.ok) throw new Error("Route not found");
            return res.json();
        })
        .then(data => {

            title.innerText = "Activity Logs — " + data.name;
            body.innerHTML = "";

            if (!data.logs || data.logs.length === 0) {
                body.innerHTML = `
                    <tr>
                        <td colspan="2" class="text-center text-muted">
                            No logs found.
                        </td>
                    </tr>
                `;
            } else {
                data.logs.forEach(log => {

                    const text =
                        log.title ||
                        log.action ||
                        log.description ||
                        '(no description)';

                    const date = log.created_at
                        ? new Date(log.created_at).toLocaleString()
                        : 'No Date';

                    body.innerHTML += `
                        <tr>
                            <td>${text}</td>
                            <td>${date}</td>
                        </tr>
                    `;
                });
            }
        })
        .catch(err => {
            console.error("LOG FETCH ERROR:", err);
            alert("Failed to load activity logs.");
        });
}


/* ✅ CLOSE ADMIN LOGS MODAL */
function closeAdminLogsModal() {
    document.body.classList.remove("modal-open");
    document.getElementById("adminLogsModal").style.display = "none";
}

/* ✅ OPEN ALL ACTIVITY LOGS POP-UP */
function openAllLogsModal() {
    document.body.classList.add("modal-open");
    document.getElementById("allActivityLogsModal").style.display = "flex";
}

/* ✅ CLOSE ALL ACTIVITY LOGS POP-UP */
function closeAllLogsModal() {
    document.body.classList.remove("modal-open");
    document.getElementById("allActivityLogsModal").style.display = "none";
}

/* ============================================================
   ✅ ✅ ✅ ADMIN PROFILE MODAL (FINAL FIXED VERSION)
============================================================ */
function openAdminProfileModal(button) {

    const url = button.dataset.url;

    document.body.classList.add("modal-open");
    document.getElementById("adminProfileViewModal").style.display = "flex";

    fetch(url)
        .then(res => res.json())
        .then(data => {

            const isActive = data.is_active ?? 1; // ✅ fallback to active
            const statusText = isActive ? "Active" : "Inactive";
            const statusClass = isActive ? "status-active" : "status-inactive";
            const statusIcon = isActive ? "fa-check-circle" : "fa-ban";

            document.getElementById("adminProfileContent").innerHTML = `
                <div class="admin-profile-layout">

                    <!-- TOP PROFILE ROW -->
                    <div class="admin-top-row">

                        <div class="admin-center">
                            <img src="${data.profile_picture || '/assets/adminpic.png'}" 
                                 class="admin-avatar">

                            <h3 class="admin-name">${data.full_name}</h3>
                            <span class="admin-role">${data.role}</span>

                            <!-- ✅ STATUS BADGE -->
                            <div class="admin-status ${statusClass}">
                                <i class="fas ${statusIcon}"></i> ${statusText}
                            </div>
                        </div>

                    </div>

                    <!-- ✅ ACTION BUTTONS -->
                                        <!-- ✅ ACTION BUTTONS -->
                    <div class="admin-action-row">

                        <!-- LEFT BUTTONS -->
                        <div class="admin-action-left">
                            <button class="admin-btn print">
                                <i class="fa fa-print me-2"></i> Print
                            </button>

                            <button class="admin-btn edit">
                                <i class="fa fa-pen-to-square me-2"></i> Edit
                            </button>
                        </div>

                        <!-- RIGHT DELETE BUTTON -->
                        <div class="admin-action-right">
                            <button class="admin-btn delete"
                                onclick="deleteAdminProfile(${data.admin_id})">
                                <i class="fa fa-trash me-2"></i> Delete
                            </button>
                        </div>

                    </div>


                    <!-- ADMIN DETAILS -->
                    <div class="admin-details-box">
                        <h5 class="admin-details-title">Admin Details</h5>

                        <div class="admin-grid">
                            <div class="admin-card">
                                <i class="fa fa-id-card"></i>
                                <b>Admin ID</b>
                                <span>${data.admin_id}</span>
                            </div>

                            <div class="admin-card">
                                <i class="fa fa-user"></i>
                                <b>Username</b>
                                <span>${data.username}</span>
                            </div>

                            <div class="admin-card">
                                <i class="fa fa-envelope"></i>
                                <b>Email</b>
                                <span>${data.email}</span>
                            </div>

                            <div class="admin-card">
                                <i class="fa fa-key"></i>
                                <b>Password</b>
                                <span>********</span>
                            </div>

                            <div class="admin-card">
                                <i class="fa fa-address-card"></i>
                                <b>Full Name</b>
                                <span>${data.full_name}</span>
                            </div>

                            <div class="admin-card">
                                <i class="fa fa-user-shield"></i>
                                <b>Role</b>
                                <span>${data.role}</span>
                            </div>

                            <div class="admin-card">
                                <i class="fa fa-phone"></i>
                                <b>Contact #</b>
                                <span>${data.contact_number || 'N/A'}</span>
                            </div>

                            <div class="admin-card">
                                <i class="fa fa-calendar-alt"></i>
                                <b>Created</b>
                                <span>${data.created_at}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        })
        .catch(err => {
            console.error("PROFILE LOAD ERROR:", err);
            document.getElementById("adminProfileContent").innerHTML = `
                <div class="text-danger text-center py-5">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i><br>
                    Failed to load profile.
                </div>
            `;
        });
}



/* ✅ CLOSE ADMIN PROFILE MODAL */
function closeAdminProfileModal() {
    document.body.classList.remove("modal-open");
    document.getElementById("adminProfileViewModal").style.display = "none";
}
function deleteAdminProfile(adminId) {
    if (!confirm("Are you sure you want to permanently delete this admin account?")) {
        return;
    }

    fetch(`/admin/delete/${adminId}`, {
        method: "DELETE",
        headers: {
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message || "Admin deleted successfully!");
        location.reload();
    })
    .catch(err => {
        console.error(err);
        alert("Failed to delete admin.");
    });
}




</script>


</section>
