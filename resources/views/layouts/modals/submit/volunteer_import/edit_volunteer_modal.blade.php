{{-- ===========================================================
   ✅ EDIT VOLUNTEER MODAL — FULL CODE (MATCHES CS + PP STYLE)
   - All CSS scoped to #editVolunteerModal (no global collisions)
   - Header/Footer renamed (ev-*)
   - ✅ Uses Universal Feedback Modal (UFM) for success/error
   - ✅ Supports "See more" recall (stores last payload globally)
=========================================================== --}}

<style>
/* ===========================================================
   BASE MODAL
=========================================================== */
#editVolunteerModal.edit-volunteer-modal{
  position: fixed;
  inset: 0;
  display: none;
  z-index: 9999;
  font-family: 'Segoe UI', Roboto, sans-serif;
}
#editVolunteerModal.edit-volunteer-modal.is-open{
  display: flex;
  justify-content: center;
  align-items: center;
}
#editVolunteerModal .modal-overlay{
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.55);
  display: flex;
  justify-content: center;
  align-items: center;
}

/* ✅ critical: do NOT let content clip header glyphs */
#editVolunteerModal .modal-content{
  width: 100%;
  max-width: 820px;
  max-height: 90vh;

  border-radius: 14px;
  overflow: visible;          /* <- important */
  background: transparent;    /* inner shell owns white bg */

  box-shadow: 0 12px 40px rgba(0,0,0,0.25);
  animation: evSlideIn 0.25s ease forwards;
}

/* ✅ inner shell keeps rounded corners without clipping */
#editVolunteerModal .ev-modal-shell{
  background:#fff;
  border-radius: 14px;
  overflow: hidden;
  padding: 1.5rem 1.75rem;
}

/* Slimmer scrollbars */
#editVolunteerModal .ev-modal-shell::-webkit-scrollbar{ width: 8px; }
#editVolunteerModal .ev-modal-shell::-webkit-scrollbar-track{ background: #f3f3f3; }
#editVolunteerModal .ev-modal-shell::-webkit-scrollbar-thumb{ background: #c4c4c4; border-radius: 999px; }

/* ===========================================================
   ✅ HEADER (same as CS/PP pattern)
=========================================================== */
#editVolunteerModal .ev-modal-header{
  display:flex;
  align-items:center;
  justify-content:flex-start;
  gap:10px;

  /* tinted strip like CS/PP */
  background: linear-gradient(180deg, rgba(178,0,12,0.14), rgba(178,0,12,0.06));
  border-bottom: 1px solid rgba(178,0,12,0.14);

  margin: -1.5rem -1.75rem 1rem;
  padding: 14px 18px !important;
  min-height: 62px;

  border-top-left-radius: 14px;
  border-top-right-radius: 14px;

  overflow: visible; /* anti-clip */
}
#editVolunteerModal .ev-head-icon{
  display:block;
  font-size:1.55rem;
  line-height:1;
  color:#7F0008;
  opacity:.95;
}
#editVolunteerModal .ev-head-title{
  margin:0 !important;
  font-weight:900;
  font-size:1.25rem;
  letter-spacing:.2px;
  color:#7F0008;

  line-height:1.25;
  padding:2px 0; /* anti-clip */
}

/* ===========================================================
   GRID LAYOUT (compact)
=========================================================== */
#editVolunteerModal .input-grid{
  display:grid;
  grid-template-columns:repeat(2,minmax(0,1fr));
  column-gap:0.9rem;
  row-gap:0.75rem;
  margin-bottom:0.5rem;
}
#editVolunteerModal .volunteer-info{ position:relative; display:flex; flex-direction:column; }
#editVolunteerModal .volunteer-info label{
  font-size:0.8rem;
  color:#555;
  margin-bottom:0.15rem;
  font-weight:600;
}
#editVolunteerModal .input-wrapper{ position:relative; }

/* ===========================================================
   INPUTS & SELECTS
=========================================================== */
#editVolunteerModal .volunteer-info input,
#editVolunteerModal .volunteer-info select{
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
@media (max-width: 576px) {
  #editVolunteerModal .volunteer-info input,
  #editVolunteerModal .volunteer-info select{
    padding:0.4rem 0.6rem 0.4rem 2rem;
    font-size:0.88rem;
  }
}
#editVolunteerModal .volunteer-info select{
  -webkit-appearance:none;
  -moz-appearance:none;
  appearance:none;
  cursor:pointer;
  padding-right:2rem;
}
#editVolunteerModal #district{
  cursor:not-allowed;
  background-color:#f7f7f7 !important;
  color:#555 !important;
}

