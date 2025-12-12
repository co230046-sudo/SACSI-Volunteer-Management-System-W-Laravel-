<style>
/* ===========================================================
   CLASS SCHEDULE MODAL – CRIMSON THEME
=========================================================== */

#classScheduleModal .modal-dialog {
  max-width: 980px;
}

#classScheduleModal .custom-schedule-modal {
  border-radius: 18px;
  overflow: hidden;
  font-family: 'Segoe UI', Roboto, sans-serif;
}

/* Header – solid crimson like Event Manager */
#classScheduleModal .custom-modal-header {
  background: #9b2733;            /* crimson bar */
  color: #fff;
  border-bottom: 1px solid #7f1d26;
}
#classScheduleModal .custom-modal-header .modal-title {
  font-weight: 700;
  font-size: 1.25rem;
}
#classScheduleModal .custom-modal-header .fa-calendar-days {
  color: #ffdddd;
}
#classScheduleModal .btn-close {
  filter: invert(1) grayscale(100%);
}

/* Body */
#classScheduleModal .custom-modal-body {
  background: #fff;
  padding: 1.15rem 1.5rem 1rem;
}

/* Hint chip */
#classScheduleModal .schedule-hint {
  border-radius: 10px;
  border: 1px dashed #f3c2c7;
  background: #fff5f6;
  padding: 0.55rem 0.85rem;
  font-size: 0.85rem;
  color: #5c1b24;
  margin-bottom: 0.9rem;
}
#classScheduleModal .schedule-hint kbd {
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  background: #333;
  color: #fff;
  font-size: 0.78rem;
}

/* Table */
#classScheduleModal .schedule-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

#classScheduleModal .schedule-table th,
#classScheduleModal .schedule-table td {
  border: 1px solid #f0d0d3;
  padding: 0.45rem 0.5rem;
}

/* Header row – crimson */
#classScheduleModal .schedule-table thead th {
  background: #9b2733;
  color: #fff;
  font-weight: 600;
}
#classScheduleModal .schedule-table thead th:first-child {
  width: 46px;
}
#classScheduleModal .schedule-table thead th:last-child {
  width: 80px;
}

/* Row number */
#classScheduleModal .schedule-time {
  font-weight: 600;
  color: #7f1d26;
}

/* Base cell */
#classScheduleModal .schedule-entry {
  font-size: 0.88rem;
  background: #ffffff;
  transition: background-color 0.15s ease, border-color 0.15s ease;
}

/* States: empty, ok, conflict */
#classScheduleModal .schedule-entry.sched-empty {
  background: #ffffff;
}
#classScheduleModal .schedule-entry.sched-ok {
  background: #e6f9ea;
  border-color: #c3e6cb;
}
#classScheduleModal .schedule-entry.sched-conflict {
  background: #ffe6e6;
  border-color: #f5c2c7;
}

/* Delete button */
#classScheduleModal .btn-row-delete {
  padding: 0.28rem 0.55rem;
}

/* Footer buttons */
#classScheduleModal .custom-modal-footer {
  background: #fafafa;
  border-top: 1px solid #eee;
  padding: 0.8rem 1.5rem;
}
#classScheduleModal .btn-add-row {
  background: #b2000c;
  border-color: #b2000c;
}
#classScheduleModal .btn-add-row:hover {
  background: #8e0009;
  border-color: #8e0009;
}
#classScheduleModal .btn-crimson {
  background: #b2000c;
  border-color: #b2000c;
}
#classScheduleModal .btn-crimson:hover {
  background: #8e0009;
  border-color: #8e0009;
}

/* ===========================================================
   TIME SELECT (PILL-LIKE, CRIMSON)
   (we keep native select for accessibility & simplicity)
=========================================================== */

