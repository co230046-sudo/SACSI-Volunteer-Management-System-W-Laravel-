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

  /* ============================================================
     Bootstrap helpers
     - Safe guard: if Bootstrap fails to load, don’t hard crash
     ============================================================ */
  const hasBootstrap = () => typeof window.bootstrap !== "undefined";

  /* ===== Modals ===== */
  const bsModal = (id) => {
    if (!hasBootstrap()) return null;
    const el = document.getElementById(id);
    return el ? bootstrap.Modal.getOrCreateInstance(el) : null;
  };

  const mConfirm  = bsModal("emModalConfirm");
  const mNotice   = bsModal("emModalNotice");
  const mNoCopy   = bsModal("emModalNoCopy");
  const mNoDelete = bsModal("emModalNoDelete");

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
     FILTERING + SORTING + PAGINATION (EVENT CARDS)
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

    // 1) filter visibility
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

    // 2) sort visible
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

    // 3) page slice
    const page       = state.pageByTab[state.tab] || 1;
    const totalPages = Math.max(1, Math.ceil(visible.length / PAGE_SIZE));
    const safePage   = Math.min(totalPages, Math.max(1, page));
    state.pageByTab[state.tab] = safePage;

    const start = (safePage - 1) * PAGE_SIZE;
    const end   = start + PAGE_SIZE;

    visible.forEach((c, i) => {
      c.style.display = (i >= start && i < end) ? "" : "none";
    });

    // 4) ensure DOM order matches sorting (append in sorted order)
    const grid = activeGrid();
    if (grid) {
      visible.forEach(c => grid.appendChild(c));
      cards.filter(c => !visible.includes(c)).forEach(c => grid.appendChild(c));
    }

    // 5) empty + pager UI
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

      pager.hidden = visible.length === 0 || totalPages <= 1;
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
     PAGINATION BUTTONS (EVENT CARDS)
     ============================================================ */
  root.addEventListener("click", (e) => {
    const prev = e.target.closest("[data-page-prev]");
    const next = e.target.closest("[data-page-next]");
    if (!prev && !next) return;

    const pane = activePane();
    if (!pane) return;

    const q = (state.q || "").trim().toLowerCase();
    const cards = cardsInActive();

    const matches = cards.filter(c => {
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

      return okSearch && okDist && okBarangay && okMonth && okDay && okTimeGroup && okTimeSlot;
    });

    const totalPages = Math.max(1, Math.ceil(matches.length / PAGE_SIZE));
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
    if (bulkBtn) bulkBtn.classList.toggle("em-bulk-empty", n === 0);
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
     COPY EXPORT
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

    if (confirmCountEl) confirmCountEl.textContent = String(selected.length);

    if (confirmListEl) {
      confirmListEl.innerHTML = titles
        .slice(0, 60)
        .map((t) =>
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
        .map((ch) => `<input type="hidden" name="event_ids[]" value="${escapeHtml(ch.value)}">`)
        .join("");
    }

    mConfirm?.show();
  });

  /* ============================================================
     ✅ SAFETY IMPROVEMENT (NO BEHAVIOR CHANGE)
     Clear stale bulk-delete modal content after close
     ============================================================ */
  const confirmModalEl = document.getElementById("emModalConfirm");
  confirmModalEl?.addEventListener("hidden.bs.modal", () => {
    if (hiddenInputsWrap) hiddenInputsWrap.innerHTML = "";
    if (confirmListEl) confirmListEl.innerHTML = "";
    if (confirmCountEl) confirmCountEl.textContent = "0";
  });

  /* ============================================================
     EVENT ACTIVITY LOG (modal) – Filters + Pagination
     ============================================================ */
  const logFilterForm  = document.getElementById("emLogFilterForm");
  const logStartInput  = logFilterForm?.querySelector('input[name="log_start"]');
  const logEndInput    = logFilterForm?.querySelector('input[name="log_end"]');
  const logSearchInput = logFilterForm?.querySelector('input[name="log_search"]');
  const logResetBtn    = document.getElementById("emLogResetBtn");

  const logTable = document.querySelector("#emActivityModal .em-log-table");
  const logBody  = logTable?.querySelector("tbody");
  const logRowsAll = logTable ? [...logTable.querySelectorAll("tbody tr.em-log-row")] : [];

  const ddLogAction = document.querySelector('#emActivityModal .em-dd[data-dd="log_action"]');
  const logActionHidden = document.getElementById("emLogActionValue");

  const logSearchClearBtn = document.getElementById("emLogSearchClear");

  const logPagerWrap = document.getElementById("emLogPager");
  const logPrevBtn   = document.getElementById("emLogPrev");
  const logNextBtn   = document.getElementById("emLogNext");
  const logPageInfo  = document.getElementById("emLogPageInfo");

  const ddLogRows    = document.querySelector('#emActivityModal .em-dd[data-dd="log_rows"]');
  const logRowsValue = document.getElementById("emLogRowsValue");

  const logState = {
    page: 1,
    rowsPerPage: 10
  };

  const clamp = (n, min, max) => Math.max(min, Math.min(max, n));

  function getLogRowsPerPage() {
    const v = Number((logRowsValue?.value || "").trim() || logState.rowsPerPage);
    return clamp(v, 5, 10);
  }

  function getLogActiveAction() {
    return (logActionHidden?.value || "").trim();
  }

  function setLogActiveAction(value, label) {
    if (logActionHidden) logActionHidden.value = value || "";
    if (ddLogAction) {
      const text = ddLogAction.querySelector("[data-dd-text]");
      if (text) text.textContent = label || "All actions";
      ddLogAction.dataset.value = value || "";
    }
  }

  function setLogRowsPerPage(n) {
    const v = clamp(Number(n || 10), 5, 10);
    logState.rowsPerPage = v;
    if (logRowsValue) logRowsValue.value = String(v);

    if (ddLogRows) {
      const text = ddLogRows.querySelector("[data-dd-text]");
      if (text) text.textContent = String(v);
      ddLogRows.dataset.value = String(v);
    }
  }

  function logRowMatchesFilters(row) {
    const start  = (logStartInput?.value || "").trim();
    const end    = (logEndInput?.value || "").trim();
    const search = (logSearchInput?.value || "").trim().toLowerCase();
    const action = getLogActiveAction();

    const rowAction = (row.dataset.action || "").trim();
    const rowDate   = (row.dataset.date || "").trim();
    const hay       = (row.dataset.search || "").toLowerCase();

    const okAction = !action || rowAction === action;
    const okSearch = !search || hay.includes(search);

    let okDate = true;
    if (start && rowDate) okDate = okDate && (rowDate >= start);
    if (end && rowDate)   okDate = okDate && (rowDate <= end);

    return okAction && okSearch && okDate;
  }

  function applyLogRender() {
    if (!logTable || !logBody) return;

    const filtered = logRowsAll.filter(logRowMatchesFilters);

    const rpp = getLogRowsPerPage();
    logState.rowsPerPage = rpp;

    const totalPages = Math.max(1, Math.ceil(filtered.length / rpp));
    logState.page = clamp(logState.page, 1, totalPages);

    const startIdx = (logState.page - 1) * rpp;
    const endIdx   = startIdx + rpp;

    logRowsAll.forEach(r => (r.style.display = "none"));
    filtered.slice(startIdx, endIdx).forEach(r => (r.style.display = ""));

    if (logPageInfo) logPageInfo.textContent = `${logState.page} / ${totalPages}`;
    if (logPrevBtn)  logPrevBtn.disabled = logState.page <= 1;
    if (logNextBtn)  logNextBtn.disabled = logState.page >= totalPages;

    if (logPagerWrap) {
      logPagerWrap.hidden = filtered.length === 0 || totalPages <= 1;
    }
  }

  if (ddLogAction) {
    wireDropdown(ddLogAction, (value, label) => {
      setLogActiveAction(value, label);
      logState.page = 1;
      applyLogRender();
    });

    const initial = getLogActiveAction();
    if (initial) {
      const item = ddLogAction.querySelector(`.em-ddItem[data-value="${CSS.escape(initial)}"]`);
      setLogActiveAction(initial, item ? item.textContent.trim() : initial);
    } else {
      setLogActiveAction("", "All actions");
    }
  }

  if (ddLogRows) {
    const menu = ddLogRows.querySelector("[data-dd-menu]");
    if (menu) {
      menu.innerHTML = `
        <button class="em-ddItem" type="button" data-value="5">5</button>
        <button class="em-ddItem" type="button" data-value="10">10</button>
      `;
    }

    wireDropdown(ddLogRows, (value, label) => {
      setLogRowsPerPage(value || 10);
      logState.page = 1;
      applyLogRender();
    });

    setLogRowsPerPage(Number(logRowsValue?.value || 10));
  }

  if (logFilterForm) {
    logFilterForm.addEventListener("submit", (e) => {
      e.preventDefault();
      logState.page = 1;
      applyLogRender();
    });
  }

  logResetBtn?.addEventListener("click", () => {
    if (logStartInput)  logStartInput.value = "";
    if (logEndInput)    logEndInput.value = "";
    if (logSearchInput) logSearchInput.value = "";

    setLogActiveAction("", "All actions");
    setLogRowsPerPage(10);
    logState.page = 1;
    applyLogRender();
  });

  logSearchClearBtn?.addEventListener("click", () => {
    if (logSearchInput) logSearchInput.value = "";
    logState.page = 1;
    applyLogRender();
  });

  logPrevBtn?.addEventListener("click", () => {
    logState.page = Math.max(1, logState.page - 1);
    applyLogRender();
  });
  logNextBtn?.addEventListener("click", () => {
    logState.page = logState.page + 1;
    applyLogRender();
  });

  logBtn?.addEventListener("click", () => {
    logState.page = 1;
    applyLogRender();
  });

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

  // ✅ Success modal trigger (supports success OR submit_success via blade flag)
  if (flags) {
    const hasSuccess = flags.getAttribute("data-has-success") === "1";
    const hasError   = flags.getAttribute("data-has-error") === "1";
    const errorMsg   = flags.getAttribute("data-error-msg") || "";

    if (hasSuccess) {
      const el = document.getElementById("emModalSuccess");
      if (el && hasBootstrap()) bootstrap.Modal.getOrCreateInstance(el).show();
    } else if (hasError) {
      showNotice("Error", errorMsg || "Something went wrong.");
    }
  }

  applyLogRender();

  syncCount();
  applyNow();
})();
