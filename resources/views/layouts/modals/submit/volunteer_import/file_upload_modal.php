<style>

/* ===============================
   FILE MODAL WRAPPER
=============================== */
.file-modal,
.file-success-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    font-family: 'Segoe UI', Roboto, sans-serif;
}
.file-modal.active,
.file-success-modal.active {
    display: flex;
    justify-content: center;
    align-items: center;
}

/* ===============================
   OVERLAY
=============================== */
.file-modal-overlay,
.file-success-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: flex;
    justify-content: center;
    align-items: center;
}

/* ===============================
   MODAL BOX
=============================== */
.file-modal-box,
.file-success-box {
    background: #fff;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    padding: 2rem;
    animation: fadeInUp 0.3s ease forwards;
    box-shadow: 0 12px 40px rgba(0,0,0,0.35);
}

/* ===============================
   HEADER
=============================== */
.file-modal-header,
.file-success-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
}

.file-modal-header h2,
.file-success-header h2 {
    font-size: 1.6rem;
    margin: 0;
    color: #B2000C !important;
}

.file-modal-icon,
.file-success-icon {
    font-size: 2rem;
    color: #B2000C;
}

/* ===============================
   SEPARATORS
=============================== */
.file-modal-separator,
.file-success-separator {
    width: 85%;
    height: 1px;
    background: #ececec;
    margin: 1rem auto;
}

/* ===============================
   TEXT BLOCKS
=============================== */
.file-modal-text,
.file-success-text {
    text-align: left !important;
    margin: 1rem auto 1.6rem;
    padding: 0 0.75rem;
    font-size: 1.07rem;
    line-height: 1.6;
    color: #333;
    word-break: break-word;
}

/* ===============================
   BUTTONS
=============================== */
.file-modal-buttons,
.file-success-buttons {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 1.5rem;
}

.file-btn-red {
    background-color: #B2000C;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 22px;
    font-size: .95rem;
    font-weight: 600;
    cursor: pointer;
}
.file-btn-red:hover {
    background-color: #8e0009;
}

.file-btn-gray {
    background-color: #f1f1f1;
    color: #222;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 10px 22px;
    font-size: .95rem;
    font-weight: 600;
    cursor: pointer;
}
.file-btn-gray:hover {
    background-color: #e2e2e2;
}

/* ===============================
   HIGHLIGHT ONLY IMPORT + UPLOADING-AS
=============================== */

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
.import-btn.file-selected:hover {
    background: #8e0009 !important;
}

/* Smooth transitions */
.import-btn,
.uploader-info .form-control {
    transition: all .25s ease;
}

@keyframes fadeInUp {
    from { opacity:0; transform:translateY(20px); }
    to   { opacity:1; transform:translateY(0); }
}
</style>

<!-- FILE MODAL (Notice / Error / Confirm) -->
<div id="fileModal" class="file-modal">
    <div class="file-modal-overlay">
        <div class="file-modal-box">

            <div class="file-modal-header">
                <i id="fileModalIcon" class="fa-solid fa-circle-exclamation file-modal-icon"></i>
                <h2 id="fileModalTitle">Notice</h2>
            </div>

            <hr class="file-modal-separator">

            <div id="fileModalText" class="file-modal-text"></div>

            <div id="fileModalButtons" class="file-modal-buttons"></div>

        </div>
    </div>
</div>

<!-- FILE SUCCESS MODAL (RED THEME) -->
<div id="fileSuccessModal" class="file-success-modal">
    <div class="file-success-overlay">
        <div class="file-success-box">

            <div class="file-success-header">
                <i class="fa-solid fa-circle-check file-success-icon"></i>
                <h2>Success</h2>
            </div>

            <hr class="file-success-separator">

            <div id="fileSuccessText" class="file-success-text"></div>

            <div class="file-success-buttons">
                <button id="fileSuccessOkBtn" class="file-btn-red">
                    <i class="fa-solid fa-check"></i> Ok
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ===========================================================
     PREVIEW DETAILS MODAL
