<style>
/* Overlay (if you use it elsewhere) */
.schedule-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

/* Generic message modal scroll styling (if you reuse it) */
.modal-content-wrapper {
  flex: 1 1 auto;
  overflow-y: auto;
  margin: 0.5rem 0 1rem;
  word-break: break-word;
  text-align: left;
}

.modal-content-wrapper::-webkit-scrollbar {
  width: 8px;
}
.modal-content-wrapper::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}
.modal-content-wrapper::-webkit-scrollbar-thumb {
  background: #d9534f;
  border-radius: 4px;
}
.modal-content-wrapper::-webkit-scrollbar-thumb:hover {
  background: #c9302c;
}

/* ======= CLASS SCHEDULE MODAL (SCOPED) ======= */

.custom-schedule-modal {
  border-radius: 15px;
  font-family: 'Segoe UI', Roboto, sans-serif;
  overflow: hidden;
}

/* Header – less red, no solid red background */
#classScheduleModal .custom-modal-header {
  background-color: #ffffff;
  color: #b71c1c;
  font-weight: 600;
  border-bottom: 1px solid #f1c0c3;
}

#classScheduleModal .custom-modal-header .fa-calendar-days {
  color: #c82333;
}

#classScheduleModal .custom-modal-body {
  background-color: #fff5f5;
  padding: 1rem 1.5rem;
}

#classScheduleModal .schedule-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

#classScheduleModal .schedule-table th,
#classScheduleModal .schedule-table td {
  border: 1px solid #f1c0c3;
  padding: 0.5rem;
}

#classScheduleModal .schedule-table th {
  background-color: #e4606d;
  color: white;
  font-weight: 600;
}

#classScheduleModal .schedule-table tbody tr:nth-child(even) {
  background-color: #ffe5e8;
}

#classScheduleModal .schedule-table tbody tr:hover {
  background-color: #f9b2bc;
}

#classScheduleModal .schedule-time {
  font-weight: 600;
  color: #b71c1c;
}

/* Static cell look in view mode */
#classScheduleModal .schedule-entry {
  font-weight: 500;
  color: #4d0000;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  background-color: #f8d0d5;
  margin: 2px 0;
}

/* ===== Red-themed select, scoped ONLY to this modal ===== */
#classScheduleModal .schedule-select {
  height: 30px;
  padding: 0 0.5rem;
  font-size: 0.85rem;
  background-color: #fff;
  border: 1px solid #e3342f;
  color: #e3342f;
  border-radius: 0.25rem;
  appearance: none;
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
}

#classScheduleModal .schedule-select:hover,
#classScheduleModal .schedule-select:focus {
  border-color: #c53030;
  box-shadow: 0 0 0 2px rgba(227,52,47,0.2);
  outline: none;
}

#classScheduleModal .schedule-select option {
  padding: 0.25rem 0.5rem;
  font-size: 0.85rem;
  background-color: #fff;
  color: #e3342f;
}

#classScheduleModal .schedule-select option:disabled {
  color: #aaa;
  font-style: italic;
}

/* Custom scrollbar for dropdown (Webkit only) */
#classScheduleModal .schedule-select::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
#classScheduleModal .schedule-select::-webkit-scrollbar-track {
  background: #fee2e2;
  border-radius: 4px;
}
#classScheduleModal .schedule-select::-webkit-scrollbar-thumb {
  background: #e3342f;
  border-radius: 4px;
}
#classScheduleModal .schedule-select::-webkit-scrollbar-thumb:hover {
  background: #c53030;
}

/* ===== SUCCESS MODAL WRAPPER ===== */
.reset-import-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 99999;
}

.reset-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.65);
    display: flex;
    justify-content: center;
    align-items: center;
}

/* ===== BOX ===== */
.reset-modal-box {
    width: 420px;
    max-width: 650px;
    background: #fff;
    border-radius: 16px;
    padding: 1.25rem 1.5rem 1.75rem;
    text-align: center;
    box-shadow: 0 6px 20px rgba(0,0,0,0.25);
    animation: fadeInScale 0.25s ease;
}

