<!-- ===========================================================
     ✅ FULL PATCHED class_schedule_modal.blade.php (FINAL)
     Fixes:
     ✅ Default 3 rows even if schedule empty
     ✅ Works when opened via data-bs-toggle (Add Volunteer modal)
     ✅ Save writes back to #vlScheduleField (Add Volunteer) OR does PUT submit (Volunteer Import)
     ✅ Portal dropdown + conflicts + sorting kept
     ✅ NEW: Close (X + footer Close) returns to Add Volunteer modal (if present)
     ✅ NEW: Wider schedule modal + table sizing so AM/PM never gets cut off
=========================================================== -->

<style>
/* ===========================================================
   CLASS SCHEDULE MODAL – CRIMSON THEME
=========================================================== */

/* ✅ PATCH: make modal wider so pill labels (AM/PM) never get clipped */
#classScheduleModal .modal-dialog { max-width: 1240px; }
@media (min-width: 1600px){
  #classScheduleModal .modal-dialog { max-width: 1360px; }
}
@media (max-width: 1200px){
  #classScheduleModal .modal-dialog { max-width: 1140px; }
}

/* ✅ PATCH: allow horizontal scroll on smaller screens instead of clipping */
#classScheduleModal .weekly-schedule{
  overflow-x: auto;
}

/* ✅ PATCH: table gets a sane minimum width so columns don’t squeeze */
#classScheduleModal .schedule-table{
  width:100%;
  min-width: 1120px;       /* prevents tight squeeze that chops “AM/PM” */
  border-collapse:collapse;
  font-size:.9rem;
  table-layout: fixed;     /* keeps columns consistent */
}

#classScheduleModal .custom-schedule-modal{
  border-radius: 18px;
  overflow: hidden;
  font-family: 'Segoe UI', Roboto, sans-serif;
}

/* Header – solid crimson like Event Manager */
#classScheduleModal .custom-modal-header{
  background:#9b2733;
  color:#fff;
  border-bottom:1px solid #7f1d26;
}
#classScheduleModal .custom-modal-header .modal-title{
  font-weight:700;
  font-size:1.25rem;
}
#classScheduleModal .custom-modal-header .fa-calendar-days{ color:#ffdddd; }
#classScheduleModal .btn-close{ filter: invert(1) grayscale(100%); }

/* Body */
#classScheduleModal .custom-modal-body{
  background:#fff;
  padding:1.15rem 1.5rem 1rem;
}

/* Hint chip */
#classScheduleModal .schedule-hint{
  border-radius:10px;
  border:1px dashed #f3c2c7;
  background:#fff5f6;
  padding:.55rem .85rem;
  font-size:.85rem;
  color:#5c1b24;
  margin-bottom:.9rem;
}
#classScheduleModal .schedule-hint kbd{
  padding:.1rem .4rem;
  border-radius:4px;
  background:#333;
  color:#fff;
  font-size:.78rem;
}

/* Table cells */
#classScheduleModal .schedule-table th,
#classScheduleModal .schedule-table td{
  border:1px solid #f0d0d3;
  padding:.45rem .5rem;
}

/* Header row – crimson */
#classScheduleModal .schedule-table thead th{
  background:#9b2733;
  color:#fff;
  font-weight:600;
}
#classScheduleModal .schedule-table thead th:first-child{ width:46px; }
#classScheduleModal .schedule-table thead th:last-child{ width:80px; }

/* ✅ PATCH: keep day columns roomy */
#classScheduleModal .schedule-table thead th:not(:first-child):not(:last-child){
  min-width: 155px;
}

/* Row number */
#classScheduleModal .schedule-time{
  font-weight:600;
  color:#7f1d26;
}

/* Base cell */
#classScheduleModal .schedule-entry{
  font-size:.88rem;
  background:#ffffff;
  transition: background-color .15s ease, border-color .15s ease;
}

/* States: empty, ok, conflict */
#classScheduleModal .schedule-entry.sched-empty{ background:#ffffff; }
#classScheduleModal .schedule-entry.sched-ok{
  background:#e6f9ea;
  border-color:#c3e6cb;
}
#classScheduleModal .schedule-entry.sched-conflict{
  background:#ffe6e6;
  border-color:#f5c2c7;
}

/* Delete button */
#classScheduleModal .btn-row-delete{ padding:.28rem .55rem; }

