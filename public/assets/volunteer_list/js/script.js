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
  const ddDay      = root.querySelector('.vl-dd[data-dd="day"]');
  const ddBlock    = root.querySelector('.vl-dd[data-dd="block"]');

  // Add-volunteer modal preview
  const photoInput        = document.getElementById("vlPhotoInput");
  const photoPreview      = document.getElementById("vlPhotoPreview");
  const addVolunteerModal = document.getElementById("addVolunteerModal");

  const DEFAULT_AVATAR =
    (root.getAttribute("data-default-avatar") || "").trim() ||
    "/storage/defaults/default_user.png";

  const perPage = 12;

  // Data from Blade
  const courses   = safeJson(root.getAttribute("data-courses"),   []);
  const barangays = safeJson(root.getAttribute("data-barangays"), []);
  const districts = safeJson(root.getAttribute("data-districts"), []);

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

  const applied = {
    page: 1,
    search: "",
    sort: "name_asc",
    course_id: "",
    barangay: "",
    district: "",
    year_level: "",
    day: "",
    schedule_day: ""
  };
  const pending = { ...applied };

  let currentPage = 1;
  let lastPage    = 1;
  let lastItems   = [];

  function buildUrl(params) {
    const url = new URL("/volunteers/data", window.location.origin);
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
     TIME META (for filter + schedule summarizer)
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
  const TIME_OPTIONS = Object.keys(TIME_META);

  function normalizeTimeRange(str) {
    if (!str) return "";
    str = str.replace(/[,;]+/g," ").trim();
    const p = str.split("-").map(s => s.trim());
    if (p.length !== 2) return str;
    const fix = t => /^\d{1,2}$/.test(t) ? t + ":00" : t;
    const n = `${fix(p[0])}-${fix(p[1])}`;
    return TIME_META[n] ? n : n;
  }

  function parseRange(str) {
    const key = normalizeTimeRange(str);
    if (!key.includes("-")) return null;

    if (TIME_META[key]) return { start: TIME_META[key].start, end: TIME_META[key].end };

    const [s,e] = key.split("-");
    const [sh,sm] = s.split(":").map(Number);
    const [eh,em] = e.split(":").map(Number);
    if ([sh,sm,eh,em].some(isNaN)) return null;

    return { start: sh*60+sm, end: eh*60+em };
  }

  function rangesOverlap(a, b) {
    const ra = parseRange(a);
    const rb = parseRange(b);
    return !!(ra && rb && ra.start < rb.end && rb.start < ra.end);
  }

  function summarizeSchedule(scheduleStr){
    if (!scheduleStr || !scheduleStr.trim()) return null;

    let hasAM = false;
    let hasPM = false;

    for (const [range, meta] of Object.entries(TIME_META)) {
      if (scheduleStr.includes(range)) {
        if (meta.group === "AM") hasAM = true;
        else if (meta.group === "PM") hasPM = true;
      }
    }

    if (!hasAM && !hasPM) return "Schedule set";
    if (hasAM && hasPM)   return "Classes in AM & PM";
    if (hasAM)            return "Classes in AM";
    return "Classes in PM";
  }

  function renderCard(v) {
    const avatar = resolveAvatar(v);
    const id     = encodeURIComponent(v.volunteer_id);
    const scheduleSummary = summarizeSchedule(v.class_schedule || "");

    const a = document.createElement("a");
    a.className = "student-card";
    a.href = `/volunteer-profile/${id}`;

    a.innerHTML = `
      <img class="avatar" src="${escapeHtml(avatar)}" alt="${escapeHtml(v.full_name)}" />
      <div class="meta">
        <div class="name">${escapeHtml(v.full_name)}</div>

        <div class="badge-grid">
          <div class="badge"><i class="fa-solid fa-graduation-cap"></i>${escapeHtml(v.course?.course_name || "—")}</div>
          <div class="badge"><i class="fa-solid fa-layer-group"></i>${v.year_level ? escapeHtml(v.year_level) + " Year" : "—"}</div>
          <div class="badge"><i class="fa-solid fa-location-dot"></i>${escapeHtml(v.barangay || "—")}</div>
          <div class="badge"><i class="fa-solid fa-map"></i>District ${escapeHtml(v.district || "—")}</div>
        </div>

        ${scheduleSummary ? `
          <div class="vl-scheduleChip">
            <i class="fa-solid fa-calendar-week"></i>
            <span>${escapeHtml(scheduleSummary)}</span>
          </div>
        ` : ""}
      </div>
    `;

    const img = a.querySelector("img.avatar");
    img?.addEventListener("error", () => { img.src = DEFAULT_AVATAR; }, { once: true });

    return a;
  }

  async function fetchPage(paramsOverride = {}) {
    const params = {
      page: paramsOverride.page ?? applied.page ?? 1,
      per_page: perPage,

      search: applied.search ?? "",
      sort:   applied.sort   ?? "",

      course_id:    applied.course_id    ?? "",
      barangay:     applied.barangay     ?? "",
      district:     applied.district     ?? "",
      year_level:   applied.year_level   ?? "",
      day:          applied.day          ?? "",
      schedule_day: applied.schedule_day ?? ""
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
      if (totalEl) {
        totalEl.textContent = String(total);
      }

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
     PORTAL DROPDOWN
  ========================================================= */
  let portalEl      = null;
  let portalOwner   = null;
  let portalAllItems = [];
  let portalOnPick  = null;
  let portalHasSearch  = false;
  let portalIsTimeBlock = false;
  let portalTimeMode = "all";

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
    portalOwner   = dd;
    portalAllItems = Array.isArray(items) ? items : [];
    portalOnPick  = onPick;
    portalHasSearch = !!options.search;
    portalIsTimeBlock = !!options.timeBlock;
    portalTimeMode = "all";

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

  wireDropdown(ddDay, dayItems, (value, label) => {
    pending.day = value || "";
    setDropdownValue(ddDay, pending.day, label || "Any Day");
  });

  wireDropdown(ddBlock, blockItems, (value, label) => {
    pending.schedule_day = value || "";
    setDropdownValue(ddBlock, pending.schedule_day, label || "Any Time");
  }, { search: true, timeBlock: true });

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

  /* ---------------- autosuggest ---------------- */
  function buildSuggest(query) {
    if (!suggestEl) return;
    const q = (query || "").trim().toLowerCase();
    if (q.length < 2) {
      suggestEl.hidden = true;
      suggestEl.innerHTML = "";
      return;
    }

    const hits = [];
    for (const v of lastItems) {
      const name   = (v.full_name || "").toLowerCase();
      const course = (v.course?.course_name || "").toLowerCase();
      const brgy   = (v.barangay || "").toLowerCase();
      const dist   = String(v.district || "").toLowerCase();

      let score = 0;
      if (name.includes(q))   score += 3;
      if (course.includes(q)) score += 2;
      if (brgy.includes(q))   score += 2;
      if (dist.includes(q))   score += 1;

      if (score > 0) hits.push({ v, score });
    }

    hits.sort((a, b) => b.score - a.score);
    const top = hits.slice(0, 6);

    if (!top.length) {
      suggestEl.hidden = true;
      suggestEl.innerHTML = "";
      return;
    }

    suggestEl.innerHTML = top.map(({ v }) => {
      const meta = [
        v.course?.course_name ? v.course.course_name : null,
        v.barangay ? v.barangay : null,
        v.district ? `District ${v.district}` : null
      ].filter(Boolean).join(" • ");

      return `
        <div class="vl-suggestItem" data-pick="${escapeHtml(v.full_name)}">
          <div class="vl-suggestMain">${escapeHtml(v.full_name)}</div>
          <div class="vl-suggestMeta">${escapeHtml(meta)}</div>
        </div>
      `;
    }).join("");

    suggestEl.hidden = false;
  }

  searchInput?.addEventListener("input", () => {
    pending.search = searchInput.value || "";
    buildSuggest(pending.search);
  });

  searchInput?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      pending.search = searchInput.value || "";
      if (suggestEl) { suggestEl.hidden = true; suggestEl.innerHTML = ""; }
    }
    if (e.key === "Escape" && suggestEl) {
      suggestEl.hidden = true;
      suggestEl.innerHTML = "";
    }
  });

  suggestEl?.addEventListener("mousedown", (e) => e.preventDefault());
  suggestEl?.addEventListener("click", (e) => {
    const item = e.target.closest(".vl-suggestItem");
    if (!item) return;
    const pick = item.getAttribute("data-pick") || "";
    if (searchInput) searchInput.value = pick;
    pending.search = pick;
    suggestEl.hidden = true;
    suggestEl.innerHTML = "";
  });

  searchClear?.addEventListener("click", () => {
    if (searchInput) searchInput.value = "";
    pending.search = "";
    if (suggestEl) { suggestEl.hidden = true; suggestEl.innerHTML = ""; }
  });

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
    pending.day          = "";
    pending.schedule_day = "";

    Object.assign(applied, pending);

    if (searchInput) searchInput.value = "";
    if (suggestEl) { suggestEl.hidden = true; suggestEl.innerHTML = ""; }

    setDropdownValue(ddSort,     "name_asc", "Sort by Name (A–Z)");
    setDropdownValue(ddCourse,   "",         "All Courses");
    setDropdownValue(ddBarangay, "",         "All Barangays");
    setDropdownValue(ddDistrict, "",         "All Districts");
    setDropdownValue(ddYear,     "",         "All Year Levels");
    setDropdownValue(ddDay,      "",         "Any Day");
    setDropdownValue(ddBlock,    "",         "Any Time");

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

  /* ---------------- Add Volunteer: search + auto district ---------------- */
  const addCourseSearch   = document.getElementById("vlCourseSearch");
  const addCourseSelect   = document.getElementById("vlCourseSelect");
  const addBarangaySearch = document.getElementById("vlBarangaySearch");
  const addBarangaySelect = document.getElementById("vlBarangaySelect");
  const addDistrictSelect = document.getElementById("vlDistrictSelect");

  // Course local filter
  if (addCourseSearch && addCourseSelect) {
    const opts = Array.from(addCourseSelect.options);
    addCourseSearch.addEventListener("input", function(){
      const term = this.value.toLowerCase().trim();
      opts.forEach(opt => {
        if (!opt.value) { opt.hidden = false; return; }
        const txt = opt.text.toLowerCase();
        opt.hidden = term && !txt.includes(term);
      });
      if (addCourseSelect.selectedOptions.length &&
          addCourseSelect.selectedOptions[0].hidden) {
        addCourseSelect.value = "";
      }
    });
  }

  // Barangay local filter
  if (addBarangaySearch && addBarangaySelect) {
    const opts = Array.from(addBarangaySelect.options);
    addBarangaySearch.addEventListener("input", function(){
      const term = this.value.toLowerCase().trim();
      opts.forEach(opt => {
        if (!opt.value) { opt.hidden = false; return; }
        const txt = opt.text.toLowerCase();
        opt.hidden = term && !txt.includes(term);
      });
      if (addBarangaySelect.selectedOptions.length &&
          addBarangaySelect.selectedOptions[0].hidden) {
        addBarangaySelect.value = "";
      }
    });
  }

  // Auto district when barangay chosen
  if (addBarangaySelect && addDistrictSelect) {
    addBarangaySelect.addEventListener("change", function(){
      const selected = this.selectedOptions[0];
      if (!selected) return;
      const districtId = selected.dataset.district;
      if (!districtId) return;
      const targetOpt = Array.from(addDistrictSelect.options)
        .find(o => o.value === String(districtId));
      if (targetOpt) {
        addDistrictSelect.value = String(districtId);
      }
    });
  }

  /* ---------------- init ---------------- */
  setDropdownValue(ddSort,     "name_asc", "Sort by Name (A–Z)");
  setDropdownValue(ddCourse,   "",         "All Courses");
  setDropdownValue(ddBarangay, "",         "All Barangays");
  setDropdownValue(ddDistrict, "",         "All Districts");
  setDropdownValue(ddYear,     "",         "All Year Levels");
  setDropdownValue(ddDay,      "",         "Any Day");
  setDropdownValue(ddBlock,    "",         "Any Time");

  setPanel(false);
  fetchPage({ page: 1 });
})();