@keyframes fadeInScale {
    from { transform: scale(0.92); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}

/* HEADER */
.reset-modal-header {
    text-align: center;
    margin-bottom: 0.8rem;
}
.reset-success-icon {
    font-size: 2.4rem;
    color: #28a745;
    margin-bottom: 0.4rem;
}
.reset-success-title {
    margin: 0;
    font-weight: 700;
    font-size: 1.4rem;
    color: #28a745;
}

/* SEPARATOR */
.reset-modal-separator {
    width: 88%;
    height: 1px;
    background: #e5e5e5;
    margin: 1rem auto;
}

/* MESSAGE BLOCK */
.reset-text-block {
    text-align: left !important;
    margin: 0.5rem auto 1.25rem;
    padding: 0 0.75rem;
    font-size: 1.05rem;
    color: #333;
    line-height: 1.55;
}

.reset-success-text {
    white-space: normal;
}

/* BUTTONS */
.reset-modal-buttons {
    display: flex;
    justify-content: center;
    margin-top: 1.2rem;
}

.reset-btn-confirm {
    background-color: #b2000c;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 28px;
    font-size: .95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s ease;
}
.reset-btn-confirm:hover {
    background-color: #8e0009;
}
</style>

<!-- Class Schedule Modal -->
<div class="modal fade" id="classScheduleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content custom-schedule-modal">

      <!-- Modal Header -->
      <div class="modal-header custom-modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title mb-0">
          <i class="fa-solid fa-calendar-days me-2"></i> Class Schedule
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body custom-modal-body">
        <div class="text-start small text-muted mb-2">
          Hint: In <strong>Edit</strong> mode, focus a dropdown and press <kbd>Ctrl</kbd> + <kbd>Z</kbd> to undo its last change for that day.
        </div>
        <div class="weekly-schedule">
          <table class="table schedule-table text-center align-middle mb-0">
            <thead>
              <tr>
                <th style="width:40px;">#</th>
                <th>Monday</th>
                <th>Tuesday</th>
                <th>Wednesday</th>
                <th>Thursday</th>
                <th>Friday</th>
                <th>Saturday</th>
                <th style="width:70px;">Action</th>
              </tr>
            </thead>
            <tbody id="scheduleContent"></tbody>
          </table>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer custom-modal-footer d-flex justify-content-between">
        <div>
          <button type="button" class="btn btn-danger btn-sm" id="addRowBtnFooter">
            <i class="fa-solid fa-plus me-1"></i> Add Row
          </button>
        </div>
        <div>
          <button type="button" class="btn btn-secondary btn-sm" id="editScheduleBtn">
            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
          </button>
          <button type="button" class="btn btn-success btn-sm d-none" id="saveScheduleBtn">
            <i class="fa-solid fa-save me-1"></i> Save
          </button>
          <button type="button" class="btn btn-danger btn-sm" data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark me-1"></i> Close
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- SUCCESS MODAL -->
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
    <input type="hidden" name="type" id="scheduleType">  <!-- 👈 ADD THIS -->
</form>

<script>
document.addEventListener("DOMContentLoaded", () => {

    @if(session('success_schedule'))
        const msg = {!! json_encode(session('success_schedule')) !!};
        const modal = document.getElementById('resetSuccessModal');
        const text = document.getElementById('resetSuccessMessage');
        const okBtn = document.getElementById('resetSuccessOkBtn');

        text.innerHTML = msg;
        modal.style.display = "block";

        okBtn.addEventListener("click", () => {
            modal.style.display = "none";
        });
    @endif

});
</script>

<script>
document.addEventListener("DOMContentLoaded", () => {

    // 1. Auto-open modal from controller
    @if(session('success_schedule'))
        showSuccessScheduleModal({!! json_encode(session('success_schedule')) !!});

    @endif

    // 2. Flash message “Show Details” → reopen modal
    const flash = document.querySelector(".action-message .message-text");

    if (flash) {
        flash.addEventListener("click", e => {
            if (e.target.classList.contains("show-modal-details")) {
                e.preventDefault();
                showSuccessScheduleModal({!! json_encode(session('success_schedule')) !!});

            }
        });
    }

    // 3. Modal helper
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
/* ============================================================
   GLOBAL CONSTANTS
   ============================================================ */
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

let currentType = null;
let currentIndex = null;
let isEditing = false;

/* ============================================================
   TIME HELPERS
   ============================================================ */

function normalizeTimeRange(str) {
    if (!str) return "";
    str = str.replace(/[,;]+/g," ").trim();

    const p = str.split("-").map(s => s.trim());
    if (p.length !== 2) return str;

    const fix = t => /^\d{1,2}$/.test(t) ? t+":00" : t;
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

/* ============================================================
   BUILD SELECTED PER DAY FROM DOM
   ============================================================ */

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

/* ============================================================
   REAPPLY CONFLICT DISABLES
   ============================================================ */

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

/* ============================================================
   PER-DAY SORTING (Option B)
   ============================================================ */

function sortColumn(colIdx) {
    const body = document.getElementById("scheduleContent");
    const rows = [...body.querySelectorAll("tr")];

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
        td.style.backgroundColor = val ? "#d4edda" : "";
    });

    rebuildSelectedPerDay();
}

function sortAllColumns() {
    for (let i = 0; i < days.length; i++) {
        sortColumn(i);
    }
}

/* ============================================================
   CREATE SELECT CELL (WITH UNDO SUPPORT)
   ============================================================ */

function createSelectInCell(td, colIdx) {
    const day = days[colIdx];
    let cur = normalizeTimeRange(td.textContent.trim());
    td.textContent = "";

    const select = document.createElement("select");
    select.className = "schedule-select form-select form-select-sm";
    select.setAttribute("data-prev", "");
    select.setAttribute("data-current", cur || "");

    const blank = document.createElement("option");
    blank.value = "";
    blank.textContent = "No Class";
    select.appendChild(blank);

    const selectedPerDay = buildSelectedPerDay();

    const groups = {
        AM: document.createElement("optgroup"),
        PM: document.createElement("optgroup")
    };
    groups.AM.label = "Morning";
    groups.PM.label = "Afternoon / Evening";

    timeOptions.forEach(opt => {
        const meta = timeMeta[opt];
        const og = groups[meta.group];

        const option = document.createElement("option");
        option.value = opt;
        option.textContent = meta.label;

        const conflict = selectedPerDay[day].some(v => v !== cur && rangesOverlap(v, opt));
        if (conflict) option.disabled = true;

        og.appendChild(option);
    });

    select.appendChild(groups.AM);
    select.appendChild(groups.PM);

    if (cur && !timeMeta[cur]) {
        const c = document.createElement("option");
        c.value = cur;
        c.textContent = cur + " (Custom)";
        select.appendChild(c);
    }

    select.value = cur || "";

    select.addEventListener("change", () => {
        const oldCurrent = select.dataset.current || "";
        select.dataset.prev = oldCurrent;
        select.dataset.current = select.value;

        td.style.backgroundColor = select.value ? "#d4edda" : "";

        sortColumn(colIdx); // sort this day only (continuous)
    });

    td.appendChild(select);
}

/* ============================================================
   CTRL+Z UNDO FOR ACTIVE SELECT
   ============================================================ */

document.addEventListener("keydown", (e) => {
    if (!e.ctrlKey || (e.key !== "z" && e.key !== "Z")) return;

    const active = document.activeElement;
    if (!active || active.tagName !== "SELECT" || !active.classList.contains("schedule-select")) return;

    e.preventDefault();

    const select = active;
    const cur = select.dataset.current || "";
    const prev = select.dataset.prev || "";

    if (!prev) return; // nothing to undo

    // swap
    select.dataset.current = prev;
    select.dataset.prev = cur;
    select.value = prev;

    const td = select.closest("td.schedule-entry");
    if (!td) return;

    const row = select.closest("tr");
    const cells = [...row.querySelectorAll("td.schedule-entry")];
    const colIdx = cells.indexOf(td);
    if (colIdx === -1) return;

    td.style.backgroundColor = prev ? "#d4edda" : "";

    sortColumn(colIdx);
});

/* ============================================================
   OPEN MODAL
   ============================================================ */

function openScheduleModal(scheduleString, type, index) {
    currentType = type;
    currentIndex = index;
    isEditing = false;

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
            return (ra?.start||0) - (rb?.start||0);
        });
    });

    let rows = Math.max(...days.map(d => scheduleData[d].length));
    if (!rows || rows < 1) rows = 1;
    rows = Math.min(rows, MAX_ROWS);

    for (let i=0;i<rows;i++) {
        const row = {};
        days.forEach(d => row[d] = scheduleData[d][i] || "");
        addScheduleRow(row);
    }

    sortAllColumns();

    document.getElementById("editScheduleBtn").classList.remove("d-none");
    document.getElementById("saveScheduleBtn").classList.add("d-none");

    document.getElementById("addRowBtnFooter").onclick = () => addScheduleRow();

    new bootstrap.Modal(document.getElementById("classScheduleModal")).show();
}