/* Autofill */
#editVolunteerModal input:-webkit-autofill,
#editVolunteerModal input:-webkit-autofill:hover,
#editVolunteerModal input:-webkit-autofill:focus,
#editVolunteerModal input:-webkit-autofill:active{
  -webkit-box-shadow: 0 0 0px 1000px #fff inset !important;
  -webkit-text-fill-color:#000 !important;
}
#editVolunteerModal input.valid:-webkit-autofill,
#editVolunteerModal input.valid:-webkit-autofill:hover,
#editVolunteerModal input.valid:-webkit-autofill:focus,
#editVolunteerModal input.valid:-webkit-autofill:active{
  -webkit-box-shadow: 0 0 0px 1000px #e6f9ea inset !important;
}
#editVolunteerModal input.invalid:-webkit-autofill,
#editVolunteerModal input.invalid:-webkit-autofill:hover,
#editVolunteerModal input.invalid:-webkit-autofill:focus,
#editVolunteerModal input.invalid:-webkit-autofill:active{
  -webkit-box-shadow: 0 0 0px 1000px #ffe6e6 inset !important;
}

/* Focus */
#editVolunteerModal .volunteer-info input:focus,
#editVolunteerModal .volunteer-info select:focus{
  outline:none;
  border-color:#B2000C;
  box-shadow:0 0 0 1px rgba(178,0,12,0.15);
}

/* Valid/Invalid */
#editVolunteerModal .volunteer-info input.invalid,
#editVolunteerModal .volunteer-info select.invalid{
  border-color:#dc3545 !important;
  background:#ffe6e6 !important;
}
#editVolunteerModal .volunteer-info input.valid,
#editVolunteerModal .volunteer-info select.valid{
  border-color:#28a745 !important;
  background:#e6f9ea !important;
}

/* select-like explicit */
#editVolunteerModal #barangay.valid,
#editVolunteerModal #course.valid,
#editVolunteerModal #district.valid,
#editVolunteerModal #batch_year.valid,
#editVolunteerModal #year_level.valid{
  border-color:#28a745 !important;
  background:#e6f9ea !important;
}
#editVolunteerModal #barangay.invalid,
#editVolunteerModal #course.invalid,
#editVolunteerModal #district.invalid,
#editVolunteerModal #batch_year.invalid,
#editVolunteerModal #year_level.invalid{
  border-color:#dc3545 !important;
  background:#ffe6e6 !important;
}
#editVolunteerModal #district[readonly]{ cursor:not-allowed; }

/* ===========================================================
   INPUT ICON
=========================================================== */
#editVolunteerModal .input-icon{
  position:absolute;
  left:0.65rem;
  top:50%;
  transform:translateY(-50%);
  color:#942a2a;
  font-size:1rem;
  pointer-events:none;
  transition:transform 0.2s ease, color 0.2s ease;
}
#editVolunteerModal .volunteer-info input:focus + .input-icon,
#editVolunteerModal .volunteer-info select:focus + .input-icon{
  color:#B2000C;
  transform:translateY(-50%) scale(1.03);
}
#editVolunteerModal #barangay + .input-icon,
#editVolunteerModal .select-search[data-target="barangay"] .input-icon{ left:0.65rem; }

/* ===========================================================
   ERROR TOOLTIP
=========================================================== */
#editVolunteerModal .error-tooltip{
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
#editVolunteerModal .volunteer-info select.invalid ~ .error-tooltip,
#editVolunteerModal .volunteer-info input.invalid ~ .error-tooltip{ opacity:1; }

/* ===========================================================
   FOOTER BUTTONS
=========================================================== */
#editVolunteerModal .ev-modal-footer{
  display:flex;
  justify-content:center;
  gap:0.75rem;
  flex-wrap:wrap;
  margin-top:0.75rem;
}
#editVolunteerModal .modal-btn{
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
#editVolunteerModal .modal-btn i{ font-size:0.9rem; }
#editVolunteerModal .modal-btn.cancel{ background:#f3f3f3; color:#333; }
#editVolunteerModal .modal-btn.cancel:hover{ background:#e0e0e0; transform:translateY(-1px); }
#editVolunteerModal .modal-btn.save{ background:#B2000C; color:#fff; }
#editVolunteerModal .modal-btn.save:hover{ background:#7F0008; transform:translateY(-1px); }
#editVolunteerModal .modal-btn.save:disabled{
  background:#B2000C;
  opacity:0.55;
  cursor:not-allowed;
  transform:none;
  box-shadow:none;
}
#editVolunteerModal .modal-btn.save.enabled{
  box-shadow:0 8px 22px rgba(178,0,12,0.28);
  background:linear-gradient(180deg,#c41a1a,#B2000C);
  opacity:1;
}

