// public/assets/js/script.js
(() => {
  const root = document.getElementById("hpRoot");
  if (!root) return;

  const state = {
    tab: (root.getAttribute("data-default-tab") || "ongoing").trim(),
    q: "",
    sort: "date_asc",
    district: "",
    barangay: "",
    month: "",
  };

  const tabs = [...root.querySelectorAll(".hp-tab")];
  const panes = [...root.querySelectorAll(".hp-pane")];

  const searchInput = document.getElementById("hpSearch");
  const searchBtn = document.getElementById("hpSearchBtn");
  const searchClear = document.getElementById("hpSearchClear");

  const panel = document.getElementById("hpPanel");
  const toggle = document.getElementById("hpFilterToggle");
  const applyBtn = document.getElementById("hpApply");
  const resetBtn = document.getElementById("hpReset");

  const ddSort = root.querySelector('.hp-dd[data-dd="sort"]');
  const ddDistrict = root.querySelector('.hp-dd[data-dd="district"]');
  const ddBarangay = root.querySelector('.hp-dd[data-dd="barangay"]');
  const ddMonth = root.querySelector('.hp-dd[data-dd="month"]');
  const barangayMenu = document.getElementById("hpBarangayMenu");

  const barangaysByDistrict = (() => {
    try { return JSON.parse(root.getAttribute("data-barangays") || "{}"); }
    catch { return {}; }
  })();

  const titleCase = (s) => (s || "")
    .toLowerCase()
    .replace(/\b\w/g, m => m.toUpperCase());

  const activePane = () => panes.find(p => p.dataset.pane === state.tab);
  const listInActive = () => activePane()?.querySelector(".hp-list");
  const cardsInActive = () => [...(activePane()?.querySelectorAll(".hp-event") || [])];

  // ----------------------------
  // Helpers
  // ----------------------------
  function closeAllDropdowns() {
    root.querySelectorAll(".hp-dd.is-open").forEach(dd => dd.classList.remove("is-open"));
  }

  function setDropdownValue(dd, value, label) {
    if (!dd) return;
    dd.dataset.value = value ?? "";
    const text = dd.querySelector("[data-dd-text]");
    if (text) text.textContent = label ?? "";
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

  // Outside clicks: close dropdowns only (not panel)
  document.addEventListener("mousedown", (e) => {
    const anyDD = root.querySelector(".hp-dd.is-open");
    if (anyDD && !anyDD.contains(e.target)) closeAllDropdowns();
  });

  // Debounce for search so typing doesn't stutter
  const debounce = (fn, ms = 150) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), ms);
    };
  };

  // Build barangay -> district lookup for autofill
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

    if (state.district === distId) return;

    state.district = distId;
    setDropdownValue(ddDistrict, distId, deriveDistrictLabelFromMenu(distId));
    rebuildBarangayMenu(); // keeps selection if valid
  }

  // ----------------------------
  // Barangay menu rebuild (now safer + less jank)
  // ----------------------------
  function rebuildBarangayMenu() {
    if (!barangayMenu) return;

    const dist = state.district;
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

    // Build with fragment (faster)
    barangayMenu.innerHTML = "";
    const frag = document.createDocumentFragment();

    // sticky search header
    const searchWrap = document.createElement("div");
    searchWrap.className = "hp-ddSearchWrap";
    searchWrap.innerHTML = `
      <div class="hp-ddSearch">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="hpBarangaySearch" placeholder="Search barangay..." autocomplete="off" />
      </div>
      <div class="hp-ddHr"></div>
    `;
    frag.appendChild(searchWrap);

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

    // filter as you type (debounced)
    const inp = barangayMenu.querySelector("#hpBarangaySearch");
    const runFilter = debounce(() => {
      const needle = (inp?.value || "").trim().toLowerCase();
      barangayMenu.querySelectorAll(".hp-ddBrgyItem").forEach((it) => {
        const label = (it.dataset.label || "");
        it.style.display = !needle || label.includes(needle) ? "" : "none";
      });
    }, 80);

    inp?.addEventListener("input", runFilter);

    // Prevent weird "input overlaps / closes" by stopping click from bubbling
    inp?.addEventListener("mousedown", (e) => e.stopPropagation());
    inp?.addEventListener("click", (e) => e.stopPropagation());

    // reset selection if no longer valid under selected district
    if (state.barangay) {
      const still = uniq.some(b => b.toLowerCase() === state.barangay);
      if (!still) {
        state.barangay = "";
        setDropdownValue(ddBarangay, "", "All Barangays");
      }
    }
  }

  // ----------------------------
  // Apply filters + sort (optimized)
  // ----------------------------
  function applyNow() {
    const pane = activePane();
    if (!pane) return;

    const q = (state.q || "").trim().toLowerCase();
    const cards = cardsInActive();

    // Filter (toggle class instead of inline display)
    for (const c of cards) {
      const hay = (c.getAttribute("data-hay") || "").toLowerCase();
      const cDist = (c.getAttribute("data-district") || "").trim();
      const cBrgy = (c.getAttribute("data-barangay") || "").trim();
      const cMonth = (c.getAttribute("data-month") || "").trim();

      const okSearch = !q || hay.includes(q);
      const okDist = !state.district || cDist === state.district;
      const okBrgy = !state.barangay || cBrgy === state.barangay;
      const okMonth = !state.month || cMonth === state.month;

      c.classList.toggle("is-hidden", !(okSearch && okDist && okBrgy && okMonth));
    }

    const visible = cards.filter(c => !c.classList.contains("is-hidden"));

    // Sort visible only
    visible.sort((a, b) => {
      const ta = (a.getAttribute("data-title") || "").toLowerCase();
      const tb = (b.getAttribute("data-title") || "").toLowerCase();
      const da = Number(a.getAttribute("data-date") || 0);
      const db = Number(b.getAttribute("data-date") || 0);

      switch (state.sort) {
        case "title_asc": return ta.localeCompare(tb);
        case "title_desc": return tb.localeCompare(ta);
        case "date_desc": return db - da;
        case "date_asc":
        default: return da - db;
      }
    });

    // Batch DOM append (fix lag)
    const list = pane.querySelector(".hp-list");
    if (list) {
      const frag = document.createDocumentFragment();
      visible.forEach(c => frag.appendChild(c));
      list.appendChild(frag);
    }
  }

  // ----------------------------
  // Tabs
  // ----------------------------
  function setTab(key) {
    state.tab = key;

    tabs.forEach(t => {
      const on = t.dataset.tab === key;
      t.classList.toggle("is-active", on);
      t.setAttribute("aria-selected", on ? "true" : "false");
    });

    panes.forEach(p => (p.hidden = (p.dataset.pane !== key)));

    // Important: no need to rebuild barangays on every tab unless district changes.
    // But keep it for correctness if you rely on tab-specific DOM.
    rebuildBarangayMenu();
    applyNow();
  }

  tabs.forEach(t => t.addEventListener("click", () => setTab(t.dataset.tab)));

  // ----------------------------
  // Search (fix: input + debounce + Enter + button)
  // ----------------------------
  const commitSearch = () => {
    state.q = (searchInput?.value || "").trim();
    applyNow();
  };

  const debouncedSearch = debounce(commitSearch, 140);

  searchInput?.addEventListener("input", debouncedSearch);
  searchInput?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") commitSearch();
  });
  searchBtn?.addEventListener("click", commitSearch);

  searchClear?.addEventListener("click", () => {
    if (searchInput) searchInput.value = "";
    state.q = "";
    applyNow();
  });

  // ----------------------------
  // Dropdown wiring
  // ----------------------------
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
        // Focus search after open
        setTimeout(() => menu?.querySelector("#hpBarangaySearch")?.focus(), 0);
      }
    });

    // Stop clicks inside menu from bubbling to document (prevents weird close/focus behavior)
    menu?.addEventListener("mousedown", (e) => e.stopPropagation());

    menu?.addEventListener("click", (e) => {
      const item = e.target.closest(".hp-ddItem");
      if (!item) return;
      const value = item.dataset.value ?? "";
      onPick(value, item.textContent.trim());
      dd.classList.remove("is-open");
    });
  }

  // Sort dropdown
  wireDropdown(ddSort, (value, label) => {
    state.sort = value || "date_asc";
    setDropdownValue(ddSort, state.sort, label || "Sort by Date (Soonest)");
    // option: live apply
    applyNow();
  });

  // District dropdown
  wireDropdown(ddDistrict, (value, label) => {
    state.district = value;

    setDropdownValue(ddDistrict, value, label || "All Districts");
    rebuildBarangayMenu();
    applyNow();
  });

  // Barangay dropdown (auto-fill district)
  wireDropdown(ddBarangay, (value, label) => {
    state.barangay = value;
    setDropdownValue(ddBarangay, value, label || "All Barangays");

    if (value) autofillDistrictFromBarangay(String(value).toLowerCase());
    applyNow();
  });

  // Month dropdown
  wireDropdown(ddMonth, (value, label) => {
    state.month = value;
    setDropdownValue(ddMonth, value, label || "All Months");
    applyNow();
  });

  applyBtn?.addEventListener("click", () => {
    commitSearch();
    applyNow();
  });

  resetBtn?.addEventListener("click", () => {
    state.q = "";
    state.sort = "date_asc";
    state.district = "";
    state.barangay = "";
    state.month = "";

    if (searchInput) searchInput.value = "";

    setDropdownValue(ddSort, "date_asc", "Sort by Date (Soonest)");
    setDropdownValue(ddDistrict, "", "All Districts");
    setDropdownValue(ddBarangay, "", "All Barangays");
    setDropdownValue(ddMonth, "", "All Months");

    rebuildBarangayMenu();
    applyNow();
  });

  // init
  rebuildBarangayMenu();
  setDropdownValue(ddSort, "date_asc", "Sort by Date (Soonest)");
  setPanel(false);
  setTab(state.tab);
})();
