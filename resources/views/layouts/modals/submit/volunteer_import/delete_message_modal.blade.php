<style>
/* ============================================================
   DELETE / UNDO MODAL LISTS (exclusive classes)
============================================================ */
.delete-scroll-list,
.undo-scroll-list {
    max-height: 240px;
    overflow-y: auto;
    padding-right: 8px;
    margin-top: 0;
}

/* Row container */
.delete-row,
.undo-row {
    padding: 10px 0;
    border-bottom: 1px dashed #d0d0d0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.delete-row:last-child,
.undo-row:last-child {
    border-bottom: none;
}

/* Entry label (left side) */
.delete-entry-label,
.undo-entry-label {
    font-size: 1.02rem;
    color: #444;
}

/* Entry name (right side) */
.delete-entry-value {
    color: #B2000C;
    font-weight: 600;
    text-decoration: underline;
    cursor: default;
}
.undo-entry-value {
    color: #1565c0;
    font-weight: 600;
    text-decoration: underline;
    cursor: default;
}

/* Title inside Success Modal */
.delete-list-title,
.undo-list-title {
    margin: 0 0 12px 0 !important;
    font-size: 1.25rem;
    font-weight: 700;
    color: #333;
    text-align: left;
    padding-left: 0.75rem;
}

.delete-entry-value,
.undo-entry-value {
    font-weight: 600;
    color: inherit; /* real color applied by class (delete/undo) */
    text-decoration: none !important; /* <-- remove underline */
}

.delete-entry-value {
    color: #B2000C !important;   /* RED */
    font-weight: 600;
    text-decoration: none !important;
}

.undo-entry-value {
    color: #1565c0 !important;   /* BLUE */
    font-weight: 600;
    text-decoration: none !important;
}

</style>

<!-- ===========================================================
     DELETE CONFIRMATION MODAL
=========================================================== -->
<div id="deleteModal" class="reset-import-modal">
    <div class="reset-modal-overlay" id="deleteOverlay">
        <div class="reset-modal-box">

            <div class="reset-modal-header">
                <i class="fa-solid fa-trash-can reset-modal-icon"></i>
                <h2 style="color:#B2000C">Delete Selected Entries?</h2>
            </div>

            <hr class="reset-modal-separator">

            <div id="deleteModalText" class="reset-text-block">
                Are you sure you want to delete the selected entries?<br>
                <strong>This action can be undone.</strong>
            </div>

            <div class="reset-modal-buttons">
                <button type="button" class="reset-btn-cancel" id="deleteCancelBtn">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </button>

                <button type="button" class="reset-btn-confirm" id="deleteConfirmBtn">
                    <i class="fa-solid fa-check"></i> Delete
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ============================================================
     DELETE SUCCESS MODAL (GREEN)
============================================================ -->
<div id="deleteSuccessModal" class="reset-import-modal">
    <div class="reset-modal-overlay" id="deleteSuccessOverlay">
        <div class="reset-modal-box">

            <div class="reset-modal-header">
                <i class="fa-solid fa-circle-check reset-success-icon"></i>
                <h2 class="reset-success-title">Deletion Complete</h2>
            </div>

            <hr class="reset-modal-separator">

            <div id="deleteSuccessMessage" class="delete-scroll-list"></div>

            <div class="reset-modal-buttons">
                <button type="button" class="reset-btn-confirm" id="deleteSuccessOkBtn">
                    <i class="fa-solid fa-check"></i> OK
                </button>
            </div>

        </div>
    </div>
</div>


<!-- ============================================================
     UNDO RESTORE SUCCESS MODAL (BLUE)
============================================================ -->
<div id="undoSuccessModal" class="reset-import-modal">
    <div class="reset-modal-overlay" id="undoSuccessOverlay">
        <div class="reset-modal-box">

            <div class="reset-modal-header">
                <i class="fa-solid fa-circle-info reset-modal-icon" style="color:#1565c0"></i>
                <h2 style="color:#1565c0;">Entries Restored</h2>
            </div>

            <hr class="reset-modal-separator">

            <div id="undoSuccessMessage" class="undo-scroll-list"></div>

            <div class="reset-modal-buttons">
                <button type="button" class="reset-btn-confirm" id="undoSuccessOkBtn">
                    <i class="fa-solid fa-check"></i> OK
                </button>
            </div>

        </div>
    </div>
</div>



<!-- Hidden form used for all deletions -->
<form id="globalDeleteForm" method="POST"></form>

@if(session('delete_success'))
<script> window.serverDeleteSuccessMessage = `{!! session('delete_success') !!}`; </script>
@endif

@if(session('undo_success'))
<script> window.serverUndoSuccessMessage = `{!! session('undo_success') !!}`; </script>
@endif

<script>
document.addEventListener("DOMContentLoaded", () => {

    /* ===========================================================
       ELEMENTS
    =========================================================== */
    const deleteModal        = document.getElementById("deleteModal");
    const deleteOverlay      = document.getElementById("deleteOverlay");
    const deleteConfirmBtn   = document.getElementById("deleteConfirmBtn");
    const deleteCancelBtn    = document.getElementById("deleteCancelBtn");

    const deleteSuccessModal   = document.getElementById("deleteSuccessModal");
    const deleteSuccessOverlay = document.getElementById("deleteSuccessOverlay");
    const deleteSuccessMessage = document.getElementById("deleteSuccessMessage");
    const deleteSuccessOkBtn   = document.getElementById("deleteSuccessOkBtn");

    const undoSuccessModal   = document.getElementById("undoSuccessModal");
    const undoSuccessOverlay = document.getElementById("undoSuccessOverlay");
    const undoSuccessMessage = document.getElementById("undoSuccessMessage");
    const undoSuccessOkBtn   = document.getElementById("undoSuccessOkBtn");

    const globalDeleteForm   = document.getElementById("globalDeleteForm");

    let pendingDelete = null;


    /* ===========================================================
       OPEN DELETE CONFIRMATION
    =========================================================== */
    window.openDeleteModal = function(tableType) {

        pendingDelete = tableType;

        const selected = document.querySelectorAll(`input[name="selected_${tableType}[]"]:checked`);

        if (!selected.length) {
            alert("No entries selected for deletion.");
            return;
        }

        deleteModal.classList.add("active");
    };


    /* ===========================================================
       CLOSE DELETE CONFIRMATION
    =========================================================== */
    function closeDeleteModal() {
        deleteModal.classList.remove("active");
        pendingDelete = null;
    }

    deleteCancelBtn?.addEventListener("click", closeDeleteModal);

    deleteOverlay?.addEventListener("click", (e) => {
        if (e.target === deleteOverlay) closeDeleteModal();
    });


    /* ===========================================================
       CONFIRM DELETE
    =========================================================== */
    deleteConfirmBtn?.addEventListener("click", () => {

        if (!pendingDelete) return;

        globalDeleteForm.innerHTML = "";

        // CSRF
        const csrf = document.createElement("input");
        csrf.type  = "hidden";
        csrf.name  = "_token";
        csrf.value = document.querySelector('meta[name="csrf-token"]').content;
        globalDeleteForm.appendChild(csrf);

        // Table type
        const field = document.createElement("input");
        field.type = "hidden";
        field.name = "table_type";
        field.value = pendingDelete;
        globalDeleteForm.appendChild(field);

        // selected rows
        const selected = document.querySelectorAll(`input[name="selected_${pendingDelete}[]"]:checked`);
        selected.forEach(cb => {
            const hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = "selected[]";
            hidden.value = cb.value;
            globalDeleteForm.appendChild(hidden);
        });

        globalDeleteForm.action = "{{ route('volunteer.deleteEntries') }}";
        globalDeleteForm.method = "POST";

        globalDeleteForm.submit();
    });


    /* ===========================================================
       SUCCESS MODAL HANDLING
    =========================================================== */
    function openDeleteSuccess(message) {
        deleteSuccessMessage.innerHTML = message;
        deleteSuccessModal.classList.add("active");
    }

    function openUndoSuccess(message) {
        undoSuccessMessage.innerHTML = message;
        undoSuccessModal.classList.add("active");
    }

    /* From backend */
    if (window.serverDeleteSuccessMessage) {
        openDeleteSuccess(window.serverDeleteSuccessMessage);
    }

    if (window.serverUndoSuccessMessage) {
        openUndoSuccess(window.serverUndoSuccessMessage);
    }


    /* ===========================================================
       CLOSE SUCCESS MODALS
    =========================================================== */
    deleteSuccessOkBtn?.addEventListener("click", () => {
        deleteSuccessModal.classList.remove("active");
    });

    undoSuccessOkBtn?.addEventListener("click", () => {
        undoSuccessModal.classList.remove("active");
    });

    deleteSuccessOverlay?.addEventListener("click", (e) => {
        if (e.target === deleteSuccessOverlay) {
            deleteSuccessModal.classList.remove("active");
        }
    });

    undoSuccessOverlay?.addEventListener("click", (e) => {
        if (e.target === undoSuccessOverlay) {
            undoSuccessModal.classList.remove("active");
        }
    });


    /* ===========================================================
    SHOW DETAILS (DELETED OR RESTORED)
    =========================================================== */
    document.addEventListener("click", (e) => {

        const link = e.target.closest(".deleted-details-link, .restored-details-link");
        if (!link) return;

        e.preventDefault();

        const detailsHtml = link.getAttribute("data-details");
        if (!detailsHtml) return;

        // Determine which modal is being used
        const isDelete   = link.classList.contains("deleted-details-link");
        const rowClass   = isDelete ? "delete-row"         : "undo-row";
        const labelClass = isDelete ? "delete-entry-label" : "undo-entry-label";
        const valueClass = isDelete ? "delete-entry-value" : "undo-entry-value";

        /* Convert each controller line into a row */
        const rows = detailsHtml.split("<br>").map(item => {

            const clean = item.replace(/<[^>]+>/g, '').trim(); // remove span tags

            const parts = clean.split(":");
            const left  = parts[0].trim();   // Entry #X
            const right = parts[1].trim();  // Name

            return `
                <div class="${rowClass}">
                    <div class="${labelClass}">${left}:</div>
                    <div class="${valueClass}">${right}</div>
                </div>
            `;
        }).join("");

        // Inject rows ONLY (title is already in HTML template)
        if (isDelete) {
            deleteSuccessMessage.innerHTML = rows;
            deleteSuccessModal.classList.add("active");
        } else {
            undoSuccessMessage.innerHTML = rows;
            undoSuccessModal.classList.add("active");
        }
    });
});
</script>