/* ===========================================================
   SEARCHABLE DROPDOWN
=========================================================== */
#editVolunteerModal .select-search{ position:relative; }
#editVolunteerModal .select-search.hidden-native select{
  position:absolute;
  left:-9999px;
  top:0;
  width:0;
  height:0;
  opacity:0;
  pointer-events:none;
  border:0;
  padding:0;
  margin:0;
}
#editVolunteerModal .select-search[data-target="batch_year"] .select-search-toggle .label-text,
#editVolunteerModal .select-search[data-target="batch_year"] .select-search-toggle .label-text .placeholder{
  background:transparent !important;
}

#editVolunteerModal .select-search-toggle{
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
#editVolunteerModal .select-search-toggle span.label-text{
  flex:1;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
#editVolunteerModal .select-search-toggle span.placeholder{ color:#888; }
#editVolunteerModal .select-search-toggle .chevron{ font-size:0.85rem; color:#777; }

#editVolunteerModal .select-search-toggle:focus-visible{
  outline:none;
  border-color:#B2000C;
  box-shadow:0 0 0 1px rgba(178,0,12,0.15);
}
#editVolunteerModal .select-search.is-invalid .select-search-toggle{
  border-color:#dc3545 !important;
  background:#ffe6e6 !important;
}
#editVolunteerModal .select-search.is-valid .select-search-toggle{
  border-color:#28a745 !important;
  background:#e6f9ea !important;
}

#editVolunteerModal .select-search-panel{
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
#editVolunteerModal .select-search.is-open .select-search-panel{ display:block; }

#editVolunteerModal .select-search-input{
  width:100%;
  border-radius:7px;
  border:1px solid #d0d0d0;
  font-size:0.85rem;
  padding:0.35rem 0.6rem;
  margin-bottom:0.35rem;
}
#editVolunteerModal .select-search-input:focus{
  outline:none;
  border-color:#B2000C;
  box-shadow:0 0 0 1px rgba(178,0,12,0.12);
}

#editVolunteerModal .select-search-list{
  max-height:210px;
  overflow-y:auto;
  margin:0;
  padding:0;
  list-style:none;
}
#editVolunteerModal .select-search-option{
  padding:0.35rem 0.5rem;
  font-size:0.85rem;
  cursor:pointer;
  border-radius:6px;
  display:flex;
  align-items:center;
  gap:0.4rem;
}
#editVolunteerModal .select-search-option:hover{ background:#f5f5f5; }
#editVolunteerModal .select-search-option.is-active{
  background:#FFE5E9;
  color:#B2000C;
  font-weight:600;
}
#editVolunteerModal .select-search-option .badge-pill{
  margin-left:auto;
  font-size:0.7rem;
  padding:0.1rem 0.35rem;
  border-radius:999px;
  background:#f1f1f1;
  color:#666;
}
#editVolunteerModal .select-search-empty{ font-size:0.8rem; color:#999; padding:0.25rem 0.5rem 0.3rem; }

#editVolunteerModal .select-search[data-target="batch_year"] .select-search-input{ display:none; margin:0; padding:0; border:0; }
#editVolunteerModal .select-search[data-target="batch_year"] .select-search-panel{ padding-top:0.35rem; }

#editVolunteerModal .select-search[data-target="year_level"] .select-search-input{ display:none; margin:0; padding:0; border:0; }
#editVolunteerModal .select-search[data-target="year_level"] .select-search-panel{ padding-top:0.35rem; }

/* ===========================================================
   RESPONSIVE
=========================================================== */
@media(max-width:700px){
  #editVolunteerModal .modal-content{ max-width:96vw; }
  #editVolunteerModal .ev-modal-shell{ padding:1.25rem 1.2rem; }
  #editVolunteerModal .ev-modal-header{ margin:-1.25rem -1.2rem 1rem; }
}
@media(max-width:540px){
  #editVolunteerModal .input-grid{ grid-template-columns:1fr; }
}

/* ===========================================================
   ANIMATION
=========================================================== */
@keyframes evSlideIn{
  from { opacity:0; transform: translateY(-16px) scale(0.97); }
  to   { opacity:1; transform: translateY(0) scale(1); }
}
</style>

@php
  use Illuminate\Support\Facades\DB;

  $courses = DB::table('courses')->orderBy('college')->orderBy('course_name')->get();
  $locations = DB::table('locations')->orderBy('barangay')->get();
  $locationsMap = $locations->pluck('district_id', 'barangay');
  $currentYear = now()->year;
