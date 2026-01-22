  <style>
  /* === Base Container === */
  .search-container {
    position: relative;
    background: #fff;
    border-radius: 12px;
    overflow: visible;
    padding: 8px 12px;
    width: 100%;
    max-width: 600px;
    margin: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }


  /* === Row Layout (Search + Results + Sort) === */
  .search-row {
    display: flex;
    align-items: center;
    justify-content: flex-start; /* changed from space-between */
    gap: 18px; /* tighter consistent spacing */
    flex-wrap: nowrap; /* Prevent stacking */
    width: 100%;
  }

  /* === Search Box === */
  .search-box {
    position: relative;
    display: flex;
    align-items: center;
    flex: 1 1 45%;
    min-width: 240px;
    max-width: 400px;
  }

  .search-box input {
    width: 100%;
    font-size: clamp(0.85rem, 1vw + 0.5rem, 1rem);
    padding: 10px 38px 10px 14px;
    border: 2px solid #ccc;
    border-radius: 8px;
    transition: all 0.3s ease;
  }

  .search-box input:focus {
    border-color: #c00;
    outline: none;
    box-shadow: 0 0 5px rgba(204, 0, 0, 0.3);
  }

  /* Search Icon */
  .search-box .icon {
    position: absolute;
    right: 12px;
    font-size: 1rem;
    color: #777;
    pointer-events: none;
    transition: color 0.3s ease;
  }

  .search-box input:focus + .icon {
    color: #c00;
  }

  .search-box .icon {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: clamp(0.9rem, 1vw, 1.2rem);
    color: #888;
    transition: transform 0.4s ease, color 0.3s ease;
  }

  .search-box .icon:hover {
    color: #c00;
  }

  .search-box:focus-within .icon {
    transform: translateY(-50%) rotate(20deg);
    color: #c00;
  }

  /* === Results Count === */
  .results-count {
    font-size: clamp(0.8rem, 0.9vw + 0.3rem, 0.95rem);
    color: #555;
    font-weight: 500;
    white-space: nowrap;
    text-align: center;
    flex: 0 1 auto;
    min-width: 90px;
  }

  /* === Sort By Button === */
  .sort-by {
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: #333;
    padding: 8px 14px;
    border: 2px solid #ccc;
    border-radius: 8px;
    background: #fff;
    transition: all 0.25s ease;
    flex-shrink: 0;
    white-space: nowrap;
    justify-content: center;
    align-items: center;
  }

  .sort-by:hover {
    background: #f2f2f2;
    transform: scale(1.05);
  }

  .sort-by .filter-icon {
    font-size: 16px;
    transition: color 0.25s ease, transform 0.25s ease;
  }

  .sort-by .icon {
    font-size: 18px;
    transition: transform 0.3s ease, color 0.25s ease;
  }

  .sort-by.active {
    background: #c00;
    color: #fff;
    transform: scale(1.05);
  }

  .sort-by.active .icon {
    transform: rotate(180deg);
    color: #fff;
  }

  .sort-by.active .filter-icon {
    color: #fff;
  }

  /* === Sort Options Dropdown === */
  .sort-options {
    position: absolute;
    top: calc(100% + 6px);
    left: 0;
    width: 100%;
    z-index: 99;
    max-height: 0;
    opacity: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    padding: 0 10px;
    transition: all 0.25s ease;
    pointer-events: none;
    outline: 2px solid #c00;
  }

  .sort-options.open {
    background: #f5f7fa; /* <- change to any color you want */
    max-height: 800px;
    opacity: 1;
    pointer-events: auto;
    padding-top: 10px;
    padding-bottom: 10px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }

  .sort-options i {
    margin-right: 6px;
    transition: transform 0.2s ease, color 0.2s ease;
  }

  .sort-options i:hover {
    transform: rotate(-10deg) scale(1.1);
    color: #b71c1c;
  }

  .custom-select {
    background: #f5f7fa; /* <- change to any color you want */
    position: relative;
    width: 100%;   /* full width inside the dropdown */
    min-width: 160px; /* optional */
    cursor: pointer;
    margin-bottom: 10px; /* spacing between selects */
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: border-color 0.2s, box-shadow 0.2s;
  }


  /* Trigger button */
  .custom-select-trigger {
    display: block;
    width: 100%;
    background: white;
    border: 2px solid #ccc;
    padding: 8px;
    border-radius: 6px;
    transition: border-color 0.3s, background 0.3s;
  }

  /* When hovering over the trigger, rotate the icon inside it */
  .custom-select-trigger:hover i {
    transform: rotate(-10deg);
    color: #e60000;
    transition: transform 0.2s ease, color 0.2s ease;
  }

  /* Smooth transition back */
  .custom-select-trigger i {
    transition: transform 0.2s ease, color 0.2s ease;
    color: #c62828; /* default red */
  }

  .custom-select-trigger:hover {
    border-color: #dc3545;
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.15);
  }

  .custom-select.open .custom-select-trigger {
    border-color: #c00;
  }

  .custom-select-trigger,
  .custom-option {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
    transition: color 0.2s ease, transform 0.2s ease;
  }

  /* Make hover states pop a little */
  .custom-select-trigger:hover {
    color: #e53935;
    transform: translateX(2px);
  }

  /* Dropdown options */
  .custom-options {
    display: none;
    position: absolute;    /* critical */
    top: calc(100% + 2px);
    left: 0;
    width: 100%;
    max-height: 180px;     /* max height for scrolling */
    overflow-y: auto;      /* enable vertical scroll */
    overflow-x: hidden;
    border: 2px solid #c00;
    background: #fff;
    border-radius: 6px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    z-index: 999;
  }

  .custom-select.open .custom-options {
    display: block;
  }

  /* Scrollbar styling */
  .custom-options::-webkit-scrollbar {
    width: 6px;
  }
  .custom-options::-webkit-scrollbar-thumb {
    background-color: #c00;
    border-radius: 3px;
  }

  /* Individual option */
  .custom-option {
    display: block;
    padding: 8px;
    transition: background 0.2s, color 0.2s;
  }

  .custom-option:hover {
    background: #c00;
    color: #fff;
  }
  .custom-option i {
    color: #666;
    min-width: 18px;
    text-align: center;
  }

  .custom-option:hover i {
    color: #fff;
  }

  .actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 8px;
  }

  .right-actions {
    display: flex;
    align-items: center;
    gap: 10px; /* space between Reset & Apply */
  }


  .right-actions button {
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
  }

  /* === Buttons for Apply / Reset === */
  .actions {
    display: flex;
    justify-content: space-between;
    width: 100%;
    margin-top: 10px;
    align-items: center;
  }


  /* --- Reset Button --- */
  .reset-btn {
    background: #b8bcc0ff;
    color: #333;
    border: 1px solid #ccc;
    border-radius: 10px;
    padding: 8px 14px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.25s ease;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  }

  .reset-btn:hover {
    background: #d8dbdfff;
    border-color: #bbb;
    transform: translateY(-2px);
  }

  .reset-btn:active {
    transform: scale(0.97);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
  }

  /* --- Apply Button --- */
  .apply-btn {
    background: #b2000c;
    color: #fff;
    border: none;
    border-radius: 10px;
    padding: 8px 14px;
    font-weight: 600;
    cursor: pointer;
    font-size: 0.9rem;
    transition: all 0.25s ease;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
  }

  .apply-btn:hover {
    background: #8f000a;
    transform: translateY(-2px);
  }

  .apply-btn:active {
    background: #6b0007;
    transform: scale(0.97);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.25);
  }

  /* ===== Remove shadows + set background for the search container ===== */
  .search-container {
    box-shadow: none !important; /* remove container shadow */
  }

  /* Remove focus glow on the input if you want no shadow on focus */
  .search-box input:focus {
    border-color: #c00;    /* keep your red border if desired */
    outline: none;
    box-shadow: none;      /* remove the focus shadow */
  }

  /* Remove dropdown and select shadows if you want fully flat UI */
  .sort-options,
  .custom-options {
    box-shadow: none !important;
    background: inherit; /* inherit container bg or set a specific bg */
  }

  /* === Two-column layout for large screens (1920px and up) === */


  /* === Two-column layout for large screens (1920px and up) === */
  @media (min-width: 1920px) {
    .sort-options {
      display: grid !important;
      grid-template-columns: repeat(2, 1fr); /* two equal columns */
      column-gap: 16px;
      row-gap: 10px;
      align-items: start;
      padding: 12px 16px;
    }

    .custom-select {
      width: 100%;
      margin-bottom: 0;
    }

    /* Actions container spans both columns */
    .sort-options .actions {
      grid-column: 1 / -1; /* span both columns */
      display: flex;
      justify-content: flex-end; /* push buttons to right */
      align-items: center;
      margin-top: 12px;
      gap: 10px; /* space between Reset & Apply */
    }

    /* Individual buttons */
    .sort-options .reset-btn,
    .sort-options .apply-btn {
      min-width: 100px;
      padding: 8px 14px;
      font-size: 0.9rem;
      border-radius: 8px;
    }

    /* Apply button style */
    .sort-options .apply-btn {
      background-color: #B2000C;
      color: #fff !important;
      border: none;
    }

    /* Reset button style */
    .sort-options .reset-btn {
      background: #b8bcc0ff;
      color: #333;
      border: 1px solid #ccc;
    }
  }


  /* --- Responsive scaling for smaller screens --- */
  @media (max-width: 768px) {
    .reset-btn,
    .apply-btn {
      padding: 6px 10px;
      font-size: 0.8rem;
      border-radius: 8px;
    }

    .right-actions {
      gap: 6px;
    }
  }
  /* If you want the inner elements (like the search input) to remain white,
    keep them as-is, otherwise make them match the container:
  .search-box input {
    background: transparent;  / * or set same as container * /
  }

  /* Optional small polish: make the top controls sit flush on flat background */
  .search-row {
    align-items: center;
  }

  /* === Sort by Status Enhancements === */
  .custom-options[data-field="status"] .custom-option i {
    min-width: 18px;
    text-align: center;
    transition: transform 0.2s ease, color 0.2s ease;
  }

  .custom-options[data-field="status"] .custom-option:hover i {
    transform: rotate(-10deg) scale(1.1);
  }


  /* === Responsive Behavior === */

  /* Medium screens */
  @media (max-width: 992px) {
    .search-row {
      gap: 12px;
    }

    .search-box {
      flex: 1 1 40%;
    }

    .results-count {
      font-size: 0.9rem;
    }

    .sort-by {
      padding: 6px 10px;
    }

    /* Table actions */
    .table-actions {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
    }

    .table-actions button {
      flex: 1 1 auto;
      font-size: 0.85rem;
    }
  }

  /* Small screens */
  @media (max-width: 768px) {
    .search-row {
      flex-wrap: wrap;
      justify-content: center;
      gap: 10px;
    }

    .search-box,
    .results-count,
    .sort-by {
      flex: 1 1 100%;
      text-align: center;
    }

    .sort-by {
      padding: 8px 12px;
    } 

      /* === Core: prevent clipping === */
    .data-table-container,
    .search-container,
    .table-controls,
    .database-container {
      overflow: visible !important;
    }

    /* Sort panel itself */
    .sort-options {
      position: absolute;
      z-index: 9999;
      background: #f8f9fa;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);           /* narrower default width */
    }

    /* Individual custom selects */
    .custom-select {
      position: relative;
    }

    /* Detached dropdown popout (after JS reparenting) */
    .custom-options {
      position: absolute;
      top: 100%;
      left: 0;
      min-width: 180px;
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 6px;
      box-shadow: 0 6px 18px rgba(0,0,0,0.25);
      z-index: 10000;
      display: none;
    }

    /* Show state */
    .custom-select.open .custom-options {
      display: block;
    }
    
    /* Highlight both trigger and dropdown when active */
  .custom-select.open .custom-select-trigger,
  .custom-select.open .custom-options {
    border: 2px solid #dc3545; /* Red border same as your trigger */
    box-shadow: 0 0 5px rgba(220, 53, 69, 0.5); /* Optional subtle glow */
  }


    /* Actions inside sort-options */
    .actions {
      flex-direction: row;
      justify-content: space-between;
      width: 100%;
      margin-top: 10px;
      gap: 8px;
    }

    .apply-btn,
    .reset-btn {
      flex: 1 1 48%;
      padding: 8px 12px;
    }

    /* Table actions */
    .table-actions {
      flex-direction: row;
      gap: 6px;
      flex-wrap: wrap;
    }

    .table-actions button {
      flex: 1 1 48%;
      font-size: 0.85r  em;
      padding: 6px 8px;
    }
  }

  /* Extra small screens */
  @media (max-width: 480px) {
    .search-row {
      flex-direction: column;
      gap: 8px;
    }

    .search-box input {
      font-size: 0.9rem;
      padding: 8px 34px 8px 12px;
    }

    .results-count,
    .sort-by {
      flex: 1 1 100%;
      text-align: center;
      width: 100%;
    }

    .sort-by {
      padding: 6px 10px;
      font-size: 0.85rem;
      
    }

    /* Actions inside sort-options */
    .actions {
      flex-direction: column;
      gap: 6px;
    }

    .apply-btn,
    .reset-btn {
      width: 100%;
      font-size: 0.85rem;
      padding: 8px 0;
    }

    /* Table actions */
    .table-actions {
      flex-direction: column;
      gap: 6px;
      width: 100%;
    }

    .table-actions button {
      width: 100%;
      font-size: 0.85rem;
      padding: 8px 0;
    }
  }

  </style>


  <style>
      /* Ghost input for inline autocomplete */
  .search-box {
      position: relative;
  }

  .search-ghost-input {
      position: absolute;
      inset: 0;
      width: 100%;
      border-radius: 8px;
      padding: 10px 38px 10px 14px;
      border: 2px solid transparent; /* real border is on main input */
      background: transparent;
      color: rgba(0,0,0,0.25); /* light suggestion color */
      pointer-events: none;
      font: inherit;
  }

  /* Make the real input sit above + be transparent bg */
  #volunteer-search-input {
      position: relative;
      background: transparent !important;
      z-index: 2;
  }

  </style>

  @php
      // Clean barangays
      $barangays = collect($barangays)->filter()->unique()->sort()->values();

      // Clean districts
      $districts = collect($districts)
          ->map(fn($d) => $d->district_id)
          ->filter()
          ->unique()
          ->sort()
          ->map(fn($id) => (object)[
              'district_id' => $id,
              'district_name' => "District $id"
          ])
          ->values();

      // Your schedule blocks (FILTER)
      $scheduleOptions = [
          "7:30-8:20 AM",
          "8:00-9:20 AM",
          "8:00-10:50 AM",
          "8:30-9:50 AM",
          "8:30-11:30 AM",
          "9:30-10:50 AM",
          "11:00-12:20 AM",

          "12:30-1:50 PM",
          "12:30-2:50 PM",
          "2:00-3:20 PM",
          "2:00-4:50 PM",
          "3:30-4:50 PM",
          "5:00-6:20 PM",
          "6:30-7:20 PM",
          "6:30-8:50 PM",
          "7:30-8:50 PM",
      ];
  @endphp

  <div class="search-container" id="volunteer-search-bar">

      <div class="search-row">

          <!-- Search Bar -->
          <div class="search-box">
              <input type="text" class="table-search" id="volunteer-search-input" placeholder="Search name...">
              <span class="icon"><i class="fas fa-search"></i></span>
          </div>

          <!-- Results Count -->
          <div class="results-count" id="volunteer-results-count">0 Results</div>

          <!-- Sort / Filter Button -->
          <div class="sort-by" id="volunteer-sort-toggle">
              <span class="label">Filter & Sort</span>
              <i class="fa-solid fa-filter filter-icon"></i>
              <span class="icon">⏷</span>
          </div>
      </div>


      <!-- SORT + FILTER PANEL -->
      <div class="sort-options" id="volunteer-sort-panel">

          <!-- SORT BY NAME -->
          <div class="custom-select" data-field="sort">
              <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-user'></i> Sort by Name">
                  <i class="fa-solid fa-user"></i> Sort by Name
              </div>

              <div class="custom-options">
                  <span class="custom-option" data-value="remove">
                      <i class="fa-solid fa-ban"></i> Remove Sort
                  </span>
                  <span class="custom-option" data-value="full_name-asc">
                      <i class="fa-solid fa-arrow-down-a-z"></i> A → Z
                  </span>
                  <span class="custom-option" data-value="full_name-desc">
                      <i class="fa-solid fa-arrow-down-z-a"></i> Z → A
                  </span>
              </div>
          </div>


          <!-- FILTER: COURSE (Searchable) -->
          <div class="custom-select searchable" data-field="course_id">
              <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-graduation-cap'></i> Course">
                  <i class="fa-solid fa-graduation-cap"></i> Course
              </div>

              <div class="custom-options">

                  <!-- Search box inside dropdown -->
                  <div class="search-box" style="padding: 6px 10px;">
                      <i class="fa-solid fa-magnifying-glass search-icon"></i>
                      <input type="text"
                          id="courseSearchInput"
                          class="dropdown-search"
                          placeholder="Search course..."
                          style="width: 100%; padding: 6px 8px; border: 1px solid #ccc; border-radius: 6px;">
                  </div>

                  <span class="custom-option" data-value="remove">
                      <i class="fa-solid fa-ban"></i> Any Course
                  </span>

                  @foreach ($courses as $c)
                      <span class="custom-option" data-value="{{ $c->course_id }}">
                          <i class="fa-solid fa-graduation-cap"></i> {{ $c->course_name }}
                      </span>
                  @endforeach
              </div>
          </div>



          <!-- FILTER BARANGAY (with live search) -->
          <div class="custom-select searchable" data-field="barangay">
              <div class="custom-select-trigger"
                  data-original-text="<i class='fa-solid fa-location-dot'></i> Barangay">
                  <i class="fa-solid fa-location-dot"></i> Barangay
              </div>

              <div class="custom-options">

                  <!-- Search box inside dropdown -->
                  <div class="search-box" style="padding: 6px 10px;">
                      <i class="fa-solid fa-magnifying-glass search-icon"></i>
                      <input type="text"
                          id="barangaySearchInput"
                          class="dropdown-search"
                          placeholder="Search barangay..."
                          style="width: 100%; padding: 6px 8px; border: 1px solid #ccc; border-radius: 6px;">
                  </div>

                  <span class="custom-option" data-value="remove">
                      <i class="fa-solid fa-ban"></i> Any Barangay
                  </span>

                  @foreach ($barangays as $b)
                      <span class="custom-option" data-value="{{ $b }}">
                          <i class="fa-solid fa-location-dot"></i> {{ $b }}
                      </span>
                  @endforeach

              </div>
          </div>



          <!-- FILTER: DISTRICT -->
          <div class="custom-select" data-field="district">
              <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-map-location-dot'></i> District">
                  <i class="fa-solid fa-map-location-dot"></i> District
              </div>

              <div class="custom-options">
                  <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Any District</span>

                  @foreach ($districts as $d)
                      <span class="custom-option" data-value="{{ $d->district_id }}">
                          <i class="fa-solid fa-map-location-dot"></i> {{ $d->district_name }}
                      </span>
                  @endforeach
              </div>
          </div>


          <!-- FILTER: YEAR LEVEL -->
          <div class="custom-select" data-field="year_level">
              <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-layer-group'></i> Year Level">
                  <i class="fa-solid fa-layer-group"></i> Year Level
              </div>

              <div class="custom-options">
                  <span class="custom-option" data-value="remove">
                      <i class="fa-solid fa-ban"></i> Any Year
                  </span>

                  @foreach ([1,2,3,4] as $y)
                      <span class="custom-option" data-value="{{ $y }}">
                          <i class="fa-solid fa-layer-group"></i>
                          {{ $y }}{{ $y==1?'st':($y==2?'nd':($y==3?'rd':'th')) }} Year
                      </span>
                  @endforeach
              </div>
          </div>


          <!-- FILTER: DAY SELECTION -->
          <div class="custom-select" data-field="day">
              <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-calendar-day'></i> Day">
                  <i class="fa-solid fa-calendar-day"></i> Day
              </div>

              <div class="custom-options">
                  <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Any Day</span>

                  @foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $d)
                      <span class="custom-option" data-value="{{ $d }}">
                          <i class="fa-solid fa-calendar-day"></i> {{ $d }}
                      </span>
                  @endforeach
              </div>
          </div>


          <!-- FILTER: SPECIFIC TIME BLOCK -->
          <div class="custom-select searchable" data-field="schedule_day">
              <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-clock'></i> Available At">
                  <i class="fa-solid fa-clock"></i> Available At
              </div>

              <div class="custom-options">

                  <!-- Search Bar inside dropdown -->
                  <div class="search-box" style="padding:6px;">
                      <i class="fa-solid fa-magnifying-glass search-icon"></i>
                      <input type="text" id="timeSearchInput" placeholder="Search time...">
                  </div>

                  <span class="custom-option" data-value="remove">
                      <i class="fa-solid fa-ban"></i> Any Time
                  </span>

                  @foreach ($scheduleOptions as $s)
                      <span class="custom-option" data-value="{{ $s }}"
                          data-text="{{ strtolower($s) }}">
                          <i class="fa-solid fa-clock"></i> {{ $s }}
                      </span>
                  @endforeach
              </div>
          </div>



          <!-- BUTTONS -->
          <div class="actions">
              <div class="right-actions">
                  <button type="button" class="reset-btn" id="volunteer-reset-btn">Reset</button>
                  <button type="button" class="apply-btn" id="volunteer-apply-btn">Apply</button>
              </div>
          </div>

      </div>
      
  </div>
  <script>
  document.addEventListener("DOMContentLoaded", () => {
      const sortToggle  = document.getElementById("volunteer-sort-toggle");
      const sortPanel   = document.getElementById("volunteer-sort-panel");
      const searchInput = document.getElementById("volunteer-search-input");
      const resetBtn    = document.getElementById("volunteer-reset-btn");
      const applyBtn    = document.getElementById("volunteer-apply-btn");

      if (typeof debounce !== "function") {
          window.debounce = function (fn, wait = 300) {
              let t;
              return (...args) => {
                  clearTimeout(t);
                  t = setTimeout(() => fn(...args), wait);
              };
          };
      }

      let activeFilters = {};
      let openSelect = null;

      const timeBlocks = Array.from(
          document.querySelectorAll('.custom-select[data-field="schedule_day"] .custom-option')
      )
      .map(opt => opt.dataset.value || opt.textContent.trim())
      .filter(v => v && v !== "remove");

      const DAY_NAMES = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

      /* ======================================================
        GHOST AUTOCOMPLETE SETUP
      ====================================================== */
      let ghostInput = null;
      let currentSuggestion = "";

      function setupGhostInput(realInput) {
          if (!realInput) return;
          const wrapper = realInput.closest(".search-box");
          if (!wrapper) return;

          wrapper.style.position = "relative";

          ghostInput = document.createElement("input");
          ghostInput.type = "text";
          ghostInput.className = "search-ghost-input";
          ghostInput.setAttribute("aria-hidden", "true");
          ghostInput.tabIndex = -1;

          wrapper.insertBefore(ghostInput, realInput);
      }

      function buildSuggestion(raw) {
          const text = raw || "";
          if (!text.trim()) return "";
          const lower = text.toLowerCase();

          let matchedDay = null;
          let dayStartIdx = -1;
          let dayEndIdx = -1;

          for (const d of DAY_NAMES) {
              const full  = d.toLowerCase();
              const short = full.slice(0,3);

              let idx = lower.indexOf(full);
              let len = full.length;

              if (idx === -1) {
                  idx = lower.indexOf(short);
                  len = short.length;
              }

              if (idx !== -1) {
                  matchedDay = d;
                  dayStartIdx = idx;
                  dayEndIdx = idx + len;
                  break;
              }
          }

          let timeFragment = null;
          let searchArea = text;

          if (matchedDay && dayEndIdx !== -1) {
              searchArea = text.substring(dayEndIdx);
          }

          const timeMatch = searchArea.match(/(\d{1,2}\s*:\s*\d{0,2})/);
          if (timeMatch) {
              timeFragment = timeMatch[1].replace(/\s+/g, "");
          }

          let bestBlock = null;
          if (timeFragment) {
              for (const block of timeBlocks) {
                  const mainPart = block.replace(/\s+(AM|PM)$/i, "");
                  if (mainPart.startsWith(timeFragment)) {
                      bestBlock = block;
                      break;
                  }
              }
          }

          if (matchedDay && bestBlock) {
              const typedTrim = text.trimEnd();
              const target = matchedDay + " " + bestBlock;
              if (target.toLowerCase().startsWith(typedTrim.toLowerCase())) {
                  return target;
              }
              return typedTrim + " " + bestBlock;
          }

          if (matchedDay && !bestBlock) {
              const typedTrim = text.trimEnd();
              const target = matchedDay;
              if (target.toLowerCase().startsWith(typedTrim.toLowerCase())) {
                  return target;
              }
              return typedTrim +
                  (typedTrim.endsWith(" ") ? "" : " ") +
                  matchedDay.slice(typedTrim.length);
          }

          if (!matchedDay && bestBlock) {
              const typedTrim = text.trimEnd();
              const target = bestBlock;
              if (target.toLowerCase().startsWith(typedTrim.toLowerCase())) {
                  return target;
              }
              return typedTrim +
                  (typedTrim.endsWith(" ") ? "" : " ") +
                  bestBlock;
          }

          return "";
      }

      function updateGhostSuggestion() {
          if (!ghostInput || !searchInput) return;
          const raw = searchInput.value;
          const suggestion = buildSuggestion(raw);

          currentSuggestion = suggestion || "";

          if (!suggestion || suggestion.toLowerCase() === raw.toLowerCase()) {
              ghostInput.value = "";
          } else {
              ghostInput.value = suggestion;
          }
      }

      /* ======================================================
        FIXED: SMART DAY + TIME DETECTION
        (Only activates when a DAY keyword is typed)
      ====================================================== */
      function detectDayAndTimeFromSearch(text, fallbackDay = null) {
          text = text || "";
          const lower = text.toLowerCase();

          const containsExplicitDay =
              DAY_NAMES.some(d => lower.includes(d.toLowerCase())) ||
              ["mon","tue","wed","thu","fri","sat"].some(short => lower.includes(short));

          if (!containsExplicitDay) {
              return { day: null, schedule_day: null };
          }

          let matchedDay = null;
          for (const d of DAY_NAMES) {
              const full  = d.toLowerCase();
              const short = full.slice(0,3);
              if (lower.includes(full) || lower.includes(short)) {
                  matchedDay = d;
                  break;
              }
          }

          const effectiveDay = matchedDay || fallbackDay || null;

          const rangeMatch = text.match(/(\d{1,2}\s*:\s*\d{2})\s*-\s*(\d{1,2}\s*:\s*\d{2})/);
          if (rangeMatch) {
              const typedStart = rangeMatch[1].replace(/\s+/g, "");
              const typedEnd   = rangeMatch[2].replace(/\s+/g, "");
              const [tsH, tsM] = typedStart.split(":").map(Number);
              const [teH, teM] = typedEnd.split(":").map(Number);

              const typedStartMin = tsH * 60 + tsM;
              const typedEndMin   = teH * 60 + teM;

              let bestBlock = null;
              let bestDiff = Infinity;

              for (const block of timeBlocks) {
                  const clean = block.replace(/\s+(AM|PM)$/i, "");
                  const [bStart, bEnd] = clean.split("-");
                  if (!bStart || !bEnd) continue;

                  const [bsH, bsM] = bStart.split(":").map(Number);
                  const [beH, beM] = bEnd.split(":").map(Number);

                  const blockStartMin = bsH * 60 + bsM;
                  const blockEndMin   = beH * 60 + beM;

                  const diff = Math.abs(blockStartMin - typedStartMin) +
                              Math.abs(blockEndMin - typedEndMin);

                  if (diff < bestDiff) {
                      bestDiff = diff;
                      bestBlock = block;
                  }
              }

              return { day: effectiveDay, schedule_day: bestBlock };
          }

          const timeMatch = text.match(/(\d{1,2}\s*:\s*\d{2})/);
          if (timeMatch) {
              const frag = timeMatch[1].replace(/\s+/g, "");
              for (const block of timeBlocks) {
                  const clean = block.replace(/\s+(AM|PM)$/i, "");
                  if (clean.startsWith(frag)) {
                      return { day: effectiveDay, schedule_day: block };
                  }
              }
          }

          return { day: effectiveDay, schedule_day: null };
      }

      /* ======================================================
        APPLY FILTERS + SMART SEARCH
      ====================================================== */
      function applyFiltersWithSearch() {
          const baseParams = {
              page: 1,
              search: searchInput.value
          };

          let params = { ...baseParams };

          Object.entries(activeFilters).forEach(([key,value]) => {
              params[key] = value;
          });

          const fallbackDay = activeFilters.day || null;
          const detected = detectDayAndTimeFromSearch(searchInput.value, fallbackDay);

          params.day = null;
          params.schedule_day = null;

          if (activeFilters.day) {
              params.day = activeFilters.day;
          }
          if (activeFilters.schedule_day) {
              params.schedule_day = activeFilters.schedule_day;
          }

          if (detected.day) {
              params.day = detected.day;
          }
          if (detected.schedule_day) {
              params.schedule_day = detected.schedule_day;
          }

          fetchPage(params);
      }

      /* ======================================================
        INIT
      ====================================================== */
      setupGhostInput(searchInput);
      updateGhostSuggestion();

      sortToggle.addEventListener("click", () => {
          sortPanel.classList.toggle("open");
          sortToggle.classList.toggle("active");
          closeAllSelects();
      });

      document.addEventListener("click", e => {
          if (!sortPanel.contains(e.target) && !sortToggle.contains(e.target)) {
              sortPanel.classList.remove("open");
              sortToggle.classList.remove("active");
              closeAllSelects();
          }
      });

      function closeAllSelects() {
          document.querySelectorAll(".custom-select").forEach(s =>
              s.classList.remove("open")
          );
          openSelect = null;
      }

      /* ======================================================
        CUSTOM SELECTS
      ====================================================== */
      document.querySelectorAll(".custom-select").forEach(select => {
          const trigger = select.querySelector(".custom-select-trigger");
          const options = [...select.querySelectorAll(".custom-option")];

          trigger.addEventListener("click", e => {
              e.stopPropagation();
              if (openSelect && openSelect !== select) closeAllSelects();
              select.classList.toggle("open");
              openSelect = select.classList.contains("open") ? select : null;
          });

          options.forEach(opt => {
              opt.addEventListener("click", () => {
                  trigger.innerHTML = opt.innerHTML;
                  select.dataset.value = opt.dataset.value;
                  select.classList.remove("open");
                  openSelect = null;
              });
          });
      });

      /* ======================================================
        RESET BUTTON
      ====================================================== */
      resetBtn.addEventListener("click", () => {
          searchInput.value = "";
          updateGhostSuggestion();

          document.querySelectorAll(".custom-select").forEach(select => {
              const trigger = select.querySelector(".custom-select-trigger");
              trigger.innerHTML = trigger.dataset.originalText;
              delete select.dataset.value;
          });

          activeFilters = {};

          if (typeof currentParams !== "undefined") {
              currentParams = {};
          }

          fetchPage({ page: 1, per_page: perPage, search: "" });
      });

      /* ======================================================
        APPLY BUTTON
      ====================================================== */
      applyBtn.addEventListener("click", () => {
          activeFilters = {};

          document.querySelectorAll(".custom-select").forEach(select => {
              const field = select.dataset.field;
              const value = select.dataset.value;
              if (value && value !== "remove") {
                  activeFilters[field] = value;
              }
          });

          applyFiltersWithSearch();
      });

      /* ======================================================
        LIVE SEARCH + TAB AUTOFILL
      ====================================================== */
      searchInput.addEventListener("input", updateGhostSuggestion);

      searchInput.addEventListener("input", debounce(() => {
          applyFiltersWithSearch();
      }, 300));

      searchInput.addEventListener("keydown", e => {
          if (e.key === "Tab" && currentSuggestion) {
              const raw = searchInput.value || "";
              if (currentSuggestion.toLowerCase().startsWith(raw.toLowerCase())) {
                  e.preventDefault();
                  searchInput.value = currentSuggestion;
                  updateGhostSuggestion();
                  applyFiltersWithSearch();
              }
          }
      });

      /* ======================================================
        SEARCHABLE BARANGAY
      ====================================================== */
      const barangaySearchInput = document.getElementById("barangaySearchInput");

      if (barangaySearchInput) {
          barangaySearchInput.addEventListener("input", () => {
              const term = barangaySearchInput.value.toLowerCase();

              document.querySelectorAll(
                  '.custom-select[data-field="barangay"] .custom-option'
              ).forEach(opt => {
                  if (opt.dataset.value === "remove") {
                      opt.style.display = "block";
                      return;
                  }
                  const text = opt.innerText.toLowerCase();
                  opt.style.display = text.includes(term) ? "block" : "none";
              });
          });
      }

      /* ======================================================
        SEARCHABLE COURSE
      ====================================================== */
      const courseSearchInput = document.getElementById("courseSearchInput");

      if (courseSearchInput) {
          courseSearchInput.addEventListener("input", () => {
              const term = courseSearchInput.value.toLowerCase();

              document.querySelectorAll(
                  '.custom-select[data-field="course_id"] .custom-option'
              ).forEach(opt => {
                  if (opt.dataset.value === "remove") {
                      opt.style.display = "block";
                      return;
                  }

                  const text = opt.innerText.toLowerCase();
                  opt.style.display = text.includes(term) ? "block" : "none";
              });
          });
      }

      /* ======================================================
        SEARCHABLE TIME BLOCKS
      ====================================================== */
      const timeSearchInput = document.getElementById("timeSearchInput");

      if (timeSearchInput) {
          timeSearchInput.addEventListener("input", () => {
              const term = timeSearchInput.value.toLowerCase();

              document.querySelectorAll(
                  '.custom-select[data-field="schedule_day"] .custom-option'
              ).forEach(opt => {
                  if (opt.dataset.value === "remove") {
                      opt.style.display = "block";
                      return;
                  }

                  const text = (opt.dataset.text || opt.innerText).toLowerCase();
                  opt.style.display = text.includes(term) ? "block" : "none";
              });
          });
      }
  });
  </script>
