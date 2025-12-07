@php
  $pageTitle = 'Volunteer Lists';

  $courses    = $courses    ?? collect();
  $barangays  = $barangays  ?? collect();
  $districts  = $districts  ?? collect();
  $locations  = $locations  ?? collect();

  $DEFAULT_AVATAR = asset('storage/defaults/default_user.png');
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Volunteer List</title>

  <link rel="stylesheet" href="{{ asset('assets/volunteer_list/css/Volunteer_List.css') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">
</head>

<body class="page--volunteer-list">
  @include('layouts.page_loader')
  @include('layouts.navbar')
  @include('layouts.back_button')

  <section class="vl-page">
    <div id="vlRoot"
         class="vl-root"
         data-default-avatar="{{ $DEFAULT_AVATAR }}"
         data-courses='@json($courses)'
         data-barangays='@json($barangays)'
         data-districts='@json($districts)'>

      <div class="vl-header">
        <div class="vl-kicker">
          <i class="fa-solid fa-user-group vl-titleIcon"></i>
          <h1>Volunteer Lists</h1>
        </div>

        <div class="vl-actions">
          <div class="vl-pill" id="vlCountPill">
            <i class="fa-solid fa-layer-group"></i>
            <span>Total:</span>
            <strong id="vlTotal">0</strong>
          </div>

          <button class="btn btn-danger add-student-trigger"
                  type="button"
                  data-bs-toggle="modal"
                  data-bs-target="#addVolunteerModal">
            <i class="fa fa-plus"></i> Add Volunteer
          </button>
        </div>
      </div>

      {{-- FLOAT WRAP: toolbar + overlay panel anchored here --}}
      <div class="vl-floatWrap">
        <!-- Toolbar -->
        <div class="vl-toolbar">
          <div class="vl-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input id="vlSearch" type="text" placeholder="Search name, course, barangay, district..." autocomplete="off" />
            <button id="vlSearchClear" type="button" class="vl-clear" aria-label="Clear search">
              <i class="fa-solid fa-xmark"></i>
            </button>

            <div id="vlSuggest" class="vl-suggest" hidden></div>
          </div>

          <button id="vlFilterToggle" class="vl-filterBtn" type="button" aria-expanded="false">
            <i class="fa-solid fa-sliders"></i>
            Filter & Sort
            <i class="fa-solid fa-caret-down"></i>
          </button>
        </div>

        <!-- Filter panel (overlay, does NOT push content) -->
        <div id="vlPanel" class="vl-panel" hidden>
          <div class="vl-panelGrid">
            <div class="vl-field">
              <label>Sort by</label>
              <div class="vl-dd" data-dd="sort">
                <button class="vl-ddBtn" type="button">
                  <span data-dd-text>Sort by Name (A–Z)</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="vl-ddMenu" data-dd-menu></div>
              </div>
            </div>

            <div class="vl-field">
              <label>Course</label>
              <div class="vl-dd" data-dd="course">
                <button class="vl-ddBtn" type="button">
                  <span data-dd-text>All Courses</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="vl-ddMenu" data-dd-menu></div>
              </div>
            </div>

            <div class="vl-field">
              <label>Barangay</label>
              <div class="vl-dd" data-dd="barangay">
                <button class="vl-ddBtn" type="button">
                  <span data-dd-text>All Barangays</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="vl-ddMenu" data-dd-menu></div>
              </div>
            </div>

            <div class="vl-field">
              <label>District</label>
              <div class="vl-dd" data-dd="district">
                <button class="vl-ddBtn" type="button">
                  <span data-dd-text>All Districts</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="vl-ddMenu" data-dd-menu></div>
              </div>
            </div>

            <div class="vl-field">
              <label>Year Level</label>
              <div class="vl-dd" data-dd="year">
                <button class="vl-ddBtn" type="button">
                  <span data-dd-text>All Year Levels</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="vl-ddMenu" data-dd-menu></div>
              </div>
            </div>

            <div class="vl-field">
              <label>Available At (Day)</label>
              <div class="vl-dd" data-dd="day">
                <button class="vl-ddBtn" type="button">
                  <span data-dd-text>Any Day</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="vl-ddMenu" data-dd-menu></div>
              </div>
            </div>
            
            <div class="vl-field">
              <label>Available At (Time Block)</label>
              <div class="vl-dd" data-dd="block">
                <button class="vl-ddBtn" type="button">
                  <span data-dd-text>Any Time</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="vl-ddMenu" data-dd-menu></div>
              </div>
            </div>

             <div class="vl-field">
            <div class="vl-dd" data-dd="status">
              <label>Status</label>
              <button type="button" class="vl-ddBtn">
                <span data-dd-text>All Status</span>
                <i class="fa-solid fa-chevron-down"></i>
              </button>
            </div>
          </div>
          </div>
          
          <div class="vl-panelFooter">
            <button id="vlReset" class="vl-btn vl-btnGhost" type="button">Reset</button>
            <button id="vlApply" class="vl-btn vl-btnSolid" type="button">
              <i class="fa-solid fa-check me-1"></i> Apply
            </button>
          </div>
        </div>
      </div>

      <!-- Main card container -->
      <div class="vl-outerCard">
        <div id="cards-grid" class="vl-grid"></div>

        <div class="vl-bottomBar">
          <div id="grid-count" class="vl-count"></div>

          <div class="vl-nav" id="vlNav">
            <button id="arrow-up" class="vl-arrow" type="button" aria-label="Prev page">
              <i class="fa-solid fa-chevron-left"></i>
            </button>
            <div class="vl-pageInfo"><span id="vlPageNow">1</span> / <span id="vlPageTotal">1</span></div>
            <button id="arrow-down" class="vl-arrow" type="button" aria-label="Next page">
              <i class="fa-solid fa-chevron-right"></i>
            </button>
          </div>
        </div>
      </div>

    </div>
  </section>

  {{-- ================================
       ADD VOLUNTEER MODAL
  ================================= --}}
  <div class="modal fade" id="addVolunteerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content" style="border-radius:18px; overflow:hidden;">
        <div class="modal-header vl-modalHeader">
          <h5 class="modal-title">
            <i class="fa-solid fa-user-plus me-2"></i>Add Volunteer
          </h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <form method="POST"
              action="{{ url('/volunteers') }}"
              enctype="multipart/form-data"
              id="vlAddVolunteerForm">
          @csrf

          <div class="modal-body" style="background:#fff;">
            <div class="row g-4">
              {{-- LEFT: Avatar --}}
              <div class="col-md-3">
                <div class="d-flex flex-column align-items-start gap-3">
                  <img id="vlPhotoPreview"
                       class="vl-photoPreview"
                       src="{{ $DEFAULT_AVATAR }}"
                       alt="Profile Preview">
                  <div class="w-100">
                    <label class="form-label fw-bold mb-1">Profile Photo</label>
                    <input id="vlPhotoInput"
                           type="file"
                           name="profile_picture"
                           class="form-control"
                           accept="image/*" />
                    <div class="vl-miniHint">Optional. JPG/PNG up to 4MB.</div>
                  </div>
                </div>
              </div>

              {{-- RIGHT: Fields --}}
              <div class="col-md-9">
                <div class="row g-3">
                  {{-- Full Name + School ID --}}
                  <div class="col-md-8">
                    <label class="form-label fw-bold">
                      Full Name <span class="text-danger">*</span>
                    </label>
                    <input name="full_name"
                           class="form-control @error('full_name') is-invalid @enderror"
                           value="{{ old('full_name') }}"
                           required
                           maxlength="255"
                           placeholder="e.g., Juan D. Dela Cruz">
                    @error('full_name')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fw-bold">
                      School ID <span class="text-danger">*</span>
                    </label>
                    <input name="id_number"
                           class="form-control @error('id_number') is-invalid @enderror"
                           value="{{ old('id_number') }}"
                           required
                           pattern="\d{6,7}"
                           maxlength="7"
                           placeholder="6–7 digit ID">
                    <div class="form-text small">Must be unique.</div>
                    @error('id_number')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  {{-- Course + Year Level --}}
                  <div class="col-md-8">
                    <label class="form-label fw-bold">
                      Course <span class="text-danger">*</span>
                    </label>

                    <div class="vl-filterWrapper mb-1">
                      <i class="fa-solid fa-magnifying-glass vl-filterIcon"></i>
                      <input type="text"
                             class="form-control form-control-sm vl-filterInput"
                             id="vlCourseSearch"
                             placeholder="Search course…">
                    </div>

                    <select name="course_id"
                            id="vlCourseSelect"
                            class="form-select @error('course_id') is-invalid @enderror"
                            required>
                      <option value="">Select course</option>
                      @foreach($courses as $c)
                        <option value="{{ $c->course_id }}"
                                data-name="{{ $c->course_name }}"
                                {{ old('course_id') == $c->course_id ? 'selected' : '' }}>
                          {{ $c->abbr ? $c->abbr.' — ' : '' }}{{ $c->course_name }}
                        </option>
                      @endforeach
                    </select>
                    @error('course_id')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-md-4">
                    <label class="form-label fw-bold">
                      Year Level <span class="text-danger">*</span>
                    </label>
                    <select name="year_level"
                            class="form-select @error('year_level') is-invalid @enderror"
                            required>
                      <option value="">Year</option>
                      <option value="1" {{ old('year_level') == '1' ? 'selected' : '' }}>1st Year</option>
                      <option value="2" {{ old('year_level') == '2' ? 'selected' : '' }}>2nd Year</option>
                      <option value="3" {{ old('year_level') == '3' ? 'selected' : '' }}>3rd Year</option>
                      <option value="4" {{ old('year_level') == '4' ? 'selected' : '' }}>4th Year</option>
                    </select>
                    @error('year_level')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  {{-- Contact + Emergency --}}
                  <div class="col-md-6">
                    <label class="form-label fw-bold">
                      Contact Number <span class="text-danger">*</span>
                    </label>
                    <input name="contact_number"
                           class="form-control @error('contact_number') is-invalid @enderror"
                           value="{{ old('contact_number') }}"
                           required
                           pattern="^(09\d{9}|\+639\d{9})$"
                           placeholder="09XXXXXXXXX or +639XXXXXXXXX">
                    <div class="form-text small">Valid PH mobile number.</div>
                    @error('contact_number')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-bold">
                      Emergency Number <span class="text-danger">*</span>
                    </label>
                    <input name="emergency_contact"
                           class="form-control @error('emergency_contact') is-invalid @enderror"
                           value="{{ old('emergency_contact') }}"
                           required
                           pattern="^(09\d{9}|\+639\d{9})$"
                           placeholder="09XXXXXXXXX or +639XXXXXXXXX">
                    <div class="form-text small">Must be different from Contact Number.</div>
                    @error('emergency_contact')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  {{-- Email + FB --}}
                  <div class="col-md-6">
                    <label class="form-label fw-bold">
                      Email <span class="text-danger">*</span>
                    </label>
                    <input name="email"
                           type="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}"
                           required
                           placeholder="name@adzu.edu.ph or name@gmail.com">
                    <div class="form-text small">Only @adzu.edu.ph or @gmail.com.</div>
                    @error('email')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-md-6">
                    <label class="form-label fw-bold">FB / Messenger (optional)</label>
                    <input name="fb_messenger"
                           type="url"
                           class="form-control @error('fb_messenger') is-invalid @enderror"
                           value="{{ old('fb_messenger') }}"
                           placeholder="https://facebook.com/…">
                    @error('fb_messenger')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  {{-- Barangay + District + Status --}}
                  <div class="col-md-6">
                    <label class="form-label fw-bold">
                      Barangay <span class="text-danger">*</span>
                    </label>

                    <div class="vl-filterWrapper mb-1">
                      <i class="fa-solid fa-magnifying-glass vl-filterIcon"></i>
                      <input type="text"
                             class="form-control form-control-sm vl-filterInput"
                             id="vlBarangaySearch"
                             placeholder="Search barangay…">
                    </div>

                    <select name="barangay"
                            id="vlBarangaySelect"
                            class="form-select @error('barangay') is-invalid @enderror"
                            required>
                      <option value="">Select barangay</option>
                      @foreach($locations as $loc)
                        <option value="{{ $loc->barangay }}"
                                data-district="{{ $loc->district_id }}"
                                {{ old('barangay') === $loc->barangay ? 'selected' : '' }}>
                          {{ $loc->barangay }}
                        </option>
                      @endforeach
                    </select>
                    @error('barangay')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-md-3">
                    <label class="form-label fw-bold">
                      District <span class="text-danger">*</span>
                    </label>
                    <select name="district"
                            id="vlDistrictSelect"
                            class="form-select @error('district') is-invalid @enderror"
                            required>
                      <option value="">Select district</option>
                      @foreach($districts as $d)
                        <option value="{{ $d->district_id }}"
                                {{ old('district') == $d->district_id ? 'selected' : '' }}>
                          {{ $d->district_name }}
                        </option>
                      @endforeach
                    </select>
                    <div class="form-text small">Auto-filled when you pick a barangay.</div>
                    @error('district')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  <div class="col-md-3">
                    <label class="form-label fw-bold">Status</label>
                    <select name="status"
                            class="form-select @error('status') is-invalid @enderror">
                      <option value="active" {{ old('status','active') === 'active' ? 'selected' : '' }}>Active</option>
                      <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                    @error('status')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                  </div>

                  {{-- Class Schedule --}}
                  <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <label class="form-label fw-bold mb-0">
                        Class Schedule (optional)
                      </label>

                      <button type="button"
                              class="btn btn-outline-danger btn-sm vl-btnSchedule"
                              id="vlScheduleTrigger">
                        <i class="fa-solid fa-calendar-days me-1"></i>
                        Set Schedule
                      </button>
                    </div>

                    {{-- Hidden field actually sent to backend --}}
                    <input type="hidden"
                           name="class_schedule"
                           id="vlScheduleField"
                           value="{{ old('class_schedule') }}">

                    {{-- Preview text --}}
                    <div class="vl-scheduleMetaBox">
                      <span id="vlScheduleSummary" class="vl-scheduleMeta">
                        No schedule set. Volunteers will be treated as available on any day &amp; time.
                      </span>
                    </div>

                    <div class="form-text small mt-1">
                      Used when matching volunteers by unavailable class blocks.
                    </div>

                    @error('class_schedule')
                      <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                  </div>

                </div>
              </div>
            </div>
          </div>

          <div class="modal-footer" style="background:#f7f7f9;">
            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-danger vl-modalSave">
              <i class="fa-solid fa-floppy-disk me-1"></i> Save
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  {{-- ================================
       SCHEDULE BUILDER MODAL (Add Volunteer)
  ================================= --}}
  <div class="modal fade" id="vlScheduleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title mb-0">
            <i class="fa-solid fa-calendar-days me-2"></i> Class Schedule
          </h5>
          <button type="button"
                  class="btn-close btn-close-white"
                  data-bs-dismiss="modal"
                  aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <p class="small text-muted mb-2">
            Select the time blocks when the volunteer <strong>has classes</strong>.
            They are considered available outside these blocks.
          </p>

          <div class="table-responsive">
            <table class="table table-sm text-center align-middle vl-schTable mb-0">
              <thead>
                <tr>
                  <th style="width:40px;">#</th>
                  <th>Monday</th>
                  <th>Tuesday</th>
                  <th>Wednesday</th>
                  <th>Thursday</th>
                  <th>Friday</th>
                  <th>Saturday</th>
                </tr>
              </thead>
              <tbody id="vlScheduleBody"></tbody>
            </table>
          </div>
        </div>

        <div class="modal-footer justify-content-between">
          <button type="button"
                  class="btn btn-outline-secondary btn-sm"
                  id="vlScheduleClear">
            <i class="fa-solid fa-eraser me-1"></i> Clear All
          </button>

          <div>
            <button type="button"
                    class="btn btn-light btn-sm me-1"
                    data-bs-dismiss="modal">
              Cancel
            </button>
            <button type="button"
                    class="btn btn-danger btn-sm"
                    id="vlScheduleSave">
              <i class="fa-solid fa-check me-1"></i> Save Schedule
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('assets/volunteer_list/js/script.js') }}"></script>
</body>
</html>
