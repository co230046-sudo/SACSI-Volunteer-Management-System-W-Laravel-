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
.move-modal-wrapper.active { display: flex; }

.move-modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.45); }

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
@keyframes modalIn { from { opacity:0; transform: translateY(-16px) scale(.96); } to { opacity:1; transform: translateY(0) scale(1); } }

/* ============================================================
   HEADER TEXT + CENTERING
============================================================ */
.move-modal-box h2 { text-align: center; margin: 0 0 6px; font-weight: 600; }
.move-modal-box h2 i { margin-right: 6px; }

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
.move-modal-text { font-size: 1.02rem; line-height: 1.55; margin-bottom: 18px; font-weight: 400; }

/* ============================================================
   ROW LISTS
============================================================ */
.move-scroll-list { max-height: 260px; overflow-y: auto; padding-right: 6px; margin-top: 4px; }
.move-row {
    border-bottom: 1px dashed #d7d7d7;
    padding: 10px 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.move-row:last-child { border-bottom: none; }
.move-row a { color: #1565c0; font-weight: 500; text-decoration: underline; }

/* ============================================================
   HR STYLING
============================================================ */
.move-modal-box hr { width: 85%; height: 1px; background: #ececec; margin: 1rem auto; }

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
.move-modal-btn-confirm { background: #B2000C; color: #fff; }
.move-modal-btn-confirm:hover { background: #7F0008; transform: translateY(-2px); }
.move-modal-btn-cancel { background: #e4e4e4; color: #333; }
.move-modal-btn-cancel:hover { background: #d6d6d6; transform: translateY(-2px); }
.move-modal-button-row { display: flex; justify-content: center; gap: 12px; margin-top: 8px; }
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
            <button class="move-modal-btn move-modal-btn-cancel" id="cancelMoveBtn" type="button">Cancel</button>
            <button class="move-modal-btn move-modal-btn-confirm" id="confirmMoveBtn" type="button">Yes, Move</button>
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
            <button class="move-modal-btn move-modal-btn-confirm" id="moveSuccessOkBtn" type="button">OK</button>
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
            <button class="move-modal-btn move-modal-btn-confirm" id="moveErrorOkBtn" type="button">OK</button>
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
        <div class="move-modal-text move-text-error">No invalid entries were selected.</div>
        <div class="move-modal-button-row">
            <button class="move-modal-btn move-modal-btn-confirm" id="moveNothingOkBtn" type="button">OK</button>
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
            <button class="move-modal-btn move-modal-btn-cancel" id="moveMissingCloseBtn" type="button">Close</button>
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
  /** MODALS */
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

  /** OTHER */
  const openMoveBtn = document.getElementById("openMoveModalBtn");
  const hiddenForm  = document.getElementById("moveToVerifiedForm");

  const failedEntries = Array.isArray(window.failedEntriesJson) ? window.failedEntriesJson : [];
  let reopenErrorAfterMissing = false;

  const openModal  = m => m?.classList.add("active");
  const closeModal = m => m?.classList.remove("active");

  function getInvalidCheckboxes() {
    return [...document.querySelectorAll('#invalid-entries-table tbody input[name="selected_invalid[]"]')];
  }

  function resetHiddenForm() {
    if (!hiddenForm) return;
    const token = hiddenForm.querySelector('input[name="_token"]');
    hiddenForm.innerHTML = "";
    if (token) hiddenForm.appendChild(token);
  }

  /**
   * ✅ Key fix:
   * When your dropdown menu is portaled to <body>, the transfer button is NOT inside <tr>.
   * So we resolve checkbox by index using the button's data-index.
   */
  function resolveInvalidCheckbox(btn) {
    // 1) try button -> closest row (works if menu not portaled)
    const row = btn.closest("tr");
    if (row) {
      const cb = row.querySelector('input[name="selected_invalid[]"]');
      if (cb) return cb;
    }

    // 2) use data-index -> find checkbox by matching its value
    const idx = btn.getAttribute("data-index");
    if (idx !== null && idx !== undefined && idx !== "") {
      const esc = (window.CSS && CSS.escape) ? CSS.escape(String(idx)) : String(idx);

     
      const byValue = document.querySelector(
        `#invalid-entries-table tbody input[name="selected_invalid[]"][value="${esc}"]`
      );
      if (byValue) return byValue;
    }

    // 3) last-resort: return null
    return null;
  }

  /* ============================================================
     BULK MOVE (selected invalid -> valid)
  ============================================================ */
  openMoveBtn?.addEventListener("click", () => {
    const boxes = getInvalidCheckboxes();
    if (boxes.length === 0) { openModal(nothingModal); return; }

    const checked = boxes.filter(b => b.checked);
    if (checked.length === 0) { openModal(nothingModal); return; }

    confirmText.innerHTML = `Move <strong>${checked.length}</strong> entr${checked.length > 1 ? "ies" : "y"}?`;
    openModal(confirmModal);
  });

  cancelBtn?.addEventListener("click", () => closeModal(confirmModal));

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
  successOkBtn?.addEventListener("click", () => closeModal(successModal));

  if (window.showErrorModal) {
    errorText.innerHTML = window.errorModalMessage;
    openModal(errorModal);
  }
  errorOkBtn?.addEventListener("click", () => closeModal(errorModal));

  if (window.showNothingModal) openModal(nothingModal);
  nothingOkBtn?.addEventListener("click", () => closeModal(nothingModal));

  if (window.redirect_anchor) {
    setTimeout(() => { window.location.hash = window.redirect_anchor; }, 80);
  }

  /* ============================================================
     FLASH BAR DETAILS LINKS
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
     MISSING FIELD POPUP
  ============================================================ */
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
      let arr = Array.isArray(msgs) ? msgs : [msgs];

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
        monday: "Invalid or conflicting Monday schedule.",
        tuesday: "Invalid or conflicting Tuesday schedule.",
        wednesday: "Invalid or conflicting Wednesday schedule.",
        thursday: "Invalid or conflicting Thursday schedule.",
        friday: "Invalid or conflicting Friday schedule.",
        saturday: "Invalid or conflicting Saturday schedule.",
      };

      arr = arr.map(v => (v === true || v === false) ? (defaultMessages[field] || "Invalid or missing value.") : v);

      html += `
        <div style="margin-bottom:10px;">
          <strong style="color:#B2000C;">${label}</strong><br>
          ${arr.join("<br>")}
        </div>
      `;
    });

    missingText.innerHTML = html;

    reopenErrorAfterMissing = errorModal.classList.contains("active");
    if (reopenErrorAfterMissing) closeModal(errorModal);

    openModal(missingModal);
  });

  missingCloseBtn?.addEventListener("click", () => {
    closeModal(missingModal);
    if (reopenErrorAfterMissing) {
      openModal(errorModal);
      reopenErrorAfterMissing = false;
    }
  });

  /* optional overlay click close */
  document.querySelectorAll(".move-modal-wrapper .move-modal-overlay").forEach(overlay => {
    overlay.addEventListener("click", () => {
      const wrapper = overlay.closest(".move-modal-wrapper");
      closeModal(wrapper);
    });
  });
});

/* ============================================================
   ✅ 1) SINGLE INVALID -> VALID
   Works even when dropdown menu is portaled
============================================================ */
window.submitMoveToValid = function(btn) {
  try {
    const form = document.getElementById("moveToVerifiedForm");
    if (!form) return;

    const cb = (function resolve() {
      // Use same resolver logic
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
      console.warn("submitMoveToValid: could not resolve checkbox (menu likely portaled + missing data-index).");
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
   IMPORTANT: your Blade calls moveValidToInvalid(...),
   so we expose that exact name.
============================================================ */
window.moveValidToInvalid = function(index) {
  try {
    window.location.href = `/volunteer-import/move-valid-to-invalid/${index}`;
  } catch (e) {
    console.error("moveValidToInvalid failed:", e);
  }
};
</script>

