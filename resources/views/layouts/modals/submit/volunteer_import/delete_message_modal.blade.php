{{-- ===========================================================
    DELETE CONFIRM + SUCCESS MODALS
    - Confirm modal: universal-like
    - Success modal: universal-like look
    - View Details: NOW opens UNIVERSAL MODAL to avoid clashes
=========================================================== --}}

<style>
/* ============================================================
   DETAILS LIST STYLES (used when clicking "View details")
============================================================ */
.delete-scroll-list,
.undo-scroll-list {
    max-height: 280px;
    overflow-y: auto;
    padding-right: 8px;
    margin-top: 0;
}

.delete-row,
.undo-row {
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 12px;
}

.delete-row:last-child,
.undo-row:last-child { border-bottom: none; }

.delete-entry-label,
.undo-entry-label {
    font-size: 0.98rem;
    color: #495057;
    font-weight: 700;
    white-space: nowrap;
}

.delete-entry-value {
    color: #B2000C !important;
    font-weight: 800;
    cursor: default;
    text-decoration: none !important;
}

.undo-entry-value {
    color: #1565c0 !important;
    font-weight: 800;
    cursor: default;
    text-decoration: none !important;
}

/* ============================================================
   CONFIRM DELETE MODAL (Universal-like)
============================================================ */
#deleteModal.reset-import-modal{
    display:none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    align-items: center;
    justify-content: center;
}
#deleteModal.reset-import-modal.active{ display:flex; }

#deleteOverlay.reset-modal-overlay{
    position:absolute;
    inset:0;
    background: rgba(0,0,0,.45);
    display:flex;
    align-items:center;
    justify-content:center;
    padding: 16px;
}

#deleteModal .reset-modal-box{
    width: min(720px, 95vw);
    max-height: min(86vh, 900px);
    background:#fff;
    border-radius: 16px;
    overflow:hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
    display:flex;
    flex-direction: column;
}

#deleteModal .dm-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    padding: 16px 18px;
    border-bottom: 1px solid #ececec;
}
#deleteModal .dm-header-left{
    display:flex;
    gap:12px;
    align-items:flex-start;
}
#deleteModal .dm-icon{
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display:flex;
    align-items:center;
    justify-content:center;
    background: rgba(178,0,12,.10);
    color:#B2000C;
    flex: 0 0 auto;
    font-size: 18px;
    margin-top: 1px;
}
#deleteModal .dm-title{
    margin:0;
    font-size: 1.15rem;
    font-weight: 900;
    color:#111;
    line-height: 1.2;
}
#deleteModal .dm-subtitle{
    margin:6px 0 0 0;
    color:#666;
    font-size: .92rem;
    line-height: 1.35;
}
#deleteModal .dm-close{
    appearance:none;
    border:none;
    background:transparent;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#333;
    cursor:pointer;
}
#deleteModal .dm-close:hover{ background:#f3f4f6; }

#deleteModal .dm-body{
    padding: 16px 18px;
    overflow:auto;
}
#deleteModal #deleteModalText{
    margin:0;
    color:#222;
    font-size: .98rem;
}
#deleteModal #deleteModalText strong{ font-weight: 900; }

#deleteModal .dm-footer{
    padding: 14px 18px;
    border-top: 1px solid #ececec;
    display:flex;
    gap:10px;
    justify-content:flex-end;
    flex-wrap:wrap;
}

#deleteModal .reset-btn-cancel,
#deleteModal .reset-btn-confirm{
    border-radius: 12px;
    padding: 10px 14px;
    font-weight: 900;
    font-size: .95rem;
    display:inline-flex;
    align-items:center;
    gap:8px;
}
#deleteModal .reset-btn-cancel{
    background:#fff;
    border: 1px solid #d1d5db;
    color:#111;
}
#deleteModal .reset-btn-cancel:hover{ background:#f9fafb; }

#deleteModal .reset-btn-confirm{
    background:#B2000C;
    border: 1px solid #B2000C;
    color:#fff;
}
#deleteModal .reset-btn-confirm:hover{ filter: brightness(.95); }

/* ============================================================
   SUCCESS MODAL (Upgraded Universal look)
============================================================ */
#deleteSuccessModal.reset-import-modal{
    display:none;
    position: fixed;
    inset: 0;
    z-index: 99999;
    align-items:center;
    justify-content:center;
}
#deleteSuccessModal.reset-import-modal.active{ display:flex; }

#deleteSuccessOverlay.reset-modal-overlay{
    position:absolute;
    inset:0;
    background: rgba(0,0,0,.45);
    display:flex;
    align-items:center;
    justify-content:center;
    padding:16px;
}

#deleteSuccessModal .reset-modal-box{
    width: min(900px, 96vw);
    max-height: min(90vh, 920px);
    background:#fff;
    border-radius: 16px;
    overflow:hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.25);
    display:flex;
    flex-direction: column;
}

