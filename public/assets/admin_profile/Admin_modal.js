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

            const isActive = data.is_active ?? 1;
            const statusText = isActive ? "Active" : "Inactive";
            const statusClass = isActive ? "status-active" : "status-inactive";
            const statusIcon = isActive ? "fa-check-circle" : "fa-ban";

            document.getElementById("adminProfileContent").innerHTML = `
                <div class="admin-profile-layout">

                    <div class="admin-top-row">
                        <div class="admin-center">
                            <img src="${data.profile_picture || '/assets/adminpic.png'}" 
                                 class="admin-avatar">

                            <h3 class="admin-name">${data.full_name}</h3>
                            <span class="admin-role">${data.role}</span>

                            <div class="admin-status ${statusClass}">
                                <i class="fas ${statusIcon}"></i> ${statusText}
                            </div>
                        </div>
                    </div>

                    <div class="admin-action-row">
                        <div class="admin-action-left">
                            <button class="admin-btn print">
                                <i class="fa fa-print me-2"></i> Print
                            </button>

                            <button class="admin-btn edit">
                                <i class="fa fa-pen-to-square me-2"></i> Edit
                            </button>
                        </div>

                        <div class="admin-action-right">
                            <button class="admin-btn delete"
                                onclick="deleteAdminProfile(${data.admin_id})">
                                <i class="fa fa-trash me-2"></i> Delete
                            </button>
                        </div>
                    </div>

                    <div class="admin-details-box">
                        <h5 class="admin-details-title">Admin Details</h5>

                        <div class="admin-grid">
                            <div class="admin-card"><b>Admin ID</b><span>${data.admin_id}</span></div>
                            <div class="admin-card"><b>Username</b><span>${data.username}</span></div>
                            <div class="admin-card"><b>Email</b><span>${data.email}</span></div>
                            <div class="admin-card"><b>Password</b><span>********</span></div>
                            <div class="admin-card"><b>Full Name</b><span>${data.full_name}</span></div>
                            <div class="admin-card"><b>Role</b><span>${data.role}</span></div>
                            <div class="admin-card"><b>Contact</b><span>${data.contact_number || 'N/A'}</span></div>
                            <div class="admin-card"><b>Created</b><span>${data.created_at}</span></div>
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


function openRegisterUserModal(adminId, adminName) {
    document.body.classList.add("modal-open");
    document.getElementById("registerUserModal").style.display = "flex";

    document.getElementById("registerAdminId").value = adminId;
    document.getElementById("registerAdminName").value = adminName;
}

function closeRegisterUserModal() {
    document.body.classList.remove("modal-open");
    document.getElementById("registerUserModal").style.display = "none";
}
