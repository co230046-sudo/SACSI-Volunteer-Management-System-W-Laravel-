{{-- ===========================================================
   ✅ TRANSFER INVALID <-> VALID (CONFIRM MODAL) — FULL PATCH
   - Same modal system as Edit Volunteer (overlay + is-open)
   - ✅ Invalid -> Valid (POST) uses #moveToVerifiedForm (route already set in main blade)
   - ✅ Valid -> Invalid (GET) uses route('volunteer.moveValidToInvalid', index)
   - ✅ Uses Universal Feedback Modal (UFM) for SUCCESS/ERROR after redirect (flash)
   - ✅ Transfer modal uses UFM only for warnings (no selection / missing form)
   - ✅ Fixes "No details payload found" by using RECALL (does NOT depend on data-details attr)
   - ✅ Prevents accidental multi-fire via guards + scoped IDs
   - ✅ No auto-select-all (respects user selection)
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
   IMPORTANT:
   - We read your controller flags:
     show_success_modal + success_modal_message
     show_error_modal   + error_modal_message
   - Unique IDs so this file won't clash with other modals
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
   ✅ MOVE FLASH -> UNIVERSAL FEEDBACK MODAL (UFM) — FINAL PATCH
   Fixes "No details payload found" by:
   - storing the details HTML in window.__UFM_LAST__
   - replacing controller <span class="...-details-link">Show Details</span>
     with a link that calls recallLastUfm()
   - DOES NOT rely on data-details / data-ufm-details formats
=========================================================== */
(function () {

  function getPayload(){
    const errEl = document.getElementById('__tx_error_html__');
    const errHtml = (errEl?.innerHTML || '').trim();
    if (errHtml) {
      return {
        variant: 'error',
        title: 'Transfer failed',
        subtitle: 'Some entries could not be transferred.',
        detailsHtml: errHtml,
        source: 'transfer_move_flash_error'
      };
    }

    const okEl = document.getElementById('__tx_success_html__');
    const okHtml = (okEl?.innerHTML || '').trim();
    if (okHtml) {
      return {
        variant: 'success',
        title: 'Transfer complete',
        subtitle: 'Entries moved successfully.',
        detailsHtml: okHtml,
        source: 'transfer_move_flash_success'
      };
    }

    return null;
  }

  // Replace controller spans with a recall link
  function injectRecallLink(html, variant){
    if (!html) return html;

    const linkHtml = `
      <a href="#"
         class="${variant === 'error' ? 'error-details-link' : 'success-details-link'}"
         data-ufm-recall="1"
         style="color:#1565c0; cursor:pointer; text-decoration:none; font-weight:600;">
        Show Details
      </a>
    `;

    return String(html).replace(
      /<span([^>]*class=['"][^'"]*(success-details-link|error-details-link)[^'"]*['"][^>]*)>([\s\S]*?)<\/span>/gi,
      linkHtml
    );
  }

  function ensureRecallHandler(){
    if (window.__TX_RECALL_BOUND__) return;
    window.__TX_RECALL_BOUND__ = true;

    // Provide recallLastUfm if missing
    if (!window.recallLastUfm) {
      window.recallLastUfm = function(){
        const p = window.__UFM_LAST__;
        if (!p || !window.FeedbackModal?.show) return;

        window.FeedbackModal.show({
          variant: p.variant || 'info',
          title: p.title || 'Notice',
          subtitle: p.subtitle || '',
          html: p.html || '',
          userAction: true,     // user click: allow showing even if single-fire guard exists
          source: 'recallLastUfm_transfer'
        });
      };
    }

    // Delegate click for recall links we inject
    document.addEventListener('click', function(e){
      const a = e.target.closest('a[data-ufm-recall="1"]');
      if (!a) return;
      e.preventDefault();
      window.recallLastUfm();
    });
  }

  function showOnce(payload){
    if (!payload) return;

    // SINGLE-FIRE just for this move action flash
    const key = '__UFM_FLASH_FIRED__:' + payload.source;
    if (window[key]) return;
    window[key] = true;

    ensureRecallHandler();

    // Store last details for recall
    window.__UFM_LAST__ = {
      variant: payload.variant,
      title: payload.title,
      subtitle: payload.subtitle,
      html: payload.detailsHtml,
      source: payload.source
    };

    const htmlWithRecall = injectRecallLink(payload.detailsHtml, payload.variant);

    window.FeedbackModal.show({
      variant: payload.variant,
      title: payload.title,
      subtitle: payload.subtitle,
      html: htmlWithRecall,
      source: payload.source
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    const payload = getPayload();
    if (!payload) return;

    let tries = 0;
    const maxTries = 80; // ~2s
    const t = setInterval(function(){
      tries++;
      if (window.FeedbackModal?.show) {
        clearInterval(t);
        showOnce(payload);
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
   ✅ TRANSFER LOGIC (Invalid <-> Valid) — PATCHED
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
  let singleValidIndex = null;

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

  function getCheckedInvalid(){
    return Array.from(document.querySelectorAll(
      '#invalid-entries-table tbody input[name="selected_invalid[]"]'
    )).filter(cb => cb.checked);
  }

  function setCopyToHiddenFormInvalidToValid() {
    const form = document.getElementById('moveToVerifiedForm');
    if (!form) return { ok:false, reason:'#moveToVerifiedForm not found' };

    form.querySelectorAll('input[name="selected_invalid[]"]').forEach(n => n.remove());

    const selected = getCheckedInvalid();
    selected.forEach(cb => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'selected_invalid[]';
      input.value = cb.value;
      form.appendChild(input);
    });

    return { ok:true, form, count: selected.length };
  }

  // Bulk: Invalid -> Valid
  window.openTransferInvalidToValid = function(){
    mode = 'invalid_to_valid';
    singleValidIndex = null;

    const selected = getCheckedInvalid().length;

    titleEl.textContent = 'Move to Verified';
    msgEl.innerHTML =
      selected
      ? `Do you want to move the selected <b>${selected}</b> invalid entr${selected===1?'y':'ies'} to the <b>Verified</b> list?`
      : `Do you want to move selected invalid entries to the <b>Verified</b> list?`;

    countEl.textContent = String(selected);
    openModal();
  };

  // Single: Invalid -> Valid
  window.submitMoveToValid = function(btn){
    try{
      const row = btn?.closest('tr');
      const cb  = row?.querySelector('input[name="selected_invalid[]"]');
      if (cb) cb.checked = true;
    }catch(e){}
    window.openTransferInvalidToValid();
  };

  // Single: Valid -> Invalid
  window.moveValidToInvalid = function(index){
    mode = 'valid_to_invalid';
    singleValidIndex = String(index);

    titleEl.textContent = 'Move to Invalid';
    msgEl.innerHTML = `Do you want to move <b>Entry #${Number(index)+1}</b> back to the <b>Invalid</b> list?`;
    countEl.textContent = '1';
    openModal();
  };

  // Wire bulk button
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
      const selectedCount = getCheckedInvalid().length;

      if (!selectedCount) {
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
