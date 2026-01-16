<style>
/* ===========================================================
   CLASS SCHEDULE MODAL – CRIMSON THEME (FULL, REVISED + CONFLICT-SAFE)
   ✅ Renames header/footer classes to avoid reference modal's global .modal-header/.modal-footer
   ✅ Keeps tinted strip header design
   ✅ Removes header clipping bugs (no glyph/icon crop)
   ✅ Keeps portal dropdown + schedule logic untouched
=========================================================== */

#classScheduleModal .modal-dialog { max-width: 1260px; }

/* ✅ critical: do NOT let modal-content be a clipping mask */
#classScheduleModal .modal-content{
  border-radius: 18px;
  overflow: visible;
}

/* modal shell */
#classScheduleModal .custom-schedule-modal{
  border-radius: 18px;
  overflow: hidden; /* keep rounded corners on the whole card */
  font-family: 'Segoe UI', Roboto, sans-serif;
}

/* ===========================================================
   ✅ HEADER (RENAMED: modal-header/custom-modal-header -> cs-modal-header)
   - avoids collisions with reference CSS: .modal-header { margin:-... }
=========================================================== */
#classScheduleModal .cs-modal-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;

  /* tinted header like reference */
  background: linear-gradient(180deg, rgba(178,0,12,0.16), rgba(178,0,12,0.07));
  border-bottom: 1px solid rgba(178,0,12,0.18);

  padding: 14px 18px !important;
  min-height: 66px;

  /* keep rounded top */
  border-top-left-radius: 18px;
  border-top-right-radius: 18px;

  /* anti-clip */
  overflow: visible;
}

/* left stack (icon + title) */
#classScheduleModal .cs-head-left{
  display:flex;
  align-items:center;
  gap:10px;
  min-width:0; /* allow ellipsis */
}

/* icon (block avoids baseline quirks) */
#classScheduleModal .cs-head-icon{
  display:block;
  font-size:1.6rem;
  line-height:1;
  color:#7F0008;
  opacity:.95;
}

/* title (explicit line-height + padding avoids crop) */
#classScheduleModal .cs-head-title{
  margin:0 !important;
  font-weight:900;
  font-size:1.35rem;
  letter-spacing:.2px;
  color:#7F0008;

  line-height:1.25;
  padding:2px 0;

  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  min-width:0;
}

/* close button tweaks (still uses btn-close for bootstrap) */
#classScheduleModal .cs-head-close{
  filter:none;
  opacity:.9;

  width:40px;
  height:40px;
  padding:0 !important;
  margin:0 !important;

  border-radius:10px;
}
#classScheduleModal .cs-head-close:hover{
  opacity:1;
  background: rgba(178,0,12,0.10);
}

/* body */
#classScheduleModal .custom-modal-body{ background:#fff; padding:1.15rem 1.5rem 1rem; }

#classScheduleModal .schedule-hint{
  border-radius:10px;
  border:1px dashed #f3c2c7;
  background:#fff5f6;
  padding:0.55rem 0.85rem;
  font-size:0.85rem;
  color:#5c1b24;
  margin-bottom:0.9rem;
}

#classScheduleModal .schedule-table{ width:100%; border-collapse:collapse; font-size:0.9rem; }
#classScheduleModal .schedule-table th,
#classScheduleModal .schedule-table td{ border:1px solid #f0d0d3; padding:0.45rem 0.5rem; }

#classScheduleModal .schedule-table thead th{ background:#9b2733; color:#fff; font-weight:600; }
#classScheduleModal .schedule-table thead th:first-child{ width:46px; }
#classScheduleModal .schedule-table thead th:last-child{ width:80px; }

#classScheduleModal .schedule-time{ font-weight:600; color:#7f1d26; }

#classScheduleModal .schedule-entry{
  font-size:0.88rem;
  background:#ffffff;
  transition: background-color 0.15s ease, border-color 0.15s ease;
}

#classScheduleModal .schedule-entry.sched-empty{ background:#ffffff; }
#classScheduleModal .schedule-entry.sched-ok{ background:#e6f9ea; border-color:#c3e6cb; }
#classScheduleModal .schedule-entry.sched-conflict{ background:#ffe6e6; border-color:#f5c2c7; }

#classScheduleModal .btn-row-delete{ padding:0.28rem 0.55rem; }

