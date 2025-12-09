(() => {
  const root = document.getElementById("emRoot");
  if (!root) return;

  /* ============================================================
     Fix bfcache: always refresh when navigating back
     ============================================================ */
  window.addEventListener("pageshow", (e) => {
    if (e.persisted) window.location.reload();
  });

  /* ============================================================
     CONFIG
     ============================================================ */
  const PAGE_SIZE = 9;

  const timeMeta = {
    // Morning
    "7:30-8:20":   { label: "7:30–8:20 AM",   group: "AM", start: 450,  end: 500  },
    "8:00-9:20":   { label: "8:00–9:20 AM",   group: "AM", start: 480,  end: 560  },
    "8:00-10:50":  { label: "8:00–10:50 AM",  group: "AM", start: 480,  end: 650  },
    "8:30-9:50":   { label: "8:30–9:50 AM",   group: "AM", start: 510,  end: 590  },
    "8:30-11:30":  { label: "8:30–11:30 AM",  group: "AM", start: 510,  end: 690  },
    "9:30-10:50":  { label: "9:30–10:50 AM",  group: "AM", start: 570,  end: 650  },
    "11:00-12:20": { label: "11:00–12:20 PM", group: "AM", start: 660,  end: 740  },

    // Afternoon / Evening
    "12:30-1:50":  { label: "12:30–1:50 PM",  group: "PM", start: 750,  end: 830  },
    "12:30-2:50":  { label: "12:30–2:50 PM",  group: "PM", start: 750,  end: 890  },
    "2:00-3:20":   { label: "2:00–3:20 PM",   group: "PM", start: 840,  end: 920  },
    "2:00-4:50":   { label: "2:00–4:50 PM",   group: "PM", start: 840,  end: 1010 },
    "3:30-4:50":   { label: "3:30–4:50 PM",   group: "PM", start: 930,  end: 1010 },
    "5:00-6:20":   { label: "5:00–6:20 PM",   group: "PM", start: 1020, end: 1100 },
    "6:30-7:20":   { label: "6:30–7:20 PM",   group: "PM", start: 1110, end: 1160 },
    "6:30-8:50":   { label: "6:30–8:50 PM",   group: "PM", start: 1110, end: 1250 },
    "7:30-8:50":   { label: "7:30–8:50 PM",   group: "PM", start: 1170, end: 1250 }
  };

  /* ============================================================
     STATE
     ============================================================ */
  const state = {
    tab: (root.getAttribute("data-default-tab") || "planned").trim(),
    pageByTab: { planned: 1, ongoing: 1, completed: 1, cancelled: 1 },

    q: "",

    sort: "date_asc",
    district: "",
    month: "",
    day: "",
    timegroup: "",
    timeslot: "",
    barangay: "",

    barangayQuery: ""
  };

  /* ============================================================
     DOM
     ============================================================ */
  const tabs   = [...root.querySelectorAll(".em-tab[data-tab]")];
  const panes  = [...root.querySelectorAll(".em-pane")];

  const searchInput = document.getElementById("emSearch");
  const searchClear = document.getElementById("emSearchClear");
  const mainSuggest = document.getElementById("emMainSuggest");

  const panel  = document.getElementById("emPanel");
  const toggle = document.getElementById("emFilterToggle");
  const applyBtn = document.getElementById("emApply");
  const resetBtn = document.getElementById("emReset");

  const ddSort      = root.querySelector('.em-dd[data-dd="sort"]');
  const ddDistrict  = root.querySelector('.em-dd[data-dd="district"]');
  const ddMonth     = root.querySelector('.em-dd[data-dd="month"]');
  const ddDay       = root.querySelector('.em-dd[data-dd="day"]');
  const ddTimeGroup = root.querySelector('.em-dd[data-dd="timegroup"]');
  const ddTimeSlot  = root.querySelector('.em-dd[data-dd="timeslot"]');
  const timeSlotMenu = document.getElementById("emTimeSlotMenu");

  const barangayInput   = document.getElementById("emBarangaySearch");
  const barangayClear   = document.getElementById("emBarangayClear");
  const barangaySuggest = document.getElementById("emBarangaySuggest");
  const barangaySelectedBtn  = document.getElementById("emBarangaySelected");
  const barangaySelectedText = document.getElementById("emBarangaySelectedText");

  const copyBtn = document.getElementById("emCopyBtn");
  const printBtn = document.getElementById("emPrintBtn");

  const bulkBtn         = document.getElementById("emBulkDeleteBtn");
  const selectedCountEl = document.getElementById("emSelectedCount");
  const selectAllBtn    = document.getElementById("emSelectAllBtn");

  const confirmCountEl   = document.getElementById("emConfirmCount");
  const confirmListEl    = document.getElementById("emConfirmList");
  const hiddenInputsWrap = document.getElementById("emBulkHiddenInputs");

  const toastEl = document.getElementById("emToast");
  const flags   = document.getElementById("emServerFlags");

  // Activity Log button
  const logBtn = document.getElementById("emLogBtn");

  /* ===== Modals ===== */
  const bsModal = (id) => {
    const el = document.getElementById(id);
    return el ? new bootstrap.Modal(el) : null;
  };

  const mConfirm  = bsModal("emModalConfirm");
  const mSuccess  = bsModal("emModalSuccess");
  const mNotice   = bsModal("emModalNotice");
  const mNoCopy   = bsModal("emModalNoCopy");
  const mNoPrint  = bsModal("emModalNoPrint");
  const mNoDelete = bsModal("emModalNoDelete");
  const mLogs     = bsModal("emActivityModal");

  const noticeTitle = document.getElementById("emNoticeTitle");
  const noticeBody  = document.getElementById("emNoticeBody");
  const showNotice = (title, body) => {
    if (noticeTitle) noticeTitle.textContent = title || "Notice";
    if (noticeBody)  noticeBody.textContent  = body || "";
    mNotice?.show();
  };

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

  function debounce(fn, ms) {
    let t = 0;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  }

  let toastTimer = null;
  const toast = (msg) => {
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => toastEl.classList.remove("show"), 1400);
  };

  const activePane  = () => panes.find(p => p.dataset.pane === state.tab);
  const activeGrid  = () => activePane()?.querySelector(".em-grid");
  const activeEmpty = () => activePane()?.querySelector("[data-empty]");
  const activePager = () => activePane()?.querySelector(".em-pagination");
  const cardsInActive = () => [...(activePane()?.querySelectorAll(".em-event-card") || [])];
  const allCards = () => [...root.querySelectorAll(".em-event-card")];

  function parseJSONAttr(el, attr, fallback) {
    try {
      const raw = el.getAttribute(attr);
      if (!raw) return fallback;
      return JSON.parse(raw);
    } catch {
      return fallback;
    }
  }

  const barangaysByDistrict = (() => {
    const fromAttr = parseJSONAttr(root, "data-barangays-by-district", null);
    return fromAttr || window.EM_BARANGAYS_BY_DISTRICT || {};
  })();

  const allBarangays = (() => {
    if (Array.isArray(window.EM_ALL_BARANGAYS) && window.EM_ALL_BARANGAYS.length) {
      return window.EM_ALL_BARANGAYS;
    }
    const set = new Set();
    Object.values(barangaysByDistrict || {}).forEach(arr =>
      (arr || []).forEach(b => set.add(b))
    );
    return [...set].filter(Boolean).sort((a, b) =>
      String(a).localeCompare(String(b))
    );
  })();

  function getDistrictForBarangay(barangay) {
    const b = (barangay || "").trim().toLowerCase();
    if (!b) return "";
    for (const [dist, list] of Object.entries(barangaysByDistrict || {})) {
      for (const item of (list || [])) {
        if ((item || "").trim().toLowerCase() === b) return String(dist);
      }
    }
    return "";
  }

  /* ============================================================
     PANEL + DROPDOWNS
     ============================================================ */
  function closeAllDropdowns() {
    root.querySelectorAll(".em-dd.is-open").forEach(dd => dd.classList.remove("is-open"));
  }

  function setPanel(open) {
    if (!panel || !toggle) return;
    panel.hidden = !open;
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
    if (!open) closeAllDropdowns();
  }

  toggle?.addEventListener("click", (e) => {
    e.stopPropagation();
    setPanel(panel.hidden);
  });

  document.addEventListener("mousedown", (e) => {
    const anyDD = root.querySelector(".em-dd.is-open");
    if (anyDD && !anyDD.contains(e.target)) closeAllDropdowns();

    if (mainSuggest && !mainSuggest.hidden) {
      const wrap = mainSuggest.closest(".em-search--suggest");
      if (wrap && !wrap.contains(e.target)) mainSuggest.hidden = true;
    }

    if (barangaySuggest && !barangaySuggest.hidden) {
      const wrap = barangaySuggest.closest(".em-suggest");
      if (wrap && !wrap.contains(e.target)) barangaySuggest.hidden = true;
    }
  });

  function setDropdownValue(dd, value, label) {
    if (!dd) return;
    dd.dataset.value = value ?? "";
    const text = dd.querySelector("[data-dd-text]");
    if (text && label != null) text.textContent = label;
  }

  function wireDropdown(dd, onPick) {
    if (!dd) return;
    const btn  = dd.querySelector(".em-ddBtn");
    const menu = dd.querySelector("[data-dd-menu]");

    btn?.addEventListener("click", (e) => {
      e.stopPropagation();
      const open = dd.classList.contains("is-open");
      closeAllDropdowns();
      dd.classList.toggle("is-open", !open);
    });

    menu?.addEventListener("click", (e) => {
      const item = e.target.closest(".em-ddItem");
      if (!item) return;
      const value = item.dataset.value ?? "";
      onPick(value, item.textContent.trim());
      dd.classList.remove("is-open");
    });
  }

  wireDropdown(ddSort, (value, label) => {
    state.sort = value || "date_asc";
    setDropdownValue(ddSort, state.sort, label || "Sort by Date (Soonest)");
  });

  wireDropdown(ddDistrict, (value, label) => {
    state.district = value || "";
    setDropdownValue(ddDistrict, state.district, label || "All Districts");

    if (state.barangay) {
      const bd = getDistrictForBarangay(state.barangay);
      if (state.district && bd && bd !== state.district) {
        clearBarangayFilter(true);
      }
    }

    if (document.activeElement === barangayInput) {
      renderBarangaySuggest();
    }
  });

  wireDropdown(ddMonth, (value, label) => {
    state.month = value || "";
    setDropdownValue(ddMonth, state.month, label || "All Months");
  });

  wireDropdown(ddDay, (value, label) => {
    state.day = value || "";
    setDropdownValue(ddDay, state.day, label || "All Days");
  });

  wireDropdown(ddTimeGroup, (value, label) => {
    state.timegroup = value || "";
    setDropdownValue(ddTimeGroup, state.timegroup, label || "All Times");

    if (state.timeslot && state.timegroup) {
      const meta = timeMeta[state.timeslot];
      if (!meta || meta.group !== state.timegroup) {
        state.timeslot = "";
        setDropdownValue(ddTimeSlot, "", "All Time Slots");
      }
    }
    renderTimeSlotMenu();
  });

  wireDropdown(ddTimeSlot, (value, label) => {
    state.timeslot = value || "";
    setDropdownValue(ddTimeSlot, state.timeslot, label || "All Time Slots");

    if (state.timeslot) {
      const meta = timeMeta[state.timeslot];
      if (meta?.group) {
        state.timegroup = meta.group;
        setDropdownValue(
          ddTimeGroup,
          state.timegroup,
          meta.group === "AM" ? "Morning (AM)" : "Afternoon/Evening (PM)"
        );
      }
    }
  });

  function renderTimeSlotMenu() {
    if (!timeSlotMenu) return;
    const keys = Object.keys(timeMeta);

    const filtered = keys
      .filter(k => !state.timegroup || timeMeta[k].group === state.timegroup)
      .sort((a, b) => timeMeta[a].start - timeMeta[b].start);

    timeSlotMenu.innerHTML =
      `<button class="em-ddItem" type="button" data-value="">All Time Slots</button>` +
      filtered.map(k =>
        `<button class="em-ddItem" type="button" data-value="${escapeHtml(k)}">
           ${escapeHtml(timeMeta[k].label)}
         </button>`
      ).join("");
  }

  /* ============================================================
     BARANGAY AUTOSUGGEST
     ============================================================ */
  function setBarangayFilter(name) {
    state.barangay = (name || "").trim();
    if (!state.barangay) return clearBarangayFilter(true);

    if (barangaySelectedBtn && barangaySelectedText) {
      barangaySelectedText.textContent = state.barangay;
      barangaySelectedBtn.hidden = false;
    }

    const dist = getDistrictForBarangay(state.barangay);
    if (dist) {
      state.district = dist;
      setDropdownValue(ddDistrict, dist, `District ${dist}`);
    }

    if (barangayInput) barangayInput.value = state.barangay;
  }

  function clearBarangayFilter(silent = false) {
    state.barangay = "";
    state.barangayQuery = "";
    if (barangayInput) barangayInput.value = "";
    if (barangaySelectedBtn) barangaySelectedBtn.hidden = true;

    if (barangaySuggest) {
      barangaySuggest.innerHTML = "";
      barangaySuggest.hidden = true;
    }

    if (!silent && document.activeElement === barangayInput) {
      renderBarangaySuggest();
    }
  }

  function renderBarangaySuggest() {
    if (!barangaySuggest) return;

    const q = (state.barangayQuery || "").trim().toLowerCase();
    const dist = state.district;

    let candidates = allBarangays.slice();

    if (dist && barangaysByDistrict && barangaysByDistrict[dist]) {
      candidates = (barangaysByDistrict[dist] || [])
        .slice()
        .sort((a, b) => String(a).localeCompare(String(b)));
    }

    if (q) {
      candidates = candidates.filter(b =>
        String(b).toLowerCase().includes(q)
      );
    }

    const limited = candidates.slice(0, 60);
    const html = limited.map(b => {
      const d = getDistrictForBarangay(b);
      const meta = d ? `District ${d}` : "";
      return `
        <div class="em-suggest-item" data-b="${escapeHtml(b)}">
          <div>${escapeHtml(b)}</div>
          <div class="em-suggest-meta">${escapeHtml(meta)}</div>
        </div>
      `;
    }).join("");

    barangaySuggest.innerHTML =
      html || `<div class="p-3 text-muted" style="font-weight:800;">No matches.</div>`;
    barangaySuggest.hidden = false;
  }

  barangayInput?.addEventListener("focus", () => {
    state.barangayQuery = (barangayInput.value || "").trim();
    renderBarangaySuggest();
  });

  barangayInput?.addEventListener("input", debounce(() => {
    state.barangayQuery = (barangayInput.value || "").trim();
    renderBarangaySuggest();
  }, 90));

  barangayClear?.addEventListener("click", () => {
    clearBarangayFilter(true);
    toast("Barangay cleared.");
  });

  barangaySuggest?.addEventListener("click", (e) => {
    const item = e.target.closest(".em-suggest-item");
    if (!item) return;
    const b = item.getAttribute("data-b") || "";
    setBarangayFilter(b);
    if (barangaySuggest) barangaySuggest.hidden = true;
    toast(`Barangay: ${b}`);
  });

  barangaySelectedBtn?.addEventListener("click", () => {
    clearBarangayFilter(true);
    toast("Barangay cleared.");
  });

  /* ============================================================
     MAIN SEARCH AUTOSUGGEST → EVENT HITS
     ============================================================ */
  function buildEventSuggestions(query) {
    const q = (query || "").trim().toLowerCase();
    if (!q) return [];

    const cards = allCards();
    const hits = [];

    for (const c of cards) {
      const title    = (c.getAttribute("data-title") || "").trim();
      const venue    = (c.getAttribute("data-venue") || "").trim();
      const barangay = (c.getAttribute("data-barangay") || "").trim();
      const distRaw  = (c.getAttribute("data-district") || "").trim();
      const districtLabel = distRaw ? `District ${distRaw}` : "";
      const date     = (c.getAttribute("data-date") || "").trim();
      const day      = (c.getAttribute("data-day") || "").trim();
      const time     = (c.getAttribute("data-time") || "").trim();
      const code     = (c.getAttribute("data-code") || "").trim();
      const status   = (c.getAttribute("data-status") || "").trim();

      const hay = `${title} ${venue} ${barangay} ${districtLabel} ${date} ${day} ${time} ${code} ${status}`.toLowerCase();
      if (!hay.includes(q)) continue;

      let score = 0;
      if (title.toLowerCase().includes(q))         score += 5;
      if (code.toLowerCase().includes(q))          score += 4;
      if (venue.toLowerCase().includes(q))         score += 3;
      if (barangay.toLowerCase().includes(q))      score += 2;
      if (districtLabel.toLowerCase().includes(q)) score += 1;
      if (status.toLowerCase().includes(q))        score += 1;
      if (date.toLowerCase().includes(q) ||
          day.toLowerCase().includes(q)  ||
          time.toLowerCase().includes(q)) score += 1;

      hits.push({
        card: c,
        score,
        title: title || "Untitled Event",
        venue,
        barangay,
        district: distRaw,
        districtLabel,
        date,
        day,
        time,
        code,
        status
      });
    }

    hits.sort((a, b) => b.score - a.score);
    return hits.slice(0, 8);
  }

  function renderMainSuggest() {
    if (!mainSuggest || !searchInput) return;
    const q = searchInput.value || "";
    const hits = buildEventSuggestions(q);

    if (!q.trim() || hits.length === 0) {
      mainSuggest.hidden = true;
      mainSuggest.innerHTML = "";
      return;
    }

    mainSuggest.innerHTML = hits.map(h => {
      const metaPieces = [
        h.date ? (h.day ? `${h.date} (${h.day})` : h.date) : "",
        h.time,
        h.venue,
        h.barangay,
        h.districtLabel,
        h.code ? `Code: ${h.code}` : "",
        h.status
      ].filter(Boolean);

      return `
        <button type="button"
                class="em-suggest-item em-suggest-item--event"
                data-url="${escapeHtml(h.card.getAttribute("data-detail-url") || "#")}">
          <div class="em-suggest-main">${escapeHtml(h.title)}</div>
          <div class="em-suggest-meta">${escapeHtml(metaPieces.join(" • "))}</div>
        </button>
      `;
    }).join("");

    mainSuggest.hidden = false;
  }

  mainSuggest?.addEventListener("click", (e) => {
    const item = e.target.closest(".em-suggest-item");
    if (!item) return;
    const url = item.getAttribute("data-url");
    if (url) window.location.href = url;
  });

  function goToBestMatchEvent(query) {
    const hits = buildEventSuggestions(query);
    if (hits.length) {
      const url = hits[0].card.getAttribute("data-detail-url");
      if (url) {
        window.location.href = url;
        return;
      }
    }
    state.q = (query || "").trim();
    state.pageByTab[state.tab] = 1;
    applyNow();
  }

  /* ============================================================
     FILTERING + SORTING + PAGINATION
     ============================================================ */
  function cardMatchesTimeSlot(card, slotKey) {
    if (!slotKey) return true;
    const meta = timeMeta[slotKey];
    if (!meta) return true;

    const s = Number(card.getAttribute("data-start-min") || -1);
    const e = Number(card.getAttribute("data-end-min") || -1);
    if (s < 0 || e < 0) return false;

    return s < meta.end && meta.start < e;
  }

  function cardMatchesTimeGroup(card, group) {
    if (!group) return true;
    const s = Number(card.getAttribute("data-start-min") || -1);
    if (s < 0) return false;
    const inferred = s < 720 ? "AM" : "PM";
    return inferred === group;
  }

  function applyNow() {
    const q = (state.q || "").trim().toLowerCase();
    const cards = cardsInActive();

    for (const c of cards) {
      const hay        = (c.getAttribute("data-search") || "").toLowerCase();
      const cDist      = (c.getAttribute("data-district") || "").trim();
      const cBarangay  = (c.getAttribute("data-barangay") || "").trim();
      const cMonth     = (c.getAttribute("data-month") || "").trim();
      const cDay       = (c.getAttribute("data-day") || "").trim();

      const okSearch   = !q || hay.includes(q);
      const okDist     = !state.district || cDist === state.district;
      const okBarangay = !state.barangay || cBarangay.toLowerCase() === state.barangay.toLowerCase();
      const okMonth    = !state.month || cMonth === String(state.month);
      const okDay      = !state.day || cDay === state.day;

      const okTimeGroup = cardMatchesTimeGroup(c, state.timegroup);
      const okTimeSlot  = cardMatchesTimeSlot(c, state.timeslot);

      c.style.display =
        (okSearch && okDist && okBarangay && okMonth && okDay && okTimeGroup && okTimeSlot)
        ? ""
        : "none";
    }

    const visible = cards.filter(c => c.style.display !== "none");
    visible.sort((a, b) => {
      const ta = (a.getAttribute("data-title") || "").toLowerCase();
      const tb = (b.getAttribute("data-title") || "").toLowerCase();
      const da = Number(a.getAttribute("data-sort-ts") || 0);
      const db = Number(b.getAttribute("data-sort-ts") || 0);

      switch (state.sort) {
        case "title_asc":  return ta.localeCompare(tb);
        case "title_desc": return tb.localeCompare(ta);
        case "date_desc":  return db - da;
        case "date_asc":
        default:           return da - db;
      }
    });

    const page       = state.pageByTab[state.tab] || 1;
    const totalPages = Math.max(1, Math.ceil(visible.length / PAGE_SIZE));
    const safePage   = Math.min(totalPages, Math.max(1, page));
    state.pageByTab[state.tab] = safePage;

    const start = (safePage - 1) * PAGE_SIZE;
    const end   = start + PAGE_SIZE;

    visible.forEach((c, i) => {
      c.style.display = (i >= start && i < end) ? "" : "none";
    });

    const grid = activeGrid();
    if (grid) visible.forEach(c => grid.appendChild(c));

    const empty = activeEmpty();
    if (empty) empty.hidden = visible.length !== 0;

    const pager = activePager();
    if (pager) {
      const prevBtn = pager.querySelector("[data-page-prev]");
      const nextBtn = pager.querySelector("[data-page-next]");
      const info    = pager.querySelector("[data-pageinfo]");

      if (info) info.textContent = `${safePage} / ${totalPages}`;
      if (prevBtn) prevBtn.closest(".page-item")?.classList.toggle("disabled", safePage <= 1);
      if (nextBtn) nextBtn.closest(".page-item")?.classList.toggle("disabled", safePage >= totalPages);
      pager.hidden = visible.length === 0;
    }

    syncCount();
  }

  /* ============================================================
     TABS
     ============================================================ */
  function setTab(key) {
    state.tab = key;

    tabs.forEach(t => {
      const on = t.dataset.tab === key;
      t.classList.toggle("is-active", on);
      t.setAttribute("aria-selected", on ? "true" : "false");
    });

    panes.forEach(p => (p.hidden = (p.dataset.pane !== key)));

    const url = new URL(window.location.href);
    url.searchParams.set("tab", key);
    window.history.replaceState({}, "", url.toString());

    mainSuggest && (mainSuggest.hidden = true);
    barangaySuggest && (barangaySuggest.hidden = true);

    state.pageByTab[key] = 1;
    applyNow();
  }
  tabs.forEach(t => t.addEventListener("click", () => setTab(t.dataset.tab)));

  /* ============================================================
     SEARCH
     ============================================================ */
  const commitSearch = () => {
    state.q = (searchInput?.value || "").trim();
    state.pageByTab[state.tab] = 1;
    applyNow();
  };

  searchInput?.addEventListener("focus", () => renderMainSuggest());

  searchInput?.addEventListener("input", debounce(() => {
    renderMainSuggest();
    commitSearch();
  }, 120));

  searchInput?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      goToBestMatchEvent(searchInput.value || "");
    } else if (e.key === "Escape") {
      if (mainSuggest) mainSuggest.hidden = true;
      searchInput.blur();
    }
  });

  searchClear?.addEventListener("click", () => {
    if (searchInput) searchInput.value = "";
    state.q = "";
    if (mainSuggest) {
      mainSuggest.hidden = true;
      mainSuggest.innerHTML = "";
    }
    state.pageByTab[state.tab] = 1;
    applyNow();
  });

  /* ============================================================
     APPLY / RESET (overlay stays open)
     ============================================================ */
  applyBtn?.addEventListener("click", () => {
    commitSearch();
    toast("Filters applied.");
  });

  resetBtn?.addEventListener("click", () => {
    state.q = "";
    state.sort = "date_asc";
    state.district = "";
    state.month = "";
    state.day = "";
    state.timegroup = "";
    state.timeslot = "";
    clearBarangayFilter(true);

    if (searchInput) searchInput.value = "";
    if (mainSuggest) mainSuggest.hidden = true;

    setDropdownValue(ddSort, "date_asc", "Sort by Date (Soonest)");
    setDropdownValue(ddDistrict, "", "All Districts");
    setDropdownValue(ddMonth, "", "All Months");
    setDropdownValue(ddDay, "", "All Days");
    setDropdownValue(ddTimeGroup, "", "All Times");
    setDropdownValue(ddTimeSlot, "", "All Time Slots");

    renderTimeSlotMenu();

    state.pageByTab[state.tab] = 1;
    applyNow();
    toast("Reset done.");
  });

  /* ============================================================
     PAGINATION BUTTONS
     ============================================================ */
  root.addEventListener("click", (e) => {
    const prev = e.target.closest("[data-page-prev]");
    const next = e.target.closest("[data-page-next]");
    if (!prev && !next) return;

    const cards = cardsInActive();
    const visibleAll = cards.filter(c => {
      const q         = (state.q || "").trim().toLowerCase();
      const hay       = (c.getAttribute("data-search") || "").toLowerCase();
      const cDist     = (c.getAttribute("data-district") || "").trim();
      const cBarangay = (c.getAttribute("data-barangay") || "").trim();
      const cMonth    = (c.getAttribute("data-month") || "").trim();
      const cDay      = (c.getAttribute("data-day") || "").trim();

      const okSearch   = !q || hay.includes(q);
      const okDist     = !state.district || cDist === state.district;
      const okBarangay = !state.barangay || cBarangay.toLowerCase() === state.barangay.toLowerCase();
      const okMonth    = !state.month || cMonth === String(state.month);
      const okDay      = !state.day || cDay === state.day;
      const okTimeGroup = cardMatchesTimeGroup(c, state.timegroup);
      const okTimeSlot  = cardMatchesTimeSlot(c, state.timeslot);

      return okSearch && okDist && okBarangay && okMonth && okDay && okTimeGroup && okTimeSlot;
    });

    const totalPages = Math.max(1, Math.ceil(visibleAll.length / PAGE_SIZE));
    const current    = state.pageByTab[state.tab] || 1;

    if (prev) state.pageByTab[state.tab] = Math.max(1, current - 1);
    if (next) state.pageByTab[state.tab] = Math.min(totalPages, current + 1);

    applyNow();
  });

  /* ============================================================
     BULK SELECTION + SELECT ALL
     ============================================================ */
  function selectedChecksAllPanes() {
    return Array.from(document.querySelectorAll(".em-check:checked"));
  }

  function syncCount() {
    const n = selectedChecksAllPanes().length;
    if (selectedCountEl) selectedCountEl.textContent = String(n);
    if (bulkBtn) {
      bulkBtn.classList.toggle("em-bulk-empty", n === 0);
    }
  }

  document.addEventListener("change", (e) => {
    if (e.target && e.target.classList.contains("em-check")) syncCount();
  });

  selectAllBtn?.addEventListener("click", () => {
    const pane = activePane();
    if (!pane) return;

    const cards = Array.from(pane.querySelectorAll(".em-event-card"))
      .filter(c => c.style.display !== "none");
    const checks = cards.map(c => c.querySelector(".em-check")).filter(Boolean);

    if (!checks.length) {
      mNoDelete?.show();
      return;
    }

    const anyUnchecked = checks.some(ch => !ch.checked);
    checks.forEach(ch => { ch.checked = anyUnchecked; });
    syncCount();
    toast(anyUnchecked ? "Selected visible events." : "Unselected visible events.");
  });

  /* ============================================================
     COPY + PRINT EXPORT
     ============================================================ */
  function activeVisibleCards() {
    const pane = activePane();
    if (!pane) return [];
    return Array.from(pane.querySelectorAll(".em-event-card"))
      .filter(c => c.style.display !== "none");
  }

  function selectedCardsInActiveTab() {
    const pane = activePane();
    if (!pane) return [];
    return Array.from(pane.querySelectorAll(".em-check:checked"))
      .map(ch => ch.closest(".em-event-card"))
      .filter(Boolean);
  }

  function exportCards(cards) {
    return cards.map((c, idx) => ({
      n:     idx + 1,
      title: c.getAttribute("data-title")  || "",
      date:  c.getAttribute("data-date")   || "",
      day:   c.getAttribute("data-day")    || "",
      time:  c.getAttribute("data-time")   || "",
      venue: c.getAttribute("data-venue")  || "",
      code:  c.getAttribute("data-code")   || "",
      status:c.getAttribute("data-status") || ""
    }));
  }

  copyBtn?.addEventListener("click", async () => {
    const selected = selectedCardsInActiveTab();
    const cards    = selected.length ? selected : activeVisibleCards();

    if (!cards.length) {
      mNoCopy?.show();
      return;
    }

    const rows = exportCards(cards);
    const text = rows.map(r =>
      `${r.n}. ${r.title}\n` +
      `   Date: ${r.date}${r.day ? ` (${r.day})` : ""}\n` +
      `   Time: ${r.time}\n` +
      `   Venue: ${r.venue}\n` +
      `   Code: ${r.code}\n` +
      `   Status: ${r.status}\n`
    ).join("\n").trim();

    try {
      if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(text);
        toast(selected.length ? "Copied selected!" : "Copied visible!");
      } else {
        window.prompt("Copy:", text);
      }
    } catch {
      window.prompt("Copy:", text);
    }
  });

  printBtn?.addEventListener("click", () => {
    const selected = selectedCardsInActiveTab();
    const cards    = selected.length ? selected : activeVisibleCards();

    if (!cards.length) {
      mNoPrint?.show();
      return;
    }

    const rows = exportCards(cards);
    const tabName =
      tabs.find(t => t.classList.contains("is-active"))?.textContent?.trim() ||
      "Events";

    const tableRows = rows.map(r => `
      <tr>
        <td>${r.n}</td>
        <td><strong>${escapeHtml(r.title)}</strong></td>
        <td>${escapeHtml(r.date)}${r.day ? ` <span style="color:#6b7280;font-weight:700;">(${escapeHtml(r.day)})</span>` : ""}</td>
        <td>${escapeHtml(r.time)}</td>
        <td>${escapeHtml(r.venue)}</td>
        <td>${escapeHtml(r.code)}</td>
        <td>${escapeHtml(r.status)}</td>
      </tr>
    `).join("");

    const html = `<!doctype html>
<html>
<head>
  <meta charset="utf-8"/>
  <title>${escapeHtml(tabName)}</title>
  <style>
    body{ font-family: Arial, sans-serif; margin:24px; color:#111827; }
    h1{ margin:0 0 6px; color:#7a232b; font-size:22px; }
    .sub{ color:#6b7280; font-weight:700; margin-bottom:14px; }
    table{ width:100%; border-collapse:collapse; font-size:14px; }
    th,td{ border:1px solid rgba(17,24,39,.14); padding:10px; text-align:left; vertical-align:top; }
    th{ background: rgba(17,24,39,.04); }
    @media print {
      tr { page-break-inside: avoid; }
      tr + tr { page-break-before: always; }
    }
  </style>
</head>
<body>
  <h1>${escapeHtml(tabName)} ${selected.length ? "(Selected)" : ""}</h1>
  <div class="sub">Generated: ${escapeHtml(new Date().toLocaleString())}</div>
  <table>
    <thead>
      <tr>
        <th>#</th><th>Event</th><th>Date</th><th>Time</th><th>Venue</th><th>Code</th><th>Status</th>
      </tr>
    </thead>
    <tbody>${tableRows}</tbody>
  </table>
  <script>
    window.onload = function(){
      window.focus();
      window.print();
      window.onafterprint = function(){ window.close(); };
    };
  <\/script>
</body>
</html>`;

    const w = window.open("", "_blank", "noopener,noreferrer,width=1100,height=720");
    if (!w) {
      showNotice("Popup blocked", "Allow popups in your browser to print this list.");
      return;
    }

    w.document.open();
    w.document.write(html);
    w.document.close();
  });

  /* ============================================================
     BULK DELETE MODAL
     ============================================================ */
  bulkBtn?.addEventListener("click", () => {
    const selected = selectedChecksAllPanes();

    if (!selected.length) {
      mNoDelete?.show();
      return;
    }

    const titles = selected.map(
      (ch) => ch.getAttribute("data-title") || "Untitled Event"
    );

    if (confirmCountEl) {
      confirmCountEl.textContent = String(selected.length);
    }

    if (confirmListEl) {
      confirmListEl.innerHTML = titles
        .slice(0, 60)
        .map(
          (t) =>
            `<div class="em-confirm-item">
               <i class="fa-regular fa-trash-can"></i>
               <div>${escapeHtml(t)}</div>
             </div>`
        )
        .join("");
      if (titles.length > 60) {
        confirmListEl.innerHTML += `
          <div class="text-muted small mt-2">
            And ${titles.length - 60} more…
          </div>`;
      }
    }

    if (hiddenInputsWrap) {
      hiddenInputsWrap.innerHTML = selected
        .map(
          (ch) =>
            `<input type="hidden" name="event_ids[]" value="${escapeHtml(
              ch.value
            )}">`
        )
        .join("");
    }

    mConfirm?.show();
  });

  /* ============================================================
     EVENT ACTIVITY LOG (modal) – JS filters (Apply-only)
     ============================================================ */
  const logFilterForm   = document.getElementById("emLogFilterForm");
  const logStartInput   = logFilterForm?.querySelector('input[name="log_start"]');
  const logEndInput     = logFilterForm?.querySelector('input[name="log_end"]');
  const logSearchInput  = logFilterForm?.querySelector('input[name="log_search"]');
  const logActionSelect = logFilterForm?.querySelector('select[name="log_action"]');
  const logResetBtn     = document.getElementById("emLogResetBtn");
  const logTable        = document.querySelector("#emActivityModal .em-log-table");
  const logRows         = logTable ? [...logTable.querySelectorAll("tbody tr.em-log-row")] : [];

  function applyLogFilters() {
    if (!logTable || !logRows.length) return;

    const start  = (logStartInput?.value || "").trim();        // yyyy-mm-dd
    const end    = (logEndInput?.value || "").trim();          // yyyy-mm-dd
    const search = (logSearchInput?.value || "").trim().toLowerCase();
    const action = (logActionSelect?.value || "").trim();

    logRows.forEach(row => {
      const rowAction = (row.dataset.action || "").trim();
      const rowDate   = (row.dataset.date || "").trim();       // yyyy-mm-dd
      const hay       = (row.dataset.search || "").toLowerCase();

      const okAction = !action || rowAction === action;
      const okSearch = !search || hay.includes(search);

      let okDate = true;
      if (start && rowDate) okDate = okDate && (rowDate >= start);
      if (end && rowDate)   okDate = okDate && (rowDate <= end);

      const show = okAction && okSearch && okDate;
      row.style.display = show ? "" : "none";
    });
  }

  if (logFilterForm) {
    logFilterForm.addEventListener("submit", (e) => {
      e.preventDefault();
      applyLogFilters();
    });

    logResetBtn?.addEventListener("click", () => {
      if (logStartInput)   logStartInput.value   = "";
      if (logEndInput)     logEndInput.value     = "";
      if (logSearchInput)  logSearchInput.value  = "";
      if (logActionSelect) logActionSelect.value = "";
      applyLogFilters();
    });

    // Respect any default values from backend on first open
    applyLogFilters();
  }

  /* ============================================================
     INIT
     ============================================================ */
  setDropdownValue(ddSort, "date_asc", "Sort by Date (Soonest)");
  setDropdownValue(ddDistrict, "", "All Districts");
  setDropdownValue(ddMonth, "", "All Months");
  setDropdownValue(ddDay, "", "All Days");
  setDropdownValue(ddTimeGroup, "", "All Times");
  setDropdownValue(ddTimeSlot, "", "All Time Slots");

  renderTimeSlotMenu();
  setPanel(false);

  const urlTab = new URL(window.location.href).searchParams.get("tab");
  setTab((urlTab || state.tab).trim());

    if (flags) {
    const hasSuccess = flags.getAttribute("data-has-success") === "1";
    const hasError   = flags.getAttribute("data-has-error") === "1";
    const errorMsg   = flags.getAttribute("data-error-msg") || "";

    if (hasSuccess) {
      // ensure modal isn't double-instantiated
      const el = document.getElementById("emModalSuccess");
      if (el && window.bootstrap) bootstrap.Modal.getOrCreateInstance(el).show();
    } else if (hasError) {
      showNotice("Error", errorMsg || "Something went wrong.");
    }
  }


  syncCount();
  applyNow();

  // When clicking "Activity Log", re-apply filters (Bootstrap data attributes handle opening)
  logBtn?.addEventListener("click", () => {
    applyLogFilters();
  });
})();