#classScheduleModal .schedule-select {
  width: 100%;
  min-width: 120px;
  height: 32px;
  padding: 0 0.85rem;
  border-radius: 999px;
  border: 1px solid #c53c45;
  background-color: #fff;
  color: #5c1b24;
  font-size: 0.82rem;
  line-height: 1.2;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  cursor: pointer;
  position: relative;
  background-image:
    linear-gradient(45deg, transparent 50%, #9b2733 50%),
    linear-gradient(135deg, #9b2733 50%, transparent 50%);
  background-position:
    calc(100% - 14px) 11px,
    calc(100% - 10px) 11px;
  background-size: 5px 5px, 5px 5px;
  background-repeat: no-repeat;
}
#classScheduleModal .schedule-select:focus {
  outline: none;
  border-color: #9b2733;
  box-shadow: 0 0 0 2px rgba(155,39,51,0.18);
}
#classScheduleModal .schedule-select option {
  font-size: 0.82rem;
}

/* Optgroup labels styling hint (browser-dependent) */
#classScheduleModal .schedule-select optgroup[label] {
  font-weight: 600;
}

/* Small tag text we include in labels, e.g. “7:30–8:20 AM” */
#classScheduleModal .time-tag {
  font-size: 0.76rem;
}

/* Make inside-modal scrollbars slim (if height overflows) */
#classScheduleModal .modal-body::-webkit-scrollbar {
  width: 8px;
}
#classScheduleModal .modal-body::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}
#classScheduleModal .modal-body::-webkit-scrollbar-thumb {
  background: #c53c45;
  border-radius: 4px;
}
#classScheduleModal .modal-body::-webkit-scrollbar-thumb:hover {
  background: #9b2733;
}
</style>

<!-- ===========================================================
     CLASS SCHEDULE MODAL
=========================================================== -->
<div class="modal fade" id="classScheduleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content custom-schedule-modal">

      <!-- Header -->
      <div class="modal-header custom-modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title mb-0">
          <i class="fa-solid fa-calendar-days me-2"></i> Class Schedule
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Body -->
      <div class="modal-body custom-modal-body">
        <div class="schedule-hint">
          In <strong>Edit</strong> mode, focus a time dropdown and press
          <kbd>Ctrl</kbd> + <kbd>Z</kbd> to undo its last change for that day.
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
            <tbody id="scheduleContent"></tbody>
          </table>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer custom-modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-add-row btn-sm text-white" id="addRowBtnFooter">
          <i class="fa-solid fa-plus me-1"></i> Add Row
        </button>

        <div>
          <button type="button" class="btn btn-secondary btn-sm" id="editScheduleBtn">
            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
          </button>
          <button type="button" class="btn btn-success btn-sm d-none" id="saveScheduleBtn">
            <i class="fa-solid fa-save me-1"></i> Save
          </button>
          <button type="button" class="btn btn-crimson btn-sm text-white" data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark me-1"></i> Close
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- SUCCESS MODAL FOR SCHEDULE (unchanged from your version) -->
<div id="resetSuccessModal" class="reset-import-modal">
  <div class="reset-modal-overlay">
    <div class="reset-modal-box">
      <div class="reset-modal-header">
        <i class="fa-solid fa-circle-check reset-success-icon"></i>
        <h2 class="reset-success-title">Success</h2>
      </div>
      <hr class="reset-modal-separator">
      <div id="resetSuccessMessage" class="reset-text-block reset-success-text"></div>
      <div class="reset-modal-buttons">
        <button type="button" class="reset-btn-confirm" id="resetSuccessOkBtn">
          <i class="fa-solid fa-check"></i> OK
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Hidden form for PUT submission -->
<form id="updateScheduleForm" method="POST" style="display:none;">
  @csrf
  @method('PUT')
  <input type="hidden" name="schedule" id="scheduleInput">
  <input type="hidden" name="type" id="scheduleType">
</form>

<script>
/* Flash success from controller (unchanged) */
document.addEventListener("DOMContentLoaded", () => {
  @if(session('success_schedule'))
    showSuccessScheduleModal({!! json_encode(session('success_schedule')) !!});
  @endif

  const flash = document.querySelector(".action-message .message-text");
  if (flash) {
    flash.addEventListener("click", e => {
      if (e.target.classList.contains("show-modal-details")) {
        e.preventDefault();
        showSuccessScheduleModal({!! json_encode(session('success_schedule')) !!});
      }
    });
  }

  function showSuccessScheduleModal(html) {
    document.getElementById("resetSuccessMessage").innerHTML = html;
    const modal = document.getElementById("resetSuccessModal");
    modal.style.display = "block";
    document.getElementById("resetSuccessOkBtn").onclick = () =>
      modal.style.display = "none";
  }
});
</script>