@endphp

<script>
  window.volunteersData = {
    invalid: @json(session('invalidEntries', [])),
    valid: @json($validEntries ?? [])
  };
  const locationsMap = @json($locationsMap);
</script>

<div class="edit-volunteer-modal" id="editVolunteerModal">
  <div class="modal-overlay">
    <div class="modal-content">
      <div class="ev-modal-shell">

        <div class="ev-modal-header">
          <i class="fa-solid fa-user-edit ev-head-icon" aria-hidden="true"></i>
          <h2 class="ev-head-title">Edit Volunteer</h2>
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
                <label>{{ $info['label'] }} @if($info['required'])* @endif</label>

                <div class="input-wrapper">
                  @if($key === 'course')
                    <div class="select-search hidden-native" data-target="course">
                      <button type="button" class="select-search-toggle" data-role="toggle">
                        <span class="label-text"><span class="placeholder">-- Select Course --</span></span>
                        <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
                      </button>

                      <div class="select-search-panel">
                        <input type="text" class="select-search-input" placeholder="Search course or college...">
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
                        <div class="select-search-empty d-none">No courses found</div>
                      </div>

                      <select id="course" name="course">
                        <option value="">-- Select Course --</option>
                        @foreach($courses as $course)
                          <option value="{{ $course->course_name }}" data-college="{{ $course->college }}">
                            {{ $course->course_name }}
                          </option>
                        @endforeach
                      </select>
                      <input type="hidden" id="college" name="college">
                    </div>

                  @elseif($key === 'year_level')
                    <div class="select-search hidden-native" data-target="year_level">
                      <button type="button" class="select-search-toggle" data-role="toggle">
                        <span class="label-text"><span class="placeholder">-- Select Year Level --</span></span>
                        <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
                      </button>

                      <div class="select-search-panel">
                        <input type="text" class="select-search-input" placeholder="Search year...">
                        <ul class="select-search-list">
                          @for($i = 1; $i <= 4; $i++)
                            <li class="select-search-option" data-value="{{ $i }}" data-label="{{ $i }}">
                              <span>{{ $i }}</span>
                            </li>
                          @endfor
                        </ul>
                        <div class="select-search-empty d-none">No years found</div>
                      </div>

                      <select id="year_level" name="year_level">
                        <option value="">-- Select Year Level --</option>
                        @for($i = 1; $i <= 4; $i++)
                          <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                      </select>
                    </div>

                  @elseif($key === 'batch_year')
                    <div class="select-search hidden-native" data-target="batch_year">
                      <button type="button" class="select-search-toggle" data-role="toggle">
                        <span class="label-text"><span class="placeholder">-- Select Batch Year (optional) --</span></span>
                        <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
                      </button>

                      <div class="select-search-panel">
                        <input type="text" class="select-search-input select-search-input-batch" placeholder="Search batch year...">
                        <ul class="select-search-list">
                          @for($y = $currentYear + 1; $y >= $currentYear - 10; $y--)
                            <li class="select-search-option" data-value="{{ $y }}" data-label="{{ $y }}">
                              <span>{{ $y }}</span>
                            </li>
                          @endfor
                        </ul>
                        <div class="select-search-empty d-none">No years found</div>
                      </div>

                      <select id="batch_year" name="batch_year">
                        <option value="">-- Select Batch Year (optional) --</option>
                        @for($y = $currentYear + 1; $y >= $currentYear - 10; $y--)
                          <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                      </select>
                    </div>

                  @elseif($key === 'barangay')
                    <div class="select-search hidden-native" data-target="barangay">
                      <button type="button" class="select-search-toggle" data-role="toggle">
                        <span class="label-text"><span class="placeholder">-- Select Barangay --</span></span>
                        <span class="chevron"><i class="fa-solid fa-chevron-down"></i></span>
                      </button>

                      <div class="select-search-panel">
                        <input type="text" class="select-search-input" placeholder="Search barangay...">
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
                        <div class="select-search-empty d-none">No barangays found</div>
                      </div>

                      <select id="barangay" name="barangay">
                        <option value="">-- Select Barangay --</option>
                        @foreach($locations as $loc)
                          <option value="{{ $loc->barangay }}" data-district="{{ $loc->district_id }}">
                            {{ $loc->barangay }}
                          </option>
                        @endforeach
                      </select>
                    </div>

                  @elseif($key === 'district')
                    <input type="text" id="district" name="district" placeholder="District" readonly>
                    <input type="hidden" id="district_id" name="district_id">

                  @else
                    <input type="text" id="{{ $key }}" name="{{ $key }}" placeholder="{{ $info['label'] }}">
                  @endif

                  <i class="fa-solid {{ $info['icon'] }} input-icon" aria-hidden="true"></i>
                  <span class="error-tooltip" id="{{ $key }}-error"></span>
                </div>
              </div>
            @endforeach

            <input type="hidden" id="class_schedule" name="class_schedule">
            <span class="error-tooltip" id="class_schedule-error"></span>
          </div>

          <div class="ev-modal-footer">
            <button type="button" class="modal-btn cancel" onclick="closeEditVolunteerModal()">
              <i class="fa-solid fa-xmark"></i> Cancel
            </button>
            <button type="submit" class="modal-btn save" disabled>
              <i class="fa-solid fa-floppy-disk"></i> Save Changes
            </button>
          </div>
        </form>

      </div> {{-- /ev-modal-shell --}}
    </div>
  </div>
