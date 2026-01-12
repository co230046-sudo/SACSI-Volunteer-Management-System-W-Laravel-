<style>
:root{
    --red:#B2000C;
    --red-dark:#8e0009;
    --green:#28a745;
    --green-dark:#1f8b39;
    --orange:#a56b00;
    --gray:#666;
    --border:rgba(0,0,0,.10);
    --shadow:0 18px 60px rgba(0,0,0,.35);
}

.submit-modal,
.submit-success-modal,
.submit-error-modal{
    display:none;
    position:fixed;
    inset:0;
    z-index:99999;
    font-family:'Segoe UI', Roboto, sans-serif;
}
.submit-modal.active,
.submit-success-modal.active,
.submit-error-modal.active{
    display:flex;
    justify-content:center;
    align-items:center;
}

.submit-modal-overlay,
.submit-success-overlay,
.submit-error-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    display:flex;
    justify-content:center;
    align-items:center;
    padding:18px;
}

.submit-modal-box,
.submit-success-box,
.submit-error-box{
    width:100%;
    max-width:520px;
    background:#fff;
    border-radius:18px;
    box-shadow:var(--shadow);
    overflow:hidden;
    border:1px solid rgba(0,0,0,.06);
    transform:translateY(6px);
    animation:submitPop .18s ease-out forwards;
}
@keyframes submitPop{ to { transform:translateY(0); } }

.modal-top{
    padding:18px 20px 12px;
    display:flex;
    align-items:flex-start;
    gap:12px;
}
.icon-wrap{
    flex:0 0 auto;
    width:42px;
    height:42px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(178,0,12,.10);
    border:1px solid rgba(178,0,12,.14);
}
.icon-wrap i{
    font-size:18px;
    color:var(--red);
}
.title-wrap{ flex:1 1 auto; }
.modal-title{
    margin:0;
    font-size:1.22rem;
    font-weight:900;
    color:var(--red);
    letter-spacing:.2px;
    line-height:1.2;
}
.modal-subtitle{
    margin-top:6px;
    font-size:.95rem;
    color:var(--gray);
    line-height:1.35;
}
.modal-close{
    flex:0 0 auto;
    border:none;
    background:transparent;
    width:36px;
    height:36px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#333;
    cursor:pointer;
}
.modal-close:hover{ background:rgba(0,0,0,.06); }

.modal-divider{
    height:1px;
    background:#eee;
    margin:0 20px;
}

.modal-body{ padding:16px 20px 18px; }
.modal-text{
    text-align:left !important;
    margin:0;
    padding:0;
    font-size:1rem;
    line-height:1.65;
    color:#333;
    word-break:break-word;
}

.modal-footer{
    padding:14px 20px 18px;
    display:flex;
    justify-content:flex-end;
    gap:10px;
    background:#fafafa;
    border-top:1px solid #eee;
}

.file-btn-red,
.file-btn-gray,
.file-btn-green{
    border-radius:12px;
    padding:10px 14px;
    font-size:.95rem;
    font-weight:900;
    cursor:pointer;
    border:1px solid transparent;
    display:inline-flex;
    align-items:center;
    gap:8px;
    transition:.15s ease;
    user-select:none;
}
.file-btn-red{
    background:var(--red);
    color:#fff;
    border-color:rgba(178,0,12,.55);
}
.file-btn-red:hover{ background:var(--red-dark); }

.file-btn-gray{
    background:#fff;
    color:#222;
    border-color:rgba(0,0,0,.15);
}
.file-btn-gray:hover{ background:rgba(0,0,0,.04); }

.file-btn-green{
    background:var(--green);
    color:#fff;
    border-color:rgba(40,167,69,.45);
}
.file-btn-green:hover{ background:var(--green-dark); }

/* Confirm modal */
.submit-summary{
    margin-top:14px;
    padding:12px 14px;
    border-radius:14px;
    border:1px solid rgba(178,0,12,.18);
    background:rgba(178,0,12,.06);
}
.submit-summary-title{
    font-size:.92rem;
    font-weight:900;
    color:var(--red);
    margin-bottom:6px;
}
.submit-summary-row{
    display:flex;
    justify-content:space-between;
    gap:10px;
    font-size:.95rem;
    line-height:1.55;
}
.submit-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 10px;
    border-radius:999px;
    font-size:.86rem;
    font-weight:900;
    border:1px solid rgba(0,0,0,.10);
    background:#fff;
}
.submit-chip-ok{
    color:var(--green);
    border-color:rgba(40,167,69,.25);
    background:rgba(40,167,69,.08);
}