<script>
/* ===========================================================
   CLASS SCHEDULE JS – CRIMSON VERSION WITH CONFLICT HIGHLIGHT
=========================================================== */

const MAX_ROWS = 6;
const days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

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

const timeOptions = Object.keys(timeMeta);

let currentType  = null;   // "valid" or "invalid"
let currentIndex = null;
let isEditing    = false;

/* ---------- Helpers: time parsing / overlaps ---------- */

function normalizeTimeRange(str) {
  if (!str) return "";
  str = str.replace(/[,;]+/g," ").trim();
  const p = str.split("-").map(s => s.trim());
  if (p.length !== 2) return str;
  const fix = t => /^\d{1,2}$/.test(t) ? t + ":00" : t;
  const n = `${fix(p[0])}-${fix(p[1])}`;
  return timeMeta[n] ? n : n;
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

/* ---------- Cell state colouring (empty / ok / conflict) ---------- */

function markCellState(td, value) {
  td.classList.remove("sched-empty","sched-ok","sched-conflict");
  if (!value) {
    td.classList.add("sched-empty");
  } else {
    td.classList.add("sched-ok"); // provisional; conflict recomputed separately
  }
}

/* ---------- Build selected map per day (current DOM) ---------- */

function buildSelectedPerDay() {
  const map = {};
  days.forEach((day, di) => {
    map[day] = [];
    document.querySelectorAll("#scheduleContent tr").forEach(row => {
      const td = row.querySelectorAll("td.schedule-entry")[di];
      if (!td) return;
      const sel = td.querySelector("select");
      const val = sel ? sel.value.trim() : td.textContent.trim();
      if (val) map[day].push(normalizeTimeRange(val));
    });
  });
  return map;
}

/* ---------- Disable overlapping slots in selects ---------- */

function rebuildSelectedPerDay() {
  const selected = buildSelectedPerDay();
  const body = document.getElementById("scheduleContent");

  body.querySelectorAll("tr").forEach(row => {
    const cells = row.querySelectorAll("td.schedule-entry");
    cells.forEach((td, di) => {
      const sel = td.querySelector("select");
      if (!sel) return;
      const day = days[di];

      sel.querySelectorAll("option").forEach(opt => {
        if (!opt.value) return;
        if (opt.value === sel.value) {
          opt.disabled = false;
          return;
        }
        opt.disabled = selected[day].some(v => rangesOverlap(v, opt.value));
      });
    });
  });
}

/* ---------- Highlight conflicts red per day ---------- */

function recomputeHighlights() {
  const body = document.getElementById("scheduleContent");
  const rows = Array.from(body.querySelectorAll("tr"));

  // reset state, keep empty vs non-empty handling in markCellState
  body.querySelectorAll("td.schedule-entry").forEach(td => {
    if (!td.classList.contains("sched-empty")) {
      td.classList.remove("sched-ok","sched-conflict");
    }
  });

  days.forEach((day, di) => {
    const cells = rows.map(r => r.querySelectorAll("td.schedule-entry")[di]);
    const items = [];

    cells.forEach(cell => {
      if (!cell) return;
      const sel = cell.querySelector("select");
      const raw = sel ? sel.value.trim() : cell.textContent.trim();
      const norm = normalizeTimeRange(raw);
      const range = parseRange(norm);
      if (norm && range) items.push({ cell, start: range.start, end: range.end });
    });

    items.sort((a,b) => a.start - b.start);

    let prev = null;
    items.forEach(it => {
      if (prev && it.start < prev.end) {
        it.cell.classList.remove("sched-ok");
        prev.cell.classList.remove("sched-ok");
        it.cell.classList.add("sched-conflict");
        prev.cell.classList.add("sched-conflict");
        if (!prev || it.end > prev.end) prev = it;
      } else {
        if (!it.cell.classList.contains("sched-conflict")) {
          it.cell.classList.add("sched-ok");
        }
        prev = it;
      }
    });
  });
}

/* ---------- Sorting by day (column) ---------- */

function sortColumn(colIdx) {
  const body = document.getElementById("scheduleContent");
  const rows = Array.from(body.querySelectorAll("tr"));

  const values = [];
  rows.forEach(row => {
    const td = row.querySelectorAll("td.schedule-entry")[colIdx];
    if (!td) return;
    const sel = td.querySelector("select");
    const raw = sel ? sel.value.trim() : td.textContent.trim();
    const norm = normalizeTimeRange(raw);
    if (norm) values.push(norm);
  });

  values.sort((a,b) => {
    const ra = parseRange(a), rb = parseRange(b);
    return (ra?.start || 0) - (rb?.start || 0);
  });

  let k = 0;
  rows.forEach(row => {
    const td = row.querySelectorAll("td.schedule-entry")[colIdx];
    if (!td) return;
    const sel = td.querySelector("select");
    const val = k < values.length ? values[k++] : "";
    if (sel) {
      sel.value = val;
      sel.dataset.current = val;
    } else {
      td.textContent = val;
    }
    markCellState(td, val);
  });

  rebuildSelectedPerDay();
  recomputeHighlights();
}

function sortAllColumns() {
  for (let i = 0; i < days.length; i++) sortColumn(i);
}

/* ---------- Create <select> in a cell ---------- */

function createSelectInCell(td, colIdx) {
  const day = days[colIdx];
  let cur = normalizeTimeRange(td.textContent.trim());
  td.textContent = "";

  const select = document.createElement("select");
  select.className = "schedule-select form-select form-select-sm";
  select.dataset.prev = "";
  select.dataset.current = cur || "";

  const blank = document.createElement("option");
  blank.value = "";
  blank.textContent = "No Class";
  select.appendChild(blank);

  const selectedPerDay = buildSelectedPerDay();

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
    const conflict = selectedPerDay[day].some(v => v !== cur && rangesOverlap(v, key));
    if (conflict) option.disabled = true;
    og.appendChild(option);
  });

  select.appendChild(groupAM);
  select.appendChild(groupPM);

  if (cur && !timeMeta[cur]) {
    const custom = document.createElement("option");
    custom.value = cur;
    custom.textContent = cur + " (Custom)";
    select.appendChild(custom);
  }

  select.value = cur || "";
  markCellState(td, cur || "");

  select.addEventListener("change", () => {
    const oldCurrent = select.dataset.current || "";
    select.dataset.prev = oldCurrent;
    select.dataset.current = select.value;
    markCellState(td, select.value);
    sortColumn(colIdx);
  });

  td.appendChild(select);
}

