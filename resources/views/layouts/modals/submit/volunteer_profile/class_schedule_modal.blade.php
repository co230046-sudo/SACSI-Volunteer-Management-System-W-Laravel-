@php
  /**
   * Volunteer Profile - Class Schedule Modal
   * - Mirrors the reference behavior
   * - Writes schedule into #vpScheduleField
   * - Returns to #editVolunteerModal after close/save
   */
@endphp

<style>
#vpClassScheduleModal .modal-dialog { max-width: 1240px; }
@media (min-width: 1600px){ #vpClassScheduleModal .modal-dialog { max-width: 1360px; } }
@media (max-width: 1200px){ #vpClassScheduleModal .modal-dialog { max-width: 1140px; } }

#vpClassScheduleModal .weekly-schedule{ overflow-x:auto; }
#vpClassScheduleModal .schedule-table{
  width:100%;
  min-width: 1120px;
  border-collapse:collapse;
  font-size:.9rem;
  table-layout: fixed;
}

#vpClassScheduleModal .custom-schedule-modal{ border-radius: 18px; overflow: hidden; font-family: 'Segoe UI', Roboto, sans-serif; }

#vpClassScheduleModal .custom-modal-header{
  background:#9b2733; color:#fff; border-bottom:1px solid #7f1d26;
}
#vpClassScheduleModal .custom-modal-header .modal-title{ font-weight:700; font-size:1.25rem; }
#vpClassScheduleModal .btn-close{ filter: invert(1) grayscale(100%); }

#vpClassScheduleModal .custom-modal-body{ background:#fff; padding:1.15rem 1.5rem 1rem; }

#vpClassScheduleModal .schedule-hint{
  border-radius:10px; border:1px dashed #f3c2c7; background:#fff5f6;
  padding:.55rem .85rem; font-size:.85rem; color:#5c1b24; margin-bottom:.9rem;
}

#vpClassScheduleModal .schedule-table th,
#vpClassScheduleModal .schedule-table td{
  border:1px solid #f0d0d3; padding:.45rem .5rem;
}
#vpClassScheduleModal .schedule-table thead th{
  background:#9b2733; color:#fff; font-weight:600;
}
#vpClassScheduleModal .schedule-table thead th:first-child{ width:46px; }
#vpClassScheduleModal .schedule-table thead th:last-child{ width:80px; }

#vpClassScheduleModal .schedule-time{ font-weight:600; color:#7f1d26; }

#vpClassScheduleModal .schedule-entry{ font-size:.88rem; background:#ffffff; transition: background-color .15s ease, border-color .15s ease; }
#vpClassScheduleModal .schedule-entry.sched-empty{ background:#ffffff; }
#vpClassScheduleModal .schedule-entry.sched-ok{ background:#e6f9ea; border-color:#c3e6cb; }
#vpClassScheduleModal .schedule-entry.sched-conflict{ background:#ffe6e6; border-color:#f5c2c7; }

#vpClassScheduleModal .custom-modal-footer{
  background:#fafafa; border-top:1px solid #eee; padding:.8rem 1.5rem;
}
#vpClassScheduleModal .btn-add-row{ background:#b2000c; border-color:#b2000c; }
#vpClassScheduleModal .btn-add-row:hover{ background:#8e0009; border-color:#8e0009; }
#vpClassScheduleModal .btn-crimson{ background:#b2000c; border-color:#b2000c; }
#vpClassScheduleModal .btn-crimson:hover{ background:#8e0009; border-color:#8e0009; }

/* Portal dropdown */
#vpClassScheduleModal select.schedule-select{
  position:absolute !important;
  left:-99999px !important;
  width:1px !important;
  height:1px !important;
  opacity:0 !important;
  pointer-events:none !important;
}
#vpClassScheduleModal .vpSch-combo{ position:relative; width:100%; }
#vpClassScheduleModal .vpSch-input{
  width:100%;
  min-height:32px;
  border-radius:999px;
  border:1px solid #c53c45;
  background:#fff;
  color:#5c1b24;
  font-weight:900;
  font-size:.80rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding:.25rem 2.0rem .25rem .70rem;
  outline:none;
  cursor:pointer;
  background-image:
    linear-gradient(45deg, transparent 50%, #9b2733 50%),
    linear-gradient(135deg, #9b2733 50%, transparent 50%);
  background-position: calc(100% - 14px) 11px, calc(100% - 10px) 11px;
  background-size:5px 5px, 5px 5px;
  background-repeat:no-repeat;
}
#vpClassScheduleModal .vpSch-input:focus{
  border-color:#9b2733;
  box-shadow:0 0 0 2px rgba(155,39,51,.18);
}

