<style>
/* ===========================================================
   BASE MODAL
=========================================================== */
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
  border-radius: 14px;
  width: 100%;
  max-width: 820px;
  max-height: 90vh;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 12px 40px rgba(0,0,0,0.25);
  animation: slideIn 0.25s ease forwards;
}

/* Slimmer scrollbars */
.edit-volunteer-modal .modal-content::-webkit-scrollbar {
  width: 8px;
}
.edit-volunteer-modal .modal-content::-webkit-scrollbar-track {
  background: #f3f3f3;
}
.edit-volunteer-modal .modal-content::-webkit-scrollbar-thumb {
  background: #c4c4c4;
  border-radius: 999px;
}


/* ===========================================================
   HEADER
=========================================================== */
.modal-header {
  display:flex;
  align-items:center;
  justify-content:flex-start;
  gap:0.5rem;
  margin-bottom:1rem;
}
.modal-header h2 {
  font-size:1.4rem;
  color:#B2000C;
  margin:0;
  font-weight:700;
}
.modal-icon {
  font-size:1.8rem;
  color:#B2000C;
  transition: transform 0.25s ease, color 0.25s ease;
}
.modal-icon:hover {
  transform: rotate(-10deg) scale(1.03);
}

/* ===========================================================
   GRID LAYOUT (more compact)
=========================================================== */
.input-grid {
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  column-gap:0.9rem;
  row-gap:0.75rem;
  margin-bottom:0.5rem;
}

/* Each field wrapper */
.volunteer-info {
  position:relative;
  display:flex;
  flex-direction:column;
}

/* Label */
.volunteer-info label {
  font-size:0.8rem;
  color:#555;
  margin-bottom:0.15rem;
  font-weight:600;
}

/* Shared wrapper so icon can sit inside */
.input-wrapper {
  position:relative;
}

/* ===========================================================
   INPUTS & SELECTS (more compact)
=========================================================== */
.volunteer-info input,
.volunteer-info select {
  width:100%;
  padding:0.45rem 0.7rem 0.45rem 2.1rem;
  border-radius:7px;
  border:1px solid #cfcfcf;
  font-size:0.9rem;
  line-height:1.2;
  transition: all 0.2s ease;
  background:#fff;
  color:#111;
}

/* Slightly slimmer for mobile */
@media (max-width: 576px) {
  .volunteer-info input,
  .volunteer-info select {
    padding:0.4rem 0.6rem 0.4rem 2rem;
    font-size:0.88rem;
  }
}

/* Native select tweaks */
.volunteer-info select {
  -webkit-appearance:none;
  -moz-appearance:none;
  appearance:none;
  cursor:pointer;
  padding-right:2rem;
}

/* Disabled district input */
#district {
  cursor:not-allowed;
  background-color:#f7f7f7 !important;
  color:#555 !important;
}

/* Autofill default */
input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus,
input:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0px 1000px #fff inset !important;
  -webkit-text-fill-color:#000 !important;
}

/* Autofill when valid */
input.valid:-webkit-autofill,
input.valid:-webkit-autofill:hover,
input.valid:-webkit-autofill:focus,
input.valid:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0px 1000px #e6f9ea inset !important;
}

/* Autofill when invalid */
input.invalid:-webkit-autofill,
input.invalid:-webkit-autofill:hover,
input.invalid:-webkit-autofill:focus,
input.invalid:-webkit-autofill:active {
  -webkit-box-shadow: 0 0 0px 1000px #ffe6e6 inset !important;
}

/* Focus states */
.volunteer-info input:focus,
.volunteer-info select:focus {
  outline:none;
  border-color:#B2000C;
  box-shadow:0 0 0 1px rgba(178,0,12,0.15);
}

/* Valid / Invalid colors */
.volunteer-info input.invalid,
.volunteer-info select.invalid {
  border-color:#dc3545 !important;
  background:#ffe6e6 !important;
}
.volunteer-info input.valid,
.volunteer-info select.valid {
  border-color:#28a745 !important;
  background:#e6f9ea !important;
}

/* Explicit for select-like fields */
#barangay.valid, #course.valid, #district.valid, #batch_year.valid {
  border-color:#28a745 !important;
  background:#e6f9ea !important;
}
#barangay.invalid, #course.invalid, #district.invalid, #batch_year.invalid {
  border-color:#dc3545 !important;
  background:#ffe6e6 !important;
}

/* District is readonly but still shows colors */
#district[readonly] {
  cursor:not-allowed;
}

