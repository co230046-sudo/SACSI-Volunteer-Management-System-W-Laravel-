/* global bootstrap */
(() => {
  "use strict";

  // ============================================================
  // Small helpers
  // ============================================================
  const DEV = typeof window.APP_DEBUG !== "undefined" ? !!window.APP_DEBUG : true;
  function debugLog(title, payload) {
    if (!DEV) return;
    console.groupCollapsed(`%c${title}`, "color:#2563eb;font-weight:900;");
    console.log(payload);
    console.groupEnd();
  }

  function el(id) { return document.getElementById(id); }

  function bsInstance(id) {
    const node = el(id);
    if (!node) return null;
    return bootstrap.Modal.getOrCreateInstance(node);
  }

  function bsShow(id) {
    const m = bsInstance(id);
    if (m) m.show();
  }

  function bsHide(id) {
    const m = bsInstance(id);
    if (m) m.hide();
  }

  function showModalAfterHiding(currentId, nextId) {
    const currentEl = el(currentId);
    const current = bsInstance(currentId);
    if (!currentEl || !current) { bsShow(nextId); return; }

    const handler = () => {
      currentEl.removeEventListener("hidden.bs.modal", handler);
      bsShow(nextId);
    };
    currentEl.addEventListener("hidden.bs.modal", handler);
    current.hide();
  }

  function escapeHtml(str) {
    return (str || "").toString().replace(/[&<>"']/g, m => ({
      "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;"
    }[m]));
  }

  function norm(s) {
    return (s || "").toString().trim().toLowerCase().replace(/\s+/g, " ");
  }

  // ============================================================
  // Soft action modal (info/warn/error)
  // ============================================================
  function showActionModal({ title = "Notice", message = "", tone = "warning" } = {}) {
    const titleEl = el("softActionTitle");
    const bodyEl = el("softActionBody");
    const headerEl = el("softActionHeader");

    if (titleEl) titleEl.textContent = title;
    if (bodyEl) bodyEl.textContent = message;

    if (headerEl) {
      headerEl.classList.remove("modal-soft-header--info", "modal-soft-header--warning", "modal-soft-header--danger");
      headerEl.classList.add(
        tone === "danger" ? "modal-soft-header--danger" :
        tone === "info" ? "modal-soft-header--info" :
        "modal-soft-header--warning"
      );
    }
    bsShow("softActionModal");
  }

  // ============================================================
  // Soft confirm modal (YES/NO)
  // ============================================================
  let confirmResolver = null;

  function confirmModal({ title = "Confirm", message = "Are you sure?", tone = "warning", confirmText = "Yes", cancelText = "Cancel" } = {}) {
    const titleEl = el("softConfirmTitle");
    const bodyEl = el("softConfirmBody");
    const headerEl = el("softConfirmHeader");
    const okBtn = el("softConfirmOk");
    const cancelBtn = el("softConfirmCancel");

    if (!titleEl || !bodyEl || !headerEl || !okBtn || !cancelBtn) {
      return Promise.resolve(window.confirm(message));
    }

    titleEl.textContent = title;
    bodyEl.textContent = message;
    okBtn.textContent = confirmText;
    cancelBtn.textContent = cancelText;

    headerEl.classList.remove("modal-soft-header--info", "modal-soft-header--warning", "modal-soft-header--danger");
    headerEl.classList.add(
      tone === "danger" ? "modal-soft-header--danger" :
      tone === "info" ? "modal-soft-header--info" :
      "modal-soft-header--warning"
    );

    bsShow("softConfirmModal");
    return new Promise((resolve) => { confirmResolver = resolve; });
  }

  function initConfirmModalHandlers() {
    const okBtn = el("softConfirmOk");
    const cancelBtn = el("softConfirmCancel");
    if (!okBtn || !cancelBtn) return;

    okBtn.addEventListener("click", () => {
      bsHide("softConfirmModal");
      if (confirmResolver) confirmResolver(true);
      confirmResolver = null;
    });

    cancelBtn.addEventListener("click", () => {
      bsHide("softConfirmModal");
      if (confirmResolver) confirmResolver(false);
      confirmResolver = null;
    });
  }

  // ============================================================
  // Organizer rows in the EVENT form (slots 1-3)
  // ============================================================
  let activeOrganizerRow = null;

  function getOrganizerRows() {
    return Array.from(document.querySelectorAll("#organizers-wrapper .organizer-row"));
  }

  function readOrganizerRow(row) {
    const name = norm(row.querySelector("input[name='organizers[name][]']")?.value);
    const email = norm(row.querySelector("input[name='organizers[email][]']")?.value);
    const contact = norm(row.querySelector("input[name='organizers[contact][]']")?.value);
    return { name, email, contact };
  }

  function showOrganizerDuplicate(msg) {
    const box = el("organizerDuplicateMsg");
    if (box) box.textContent = msg || "Duplicate organizer detected.";
    bsShow("organizerDuplicateModal");
  }

  function findOrganizerDuplicate(targetRow) {
    const t = readOrganizerRow(targetRow);
    if (!t.name) return null;

    for (const row of getOrganizerRows()) {
      if (row === targetRow) continue;
      const o = readOrganizerRow(row);
      if (!o.name) continue;

      if (t.name === o.name) {
        const emailDup = t.email && o.email && t.email === o.email;
        const contactDup = t.contact && o.contact && t.contact === o.contact;
        if (emailDup) return { by: "name+email" };
        if (contactDup) return { by: "name+contact" };
      }
    }
    return null;
  }

  function addOrganizerRowIfMissing(idx) {
    const wrap = el("organizers-wrapper");
    if (!wrap) return;
    while (wrap.children.length < idx + 1 && wrap.children.length < 3) addOrganizer();
  }

  function addOrganizer() {
    const wrap = el("organizers-wrapper");
    if (!wrap) return;

    if (wrap.children.length >= 3) {
      bsShow("organizerLimitModal");
      return;
    }

    const row = document.createElement("div");
    row.className = "organizer-row";
    row.innerHTML = `
      <input type="text" name="organizers[name][]" placeholder="Organizer Name" class="organizer-input" ${wrap.children.length === 0 ? "required" : ""}>
      <button type="button" class="org-btn org-btn-ghost" data-action="org-details" title="Details">
        <i class="fa-solid fa-pen-to-square"></i>
      </button>
      <button type="button" class="org-btn org-btn-danger" data-action="org-remove" title="Remove">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <input type="hidden" name="organizers[email][]" value="">
      <input type="hidden" name="organizers[contact][]" value="">
    `;
    wrap.appendChild(row);
  }

  function removeOrganizer(btnOrRow) {
    const rows = getOrganizerRows();
    if (rows.length <= 1) {
      bsShow("organizerMinimumModal");
      return;
    }

    const row = (btnOrRow instanceof HTMLElement && btnOrRow.classList.contains("organizer-row"))
      ? btnOrRow
      : btnOrRow.closest(".organizer-row");

    row?.remove();
  }

  function openOrganizerModal(btnOrRow) {
    const row = (btnOrRow instanceof HTMLElement && btnOrRow.classList.contains("organizer-row"))
      ? btnOrRow
      : btnOrRow.closest(".organizer-row");

    if (!row) return;
    activeOrganizerRow = row;

    if (el("orgEmail")) el("orgEmail").value = row.querySelector("input[name='organizers[email][]']")?.value || "";
    if (el("orgContact")) el("orgContact").value = row.querySelector("input[name='organizers[contact][]']")?.value || "";

    bsShow("organizerDetailsModal");
  }

  function initOrganizerEvents() {
    document.addEventListener("click", (e) => {
      const t = e.target.closest("[data-action]");
      if (!t) return;
      const action = t.getAttribute("data-action");

      if (action === "org-details") openOrganizerModal(t);
      if (action === "org-remove") removeOrganizer(t);
    });

    // keep your inline onclicks working
    window.addOrganizer = addOrganizer;
    window.removeOrganizer = (btn) => removeOrganizer(btn);
    window.openOrganizerModal = (btn) => openOrganizerModal(btn);

    document.addEventListener("blur", (e) => {
      const inp = e.target;
      if (!(inp instanceof HTMLInputElement)) return;
      if (inp.name !== "organizers[name][]") return;

      const row = inp.closest(".organizer-row");
      if (!row) return;

      const dup = findOrganizerDuplicate(row);
      if (dup) {
        showOrganizerDuplicate("Duplicate organizer: same name + same email/contact is not allowed.");
        inp.focus();
      }
    }, true);
  }

  function initOrganizerDetailsSave() {
    el("org-save-btn")?.addEventListener("click", () => {
      if (!activeOrganizerRow) return;

      const emailHidden = activeOrganizerRow.querySelector("input[name='organizers[email][]']");
      const contactHidden = activeOrganizerRow.querySelector("input[name='organizers[contact][]']");
      if (!emailHidden || !contactHidden) return;

      const prevEmail = emailHidden.value;
      const prevContact = contactHidden.value;

      const nextEmail = (el("orgEmail")?.value || "").trim();
      const nextContact = (el("orgContact")?.value || "").trim();

      emailHidden.value = nextEmail;
      contactHidden.value = nextContact;

      const dup = findOrganizerDuplicate(activeOrganizerRow);
      if (dup) {
        emailHidden.value = prevEmail;
        contactHidden.value = prevContact;
        showOrganizerDuplicate("Duplicate organizer: same name + same email/contact is not allowed.");
        return;
      }

      bsHide("organizerDetailsModal");
      bsShow("organizerSavedModal");
    });
  }

  // ============================================================
  // Custom Selects
  // ============================================================
  function closeAllSelects(except) {
    document.querySelectorAll(".custom-select.open").forEach(s => {
      if (except && s === except) return;
      s.classList.remove("open");
    });
  }

  function initCustomSelects() {
    const selects = document.querySelectorAll(".custom-select");
    selects.forEach(select => {
      const trigger = select.querySelector(".custom-select-trigger");
      const hidden = select.querySelector("input[type='hidden']");
      if (!trigger || !hidden) return;

      trigger.addEventListener("click", (e) => {
        e.stopPropagation();
        const willOpen = !select.classList.contains("open");
        closeAllSelects(select);
        select.classList.toggle("open", willOpen);
      });

      select.addEventListener("click", (e) => {
        const option = e.target.closest(".custom-option");
        if (!option) return;

        const value = option.dataset.value || "";
        const label = (option.dataset.label || option.textContent || "").trim();

        if (value === "__add_event_type__") {
          select.classList.remove("open");
          bsShow("eventTypeModal");
          refreshEventTypesList("");
          return;
        }

        hidden.value = value;

        const left = trigger.querySelector(".cs-left");
        if (left) left.textContent = label;
        else trigger.textContent = label;

        select.classList.remove("open");

        if (select.dataset.field === "location_id") {
          const districtVal = option.dataset.district || "";
          const dh = el("districtHidden");
          const dd = el("districtDisplay");
          if (dh) dh.value = districtVal;
          if (dd) dd.value = districtVal ? `District ${districtVal}` : "";
        }
      });

      if (select.classList.contains("searchable")) {
        const searchInput = select.querySelector(".search-box input");
        const searchIcon = select.querySelector(".search-icon");
        if (searchInput) {
          searchInput.addEventListener("click", (e) => e.stopPropagation());
          searchInput.addEventListener("keyup", () => {
            const q = (searchInput.value || "").toLowerCase().trim();
            const keywords = q.split(" ").filter(Boolean);

            if (q) {
              searchInput.classList.add("search-active");
              if (searchIcon) searchIcon.classList.add("tilt");
            } else {
              searchInput.classList.remove("search-active");
              if (searchIcon) searchIcon.classList.remove("tilt");
            }

            select.querySelectorAll(".custom-option").forEach(opt => {
              const v = opt.dataset.value || "";
              if (v === "__add_event_type__") { opt.style.display = "flex"; return; }
              const text = (opt.dataset.label || opt.textContent || "").toLowerCase();
              const ok = keywords.every(k => text.includes(k));
              opt.style.display = ok ? "flex" : "none";
            });
          });
        }
      }
    });

    document.addEventListener("click", () => closeAllSelects(null));
  }

  function seedSelectLabels() {
    const locVal = el("location_id_hidden")?.value;
    if (locVal) {
      const sel = document.querySelector("#barangay-select");
      const left = sel?.querySelector(".custom-select-trigger .cs-left");
      const opt = sel?.querySelector(`.custom-option[data-value='${CSS.escape(locVal)}']`);
      if (left && opt) left.textContent = (opt.dataset.label || opt.textContent).trim();

      const dist = opt?.dataset?.district || el("districtHidden")?.value;
      if (dist && el("districtHidden") && el("districtDisplay")) {
        el("districtHidden").value = dist;
        el("districtDisplay").value = `District ${dist}`;
      }
    }

    const typeVal = el("event_type_id_hidden")?.value;
    if (typeVal && typeVal !== "__add_event_type__") {
      const sel = document.querySelector("#event-type-select");
      const left = sel?.querySelector(".custom-select-trigger .cs-left");
      const opt = sel?.querySelector(`.custom-option[data-value='${CSS.escape(typeVal)}']`);
      if (left && opt) left.textContent = (opt.dataset.label || opt.textContent).trim();
    }
  }

  // ✅ NEW: update + auto-select event type after creation
  function setEventTypeSelection(typeId, label) {
    const hidden = el("event_type_id_hidden");
    const sel = document.querySelector("#event-type-select");
    const left = sel?.querySelector(".custom-select-trigger .cs-left");

    if (hidden) hidden.value = String(typeId || "");
    if (left) left.textContent = label || "Select Event Type";
  }

  // ============================================================
  // Manage Organizers modal (Directory search + assign + edit/delete)
  // ============================================================
  let activeAssignSlot = 0;

  function getSlotRow(slotIndex) {
    addOrganizerRowIfMissing(slotIndex);
    return getOrganizerRows()[slotIndex] || null;
  }

  function writeRowFromDb(row, org) {
    const nameInp = row.querySelector("input[name='organizers[name][]']");
    const emailInp = row.querySelector("input[name='organizers[email][]']");
    const contactInp = row.querySelector("input[name='organizers[contact][]']");

    if (nameInp) nameInp.value = org.name || "";
    if (emailInp) emailInp.value = org.email || "";
    if (contactInp) contactInp.value = org.contact || "";

    const dup = findOrganizerDuplicate(row);
    if (dup) {
      showOrganizerDuplicate("Duplicate organizer: same name + same email/contact is not allowed.");
      return false;
    }
    return true;
  }

  function setOrgDebug(obj) {
    debugLog("Organizer Directory Debug", obj);
  }

  async function fetchDbOrganizers(search) {
    const base = window.ORGANIZERS_API_URL;
    if (!base) {
      setOrgDebug({ ok: false, reason: "ORGANIZERS_API_URL missing" });
      showActionModal({ title: "Organizer API URL missing", message: "ORGANIZERS_API_URL is not set.", tone: "danger" });
      return [];
    }

    const url = new URL(base, window.location.origin);
    if (search) url.searchParams.set("search", search);

    let res;
    let rawText = "";

    try {
      res = await fetch(url.toString(), { headers: { "Accept": "application/json" }, credentials: "same-origin" });
      rawText = await res.text().catch(() => "");
    } catch (err) {
      setOrgDebug({ ok: false, stage: "network", url: url.toString(), error: String(err) });
      showActionModal({ title: "Organizer fetch failed", message: "Network error. Check console.", tone: "danger" });
      return [];
    }

    const contentType = res.headers.get("content-type") || "";
    setOrgDebug({ ok: res.ok, url: url.toString(), status: res.status, contentType, sample: rawText.slice(0, 600) });

    if (!res.ok) {
      showActionModal({ title: "Organizer fetch failed", message: `HTTP ${res.status}.`, tone: "danger" });
      return [];
    }
    if (!contentType.includes("application/json")) {
      showActionModal({ title: "Organizer fetch is not JSON", message: `Got "${contentType}".`, tone: "danger" });
      return [];
    }

    let data;
    try { data = JSON.parse(rawText); } catch {
      showActionModal({ title: "Organizer JSON parse error", message: "Invalid JSON response.", tone: "danger" });
      return [];
    }

    return Array.isArray(data) ? data : (data.data || []);
  }

  async function updateDirectoryOrganizer(organizerId, payload) {
    const base = window.ORGANIZER_UPDATE_URL;
    if (!base) throw new Error("ORGANIZER_UPDATE_URL is missing in Blade.");

    const url = `${base}/${organizerId}`;
    const res = await fetch(url, {
      method: "PUT",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": window.CSRF_TOKEN
      },
      credentials: "same-origin",
      body: JSON.stringify(payload)
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || `Update failed (HTTP ${res.status})`);
    return data;
  }

  async function deleteDirectoryOrganizer(organizerId) {
    const base = window.ORGANIZER_DELETE_URL;
    if (!base) throw new Error("ORGANIZER_DELETE_URL is missing in Blade.");

    const url = `${base}/${organizerId}`;
    const res = await fetch(url, {
      method: "DELETE",
      headers: {
        "Accept": "application/json",
        "X-CSRF-TOKEN": window.CSRF_TOKEN
      },
      credentials: "same-origin"
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || `Delete failed (HTTP ${res.status})`);
    return data;
  }

  function initOrganizerSlotSelector() {
    const bar = el("orgSlotBar");
    if (!bar) return;

    const buttons = Array.from(bar.querySelectorAll(".org-slot-btn"));
    const setActive = (slot) => {
      activeAssignSlot = slot;
      buttons.forEach(b => b.classList.toggle("active", parseInt(b.dataset.slot, 10) === slot));
    };

    buttons.forEach(btn => btn.addEventListener("click", () => setActive(parseInt(btn.dataset.slot, 10))));
    setActive(0);
  }

  function renderDbList(listEl, organizers) {
    listEl.innerHTML = "";

    if (!organizers.length) {
      listEl.innerHTML = `<div class="soft-sub">No organizers found.</div>`;
      return;
    }

    organizers.forEach(org => {
      const id = org.organizer_id;
      const name = org.name || "";
      const email = org.email || "";
      const contact = org.contact || "";
      const meta = [email, contact].filter(Boolean).join(" • ");

      const item = document.createElement("div");
      item.className = "db-org-item";

      item.innerHTML = `
        <button type="button" class="db-org-pill" data-action="assign" style="width:100%; text-align:left;">
          <div class="db-org-main">
            <div class="db-org-name">${escapeHtml(name)}</div>
            <div class="db-org-meta">${escapeHtml(meta || "No email/contact")}</div>
          </div>
          <div class="db-org-actions-label">Click to assign →</div>
        </button>

        <div class="db-org-tools">
          <button type="button" class="db-org-tool" title="Edit" data-action="edit">
            <i class="fa-solid fa-pen"></i>
          </button>
          <button type="button" class="db-org-tool db-org-tool--danger" title="Delete" data-action="delete">
            <i class="fa-solid fa-trash"></i>
          </button>
        </div>

        <div class="db-org-edit" style="display:none;">
          <div class="db-org-edit-grid">
            <input class="db-edit-name" type="text" placeholder="Name" value="${escapeHtml(name)}">
            <input class="db-edit-email" type="email" placeholder="Email (optional)" value="${escapeHtml(email)}">
            <input class="db-edit-contact" type="text" placeholder="Contact (optional)" value="${escapeHtml(contact)}">
          </div>
          <div class="db-org-edit-actions">
            <button type="button" class="db-org-tool" data-action="cancelEdit" title="Cancel">
              <i class="fa-solid fa-xmark"></i>
            </button>
            <button type="button" class="db-org-tool db-org-tool--ok" data-action="saveEdit" title="Save">
              <i class="fa-solid fa-check"></i>
            </button>
          </div>
        </div>
      `;

      const pill = item.querySelector("[data-action='assign']");
      const btnEdit = item.querySelector("[data-action='edit']");
      const btnDelete = item.querySelector("[data-action='delete']");
      const editWrap = item.querySelector(".db-org-edit");
      const nameInp = item.querySelector(".db-edit-name");
      const emailInp = item.querySelector(".db-edit-email");
      const contactInp = item.querySelector(".db-edit-contact");
      const btnCancelEdit = item.querySelector("[data-action='cancelEdit']");
      const btnSaveEdit = item.querySelector("[data-action='saveEdit']");

      pill?.addEventListener("click", () => {
        const row = getSlotRow(activeAssignSlot);
        if (!row) return;

        const ok = writeRowFromDb(row, { name, email, contact });
        if (!ok) return;

        showModalAfterHiding("manageOrganizersModal", "organizerSavedModal");
      });

      btnEdit?.addEventListener("click", () => {
        if (editWrap) editWrap.style.display = "";
        nameInp?.focus();
        nameInp?.select();
      });

      btnCancelEdit?.addEventListener("click", () => {
        if (nameInp) nameInp.value = name;
        if (emailInp) emailInp.value = email;
        if (contactInp) contactInp.value = contact;
        if (editWrap) editWrap.style.display = "none";
      });

      btnSaveEdit?.addEventListener("click", async () => {
        const nextName = (nameInp?.value || "").trim();
        const nextEmail = (emailInp?.value || "").trim() || null;
        const nextContact = (contactInp?.value || "").trim() || null;

        if (!nextName) {
          showActionModal({ title: "Invalid", message: "Name is required.", tone: "warning" });
          return;
        }

        btnSaveEdit.disabled = true;
        try {
          const updated = await updateDirectoryOrganizer(id, { name: nextName, email: nextEmail, contact: nextContact });

          org.name = updated.name ?? nextName;
          org.email = updated.email ?? nextEmail ?? "";
          org.contact = updated.contact ?? nextContact ?? "";

          item.querySelector(".db-org-name").textContent = org.name;
          item.querySelector(".db-org-meta").textContent = [org.email, org.contact].filter(Boolean).join(" • ") || "No email/contact";

          pill.onclick = () => {
            const row = getSlotRow(activeAssignSlot);
            if (!row) return;
            const ok = writeRowFromDb(row, { name: org.name, email: org.email || "", contact: org.contact || "" });
            if (!ok) return;
            showModalAfterHiding("manageOrganizersModal", "organizerSavedModal");
          };

          if (editWrap) editWrap.style.display = "none";
          showActionModal({ title: "Saved", message: "Organizer updated in directory.", tone: "info" });
        } catch (err) {
          showActionModal({ title: "Update failed", message: err.message || "Update failed", tone: "danger" });
        } finally {
          btnSaveEdit.disabled = false;
        }
      });

      btnDelete?.addEventListener("click", async () => {
        const ok = await confirmModal({
          title: "Delete organizer?",
          message: `Delete "${name}" from the directory?`,
          tone: "danger",
          confirmText: "Delete",
          cancelText: "Cancel"
        });
        if (!ok) return;

        btnDelete.disabled = true;
        try {
          await deleteDirectoryOrganizer(id);
          item.remove();
          showActionModal({ title: "Deleted", message: "Organizer deleted from directory.", tone: "info" });
        } catch (err) {
          showActionModal({ title: "Delete failed", message: err.message || "Delete failed", tone: "danger" });
        } finally {
          btnDelete.disabled = false;
        }
      });

      listEl.appendChild(item);
    });
  }

  function initManageOrganizersDb() {
    const openBtn = el("openManageOrganizersBtn");
    const modalId = "manageOrganizersModal";
    const searchInput = el("orgManageSearch");
    const listEl = el("orgManageList");

    if (!openBtn || !searchInput || !listEl) return;

    openBtn.addEventListener("click", async () => {
      bsShow(modalId);
      searchInput.value = "";
      listEl.innerHTML = `<div class="soft-sub">Loading organizers...</div>`;

      const data = await fetchDbOrganizers("");
      renderDbList(listEl, data);
      searchInput.focus();
    });

    let t = null;
    searchInput.addEventListener("input", () => {
      clearTimeout(t);
      t = setTimeout(async () => {
        listEl.innerHTML = `<div class="soft-sub">Searching...</div>`;
        const data = await fetchDbOrganizers(searchInput.value.trim());
        renderDbList(listEl, data);
      }, 250);
    });
  }

  // ============================================================
  // Create / Update form modals
  // ============================================================
  function initFormModals() {
    const form = el("create-event-form");

    // ✅ Only show confirm modal if valid; otherwise show native validation bubbles
    el("open-create-modal-btn")?.addEventListener("click", () => {
      if (!form) return bsShow("confirmModal");
      if (form.checkValidity()) return bsShow("confirmModal");
      form.reportValidity();
    });

    el("confirm-create-btn")?.addEventListener("click", () => form?.submit());

    el("dup-confirm-btn")?.addEventListener("click", () => {
      const force = el("force_create");
      if (force) force.value = "1";
      bsHide("duplicateModal");
      form?.submit();
    });
  }

  function initMultiDayMin() {
    const start = el("start_datetime");
    const end = el("end_datetime");
    if (!start || !end) return;

    const sync = () => {
      if (!start.value) return;
      end.min = start.value;
      if (end.value && end.value < start.value) end.value = "";
    };

    start.addEventListener("input", sync);
    start.addEventListener("change", sync);
    sync();
  }

  // ============================================================
  // EVENT TYPE MANAGER
  // ============================================================
  async function fetchEventTypes(search) {
    const base = window.EVENT_TYPES_API_URL;
    if (!base) return [];

    const url = new URL(base, window.location.origin);
    if (search) url.searchParams.set("search", search);

    const res = await fetch(url.toString(), {
      headers: { "Accept": "application/json" },
      credentials: "same-origin"
    });

    const contentType = res.headers.get("content-type") || "";
    if (!contentType.includes("application/json")) return [];
    if (!res.ok) return [];

    const data = await res.json().catch(() => []);
    return Array.isArray(data) ? data : [];
  }

  async function updateEventType(id, label) {
    const url = `${window.EVENT_TYPE_UPDATE_URL}/${id}`;
    const res = await fetch(url, {
      method: "PUT",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": window.CSRF_TOKEN
      },
      credentials: "same-origin",
      body: JSON.stringify({ label })
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || "Update failed");
    return data;
  }

  async function deleteEventType(id) {
    const url = `${window.EVENT_TYPE_DELETE_URL}/${id}`;
    const res = await fetch(url, {
      method: "DELETE",
      headers: {
        "Accept": "application/json",
        "X-CSRF-TOKEN": window.CSRF_TOKEN
      },
      credentials: "same-origin"
    });

    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(data.message || "Delete failed");
    return data;
  }

  async function createEventType(label) {
    const url = window.EVENT_TYPE_STORE_URL;
    if (!url) throw new Error("EVENT_TYPE_STORE_URL is missing.");

    const formData = new FormData();
    formData.append("label", label);
    formData.append("_token", window.CSRF_TOKEN);

    const res = await fetch(url, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
      headers: { "Accept": "application/json" }
    });

    const ct = res.headers.get("content-type") || "";
    const data = ct.includes("application/json")
      ? await res.json().catch(() => ({}))
      : { message: "Non-JSON response from server." };

    if (!res.ok) throw new Error(data.message || "Failed to add event type.");
    return data;
  }

  function renderEventTypes(listEl, types) {
    listEl.innerHTML = "";

    if (!types.length) {
      listEl.innerHTML = `<div class="soft-sub">No event types found.</div>`;
      return;
    }

    types.forEach(t => {
      const wrap = document.createElement("div");
      wrap.className = "org-manage-item";

      wrap.innerHTML = `
        <div class="org-manage-row" style="grid-template-columns: 1.6fr 1fr auto;">
          <div style="display:flex; flex-direction:column; gap:4px; min-width:0;">
            <div style="font-weight:950; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
              <span class="et-label">${escapeHtml(t.label)}</span>
            </div>
            <div style="font-size:.86rem; color:rgba(17,24,39,.55); font-weight:800;">
              Event Type
            </div>
          </div>

          <input class="et-edit-input" type="text" value="${escapeHtml(t.label)}" style="display:none;">

          <div class="org-manage-actions">
            <button type="button" class="org-mini-btn et-edit" title="Edit"><i class="fa-solid fa-pen"></i></button>
            <button type="button" class="org-mini-btn et-save" title="Save" style="display:none;"><i class="fa-solid fa-check"></i></button>
            <button type="button" class="org-mini-btn et-cancel" title="Cancel" style="display:none;"><i class="fa-solid fa-xmark"></i></button>
            <button type="button" class="org-mini-btn org-mini-btn--danger et-del" title="Delete"><i class="fa-solid fa-trash"></i></button>
          </div>
        </div>
      `;

      const labelSpan = wrap.querySelector(".et-label");
      const input = wrap.querySelector(".et-edit-input");
      const btnEdit = wrap.querySelector(".et-edit");
      const btnSave = wrap.querySelector(".et-save");
      const btnCancel = wrap.querySelector(".et-cancel");
      const btnDel = wrap.querySelector(".et-del");

      const setEditMode = (on) => {
        if (!input || !btnSave || !btnCancel || !btnEdit) return;
        if (on) {
          input.style.display = "";
          btnSave.style.display = "";
          btnCancel.style.display = "";
          btnEdit.style.display = "none";
          if (labelSpan) labelSpan.style.display = "none";
          input.focus();
          input.select();
        } else {
          input.style.display = "none";
          btnSave.style.display = "none";
          btnCancel.style.display = "none";
          btnEdit.style.display = "";
          if (labelSpan) labelSpan.style.display = "";
        }
      };

      btnEdit?.addEventListener("click", () => setEditMode(true));
      btnCancel?.addEventListener("click", () => {
        if (input) input.value = t.label;
        setEditMode(false);
      });

      btnSave?.addEventListener("click", async () => {
        const next = (input?.value || "").trim();
        if (!next) {
          showActionModal({ title: "Invalid label", message: "Label cannot be empty.", tone: "warning" });
          return;
        }

        btnSave.disabled = true;
        try {
          const updated = await updateEventType(t.event_type_id, next);
          t.label = updated.label || next;
          if (labelSpan) labelSpan.textContent = t.label;
          setEditMode(false);
          await refreshEventTypeDropdownOptions();
        } catch (err) {
          showActionModal({ title: "Update failed", message: err.message || "Update failed", tone: "danger" });
        } finally {
          btnSave.disabled = false;
        }
      });

      btnDel?.addEventListener("click", async () => {
        const ok = await confirmModal({
          title: "Delete event type?",
          message: `Delete "${t.label}"?\n\nThis will be blocked if it is used by events.`,
          tone: "danger",
          confirmText: "Delete",
          cancelText: "Cancel"
        });
        if (!ok) return;

        btnDel.disabled = true;
        try {
          await deleteEventType(t.event_type_id);
          wrap.remove();
          await refreshEventTypeDropdownOptions();
        } catch (err) {
          showActionModal({ title: "Delete failed", message: err.message || "Delete failed", tone: "danger" });
        } finally {
          btnDel.disabled = false;
        }
      });

      listEl.appendChild(wrap);
    });
  }

  async function refreshEventTypesList(search) {
    const listEl = el("eventTypeManageList");
    if (!listEl) return;
    listEl.innerHTML = `<div class="soft-sub">Loading event types...</div>`;
    const types = await fetchEventTypes(search || "");
    renderEventTypes(listEl, types);
  }

  async function refreshEventTypeDropdownOptions() {
    const optionsWrap = document.querySelector("#event-type-select .custom-options");
    if (!optionsWrap) return;

    const types = await fetchEventTypes("");

    optionsWrap.querySelectorAll(".custom-option").forEach(opt => {
      const v = opt.dataset.value || "";
      if (v === "__add_event_type__") return;
      opt.remove();
    });

    types.forEach(t => {
      const opt = document.createElement("span");
      opt.className = "custom-option";
      opt.dataset.value = String(t.event_type_id);
      opt.dataset.label = t.label;
      opt.textContent = t.label;
      optionsWrap.appendChild(opt);
    });

    seedSelectLabels();
  }

  function initEventTypeManager() {
    const search = el("eventTypeManageSearch");
    const modalEl = el("eventTypeModal");

    // ✅ Add New modal
    const addBtn = el("eventTypeAddNewBtn");
    const addInput = el("eventTypeAddLabel");
    const addSaveBtn = el("eventTypeAddSaveBtn");

    if (search) {
      let t = null;
      search.addEventListener("input", () => {
        clearTimeout(t);
        t = setTimeout(() => refreshEventTypesList(search.value.trim()), 250);
      });
    }

    if (modalEl) {
      modalEl.addEventListener("shown.bs.modal", () => {
        if (search) search.value = "";
        refreshEventTypesList("");
        search?.focus();
      });
    }

    // open add modal
    addBtn?.addEventListener("click", () => {
      bsShow("eventTypeAddModal");
      setTimeout(() => {
        if (addInput) {
          addInput.value = "";
          addInput.focus();
        }
      }, 50);
    });

    // save new type
    const doSave = async () => {
      const label = (addInput?.value || "").trim();
      if (!label) {
        showActionModal({ title: "Missing label", message: "Please enter an event type label.", tone: "warning" });
        addInput?.focus();
        return;
      }

      if (!window.EVENT_TYPE_STORE_URL || !window.CSRF_TOKEN) {
        showActionModal({ title: "Config missing", message: "EVENT_TYPE_STORE_URL / CSRF_TOKEN not set.", tone: "danger" });
        return;
      }

      if (addSaveBtn) addSaveBtn.disabled = true;

      try {
        const created = await createEventType(label);

        const createdId =
          created?.event_type_id ??
          created?.id ??
          created?.data?.event_type_id ??
          created?.data?.id ??
          null;

        const createdLabel =
          created?.label ??
          created?.data?.label ??
          label;

        bsHide("eventTypeAddModal");

        await refreshEventTypesList(search?.value?.trim() || "");
        await refreshEventTypeDropdownOptions();

        if (createdId) setEventTypeSelection(createdId, createdLabel);

        showActionModal({ title: "Saved", message: "Event type added successfully.", tone: "info" });
      } catch (err) {
        showActionModal({ title: "Add failed", message: err.message || "Failed to add event type.", tone: "danger" });
      } finally {
        if (addSaveBtn) addSaveBtn.disabled = false;
      }
    };

    addSaveBtn?.addEventListener("click", doSave);
    addInput?.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        doSave();
      }
    });
  }

  // ============================================================
  // Boot
  // ============================================================
  document.addEventListener("DOMContentLoaded", () => {
    initCustomSelects();
    seedSelectLabels();

    initFormModals();
    initOrganizerEvents();
    initOrganizerDetailsSave();

    initManageOrganizersDb();
    initOrganizerSlotSelector();

    initMultiDayMin();
    initEventTypeManager();
    initConfirmModalHandlers();
  });
})();
