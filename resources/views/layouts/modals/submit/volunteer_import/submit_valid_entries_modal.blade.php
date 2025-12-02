<style>
/* ===========================================================
   MODAL FRAMEWORK — SUBMIT / SUCCESS / ERROR
=========================================================== */

/* Wrapper */
.submit-modal,
.submit-success-modal,
.submit-error-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    font-family: 'Segoe UI', Roboto, sans-serif;
}
.submit-modal.active,
.submit-success-modal.active,   
.submit-error-modal.active {
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Overlay */
.submit-modal-overlay,
.submit-success-overlay,
.submit-error-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Modal Box */
.submit-modal-box,
.submit-success-box,
.submit-error-box {
    background: #fff;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    padding: 2rem;
    animation: fadeInUp 0.3s ease forwards;
    box-shadow: 0 12px 40px rgba(0,0,0,0.35);
}

/* Headers */
.submit-modal-header,
.submit-success-header,
.submit-error-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
}

.submit-modal-header h2,
.submit-success-header h2,
.submit-error-header h2 {
    font-size: 1.6rem;
    margin: 0;
    color: #B2000C;
}

.submit-modal-icon,
.submit-success-icon,
.submit-error-icon {
    font-size: 2rem;
    color: #B2000C;
}

/* Separator */
.submit-modal-separator,
.submit-success-separator,
.submit-error-separator {
    width: 85%;
    height: 1px;
    background: #ececec;
    margin: 1rem auto;
}

/* Text Body */
.submit-modal-text,
.submit-success-text,
.submit-error-text {
    text-align: left;
    margin: 1rem auto 1.6rem;
    padding: 0 0.75rem;
    font-size: 1.07rem;
    line-height: 1.6;
    color: #333;
    word-break: break-word;
}

/* Buttons */
.submit-modal-buttons,
.submit-success-buttons,
.submit-error-buttons {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 1.5rem;
}

/* Expanded technical modal */
.expanded-error-box {
    width: 95% !important;
    height: 90vh !important;
    overflow-y: auto;
}

/* ===========================================================
   SUCCESS MODAL – CLEAN GREEN THEME
=========================================================== */

/* Wrapper */
.submit-success-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    font-family: 'Segoe UI', Roboto, sans-serif;
}
.submit-success-modal.active {
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Overlay */
.submit-success-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Modal Box */
.submit-success-box {
    background: #fff;
    border-radius: 18px;
    width: 92%;
    max-width: 520px;
    padding: 2rem 2.2rem 2rem;
    animation: fadeInUp 0.28s ease forwards;
    box-shadow: 0 14px 45px rgba(0,0,0,0.35);
    text-align: center;
}

/* HEADER */
.submit-success-header {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: .6rem;
    margin-bottom: .6rem;
}

.submit-success-header h2 {
    font-size: 1.7rem;
    margin: 0;
    font-weight: 700;
    color: #28a745 !important;   /* PURE GREEN */
}

.submit-success-icon {
    font-size: 2rem;
    color: #28a745 !important;    /* PURE GREEN */
}

/* Separator */
.submit-success-separator {
    width: 85%;
    height: 1px;
    background: #e3e3e3;
    margin: 1rem auto;
}

/* Text */
.submit-success-text {
    text-align: left;
    margin: 1rem auto 1.4rem;
    font-size: 1.1rem;
    line-height: 1.62;
    color: #333;
    padding: 0 0.75rem;
}

/* BUTTON ROW */
.submit-success-buttons {
    display: flex;
    justify-content: center;
    margin-top: 1.4rem;
}

/* Green Button (consistent theme) */
.file-btn-green {
    background: #28a745;
    color: #fff;
    padding: 10px 26px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: background .2s ease, transform .15s ease;
}
.file-btn-green:hover {
    background: #1f8b39;
    transform: translateY(-2px);
}
.file-btn-green:active {
    transform: translateY(0);
}

/* ===========================================================
   ENTRY ERROR CARD STYLE (collapsible)
=========================================================== */
.entry-error-card {
    border:1px solid #ddd;
    border-radius:8px;
    padding:10px;
    background:#fafafa;
    margin-bottom:10px;
}
.entry-error-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.entry-more {
    display:none;
    margin-top:8px;
    color:#555;
    font-size:0.92rem;
}
.entry-name {
    color:#B2000C;
    font-weight:600;
}

/* Make the ERROR MODAL scrollable when content is long */
#errorModalBox {
    max-height: 85vh;          /* prevents exploding beyond screen */
    overflow-y: auto !important;
    padding-right: 18px;       /* avoid content being hidden behind scrollbar */
}

/* Make entry list itself scroll if it becomes huge */
#errorEntryList {
    max-height: 45vh;
    overflow-y: auto;
    padding-right: 10px;
}

</style>


<!-- ===========================================================
     SUBMIT CONFIRMATION MODAL