/* Success */
.submit-success-box{ max-width:560px; }
.submit-success-box .icon-wrap{
    background:rgba(40,167,69,.10);
    border:1px solid rgba(40,167,69,.16);
}
.submit-success-box .icon-wrap i{ color:var(--green); }
.submit-success-box .modal-title{ color:var(--green); }

/* Error modal size */
.submit-error-box{ max-width:760px; }

/* Entry list */
#errorEntryList{
    margin-top:14px;
    max-height:40vh;
    overflow-y:auto;
    padding-right:10px;
}

/* Entry card */
.entry-error-card{
    border:1px solid var(--border);
    border-radius:14px;
    padding:12px;
    background:#fff;
    margin-bottom:10px;
}
.entry-error-header{
    display:flex;
    justify-content:space-between;
    gap:10px;
    align-items:flex-start;
}
.entry-left{
    display:flex;
    flex-direction:column;
    gap:4px;
}
.entry-name{
    font-weight:900;
    color:#222;
}
.entry-meta{
    font-size:.92rem;
    color:#666;
}

/* Status badge (simple, not a pill “button”) */
.status-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 10px;
    border-radius:999px;
    font-size:.82rem;
    font-weight:900;
    border:1px solid rgba(0,0,0,.10);
    background:#f8f8f8;
    white-space:nowrap;
}
.badge-good{
    color:var(--green);
    border-color:rgba(40,167,69,.22);
    background:rgba(40,167,69,.07);
}
.badge-dup{
    color:var(--red);
    border-color:rgba(178,0,12,.22);
    background:rgba(178,0,12,.07);
}
.badge-invalid{
    color:var(--orange);
    border-color:rgba(211,139,0,.25);
    background:rgba(211,139,0,.10);
}

/* Reasons */
.reason-box{
    margin-top:10px;
    background:rgba(0,0,0,.02);
    border:1px solid rgba(0,0,0,.10);
    border-radius:12px;
    padding:10px 12px;
    color:#333;
    line-height:1.55;
    font-size:.95rem;
}
.reason-box ul{
    margin:8px 0 0;
    padding-left:18px;
}
.reason-box li{ margin:4px 0; }

/* Technical (small) */
#toggleTechDetailsBtn{
    margin:12px 0 0;
    display:none;
}
#technicalErrorBox{
    display:none;
    background:#f8f8f8;
    padding:10px 12px;
    border-radius:12px;
    border:1px solid rgba(0,0,0,.10);
    font-size:.83rem;
    max-height:140px;
    overflow:auto;
    white-space:pre-wrap;
}
#techTools{
    display:none;
    margin-top:8px;
    text-align:left;
    gap:10px;
}

@media (max-width: 720px){
    .modal-top{ padding:16px 16px 10px; }
    .modal-body{ padding:14px 16px 16px; }
    .modal-footer{ padding:12px 16px 16px; }
}
</style>

<!-- ===========================================================
     SUBMIT CONFIRMATION MODAL
=========================================================== -->
<div id="modalSubmit" class="submit-modal">
    <div class="submit-modal-overlay" id="submitConfirmOverlay">
        <div class="submit-modal-box" role="dialog" aria-modal="true" aria-labelledby="submitTitle">

            <div class="modal-top">
                <div class="icon-wrap">
                    <i class="fa-solid fa-database"></i>
                </div>

                <div class="title-wrap">
                    <h2 id="submitTitle" class="modal-title">Submit to Database</h2>
                    <div class="modal-subtitle">
                        This will permanently save the selected verified entries.
                        <strong style="color:var(--red);">This action can’t be undone.</strong>
                    </div>
                </div>

                <button type="button" class="modal-close" id="cancelSubmitX" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-divider"></div>

            <div class="modal-body">
                <div id="modalSubmitText" class="modal-text">
                    <span id="modalSubmitCount">Are you sure you want to submit?</span>
                </div>

                <div class="submit-summary">
                    <div class="submit-summary-title">Submission summary</div>
                    <div class="submit-summary-row">
                        <div style="font-weight:900; color:#333;">Status</div>
                        <div><span class="submit-chip submit-chip-ok">✅ Verified entries</span></div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
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
     SUCCESS MODAL