/* ===========================================================
   ✅ FOOTER (RENAMED: modal-footer/custom-modal-footer -> cs-modal-footer)
   - avoids collisions with reference CSS: .modal-footer { ... }
=========================================================== */
#classScheduleModal .cs-modal-footer{
  background:#fafafa;
  border-top:1px solid #eee;
  padding:0.8rem 1.5rem;

  /* keep footer corners rounded */
  border-bottom-left-radius: 18px;
  border-bottom-right-radius: 18px;
}

#classScheduleModal .btn-add-row{ background:#b2000c; border-color:#b2000c; }
#classScheduleModal .btn-add-row:hover{ background:#8e0009; border-color:#8e0009; }
#classScheduleModal .btn-crimson{ background:#b2000c; border-color:#b2000c; }
#classScheduleModal .btn-crimson:hover{ background:#8e0009; border-color:#8e0009; }

/* ===========================================================
   ✅ PORTAL DROPDOWN (vlScheduleModal style) – unchanged
=========================================================== */
#classScheduleModal select.schedule-select{
  position:absolute !important;
  left:-99999px !important;
  width:1px !important;
  height:1px !important;
  opacity:0 !important;
  pointer-events:none !important;
}

#classScheduleModal td.schedule-entry{ min-width:150px; }

#classScheduleModal .impSch-combo{ position:relative; width:100%; }
#classScheduleModal .impSch-input{
  width:100%;
  min-height:34px;
  border-radius:999px;
  border:1px solid #c53c45;
  background:#fff;
  color:#5c1b24;
  font-weight:900;
  font-size:.82rem;
  padding:.25rem 3.0rem .25rem .85rem;
  outline:none;
  cursor:pointer;
  box-sizing:border-box;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  background-image:
    linear-gradient(45deg, transparent 50%, #9b2733 50%),
    linear-gradient(135deg, #9b2733 50%, transparent 50%);
  background-position:
    calc(100% - 18px) 12px,
    calc(100% - 14px) 12px;
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
  min-width:260px;
}
#impSchPortal .impSch-portalBody{ max-height:340px; overflow:auto; }

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
  border-top: 1px solid rgba(15,23,42,.08);
}

#classScheduleModal .impSchErr{
  display:none;
  color:#b91c1c;
  font-weight:950;
  font-size:12px;
  margin-right:.6rem;
}
</style>

{{-- ============================================================
   ✅ CLASS SCHEDULE MODAL
============================================================ --}}
<div class="modal fade" id="classScheduleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content custom-schedule-modal">

      {{-- ✅ HEADER (RENAMED – avoids reference collisions) --}}
      <div class="cs-modal-header">
        <div class="cs-head-left">
          <i class="fa-solid fa-calendar-days cs-head-icon" aria-hidden="true"></i>
          <h5 class="cs-head-title m-0">Class Schedule</h5>
        </div>
        <button type="button" class="btn-close cs-head-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

      {{-- ✅ FOOTER (RENAMED – avoids reference collisions) --}}
      <div class="cs-modal-footer d-flex justify-content-between">
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