/* Footer buttons */
#classScheduleModal .custom-modal-footer{
  background:#fafafa;
  border-top:1px solid #eee;
  padding:.8rem 1.5rem;
}
#classScheduleModal .btn-add-row{
  background:#b2000c;
  border-color:#b2000c;
}
#classScheduleModal .btn-add-row:hover{
  background:#8e0009;
  border-color:#8e0009;
}
#classScheduleModal .btn-crimson{
  background:#b2000c;
  border-color:#b2000c;
}
#classScheduleModal .btn-crimson:hover{
  background:#8e0009;
  border-color:#8e0009;
}

/* ===========================================================
   ✅ PATCH: DROPDOWN STYLES
=========================================================== */

#classScheduleModal select.schedule-select{
  position:absolute !important;
  left:-99999px !important;
  width:1px !important;
  height:1px !important;
  opacity:0 !important;
  pointer-events:none !important;
}

#classScheduleModal .impSch-combo{ position:relative; width:100%; }
#classScheduleModal .impSch-input{
  width:100%;
  min-height:32px;
  border-radius:999px;
  border:1px solid #c53c45;
  background:#fff;
  color:#5c1b24;
  font-weight:900;

  /* ✅ PATCH: slightly smaller + never wrap -> prevents “A” cut */
  font-size:.80rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;

  padding:.25rem 2.0rem .25rem .70rem; /* a bit more room for text */
  outline:none;
  cursor:pointer;

  background-image:
    linear-gradient(45deg, transparent 50%, #9b2733 50%),
    linear-gradient(135deg, #9b2733 50%, transparent 50%);
  background-position:
    calc(100% - 14px) 11px,
    calc(100% - 10px) 11px;
  background-size:5px 5px, 5px 5px;
  background-repeat:no-repeat;
}
#classScheduleModal .impSch-input:focus{
  border-color:#9b2733;
  box-shadow:0 0 0 2px rgba(155,39,51,.18);
}

#impSchPortal.impSch-portal{
  position:fixed;
  z-index:30000;
  display:none;
  background:#fff;
  border:1px solid rgba(15,23,42,.12);
  border-radius:14px;
  box-shadow:0 18px 44px rgba(2,6,23,.18);
  overflow:hidden;
}
#impSchPortal .impSch-portalBody{ max-height:320px; overflow:auto; }

#impSchPortal .impSch-item{
  width:100%;
  border:0;
  background:transparent;
  text-align:left;
  padding:10px 12px;
  font-weight:900;
  font-size:.84rem;
  color:#111827;
}
#impSchPortal .impSch-item:hover{ background: rgba(225,29,72,.06); }
#impSchPortal .impSch-item.is-active{ background: rgba(225,29,72,.10); }

#impSchPortal .impSch-group{
  padding:8px 12px;
  font-size:12px;
  font-weight:1000;
  color:#7f1d26;
  background: rgba(162,52,63,.08);
  border-top:1px solid rgba(15,23,42,.08);
}

#classScheduleModal .impSchErr{
  display:none;
  color:#b91c1c;
  font-weight:950;
  font-size:12px;
  margin-right:.6rem;
}

#classScheduleModal .modal-body::-webkit-scrollbar{ width:8px; }
#classScheduleModal .modal-body::-webkit-scrollbar-track{
  background:#f1f1f1;
  border-radius:4px;
}
#classScheduleModal .modal-body::-webkit-scrollbar-thumb{
  background:#c53c45;
  border-radius:4px;
}
#classScheduleModal .modal-body::-webkit-scrollbar-thumb:hover{ background:#9b2733; }

/* ===========================================================
   ✅ OPTIONAL: if you also want Add Volunteer modal wider
   (safe: only applies if that modal exists on the page)
=========================================================== */
#addVolunteerModal .modal-dialog{ max-width: 1280px; }
@media (min-width: 1600px){
  #addVolunteerModal .modal-dialog{ max-width: 1360px; }
}
</style>

