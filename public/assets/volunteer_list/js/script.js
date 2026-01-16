/* =========================================================
   VOLUNTEER LIST: cards, filters, search → profile behaviour
   ✅ PATCH: Batch dropdown now uses DB-provided list (data-batches)
   ✅ PATCH: Rows dropdown now uses custom portal dropdown (NO native select)
   ✅ PATCH: Expose portal dropdown for Add Volunteer modal
   ✅ PATCH (THIS REQUEST): Removed schedule builder from script.js
   ✅ PATCH (THIS REQUEST): Added schedule "bridge" only (no builder)
========================================================= */
(() => {
  const root = document.getElementById("vlRoot");
  if (!root) return;

  // Core DOM
  const cardsGrid = document.getElementById("cards-grid");
  const gridCount = document.getElementById("grid-count");
  const totalEl   = document.getElementById("vlTotal");

  const arrowUp   = document.getElementById("arrow-up");
  const arrowDown = document.getElementById("arrow-down");
  const navWrap   = document.getElementById("vlNav");
  const pageNow   = document.getElementById("vlPageNow");
  const pageTotal = document.getElementById("vlPageTotal");

  const searchInput = document.getElementById("vlSearch");
  const searchClear = document.getElementById("vlSearchClear");
  const suggestEl   = document.getElementById("vlSuggest");

  const panel        = document.getElementById("vlPanel");
  const filterToggle = document.getElementById("vlFilterToggle");
  const applyBtn     = document.getElementById("vlApply");
  const resetBtn     = document.getElementById("vlReset");

  // Dropdowns
  const ddSort     = root.querySelector('.vl-dd[data-dd="sort"]');
  const ddCourse   = root.querySelector('.vl-dd[data-dd="course"]');
  const ddBarangay = root.querySelector('.vl-dd[data-dd="barangay"]');
  const ddDistrict = root.querySelector('.vl-dd[data-dd="district"]');
  const ddYear     = root.querySelector('.vl-dd[data-dd="year"]');
  const ddBatch    = root.querySelector('.vl-dd[data-dd="batch"]'); // ✅ PATCH
  const ddDay      = root.querySelector('.vl-dd[data-dd="day"]');
  const ddBlock    = root.querySelector('.vl-dd[data-dd="block"]');
  const ddStatus   = root.querySelector('.vl-dd[data-dd="status"]');

  // ✅ PATCH: Custom Rows dropdown (no native select)
  const ddPerPage  = root.querySelector('.vl-dd[data-dd="perpage"]');

  // Add-volunteer modal preview
  const photoInput        = document.getElementById("vlPhotoInput");
  const photoPreview      = document.getElementById("vlPhotoPreview");
  const addVolunteerModal = document.getElementById("addVolunteerModal");

  const DEFAULT_AVATAR =
    (root.getAttribute("data-default-avatar") || "").trim() ||
    "/storage/defaults/default_user.png";

  // ✅ PATCH: user-selectable rows per page (3 / 6 / 9)
  const PER_PAGE_ALLOWED = [3,6,9];
  let perPage = (() => {
    const saved = Number(localStorage.getItem('vl_perPage') || '6');
    return PER_PAGE_ALLOWED.includes(saved) ? saved : 6;
  })();

  // URLs
  const DATA_URL = (root.getAttribute("data-data-url") || "/volunteers/data").trim();
  const PROFILE_BASE = (root.getAttribute("data-profile-url-base") || "/volunteer-profile").trim();

  function safeJson(str, fallback) {
    try { return str ? JSON.parse(str) : fallback; } catch { return fallback; }
  }

  function escapeHtml(value) {
    if (value === null || value === undefined) return "";
    return String(value).replace(/[&<>"']/g, (c) => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;",
      '"': "&quot;", "'": "&#39;"
    }[c]));
  }

  // Data from Blade
  const courses   = safeJson(root.getAttribute("data-courses"),   []);
  const barangays = safeJson(root.getAttribute("data-barangays"), []);
  const districts = safeJson(root.getAttribute("data-districts"), []);

  // ✅ PATCH: batch list from Blade (DB distinct)
  const batchesRaw = safeJson(root.getAttribute("data-batches"), []);

  // ✅ PATCH: sanitized + sorted batchItems for dropdown
  const batchItems = (() => {
    const nums = (Array.isArray(batchesRaw) ? batchesRaw : [])
      .map(v => String(v ?? "").trim())
      .filter(v => v !== "")
      .map(v => Number(v))
      .filter(n => Number.isFinite(n) && n >= 1900 && n <= 2100);

    nums.sort((a, b) => b - a);

    return [
      { value: "", label: "All Batches" },
      ...nums.map(n => ({ value: String(n), label: String(n) }))
    ];
  })();

  const applied = {
    page: 1,
    search: "",
    sort: "name_asc",
    course_id: "",
    barangay: "",
    district: "",
    year_level: "",
    batch_year: "",
    day: "",
    schedule_day: "",
    status: ""
  };
  const pending = { ...applied };

  let currentPage = 1;
  let lastPage    = 1;
  let lastItems   = [];

  /* =========================================================
     RICH SEARCH SUGGESTIONS
  ========================================================= */
  const SUGGEST_LIMIT = 6;
  let suggestAbort = null;
  let suggestCache = new Map(); // key -> { at:number, items:Array }

  function hideSuggest() {
    if (!suggestEl) return;
    suggestEl.hidden = true;
    suggestEl.innerHTML = "";
  }

  function showSuggestLoading() {
    if (!suggestEl) return;
    suggestEl.hidden = false;
    suggestEl.innerHTML = `
      <div class="vl-suggestItem is-muted" style="pointer-events:none;">
        <span>Searching…</span>
      </div>
    `;
  }

  function debounce(fn, wait = 200) {
    let t = null;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), wait);
    };
  }

  function normalizeQ(q) {
    return (q || "").toString().trim();
  }

  function applySearch(q) {
    const query = normalizeQ(q);
    if (searchInput) searchInput.value = query;

    pending.search = query;
    applied.search = query;

    pending.page = 1;
    applied.page = 1;

    hideSuggest();
    fetchPage({ page: 1 });
  }

  async function fetchSuggest(qRaw) {
    const q = normalizeQ(qRaw);
    if (!q || q.length < 2) { hideSuggest(); return; }
    if (!suggestEl) return;

    const cacheKey = JSON.stringify({
      q,
      course_id: applied.course_id || "",
      barangay: applied.barangay || "",
      district: applied.district || "",
      year_level: applied.year_level || "",
      batch_year: applied.batch_year || "",
      day: applied.day || "",
      schedule_day: applied.schedule_day || "",
      status: applied.status || "",
      sort: applied.sort || ""
    });

    const cached = suggestCache.get(cacheKey);
    if (cached && (Date.now() - cached.at) < 2000) {
      renderSuggest(q, cached.items);
      return;
    }

    try { suggestAbort?.abort(); } catch {}
    suggestAbort = new AbortController();

    showSuggestLoading();

    const params = {
      page: 1,
      per_page: SUGGEST_LIMIT,
      search: q,
      sort: applied.sort || "name_asc",
      course_id: applied.course_id || "",
      barangay: applied.barangay || "",
      district: applied.district || "",
      year_level: applied.year_level || "",
      batch_year: applied.batch_year || "",
      day: applied.day || "",
      schedule_day: applied.schedule_day || "",
      status: applied.status || ""
    };

    const url = buildUrl(params);

    try {
      const res = await fetch(url, {
        headers: { Accept: "application/json" },
        signal: suggestAbort.signal
      });

      const text = await res.text();
      if (!res.ok) {
        hideSuggest();
        console.error("Suggest API error:", res.status, text);
        return;
      }

      let json;
      try { json = JSON.parse(text); }
      catch {
        hideSuggest();
        console.error("Suggest JSON parse error:", text);
        return;
      }

      const items = Array.isArray(json.data) ? json.data : [];
      suggestCache.set(cacheKey, { at: Date.now(), items });
      renderSuggest(q, items);
    } catch (err) {
      if (String(err?.name) === "AbortError") return;
      hideSuggest();
      console.error("Suggest fetch error:", err);
    }
  }

  function metaLine(v) {
    const parts = [];
    const course = v?.course?.abbr
      ? `${v.course.abbr}`
      : (v?.course?.course_name || "");
    if (course) parts.push(course);

    if (v?.year_level) parts.push(formatYearLevel(v.year_level));
    if (v?.barangay) parts.push(v.barangay);
    if (v?.district) parts.push(`District ${v.district}`);
    if (v?.batch_year) parts.push(`Batch ${v.batch_year}`);

    const status = (v?.status || "active") === "active" ? "Active" : "Inactive";
    parts.push(status);

    return parts.filter(Boolean).join(" • ");
  }

  function renderSuggest(q, volunteers) {
    if (!suggestEl) return;

    const list = Array.isArray(volunteers) ? volunteers.slice(0, SUGGEST_LIMIT) : [];

    const parts = [];
    parts.push(`
      <button type="button" class="vl-suggestItem" data-suggest-action="search" data-q="${escapeHtml(q)}">
        <i class="fa-solid fa-magnifying-glass me-2"></i>
        <span>Search for <strong>${escapeHtml(q)}</strong></span>
      </button>
    `);

    if (!list.length) {
      parts.push(`
        <div class="vl-suggestItem is-muted" style="pointer-events:none;">
          <span>No matches</span>
        </div>
      `);
      suggestEl.hidden = false;
      suggestEl.innerHTML = parts.join("");
      return;
    }

    list.forEach(v => {
      const id = encodeURIComponent(v.volunteer_id);
      const name = v.full_name || "Unnamed Volunteer";
      const meta = metaLine(v);

      parts.push(`
        <button type="button"
                class="vl-suggestItem"
                data-suggest-action="goto"
                data-id="${escapeHtml(id)}">
          <div class="vl-suggestMain">
            <i class="fa-solid fa-user me-2"></i>
            <span class="vl-suggestName">${escapeHtml(name)}</span>
          </div>
          <div class="vl-suggestMeta">${escapeHtml(meta)}</div>
        </button>
      `);
    });

    suggestEl.hidden = false;
    suggestEl.innerHTML = parts.join("");
  }

  const runSuggestDebounced = debounce(() => fetchSuggest(searchInput?.value || ""), 220);

  function buildUrl(params) {
    const url = new URL(DATA_URL, window.location.origin);
    Object.entries(params).forEach(([k, v]) => {
      if (v !== undefined && v !== null && v !== "") {
        url.searchParams.set(k, v);
      }
    });
    return url.toString();
  }

  function setPanel(open) {
    if (!panel || !filterToggle) return;
    panel.hidden = !open;
    filterToggle.setAttribute("aria-expanded", open ? "true" : "false");
    closePortalMenu();
  }

  function setDropdownValue(dd, value, label) {
    if (!dd) return;
    dd.dataset.value = value ?? "";
    const text = dd.querySelector("[data-dd-text]");
    if (text && label != null) text.textContent = label;
  }

  function resolveAvatar(v) {
    const a = (v?.avatar_url ?? "").toString().trim();
    return a || DEFAULT_AVATAR;
  }

  function showError(message, details = "") {
    cardsGrid.innerHTML = `
      <div class="vol-error">
        <div class="vol-error__title">Failed to load volunteers.</div>
        <div class="vol-error__msg">${escapeHtml(message)}</div>
        ${details ? `<pre class="vol-error__pre">${escapeHtml(details)}</pre>` : ""}
      </div>
    `;
    if (gridCount) gridCount.textContent = "";
    if (totalEl) totalEl.textContent = "0";
  }

  /* =========================================================
     TIME META (for filter dropdown)
  ========================================================= */
  const DAYS = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

  const TIME_META = {
    "7:30-8:20":  { label:"7:30–8:20 AM",  group:"AM", start:450,  end:500 },
    "8:00-9:20":  { label:"8:00–9:20 AM",  group:"AM", start:480,  end:560 },
    "8:00-10:50": { label:"8:00–10:50 AM", group:"AM", start:480,  end:650 },
    "8:30-9:50":  { label:"8:30–9:50 AM",  group:"AM", start:510,  end:590 },
    "8:30-11:30": { label:"8:30–11:30 AM", group:"AM", start:510,  end:690 },
    "9:30-10:50": { label:"9:30–10:50 AM", group:"AM", start:570,  end:650 },
    "11:00-12:20":{ label:"11:00–12:20 PM",group:"AM", start:660,  end:740 },

    "12:30-1:50": { label:"12:30–1:50 PM", group:"PM", start:750,  end:830 },
    "12:30-2:50": { label:"12:30–2:50 PM", group:"PM", start:750,  end:890 },
    "2:00-3:20":  { label:"2:00–3:20 PM",  group:"PM", start:840,  end:920 },
    "2:00-4:50":  { label:"2:00–4:50 PM",  group:"PM", start:840,  end:1010},
    "3:30-4:50":  { label:"3:30–4:50 PM",  group:"PM", start:930,  end:1010},
    "5:00-6:20":  { label:"5:00–6:20 PM",  group:"PM", start:1020, end:1100},
    "6:30-7:20":  { label:"6:30–7:20 PM",  group:"PM", start:1110, end:1160},
    "6:30-8:50":  { label:"6:30–8:50 PM",  group:"PM", start:1110, end:1250},
    "7:30-8:50":  { label:"7:30–8:50 PM",  group:"PM", start:1170, end:1250}
  };

  function formatYearLevel(year) {
    const map = { 1:"1st Year", 2:"2nd Year", 3:"3rd Year", 4:"4th Year" };
    const key = Number(year);
    return map[key] || "Year N/A";
  }

  /* =========================================================
     CARD RENDERING
  ========================================================= */
  function renderCard(v) {
    const avatar = resolveAvatar(v);
    const id     = encodeURIComponent(v.volunteer_id);

    const fullName   = v.full_name || "Unnamed Volunteer";
    const courseName = v.course?.course_name || "";
    const courseAbbr = v.course?.abbr || "";
    const courseDisplay = courseAbbr
      ? `${courseAbbr} — ${courseName}`
      : (courseName || "No course");

    const yearLevel = formatYearLevel(v.year_level);
    const barangay  = v.barangay || "No barangay";
    const districtLabel = v.district ? `District ${v.district}` : "District N/A";

    const contact   = v.contact_number || "";
    const contactLabel = contact ? `Contact # ${contact}` : "";

    const isActive     = (v.status || "active") === "active";
    const statusTitle  = isActive ? "Active volunteer" : "Alumni / Inactive";
    const statusClass  = isActive ? "status-dot--active" : "status-dot--inactive";

    const a = document.createElement("a");
    a.className = "student-card";
    a.href = `/volunteer-profile/${id}`;

    a.innerHTML = `
      <div class="avatar-wrap" title="${escapeHtml(statusTitle)}">
        <img class="avatar"
             src="${escapeHtml(avatar)}"
             alt="${escapeHtml(fullName)}" />
        <span class="status-dot ${statusClass}"></span>
      </div>

      <div class="meta">
        <div class="vl-row vl-rowName">
          <div class="vl-name" title="${escapeHtml(fullName)}">
            ${escapeHtml(fullName)}
          </div>
        </div>

        <div class="vl-row vl-rowCourse">
          <div class="vl-course" title="${escapeHtml(courseDisplay)}">
            ${escapeHtml(courseDisplay)}
          </div>
          <span class="vl-pillSmall" title="${escapeHtml(yearLevel)}">
            ${escapeHtml(yearLevel)}
          </span>
        </div>

        <div class="vl-row vl-rowLocation">
          <div class="vl-location" title="${escapeHtml(barangay)}">
            ${escapeHtml(barangay)}
          </div>
          <span class="vl-pillSmall" title="${escapeHtml(districtLabel)}">
            ${escapeHtml(districtLabel)}
          </span>
        </div>

        ${contactLabel ? `
          <div class="vl-row vl-rowContact">
            <div class="vl-course vl-contact" title="${escapeHtml(contactLabel)}">
              ${escapeHtml(contactLabel)}
            </div>
          </div>` : ""}
      </div>
    `;

    const img = a.querySelector("img.avatar");
    img?.addEventListener("error", () => { img.src = DEFAULT_AVATAR; }, { once: true });

    return a;
  }

  /* =========================================================
     FETCH PAGE
  ========================================================= */
  async function fetchPage(paramsOverride = {}) {
    const params = {
      page: paramsOverride.page ?? applied.page ?? 1,
      per_page: paramsOverride.per_page ?? perPage,

      search: applied.search ?? "",
      sort:   applied.sort   ?? "",

      course_id:    applied.course_id    ?? "",
      barangay:     applied.barangay     ?? "",
      district:     applied.district     ?? "",
      year_level:   applied.year_level   ?? "",
      batch_year:   applied.batch_year   ?? "",
      day:          applied.day          ?? "",
      schedule_day: applied.schedule_day ?? "",
      status:       applied.status       ?? ""
    };

    const url = buildUrl(params);

    try {
      const res  = await fetch(url, { headers: { Accept: "application/json" } });
      const text = await res.text();

      if (!res.ok) {
        showError(`HTTP ${res.status} ${res.statusText}`, text.slice(0, 1500));
        console.error("Volunteer API error:", res.status, text);
        return;
      }

      let json;
      try { json = JSON.parse(text); }
      catch {
        showError("Response is not valid JSON.", text.slice(0, 1500));
        return;
      }

      lastItems = Array.isArray(json.data) ? json.data : [];

      cardsGrid.innerHTML = "";
      lastItems.forEach(v => cardsGrid.appendChild(renderCard(v)));

      const total = Number(json.total ?? 0);
      if (gridCount) {
        const label = total === 1 ? "student" : "students";
        gridCount.textContent = `${total} ${label}`;
      }
      if (totalEl) totalEl.textContent = String(total);

      currentPage = Number(json.current_page ?? 1);
      lastPage    = Number(json.last_page    ?? 1);

      if (pageNow)   pageNow.textContent   = String(currentPage);
      if (pageTotal) pageTotal.textContent = String(lastPage);

      if (navWrap) navWrap.style.display = "flex";

      if (arrowUp)   arrowUp.disabled   = currentPage <= 1;
      if (arrowDown) arrowDown.disabled = currentPage >= lastPage;

      arrowUp?.classList.toggle("disabled",   currentPage <= 1);
      arrowDown?.classList.toggle("disabled", currentPage >= lastPage);

    } catch (err) {
      showError("Network/JS error while fetching.", String(err));
      console.error(err);
    }
  }

  /* =========================================================
     PORTAL DROPDOWN (shared)
  ========================================================= */
  let portalEl       = null;
  let portalOwner    = null;
  let portalAllItems = [];
  let portalOnPick   = null;
  let portalHasSearch   = false;
  let portalIsTimeBlock = false;
  let portalTimeMode    = "all";

  function ensurePortal(){
    if (portalEl) return portalEl;
    portalEl = document.createElement("div");
    portalEl.className = "vl-ddPortal";
    portalEl.style.display = "none";
    document.body.appendChild(portalEl);
    return portalEl;
  }

  function closePortalMenu(){
    if (!portalEl) return;
    portalEl.style.display = "none";
    portalEl.innerHTML = "";
    portalOwner = null;
    portalAllItems = [];
    portalOnPick = null;
    portalHasSearch = false;
    portalIsTimeBlock = false;
    portalTimeMode = "all";
    portalEl.classList.remove("vl-ddPortal--time");
  }

  function renderPortalItems(filterText = ""){
    if (!portalEl) return;
    const q = (filterText || "").trim().toLowerCase();

    const items = portalAllItems.filter(it => {
      if (portalIsTimeBlock && portalTimeMode !== "all" && it.value) {
        const p = String(it.period || "").toLowerCase();
        if (p && p !== portalTimeMode) return false;
      }

      if (!portalHasSearch || !q) return true;

      const label = String(it.label || "").toLowerCase();
      const meta  = String(it.meta  || "").toLowerCase();
      return label.includes(q) || meta.includes(q);
    });

    const body = portalEl.querySelector(".vl-ddPortalBody");
    if (!body) return;

    body.innerHTML = "";
    const frag = document.createDocumentFragment();

    if (!items.length) {
      const empty = document.createElement("div");
      empty.style.padding = "10px 12px";
      empty.style.color   = "#6b7280";
      empty.style.fontWeight = "800";
      empty.textContent   = "No results";
      frag.appendChild(empty);
      body.appendChild(frag);
      return;
    }

    items.forEach(it => {
      const b = document.createElement("button");
      b.type = "button";
      b.className = "vl-ddItem";
      b.dataset.value = it.value ?? "";
      b.dataset.label = it.label ?? "";
      b.innerHTML = it.meta
        ? `<span>${escapeHtml(it.label)}</span><span class="vl-ddMeta">${escapeHtml(it.meta)}</span>`
        : `<span>${escapeHtml(it.label)}</span>`;
      frag.appendChild(b);
    });

    body.appendChild(frag);
  }

  function openPortalMenu(dd, items, onPick, options = {}){
    const portal = ensurePortal();
    portalOwner      = dd;
    portalAllItems   = Array.isArray(items) ? items : [];
    portalOnPick     = onPick;
    portalHasSearch  = !!options.search;
    portalIsTimeBlock = !!options.timeBlock;
    portalTimeMode   = "all";

    portal.classList.toggle("vl-ddPortal--time", portalIsTimeBlock);

    const btn = dd.querySelector(".vl-ddBtn");
    if (!btn) return;

    let headerHtml = "";

    if (portalIsTimeBlock) {
      headerHtml = `
        <div class="vl-timeFilter">
          <div class="vl-timeHeading">Filter by Time</div>
          <div class="vl-timeTabs">
            <button type="button" class="vl-timeTab is-active" data-time-mode="all">All</button>
            <button type="button" class="vl-timeTab" data-time-mode="am">AM</button>
            <button type="button" class="vl-timeTab" data-time-mode="pm">PM</button>
          </div>
          <div class="vl-ddPortalHeader vl-ddPortalHeader--time">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input class="vl-ddPortalSearch" type="text" placeholder="Search time slot..." autocomplete="off" />
          </div>
        </div>
      `;
    } else if (portalHasSearch) {
      headerHtml = `
        <div class="vl-ddPortalHeader">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input class="vl-ddPortalSearch" type="text" placeholder="Search..." autocomplete="off" />
        </div>
      `;
    }

    portal.innerHTML = `
      ${headerHtml}
      <div class="vl-ddPortalBody"></div>
    `;

    const r = btn.getBoundingClientRect();
    const gap = 8;
    const left = Math.max(10, r.left);
    const top  = r.bottom + gap;

    portal.style.left = `${left}px`;
    portal.style.top  = `${top}px`;
    portal.style.minWidth = `${r.width}px`;
    portal.style.maxWidth = `${Math.min(window.innerWidth - 20 - left, 520)}px`;
    portal.style.display = "block";

    const input = portal.querySelector(".vl-ddPortalSearch");
    if (input) {
      input.addEventListener("input", () => renderPortalItems(input.value));
      setTimeout(() => input.focus(), 0);
    }

    if (portalIsTimeBlock) {
      const tabs = portal.querySelectorAll(".vl-timeTab");
      tabs.forEach(tab => {
        tab.addEventListener("click", () => {
          tabs.forEach(t => t.classList.remove("is-active"));
          tab.classList.add("is-active");
          portalTimeMode = tab.getAttribute("data-time-mode") || "all";
          renderPortalItems(input ? input.value : "");
        });
      });
    }

    renderPortalItems("");

    portal.onclick = (e) => {
      const item = e.target.closest(".vl-ddItem");
      if (!item) return;
      const value = item.dataset.value ?? "";
      let label   = item.dataset.label || item.textContent.trim();

      if (portalIsTimeBlock) {
        const metaSpan = item.querySelector(".vl-ddMeta");
        const metaText = metaSpan ? metaSpan.textContent.trim() : "";
        if (metaText) label = `${label} ${metaText}`;
      }

      portalOnPick?.(value, label);
      closePortalMenu();
    };
  }

  function wireDropdown(dd, items, onPick, options = {}){
    if (!dd) return;
    const btn = dd.querySelector(".vl-ddBtn");
    const withSearch = !!options.search;
    const timeBlock  = !!options.timeBlock;

    btn?.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      if (portalOwner === dd && portalEl && portalEl.style.display === "block") {
        closePortalMenu();
        return;
      }

      closePortalMenu();
      openPortalMenu(dd, items, onPick, { search: withSearch, timeBlock });
    });
  }

  /* ---------------- dropdown data ---------------- */
  const sortItems = [
    { value: "name_asc",  label: "Sort by Name (A–Z)" },
    { value: "name_desc", label: "Sort by Name (Z–A)" },
  ];

  const courseItems = [
    { value: "", label: "All Courses" },
    ...courses.map(c => ({ value: String(c.course_id), label: c.course_name }))
  ];

  const barangayItems = [
    { value: "", label: "All Barangays" },
    ...barangays.map(b => ({ value: String(b), label: String(b) }))
  ];

  const districtItems = [
    { value: "", label: "All Districts" },
    ...districts.map(d => ({ value: String(d.district_id), label: d.district_name }))
  ];

  const yearItems = [
    { value: "", label: "All Year Levels" },
    { value: "1", label: "1st Year" },
    { value: "2", label: "2nd Year" },
    { value: "3", label: "3rd Year" },
    { value: "4", label: "4th Year" },
  ];

  const dayItems = [
    { value: "", label: "Any Day" },
    ...DAYS.map(d => ({ value: d, label: d }))
  ];

  const blockItems = [
    { value: "", label: "Any Time", meta: "", period: "all" },
    ...Object.entries(TIME_META).map(([range, meta]) => ({
      value: range,
      label: range,
      meta:  meta.group,
      period: meta.group.toLowerCase()
    }))
  ];

  const statusItems = [
    { value: "",         label: "All Status" },
    { value: "active",   label: "Active Only" },
    { value: "inactive", label: "Inactive / Alumni" }
  ];

  const perPageItems = [
    { value: "3", label: "3 rows" },
    { value: "6", label: "6 rows" },
    { value: "9", label: "9 rows" },
  ];

  wireDropdown(ddSort, sortItems, (value, label) => {
    pending.sort = value || "name_asc";
    setDropdownValue(ddSort, pending.sort, label || "Sort by Name (A–Z)");
  });

  wireDropdown(ddCourse, courseItems, (value, label) => {
    pending.course_id = value || "";
    setDropdownValue(ddCourse, pending.course_id, label || "All Courses");
  }, { search: true });

  wireDropdown(ddBarangay, barangayItems, (value, label) => {
    pending.barangay = value || "";
    setDropdownValue(ddBarangay, pending.barangay, label || "All Barangays");
  }, { search: true });

  wireDropdown(ddDistrict, districtItems, (value, label) => {
    pending.district = value || "";
    setDropdownValue(ddDistrict, pending.district, label || "All Districts");
  });

  wireDropdown(ddYear, yearItems, (value, label) => {
    pending.year_level = value || "";
    setDropdownValue(ddYear, pending.year_level, label || "All Year Levels");
  });

  wireDropdown(ddBatch, batchItems, (value, label) => {
    pending.batch_year = value || "";
    setDropdownValue(ddBatch, pending.batch_year, label || "All Batches");
  }, { search: true });

  wireDropdown(ddDay, dayItems, (value, label) => {
    pending.day = value || "";
    setDropdownValue(ddDay, pending.day, label || "Any Day");
  });

  wireDropdown(ddBlock, blockItems, (value, label) => {
    pending.schedule_day = value || "";
    setDropdownValue(ddBlock, pending.schedule_day, label || "Any Time");
  }, { search: true, timeBlock: true });

  wireDropdown(ddStatus, statusItems, (value, label) => {
    pending.status = value || "";
    setDropdownValue(ddStatus, pending.status, label || "All Status");
  });

  wireDropdown(ddPerPage, perPageItems, (value, label) => {
    const v = Number(value || "6");
    perPage = PER_PAGE_ALLOWED.includes(v) ? v : 6;
    localStorage.setItem('vl_perPage', String(perPage));

    setDropdownValue(ddPerPage, String(perPage), label || `${perPage} rows`);

    applied.page = 1;
    fetchPage({ page: 1 });
  });

  /* ---------------- search ---------------- */
  function setClearVisible() {
    if (!searchClear || !searchInput) return;
    const has = !!normalizeQ(searchInput.value);
    searchClear.style.visibility = has ? "visible" : "hidden";
  }

  setClearVisible();

  searchInput?.addEventListener("input", () => {
    setClearVisible();
    runSuggestDebounced();
  });

  searchInput?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      applySearch(searchInput.value || "");
      return;
    }
    if (e.key === "Escape") {
      hideSuggest();
    }
  });

  searchClear?.addEventListener("click", (e) => {
    e.preventDefault();
    if (searchInput) searchInput.value = "";
    setClearVisible();
    hideSuggest();
    applySearch("");
  });

  suggestEl?.addEventListener("mousedown", (e) => {
    e.preventDefault();
  });

  suggestEl?.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-suggest-action]");
    if (!btn) return;

    const action = btn.getAttribute("data-suggest-action");
    if (action === "search") {
      const q = btn.getAttribute("data-q") || (searchInput?.value || "");
      applySearch(q);
      setClearVisible();
      return;
    }

    if (action === "goto") {
      const id = btn.getAttribute("data-id");
      if (!id) return;
      window.location.href = `${PROFILE_BASE}/${id}`;
    }
  });

  /* ---------------- toolbar / panel ---------------- */
  filterToggle?.addEventListener("click", (e) => {
    e.preventDefault();
    setPanel(panel.hidden);
  });

  document.addEventListener("mousedown", (e) => {
    if (suggestEl && !suggestEl.hidden && !e.target.closest(".vl-search")) {
      suggestEl.hidden = true;
      suggestEl.innerHTML = "";
    }

    if (portalEl && portalEl.style.display === "block") {
      const insidePortal = portalEl.contains(e.target);
      const insideOwner  = portalOwner && portalOwner.contains(e.target);
      if (!insidePortal && !insideOwner) closePortalMenu();
    }

    if (!panel.hidden) {
      const insidePanel  = panel.contains(e.target);
      const insideToggle = filterToggle && filterToggle.contains(e.target);
      const insidePortal = portalEl && portalEl.contains(e.target);
      if (!insidePanel && !insideToggle && !insidePortal) {
        setPanel(false);
      }
    }
  });

  window.addEventListener("scroll", () => closePortalMenu(), { passive:true });
  window.addEventListener("resize", () => closePortalMenu());

  /* ---------------- apply / reset ---------------- */
  applyBtn?.addEventListener("click", () => {
    Object.assign(applied, pending);
    applied.page = 1;
    fetchPage({ page: 1 });
  });

  resetBtn?.addEventListener("click", () => {
    pending.page         = 1;
    pending.search       = "";
    pending.sort         = "name_asc";
    pending.course_id    = "";
    pending.barangay     = "";
    pending.district     = "";
    pending.year_level   = "";
    pending.batch_year   = "";
    pending.day          = "";
    pending.schedule_day = "";
    pending.status       = "";

    Object.assign(applied, pending);

    if (searchInput) searchInput.value = "";
    setClearVisible();
    if (suggestEl) { suggestEl.hidden = true; suggestEl.innerHTML = ""; }

    setDropdownValue(ddSort,     "name_asc", "Sort by Name (A–Z)");
    setDropdownValue(ddCourse,   "",         "All Courses");
    setDropdownValue(ddBarangay, "",         "All Barangays");
    setDropdownValue(ddDistrict, "",         "All Districts");
    setDropdownValue(ddYear,     "",         "All Year Levels");
    setDropdownValue(ddBatch,    "",         "All Batches");
    setDropdownValue(ddDay,      "",         "Any Day");
    setDropdownValue(ddBlock,    "",         "Any Time");
    setDropdownValue(ddStatus,   "",         "All Status");
    setDropdownValue(ddPerPage, String(perPage), `${perPage} rows`);

    fetchPage({ page: 1 });
  });

  /* ---------------- pagination ---------------- */
  arrowUp?.addEventListener("click", (e) => {
    e.preventDefault();
    if (currentPage > 1) {
      applied.page = currentPage - 1;
      fetchPage({ page: applied.page });
    }
  });

  arrowDown?.addEventListener("click", (e) => {
    e.preventDefault();
    if (currentPage < lastPage) {
      applied.page = currentPage + 1;
      fetchPage({ page: applied.page });
    }
  });

  /* ---------------- add volunteer modal preview ---------------- */
  function resetPreview() {
    if (photoPreview) photoPreview.src = DEFAULT_AVATAR;
    if (photoInput)   photoInput.value = "";
  }

  photoInput?.addEventListener("change", () => {
    const file = photoInput.files?.[0];
    if (!file) { resetPreview(); return; }
    const url = URL.createObjectURL(file);
    if (photoPreview) photoPreview.src = url;
  });

  addVolunteerModal?.addEventListener("hidden.bs.modal", () => {
    resetPreview();
  });

  /* ---------------- init ---------------- */
  setDropdownValue(ddSort,     "name_asc", "Sort by Name (A–Z)");
  setDropdownValue(ddCourse,   "",         "All Courses");
  setDropdownValue(ddBarangay, "",         "All Barangays");
  setDropdownValue(ddDistrict, "",         "All Districts");
  setDropdownValue(ddYear,     "",         "All Year Levels");
  setDropdownValue(ddBatch,    "",         "All Batches");
  setDropdownValue(ddDay,      "",         "Any Day");
  setDropdownValue(ddBlock,    "",         "Any Time");
  setDropdownValue(ddStatus,   "",         "All Status");
  setDropdownValue(ddPerPage, String(perPage), `${perPage} rows`);

  // navbar overlap safety
  (() => {
    const nav = document.querySelector('nav.navbar, .navbar, #navbar');
    const h = nav ? nav.getBoundingClientRect().height : 0;
    if (h && Number.isFinite(h)) {
      document.documentElement.style.setProperty('--vlNavOffset', `${Math.ceil(h) + 18}px`);
    }
  })();

  // ✅ PATCH: expose portal dropdown helpers globally (for Add Volunteer modal)
  window.VLPortalDropdown = {
    wireDropdown,
    setDropdownValue,
    closePortalMenu,
  };

  setPanel(false);
  setClearVisible();
  fetchPage({ page: 1 });
})();

