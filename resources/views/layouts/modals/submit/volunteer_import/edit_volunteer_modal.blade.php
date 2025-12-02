<style>
/* Modal Base */
.edit-volunteer-modal {
  position: fixed;
  inset: 0;
  display: none;
  z-index: 9999;
  font-family: 'Segoe UI', Roboto, sans-serif;
}
.edit-volunteer-modal.is-open {
  display: flex;
  justify-content: center;
  align-items: center;
}
.edit-volunteer-modal .modal-overlay {
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.55);
  display: flex;
  justify-content: center;
  align-items: center;
}

/* Modal Content */
.edit-volunteer-modal .modal-content {
  background: #fff;
  border-radius: 16px;
  width: 100%;
  max-width: 850px;
  max-height: 90vh;
  padding: 2rem;
  box-shadow: 0 12px 40px rgba(0,0,0,0.25);
  overflow-y: auto;
  animation: slideIn 0.3s ease forwards;
}

/* Autofill default (no class) */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0px 1000px #fff inset !important; /* default white */
  -webkit-text-fill-color: #000 !important;
  transition: background-color 5000s ease-in-out 0s;
}

/* Autofill when valid */
input.valid:-webkit-autofill,
input.valid:-webkit-autofill:hover,
input.valid:-webkit-autofill:focus,
input.valid:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0px 1000px #e6f9ea inset !important;
  -webkit-text-fill-color: #000 !important;
}

/* Autofill when invalid */
input.invalid:-webkit-autofill,
input.invalid:-webkit-autofill:hover,
input.invalid:-webkit-autofill:focus,
input.invalid:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0px 1000px #ffe6e6 inset !important;
  -webkit-text-fill-color: #000 !important;
}

/* Header */
.modal-header {
    display:flex;
    align-items:center;
    gap:0.5rem;
    margin-bottom:1.5rem;
}

.modal-header h2 { 
  font-size:1.6rem; 
  color:#B2000C; 
  margin:0; 
}

.modal-icon { 
  font-size:2rem; 
  color:#B2000C; 
  transition: transform 0.25s ease, color 0.25s ease; 
}

.modal-icon:hover { 
  transform: rotate(-15deg); 
}

/* Input Grid */
.input-grid {
  display: grid;
  grid-template-columns: repeat(2,1fr);
  gap:1rem;
  margin-bottom:1.5rem;
}

/* Input Container */
.volunteer-info {
  position: relative;
  display: flex;
  flex-direction: column;
  margin-bottom: 1.2rem;
}
.volunteer-info label {
  font-size:0.9rem;
  color:#555;
  margin-top:1rem;
  font-weight:500;
}

.input-wrapper { 
  position: relative; 
}

/* Inputs & Selects */
.volunteer-info input,
.volunteer-info select {
  width: 100%;
  padding: 0.6rem 0.75rem 0.6rem 2.5rem;
  border-radius:8px;
  border:1px solid #ccc;
  font-size:1rem;
  transition: all 0.25s ease;
  background: #fff;
  color: #000;
}

/* Select specific styling */
.volunteer-info select {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  cursor: pointer;
  background-size: 12px;
  padding-right: 2rem;
}

/* Disabled District */
#district {
  cursor: not-allowed;
  background-color: #f7f7f7 !important;
  color: #555 !important;
}

/* Move input-icon */
#barangay + .input-icon {
  left: 0.75rem;
  right: auto;
}

/* Focus states */
.volunteer-info input:focus,
.volunteer-info select:focus {
  outline: none;
  border-color: #B2000C;
}

/* Valid / Invalid */
.volunteer-info input.invalid,
.volunteer-info select.invalid {
  border-color: #dc3545 !important;
  background: #ffe6e6 !important;
}
.volunteer-info input.valid,
.volunteer-info select.valid {
  border-color: #28a745 !important;
  background: #e6f9ea !important;
}

/* Input Icon */
.input-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #942a2a;
  font-size: 1.2rem;
  pointer-events: none;
  transition: transform 0.25s ease, color 0.25s ease;
}

/* Error tooltip */
.error-tooltip {
  display: block;
  font-size: 0.75rem;
  color: #fff;
  background: #dc3545;
  padding: 0.35rem 0.5rem;
  border-radius: 5px;
  position: absolute;
  top: 110%;
  left: 0;
  z-index: 10;
  opacity: 0; /* will fade in via JS */
  transition: opacity 0.25s ease;
}