</div>

{{-- ===========================================================
   ✅ ROBUST FLASH PAYLOADS (SUCCESS + ERRORS)
   (kept as-is; just changing how we display them)
=========================================================== --}}
@if(session('updateDetails'))
  <div id="__ev_success_b64__" style="display:none;">{{ session('updateDetails') }}</div>
@endif

@if($errors->any())
  <div id="__ev_errors_html__" style="display:none;">
    <ul class="mb-0">
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif

<script>
/* ===========================================================
   ✅ EDIT VOLUNTEER FLASH -> UNIVERSAL FEEDBACK MODAL (UFM)
   - Shows success/error via UFM
   - Stores LAST modal payload globally so "See more" can reopen it
   - Works even if UFM loads slightly later (wait loop)
=========================================================== */
(function () {
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

  function decodeB64(b64){
    if (window.FeedbackModal?.decodeBase64Utf8) return window.FeedbackModal.decodeBase64Utf8(b64);
    try { return decodeURIComponent(escape(atob(b64))); }
    catch (e) { try { return atob(b64); } catch (e2) { return ''; } }
  }

  if (!window.__UFM_LAST__) window.__UFM_LAST__ = null;

  function ensureSeeMoreLink(html, variant){
    const hasSeeMore =
      /data-ufm-details=|data-details=/i.test(html) ||
      /see\s*more/i.test(html);

    if (hasSeeMore) return html;

    const b64 = b64utf8Encode(html);

    const link = `
      <div style="margin-top:10px;">
        <a href="#" class="${variant === 'error' ? 'error' : 'success'}"
           data-ufm-details="${escHtml(b64)}"
           style="font-weight:600; text-decoration:underline;">
          See more
        </a>
      </div>
    `;
    return String(html || '') + link;
  }

  function getFlashPayload(){
    const errEl = document.getElementById('__ev_errors_html__');
    const errHtml = (errEl?.innerHTML || '').trim();
    if (errHtml) {
      return {
        variant: 'error',
        title: 'Save failed',
        subtitle: 'Please fix the highlighted fields.',
        html: errHtml,
        source: 'edit_volunteer_flash_error'
      };
    }

    const el = document.getElementById('__ev_success_b64__');
    const b64 = (el?.textContent || '').trim();
    if (!b64) return null;

    return {
      variant: 'success',
      title: 'Changes saved',
      subtitle: 'Entry updated successfully.',
      b64,
      source: 'edit_volunteer_flash_success'
    };
  }

  function showPayload(payload){
    if (!payload) return;

    let html = payload.html || '';
    if (payload.b64) html = decodeB64(payload.b64);

    // store last
    window.__UFM_LAST__ = {
      variant: payload.variant,
      title: payload.title,
      subtitle: payload.subtitle,
      html: html,
      source: payload.source
    };

    html = ensureSeeMoreLink(html, payload.variant);

    window.FeedbackModal.show({
      variant: payload.variant,
      title: payload.title,
      subtitle: payload.subtitle,
      html: html,
      source: payload.source
    });
  }

  // optional programmatic recall (if you ever want controller link to call JS)
  window.recallLastUfm = function(){
    const p = window.__UFM_LAST__;
    if (!p || !window.FeedbackModal?.show) return;

    window.FeedbackModal.show({
      variant: p.variant || 'info',
      title: p.title || 'Notice',
      subtitle: p.subtitle || '',
      html: ensureSeeMoreLink(p.html || '', p.variant),
      userAction: true,              // ✅ bypass single-fire lock
      source: 'recallLastUfm'
    });
  };

  document.addEventListener('DOMContentLoaded', function () {
    const payload = getFlashPayload();
    if (!payload) return;

    // wait for UFM to exist (handles load order)
    let tries = 0;
    const maxTries = 80; // ~2s
    const t = setInterval(function(){
      tries++;
      if (window.FeedbackModal?.show) {
        clearInterval(t);
        showPayload(payload);
        return;
      }
      if (tries >= maxTries) {
        clearInterval(t);
        console.error('[EV FLASH] FeedbackModal not available - check UFM js load/order.');
      }
    }, 25);
  });
})();
</script>

