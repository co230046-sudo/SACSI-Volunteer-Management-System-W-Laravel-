<!-- Class Schedule Modal -->
<div class="modal fade" id="classScheduleModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content custom-schedule-modal">

      <!-- Modal Header -->
      <div class="modal-header custom-modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title"><i class="fa-solid fa-calendar-days me-2"></i> Class Schedule</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body custom-modal-body">
        <div class="weekly-schedule">
          <table class="table schedule-table text-center">
            <thead>
              <tr>
                <th>Time</th>
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

      <!-- Modal Footer -->
      <div class="modal-footer custom-modal-footer d-flex justify-content-between">
        <div>
          <button type="button" class="btn btn-danger me-2" id="addRowBtnFooter">
            <i class="fa-solid fa-plus me-1"></i> Add Row
          </button>
        </div>
        <div>
          <button type="button" class="btn btn-secondary" id="editScheduleBtn">
            <i class="fa-solid fa-pen-to-square me-1"></i> Edit
          </button>
          <button type="button" class="btn btn-success d-none" id="saveScheduleBtn">
            <i class="fa-solid fa-save me-1"></i> Save
          </button>
          <button type="button" class="btn btn-danger" data-bs-dismiss="modal">
            <i class="fa-solid fa-xmark me-1"></i> Close
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Class Schedule Message Modal -->
<div id="scheduleMessageModal" class="schedule-modal-overlay" style="display:none;">
  <div class="schedule-modal">
    <h3 class="modal-title">
      <i class="fa-solid fa-circle-exclamation" style="color:#d9534f;"></i> Notice
    </h3>
    <div class="modal-content-wrapper">
      <p id="scheduleMessageText">This is a message</p>
    </div>
    <div class="modal-buttons">
      <button type="button" class="btn btn-secondary" onclick="closeScheduleMessageModal()">OK</button>
    </div>
  </div>
</div>

<style>
/* Overlay */
.schedule-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

/* Modal box */
/* Modal box with dynamic width */
.schedule-modal {
  background: #fff;
  border-radius: 8px;
  padding: 1rem 1.5rem;
  width: max-content; /* width adjusts to content */
  max-width: 90vw;    /* prevent overflow on small screens */
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-sizing: border-box; /* include padding in width */
}


/* Modal title */
.schedule-modal .modal-title {
  text-align: center; /* title centered */
  margin-bottom: 0.5rem;
}

/* Content wrapper for scrolling */
.modal-content-wrapper {
  flex: 1 1 auto;
  overflow-y: auto;
  margin: 0.5rem 0 1rem;
  word-break: break-word; /* wrap long lines */
  text-align: left; /* message text left-aligned */
}

/* Buttons */
.modal-buttons {
  text-align: right;
}

/* Optional: scrollbar styling */
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
</style>


<!-- Hidden form for PUT submission -->
<form id="updateScheduleForm" method="POST" style="display:none;">
    @csrf
    @method('PUT')
    <input type="hidden" name="schedule" id="scheduleInput">
</form>

<script>
const MAX_ROWS = 6;
const timeOptions = [
  "7:30-8:00","8:00-10:50","8:00-11:00","8:30-9:30","9:30-10:50",
  "11:00-12:20","12:30-1:50","2:00-3:20","2:00-3:30","2:00-4:50",
  "3:00-4:50","3:30-5:00","5:00-6:20","5:00-6:30","6:30-7:20","6:30-7:30"
];

let currentType = null;
let currentIndex = null;
let currentVolunteerId = null;
let isEditing = false; 
const days = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

// ----- Style for disabled options -----
const style = document.createElement('style');
style.innerHTML = `select option:disabled { color: #aaa; font-style: italic; }`;
document.head.appendChild(style);

// Normalize HH:MM-HH:MM and return closest match in timeOptions if exists
function normalizeTimeRange(timeStr) {
    if (!timeStr) return "";
    const parts = timeStr.split('-').map(p => p.trim());
    if (parts.length !== 2) return timeStr;
    const normalized = parts.map(p => /^\d{1,2}$/.test(p) ? p + ":00" : p).join('-');
    // Try to match closest from timeOptions
    const match = timeOptions.find(opt => opt === normalized);
    return match || normalized;
}

// Convert HH:MM -> minutes
function timeToMinutes(t) {
    const [h,m] = t.split(':').map(Number);
    return h*60 + m;
}

// Compare by start time
function compareTimeRanges(a,b){
    return timeToMinutes(a.split('-')[0]) - timeToMinutes(b.split('-')[0]);
}