/* ===========================================================
   INPUT ICON
=========================================================== */
.input-icon {
  position:absolute;
  left:0.65rem;
  top:50%;
  transform:translateY(-50%);
  color:#942a2a;
  font-size:1rem;
  pointer-events:none;
  transition:transform 0.2s ease, color 0.2s ease;
}
.volunteer-info input:focus + .input-icon,
.volunteer-info select:focus + .input-icon {
  color:#B2000C;
  transform:translateY(-50%) scale(1.03);
}

/* Barangay icon sits correctly even with custom dropdown */
#barangay + .input-icon,
.select-search[data-target="barangay"] .input-icon {
  left:0.65rem;
}

/* ===========================================================
   ERROR TOOLTIP
=========================================================== */
.error-tooltip {
  display:block;
  font-size:0.7rem;
  color:#fff;
  background:#dc3545;
  padding:0.25rem 0.45rem;
  border-radius:4px;
  position:absolute;
  top:100%;
  left:0;
  margin-top:0.15rem;
  z-index:10;
  opacity:0;
  pointer-events:none;
  white-space:normal;
  max-width:100%;
  box-shadow:0 3px 8px rgba(0,0,0,0.18);
  transition:opacity 0.2s ease;
}
.volunteer-info select.invalid ~ .error-tooltip,
.volunteer-info input.invalid ~ .error-tooltip {
  opacity:1;
}

