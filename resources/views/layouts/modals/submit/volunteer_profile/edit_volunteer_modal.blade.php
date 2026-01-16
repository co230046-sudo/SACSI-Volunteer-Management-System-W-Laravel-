@php
  use Illuminate\Support\Facades\Route;

  $DEFAULT_AVATAR = $DEFAULT_AVATAR ?? asset('storage/defaults/default_user.png');

  $courses   = $courses   ?? collect();
  $locations = $locations ?? collect();   // ✅ now comes from controller
  $districts = $districts ?? collect();

  $vpHasErrors = session()->has('errors') && $errors->any();
  $vpUpdateAction = route('volunteers.update', $volunteer->volunteer_id);

  // ✅ Delete action: must exist in routes (recommended: Route::delete('/volunteer-profile/{id}', ...)->name('volunteers.destroy');)
  $vpDeleteAction = Route::has('volunteers.destroy')
      ? route('volunteers.destroy', $volunteer->volunteer_id)
      : url('/volunteer-profile/' . $volunteer->volunteer_id);

  $vpExistingAvatar = old('profile_picture_url') ?: ($volunteer->profile_picture_url ?? null);
  $vpAvatar = !empty($vpExistingAvatar) ? $vpExistingAvatar : $DEFAULT_AVATAR;

  $vpExistingSchedule = old('class_schedule', $volunteer->class_schedule ?? '');

  $vpVolunteerId = $volunteer->volunteer_id;

  // If you later add uniqueness route, set it here:
  $vpCheckUniqueUrl = '';

  // ✅ SUCCESS MODAL SHOULD ONLY SHOW AFTER UPDATE (NOT DELETE)
  // Controller should set ->with('vp_updated', true)->with('vp_changed', $changed)->with('vp_changed_name', $nameForLog)
  $vpShowUpdateSuccess = session('vp_updated') === true;
  $vpChanged = session('vp_changed') ?? [];
  $vpChangedName = session('vp_changed_name') ?? ($volunteer->full_name ?? 'Volunteer');
@endphp

