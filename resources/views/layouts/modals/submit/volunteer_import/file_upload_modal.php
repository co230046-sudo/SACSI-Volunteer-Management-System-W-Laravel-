<style>
/* ==========================
   FILE MODALS (Redesign)
   Match Reset Modal UI
========================== */
.file-modal,
.file-success-modal{
    display:none;
    position:fixed;
    inset:0;
    z-index:99999;
    font-family:'Segoe UI', Roboto, sans-serif;
}
.file-modal.active,
.file-success-modal.active{
    display:flex;
    justify-content:center;
    align-items:center;
}

.file-modal-overlay,
.file-success-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    display:flex;
    justify-content:center;
    align-items:center;
    padding:18px;
}

/* Box */
.file-modal-box,
.file-success-box{
    width:100%;
    max-width:560px;
    background:#fff;
    border-radius:18px;
    box-shadow:0 18px 60px rgba(0,0,0,.35);
    overflow:hidden;
    border:1px solid rgba(0,0,0,.06);
    transform:translateY(6px);
    animation:filePop .18s ease-out forwards;
}

/* Header */
.file-modal-top{
    padding:18px 20px 12px;
    display:flex;
    align-items:flex-start;
    gap:12px;
}

.file-icon-wrap{
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
.file-icon-wrap i{
    font-size:18px;
    color:#B2000C;
}

.file-title-wrap{
    flex:1 1 auto;
}
.file-title{
    margin:0;
    font-size:1.22rem;
    font-weight:900;
    color:#B2000C;
    letter-spacing:.2px;
    line-height:1.2;
}
.file-subtitle{
    margin-top:6px;
    font-size:.95rem;
    color:#666;
    line-height:1.35;
}

.file-close{
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
.file-close:hover{ background:rgba(0,0,0,.06); }

.file-divider{
    height:1px;
    background:#eee;
    margin:0 20px;
}

/* Body */
.file-body{
    padding:16px 20px 18px;
}

.file-modal-text,
.file-success-text{
    text-align:left !important;
    margin:0;
    padding:0;
    font-size:1rem;
    line-height:1.65;
    color:#333;
    word-break:break-word;
}

/* Footer */
.file-footer{
    padding:14px 20px 18px;
    display:flex;
    justify-content:flex-end;
    gap:10px;
    background:#fafafa;
    border-top:1px solid #eee;
}

.file-btn{
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
    white-space:nowrap;
}
.file-btn i{ font-size:.95rem; }

/* Cancel */
.file-btn-gray{
    background:#fff;
    color:#222;
    border-color:rgba(0,0,0,.15);
}
.file-btn-gray:hover{ background:rgba(0,0,0,.04); }

/* Confirm / primary */
.file-btn-red{
    background:#B2000C;
    color:#fff;
    border-color:rgba(178,0,12,.55);
}
.file-btn-red:hover{ background:#8e0009; }

/* Optional “summary box” look (when your HTML includes it) */
.file-summary{
    margin-top:14px;
    padding:12px 14px;
    border-radius:14px;
    border:1px solid rgba(178,0,12,.18);
    background:rgba(178,0,12,.06);
}
.file-summary .file-summary-title{
    font-size:.92rem;
    font-weight:900;
    color:#B2000C;
    margin-bottom:6px;
}

/* Highlights (keep existing behavior) */
.file-selected {
    border: 2px solid #B2000C !important;
    background: rgba(178, 0, 12, 0.09) !important;
    color: #B2000C !important;
    border-radius: 6px !important;
}
.import-btn.file-selected {
    background: #B2000C !important;
    color: #fff !important;
    border-color: #B2000C !important;
}
.import-btn.file-selected:hover { background: #8e0009 !important; }

.import-btn,
.uploader-info .form-control { transition: all .25s ease; }

@keyframes filePop { to { transform:translateY(0); } }

@media (max-width: 560px){
    .file-modal-top{ padding:16px 16px 10px; }
    .file-body{ padding:14px 16px 16px; }
    .file-footer{ padding:12px 16px 16px; }
}
</style>

<!-- ==========================
     FILE MODAL (Notice/Error/Confirm)
     IDs preserved for your JS
========================== -->
<div id="fileModal" class="file-modal">
    <div class="file-modal-overlay" id="fileModalOverlay">
        <div class="file-modal-box" role="dialog" aria-modal="true" aria-labelledby="fileModalTitle">

            <div class="file-modal-top">
                <div class="file-icon-wrap">
                    <i id="fileModalIcon" class="fa-solid fa-circle-exclamation"></i>
                </div>

                <div class="file-title-wrap">
                    <h2 id="fileModalTitle" class="file-title">Notice</h2>
                    <div id="fileModalSubtitle" class="file-subtitle">
                        Please review the message below.
                    </div>
                </div>

                <button type="button" class="file-close" id="fileModalCloseBtn" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="file-divider"></div>

            <div class="file-body">
                <div id="fileModalText" class="file-modal-text"></div>
            </div>

            <div class="file-footer" id="fileModalButtons"></div>

        </div>
    </div>
</div>

<!-- ==========================
     FILE SUCCESS MODAL (Upload success only)
========================== -->
<div id="fileSuccessModal" class="file-success-modal">
    <div class="file-success-overlay" id="fileSuccessOverlay">
        <div class="file-success-box" role="dialog" aria-modal="true" aria-labelledby="fileSuccessTitle">

            <div class="file-modal-top">
                <div class="file-icon-wrap" style="background:rgba(40,167,69,.10); border-color:rgba(40,167,69,.16);">
                    <i class="fa-solid fa-circle-check" style="color:#28a745;"></i>
                </div>

                <div class="file-title-wrap">
                    <h2 id="fileSuccessTitle" class="file-title" style="color:#28a745;">Success</h2>
                    <div class="file-subtitle">Your file upload action completed.</div>
                </div>

                <button type="button" class="file-close" id="fileSuccessCloseBtn" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="file-divider"></div>

            <div class="file-body">
                <div id="fileSuccessText" class="file-success-text"></div>
            </div>

            <div class="file-footer">
                <button id="fileSuccessOkBtn" class="file-btn file-btn-red">
                    <i class="fa-solid fa-check"></i> Ok
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ==========================
     PREVIEW DETAILS MODAL (Show Details)
     Same styling as above
========================== -->
<div id="previewModal" class="file-modal">
    <div class="file-modal-overlay" id="previewOverlay">
        <div class="file-modal-box" role="dialog" aria-modal="true" aria-labelledby="previewTitle">

            <div class="file-modal-top">
                <div class="file-icon-wrap" style="background:rgba(21,101,192,.10); border-color:rgba(21,101,192,.16);">
                    <i class="fa-solid fa-circle-info" style="color:#1565c0;"></i>
                </div>

                <div class="file-title-wrap">
                    <h2 id="previewTitle" class="file-title" style="color:#1565c0;">Preview Summary</h2>
                    <div class="file-subtitle">Review the breakdown of your imported CSV.</div>
                </div>

                <button type="button" class="file-close" id="previewCloseX" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="file-divider"></div>

            <div class="file-body">
                <div id="previewModalContent" class="file-modal-text" style="max-height:320px; overflow-y:auto;"></div>
            </div>

            <div class="file-footer">
                <button type="button" class="file-btn file-btn-red" id="previewModalCloseBtn">
                    <i class="fa-solid fa-check"></i> Close
                </button>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    /* ==========================
       FILE MODAL ELEMENTS
    ========================== */
    const fileModal      = document.getElementById("fileModal");
    const fileIcon       = document.getElementById("fileModalIcon");
    const fileTitle      = document.getElementById("fileModalTitle");
    const fileSubtitle   = document.getElementById("fileModalSubtitle");
    const fileText       = document.getElementById("fileModalText");
    const fileBtns       = document.getElementById("fileModalButtons");
    const fileModalClose = document.getElementById("fileModalCloseBtn");
    const fileModalOverlay = document.getElementById("fileModalOverlay");

    /* SUCCESS MODAL (UPLOAD SUCCESS ONLY) */
    const fileSuccessModal = document.getElementById("fileSuccessModal");
    const fileSuccessText  = document.getElementById("fileSuccessText");
    const fileSuccessOk    = document.getElementById("fileSuccessOkBtn");
    const fileSuccessClose = document.getElementById("fileSuccessCloseBtn");
    const fileSuccessOverlay = document.getElementById("fileSuccessOverlay");

    /* PREVIEW SUMMARY MODAL (VALID/INVALID/DUPES DETAILS) */
    const previewModal     = document.getElementById("previewModal");
    const previewContent   = document.getElementById("previewModalContent");
    const previewCloseBtn  = document.getElementById("previewModalCloseBtn");
    const previewCloseX    = document.getElementById("previewCloseX");
    const previewOverlay   = document.getElementById("previewOverlay");

    /* ==========================
       GENERIC MODAL FUNCTIONS
    ========================== */
    function openFileModal()  { fileModal.classList.add("active"); }
    function closeFileModal() { fileModal.classList.remove("active"); }

    // close actions
    fileModalClose?.addEventListener("click", closeFileModal);
    fileModalOverlay?.addEventListener("click", (e) => { if (e.target === fileModalOverlay) closeFileModal(); });

    function showFileNotice(msg) {
        fileIcon.className = "fa-solid fa-circle-exclamation";
        fileTitle.textContent = "Notice";
        if (fileSubtitle) fileSubtitle.textContent = "Please review the message below.";
        fileText.innerHTML = msg;

        fileBtns.innerHTML = `
            <button class="file-btn file-btn-red">
                <i class="fa-solid fa-check"></i> Ok
            </button>
        `;
        fileBtns.querySelector("button").onclick = closeFileModal;

        openFileModal();
    }

    function showFileError(msg) {
        fileIcon.className = "fa-solid fa-circle-xmark";
        fileTitle.textContent = "Error";
        if (fileSubtitle) fileSubtitle.textContent = "Something went wrong. Please fix the issue and try again.";
        fileText.innerHTML = msg;

        fileBtns.innerHTML = `
            <button class="file-btn file-btn-red">
                <i class="fa-solid fa-check"></i> OK
            </button>
        `;
        fileBtns.querySelector("button").onclick = closeFileModal;

        openFileModal();
    }

    function showFileConfirm(msg, yesCallback) {
        fileIcon.className = "fa-solid fa-circle-question";
        fileTitle.textContent = "Confirm";
        if (fileSubtitle) fileSubtitle.textContent = "Please confirm before continuing.";
        fileText.innerHTML = msg;

        fileBtns.innerHTML = `
            <button class="file-btn file-btn-gray">
                <i class="fa-solid fa-xmark"></i> Cancel
            </button>
            <button class="file-btn file-btn-red">
                <i class="fa-solid fa-check"></i> Confirm
            </button>
        `;

        fileBtns.querySelector(".file-btn-gray").onclick = closeFileModal;
        fileBtns.querySelector(".file-btn-red").onclick = () => {
            closeFileModal();
            yesCallback?.();
        };

        openFileModal();
    }

    function showFileSuccess(msg) {
        fileSuccessText.innerHTML = msg;
        fileSuccessModal.classList.add("active");
    }

    function closeFileSuccess() {
        fileSuccessModal.classList.remove("active");
    }

    fileSuccessOk?.addEventListener("click", closeFileSuccess);
    fileSuccessClose?.addEventListener("click", closeFileSuccess);
    fileSuccessOverlay?.addEventListener("click", (e) => { if (e.target === fileSuccessOverlay) closeFileSuccess(); });

    /* ==========================
       UPLOAD SUCCESS MODAL
    ========================== */
    if (sessionStorage.getItem("file-upload-success")) {
        showFileSuccess(sessionStorage.getItem("file-upload-success"));
        sessionStorage.removeItem("file-upload-success");
    }

    /* ==========================
       FILE UPLOAD LOGIC (KEEP INTACT)
    ========================== */
    const fileInput     = document.getElementById("file-upload");
    const filePath      = document.getElementById("file-path");
    const uploadBtn     = document.getElementById("file-upload-button");
    const importBtn     = document.querySelector(".uploader-info .import-btn");
    const uploaderField = document.querySelector(".uploader-info .form-control");

    if (fileInput && uploadBtn && filePath && uploaderField) {

        function applyUploadHighlight() {
            if (importBtn) importBtn.classList.add("file-selected");
            uploaderField.classList.add("file-selected");
        }

        function keepOnlyUploaderHighlight() {
            if (importBtn) importBtn.classList.remove("file-selected");
            uploaderField.classList.add("file-selected");
        }

        if (sessionStorage.getItem("upload-highlight") === "1") {
            uploaderField.classList.add("file-selected");
        }

        uploadBtn.onclick = () => {
            fileInput.value = "";
            fileInput.click();
        };

        fileInput.onchange = () => {
            if (!fileInput.files.length) return;

            const name = fileInput.files[0].name;
            filePath.textContent = name;

            applyUploadHighlight();

            showFileNotice(`
                Selected File:<br>
                <strong style="color:#B2000C">${name}</strong>
            `);
        };

        if (importBtn && importBtn.form) {
            importBtn.form.addEventListener("submit", (e) => {
                e.preventDefault();

                if (!fileInput.files.length) {
                    showFileError("No file selected.");
                    return;
                }

                const name = fileInput.files[0].name;

                showFileConfirm(
                    `Upload File:<br><strong style="color:#B2000C">${name}</strong>?`,
                    () => {
                        keepOnlyUploaderHighlight();

                        sessionStorage.setItem("upload-highlight", "1");
                        sessionStorage.setItem(
                            "file-upload-success",
                            `File "<strong style="color:#B2000C">${name}</strong>" uploaded successfully.`
                        );

                        importBtn.form.submit();
                    }
                );
            });
        }
    }

    /* =================================================
       PREVIEW DETAILS → WHEN "Show Details" CLICKED
       (kept intact, now with redesigned previewModal)
    ================================================= */
    document.addEventListener('click', function(e) {
        const link = e.target.closest(".move-details-link");
        if (!link) return;

        e.preventDefault();

        const encoded = link.getAttribute("data-details");
        if (!encoded) return;

        previewContent.innerHTML = atob(encoded);
        previewModal.classList.add("active");
    });

    function closePreview() { previewModal.classList.remove("active"); }

    previewCloseBtn?.addEventListener("click", closePreview);
    previewCloseX?.addEventListener("click", closePreview);
    previewOverlay?.addEventListener("click", (e) => { if (e.target === previewOverlay) closePreview(); });

});
</script>