.volunteer-info select.invalid ~ .error-tooltip,
.volunteer-info input.invalid ~ .error-tooltip {
  opacity: 1;
}

/* --- Course, Barangay, District visual consistency --- */
#barangay.valid, #course.valid, #district.valid {
  border-color: #28a745 !important;
  background: #e6f9ea !important;
}
#barangay.invalid, #course.invalid, #district.invalid {
  border-color: #dc3545 !important;
  background: #ffe6e6 !important;
}

/* District is readonly but will still show valid/invalid color */
#district[readonly] {
  cursor: not-allowed;
  color: #555;
}

/* Footer Buttons */
.modal-footer { 
  display:flex; 
  justify-content:center; 
  gap:1rem; flex-wrap:wrap; 
  margin-top:1rem; 
}

.modal-btn { 
  display:flex; 
  align-items:center; 
  justify-content:center; 
  gap:0.5rem; 
  padding:0.65rem 1.8rem; 
  font-size:1rem; 
  font-weight:600; 
  border-radius:8px; 
  cursor:pointer; 
  border:none; 
  transition: all 0.25s ease; 
  height:50px; 
}

.modal-btn.cancel { 
  background:#f1f1f1; 
  color:#333; 
}

.modal-btn.cancel:hover { 
  background:#e0e0e0; 
  transform:scale(1.05); 
}

.modal-btn.save { 
  background:#B2000C; 
  color:#fff; 
}

.modal-btn.save:hover { 
  background:#7F0008; 
  transform:scale(1.05); 
}

.modal-btn.save:disabled { 
  background: #B2000C; 
  opacity: 0.5; 
  cursor: not-allowed; 
  box-shadow: none; 
  transform: none; 
}

.modal-btn.save.enabled { 
  box-shadow: 0 8px 24px rgba(178,0,12,0.28); 
  transform: translateY(-2px); 
  background: linear-gradient(180deg,#c41a1a,#B2000C); 
  opacity: 1; 
  cursor: pointer; 
}

/* ===========================================================
   SUCCESS MODAL (GREEN THEME, SAME STYLE AS EDIT MODAL)
=========================================================== */

.edit-success-modal {
  position: fixed;
  inset: 0;
  display: none;
  z-index: 99999;
  font-family: 'Segoe UI', Roboto, sans-serif;
}

.edit-success-modal.active {
  display: flex;
  justify-content: center;
  align-items: center;
}

.edit-success-overlay {
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.55);
  display: flex;
  justify-content: center;
  align-items: center;
}

.edit-success-content {
  position: relative;
  background: #fff;
  border-radius: 20px;
  width: 100%;
  max-width: 450px;              /* wider + smoother */
  max-height: 90vh;
  padding: 2.1rem 2.4rem;
  font-size: 1.05rem;
  box-shadow: 0 14px 45px rgba(0,0,0,0.28);
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-gutter: stable both-edges;
  animation: slideIn .3s ease forwards;
}

/* Smooth rounded scrollbars */
.edit-success-content::-webkit-scrollbar {
  width: 9px;
}
.edit-success-content::-webkit-scrollbar-track {
  background: #f5f5f5;
  border-radius: 10px;
}
.edit-success-content::-webkit-scrollbar-thumb {
  background: #c2c2c2;
  border-radius: 10px;
}
.edit-success-content::-webkit-scrollbar-thumb:hover {
  background: #a5a5a5;
}

/* Header: icon + text on a single line, centered */
.edit-success-header {
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  gap: 0.6rem;
  margin-bottom: 1.1rem;
}

.edit-success-title {
  font-size: 1.6rem;
  color: #28a745;
  margin: 0;
  font-weight: 700;
}

.edit-success-icon {
  font-size: 1.8rem;
  color: #28a745;
}

.edit-success-separator {
  width: 88%;
  height: 1px;
  background: #ececec;
  margin: 0.9rem auto 1.1rem;
}

.edit-success-text {
  font-size: 1.05rem;
  color: #222;
  line-height: 1.55;
  padding-right: 10px; /* keep text away from scrollbar */
}