{{-- ============================================================
   ✅ HIDDEN SERVER PAYLOADS
   NOTE: we keep only ONE success path -> FeedbackModal
============================================================ --}}
@if(session('success_schedule'))
  <div id="__server_success_schedule_html__" style="display:none;">
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
/* ===========================================================
   ✅ SUCCESS FLASH -> UNIVERSAL FEEDBACK MODAL (UFM)
   - Prevents duplicate firing across other modals using same session key
   - Uses UFM global lock for single-fire
   - Adds "See more" recall (click bypasses lock)
=========================================================== */
(function () {
  // ✅ prevents other modals that also read success_schedule from firing too
  if (!window.__FLASH_CONSUMED__) window.__FLASH_CONSUMED__ = {};

  function escHtml(s){
    return String(s ?? '')
      .replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")
      .replace(/"/g,"&quot;").replace(/'/g,"&#039;");
  }

  function b64utf8Encode(str){
    try {
      const bytes = new TextEncoder().encode(String(str ?? ''));
      let bin = '';
      bytes.forEach(b => bin += String.fromCharCode(b));
      return btoa(bin);
    } catch (e) {
      try { return btoa(unescape(encodeURIComponent(String(str ?? '')))); }
      catch (_) { return ''; }
    }
  }

  function ensureSeeMoreLink(html){
    const hasDetailsLink = /data-ufm-details=|data-details=/i.test(html) || /see\s*more/i.test(html);
    if (hasDetailsLink) return html;

    const b64 = b64utf8Encode(html);
    return String(html || '') + `
      <div style="margin-top:10px;">
        <a href="#"
           class="success"
           data-ufm-details="${escHtml(b64)}"
           style="font-weight:600; text-decoration:underline;">
          See more
        </a>
      </div>
    `;
  }

  function showScheduleSuccess(html){
    // ✅ consume shared flash so other modals won't show it too
    window.__FLASH_CONSUMED__.success_schedule = true;

    // (optional) store last payload for recall-by-code
    if (!window.__UFM_LAST__) window.__UFM_LAST__ = null;
    window.__UFM_LAST__ = {
      variant: 'success',
      title: 'Schedule Updated',
      subtitle: 'Class schedule saved successfully.',
      html: html,
      source: 'class_schedule_flash_success'
    };

    window.FeedbackModal.show({
      variant: 'success',
      title: 'Schedule Updated',
      subtitle: 'Class schedule saved successfully.',
      html: ensureSeeMoreLink(html),
      source: 'class_schedule_flash_success'
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    // ✅ if already consumed elsewhere, do nothing
    if (window.__FLASH_CONSUMED__.success_schedule) return;

    const el = document.getElementById('__server_success_schedule_html__');
    if (!el) return;

    const html = (el.innerHTML || '').trim();
    if (!html) return;

    let tries = 0;
    const maxTries = 80; // ~2 seconds
    const t = setInterval(function(){
      tries++;

      if (window.FeedbackModal?.show) {
        clearInterval(t);

        // race-safe: check again before showing
        if (window.__FLASH_CONSUMED__.success_schedule) return;

        showScheduleSuccess(html);
        return;
      }

      if (tries >= maxTries) {
        clearInterval(t);
        console.error('[CS FLASH] FeedbackModal not available - check UFM js load/order.');
      }
    }, 25);
  });
})();
</script>

<script>
/* ===========================================================
   ✅ Schedule JS – portal dropdown + guards + 3 rows default
=========================================================== */

const MAX_ROWS = 6;
const DEFAULT_ROWS = 3;
const days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

const timeMeta = {
  // Morning
  "7:30-8:20":  { label:"7:30–8:20 AM",  group:"AM", start:450,  end:500 },
  "7:30-8:50":  { label:"7:30–8:50 AM",  group:"AM", start:450,  end:530 },
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
  "6:30-8:50":  { label:"6:30–8:50 PM",  group:"PM", start:1110, end:1250}
};
const timeOptions = Object.keys(timeMeta);

let currentType  = null;
let currentIndex = null;
let isEditing    = false;

/* ✅ baseline canonical string (for "no changes" guard) */
let baselineCanonical = "";

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

/* ✅ canonicalize any schedule string into the exact final format */
function canonicalizeScheduleString(scheduleString){
  scheduleString = normalizeIncomingScheduleString(scheduleString);
  const scheduleData = {};
  days.forEach(day => {
    const regex = new RegExp(`${day}:([^]*?)(?=Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|$)`,"i");
    const match = scheduleString.match(regex);
    let raw = match ? match[1].trim() : "";
    raw = raw.replace(/No Class/gi,"").replace(/[,;]+/g," ").trim();
    scheduleData[day] = raw ? raw.split(/\s+/).map(normalizeTimeRange) : [];
    scheduleData[day] = scheduleData[day]
      .filter(Boolean)
      .sort((a,b)=> (parseRange(a)?.start||0) - (parseRange(b)?.start||0));
  });

  return days.map(day => {
    const arr = scheduleData[day];
    if (!arr.length) return `${day}: No Class`;
    return `${day}: ${arr.join(" ")}`;
  }).join(" ").replace(/\s+/g," ").trim();
}

function hasAnySelection(){
  const body = document.getElementById("scheduleContent");
  return Array.from(body.querySelectorAll("td.schedule-entry"))
    .some(td => String(td.dataset.value||"").trim() !== "");
}

function computeGridCanonical(){
  const updated = {};
  days.forEach(d => updated[d] = []);

  document.querySelectorAll("#scheduleContent tr").forEach((row) => {
    row.querySelectorAll("td.schedule-entry").forEach((td, di) => {
      const val = normalizeTimeRange(String(td.dataset.value||"").trim());
      if (val) updated[days[di]].push(val);
    });
  });

  days.forEach(d => {
    updated[d] = updated[d].filter(Boolean).sort((a,b)=> (parseRange(a)?.start||0)-(parseRange(b)?.start||0));
  });

  return days.map(day => {
    const arr = updated[day];
    if (!arr.length) return `${day}: No Class`;
    return `${day}: ${arr.join(" ")}`;
  }).join(" ").replace(/\s+/g," ").trim();
}

/* ---------- conflict highlight ---------- */
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

/* ---------- sorting by day column ---------- */
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

  values.sort((a,b) => (parseRange(a)?.start||0) - (parseRange(b)?.start||0));

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
function sortAllColumns(){ for (let i=0;i<days.length;i++) sortColumn(i); }

/* ---------- portal ---------- */
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
  const maxW = Math.min(560, window.innerWidth - 20);
  const left = Math.min(Math.max(10, r.left), window.innerWidth - 10 - maxW);
  const top = r.bottom + gap;

  impPortal.style.left = left + "px";
  impPortal.style.top = top + "px";
  impPortal.style.minWidth = Math.max(260, r.width) + "px";
  impPortal.style.maxWidth = maxW + "px";
  impPortal.style.display = "block";
  impPortal.setAttribute("aria-hidden","false");
}

function getSelectedForDay(day, excludeTd){
  const body = document.getElementById("scheduleContent");
  return Array.from(body.querySelectorAll(`td.schedule-entry[data-day="${day}"]`))
    .filter(td => td !== excludeTd)
    .map(td => normalizeTimeRange(String(td.dataset.value||"").trim()))
    .filter(Boolean);
}

function renderImpPortalFor(input, td){
  impOwnerTd = td;
  impOwnerInput = input;

  const day = td.getAttribute("data-day");
  const cur = normalizeTimeRange(String(td.dataset.value||"").trim());
  const picked = day ? getSelectedForDay(day, td) : [];

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
  am.sort((a,b)=>timeMeta[a].start-timeMeta[b].start);
  pm.sort((a,b)=>timeMeta[a].start-timeMeta[b].start);

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

  const colIdx = days.indexOf(String(impOwnerTd.getAttribute("data-day")||""));
  if (colIdx >= 0) sortColumn(colIdx);

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
});