<div class="modal fade" id="classScheduleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content custom-schedule-modal">

      <div class="modal-header custom-modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title mb-0">
          <i class="fa-solid fa-calendar-days me-2"></i> Class Schedule
        </h5>
        <!-- ✅ PATCH: keep data-bs-dismiss (we intercept click in JS) -->
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
            <tbody id="scheduleContent"></tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer custom-modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-add-row btn-sm text-white" id="addRowBtnFooter">
          <i class="fa-solid fa-plus me-1"></i> Add Row
        </button>

        <div class="d-flex align-items-center">
          <span class="impSchErr" id="impScheduleGuardMsg"></span>

          <button type="button" class="btn btn-secondary btn-sm" id="editScheduleBtn">
            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
          </button>
          <button type="button" class="btn btn-success btn-sm d-none" id="saveScheduleBtn">
            <i class="fa-solid fa-save me-1"></i> Save
          </button>

          <!-- ✅ PATCH: keep data-bs-dismiss (we intercept click in JS) -->
          <button type="button" class="btn btn-crimson btn-sm text-white ms-2" data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark me-1"></i> Close
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

<div id="impSchPortal" class="impSch-portal" aria-hidden="true">
  <div class="impSch-portalBody"></div>
</div>

@if(session('success_schedule'))
  <div id="__server_schedule_success__" style="display:none;">
    {!! session('success_schedule') !!}
  </div>
@endif

<form id="updateScheduleForm" method="POST" style="display:none;">
  @csrf
  @method('PUT')
  <input type="hidden" name="schedule" id="scheduleInput">
  <input type="hidden" name="type" id="scheduleType">
</form>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const ok = document.getElementById("__server_schedule_success__");
  if (ok && ok.innerHTML.trim() && window.FeedbackModal && typeof window.FeedbackModal.show === "function") {
    window.FeedbackModal.show({
      variant: "success",
      title: "Changes saved",
      subtitle: "Entry updated successfully.",
      html: ok.innerHTML.trim()
    });
  }

  document.addEventListener('click', (e) => {
    const t = e.target;
    if (!(t instanceof Element)) return;
    if (!t.classList.contains("show-modal-details")) return;

    e.preventDefault();
    if (!ok || !ok.innerHTML.trim()) return;

    if (window.FeedbackModal && typeof window.FeedbackModal.show === "function") {
      window.FeedbackModal.show({
        variant: "success",
        title: "Changes saved",
        subtitle: "Entry updated successfully.",
        html: ok.innerHTML.trim()
      });
    }
  });
});
</script>

<script>
/* ===========================================================
   CLASS SCHEDULE JS – with portal dropdown + save guards
   ✅ PATCHED: auto-init DEFAULT_ROWS when opened via data-bs-toggle
   ✅ PATCHED: Save to hidden field (#vlScheduleField) when Add Volunteer
   ✅ PATCHED: Close returns to Add Volunteer modal (if present)
=========================================================== */

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
  "11:00-12:20":{ label:"11:00–12:20 PM",group:"AM", start:660,  end:740 },

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

let currentType  = null;
let currentIndex = null;
let isEditing    = false;

let baselineSchedule = "";

// ✅ PATCH: return-to-add-volunteer flag
let __returnToAddVolunteer = false;

const impPortal = document.getElementById("impSchPortal");
const impPortalBody = impPortal ? impPortal.querySelector(".impSch-portalBody") : null;
const guardMsg = document.getElementById("impScheduleGuardMsg");
let impOwnerTd = null;
let impOwnerInput = null;

function showGuard(msg){
  if (!guardMsg) return;
  guardMsg.textContent = msg || "";
  guardMsg.style.display = msg ? "inline-block" : "none";
}

function normalizeIncomingScheduleString(s) {
  s = String(s || "");
  s = s.replace(/<br\s*\/?>/gi, " ");
  const ta = document.createElement("textarea");
  ta.innerHTML = s;
  s = ta.value;
  s = s.replace(/<[^>]*>/g, " ");
  return s.replace(/\s+/g, " ").trim();
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

function getSelectedForDay(day, excludeTd){
  const body = document.getElementById("scheduleContent");
  return Array.from(body.querySelectorAll(`td.schedule-entry[data-day="${day}"]`))
    .filter(td => td !== excludeTd)
    .map(td => String(td.dataset.value||"").trim())
    .filter(Boolean);
}

function recomputeHighlights() {
  const body = document.getElementById("scheduleContent");
  const rows = Array.from(body.querySelectorAll("tr"));

  body.querySelectorAll("td.schedule-entry").forEach(td => {
    if (!td.classList.contains("sched-empty")) td.classList.remove("sched-ok","sched-conflict");
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
        it.cell.classList.remove("sched-ok");
        prev.cell.classList.remove("sched-ok");
        it.cell.classList.add("sched-conflict");
        prev.cell.classList.add("sched-conflict");
        if (!prev || it.end > prev.end) prev = it;
      } else {
        if (!it.cell.classList.contains("sched-conflict")) it.cell.classList.add("sched-ok");
        prev = it;
      }
    });
  });
}

