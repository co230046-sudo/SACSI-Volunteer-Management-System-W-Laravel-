(() => {
  const form = document.getElementById('logsFilterForm');

  // ---------- Utilities ----------
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function safeJsonParse(text) {
    if (!text) return null;
    const trimmed = String(text).trim();
    if (!(trimmed.startsWith('{') || trimmed.startsWith('['))) return null;
    try { return JSON.parse(trimmed); } catch { return null; }
  }

  function titleize(s) {
    return String(s || '')
      .replace(/[_-]+/g, ' ')
      .trim()
      .replace(/\b\w/g, m => m.toUpperCase());
  }

  // ✅ Base64 decode helper (safe for utf-8)
  function decodeB64Unicode(b64) {
    if (!b64) return '';
    try {
      const bin = atob(String(b64));
      const bytes = Uint8Array.from(bin, c => c.charCodeAt(0));
      return new TextDecoder('utf-8').decode(bytes);
    } catch {
      try { return atob(String(b64)); } catch { return ''; }
    }
  }

  // ---------- Humanize ----------
  function summarize({ admin, action, categoryKey, raw, fallbackText }) {
    const a = admin || 'Admin';
    const act = String(action || '').toLowerCase();
    const parsed = safeJsonParse(raw);

    if (!parsed) {
      const t = (fallbackText || '').trim();
      if (!t) return '—';
      return t.length > 160 ? (t.slice(0, 150) + '…') : t;
    }

    // ✅ FactLogger payload: prefer summary if present
    if (typeof parsed === 'object' && parsed !== null && typeof parsed.summary === 'string' && parsed.summary.trim() !== '') {
      return parsed.summary.trim();
    }

    if (act.includes('failed_login')) return `${a} failed to log in.`;
    if (act.includes('login')) return `${a} logged in successfully.`;
    if (act.includes('logout')) return `${a} logged out.`;

    const entryNo = parsed.entry_no ?? parsed.entry ?? parsed.row ?? parsed.index;
    const person = parsed.name ?? parsed.volunteer_name ?? parsed.full_name;

    if (String(categoryKey).includes('volunteer_import') && (entryNo != null || person != null)) {
      const left = entryNo != null ? `Volunteer Entry #${entryNo}` : 'Volunteer Entry';
      const who = person ? person : 'No Name';
      return `${a} updated ${left} — ${who}.`;
    }

    const title = parsed.title || parsed.event_title;
    const code  = parsed.code || parsed.event_code;
    if (title && String(categoryKey).includes('event')) {
      const extra = code ? ` (Code: ${code}).` : '.';
      return `${a} updated event “${title}”${extra}`;
    }

    return `${a} performed “${titleize(action)}”.`;
  }

  // Apply humanize on table load
  $$('.js-humanize').forEach(el => {
    const admin = el.getAttribute('data-admin') || 'Admin';
    const action = el.getAttribute('data-action') || '';
    const categoryKey = el.getAttribute('data-category-key') || '';
    const raw = decodeB64Unicode(el.getAttribute('data-raw-b64') || '');
    const fallbackText = (el.textContent || '').trim();
    el.textContent = summarize({ admin, action, categoryKey, raw, fallbackText });
  });

  // ---------- Modal ----------
  const backdrop = $('#logModalBackdrop');
  const closeBtn = $('#logModalClose');
  const closeBtn2 = $('#logModalClose2');

  const metaEl = $('#logModalMeta');
  const summaryEl = $('#logModalSummary');
  const rawEl = $('#logModalRaw');
  const chipsEl = $('#logModalChips');
  const jumpBtn = $('#logModalJump');

  const rawToggle = $('#logModalRawToggle');
  const rawPanel = $('#logModalRawPanel');

  let lastRowId = null;

  function setRawOpen(open) {
    if (!rawToggle || !rawPanel) return;
    rawToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    const label = rawToggle.querySelector('span');
    if (label) label.textContent = open ? 'Hide raw details' : 'Show raw details';
    rawPanel.hidden = !open;
  }

  rawToggle?.addEventListener('click', () => {
    const isOpen = rawToggle.getAttribute('aria-expanded') === 'true';
    setRawOpen(!isOpen);
  });

  function openModalFromRow(row) {
    const detailsEl = $('.log-details', row);
    if (!detailsEl) return;

    lastRowId = detailsEl.getAttribute('data-row-id') || row.id || null;

    const timestamp = detailsEl.getAttribute('data-timestamp') || '';
    const categoryLabel = detailsEl.getAttribute('data-category-label') || '';
    const categoryKey = detailsEl.getAttribute('data-category-key') || '';
    const action = detailsEl.getAttribute('data-action') || '';
    const admin = detailsEl.getAttribute('data-admin') || 'Admin';
    const adminUrl = detailsEl.getAttribute('data-admin-url') || '';
    const raw = decodeB64Unicode(detailsEl.getAttribute('data-raw-b64') || '');
    const entityType = detailsEl.getAttribute('data-entity-type') || '';
    const entityId = detailsEl.getAttribute('data-entity-id') || '';

    metaEl.innerHTML = `
      ${escapeHtml(timestamp)} • ${escapeHtml(categoryLabel)} • ${escapeHtml(action)} •
      ${adminUrl
        ? `<a class="admin-link" href="${escapeHtml(adminUrl)}"><span class="admin-pill">${escapeHtml(admin)}</span></a>`
        : `<span class="admin-pill">${escapeHtml(admin)}</span>`
      }
    `;

    const fallbackText = (detailsEl.textContent || '').trim();
    summaryEl.textContent = summarize({ admin, action, categoryKey, raw, fallbackText });

    if (chipsEl) {
      const chips = [];
      if (entityType) chips.push({ icon: 'fa-cube', text: titleize(entityType) });
      if (entityId) chips.push({ icon: 'fa-hashtag', text: `#${entityId}` });
      if (categoryLabel) chips.push({ icon: 'fa-tags', text: categoryLabel });
      if (action) chips.push({ icon: 'fa-bolt', text: action });

      chipsEl.innerHTML = chips.map(c =>
        `<span class="mchip"><i class="fa-solid ${c.icon}"></i>${escapeHtml(c.text)}</span>`
      ).join('');
    }

    const parsed = safeJsonParse(raw);
    rawEl.textContent = parsed ? JSON.stringify(parsed, null, 2) : (raw && raw.trim() ? raw.trim() : '—');

    setRawOpen(false);

    backdrop?.classList.add('is-open');
    backdrop?.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    backdrop?.classList.remove('is-open');
    backdrop?.setAttribute('aria-hidden', 'true');
  }

  closeBtn?.addEventListener('click', closeModal);
  closeBtn2?.addEventListener('click', closeModal);
  backdrop?.addEventListener('click', (e) => { if (e.target === backdrop) closeModal(); });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && backdrop?.classList.contains('is-open')) closeModal();
  });

  jumpBtn?.addEventListener('click', () => {
    if (!lastRowId) return;
    const row = document.getElementById(lastRowId);
    if (!row) return;

    closeModal();

    $$('.log-row.is-hit').forEach(r => r.classList.remove('is-hit'));
    row.classList.add('is-hit');
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => row.classList.remove('is-hit'), 1800);
  });

  $$('.log-row').forEach(row => {
    row.addEventListener('click', (e) => {
      if (e.target.closest('a') || e.target.closest('button')) return;
      openModalFromRow(row);
    });
  });

  $$('.js-open-modal').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const rowId = btn.getAttribute('data-row');
      const row = rowId ? document.getElementById(rowId) : null;
      if (row) openModalFromRow(row);
    });
  });

  // ---------- Portal Custom Select ----------
  const selects = $$('.cselect');
  const portalState = new Map();

  function positionPop(btn, pop) {
    const r = btn.getBoundingClientRect();
    const top = r.bottom + 10;
    const left = r.left;

    pop.style.top = `${top}px`;
    pop.style.left = `${left}px`;

    const popRect = pop.getBoundingClientRect();
    const maxLeft = window.innerWidth - popRect.width - 12;
    if (left > maxLeft) pop.style.left = `${Math.max(12, maxLeft)}px`;
  }

  function closeAllSelects(except = null) {
    selects.forEach(s => {
      const st = portalState.get(s);
      if (!st) return;
      if (s === except) return;

      st.pop.classList.remove('is-open');
      s.classList.remove('is-open');
      st.btn.setAttribute('aria-expanded', 'false');
    });
  }

  selects.forEach((wrap) => {
    const name = wrap.getAttribute('data-name'); // action/category
    const btn = $('.cselect-btn', wrap);
    const pop = $('.cselect-pop', wrap);
    const searchInput = $('.cselect-search input', pop);
    const items = $$('.cselect-item', pop);
    const valueSpan = $('.cselect-value', wrap);
    const hidden = document.getElementById(name === 'action' ? 'actionHidden' : 'categoryHidden');

    if (!btn || !pop) return;

    document.body.appendChild(pop);
    portalState.set(wrap, { pop, btn, hidden, valueSpan, items, searchInput, name });

    function setSelected(value, label) {
      if (hidden) hidden.value = value;
      if (valueSpan) valueSpan.textContent = label;
      items.forEach(i => i.classList.toggle('is-selected', (i.getAttribute('data-value') ?? '') === value));
    }

    // initial
    const current = (hidden?.value ?? '');
    const found = items.find(i => (i.getAttribute('data-value') ?? '') === current);
    if (found) setSelected(current, found.textContent.trim());
    else setSelected('', name === 'action' ? 'All actions' : 'All categories');

    // expose reset helper
    wrap.__setSelected = setSelected;

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const isOpen = pop.classList.contains('is-open');

      closeAllSelects(wrap);

      if (!isOpen) {
        pop.classList.add('is-open');
        wrap.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');

        if (searchInput) searchInput.value = '';
        items.forEach(i => (i.style.display = ''));

        positionPop(btn, pop);
        setTimeout(() => searchInput?.focus(), 0);
      } else {
        pop.classList.remove('is-open');
        wrap.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });

    items.forEach(item => {
      item.addEventListener('click', () => {
        const val = item.getAttribute('data-value') ?? '';
        const label = item.textContent.trim();
        setSelected(val, label);

        pop.classList.remove('is-open');
        wrap.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
      });
    });

    searchInput?.addEventListener('input', () => {
      const q = (searchInput.value || '').toLowerCase().trim();
      items.forEach(i => { i.style.display = i.textContent.toLowerCase().includes(q) ? '' : 'none'; });
      positionPop(btn, pop);
    });
  });

  document.addEventListener('click', (e) => {
    const insideSelect = e.target.closest('.cselect');
    const insidePop = e.target.closest('.cselect-pop');
    if (!insideSelect && !insidePop) closeAllSelects();
  });

  window.addEventListener('resize', () => {
    selects.forEach(s => {
      const st = portalState.get(s);
      if (st?.pop.classList.contains('is-open')) positionPop(st.btn, st.pop);
    });
  });

  window.addEventListener('scroll', () => {
    selects.forEach(s => {
      const st = portalState.get(s);
      if (st?.pop.classList.contains('is-open')) positionPop(st.btn, st.pop);
    });
  }, { passive: true });

  // ---------- Apply + Reset ----------
  $('.btn-apply-filters')?.addEventListener('click', () => {
    form?.submit?.();
  });

  $('#logsResetBtn')?.addEventListener('click', (e) => {
    e.preventDefault();

    const dateStart = $('#date_start');
    const dateEnd = $('#date_end');
    const q = $('#q');
    if (dateStart) dateStart.value = '';
    if (dateEnd) dateEnd.value = '';
    if (q) q.value = '';

    const actionHidden = $('#actionHidden');
    const categoryHidden = $('#categoryHidden');
    if (actionHidden) actionHidden.value = '';
    if (categoryHidden) categoryHidden.value = '';

    $$('.cselect').forEach(wrap => {
      const name = wrap.getAttribute('data-name');
      const setSelected = wrap.__setSelected;
      if (typeof setSelected === 'function') {
        setSelected('', name === 'action' ? 'All actions' : 'All categories');
      } else {
        const valueSpan = $('.cselect-value', wrap);
        if (valueSpan) valueSpan.textContent = name === 'action' ? 'All actions' : 'All categories';
      }
    });

    window.location.href = (form?.getAttribute('action') || window.location.pathname);
  });
})();
