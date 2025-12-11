/**
 * admin_profile.js
 * ✅ FINAL CLEAN & FIXED VERSION
 * - NO duplicate listeners
 * - Image upload opens ONLY once
 * - Only image click triggers file picker
 * - Image validation included
 * - All modal + toggle features preserved
 */

document.addEventListener("DOMContentLoaded", function () {

    /* ================================
       ✅ EDIT BUTTON → OPEN MODAL ONLY
    ================================= */
    const editBtn = document.getElementById("editSaveBtn");

    if (editBtn) {
        editBtn.addEventListener("click", function () {
            openEditProfileModal();
        });
    }

    /* ================================
       ✅ PROFILE IMAGE PREVIEW (SINGLE BIND — NO DUPLICATES)
    ================================= */
    const modalProfileImage = document.getElementById("modalProfileImage");
    const modalFileInput = document.getElementById("modalProfileUpload");

    if (modalProfileImage && modalFileInput) {

        // ✅ Image click is the ONLY trigger
        modalProfileImage.onclick = function () {
            modalFileInput.click();
        };

        // ✅ Safe image handling
        modalFileInput.onchange = function (evt) {
            const file = evt.target.files && evt.target.files[0];
            if (!file) return;

            // ✅ Allow images only
            if (!file.type.startsWith("image/")) {
                alert("Please upload a valid image file.");
                modalFileInput.value = "";
                return;
            }

            // ✅ Max file size 2MB
            if (file.size > 2 * 1024 * 1024) {
                alert("Image must be 2MB or smaller.");
                modalFileInput.value = "";
                return;
            }

            const reader = new FileReader();
            reader.onload = function (ev) {
                modalProfileImage.src = ev.target.result;
            };
            reader.readAsDataURL(file);
        };
    }

    /* ================================
       ✅ ADMIN PROFILE / ACTIVITY TOGGLE
    ================================= */
    const toggleBtn = document.getElementById("toggleButton");
    const logSection = document.getElementById("activityLogSection");
    const adminSection = document.getElementById("adminAccountsSection");

    if (toggleBtn && logSection && adminSection) {
        toggleBtn.addEventListener("click", function () {

            if (adminSection.style.display === "none" || adminSection.style.display === "") {

                adminSection.style.display = "block";
                logSection.style.display = "none";

                toggleBtn.innerHTML = `View Activity Log`;
                toggleBtn.classList.remove("btn-danger");
                toggleBtn.classList.add("btn-primary");

            } else {

                adminSection.style.display = "none";
                logSection.style.display = "block";

                toggleBtn.innerHTML = `View Admin Profiles`;
                toggleBtn.classList.remove("btn-primary");
                toggleBtn.classList.add("btn-danger");
            }
        });
    }

});


/* ================================
   ✅ EDIT PROFILE MODAL CONTROL
================================= */
function openEditProfileModal() {
    document.body.classList.add("modal-open");
    document.getElementById("editProfileModal").style.display = "flex";

    // ✅ Reset file input every time
    const upload = document.getElementById("modalProfileUpload");
    if (upload) upload.value = "";
}

function closeEditProfileModal() {
    document.body.classList.remove("modal-open");
    document.getElementById("editProfileModal").style.display = "none";
}


/* ================================
   ✅ CHANGE PASSWORD MODAL CONTROL
================================= */
function openChangePasswordModal() {
    document.body.classList.add("modal-open");
    document.getElementById("changePasswordModal").style.display = "flex";
}

function closeChangePasswordModal() {
    document.body.classList.remove("modal-open");
    document.getElementById("changePasswordModal").style.display = "none";
}


/* ================================
   ✅ ADMIN LOGS AJAX LOADER
================================= */
function loadAdminLogs(adminId) {
    fetch(`/admin/profile/logs/${adminId}`)
        .then(res => res.json())
        .then(data => {
            const logContainer = document.getElementById('logContainer');
            if (!logContainer) return;

            logContainer.innerHTML = '';

            if (!data.logs || data.logs.length === 0) {
                logContainer.innerHTML = `<p class="text-muted">No logs available.</p>`;
                return;
            }

            data.logs.forEach(log => {
                const item = document.createElement('div');
                item.classList.add('log-item');

                item.innerHTML = `
                    <div><strong>${log.created_at}</strong></div>
                    <div>${log.description || log.action || 'Log entry'}</div>
                `;

                logContainer.appendChild(item);
            });
        })
        .catch(err => console.error('Error loading logs:', err));
}


function printLeftColumn() {
    const leftColumn = document.getElementById("leftColumn");

    if (!leftColumn) {
        alert("Error: leftColumn container not found.");
        return;
    }

    const content = leftColumn.innerHTML;
    const printWindow = window.open("", "", "width=900,height=700");

    printWindow.document.write(`
        <html>
        <head>
            <title>Print</title>

            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                }

                img {
                    max-width: 100%;
                    height: auto;
                }

                .info-card {
                    border: 1px solid #ddd;
                    padding: 10px;
                    margin-bottom: 10px;
                    border-radius: 8px;
                }

                h3, h4, h5 {
                    margin-top: 0;
                }
            </style>
        </head>

        <body>
            ${content}
        </body>
        </html>
    `);

    printWindow.document.close();

    // Make sure content loads before printing
    printWindow.onload = function () {
        printWindow.print();
        printWindow.close();
    };
}

