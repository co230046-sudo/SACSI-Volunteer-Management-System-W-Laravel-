{{-- ============================================================
   MOVE ENTRIES MODALS (Consistent with Submit/Reset style)
   ✅ PATCH: lower overlay stacking so it won't sit on top of other overlays/modals
   ✅ PATCH: prevent body scroll when any move modal is open
   ✅ PATCH: keep ALL your JS/functions intact (no removals), only adds tiny helpers
============================================================ --}}

<style>
/* ===========================================================
   SHARED BASE (match submit/reset modals)
=========================================================== */
:root{
    --red:#B2000C;
    --red-dark:#8e0009;
    --green:#28a745;
    --green-dark:#1f8b39;
    --blue:#1565c0;
    --gray:#666;
    --border:rgba(0,0,0,.10);
    --shadow:0 18px 60px rgba(0,0,0,.35);
}

/* ✅ PATCH: lock background scroll when modal active */
body.move-modal-open{ overflow:hidden !important; }

/* ✅ PATCH: LOWER z-index so it doesn't cover other overlays */
.move-modal-wrapper{
    display:none;
    position:fixed;
    inset:0;
    z-index: 9000; /* was 99999 */
    font-family:'Segoe UI', Roboto, sans-serif;
}
.move-modal-wrapper.active{
    display:flex;
    justify-content:center;
    align-items:center;
}

.move-modal-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    display:flex;
    justify-content:center;
    align-items:center;
    padding:18px;

    /* ✅ PATCH: keep overlay below other modals that use 9999+ etc */
    z-index: 9000;
}

/* Modal box */
.move-modal-box{
    width:100%;
    max-width:520px;
    background:#fff;
    border-radius:18px;
    box-shadow:var(--shadow);
    overflow:hidden;
    border:1px solid rgba(0,0,0,.06);
    transform:translateY(6px);
    animation:movePop .18s ease-out forwards;

    /* ✅ PATCH: ensure box is above its own overlay */
    position:relative;
    z-index: 9001;
}
@keyframes movePop{ to { transform:translateY(0); } }