// ----- HELPER: create select inside a cell -----
function createSelectInCell(td, colIdx, selectedPerDay) {
  const day = days[colIdx];
  let currentValue = td.textContent.trim();
  currentValue = normalizeTimeRange(currentValue); // ✅ normalize
  td.textContent = '';

  const select = document.createElement("select");
  select.classList.add("form-select","form-select-sm");
  select.setAttribute('data-prev',currentValue);

  const placeholder = document.createElement("option");
  placeholder.value = "";
  placeholder.text = "No Class";
  select.appendChild(placeholder);

  timeOptions.forEach(opt => {
    const option = document.createElement("option");
    option.value = opt; 
    option.text = opt;
    if(selectedPerDay[day].includes(opt) && opt!==currentValue) option.disabled=true;
    select.appendChild(option);
  });

  // Set select to closest option or current value if not in options
  select.value = timeOptions.includes(currentValue) ? currentValue : "";
  if(!select.value && currentValue) {
      const customOption = document.createElement("option");
      customOption.value = currentValue;
      customOption.text = currentValue + " (Custom)";
      select.appendChild(customOption);
      select.value = currentValue;
  }

  select.addEventListener('change', e=>{
    const sel=e.target;
    const oldVal=sel.getAttribute('data-prev')||'';
    const newVal=sel.value;

    const idx=selectedPerDay[day].indexOf(oldVal);
    if(idx>-1) selectedPerDay[day].splice(idx,1);
    if(newVal) selectedPerDay[day].push(newVal);

    sel.setAttribute('data-prev',newVal);

    // update other selects in same column
    document.querySelectorAll("#scheduleContent tr").forEach(r=>{
      const cell=r.querySelectorAll("td.schedule-entry")[colIdx];
      const otherSel=cell.querySelector("select");
      if(otherSel && otherSel!==sel){
        otherSel.querySelectorAll("option").forEach(opt=>{
          if(opt.value && opt.value!==otherSel.value) opt.disabled=selectedPerDay[day].includes(opt.value);
        });
      }
    });
  });

  td.appendChild(select);
}

// ----- OPEN MODAL -----
function openScheduleModal(scheduleString, type, index, volunteerId) {
  currentType = type;
  currentIndex = index;
  currentVolunteerId = volunteerId;
  isEditing = false;

  document.getElementById('editScheduleBtn').classList.remove('d-none');
  document.getElementById('saveScheduleBtn').classList.add('d-none');

  const container = document.getElementById('scheduleContent');
  container.innerHTML = '';

  const scheduleData = {};
  days.forEach(day => {
    const regex = new RegExp(day+":\\s*([^]*?)(?=(Monday|Tuesday|Wednesday|Thursday|Friday|Saturday|$))","i");
    const match = scheduleString.match(regex);
    let raw = (match && match[1]) ? match[1].trim() : "";
    raw = raw.replace(/No Class/gi,'').trim();
    scheduleData[day] = raw ? raw.split(/\s+/).filter(Boolean).map(normalizeTimeRange) : [];
    scheduleData[day].sort(compareTimeRanges);
  });

  let numRows = Math.max(...days.map(day=>scheduleData[day].length));
  if(!Number.isFinite(numRows)||numRows<=0) numRows=1;
  numRows = Math.min(MAX_ROWS,numRows);

  for(let r=0;r<numRows;r++){
    const rowData = {};
    days.forEach(day => rowData[day] = scheduleData[day][r] || "");
    addScheduleRow(rowData);
  }

  document.getElementById('addRowBtnFooter').onclick = ()=>addScheduleRow();
  new bootstrap.Modal(document.getElementById('classScheduleModal')).show();
}