/* ---------- Ctrl+Z undo per select ---------- */

document.addEventListener("keydown", (e) => {
  if (!e.ctrlKey || (e.key !== "z" && e.key !== "Z")) return;
  const active = document.activeElement;
  if (!active || active.tagName !== "SELECT" || !active.classList.contains("schedule-select")) return;

  e.preventDefault();
  const select = active;
  const cur  = select.dataset.current || "";
  const prev = select.dataset.prev || "";
  if (!prev) return;

  select.dataset.current = prev;
  select.dataset.prev    = cur;
  select.value = prev;

  const td = select.closest("td.schedule-entry");
  const row = select.closest("tr");
  const cells = Array.from(row.querySelectorAll("td.schedule-entry"));
  const colIdx = cells.indexOf(td);
  markCellState(td, prev);
  sortColumn(colIdx);
});

/* ---------- OPEN MODAL (called from Actions) ---------- */

function openScheduleModal(scheduleString, type, index) {
  currentType  = type;
  currentIndex = index;
  isEditing    = false;

  const body = document.getElementById("scheduleContent");
  body.innerHTML = "";

  const scheduleData = {};
  days.forEach(day => {
    const regex = new RegExp(`${day}:([^]*?)(?=Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|$)`,"i");
    const match = scheduleString.match(regex);
    let raw = match ? match[1].trim() : "";
    raw = raw.replace(/No Class/gi,"").replace(/[,;]+/g," ").trim();
    scheduleData[day] = raw ? raw.split(/\s+/).map(normalizeTimeRange) : [];
    scheduleData[day].sort((a,b) => {
      const ra = parseRange(a), rb = parseRange(b);
      return (ra?.start || 0) - (rb?.start || 0);
    });
  });

  let rows = Math.max(...days.map(d => scheduleData[d].length));
  rows = !rows || rows < 1 ? 1 : Math.min(rows, MAX_ROWS);

  for (let i = 0; i < rows; i++) {
    const row = {};
    days.forEach(d => row[d] = scheduleData[d][i] || "");
    addScheduleRow(row);
  }

  sortAllColumns();
  recomputeHighlights();

  document.getElementById("editScheduleBtn").classList.remove("d-none");
  document.getElementById("saveScheduleBtn").classList.add("d-none");

  document.getElementById("addRowBtnFooter").onclick = () => addScheduleRow();

  new bootstrap.Modal(document.getElementById("classScheduleModal")).show();
}

