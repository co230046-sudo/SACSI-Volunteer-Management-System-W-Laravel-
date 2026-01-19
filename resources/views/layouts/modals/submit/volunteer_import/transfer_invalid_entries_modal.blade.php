{{-- ===========================================================
   ✅ TRANSFER INVALID <-> VALID (CONFIRM MODAL) — FIXED v3
   - Fixes single-row Invalid -> Valid even if main blade passes index instead of "this"
   - Fixes checkbox selector mismatch (name may be selected_invalid[], selected_invalid[3], etc.)
=========================================================== --}}

<style>
/* ===========================================================
   BASE MODAL (scoped)
=========================================================== */
#transferEntriesModal.transfer-entries-modal{
  position: fixed;
  inset: 0;
  display: none;
  z-index: 9999;
  font-family: 'Segoe UI', Roboto, sans-serif;
}
#transferEntriesModal.transfer-entries-modal.is-open{
  display: flex;
  justify-content: center;
  align-items: center;
}
#transferEntriesModal .modal-overlay{
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.55);
  display:flex;
  justify-content:center;
  align-items:center;
}

/* ===========================================================
   CONTENT SHELL
=========================================================== */
#transferEntriesModal .modal-content{
  width: 100%;
  max-width: 520px;
  border-radius: 14px;
  overflow: visible;
  background: transparent;
  box-shadow: 0 12px 40px rgba(0,0,0,0.25);
  animation: txSlideIn 0.22s ease forwards;
}
#transferEntriesModal .tx-modal-shell{
  background:#fff;
  border-radius: 14px;
  overflow:hidden;
  padding: 1.25rem 1.35rem;
}

/* ===========================================================
   HEADER
=========================================================== */
#transferEntriesModal .tx-modal-header{
  display:flex;
  align-items:center;
  justify-content:flex-start;
  gap:10px;

  background: linear-gradient(180deg, rgba(178,0,12,0.14), rgba(178,0,12,0.06));
  border-bottom: 1px solid rgba(178,0,12,0.14);

  margin: -1.25rem -1.35rem 0.9rem;
  padding: 14px 16px;
  min-height: 60px;

  border-top-left-radius: 14px;
  border-top-right-radius: 14px;
}
#transferEntriesModal .tx-head-icon{
  font-size: 1.55rem;
  line-height: 1;
  color:#7F0008;
  opacity:.95;
}
#transferEntriesModal .tx-head-title{
  margin:0;
  font-weight: 950;
  font-size: 1.15rem;
  letter-spacing: .2px;
  color:#7F0008;
}

/* ===========================================================
   BODY
=========================================================== */
#transferEntriesModal .tx-body{
  padding: 0.2rem 0.1rem 0.2rem;
}
#transferEntriesModal .tx-note{
  background:#fff5f6;
  border: 1px dashed #f3c2c7;
  border-radius: 12px;
  padding: .75rem .9rem;
  color:#5c1b24;
  font-weight: 700;
  font-size: .92rem;
  line-height: 1.35;
}
#transferEntriesModal .tx-count{
  margin-top:.85rem;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:.5rem;
  font-weight: 950;
  color:#7F0008;
}
#transferEntriesModal .tx-count i{ color:#7F0008; }

/* ===========================================================
   FOOTER BUTTONS
=========================================================== */
#transferEntriesModal .tx-footer{
  display:flex;
  justify-content:center;
  gap:.75rem;
  flex-wrap:wrap;
  margin-top: 1rem;
}
#transferEntriesModal .modal-btn{
  display:flex;
  align-items:center;
  justify-content:center;
  gap:0.45rem;
  padding: 0.55rem 1.5rem;
  font-size: 0.95rem;
  font-weight: 650;
  border-radius: 8px;
  cursor:pointer;
  border:none;
  transition: all 0.2s ease;
  height: 44px;
}
#transferEntriesModal .modal-btn.cancel{
  background:#f3f3f3;
  color:#333;
}
#transferEntriesModal .modal-btn.cancel:hover{
  background:#e0e0e0;
  transform: translateY(-1px);
}
#transferEntriesModal .modal-btn.primary{
  background:#B2000C;
  color:#fff;
}
#transferEntriesModal .modal-btn.primary:hover{
  background:#7F0008;
  transform: translateY(-1px);
}
#transferEntriesModal .modal-btn.primary:disabled{
  opacity:.55;
  cursor:not-allowed;
  transform:none;
}