#vpSchPortal{
  position:fixed;
  z-index:30000;
  display:none;
  background:#fff;
  border:1px solid rgba(15,23,42,.12);
  border-radius:14px;
  box-shadow:0 18px 44px rgba(2,6,23,.18);
  overflow:hidden;
}
#vpSchPortal .vpSch-portalBody{ max-height:320px; overflow:auto; }

#vpSchPortal .vpSch-item{
  width:100%;
  border:0;
  background:transparent;
  text-align:left;
  padding:10px 12px;
  font-weight:900;
  font-size:.84rem;
  color:#111827;
}
#vpSchPortal .vpSch-item:hover{ background: rgba(225,29,72,.06); }
#vpSchPortal .vpSch-item.is-active{ background: rgba(225,29,72,.10); }

#vpSchPortal .vpSch-group{
  padding:8px 12px;
  font-size:12px;
  font-weight:1000;
  color:#7f1d26;
  background: rgba(162,52,63,.08);
  border-top:1px solid rgba(15,23,42,.08);
}

#vpClassScheduleModal .vpSchErr{
  display:none;
  color:#b91c1c;
  font-weight:950;
  font-size:12px;
  margin-right:.6rem;
}
</style>

<div class="modal fade" id="vpClassScheduleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content custom-schedule-modal">

      <div class="modal-header custom-modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title mb-0">
          <i class="fa-solid fa-calendar-days me-2"></i> Class Schedule
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body custom-modal-body">
        <div class="schedule-hint">
          In <strong>Edit</strong> mode, update the time slots as needed.
          <span class="ms-2 text-decoration-underline" style="cursor:help;"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="Red cells indicate overlapping schedule times.">
            Why are some cells red?
          </span>
        </div>

        <div class="weekly-schedule">
          <table class="table schedule-table text-center align-middle mb-0">
            <thead>
              <tr>
                <th>#</th>
                <th>Monday</th>
                <th>Tuesday</th>
                <th>Wednesday</th>
                <th>Thursday</th>
                <th>Friday</th>
                <th>Saturday</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="vpScheduleContent"></tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer custom-modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-add-row btn-sm text-white" id="vpAddRowBtnFooter">
          <i class="fa-solid fa-plus me-1"></i> Add Row
        </button>

        <div class="d-flex align-items-center">
          <span class="vpSchErr" id="vpScheduleGuardMsg"></span>

          <button type="button" class="btn btn-secondary btn-sm" id="vpEditScheduleBtn">
            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
          </button>
          <button type="button" class="btn btn-success btn-sm d-none" id="vpSaveScheduleBtn">
            <i class="fa-solid fa-save me-1"></i> Save
          </button>

          <button type="button" class="btn btn-crimson btn-sm text-white ms-2" data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark me-1"></i> Close
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<div id="vpSchPortal" aria-hidden="true">
  <div class="vpSch-portalBody"></div>
</div>