/* Header */
.move-modal-top{
    padding:18px 20px 12px;
    display:flex;
    align-items:flex-start;
    gap:12px;
}
.move-icon-wrap{
    flex:0 0 auto;
    width:42px;
    height:42px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    border:1px solid rgba(0,0,0,.08);
    background:rgba(0,0,0,.04);
}
.move-icon-wrap i{ font-size:18px; }
.move-title-wrap{ flex:1 1 auto; }
.move-modal-title{
    margin:0;
    font-size:1.22rem;
    font-weight:900;
    letter-spacing:.2px;
    line-height:1.2;
    color:#222;
}
.move-modal-subtitle{
    margin-top:6px;
    font-size:.95rem;
    color:var(--gray);
    line-height:1.35;
}
.move-modal-close{
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
.move-modal-close:hover{ background:rgba(0,0,0,.06); }

/* Divider */
.move-modal-divider{
    height:1px;
    background:#eee;
    margin:0 20px;
}

/* Body */
.move-modal-body{ padding:16px 20px 18px; }
.move-modal-text{
    text-align:left !important;
    margin:0;
    padding:0;
    font-size:1rem;
    line-height:1.65;
    color:#333;
    word-break:break-word;
}

/* Footer */
.move-modal-footer{
    padding:14px 20px 18px;
    display:flex;
    justify-content:flex-end;
    gap:10px;
    background:#fafafa;
    border-top:1px solid #eee;
}

/* Buttons (use your submit button styles for consistency) */
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

/* Theme helpers */
.theme-warn .move-icon-wrap{
    background:rgba(178,0,12,.10);
    border-color:rgba(178,0,12,.14);
}
.theme-warn .move-icon-wrap i{ color:var(--red); }
.theme-warn .move-modal-title{ color:var(--red); }

.theme-success .move-icon-wrap{
    background:rgba(40,167,69,.10);
    border-color:rgba(40,167,69,.16);
}
.theme-success .move-icon-wrap i{ color:var(--green); }
.theme-success .move-modal-title{ color:var(--green); }

.theme-error .move-icon-wrap{
    background:rgba(178,0,12,.10);
    border-color:rgba(178,0,12,.14);
}
.theme-error .move-icon-wrap i{ color:var(--red); }
.theme-error .move-modal-title{ color:var(--red); }

.theme-info .move-icon-wrap{
    background:rgba(21,101,192,.10);
    border-color:rgba(21,101,192,.18);
}
.theme-info .move-icon-wrap i{ color:var(--blue); }
.theme-info .move-modal-title{ color:var(--blue); }

/* Scroll list inside body (for moved entries / failed list / missing fields) */
.move-scroll{
    margin-top:12px;
    max-height:38vh;
    overflow:auto;
    padding-right:8px;
}
.move-item{
    border:1px solid var(--border);
    border-radius:14px;
    padding:10px 12px;
    background:#fff;
    margin-bottom:10px;
}
.move-item-title{
    font-weight:900;
    color:#222;
    display:flex;
    justify-content:space-between;
    gap:10px;
}
.move-item-title .name{ color:var(--red); font-weight:900; }
.theme-success .move-item-title .name{ color:var(--green); }
.move-item-sub{ font-size:.92rem; color:#666; margin-top:4px; }

/* Missing field block (compact + consistent) */
.move-missing-block{
    margin-top:10px;
    padding:12px 12px;
    border-radius:14px;
    border:1px solid rgba(21,101,192,.18);
    background:rgba(21,101,192,.06);
    color:#222;
}
.move-missing-block .field{
    font-weight:900;
    color:var(--blue);
    margin-top:8px;
}
.move-missing-block .msgs{
    margin-top:4px;
    color:#333;
    line-height:1.55;
    font-size:.95rem;
}

/* Mobile */
@media (max-width: 720px){
    .move-modal-top{ padding:16px 16px 10px; }
    .move-modal-body{ padding:14px 16px 16px; }
    .move-modal-footer{ padding:12px 16px 16px; }
}
</style>

<!-- ===========================================================
     CONFIRM MOVE (Warn/Red)
=========================================================== -->
<div id="moveConfirmModal" class="move-modal-wrapper">
    <div class="move-modal-overlay" id="moveConfirmOverlay">
        <div class="move-modal-box theme-warn" role="dialog" aria-modal="true" aria-labelledby="moveConfirmTitle">
            <div class="move-modal-top">
                <div class="move-icon-wrap"><i class="fa-solid fa-circle-exclamation"></i></div>
                <div class="move-title-wrap">
                    <h2 id="moveConfirmTitle" class="move-modal-title">Confirm Move</h2>
                    <div class="move-modal-subtitle">
                        This will re-check the selected entries. Only entries with no errors can be moved.
                    </div>
                </div>
                <button type="button" class="move-modal-close" id="cancelMoveX" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="move-modal-divider"></div>

            <div class="move-modal-body">
                <div id="moveConfirmText" class="move-modal-text"></div>
            </div>

            <div class="move-modal-footer">
                <button class="file-btn-gray" id="cancelMoveBtn" type="button">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </button>
                <button class="file-btn-red" id="confirmMoveBtn" type="button">
                    <i class="fa-solid fa-check"></i> Yes, Move
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===========================================================
     SUCCESS (Green)
=========================================================== -->
<div id="moveSuccessModal" class="move-modal-wrapper">
    <div class="move-modal-overlay" id="moveSuccessOverlay">
        <div class="move-modal-box theme-success" role="dialog" aria-modal="true" aria-labelledby="moveSuccessTitle">
            <div class="move-modal-top">
                <div class="move-icon-wrap"><i class="fa-solid fa-circle-check"></i></div>
                <div class="move-title-wrap">
                    <h2 id="moveSuccessTitle" class="move-modal-title">Success</h2>
                    <div class="move-modal-subtitle">Entries moved successfully.</div>
                </div>
                <button type="button" class="move-modal-close" id="moveSuccessX" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="move-modal-divider"></div>

            <div class="move-modal-body">
                <div id="moveSuccessMessage" class="move-modal-text"></div>
            </div>

            <div class="move-modal-footer">
                <button class="file-btn-green" id="moveSuccessOkBtn" type="button">
                    <i class="fa-solid fa-check"></i> Ok
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===========================================================
     ERROR (Red)
=========================================================== -->
<div id="moveErrorModal" class="move-modal-wrapper">
    <div class="move-modal-overlay" id="moveErrorOverlay">
        <div class="move-modal-box theme-error" role="dialog" aria-modal="true" aria-labelledby="moveErrorTitle">
            <div class="move-modal-top">
                <div class="move-icon-wrap"><i class="fa-solid fa-circle-xmark"></i></div>
                <div class="move-title-wrap">
                    <h2 id="moveErrorTitle" class="move-modal-title">Cannot Move</h2>
                    <div class="move-modal-subtitle">
                        Some entries still have missing/invalid fields.
                    </div>
                </div>
                <button type="button" class="move-modal-close" id="moveErrorX" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="move-modal-divider"></div>

            <div class="move-modal-body">
                <div id="moveErrorMessage" class="move-modal-text"></div>
            </div>

            <div class="move-modal-footer">
                <button class="file-btn-red" id="moveErrorOkBtn" type="button">
                    <i class="fa-solid fa-xmark"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===========================================================
     NOTHING SELECTED (Red)
=========================================================== -->
<div id="moveNothingModal" class="move-modal-wrapper">
    <div class="move-modal-overlay" id="moveNothingOverlay">
        <div class="move-modal-box theme-error" role="dialog" aria-modal="true" aria-labelledby="moveNothingTitle">
            <div class="move-modal-top">
                <div class="move-icon-wrap"><i class="fa-solid fa-ban"></i></div>
                <div class="move-title-wrap">
                    <h2 id="moveNothingTitle" class="move-modal-title">Nothing to Move</h2>
                    <div class="move-modal-subtitle">Select at least one entry first.</div>
                </div>
                <button type="button" class="move-modal-close" id="moveNothingX" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="move-modal-divider"></div>

            <div class="move-modal-body">
                <div class="move-modal-text">No entries were selected.</div>
            </div>

            <div class="move-modal-footer">
                <button class="file-btn-red" id="moveNothingOkBtn" type="button">
                    <i class="fa-solid fa-check"></i> Ok
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===========================================================
     MISSING FIELDS (Blue)
=========================================================== -->
<div id="moveMissingModal" class="move-modal-wrapper">
    <div class="move-modal-overlay" id="moveMissingOverlay">
        <div class="move-modal-box theme-info" role="dialog" aria-modal="true" aria-labelledby="moveMissingTitle">
            <div class="move-modal-top">
                <div class="move-icon-wrap"><i class="fa-solid fa-circle-info"></i></div>
                <div class="move-title-wrap">
                    <h2 id="moveMissingTitle" class="move-modal-title">Missing Fields</h2>
                    <div class="move-modal-subtitle">Here’s what is still blocking this entry.</div>
                </div>
                <button type="button" class="move-modal-close" id="moveMissingX" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="move-modal-divider"></div>

            <div class="move-modal-body">
                <div id="moveMissingContent" class="move-modal-text"></div>
            </div>

            <div class="move-modal-footer">
                <button class="file-btn-gray" id="moveMissingCloseBtn" type="button">
                    <i class="fa-solid fa-xmark"></i> Close
                </button>
            </div>
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

<script>
document.addEventListener("DOMContentLoaded", () => {

  /* ============================================================
     HELPERS
  ============================================================ */
  const openModal  = m => {
    if (!m) return;
    m.classList.add("active");
    document.body.classList.add("move-modal-open"); // ✅ PATCH
  };

  const closeModal = m => {
    if (!m) return;
    m.classList.remove("active");

    // ✅ PATCH: only remove body lock if no move modal is active
    const anyOpen = document.querySelector(".move-modal-wrapper.active");
    if (!anyOpen) document.body.classList.remove("move-modal-open");
  };

  function escHtml(str){
    return String(str ?? '')
      .replaceAll("&","&amp;")
      .replaceAll("<","&lt;")
      .replaceAll(">","&gt;")
      .replaceAll('"',"&quot;")
      .replaceAll("'","&#039;");
  }

  /* ============================================================
     MODALS
  ============================================================ */
  const confirmModal = document.getElementById("moveConfirmModal");
  const successModal = document.getElementById("moveSuccessModal");
  const errorModal   = document.getElementById("moveErrorModal");
  const nothingModal = document.getElementById("moveNothingModal");
  const missingModal = document.getElementById("moveMissingModal");

  const confirmText  = document.getElementById("moveConfirmText");
  const successText  = document.getElementById("moveSuccessMessage");
  const errorText    = document.getElementById("moveErrorMessage");
  const missingText  = document.getElementById("moveMissingContent");

  const confirmBtn      = document.getElementById("confirmMoveBtn");
  const cancelBtn       = document.getElementById("cancelMoveBtn");
  const cancelX         = document.getElementById("cancelMoveX");
  const successOkBtn    = document.getElementById("moveSuccessOkBtn");
  const successX        = document.getElementById("moveSuccessX");
  const errorOkBtn      = document.getElementById("moveErrorOkBtn");
  const errorX          = document.getElementById("moveErrorX");
  const nothingOkBtn    = document.getElementById("moveNothingOkBtn");
  const nothingX        = document.getElementById("moveNothingX");
  const missingCloseBtn = document.getElementById("moveMissingCloseBtn");
  const missingX        = document.getElementById("moveMissingX");

  const openMoveBtn = document.getElementById("openMoveModalBtn");
  const hiddenForm  = document.getElementById("moveToVerifiedForm");

  const failedEntries = Array.isArray(window.failedEntriesJson) ? window.failedEntriesJson : [];
  let reopenErrorAfterMissing = false;

  function getInvalidCheckboxes() {
    return [...document.querySelectorAll('#invalid-entries-table tbody input[name="selected_invalid[]"]')];
  }

  function resetHiddenForm() {
    if (!hiddenForm) return;
    const token = hiddenForm.querySelector('input[name="_token"]');
    hiddenForm.innerHTML = "";
    if (token) hiddenForm.appendChild(token);
  }

  /* ============================================================
     CLOSE HANDLERS
  ============================================================ */
  cancelBtn?.addEventListener("click", () => closeModal(confirmModal));
  cancelX?.addEventListener("click", () => closeModal(confirmModal));

  successOkBtn?.addEventListener("click", () => closeModal(successModal));
  successX?.addEventListener("click", () => closeModal(successModal));

  errorOkBtn?.addEventListener("click", () => closeModal(errorModal));
  errorX?.addEventListener("click", () => closeModal(errorModal));

  nothingOkBtn?.addEventListener("click", () => closeModal(nothingModal));
  nothingX?.addEventListener("click", () => closeModal(nothingModal));

  missingCloseBtn?.addEventListener("click", () => {
    closeModal(missingModal);
    if (reopenErrorAfterMissing) {
      openModal(errorModal);
      reopenErrorAfterMissing = false;
    }
  });
  missingX?.addEventListener("click", () => {
    closeModal(missingModal);
    if (reopenErrorAfterMissing) {
      openModal(errorModal);
      reopenErrorAfterMissing = false;
    }
  });

  // overlay click close (keeps behavior, but safe)
  document.querySelectorAll(".move-modal-overlay").forEach(overlay => {
    overlay.addEventListener("click", (e) => {
      if (e.target !== overlay) return;
      const wrapper = overlay.closest(".move-modal-wrapper");
      closeModal(wrapper);
    });
  });

  // ✅ PATCH: ESC closes topmost opened move modal (does not remove any existing behavior)
  document.addEventListener("keydown", (e) => {
    if (e.key !== "Escape") return;
    const topOpen = document.querySelector(".move-modal-wrapper.active");
    if (topOpen) closeModal(topOpen);
  });

  /* ============================================================
     BULK MOVE (selected invalid -> valid)
  ============================================================ */
  openMoveBtn?.addEventListener("click", () => {
    const boxes = getInvalidCheckboxes();
    if (boxes.length === 0) { openModal(nothingModal); return; }

    const checked = boxes.filter(b => b.checked);
    if (checked.length === 0) { openModal(nothingModal); return; }

    confirmText.innerHTML =
      `Move <strong style="color:var(--red);">${checked.length}</strong> entr${checked.length > 1 ? "ies" : "y"} to <strong>Verified</strong>?`;

    openModal(confirmModal);
  });

  confirmBtn?.addEventListener("click", () => {
    if (!hiddenForm) return;

    const checked = getInvalidCheckboxes().filter(cb => cb.checked);
    resetHiddenForm();

    if (checked.length === 0) {
      closeModal(confirmModal);
      openModal(nothingModal);
      return;
    }

    checked.forEach(cb => {
      const input = document.createElement("input");
      input.type = "hidden";
      input.name = "selected_invalid[]";
      input.value = cb.value;
      hiddenForm.appendChild(input);
    });

    hiddenForm.submit();
  });

  /* ============================================================
     AUTO SHOW: SUCCESS / ERROR / NOTHING, and anchor
  ============================================================ */
  if (window.showSuccessModal) {
    successText.innerHTML = window.successModalMessage;
    openModal(successModal);
  }

  if (window.showErrorModal) {
    errorText.innerHTML = window.errorModalMessage;
    openModal(errorModal);
  }

  if (window.showNothingModal) openModal(nothingModal);

  if (window.redirect_anchor) {
    setTimeout(() => { window.location.hash = window.redirect_anchor; }, 80);
  }

  /* ============================================================
     FLASH BAR DETAILS LINKS (same classes you already emit)
  ============================================================ */
  document.addEventListener("click", e => {
    const link = e.target.closest(".success-details-link");
    if (!link) return;
    e.preventDefault();
    successText.innerHTML = window.successModalMessage;
    openModal(successModal);
  });

  document.addEventListener("click", e => {
    const link = e.target.closest(".error-details-link");
    if (!link) return;
    e.preventDefault();
    errorText.innerHTML = window.errorModalMessage;
    openModal(errorModal);
  });

  /* ============================================================
     MISSING FIELD POPUP (from failed_entries_json)
     - Displays compact, consistent “blue info” content
  ============================================================ */
  document.addEventListener("click", e => {
    const link = e.target.closest(".show-missing-link");
    if (!link) return;

    e.preventDefault();

    const id = link.getAttribute("data-id");
    const entry = failedEntries[id];
    if (!entry) return;

    let html = `
      <div style="font-weight:900; font-size:1.05rem; margin-bottom:8px;">
        Entry #${escHtml(entry.index)} — ${escHtml(entry.name)}
      </div>
      <div class="move-missing-block">
    `;

    const defaultMessages = {
      full_name: "Full Name is required and must contain letters only.",
      id_number: "School ID must be 6 or 7 digits.",
      course: "Course is required.",
      year_level: "Year Level must be between 1 and 4.",
      batch_year: "Batch year must be a valid 4-digit year (e.g., 2023).",
      contact_number: "Contact Number must be a valid PH mobile number.",
      emergency_contact: "Emergency Contact must be valid and different from Contact Number.",
      email: "Email must be valid and end with @gmail.com or @adzu.edu.ph.",
      barangay: "Barangay is required or not recognized.",
      district: "District is required.",
      fb_messenger: "FB/Messenger link must be a valid Facebook/Messenger URL.",
      monday: "Invalid or conflicting Monday schedule.",
      tuesday: "Invalid or conflicting Tuesday schedule.",
      wednesday: "Invalid or conflicting Wednesday schedule.",
      thursday: "Invalid or conflicting Thursday schedule.",
      friday: "Invalid or conflicting Friday schedule.",
      saturday: "Invalid or conflicting Saturday schedule.",
    };

    Object.entries(entry.errors || {}).forEach(([field, msgs]) => {
      const label = field.replace(/_/g, " ").replace(/\b\w/g, c => c.toUpperCase());
      let arr = Array.isArray(msgs) ? msgs : [msgs];

      arr = arr.map(v => (v === true || v === false)
        ? (defaultMessages[field] || "Invalid or missing value.")
        : String(v)
      );

      html += `
        <div class="field">${escHtml(label)}</div>
        <div class="msgs">${arr.map(escHtml).join("<br>")}</div>
      `;
    });

    html += `</div>`;

    missingText.innerHTML = html;

    reopenErrorAfterMissing = errorModal.classList.contains("active");
    if (reopenErrorAfterMissing) closeModal(errorModal);

    openModal(missingModal);
  });

});

/* ============================================================
   ✅ 1) SINGLE INVALID -> VALID
============================================================ */
window.submitMoveToValid = function(btn) {
  try {
    const form = document.getElementById("moveToVerifiedForm");
    if (!form) return;

    const cb = (function resolve() {
      const row = btn.closest("tr");
      if (row) {
        const c = row.querySelector('input[name="selected_invalid[]"]');
        if (c) return c;
      }

      const idx = btn.getAttribute("data-index");
      if (idx !== null && idx !== undefined && idx !== "") {
        const esc = (window.CSS && CSS.escape) ? CSS.escape(String(idx)) : String(idx);
        const byValue = document.querySelector(
          `#invalid-entries-table tbody input[name="selected_invalid[]"][value="${esc}"]`
        );
        if (byValue) return byValue;
      }
      return null;
    })();

    if (!cb) {
      console.warn("submitMoveToValid: could not resolve checkbox.");
      return;
    }

    // keep token
    const token = form.querySelector('input[name="_token"]');
    form.innerHTML = "";
    if (token) form.appendChild(token);

    const input = document.createElement("input");
    input.type = "hidden";
    input.name = "selected_invalid[]";
    input.value = cb.value;

    form.appendChild(input);
    form.submit();
  } catch (e) {
    console.error("submitMoveToValid failed:", e);
  }
};

/* ============================================================
   ✅ 2) SINGLE VALID -> INVALID
============================================================ */
window.moveValidToInvalid = function(index) {
  try {
    window.location.href = `/volunteer-import/move-valid-to-invalid/${index}`;
  } catch (e) {
    console.error("moveValidToInvalid failed:", e);
  }
};
</script>