=========================================================== -->
<div id="modalSubmit" class="submit-modal">
    <div class="submit-modal-overlay">
        <div class="submit-modal-box">

            <div class="submit-modal-header">
                <i class="fa-solid fa-database submit-modal-icon"></i>
                <h2>Submit to Database</h2>
            </div>

            <hr class="submit-modal-separator">

            <div id="modalSubmitText" class="submit-modal-text">
                <span id="modalSubmitCount">Are you sure you want to submit?</span>
            </div>

            <div class="submit-modal-buttons">
                <button type="button" class="file-btn-gray" id="cancelSubmitBtn">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </button>
                <button type="button" class="file-btn-red" id="confirmSubmitBtn">
                    <i class="fa-solid fa-check"></i> Yes, Submit
                </button>
            </div>

        </div>
    </div>
</div>


<!-- ===========================================================
     SUCCESS MODAL (GREEN THEME)
=========================================================== -->
<div id="submitSuccessModal" class="submit-success-modal">
    <div class="submit-success-overlay">
        <div class="submit-success-box">

            <div class="submit-success-header">
                <i class="fa-solid fa-circle-check submit-success-icon"></i>
                <h2>Success</h2>
            </div>

            <hr class="submit-success-separator">

            <div id="submitSuccessModalMessage" class="submit-success-text"
                style="font-size:1.05rem; line-height:1.6; color:#333;"></div>

            <div class="submit-success-buttons" style="margin-top:1.7rem;">
                <button type="button" id="closeSubmitSuccessModal" class="file-btn-green">
                    <i class="fa-solid fa-check"></i> Ok
                </button>
            </div>

        </div>
    </div>
</div>


<!-- ===========================================================
     ERROR MODAL
=========================================================== -->
<div id="errorModal" class="submit-error-modal">
    <div class="submit-error-overlay">
        <div id="errorModalBox" class="submit-error-box">

            <div class="submit-error-header">
                <i class="fa-solid fa-triangle-exclamation submit-error-icon"></i>
                <h2>Error</h2>
            </div>

            <hr class="submit-error-separator">

            <div id="errorModalMessage" class="submit-error-text"></div>

            <!-- Entry Errors -->
            <div id="errorEntryList" style="margin-top:10px;"></div>

            <!-- Technical details -->
            <button id="toggleTechDetailsBtn"
                    class="file-btn-gray"
                    style="margin: 10px auto; display:none;">
                Show Technical Details
            </button>

            <pre id="technicalErrorBox"
                 style="display:none;background:#f8f8f8;padding:15px;border-radius:10px;
                        font-size:0.85rem;max-height:200px;overflow-y:auto;
                        border:1px solid #ddd;white-space:pre-wrap;">
            </pre>

            <div id="techTools" style="display:none;text-align:center;margin-top:10px;">
                <button id="copyTechErrorBtn" class="file-btn-gray">
                    <i class="fa-solid fa-copy"></i> Copy
                </button>
                <button id="expandTechErrorBtn" class="file-btn-gray">
                    <i class="fa-solid fa-expand"></i> Expand
                </button>
            </div>

            <div class="submit-error-buttons" style="margin-top:20px;">
                <button type="button" id="closeErrorModal" class="file-btn-red">
                    <i class="fa-solid fa-xmark"></i> Close
                </button>
            </div>

        </div>
    </div>
</div>


<!-- ===========================================================
     FLASH DATA (SERVER → JS)
=========================================================== -->
@if(session('error_modal'))
<div id="flashErrorModal" data-message="{!! session('error_modal') !!}"></div>
@endif

@if(session('error_modal_technical'))
<div id="flashTechnicalError" data-technical="{{ session('error_modal_technical') }}"></div>
@endif

@if(session('submit_success'))
<div id="flashSubmitSuccessModal" data-message="{!! session('submit_success') !!}"></div>
@endif


@if(session('error_modal_entries'))
<script>
    window.__error_entries = @json(session('error_modal_entries'));
</script>
@endif