=========================================================== -->
<div id="previewModal" class="file-modal">
    <div class="file-modal-overlay">
        <div class="file-modal-box">

            <div class="file-modal-header">
                <i class="fa-solid fa-circle-info file-modal-icon"></i>
                <h2>Preview Summary</h2>
            </div>

            <hr class="file-modal-separator">

            <div id="previewModalContent" class="file-modal-text" style="max-height:300px; overflow-y:auto;"></div>

            <div class="file-modal-buttons">
                <button type="button" class="file-btn-red" id="previewModalCloseBtn">
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
    const fileText       = document.getElementById("fileModalText");
    const fileBtns       = document.getElementById("fileModalButtons");

    /* SUCCESS MODAL (UPLOAD SUCCESS ONLY) */
    const fileSuccessModal = document.getElementById("fileSuccessModal");
    const fileSuccessText  = document.getElementById("fileSuccessText");
    const fileSuccessOk    = document.getElementById("fileSuccessOkBtn");

    /* PREVIEW SUMMARY MODAL (VALID/INVALID/DUPES DETAILS) */
    const previewModal     = document.getElementById("previewModal");
    const previewContent   = document.getElementById("previewModalContent");
    const previewClose     = document.getElementById("previewModalCloseBtn");

    /* ==========================
       GENERIC MODAL FUNCTIONS
    ========================== */
    function openFileModal()  { fileModal.classList.add("active"); }
    function closeFileModal() { fileModal.classList.remove("active"); }

    function showFileNotice(msg) {
        fileIcon.className = "fa-solid fa-circle-exclamation file-modal-icon";
        fileTitle.textContent = "Notice";
        fileText.innerHTML = msg;
        fileBtns.innerHTML = `<button class="file-btn-red"><i class="fa-solid fa-check"></i> Ok</button>`;
        fileBtns.querySelector("button").onclick = closeFileModal;
        openFileModal();
    }

    function showFileError(msg) {
        fileIcon.className = "fa-solid fa-circle-xmark file-modal-icon";
        fileTitle.textContent = "Error";
        fileText.innerHTML = msg;
        fileBtns.innerHTML = `<button class="file-btn-red">OK</button>`;
        fileBtns.querySelector("button").onclick = closeFileModal;
        openFileModal();
    }

    function showFileConfirm(msg, yesCallback) {
        fileIcon.className = "fa-solid fa-circle-question file-modal-icon";
        fileTitle.textContent = "Confirm";
        fileText.innerHTML = msg;
        fileBtns.innerHTML = `
            <button class="file-btn-gray">
                <i class="fa-solid fa-xmark" style="margin-right:6px;"></i> No
            </button>
            <button class="file-btn-red">
                <i class="fa-solid fa-check" style="margin-right:6px;"></i> Yes
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

    /* ==========================
       UPLOAD SUCCESS MODAL
    ========================== */
    if (sessionStorage.getItem("file-upload-success")) {
        showFileSuccess(sessionStorage.getItem("file-upload-success"));
        sessionStorage.removeItem("file-upload-success");
    }

    fileSuccessOk.onclick = () => fileSuccessModal.classList.remove("active");

    /* ==========================  
       FILE UPLOAD LOGIC
    ========================== */
    const fileInput     = document.getElementById("file-upload");
    const filePath      = document.getElementById("file-path");
    const uploadBtn     = document.getElementById("file-upload-button");
    const importBtn     = document.querySelector(".uploader-info .import-btn");
    const uploaderField = document.querySelector(".uploader-info .form-control");

    // ⚠️ Do NOT return early here anymore – we still need the Show Details handler.
    if (fileInput && uploadBtn && filePath && uploaderField) {

        // Highlight logic (guard importBtn because it might not exist after import)
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

        // Choose file
        uploadBtn.onclick = () => {
            fileInput.value = "";
            fileInput.click();
        };

        // File chosen
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

        // Submit / Upload confirm — only if the Import button exists
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
    ================================================= */
    document.addEventListener('click', function(e) {
        const link = e.target.closest(".move-details-link");
        if (!link) return;

        e.preventDefault();

        const encoded = link.getAttribute("data-details");
        if (!encoded) return;

        // these already exist above
        previewContent.innerHTML = atob(encoded);
        previewModal.classList.add("active");
    });

    // (optional) close button for the preview modal
    if (previewClose) {
        previewClose.addEventListener("click", () => {
            previewModal.classList.remove("active");
        });
    }

});
</script>