#deleteSuccessModal .um-header{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:12px;
    padding: 16px 18px;
    border-bottom: 1px solid #ececec;
}
#deleteSuccessModal .um-left{
    display:flex;
    gap:12px;
    align-items:flex-start;
}
#deleteSuccessModal .um-icon{
    width: 38px;
    height: 38px;
    border-radius: 999px;
    display:flex;
    align-items:center;
    justify-content:center;
    background: rgba(40,167,69,.12);
    color:#28a745;
    flex: 0 0 auto;
    font-size: 18px;
    margin-top: 1px;
}
#deleteSuccessModal .um-title{
    margin:0;
    font-size: 1.10rem;
    font-weight: 900;
    color:#111;
    line-height:1.2;
}
#deleteSuccessModal .um-subtitle{
    margin:4px 0 0 0;
    color:#6b7280;
    font-size: .90rem;
    line-height: 1.35;
}

#deleteSuccessModal .um-close{
    appearance:none;
    border:none;
    background:transparent;
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#333;
    cursor:pointer;
}
#deleteSuccessModal .um-close:hover{ background:#f3f4f6; }

#deleteSuccessModal .um-body{
    padding: 14px 18px 18px 18px;
    overflow:auto;
}

#deleteSuccessMessage{
    font-size: .98rem;
    color:#111;
}
#deleteSuccessMessage a{
    text-decoration:none;
    font-weight: 800;
}

#deleteSuccessModal .um-footer{
    padding: 14px 18px;
    border-top: 1px solid #ececec;
    display:flex;
    justify-content:flex-end;
    gap:10px;
}

#deleteSuccessOkBtn.reset-btn-confirm{
    border-radius: 12px;
    padding: 10px 16px;
    font-weight: 900;
    font-size: .95rem;
    background:#fff;
    border:1px solid #d1d5db;
    color:#111;
}
#deleteSuccessOkBtn.reset-btn-confirm:hover{
    background:#f9fafb;
}
</style>

<!-- ===========================================================
     DELETE CONFIRMATION MODAL
=========================================================== -->
<div id="deleteModal" class="reset-import-modal" aria-hidden="true">
    <div class="reset-modal-overlay" id="deleteOverlay" role="dialog" aria-modal="true">

        <div class="reset-modal-box">

            <div class="dm-header">
                <div class="dm-header-left">
                    <div class="dm-icon">
                        <i class="fa-solid fa-trash-can"></i>
                    </div>

                    <div>
                        <h2 class="dm-title">Delete selected entries?</h2>
                        <p class="dm-subtitle">
                            This action can be undone after deletion.
                        </p>
                    </div>
                </div>

                <button type="button" class="dm-close" id="deleteCloseBtn" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="dm-body">
                <div id="deleteModalText">
                    Are you sure you want to delete the selected entries?<br>
                    <strong>This action can be undone.</strong>
                </div>
            </div>

            <div class="dm-footer">
                <button type="button" class="reset-btn-cancel" id="deleteCancelBtn">
                    Cancel
                </button>

                <button type="button" class="reset-btn-confirm" id="deleteConfirmBtn">
                    <i class="fa-solid fa-trash-can"></i> Delete
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ===========================================================
     DELETE SUCCESS MODAL (UPGRADED UNIVERSAL LOOK)
=========================================================== -->
<div id="deleteSuccessModal" class="reset-import-modal" aria-hidden="true">
    <div class="reset-modal-overlay" id="deleteSuccessOverlay" role="dialog" aria-modal="true">

        <div class="reset-modal-box">

            <div class="um-header">
                <div class="um-left">
                    <div class="um-icon" id="deleteSuccessIconWrap">
                        <i class="fa-solid fa-circle-check" id="deleteSuccessIcon"></i>
                    </div>

                    <div>
                        <h2 class="um-title" id="deleteSuccessTitle">Success</h2>
                        <p class="um-subtitle" id="deleteSuccessSubtitle">Operation completed.</p>
                    </div>
                </div>

                <button type="button" class="um-close" id="deleteSuccessCloseBtn" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="um-body">
                <div id="deleteSuccessMessage"></div>
            </div>

            <div class="um-footer">
                <button type="button" class="reset-btn-confirm" id="deleteSuccessOkBtn">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Success HTML from controller (safe templates; avoids Blade/JS parsing issues) --}}
@if(session('delete_success'))
    <template id="__delete_success_tpl__">{!! session('delete_success') !!}</template>
@endif

@if(session('undo_success'))
    <template id="__undo_success_tpl__">{!! session('undo_success') !!}</template>
@endif

