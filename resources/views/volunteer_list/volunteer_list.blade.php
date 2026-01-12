@php
  $pageTitle = 'Volunteer Lists';

  $courses    = $courses    ?? collect();
  $barangays  = $barangays  ?? collect();
  $districts  = $districts  ?? collect();
  $locations  = $locations  ?? collect();

  $DEFAULT_AVATAR = asset('storage/defaults/default_user.png');

  // ✅ PATCH: provide distinct batches for the dropdown
  // NOTE: Ideally this is computed in controller, but this works immediately.
  $batches = \App\Models\VolunteerProfile::query()
    ->whereNotNull('batch_year')
    ->where('batch_year', '!=', '')
    ->distinct()
    ->orderBy('batch_year', 'desc')
    ->pluck('batch_year')
    ->values();
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

  {{-- ✅ PATCH: ONLY layout + rows UI + navbar offset + keep your autosuggest styles (no JS here) --}}
  <style>
    /* ✅ Navbar overlap safety (script also sets --vlNavOffset dynamically; keep a good default) */
    :root{ --vlNavOffset: 72px; }
    .vl-page{ padding-top: var(--vlNavOffset) !important; margin-top: 0 !important; }

    /* ✅ Toolbar alignment: Search + Rows + Filter (Filter must NOT take full width) */
    .vl-toolbar{
      display:flex !important;
      align-items:center;
      gap:12px;
      margin: 12px 0;
    }
    .vl-search{
      flex: 1 1 auto;
      max-width: 1200px;        /* prevents filter button from being pushed to next line */
      min-width: 260px;
    }
    .vl-filterBtn{
      width:auto !important;
      white-space:nowrap;
      flex: 0 0 auto;
    }

    /* ✅ Rows per page (uses the EXISTING JS hook: #vlPerPage) */
    .vl-perPage{
      flex: 0 0 auto;
      display:flex;
      align-items:center;
      gap:8px;
      background:#fff;
      border:1px solid rgba(15,23,42,.12);
      border-radius:14px;
      padding:6px 10px;
      box-shadow:0 6px 18px rgba(2,6,23,.08);
      height:44px;
      white-space:nowrap;
    }
    .vl-perPage label{
      font-size:12px;
      font-weight:900;
      color:#6b7280;
      margin:0;
      white-space:nowrap;
    }

    /* Make native select look custom (no extra JS needed) */
    .vl-perPage select{
      appearance:none;
      -webkit-appearance:none;
      -moz-appearance:none;
      border:0;
      outline:0;
      font-weight:1000;
      background:transparent;
      padding: 2px 28px 2px 6px; /* room for caret */
      cursor:pointer;
      color:#111827;
      line-height:1.1;
    }
    .vl-perPage .vl-perPageCaret{
      width:28px;
      height:28px;
      display:grid;
      place-items:center;
      border-radius:10px;
      background: rgba(162,52,63,0.08);
      color:#a2343f;
      pointer-events:none;
    }

    @media (max-width: 992px){
      :root{ --vlNavOffset: 64px; }
      .vl-toolbar{
        flex-wrap:wrap;
      }
      .vl-search{
        max-width: 100%;
        flex: 1 1 100%;
      }
      .vl-perPage{
        height:40px;
        padding:5px 9px;
        border-radius:12px;
      }
    }

    /* ✅ Keep your existing rich autosuggest look (so it doesn't break) */
    .vl-search{ position:relative; }
    .vl-suggest{
      position:absolute;
      left:10px;
      right:10px;
      top: calc(100% + 8px);
      z-index:9999;
      background:#fff;
      border:1px solid rgba(15, 23, 42, .10);
      border-radius:14px;
      box-shadow:0 14px 32px rgba(2, 6, 23, .18);
      overflow:hidden;
      max-height:320px;
      overflow-y:auto;
      padding:6px;
    }
    .vl-suggestItem{
      width:100%;
      border:0;
      background:transparent;
      display:flex;
      align-items:center;
      gap:10px;
      padding:10px 12px;
      border-radius:12px;
      text-align:left;
      cursor:pointer;
    }
    .vl-suggestItem:hover{ background:rgba(220, 38, 38, .07); }
  </style>
  {{-- ✅ END PATCH --}}
</head>

<body class="page--volunteer-list">
  @include('layouts.page_loader')
  @include('layouts.navbar')
  @include('layouts.back_button')

  <section class="vl-page">
    <div id="vlRoot"
         class="vl-root"
         data-data-url="{{ url('/volunteers/data') }}"
         data-profile-url-base="{{ url('/volunteer-profile') }}"
         data-default-avatar="{{ $DEFAULT_AVATAR }}"
         data-courses='@json($courses)'
         data-barangays='@json($barangays)'
         data-districts='@json($districts)'
         data-batches='@json($batches)'>

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

          {{-- ✅ PATCH: rows dropdown that your CURRENT script.js already supports --}}
          <div class="vl-dd vl-dd--rows" data-dd="perpage">
            <button class="vl-ddBtn" type="button">
              <span data-dd-text>6 rows</span>
              <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="vl-ddMenu" data-dd-menu></div>
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
              <label>Batch Year</label>
              <div class="vl-dd" data-dd="batch">
                <button class="vl-ddBtn" type="button">
                  <span data-dd-text>All Batches</span>
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

  @include('layouts.modals.submit.volunteer_list.add_volunteer_modal')

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="{{ asset('assets/volunteer_list/js/script.js') }}"></script>
</body>
</html>
