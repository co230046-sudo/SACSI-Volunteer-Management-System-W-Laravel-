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
    return String(s || '').replace(/[_-]+/g, ' ').trim().replace(/\b\w/g, m => m.toUpperCase());
  }

  // ---------- Humanize ----------
  function summarize({ admin, action, categoryKey, raw }) {
    const a = admin || 'Admin';
    const act = String(action || '').toLowerCase();
    const parsed = safeJsonParse(raw);

    if (!parsed) {
      const t = (raw || '').trim();
      if (!t) return '—';
      return t.length > 160 ? (t.slice(0, 150) + '…') : t;
    }

    if (act.includes('failed_login')) return `${a} failed to log in.`;
    if (act.includes('login')) return `${a} logged in successfully.`;
    if (act.includes('logout')) return `${a} logged out.`;

    const entryNo = parsed.entry_no ?? parsed.entry ?? parsed.row ?? parsed.index;
    const person = parsed.name ?? parsed.volunteer_name ?? parsed.full_name;
    if (String(categoryKey).includes('volunteer') && act.includes('import') && (entryNo != null || person)) {
      const left = entryNo != null ? `Imported Volunteer Entry #${entryNo}` : 'Imported Volunteer Entry';
      const right = person ? ` – ${person}` : '';
      return left + right + '.';
    }

    const title = parsed.title || parsed.event_title;
    const code  = parsed.code || parsed.event_code;
    if (title && (String(categoryKey).includes('event'))) {
      const extra = code ? ` (Code: ${code}).` : '.';
      return `${a} created event “${title}”${extra}`;
    }

    return `${a} performed “${titleize(action)}”.`;
  }

  // Apply humanize on table load
  $$('.js-humanize').forEach(el => {
    const admin = el.getAttribute('data-admin') || 'Admin';
    const action = el.getAttribute('data-action') || '';
    // NOTE: your blade uses data-category="{{ $catKey }}" (not data-category-key)
    const categoryKey = el.getAttribute('data-category-key') || el.getAttribute('data-category') || '';
    const raw = el.getAttribute('data-raw') || '';
    el.textContent = summarize({ admin, action, categoryKey, raw });
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

  // ✅ Raw details toggle (collapsed by default)
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

    // your blade doesn't set data-row-id currently; fall back to row.id
    lastRowId = detailsEl.getAttribute('data-row-id') || row.id || null;

    const timestamp = detailsEl.getAttribute('data-timestamp') || '';
    const categoryLabel = detailsEl.getAttribute('data-category') || ''; // blade currently puts catKey here, not label
    const action = detailsEl.getAttribute('data-action') || '';
    const admin = detailsEl.getAttribute('data-admin') || 'Admin';
    const adminUrl = detailsEl.getAttribute('data-admin-url') || '';
    const raw = detailsEl.getAttribute('data-raw') || '';
    const categoryKey = detailsEl.getAttribute('data-category-key') || detailsEl.getAttribute('data-category') || '';
    const entityType = detailsEl.getAttribute('data-entity-type') || '';
    const entityId = detailsEl.getAttribute('data-entity-id') || '';

    metaEl.innerHTML = `
      ${escapeHtml(timestamp)} • ${escapeHtml(categoryLabel)} • ${escapeHtml(action)} •
      ${adminUrl
        ? `<a class="admin-link" href="${escapeHtml(adminUrl)}"><span class="admin-pill">${escapeHtml(admin)}</span></a>`
        : `<span class="admin-pill">${escapeHtml(admin)}</span>`
      }
    `;

    summaryEl.textContent = summarize({ admin, action, categoryKey, raw });

    // Chips (old vibe: who did it + what entity)
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

    // ✅ start collapsed
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

  // ✅ “Show & highlight row” inside modal (old behavior)
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

  // Row click opens modal (except clicking links/buttons)
  $$('.log-row').forEach(row => {
    row.addEventListener('click', (e) => {
      if (e.target.closest('a') || e.target.closest('button')) return;
      openModalFromRow(row);
    });
  });

  // “More” button opens modal
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

    // keep inside viewport
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
    portalState.set(wrap, { pop, btn, hidden, valueSpan, items, searchInput });

    function setSelected(value, label) {
      if (hidden) hidden.value = value;
      if (valueSpan) valueSpan.textContent = label;
      items.forEach(i => i.classList.toggle('is-selected', (i.getAttribute('data-value') ?? '') === value));
    }

    const current = (hidden?.value ?? '');
    const found = items.find(i => (i.getAttribute('data-value') ?? '') === current);
    if (found) setSelected(current, found.textContent.trim());
    else setSelected('', name === 'action' ? 'All actions' : 'All categories');

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

        applyClientFilters();
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

  // ---------- Client-side filtering + autosuggest ----------
  const rows = $$('.log-row');
  const qInput = $('#q');
  const dateStart = $('#date_start');
  const dateEnd = $('#date_end');
  const perPageSel = $('#per_page');

  const sugg = document.createElement('div');
  sugg.className = 'autosuggest';
  sugg.style.display = 'none';
  document.body.appendChild(sugg);

  function parseRowDate(row) {
    const details = $('.log-details', row);
    const t = details?.getAttribute('data-timestamp') || '';
    const datePart = t.split(' ')[0];
    if (!datePart || datePart.length !== 10) return null;
    return datePart;
  }

  function applyClientFilters() {
    const q = (qInput?.value || '').trim().toLowerCase();
    const start = (dateStart?.value || '').trim();
    const end = (dateEnd?.value || '').trim();

    const actionVal = $('#actionHidden')?.value || '';
    const catVal = $('#categoryHidden')?.value || '';

    rows.forEach(row => {
      let ok = true;

      // NOTE: your blade uses data-search-text, not data-search
      const search = (row.getAttribute('data-search') || row.getAttribute('data-search-text') || '');
      if (q) ok = ok && search.includes(q);

      const details = $('.log-details', row);
      const rowAction = (details?.getAttribute('data-action') || '');
      const rowCatKey = (details?.getAttribute('data-category-key') || details?.getAttribute('data-category') || '');

      if (actionVal) ok = ok && rowAction === actionVal;
      if (catVal) ok = ok && rowCatKey === catVal;

      if (start || end) {
        const d = parseRowDate(row);
        if (!d) ok = false;
        if (start && d < start) ok = false;
        if (end && d > end) ok = false;
      }

      row.style.display = ok ? '' : 'none';
    });
  }

  qInput?.addEventListener('input', () => {
    applyClientFilters();
    showSuggest();
  });
  dateStart?.addEventListener('change', applyClientFilters);
  dateEnd?.addEventListener('change', applyClientFilters);

  // keep server pagination
  perPageSel?.addEventListener('change', () => form?.submit());

  function buildSuggestions(query) {
    if (!query) return [];
    const scored = [];

    rows.forEach(row => {
      if (row.style.display === 'none') return;
      const text = (row.getAttribute('data-search') || row.getAttribute('data-search-text') || '');
      const idx = text.indexOf(query);
      if (idx !== -1) scored.push({ row, idx });
    });

    scored.sort((a,b) => a.idx - b.idx);
    return scored.slice(0, 6);
  }

  function positionSuggest() {
    if (!qInput) return;
    const r = qInput.getBoundingClientRect();
    sugg.style.position = 'fixed';
    sugg.style.left = `${r.left}px`;
    sugg.style.top = `${r.bottom + 8}px`;
    sugg.style.width = `${r.width}px`;
    sugg.style.zIndex = '10060';
  }

  function showSuggest() {
    if (!qInput) return;
    const query = qInput.value.trim().toLowerCase();
    if (query.length < 2) { sugg.style.display = 'none'; return; }

    const items = buildSuggestions(query);
    if (!items.length) { sugg.style.display = 'none'; return; }

    positionSuggest();
    sugg.innerHTML = items.map(({ row }) => {
      const details = $('.log-details', row);
      const admin = details?.getAttribute('data-admin') || 'Admin';
      const action = details?.getAttribute('data-action') || '';
      const cat = details?.getAttribute('data-category') || '';
      const raw = details?.getAttribute('data-raw') || '';
      const categoryKey = details?.getAttribute('data-category-key') || details?.getAttribute('data-category') || '';
      const sum = summarize({ admin, action, categoryKey, raw });

      return `
        <button type="button" class="as-item" data-row="${escapeHtml(row.id)}">
          <div class="as-top">${escapeHtml(admin)} • ${escapeHtml(cat)} • ${escapeHtml(action)}</div>
          <div class="as-sub">${escapeHtml(sum)}</div>
        </button>
      `;
    }).join('');

    sugg.style.display = 'block';
  }

  // click suggest -> jump + highlight
  sugg.addEventListener('click', (e) => {
    const btn = e.target.closest('.as-item');
    if (!btn) return;
    const id = btn.getAttribute('data-row');
    const row = id ? document.getElementById(id) : null;
    if (!row) return;

    sugg.style.display = 'none';

    $$('.log-row.is-hit').forEach(r => r.classList.remove('is-hit'));
    row.classList.add('is-hit');
    row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    setTimeout(() => row.classList.remove('is-hit'), 1800);
  });

  document.addEventListener('click', (e) => {
    if (e.target.closest('#q')) return;
    if (e.target.closest('.autosuggest')) return;
    sugg.style.display = 'none';
  });

  window.addEventListener('resize', () => {
    if (sugg.style.display !== 'none') positionSuggest();
  });
  window.addEventListener('scroll', () => {
    if (sugg.style.display !== 'none') positionSuggest();
  }, { passive: true });

  // Reset (client-side)
  $('#logsResetBtn')?.addEventListener('click', () => {
    if (dateStart) dateStart.value = '';
    if (dateEnd) dateEnd.value = '';
    if (qInput) qInput.value = '';

    const actionHidden = $('#actionHidden');
    const categoryHidden = $('#categoryHidden');
    if (actionHidden) actionHidden.value = '';
    if (categoryHidden) categoryHidden.value = '';

    $$('.cselect').forEach(s => {
      const name = s.getAttribute('data-name');
      const valueSpan = $('.cselect-value', s);
      if (valueSpan) valueSpan.textContent = name === 'action' ? 'All actions' : 'All categories';
    });

    applyClientFilters();
  });

  // first run
  applyClientFilters();
})();
    