/* ============================================================
   ADD ROW
   ============================================================ */

function addScheduleRow(data={}) {
    const body = document.getElementById("scheduleContent");

    if (body.children.length >= MAX_ROWS) {
        showMessageModal("You can only add up to " + MAX_ROWS + " rows.");
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
        td.style.backgroundColor = val ? "#d4edda" : "";
        tr.appendChild(td);
    });

    const tdDel = document.createElement("td");
    const b = document.createElement("button");
    b.className = "btn btn-sm btn-danger";
    b.innerHTML = "<i class='fa-solid fa-trash'></i>";
    b.onclick = () => {
        tr.remove();
        updateRowNumbers();
        sortAllColumns();
    };
    tdDel.appendChild(b);
    tr.appendChild(tdDel);

    body.appendChild(tr);

    if (isEditing) {
        const cells = tr.querySelectorAll("td.schedule-entry");
        cells.forEach((td, di) => createSelectInCell(td, di));
        rebuildSelectedPerDay();
    }

    sortAllColumns();
}

/* ============================================================
   UPDATE ROW NUMBERS
   ============================================================ */

function updateRowNumbers() {
    document.querySelectorAll("#scheduleContent .schedule-time").forEach((c,i)=>{
        c.textContent = i+1;
    });
}

/* ============================================================
   EDIT MODE
   ============================================================ */