/* ===========================================================
   ANIM
=========================================================== */
@keyframes txSlideIn{
  from { opacity:0; transform: translateY(-14px) scale(0.98); }
  to   { opacity:1; transform: translateY(0) scale(1); }
}
</style>

<div class="transfer-entries-modal" id="transferEntriesModal" aria-hidden="true">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="tx-modal-shell">

        <div class="tx-modal-header">
          <i class="fa-solid fa-triangle-exclamation tx-head-icon" aria-hidden="true"></i>
          <h2 class="tx-head-title" id="txTitle">Transfer Entries</h2>
        </div>

        <div class="tx-body">
          <div class="tx-note" id="txMessage">
            Are you sure you want to transfer the selected entries?
          </div>

          <div class="tx-count">
            <i class="fa-solid fa-list-check"></i>
            <span>Selected:</span>
            <span id="txCount">0</span>
          </div>
        </div>

        <div class="tx-footer">
          <button type="button" class="modal-btn cancel" id="txCancelBtn">
            <i class="fa-solid fa-xmark"></i> Cancel
          </button>

          <button type="button" class="modal-btn primary" id="txConfirmBtn">
            <i class="fa-solid fa-check"></i> Yes, Transfer
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

{{-- ===========================================================
   ✅ MOVE FLASH PAYLOADS (SUCCESS + ERROR) -> UFM
=========================================================== --}}
@if(session('show_success_modal') && session('success_modal_message'))
  <div id="__tx_success_html__" style="display:none;">
    {!! session('success_modal_message') !!}
  </div>
@endif

@if(session('show_error_modal') && session('error_modal_message'))
  <div id="__tx_error_html__" style="display:none;">
    {!! session('error_modal_message') !!}
  </div>
@endif

