@include('layouts.navbar')
@include('layouts.page_loader')

<link rel="stylesheet" href="{{ asset('assets/admin_profile/Admin_profile.css') }}">
<script src="{{ asset('assets/admin_profile/admin_profile.js') }}"></script>

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
    color: #B2000C;
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
    color: #B2000C;
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
    background: #B2000C;
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
    background: #B2000C;
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

                                <td><div class="detail-card"><h6><i class="fas fa-phone"></i> Contact #</h6><p>{{ $admin->contact_number }}</p></div></td>

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
<div class="event-wrapper">
    <div class="events-section p-3 border rounded">
        <h4 class="events-title mb-3">Activity Log</h4>

        <table class="table table-bordered mb-0 event-table">
            <tbody>
                <tr class="event-item">
                    <td class="event-name">
                        <a>
                            Database Backup & Maintenance
                            <span class="click-bubble"><i class="fa fa-eye"></i> View Log Entry</span>
                        </a>
                    </td>
                    <td class="event-datetime">Nov 14, 2025 - 11:00 PM</td>
                </tr>

                <tr class="event-item">
                    <td class="event-name">
                        <a>
                            User Account Creation: John Doe
                            <span class="click-bubble"><i class="fa fa-eye"></i> View Log Entry</span>
                        </a>
                    </td>
                    <td class="event-datetime">Nov 10, 2025 - 10:00 AM</td>
                </tr>
            </tbody>
        </table>

    </div>
</div>

</div>

</div>
</div>

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

    <!-- FIXED PROFILE BUTTON -->
                    
    
         <a href="{{ route('admin.profile', ['id' => $acc->admin_id]) }}" 
            class="btn-view" target="_blank">
            <i class="fas fa-eye"></i> Profile
            </a>


                </tr>
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



<script>

document.addEventListener("DOMContentLoaded", () => {

    const btn = document.getElementById("toggleButton");

    if (btn) {
        btn.onclick = () => {
            document.getElementById("adminProfilesModal").style.display = "flex";
        };
    }

});

/* ============================================================
   FIXED MODAL HANDLING — NO WHITE BAR + NO BACKGROUND SCROLL
============================================================ */

document.addEventListener("DOMContentLoaded", () => {
    const btn = document.getElementById("toggleButton");

    if (btn) {
        btn.onclick = () => openAdminProfilesModal();
    }
});

function openAdminProfilesModal() {
    document.body.classList.add("modal-open");
    document.getElementById("adminProfilesModal").style.display = "flex";
}

function closeAdminProfilesModal() {
    document.body.classList.remove("modal-open");
    document.getElementById("adminProfilesModal").style.display = "none";
}

function openAdminLogsModal(id) {

    fetch(`/admin/profile/logs/${id}`)
        .then(res => res.json())
        .then(data => {

            document.getElementById('modalAdminName').innerText =
                "Activity Logs — " + data.name;

            let body = document.getElementById('modalLogTable');
            body.innerHTML = "";

            if (!data.logs || data.logs.length === 0) {
                body.innerHTML = `
                    <tr>
                        <td colspan="2" class="text-center text-muted">No logs found.</td>
                    </tr>`;
            } else {
                data.logs.forEach(log => {
                    body.innerHTML += `
                        <tr>
                            <td>${log.description ?? '(no description)'}</td>
                            <td>${new Date(log.created_at).toLocaleString()}</td>
                        </tr>`;
                });
            }

            document.body.classList.add("modal-open");
            document.getElementById("adminLogsModal").style.display = "flex";
        })
        .catch(err => console.error("Log fetch error:", err));
}

function closeAdminLogsModal() {
    document.body.classList.remove("modal-open");
    document.getElementById("adminLogsModal").style.display = "none";
}





</script>

</section>