document.getElementById("editScheduleBtn").onclick = () => {
    isEditing = true;

    const body = document.getElementById("scheduleContent");
    body.querySelectorAll("tr").forEach(row => {
        row.querySelectorAll("td.schedule-entry").forEach((td, di) => {
            createSelectInCell(td, di);
        });
    });

    sortAllColumns();

    document.getElementById("editScheduleBtn").classList.add("d-none");
    document.getElementById("saveScheduleBtn").classList.remove("d-none");
};

/* ============================================================
   SAVE MODE
   ============================================================ */

document.getElementById("saveScheduleBtn").onclick = () => {
    sortAllColumns();

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
            td.style.backgroundColor = val ? "#d4edda" : "";
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

    // 🔴 ADD THIS:
    document.getElementById("scheduleType").value = currentType; // "valid" or "invalid"

    const form = document.getElementById("updateScheduleForm");
    form.action = `/volunteer-import/volunteers/${currentIndex}/update-schedule`;
    form.submit();

    document.getElementById("saveScheduleBtn").classList.add("d-none");
    document.getElementById("editScheduleBtn").classList.remove("d-none");
    isEditing = false;
};


/* ============================================================
   FORMAT SCHEDULE STRING
   ============================================================ */

function formatSchedule(obj) {
    return days.map(day => {
        let arr = obj[day];
        if (!arr.length) return `${day}: No Class`;
        return `${day}: ${arr.join(" ")}`;
    }).join(" ");
}

/* ============================================================
   MESSAGE MODALS (if you already have them)
   ============================================================ */

function showMessageModal(msg){
  const el=document.getElementById('messageModalText');
  if(el) el.textContent=msg;
  const overlay=document.getElementById('messageModal');
  if(overlay) overlay.style.display='flex';
}
function closeMessageModal(){
  const overlay=document.getElementById('messageModal');
  if(overlay) overlay.style.display='none';
}
</script>