<script>
/* ===========================================================
   ✅ TRANSFER FLASH -> UFM (LIKE EDIT VOLUNTEER)
   Fixes: "No details payload found" for controller flash spans
=========================================================== */
(function () {
  function escHtml(s){
    return String(s ?? '')
      .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
  }

  function b64utf8Encode(str){
    try {
      const bytes = new TextEncoder().encode(String(str ?? ''));
      let bin = '';
      bytes.forEach(b => bin += String.fromCharCode(b));
      return btoa(bin);
    } catch (e) {
      try { return btoa(unescape(encodeURIComponent(String(str ?? '')))); }
      catch (_) { return ''; }
    }
  }

  if (!window.__UFM_LAST__) window.__UFM_LAST__ = null;

  function getFlashPayload(){
    const errEl = document.getElementById('__tx_error_html__');
    const errHtml = (errEl?.innerHTML || '').trim();
    if (errHtml) {
      return { variant:'error', title:'Transfer failed', subtitle:'Some entries could not be transferred.', html: errHtml, source:'transfer_flash_error' };
    }

    const okEl = document.getElementById('__tx_success_html__');
    const okHtml = (okEl?.innerHTML || '').trim();
    if (okHtml) {
      return { variant:'success', title:'Transfer complete', subtitle:'Entries moved successfully.', html: okHtml, source:'transfer_flash_success' };
    }

    return null;
  }

  function attachDetailsPayloadToHtml(anyHtml, detailsHtml){
    const b64 = b64utf8Encode(detailsHtml || anyHtml || '');
    if (!b64) return anyHtml;

    const safeB64 = escHtml(b64);

    return String(anyHtml || '').replace(
      /<span([^>]*class=['"][^'"]*(success-details-link|error-details-link)[^'"]*['"][^>]*)>([\s\S]*?)<\/span>/gi,
      function(_, attrs, cls, inner){
        const kind = /error-details-link/i.test(cls) ? 'error' : 'success';
        return `<a href="#"
                  class="${kind}-details-link"
                  data-ufm-details="${safeB64}"
                  style="color:#1565c0; cursor:pointer; text-decoration:none; font-weight:600;">
                  ${inner}
                </a>`;
      }
    );
  }

  function showPayload(payload){
    if (!payload) return;

    window.__UFM_LAST__ = {
      variant: payload.variant,
      title: payload.title,
      subtitle: payload.subtitle,
      html: payload.html,
      source: payload.source
    };

    const htmlWithPayload = attachDetailsPayloadToHtml(payload.html, payload.html);

    window.FeedbackModal.show({
      variant: payload.variant,
      title: payload.title,
      subtitle: payload.subtitle,
      html: htmlWithPayload,
      source: payload.source
    });
  }

  document.addEventListener('DOMContentLoaded', function(){
    const payload = getFlashPayload();
    if (!payload) return;

    let tries = 0;
    const maxTries = 80;
    const t = setInterval(function(){
      tries++;
      if (window.FeedbackModal?.show) {
        clearInterval(t);
        showPayload(payload);
        return;
      }
      if (tries >= maxTries) {
        clearInterval(t);
        console.error('[TX FLASH] FeedbackModal not available - check UFM include/script order.');
      }
    }, 25);
  });
})();
</script>

<script>
/* ===========================================================
   ✅ TRANSFER LOGIC (Invalid <-> Valid) — FIXED v3
   Key fixes:
   - checkbox selector uses name^="selected_invalid" (works with [] or [i])
   - submitMoveToValid(arg) accepts:
       - DOM button: submitMoveToValid(this)
       - numeric/string index: submitMoveToValid(3)
=========================================================== */
(function () {
  "use strict";

  const modal  = document.getElementById('transferEntriesModal');
  if (!modal) return;

  const overlay = modal.querySelector('.modal-overlay');
  const titleEl = document.getElementById('txTitle');
  const msgEl   = document.getElementById('txMessage');
  const countEl = document.getElementById('txCount');

  const cancelBtn  = document.getElementById('txCancelBtn');
  const confirmBtn = document.getElementById('txConfirmBtn');

  let mode = 'invalid_to_valid';

  let singleValidIndex   = null; // valid -> invalid
  let singleInvalidIndex = null; // invalid -> valid

  let submitting = false;
  if (typeof window.__TX_OPENING__ === 'undefined') window.__TX_OPENING__ = false;

  function openModal() {
    if (window.__TX_OPENING__) return;
    window.__TX_OPENING__ = true;

    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';

    setTimeout(() => { window.__TX_OPENING__ = false; }, 60);
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
    submitting = false;
    confirmBtn.disabled = false;

    singleInvalidIndex = null;
    singleValidIndex = null;
  }

  function showUfmWarn(title, subtitle, html){
    if (window.FeedbackModal?.show) {
      window.FeedbackModal.show({
        variant:'warning',
        title, subtitle,
        html,
        userAction:true,
        source:'transfer_modal_warn'
      });
      return;
    }
    alert(title + "\n" + subtitle);
  }

  // ✅ robust checkbox selector (works with selected_invalid[], selected_invalid[3], etc.)
  function getAllInvalidCheckboxes(){
    return Array.from(document.querySelectorAll(
      '#invalid-entries-table tbody input[type="checkbox"][name^="selected_invalid"]'
    ));
  }

  function getCheckedInvalid(){
    return getAllInvalidCheckboxes().filter(cb => cb.checked);
  }

  function uncheckAllInvalid(){
    getAllInvalidCheckboxes().forEach(cb => { cb.checked = false; });
  }

  function setCopyToHiddenFormInvalidToValid() {
    const form = document.getElementById('moveToVerifiedForm');
    if (!form) return { ok:false, reason:'#moveToVerifiedForm not found' };

    form.querySelectorAll('input[name="selected_invalid[]"]').forEach(n => n.remove());

    const selected = getCheckedInvalid();

    // ✅ single fallback (even if checkbox naming/behavior is weird)
    if (selected.length === 0 && singleInvalidIndex !== null && singleInvalidIndex !== '') {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'selected_invalid[]';
      input.value = String(singleInvalidIndex);
      form.appendChild(input);
      return { ok:true, form, count: 1, usedSingle:true };
    }

    selected.forEach(cb => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'selected_invalid[]';
      input.value = cb.value;
      form.appendChild(input);
    });

    return { ok:true, form, count: selected.length, usedSingle:false };
  }

  // Bulk: Invalid -> Valid
  window.openTransferInvalidToValid = function(){
    mode = 'invalid_to_valid';
    singleValidIndex = null;
    singleInvalidIndex = null;

    const selected = getCheckedInvalid().length;

    titleEl.textContent = 'Move to Verified';
    msgEl.innerHTML =
      selected
      ? `Do you want to move the selected <b>${selected}</b> invalid entr${selected===1?'y':'ies'} to the <b>Verified</b> list?`
      : `Do you want to move selected invalid entries to the <b>Verified</b> list?`;

    countEl.textContent = String(selected);
    openModal();
  };

  /**
   * ✅ Single: Invalid -> Valid
   * Supports BOTH:
   *   submitMoveToValid(this)
   *   submitMoveToValid(3)
   */
  window.submitMoveToValid = function(arg){
    mode = 'invalid_to_valid';
    singleValidIndex = null;

    // clear all first so it's truly "single"
    uncheckAllInvalid();

    // case A: arg is a button element
    if (arg && typeof arg === 'object' && (arg.nodeType === 1 || arg instanceof Element)) {
      const btn = arg;

      // try check its row checkbox if present
      try{
        const row = btn.closest('tr');
        const cb  = row?.querySelector('input[type="checkbox"][name^="selected_invalid"]');
        if (cb) {
          cb.checked = true;
          singleInvalidIndex = cb.value; // safest
        } else {
          // fallback: data-index on row/button
          singleInvalidIndex =
            btn.getAttribute('data-index') ||
            row?.getAttribute('data-index') ||
            btn.dataset?.index ||
            row?.dataset?.index ||
            null;
        }
      }catch(e){
        singleInvalidIndex = null;
      }

    } else {
      // case B: arg is an index/value
      singleInvalidIndex = (arg !== null && arg !== undefined && String(arg).trim() !== '')
        ? String(arg).trim()
        : null;

      // if we can find a checkbox with value == arg, check it (nice UI consistency)
      if (singleInvalidIndex) {
        const cb = getAllInvalidCheckboxes().find(x => String(x.value) === String(singleInvalidIndex));
        if (cb) cb.checked = true;
      }
    }

    // count display: checked count; if none but have single fallback, show 1
    let selected = getCheckedInvalid().length;
    if (selected === 0 && singleInvalidIndex) selected = 1;

    titleEl.textContent = 'Move to Verified';
    msgEl.innerHTML =
      `Do you want to move the selected <b>${selected}</b> invalid entr${selected===1?'y':'ies'} to the <b>Verified</b> list?`;
    countEl.textContent = String(selected);

    openModal();
  };

  // Single: Valid -> Invalid (already works)
  window.moveValidToInvalid = function(index){
    mode = 'valid_to_invalid';
    singleInvalidIndex = null;
    singleValidIndex = String(index);

    titleEl.textContent = 'Move to Invalid';
    msgEl.innerHTML = `Do you want to move <b>Entry #${Number(index)+1}</b> back to the <b>Invalid</b> list?`;
    countEl.textContent = '1';
    openModal();
  };

  // Bulk open button
  document.addEventListener('click', function(e){
    const btn = e.target.closest('#openMoveModalBtn');
    if (!btn) return;
    e.preventDefault();
    window.openTransferInvalidToValid();
  });

  // Close
  cancelBtn.addEventListener('click', closeModal);
  overlay.addEventListener('click', function(e){ if (e.target === overlay) closeModal(); });
  document.addEventListener('keydown', function(e){
    if (modal.classList.contains('is-open') && e.key === 'Escape') closeModal();
  });

  // Confirm
  confirmBtn.addEventListener('click', function(){
    if (submitting) return;
    submitting = true;
    confirmBtn.disabled = true;

    if (mode === 'invalid_to_valid') {
      const checkedCount = getCheckedInvalid().length;

      if (!checkedCount && !(singleInvalidIndex !== null && singleInvalidIndex !== '')) {
        submitting = false;
        confirmBtn.disabled = false;
        showUfmWarn(
          'No selection',
          'Select invalid entries first',
          "<div style='font-weight:900;'>Please select at least one invalid entry before transferring.</div>"
        );
        return;
      }

      const r = setCopyToHiddenFormInvalidToValid();
      if (!r.ok) {
        submitting = false;
        confirmBtn.disabled = false;
        showUfmWarn('Missing form', 'Cannot transfer', `<div style="font-weight:900;">${r.reason}</div>`);
        return;
      }

      r.form.submit();
      closeModal();
      return;
    }

    if (mode === 'valid_to_invalid') {
      if (singleValidIndex === null || singleValidIndex === '') {
        submitting = false;
        confirmBtn.disabled = false;
        showUfmWarn('Missing index', 'Cannot transfer', `<div style="font-weight:900;">No entry index provided.</div>`);
        return;
      }

      const urlTemplate = @json(route('volunteer.moveValidToInvalid', ['index' => '__IDX__']));
      const url = urlTemplate.replace('__IDX__', encodeURIComponent(singleValidIndex));
      window.location.href = url;
      closeModal();
      return;
    }

    submitting = false;
    confirmBtn.disabled = false;
    closeModal();
  });

})();
</script>