function sortColumn(colIdx) {
  const body = document.getElementById("scheduleContent");
  const rows = Array.from(body.querySelectorAll("tr"));

  const values = [];
  rows.forEach(row => {
    const td = row.querySelectorAll("td.schedule-entry")[colIdx];
    if (!td) return;
    const norm = normalizeTimeRange(String(td.dataset.value||"").trim());
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
    const val = k < values.length ? values[k++] : "";

    td.dataset.value = val;

    if (isEditing) {
      const input = td.querySelector("input.impSch-input");
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

/* ---------- portal dropdown ---------- */

function closeImpPortal(){
  if (!impPortal) return;
  impPortal.style.display = "none";
  impPortal.setAttribute("aria-hidden","true");
  if (impPortalBody) impPortalBody.innerHTML = "";
  impOwnerTd = null;
  impOwnerInput = null;
}

function positionImpPortal(input){
  if (!impPortal) return;
  const r = input.getBoundingClientRect();
  const gap = 8;
  const maxW = Math.min(520, window.innerWidth - 20);
  const left = Math.min(Math.max(10, r.left), window.innerWidth - 10 - maxW);
  const top = r.bottom + gap;

  impPortal.style.left = left + "px";
  impPortal.style.top = top + "px";
  impPortal.style.minWidth = r.width + "px";
  impPortal.style.maxWidth = maxW + "px";
  impPortal.style.display = "block";
  impPortal.setAttribute("aria-hidden","false");
}

function renderImpPortalFor(input, td){
  impOwnerTd = td;
  impOwnerInput = input;

  const day = td.getAttribute("data-day");
  const cur = normalizeTimeRange(String(td.dataset.value||"").trim());
  const picked = day ? getSelectedForDay(day, td).map(normalizeTimeRange) : [];

  if (!impPortalBody) return;
  impPortalBody.innerHTML = "";

  const frag = document.createDocumentFragment();

  function mkItem(value, label, active, disabled){
    const b = document.createElement("button");
    b.type = "button";
    b.className = "impSch-item" + (active ? " is-active" : "");
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
    g.className = "impSch-group";
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

  impPortalBody.appendChild(frag);
  positionImpPortal(input);
}

impPortal?.addEventListener("mousedown", (e) => {
  const btn = e.target.closest(".impSch-item");
  if (!btn || !impOwnerTd || !impOwnerInput) return;
  if (btn.disabled) return;

  e.preventDefault();
  e.stopPropagation();

  const val = normalizeTimeRange(String(btn.dataset.value||"").trim());
  impOwnerTd.dataset.value = val;

  impOwnerInput.value = val ? displayLabel(val) : "No Class";

  const sel = impOwnerTd.querySelector("select.schedule-select");
  if (sel) sel.value = val || "";

  const colIdx = days.indexOf(String(impOwnerTd.getAttribute("data-day")||""));
  if (colIdx >= 0) sortColumn(colIdx);
  recomputeHighlights();

  showGuard("");
  closeImpPortal();
});

document.addEventListener("mousedown", (e) => {
  if (!impPortal || impPortal.style.display !== "block") return;
  const insidePortal = impPortal.contains(e.target);
  const insideOwner = impOwnerInput && impOwnerInput.contains(e.target);
  if (!insidePortal && !insideOwner) closeImpPortal();
}, true);

window.addEventListener("scroll", () => closeImpPortal(), { passive:true });
window.addEventListener("resize", () => closeImpPortal());

document.getElementById("classScheduleModal")?.addEventListener("hidden.bs.modal", () => {
  closeImpPortal();
  showGuard("");

  // ✅ PATCH: after closing schedule, optionally reopen Add Volunteer modal
  if (__returnToAddVolunteer) {
    __returnToAddVolunteer = false;

    const addEl = document.getElementById("addVolunteerModal");
    if (addEl) {
      try { bootstrap.Modal.getOrCreateInstance(addEl).show(); } catch (e) {}
    }
  }
});

/* ---------- format/parse ---------- */

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

  document.querySelectorAll("#scheduleContent tr").forEach((row, ri) => {
    row.querySelectorAll("td.schedule-entry").forEach((td, di) => {
      const val = normalizeTimeRange(String(td.dataset.value||"").trim());
      updated[days[di]][ri] = val;
    });
  });

  days.forEach(d => {
    updated[d] = updated[d].filter(v => v).sort((a,b)=>{
      const ra = parseRange(a), rb = parseRange(b);
      return (ra?.start||0)-(rb?.start||0);
    });
  });

  return formatSchedule(updated);
}

function hasAnySelection(){
  const body = document.getElementById("scheduleContent");
  return Array.from(body.querySelectorAll("td.schedule-entry"))
    .some(td => String(td.dataset.value||"").trim() !== "");
}

/* ---------- Load schedule into table (view mode) ---------- */

function loadScheduleIntoModal(scheduleString) {
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
  rows = Math.max(DEFAULT_ROWS, Math.min(MAX_ROWS, rows || DEFAULT_ROWS));

  for (let i = 0; i < rows; i++) {
    const row = {};
    days.forEach(d => row[d] = scheduleData[d][i] || "");
    addScheduleRow(row);
  }

  sortAllColumns();
  recomputeHighlights();
}

/* ---------- OPEN MODAL (called from Actions list) ---------- */

function openScheduleModal(scheduleString, type, index) {
  scheduleString = normalizeIncomingScheduleString(scheduleString);

  currentType  = type;
  currentIndex = index;
  isEditing    = false;

  loadScheduleIntoModal(scheduleString);

  document.getElementById("editScheduleBtn").classList.remove("d-none");
  document.getElementById("saveScheduleBtn").classList.add("d-none");
  showGuard("");

  document.getElementById("addRowBtnFooter").onclick = () => addScheduleRow();

  const modalEl = document.getElementById("classScheduleModal");
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();

  try {
    modalEl.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  } catch (e) {}

  baselineSchedule = normalizeIncomingScheduleString(computeCurrentScheduleString());
}

/* ---------- Add row ---------- */

function addScheduleRow(data = {}) {
  const body = document.getElementById("scheduleContent");
  if (body.children.length >= MAX_ROWS) {
    if (window.FeedbackModal && typeof window.FeedbackModal.show === "function") {
      window.FeedbackModal.show({
        variant: "warning",
        title: "Limit Reached",
        subtitle: "Row limit",
        html: "You can only add up to " + MAX_ROWS + " rows."
      });
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
    updateRowNumbers();
    sortAllColumns();
    recomputeHighlights();
    showGuard("");
  };
  tdDel.appendChild(btn);
  tr.appendChild(tdDel);

  body.appendChild(tr);

  if (isEditing) {
    tr.querySelectorAll("td.schedule-entry").forEach((td, di) => createSelectInCell(td, di));
  }

  recomputeHighlights();
}

function updateRowNumbers() {
  document.querySelectorAll("#scheduleContent .schedule-time")
    .forEach((c,i)=> c.textContent = i+1);
}

/* ---------- Edit mode ---------- */

function createSelectInCell(td, colIdx) {
  const day = days[colIdx];
  td.setAttribute("data-day", day);

  const cur = normalizeTimeRange(td.dataset.value || td.textContent.trim());
  td.textContent = "";

  const wrap = document.createElement("div");
  wrap.className = "impSch-combo";

  const input = document.createElement("input");
  input.type = "text";
  input.readOnly = true;
  input.className = "impSch-input";
  input.value = cur ? displayLabel(cur) : "No Class";

  input.addEventListener("mousedown", (e) => {
    e.preventDefault();
    e.stopPropagation();
    showGuard("");
    renderImpPortalFor(input, td);
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

document.getElementById("editScheduleBtn").onclick = () => {
  isEditing = true;

  const body = document.getElementById("scheduleContent");
  body.querySelectorAll("tr").forEach(row => {
    row.querySelectorAll("td.schedule-entry").forEach((td, di) => createSelectInCell(td, di));
  });

  sortAllColumns();
  recomputeHighlights();

  document.getElementById("editScheduleBtn").classList.add("d-none");
  document.getElementById("saveScheduleBtn").classList.remove("d-none");
  showGuard("");
};

/* ---------- ✅ PATCH: Auto-init when opened via data-bs-toggle ---------- */

(function wireBootstrapOpenSeed(){
  const modalEl = document.getElementById("classScheduleModal");
  const body = document.getElementById("scheduleContent");
  if (!modalEl || !body) return;

  const hiddenAdd = document.getElementById("vlScheduleField"); // Add Volunteer hidden field
  const addRowBtn = document.getElementById("addRowBtnFooter");

  function ensureSeeded(){
    if (body.children.length > 0) return;

    const s = hiddenAdd ? (hiddenAdd.value || "") : "";
    currentType = null;
    currentIndex = null;
    isEditing = false;

    loadScheduleIntoModal(normalizeIncomingScheduleString(s));

    document.getElementById("editScheduleBtn")?.classList.remove("d-none");
    document.getElementById("saveScheduleBtn")?.classList.add("d-none");
    showGuard("");

    if (addRowBtn) addRowBtn.onclick = () => addScheduleRow();

    baselineSchedule = normalizeIncomingScheduleString(computeCurrentScheduleString());
  }

  modalEl.addEventListener("show.bs.modal", ensureSeeded);
  modalEl.addEventListener("shown.bs.modal", ensureSeeded);
})();

/* ---------- ✅ PATCH: Close should return to Add Volunteer modal ---------- */
(function wireCloseToAddVolunteer(){
  const schEl = document.getElementById("classScheduleModal");
  if (!schEl) return;

  function goBackToAddVolunteer(e){
    // Only do the "return" behavior when Add Volunteer modal exists on page
    const addEl = document.getElementById("addVolunteerModal");
    if (!addEl) return; // in Volunteer Import pages, behave normal

    // We intercept and control hide/show ourselves
    e.preventDefault();
    e.stopPropagation();

    __returnToAddVolunteer = true;

    try { bootstrap.Modal.getOrCreateInstance(schEl).hide(); } catch (err) {}
  }

  // Header X + Footer Close are both [data-bs-dismiss="modal"]
  schEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
    btn.addEventListener("click", goBackToAddVolunteer, true);
  });
})();

/* ---------- Save (patched) ---------- */

document.getElementById("saveScheduleBtn").onclick = () => {
  sortAllColumns();
  recomputeHighlights();

  if (!hasAnySelection()){
    showGuard("Please select at least one class schedule block before saving.");
    return;
  }

  const nowSchedule = normalizeIncomingScheduleString(computeCurrentScheduleString());
  if (nowSchedule === baselineSchedule){
    showGuard("No changes detected. Update at least one time slot before saving.");
    return;
  }

  const updated = {};
  days.forEach(d => updated[d] = []);

  document.querySelectorAll("#scheduleContent tr").forEach((row, ri) => {
    row.querySelectorAll("td.schedule-entry").forEach((td, di) => {
      const val = normalizeTimeRange(String(td.dataset.value||"").trim());
      updated[days[di]][ri] = val;

      td.dataset.value = val;
      td.textContent = displayLabel(val);
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

  // ✅ Add Volunteer flow: write to hidden field and return to Add Volunteer modal
  const hiddenAdd = document.getElementById("vlScheduleField");
  if (hiddenAdd) {
    hiddenAdd.value = final;

    // tell Add Volunteer modal to update button validity
    window.dispatchEvent(new Event("vl:schedule-updated"));

    // close schedule modal -> hidden handler will reopen Add Volunteer
    __returnToAddVolunteer = !!document.getElementById("addVolunteerModal");

    try { bootstrap.Modal.getOrCreateInstance(document.getElementById("classScheduleModal")).hide(); } catch {}

    // reset UI
    document.getElementById("saveScheduleBtn").classList.add("d-none");
    document.getElementById("editScheduleBtn").classList.remove("d-none");
    isEditing = false;

    baselineSchedule = normalizeIncomingScheduleString(final);
    showGuard("");
    return;
  }

  // ✅ Volunteer Import flow: submit PUT
  document.getElementById("scheduleInput").value = final;
  document.getElementById("scheduleType").value  = currentType;

  const form = document.getElementById("updateScheduleForm");
  form.action = `/volunteer-import/volunteers/${currentIndex}/update-schedule`;
  form.submit();

  document.getElementById("saveScheduleBtn").classList.add("d-none");
  document.getElementById("editScheduleBtn").classList.remove("d-none");
  isEditing = false;
};
</script>
