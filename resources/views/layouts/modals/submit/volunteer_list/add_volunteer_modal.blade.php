@php
  $DEFAULT_AVATAR = $DEFAULT_AVATAR ?? asset('storage/defaults/default_user.png');
  $courses   = $courses   ?? collect();
  $locations = $locations ?? collect();
  $districts = $districts ?? collect();
@endphp

<style>
  /* =========================================================
     Add Volunteer Modal (scoped)
     - Keeps IDs compatible with assets/volunteer_list/js/script.js
     - No JS here (handled by script.js)
  ========================================================= */
  .vlAdd-modal .modal-dialog{ max-width: 1180px; }
  @media (max-width: 1400px){ .vlAdd-modal .modal-dialog{ max-width: 1100px; } }

  .vlAdd-header{
    background: linear-gradient(135deg,#7f1d1d,#b91c1c 55%,#ef4444);
    color:#fff;
    border-bottom: 0;
  }
  .vlAdd-header .modal-title{ font-weight: 1000; letter-spacing: .2px; }
  .vlAdd-header .btn-close{ filter: invert(1); opacity: .9; }

  .vlAdd-body{ background:#fff; }
  .vlAdd-footer{ background:#f7f7f9; border-top: 1px solid rgba(15,23,42,.08); }

  .vlAdd-shell{ display:grid; grid-template-columns: 320px 1fr; gap: 16px; }
  @media (max-width: 992px){ .vlAdd-shell{ grid-template-columns: 1fr; } }

  .vlAdd-card{
    border:1px solid rgba(15,23,42,.10);
    border-radius:18px;
    background:#fff;
    box-shadow: 0 12px 28px rgba(2,6,23,.08);
    padding: 14px;
  }

  .vlAdd-photo{
    width: 100%;
    max-width: 220px;
    aspect-ratio: 1 / 1;
    border-radius: 18px;
    object-fit: cover;
    background:#fff;
    border: 3px solid rgba(255,255,255,.85);
    box-shadow: 0 14px 32px rgba(2,6,23,.16);
  }
  .vlAdd-miniHint{ font-size: 12px; color:#6b7280; font-weight: 800; }

  .vlAdd-label{ font-weight: 1000; color:#7f1d1d; }
  .vlAdd-label .req{ color:#dc2626; }

  /* inputs */
  .vlAdd-input.form-control,
  .vlAdd-input.form-select{
    border-radius: 14px;
    border-color: rgba(15,23,42,.14);
    font-weight: 800;
  }
  .vlAdd-input.form-control:focus,
  .vlAdd-input.form-select:focus{
    border-color: rgba(185,28,28,.55);
    box-shadow: 0 0 0 .2rem rgba(185,28,28,.15);
  }

  /* “Pretty” select (still native, just styled) */
  .vlAdd-selectWrap{ position: relative; }
  .vlAdd-selectWrap::after{
    content:"\f078"; /* fa-chevron-down */
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    position:absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(127,29,29,.75);
    pointer-events: none;
    font-size: 12px;
  }
  .vlAdd-selectWrap select{
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    padding-right: 40px; /* room for arrow */
    background-image: none !important;
  }

  /* Search-above-select helper */
  .vlAdd-filterWrap{ position: relative; }
  .vlAdd-filterIcon{
    position:absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(107,114,128,.9);
    font-size: 12px;
  }
  .vlAdd-filterInput{
    padding-left: 34px;
    border-radius: 14px;
    border-color: rgba(15,23,42,.12);
    font-weight: 800;
  }
  .vlAdd-filterInput:focus{
    border-color: rgba(185,28,28,.55);
    box-shadow: 0 0 0 .2rem rgba(185,28,28,.12);
  }

  /* schedule preview */
  .vlAdd-scheduleBox{
    border:1px dashed rgba(185,28,28,.35);
    background: rgba(185,28,28,.05);
    border-radius: 16px;
    padding: 10px 12px;
    font-weight: 900;
    color:#374151;
  }

  .vlAdd-btnPrimary{
    background: #b91c1c !important;
    border-color: #b91c1c !important;
    font-weight: 950;
    border-radius: 999px;
    padding: 10px 16px;
    box-shadow: 0 12px 26px rgba(185,28,28,.18);
  }
  .vlAdd-btnPrimary:hover{
    background:#991b1b !important;
    border-color:#991b1b !important;
  }

  .vlAdd-btnGhost{
    border-radius: 999px;
    font-weight: 900;
  }

  .vlAdd-scheduleBtn{
    border-radius: 14px;
    font-weight: 950;
    border-color: rgba(185,28,28,.35) !important;
  }
  .vlAdd-scheduleBtn:hover{
    background: rgba(185,28,28,.08) !important;
  }

  /* tighten bootstrap help text */
  .vlAdd-help{ font-size: 12px; font-weight: 800; color:#6b7280; }
</style>

{{-- ================================
     ADD VOLUNTEER MODAL
     (IDs preserved for script.js)
================================= --}}
<div class="modal fade vlAdd-modal" id="addVolunteerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content" style="border-radius:18px; overflow:hidden;">
      <div class="modal-header vlAdd-header">
        <h5 class="modal-title mb-0">
          <i class="fa-solid fa-user-plus me-2"></i>Add Volunteer
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="POST" action="{{ url('/volunteers') }}" enctype="multipart/form-data" id="vlAddVolunteerForm">
        @csrf

        <div class="modal-body vlAdd-body">
          <div class="vlAdd-shell">
            {{-- LEFT: photo + status --}}
            <div class="vlAdd-card">
              <div class="d-flex flex-column gap-3">
                <div>
                  <img id="vlPhotoPreview" class="vlAdd-photo" src="{{ $DEFAULT_AVATAR }}" alt="Profile Preview">
                </div>

                <div>
                  <label class="form-label vlAdd-label mb-1">Profile Photo</label>
                  <input id="vlPhotoInput" type="file" name="profile_picture" class="form-control vlAdd-input" accept="image/*" />
                  <div class="vlAdd-miniHint mt-1">Optional. JPG/PNG up to 4MB.</div>
                </div>

                <div>
                  <label class="form-label vlAdd-label mb-1">Status</label>
                  <div class="vlAdd-selectWrap">
                    <select name="status" class="form-select vlAdd-input @error('status') is-invalid @enderror">
                      <option value="active" {{ old('status','active') === 'active' ? 'selected' : '' }}>Active</option>
                      <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                  </div>
                  @error('status')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>

                <div class="vlAdd-help">
                  <i class="fa-solid fa-circle-info me-1"></i>
                  Tip: Use filters above the dropdowns to find items quickly.
                </div>
              </div>
            </div>

            {{-- RIGHT: fields --}}
            <div class="vlAdd-card">
              <div class="row g-3">
                <div class="col-md-8">
                  <label class="form-label vlAdd-label">Full Name <span class="req">*</span></label>
                  <input name="full_name"
                         class="form-control vlAdd-input @error('full_name') is-invalid @enderror"
                         value="{{ old('full_name') }}"
                         required maxlength="255"
                         placeholder="e.g., Juan D. Dela Cruz">
                  @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                  <label class="form-label vlAdd-label">School ID <span class="req">*</span></label>
                  <input name="id_number"
                         class="form-control vlAdd-input @error('id_number') is-invalid @enderror"
                         value="{{ old('id_number') }}"
                         required pattern="\d{6,7}" maxlength="7"
                         placeholder="6–7 digit ID">
                  <div class="vlAdd-help mt-1">Must be unique.</div>
                  @error('id_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-8">
                  <label class="form-label vlAdd-label">Course <span class="req">*</span></label>

                  <div class="vlAdd-filterWrap mb-1">
                    <i class="fa-solid fa-magnifying-glass vlAdd-filterIcon"></i>
                    {{-- IMPORTANT: ID kept for script.js filtering --}}
                    <input type="text" class="form-control form-control-sm vlAdd-filterInput" id="vlCourseSearch" placeholder="Search course…">
                  </div>

                  <div class="vlAdd-selectWrap">
                    {{-- IMPORTANT: ID kept for script.js filtering --}}
                    <select name="course_id" id="vlCourseSelect" class="form-select vlAdd-input @error('course_id') is-invalid @enderror" required>
                      <option value="">Select course</option>
                      @foreach($courses as $c)
                        <option value="{{ $c->course_id }}" {{ old('course_id') == $c->course_id ? 'selected' : '' }}>
                          {{ $c->abbr ? $c->abbr.' — ' : '' }}{{ $c->course_name }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  @error('course_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                  <label class="form-label vlAdd-label">Year Level <span class="req">*</span></label>
                  <div class="vlAdd-selectWrap">
                    <select name="year_level" class="form-select vlAdd-input @error('year_level') is-invalid @enderror" required>
                      <option value="">Year</option>
                      <option value="1" {{ old('year_level') == '1' ? 'selected' : '' }}>1st Year</option>
                      <option value="2" {{ old('year_level') == '2' ? 'selected' : '' }}>2nd Year</option>
                      <option value="3" {{ old('year_level') == '3' ? 'selected' : '' }}>3rd Year</option>
                      <option value="4" {{ old('year_level') == '4' ? 'selected' : '' }}>4th Year</option>
                    </select>
                  </div>
                  @error('year_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label vlAdd-label">Contact Number <span class="req">*</span></label>
                  <input name="contact_number"
                         class="form-control vlAdd-input @error('contact_number') is-invalid @enderror"
                         value="{{ old('contact_number') }}"
                         required pattern="^(09\d{9}|\+639\d{9})$"
                         placeholder="09XXXXXXXXX or +639XXXXXXXXX">
                  <div class="vlAdd-help mt-1">Valid PH mobile number.</div>
                  @error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label vlAdd-label">Emergency Number <span class="req">*</span></label>
                  <input name="emergency_contact"
                         class="form-control vlAdd-input @error('emergency_contact') is-invalid @enderror"
                         value="{{ old('emergency_contact') }}"
                         required pattern="^(09\d{9}|\+639\d{9})$"
                         placeholder="09XXXXXXXXX or +639XXXXXXXXX">
                  <div class="vlAdd-help mt-1">Must be different from Contact Number.</div>
                  @error('emergency_contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label vlAdd-label">Email <span class="req">*</span></label>
                  <input name="email"
                         type="email"
                         class="form-control vlAdd-input @error('email') is-invalid @enderror"
                         value="{{ old('email') }}"
                         required
                         placeholder="name@adzu.edu.ph or name@gmail.com">
                  <div class="vlAdd-help mt-1">Only @adzu.edu.ph or @gmail.com.</div>
                  @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label vlAdd-label">FB / Messenger (optional)</label>
                  <input name="fb_messenger"
                         type="url"
                         class="form-control vlAdd-input @error('fb_messenger') is-invalid @enderror"
                         value="{{ old('fb_messenger') }}"
                         placeholder="https://facebook.com/…">
                  @error('fb_messenger')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label vlAdd-label">Barangay <span class="req">*</span></label>

                  <div class="vlAdd-filterWrap mb-1">
                    <i class="fa-solid fa-magnifying-glass vlAdd-filterIcon"></i>
                    {{-- IMPORTANT: ID kept for script.js filtering --}}
                    <input type="text" class="form-control form-control-sm vlAdd-filterInput" id="vlBarangaySearch" placeholder="Search barangay…">
                  </div>

                  <div class="vlAdd-selectWrap">
                    {{-- IMPORTANT: ID kept for script.js filtering + auto district --}}
                    <select name="barangay" id="vlBarangaySelect" class="form-select vlAdd-input @error('barangay') is-invalid @enderror" required>
                      <option value="">Select barangay</option>
                      @foreach($locations as $loc)
                        <option value="{{ $loc->barangay }}"
                                data-district="{{ $loc->district_id }}"
                                {{ old('barangay') === $loc->barangay ? 'selected' : '' }}>
                          {{ $loc->barangay }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  @error('barangay')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                  <label class="form-label vlAdd-label">District <span class="req">*</span></label>
                  <div class="vlAdd-selectWrap">
                    {{-- IMPORTANT: ID kept for script.js auto district --}}
                    <select name="district" id="vlDistrictSelect" class="form-select vlAdd-input @error('district') is-invalid @enderror" required>
                      <option value="">Select district</option>
                      @foreach($districts as $d)
                        <option value="{{ $d->district_id }}" {{ old('district') == $d->district_id ? 'selected' : '' }}>
                          {{ $d->district_name }}
                        </option>
                      @endforeach
                    </select>
                  </div>
                  <div class="vlAdd-help mt-1">Auto-filled when you pick a barangay.</div>
                  @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                  <label class="form-label vlAdd-label">Class Schedule</label>
                  <button type="button" class="btn btn-outline-danger w-100 vlAdd-scheduleBtn" id="vlScheduleTrigger">
                    <i class="fa-solid fa-calendar-days me-1"></i> Set Schedule
                  </button>
                  <div class="vlAdd-help mt-1">Optional</div>
                </div>

                <div class="col-12">
                  {{-- IMPORTANT: IDs kept for script.js schedule builder --}}
                  <input type="hidden" name="class_schedule" id="vlScheduleField" value="{{ old('class_schedule') }}">
                  <div class="vlAdd-scheduleBox">
                    <span id="vlScheduleSummary">No schedule set. Volunteers will be treated as available on any day &amp; time.</span>
                  </div>
                  @error('class_schedule')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                  @enderror
                </div>

              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer vlAdd-footer">
          <button type="button" class="btn btn-light vlAdd-btnGhost" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger vlAdd-btnPrimary">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Separate include for schedule modal --}}
@include('layouts.modals.submit.volunteer_list.class_schedule_modal')
