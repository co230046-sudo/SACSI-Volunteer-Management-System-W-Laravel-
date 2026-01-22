@php
  $DEFAULT_AVATAR = $DEFAULT_AVATAR ?? asset('storage/defaults/default_user.png');
  $courses   = $courses   ?? collect();
  $locations = $locations ?? collect();
  $districts = $districts ?? collect();

  $vlAddShowSuccess   = session()->has('success') || session()->has('status') || session()->has('message');
  $vlAddHasErrors     = session()->has('errors') && $errors->any();
  $vlAddVolunteerName = session('vlAddVolunteerName');

  $vlAddVolunteerIdNumber = session('vlAddVolunteerIdNumber');
  $vlAddSavedAtIso = session('vlAddSavedAtIso') ?? now()->toIso8601String();        

@endphp

<style>
  /* ================================
     Add Volunteer Modal (scoped)
     - combobox style
     - photo zoom doesn't reset
     - solid crimson header
     - fb link validation
  ================================= */

  .vlAdd-modal .modal-dialog{ max-width: 1180px; }
  @media (max-width: 1400px){ .vlAdd-modal .modal-dialog{ max-width: 1100px; } }

  .vlAdd-header{
    background: #8b1234 !important;
    color:#fff; border-bottom: 0;
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

  .vlAdd-label{ font-weight: 1000; color:#8b1234; }
  .vlAdd-label .req{ color:#dc2626; }

  .vlAdd-input.form-control,
  .vlAdd-input.form-select{
    border-radius: 14px;
    border-color: rgba(15,23,42,.14);
    font-weight: 800;
    min-height: 44px;
  }
  .vlAdd-input.form-control:focus,
  .vlAdd-input.form-select:focus{
    border-color: rgba(225,29,72,.55);
    box-shadow: 0 0 0 .2rem rgba(225,29,72,.15);
  }

  /* remove bootstrap validation icons */
  .vlAdd-modal .form-control.is-valid,
  .vlAdd-modal .form-control.is-invalid,
  .vlAdd-modal .form-select.is-valid,
  .vlAdd-modal .form-select.is-invalid,
  .vlAdd-modal .vlAdd-selectStyled.is-valid,
  .vlAdd-modal .vlAdd-selectStyled.is-invalid{
    background-image: none !important;
    padding-right: .75rem !important;
  }

  /* kill browser autofill blue bg */
  .vlAdd-modal input:-webkit-autofill,
  .vlAdd-modal input:-webkit-autofill:hover,
  .vlAdd-modal input:-webkit-autofill:focus,
  .vlAdd-modal textarea:-webkit-autofill,
  .vlAdd-modal textarea:-webkit-autofill:hover,
  .vlAdd-modal textarea:-webkit-autofill:focus,
  .vlAdd-modal select:-webkit-autofill,
  .vlAdd-modal select:-webkit-autofill:hover,
  .vlAdd-modal select:-webkit-autofill:focus{
    -webkit-text-fill-color: #111827 !important;
    -webkit-box-shadow: 0 0 0 1000px #ffffff inset !important;
    box-shadow: 0 0 0 1000px #ffffff inset !important;
    transition: background-color 999999s ease-in-out 0s !important;
  }

  .vlAdd-btnPrimary{
    background: #e11d48 !important;
    border-color: #e11d48 !important;
    font-weight: 950;
    border-radius: 999px;
    padding: 10px 16px;
    box-shadow: 0 12px 26px rgba(225,29,72,.18);
  }
  .vlAdd-btnPrimary:hover{ background:#be123c !important; border-color:#be123c !important; }
  .vlAdd-btnGhost{ border-radius: 999px; font-weight: 900; }

  .vlAdd-scheduleBtn{
    border-radius: 14px;
    font-weight: 950;
    border-color: rgba(225,29,72,.45) !important;
    color:#8b1234 !important;
    min-height: 44px;
    background:#fff;
  }
  .vlAdd-scheduleBtn:hover{
    background:#e11d48 !important;
    border-color:#e11d48 !important;
    color:#fff !important;
  }
  .vlAdd-scheduleBtn.is-valid{
    background: rgba(22,163,74,.08) !important;
    border-color: #16a34a !important;
    color:#166534 !important;
  }

  /* ================================
     Combobox UI
  ================================= */
  .vlAdd-nativeSelect{
    position:absolute !important;
    left:-99999px !important;
    width:1px !important;
    height:1px !important;
    opacity:0 !important;
    pointer-events:none !important;
  }

  .vlAdd-combo{ position: relative; width: 100%; }

  .vlAdd-comboInput{
    width: 100%;
    border-radius: 14px;
    border: 1px solid rgba(15,23,42,.14);
    padding: 10px 46px 10px 12px;
    font-weight: 900;
    outline: none;
    min-height: 44px;
    background:#fff;
  }
  .vlAdd-comboInput:focus{
    border-color: rgba(225,29,72,.55);
    box-shadow: 0 0 0 .2rem rgba(225,29,72,.15);
  }

  .vlAdd-comboIcons{
    position:absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    display:flex;
    align-items:center;
    gap: 10px;
  }

  .vlAdd-comboClear{
    border:0;
    background: transparent;
    font-size: 14px;
    color: rgba(107,114,128,.95);
    padding: 2px 6px;
    border-radius: 10px;
    display:none;
  }
  .vlAdd-comboClear:hover{ background: rgba(15,23,42,.06); }

  .vlAdd-ddPortal{
    position: fixed;
    z-index: 20000;
    display:none;
    background:#fff;
    border: 1px solid rgba(15,23,42,.12);
    border-radius: 16px;
    box-shadow: 0 18px 44px rgba(2,6,23,.18);
    overflow:hidden;
    box-sizing: border-box;
    width: min(520px, calc(100vw - 64px)) !important;
    max-width: min(520px, calc(100vw - 64px)) !important;
    min-width: 0 !important;
  }
  .vlAdd-ddBody{ max-height: 300px; overflow:auto; }

  .vlAdd-ddItem{
    width: 100%;
    padding: 10px 12px;
    border: 0;
    background: transparent;
    text-align:left;
    font-weight: 900;
    color:#111827;
    white-space: normal;
    overflow-wrap: anywhere;
  }
  .vlAdd-ddItem:hover{ background: rgba(225,29,72,.06); }
  .vlAdd-ddItem.is-active{ background: rgba(225,29,72,.10); }

  .vlAdd-ddEmpty{
    padding: 12px;
    font-weight: 900;
    color:#6b7280;
  }

  /* outlines */
  .is-valid{
    border-color: #16a34a !important;
    box-shadow: 0 0 0 .2rem rgba(22,163,74,.15) !important;
  }
  .is-invalid{
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 .2rem rgba(220,38,38,.15) !important;
  }

  .vlAdd-photoPreviewBtn{
    border:0; background:transparent; padding:0; margin:0;
    width:100%;
    display:flex; justify-content:center; align-items:center;
    cursor:pointer;
  }
  .vlAdd-photoPreviewBtn:focus{ outline: none; }

  /* file input: remove bootstrap checkmark icon */
  .vlAdd-modal input[type="file"].form-control.is-valid,
  .vlAdd-modal input[type="file"].form-control.is-invalid,
  .vlAdd-modal .was-validated input[type="file"].form-control:valid,
  .vlAdd-modal .was-validated input[type="file"].form-control:invalid{
    background-image: none !important;
    padding-right: .75rem !important;
  }

  /* FB input: remove bootstrap checkmark icon too */
  .vlAdd-modal #vlFbField.is-valid,
  .vlAdd-modal #vlFbField.is-invalid,
  .vlAdd-modal .was-validated #vlFbField:valid,
  .vlAdd-modal .was-validated #vlFbField:invalid{
    background-image: none !important;
    padding-right: .75rem !important;
  }

  /* ✅ PATCH: stop Bootstrap .was-validated from making OPTIONAL file input look green */
  .vlAdd-modal .was-validated input[type="file"].form-control:valid{
    border-color: rgba(15,23,42,.14) !important;
    box-shadow: none !important;
  }

  /* (optional) keep consistent on focus too */
  .vlAdd-modal .was-validated input[type="file"].form-control:valid:focus{
    border-color: rgba(225,29,72,.55) !important;
    box-shadow: 0 0 0 .2rem rgba(225,29,72,.15) !important;
  }

  /* ================================
    Success Modal (polished)
  ================================ */
  .vlAdd-successCard{
    border: 0;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 22px 60px rgba(2,6,23,.22);
  }

  .vlAdd-successHeader{
    border: 0;
    color:#fff;
    background: linear-gradient(135deg, #8b1234 0%, #e11d48 100%);
    padding: 14px 16px;
  }

  .vlAdd-successIcon{
    width: 36px;
    height: 36px;
    border-radius: 12px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background: rgba(255,255,255,.16);
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.18);
    font-size: 18px;
  }

  .vlAdd-successSub{
    font-size: 12px;
    font-weight: 900;
    opacity: .92;
  }

  .vlAdd-successBody{
    padding: 16px 18px;
  }

  .vlAdd-successMain{
    font-weight: 1000;
    color:#111827;
    font-size: 16px;
  }

  .vlAdd-successMeta{
    font-size: 13px;
    font-weight: 900;
    color:#6b7280;
  }

  .vlAdd-successFooter{
    border: 0;
    padding: 0 18px 18px;
    justify-content: flex-end;
  }

</style>

<div class="modal fade vlAdd-modal" id="addVolunteerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content" style="border-radius:18px; overflow:hidden;">
      <div class="modal-header vlAdd-header">
        <h5 class="modal-title mb-0">
          <i class="fa-solid fa-user-plus me-2"></i>Add Volunteer
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="POST" action="{{ route('volunteers.store') }}" enctype="multipart/form-data" id="vlAddVolunteerForm" novalidate>
        @csrf
        <button type="submit" class="d-none" id="vlAddHiddenSubmit"></button>

        <div class="modal-body vlAdd-body">
          <div class="vlAdd-shell">

            {{-- LEFT SIDE --}}
            <div class="vlAdd-card">
              <div class="d-flex flex-column gap-3">
                <div>
                  <button type="button" class="vlAdd-photoPreviewBtn" id="vlPhotoZoomBtn"
                          aria-label="Preview photo" data-bs-toggle="modal" data-bs-target="#vlPhotoZoomModal">
                    <img id="vlPhotoPreview"
                         class="vlAdd-photo"
                         src="{{ $DEFAULT_AVATAR }}"
                         alt="Profile Preview"
                         onerror="this.onerror=null;this.src='{{ asset('storage/defaults/default_user.png') }}';"
                         data-default="{{ $DEFAULT_AVATAR }}">
                  </button>
                </div>

                <div>
                  <label class="form-label vlAdd-label mb-1">Profile Photo</label>
                  <input id="vlPhotoInput" type="file" name="profile_picture"
                         class="form-control vlAdd-input @error('profile_picture') is-invalid @enderror"
                         accept="image/*" />
                  <div class="vlAdd-miniHint mt-1">
                    Optional. JPG/PNG. If not provided, default photo will be used.
                  </div>
                  @error('profile_picture')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- STATUS (combo style) --}}
                <div>
                  <label class="form-label vlAdd-label mb-1">Status</label>

                  <select name="status" id="vlStatusSelect"
                          class="vlAdd-nativeSelect @error('status') is-invalid @enderror" required>
                    <option value="active" {{ old('status','active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                  </select>

                  <div class="vlAdd-combo" data-combo="status" data-select="#vlStatusSelect">
                    <input id="vlStatusSearch" type="text" class="vlAdd-comboInput" autocomplete="off" placeholder="Select status…">
                    <div class="vlAdd-comboIcons">
                      <button type="button" class="vlAdd-comboClear" aria-label="Clear">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>

                  <div id="vlStatusInvalid" class="invalid-feedback" style="display:none;">
                    Please select a valid status.
                  </div>
                  @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              </div>
            </div>

            {{-- RIGHT SIDE --}}
            <div class="vlAdd-card">
              <div class="row g-3">

                <div class="col-md-8">
                  <label class="form-label vlAdd-label">Full Name <span class="req">*</span></label>
                  <input name="full_name"
                         id="vlFullName"
                         class="form-control vlAdd-input @error('full_name') is-invalid @enderror"
                         value="{{ old('full_name') }}"
                         required maxlength="255"
                         pattern="^[A-Za-zÑñ .]+$"
                         placeholder="e.g., Juan D. Dela Cruz">
                  @unless($errors->has('full_name'))
                    <div class="invalid-feedback">Name must contain letters/spaces/dots only, and must not contain "admin" or "administrator".</div>
                  @endunless
                  @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                  <label class="form-label vlAdd-label">School ID <span class="req">*</span></label>
                  <input name="id_number"
                         id="vlIdNumber"
                         class="form-control vlAdd-input @error('id_number') is-invalid @enderror"
                         value="{{ old('id_number') }}"
                         required inputmode="numeric" autocomplete="off"
                         pattern="^\d{6,7}$" maxlength="7"
                         placeholder="6–7 digit ID">
                  @unless($errors->has('id_number'))
                    <div class="invalid-feedback">School ID must be exactly 6–7 digits.</div>
                  @endunless
                  @error('id_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4">
                  <label class="form-label vlAdd-label">Batch <span class="req">*</span></label>
                  <input name="batch_number"
                        id="vlBatch"
                        class="form-control vlAdd-input @error('batch_number') is-invalid @enderror"
                        value="{{ old('batch_number') }}"
                        required
                        inputmode="numeric"
                        autocomplete="off"
                        pattern="^[1-9]\d*$"
                        placeholder="e.g., 1, 2, 3">

                  @unless($errors->has('batch_number'))
                    <div class="invalid-feedback">
                      Batch number must be a positive number greater than 0.
                    </div>
                  @endunless

                  @error('batch_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- COURSE (combo style) --}}
                <div class="col-md-8">
                  <label class="form-label vlAdd-label">Course <span class="req">*</span></label>

                  <select name="course_id" id="vlCourseSelect"
                          class="vlAdd-nativeSelect @error('course_id') is-invalid @enderror" required>
                    <option value="">Select course</option>
                    @foreach($courses as $c)
                      <option value="{{ $c->course_id }}" {{ old('course_id') == $c->course_id ? 'selected' : '' }}>
                        {{ $c->course_name }}
                      </option>
                    @endforeach
                  </select>

                  <div class="vlAdd-combo" data-combo="course" data-select="#vlCourseSelect">
                    <input id="vlCourseSearch" type="text" class="vlAdd-comboInput" autocomplete="off" placeholder="Type to search course…">
                    <div class="vlAdd-comboIcons">
                      <button type="button" class="vlAdd-comboClear" aria-label="Clear">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>

                  <div id="vlCourseInvalid" class="invalid-feedback" style="display:none;">
                    Please select a course from the list.
                  </div>
                  @error('course_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- YEAR LEVEL (combo style) --}}
                <div class="col-md-4">
                  <label class="form-label vlAdd-label">Year Level <span class="req">*</span></label>

                  <select name="year_level" id="vlYearSelect"
                          class="vlAdd-nativeSelect @error('year_level') is-invalid @enderror"
                          required>
                    <option value="">Select year</option>
                    <option value="1" {{ old('year_level') == '1' ? 'selected' : '' }}>1st Year</option>
                    <option value="2" {{ old('year_level') == '2' ? 'selected' : '' }}>2nd Year</option>
                    <option value="3" {{ old('year_level') == '3' ? 'selected' : '' }}>3rd Year</option>
                    <option value="4" {{ old('year_level') == '4' ? 'selected' : '' }}>4th Year</option>
                  </select>

                  <div class="vlAdd-combo" data-combo="year" data-select="#vlYearSelect">
                    <input id="vlYearSearch" type="text" class="vlAdd-comboInput" autocomplete="off" placeholder="Select year…">
                    <div class="vlAdd-comboIcons">
                      <button type="button" class="vlAdd-comboClear" aria-label="Clear">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>

                  <div id="vlYearInvalid" class="invalid-feedback" style="display:none;">
                    Please select a year level.
                  </div>
                  @error('year_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label vlAdd-label">Contact Number <span class="req">*</span></label>
                  <input name="contact_number"
                         class="form-control vlAdd-input @error('contact_number') is-invalid @enderror"
                         value="{{ old('contact_number') }}"
                         required pattern="^(09\d{9}|\+639\d{9})$"
                         inputmode="numeric"
                         placeholder="09XXXXXXXXX or +639XXXXXXXXX">
                  @unless($errors->has('contact_number'))
                    <div class="invalid-feedback">Enter a valid PH mobile number.</div>
                  @endunless
                  @error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label vlAdd-label">Emergency Number <span class="req">*</span></label>
                  <input name="emergency_contact"
                         class="form-control vlAdd-input @error('emergency_contact') is-invalid @enderror"
                         value="{{ old('emergency_contact') }}"
                         required pattern="^(09\d{9}|\+639\d{9})$"
                         inputmode="numeric"
                         placeholder="09XXXXXXXXX or +639XXXXXXXXX">
                  @unless($errors->has('emergency_contact'))
                    <div class="invalid-feedback">Enter a valid PH mobile number.</div>
                  @endunless
                  @error('emergency_contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                  <label class="form-label vlAdd-label">Email <span class="req">*</span></label>
                  <input name="email"
                         type="email"
                         class="form-control vlAdd-input @error('email') is-invalid @enderror"
                         value="{{ old('email') }}"
                         required
                         placeholder="name@adzu.edu.ph or name@gmail.com"
                         pattern="^[A-Za-z0-9._%+\-]+@(adzu\.edu\.ph|gmail\.com)$">
                  @unless($errors->has('email'))
                    <div class="invalid-feedback">Email must end with @adzu.edu.ph or @gmail.com.</div>
                  @endunless
                  @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- ✅ FB/Messenger (PATCHED: REQUIRED + VALIDATED) --}}
                <div class="col-md-6">
                  <label class="form-label vlAdd-label">FB / Messenger <span class="req">*</span></label>
                  <input name="fb_messenger"
                         id="vlFbField"
                         type="text"
                         class="form-control vlAdd-input @error('fb_messenger') is-invalid @enderror"
                         value="{{ old('fb_messenger') }}"
                         placeholder="facebook.com/... or m.me/..."
                         required>
                  @unless($errors->has('fb_messenger'))
                    <div class="invalid-feedback" id="vlFbInvalid">FB / Messenger is required and must be a valid link (URL).</div>
                  @endunless
                  @error('fb_messenger')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- BARANGAY --}}
                <div class="col-md-6">
                  <label class="form-label vlAdd-label">Barangay <span class="req">*</span></label>

                  <select name="barangay" id="vlBarangaySelect"
                          class="vlAdd-nativeSelect @error('barangay') is-invalid @enderror" required>
                    <option value="">Select barangay</option>
                    @foreach($locations as $loc)
                      <option value="{{ $loc->barangay }}"
                              data-district="{{ $loc->district_id }}"
                              {{ old('barangay') === $loc->barangay ? 'selected' : '' }}>
                        {{ $loc->barangay }}
                      </option>
                    @endforeach
                  </select>

                  <div class="vlAdd-combo" data-combo="barangay" data-select="#vlBarangaySelect">
                    <input id="vlBarangaySearch" type="text" class="vlAdd-comboInput" autocomplete="off" placeholder="Type to search barangay…">
                    <div class="vlAdd-comboIcons">
                      <button type="button" class="vlAdd-comboClear" aria-label="Clear">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>

                  <div id="vlBarangayInvalid" class="invalid-feedback" style="display:none;">
                    Please select a barangay from the list.
                  </div>
                  @error('barangay')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- DISTRICT --}}
                <div class="col-md-3">
                  <label class="form-label vlAdd-label">District <span class="req">*</span></label>

                  <select name="district" id="vlDistrictSelect"
                          class="vlAdd-nativeSelect @error('district') is-invalid @enderror"
                          required>
                    <option value="">Select district</option>
                    @foreach($districts as $d)
                      <option value="{{ $d->district_id }}" {{ old('district') == $d->district_id ? 'selected' : '' }}>
                        {{ $d->district_name }}
                      </option>
                    @endforeach
                  </select>

                  <div class="vlAdd-combo" data-combo="district" data-select="#vlDistrictSelect">
                    <input id="vlDistrictSearch" type="text" class="vlAdd-comboInput" autocomplete="off" placeholder="Select district…">
                    <div class="vlAdd-comboIcons">
                      <button type="button" class="vlAdd-comboClear" aria-label="Clear">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>

                  <div id="vlDistrictInvalid" class="invalid-feedback" style="display:none;">
                    Please select a district.
                  </div>
                  @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3">
                  <label class="form-label vlAdd-label">Class Schedule</label>
                  <button type="button"
                    class="btn btn-outline-danger w-100 vlAdd-scheduleBtn"
                    id="vlScheduleTrigger"
                    data-bs-toggle="modal"
                    data-bs-target="#classScheduleModal"
                    aria-describedby="vlScheduleInvalid">
                    <i class="fa-solid fa-calendar-days me-1"></i> Add Schedule
                  </button>

                  <div id="vlScheduleInvalid" class="invalid-feedback" style="display:none;">Please add a class schedule before saving.</div>
                </div>

                <input type="hidden" name="class_schedule" id="vlScheduleField" value="{{ old('class_schedule') }}">
                @error('class_schedule')
                  <div class="col-12">
                    <div class="text-danger small mt-1">{{ $message }}</div>
                  </div>
                @enderror

              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer vlAdd-footer">
          <button type="button" class="btn btn-light vlAdd-btnGhost" data-bs-dismiss="modal">
            Cancel
          </button>

          <button type="button" class="btn btn-outline-secondary vlAdd-btnGhost" id="vlAddResetBtn">
            <i class="fa-solid fa-rotate-left me-1"></i> Reset
          </button>

          <button type="button" class="btn btn-danger vlAdd-btnPrimary" id="vlAddOpenConfirm">
            <i class="fa-solid fa-floppy-disk me-1"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@include('layouts.modals.submit.volunteer_list.class_schedule_modal')

{{-- CONFIRM MODAL --}}
<div class="modal fade" id="vlAddConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius:18px; overflow:hidden; border:0;">
      <div class="modal-header" style="background:#8b1234; color:#fff; border-bottom:0;">
        <h5 class="modal-title mb-0" style="font-weight:950; letter-spacing:.2px;">
          <i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Save
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1); opacity:.9;"></button>
      </div>

      <div class="modal-body">
        <div class="fw-bold mb-1">Save this volunteer?</div>
        <div class="text-muted small">Please double-check the details before submitting.</div>
      </div>

      <div class="modal-footer" style="background:#f7f7f9; border-top: 1px solid rgba(15,23,42,.08);">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:999px; font-weight:900;">Cancel</button>
        <button type="button" class="btn btn-danger" id="vlAddConfirmSubmit" style="font-weight:950; border-radius:999px;">Yes, Save</button>
      </div>
    </div>
  </div>
</div>

<!-- Photo Zoom Modal -->
<div class="modal fade" id="vlPhotoZoomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:18px; overflow:hidden;">
      <div class="modal-header" style="border:0; padding:14px 16px;">
        <h5 class="modal-title" style="margin:0; font-weight:1000;">Profile Photo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding:0; background:#0b0b0b;">
        <img id="vlPhotoZoomImg" src="{{ $DEFAULT_AVATAR ?? asset('storage/defaults/default_user.png') }}" alt="Profile photo"
             style="display:block; width:100%; height:auto; max-height:78vh; object-fit:contain; margin:0 auto;" />
      </div>
    </div>
  </div>
</div>

<!-- Save Success Modal -->
<div class="modal fade" id="vlAddSuccessModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content vlAdd-successCard">
      <div class="modal-header vlAdd-successHeader">
        <div class="d-flex align-items-center gap-2">
          <span class="vlAdd-successIcon">
            <i class="fa-solid fa-circle-check"></i>
          </span>
          <div>
            <h5 class="modal-title mb-0">Saved</h5>
            <div class="vlAdd-successSub" id="vlAddSuccessStamp">—</div>
          </div>
        </div>

        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body vlAdd-successBody">
        <div class="vlAdd-successMain" id="vlAddSuccessText">
          Volunteer saved successfully.
        </div>

        <div class="vlAdd-successMeta mt-2" id="vlAddSuccessMeta">
          <!-- populated by JS -->
        </div>
      </div>

      <div class="modal-footer vlAdd-successFooter">
        <button type="button" class="btn btn-danger vlAdd-btnPrimary" data-bs-dismiss="modal">
          <i class="fa-solid fa-thumbs-up me-1"></i> OK
        </button>
      </div>
    </div>
  </div>
</div>


<script>
/* ============================================================
   ✅ Add Volunteer Modal JS (FINAL READY-PASTE)
   Fixes:
   ✅ Laravel duplicate errors (email/fb_messenger) show red properly
   ✅ JS does NOT override server invalid state (locks server invalid)
   ✅ Green outlines remain consistent after refresh
   ✅ Combobox inputs validate correctly
   ✅ Schedule button validation correct
   ✅ ✅ FB / Messenger REQUIRED + valid URL
============================================================ */

(function(){
  const btn = document.getElementById('vlScheduleTrigger');
  if (!btn) return;

  if (typeof window.__vlScheduleOpening === 'undefined') window.__vlScheduleOpening = false;
  if (typeof window.__vlScheduleSwitching === 'undefined') window.__vlScheduleSwitching = false;

  const arm = () => {
    window.__vlScheduleOpening = true;
    window.__vlScheduleSwitching = true;
  };

  btn.addEventListener('pointerdown', arm, true);
  btn.addEventListener('mousedown', arm, true);
  btn.addEventListener('click', arm, true);
})();

(function(){
  const modalEl = document.getElementById('addVolunteerModal');
  if (!modalEl) return;

  const SHOULD_SHOW_SUCCESS = {{ $vlAddShowSuccess ? 'true' : 'false' }};
  const HAS_SERVER_ERRORS   = {{ $vlAddHasErrors ? 'true' : 'false' }};
  const ADDED_NAME          = @json($vlAddVolunteerName);

  if (typeof window.__vlScheduleOpening === 'undefined') window.__vlScheduleOpening = false;
  if (typeof window.__vlScheduleSwitching === 'undefined') window.__vlScheduleSwitching = false;

  function whenBootstrapReady(cb){
    if (window.bootstrap && window.bootstrap.Modal) return cb();
    setTimeout(() => whenBootstrapReady(cb), 30);
  }

  if (typeof window.syncZoomImg !== 'function') window.syncZoomImg = function(){};

  /* ============================================================
     ✅ Main bootstrap init
  ============================================================ */
  whenBootstrapReady(() => {
    const formEl = document.getElementById('vlAddVolunteerForm');
    if (!formEl) return;

    const openConfirmBtn   = document.getElementById('vlAddOpenConfirm');
    const resetBtn         = document.getElementById('vlAddResetBtn');
    const confirmModalEl   = document.getElementById('vlAddConfirmModal');
    const confirmSubmitBtn = document.getElementById('vlAddConfirmSubmit');

    const photoInput   = document.getElementById('vlPhotoInput');
    const photoPreview = document.getElementById('vlPhotoPreview');

    const yearSel      = document.getElementById('vlYearSelect');
    const districtSel  = document.getElementById('vlDistrictSelect');

    const scheduleField   = document.getElementById('vlScheduleField');
    const scheduleBtn     = document.getElementById('vlScheduleTrigger');
    const scheduleInvalid = document.getElementById('vlScheduleInvalid');

    const yearInvalidEl = document.getElementById('vlYearInvalid');
    const photoZoomEl   = document.getElementById('vlPhotoZoomModal');

    const fbEl        = document.getElementById('vlFbField');
    const fbInvalidEl = document.getElementById('vlFbInvalid');

    const emailEl = formEl.querySelector('input[name="email"]');

    const batchEl = document.getElementById('vlBatch');

    const schModalEl = document.getElementById('classScheduleModal');

    const DEFAULT_AVATAR =
      (document.getElementById('vlRoot')?.getAttribute('data-default-avatar') || '').trim()
      || "{{ asset('storage/defaults/default_user.png') }}";

    function getOrCreateModal(el, opts){
      if (!el) return null;
      try { return bootstrap.Modal.getOrCreateInstance(el, opts || {}); }
      catch {
        try { return new bootstrap.Modal(el, opts || {}); }
        catch { return null; }
      }
    }

    const addModal     = getOrCreateModal(modalEl);
    const confirmModal = getOrCreateModal(confirmModalEl, { backdrop:'static', keyboard:false });
    getOrCreateModal(photoZoomEl);

    /* ============================================================
       ✅ Server invalid locking logic
    ============================================================ */
    function isServerInvalid(el){
      return !!(el && el.classList && el.classList.contains('is-invalid'));
    }

    function lockServerInvalidInputs(){
      if (!HAS_SERVER_ERRORS) return;
      formEl.querySelectorAll('.is-invalid').forEach(el => {
        el.dataset.vlServerInvalid = '1';
      });
    }

    function isLockedServerInvalid(el){
      return !!(el && el.dataset && el.dataset.vlServerInvalid === '1');
    }

    /* ============================================================
       ✅ Schedule guard (prevents reset during schedule modal)
    ============================================================ */
    function setScheduleGuard(on){
      window.__vlScheduleOpening = !!on;
      window.__vlScheduleSwitching = !!on;
    }

    scheduleBtn?.addEventListener('pointerdown', () => setScheduleGuard(true), true);
    scheduleBtn?.addEventListener('mousedown',   () => setScheduleGuard(true), true);
    scheduleBtn?.addEventListener('click',       () => setScheduleGuard(true), true);

    schModalEl?.addEventListener('show.bs.modal',   () => setScheduleGuard(true));
    schModalEl?.addEventListener('shown.bs.modal',  () => setScheduleGuard(true));
    schModalEl?.addEventListener('hidden.bs.modal', () => setScheduleGuard(false));

    function scheduleModalIsShowing(){
      return !!(schModalEl && schModalEl.classList.contains('show'));
    }

    /* ============================================================
       ✅ Photo zoom modal switching
    ============================================================ */
    let isPhotoZooming = false;
    photoZoomEl?.addEventListener('show.bs.modal', () => {
      isPhotoZooming = true;
      try { addModal?.hide(); } catch {}
    });
    photoZoomEl?.addEventListener('hidden.bs.modal', () => {
      try { addModal?.show(); } catch {}
      setTimeout(() => { isPhotoZooming = false; }, 0);
    });

    function show(el, on){
      if (!el) return;
      el.style.display = on ? 'block' : 'none';
    }

    function markTouched(el){
      if (!el) return;
      el.dataset.vlTouched = '1';
    }

    function setValidState(el, isOk){
      if (!el) return;

      // ✅ never override locked server-invalid field
      if (isLockedServerInvalid(el)) return;

      el.classList.remove('is-valid','is-invalid');
      el.classList.add(isOk ? 'is-valid' : 'is-invalid');
    }

    let saveAttempted = false;

    /* ============================================================
       ✅ Schedule validation
    ============================================================ */
    function refreshScheduleBtn(forceShowInvalid=false){
      if (!scheduleBtn) return true;

      const has = !!(scheduleField && (scheduleField.value || '').trim().length);

      scheduleBtn.classList.toggle('is-valid', has);

      const invalidOn = (!has && (saveAttempted || forceShowInvalid));
      scheduleBtn.classList.toggle('is-invalid', invalidOn);
      show(scheduleInvalid, invalidOn);

      return has;
    }

    /* ============================================================
       ✅ Year combo invalid handler (special)
    ============================================================ */
    function refreshYearInvalid(forceShowInvalid=false){
      if (!yearSel || !yearInvalidEl) return true;

      const has = !!String(yearSel.value || '').trim();
      const yearInput = document.getElementById('vlYearSearch');

      show(yearInvalidEl, !has && (saveAttempted || forceShowInvalid));

      if (yearInput) {
        if (!isLockedServerInvalid(yearInput)) {
          yearInput.classList.remove('is-valid','is-invalid');

          if (has) yearInput.classList.add('is-valid');
          else if (saveAttempted || forceShowInvalid) yearInput.classList.add('is-invalid');
        }
      }

      return has;
    }

    refreshScheduleBtn(HAS_SERVER_ERRORS);
    refreshYearInvalid(HAS_SERVER_ERRORS);

    scheduleField?.addEventListener('input',  () => refreshScheduleBtn(false));
    scheduleField?.addEventListener('change', () => refreshScheduleBtn(false));
    window.addEventListener('vl:schedule-updated', () => refreshScheduleBtn(false));

    yearSel?.addEventListener('change', () => refreshYearInvalid(false));

    /* ============================================================
       ✅ Photo input (optional)
    ============================================================ */
    function refreshPhotoFileState(){
      if (!photoInput) return true;
      const hasFile = !!(photoInput.files && photoInput.files.length);

      photoInput.classList.remove('is-valid','is-invalid');
      if (hasFile) photoInput.classList.add('is-valid');

      return true;
    }

    function resetPhoto(){
      if (photoPreview) photoPreview.src = DEFAULT_AVATAR;
      window.syncZoomImg();

      if (photoInput) {
        photoInput.value = '';
        photoInput.classList.remove('is-valid','is-invalid');
      }
    }

    photoInput?.addEventListener('change', () => {
      const file = photoInput.files?.[0];
      if (!file) {
        resetPhoto();
        refreshPhotoFileState();
        return;
      }

      const url = URL.createObjectURL(file);
      if (photoPreview) photoPreview.src = url;

      window.syncZoomImg();
      const zoomImg = document.getElementById('vlPhotoZoomImg');
      if (zoomImg) zoomImg.src = url;

      refreshPhotoFileState();
    });

    /* ============================================================
      ✅ Batch number validation (number > 0 only)
    ============================================================ */
    function validateBatch(forceShow=false){
      if (!batchEl) return true;

      if (isLockedServerInvalid(batchEl)) return false;

      const raw = String(batchEl.value || '').trim();

      if (!raw) {
        if (forceShow || saveAttempted) setValidState(batchEl, false);
        else batchEl.classList.remove('is-valid','is-invalid');
        return false;
      }

      if (!/^\d+$/.test(raw)) {
        setValidState(batchEl, false);
        return false;
      }

      const num = parseInt(raw, 10);
      const ok = num > 0;

      if (forceShow || saveAttempted) setValidState(batchEl, ok);
      else if (ok) setValidState(batchEl, true);

      return ok;
    }

    /* ============================================================
       ✅ Email validation
    ============================================================ */
    function validateEmail(forceShow=false){
      if (!emailEl) return true;

      // ✅ if server returned duplicate/invalid, keep invalid until user edits
      if (isLockedServerInvalid(emailEl)) return false;

      const raw = String(emailEl.value || '').trim();

      if (!raw) {
        if (forceShow || saveAttempted) setValidState(emailEl, false);
        else emailEl.classList.remove('is-valid','is-invalid');
        return false;
      }

      const ok = emailEl.checkValidity();

      if (forceShow || saveAttempted) setValidState(emailEl, ok);
      else if (ok) setValidState(emailEl, true);

      return ok;
    }

    emailEl?.addEventListener('input', () => {
      markTouched(emailEl);

      // ✅ unlock server invalid on edit
      delete emailEl.dataset.vlServerInvalid;

      emailEl.classList.remove('is-invalid','is-valid');
      emailEl.setCustomValidity('');
    });

    batchEl?.addEventListener('input', () => {
      markTouched(batchEl);
      delete batchEl.dataset.vlServerInvalid;
      batchEl.classList.remove('is-invalid','is-valid');
    });

    batchEl?.addEventListener('blur', () => {
      markTouched(batchEl);
      validateBatch(false);
    });

    emailEl?.addEventListener('blur', () => {
      markTouched(emailEl);
      validateEmail(false);
    });

    /* ============================================================
       ✅ FB validation (PATCHED: REQUIRED + no default green)
    ============================================================ */
    function normalizeLink(s){
      const raw = String(s || '').trim();
      if (!raw) return '';
      if (!/^[a-zA-Z][a-zA-Z0-9+\-.]*:\/\//.test(raw)) return 'https://' + raw;
      return raw;
    }

    function parseUrlSafe(s){
      try { return new URL(s); } catch { return null; }
    }

    function isFacebookHost(host){
      const h = String(host || '').toLowerCase().trim().replace(/^www\./, '');
      if (h === 'facebook.com' || h.endsWith('.facebook.com')) return true;
      if (h === 'fb.com' || h.endsWith('.fb.com')) return true;
      if (h === 'm.me') return true;
      if (h === 'messenger.com' || h.endsWith('.messenger.com')) return true;
      return false;
    }

    function validateFb(forceShow=false){
      if (!fbEl) return true;

      // ✅ if server returned duplicate/invalid, keep invalid until user edits
      if (isLockedServerInvalid(fbEl)) {
        if (fbInvalidEl) show(fbInvalidEl, true);
        return false;
      }

      const raw = String(fbEl.value || '').trim();

      // ✅ REQUIRED: empty is invalid (but don't force red unless save attempted / forceShow / touched)
      if (!raw) {
        fbEl.setCustomValidity('Required.');

        const shouldShow = forceShow || saveAttempted || fbEl.dataset.vlTouched === '1';

        if (shouldShow) {
          setValidState(fbEl, false);
          if (fbInvalidEl) show(fbInvalidEl, true);
        } else {
          fbEl.classList.remove('is-valid','is-invalid');
          if (fbInvalidEl) show(fbInvalidEl, false);
        }

        return false;
      }

      const normalized = normalizeLink(raw);
      const u = parseUrlSafe(normalized);
      const ok = !!(u && u.protocol && u.host && isFacebookHost(u.host));

      fbEl.setCustomValidity(ok ? '' : 'Invalid link.');

      if (ok) {
        if (normalized !== raw) fbEl.value = normalized;
        setValidState(fbEl, true);
        if (fbInvalidEl) show(fbInvalidEl, false);
        return true;
      }

      setValidState(fbEl, false);
      if (fbInvalidEl) show(fbInvalidEl, forceShow || saveAttempted);
      return false;
    }

    fbEl?.addEventListener('input', () => {
      markTouched(fbEl);

      // ✅ unlock server invalid on edit
      delete fbEl.dataset.vlServerInvalid;

      fbEl.classList.remove('is-invalid','is-valid');
      fbEl.setCustomValidity('');
      if (fbInvalidEl) show(fbInvalidEl, false);
    });

    fbEl?.addEventListener('blur', () => {
      markTouched(fbEl);
      validateFb(false);
    });

    /* ============================================================
       ✅ Combobox strict validation
    ============================================================ */
    function showComboInvalid(selectId, on){
      const map = {
        'vlCourseSelect': 'vlCourseInvalid',
        'vlBarangaySelect': 'vlBarangayInvalid',
        'vlStatusSelect': 'vlStatusInvalid',
        'vlYearSelect': 'vlYearInvalid',
        'vlDistrictSelect': 'vlDistrictInvalid',
      };
      const el = document.getElementById(map[selectId] || '');
      if (selectId === 'vlYearSelect') return refreshYearInvalid(on);
      show(el, on);
    }

    let portal = document.getElementById('vlAddDdPortal');
    let ownerCombo = null, ownerInput = null, ownerSelect = null, ownerClearBtn = null;
    let visibleItems = [];
    let activeIndex = -1;
    let lastPickAt = 0;
    const pickedRecently = () => (Date.now() - lastPickAt) < 450;

    function ensurePortal(){
      if (portal) return portal;
      portal = document.createElement('div');
      portal.id = 'vlAddDdPortal';
      portal.className = 'vlAdd-ddPortal';
      portal.innerHTML = `<div class="vlAdd-ddBody"></div>`;
      document.body.appendChild(portal);
      return portal;
    }

    function norm(s){
      return String(s ?? '').replace(/\u00A0/g,' ').replace(/\s+/g,' ').trim().toLowerCase();
    }

    function escapeHtml(s){
      return String(s ?? '').replace(/[&<>"']/g, c => ({
        "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"
      }[c]));
    }

    function forceSelectValue(selectEl, value){
      if (!selectEl) return false;
      const v = String(value ?? '').trim();
      const opts = Array.from(selectEl.options || []);
      const idx = opts.findIndex(o => String(o.value).trim() === v);

      if (idx >= 0) {
        opts.forEach(o => o.selected = false);
        opts[idx].selected = true;
        selectEl.selectedIndex = idx;
        selectEl.value = v;
        try {
          selectEl.dispatchEvent(new Event('input',  { bubbles:true }));
          selectEl.dispatchEvent(new Event('change', { bubbles:true }));
        } catch {}
        return true;
      }

      selectEl.value = '';
      selectEl.selectedIndex = 0;
      try {
        selectEl.dispatchEvent(new Event('input',  { bubbles:true }));
        selectEl.dispatchEvent(new Event('change', { bubbles:true }));
      } catch {}
      return false;
    }

    function syncInputFromSelect(selectEl, inputEl){
      const val = String(selectEl.value || '').trim();
      if (!val) return false;

      const opt = selectEl.selectedOptions?.[0] ||
        Array.from(selectEl.options).find(o => String(o.value).trim() === val);

      if (!opt) return false;

      const label = String(opt.textContent || '').trim();
      inputEl.value = label;
      inputEl.dataset.comboValue = val;
      inputEl.dataset.comboLabel = norm(label);

      setValidState(inputEl, true);
      showComboInvalid(selectEl.id, false);
      return true;
    }

    function closePortal(){
      if (!portal) return;
      portal.style.display = 'none';
      portal.querySelector('.vlAdd-ddBody').innerHTML = '';
      ownerCombo = ownerInput = ownerSelect = ownerClearBtn = null;
      visibleItems = [];
      activeIndex = -1;
    }

    function buildItemsFromSelect(selectEl){
      const items = [];
      Array.from(selectEl.options || []).forEach(opt => {
        const v = String(opt.value || '').trim();
        const label = String(opt.textContent || '').trim();
        if (!v) return;
        items.push({ value: v, label });
      });
      return items;
    }

    function setActive(idx){
      activeIndex = idx;
      const buttons = portal.querySelectorAll('.vlAdd-ddItem');
      buttons.forEach((b,i) => b.classList.toggle('is-active', i === activeIndex));
      const active = buttons[activeIndex];
      if (active) active.scrollIntoView({ block:'nearest' });
    }

    function renderPortal(list){
      const body = portal.querySelector('.vlAdd-ddBody');
      body.innerHTML = '';
      visibleItems = list.slice();
      activeIndex = list.length ? 0 : -1;

      if (!list.length){
        body.innerHTML = `<div class="vlAdd-ddEmpty">No results</div>`;
        return;
      }

      const frag = document.createDocumentFragment();
      list.forEach((it, idx) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'vlAdd-ddItem' + (idx === 0 ? ' is-active' : '');
        b.dataset.value = it.value;
        b.dataset.label = it.label;
        b.innerHTML = `<span>${escapeHtml(it.label)}</span>`;
        frag.appendChild(b);
      });
      body.appendChild(frag);
    }

    function positionPortalForInput(inputEl){
      const p = ensurePortal();
      const rect = inputEl.getBoundingClientRect();
      const gap = 8;
      const maxW = Math.min(640, window.innerWidth - 20);
      const left = Math.min(Math.max(10, rect.left), window.innerWidth - 10 - maxW);

      const preferDownTop = rect.bottom + gap;
      const maxH = 340;
      const canDown = (window.innerHeight - preferDownTop) > 160;
      const top = canDown ? preferDownTop : Math.max(10, rect.top - gap - maxH);

      const isCourse = !!(ownerSelect && ownerSelect.id === 'vlCourseSelect');
      const shrink = isCourse ? 6 : 0;
      const minW = Math.max(220, Math.floor(rect.width - shrink));

      p.style.left = left + 'px';
      p.style.top = top + 'px';
      p.style.minWidth = minW + 'px';
      p.style.maxWidth = maxW + 'px';
      p.style.display = 'block';
    }

    function applySelection(value, label){
      if (!ownerSelect || !ownerInput) return;
      lastPickAt = Date.now();

      const ok = forceSelectValue(ownerSelect, value);

      ownerInput.value = String(label || '').trim();
      ownerInput.dataset.comboValue = ok ? String(value).trim() : '';
      ownerInput.dataset.comboLabel = norm(ownerInput.value);

      if (ownerClearBtn) ownerClearBtn.style.display = ownerInput.value ? 'inline-flex' : 'none';

      // auto district from barangay
      if (ownerSelect.id === 'vlBarangaySelect' && ok){
        const opt = ownerSelect.selectedOptions?.[0];
        const d = opt?.getAttribute('data-district');
        if (d && districtSel) {
          districtSel.value = String(d);
          const districtInput = document.getElementById('vlDistrictSearch');
          if (districtInput) {
            syncInputFromSelect(districtSel, districtInput);
            setValidState(districtInput, true);
          }
        }
      }

      markTouched(ownerInput);
      setValidState(ownerInput, ok);
      closePortal();
    }

    function openForCombo(comboEl){
      const selectSel = comboEl.getAttribute('data-select');
      const selectEl = selectSel ? document.querySelector(selectSel) : null;
      const inputEl = comboEl.querySelector('.vlAdd-comboInput');
      const clearBtn = comboEl.querySelector('.vlAdd-comboClear');
      if (!selectEl || !inputEl) return;

      ownerCombo = comboEl;
      ownerSelect = selectEl;
      ownerInput = inputEl;
      ownerClearBtn = clearBtn;

      const allItems = buildItemsFromSelect(selectEl);
      const q = norm(inputEl.value);
      const filtered = !q ? allItems : allItems.filter(it => norm(it.label).includes(q));

      positionPortalForInput(inputEl);
      renderPortal(filtered);
    }

    function enforceMatch(selectEl, inputEl){
      if (pickedRecently()) return true;

      markTouched(inputEl);

      const currentVal = String(selectEl.value || '').trim();
      if (currentVal) {
        const ok = syncInputFromSelect(selectEl, inputEl);
        setValidState(inputEl, ok);
        return ok;
      }

      const typed = norm(inputEl.value);
      if (!typed) return false;

      const opts = Array.from(selectEl.options || []).filter(o => String(o.value||'').trim() !== '');
      const exact = opts.find(o => norm(o.textContent || '') === typed);

      if (exact) {
        applySelection(String(exact.value).trim(), String(exact.textContent || '').trim());
        return true;
      }

      return false;
    }

    function wireCombo(comboEl){
      const selectSel = comboEl.getAttribute('data-select');
      const selectEl = selectSel ? document.querySelector(selectSel) : null;
      const inputEl = comboEl.querySelector('.vlAdd-comboInput');
      const clearBtn = comboEl.querySelector('.vlAdd-comboClear');
      if (!selectEl || !inputEl) return;

      if (String(selectEl.value || '').trim()) {
        syncInputFromSelect(selectEl, inputEl);
        if (clearBtn) clearBtn.style.display = 'inline-flex';
      }

      function refreshClear(){
        if (!clearBtn) return;
        clearBtn.style.display = inputEl.value ? 'inline-flex' : 'none';
      }
      refreshClear();

      selectEl.addEventListener('change', () => {
        const val = String(selectEl.value || '').trim();
        if (!val) {
          inputEl.value = '';
          inputEl.dataset.comboValue = '';
          inputEl.dataset.comboLabel = '';
          if (!isLockedServerInvalid(inputEl)) inputEl.classList.remove('is-valid','is-invalid');
          refreshClear();
          return;
        }
        syncInputFromSelect(selectEl, inputEl);
        refreshClear();
      });

      inputEl.addEventListener('focus', () => { markTouched(inputEl); openForCombo(comboEl); });
      inputEl.addEventListener('click',  () => { markTouched(inputEl); openForCombo(comboEl); });

      inputEl.addEventListener('input', () => {
        refreshClear();
        openForCombo(comboEl);
        if (!isLockedServerInvalid(inputEl)) inputEl.classList.remove('is-valid','is-invalid');
      });

      clearBtn?.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); });
      clearBtn?.addEventListener('click', (e) => {
        e.preventDefault(); e.stopPropagation();
        inputEl.value = '';
        inputEl.dataset.comboValue = '';
        inputEl.dataset.comboLabel = '';
        forceSelectValue(selectEl, '');
        refreshClear();
        if (!isLockedServerInvalid(inputEl)) inputEl.classList.remove('is-valid','is-invalid');
        openForCombo(comboEl);
        inputEl.focus({ preventScroll:true });
      });

      inputEl.addEventListener('keydown', (e) => {
        if (!portal || portal.style.display !== 'block') return;

        if (e.key === 'ArrowDown') {
          e.preventDefault();
          if (!visibleItems.length) return;
          setActive(Math.min(activeIndex + 1, visibleItems.length - 1));
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          if (!visibleItems.length) return;
          setActive(Math.max(activeIndex - 1, 0));
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (!visibleItems.length) return;
          const it = visibleItems[Math.max(activeIndex, 0)];
          if (it) applySelection(it.value, it.label);
        } else if (e.key === 'Escape') {
          e.preventDefault();
          closePortal();
        }
      });

      inputEl.addEventListener('blur', () => {
        setTimeout(() => {
          if (pickedRecently()) return;
          if (portal && portal.matches(':hover')) return;
          enforceMatch(selectEl, inputEl);
          closePortal();
        }, 130);
      });
    }

    modalEl.querySelectorAll('.vlAdd-combo[data-combo]').forEach(wireCombo);

    ensurePortal().addEventListener('mousedown', (e) => {
      const item = e.target.closest('.vlAdd-ddItem');
      if (!item) return;
      e.preventDefault();
      e.stopPropagation();
      applySelection(item.dataset.value, item.dataset.label);
    });

    document.addEventListener('mousedown', (e) => {
      if (!portal || portal.style.display !== 'block') return;
      const insidePortal = portal.contains(e.target);
      const insideOwner  = ownerCombo && ownerCombo.contains(e.target);
      if (!insidePortal && !insideOwner) closePortal();
    });

    window.addEventListener('scroll', () => closePortal(), { passive:true });
    window.addEventListener('resize', () => closePortal());
    modalEl.addEventListener('hidden.bs.modal', () => closePortal());

    function validateCombosStrict(forceShow=false){
      const pairs = [
        ['vlCourseSelect','vlCourseSearch'],
        ['vlBarangaySelect','vlBarangaySearch'],
        ['vlStatusSelect','vlStatusSearch'],
        ['vlYearSelect','vlYearSearch'],
        ['vlDistrictSelect','vlDistrictSearch'],
      ];

      let ok = true;

      pairs.forEach(([sid,iid]) => {
        const s = document.getElementById(sid);
        const i = document.getElementById(iid);
        if (!s || !i) return;

        const required = s.hasAttribute('required');
        const has = !!String(s.value || '').trim();

        if (sid === 'vlYearSelect') {
          const yearOk = refreshYearInvalid(forceShow);
          if (required && !yearOk) ok = false;
          return;
        }

        if (required && !has) {
          if (!isLockedServerInvalid(i)) setValidState(i, false);
          if (forceShow) showComboInvalid(sid, true);
          ok = false;
        } else if (has) {
          const synced = syncInputFromSelect(s, i);
          if (!isLockedServerInvalid(i)) setValidState(i, synced);
          if (forceShow) showComboInvalid(sid, !synced);
          if (!synced) ok = false;
        } else {
          if (!isLockedServerInvalid(i)) i.classList.remove('is-valid','is-invalid');
          if (forceShow) showComboInvalid(sid, false);
        }
      });

      return ok;
    }

    /* ============================================================
       ✅ Native outline validation (green stays consistent)
    ============================================================ */
    const watchEls = Array.from(formEl.querySelectorAll('input, select, textarea'))
      .filter(el => el.id !== 'vlAddHiddenSubmit')
      .filter(el => el.type !== 'file');

    function updateOutline(el, force=false){
      if (!el || el.type === 'hidden') return;

      // ✅ preserve ONLY locked server invalid fields
      if (isLockedServerInvalid(el)) return;

      const touched = !!el.dataset.vlTouched;
      if (!force && !touched && !el.classList.contains('is-invalid')) return;

      // optional empty clears state
      if (!el.required && !String(el.value || '').trim().length) {
        el.classList.remove('is-valid','is-invalid');
        return;
      }

      setValidState(el, el.checkValidity());
    }

    watchEls.forEach(el => {
      if (el.classList.contains('is-invalid')) el.dataset.vlTouched = '1';
      el.addEventListener('input',  () => { markTouched(el); updateOutline(el); });
      el.addEventListener('change', () => { markTouched(el); updateOutline(el); });
      el.addEventListener('blur',   () => { markTouched(el); updateOutline(el); });
      el.addEventListener('focus',  () => { markTouched(el); });
    });

    function submitFormNow(){
      if (typeof formEl.requestSubmit === 'function') {
        try { formEl.requestSubmit(); return; } catch {}
      }

      try {
        const tmp = document.createElement('button');
        tmp.type = 'submit';
        tmp.style.position = 'absolute';
        tmp.style.left = '-9999px';
        tmp.style.width = '1px';
        tmp.style.height = '1px';
        tmp.style.opacity = '0';
        formEl.appendChild(tmp);
        tmp.click();
        tmp.remove();
        return;
      } catch {}

      try { formEl.submit(); } catch {}
    }

    function markAllForce(){
      watchEls.forEach(el => {
        if (el.type === 'hidden') return;
        markTouched(el);
        updateOutline(el, true);
      });
    }

    /* ============================================================
       ✅ Save click validation
    ============================================================ */
    openConfirmBtn?.addEventListener('click', (e) => {
      e.preventDefault();
      closePortal();

      saveAttempted = true;
      markAllForce();

      refreshPhotoFileState();

      const scheduleOk = refreshScheduleBtn(true);
      const combosOk   = validateCombosStrict(true);

      const batchOk = validateBatch(true);
      const fbOk    = validateFb(true);
      const emailOk = validateEmail(true);


      const nativeOk = formEl.checkValidity();

      if (!scheduleOk || !combosOk || !batchOk || !fbOk || !emailOk || !nativeOk) {
        formEl.classList.add('was-validated');

        const firstBad =
          formEl.querySelector('.is-invalid') ||
          formEl.querySelector(':invalid');

        firstBad?.focus?.();
        return;
      }

      if (confirmModal) confirmModal.show();
      else submitFormNow();
    });

    confirmSubmitBtn?.addEventListener('click', () => {
      saveAttempted = true;
      markAllForce();

      refreshPhotoFileState();

      const scheduleOk = refreshScheduleBtn(true);
      const combosOk   = validateCombosStrict(true);

      const batchOk = validateBatch(true);
      const fbOk    = validateFb(true);
      const emailOk = validateEmail(true);


      const nativeOk = formEl.checkValidity();

      if (!scheduleOk || !combosOk || !batchOk || !fbOk || !emailOk || !nativeOk) return;

      try { confirmModal?.hide(); } catch {}
      setTimeout(() => submitFormNow(), 0);
    });

    /* ============================================================
       ✅ Reset button
    ============================================================ */
    function resetFormState(){
      formEl.reset();
      saveAttempted = false;

      // ✅ remove bootstrap validation mode
      formEl.classList.remove('was-validated');

      // ✅ clear validity UI classes everywhere
      formEl.querySelectorAll('.is-valid, .is-invalid').forEach(el => el.classList.remove('is-valid','is-invalid'));

      // ✅ clear touched + server locks
      formEl.querySelectorAll('[data-vl-touched]').forEach(el => delete el.dataset.vlTouched);
      formEl.querySelectorAll('[data-vl-server-invalid]').forEach(el => delete el.dataset.vlServerInvalid);

      // ✅ clear all custom validity (removes browser tooltip reason)
      formEl.querySelectorAll('input, select, textarea').forEach(el => {
        try { el.setCustomValidity(''); } catch {}
      });

      // ✅ schedule reset
      if (scheduleField) scheduleField.value = '';
      if (scheduleBtn) scheduleBtn.classList.remove('is-valid','is-invalid');
      if (scheduleInvalid) show(scheduleInvalid, false);

      // ✅ fb reset
      if (fbEl) {
        fbEl.value = '';
        fbEl.setCustomValidity('');
        fbEl.classList.remove('is-valid','is-invalid');
      }
      if (fbInvalidEl) show(fbInvalidEl, false);

      // ✅ email reset
      if (emailEl) {
        emailEl.value = '';
        emailEl.setCustomValidity('');
        emailEl.classList.remove('is-valid','is-invalid');
      }

      // ✅ batch reset
      if (batchEl) {
        batchEl.value = '';
        batchEl.setCustomValidity('');
        batchEl.classList.remove('is-valid','is-invalid');
      }


      // ✅ combo invalid blocks reset (ONLY the ones we control)
      [
        'vlCourseInvalid',
        'vlBarangayInvalid',
        'vlStatusInvalid',
        'vlYearInvalid',
        'vlDistrictInvalid',
      ].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
      });

      // ✅ photo reset (optional) — keep JS as single source of truth
      resetPhoto();
      refreshPhotoFileState();

      closePortal();
    }

    resetBtn?.addEventListener('click', (e) => {
      e.preventDefault();
      closePortal();
      setScheduleGuard(false);
      resetFormState();
    });

    /* ============================================================
       ✅ Prevent reset during schedule or photo modal switching
    ============================================================ */
    modalEl.addEventListener('hidden.bs.modal', () => {
      if (isPhotoZooming) return;
      if (window.__vlScheduleOpening === true) return;
      if (window.__vlScheduleSwitching === true) return;
      if (scheduleModalIsShowing()) return;
      resetFormState();
    });

    /* ============================================================
       ✅ Restore server error states on refresh
    ============================================================ */
    if (HAS_SERVER_ERRORS && addModal) {
      try { addModal.show(); } catch {}

      lockServerInvalidInputs();

      saveAttempted = false;

      refreshScheduleBtn(true);
      refreshYearInvalid(true);

      validateBatch(true);
      validateFb(true);
      validateEmail(true);
      validateCombosStrict(true);

      refreshPhotoFileState();

      setTimeout(() => {
        watchEls.forEach(el => updateOutline(el, true));
      }, 50);
    }
  });

  /* ============================================================
   ✅ Success modal handler (ENHANCED)
  ============================================================ */
  whenBootstrapReady(() => {
    if (!SHOULD_SHOW_SUCCESS) return;

    const successEl = document.getElementById('vlAddSuccessModal');
    if (!successEl) return;

    const name = (ADDED_NAME || '').toString().trim();
    const idNo = @json($vlAddVolunteerIdNumber);
    const savedAtIso = @json($vlAddSavedAtIso);

    const txtEl   = document.getElementById('vlAddSuccessText');
    const metaEl  = document.getElementById('vlAddSuccessMeta');
    const stampEl = document.getElementById('vlAddSuccessStamp');

    // Format datetime nicely (uses browser locale)
    let whenText = '';
    try {
      const d = new Date(savedAtIso);
      whenText = d.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
      });
    } catch {
      whenText = '';
    }

    const idPart = (idNo && String(idNo).trim()) ? ` (ID: ${String(idNo).trim()})` : '';
    const main = name ? `${name}${idPart} saved successfully.` : 'Volunteer saved successfully.';

    if (txtEl) txtEl.textContent = main;

    if (metaEl) {
      metaEl.textContent = whenText ? `Saved on ${whenText}` : '';
    }

    if (stampEl) {
      stampEl.textContent = whenText ? whenText : ' ';
    }

    try { bootstrap.Modal.getOrCreateInstance(successEl).show(); }
    catch { try { new bootstrap.Modal(successEl).show(); } catch {} }
  });


})();
</script>