<script>
(function(){
  const MAX_ROWS = 6;
  const DEFAULT_ROWS = 3;
  const days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

  const timeMeta = {
    "7:30-8:20":  { label:"7:30–8:20 AM",  group:"AM", start:450,  end:500 },
    "8:00-9:20":  { label:"8:00–9:20 AM",  group:"AM", start:480,  end:560 },
    "8:00-10:50": { label:"8:00–10:50 AM", group:"AM", start:480,  end:650 },
    "8:30-9:50":  { label:"8:30–9:50 AM",  group:"AM", start:510,  end:590 },
    "8:30-11:30": { label:"8:30–11:30 AM", group:"AM", start:510,  end:690 },
    "9:30-10:50": { label:"9:30–10:50 AM", group:"AM", start:570,  end:650 },
    "11:00-12:20":{ label:"11:00–12:20 PM", group:"AM", start:660,  end:740 },

    "12:30-1:50": { label:"12:30–1:50 PM", group:"PM", start:750,  end:830 },
    "12:30-2:50": { label:"12:30–2:50 PM", group:"PM", start:750,  end:890 },
    "2:00-3:20":  { label:"2:00–3:20 PM",  group:"PM", start:840,  end:920 },
    "2:00-4:50":  { label:"2:00–4:50 PM",  group:"PM", start:840,  end:1010},
    "3:30-4:50":  { label:"3:30–4:50 PM",  group:"PM", start:930,  end:1010},
    "5:00-6:20":  { label:"5:00–6:20 PM",  group:"PM", start:1020, end:1100},
    "6:30-7:20":  { label:"6:30–7:20 PM",  group:"PM", start:1110, end:1160},
    "6:30-8:50":  { label:"6:30–8:50 PM",  group:"PM", start:1110, end:1250},

    "7:30-8:50":  { label:"7:30–8:50 AM",  group:"AM", start:450,  end:530 }
  };

  const timeOptions = Object.keys(timeMeta);
  let isEditing = false;
  let baselineSchedule = "";
  let returnToEditModal = false;

  const modalEl = document.getElementById("vpClassScheduleModal");
  const bodyEl  = document.getElementById("vpScheduleContent");
  const guardMsg = document.getElementById("vpScheduleGuardMsg");

  const portal = document.getElementById("vpSchPortal");
  const portalBody = portal ? portal.querySelector(".vpSch-portalBody") : null;

  let ownerTd = null;
  let ownerInput = null;

  function showGuard(msg){
    if (!guardMsg) return;
    guardMsg.textContent = msg || "";
    guardMsg.style.display = msg ? "inline-block" : "none";
  }

  function normalizeTimeRange(str) {
    if (!str) return "";
    str = String(str).replace(/[,;]+/g," ").trim();
    const p = str.split("-").map(s => s.trim());
    if (p.length !== 2) return str;
    const fix = t => /^\d{1,2}$/.test(t) ? t + ":00" : t;
    const n = `${fix(p[0])}-${fix(p[1])}`;
    return timeMeta[n] ? n : n;
  }

  function displayLabel(key) {
    const k = normalizeTimeRange(key);
    return timeMeta[k] ? timeMeta[k].label : (key || "");
  }

  function parseRange(str) {
    const key = normalizeTimeRange(str);
    if (!key.includes("-")) return null;
    if (timeMeta[key]) return { start: timeMeta[key].start, end: timeMeta[key].end };

    const [s,e] = key.split("-");
    const [sh,sm] = s.split(":").map(Number);
    const [eh,em] = e.split(":").map(Number);
    if ([sh,sm,eh,em].some(isNaN)) return null;
    return { start: sh*60+sm, end: eh*60+em };
  }

  function rangesOverlap(a,b) {
    a = parseRange(a);
    b = parseRange(b);
    return a && b && a.start < b.end && b.start < a.end;
  }

  function markCellState(td, value) {
    td.classList.remove("sched-empty","sched-ok","sched-conflict");
    if (!value) td.classList.add("sched-empty");
    else td.classList.add("sched-ok");
  }

  function recomputeHighlights() {
    const rows = Array.from(bodyEl.querySelectorAll("tr"));
    bodyEl.querySelectorAll("td.schedule-entry").forEach(td => {
      td.classList.remove("sched-ok","sched-conflict");
      if (!String(td.dataset.value||"").trim()) td.classList.add("sched-empty");
    });

    days.forEach((day, di) => {
      const cells = rows.map(r => r.querySelectorAll("td.schedule-entry")[di]);
      const items = [];

      cells.forEach(cell => {
        if (!cell) return;
        const norm = normalizeTimeRange(String(cell.dataset.value||"").trim());
        const range = parseRange(norm);
        if (norm && range) items.push({ cell, start: range.start, end: range.end });
      });

      items.sort((a,b) => a.start - b.start);

      let prev = null;
      items.forEach(it => {
        if (prev && it.start < prev.end) {
          it.cell.classList.add("sched-conflict");
          prev.cell.classList.add("sched-conflict");
          prev = (it.end > prev.end) ? it : prev;
        } else {
          it.cell.classList.add("sched-ok");
          prev = it;
        }
      });
    });
  }

  function sortColumn(colIdx) {
    const rows = Array.from(bodyEl.querySelectorAll("tr"));

    const values = [];
    rows.forEach(row => {
      const td = row.querySelectorAll("td.schedule-entry")[colIdx];
      if (!td) return;
      const norm = normalizeTimeRange(String(td.dataset.value||"").trim());
      if (norm) values.push(norm);
    });

    values.sort((a,b) => (parseRange(a)?.start||0) - (parseRange(b)?.start||0));

    let k = 0;
    rows.forEach(row => {
      const td = row.querySelectorAll("td.schedule-entry")[colIdx];
      if (!td) return;
      const val = k < values.length ? values[k++] : "";

      td.dataset.value = val;

      if (isEditing) {
        const input = td.querySelector("input.vpSch-input");
        if (input) input.value = val ? displayLabel(val) : "No Class";
        const sel = td.querySelector("select.schedule-select");
        if (sel) sel.value = val || "";
      } else {
        td.textContent = displayLabel(val);
      }

      markCellState(td, val);
    });

    recomputeHighlights();
  }

  function sortAllColumns() {
    for (let i = 0; i < days.length; i++) sortColumn(i);
  }

  function closePortal(){
    if (!portal) return;
    portal.style.display = "none";
    portal.setAttribute("aria-hidden","true");
    if (portalBody) portalBody.innerHTML = "";
    ownerTd = null;
    ownerInput = null;
  }

  function getSelectedForDay(day, excludeTd){
    return Array.from(bodyEl.querySelectorAll(`td.schedule-entry[data-day="${day}"]`))
      .filter(td => td !== excludeTd)
      .map(td => String(td.dataset.value||"").trim())
      .filter(Boolean);
  }

  function positionPortal(input){
    if (!portal) return;
    const r = input.getBoundingClientRect();
    const gap = 8;
    const maxW = Math.min(520, window.innerWidth - 20);
    const left = Math.min(Math.max(10, r.left), window.innerWidth - 10 - maxW);
    const top = r.bottom + gap;

    portal.style.left = left + "px";
    portal.style.top = top + "px";
    portal.style.minWidth = r.width + "px";
    portal.style.maxWidth = maxW + "px";
    portal.style.display = "block";
    portal.setAttribute("aria-hidden","false");
  }

  function renderPortalFor(input, td){
    ownerTd = td;
    ownerInput = input;

    const day = td.getAttribute("data-day");
    const cur = normalizeTimeRange(String(td.dataset.value||"").trim());
    const picked = day ? getSelectedForDay(day, td).map(normalizeTimeRange) : [];

    if (!portalBody) return;
    portalBody.innerHTML = "";

    const frag = document.createDocumentFragment();

    function mkItem(value, label, active, disabled){
      const b = document.createElement("button");
      b.type = "button";
      b.className = "vpSch-item" + (active ? " is-active" : "");
      b.dataset.value = value;
      b.disabled = !!disabled;
      b.textContent = label;
      if (b.disabled){
        b.style.opacity = "0.45";
        b.style.cursor = "not-allowed";
      }
      frag.appendChild(b);
    }

    mkItem("", "No Class", !cur, false);

    const am = [];
    const pm = [];
    timeOptions.forEach(k => (timeMeta[k].group === "AM" ? am : pm).push(k));
    const byStart = (a,b)=> timeMeta[a].start - timeMeta[b].start;
    am.sort(byStart); pm.sort(byStart);

    function renderGroup(title, keys){
      if (!keys.length) return;
      const g = document.createElement("div");
      g.className = "vpSch-group";
      g.textContent = title;
      frag.appendChild(g);

      keys.forEach(k => {
        const label = timeMeta[k].label;
        let disabled = false;
        if (k !== cur) disabled = picked.some(v => rangesOverlap(v, k));
        mkItem(k, label, k === cur, disabled);
      });
    }

    renderGroup("Morning", am);
    renderGroup("Afternoon / Evening", pm);

    portalBody.appendChild(frag);
    positionPortal(input);
  }

  portal?.addEventListener("mousedown", (e) => {
    const btn = e.target.closest(".vpSch-item");
    if (!btn || !ownerTd || !ownerInput) return;
    if (btn.disabled) return;

    e.preventDefault();
    e.stopPropagation();

    const val = normalizeTimeRange(String(btn.dataset.value||"").trim());
    ownerTd.dataset.value = val;

    ownerInput.value = val ? displayLabel(val) : "No Class";

    const sel = ownerTd.querySelector("select.schedule-select");
    if (sel) sel.value = val || "";

    const colIdx = days.indexOf(String(ownerTd.getAttribute("data-day")||""));
    if (colIdx >= 0) sortColumn(colIdx);
    recomputeHighlights();

    showGuard("");
    closePortal();
  });

  document.addEventListener("mousedown", (e) => {
    if (!portal || portal.style.display !== "block") return;
    const insidePortal = portal.contains(e.target);
    const insideOwner = ownerInput && ownerInput.contains(e.target);
    if (!insidePortal && !insideOwner) closePortal();
  }, true);

  window.addEventListener("scroll", () => closePortal(), { passive:true });
  window.addEventListener("resize", () => closePortal());

  modalEl?.addEventListener("hidden.bs.modal", () => {
    closePortal();
    showGuard("");

    // ✅ Return to edit modal (NOT addVolunteerModal)
    if (returnToEditModal) {
      returnToEditModal = false;
      const editEl = document.getElementById("editVolunteerModal");
      if (editEl) {
        try { bootstrap.Modal.getOrCreateInstance(editEl).show(); } catch {}
      }
    }
  });

  function formatSchedule(obj) {
    return days.map(day => {
      const arr = obj[day];
      if (!arr.length) return `${day}: No Class`;
      return `${day}: ${arr.join(" ")}`;
    }).join(" ");
  }

  function computeCurrentScheduleString(){
    const updated = {};
    days.forEach(d => updated[d] = []);

    bodyEl.querySelectorAll("tr").forEach((row, ri) => {
      row.querySelectorAll("td.schedule-entry").forEach((td, di) => {
        const val = normalizeTimeRange(String(td.dataset.value||"").trim());
        updated[days[di]][ri] = val;
      });
    });

    days.forEach(d => {
      updated[d] = updated[d].filter(v => v).sort((a,b)=> (parseRange(a)?.start||0)-(parseRange(b)?.start||0));
    });

    return formatSchedule(updated);
  }

  function hasAnySelection(){
    return Array.from(bodyEl.querySelectorAll("td.schedule-entry"))
      .some(td => String(td.dataset.value||"").trim() !== "");
  }

  function addRow(data = {}) {
    if (bodyEl.children.length >= MAX_ROWS) {
      showGuard("You can only add up to " + MAX_ROWS + " rows.");
      return;
    }

    const tr = document.createElement("tr");

    const idx = document.createElement("td");
    idx.className = "schedule-time";
    idx.textContent = bodyEl.children.length + 1;
    tr.appendChild(idx);

    days.forEach(day => {
      const td = document.createElement("td");
      td.className = "schedule-entry";
      td.setAttribute("data-day", day);

      const val = normalizeTimeRange(data[day] || "");
      td.dataset.value = val;
      td.textContent = displayLabel(val);
      markCellState(td, val);

      tr.appendChild(td);
    });

    const tdDel = document.createElement("td");
    const btn   = document.createElement("button");
    btn.type    = "button";
    btn.className = "btn btn-sm btn-danger btn-row-delete";
    btn.innerHTML = "<i class='fa-solid fa-trash'></i>";
    btn.onclick = () => {
      tr.remove();
      Array.from(bodyEl.querySelectorAll(".schedule-time")).forEach((c,i)=> c.textContent = i+1);
      sortAllColumns();
      recomputeHighlights();
      showGuard("");
    };
    tdDel.appendChild(btn);
    tr.appendChild(tdDel);

    bodyEl.appendChild(tr);

    if (isEditing) {
      tr.querySelectorAll("td.schedule-entry").forEach((td, di) => createSelectInCell(td, di));
    }

    recomputeHighlights();
  }

  function loadScheduleIntoModal(scheduleString) {
    bodyEl.innerHTML = "";

    const scheduleData = {};
    days.forEach(day => {
      const regex = new RegExp(`${day}:([^]*?)(?=Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|$)`,"i");
      const match = scheduleString.match(regex);
      let raw = match ? match[1].trim() : "";
      raw = raw.replace(/No Class/gi,"").replace(/[,;]+/g," ").trim();
      scheduleData[day] = raw ? raw.split(/\s+/).map(normalizeTimeRange) : [];
      scheduleData[day].sort((a,b) => (parseRange(a)?.start||0) - (parseRange(b)?.start||0));
    });

    let rows = Math.max(...days.map(d => scheduleData[d].length));
    rows = Math.max(DEFAULT_ROWS, Math.min(MAX_ROWS, rows || DEFAULT_ROWS));

    for (let i = 0; i < rows; i++) {
      const row = {};
      days.forEach(d => row[d] = scheduleData[d][i] || "");
      addRow(row);
    }

    sortAllColumns();
    recomputeHighlights();
  }

  function createSelectInCell(td, colIdx) {
    const day = days[colIdx];
    td.setAttribute("data-day", day);

    const cur = normalizeTimeRange(td.dataset.value || td.textContent.trim());
    td.textContent = "";

    const wrap = document.createElement("div");
    wrap.className = "vpSch-combo";

    const input = document.createElement("input");
    input.type = "text";
    input.readOnly = true;
    input.className = "vpSch-input";
    input.value = cur ? displayLabel(cur) : "No Class";

    input.addEventListener("mousedown", (e) => {
      e.preventDefault();
      e.stopPropagation();
      showGuard("");
      renderPortalFor(input, td);
    });

    const select = document.createElement("select");
    select.className = "schedule-select form-select form-select-sm";

    const blank = document.createElement("option");
    blank.value = "";
    blank.textContent = "No Class";
    select.appendChild(blank);

    const groupAM = document.createElement("optgroup");
    const groupPM = document.createElement("optgroup");
    groupAM.label = "Morning";
    groupPM.label = "Afternoon / Evening";

    timeOptions.forEach(key => {
      const meta = timeMeta[key];
      const og   = meta.group === "AM" ? groupAM : groupPM;
      const option = document.createElement("option");
      option.value = key;
      option.textContent = meta.label;
      og.appendChild(option);
    });

    select.appendChild(groupAM);
    select.appendChild(groupPM);

    select.value = cur || "";
    td.dataset.value = cur || "";
    markCellState(td, cur || "");

    wrap.appendChild(input);
    td.appendChild(wrap);
    td.appendChild(select);
  }

  function seedFromHidden(){
    const hidden = document.getElementById("vpScheduleField");
    const s = hidden ? hidden.value : "";
    isEditing = false;
    loadScheduleIntoModal(String(s||"").trim());
    baselineSchedule = String(computeCurrentScheduleString() || "").trim();

    document.getElementById("vpEditScheduleBtn")?.classList.remove("d-none");
    document.getElementById("vpSaveScheduleBtn")?.classList.add("d-none");
    showGuard("");

    try {
      modalEl.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    } catch {}
  }

  modalEl?.addEventListener("show.bs.modal", seedFromHidden);
  modalEl?.addEventListener("shown.bs.modal", seedFromHidden);

  document.getElementById("vpAddRowBtnFooter")?.addEventListener("click", () => addRow());

  document.getElementById("vpEditScheduleBtn")?.addEventListener("click", () => {
    isEditing = true;

    bodyEl.querySelectorAll("tr").forEach(row => {
      row.querySelectorAll("td.schedule-entry").forEach((td, di) => createSelectInCell(td, di));
    });

    sortAllColumns();
    recomputeHighlights();

    document.getElementById("vpEditScheduleBtn").classList.add("d-none");
    document.getElementById("vpSaveScheduleBtn").classList.remove("d-none");
    showGuard("");
  });

  document.getElementById("vpSaveScheduleBtn")?.addEventListener("click", () => {
    sortAllColumns();
    recomputeHighlights();

    if (!hasAnySelection()){
      showGuard("Please select at least one class schedule block before saving.");
      return;
    }

    const nowSchedule = String(computeCurrentScheduleString() || "").trim();
    if (nowSchedule === baselineSchedule){
      showGuard("No changes detected. Update at least one time slot before saving.");
      return;
    }

    const final = nowSchedule;
    const hidden = document.getElementById("vpScheduleField");
    if (hidden) hidden.value = final;

    window.dispatchEvent(new Event("vp:schedule-updated"));

    returnToEditModal = !!document.getElementById("editVolunteerModal");
    try { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); } catch {}

    document.getElementById("vpSaveScheduleBtn").classList.add("d-none");
    document.getElementById("vpEditScheduleBtn").classList.remove("d-none");
    isEditing = false;

    baselineSchedule = final;
    showGuard("");
  });

  // Close -> return to edit modal
  (function wireCloseToEdit(){
    if (!modalEl) return;
    modalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
      btn.addEventListener("click", (e) => {
        const editEl = document.getElementById("editVolunteerModal");
        if (!editEl) return;

        e.preventDefault();
        e.stopPropagation();

        returnToEditModal = true;
        try { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); } catch {}
      }, true);
    });
  })();

})();
</script>