// ----- ADD ROW -----
function addScheduleRow(rowData=null){
  const container=document.getElementById('scheduleContent');
  if(container.children.length>=MAX_ROWS){
    showMessageModal("You can only add up to "+MAX_ROWS+" rows."); return;
  }

  const tr=document.createElement('tr');
  tr.innerHTML=`<td class="schedule-time">${container.children.length+1}</td>`;

  days.forEach(day=>{
    const td=document.createElement('td');
    td.classList.add('schedule-entry');
    const value=rowData && rowData[day]?normalizeTimeRange(rowData[day]):"";
    td.textContent=value;
    td.style.backgroundColor=value?"#d4edda":"";
    tr.appendChild(td);
  });

  const delTd=document.createElement('td');
  const delBtn=document.createElement('button');
  delBtn.type='button';
  delBtn.className='btn btn-sm btn-danger delete-row-btn';
  delBtn.innerHTML='<i class="fa-solid fa-trash"></i>';
  delBtn.addEventListener("click",()=>{ tr.remove(); updateRowNumbers(); });
  delTd.appendChild(delBtn);
  tr.appendChild(delTd);

  container.appendChild(tr);

  // Convert new row to select if editing
  if(isEditing){
    const selectedPerDay={};
    days.forEach((day,idx)=>{
      selectedPerDay[day]=[];
      container.querySelectorAll("tr").forEach(row=>{
        const val=row.querySelectorAll("td.schedule-entry")[idx].textContent.trim();
        if(val) selectedPerDay[day].push(val);
      });
    });
    tr.querySelectorAll("td.schedule-entry").forEach((td,colIdx)=>{
      createSelectInCell(td,colIdx,selectedPerDay);
    });
  }
}

// ----- UPDATE ROW NUMBERS -----
function updateRowNumbers(){
  document.querySelectorAll("#scheduleContent tr").forEach((row,idx)=>{
    row.querySelector(".schedule-time").textContent = idx+1;
  });
}

// ----- EDIT SCHEDULE -----
document.getElementById('editScheduleBtn').addEventListener('click',()=>{
  isEditing = true;
  const container=document.querySelector("#scheduleContent");
  const selectedPerDay={};

  days.forEach((day,idx)=>{
    selectedPerDay[day]=[];
    container.querySelectorAll("tr").forEach(row=>{
      const val=row.querySelectorAll("td.schedule-entry")[idx].textContent.trim();
      if(val) selectedPerDay[day].push(val);
    });
  });

  container.querySelectorAll("tr").forEach(row=>{
    row.querySelectorAll("td.schedule-entry").forEach((td,colIdx)=>{
      createSelectInCell(td,colIdx,selectedPerDay);
    });
  });

  document.getElementById('editScheduleBtn').classList.add('d-none');
  document.getElementById('saveScheduleBtn').classList.remove('d-none');
});

// ----- SAVE SCHEDULE -----
document.getElementById('saveScheduleBtn').addEventListener('click',()=>{
  const updatedSchedule={};
  days.forEach(day=>updatedSchedule[day]=[]);

  document.querySelectorAll("#scheduleContent tr").forEach((row,rIdx)=>{
    row.querySelectorAll("td.schedule-entry").forEach((td,cIdx)=>{
      const sel=td.querySelector("select");
      let val=sel?sel.value.trim():td.textContent.trim();
      val = normalizeTimeRange(val);
      updatedSchedule[days[cIdx]][rIdx]=val;
      td.textContent=val;
      td.style.backgroundColor=val?"#d4edda":"";
    });
  });

  days.forEach(day=>updatedSchedule[day].sort(compareTimeRanges));
  const displaySchedule={};
  days.forEach(day=>displaySchedule[day]=updatedSchedule[day].map(t=>t||"No Class"));
  const scheduleStr=formatScheduleString(displaySchedule);

  const form=document.getElementById('updateScheduleForm');
  document.getElementById('scheduleInput').value=scheduleStr;

  let typeInput=form.querySelector("input[name='type']");
  if(!typeInput){
    typeInput=document.createElement('input');
    typeInput.type='hidden';
    typeInput.name='type';
    form.appendChild(typeInput);
  }
  typeInput.value=currentType;

  form.action=`/volunteer-import/volunteers/${currentIndex}/update-schedule`;
  form.submit();

  document.getElementById('saveScheduleBtn').classList.add('d-none');
  document.getElementById('editScheduleBtn').classList.remove('d-none');
  isEditing = false;
});

// ----- FORMAT SCHEDULE STRING -----
function formatScheduleString(scheduleObj){
  return Object.entries(scheduleObj).map(([day,times])=>{
    return day+": "+(times.length?times.join(" "):"No Class");
  }).join(' ');
}

// ----- MODALS -----
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

function showScheduleMessageModal(message){
  const overlay=document.getElementById('scheduleMessageModal');
  const textEl=document.getElementById('scheduleMessageText');
  textEl.innerHTML=message;
  overlay.style.display='flex';
}
function closeScheduleMessageModal(){
  const overlay=document.getElementById('scheduleMessageModal');
  overlay.style.display='none';
}

// RESET BUTTONS IF MODAL CLOSES
document.getElementById('classScheduleModal').addEventListener('hidden.bs.modal',()=>{
  document.getElementById('saveScheduleBtn').classList.add('d-none');
  document.getElementById('editScheduleBtn').classList.remove('d-none');
  isEditing = false;
});