/* Footer with green OK button */
.edit-success-footer {
  margin-top: 1.5rem;
  display: flex;
  justify-content: center;
}

.edit-success-btn {
  background: #8B0000;          /* deep red */
  color: #fff;
  padding: .65rem 1.9rem;
  border-radius: 10px;          /* smoother corners like screenshot */
  font-size: 1rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all 0.25s ease;
}

.edit-success-btn:hover {
  background: #7F0008;          /* slightly darker hover */
  transform: scale(1.05);
  box-shadow: 0 6px 14px rgba(139,0,0,0.45);
}


@keyframes slideIn {
  from { opacity:0; transform: translateY(-20px) scale(0.96); }
  to { opacity:1; transform: translateY(0) scale(1); }
}

/* Small screens */
@media(max-height:600px){ 
  .edit-volunteer-modal .modal-content { 
    max-height: 95vh; padding: 1.5rem; 
  } 
}

@media(max-width:500px){ 
  .input-grid { 
    grid-template-columns: 1fr; 
  } 
}
</style>

@php
// Fetch courses
$courses = DB::table('courses')
    ->orderBy('college')
    ->orderBy('course_name')
    ->get();

// Fetch locations
$locations = DB::table('locations')
    ->orderBy('barangay')
    ->get();

// Map barangay -> district_id
$locationsMap = $locations->pluck('district_id', 'barangay');
@endphp

<script>
window.volunteersData = {
    invalid: @json(session('invalidEntries', [])),
    valid: [
        @foreach ($validEntries as $entry)
        {
            full_name: "{{ $entry['full_name'] }}",
            id_number: "{{ $entry['id_number'] }}",
            course: "{{ $entry['course'] }}",
            year_level: "{{ $entry['year_level'] }}",
            contact_number: "{{ $entry['contact_number'] }}",
            email: "{{ $entry['email'] }}",
            emergency_contact: "{{ $entry['emergency_contact'] }}",
            fb_messenger: "{{ $entry['fb_messenger'] }}",
            barangay: "{{ $entry['barangay'] }}",
            district: "{{ $entry['district'] }}",
            class_schedule: {!! json_encode($entry['class_schedule'] ?? '') !!}
        },
        @endforeach
    ]
};

const locationsMap = @json($locationsMap);
</script>