/* =========================================================
   Add Volunteer: schedule builder (vlScheduleModal)
========================================================= */
(() => {
  const vlScheduleField   = document.getElementById("vlScheduleField");
  const vlScheduleSummary = document.getElementById("vlScheduleSummary");
  const vlScheduleTrigger = document.getElementById("vlScheduleTrigger");
  const vlScheduleModalEl = document.getElementById("vlScheduleModal");
  const vlScheduleBody    = document.getElementById("vlScheduleBody");
  const vlScheduleSave    = document.getElementById("vlScheduleSave");
  const vlScheduleClear   = document.getElementById("vlScheduleClear");

  if (!(vlScheduleField && vlScheduleModalEl && vlScheduleBody)) return;

  const SCH_DAYS = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];
  const SCH_SLOTS = {
    "07:30-08:20": { label:"7:30–8:20 AM",  group:"AM" },
    "08:00-09:20": { label:"8:00–9:20 AM",  group:"AM" },
    "08:30-09:50": { label:"8:30–9:50 AM",  group:"AM" },
    "09:30-10:50": { label:"9:30–10:50 AM", group:"AM" },
    "11:00-12:20": { label:"11:00–12:20 AM",group:"AM" },

    "12:30-13:50": { label:"12:30–1:50 PM", group:"PM" },
    "14:00-15:20": { label:"2:00–3:20 PM",  group:"PM" },
    "15:30-16:50": { label:"3:30–4:50 PM",  group:"PM" },
    "17:00-18:20": { label:"5:00–6:20 PM",  group:"PM" },
    "18:30-20:50": { label:"6:30–8:50 PM",  group:"PM" }
  };
  const MAX_ROWS = 3;

  const emptySchedule = () => {
    const obj = {};
    SCH_DAYS.forEach(d => { obj[d] = []; });
    return obj;
  };

  function parseScheduleString(str) {
    const data = emptySchedule();
    if (!str) return data;

    SCH_DAYS.forEach(day => {
      const re = new RegExp(`${day}:\\s*(.*?)(?=(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|$))`,"i");
      const m  = str.match(re);
      if (!m) return;
      let raw = (m[1] || "").trim();
      if (!raw || /^no class/i.test(raw)) return;
      const parts = raw.split(/\s+/);
      data[day] = parts.filter(v => SCH_SLOTS[v]);
    });

    return data;
  }

  function scheduleToString(data) {
    const parts = [];
    SCH_DAYS.forEach(day => {
      const blocks = (data[day] || []).filter(Boolean);
      if (!blocks.length) parts.push(`${day}: No Class`);
      else parts.push(`${day}: ${blocks.join(" ")}`);
    });
    return parts.join(" ");
  }

  function buildSummary(data) {
    const daysWithClass = [];
    let hasAM = false, hasPM = false;

    SCH_DAYS.forEach(day => {
      const blocks = (data[day] || []).filter(Boolean);
      if (blocks.length) daysWithClass.push(day);
      blocks.forEach(b => {
        const meta = SCH_SLOTS[b];
        if (!meta) return;
        if (meta.group === "AM") hasAM = true;
        if (meta.group === "PM") hasPM = true;
      });
    });

    if (!daysWithClass.length) {
      return 'No schedule set. Volunteers will be treated as available on any day & time.';
    }

    const dayLabel = daysWithClass.join(", ");
    let band = "";
    if (hasAM && hasPM)      band = " (AM & PM)";
    else if (hasAM)          band = " (AM only)";
    else if (hasPM)          band = " (PM only)";

    return `Has classes on ${dayLabel}${band}.`;
  }

  let scheduleData = parseScheduleString(vlScheduleField.value || "");

  function renderScheduleTable() {
    vlScheduleBody.innerHTML = "";
    for (let row = 0; row < MAX_ROWS; row++) {
      const tr = document.createElement("tr");

      const idx = document.createElement("td");
      idx.className = "vl-schIndex";
      idx.textContent = String(row + 1);
      tr.appendChild(idx);

      SCH_DAYS.forEach(day => {
        const td = document.createElement("td");
        const select = document.createElement("select");
        select.className = "form-select form-select-sm vl-schSelect";
        select.dataset.day = day;
        select.dataset.row = String(row);

        const optEmpty = document.createElement("option");
        optEmpty.value = "";
        optEmpty.textContent = "No Class";
        select.appendChild(optEmpty);

        const groups = {
          AM: document.createElement("optgroup"),
          PM: document.createElement("optgroup")
        };
        groups.AM.label = "Morning";
        groups.PM.label = "Afternoon / Evening";

        Object.entries(SCH_SLOTS).forEach(([value, meta]) => {
          const opt = document.createElement("option");
          opt.value = value;
          opt.textContent = meta.label;
          groups[meta.group].appendChild(opt);
        });

        select.appendChild(groups.AM);
        select.appendChild(groups.PM);

        const existing = scheduleData[day] || [];
        select.value = existing[row] || "";

        td.appendChild(select);
        tr.appendChild(td);
      });

      vlScheduleBody.appendChild(tr);
    }
  }

  let vlScheduleModal;

  function openScheduleModal() {
    scheduleData = parseScheduleString(vlScheduleField.value || "");
    renderScheduleTable();
    if (!vlScheduleModal) {
      vlScheduleModal = new bootstrap.Modal(vlScheduleModalEl);
    }
    vlScheduleModal.show();
  }

  vlScheduleTrigger?.addEventListener("click", () => {
    openScheduleModal();
  });

  vlScheduleClear?.addEventListener("click", () => {
    scheduleData = emptySchedule();
    renderScheduleTable();
  });

  vlScheduleSave?.addEventListener("click", () => {
    const data = emptySchedule();

    vlScheduleBody.querySelectorAll(".vl-schSelect").forEach(sel => {
      const day  = sel.dataset.day;
      const row  = parseInt(sel.dataset.row || "0", 10);
      const val  = sel.value || "";
      if (val && SCH_SLOTS[val]) {
        if (!data[day]) data[day] = [];
        data[day][row] = val;
      }
    });

    SCH_DAYS.forEach(day => {
      data[day] = (data[day] || []).filter(Boolean);
    });

    const schedStr = scheduleToString(data);
    vlScheduleField.value = schedStr;
    if (vlScheduleSummary) {
      vlScheduleSummary.textContent = buildSummary(data);
    }

    scheduleData = data;
    if (vlScheduleModal) vlScheduleModal.hide();
  });

  // Initial summary text
  if (vlScheduleSummary) {
    vlScheduleSummary.textContent = buildSummary(scheduleData);
  }
})();
