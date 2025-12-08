(function () {
  "use strict";

  const BOOT = window.__EVENT_DETAILS_BOOT || {};
  const CSRF_TOKEN = BOOT.csrfToken || "";

  // --- Data from backend -----------------------------------------------------
  let EXPECTED = Array.isArray(BOOT.expected) ? BOOT.expected.slice() : [];
  const RAW_ACTUAL = Array.isArray(BOOT.actual) ? BOOT.actual.slice() : [];

  const DEFAULT_TAB = BOOT.defaultTab === "actual" ? "actual" : "expected";
  const DEFAULT_AVATAR = BOOT.defaultAvatar || "";

  const PRESENT_COUNT = Number(BOOT.presentCount || 0);
  const LATE_COUNT = Number(BOOT.lateCount || 0);
  const WALKIN_COUNT = Number(BOOT.walkInCount || 0);
  const MAX_VOLUNTEERS = BOOT.maxVolunteers ?? null;

  const ADD_VOL_URL = BOOT.addVolUrl || "";
  const VOL_DATA_URL = BOOT.volDataUrl || "";
  const REMOVE_EXPECTED_URL_TEMPLATE = BOOT.removeExpectedUrlTemplate || "";
  const VOL_SHOW_URL_TEMPLATE = BOOT.volunteerShowUrlTemplate || "";

  const BOOT_SUCCESS = BOOT.bootSuccess || null;
  const SUMMARY_NOTICE = BOOT.summaryNotice || null;

  const EVENT_CODE = (BOOT.eventCode || "").toString();
  const EVENT_STATUS = (BOOT.eventStatus || "").toLowerCase();
  const HAS_ATTENDANCE_IMPORT = !!BOOT.hasAttendanceImport;

  // --- helpers ---------------------------------------------------------------
  const q = (sel, root = document) => root.querySelector(sel);
  const qa = (sel, root = document) => Array.from(root.querySelectorAll(sel));
  const norm = (s) => (s ?? "").toString().trim().toLowerCase();
  const truthy = (v) =>
    v === true || v === 1 || v === "1" || v === "true" || v === "yes";

  function escapeHtml(str) {
    return (str ?? "").toString()
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function openResultModal(title, text, mode = "success") {
    const modalEl = q("#actionResultModal");
    if (!modalEl) return;

    q("#resultTitle").textContent =
      title || (mode === "error" ? "Error" : "Success");
    q("#resultSub").textContent = text || "";

    const iconBox = q("#resultIcon");
    iconBox.classList.remove("success", "error");

    if (mode === "error") {
      iconBox.classList.add("error");
      iconBox.innerHTML =
        '<i class="fa-solid fa-triangle-exclamation"></i>';
    } else {
      iconBox.classList.add("success");
      iconBox.innerHTML = '<i class="fa-solid fa-check"></i>';
    }

    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  window.openResultModal = openResultModal;

  // --- contact / emergency helpers ------------------------------------------
  function resolveContact(v) {
    return (
      v.contact ||
      v.phone ||
      v.mobile ||
      v.mobile_no ||
      v.contact_number ||
      v.contact_no ||
      v.number || // in case the column is literally "number"
      ""
    );
  }

  function resolveEmergency(v) {
    return (
      v.emergency_contact ||
      v.emergency_number ||
      v.emergency_no ||
      v.emergency ||
      ""
    );
  }

  function resolveImportedLabel(v) {
    return (
      v.imported_label ||
      v.imported_at_label ||
      v.checkin_label ||
      v.checkin_at_label ||
      v.time_label ||
      v.imported_at ||
      v.checkin_at ||
      ""
    );
  }

  // --- Dropdown helpers ------------------------------------------------------
  function closeAllDropdowns() {
    qa(".dd.open").forEach((dd) => dd.classList.remove("open"));
  }

  function setupDropdown(dd) {
    if (!dd) return;
    const trigger = dd.querySelector(".dd-trigger");
    if (trigger) {
      trigger.addEventListener("click", (e) => {
        e.stopPropagation();
        const willOpen = !dd.classList.contains("open");
        closeAllDropdowns();
        dd.classList.toggle("open", willOpen);
      });
    }

    dd.addEventListener("click", (e) => {
      const item = e.target.closest(".dd-item");
      if (!item) return;

      dd.querySelectorAll(".dd-item").forEach((x) =>
        x.classList.remove("is-active")
      );
      item.classList.add("is-active");

      const hidden = dd.querySelector('input[type="hidden"]');
      const label = dd.querySelector(".dd-label");
      const val = item.getAttribute("data-value") ?? "";

      if (hidden) hidden.value = val;
      if (label)
        label.textContent = item.textContent.replace(/\s+/g, " ").trim();

      dd.classList.remove("open");
      renderActiveTab(1);
      updateTopStat();
    });
  }

  function initCourseFilter() {
    const menu = q("#course-menu");
    if (!menu) return;

    const courses = new Set();
    EXPECTED.forEach((v) => {
      const c = (v.course ?? "").toString().trim();
      if (c) courses.add(c);
    });

    qa("#course-menu .dd-item")
      .slice(1)
      .forEach((el) => el.remove());

    Array.from(courses)
      .sort((a, b) => a.localeCompare(b))
      .forEach((c) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.className = "dd-item";
        btn.setAttribute("data-value", c);
        btn.innerHTML =
          '<i class="fa-solid fa-graduation-cap"></i> ' + escapeHtml(c);
        menu.appendChild(btn);
      });

    q("#dd-course")?.classList.toggle("is-empty", courses.size === 0);
  }

  // --- Attendance (no extra JS absents) -------------------------------------
  let ACTUAL = [];
  let ABSENT_ROWS = [];
  let ABSENT_COUNT = 0;

  function recomputeActualWithAbsents() {
    const normalizedActual = RAW_ACTUAL.map((row) => ({
      ...row,
      walk_in: truthy(row.walk_in),
      status: norm(row.status || "present") || "present",
      contact: resolveContact(row),
      emergency: resolveEmergency(row),
    }));

    ACTUAL = normalizedActual;
    ABSENT_ROWS = normalizedActual.filter(
      (r) => r.status === "absent" && !truthy(r.walk_in)
    );
    ABSENT_COUNT = ABSENT_ROWS.length;
  }

  recomputeActualWithAbsents();

  // --- Roster stats / tabs ---------------------------------------------------
  let activeTab = DEFAULT_TAB;
  let expPage = 1;
  let actPage = 1;
  const ITEMS_PER_PAGE = 10;

  let ACTIVE_STATUS_FILTER = "";

  function updateStatusFilterButtons() {
    qa(".status-pill").forEach((btn) => {
      const val = btn.getAttribute("data-status") || "";
      btn.classList.toggle("is-active", val === ACTIVE_STATUS_FILTER);
    });
  }

  function updateStatusFilterVisibility() {
    const row2 = q(".ra-filters--row2");
    if (!row2) return;
    row2.style.display = activeTab === "actual" ? "" : "none";
  }

  function updateTopStat() {
    const stat = q("#raStat");
    if (!stat) return;

    if (activeTab === "expected") {
      const expected = EXPECTED.length;
      if (MAX_VOLUNTEERS) {
        stat.innerHTML =
          '<span class="ra-stat-pill">Expected <b>' +
          expected +
          "</b> / <b>" +
          MAX_VOLUNTEERS +
          "</b></span>";
      } else {
        stat.innerHTML =
          '<span class="ra-stat-pill">Expected <b>' +
          expected +
          "</b></span>";
      }
    } else {
      let html = "";
      html +=
        '<span class="ra-stat-pill ra-stat-pill--ok">Present <b>' +
        PRESENT_COUNT +
        "</b></span>";
      html +=
        '<span class="ra-stat-pill ra-stat-pill--warn">Late <b>' +
        LATE_COUNT +
        "</b></span>";
      html +=
        '<span class="ra-stat-pill ra-stat-pill--neutral">Absent <b>' +
        ABSENT_COUNT +
        "</b></span>";
      if (WALKIN_COUNT > 0) {
        html +=
          '<span class="ra-stat-pill ra-stat-pill--neutral">Walk-in <b>' +
          WALKIN_COUNT +
          "</b></span>";
      }
      stat.innerHTML = html;
    }
  }

  function setTab(tab) {
    activeTab = tab === "actual" ? "actual" : "expected";

    qa(".ra-tab").forEach((b) =>
      b.classList.toggle("is-active", b.dataset.tab === activeTab)
    );
    qa(".tab-panel").forEach((p) =>
      p.classList.toggle("is-active", p.dataset.panel === activeTab)
    );

    updateStatusFilterVisibility();
    renderActiveTab(1);
    updateTopStat();
  }

  // --- Sorting / filtering / search / status filter -------------------------
  function sortItems(items) {
    const sortVal = q("#sort")?.value || "name_asc";
    const arr = items.slice();
    arr.sort((a, b) => {
      const an = norm(a.name);
      const bn = norm(b.name);
      return sortVal === "name_desc"
        ? bn.localeCompare(an)
        : an.localeCompare(bn);
    });
    return arr;
  }

  function filterItems(items) {
    const term = norm(q("#list-search")?.value);
    const courseVal = (q("#course")?.value ?? "").toString().trim();

    return items.filter((v) => {
      // text search
      let okTerm = true;
      if (term) {
        const blob = [
          v.name,
          v.course,
          v.email,
          v.school_id,
          v.status,
          v.source,
          resolveContact(v),
        ]
          .filter(Boolean)
          .join(" ")
          .toLowerCase();
        okTerm = blob.includes(term);
      }

      // course filter – applies to both expected & actual
      let okCourse = true;
      if (courseVal) {
        okCourse = (v.course ?? "").toString().trim() === courseVal;
      }

      // status filter – only for Attendance tab
      let okStatus = true;
      if (activeTab === "actual" && ACTIVE_STATUS_FILTER) {
        const s = norm(v.status || "");
        const isWalk = truthy(v.walk_in);
        switch (ACTIVE_STATUS_FILTER) {
          case "present":
            okStatus = s === "present";
            break;
          case "late":
            okStatus = s === "late";
            break;
          case "absent":
            okStatus = s === "absent";
            break;
          case "walk_in":
            okStatus = isWalk;
            break;
          default:
            okStatus = true;
        }
      }

      return okTerm && okCourse && okStatus;
    });
  }

  function resolveAvatarUrl(v) {
    const pic = (v?.profile_pic ?? "").toString().trim();
    if (pic) return pic;
    return DEFAULT_AVATAR;
  }

  // --- highlight helper -----------------------------------------------------
  function ensureHighlightStyles() {
    if (document.getElementById("event-details-highlight-style")) return;
    const style = document.createElement("style");
    style.id = "event-details-highlight-style";
    style.textContent = `
      .card-highlight {
        box-shadow: 0 0 0 3px rgba(178,58,69,.7), 0 0 0 6px rgba(178,58,69,.25) !important;
        transform: translateY(-2px);
      }
    `;
    document.head.appendChild(style);
  }

  function highlightCard(tab, id) {
    ensureHighlightStyles();
    const gridId = tab === "actual" ? "#grid-actual" : "#grid-expected";

    const esc =
      window.CSS && CSS.escape
        ? CSS.escape(String(id))
        : String(id).replace(/"/g, '\\"');

    const card = q(`${gridId} .student-card[data-id="${esc}"]`);
    if (!card) return;

    card.classList.add("card-highlight");
    card.scrollIntoView({ behavior: "smooth", block: "center" });

    setTimeout(() => {
      card.classList.remove("card-highlight");
    }, 1600);
  }

  // --- render grid ----------------------------------------------------------
  function renderGrid({ items, gridEl, page, leftBtn, rightBtn, type }) {
    const filtered = sortItems(filterItems(items));
    const total = filtered.length;
    const maxPage = Math.max(1, Math.ceil(total / ITEMS_PER_PAGE));
    const safePage = Math.max(1, Math.min(page, maxPage));

    gridEl.innerHTML = "";

    const start = (safePage - 1) * ITEMS_PER_PAGE;
    const slice = filtered.slice(start, start + ITEMS_PER_PAGE);

    if (slice.length === 0) {
      const msg =
        type === "expected"
          ? "No volunteers found."
          : "No attendance records found.";
      const sub =
        type === "expected"
          ? "Try a different search/filter, or add volunteers to the roster."
          : "Try a different search, or import attendance from the left-side action.";

      gridEl.innerHTML =
        '<div class="expected-empty">' +
        '<div class="expected-empty-ico"><i class="fa-regular fa-user"></i></div>' +
        '<div class="expected-empty-title">' +
        msg +
        "</div>" +
        '<div class="expected-empty-sub">' +
        sub +
        "</div>" +
        "</div>";
    } else {
      slice.forEach((v) => {
        const card = document.createElement("div");
        card.className =
          "student-card" + (type === "actual" ? " student-card--actual" : "");
        card.dataset.id = v.id ?? "";

        const statusRaw = norm(v.status || "");
        const isLate = statusRaw === "late";
        const isAbsent = statusRaw === "absent";
        const isWalk = truthy(v.walk_in);

        let statusLabel = "";
        let statusClass = "";
        if (type === "actual") {
          if (isAbsent) {
            statusLabel = "Absent";
            statusClass = "pill-sm--neutral";
          } else if (isLate) {
            statusLabel = "Late";
            statusClass = "pill-sm--warn";
          } else {
            statusLabel = "Present";
            statusClass = "pill-sm--good";
          }
        }

        let rightMeta = "";

        if (type === "actual") {
          const importedLabel = resolveImportedLabel(v);
          const email = v.email || "";
          const contact = resolveContact(v);
          const emergency = resolveEmergency(v);
          let sourceText = v.source || "";

          if (!sourceText) {
            if (isWalk) sourceText = "Walk-in";
            else if (isAbsent) sourceText = "No check-in";
            else sourceText = "Attendance import";
          }

          const statusPill = statusLabel
            ? `<span class="pill-sm ${statusClass}">${escapeHtml(
                statusLabel.toUpperCase()
              )}</span>`
            : "";
          const walkPill = isWalk
            ? '<span class="pill-sm pill-sm--neutral">WALK-IN</span>'
            : "";

          const detailsBtn = `
            <button type="button"
                    class="btn-details"
                    data-details-toggle
                    data-imported="${escapeHtml(importedLabel || "")}"
                    data-email="${escapeHtml(email || "")}"
                    data-contact="${escapeHtml(contact || "")}"
                    data-emergency="${escapeHtml(emergency || "")}"
                    data-source="${escapeHtml(sourceText || "")}">
              <i class="fa-solid fa-circle-info"></i>
              <span>Details</span>
            </button>
          `;

          rightMeta = `
            <div class="meta-right">
              ${statusPill}
              ${walkPill}
              ${detailsBtn}
            </div>
          `;
        } else {
          rightMeta = `
            <button type="button" class="btn btn-sm btn-card-remove ms-auto"
                    data-remove-expected="${v.id}" title="Remove from roster">
              <i class="fa-solid fa-xmark"></i>
            </button>
          `;
        }

        const avatar = resolveAvatarUrl(v);
        const nameHtml = v.profile_url
          ? `<a class="name" href="${escapeHtml(
              v.profile_url
            )}">${escapeHtml(v.name || "—")}</a>`
          : `<div class="name">${escapeHtml(v.name || "—")}</div>`;

        const courseOrEmail = v.course || v.email || "—";

        card.innerHTML = `
          <img
            src="${escapeHtml(avatar)}"
            class="avatar"
            alt=""
            onerror="this.onerror=null;this.src='${escapeHtml(DEFAULT_AVATAR)}';"
          >
          <div class="meta">
            ${nameHtml}
            <div class="course" title="${escapeHtml(
              courseOrEmail
            )}">${escapeHtml(courseOrEmail)}</div>
          </div>
          ${rightMeta}
        `;

        gridEl.appendChild(card);
      });
    }

    leftBtn.disabled = safePage <= 1;
    rightBtn.disabled = safePage >= maxPage;

    return safePage;
  }

  function renderActiveTab(page) {
    if (activeTab === "expected") {
      expPage = renderGrid({
        items: EXPECTED,
        gridEl: q("#grid-expected"),
        page,
        leftBtn: q("#exp-left"),
        rightBtn: q("#exp-right"),
        type: "expected",
      });
    } else {
      actPage = renderGrid({
        items: ACTUAL,
        gridEl: q("#grid-actual"),
        page,
        leftBtn: q("#act-left"),
        rightBtn: q("#act-right"),
        type: "actual",
      });
    }
  }

  // --- pagination controls ---------------------------------------------------
  q("#exp-left")?.addEventListener("click", () => {
    setTab("expected");
    renderActiveTab(expPage - 1);
  });
  q("#exp-right")?.addEventListener("click", () => {
    setTab("expected");
    renderActiveTab(expPage + 1);
  });
  q("#act-left")?.addEventListener("click", () => {
    setTab("actual");
    renderActiveTab(actPage - 1);
  });
  q("#act-right")?.addEventListener("click", () => {
    setTab("actual");
    renderActiveTab(actPage + 1);
  });

  // --- remove expected helper -----------------------------------------------
  async function removeExpectedVolunteer(vid) {
    const url = REMOVE_EXPECTED_URL_TEMPLATE.replace("__VID__", String(vid));
    const res = await fetch(url, {
      method: "DELETE",
      headers: {
        Accept: "application/json",
        "X-CSRF-TOKEN": CSRF_TOKEN,
      },
    });
    const text = await res.text();
    let json = {};
    try {
      json = JSON.parse(text);
    } catch {
      json = { success: false, message: text };
    }
    if (!res.ok || json.success !== true) {
      throw new Error(json.message || "Failed to remove volunteer.");
    }
    return true;
  }

  // --- Attendance Details modal ---------------------------------------------
  function ensureAttendanceDetailsModal() {
    if (q("#attendanceDetailsModal")) return;

    const wrapper = document.createElement("div");
    wrapper.innerHTML = `
      <div class="modal fade" id="attendanceDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content modal-soft">
            <div class="modal-header modal-soft-header">
              <div class="d-flex align-items-center gap-2">
                <div class="modal-icon"><i class="fa-solid fa-user"></i></div>
                <div>
                  <h5 class="modal-title mb-0" id="attDetailsTitle">Volunteer details</h5>
                  <div class="small text-muted" id="attDetailsSubtitle"></div>
                </div>
              </div>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-soft-body">
              <div class="student-card-details-grid">
                <div class="student-card-details-row">
                  <div class="student-card-details-label">Imported:</div>
                  <div class="student-card-details-value" id="attDetailsImported">—</div>
                </div>
                <div class="student-card-details-row">
                  <div class="student-card-details-label">Email:</div>
                  <div class="student-card-details-value" id="attDetailsEmail">—</div>
                </div>
                <div class="student-card-details-row">
                  <div class="student-card-details-label">Contact:</div>
                  <div class="student-card-details-value" id="attDetailsContact">—</div>
                </div>
                <div class="student-card-details-row">
                  <div class="student-card-details-label">Emergency:</div>
                  <div class="student-card-details-value" id="attDetailsEmergency">—</div>
                </div>
                <div class="student-card-details-row">
                  <div class="student-card-details-label">Source:</div>
                  <div class="student-card-details-value" id="attDetailsSource">—</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>`;
    document.body.appendChild(wrapper.firstElementChild);
  }

  function openAttendanceDetailsModal(card, btn) {
    ensureAttendanceDetailsModal();
    const modalEl = q("#attendanceDetailsModal");
    if (!modalEl) return;

    const name =
      card.querySelector(".name")?.textContent?.trim() || "Volunteer details";
    const course =
      card.querySelector(".course")?.textContent?.trim() || "";

    q("#attDetailsTitle").textContent = name;
    q("#attDetailsSubtitle").textContent = course;

    const d = btn.dataset || {};
    q("#attDetailsImported").textContent = d.imported || "—";
    q("#attDetailsEmail").textContent = d.email || "—";
    q("#attDetailsContact").textContent = d.contact || "—";
    q("#attDetailsEmergency").textContent = d.emergency || "—";
    q("#attDetailsSource").textContent = d.source || "—";

    window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  // --- card click handling: details modal + remove expected -----------------
  document.addEventListener("click", async (e) => {
    // Details button (Attendance cards)
    const detailsBtn = e.target.closest("[data-details-toggle]");
    if (detailsBtn) {
      const card = detailsBtn.closest(".student-card--actual");
      if (card) {
        e.preventDefault();
        e.stopPropagation();
        openAttendanceDetailsModal(card, detailsBtn);
      }
      return;
    }

    // Remove expected button
    const btn = e.target.closest("[data-remove-expected]");
    if (!btn) return;

    const vid = Number(btn.getAttribute("data-remove-expected"));
    if (!vid) return;

    try {
      await removeExpectedVolunteer(vid);

      const idx = EXPECTED.findIndex((x) => Number(x.id) === vid);
      if (idx !== -1) EXPECTED.splice(idx, 1);

      initCourseFilter();
      recomputeActualWithAbsents();
      renderActiveTab(1);
      updateTopStat();
      openResultModal("Removed", "Volunteer removed from roster.");
    } catch (err) {
      openResultModal(
        "Error",
        err.message || "Failed to remove volunteer.",
        "error"
      );
    }
  });

  // --- Add Volunteers modal --------------------------------------------------
  let AVAILABLE_VOLUNTEERS = [];

  function ensureSelectedEmptyState() {
    const selectedList = q("#selected-list");
    if (!selectedList) return;
    if (selectedList.querySelector(".student-card")) return;

    selectedList.innerHTML = `
      <div class="empty-state">
        <div class="empty-ico"><i class="fa-solid fa-user-plus"></i></div>
        <div class="empty-title">No one selected</div>
        <div class="empty-sub">Select from the left, then click “Move to Selected”.</div>
      </div>
    `;
    q("#selected-count").textContent = "0";
  }

  function updateSelectedCount() {
    q("#selected-count").textContent = String(
      qa("#selected-list .student-card").length
    );
  }

  function resolveApiAvatar(v) {
    const avatarUrl = (v?.avatar_url ?? "").toString().trim();
    if (avatarUrl) return avatarUrl;

    const p = (v?.profile_picture_path ?? "").toString().trim();
    if (p) {
      const trimmed = p.replace(/^\/+/, "");
      return "/storage/" + trimmed;
    }
    return DEFAULT_AVATAR;
  }

  async function loadAvailableVolunteers() {
    const list = q("#available-volunteers-list");
    try {
      const url = new URL(VOL_DATA_URL, window.location.origin);
      url.searchParams.set("per_page", "500");

      const res = await fetch(url.toString(), {
        headers: { Accept: "application/json" },
      });
      if (!res.ok) throw new Error("Failed to load volunteers");

      const json = await res.json();
      AVAILABLE_VOLUNTEERS = json.data || [];

      renderAvailableList();
      ensureSelectedEmptyState();
    } catch (err) {
      console.error(err);
      if (list)
        list.innerHTML =
          '<div class="small text-danger py-2">Error loading volunteers.</div>';
    }
  }

  function renderAvailableList() {
    const list = q("#available-volunteers-list");
    if (!list) return;

    const term = norm(q("#modal-search")?.value);
    const selectedIds = new Set(
      qa("#selected-list .student-card").map((el) => Number(el.dataset.id))
    );

    let filtered = AVAILABLE_VOLUNTEERS.filter(
      (v) => !selectedIds.has(Number(v.volunteer_id))
    ).filter((v) => {
      const name = norm(v.full_name);
      const course = norm(v.course?.course_name || "");
      return !term || name.includes(term) || course.includes(term);
    });

    filtered.sort((a, b) => norm(a.full_name).localeCompare(norm(b.full_name)));

    list.innerHTML = "";

    if (filtered.length === 0) {
      list.innerHTML = `
        <div class="expected-empty" style="min-height: 220px;">
          <div class="expected-empty-ico"><i class="fa-regular fa-face-frown"></i></div>
          <div class="expected-empty-title">No volunteers found</div>
          <div class="expected-empty-sub">Try a different search.</div>
        </div>
      `;
      return;
    }

    filtered.forEach((v) => {
      const avatar = resolveApiAvatar(v);
      const course = v.course?.course_name || "—";

      const div = document.createElement("div");
      div.className = "student-card modal-student d-flex align-items-center";
      div.dataset.id = v.volunteer_id;

      div.innerHTML = `
        <input type="checkbox" class="form-check-input me-2 available-check" data-id="${v.volunteer_id}">
        <img
          src="${escapeHtml(avatar)}"
          class="avatar me-2"
          alt=""
          onerror="this.onerror=null;this.src='${escapeHtml(DEFAULT_AVATAR)}';"
        >
        <div class="meta">
          <div class="name">${escapeHtml(v.full_name)}</div>
          <div class="course small text-muted">${escapeHtml(course)}</div>
        </div>
      `;
      list.appendChild(div);
    });
  }

  async function saveToServer(volunteerIDs) {
    const res = await fetch(ADD_VOL_URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": CSRF_TOKEN,
      },
      body: JSON.stringify({ volunteer_ids: volunteerIDs }),
    });

    const text = await res.text();
    try {
      return { ok: res.ok, json: JSON.parse(text) };
    } catch {
      return { ok: res.ok, json: { success: false, message: text } };
    }
  }

  // --- Auto-suggest search ---------------------------------------------------
  function gatherSuggestions(term) {
    const t = norm(term);
    if (!t) return [];
    const out = [];
    const seen = new Set();

    function addFrom(source, tab) {
      source.forEach((item) => {
        const id = item.id ?? item.volunteer_id ?? item.school_id ?? item.email;
        if (!id) return;
        if (seen.has(id)) return;

        const blob = [
          item.name || item.full_name,
          item.course,
          item.email,
          item.school_id,
          resolveContact(item),
        ]
          .filter(Boolean)
          .join(" ")
          .toLowerCase();

        if (!blob.includes(t)) return;

        const contact = resolveContact(item);

        seen.add(id);
        out.push({
          id: item.id ?? item.volunteer_id,
          name: item.name || item.full_name || "Unknown",
          course: item.course || item.course_name || "",
          email: item.email || "",
          school_id: item.school_id || "",
          contact,
          tab,
          status: item.status || "",
          walk_in: truthy(item.walk_in),
        });
      });
    }

    addFrom(ACTUAL, "actual");
    addFrom(EXPECTED, "expected");

    return out.slice(0, 8);
  }

  function renderSuggestions() {
    const panel = q("#search-suggest");
    const input = q("#list-search");
    if (!panel || !input) return;

    const term = input.value || "";
    if (term.trim().length < 2) {
      panel.innerHTML = "";
      panel.style.display = "none";
      return;
    }

    const suggestions = gatherSuggestions(term);
    if (!suggestions.length) {
      panel.innerHTML = "";
      panel.style.display = "none";
      return;
    }

    panel.innerHTML = suggestions
      .map((s) => {
        const metaParts = [];
        if (s.course) metaParts.push(s.course);
        if (s.email) metaParts.push(s.email);
        if (s.contact) metaParts.push(s.contact);
        if (s.school_id) metaParts.push("#" + s.school_id);
        metaParts.push(s.tab === "expected" ? "Roster" : "Attendance");
        if (s.walk_in) metaParts.push("Walk-in");
        if (s.status)
          metaParts.push(
            s.status.charAt(0).toUpperCase() + s.status.slice(1)
          );

        return `
          <button type="button"
                  class="suggest-item"
                  data-id="${escapeHtml(s.id)}"
                  data-tab="${s.tab}"
                  data-status="${escapeHtml(s.status || "")}">
            <div class="suggest-name">${escapeHtml(s.name)}</div>
            <div class="suggest-meta">${escapeHtml(metaParts.join(" • "))}</div>
          </button>
        `;
      })
      .join("");

    panel.style.display = "block";
  }

  function hideSuggestions() {
    const panel = q("#search-suggest");
    if (!panel) return;
    panel.innerHTML = "";
    panel.style.display = "none";
  }

  // --- DOM ready -------------------------------------------------------------
  document.addEventListener("DOMContentLoaded", () => {
    setupDropdown(q("#dd-sort"));
    setupDropdown(q("#dd-course"));
    initCourseFilter();

    updateStatusFilterButtons();
    setTab(activeTab);

    if (BOOT_SUCCESS) {
      openResultModal("Success", BOOT_SUCCESS, "success");
    }

    if (SUMMARY_NOTICE) {
      openResultModal("Summary unavailable", SUMMARY_NOTICE, "error");
    }

    q("#raHintClose")?.addEventListener("click", () =>
      q("#raHint")?.remove()
    );

    const modalEl = q("#addStudentModal");
    modalEl?.addEventListener("shown.bs.modal", () => {
      q("#selected-list").innerHTML = "";
      ensureSelectedEmptyState();
      loadAvailableVolunteers();
    });

    q("#modal-search")?.addEventListener("input", renderAvailableList);

    q("#add-selected-btn")?.addEventListener("click", () => {
      const selectedList = q("#selected-list");
      if (!selectedList) return;

      const checks = qa(".available-check").filter((x) => x.checked);
      if (checks.length === 0) return;

      selectedList.querySelector(".empty-state")?.remove();

      checks.forEach((cb) => {
        const card = cb.closest(".student-card");
        const id = cb.dataset.id;
        if (!card || !id) return;

        if (selectedList.querySelector(`.student-card[data-id="${id}"]`))
          return;

        cb.remove();

        const removeBtn = document.createElement("button");
        removeBtn.className =
          "btn btn-sm btn-outline-secondary ms-auto remove-added";
        removeBtn.type = "button";
        removeBtn.innerHTML = '<i class="fa-solid fa-xmark"></i> Remove';
        card.appendChild(removeBtn);

        selectedList.appendChild(card);
      });

      updateSelectedCount();
      renderAvailableList();
    });

    // Remove from "Selected" + click-card-to-toggle-checkbox in modal
    document.addEventListener("click", (e) => {
      // remove button in Selected list
      const removeBtn = e.target.closest(".remove-added");
      if (removeBtn) {
        const card = removeBtn.closest(".student-card");
        if (!card) return;
        card.remove();
        updateSelectedCount();
        ensureSelectedEmptyState();
        renderAvailableList();
        return;
      }

      // toggle card selection in Available list
      const modalCard = e.target.closest(".modal-student");
      if (modalCard) {
        const cb = modalCard.querySelector(".available-check");
        if (!cb) return;

        if (e.target === cb) {
          modalCard.classList.toggle("is-selected", cb.checked);
        } else {
          cb.checked = !cb.checked;
          modalCard.classList.toggle("is-selected", cb.checked);
        }
      }
    });

    q("#save-student-btn")?.addEventListener("click", async () => {
      const selectedCards = qa("#selected-list .student-card");
      if (selectedCards.length === 0) {
        openResultModal(
          "Nothing selected",
          "Please select volunteers first.",
          "error"
        );
        return;
      }

      const ids = selectedCards
        .map((c) => Number(c.dataset.id))
        .filter(Boolean);
      const { ok, json } = await saveToServer(ids);

      if (!ok || json.success !== true) {
        openResultModal(
          "Error",
          json.message || "Failed to save volunteers.",
          "error"
        );
        return;
      }

      selectedCards.forEach((card) => {
        const id = Number(card.dataset.id);
        if (!EXPECTED.find((v) => Number(v.id) === id)) {
          const imgSrc = card.querySelector("img")?.src || DEFAULT_AVATAR;
          const name = card.querySelector(".name")?.textContent ?? "";
          const course =
            card.querySelector(".course")?.textContent ?? "";

          EXPECTED.push({
            id,
            name,
            course,
            profile_pic: imgSrc,
            profile_url: VOL_SHOW_URL_TEMPLATE.replace("__VID__", String(id)),
          });
        }
      });

      initCourseFilter();
      recomputeActualWithAbsents();
      updateTopStat();

      window.bootstrap.Modal.getInstance(modalEl)?.hide();

      setTab("expected");
      openResultModal(
        "Saved",
        `Added ${json.added ?? 0} volunteer(s). Skipped ${
          json.skipped ?? 0
        }.`,
        "success"
      );
    });

    // Copy event code
    const copyBtn = document.getElementById("copyEventCode");
    if (copyBtn) {
      copyBtn.addEventListener("click", async (e) => {
        e.preventDefault();
        e.stopPropagation();

        const code = EVENT_CODE.toString().trim();
        if (!code || code === "—") {
          openResultModal(
            "No code",
            "This event has no access code to copy.",
            "error"
          );
          return;
        }

        try {
          await navigator.clipboard.writeText(code);
          openResultModal("Copied", "Event code copied to clipboard.");
        } catch (err) {
          const ta = document.createElement("textarea");
          ta.value = code;
          ta.setAttribute("readonly", "");
          ta.style.position = "fixed";
          ta.style.left = "-9999px";
          document.body.appendChild(ta);
          ta.select();
          const ok = document.execCommand("copy");
          document.body.removeChild(ta);

          if (ok)
            openResultModal("Copied", "Event code copied to clipboard.");
          else
            openResultModal(
              "Error",
              "Copy failed. Your browser blocked clipboard access.",
              "error"
            );
        }
      });
    }

    // Summary gating
    const btnSummary = document.getElementById("btnSummary");
    if (btnSummary) {
      btnSummary.addEventListener("click", (e) => {
        const status = EVENT_STATUS;
        const hasAttendance = HAS_ATTENDANCE_IMPORT;

        if (status !== "completed") {
          e.preventDefault();
          openResultModal(
            "Summary unavailable",
            "Event Summary can only be viewed once the event is completed.",
            "error"
          );
          return;
        }

        if (!hasAttendance) {
          e.preventDefault();
          openResultModal(
            "Summary locked",
            "Event Summary is locked until attendance is imported for this event.",
            "error"
          );
          return;
        }
      });
    }

    // list search + auto-suggest
    const listSearch = q("#list-search");
    if (listSearch) {
      listSearch.addEventListener("input", () => {
        renderActiveTab(1);
        renderSuggestions();
      });
      listSearch.addEventListener("focus", renderSuggestions);
    }

    const statusGroup = q("#status-filter-group");
    if (statusGroup) {
      statusGroup.addEventListener("click", (e) => {
        const btn = e.target.closest(".status-pill");
        if (!btn) return;
        ACTIVE_STATUS_FILTER = btn.getAttribute("data-status") || "";
        updateStatusFilterButtons();
        if (activeTab === "actual") {
          renderActiveTab(1);
        }
      });
    }

    const suggestPanel = q("#search-suggest");
    if (suggestPanel) {
      suggestPanel.addEventListener("click", (e) => {
        const item = e.target.closest(".suggest-item");
        if (!item) return;
        const id = item.getAttribute("data-id");
        const tab = item.getAttribute("data-tab") || "expected";

        activeTab = tab;
        setTab(tab);

        const arr = tab === "actual" ? ACTUAL : EXPECTED;
        const idx = arr.findIndex((v) => String(v.id) === String(id));
        if (idx !== -1) {
          const page = Math.floor(idx / ITEMS_PER_PAGE) + 1;
          renderActiveTab(page);
          highlightCard(tab, id);
        }

        hideSuggestions();
      });
    }

    // tab buttons
    qa(".ra-tab").forEach((btn) =>
      btn.addEventListener("click", () => setTab(btn.dataset.tab))
    );
  });

  // Global document click: close suggestions + dropdowns
  document.addEventListener("click", (e) => {
    const inSearch =
      e.target.closest(".search-wrap") || e.target.closest("#search-suggest");
    if (!inSearch) hideSuggestions();
    closeAllDropdowns();
  });
})();