=========================================================== -->
<div id="submitSuccessModal" class="submit-success-modal">
    <div class="submit-success-overlay" id="submitSuccessOverlay">
        <div class="submit-success-box" role="dialog" aria-modal="true" aria-labelledby="submitSuccessTitle">

            <div class="modal-top">
                <div class="icon-wrap">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div class="title-wrap">
                    <h2 id="submitSuccessTitle" class="modal-title">Success</h2>
                    <div class="modal-subtitle">
                        Your verified entries have been saved successfully.
                    </div>
                </div>

                <button type="button" class="modal-close" id="closeSubmitSuccessX" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-divider"></div>

            <div class="modal-body">
                <div id="submitSuccessModalMessage" class="modal-text"></div>
            </div>

            <div class="modal-footer">
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
    <div class="submit-error-overlay" id="errorModalOverlay">
        <div id="errorModalBox" class="submit-error-box" role="dialog" aria-modal="true" aria-labelledby="errTitle">

            <div class="modal-top">
                <div class="icon-wrap">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>

                <div class="title-wrap">
                    <h2 id="errTitle" class="modal-title">Upload Blocked</h2>
                    <div class="modal-subtitle">
                        Fix the issue(s) below, then try submitting again.
                    </div>
                </div>

                <button type="button" class="modal-close" id="closeErrorX" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="modal-divider"></div>

            <div class="modal-body">
                <div id="errorModalMessage" class="modal-text"></div>

                <div id="errorEntryList"></div>

                <button id="toggleTechDetailsBtn" class="file-btn-gray">
                    Show Technical Details
                </button>

                <pre id="technicalErrorBox"></pre>

                <div id="techTools">
                    <button id="copyTechErrorBtn" class="file-btn-gray">
                        <i class="fa-solid fa-copy"></i> Copy
                    </button>
                </div>
            </div>

            <div class="modal-footer">
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

    function escapeHtml(str){
        return String(str ?? '')
            .replaceAll("&", "&amp;")
            .replaceAll("<", "&lt;")
            .replaceAll(">", "&gt;")
            .replaceAll('"', "&quot;")
            .replaceAll("'", "&#039;");
    }

    function safeParseJson(maybeJson){
        try {
            const obj = JSON.parse(maybeJson);
            return (obj && typeof obj === "object") ? obj : null;
        } catch {
            return null;
        }
    }

    // Map raw validation messages to short labels like you want:
    // "Invalid Character", "Invalid ID Number", etc.
    function simplifyIssue(field, message){
        const f = String(field || '').toLowerCase();
        const m = String(message || '').toLowerCase();

        if (f.includes('full_name') || f.includes('full name')) {
            if (m.includes('only letters') || m.includes('letters allowed') || m.includes('regex')) {
                return "Invalid Character";
            }
        }

        if (f.includes('id_number') || f.includes('id number')) {
            return "Invalid ID Number";
        }

        if (f.includes('email')) {
            return "Invalid Email";
        }

        if (f.includes('contact')) {
            return "Invalid Contact Number";
        }

        if (f.includes('emergency')) {
            return "Invalid Emergency Contact";
        }

        if (f.includes('barangay')) {
            return "Invalid Barangay";
        }

        if (f.includes('course')) {
            return "Invalid Course";
        }

        if (f.includes('year_level') || f.includes('year level')) {
            return "Invalid Year Level";
        }

        if (f.includes('batch_year') || f.includes('batch year')) {
            return "Invalid Batch Year";
        }

        return null;
    }

    function statusBadge(status){
        if (status === 'good') {
            return `<span class="status-badge badge-good">✅ Good to go</span>`;
        }
        if (status === 'duplicate') {
            return `<span class="status-badge badge-dup">⛔ Duplicate</span>`;
        }
        return `<span class="status-badge badge-invalid">⚠️ Invalid</span>`;
    }

    function renderReasons(reasons){
        if (!reasons || !reasons.length) return ``;
        return `
            <div class="reason-box">
                <div style="font-weight:900;color:#333;">Reason:</div>
                <ul>${reasons.map(r => `<li>${escapeHtml(r)}</li>`).join('')}</ul>
            </div>
        `;
    }

    /* ===========================================================
       ERROR MODAL (server-triggered)
    ============================================================ */
    const flashErrorModal     = document.getElementById("flashErrorModal");
    const flashTechnicalError = document.getElementById("flashTechnicalError");

    const errorModal   = document.getElementById("errorModal");
    const errOverlay   = document.getElementById("errorModalOverlay");
    const closeErrBtn  = document.getElementById("closeErrorModal");
    const closeErrX    = document.getElementById("closeErrorX");

    function closeError(){ errorModal?.classList.remove("active"); }

    closeErrBtn?.addEventListener("click", closeError);
    closeErrX?.addEventListener("click", closeError);
    errOverlay?.addEventListener("click", (e) => { if (e.target === errOverlay) closeError(); });

    if (flashErrorModal && (flashErrorModal.dataset.message || "").trim() !== "") {

        const friendly  = (flashErrorModal.dataset.message || "").trim();
        const technical = flashTechnicalError ? (flashTechnicalError.dataset.technical || "").trim() : "";

        const friendlyBox = document.getElementById("errorModalMessage");
        const entryList   = document.getElementById("errorEntryList");

        const techBox    = document.getElementById("technicalErrorBox");
        const toggleBtn  = document.getElementById("toggleTechDetailsBtn");
        const toolsBox   = document.getElementById("techTools");
        const copyBtn    = document.getElementById("copyTechErrorBtn");

        friendlyBox.innerHTML = friendly;
        errorModal.classList.add("active");

        // Build cards
        if (window.__error_entries && Array.isArray(window.__error_entries)) {

            entryList.innerHTML = window.__error_entries.map((e) => {

                const row  = e.row ?? "?";
                const name = e.name ?? "Unknown";

                // Preferred format (future): status + issues[]
                let status = String(e.status || "").toLowerCase();
                let reasons = [];

                if (Array.isArray(e.issues) && e.issues.length) {
                    reasons = e.issues.map(x => String(x));
                    if (!status) status = 'invalid';
                }

                // Current controller format: "details" has JSON of entry, including errors
                if (!status) {
                    const detailsRaw = e.details ?? "";
                    const detailsObj = (typeof detailsRaw === "string") ? safeParseJson(detailsRaw) : null;

                    if (detailsObj && detailsObj.errors) {
                        status = 'invalid';

                        // Produce short “Invalid Character” style reasons
                        const errs = detailsObj.errors;
                        const shortSet = new Set();

                        Object.entries(errs).forEach(([field, msgs]) => {
                            (Array.isArray(msgs) ? msgs : [msgs]).forEach((m) => {
                                const short = simplifyIssue(field, m);
                                if (short) shortSet.add(short);
                            });
                        });

                        reasons = Array.from(shortSet);
                        if (reasons.length === 0) reasons = ["Invalid entry data"];
                    } else {
                        // If it’s not a per-entry JSON error, it’s likely the generic system entry
                        // If your catch is duplicate (1062), we can only say “Duplicate detected”
                        if (String(friendly).toLowerCase().includes('duplicate') || String(detailsRaw).toLowerCase().includes('duplicate') || String(detailsRaw).includes('1062')) {
                            status = 'duplicate';
                            reasons = ["Volunteer Already Exist"];
                        } else {
                            status = 'invalid';
                            reasons = [String(e.details || "Upload blocked")];
                        }
                    }
                }

                // If you ever send status=good, show it too
                const metaLine = (status === 'good')
                    ? "Ready to be saved."
                    : "This entry is blocking upload.";

                return `
                    <div class="entry-error-card">
                        <div class="entry-error-header">
                            <div class="entry-left">
                                <div style="font-weight:900;color:#333;">
                                    Entry #${escapeHtml(row)} — <span class="entry-name">${escapeHtml(name)}</span>
                                </div>
                                <div class="entry-meta">${escapeHtml(metaLine)}</div>
                            </div>
                            ${statusBadge(status)}
                        </div>
                        ${status === 'good' ? '' : renderReasons(reasons)}
                    </div>
                `;
            }).join("");
        }

        // Small technical toggle
        if (technical.length > 0) {
            toggleBtn.style.display = "inline-flex";
            techBox.textContent = technical;

            toggleBtn.onclick = () => {
                const show = techBox.style.display !== "block";
                techBox.style.display  = show ? "block" : "none";
                toolsBox.style.display = show ? "flex" : "none";
                toggleBtn.innerHTML    = show ? "Hide Technical Details" : "Show Technical Details";
            };

            copyBtn.onclick = () => {
                navigator.clipboard.writeText(technical);
                copyBtn.innerHTML = "Copied!";
                setTimeout(() => copyBtn.innerHTML = `<i class="fa-solid fa-copy"></i> Copy`, 1200);
            };
        }
    }

    /* ===========================================================
       SUCCESS MODAL (server-triggered)
    ============================================================ */
    const flashSubmitSuccessModal = document.getElementById("flashSubmitSuccessModal");
    const submitSuccessModal      = document.getElementById("submitSuccessModal");
    const successOverlay          = document.getElementById("submitSuccessOverlay");

    function closeSuccess(){ submitSuccessModal?.classList.remove("active"); }

    document.getElementById("closeSubmitSuccessModal")?.addEventListener("click", closeSuccess);
    document.getElementById("closeSubmitSuccessX")?.addEventListener("click", closeSuccess);
    successOverlay?.addEventListener("click", (e) => { if (e.target === successOverlay) closeSuccess(); });

    if (flashSubmitSuccessModal && (flashSubmitSuccessModal.dataset.message || "").trim() !== "") {
        const msg = (flashSubmitSuccessModal.dataset.message || "").trim();
        const msgBox = document.getElementById("submitSuccessModalMessage");
        if (msgBox && submitSuccessModal) {
            msgBox.innerHTML = msg;
            submitSuccessModal.classList.add("active");
        }
    }

    /* ===========================================================
       SUBMIT CONFIRMATION MODAL
    ============================================================ */
    const modalSubmit   = document.getElementById("modalSubmit");
    const overlaySubmit = document.getElementById("submitConfirmOverlay");
    const openModalBtn  = document.getElementById("openSubmitModalBtn");
    const confirmBtn    = document.getElementById("confirmSubmitBtn");
    const cancelBtn     = document.getElementById("cancelSubmitBtn");
    const cancelX       = document.getElementById("cancelSubmitX");

    const validForm = document
        .getElementById("import-Section-valid")
        ?.querySelector("form");

    if (!openModalBtn || !validForm) return;

    const getTableCheckboxes = () =>
        document.querySelectorAll('#valid-entries-table tbody input[name="selected_valid[]"]');

    const getChecked = () =>
        document.querySelectorAll('#valid-entries-table tbody input[name="selected_valid[]"]:checked');

    function closeSubmitConfirm(){ modalSubmit?.classList.remove("active"); }

    openModalBtn.addEventListener("click", () => {

        const checkboxes = getTableCheckboxes();

        if (checkboxes.length === 0) {
            const msgBox = document.getElementById("errorModalMessage");
            msgBox.innerHTML = "No verified entries to submit.";
            errorModal.classList.add("active");
            return;
        }

        if (getChecked().length === 0) {
            checkboxes.forEach(c => c.checked = true);
        }

        const total = getChecked().length;

        document.getElementById("modalSubmitCount").innerHTML =
            `Submit <strong style="color:var(--green)">${total}</strong> entries to the database?`;

        modalSubmit.classList.add("active");
    });

    cancelBtn?.addEventListener("click", closeSubmitConfirm);
    cancelX?.addEventListener("click", closeSubmitConfirm);
    overlaySubmit?.addEventListener("click", (e) => { if (e.target === overlaySubmit) closeSubmitConfirm(); });

    confirmBtn.addEventListener("click", () => validForm.submit());

});
</script>
