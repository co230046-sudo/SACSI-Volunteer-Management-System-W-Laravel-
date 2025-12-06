// public/assets/js/script.js
(() => {
  const root = document.getElementById("hpRoot");
  if (!root) return;

  /* ============================================================
     MATCH EVENT MANAGER BEHAVIOR
     - "Pending" vs "Applied" state (filters only apply on Apply click)
     - Week filter becomes Day filter (Mon/Tue/...)
     - Time filter becomes Time Group + Time Slot with search (same logic)
     - ADD: Homepage autosuggest like Event Manager (does NOT auto-apply)
     - Keep homepage classes (.hp-*) and HTML ids you already have
     ============================================================ */

  /* ---------- config ---------- */
  const daysFull = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"];

  const timeMeta = {
    // Morning
    "7:30-8:20":  { label:"7:30–8:20 AM",  group:"AM", start:450,  end:500 },
    "8:00-9:20":  { label:"8:00–9:20 AM",  group:"AM", start:480,  end:560 },
    "8:00-10:50": { label:"8:00–10:50 AM", group:"AM", start:480,  end:650 },
    "8:30-9:50":  { label:"8:30–9:50 AM",  group:"AM", start:510,  end:590 },
    "8:30-11:30": { label:"8:30–11:30 AM", group:"AM", start:510,  end:690 },
    "9:30-10:50": { label:"9:30–10:50 AM", group:"AM", start:570,  end:650 },
    "11:00-12:20":{ label:"11:00–12:20 PM",group:"AM", start:660,  end:740 },

    // Afternoon / Evening
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

  /* ============================================================
     STATE
     ============================================================ */
  // applied = actually used for filtering
  const applied = {
    tab: (root.getAttribute("data-default-tab") || "ongoing").trim(),
    q: "",
    sort: "date_asc",
    district: "",
    barangay: "",
    month: "",
    day: "",
    timegroup: "",
    timeslot: ""
  };

  // pending = what user selects in dropdowns before pressing Apply
  const pending = { ...applied, timeQuery: "" };

  /* ============================================================
     DOM
     ============================================================ */
  const tabs = [...root.querySelectorAll(".hp-tab")];
  const panes = [...root.querySelectorAll(".hp-pane")];

  const searchInput = document.getElementById("hpSearch");
  const searchBtn = document.getElementById("hpSearchBtn");
  const searchClear = document.getElementById("hpSearchClear");

  // --- autosuggest (optional, created if missing) ---
  const searchWrap = searchInput?.closest(".hp-search") || searchInput?.parentElement || null;
  let mainSuggest = document.getElementById("hpMainSuggest"); // if you already have it in HTML
  if (!mainSuggest && searchWrap) {
    mainSuggest = document.createElement("div");
    mainSuggest.id = "hpMainSuggest";
    mainSuggest.className = "hp-suggest-box";
    mainSuggest.hidden = true;
    searchWrap.appendChild(mainSuggest);
  }

  const panel = document.getElementById("hpPanel");
  const toggle = document.getElementById("hpFilterToggle");
  const applyBtn = document.getElementById("hpApply");
  const resetBtn = document.getElementById("hpReset");

  const ddSort = root.querySelector('.hp-dd[data-dd="sort"]');
  const ddDistrict = root.querySelector('.hp-dd[data-dd="district"]');
  const ddBarangay = root.querySelector('.hp-dd[data-dd="barangay"]');
  const ddMonth = root.querySelector('.hp-dd[data-dd="month"]');

  // - data-dd="week"  => Day dropdown (Mon..Sun)
  // - data-dd="time"  => TimeSlot dropdown + search + optional group selector row injected
  const ddDay = root.querySelector('.hp-dd[data-dd="week"]');
  const dayMenu = document.getElementById("hpWeekMenu");

  const ddTimeSlot = root.querySelector('.hp-dd[data-dd="time"]');
  const timeSlotMenu = ddTimeSlot?.querySelector("[data-dd-menu]") || null;

  const barangayMenu = document.getElementById("hpBarangayMenu");

  const barangaysByDistrict = (() => {
    try { return JSON.parse(root.getAttribute("data-barangays") || "{}"); }
    catch { return {}; }
  })();

  const titleCase = (s) => (s || "")
    .toLowerCase()
    .replace(/\b\w/g, m => m.toUpperCase());

  const activePane = () => panes.find(p => p.dataset.pane === applied.tab);
  const cardsInActive = () => [...(activePane()?.querySelectorAll(".hp-event") || [])];

  /* ============================================================
     HELPERS
     ============================================================ */
  const escapeHtml = (str) =>
    (str ?? "").toString()
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");

  const debounce = (fn, ms = 150) => {
    let t = 0;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  };

  function closeAllDropdowns() {
    root.querySelectorAll(".hp-dd.is-open").forEach(dd => dd.classList.remove("is-open"));
  }

  function setDropdownValue(dd, value, label) {
    if (!dd) return;
    dd.dataset.value = value ?? "";
    const text = dd.querySelector("[data-dd-text]");
    if (text && label != null) text.textContent = label;
  }

  function setPanel(open) {
    if (!panel || !toggle) return;
    panel.hidden = !open;
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
    toggle.classList.toggle("is-open", open);
    if (!open) closeAllDropdowns();
  }

  toggle?.addEventListener("click", (e) => {
    e.stopPropagation();
    setPanel(panel.hidden);
  });

  document.addEventListener("mousedown", (e) => {
    // close dd
    const anyDD = root.querySelector(".hp-dd.is-open");
    if (anyDD && !anyDD.contains(e.target)) closeAllDropdowns();

    // close autosuggest if click outside search
    if (mainSuggest && !mainSuggest.hidden) {
      const wrap = searchWrap;
      if (wrap && !wrap.contains(e.target)) mainSuggest.hidden = true;
    }
  });

  /* ============================================================
     DISTRICT AUTOFILL FROM BARANGAY (affects PENDING)
     ============================================================ */
  const barangayToDistrict = (() => {
    const map = new Map();
    Object.keys(barangaysByDistrict || {}).forEach((distId) => {
      (barangaysByDistrict[distId] || []).forEach((x) => {
        const name = (x && typeof x === "object") ? x.barangay : x;
        const key = String(name || "").trim().toLowerCase();
        if (key) map.set(key, String(distId));
      });
    });
    return map;
  })();

  function deriveDistrictLabelFromMenu(value) {
    const menu = ddDistrict?.querySelector("[data-dd-menu]");
    if (!menu) return value ? `District ${value}` : "All Districts";
    const match = menu.querySelector(`.hp-ddItem[data-value="${CSS.escape(String(value))}"]`);
    return match ? match.textContent.trim() : (value ? `District ${value}` : "All Districts");
  }

  function autofillDistrictFromBarangay(brgyValueLower) {
    if (!brgyValueLower) return;
    const distId = barangayToDistrict.get(brgyValueLower);
    if (!distId) return;

    if (pending.district === distId) return;

    pending.district = distId;
    setDropdownValue(ddDistrict, distId, deriveDistrictLabelFromMenu(distId));
    rebuildBarangayMenu();
  }

  /* ============================================================
     BARANGAY MENU (driven by PENDING district)
     ============================================================ */
  function rebuildBarangayMenu() {
    if (!barangayMenu) return;

    const dist = pending.district;
    let list = [];

    if (dist && barangaysByDistrict[dist]) {
      list = barangaysByDistrict[dist].map(x => (x && typeof x === "object") ? x.barangay : x);
    } else {
      const all = [];
      Object.keys(barangaysByDistrict).forEach(k => {
        (barangaysByDistrict[k] || []).forEach(x => all.push((x && typeof x === "object") ? x.barangay : x));
      });
      list = all;
    }

    const uniq = [...new Set(list.filter(Boolean).map(b => String(b).trim()))]
      .sort((a, b) => a.localeCompare(b));

    barangayMenu.innerHTML = "";
    const frag = document.createDocumentFragment();

    const searchWrapEl = document.createElement("div");
    searchWrapEl.className = "hp-ddSearchWrap";
    searchWrapEl.innerHTML = `
      <div class="hp-ddSearch">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="hpBarangaySearch" placeholder="Search barangay..." autocomplete="off" />
      </div>
      <div class="hp-ddHr"></div>
    `;
    frag.appendChild(searchWrapEl);

    const allBtn = document.createElement("button");
    allBtn.type = "button";
    allBtn.className = "hp-ddItem";
    allBtn.dataset.value = "";
    allBtn.textContent = "All Barangays";
    frag.appendChild(allBtn);

    uniq.forEach((b) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "hp-ddItem hp-ddBrgyItem";
      btn.dataset.value = b.toLowerCase();
      btn.dataset.label = b.toLowerCase();
      btn.innerHTML = `<i class="fa-solid fa-location-dot me-2"></i>${titleCase(b)}`;
      frag.appendChild(btn);
    });

    barangayMenu.appendChild(frag);

    const inp = barangayMenu.querySelector("#hpBarangaySearch");
    const runFilter = debounce(() => {
      const needle = (inp?.value || "").trim().toLowerCase();
      barangayMenu.querySelectorAll(".hp-ddBrgyItem").forEach((it) => {
        const label = (it.dataset.label || "");
        it.style.display = !needle || label.includes(needle) ? "" : "none";
      });
    }, 80);

    inp?.addEventListener("input", runFilter);
    inp?.addEventListener("mousedown", (e) => e.stopPropagation());
    inp?.addEventListener("click", (e) => e.stopPropagation());

    // if pending.barangay no longer exists, clear it
    if (pending.barangay) {
      const still = uniq.some(b => b.toLowerCase() === pending.barangay);
      if (!still) {
        pending.barangay = "";
        setDropdownValue(ddBarangay, "", "All Barangays");
      }
    }
  }

  /* ============================================================
     DAY MENU (replaces your "week" menu)
     ============================================================ */
  function rebuildDayMenu() {
    if (!dayMenu) return;
    dayMenu.innerHTML = "";

    const allBtn = document.createElement("button");
    allBtn.type = "button";
    allBtn.className = "hp-ddItem";
    allBtn.dataset.value = "";
    allBtn.textContent = "All Days";
    dayMenu.appendChild(allBtn);

    daysFull.forEach((d) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "hp-ddItem";
      btn.dataset.value = d;
      btn.textContent = d;
      dayMenu.appendChild(btn);
    });

    // label should display All Days by default
    if (ddDay && (ddDay.querySelector("[data-dd-text]")?.textContent || "").trim() === "All Weeks") {
      setDropdownValue(ddDay, "", "All Days");
    }
  }

  /* ============================================================
     TIME GROUP + TIME SLOT (Event Manager logic)
     - We keep your single "Filter by Time" field but inject:
       (1) Time Group row (All / AM / PM)
       (2) Search bar
       (3) Time Slot list populated from timeMeta
  ============================================================ */
  function ensureTimeMenuScaffold() {
    if (!timeSlotMenu) return;

    // if already scaffolded, do nothing
    if (timeSlotMenu.querySelector("#hpTimeSearch")) return;

    // Clear whatever static items were in blade
    timeSlotMenu.innerHTML = "";

    const groupWrap = document.createElement("div");
    groupWrap.className = "hp-ddSearchWrap";
    groupWrap.innerHTML = `
      <div style="display:flex; gap:8px; padding:8px;">
        <button type="button" class="hp-ddItem hp-timeGroup" data-group="" style="flex:1;">All</button>
        <button type="button" class="hp-ddItem hp-timeGroup" data-group="AM" style="flex:1;">AM</button>
        <button type="button" class="hp-ddItem hp-timeGroup" data-group="PM" style="flex:1;">PM</button>
      </div>
      <div class="hp-ddHr"></div>
      <div class="hp-ddSearch">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="hpTimeSearch" placeholder="Search time slot..." autocomplete="off" />
      </div>
      <div class="hp-ddHr"></div>
    `;
    timeSlotMenu.appendChild(groupWrap);

    const allBtn = document.createElement("button");
    allBtn.type = "button";
    allBtn.className = "hp-ddItem hp-ddTimeItem";
    allBtn.dataset.value = "";
    allBtn.dataset.label = "all time slots";
    allBtn.textContent = "All Time Slots";
    timeSlotMenu.appendChild(allBtn);

    const setGroupActiveUI = () => {
      timeSlotMenu.querySelectorAll(".hp-timeGroup").forEach(btn => {
        const g = btn.getAttribute("data-group") || "";
        btn.classList.toggle("is-active", g === (pending.timegroup || ""));
      });
    };

    timeSlotMenu.addEventListener("click", (e) => {
      const gbtn = e.target.closest(".hp-timeGroup");
      if (!gbtn) return;
      e.stopPropagation();

      pending.timegroup = gbtn.getAttribute("data-group") || "";

      if (pending.timeslot && pending.timegroup) {
        const meta = timeMeta[pending.timeslot];
        if (!meta || meta.group !== pending.timegroup) {
          pending.timeslot = "";
          setDropdownValue(ddTimeSlot, "", "All Time Slots");
        }
      }

      setGroupActiveUI();
      renderTimeSlotMenu();
    });

    const inp = timeSlotMenu.querySelector("#hpTimeSearch");
    inp?.addEventListener("mousedown", (e) => e.stopPropagation());
    inp?.addEventListener("click", (e) => e.stopPropagation());
    inp?.addEventListener("input", debounce(() => {
      pending.timeQuery = (inp.value || "").trim().toLowerCase();
      renderTimeSlotMenu();
    }, 90));

    setGroupActiveUI();
  }

  function renderTimeSlotMenu() {
    if (!timeSlotMenu) return;

    ensureTimeMenuScaffold();

    // remove old slot buttons only (keep scaffold + All Time Slots button)
    [...timeSlotMenu.querySelectorAll(".hp-ddTimeSlotBtn")].forEach(n => n.remove());

    let filtered = Object.keys(timeMeta).filter(k => {
      if (!pending.timegroup) return true;
      return timeMeta[k].group === pending.timegroup;
    });

    const q = (pending.timeQuery || "").trim().toLowerCase();
    if (q) {
      filtered = filtered.filter(k => {
        const hay = `${k} ${timeMeta[k].label} ${timeMeta[k].group}`.toLowerCase();
        return hay.includes(q);
      });
    }

    filtered.sort((a, b) => timeMeta[a].start - timeMeta[b].start);

    const frag = document.createDocumentFragment();
    filtered.forEach(k => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "hp-ddItem hp-ddTimeItem hp-ddTimeSlotBtn";
      btn.dataset.value = k;
      btn.dataset.label = `${k} ${timeMeta[k].label}`.toLowerCase();
      btn.textContent = timeMeta[k].label;
      frag.appendChild(btn);
    });

    timeSlotMenu.appendChild(frag);
  }

  /* ============================================================
     AUTOSUGGEST (like Event Manager) for the main search
     - Suggests from the ACTIVE tab cards using data attributes
     - Does NOT apply filters until Apply is clicked
     ============================================================ */
  function buildMainSuggestions(query) {
    const q = (query || "").trim().toLowerCase();
    if (!q) return [];

    const cards = cardsInActive();
    const set = new Map(); // key -> {type, value}

    const push = (type, value) => {
      const v = (value || "").trim();
      if (!v) return;
      const key = `${type}::${v}`.toLowerCase();
      if (!set.has(key)) set.set(key, { type, value: v });
    };

    for (const c of cards) {
      const title = (c.getAttribute("data-title") || "").trim();
      const barangay = (c.getAttribute("data-barangay") || "").trim();
      const district = (c.getAttribute("data-district") || "").trim();
      const dateTxt = (c.getAttribute("data-date-text") || c.getAttribute("data-date-label") || c.getAttribute("data-date-str") || "").trim();
      const day = (c.getAttribute("data-day") || "").trim();
      const timeTxt = (c.getAttribute("data-time-text") || c.getAttribute("data-time-label") || c.getAttribute("data-time") || "").trim();
      const code = (c.getAttribute("data-code") || "").trim();
      const venue = (c.getAttribute("data-venue") || "").trim();

      const hay = `${title} ${barangay} ${district} ${dateTxt} ${day} ${timeTxt} ${code} ${venue}`.toLowerCase();
      if (!hay.includes(q)) continue;

      if (title && title.toLowerCase().includes(q)) push("Title", title);
      if (barangay && barangay.toLowerCase().includes(q)) push("Barangay", barangay);
      if (day && day.toLowerCase().includes(q)) push("Day", day);
      if (dateTxt && dateTxt.toLowerCase().includes(q)) push("Date", dateTxt);
      if (timeTxt && timeTxt.toLowerCase().includes(q)) push("Time", timeTxt);
      if (code && code.toLowerCase().includes(q)) push("Code", code);
      if (venue && venue.toLowerCase().includes(q)) push("Venue", venue);
      if (district && (`district ${district}`).includes(q)) push("District", `District ${district}`);
    }

    return [...set.values()].slice(0, 10);
  }

  function renderMainSuggest() {
    if (!mainSuggest) return;
    const q = (searchInput?.value || "").trim();
    const items = buildMainSuggestions(q);

    if (!q || items.length === 0) {
      mainSuggest.hidden = true;
      mainSuggest.innerHTML = "";
      return;
    }

    mainSuggest.innerHTML = items.map(it => `
      <div class="hp-suggest-item" data-v="${escapeHtml(it.value)}">
        <div>${escapeHtml(it.value)}</div>
        <div class="hp-suggest-meta">${escapeHtml(it.type)}</div>
      </div>
    `).join("");

    mainSuggest.hidden = false;
  }

  mainSuggest?.addEventListener("click", (e) => {
    const item = e.target.closest(".hp-suggest-item");
    if (!item) return;
    const v = item.getAttribute("data-v") || "";
    if (searchInput) searchInput.value = v;
    pending.q = v;
    mainSuggest.hidden = true;
    // IMPORTANT: do NOT apply automatically; Apply button applies.
  });

  /* ============================================================
     FILTERING (Event Manager logic)
     ============================================================ */
  function cardMatchesTimeSlot(card, slotKey) {
    if (!slotKey) return true;
    const meta = timeMeta[slotKey];
    if (!meta) return true;

    const s = Number(card.getAttribute("data-start-min") || -1);
    const eAttr = card.getAttribute("data-end-min");
    const e = eAttr == null ? -1 : Number(eAttr);

    if (s < 0) return false;

    if (e >= 0) {
      return s < meta.end && meta.start < e;
    }
    return s >= meta.start && s < meta.end;
  }

  function cardMatchesTimeGroup(card, group) {
    if (!group) return true;
    const s = Number(card.getAttribute("data-start-min") || -1);
    if (s < 0) return false;
    const inferred = s < 720 ? "AM" : "PM";
    return inferred === group;
  }

  function applyNow() {
    const q = (applied.q || "").trim().toLowerCase();
    const cards = cardsInActive();

    for (const c of cards) {
      const hay = (c.getAttribute("data-hay") || "").toLowerCase();
      const cDist = (c.getAttribute("data-district") || "").trim();
      const cBarangay = (c.getAttribute("data-barangay") || "").trim();
      const cMonth = (c.getAttribute("data-month") || "").trim();
      const cDay = (c.getAttribute("data-day") || "").trim();

      const okSearch = !q || hay.includes(q);
      const okDist = !applied.district || cDist === applied.district;
      const okBarangay = !applied.barangay || cBarangay === applied.barangay;
      const okMonth = !applied.month || cMonth === String(applied.month);
      const okDay = !applied.day || cDay === applied.day;

      const okTimeGroup = cardMatchesTimeGroup(c, applied.timegroup);
      const okTimeSlot = cardMatchesTimeSlot(c, applied.timeslot);

      c.classList.toggle(
        "is-hidden",
        !(okSearch && okDist && okBarangay && okMonth && okDay && okTimeGroup && okTimeSlot)
      );
    }

    const visible = cards.filter(c => !c.classList.contains("is-hidden"));
    visible.sort((a, b) => {
      const ta = (a.getAttribute("data-title") || "").toLowerCase();
      const tb = (b.getAttribute("data-title") || "").toLowerCase();
      const da = Number(a.getAttribute("data-date") || 0);
      const db = Number(b.getAttribute("data-date") || 0);

      switch (applied.sort) {
        case "title_asc": return ta.localeCompare(tb);
        case "title_desc": return tb.localeCompare(ta);
        case "date_desc": return db - da;
        case "date_asc":
        default: return da - db;
      }
    });

    const list = activePane()?.querySelector(".hp-list");
    if (list) visible.forEach(c => list.appendChild(c));
  }

  /* ============================================================
     TABS (apply immediately on tab change)
     ============================================================ */
  function setTab(key) {
    applied.tab = key;
    pending.tab = key;

    tabs.forEach(t => {
      const on = t.dataset.tab === key;
      t.classList.toggle("is-active", on);
      t.setAttribute("aria-selected", on ? "true" : "false");
    });

    panes.forEach(p => (p.hidden = (p.dataset.pane !== key)));

    // close suggest on tab change
    if (mainSuggest) mainSuggest.hidden = true;

    applyNow();
  }
  tabs.forEach(t => t.addEventListener("click", () => setTab(t.dataset.tab)));

  /* ============================================================
     SEARCH (pending only; autosuggest while typing; no auto-apply)
     ============================================================ */
  const commitPendingSearch = () => {
    pending.q = (searchInput?.value || "").trim();
  };

  searchInput?.addEventListener("focus", () => renderMainSuggest());
  searchInput?.addEventListener("input", debounce(() => {
    commitPendingSearch();
    renderMainSuggest();
  }, 120));

  searchInput?.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      if (mainSuggest) mainSuggest.hidden = true;
    }
    if (e.key === "Enter") {
      commitPendingSearch();
      if (mainSuggest) mainSuggest.hidden = true;
      // do NOT apply instantly
    }
  });

  searchBtn?.addEventListener("click", () => {
    commitPendingSearch();
    if (mainSuggest) mainSuggest.hidden = true;
  });

  searchClear?.addEventListener("click", () => {
    if (searchInput) searchInput.value = "";
    pending.q = "";
    if (mainSuggest) mainSuggest.hidden = true;
    // do not apply until Apply click
  });

  /* ============================================================
     DROPDOWNS WIRING (updates PENDING only)
     ============================================================ */
  function wireDropdown(dd, onPick) {
    if (!dd) return;
    const btn = dd.querySelector(".hp-ddBtn");
    const menu = dd.querySelector("[data-dd-menu]");

    btn?.addEventListener("click", (e) => {
      e.stopPropagation();
      const open = dd.classList.contains("is-open");
      closeAllDropdowns();
      dd.classList.toggle("is-open", !open);

      if (!open && dd.dataset.dd === "barangay") {
        setTimeout(() => menu?.querySelector("#hpBarangaySearch")?.focus(), 0);
      }
      if (!open && dd.dataset.dd === "week") {
        rebuildDayMenu();
      }
      if (!open && dd.dataset.dd === "time") {
        renderTimeSlotMenu();
        setTimeout(() => menu?.querySelector("#hpTimeSearch")?.focus(), 0);
      }
    });

    menu?.addEventListener("mousedown", (e) => e.stopPropagation());

    menu?.addEventListener("click", (e) => {
      const item = e.target.closest(".hp-ddItem");
      if (!item) return;

      // prevent group buttons from closing dropdown
      if (item.classList.contains("hp-timeGroup")) return;

      const value = item.dataset.value ?? "";
      onPick(value, item.textContent.trim());
      dd.classList.remove("is-open");
    });
  }

  // Sort
  wireDropdown(ddSort, (value, label) => {
    pending.sort = value || "date_asc";
    setDropdownValue(ddSort, pending.sort, label || "Sort by Date (Soonest)");
  });

  // District
  wireDropdown(ddDistrict, (value, label) => {
    pending.district = value || "";
    setDropdownValue(ddDistrict, pending.district, label || "All Districts");

    if (pending.barangay) {
      const bd = barangayToDistrict.get(pending.barangay);
      if (pending.district && bd && bd !== pending.district) {
        pending.barangay = "";
        setDropdownValue(ddBarangay, "", "All Barangays");
      }
    }
    rebuildBarangayMenu();
  });

  // Barangay
  wireDropdown(ddBarangay, (value, label) => {
    pending.barangay = value || "";
    setDropdownValue(ddBarangay, pending.barangay, label || "All Barangays");
    if (value) autofillDistrictFromBarangay(String(value).toLowerCase());
  });

  // Month
  wireDropdown(ddMonth, (value, label) => {
    pending.month = value || "";
    setDropdownValue(ddMonth, pending.month, label || "All Months");
  });

  // Day (was week)
  wireDropdown(ddDay, (value, label) => {
    pending.day = value || "";
    setDropdownValue(ddDay, pending.day, label || "All Days");
  });

  // Time Slot dropdown
  wireDropdown(ddTimeSlot, (value, label) => {
    pending.timeslot = value || "";
    setDropdownValue(ddTimeSlot, pending.timeslot, label || "All Time Slots");

    if (pending.timeslot) {
      const meta = timeMeta[pending.timeslot];
      if (meta?.group) {
        pending.timegroup = meta.group;
        renderTimeSlotMenu();
      }
    }
  });

  /* ============================================================
     APPLY / RESET
     ============================================================ */
  applyBtn?.addEventListener("click", () => {
    // copy pending -> applied
    Object.assign(applied, pending);

    // normalize UI labels for time dropdown
    if (!applied.timeslot) setDropdownValue(ddTimeSlot, "", "All Time Slots");

    applyNow();
    // keep panel open
  });

  resetBtn?.addEventListener("click", () => {
    pending.q = "";
    pending.sort = "date_asc";
    pending.district = "";
    pending.barangay = "";
    pending.month = "";
    pending.day = "";
    pending.timegroup = "";
    pending.timeslot = "";
    pending.timeQuery = "";

    Object.assign(applied, pending);

    if (searchInput) searchInput.value = "";
    if (mainSuggest) mainSuggest.hidden = true;

    setDropdownValue(ddSort, "date_asc", "Sort by Date (Soonest)");
    setDropdownValue(ddDistrict, "", "All Districts");
    setDropdownValue(ddBarangay, "", "All Barangays");
    setDropdownValue(ddMonth, "", "All Months");
    setDropdownValue(ddDay, "", "All Days");
    setDropdownValue(ddTimeSlot, "", "All Time Slots");

    rebuildBarangayMenu();
    rebuildDayMenu();
    renderTimeSlotMenu();

    applyNow();
    // keep panel open
  });

  /* ============================================================
     INIT
     ============================================================ */
  setDropdownValue(ddSort, "date_asc", "Sort by Date (Soonest)");
  setDropdownValue(ddDistrict, "", "All Districts");
  setDropdownValue(ddBarangay, "", "All Barangays");
  setDropdownValue(ddMonth, "", "All Months");
  setDropdownValue(ddDay, "", "All Days");
  setDropdownValue(ddTimeSlot, "", "All Time Slots");

  rebuildBarangayMenu();
  rebuildDayMenu();
  renderTimeSlotMenu();

  setPanel(false);
  setTab(applied.tab);
  applyNow();
})();