<style>
  .vpEdit-modal .modal-dialog{ max-width: 1180px; }
  @media (max-width: 1400px){ .vpEdit-modal .modal-dialog{ max-width: 1100px; } }

  .vpEdit-header{ background:#8b1234 !important; color:#fff; border-bottom:0; }
  .vpEdit-header .modal-title{ font-weight:1000; letter-spacing:.2px; }
  .vpEdit-header .btn-close{ filter: invert(1); opacity:.9; }

  .vpEdit-body{ background:#fff; }
  .vpEdit-footer{ background:#f7f7f9; border-top: 1px solid rgba(15,23,42,.08); }

  .vpEdit-shell{ display:grid; grid-template-columns: 320px 1fr; gap: 16px; }
  @media (max-width: 992px){ .vpEdit-shell{ grid-template-columns: 1fr; } }

  .vpEdit-card{
    border:1px solid rgba(15,23,42,.10);
    border-radius:18px;
    background:#fff;
    box-shadow: 0 12px 28px rgba(2,6,23,.08);
    padding: 14px;
  }

  .vpEdit-photo{
    width: 100%;
    max-width: 220px;
    aspect-ratio: 1 / 1;
    border-radius: 18px;
    object-fit: cover;
    background:#fff;
    border: 3px solid rgba(255,255,255,.85);
    box-shadow: 0 14px 32px rgba(2,6,23,.16);
  }

  .vpEdit-photoPreviewBtn{
    border:0; background:transparent; padding:0; margin:0;
    width:100%;
    display:flex; justify-content:center; align-items:center;
    cursor:pointer;
  }

  .vpEdit-miniHint{ font-size: 12px; color:#6b7280; font-weight: 800; }
  .vpEdit-label{ font-weight: 1000; color:#8b1234; }
  .vpEdit-label .req{ color:#dc2626; }

  .vpEdit-input.form-control,
  .vpEdit-input.form-select{
    border-radius: 14px;
    border-color: rgba(15,23,42,.14);
    font-weight: 800;
    min-height: 44px;
  }
  .vpEdit-input.form-control:focus,
  .vpEdit-input.form-select:focus{
    border-color: rgba(225,29,72,.55);
    box-shadow: 0 0 0 .2rem rgba(225,29,72,.15);
  }

  .vpEdit-modal .form-control.is-valid,
  .vpEdit-modal .form-control.is-invalid,
  .vpEdit-modal .form-select.is-valid,
  .vpEdit-modal .form-select.is-invalid,
  .vpEdit-modal .vpEdit-selectStyled.is-valid,
  .vpEdit-modal .vpEdit-selectStyled.is-invalid{
    background-image: none !important;
    padding-right: .75rem !important;
  }

  .vpEdit-btnPrimary{
    background: #e11d48 !important;
    border-color: #e11d48 !important;
    font-weight: 950;
    border-radius: 999px;
    padding: 10px 16px;
    box-shadow: 0 12px 26px rgba(225,29,72,.18);
  }
  .vpEdit-btnPrimary:hover{ background:#be123c !important; border-color:#be123c !important; }
  .vpEdit-btnPrimary:disabled{ opacity: .55; cursor: not-allowed; box-shadow: none; }

  .vpEdit-btnGhost{ border-radius: 999px; font-weight: 900; }

  .vpEdit-scheduleBtn{
    border-radius: 14px;
    font-weight: 950;
    border-color: rgba(225,29,72,.45) !important;
    color:#8b1234 !important;
    min-height: 44px;
    background:#fff;
  }
  .vpEdit-scheduleBtn:hover{
    background:#e11d48 !important;
    border-color:#e11d48 !important;
    color:#fff !important;
  }
  .vpEdit-scheduleBtn.is-valid{
    background: rgba(22,163,74,.08) !important;
    border-color: #16a34a !important;
    color:#166534 !important;
  }

  .vpEdit-modal .is-valid{
    border-color: #16a34a !important;
    box-shadow: 0 0 0 .2rem rgba(22,163,74,.15) !important;
  }
  .vpEdit-modal .is-invalid{
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 .2rem rgba(220,38,38,.15) !important;
  }

  .vpEdit-nativeSelect{
    position:absolute !important;
    left:-99999px !important;
    width:1px !important;
    height:1px !important;
    opacity:0 !important;
    pointer-events:none !important;
  }

  .vpEdit-combo{ position: relative; width: 100%; }

  .vpEdit-comboInput{
    width: 100%;
    border-radius: 14px;
    border: 1px solid rgba(15,23,42,.14);
    padding: 10px 46px 10px 12px;
    font-weight: 900;
    outline: none;
    min-height: 44px;
    background:#fff;
  }
  .vpEdit-comboInput:focus{
    border-color: rgba(225,29,72,.55);
    box-shadow: 0 0 0 .2rem rgba(225,29,72,.15);
  }

  .vpEdit-comboIcons{
    position:absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    display:flex;
    align-items:center;
    gap: 10px;
  }

  .vpEdit-comboClear{
    border:0;
    background: transparent;
    font-size: 14px;
    color: rgba(107,114,128,.95);
    padding: 2px 6px;
    border-radius: 10px;
    display:none;
  }
  .vpEdit-comboClear:hover{ background: rgba(15,23,42,.06); }

  .vpEdit-ddPortal{
    position: fixed;
    z-index: 20000;
    display:none;
    background:#fff;
    border: 1px solid rgba(15,23,42,.12);
    border-radius: 16px;
    box-shadow: 0 18px 44px rgba(2,6,23,.18);
    overflow:hidden;
    box-sizing: border-box;
    min-width: 0 !important;
    width: min(360px, calc(100vw - 64px)) !important;
    max-width: min(360px, calc(100vw - 64px)) !important;
  }
  .vpEdit-ddBody{ max-height: 300px; overflow:auto; }

  .vpEdit-ddItem{
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
  .vpEdit-ddItem:hover{ background: rgba(225,29,72,.06); }
  .vpEdit-ddItem.is-active{ background: rgba(225,29,72,.10); }

  .vpEdit-ddEmpty{
    padding: 12px;
    font-weight: 900;
    color:#6b7280;
  }

  .vpEdit-fieldWrap{ position: relative; }

  .vpEdit-tooltip{
    position: absolute;
    z-index: 1080;
    max-width: 320px;
    padding: 8px 10px;
    border-radius: 10px;
    background: #dc2626;
    color: #fff;
    font-weight: 900;
    font-size: 12px;
    box-shadow: 0 12px 28px rgba(2,6,23,.18);
    transform: translateY(6px);
    opacity: 0;
    pointer-events: none;
    transition: opacity .12s ease, transform .12s ease;
  }
  .vpEdit-tooltip.show{ opacity: 1; transform: translateY(0); }
  .vpEdit-tooltip:after{
    content:"";
    position:absolute;
    top:-6px; left:14px;
    width:0;height:0;
    border-left:6px solid transparent;
    border-right:6px solid transparent;
    border-bottom:6px solid #dc2626;
  }

  /* ✅ nice list for success modal */
  .vpSuccessList{ display:flex; flex-direction:column; gap:10px; }
  .vpSuccessRow{
    display:grid;
    grid-template-columns: 130px 1fr 28px 1fr;
    gap:8px;
    align-items:center;
    border:1px solid rgba(15,23,42,.08);
    border-radius:14px;
    padding:10px;
    background:#fff;
  }
  .vpSuccessField{ font-weight:1000; color:#111827; }
  .vpSuccessOld{ font-weight:900; color:#6b7280; }
  .vpSuccessArrow{ text-align:center; font-weight:1000; }
  .vpSuccessNew{ font-weight:1000; color:#166534; }
  @media(max-width:576px){
    .vpSuccessRow{ grid-template-columns: 1fr; }
    .vpSuccessArrow{ display:none; }
  }
</style>

<div class="modal fade vpEdit-modal"
     id="editVolunteerModal"
     tabindex="-1"
     aria-hidden="true"
     data-unique-url="{{ $vpCheckUniqueUrl }}"
     data-volunteer-id="{{ $vpVolunteerId }}">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content" style="border-radius:18px; overflow:hidden;">
      <div class="modal-header vpEdit-header">
        <h5 class="modal-title mb-0">
          <i class="fa-solid fa-user-pen me-2"></i>Edit Volunteer
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="POST" action="{{ $vpUpdateAction }}" enctype="multipart/form-data" id="vpEditVolunteerForm" novalidate>
        @csrf
        @method('PUT')

        <div class="modal-body vpEdit-body">
          <div class="vpEdit-shell">

            {{-- LEFT --}}
            <div class="vpEdit-card">
              <div class="d-flex flex-column gap-3">
                <div>
                  <button type="button" class="vpEdit-photoPreviewBtn" data-bs-toggle="modal" data-bs-target="#vpPhotoZoomModal">
                    <img id="vpPhotoPreview"
                         class="vpEdit-photo"
                         src="{{ $vpAvatar }}"
                         alt="Profile Preview"
                         onerror="this.onerror=null;this.src='{{ $DEFAULT_AVATAR }}';"
                         data-default="{{ $vpAvatar }}">
                  </button>
                </div>

                <div class="vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label mb-1">Profile Photo</label>
                  <input id="vpPhotoInput" type="file" name="profile_picture"
                         class="form-control vpEdit-input @error('profile_picture') is-invalid @enderror"
                         accept="image/*" />
                  <div class="vpEdit-miniHint mt-1">
                    Optional. JPG/PNG. Leave blank to keep current photo.
                  </div>
                  @error('profile_picture')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                {{-- STATUS (combo) --}}
                <div class="vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label mb-1">Status <span class="req">*</span></label>

                  <select name="status" id="vpStatusSelect"
                          class="vpEdit-nativeSelect @error('status') is-invalid @enderror" required>
                    <option value="active"  {{ old('status', $volunteer->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive"{{ old('status', $volunteer->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                  </select>

                  <div class="vpEdit-combo" data-combo="status" data-select="#vpStatusSelect">
                    <input id="vpStatusSearch" type="text" class="vpEdit-comboInput" autocomplete="off" placeholder="Select status…">
                    <div class="vpEdit-comboIcons">
                      <button type="button" class="vpEdit-comboClear" aria-label="Clear">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>

                  <div id="vpStatusInvalid" class="invalid-feedback" style="display:none;">Please select a valid status.</div>
                  @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

              </div>
            </div>

            {{-- RIGHT --}}
            <div class="vpEdit-card">
              <div class="row g-3">

                <div class="col-md-8 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">Full Name <span class="req">*</span></label>
                  <input name="full_name"
                         id="vpFullName"
                         class="form-control vpEdit-input @error('full_name') is-invalid @enderror"
                         value="{{ old('full_name', $volunteer->full_name ?? '') }}"
                         required maxlength="255"
                         pattern="^[A-Za-zÑñ .]+$"
                         placeholder="e.g., Juan D. Dela Cruz">
                  @unless($errors->has('full_name'))
                    <div class="invalid-feedback">Name must contain letters/spaces/dots only, and must not contain "admin" or "administrator".</div>
                  @endunless
                  @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                <div class="col-md-4 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">School ID <span class="req">*</span></label>
                  <input name="id_number"
                         id="vpIdNumber"
                         class="form-control vpEdit-input @error('id_number') is-invalid @enderror"
                         value="{{ old('id_number', $volunteer->id_number ?? '') }}"
                         required inputmode="numeric" autocomplete="off"
                         pattern="^\d{6,7}$" maxlength="7"
                         placeholder="6–7 digit ID">
                  @unless($errors->has('id_number'))
                    <div class="invalid-feedback">School ID must be exactly 6–7 digits.</div>
                  @endunless
                  @error('id_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                <div class="col-md-4 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">Batch <span class="req">*</span></label>
                  <input name="batch_year"
                         id="vpBatch"
                         class="form-control vpEdit-input @error('batch_year') is-invalid @enderror"
                         value="{{ old('batch_year', $volunteer->batch_year ?? '') }}"
                         required inputmode="numeric" autocomplete="off"
                         pattern="^\d{4}$" maxlength="4"
                         placeholder="e.g., 2025">
                  @unless($errors->has('batch_year'))
                    <div class="invalid-feedback">Batch must be a 4-digit year (e.g., 2025).</div>
                  @endunless
                  @error('batch_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                {{-- COURSE (combo) --}}
                <div class="col-md-8 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">Course <span class="req">*</span></label>

                  <select name="course_id" id="vpCourseSelect"
                          class="vpEdit-nativeSelect @error('course_id') is-invalid @enderror" required>
                    <option value="">Select course</option>
                    @foreach($courses as $c)
                      <option value="{{ $c->course_id }}"
                        {{ (string)old('course_id', $volunteer->course_id ?? '') === (string)$c->course_id ? 'selected' : '' }}>
                        {{ $c->course_name }}
                      </option>
                    @endforeach
                  </select>

                  <div class="vpEdit-combo" data-combo="course" data-select="#vpCourseSelect">
                    <input id="vpCourseSearch" type="text" class="vpEdit-comboInput" autocomplete="off" placeholder="Type to search course…">
                    <div class="vpEdit-comboIcons">
                      <button type="button" class="vpEdit-comboClear" aria-label="Clear">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>

                  <div id="vpCourseInvalid" class="invalid-feedback" style="display:none;">Please select a course from the list.</div>
                  @error('course_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                {{-- YEAR LEVEL (combo) --}}
                <div class="col-md-4 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">Year Level <span class="req">*</span></label>

                  <select name="year_level" id="vpYearSelect"
                          class="vpEdit-nativeSelect @error('year_level') is-invalid @enderror" required>
                    <option value="">Select year</option>
                    <option value="1" {{ old('year_level', $volunteer->year_level ?? '') == '1' ? 'selected' : '' }}>1st Year</option>
                    <option value="2" {{ old('year_level', $volunteer->year_level ?? '') == '2' ? 'selected' : '' }}>2nd Year</option>
                    <option value="3" {{ old('year_level', $volunteer->year_level ?? '') == '3' ? 'selected' : '' }}>3rd Year</option>
                    <option value="4" {{ old('year_level', $volunteer->year_level ?? '') == '4' ? 'selected' : '' }}>4th Year</option>
                  </select>

                  <div class="vpEdit-combo" data-combo="year" data-select="#vpYearSelect">
                    <input id="vpYearSearch" type="text" class="vpEdit-comboInput" autocomplete="off" placeholder="Select year…">
                    <div class="vpEdit-comboIcons">
                      <button type="button" class="vpEdit-comboClear" aria-label="Clear">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>

                  <div id="vpYearInvalid" class="invalid-feedback" style="display:none;">Please select a year level.</div>
                  @error('year_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                <div class="col-md-6 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">Contact Number <span class="req">*</span></label>
                  <input name="contact_number"
                         id="vpContact"
                         class="form-control vpEdit-input @error('contact_number') is-invalid @enderror"
                         value="{{ old('contact_number', $volunteer->contact_number ?? '') }}"
                         required pattern="^(09\d{9}|\+639\d{9})$"
                         inputmode="numeric"
                         placeholder="09XXXXXXXXX or +639XXXXXXXXX">
                  @unless($errors->has('contact_number'))
                    <div class="invalid-feedback">Enter a valid PH mobile number.</div>
                  @endunless
                  @error('contact_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                <div class="col-md-6 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">Emergency Number <span class="req">*</span></label>
                  <input name="emergency_contact"
                         id="vpEmergency"
                         class="form-control vpEdit-input @error('emergency_contact') is-invalid @enderror"
                         value="{{ old('emergency_contact', $volunteer->emergency_contact ?? '') }}"
                         required pattern="^(09\d{9}|\+639\d{9})$"
                         inputmode="numeric"
                         placeholder="09XXXXXXXXX or +639XXXXXXXXX">
                  @unless($errors->has('emergency_contact'))
                    <div class="invalid-feedback">Enter a valid PH mobile number.</div>
                  @endunless
                  @error('emergency_contact')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                <div class="col-md-6 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">Email <span class="req">*</span></label>
                  <input name="email"
                         id="vpEmail"
                         type="email"
                         class="form-control vpEdit-input @error('email') is-invalid @enderror"
                         value="{{ old('email', $volunteer->email ?? '') }}"
                         required
                         placeholder="name@adzu.edu.ph or name@gmail.com"
                         pattern="^[A-Za-z0-9._%+\-]+@(adzu\.edu\.ph|gmail\.com)$">
                  @unless($errors->has('email'))
                    <div class="invalid-feedback">Email must end with @adzu.edu.ph or @gmail.com.</div>
                  @endunless
                  @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                {{-- FB --}}
                <div class="col-md-6 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">FB / Messenger <span class="req">*</span></label>
                  <input name="fb_messenger"
                         id="vpFbField"
                         type="text"
                         class="form-control vpEdit-input @error('fb_messenger') is-invalid @enderror"
                         value="{{ old('fb_messenger', $volunteer->fb_messenger ?? '') }}"
                         placeholder="facebook.com/... or m.me/..."
                         required>
                  @unless($errors->has('fb_messenger'))
                    <div class="invalid-feedback" id="vpFbInvalid">FB / Messenger is required and must be a valid link (URL).</div>
                  @endunless
                  @error('fb_messenger')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                {{-- BARANGAY (combo + auto district) --}}
                <div class="col-md-6 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">Barangay <span class="req">*</span></label>

                  <select name="barangay" id="vpBarangaySelect"
                          class="vpEdit-nativeSelect @error('barangay') is-invalid @enderror" required>
                    <option value="">Select barangay</option>
                    @if($locations && $locations->count())
                      @foreach($locations as $loc)
                        @php
                          $b = (string)($loc->barangay ?? '');
                          $d = (string)($loc->district_id ?? '');
                        @endphp
                        @if($b !== '')
                          <option value="{{ $b }}"
                                  data-district="{{ $d }}"
                                  {{ (string)old('barangay', $volunteer->barangay ?? '') === (string)$b ? 'selected' : '' }}>
                            {{ $b }}
                          </option>
                        @endif
                      @endforeach
                    @endif
                  </select>

                  <div class="vpEdit-combo" data-combo="barangay" data-select="#vpBarangaySelect">
                    <input id="vpBarangaySearch" type="text" class="vpEdit-comboInput" autocomplete="off" placeholder="Type to search barangay…">
                    <div class="vpEdit-comboIcons">
                      <button type="button" class="vpEdit-comboClear" aria-label="Clear">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>

                  <div id="vpBarangayInvalid" class="invalid-feedback" style="display:none;">Please select a barangay from the list.</div>
                  @error('barangay')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                {{-- DISTRICT (combo) --}}
                <div class="col-md-3 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">District <span class="req">*</span></label>

                  <select name="district" id="vpDistrictSelect"
                          class="vpEdit-nativeSelect @error('district') is-invalid @enderror" required>
                    <option value="">Select district</option>
                    @foreach($districts as $d)
                      @php
                        $val = (string)$d;
                        $lbl = "District " . (string)$d;
                      @endphp
                      @if($val !== '')
                        <option value="{{ $val }}" {{ (string)old('district', $volunteer->district ?? '') === (string)$val ? 'selected' : '' }}>
                          {{ $lbl }}
                        </option>
                      @endif
                    @endforeach
                  </select>

                  <div class="vpEdit-combo" data-combo="district" data-select="#vpDistrictSelect">
                    <input id="vpDistrictSearch" type="text" class="vpEdit-comboInput" autocomplete="off" placeholder="Select district…">
                    <div class="vpEdit-comboIcons">
                      <button type="button" class="vpEdit-comboClear" aria-label="Clear">
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                    </div>
                  </div>

                  <div id="vpDistrictInvalid" class="invalid-feedback" style="display:none;">Please select a district.</div>
                  @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="vpEdit-tooltip"></div>
                </div>

                <div class="col-md-3 vpEdit-fieldWrap">
                  <label class="form-label vpEdit-label">Class Schedule</label>

                  <button type="button"
                          class="btn btn-outline-danger w-100 vpEdit-scheduleBtn"
                          id="vpScheduleTrigger"
                          data-bs-toggle="modal"
                          data-bs-target="#vpClassScheduleModal">
                    <i class="fa-solid fa-calendar-days me-1"></i>
                    <span id="vpScheduleBtnText">Edit Schedule</span>
                  </button>

                  <div class="vpEdit-tooltip"></div>
                </div>

                <input type="hidden" name="class_schedule" id="vpScheduleField" value="{{ $vpExistingSchedule }}">
                @error('class_schedule')
                  <div class="col-12"><div class="text-danger small mt-1">{{ $message }}</div></div>
                @enderror

              </div>
            </div>

          </div>
        </div>

        <div class="modal-footer vpEdit-footer" style="display:flex; gap:10px; justify-content:space-between; flex-wrap:wrap;">
          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button"
                    class="btn btn-outline-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#vpDeleteVolunteerModal"
                    style="border-radius:999px; font-weight:950;">
              <i class="fa-solid fa-trash me-1"></i> Delete Volunteer
            </button>
          </div>

          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button type="button" class="btn btn-light vpEdit-btnGhost" data-bs-dismiss="modal" id="vpEditCancelBtn">
              Cancel
            </button>

            <button type="submit" class="btn btn-danger vpEdit-btnPrimary" id="vpEditSaveBtn" disabled>
              <i class="fa-solid fa-floppy-disk me-1"></i> Save Changes
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- schedule modal include --}}
@include('layouts.modals.submit.volunteer_profile.class_schedule_modal')

<!-- Photo Zoom Modal -->
<div class="modal fade" id="vpPhotoZoomModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:18px; overflow:hidden;">
      <div class="modal-header" style="border:0; padding:14px 16px;">
        <h5 class="modal-title" style="margin:0; font-weight:1000;">Profile Photo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" style="padding:0; background:#0b0b0b;">
        <img id="vpPhotoZoomImg" src="{{ $vpAvatar }}" alt="Profile photo"
             style="display:block; width:100%; height:auto; max-height:78vh; object-fit:contain; margin:0 auto;" />
      </div>
    </div>
  </div>
</div>

{{-- ✅ Delete Confirm Modal --}}
<div class="modal fade" id="vpDeleteVolunteerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-md">
    <div class="modal-content" style="border-radius:18px; overflow:hidden;">
      <div class="modal-header" style="background:#b91c1c; color:#fff; border:0;">
        <h5 class="modal-title" style="margin:0; font-weight:1000;">
          <i class="fa-solid fa-triangle-exclamation me-2"></i>Confirm Delete
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1);"></button>
      </div>
      <div class="modal-body" style="padding:16px;">
        <div style="font-weight:950; color:#111827;">
          You are about to delete:
        </div>
        <div style="margin-top:6px; font-weight:1000; color:#b91c1c;">
          {{ $volunteer->full_name ?? 'Volunteer' }}
        </div>
        <div style="margin-top:10px; font-weight:850; color:#6b7280;">
          This action cannot be undone.
        </div>
      </div>
      <div class="modal-footer" style="background:#f7f7f9; border-top:1px solid rgba(15,23,42,.08); gap:10px;">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="border-radius:999px; font-weight:900;">
          Cancel
        </button>

        <form method="POST" action="{{ $vpDeleteAction }}" style="margin:0;">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger" style="border-radius:999px; font-weight:950;">
            <i class="fa-solid fa-trash me-1"></i> Yes, Delete
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- ✅ Success Modal (shows changed fields; ONLY after update) --}}
<div class="modal fade" id="vpUpdateSuccessModal" tabindex="-1" aria-hidden="true"
     data-vp-changed='@json($vpChanged)'
     data-vp-name="{{ e($vpChangedName) }}">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content" style="border-radius:18px; overflow:hidden;">
      <div class="modal-header" style="background:#16a34a; color:#fff; border:0;">
        <h5 class="modal-title" style="margin:0; font-weight:1000;">
          <i class="fa-solid fa-circle-check me-2"></i>Update Successful
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter:invert(1);"></button>
      </div>
      <div class="modal-body" style="padding:16px;">
        <div style="font-weight:950; color:#111827;">
          Updated fields for <span style="color:#166534; font-weight:1000;" id="vpSuccessName"></span>
        </div>

        <div id="vpSuccessNoChanges" style="display:none; margin-top:10px; font-weight:900; color:#6b7280;">
          No changes detected.
        </div>

        <div id="vpSuccessList" class="vpSuccessList" style="margin-top:12px;"></div>
      </div>
      <div class="modal-footer" style="background:#f7f7f9; border-top:1px solid rgba(15,23,42,.08);">
        <button type="button" class="btn btn-success" data-bs-dismiss="modal" style="border-radius:999px; font-weight:900;">
          OK
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const modalEl = document.getElementById('editVolunteerModal');
  const formEl  = document.getElementById('vpEditVolunteerForm');
  if (!modalEl || !formEl) return;

  const HAS_SERVER_ERRORS = {{ $vpHasErrors ? 'true' : 'false' }};
  const UNIQUE_URL = String(modalEl.getAttribute('data-unique-url') || '').trim();
  const VOL_ID     = String(modalEl.getAttribute('data-volunteer-id') || '').trim();

  const saveBtn    = document.getElementById('vpEditSaveBtn');
  const cancelBtn  = document.getElementById('vpEditCancelBtn');

  function show(el, on){ if (el) el.style.display = on ? 'block' : 'none'; }
  function normSpace(s){ return String(s ?? '').replace(/\u00A0/g,' ').replace(/\s+/g,' ').trim(); }
  function norm(s){ return String(s ?? '').replace(/\u00A0/g,' ').replace(/\s+/g,' ').trim().toLowerCase(); }
  function escapeHtml(s){
    return String(s ?? '').replace(/[&<>"']/g, c => ({
      "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"
    }[c]));
  }

  function isLockedServerInvalid(el){
    return !!(el && el.dataset && el.dataset.vpServerInvalid === '1');
  }
  function lockServerInvalidInputs(){
    if (!HAS_SERVER_ERRORS) return;
    formEl.querySelectorAll('.is-invalid').forEach(el => el.dataset.vpServerInvalid = '1');
  }

  function getWrap(el){ return el?.closest?.('.vpEdit-fieldWrap') || null; }
  function getTooltip(el){ return getWrap(el)?.querySelector?.('.vpEdit-tooltip') || null; }

  function setTooltip(el, msg){
    const tip = getTooltip(el);
    if (!tip) return;
    tip.textContent = msg || '';
    if (!msg) { tip.classList.remove('show'); return; }

    const wrap = getWrap(el);
    if (!wrap) return;

    const rect = el.getBoundingClientRect();
    const wrapRect = wrap.getBoundingClientRect();
    tip.style.top  = (rect.bottom - wrapRect.top + 6) + 'px';
    tip.style.left = Math.max(0, (rect.left - wrapRect.left)) + 'px';
    tip.classList.add('show');
  }
  function clearTooltip(el){
    const tip = getTooltip(el);
    if (tip) tip.classList.remove('show');
  }

  function setValid(el){
    if (!el) return;
    if (isLockedServerInvalid(el)) return;
    el.classList.remove('is-invalid');
    el.classList.add('is-valid');
    clearTooltip(el);
  }
  function setInvalid(el, msg){
    if (!el) return;
    if (isLockedServerInvalid(el)) return;
    el.classList.remove('is-valid');
    el.classList.add('is-invalid');
    setTooltip(el, msg || 'Invalid value.');
  }

  function unlockOnEdit(el){
    if (!el) return;
    el.addEventListener('input', ()=>{
      delete el.dataset.vpServerInvalid;
      el.setCustomValidity?.('');
      clearTooltip(el);
      setValid(el);
      refreshSaveDisabled();
    });
  }

  function humanMessage(el){
    const wrap = getWrap(el);
    const fb = wrap ? wrap.querySelector('.invalid-feedback') : null;
    const txt = fb ? String(fb.textContent || '').trim() : '';
    if (txt) return txt;
    if (el.validity?.valueMissing) return 'This field is required.';
    if (el.validity?.patternMismatch) return 'Invalid format.';
    if (el.validity?.typeMismatch) return 'Please enter a valid value.';
    return 'Invalid value.';
  }

  const fullNameEl = document.getElementById('vpFullName');
  const idEl       = document.getElementById('vpIdNumber');
  const batchEl    = document.getElementById('vpBatch');
  const contactEl  = document.getElementById('vpContact');
  const emergEl    = document.getElementById('vpEmergency');
  const emailEl    = document.getElementById('vpEmail');
  const fbEl       = document.getElementById('vpFbField');

  const scheduleField   = document.getElementById('vpScheduleField');
  const scheduleBtn     = document.getElementById('vpScheduleTrigger');
  const scheduleBtnText = document.getElementById('vpScheduleBtnText');

  const districtSel = document.getElementById('vpDistrictSelect');

  function normalizeScheduleVal(v){
    const s = String(v || '').trim();
    if (!s) return '';
    if (/^no class schedule$/i.test(s)) return '';
    return s;
  }
  function refreshScheduleBtn(){
    if (!scheduleBtn) return;
    const has = !!normalizeScheduleVal(scheduleField?.value);
    scheduleBtn.classList.toggle('is-valid', has);
    if (scheduleBtnText) scheduleBtnText.textContent = has ? 'Edit Schedule' : 'Add Schedule';
  }

  function validateFullNameLive(){
    if (!fullNameEl) return true;
    if (isLockedServerInvalid(fullNameEl)) return false;

    const raw = normSpace(fullNameEl.value);
    if (!raw){ setValid(fullNameEl); return true; }

    const lowered = raw.toLowerCase();
    if (lowered.includes('admin') || lowered.includes('administrator')){
      fullNameEl.setCustomValidity('Invalid.');
      setInvalid(fullNameEl, 'Name must not contain "admin" or "administrator".');
      return false;
    }

    const ok = fullNameEl.checkValidity();
    fullNameEl.setCustomValidity(ok ? '' : 'Invalid.');
    if (ok){ setValid(fullNameEl); return true; }

    setInvalid(fullNameEl, 'Name must contain letters/spaces/dots only (no numbers).');
    return false;
  }

  function validateNativeLive(el){
    if (!el) return true;
    if (isLockedServerInvalid(el)) return false;

    const v = normSpace(el.value);
    if (!v){ setValid(el); return true; }

    const ok = el.checkValidity();
    if (ok) setValid(el);
    else setInvalid(el, humanMessage(el));
    return ok;
  }

  function normalizeLink(s){
    const raw = String(s || '').trim();
    if (!raw) return '';
    if (!/^[a-zA-Z][a-zA-Z0-9+\-.]*:\/\//.test(raw)) return 'https://' + raw;
    return raw;
  }
  function parseUrlSafe(s){ try { return new URL(s); } catch { return null; } }
  function isFacebookHost(host){
    const h = String(host || '').toLowerCase().trim().replace(/^www\./, '');
    return (
      h === 'facebook.com' || h.endsWith('.facebook.com') ||
      h === 'fb.com' || h.endsWith('.fb.com') ||
      h === 'm.me' ||
      h === 'messenger.com' || h.endsWith('.messenger.com')
    );
  }
  function validateFbLive(){
    if (!fbEl) return true;
    if (isLockedServerInvalid(fbEl)) return false;

    const raw = String(fbEl.value || '').trim();
    if (!raw){ setValid(fbEl); return true; }

    const normalized = normalizeLink(raw);
    const u = parseUrlSafe(normalized);
    const ok = !!(u && u.protocol && u.host && isFacebookHost(u.host));

    fbEl.setCustomValidity(ok ? '' : 'Invalid.');
    if (ok){
      if (normalized !== raw) fbEl.value = normalized;
      setValid(fbEl);
      return true;
    }
    setInvalid(fbEl, 'FB / Messenger must be a valid Facebook/Messenger link.');
    return false;
  }

  function validateContactEmergencyPair(){
    if (!contactEl || !emergEl) return true;
    if (isLockedServerInvalid(contactEl) || isLockedServerInvalid(emergEl)) return false;

    const c = normSpace(contactEl.value);
    const e = normSpace(emergEl.value);

    if (!c || !e){
      if (!c) setValid(contactEl);
      if (!e) setValid(emergEl);
      return true;
    }

    const okC = contactEl.checkValidity();
    const okE = emergEl.checkValidity();

    if (!okC) setInvalid(contactEl, humanMessage(contactEl)); else setValid(contactEl);
    if (!okE) setInvalid(emergEl, humanMessage(emergEl)); else setValid(emergEl);

    if (!okC || !okE) return false;

    if (c === e){
      setInvalid(contactEl, 'Contact number must be different from emergency number.');
      setInvalid(emergEl, 'Emergency number must be different from contact number.');
      return false;
    }

    setValid(contactEl);
    setValid(emergEl);
    return true;
  }

  // ---------- Combobox engine ----------
  function showComboInvalid(selectId, on){
    const map = {
      'vpCourseSelect': 'vpCourseInvalid',
      'vpBarangaySelect': 'vpBarangayInvalid',
      'vpStatusSelect': 'vpStatusInvalid',
      'vpYearSelect': 'vpYearInvalid',
      'vpDistrictSelect': 'vpDistrictInvalid',
    };
    show(document.getElementById(map[selectId] || ''), on);
  }

  let portal = document.getElementById('vpEditDdPortal');
  let ownerCombo=null, ownerInput=null, ownerSelect=null, ownerClearBtn=null;
  let visibleItems=[], activeIndex=-1, lastPickAt=0;
  const pickedRecently = () => (Date.now() - lastPickAt) < 450;

  function ensurePortal(){
    if (portal) return portal;
    portal = document.createElement('div');
    portal.id = 'vpEditDdPortal';
    portal.className = 'vpEdit-ddPortal';
    portal.innerHTML = `<div class="vpEdit-ddBody"></div>`;
    document.body.appendChild(portal);
    return portal;
  }
  function closePortal(){
    if (!portal) return;
    portal.style.display = 'none';
    portal.querySelector('.vpEdit-ddBody').innerHTML = '';
    ownerCombo=ownerInput=ownerSelect=ownerClearBtn=null;
    visibleItems=[]; activeIndex=-1;
  }
  function buildItemsFromSelect(selectEl){
    const items=[];
    Array.from(selectEl.options || []).forEach(opt=>{
      const v=String(opt.value||'').trim();
      const label=String(opt.textContent||'').trim();
      if (!v) return;
      items.push({value:v,label});
    });
    return items;
  }
  function setActive(idx){
    activeIndex = idx;
    portal.querySelectorAll('.vpEdit-ddItem').forEach((b,i)=>b.classList.toggle('is-active', i===activeIndex));
    const active = portal.querySelectorAll('.vpEdit-ddItem')[activeIndex];
    if (active) active.scrollIntoView({block:'nearest'});
  }
  function renderPortal(list){
    const body = portal.querySelector('.vpEdit-ddBody');
    body.innerHTML='';
    visibleItems=list.slice();
    activeIndex=list.length ? 0 : -1;

    if (!list.length){
      body.innerHTML = `<div class="vpEdit-ddEmpty">No results</div>`;
      return;
    }

    const frag=document.createDocumentFragment();
    list.forEach((it, idx)=>{
      const b=document.createElement('button');
      b.type='button';
      b.className='vpEdit-ddItem' + (idx===0?' is-active':'');
      b.dataset.value=it.value;
      b.dataset.label=it.label;
      b.innerHTML=`<span>${escapeHtml(it.label)}</span>`;
      frag.appendChild(b);
    });
    body.appendChild(frag);
  }
  function positionPortalForInput(inputEl){
    const p = ensurePortal();
    const rect = inputEl.getBoundingClientRect();
    const gap = 8;

    const maxW = Math.min(360, window.innerWidth - 64);
    const left = Math.min(Math.max(10, rect.left), window.innerWidth - 10 - maxW);

    const preferDownTop = rect.bottom + gap;
    const maxH = 340;
    const canDown = (window.innerHeight - preferDownTop) > 160;
    const top = canDown ? preferDownTop : Math.max(10, rect.top - gap - maxH);

    const minW = Math.max(220, Math.floor(rect.width));
    p.style.left = left + 'px';
    p.style.top = top + 'px';
    p.style.minWidth = minW + 'px';
    p.style.maxWidth = maxW + 'px';
    p.style.display='block';
  }

  function forceSelectValue(selectEl, value){
    if (!selectEl) return false;
    const v=String(value??'').trim();
    const opts=Array.from(selectEl.options||[]);
    const idx=opts.findIndex(o=>String(o.value).trim()===v);
    if (idx>=0){
      opts.forEach(o=>o.selected=false);
      opts[idx].selected=true;
      selectEl.selectedIndex=idx;
      selectEl.value=v;
      try{
        selectEl.dispatchEvent(new Event('input',{bubbles:true}));
        selectEl.dispatchEvent(new Event('change',{bubbles:true}));
      }catch{}
      return true;
    }
    selectEl.value='';
    selectEl.selectedIndex=0;
    try{
      selectEl.dispatchEvent(new Event('input',{bubbles:true}));
      selectEl.dispatchEvent(new Event('change',{bubbles:true}));
    }catch{}
    return false;
  }

  function syncInputFromSelect(selectEl, inputEl){
    const val=String(selectEl.value||'').trim();
    if (!val) return false;
    const opt = selectEl.selectedOptions?.[0] || Array.from(selectEl.options).find(o=>String(o.value).trim()===val);
    if (!opt) return false;
    const label=String(opt.textContent||'').trim();
    inputEl.value=label;
    inputEl.dataset.comboValue=val;
    inputEl.dataset.comboLabel=norm(label);
    setValid(inputEl);
    showComboInvalid(selectEl.id,false);
    return true;
  }

  function applySelection(value,label){
    if (!ownerSelect || !ownerInput) return;
    lastPickAt = Date.now();

    const ok = forceSelectValue(ownerSelect, value);

    ownerInput.value = String(label||'').trim();
    ownerInput.dataset.comboValue = ok ? String(value).trim() : '';
    ownerInput.dataset.comboLabel = norm(ownerInput.value);

    if (ownerClearBtn) ownerClearBtn.style.display = ownerInput.value ? 'inline-flex' : 'none';

    // ✅ barangay -> district auto-set uses forceSelectValue (fires change)
    if (ownerSelect.id === 'vpBarangaySelect' && ok){
      const opt = ownerSelect.selectedOptions?.[0];
      const d = opt?.getAttribute('data-district');
      if (d && districtSel){
        forceSelectValue(districtSel, String(d));
        const districtInput = document.getElementById('vpDistrictSearch');
        if (districtInput) syncInputFromSelect(districtSel, districtInput);
      }
    }

    if (ok) setValid(ownerInput);
    else setInvalid(ownerInput, 'Please select from the list.');

    refreshSaveDisabled();
    closePortal();
  }

  function openForCombo(comboEl){
    const sel = comboEl.getAttribute('data-select');
    const selectEl = sel ? document.querySelector(sel) : null;
    const inputEl = comboEl.querySelector('.vpEdit-comboInput');
    const clearBtn = comboEl.querySelector('.vpEdit-comboClear');
    if (!selectEl || !inputEl) return;

    ownerCombo=comboEl;
    ownerSelect=selectEl;
    ownerInput=inputEl;
    ownerClearBtn=clearBtn;

    const items=buildItemsFromSelect(selectEl);
    const q=norm(inputEl.value);
    const filtered=!q ? items : items.filter(it=>norm(it.label).includes(q));

    positionPortalForInput(inputEl);
    renderPortal(filtered);
  }

  function enforceMatch(selectEl, inputEl){
    if (pickedRecently()) return true;

    const currentVal = String(selectEl.value||'').trim();
    if (currentVal){
      const ok = syncInputFromSelect(selectEl, inputEl);
      if (!ok) setInvalid(inputEl, 'Please select from the list.');
      return ok;
    }

    const typed = norm(inputEl.value);
    if (!typed){ setValid(inputEl); showComboInvalid(selectEl.id,false); return true; }

    const opts = Array.from(selectEl.options||[]).filter(o=>String(o.value||'').trim()!=='');
    const exact = opts.find(o=>norm(o.textContent||'')===typed);
    if (exact){
      applySelection(String(exact.value).trim(), String(exact.textContent||'').trim());
      return true;
    }

    setInvalid(inputEl, 'Please select from the list.');
    showComboInvalid(selectEl.id,true);
    return false;
  }

  function wireCombo(comboEl){
    const sel = comboEl.getAttribute('data-select');
    const selectEl = sel ? document.querySelector(sel) : null;
    const inputEl = comboEl.querySelector('.vpEdit-comboInput');
    const clearBtn = comboEl.querySelector('.vpEdit-comboClear');
    if (!selectEl || !inputEl) return;

    if (String(selectEl.value||'').trim()){
      syncInputFromSelect(selectEl, inputEl);
      if (clearBtn) clearBtn.style.display='inline-flex';
    } else {
      setValid(inputEl);
    }

    function refreshClear(){
      if (!clearBtn) return;
      clearBtn.style.display = inputEl.value ? 'inline-flex' : 'none';
    }
    refreshClear();

    selectEl.addEventListener('change', ()=>{
      const val=String(selectEl.value||'').trim();
      if (!val){
        inputEl.value='';
        inputEl.dataset.comboValue='';
        inputEl.dataset.comboLabel='';
        setValid(inputEl);
        clearTooltip(inputEl);
        refreshClear();
        refreshSaveDisabled();
        return;
      }
      syncInputFromSelect(selectEl, inputEl);
      refreshClear();
      refreshSaveDisabled();
    });

    inputEl.addEventListener('focus', ()=> openForCombo(comboEl));
    inputEl.addEventListener('click',  ()=> openForCombo(comboEl));

    inputEl.addEventListener('input', ()=>{
      setValid(inputEl);
      clearTooltip(inputEl);
      showComboInvalid(selectEl.id,false);
      refreshClear();
      openForCombo(comboEl);
      refreshSaveDisabled();
    });

    clearBtn?.addEventListener('mousedown', e=>{ e.preventDefault(); e.stopPropagation(); });
    clearBtn?.addEventListener('click', e=>{
      e.preventDefault(); e.stopPropagation();
      inputEl.value='';
      inputEl.dataset.comboValue='';
      inputEl.dataset.comboLabel='';
      forceSelectValue(selectEl,'');
      refreshClear();
      setValid(inputEl);
      clearTooltip(inputEl);
      showComboInvalid(selectEl.id,false);
      openForCombo(comboEl);
      inputEl.focus({preventScroll:true});
      refreshSaveDisabled();
    });

    inputEl.addEventListener('keydown', (e)=>{
      if (!portal || portal.style.display!=='block') return;
      if (e.key==='ArrowDown'){ e.preventDefault(); if (!visibleItems.length) return; setActive(Math.min(activeIndex+1, visibleItems.length-1)); }
      else if (e.key==='ArrowUp'){ e.preventDefault(); if (!visibleItems.length) return; setActive(Math.max(activeIndex-1, 0)); }
      else if (e.key==='Enter'){ e.preventDefault(); if (!visibleItems.length) return; const it=visibleItems[Math.max(activeIndex,0)]; if (it) applySelection(it.value,it.label); }
      else if (e.key==='Escape'){ e.preventDefault(); closePortal(); }
    });

    inputEl.addEventListener('blur', ()=>{
      setTimeout(()=>{
        if (pickedRecently()) return;
        if (portal && portal.matches(':hover')) return;
        enforceMatch(selectEl, inputEl);
        closePortal();
        refreshSaveDisabled();
      }, 130);
    });
  }

  modalEl.querySelectorAll('.vpEdit-combo[data-combo]').forEach(wireCombo);

  ensurePortal().addEventListener('mousedown', (e)=>{
    const item = e.target.closest('.vpEdit-ddItem');
    if (!item) return;
    e.preventDefault(); e.stopPropagation();
    applySelection(item.dataset.value, item.dataset.label);
  });

  document.addEventListener('mousedown', (e)=>{
    if (!portal || portal.style.display!=='block') return;
    const insidePortal = portal.contains(e.target);
    const insideOwner  = ownerCombo && ownerCombo.contains(e.target);
    if (!insidePortal && !insideOwner) closePortal();
  });
  window.addEventListener('scroll', ()=>closePortal(), {passive:true});
  window.addEventListener('resize', ()=>closePortal());
  modalEl.addEventListener('hidden.bs.modal', ()=>closePortal());

  // Photo preview
  const photoInput   = document.getElementById('vpPhotoInput');
  const photoPreview = document.getElementById('vpPhotoPreview');
  const zoomImg      = document.getElementById('vpPhotoZoomImg');
  const DEFAULT_AVATAR = (photoPreview?.dataset?.default || '').trim() || "{{ $DEFAULT_AVATAR }}";
  function syncZoom(){ if (zoomImg && photoPreview) zoomImg.src = photoPreview.src; }
  photoInput?.addEventListener('change', () => {
    const f = photoInput.files?.[0];
    if (!f) { if (photoPreview) photoPreview.src = DEFAULT_AVATAR; syncZoom(); return; }
    const url = URL.createObjectURL(f);
    if (photoPreview) photoPreview.src = url;
    syncZoom();
  });

  function forceGreenAllOnOpen(){
    const els = Array.from(formEl.querySelectorAll('input, textarea, select'))
      .filter(el => el.type !== 'hidden' && el.type !== 'file');

    els.forEach(el=>{
      if (isLockedServerInvalid(el)) return;
      el.classList.remove('is-invalid');
      el.classList.add('is-valid');
      clearTooltip(el);
    });

    ['vpCourseSearch','vpBarangaySearch','vpStatusSearch','vpYearSearch','vpDistrictSearch'].forEach(id=>{
      const i = document.getElementById(id);
      if (!i || isLockedServerInvalid(i)) return;
      i.classList.remove('is-invalid');
      i.classList.add('is-valid');
      clearTooltip(i);
    });

    refreshScheduleBtn();
  }

  // initial snapshot for cancel reset + "no changes, no save"
  const tracked = [
    ['Full Name', fullNameEl],
    ['School ID', idEl],
    ['Batch', batchEl],
    ['Course', document.getElementById('vpCourseSearch')],
    ['Year Level', document.getElementById('vpYearSearch')],
    ['Contact Number', contactEl],
    ['Emergency Number', emergEl],
    ['Email', emailEl],
    ['FB / Messenger', fbEl],
    ['Barangay', document.getElementById('vpBarangaySearch')],
    ['District', document.getElementById('vpDistrictSearch')],
    ['Status', document.getElementById('vpStatusSearch')],
  ];

  const initial = {};
  function snapshotInitial(){
    tracked.forEach(([label, el])=>{
      if (!el) return;
      initial[label] = normSpace(el.value);
    });
    initial['Class Schedule'] = normSpace(scheduleField?.value || '');
  }

  function hasChangesNow(){
    for (const [label, el] of tracked){
      if (!el) continue;
      if ((initial[label] ?? '') !== normSpace(el.value)) return true;
    }
    const nowSched = normSpace(scheduleField?.value || '');
    if ((initial['Class Schedule'] ?? '') !== nowSched) return true;
    // photo file counts as change
    if (photoInput && photoInput.files && photoInput.files.length) return true;
    return false;
  }

  function resetToInitial(){
    tracked.forEach(([label, el])=>{
      if (!el) return;
      el.value = initial[label] ?? '';
      try { el.dispatchEvent(new Event('input', {bubbles:true})); } catch {}
      try { el.dispatchEvent(new Event('change', {bubbles:true})); } catch {}
    });

    if (scheduleField){
      scheduleField.value = initial['Class Schedule'] ?? '';
      try { scheduleField.dispatchEvent(new Event('input', {bubbles:true})); } catch {}
      try { scheduleField.dispatchEvent(new Event('change', {bubbles:true})); } catch {}
    }

    if (photoInput) photoInput.value = '';
    if (photoPreview){
      photoPreview.src = "{{ $vpAvatar }}";
      syncZoom();
    }

    const pairs = [
      ['vpCourseSelect','vpCourseSearch'],
      ['vpYearSelect','vpYearSearch'],
      ['vpStatusSelect','vpStatusSearch'],
      ['vpBarangaySelect','vpBarangaySearch'],
      ['vpDistrictSelect','vpDistrictSearch'],
    ];
    pairs.forEach(([sid,iid])=>{
      const s = document.getElementById(sid);
      const i = document.getElementById(iid);
      if (!s || !i) return;
      syncInputFromSelect(s,i);
    });

    forceGreenAllOnOpen();
    validateContactEmergencyPair();
    validateFullNameLive();
    validateNativeLive(idEl);
    validateNativeLive(batchEl);
    validateNativeLive(emailEl);
    validateFbLive();
    refreshSaveDisabled();
  }

  cancelBtn?.addEventListener('click', ()=> resetToInitial());
  modalEl.addEventListener('hide.bs.modal', ()=> resetToInitial());

  function anyInvalidNow(){
    if (formEl.querySelector('.is-invalid')) return true;

    const requiredSelectIds = ['vpCourseSelect','vpYearSelect','vpStatusSelect','vpBarangaySelect','vpDistrictSelect'];
    for (const sid of requiredSelectIds){
      const s = document.getElementById(sid);
      if (s && s.hasAttribute('required') && !String(s.value||'').trim()) return true;
    }

    const c = normSpace(contactEl?.value);
    const e = normSpace(emergEl?.value);
    if (c && e && c === e) return true;

    const mustBeValid = [fullNameEl, idEl, batchEl, contactEl, emergEl, emailEl, fbEl].filter(Boolean);
    for (const el of mustBeValid){
      const v = normSpace(el.value);
      if (v && !el.checkValidity()) return true;
      if (String(el.validationMessage||'').includes('Duplicate')) return true;
    }
    return false;
  }

  function refreshSaveDisabled(){
    if (!saveBtn) return;

    // required empties block save
    const requiredInputs = Array.from(formEl.querySelectorAll('input[required], select[required], textarea[required]'))
      .filter(el => el.type !== 'hidden' && el.type !== 'file');

    let missingRequired = false;
    requiredInputs.forEach(el=>{
      if (el.tagName === 'SELECT'){
        if (!String(el.value||'').trim()) missingRequired = true;
      } else {
        if (!normSpace(el.value)) missingRequired = true;
      }
    });

    // ✅ also block save if NO changes
    const noChanges = !hasChangesNow();

    saveBtn.disabled = !!(missingRequired || anyInvalidNow() || noChanges);
  }

  unlockOnEdit(fullNameEl); unlockOnEdit(idEl); unlockOnEdit(batchEl);
  unlockOnEdit(contactEl); unlockOnEdit(emergEl);
  unlockOnEdit(emailEl); unlockOnEdit(fbEl);

  fullNameEl?.addEventListener('input', ()=>{ validateFullNameLive(); refreshSaveDisabled(); });
  idEl?.addEventListener('input', ()=>{ validateNativeLive(idEl); refreshSaveDisabled(); });
  batchEl?.addEventListener('input', ()=>{ validateNativeLive(batchEl); refreshSaveDisabled(); });

  contactEl?.addEventListener('input', ()=>{
    validateNativeLive(contactEl);
    validateContactEmergencyPair();
    refreshSaveDisabled();
  });
  emergEl?.addEventListener('input', ()=>{
    validateNativeLive(emergEl);
    validateContactEmergencyPair();
    refreshSaveDisabled();
  });

  emailEl?.addEventListener('input', ()=>{ validateNativeLive(emailEl); refreshSaveDisabled(); });
  fbEl?.addEventListener('input', ()=>{ validateFbLive(); refreshSaveDisabled(); });

  // combo inputs should also count as changes
  ['vpCourseSearch','vpYearSearch','vpStatusSearch','vpBarangaySearch','vpDistrictSearch'].forEach(id=>{
    const el = document.getElementById(id);
    el?.addEventListener('input', refreshSaveDisabled);
    el?.addEventListener('change', refreshSaveDisabled);
  });

  scheduleField?.addEventListener('input', ()=>{ refreshScheduleBtn(); refreshSaveDisabled(); });
  photoInput?.addEventListener('change', refreshSaveDisabled);

  formEl.addEventListener('submit', (e)=>{
    closePortal();

    let ok = true;

    const requiredEls = Array.from(formEl.querySelectorAll('[required]'))
      .filter(el => el.type !== 'hidden' && el.type !== 'file');

    requiredEls.forEach(el=>{
      const val = (el.tagName === 'SELECT') ? String(el.value||'').trim() : normSpace(el.value);
      if (!val){
        ok = false;
        if (el.classList.contains('vpEdit-nativeSelect')){
          const map = {
            'vpCourseSelect':'vpCourseSearch',
            'vpYearSelect':'vpYearSearch',
            'vpStatusSelect':'vpStatusSearch',
            'vpBarangaySelect':'vpBarangaySearch',
            'vpDistrictSelect':'vpDistrictSearch',
          };
          const iid = map[el.id];
          const inputEl = iid ? document.getElementById(iid) : null;
          if (inputEl) setInvalid(inputEl, 'This field is required.');
          showComboInvalid(el.id, true);
        } else {
          setInvalid(el, 'This field is required.');
        }
      }
    });

    // block submit if no changes
    if (!hasChangesNow()){
      ok = false;
      e.preventDefault();
      e.stopPropagation();
      refreshSaveDisabled();
      return;
    }

    if (!validateFullNameLive()) ok = false;
    if (!validateNativeLive(idEl)) ok = false;
    if (!validateNativeLive(batchEl)) ok = false;
    if (!validateNativeLive(emailEl)) ok = false;
    if (!validateFbLive()) ok = false;
    if (!validateContactEmergencyPair()) ok = false;

    const comboPairs = [
      ['vpCourseSelect','vpCourseSearch'],
      ['vpYearSelect','vpYearSearch'],
      ['vpStatusSelect','vpStatusSearch'],
      ['vpBarangaySelect','vpBarangaySearch'],
      ['vpDistrictSelect','vpDistrictSearch'],
    ];
    comboPairs.forEach(([sid,iid])=>{
      const s = document.getElementById(sid);
      const i = document.getElementById(iid);
      if (!s || !i) return;
      const good = enforceMatch(s,i);
      if (!good) ok = false;
    });

    refreshSaveDisabled();

    if (!ok){
      e.preventDefault();
      e.stopPropagation();
      formEl.classList.add('was-validated');
      (formEl.querySelector('.is-invalid') || formEl.querySelector(':invalid'))?.focus?.();
      return;
    }
  });

  function whenBootstrapReady(cb){
    if (window.bootstrap && window.bootstrap.Modal) return cb();
    setTimeout(() => whenBootstrapReady(cb), 25);
  }

  const VP_SHOW_UPDATE_SUCCESS = {{ $vpShowUpdateSuccess ? 'true' : 'false' }};

  whenBootstrapReady(()=>{
    if (HAS_SERVER_ERRORS) lockServerInvalidInputs();

    modalEl.addEventListener('shown.bs.modal', ()=>{
      snapshotInitial();
      forceGreenAllOnOpen();
      refreshScheduleBtn();

      if (HAS_SERVER_ERRORS){
        formEl.querySelectorAll('.is-invalid').forEach(el=>{
          el.dataset.vpServerInvalid = '1';
          setTooltip(el, humanMessage(el));
        });
      }

      validateContactEmergencyPair();
      validateFullNameLive();
      validateNativeLive(idEl);
      validateNativeLive(batchEl);
      validateNativeLive(emailEl);
      validateFbLive();
      refreshSaveDisabled();
    });

    snapshotInitial();
    forceGreenAllOnOpen();
    refreshScheduleBtn();
    validateContactEmergencyPair();
    refreshSaveDisabled();

    // ✅ show success modal ONLY after update (uses vp_updated session flag)
    if (VP_SHOW_UPDATE_SUCCESS){
      setTimeout(() => {
        const sEl = document.getElementById('vpUpdateSuccessModal');
        if (!sEl) return;

        const name = sEl.getAttribute('data-vp-name') || '';
        const changedRaw = sEl.getAttribute('data-vp-changed') || '{}';
        let changed = {};
        try { changed = JSON.parse(changedRaw); } catch { changed = {}; }

        const nameEl = document.getElementById('vpSuccessName');
        if (nameEl) nameEl.textContent = name;

        const listEl = document.getElementById('vpSuccessList');
        const noneEl = document.getElementById('vpSuccessNoChanges');
        if (listEl) listEl.innerHTML = '';

        const keys = Object.keys(changed || {});
        if (!keys.length){
          show(noneEl, true);
        } else {
          show(noneEl, false);
          keys.forEach(k=>{
            const info = changed[k] || {};
            const row = document.createElement('div');
            row.className = 'vpSuccessRow';
            row.innerHTML = `
              <div class="vpSuccessField">${escapeHtml(info.label || k)}:</div>
              <div class="vpSuccessOld">${escapeHtml(String(info.from ?? '(empty)') || '(empty)')}</div>
              <div class="vpSuccessArrow">→</div>
              <div class="vpSuccessNew">${escapeHtml(String(info.to ?? '(empty)') || '(empty)')}</div>
            `;
            listEl?.appendChild(row);
          });
        }

        try { bootstrap.Modal.getOrCreateInstance(sEl).show(); } catch {}
      }, 350);
    }

    scheduleBtn?.addEventListener('click', () => {
      try { bootstrap.Modal.getOrCreateInstance(modalEl).hide(); } catch(e) {}
    });
  });

})();
</script>