/* ---------- Add row ---------- */

function addScheduleRow(data = {}) {
  const body = document.getElementById("scheduleContent");
  if (body.children.length >= MAX_ROWS) {
    if (typeof showMessageModal === "function") {
      showMessageModal("You can only add up to " + MAX_ROWS + " rows.");
    }
    return;
  }

  const tr = document.createElement("tr");

  const idx = document.createElement("td");
  idx.className = "schedule-time";
  idx.textContent = body.children.length + 1;
  tr.appendChild(idx);

  days.forEach(day => {
    const td = document.createElement("td");
    td.className = "schedule-entry";
    const val = normalizeTimeRange(data[day] || "");
    td.textContent = val;
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
    updateRowNumbers();
    sortAllColumns();
    recomputeHighlights();
  };
  tdDel.appendChild(btn);
  tr.appendChild(tdDel);

  body.appendChild(tr);

  if (isEditing) {
    tr.querySelectorAll("td.schedule-entry").forEach((td, di) => {
      createSelectInCell(td, di);
    });
    rebuildSelectedPerDay();
    recomputeHighlights();
  } else {
    recomputeHighlights();
  }
}

function updateRowNumbers() {
  document.querySelectorAll("#scheduleContent .schedule-time")
    .forEach((c,i)=> c.textContent = i+1);
}

/* ---------- Edit mode ---------- */

document.getElementById("editScheduleBtn").onclick = () => {
  isEditing = true;
  const body = document.getElementById("scheduleContent");
  body.querySelectorAll("tr").forEach(row => {
    row.querySelectorAll("td.schedule-entry").forEach((td, di) => {
      createSelectInCell(td, di);
    });
  });

  sortAllColumns();
  rebuildSelectedPerDay();
  recomputeHighlights();

  document.getElementById("editScheduleBtn").classList.add("d-none");
  document.getElementById("saveScheduleBtn").classList.remove("d-none");
};

/* ---------- Save ---------- */

document.getElementById("saveScheduleBtn").onclick = () => {
  sortAllColumns();
  rebuildSelectedPerDay();
  recomputeHighlights();

  const updated = {};
  days.forEach(d => updated[d] = []);

  document.querySelectorAll("#scheduleContent tr").forEach((row, ri) => {
    row.querySelectorAll("td.schedule-entry").forEach((td, di) => {
      const sel = td.querySelector("select");
      let val = sel ? sel.value.trim() : td.textContent.trim();
      val = val.replace("(Custom)", "").trim();
      val = normalizeTimeRange(val);
      updated[days[di]][ri] = val;
      td.textContent = val;
      markCellState(td, val);
    });
  });

  days.forEach(d => {
    updated[d] = updated[d].filter(v => v).sort((a,b)=>{
      const ra = parseRange(a), rb = parseRange(b);
      return (ra?.start||0)-(rb?.start||0);
    });
  });

  const final = formatSchedule(updated);
  document.getElementById("scheduleInput").value = final;
  document.getElementById("scheduleType").value  = currentType;

  const form = document.getElementById("updateScheduleForm");
  form.action = `/volunteer-import/volunteers/${currentIndex}/update-schedule`;
  form.submit();

  document.getElementById("saveScheduleBtn").classList.add("d-none");
  document.getElementById("editScheduleBtn").classList.remove("d-none");
  isEditing = false;
};

function formatSchedule(obj) {
  return days.map(day => {
    const arr = obj[day];
    if (!arr.length) return `${day}: No Class`;
    return `${day}: ${arr.join(" ")}`;
  }).join(" ");
}
</script>