/* ---------- rows ---------- */
function addScheduleRow(data = {}) {
  const body = document.getElementById("scheduleContent");
  if (body.children.length >= MAX_ROWS) {
    window.FeedbackModal?.show?.({
      variant: "warning",
      title: "Limit Reached",
      subtitle: "Row limit",
      html: "You can only add up to " + MAX_ROWS + " rows."
    });
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
    sortAllColumns();
  } else {
    recomputeHighlights();
  }
}

function updateRowNumbers() {
  document.querySelectorAll("#scheduleContent .schedule-time").forEach((c,i)=> c.textContent = i+1);
}

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

  wrap.appendChild(input);
  td.appendChild(wrap);
  td.appendChild(select);

  td.dataset.value = cur || "";
  markCellState(td, cur || "");
}

/* ---------- view load ---------- */
function loadScheduleIntoModal(scheduleString) {
  const body = document.getElementById("scheduleContent");
  body.innerHTML = "";

  const canonical = canonicalizeScheduleString(scheduleString);

  const scheduleData = {};
  days.forEach(day => {
    const regex = new RegExp(`${day}:([^]*?)(?=Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|$)`,"i");
    const match = canonical.match(regex);
    let raw = match ? match[1].trim() : "";
    raw = raw.replace(/No Class/gi,"").replace(/[,;]+/g," ").trim();
    scheduleData[day] = raw ? raw.split(/\s+/).map(normalizeTimeRange) : [];
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

/* ---------- OPEN MODAL ---------- */
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
  const modal = new bootstrap.Modal(modalEl);
  modal.show();

  try {
    modalEl.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  } catch (e) {}

  baselineCanonical = canonicalizeScheduleString(scheduleString);
}

/* ---------- Edit ---------- */
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

/* ---------- Save guards ---------- */
document.getElementById("saveScheduleBtn").onclick = () => {
  sortAllColumns();
  recomputeHighlights();

  if (!hasAnySelection()){
    showGuard("Please select at least one class schedule block before saving.");
    return;
  }

  const nowCanonical = computeGridCanonical();
  if (nowCanonical === baselineCanonical){
    showGuard("No changes detected. Update at least one time slot before saving.");
    return;
  }

  document.getElementById("scheduleInput").value = nowCanonical;
  document.getElementById("scheduleType").value  = currentType;

  const form = document.getElementById("updateScheduleForm");
  form.action = `/volunteer-import/volunteers/${currentIndex}/update-schedule`;
  form.submit();
};
</script>