<script>
document.addEventListener("DOMContentLoaded", () => {

    const deleteModal           = document.getElementById("deleteModal");
    const deleteOverlay         = document.getElementById("deleteOverlay");
    const deleteConfirmBtn      = document.getElementById("deleteConfirmBtn");
    const deleteCancelBtn       = document.getElementById("deleteCancelBtn");
    const deleteCloseBtn        = document.getElementById("deleteCloseBtn");

    const deleteSuccessModal    = document.getElementById("deleteSuccessModal");
    const deleteSuccessOverlay  = document.getElementById("deleteSuccessOverlay");
    const deleteSuccessMessage  = document.getElementById("deleteSuccessMessage");
    const deleteSuccessOkBtn    = document.getElementById("deleteSuccessOkBtn");
    const deleteSuccessCloseBtn = document.getElementById("deleteSuccessCloseBtn");

    const deleteSuccessTitle    = document.getElementById("deleteSuccessTitle");
    const deleteSuccessSubtitle = document.getElementById("deleteSuccessSubtitle");
    const deleteSuccessIconWrap = document.getElementById("deleteSuccessIconWrap");
    const deleteSuccessIcon     = document.getElementById("deleteSuccessIcon");

    const globalDeleteForm      = document.getElementById("globalDeleteForm");

    let pendingDelete = null;

    function hasUniversal() {
        return (typeof window.showUniversalModal === "function");
    }

    function openUniversal(payload) {
        if (!hasUniversal()) return false;
        window.showUniversalModal(payload);
        return true;
    }

    function openDeleteModal(){
        if (!deleteModal) return;
        deleteModal.classList.add("active");
    }
    function closeDeleteModal(){
        if (!deleteModal) return;
        deleteModal.classList.remove("active");
        pendingDelete = null;
    }

    function openSuccessModal(html, opts = {}) {
        if (!deleteSuccessModal) return;

        deleteSuccessTitle.textContent = opts.title || "Success";
        deleteSuccessSubtitle.textContent = opts.subtitle || "Operation completed.";

        const type = opts.type || "success";
        if (type === "undo") {
            deleteSuccessIconWrap.style.background = "rgba(21,101,192,.12)";
            deleteSuccessIconWrap.style.color = "#1565c0";
            deleteSuccessIcon.className = "fa-solid fa-circle-info";
        } else {
            deleteSuccessIconWrap.style.background = "rgba(40,167,69,.12)";
            deleteSuccessIconWrap.style.color = "#28a745";
            deleteSuccessIcon.className = "fa-solid fa-circle-check";
        }

        deleteSuccessMessage.innerHTML = html || "";
        deleteSuccessModal.classList.add("active");
    }

    function closeSuccessModal(){
        if (!deleteSuccessModal) return;
        deleteSuccessModal.classList.remove("active");
    }

    /* ===========================================================
       OPEN CONFIRM MODAL when clicking .delete-btn
    =========================================================== */
    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".delete-btn");
        if (!btn) return;

        const action = btn.getAttribute("data-action") || "";
        const tableType = btn.getAttribute("data-table-type") || "";
        if (!action || !tableType) return;

        const selected = document.querySelectorAll(`input[name="selected_${tableType}[]"]:checked`);
        if (!selected.length) {
            // fallback if no selection
            if (hasUniversal()) {
                openUniversal({
                    type: "warning",
                    title: "No Selection",
                    html: "<div style='font-weight:800;'>ℹ️ No entries selected for deletion.</div>",
                    show_details: false
                });
            } else {
                openSuccessModal(
                    "<div style='font-weight:900; font-size:1.02rem;'>ℹ️ No entries selected for deletion.</div>",
                    { title: "No Selection", subtitle: "Please select at least one row.", type: "undo" }
                );
            }
            return;
        }

        pendingDelete = { action, tableType };

        deleteModal.classList.add("active");
        deleteModal.style.display = "";
    }, true);

    /* ===========================================================
       CLOSE CONFIRM MODAL
    =========================================================== */
    deleteCancelBtn?.addEventListener("click", closeDeleteModal);
    deleteCloseBtn?.addEventListener("click", closeDeleteModal);

    deleteOverlay?.addEventListener("click", (e) => {
        if (e.target === deleteOverlay) closeDeleteModal();
    });

    /* ===========================================================
       CONFIRM DELETE => submit to controller
    =========================================================== */
    deleteConfirmBtn?.addEventListener("click", () => {
        if (!pendingDelete) return;

        if (!globalDeleteForm) {
            if (hasUniversal()) {
                openUniversal({
                    type: "error",
                    title: "Error",
                    html: "⚠️ Delete form not found (#globalDeleteForm).",
                    show_details: false
                });
            } else {
                openSuccessModal(
                    "<div style='font-weight:900;'>⚠️ Delete form not found (#globalDeleteForm).</div>",
                    { title: "Error", subtitle: "Missing globalDeleteForm in the page.", type: "undo" }
                );
            }
            return;
        }

        globalDeleteForm.innerHTML = "";

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";
        const csrf = document.createElement("input");
        csrf.type = "hidden";
        csrf.name = "_token";
        csrf.value = csrfToken;
        globalDeleteForm.appendChild(csrf);

        const tableType = document.createElement("input");
        tableType.type = "hidden";
        tableType.name = "table_type";
        tableType.value = pendingDelete.tableType;
        globalDeleteForm.appendChild(tableType);

        const selected = document.querySelectorAll(`input[name="selected_${pendingDelete.tableType}[]"]:checked`);
        selected.forEach(cb => {
            const hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = "selected[]";
            hidden.value = cb.value;
            globalDeleteForm.appendChild(hidden);
        });

        globalDeleteForm.action = pendingDelete.action;
        globalDeleteForm.method = "POST";

        closeDeleteModal();
        globalDeleteForm.submit();
    });

    /* ===========================================================
       SUCCESS MODAL OPEN AFTER REDIRECT
       Prefer UNIVERSAL MODAL if available (prevents clashes)
       Fallback to custom success modal if universal missing
    =========================================================== */
    const delTpl = document.getElementById("__delete_success_tpl__");
    if (delTpl) {
        if (!openUniversal({
            type: "success",
            title: "Deletion Complete",
            html: delTpl.innerHTML,
            show_details: false
        })) {
            openSuccessModal(delTpl.innerHTML, {
                title: "Deletion Complete",
                subtitle: "Selected entries were deleted successfully.",
                type: "success"
            });
        }
    }

    const undoTpl = document.getElementById("__undo_success_tpl__");
    if (undoTpl) {
        if (!openUniversal({
            type: "success",
            title: "Entries Restored",
            html: undoTpl.innerHTML,
            show_details: false
        })) {
            openSuccessModal(undoTpl.innerHTML, {
                title: "Entries Restored",
                subtitle: "Your deleted entries were restored successfully.",
                type: "undo"
            });
        }
    }

    /* ===========================================================
       CLOSE SUCCESS MODAL (only for fallback custom modal)
    =========================================================== */
    deleteSuccessOkBtn?.addEventListener("click", closeSuccessModal);
    deleteSuccessCloseBtn?.addEventListener("click", closeSuccessModal);

    deleteSuccessOverlay?.addEventListener("click", (e) => {
        if (e.target === deleteSuccessOverlay) closeSuccessModal();
    });

    /* ===========================================================
       ✅ VIEW DETAILS → OPEN UNIVERSAL MODAL (NO MORE CUSTOM REPLACE)
       - Stops double firing
       - Uses Universal modal "details" area
       - Fallback: opens custom success modal if universal isn't available
    =========================================================== */
    document.addEventListener("click", (e) => {
        const link = e.target.closest(".deleted-details-link, .restored-details-link");
        if (!link) return;

        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === "function") e.stopImmediatePropagation();

        const detailsHtml = (link.getAttribute("data-details") || "").trim();
        if (!detailsHtml) return;

        const isDelete   = link.classList.contains("deleted-details-link");
        const rowClass   = isDelete ? "delete-row"         : "undo-row";
        const labelClass = isDelete ? "delete-entry-label" : "undo-entry-label";
        const valueClass = isDelete ? "delete-entry-value" : "undo-entry-value";
        const listClass  = isDelete ? "delete-scroll-list" : "undo-scroll-list";

        const rows = detailsHtml.split("<br>").map(item => {
            const clean = item.replace(/<[^>]+>/g, "").trim();
            if (!clean) return "";

            const parts = clean.split(":");
            const left  = (parts[0] || "").trim();
            const right = (parts.slice(1).join(":") || "").trim();

            return `
                <div class="${rowClass}">
                    <div class="${labelClass}">${left}:</div>
                    <div class="${valueClass}">${right}</div>
                </div>
            `;
        }).filter(Boolean).join("");

        const detailsBody = `<div class="${listClass}">${rows}</div>`;

        // ✅ Preferred: universal modal
        if (hasUniversal()) {
            // details_base64 because your universal supports it
            const encoded = btoa(unescape(encodeURIComponent(detailsBody)));

            openUniversal({
                type: "success",
                title: isDelete ? "Deleted Entries" : "Restored Entries",
                html: isDelete ? "✔ Deletion completed." : "✔ Restore completed.",
                details_base64: encoded,
                show_details: true
            });
            return;
        }

        // Fallback: open custom success modal with just the details list
        openSuccessModal(detailsBody, {
            title: isDelete ? "Deleted Entries" : "Restored Entries",
            subtitle: "Details",
            type: isDelete ? "success" : "undo"
        });
    }, true); // capture = true prevents other bubbling handlers from fighting

});
</script>