<script>
/* ===========================================================
   EDIT MODAL + VALIDATION + SEARCHABLE SELECTS (PATCHED)
   (UNCHANGED)
=========================================================== */
(function () {
  const modal          = document.getElementById('editVolunteerModal');
  const overlay        = modal.querySelector('.modal-overlay');
  const form           = document.getElementById('editVolunteerForm');
  const saveBtn        = form.querySelector('.modal-btn.save');

  const barangaySelect  = document.getElementById('barangay');
  const districtInput   = document.getElementById('district');
  const districtIdInput = document.getElementById('district_id');

  const courseSelect    = document.getElementById('course');
  const collegeInput    = document.getElementById('college');

  const yearLevelSelect = document.getElementById('year_level');
  const batchYearSelect = document.getElementById('batch_year');

  let __initialStateJson = '';
  let __openedType = null;
  let __openedIndex = null;

  function updateDistrict() {
    const selected = (barangaySelect.value || '').trim();
    const errorSpan = document.getElementById('district-error');

    if (!selected) {
      districtInput.value = '';
      districtIdInput.value = '';
      if (errorSpan) errorSpan.textContent = 'District depends on Barangay selection';
      districtInput.classList.add('invalid');
      districtInput.classList.remove('valid');
      return;
    }

    const districtId = locationsMap[selected];
    if (districtId) {
      districtInput.value = "District " + districtId;
      districtIdInput.value = districtId;
      if (errorSpan) errorSpan.textContent = '';
      districtInput.classList.add('valid');
      districtInput.classList.remove('invalid');
    } else {
      districtInput.value = '';
      districtIdInput.value = '';
      if (errorSpan) errorSpan.textContent = 'Invalid district for selected barangay';
      districtInput.classList.add('invalid');
      districtInput.classList.remove('valid');
    }
  }

  const rules = {
    full_name: v => v.trim() !== '' && /^[A-Za-zÑñ\s\.\'-]+$/.test(v) ? true : 'Invalid full name',
    id_number: v => /^\d{6,7}$/.test(v.trim()) ? true : 'ID must be 6-7 digits',
    course: v => v !== '' ? true : 'Please select a course',
    year_level: v => /^[1-4]$/.test(v.trim()) ? true : 'Year must be 1-4',
    batch_year: v => {
      const value = v.trim();
      if (!value) return true;
      if (!/^\d{4}$/.test(value)) return 'Batch year must be 4 digits';
      const year = parseInt(value, 10);
      const nowY = (new Date()).getFullYear();
      if (year < 2000 || year > nowY + 1) return 'Batch year looks invalid';
      return true;
    },
    contact_number: v => {
      const value = v.trim();
      if (!/^(09|\+639)\d{9}$/.test(value)) return 'Invalid PH number';
      const emergency = (document.getElementById('emergency_contact')?.value || '').trim();
      if (emergency && emergency === value) return 'Contact # and emergency # must be different';
      return true;
    },
    emergency_contact: v => {
      const value = v.trim();
      if (!/^(09|\+639)\d{9}$/.test(value)) return 'Invalid PH number';
      const contact = (document.getElementById('contact_number')?.value || '').trim();
      if (contact && contact === value) return 'Contact # and emergency # must be different';
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
        if (!['http:', 'https:'].includes(url.protocol)) return 'URL must start with http:// or https://';
        const host = (url.hostname || '').toLowerCase().replace(/^www\./,'');
        const okHost =
          host === 'facebook.com' || host.endsWith('.facebook.com') ||
          host === 'fb.com' || host.endsWith('.fb.com') ||
          host === 'messenger.com' || host.endsWith('.messenger.com') ||
          host === 'm.me';
        if (!okHost) return 'URL should be a Facebook/Messenger link';
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
    class_schedule: v => true
  };

  function syncSelectSearchValidity(input, isValid, hasError) {
    const wrapper = document.querySelector('#editVolunteerModal .select-search[data-target="' + input.id + '"]');
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
      if (errorSpan) { errorSpan.textContent = res; errorSpan.style.display = 'block'; }
    } else {
      input.classList.remove('invalid');
      input.classList.add('valid');
      if (errorSpan) { errorSpan.textContent = ''; errorSpan.style.display = 'none'; }
    }

    if (['course', 'barangay', 'batch_year', 'year_level'].includes(input.id)) {
      syncSelectSearchValidity(input, !hasError, hasError);
    }

    return !hasError;
  }

  function normStr(s){
    return String(s ?? '').replace(/\u00A0/g,' ').replace(/\s+/g,' ').trim();
  }

  function getFormState() {
    const keys = [
      'full_name','id_number','course','college','year_level','batch_year',
      'contact_number','emergency_contact','email','fb_messenger',
      'barangay','district','district_id','class_schedule'
    ];
    const state = {};
    keys.forEach(k => {
      const el = document.getElementById(k);
      state[k] = el ? normStr(el.value) : '';
    });
    return state;
  }

  function captureInitialState() { __initialStateJson = JSON.stringify(getFormState()); }
  function isDirty() { return __initialStateJson !== JSON.stringify(getFormState()); }

  function updateSaveButton(allValid) {
    const dirty = isDirty();
    const canSave = !!(allValid && dirty);
    saveBtn.disabled = !canSave;
    saveBtn.classList.toggle('enabled', canSave);
  }

  function validateAll() {
    let allValid = true;
    document.querySelectorAll('#editVolunteerModal .volunteer-info input, #editVolunteerModal .volunteer-info select').forEach(input => {
      if (rules[input.id] && !validateField(input)) allValid = false;
    });
    updateSaveButton(allValid);
    return allValid;
  }

  document.querySelectorAll('#editVolunteerModal .volunteer-info input, #editVolunteerModal .volunteer-info select').forEach(input => {
    ['input', 'change', 'blur'].forEach(evt => input.addEventListener(evt, validateAll));
  });

  barangaySelect.addEventListener('change', () => { updateDistrict(); validateAll(); });

  courseSelect.addEventListener('change', () => {
    const opt = courseSelect.options[courseSelect.selectedIndex];
    collegeInput.value = opt ? (opt.dataset.college || '') : '';
    validateAll();
  });

  yearLevelSelect?.addEventListener('change', validateAll);
  batchYearSelect?.addEventListener('change', validateAll);

  function syncSelectSearchFromSelect(targetId) {
    const wrapper = document.querySelector('#editVolunteerModal .select-search[data-target="' + targetId + '"]');
    if (!wrapper) return;

    const select = wrapper.querySelector('select');
    const toggle = wrapper.querySelector('.select-search-toggle');
    const labelSpan = toggle.querySelector('.label-text');
    const value = select.value;
    const optionsEls = wrapper.querySelectorAll('.select-search-option');
    const placeholderText = wrapper.dataset.placeholder || '-- Select --';

    optionsEls.forEach(li => li.classList.toggle('is-active', li.dataset.value === value));

    if (!value) labelSpan.innerHTML = '<span class="placeholder">' + placeholderText + '</span>';
    else {
      const opt = Array.from(select.options).find(o => o.value === value);
      labelSpan.textContent = opt ? opt.textContent : value;
    }
  }

  function initSelectSearch(wrapper) {
    const target = wrapper.dataset.target;
    const toggle = wrapper.querySelector('[data-role="toggle"]');
    const panel  = wrapper.querySelector('.select-search-panel');
    const searchInput = wrapper.querySelector('.select-search-input');
    const list   = wrapper.querySelector('.select-search-list');
    const emptyText = wrapper.querySelector('.select-search-empty');
    const select = wrapper.querySelector('select');

    if (!toggle || !panel || !searchInput || !list || !select) return;

    const labelSpan = toggle.querySelector('.label-text');
    const placeholderSpan = labelSpan.querySelector('.placeholder');
    wrapper.dataset.placeholder = placeholderSpan ? placeholderSpan.textContent : '-- Select --';

    function openPanel() {
      document.querySelectorAll('#editVolunteerModal .select-search.is-open').forEach(w => { if (w !== wrapper) w.classList.remove('is-open'); });
      wrapper.classList.add('is-open');
      searchInput.value = '';
      filterOptions('');
      setTimeout(() => { if (searchInput && searchInput.offsetParent !== null) searchInput.focus(); }, 10);
    }
    function closePanel(){ wrapper.classList.remove('is-open'); }

    function filterOptions(term) {
      const t = String(term || '').toLowerCase();
      let visibleCount = 0;

      list.querySelectorAll('.select-search-option').forEach(li => {
        const label = (li.dataset.label || '').toLowerCase();
        const college = (li.dataset.college || '').toLowerCase();
        const hay = label + ' ' + college;
        const match = !t || hay.includes(t);
        li.style.display = match ? '' : 'none';
        if (match) visibleCount++;
      });

      if (emptyText) emptyText.classList.toggle('d-none', visibleCount > 0);
    }

    toggle.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation();
      wrapper.classList.contains('is-open') ? closePanel() : openPanel();
    });

    searchInput.addEventListener('input', () => filterOptions(searchInput.value));

    list.addEventListener('click', (e) => {
      const optionEl = e.target.closest('.select-search-option');
      if (!optionEl) return;

      const value = optionEl.dataset.value || '';
      select.value = value;
      select.dispatchEvent(new Event('change', { bubbles: true }));

      syncSelectSearchFromSelect(target);
      validateField(select);
      validateAll();
      closePanel();
    });

    document.addEventListener('click', (e) => { if (!wrapper.contains(e.target)) closePanel(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePanel(); });

    syncSelectSearchFromSelect(target);
  }

  document.querySelectorAll('#editVolunteerModal .select-search').forEach(initSelectSearch);

  window._lastActionsToggleAfterEdit = null;

  window.openEditVolunteerModal = function (type, index, originEl) {
    const actionsBtn = originEl
      ? originEl.closest('.entry-actions')?.querySelector('.entry-actions-btn')
      : null;

    window._lastActionsToggleAfterEdit = actionsBtn || null;

    if (actionsBtn && typeof bootstrap !== 'undefined') {
      const inst = bootstrap.Dropdown.getOrCreateInstance(actionsBtn, { autoClose: 'outside' });
      inst.hide();
    } else if (typeof window.closeAllEntryDropdowns === 'function') {
      window.closeAllEntryDropdowns();
    }

    __openedType = type;
    __openedIndex = index;

    const group = window.volunteersData[type] || [];
    const volunteer = group[index] || {};

    const simpleKeys = [
      'full_name','id_number','contact_number','emergency_contact','email','fb_messenger','class_schedule'
    ];
    simpleKeys.forEach(key => {
      const input = document.getElementById(key);
      if (input) input.value = volunteer[key] || '';
    });

    if (courseSelect) {
      courseSelect.value = volunteer.course ? String(volunteer.course) : '';
      const courseOpt = courseSelect.options[courseSelect.selectedIndex];
      collegeInput.value = courseOpt ? (courseOpt.dataset.college || '') : '';
      syncSelectSearchFromSelect('course');
    }

    if (yearLevelSelect) {
      yearLevelSelect.value = volunteer.year_level ? String(volunteer.year_level) : '';
      syncSelectSearchFromSelect('year_level');
    }

    if (batchYearSelect) {
      batchYearSelect.value = volunteer.batch_year ? String(volunteer.batch_year) : '';
      syncSelectSearchFromSelect('batch_year');
    }

    if (barangaySelect) {
      if (volunteer.barangay && locationsMap[volunteer.barangay]) barangaySelect.value = volunteer.barangay;
      else barangaySelect.value = '';
      syncSelectSearchFromSelect('barangay');
    }

    updateDistrict();

    if (volunteer.district_id) {
      districtIdInput.value = String(volunteer.district_id);
      districtInput.value = 'District ' + String(volunteer.district_id);
    }

    const routeTemplate = "{{ route('volunteer.import.update-entry', ['index' => '__INDEX__', 'type' => '__TYPE__']) }}";
    form.action = routeTemplate.replace('__INDEX__', index).replace('__TYPE__', type);

    modal.classList.add('is-open');
    document.documentElement.style.overflow = 'hidden';
    document.body.style.overflow = 'hidden';

    validateAll();
    captureInitialState();
    updateSaveButton(true);
  };

  window.closeEditVolunteerModal = function () {
    modal.classList.remove('is-open');
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';

    if (window._lastActionsToggleAfterEdit) {
      try {
        if (typeof bootstrap !== 'undefined') {
          const inst = bootstrap.Dropdown.getOrCreateInstance(window._lastActionsToggleAfterEdit, { autoClose: 'outside' });
          inst.show();
        }
      } catch (err) {
        console.error('Failed to reopen Actions dropdown after edit modal:', err);
      }
      window._lastActionsToggleAfterEdit = null;
    }
  };

  overlay.addEventListener('click', e => { if (e.target === overlay) closeEditVolunteerModal(); });
  document.addEventListener('keydown', e => { if (modal.classList.contains('is-open') && e.key === 'Escape') closeEditVolunteerModal(); });

  form.addEventListener('submit', (e) => {
    const ok = validateAll();
    const dirty = isDirty();
    if (!ok || !dirty) {
      e.preventDefault();
      updateSaveButton(ok);
      return;
    }
  });
})();
</script>