<script>
document.addEventListener("DOMContentLoaded", () => {

    const flashErrorModal     = document.getElementById("flashErrorModal");
    const flashTechnicalError = document.getElementById("flashTechnicalError");

    const errorModal          = document.getElementById("errorModal");

    /*
    |----------------------------------------------------------------------
    | SUBMIT SUCCESS MODAL (isolated)
    |----------------------------------------------------------------------
    */
    const flashSubmitSuccessModal = document.getElementById("flashSubmitSuccessModal");
    const submitSuccessModal      = document.getElementById("submitSuccessModal"); // isolated ID

    /* ===========================================================
       ERROR MODAL
    ============================================================ */
    if (flashErrorModal && flashErrorModal.dataset.message.trim() !== "") {

        const friendly  = flashErrorModal.dataset.message.trim();
        const technical = flashTechnicalError ? flashTechnicalError.dataset.technical.trim() : "";

        const modalBox     = document.getElementById("errorModalBox");
        const friendlyBox  = document.getElementById("errorModalMessage");
        const techBox      = document.getElementById("technicalErrorBox");
        const toggleBtn    = document.getElementById("toggleTechDetailsBtn");
        const copyBtn      = document.getElementById("copyTechErrorBtn");
        const expandBtn    = document.getElementById("expandTechErrorBtn");
        const toolsBox     = document.getElementById("techTools");
        const entryList    = document.getElementById("errorEntryList");

        friendlyBox.innerHTML = friendly;
        errorModal.classList.add("active");

        /* Entry list rendering */
        if (window.__error_entries && Array.isArray(window.__error_entries)) {
            entryList.innerHTML = window.__error_entries.map((e, i) => `
                <div class="entry-error-card">
                    <div class="entry-error-header">
                        <div>
                            <strong>Entry #${e.row ?? "?"}</strong> —
                            <span class="entry-name">${e.name ?? "Unknown"}</span>
                        </div>
                        <a href="#" class="toggleEntryMore" data-target="entryMore${i}">
                            View more +
                        </a>
                    </div>
                    <div id="entryMore${i}" class="entry-more">
                        ${e.details ?? "<em>No details provided.</em>"}
                    </div>
                </div>
            `).join("");

            document.querySelectorAll(".toggleEntryMore").forEach(btn => {
                btn.addEventListener("click", ev => {
                    ev.preventDefault();
                    const div  = document.getElementById(btn.dataset.target);
                    const show = (div.style.display === "none" || div.style.display === "");
                    div.style.display = show ? "block" : "none";
                    btn.innerHTML    = show ? "Hide −" : "View more +";
                });
            });
        }

        if (technical.length > 0) {
            toggleBtn.style.display = "block";
            techBox.textContent = technical;

            toggleBtn.onclick = () => {
                const show = techBox.style.display !== "block";
                techBox.style.display   = show ? "block" : "none";
                toolsBox.style.display  = show ? "block" : "none";
                toggleBtn.innerHTML     = show ? "Hide Technical Details" : "Show Technical Details";
            };

            copyBtn.onclick = () => {
                navigator.clipboard.writeText(technical);
                copyBtn.innerHTML = "Copied!";
                setTimeout(() => copyBtn.innerHTML = "Copy", 1200);
            };

            expandBtn.onclick = () => {
                const expanded = modalBox.classList.toggle("expanded-error-box");
                expandBtn.innerHTML = expanded ? "Collapse" : "Expand";
                techBox.style.maxHeight = expanded ? "60vh" : "200px";
            };
        }
    }

    document.getElementById("closeErrorModal")?.addEventListener("click", () => {
        errorModal.classList.remove("active");
    });

    /* ===========================================================
       SUBMIT SUCCESS MODAL — ONLY FOR submit_success
    ============================================================ */
    if (flashSubmitSuccessModal && flashSubmitSuccessModal.dataset.message.trim() !== "") {

        const msg = flashSubmitSuccessModal.dataset.message.trim();

        const msgBox = document.getElementById("submitSuccessModalMessage");
        if (msgBox && submitSuccessModal) {
            msgBox.innerHTML = msg;
            submitSuccessModal.classList.add("active");
        }
    }

    document.getElementById("closeSubmitSuccessModal")?.addEventListener("click", () => {
        submitSuccessModal?.classList.remove("active");
    });

    /* ===========================================================
       SUBMIT CONFIRMATION MODAL
    ============================================================ */
    const modalSubmit   = document.getElementById("modalSubmit");
    const openModalBtn  = document.getElementById("openSubmitModalBtn");
    const confirmBtn    = document.getElementById("confirmSubmitBtn");
    const cancelBtn     = document.getElementById("cancelSubmitBtn");

    const validForm = document
        .getElementById("import-Section-valid")
        ?.querySelector("form");

    if (!openModalBtn || !validForm) return;

    const getTableCheckboxes = () =>
        document.querySelectorAll('#valid-entries-table tbody input[name="selected_valid[]"]');

    const getChecked = () =>
        document.querySelectorAll('#valid-entries-table tbody input[name="selected_valid[]"]:checked');

    openModalBtn.addEventListener("click", () => {

        const checkboxes = getTableCheckboxes();

        if (checkboxes.length === 0) {
            document.getElementById("errorModalMessage").innerHTML =
                "No verified entries to submit.";
            errorModal.classList.add("active");
            return;
        }

        if (getChecked().length === 0) {
            checkboxes.forEach(c => c.checked = true);
        }

        const total = getChecked().length;

        document.getElementById("modalSubmitCount").innerHTML =
            `Submit <strong style="color:#28a745">${total}</strong> entries to the database?`;

        modalSubmit.classList.add("active");
    });

    cancelBtn.addEventListener("click", () => modalSubmit.classList.remove("active"));
    confirmBtn.addEventListener("click", () => validForm.submit());

});
</script>