// Auto-show Laravel flash messages
document.addEventListener("DOMContentLoaded", function() {
    @if(session('success') || session('info'))
        const msg = {!! json_encode(session('success') ?? session('info')) !!};
        showScheduleMessageModal(msg);
    @endif
});
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const persistKey = 'persistSection';
    const defaultSectionId = 'import-Section-invalid';

    // Check if there’s a redirect section from the server
    @if(session('last_updated_table'))
        const updatedSection = "{{ session('last_updated_table') }}"; // 'valid' or 'invalid'
        const updatedId = updatedSection === 'valid' ? 'import-Section-valid' : defaultSectionId;

        // Store in sessionStorage with persistence count
        sessionStorage.setItem(persistKey, JSON.stringify({ section: updatedId, remaining: 2 }));
    @endif

    // Read from sessionStorage
    let data = sessionStorage.getItem(persistKey);
    let targetId = defaultSectionId;

    if(data){
        try {
            data = JSON.parse(data);
            if(data.remaining > 0){
                targetId = data.section;
                data.remaining--;
                sessionStorage.setItem(persistKey, JSON.stringify(data));
            } else {
                sessionStorage.removeItem(persistKey);
            }
        } catch(e){
            sessionStorage.removeItem(persistKey);
        }
    }

    // Scroll to the target section
    const targetEl = document.getElementById(targetId);
    if(targetEl){
        targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Optional highlight
        targetEl.style.transition = "background-color 0.5s";
        targetEl.style.backgroundColor = "#fff3cd";
        setTimeout(() => targetEl.style.backgroundColor = "", 2000);
    }
});
</script>


<style>/* ---- Red-themed Select ---- */
.form-select.form-select-sm {
  height: 30px;                 /* shorter height */
  padding: 0 0.5rem;
  font-size: 0.85rem;
  background-color: #fff5f5;    /* light red background */
  border: 1px solid #e3342f;    /* red border */
  color: #e3342f;               /* red text */
  border-radius: 0.25rem;
  appearance: none;             /* remove default arrow */
  cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
}

/* Hover / focus effect */
.form-select.form-select-sm:hover,
.form-select.form-select-sm:focus {
  border-color: #c53030;
  box-shadow: 0 0 0 2px rgba(227,52,47,0.2);
  outline: none;
}

/* ---- Option styling ---- */
.form-select.form-select-sm option {
  padding: 0.25rem 0.5rem;     /* smaller padding */
  font-size: 0.85rem;
  background-color: #fff5f5;
  color: #e3342f;
}

/* Disabled option style (already added) */
.form-select.form-select-sm option:disabled {
  color: #aaa;
  font-style: italic;
}

/* ---- Custom scrollbar for dropdown (Webkit only) ---- */
.form-select.form-select-sm::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.form-select.form-select-sm::-webkit-scrollbar-track {
  background: #fee2e2;  /* light red track */
  border-radius: 4px;
}

.form-select.form-select-sm::-webkit-scrollbar-thumb {
  background: #e3342f;  /* red thumb */
  border-radius: 4px;
}

.form-select.form-select-sm::-webkit-scrollbar-thumb:hover {
  background: #c53030;
}

/* ---- Optional: smaller arrow indicator ---- */
.form-select.form-select-sm::after {
  border-color: #e3342f transparent transparent transparent;
}
</style>


<!-- Custom CSS -->
<style>
.custom-schedule-modal {
  border-radius: 15px;
  font-family: 'Segoe UI', Roboto, sans-serif;
  overflow: hidden;
}

.custom-modal-header {
  background-color: #c82333;
  color: white;
  font-weight: 600;
  border-bottom: none;
}

.custom-modal-body {
  background-color: #fff5f5;
  padding: 1rem 1.5rem;
}

.schedule-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.schedule-table th, .schedule-table td {
  border: 1px solid #f1c0c3;
  padding: 0.5rem;
}

.schedule-table th {
  background-color: #e4606d;
  color: white;
  font-weight: 600;
}

.schedule-table tbody tr:nth-child(even) {
  background-color: #ffe5e8;
}

.schedule-table tbody tr:hover {
  background-color: #f9b2bc;
}

.schedule-time {
  font-weight: 600;
  color: #b71c1c;
}

.schedule-entry {
  font-weight: 500;
  color: #4d0000;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  background-color: #f8d0d5;
  margin: 2px 0;
}
</style>