/* =========================================================
   Add Volunteer: schedule bridge ONLY (SAFE)
   ✅ No recursion / no freeze
   ✅ Works with:
      - scheduleField.value changes
      - scheduleField.setAttribute('value', ...)
      - schedule modal dispatching vl:schedule-updated
========================================================= */
(() => {
  const scheduleField = document.getElementById("vlScheduleField");
  if (!scheduleField) return;

  let inNotify = false;
  let lastValue = (scheduleField.value || "").trim();

  function emit(value) {
    // Single source of truth signal for the Add Volunteer modal
    try {
      window.dispatchEvent(new CustomEvent("vl:schedule-updated", {
        detail: { value }
      }));
    } catch {
      try { window.dispatchEvent(new Event("vl:schedule-updated")); } catch {}
    }
  }

  function notify(reason = "") {
    if (inNotify) return;
    inNotify = true;

    const value = (scheduleField.value || "").trim();
    if (value !== lastValue) {
      lastValue = value;
      emit(value);
    }

    inNotify = false;
  }

  // 1) If schedule modal sets attribute: setAttribute('value', ...)
  const obs = new MutationObserver(() => notify("attr"));
  obs.observe(scheduleField, { attributes: true, attributeFilter: ["value"] });

  // 2) If schedule modal sets property: scheduleField.value = "..."
  //    we listen to input/change BUT we DO NOT re-dispatch input/change (no recursion)
  scheduleField.addEventListener("input",  () => notify("input"));
  scheduleField.addEventListener("change", () => notify("change"));

  // 3) If schedule modal fires a global event directly
  window.addEventListener("vl:schedule-updated", () => notify("global"));

  // Initial sync once
  notify("init");
})();