/* ===========================================================
   FOOTER BUTTONS
=========================================================== */
.modal-footer {
  display:flex;
  justify-content:center;
  gap:0.75rem;
  flex-wrap:wrap;
  margin-top:0.75rem;
}
.modal-btn {
  display:flex;
  align-items:center;
  justify-content:center;
  gap:0.4rem;
  padding:0.55rem 1.6rem;
  font-size:0.95rem;
  font-weight:600;
  border-radius:8px;
  cursor:pointer;
  border:none;
  transition: all 0.2s ease;
  height:44px;
}
.modal-btn i {
  font-size:0.9rem;
}
.modal-btn.cancel {
  background:#f3f3f3;
  color:#333;
}
.modal-btn.cancel:hover {
  background:#e0e0e0;
  transform:translateY(-1px);
}
.modal-btn.save {
  background:#B2000C;
  color:#fff;
}
.modal-btn.save:hover {
  background:#7F0008;
  transform:translateY(-1px);
}
.modal-btn.save:disabled {
  background:#B2000C;
  opacity:0.55;
  cursor:not-allowed;
  transform:none;
  box-shadow:none;
}
.modal-btn.save.enabled {
  box-shadow:0 8px 22px rgba(178,0,12,0.28);
  background:linear-gradient(180deg,#c41a1a,#B2000C);
  opacity:1;
}

/* ===========================================================
   SUCCESS MODAL (unchanged theme, slightly compact)
=========================================================== */
.edit-success-modal {
  position:fixed;
  inset:0;
  display:none;
  z-index:99999;
  font-family:'Segoe UI', Roboto, sans-serif;
}
.edit-success-modal.active {
  display:flex;
  justify-content:center;
  align-items:center;
}
.edit-success-overlay {
  width:100%;
  height:100%;
  background:rgba(0,0,0,0.55);
  display:flex;
  justify-content:center;
  align-items:center;
}
.edit-success-content {
  position:relative;
  background:#fff;
  border-radius:18px;
  width:100%;
  max-width:440px;
  max-height:88vh;
  padding:1.8rem 2.1rem;
  font-size:0.98rem;
  box-shadow:0 14px 45px rgba(0,0,0,0.28);
  overflow-y:auto;
  animation:slideIn .25s ease forwards;
}
.edit-success-header {
  display:flex;
  align-items:center;
  justify-content:center;
  text-align:center;
  gap:0.55rem;
  margin-bottom:0.8rem;
}
.edit-success-title {
  font-size:1.45rem;
  color:#28a745;
  margin:0;
  font-weight:700;
}
.edit-success-icon {
  font-size:1.7rem;
  color:#28a745;
}
.edit-success-separator {
  width:88%;
  height:1px;
  background:#ececec;
  margin:0.6rem auto 0.8rem;
}
.edit-success-text {
  font-size:0.97rem;
  color:#222;
  line-height:1.5;
  padding-right:6px;
}
.edit-success-footer {
  margin-top:1.2rem;
  display:flex;
  justify-content:center;
}
.edit-success-btn {
  background:#8B0000;
  color:#fff;
  padding:.55rem 1.7rem;
  border-radius:10px;
  font-size:0.95rem;
  font-weight:600;
  border:none;
  cursor:pointer;
  transition:all 0.2s ease;
}
.edit-success-btn:hover {
  background:#7F0008;
  transform:translateY(-1px);
  box-shadow:0 6px 14px rgba(139,0,0,0.45);
}

/* ===========================================================
   SEARCHABLE DROPDOWN (Course + Barangay)
   (match event manager feel – toggle + panel + search)
=========================================================== */

/* wrapper replaces the plain select visually */
.select-search {
  position:relative;
}
/* Fully hide the native <select> inside our custom select-search wrappers
   (course, barangay, batch year) so no grey box peeks through */
.select-search.hidden-native select {
  position: absolute;
  left: -9999px;
  top: 0;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
  border: 0;
  padding: 0;
  margin: 0;
}
.select-search[data-target="batch_year"] .select-search-toggle .label-text,
.select-search[data-target="batch_year"] .select-search-toggle .label-text .placeholder {
  background: transparent !important;
}


/* visible toggle */
.select-search-toggle {
  width:100%;
  padding:0.45rem 2.1rem 0.45rem 2.1rem;
  border-radius:7px;
  border:1px solid #cfcfcf;
  font-size:0.9rem;
  line-height:1.2;
  background:#fff;
  color:#111;
  text-align:left;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:0.5rem;
  cursor:pointer;
  transition: all 0.2s ease;
}
.select-search-toggle span.label-text {
  flex:1;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.select-search-toggle span.placeholder {
  color:#888;
}
.select-search-toggle .chevron {
  font-size:0.85rem;
  color:#777;
}

/* states */
.select-search-toggle:focus-visible {
  outline:none;
  border-color:#B2000C;
  box-shadow:0 0 0 1px rgba(178,0,12,0.15);
}
.select-search.is-invalid .select-search-toggle {
  border-color:#dc3545 !important;
  background:#ffe6e6 !important;
}
.select-search.is-valid .select-search-toggle {
  border-color:#28a745 !important;
  background:#e6f9ea !important;
}

/* dropdown panel */
.select-search-panel {
  position:absolute;
  top:calc(100% + 4px);
  left:0;
  right:0;
  background:#fff;
  border-radius:10px;
  box-shadow:0 16px 40px rgba(0,0,0,0.18);
  padding:0.5rem 0.5rem 0.4rem;
  z-index:100000;
  display:none;
}
.select-search.is-open .select-search-panel {
  display:block;
}

/* search box inside panel */
.select-search-input {
  width:100%;
  border-radius:7px;
  border:1px solid #d0d0d0;
  font-size:0.85rem;
  padding:0.35rem 0.6rem;
  margin-bottom:0.35rem;
}
.select-search-input:focus {
  outline:none;
  border-color:#B2000C;
  box-shadow:0 0 0 1px rgba(178,0,12,0.12);
}

/* list */
.select-search-list {
  max-height:210px;
  overflow-y:auto;
  margin:0;
  padding:0;
  list-style:none;
}
.select-search-option {
  padding:0.35rem 0.5rem;
  font-size:0.85rem;
  cursor:pointer;
  border-radius:6px;
  display:flex;
  align-items:center;
  gap:0.4rem;
}
.select-search-option:hover {
  background:#f5f5f5;
}
.select-search-option.is-active {
  background:#FFE5E9;
  color:#B2000C;
  font-weight:600;
}



/* small pill for college grouping in course dropdown */
.select-search-option .badge-pill {
  margin-left:auto;
  font-size:0.7rem;
  padding:0.1rem 0.35rem;
  border-radius:999px;
  background:#f1f1f1;
  color:#666;
}

/* "no results" text */
.select-search-empty {
  font-size:0.8rem;
  color:#999;
  padding:0.25rem 0.5rem 0.3rem;
}

/* Batch year dropdown: use same panel style but no visible search bar */
.select-search[data-target="batch_year"] .select-search-input {
  display: none;
  margin: 0;
  padding: 0;
  border: 0;
}

/* Tighter padding on the panel because no search row on top */
.select-search[data-target="batch_year"] .select-search-panel {
  padding-top: 0.35rem;
}

/* ===========================================================
   RESPONSIVE
=========================================================== */
@media(max-width:700px){
  .edit-volunteer-modal .modal-content {
    max-width:96vw;
    padding:1.25rem 1.2rem;
  }
}
@media(max-width:540px){
  .input-grid {
    grid-template-columns:1fr;
  }
}

/* ===========================================================
   ANIMATION
=========================================================== */
@keyframes slideIn {
  from { opacity:0; transform: translateY(-16px) scale(0.97); }
  to   { opacity:1; transform: translateY(0) scale(1); }
}
</style>

@php
    use Illuminate\Support\Facades\DB;

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

    $currentYear = now()->year;
@endphp

<script>
    // Session data for modal
    window.volunteersData = {
        invalid: @json(session('invalidEntries', [])),
        valid: @json($validEntries ?? [])
    };

    // barangay => district_id
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
                            'full_name'         => ['label' => 'Full Name',         'icon' => 'fa-user',             'type' => 'text',   'required' => true],
                            'id_number'         => ['label' => 'School ID',         'icon' => 'fa-id-card',          'type' => 'text',   'required' => true],
                            'course'            => ['label' => 'Course',            'icon' => 'fa-graduation-cap',   'type' => 'select', 'required' => true],
                            'year_level'        => ['label' => 'Year Level',        'icon' => 'fa-calendar',         'type' => 'select', 'required' => true],
                            'batch_year'        => ['label' => 'Batch Year',        'icon' => 'fa-calendar-days',    'type' => 'select', 'required' => false],
                            'contact_number'    => ['label' => 'Contact Number',    'icon' => 'fa-phone',            'type' => 'text',   'required' => true],
                            'emergency_contact' => ['label' => 'Emergency Contact', 'icon' => 'fa-phone-volume',     'type' => 'text',   'required' => true],
                            'email'             => ['label' => 'Email',             'icon' => 'fa-envelope',         'type' => 'text',   'required' => true],
                            'fb_messenger'      => ['label' => 'FB Messenger',      'icon' => 'fa-comment',          'type' => 'text',   'required' => false],
                            'barangay'          => ['label' => 'Barangay',          'icon' => 'fa-house',            'type' => 'select', 'required' => true],
                            'district'          => ['label' => 'District',          'icon' => 'fa-map-location-dot', 'type' => 'text',   'required' => true],
                        ];
                    @endphp

                    @foreach ($fields as $key => $info)
                        <div class="volunteer-info">
                            <label>
                                {{ $info['label'] }}
                                @if($info['required'])* @endif
                            </label>

                            <div class="input-wrapper">
                                {{-- SEARCHABLE COURSE --}}
                                @if($key === 'course')
                                    <div class="select-search hidden-native" data-target="course">
                                        <button type="button" class="select-search-toggle" data-role="toggle">
                                            <span class="label-text">
                                                <span class="placeholder">-- Select Course --</span>
                                            </span>
                                            <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                        </button>

                                        <div class="select-search-panel">
                                            <input type="text" class="select-search-input"
                                                   placeholder="Search course or college...">
                                            <ul class="select-search-list">
                                                @foreach($courses as $course)
                                                    <li class="select-search-option"
                                                        data-value="{{ $course->course_name }}"
                                                        data-label="{{ $course->course_name }}"
                                                        data-college="{{ $course->college }}">
                                                        <span>{{ $course->course_name }}</span>
                                                        @if($course->college)
                                                            <span class="badge-pill">{{ $course->college }}</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <div class="select-search-empty d-none">
                                                No courses found
                                            </div>
                                        </div>

                                        {{-- hidden native select (for form + validation) --}}
                                        <select id="course" name="course">
                                            <option value="">-- Select Course --</option>
                                            @foreach($courses as $course)
                                                <option value="{{ $course->course_name }}"
                                                        data-college="{{ $course->college }}">
                                                    {{ $course->course_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="hidden" id="college" name="college">
                                    </div>

                                {{-- YEAR LEVEL --}}
                                @elseif($key === 'year_level')
                                    <select id="year_level" name="year_level">
                                        <option value="">-- Select Year Level --</option>
                                        @for($i = 1; $i <= 4; $i++)
                                            <option value="{{ $i }}">{{ $i }}</option>
                                        @endfor
                                    </select>

                                {{-- BATCH YEAR --}}
                                @elseif($key === 'batch_year')
                                <div class="select-search hidden-native" data-target="batch_year">
                                    <button type="button" class="select-search-toggle" data-role="toggle">
                                        <span class="label-text">
                                            <span class="placeholder">-- Select Batch Year (optional) --</span>
                                        </span>
                                        <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                    </button>

                                    <div class="select-search-panel">
                                        {{-- search input will be hidden via CSS, but JS still uses it safely --}}
                                        <input type="text"
                                              class="select-search-input select-search-input-batch"
                                              placeholder="Search batch year...">

                                        <ul class="select-search-list">
                                            @for($y = $currentYear + 1; $y >= $currentYear - 10; $y--)
                                                <li class="select-search-option"
                                                    data-value="{{ $y }}"
                                                    data-label="{{ $y }}">
                                                    <span>{{ $y }}</span>
                                                </li>
                                            @endfor
                                        </ul>

                                        <div class="select-search-empty d-none">
                                            No years found
                                        </div>
                                    </div>

                                    {{-- hidden native select (used by form + validation JS) --}}
                                    <select id="batch_year" name="batch_year">
                                        <option value="">-- Select Batch Year (optional) --</option>
                                        @for($y = $currentYear + 1; $y >= $currentYear - 10; $y--)
                                            <option value="{{ $y }}">{{ $y }}</option>
                                        @endfor
                                    </select>
                                </div>


                                {{-- SEARCHABLE BARANGAY --}}
                                @elseif($key === 'barangay')
                                    <div class="select-search hidden-native" data-target="barangay">
                                        <button type="button" class="select-search-toggle" data-role="toggle">
                                            <span class="label-text">
                                                <span class="placeholder">-- Select Barangay --</span>
                                            </span>
                                            <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
                                        </button>

                                        <div class="select-search-panel">
                                            <input type="text" class="select-search-input"
                                                   placeholder="Search barangay...">
                                            <ul class="select-search-list">
                                                @foreach($locations as $loc)
                                                    <li class="select-search-option"
                                                        data-value="{{ $loc->barangay }}"
                                                        data-label="{{ $loc->barangay }}"
                                                        data-district="{{ $loc->district_id }}">
                                                        <span>{{ $loc->barangay }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                            <div class="select-search-empty d-none">
                                                No barangays found
                                            </div>
                                        </div>

                                        {{-- hidden native select (for form + validation) --}}
                                        <select id="barangay" name="barangay">
                                            <option value="">-- Select Barangay --</option>
                                            @foreach($locations as $loc)
                                                <option value="{{ $loc->barangay }}"
                                                        data-district="{{ $loc->district_id }}">
                                                    {{ $loc->barangay }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                {{-- DISTRICT (readonly text + hidden id) --}}
                                @elseif($key === 'district')
                                    <input type="text" id="district" name="district"
                                           placeholder="District" readonly>
                                    <input type="hidden" id="district_id" name="district_id">

                                {{-- normal text inputs --}}
                                @else
                                    <input type="text" id="{{ $key }}" name="{{ $key }}"
                                           placeholder="{{ $info['label'] }}">
                                @endif

                                <i class="fa-solid {{ $info['icon'] }} input-icon"></i>
                                <span class="error-tooltip" id="{{ $key }}-error"></span>
                            </div>
                        </div>
                    @endforeach

                    {{-- Hidden class schedule (not editable here) --}}
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
     UPDATE SUCCESS MODAL
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
/* ===========================================================
   SUCCESS MODAL JS
=========================================================== */
document.addEventListener("DOMContentLoaded", () => {
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

    // auto-open when controller flashes updateDetails
    if (window.serverUpdateDetails) {
        const decoded = atob(window.serverUpdateDetails);
        openUpdateModal(decoded);
    }

    // "Show Details" links in flash message
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
/* ===========================================================
   EDIT MODAL + VALIDATION + SEARCHABLE SELECTS
=========================================================== */
(function () {
    const modal          = document.getElementById('editVolunteerModal');
    const overlay        = modal.querySelector('.modal-overlay');
    const form           = document.getElementById('editVolunteerForm');
    const saveBtn        = form.querySelector('.modal-btn.save');
    const barangaySelect = document.getElementById('barangay');
    const districtInput  = document.getElementById('district');
    const districtIdInput= document.getElementById('district_id');
    const courseSelect   = document.getElementById('course');
    const collegeInput   = document.getElementById('college');
    const batchYearSelect= document.getElementById('batch_year');

    /* -------------------------------------------------------
       BARANGAY -> DISTRICT
    ------------------------------------------------------- */
    function updateDistrict() {
        const selected = (barangaySelect.value || '').trim();
        const errorSpan = document.getElementById('district-error');

        if (!selected) {
            districtInput.value = '';
            districtIdInput.value = '';
            errorSpan.textContent = 'District depends on Barangay selection';
            districtInput.classList.add('invalid');
            districtInput.classList.remove('valid');
            return;
        }

        const districtId = locationsMap[selected];
        if (districtId) {
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

    /* -------------------------------------------------------
       VALIDATION RULES
    ------------------------------------------------------- */
    const rules = {
        full_name: v => v.trim() !== '' && /^[A-Za-zÑñ\s\.\'-]+$/.test(v)
            ? true
            : 'Invalid full name',
        id_number: v => /^\d{6,7}$/.test(v.trim())
            ? true
            : 'ID must be 6-7 digits',
        course: v => v !== ''
            ? true
            : 'Please select a course',
        year_level: v => /^[1-4]$/.test(v.trim())
            ? true
            : 'Year must be 1-4',
        batch_year: v => {
            const value = v.trim();
            if (!value) return true; // optional
            if (!/^\d{4}$/.test(value)) return 'Batch year must be 4 digits';
            const year = parseInt(value, 10);
            const nowY = (new Date()).getFullYear();
            if (year < 2000 || year > nowY + 1) return 'Batch year looks invalid';
            return true;
        },
        // Contact Number – PH only
        contact_number: v => {
            const value = v.trim();
            if (!/^(09|\+639)\d{9}$/.test(value)) {
                return 'Invalid PH number';
            }
            const emergency = (document.getElementById('emergency_contact')?.value || '').trim();
            if (emergency && emergency === value) {
                return 'Contact # and emergency # must be different';
            }
            return true;
        },
        emergency_contact: v => {
            const value = v.trim();
            if (!/^(09|\+639)\d{9}$/.test(value)) {
                return 'Invalid PH number';
            }
            const contact = (document.getElementById('contact_number')?.value || '').trim();
            if (contact && contact === value) {
                return 'Contact # and emergency # must be different';
            }
            return true;
        },
        email: v => /^[A-Za-z0-9._%+-]+@(gmail\.com|adzu\.edu\.ph)$/i.test(v.trim())
            ? true
            : 'Must be @gmail.com or @adzu.edu.ph',
        fb_messenger: v => {
            const value = v.trim();
            if (!value) return true;
            try {
                const url = new URL(value);
                if (!['http:', 'https:'].includes(url.protocol)) {
                    return 'URL must start with http:// or https://';
                }
                if (!url.hostname.includes('facebook.com')) {
                    return 'URL should be a Facebook link';
                }
                return true;
            } catch {
                return 'Must be a valid URL like https://www.facebook.com/username';
            }
        },
        barangay: v => {
            const value = v.trim();
            if (!value) return 'Please select a barangay';
            if (!locationsMap[value]) return 'Invalid barangay';
            return true;
        },
        district: v => {
            const barangay = (barangaySelect.value || '').trim();
            const districtId = (districtIdInput.value || '').trim();
            if (!barangay) return 'District depends on Barangay selection';
            if (!districtId) return 'Invalid district for selected barangay';
            return true;
        },
        class_schedule: v => true // not edited here
    };

    function syncSelectSearchValidity(input, isValid, hasError) {
        const wrapper = document.querySelector('.select-search[data-target="' + input.id + '"]');
        if (!wrapper) return;
        wrapper.classList.remove('is-valid', 'is-invalid');
        if (hasError) wrapper.classList.add('is-invalid');
        else if (isValid) wrapper.classList.add('is-valid');
    }

    function validateField(input) {
        if (!rules[input.id]) return true;

        const res = rules[input.id](input.value);
        const errorSpan = document.getElementById(input.id + '-error');
        const hasError = res !== true;

        if (hasError) {
            input.classList.add('invalid');
            input.classList.remove('valid');
            if (errorSpan) {
                errorSpan.textContent = res;
                errorSpan.style.display = 'block';
            }
        } else {
            input.classList.remove('invalid');
            input.classList.add('valid');
            if (errorSpan) {
                errorSpan.textContent = '';
                errorSpan.style.display = 'none';
            }
        }

        if (['course', 'barangay', 'batch_year'].includes(input.id)) {
            syncSelectSearchValidity(input, !hasError, hasError);
        }

        return !hasError;
    }

    function validateAll() {
        let allValid = true;
        document.querySelectorAll('.volunteer-info input, .volunteer-info select').forEach(input => {
            if (rules[input.id] && !validateField(input)) {
                allValid = false;
            }
        });
        saveBtn.disabled = !allValid;
        saveBtn.classList.toggle('enabled', allValid);
        return allValid;
    }

    document.querySelectorAll('.volunteer-info input, .volunteer-info select').forEach(input => {
        ['input', 'change', 'blur'].forEach(evt =>
            input.addEventListener(evt, validateAll)
        );
    });

    barangaySelect.addEventListener('change', () => {
        updateDistrict();
        validateAll();
    });

    courseSelect.addEventListener('change', () => {
        const opt = courseSelect.options[courseSelect.selectedIndex];
        collegeInput.value = opt ? (opt.dataset.college || '') : '';
        validateAll();
    });

    if (batchYearSelect) {
        batchYearSelect.addEventListener('change', validateAll);
    }

    /* -------------------------------------------------------
       SEARCHABLE SELECT (Course + Barangay)
    ------------------------------------------------------- */
    function syncSelectSearchFromSelect(targetId) {
      const wrapper = document.querySelector('.select-search[data-target="' + targetId + '"]');
      if (!wrapper) return;

      const select   = wrapper.querySelector('select');
      const toggle   = wrapper.querySelector('.select-search-toggle');
      const labelSpan = toggle.querySelector('.label-text');
      const value    = select.value;
      const optionsEls = wrapper.querySelectorAll('.select-search-option');

      // use placeholder stored by initSelectSearch (fallback is generic)
      const placeholderText = wrapper.dataset.placeholder || '-- Select --';

      optionsEls.forEach(li => {
          li.classList.toggle('is-active', li.dataset.value === value);
      });

      if (!value) {
          labelSpan.innerHTML = '<span class="placeholder">' + placeholderText + '</span>';
      } else {
          const opt = Array.from(select.options).find(o => o.value === value);
          labelSpan.textContent = opt ? opt.textContent : value;
      }
  }


    function initSelectSearch(wrapper) {
        const target = wrapper.dataset.target;
        const toggle = wrapper.querySelector('[data-role="toggle"]');
        const panel = wrapper.querySelector('.select-search-panel');
        const searchInput = wrapper.querySelector('.select-search-input');
        const list = wrapper.querySelector('.select-search-list');
        const emptyText = wrapper.querySelector('.select-search-empty');
        const select = wrapper.querySelector('select');

        if (!toggle || !panel || !searchInput || !list || !select) return;

        // save the initial placeholder text so syncSelectSearchFromSelect can reuse it
        const labelSpan = toggle.querySelector('.label-text');
        const placeholderSpan = labelSpan.querySelector('.placeholder');
        const initialPlaceholder = placeholderSpan ? placeholderSpan.textContent : '-- Select --';
        wrapper.dataset.placeholder = initialPlaceholder;

        function openPanel() {
            document.querySelectorAll('.select-search.is-open').forEach(w => {
                if (w !== wrapper) w.classList.remove('is-open');
            });
            wrapper.classList.add('is-open');
            searchInput.value = '';
            filterOptions('');
            setTimeout(() => searchInput.focus(), 10);
        }
        function closePanel() {
            wrapper.classList.remove('is-open');
        }

        function filterOptions(term) {
            const t = term.toLowerCase();
            let visibleCount = 0;
            list.querySelectorAll('.select-search-option').forEach(li => {
                const label = (li.dataset.label || '').toLowerCase();
                const college = (li.dataset.college || '').toLowerCase();
                const hay = label + ' ' + college;
                const match = !t || hay.includes(t);
                li.style.display = match ? '' : 'none';
                if (match) visibleCount++;
            });
            if (emptyText) {
                emptyText.classList.toggle('d-none', visibleCount > 0);
            }
        }

        toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (wrapper.classList.contains('is-open')) closePanel();
            else openPanel();
        });

        searchInput.addEventListener('input', () => {
            filterOptions(searchInput.value);
        });

        list.addEventListener('click', (e) => {
            const optionEl = e.target.closest('.select-search-option');
            if (!optionEl) return;
            const value = optionEl.dataset.value || '';

            select.value = value;
            select.dispatchEvent(new Event('change', { bubbles: true }));

            syncSelectSearchFromSelect(target);
            validateField(select);
            closePanel();
        });

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                closePanel();
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closePanel();
        });

        // initial sync
        syncSelectSearchFromSelect(target);
    }

    document.querySelectorAll('.select-search').forEach(initSelectSearch);

    /* -------------------------------------------------------
       OPEN / CLOSE MODAL FROM TABLE
    ------------------------------------------------------- */
    /* -------------------------------------------------------
   OPEN / CLOSE MODAL FROM TABLE
------------------------------------------------------- */
window._lastActionsToggleAfterEdit = window._lastActionsToggleAfterEdit || null;

window.openEditVolunteerModal = function (type, index) {
    // ✅ 1. Remember which Actions dropdown was open
    //     (this is set by your main Actions script)
    if (typeof lastDropdownToggle !== 'undefined' && lastDropdownToggle) {
        window._lastActionsToggleAfterEdit = lastDropdownToggle;
    } else {
        window._lastActionsToggleAfterEdit = null;
    }

    // ✅ 2. Close all Actions dropdowns so nothing stays open behind the modal
    if (typeof window.closeAllEntryDropdowns === 'function') {
        window.closeAllEntryDropdowns();
    } else if (window._lastActionsToggleAfterEdit && typeof bootstrap !== 'undefined') {
        const inst = bootstrap.Dropdown.getInstance(window._lastActionsToggleAfterEdit) ||
                     bootstrap.Dropdown.getOrCreateInstance(window._lastActionsToggleAfterEdit, { autoClose: 'outside' });
        inst.hide();
    }

    // 🔽 3. From here down, keep exactly the same code you already had
    const group = window.volunteersData[type] || [];
    const volunteer = group[index] || {};

    // simple fields
    const simpleKeys = [
        'full_name',
        'id_number',
        'year_level',
        'batch_year',
        'contact_number',
        'emergency_contact',
        'email',
        'fb_messenger',
        'class_schedule'
    ];
    simpleKeys.forEach(key => {
        const input = document.getElementById(key);
        if (!input) return;
        input.value = volunteer[key] || '';
    });

    // make sure the three custom dropdowns reflect the underlying select
    syncSelectSearchFromSelect('course');
    syncSelectSearchFromSelect('barangay');
    syncSelectSearchFromSelect('batch_year');

    // course
    if (volunteer.course && courseSelect) {
        courseSelect.value = volunteer.course;
    } else if (courseSelect) {
        courseSelect.value = '';
    }
    const courseOpt = courseSelect.options[courseSelect.selectedIndex];
    collegeInput.value = courseOpt ? (courseOpt.dataset.college || '') : '';
    syncSelectSearchFromSelect('course');

    // barangay (may be invalid -> fall back to empty)
    if (volunteer.barangay && locationsMap[volunteer.barangay]) {
        barangaySelect.value = volunteer.barangay;
    } else {
        barangaySelect.value = '';
    }
    syncSelectSearchFromSelect('barangay');
    updateDistrict(); // also fills district_id

    // district override if we have stored district_id
    if (volunteer.district_id) {
        districtIdInput.value = volunteer.district_id;
        districtInput.value = 'District ' + volunteer.district_id;
    }

    // run validation once to paint fields
    validateAll();

    // wire action URL
    const routeTemplate = "{{ route('volunteer.import.update-entry', ['index' => '__INDEX__', 'type' => '__TYPE__']) }}";
    form.action = routeTemplate.replace('__INDEX__', index).replace('__TYPE__', type);

    // show modal
    modal.classList.add('is-open');
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';
};


    window.closeEditVolunteerModal = function () {
      modal.classList.remove('is-open');
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
      // 🔴 EDIT ACTIONS DROPDOWN RESTORE: reopen the Actions dropdown we had before
      if (window._lastActionsToggleAfterEdit) {
          try {
              if (typeof bootstrap !== 'undefined') {
                  const inst = bootstrap.Dropdown.getOrCreateInstance(
                      window._lastActionsToggleAfterEdit,
                      { autoClose: 'outside' }
                  );
                  inst.show();
              }
          } catch (err) {
              console.error('Failed to reopen Actions dropdown after edit modal:', err);
          }
          window._lastActionsToggleAfterEdit = null;
      }
    };


    overlay.addEventListener('click', e => {
        if (e.target === overlay) {
            closeEditVolunteerModal();
        }
    });

    document.addEventListener('keydown', e => {
        if (modal.classList.contains('is-open') && e.key === 'Escape') {
            closeEditVolunteerModal();
        }
    });

    /* -------------------------------------------------------
       SUBMIT HANDLER
    ------------------------------------------------------- */
    form.addEventListener('submit', (e) => {
        if (!validateAll()) {
            e.preventDefault();
        }
        // class_schedule is just forwarded as-is (editing is in separate modal)
    });

})();
</script>