<div class="edit-volunteer-modal" id="editVolunteerModal">
    <div class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fa-solid fa-user-edit modal-icon"></i>
                <h2>Edit Volunteer</h2>
            </div>

            <form id="editVolunteerForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body input-grid">
                    @php
                    $fields = [
                        'full_name' => ['label'=>'Full Name','icon'=>'fa-user','type'=>'text','required'=>true],
                        'id_number' => ['label'=>'School ID','icon'=>'fa-id-card','type'=>'text','required'=>true],
                        'course' => ['label'=>'Course','icon'=>'fa-graduation-cap','type'=>'select','required'=>true],
                        'year_level' => ['label'=>'Year Level','icon'=>'fa-calendar','type'=>'select','required'=>true],
                        'contact_number' => ['label'=>'Contact Number','icon'=>'fa-phone','type'=>'text','required'=>true],
                        'emergency_contact' => ['label'=>'Emergency Contact','icon'=>'fa-phone-volume','type'=>'text','required'=>true],
                        'email' => ['label'=>'Email','icon'=>'fa-envelope','type'=>'text','required'=>true],
                        'fb_messenger' => ['label'=>'FB Messenger','icon'=>'fa-comment','type'=>'text','required'=>false],
                        'barangay' => ['label'=>'Barangay','icon'=>'fa-house','type'=>'select','required'=>true],
                        'district' => ['label'=>'District','icon'=>'fa-map-location-dot','type'=>'text','required'=>true],
                    ];
                    @endphp

                    @foreach ($fields as $key => $info)
                    <div class="volunteer-info">
                        <label>{{ $info['label'] }} @if($info['required'])* @endif</label>
                        <div class="input-wrapper">
                            @if($info['type'] === 'select' && $key === 'barangay')
                                <select id="barangay" name="barangay">
                                    <option value="">-- Select Barangay --</option>
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->barangay }}">{{ $loc->barangay }}</option>
                                    @endforeach
                                </select>
                            @elseif($info['type'] === 'select' && $key === 'course')
                                <select id="course" name="course">
                                    <option value="">-- Select Course --</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->course_name }}" data-college="{{ $course->college }}">
                                            {{ $course->course_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" id="college" name="college">
                            @elseif($key === 'year_level')
                                <select id="year_level" name="year_level">
                                    <option value="">-- Select Year Level --</option>
                                    @for($i=1; $i<=4; $i++)
                                        <option value="{{ $i }}">{{ $i }}</option>
                                    @endfor
                                </select>
                            @elseif($key === 'district')
                                <input type="text" id="district" name="district" placeholder="District" readonly>
                                <input type="hidden" id="district_id" name="district_id">
                            @else
                                <input type="text" id="{{ $key }}" name="{{ $key }}" placeholder="{{ $info['label'] }}">
                            @endif
                            <i class="fa-solid {{ $info['icon'] }} input-icon"></i>
                            <span class="error-tooltip" id="{{ $key }}-error"></span>
                        </div>
                    </div>
                    @endforeach

                    <!-- Hidden class schedule -->
                    <input type="hidden" id="class_schedule" name="class_schedule">
                    <span class="error-tooltip" id="class_schedule-error"></span>
                </div>

                <div class="modal-footer">
                    <button type="button" class="modal-btn cancel" onclick="closeEditVolunteerModal()">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </button>
                    <button type="submit" class="modal-btn save">
                        <i class="fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===========================================================
     UPDATE SUCCESS MODAL — MATCHES EDIT MODAL
=========================================================== -->
<div id="updateSuccessModal" class="edit-success-modal">
    <div id="updateSuccessOverlay" class="edit-success-overlay">
        <div class="edit-success-content">

            <div class="edit-success-header">
                <i class="fa-solid fa-circle-check edit-success-icon"></i>
                <h2 class="edit-success-title">Changes Saved</h2>
            </div>

            <hr class="edit-success-separator">

            <div id="updateSuccessMessage" class="edit-success-text"></div>

            <div class="edit-success-footer">
                <button id="updateSuccessOkBtn" class="edit-success-btn">
                    <i class="fa-solid fa-check"></i> OK
                </button>
            </div>

        </div>
    </div>
</div>

@if(session('updateDetails'))
<script>
    window.serverUpdateDetails = "{{ session('updateDetails') }}";
</script>
@endif

<script>
document.addEventListener("DOMContentLoaded", () => {

    /* ===========================================================
       UPDATE SUCCESS MODAL ELEMENTS
    =========================================================== */
    const updateModal   = document.getElementById("updateSuccessModal");
    const updateOverlay = document.getElementById("updateSuccessOverlay");
    const updateMsg     = document.getElementById("updateSuccessMessage");
    const updateOkBtn   = document.getElementById("updateSuccessOkBtn");

    function openUpdateModal(html) {
        updateMsg.innerHTML = html;
        updateModal.classList.add("active");
    }

    function closeUpdateModal() {
        updateModal.classList.remove("active");
    }

    updateOkBtn?.addEventListener("click", closeUpdateModal);
    updateOverlay?.addEventListener("click", e => {
        if (e.target === updateOverlay) closeUpdateModal();
    });

    /* AUTO OPEN (controller sets updateDetails) */
    if (window.serverUpdateDetails) {
        const decoded = atob(window.serverUpdateDetails);
        openUpdateModal(decoded);
    }

    /* FLASH MESSAGE "SHOW DETAILS" HANDLER */
    document.addEventListener("click", e => {
        const link = e.target.closest(".update-details-link");
        if (!link) return;

        e.preventDefault();
        const encoded = link.getAttribute("data-details");
        if (!encoded) return;

        const decoded = atob(encoded);
        openUpdateModal(decoded);
    });

});

</script>

<script>
(function(){
    const modal = document.getElementById('editVolunteerModal');
    const form = document.getElementById('editVolunteerForm');
    const saveBtn = form.querySelector('.modal-btn.save');
    const barangaySelect = document.getElementById('barangay');
    const districtInput = document.getElementById('district');
    const districtIdInput = document.getElementById('district_id');
    const courseSelect = document.getElementById('course');
    const collegeInput = document.getElementById('college');

    /* ------------------------------------------------------------------
       ⭐ STRICT + SMART CLASS SCHEDULE NORMALIZER (NEW PATCH)
       ------------------------------------------------------------------ */
    function normalizeClassSchedule(raw) {
        if (!raw) return "";

        let val = raw.toString().trim();

        // Clean sloppy input
        val = val.replace(/\s*/g, "");
        val = val.replace(/–/g, "-");
        val = val.replace(/to/gi, "-");

        let parts = val.split("-");
        if (parts.length !== 2) return raw;

        function norm(t) {
            if (/^\d{1,2}$/.test(t)) return `${t}:00`;
            if (/^\d{3,4}$/.test(t)) {
                let h = t.slice(0, -2);
                let m = t.slice(-2);
                return `${parseInt(h)}:${m}`;
            }
            if (/^\d{1,2}:$/.test(t)) return `${t}00`;
            return t;
        }

        let start = norm(parts[0]);
        let end   = norm(parts[1]);

        function toMin(str) {
            let [h, m] = str.split(":").map(Number);
            return h * 60 + m;
        }

        let sMin = toMin(start);
        let eMin = toMin(end);
        let diff = eMin - sMin;

        // Allowed durations: 80min or 180min
        if (diff === 80 || diff === 180) {
            return `${start}-${end}`;
        }

        // Snap to nearest valid block (80 or 180)
        const d80  = sMin + 80;
        const d180 = sMin + 180;

        let target = Math.abs(d80 - eMin) < Math.abs(d180 - eMin) ? d80 : d180;

        let endH = Math.floor(target / 60);
        let endM = String(target % 60).padStart(2, "0");

        return `${start}-${endH}:${endM}`;
    }
    /* ------------------------------------------------------------------ */

    function updateDistrict() {
        const selected = barangaySelect.value.trim();
        const errorSpan = document.getElementById('district-error');

        if(!selected){
            districtInput.value = '';
            districtIdInput.value = '';
            errorSpan.textContent = 'District depends on Barangay selection';
            districtInput.classList.add('invalid');
            districtInput.classList.remove('valid');
            return;
        }

        const districtId = locationsMap[selected];
        if(districtId){
            districtInput.value = "District " + districtId;
            districtIdInput.value = districtId;
            errorSpan.textContent = '';
            districtInput.classList.add('valid');
            districtInput.classList.remove('invalid');
        } else {
            districtInput.value = '';
            districtIdInput.value = '';
            errorSpan.textContent = 'Invalid district for selected barangay';
            districtInput.classList.add('invalid');
            districtInput.classList.remove('valid');
        }
    }

    const rules = {
      full_name: v => v.trim()!=='' && /^[A-Za-z\s\-.,]+$/.test(v) ? true : 'Invalid full name',
      id_number: v => /^\d{6,7}$/.test(v.trim()) ? true : 'ID must be 6-7 digits',
      course: v => v!=='' ? true : 'Please select a course',
      year_level: v => /^[1-4]$/.test(v.trim()) ? true : 'Year must be 1-4',

      // Contact Number – valid PH only
      contact_number: v => {
          const value = v.trim();
          if (!/^(09|\+639)\d{9}$/.test(value)) {
              return 'Invalid PH number';
          }
          // Also check difference if emergency already filled
          const emergency = (document.getElementById('emergency_contact')?.value || '').trim();
          if (emergency && emergency === value) {
              return 'Contact number and emergency contact must be different';
          }
          return true;
      },

      // Emergency Contact – valid PH + must differ from contact_number
      emergency_contact: v => {
          const value = v.trim();
          if (!/^(09|\+639)\d{9}$/.test(value)) {
              return 'Invalid PH number';
          }
          const contact = (document.getElementById('contact_number')?.value || '').trim();
          if (contact && contact === value) {
              return 'Contact number and emergency contact must be different';
          }
          return true;
      },

      email: v => /^[a-zA-Z0-9._%+-]+@(gmail\.com|adzu\.edu\.ph)$/.test(v.trim()) ? true : 'Must be @gmail.com or @adzu.edu.ph',
      fb_messenger: v => {
          if(!v || !v.trim()) return true;
          try {
              const url = new URL(v.trim());
              if(!['http:','https:'].includes(url.protocol)) return 'URL must start with http:// or https://';
              if(!url.hostname.includes('facebook.com')) return 'URL should be a Facebook link';
              return true;
          } catch { 
              return 'Must be a valid URL like https://www.facebook.com/username'; 
          }
      },
      barangay: v => {
          if(!v || v.trim()==='') return 'Please select a barangay';
          if(!locationsMap[v.trim()]) return 'Invalid barangay';
          return true;
      },
      district: v => {
          const barangay = barangaySelect.value.trim();
          const districtId = districtIdInput.value.trim();
          if(!barangay) return 'District depends on Barangay selection';
          if(!districtId) return 'Invalid district for selected barangay';
          return true;
      },
      class_schedule: v => true // hidden
  };


    function validateField(input){
        if(!rules[input.id]) return true;
        const res = rules[input.id](input.value);
        const errorSpan = document.getElementById(input.id+'-error');
        if(res!==true){
            input.classList.add('invalid');
            input.classList.remove('valid');
            if(errorSpan) { errorSpan.textContent=res; errorSpan.style.display='block'; }
            return false;
        } else {
            input.classList.remove('invalid');
            input.classList.add('valid');
            if(errorSpan){ errorSpan.textContent=''; errorSpan.style.display='none'; }
            return true;
        }
    }

    function validateAll(){
        let allValid = true;
        document.querySelectorAll('.volunteer-info input, .volunteer-info select').forEach(input=>{
            if(rules[input.id] && !validateField(input)) allValid=false;
        });
        saveBtn.disabled = !allValid;
        saveBtn.classList.toggle('enabled', allValid);
        return allValid;
    }

    document.querySelectorAll('.volunteer-info input, .volunteer-info select').forEach(input=>{
        ['input','change','blur'].forEach(evt=>input.addEventListener(evt, validateAll));
    });

    barangaySelect.addEventListener('change', ()=>{ updateDistrict(); validateAll(); });
    courseSelect.addEventListener('change', ()=>{
        const opt = courseSelect.options[courseSelect.selectedIndex];
        collegeInput.value = opt ? opt.dataset.college||'' : '';
        validateAll();
    });

    window.openEditVolunteerModal = function(type, index){
        const volunteer = (window.volunteersData[type]||[])[index]||{};
        Object.keys(rules).forEach(key=>{
            const input=document.getElementById(key);
            if(!input) return;
            if(key==='barangay'){
                input.value = volunteer[key] && locationsMap[volunteer[key]] ? volunteer[key] : '';
            } else input.value = volunteer[key] || '';
            if(input.tagName==='SELECT'){
                const opt = Array.from(input.options).find(o=>o.value===input.value);
                if(opt) input.value = opt.value;
            }
        });

        const selectedCourse = Array.from(courseSelect.options).find(o=>o.value===volunteer.course);
        if(selectedCourse){ 
            courseSelect.value = selectedCourse.value; 
            collegeInput.value = selectedCourse.dataset.college||''; 
        }

        updateDistrict();
        validateAll();

        const routeTemplate = "{{ route('volunteer.import.update-entry', ['index' => '__INDEX__', 'type' => '__TYPE__']) }}";
        form.action = routeTemplate.replace('__INDEX__', index).replace('__TYPE__', type);

        modal.classList.add('is-open');
        document.documentElement.style.overflow='hidden';
        document.body.style.overflow='hidden';
    };

    window.closeEditVolunteerModal = function(){
        modal.classList.remove('is-open');
        document.documentElement.style.overflow=''; 
        document.body.style.overflow='';
    };

    modal.querySelector('.modal-overlay').addEventListener('click', e=>{
        if(e.target===modal.querySelector('.modal-overlay')) closeEditVolunteerModal();
    });

    document.addEventListener('keydown', e=>{
        if(modal.classList.contains('is-open') && e.key==='Escape') closeEditVolunteerModal();
    });

    /* ------------------------------------------------------------------
       ⭐ INJECT NORMALIZATION BEFORE SUBMIT (NEW PATCH)
       ------------------------------------------------------------------ */
    form.addEventListener('submit', e=>{
        const sched = document.getElementById('class_schedule');
        if (sched) {
            sched.value = normalizeClassSchedule(sched.value);
        }

        if(!validateAll()) e.preventDefault();
    });
    /* ------------------------------------------------------------------ */

})();
</script>
