<style>
/* ============================================================
   UNIVERSAL WRAPPER
============================================================ */
.move-modal-wrapper {
    position: fixed;
    inset: 0;
    display: none;
    justify-content: center;
    align-items: center;
    padding: 20px;
    z-index: 99999;
}
.move-modal-wrapper.active {
    display: flex;
}

.move-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,0.45);
}

/* ============================================================
   MODAL BOX
============================================================ */
.move-modal-box {
    position: relative;
    background: #fff;
    border-radius: 16px;
    width: 100%;
    max-width: 480px;
    padding: 26px 32px 22px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.25);
    animation: modalIn .25s ease;
}
@keyframes modalIn {
    from { opacity:0; transform: translateY(-16px) scale(.96); }
    to   { opacity:1; transform: translateY(0) scale(1); }
}

/* ============================================================
   HEADER TEXT + CENTERING
============================================================ */
.move-modal-box h2 {
    text-align: center;
    margin: 0 0 6px;
    font-weight: 600;
}
.move-modal-box h2 i {
    margin-right: 6px;
}

/* ============================================================
   HEADER COLORS
============================================================ */
.move-modal-header-success { color: #28a745 !important; }
.move-modal-header-error   { color: #B2000C !important; }
.move-modal-header-info    { color: #1565c0 !important; }
.move-modal-header-warn    { color: #B2000C !important; }

/* Icon color matching headers */
.move-modal-header-success i { color: #28a745 !important; }
.move-modal-header-error   i { color: #B2000C !important; }
.move-modal-header-info    i { color: #1565c0 !important; }
.move-modal-header-warn    i { color: #B2000C !important; }


/* ============================================================
   CONTENT TEXT
============================================================ */
.move-modal-text {
    font-size: 1.02rem;
    line-height: 1.55;
    margin-bottom: 18px;
    font-weight: 400;
}

/* ============================================================
   ROW LISTS
============================================================ */
.move-scroll-list {
    max-height: 260px;
    overflow-y: auto;
    padding-right: 6px;
    margin-top: 4px;
}

.move-row {
    border-bottom: 1px dashed #d7d7d7;
    padding: 10px 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.move-row:last-child { border-bottom: none; }

.move-row a {
    color: #1565c0;
    font-weight: 500;
    text-decoration: underline;
}

/* ============================================================
   HR STYLING
============================================================ */
.move-modal-box hr {
    width: 85%;
    height: 1px;
    background: #ececec;
    margin: 1rem auto;
}

/* ============================================================
   BUTTONS
============================================================ */
.move-modal-btn {
    border: none;
    padding: 10px 22px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.move-modal-btn-confirm {
    background: #B2000C;
    color: #fff;
}
.move-modal-btn-confirm:hover {
    background: #7F0008;
    transform: translateY(-2px);
}

.move-modal-btn-cancel {
    background: #e4e4e4;
    color: #333;
}
.move-modal-btn-cancel:hover {
    background: #d6d6d6;
    transform: translateY(-2px);
}

.move-modal-button-row {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 8px;
}

</style>

<!-- ============================================================
     CONFIRM MOVE – RED
============================================================ -->
<div id="moveConfirmModal" class="move-modal-wrapper">
    <div class="move-modal-overlay"></div>
    <div class="move-modal-box">

        <h2 class="move-modal-header-warn">
            <i class="fa-solid fa-circle-exclamation"></i> Confirm Move
        </h2>
        <hr>

        <div id="moveConfirmText" class="move-modal-text move-text-error"></div>

        <div class="move-modal-button-row">
            <button class="move-modal-btn move-modal-btn-cancel" id="cancelMoveBtn">Cancel</button>
            <button class="move-modal-btn move-modal-btn-confirm" id="confirmMoveBtn">Yes, Move</button>
        </div>
    </div>
</div>

<!-- ============================================================
     SUCCESS – GREEN
============================================================ -->
<div id="moveSuccessModal" class="move-modal-wrapper">
    <div class="move-modal-overlay"></div>
    <div class="move-modal-box">

        <h2 class="move-modal-header-success">
            <i class="fa-solid fa-circle-check"></i> Success
        </h2>
        <hr>

        <div id="moveSuccessMessage" class="move-modal-text move-text-success"></div>

        <div class="move-modal-button-row">
            <button class="move-modal-btn move-modal-btn-confirm" id="moveSuccessOkBtn">OK</button>
        </div>
    </div>
</div>

<!-- ============================================================
     ERROR – RED
============================================================ -->
<div id="moveErrorModal" class="move-modal-wrapper">
    <div class="move-modal-overlay"></div>
    <div class="move-modal-box">

        <h2 class="move-modal-header-error">
            <i class="fa-solid fa-circle-xmark"></i> Cannot Move
        </h2>
        <hr>

        <div id="moveErrorMessage" class="move-modal-text move-text-error"></div>

        <div class="move-modal-button-row">
            <button class="move-modal-btn move-modal-btn-confirm" id="moveErrorOkBtn">OK</button>
        </div>
    </div>
</div>


<!-- ============================================================
     NOTHING TO MOVE – RED
============================================================ -->
<div id="moveNothingModal" class="move-modal-wrapper">
    <div class="move-modal-overlay"></div>
    <div class="move-modal-box">

        <h2 class="move-modal-header-error">
            <i class="fa-solid fa-ban"></i> Nothing to Move
        </h2>
        <hr>

        <div class="move-modal-text move-text-error">
            No invalid entries were selected.
        </div>

        <div class="move-modal-button-row">
            <button class="move-modal-btn move-modal-btn-confirm" id="moveNothingOkBtn">OK</button>
        </div>
    </div>
</div>

<!-- ============================================================
     MISSING FIELDS – BLUE
============================================================ -->
<div id="moveMissingModal" class="move-modal-wrapper">
    <div class="move-modal-overlay"></div>
    <div class="move-modal-box">

        <h2 class="move-modal-header-info">
            <i class="fa-solid fa-circle-info"></i> Missing Fields
        </h2>
        <hr>

        <div id="moveMissingContent" class="move-modal-text move-scroll-list move-text-info"></div>

        <div class="move-modal-button-row">
            <button class="move-modal-btn move-modal-btn-cancel" id="moveMissingCloseBtn">Close</button>
        </div>
    </div>
</div>


<!-- ============================
     GLOBAL VARIABLES (CONTROLLER → JS)
=============================== -->
<script>
window.showSuccessModal = {{ session('show_success_modal') ? 'true' : 'false' }};
window.showErrorModal   = {{ session('show_error_modal') ? 'true' : 'false' }};
window.showNothingModal = {{ session('show_nothing_modal') ? 'true' : 'false' }};

window.successModalMessage = `{!! session('success_modal_message') !!}`;
window.errorModalMessage   = `{!! session('error_modal_message') !!}`;

window.failedEntriesJson = @json(session('failed_entries_json', []));
window.redirect_anchor = "{{ session('redirect_anchor') }}";
</script>


<!-- ============================
     FINAL WORKING JS
=============================== -->
<script>
document.addEventListener("DOMContentLoaded", () => {

    /** ============================================================
     *  MODALS
     * ============================================================ */
    const confirmModal = document.getElementById("moveConfirmModal");
    const successModal = document.getElementById("moveSuccessModal");
    const errorModal   = document.getElementById("moveErrorModal");
    const nothingModal = document.getElementById("moveNothingModal");
    const missingModal = document.getElementById("moveMissingModal");

    /** TEXT BLOCKS */
    const confirmText  = document.getElementById("moveConfirmText");
    const successText  = document.getElementById("moveSuccessMessage");
    const errorText    = document.getElementById("moveErrorMessage");
    const missingText  = document.getElementById("moveMissingContent");

    /** BUTTONS */
    const confirmBtn       = document.getElementById("confirmMoveBtn");
    const cancelBtn        = document.getElementById("cancelMoveBtn");
    const successOkBtn     = document.getElementById("moveSuccessOkBtn");
    const errorOkBtn       = document.getElementById("moveErrorOkBtn");
    const nothingOkBtn     = document.getElementById("moveNothingOkBtn");
    const missingCloseBtn  = document.getElementById("moveMissingCloseBtn");

    /** OTHER ELEMENTS */
    const openMoveBtn = document.getElementById("openMoveModalBtn");
    const hiddenForm  = document.getElementById("moveToVerifiedForm");

    /** FAILED ENTRIES FROM CONTROLLER */
    const failedEntries = Array.isArray(window.failedEntriesJson)
        ? window.failedEntriesJson
        : [];

    /** FLAG: did Missing open from Error? */
    let reopenErrorAfterMissing = false;

    /** ============================================================
     *  HELPERS
     * ============================================================ */
    const openModal  = m => m?.classList.add("active");
    const closeModal = m => m?.classList.remove("active");

    const getInvalidCheckboxes = () =>
        document.querySelectorAll(
            '#invalid-entries-table tbody input[name="selected_invalid[]"]'
        );

    const resetHiddenForm = () => {
        const token = hiddenForm.querySelector('input[name="_token"]');
        hiddenForm.innerHTML = "";
        if (token) hiddenForm.appendChild(token);
    };

    /** ============================================================
     *  OPEN CONFIRM MOVE
     * ============================================================ */
    openMoveBtn?.addEventListener("click", () => {

        const boxes = getInvalidCheckboxes();

        if (boxes.length === 0) {
            openModal(nothingModal);
            return;
        }

        boxes.forEach(b => b.checked = true);

        confirmText.innerHTML =
            `Move <strong>${boxes.length}</strong> entr${boxes.length > 1 ? "ies" : "y"}?`;

        openModal(confirmModal);
    });

    cancelBtn?.addEventListener("click", () => closeModal(confirmModal));


    /** ============================================================
     *  CONFIRM MOVE SUBMIT
     * ============================================================ */
    confirmBtn?.addEventListener("click", () => {

        const boxes = getInvalidCheckboxes();
        resetHiddenForm();

        let selected = 0;

        boxes.forEach(cb => {
            if (cb.checked) {
                selected++;
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "selected_invalid[]";
                input.value = cb.value;
                hiddenForm.appendChild(input);
            }
        });

        if (selected === 0) {
            closeModal(confirmModal);
            openModal(nothingModal);
            return;
        }

        hiddenForm.submit();
    });


    /** ============================================================
     *  AUTO-SHOW SUCCESS MODAL
     * ============================================================ */
    if (window.showSuccessModal) {
        successText.innerHTML = window.successModalMessage;
        openModal(successModal);
    }

    successOkBtn?.addEventListener("click", () => closeModal(successModal));


    /** ============================================================
     *  AUTO-SHOW ERROR MODAL
     * ============================================================ */
    if (window.showErrorModal) {
        errorText.innerHTML = window.errorModalMessage;
        openModal(errorModal);
    }

    errorOkBtn?.addEventListener("click", () => closeModal(errorModal));


    /** ============================================================
     *  AUTO-SHOW NOTHING MODAL
     * ============================================================ */
    if (window.showNothingModal) {
        openModal(nothingModal);
    }

    nothingOkBtn?.addEventListener("click", () => closeModal(nothingModal));


    /** ============================================================
     *  OPTIONAL AUTO SCROLL HANDLING FROM CONTROLLER
     * ============================================================ */
    if (window.redirect_anchor) {
        setTimeout(() => {
            window.location.hash = window.redirect_anchor;
        }, 80);
    }


    /** ============================================================
     *  FLASH BAR: SHOW DETAILS (SUCCESS)
     * ============================================================ */
    document.addEventListener("click", e => {
        const link = e.target.closest(".success-details-link");
        if (!link) return;

        e.preventDefault();
        successText.innerHTML = window.successModalMessage;
        openModal(successModal);
    });


    /** ============================================================
     *  FLASH BAR: SHOW DETAILS (ERROR)
     * ============================================================ */
    document.addEventListener("click", e => {
        const link = e.target.closest(".error-details-link");
        if (!link) return;

        e.preventDefault();
        errorText.innerHTML = window.errorModalMessage;
        openModal(errorModal);
    });


    /** ============================================================
     *  SHOW MISSING FIELD POPUP
     * ============================================================ */
    document.addEventListener("click", e => {
        const link = e.target.closest(".show-missing-link");
        if (!link) return;

        e.preventDefault();

        const id = link.getAttribute("data-id");
        const entry = failedEntries[id];
        if (!entry) return;

        let html = `
            <h4 style="margin-bottom:10px;">
                Entry #${entry.index} – ${entry.name}
            </h4>
        `;

        Object.entries(entry.errors).forEach(([field, msgs]) => {
            const label = field.replace(/_/g, " ").replace(/\b\w/g, c => c.toUpperCase());

            // Convert non-array messages into array
            let arr = Array.isArray(msgs) ? msgs : [msgs];

            // === FIELD-SPECIFIC DEFAULT MESSAGES (used when msgs == true) ===
            const defaultMessages = {
                full_name: "Full Name is required and must contain letters only.",
                id_number: "School ID must be 6 or 7 digits.",
                course: "Course is required.",
                year_level: "Year Level must be between 1 and 4.",
                contact_number: "Contact Number must be a valid PH mobile number.",
                emergency_contact: "Emergency Contact must be valid and different from Contact Number.",
                email: "Email must be valid and end with @gmail.com or @adzu.edu.ph.",
                barangay: "Barangay is required or not recognized.",
                district: "District is required.",
                profile_picture: "Profile picture link is invalid.",
                profile_picture_local: "Unable to load profile picture.",
                fb_messenger: "FB/Messenger link must be a valid Facebook URL.",

                // Schedules (day-specific)
                monday: "Invalid or conflicting Monday schedule.",
                tuesday: "Invalid or conflicting Tuesday schedule.",
                wednesday: "Invalid or conflicting Wednesday schedule.",
                thursday: "Invalid or conflicting Thursday schedule.",
                friday: "Invalid or conflicting Friday schedule.",
                saturday: "Invalid or conflicting Saturday schedule.",
            };

            // Convert TRUE/FALSE → readable field-specific message
            arr = arr.map(v => {
                if (v === true || v === false) {
                    return defaultMessages[field] || "Invalid or missing value.";
                }
                return v;
            });

            html += `
                <div style="margin-bottom:10px;">
                    <strong style="color:#B2000C;">${label}</strong><br>
                    ${arr.join("<br>")}
                </div>
            `;
        });


        missingText.innerHTML = html;

        // 🔥 Remember if Error was open, then temporarily hide it
        reopenErrorAfterMissing = errorModal.classList.contains("active");
        if (reopenErrorAfterMissing) {
            closeModal(errorModal);
        }

        openModal(missingModal);
    });

    /** ============================================================
     *  CLOSE MISSING MODAL (AND OPTIONALLY REOPEN ERROR)
     * ============================================================ */
    missingCloseBtn?.addEventListener("click", () => {
        closeModal(missingModal);

        // If Missing came from Error, bring Error back
        if (reopenErrorAfterMissing) {
            openModal(errorModal);
            reopenErrorAfterMissing = false;
        }
    });
});


/** ============================================================
 *  MOVE SINGLE ENTRY
 * ============================================================ */
function submitMoveToValid(btn) {
    const row = btn.closest("tr");
    const cb  = row?.querySelector('input[name="selected_invalid[]"]');
    if (!cb) return;

    const form  = document.getElementById("moveToVerifiedForm");
    const token = form.querySelector('input[name="_token"]');

    form.innerHTML = "";
    if (token) form.appendChild(token);

    const input = document.createElement("input");
    input.type = "hidden";
    input.name = "selected_invalid[]";
    input.value = cb.value;

    form.appendChild(input);
    form.submit();
}

function moveToInvalid(index) {
    window.location.href = `/volunteer-import/move-valid-to-invalid/${index}`;
}
</script>
