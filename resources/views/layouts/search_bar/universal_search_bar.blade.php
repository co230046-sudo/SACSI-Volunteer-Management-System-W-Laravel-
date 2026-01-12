{{-- universal_search_bar.blade.php (FULL PATCHED CODE) --}}
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
.search-container { box-shadow: none !important; }
.search-box input:focus { border-color: #c00; outline: none; box-shadow: none; }
.sort-options, .custom-options { box-shadow: none !important; background: inherit; }

/* === Two-column layout for large screens (1920px and up) === */
@media (min-width: 1920px) {
  .sort-options {
    display: grid !important;
    grid-template-columns: repeat(2, 1fr);
    column-gap: 16px;
    row-gap: 10px;
    align-items: start;
    padding: 12px 16px;
  }
  .custom-select { width: 100%; margin-bottom: 0; }
  .sort-options .actions {
    grid-column: 1 / -1;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-top: 12px;
    gap: 10px;
  }
  .sort-options .reset-btn, .sort-options .apply-btn {
    min-width: 100px;
    padding: 8px 14px;
    font-size: 0.9rem;
    border-radius: 8px;
  }
  .sort-options .apply-btn { background-color: #B2000C; color: #fff !important; border: none; }
  .sort-options .reset-btn { background: #b8bcc0ff; color: #333; border: 1px solid #ccc; }
}

/* --- Responsive scaling for smaller screens --- */
@media (max-width: 768px) {
  .reset-btn, .apply-btn { padding: 6px 10px; font-size: 0.8rem; border-radius: 8px; }
  .right-actions { gap: 6px; }
}

/* Optional small polish: make the top controls sit flush on flat background */
.search-row { align-items: center; }

/* === Sort by Status Enhancements === */
.custom-options[data-field="status"] .custom-option i {
  min-width: 18px;
  text-align: center;
  transition: transform 0.2s ease, color 0.2s ease;
}
.custom-options[data-field="status"] .custom-option:hover i {
  transform: rotate(-10deg) scale(1.1);
}

/* Medium screens */
@media (max-width: 992px) {
  .search-row { gap: 12px; }
  .search-box { flex: 1 1 40%; }
  .results-count { font-size: 0.9rem; }
  .sort-by { padding: 6px 10px; }
  .table-actions { display: flex; gap: 6px; flex-wrap: wrap; }
  .table-actions button { flex: 1 1 auto; font-size: 0.85rem; }
}

/* Small screens */
@media (max-width: 768px) {
  .search-row { flex-wrap: wrap; justify-content: center; gap: 10px; }
  .search-box, .results-count, .sort-by { flex: 1 1 100%; text-align: center; }
  .sort-by { padding: 8px 12px; }

  .data-table-container, .search-container, .table-controls, .database-container {
    overflow: visible !important;
  }

  .sort-options {
    position: absolute;
    z-index: 9999;
    background: #f8f9fa;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .custom-select { position: relative; }

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

  .custom-select.open .custom-options { display: block; }

  .custom-select.open .custom-select-trigger,
  .custom-select.open .custom-options {
    border: 2px solid #dc3545;
    box-shadow: 0 0 5px rgba(220, 53, 69, 0.5);
  }

  .actions {
    flex-direction: row;
    justify-content: space-between;
    width: 100%;
    margin-top: 10px;
    gap: 8px;
  }

  .apply-btn, .reset-btn { flex: 1 1 48%; padding: 8px 12px; }

  .table-actions { flex-direction: row; gap: 6px; flex-wrap: wrap; }
  .table-actions button { flex: 1 1 48%; font-size: 0.85rem; padding: 6px 8px; }
}

/* Extra small screens */
@media (max-width: 480px) {
  .search-row { flex-direction: column; gap: 8px; }
  .search-box input { font-size: 0.9rem; padding: 8px 34px 8px 12px; }
  .results-count, .sort-by { flex: 1 1 100%; text-align: center; width: 100%; }
  .sort-by { padding: 6px 10px; font-size: 0.85rem; }

  .actions { flex-direction: column; gap: 6px; }
  .apply-btn, .reset-btn { width: 100%; font-size: 0.85rem; padding: 8px 0; }

  .table-actions { flex-direction: column; gap: 6px; width: 100%; }
  .table-actions button { width: 100%; font-size: 0.85rem; padding: 8px 0; }
}

/* ---------- D3 NUMERIC FILTER CARD STYLES (Import Logs only) ---------- */
.numeric-filter-card {
    background: #fff;
    border: 2px solid #ccc;
    border-radius: 10px;
    padding: 12px;
    width: 100%;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}
.numeric-filter-title {
    font-size: 1rem;
    font-weight: bold;
    margin-bottom: 10px;
    color: #b2000c;
    display: flex;
    align-items: center;
    gap: 6px;
}
.numeric-filter-group { margin-bottom: 10px; }
.numeric-filter-group label { font-size: 0.85rem; font-weight: 600; color: #444; }
.numeric-range { display: flex; align-items: center; gap: 6px; margin-top: 4px; }
.numeric-range input {
    width: 100%;
    padding: 6px 8px;
    font-size: 0.85rem;
    border: 2px solid #ccc;
    border-radius: 6px;
}
.numeric-range input:focus { border-color: #b2000c; outline: none; }
.numeric-range .dash { font-weight: bold; color: #555; }
</style>

@php
    $isLogs = isset($tableId) && $tableId === 'import-logs-table';
@endphp

<!-- Integrated Search + Sort Bar -->
<div class="search-container" data-target-table="{{ $tableId }}">
  <div class="search-row">
    <!-- Search Bar -->
    <div class="search-box">
      <input type="text" class="table-search" placeholder="{{ $placeholder ?? 'Type keywords...' }}">
      <span class="icon"><i class="fas fa-search"></i></span>
    </div>

    <!-- Results Count -->
    <div class="results-count">0 Results</div>

    <!-- Sort Dropdown Toggle -->
    <div class="sort-by" role="button" tabindex="0" aria-expanded="false">
      <span class="label">Filter & Sort</span>
      <i class="fa-solid fa-filter filter-icon"></i>
      <span class="icon">⏷</span>
    </div>
  </div>

  <!-- SORT / FILTER AREA -->
  <div class="sort-options">
    @if(!$isLogs)
      {{-- ================= VOLUNTEERS (invalid/valid tables) ================= --}}
      <!-- Sort by Full Name -->
      <div class="custom-select" data-field="fullname">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-user'></i> Sort by Full Name">
          <i class="fa-solid fa-user"></i> Sort by Full Name
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Sort</span>
          <span class="custom-option" data-value="name-az"><i class="fa-solid fa-arrow-down-a-z"></i> A → Z</span>
          <span class="custom-option" data-value="name-za"><i class="fa-solid fa-arrow-down-z-a"></i> Z → A</span>
        </div>
      </div>

      <!-- Sort by ID Number -->
      <div class="custom-select" data-field="idnum">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-id-card'></i> Sort by ID #">
          <i class="fa-solid fa-id-card"></i> Sort by ID #
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Sort</span>
          <span class="custom-option" data-value="id-asc"><i class="fa-solid fa-arrow-up-1-9"></i> Lowest → Highest</span>
          <span class="custom-option" data-value="id-desc"><i class="fa-solid fa-arrow-down-9-1"></i> Highest → Lowest</span>
        </div>
      </div>

      <!-- Filter by Course -->
      <div class="custom-select" data-field="course">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-graduation-cap'></i> Filter by Course">
          <i class="fa-solid fa-graduation-cap"></i> Filter by Course
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Filter</span>
          @foreach ($courses as $course)
            <span class="custom-option" data-value="{{ $course->course_name }}">
              <i class="fa-solid fa-graduation-cap"></i> {{ $course->course_name }}
            </span>
          @endforeach
        </div>
      </div>

      <!-- Filter by Year -->
      <div class="custom-select" data-field="year">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-layer-group'></i> Filter by Year Level">
          <i class="fa-solid fa-layer-group"></i> Filter by Year Level
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Filter</span>
          @foreach ([1,2,3,4] as $year)
            <span class="custom-option" data-value="{{ $year }}">
              <i class="fa-solid fa-layer-group"></i>
              {{ $year }}{{ ['st','nd','rd','th'][$year-1] }} Year
            </span>
          @endforeach
        </div>
      </div>

      <!-- Filter by Batch (Year) -->
      <div class="custom-select" data-field="batch">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-layer-group'></i> Filter by Batch (Year)">
          <i class="fa-solid fa-layer-group"></i> Filter by Batch (Year)
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Filter</span>
          {{-- Batch options injected by JS --}}
        </div>
      </div>

      <!-- Filter by Barangay -->
      <div class="custom-select" data-field="barangay">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-house'></i> Filter by Barangay">
          <i class="fa-solid fa-house"></i> Filter by Barangay
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Filter</span>
          @foreach ($barangays as $loc)
            <span class="custom-option" data-value="{{ $loc->barangay }}">
              <i class="fa-solid fa-location-dot"></i> {{ $loc->barangay }}
            </span>
          @endforeach
        </div>
      </div>

      <!-- Filter by District -->
      <div class="custom-select" data-field="district">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-map-location-dot'></i> Filter by District">
          <i class="fa-solid fa-map-location-dot"></i> Filter by District
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Filter</span>
          @foreach ($districts as $dist)
            <span class="custom-option" data-value="District {{ $dist->district_id }}">
              <i class="fa-solid fa-location-dot"></i> District {{ $dist->district_id }}
            </span>
          @endforeach
        </div>
      </div>
    @else
      {{-- ================= IMPORT LOGS TABLE ================= --}}
      <!-- Sort by File Name -->
      <div class="custom-select" data-field="filename">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-file'></i> Sort by File Name">
          <i class="fa-solid fa-file"></i> Sort by File Name
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Sort</span>
          <span class="custom-option" data-value="filename-az"><i class="fa-solid fa-arrow-down-a-z"></i> A → Z</span>
          <span class="custom-option" data-value="filename-za"><i class="fa-solid fa-arrow-down-z-a"></i> Z → A</span>
        </div>
      </div>

      <!-- Sort by Uploaded By -->
      <div class="custom-select" data-field="uploaded_by">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-user'></i> Sort by Uploaded By">
          <i class="fa-solid fa-user"></i> Sort by Uploaded By
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Sort</span>
          <span class="custom-option" data-value="uploaded_by-az"><i class="fa-solid fa-arrow-down-a-z"></i> A → Z</span>
          <span class="custom-option" data-value="uploaded_by-za"><i class="fa-solid fa-arrow-down-z-a"></i> Z → A</span>
        </div>
      </div>

      <!-- Sort by Uploaded At -->
      <div class="custom-select" data-field="uploaded_at">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-clock'></i> Sort by Date">
          <i class="fa-solid fa-clock"></i> Sort by Date
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Sort</span>
          <span class="custom-option" data-value="date-asc"><i class="fa-solid fa-arrow-up-1-9"></i> Oldest → Newest</span>
          <span class="custom-option" data-value="date-desc"><i class="fa-solid fa-arrow-down-9-1"></i> Newest → Oldest</span>
        </div>
      </div>

      <!-- Filter by Batch (Year) -->
      <div class="custom-select" data-field="batch_number">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-layer-group'></i> Filter by Batch (Year)">
          <i class="fa-solid fa-layer-group"></i> Filter by Batch (Year)
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Filter</span>
          {{-- Batch options injected by JS --}}
        </div>
      </div>

      <!-- Filter by Status -->
      <div class="custom-select" data-field="status">
        <div class="custom-select-trigger" data-original-text="<i class='fa-solid fa-traffic-light'></i> Filter by Status">
          <i class="fa-solid fa-traffic-light"></i> Filter by Status
        </div>
        <div class="custom-options">
          <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Filter</span>
          <span class="custom-option" data-value="pending"><i class="fa-solid fa-circle"></i> Pending</span>
          <span class="custom-option" data-value="completed"><i class="fa-solid fa-circle-check"></i> Completed</span>
          <span class="custom-option" data-value="cancelled"><i class="fa-solid fa-circle-xmark"></i> Cancelled</span>
          <span class="custom-option" data-value="reset"><i class="fa-solid fa-arrow-rotate-left"></i> Reset</span>
          <span class="custom-option" data-value="failed"><i class="fa-solid fa-triangle-exclamation"></i> Failed</span>
          <span class="custom-option" data-value="abandoned"><i class="fa-solid fa-person-walking-arrow-right"></i> Abandoned</span>
        </div>
      </div>
    @endif

    <!-- Buttons -->
    <div class="actions">
      <div class="left-actions">
        <span class="results-inline" style="display:none;">0 Results</span>
      </div>
      <div class="right-actions">
        <button type="button" class="reset-btn">Reset</button>
        <button type="button" class="apply-btn">Apply Sort</button>
      </div>
    </div>
  </div>
</div>

<!-- ✅ ADD THIS (or paste into your existing CSS at the VERY BOTTOM to override reds + style dropdown-search) -->
<style>
  :root{
    --accent: #b23a48;
    --accent-dark: #8f2c37;
    --accent-soft: rgba(178, 58, 72, .18);
  }
  .search-box input:focus { border-color: var(--accent) !important; box-shadow: none !important; }
  .search-box:focus-within .icon,
  .search-box input:focus + .icon { color: var(--accent) !important; }
  .sort-options { outline-color: var(--accent) !important; }
  .sort-by.active { background: var(--accent) !important; }
  .sort-options i:hover { color: var(--accent-dark) !important; }
  .custom-select.open .custom-select-trigger { border-color: var(--accent) !important; }
  .custom-options { border-color: var(--accent) !important; }
  .custom-options::-webkit-scrollbar-thumb { background-color: var(--accent) !important; }
  .custom-option:hover { background: var(--accent) !important; }
  .custom-select-trigger:hover { border-color: var(--accent) !important; box-shadow: 0 0 0 3px var(--accent-soft) !important; }
  .apply-btn { background: var(--accent) !important; }
  .apply-btn:hover { background: var(--accent-dark) !important; }

  /* Dropdown search input */
  .custom-options .dropdown-search-wrap{
    position: sticky;
    top: 0;
    background: #fff;
    padding: 8px;
    border-bottom: 1px solid #e6e6e6;
    z-index: 1;
  }
  .custom-options input.dropdown-search{
    width: 100%;
    padding: 8px 10px;
    border: 2px solid #ddd;
    border-radius: 8px;
    outline: none;
    font-size: 0.9rem;
  }
  .custom-options input.dropdown-search:focus{
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-soft);
  }
  .custom-options .no-option-match{
    display:none;
    padding: 8px;
    color:#777;
    font-size:.9rem;
  }
</style>

<script>
/**
 * PATCH NOTES (Batch fix):
 * ✅ Fixes Batch filter not working by:
 * 1) Injecting batch year options for BOTH volunteers (field "batch") and import logs (field "batch_number")
 * 2) Parsing years correctly (supports "2024-2025", "Batch 2024", etc.)
 * 3) Fixing custom-option click handling (delegation was broken / duplicated before)
 * 4) Making "Remove" reset the trigger back to its original label (so Apply won’t accidentally keep a stale selection)
 */
(function () {
  function universalSearchEngine(container) {
    const tableId = container.dataset.targetTable;
    const table = document.getElementById(tableId);
    if (!table) return;

    const tbody = table.querySelector("tbody");
    if (!tbody) return;

    // ---------------------------------------------
    // Rows helpers
    // ---------------------------------------------
    function isPlaceholderRow(row) {
      if (!row) return true;
      if (row.classList.contains("no-search-results")) return true;
      if (row.dataset && (row.dataset.noResults === "1" || row.dataset.placeholder === "1")) return true;

      const tds = row.querySelectorAll("td");
      if (!tds.length) return true;

      if (tds.length === 1 && tds[0].hasAttribute("colspan")) {
        const msg = (tds[0].textContent || "").toLowerCase().trim();
        if (
          msg.startsWith("no") ||
          msg.includes("no result") ||
          msg.includes("no record") ||
          msg.includes("no entry") ||
          msg.includes("no data") ||
          msg.includes("empty") ||
          msg.includes("nothing")
        ) {
          return true;
        }
      }
      return false;
    }

    function getAllRows() {
      return Array.from(tbody.querySelectorAll("tr")).filter(r => !isPlaceholderRow(r));
    }

    const noResultsRow = tbody.querySelector(".no-search-results")
      || tbody.querySelector("tr[data-no-results='1']")
      || tbody.querySelector("tr[data-placeholder='1']");

    const searchInput   = container.querySelector(".table-search");
    const resultsCount  = container.querySelector(".results-count");
    const sortBtn       = container.querySelector(".sort-by");
    const sortPanel     = container.querySelector(".sort-options");
    const customSelects = container.querySelectorAll(".custom-select");

    function setTopSearchEnabled(enabled) {
      if (!searchInput) return;
      searchInput.disabled = !enabled;
      searchInput.classList.toggle("is-disabled", !enabled);
      if (!enabled) searchInput.blur();
    }

    const FIELD_TO_COL =
      tableId === "import-logs-table"
        ? {
            batch_number:   1,
            filename:        2,
            uploaded_by:     3,
            uploaded_at:     4,
            total_records:   5,
            valid_count:     6,
            invalid_count:   7,
            duplicate_count: 8,
            status:          9
          }
        : {
            fullname:  3,
            idnum:     4,
            course:    5,
            year:      6,
            batch:     7,
            contact:   8,
            email:     9,
            emergency: 10,
            fb:        11,
            barangay:  12,
            district:  13,
            schedule:  14
          };

    let activeFilters = {}; // colIndex -> string
    let activeSort = null;

    // ----------------------------
    // Normalization + helpers
    // ----------------------------
    function normalize(s) {
      return (s || "")
        .toString()
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/&/g, " and ")
        .replace(/[^a-z0-9\s@._-]/g, " ")
        .replace(/\s+/g, " ")
        .trim();
    }
    function compact(s) { return normalize(s).replace(/\s+/g, ""); }

    function getCellText(cell) {
      if (!cell) return "";
      if (cell.dataset && cell.dataset.value !== undefined) return (cell.dataset.value || "").toString().trim();
      const input = cell.querySelector("input, textarea, select");
      if (input && input.value !== undefined) return (input.value || "").toString().trim();
      return (cell.innerText || "").toString().trim();
    }

    function getRowSearchText(row) {
      let combined = "";
      row.querySelectorAll("td").forEach(cell => {
        let chunk = "";
        if (cell.innerText) chunk += " " + cell.innerText;
        if (cell.dataset && cell.dataset.value) chunk += " " + cell.dataset.value;
        const input = cell.querySelector("input, textarea, select");
        if (input && input.value) chunk += " " + input.value;
        combined += " " + chunk;
      });
      return combined;
    }

    function isSubsequence(q, t) {
      let ti = 0;
      for (let qi = 0; qi < q.length; qi++) {
        const ch = q[qi];
        if (ch === " ") continue;
        ti = t.indexOf(ch, ti);
        if (ti === -1) return false;
        ti++;
      }
      return true;
    }

    function isShortCode(qRaw) {
      const c = compact(qRaw);
      return /^[a-z]{2,10}$/.test(c);
    }

    function makeAcronym(str) {
      const stop = new Set(["and","of","the","in","for","to","on","at","with","a","an","major","certificate"]);
      const toks = normalize(str)
        .replace(/[@._-]/g, " ")
        .split(" ")
        .filter(Boolean)
        .filter(t => !stop.has(t));
      return toks.map(t => t[0]).join("");
    }

    function courseAcronym(str) {
      const t = normalize(str).replace(/[@._-]/g, " ");
      if (!t) return "";
      if (isShortCode(str)) return compact(t);

      const toks = t.split(" ").filter(Boolean);
      if (!toks.length) return "";

      const PREFIXES = new Set([
        "bs","ba","bse","bsc","bsa","bshm","bstm","bsed","beed",
        "bscs","bsit","bscpe","bsce","bsee","bsece"
      ]);

      const first = toks[0];
      const rest = toks.slice(1);

      if (PREFIXES.has(first)) {
        const restInit = rest.map(w => w[0]).join("");
        return compact(first + restInit);
      }
      return compact(makeAcronym(t));
    }

    function acr(str){ return compact(makeAcronym(str)); }

    function emailClean(s) { return (s || "").toString().toLowerCase().replace(/\s+/g, ""); }
    function isEmailishQuery(qRaw) {
      const q = emailClean(qRaw);
      return q.includes("@") || q.includes(".com") || q.includes(".edu") || q.includes(".ph");
    }
    function emailMatch(qRaw, textRaw) {
      const q = emailClean(qRaw);
      const t = emailClean(textRaw);
      if (!q) return true;
      if (!t) return false;
      if (q.includes("@")) return t.includes(q);
      return t.includes(q);
    }

    function fuzzyMatch(qRaw, textRaw) {
      const q = normalize(qRaw);
      const t = normalize(textRaw);
      if (!q) return true;
      if (!t) return false;

      if (isEmailishQuery(qRaw)) return emailMatch(qRaw, textRaw);

      const qc = compact(q);
      const tc = compact(t);

      if (t.includes(q) || tc.includes(qc)) return true;

      const qToks = q.split(" ").filter(Boolean);
      const tToks = t.split(" ").filter(Boolean);
      if (qToks.length) {
        const ok = qToks.every(qTok => tToks.some(tTok => tTok.startsWith(qTok)));
        if (ok) return true;
      }

      const tA  = acr(t);
      const tCA = courseAcronym(textRaw);
      const qA  = acr(q);
      const qCA = courseAcronym(qRaw);

      if (isShortCode(qRaw)) {
        if (
          (tA  && (tA === qc || tA.startsWith(qc))) ||
          (tCA && (tCA === qc || tCA.startsWith(qc) || qc.startsWith(tCA)))
        ) return true;
      } else {
        if (
          (qA  && tA  && (tA === qA  || tA.startsWith(qA))) ||
          (qCA && tCA && (tCA === qCA || tCA.startsWith(qCA) || qCA.startsWith(tCA)))
        ) return true;
      }

      if (tc.startsWith(qc) || qc.startsWith(tc)) return true;
      return isSubsequence(qc, tc);
    }

    // ----------------------------
    // Batch year helpers (✅ NEW)
    // ----------------------------
    function extractYears(text) {
      const s = (text || "").toString();
      const matches = s.match(/\b(19|20)\d{2}\b/g) || [];
      const years = matches
        .map(y => parseInt(y, 10))
        .filter(y => !isNaN(y) && y >= 2000 && y <= 2100);
      // unique
      return Array.from(new Set(years));
    }

    function isBatchField(field) {
      return field === "batch" || field === "batch_number";
    }

    function isBatchCol(colIdx) {
      const batchCol = FIELD_TO_COL.batch;
      const batchNumCol = FIELD_TO_COL.batch_number;
      return (batchCol != null && colIdx === batchCol) || (batchNumCol != null && colIdx === batchNumCol);
    }

    function batchCellMatchesYear(cellText, selectedYear) {
      const y = parseInt(selectedYear, 10);
      if (isNaN(y)) return false;
      const yearsInCell = extractYears(cellText);
      if (yearsInCell.includes(y)) return true;

      // fallback: whole-word contains (handles "Batch 2024")
      return new RegExp(`\\b${y}\\b`).test((cellText || "").toString());
    }

    // ----------------------------
    // Course smart synonyms
    // ----------------------------
    const COURSE_NORMALIZE_MAP = [
      { keys: ["bsit","bs it","bs-it","it","information technology","info tech"], canon: "bs information technology" },
      { keys: ["bscs","bs cs","bs-cs","cs","computer science","comp sci"], canon: "bs computer science" },
      { keys: ["bsa","bsac","accountancy","bs accountancy"], canon: "bs accountancy" },
      { keys: ["bscpe","bs cpe","bs-cpe","cpe","computer engineering","comp eng"], canon: "bs computer engineering" },
      { keys: ["bsce","bs ce","bs-ce","ce","civil engineering"], canon: "bs civil engineering" },
      { keys: ["bsee","bs ee","bs-ee","ee","electrical engineering"], canon: "bs electrical engineering" },
      { keys: ["bsece","bs ece","bs-ece","ece","electronics engineering"], canon: "bs electronics engineering" }
    ].map(x => ({
      keys: x.keys.map(normalize),
      canon: normalize(x.canon),
      canonA: courseAcronym(x.canon)
    }));

    function expandCourseQuery(qRaw) {
      const q = normalize(qRaw);
      const c = compact(q);
      const set = new Set([q, c]);

      const qCourseA = courseAcronym(qRaw);
      if (qCourseA) set.add(qCourseA);

      for (const rule of COURSE_NORMALIZE_MAP) {
        const hit = rule.keys.some(k => q.includes(k) || k.includes(q) || compact(k) === c);
        if (hit) {
          set.add(rule.canon);
          set.add(rule.canonA);
        }
      }
      return Array.from(set).filter(Boolean);
    }

    // ----------------------------
    // Logs smart: status + date parsing (unchanged)
    // ----------------------------
    const STATUS_SYNONYMS = {
      pending:   ["pending","awaiting","queued","queue"],
      completed: ["completed","complete","done","success","successful","finished"],
      cancelled: ["cancelled","canceled","void"],
      reset:     ["reset","restarted","rerun","re-run"],
      failed:    ["failed","error","errored","crashed"],
      abandoned: ["abandoned","stopped","dropped"]
    };

    function normalizeStatusFromQuery(qRaw){
      const q = normalize(qRaw);
      for (const [status, keys] of Object.entries(STATUS_SYNONYMS)) {
        if (q === status) return status;
        if (keys.some(k => q.includes(normalize(k)))) return status;
      }
      return null;
    }

    function startOfDayMs(d){
      return new Date(d.getFullYear(), d.getMonth(), d.getDate()).getTime();
    }

    function parseQueryDateToDayMs(qRaw){
      const q = normalize(qRaw);
      if (!q) return null;

      const now = new Date();
      if (q === "today") return startOfDayMs(now);
      if (q === "yesterday") {
        const d = new Date(now);
        d.setDate(d.getDate() - 1);
        return startOfDayMs(d);
      }

      let m = q.match(/\b(\d{4})[-\/](\d{1,2})[-\/](\d{1,2})\b/);
      if (m) {
        const d = new Date(+m[1], +m[2]-1, +m[3]);
        return isNaN(d.getTime()) ? null : startOfDayMs(d);
      }

      m = q.match(/\b(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})\b/);
      if (m) {
        const a = +m[1], b = +m[2], y = +m[3];
        const d1 = new Date(y, a-1, b);
        const d2 = new Date(y, b-1, a);
        const t1 = isNaN(d1.getTime()) ? null : startOfDayMs(d1);
        const t2 = isNaN(d2.getTime()) ? null : startOfDayMs(d2);
        return t1 ?? t2;
      }

      const months = {
        jan:0,january:0,feb:1,february:1,mar:2,march:2,apr:3,april:3,may:4,
        jun:5,june:5,jul:6,july:6,aug:7,august:7,sep:8,september:8,oct:9,october:9,
        nov:10,november:10,dec:11,december:11
      };
      const parts = q.split(" ").filter(Boolean);
      if (parts.length >= 2 && months[parts[0]] != null) {
        const mi = months[parts[0]];
        const day = parseInt(parts[1], 10);
        const year = parts[2] ? parseInt(parts[2], 10) : now.getFullYear();
        const d = new Date(year, mi, day);
        return isNaN(d.getTime()) ? null : startOfDayMs(d);
      }

      return null;
    }

    function rowCellDateToDayMs(cellText){
      const raw = (cellText || "").trim();
      if (!raw) return null;
      const d = new Date(raw);
      return isNaN(d.getTime()) ? null : startOfDayMs(d);
    }

    // ----------------------------
    // Smart row match for TOP SEARCH (unchanged)
    // ----------------------------
    function smartRowMatch(row, qRaw) {
      const q = normalize(qRaw);
      if (!q) return true;

      const rowText = normalize(getRowSearchText(row));
      if (rowText.includes(q)) return true;

      if (tableId !== "import-logs-table") {
        const courseText = getCellText(row.children[FIELD_TO_COL.course]);
        const brgyText   = getCellText(row.children[FIELD_TO_COL.barangay]);
        const emailText  = getCellText(row.children[FIELD_TO_COL.email]);

        if (isEmailishQuery(qRaw) && emailMatch(qRaw, emailText)) return true;

        const expanded = expandCourseQuery(qRaw);
        const courseA  = courseAcronym(courseText);

        if (expanded.some(eq => {
          const eqC = compact(eq);
          return (
            fuzzyMatch(eq, courseText) ||
            (courseA && (courseA === eqC || courseA.startsWith(eqC) || eqC.startsWith(courseA)))
          );
        })) return true;

        if (fuzzyMatch(qRaw, brgyText)) return true;
        return fuzzyMatch(qRaw, emailText);
      }

      const statusText = getCellText(row.children[FIELD_TO_COL.status]);
      const dateText   = getCellText(row.children[FIELD_TO_COL.uploaded_at]);
      const fileText   = getCellText(row.children[FIELD_TO_COL.filename]);
      const byText     = getCellText(row.children[FIELD_TO_COL.uploaded_by]);

      const qStatus = normalizeStatusFromQuery(qRaw);
      if (qStatus && normalize(statusText).includes(qStatus)) return true;

      const qDay = parseQueryDateToDayMs(qRaw);
      if (qDay != null) {
        const rowDay = rowCellDateToDayMs(dateText);
        if (rowDay != null && rowDay === qDay) return true;
      }

      return fuzzyMatch(qRaw, fileText) || fuzzyMatch(qRaw, byText);
    }

    // ----------------------------
    // Sorting (unchanged)
    // ----------------------------
    function getCellValue(cell, type) {
      const raw = getCellText(cell);
      if (type === "number") {
        const n = parseFloat(raw.replace(/[^0-9.-]/g, ""));
        return isNaN(n) ? 0 : n;
      }
      if (type === "date") {
        const d = new Date(raw);
        return isNaN(d.getTime()) ? 0 : d.getTime();
      }
      return (raw || "").toString().toLowerCase();
    }

    function detectType(colIndex) {
      if (tableId === "import-logs-table") {
        if ([1,5,6,7,8].includes(colIndex)) return "number";
        if (colIndex === 4) return "date";
        return "string";
      }
      if ([1,3,4,7].includes(colIndex)) return "number";
      return "string";
    }

    // ----------------------------
    // Dropdown search (Course + Barangay + Batch)
    // ----------------------------
    const searchableFields = new Set(["course", "barangay", "batch", "batch_number"]);

    // ✅ PATCH: Build Batch options from the actual table column for BOTH fields
    function populateBatchOptionsFromTable(fieldName) {
      const select = container.querySelector(`.custom-select[data-field="${fieldName}"]`);
      if (!select) return;

      const colIndex = FIELD_TO_COL[fieldName];
      if (colIndex == null) return;

      const optionsWrap = select.querySelector(".custom-options");
      if (!optionsWrap) return;

      const years = new Set();
      getAllRows().forEach(row => {
        const cell = row.children[colIndex];
        const raw = getCellText(cell);
        extractYears(raw).forEach(y => years.add(y));
      });

      if (!years.size) return;

      const sorted = Array.from(years).sort((a,b) => a - b);

      // remove previously injected
      Array.from(optionsWrap.querySelectorAll('.custom-option[data-batch="1"]')).forEach(el => el.remove());

      const removeOpt = optionsWrap.querySelector('.custom-option[data-value="remove"]');

      sorted.forEach(y => {
        const opt = document.createElement("span");
        opt.className = "custom-option";
        opt.dataset.value = String(y);
        opt.dataset.batch = "1";
        opt.innerHTML = `<i class="fa-solid fa-calendar"></i> ${y}`;
        if (removeOpt && removeOpt.parentNode === optionsWrap) {
          optionsWrap.insertBefore(opt, removeOpt.nextSibling);
        } else {
          optionsWrap.appendChild(opt);
        }
      });
    }

    function ensureDropdownSearch(select) {
      const field = select.dataset.field;
      if (!searchableFields.has(field)) return;

      const optionsWrap = select.querySelector(".custom-options");
      if (!optionsWrap) return;
      if (optionsWrap.querySelector("input.dropdown-search")) return;

      const wrap = document.createElement("div");
      wrap.className = "dropdown-search-wrap";

      const label =
        field === "course" ? "course" :
        field === "barangay" ? "barangay" :
        "batch";

      wrap.innerHTML = `
        <input class="dropdown-search" type="text" placeholder="Search ${label}..." />
        <div class="no-option-match">No ${label} found</div>
      `;
      optionsWrap.insertBefore(wrap, optionsWrap.firstChild);

      const input = wrap.querySelector(".dropdown-search");
      const noMatch = wrap.querySelector(".no-option-match");

      function listOptions() {
        return Array.from(optionsWrap.querySelectorAll(".custom-option"))
          .filter(el => (el.dataset.value || "") !== "remove")
          .filter(el => !el.closest(".dropdown-search-wrap"));
      }

      function runFilter() {
        const optionEls = listOptions();
        const qRaw = input.value || "";
        if (!qRaw.trim()) {
          optionEls.forEach(el => (el.style.display = ""));
          noMatch.style.display = "none";
          return;
        }

        let shown = 0;
        optionEls.forEach(el => {
          const valueText = (el.getAttribute("data-value") || "").trim();
          const labelText = (el.textContent || "").trim();

          let ok = false;
          if (field === "course") {
            const expanded = expandCourseQuery(qRaw);
            const optA = courseAcronym(valueText || labelText);
            ok = expanded.some(eq => {
              const eqC = compact(eq);
              return (
                fuzzyMatch(eq, valueText) ||
                fuzzyMatch(eq, labelText) ||
                (optA && (optA === eqC || optA.startsWith(eqC) || eqC.startsWith(optA)))
              );
            });
          } else {
            ok = fuzzyMatch(qRaw, valueText) || fuzzyMatch(qRaw, labelText);
          }

          el.style.display = ok ? "" : "none";
          if (ok) shown++;
        });

        noMatch.style.display = shown === 0 ? "block" : "none";
      }

      input.addEventListener("input", runFilter);

      select.addEventListener("dropdown:open", () => {
        input.value = "";
        listOptions().forEach(el => (el.style.display = ""));
        noMatch.style.display = "none";
        setTimeout(() => input.focus(), 0);
      });
    }

    // ----------------------------
    // Apply search + filter + sort
    // ----------------------------
    function applySearchAndFilterAndSort() {
      const allRows = getAllRows();
      let visibleRows = allRows.slice();

      const queryRaw = searchInput ? searchInput.value.trim() : "";
      if (queryRaw) visibleRows = visibleRows.filter(row => smartRowMatch(row, queryRaw));

      Object.keys(activeFilters).forEach(colIdxStr => {
        const colIdx = parseInt(colIdxStr, 10);
        const filterVal = activeFilters[colIdxStr];
        if (!filterVal || filterVal === "remove") return;

        visibleRows = visibleRows.filter(row => {
          const cellVal = getCellText(row.children[colIdx]);

          // ✅ PATCH: batch filter must match year reliably
          if (isBatchCol(colIdx) && /^\d{4}$/.test(String(filterVal))) {
            return batchCellMatchesYear(cellVal, filterVal);
          }

          // course filter: supports BSIT/BS IT/full name
          const isCourseCol = (tableId !== "import-logs-table") && (colIdx === FIELD_TO_COL.course);
          if (isCourseCol) {
            const expanded = expandCourseQuery(filterVal);
            const cellA = courseAcronym(cellVal);
            const cellC = compact(cellVal);

            return expanded.some(eq => {
              const eqC = compact(eq);
              if (fuzzyMatch(eq, cellVal)) return true;
              if (cellA && (cellA === eqC || cellA.startsWith(eqC) || eqC.startsWith(cellA))) return true;
              if (cellC && (cellC.includes(eqC) || eqC.includes(cellC))) return true;
              return false;
            });
          }

          return fuzzyMatch(filterVal, cellVal);
        });
      });

      if (activeSort && activeSort.colIndex != null) {
        const { colIndex, direction, type } = activeSort;
        visibleRows.sort((a, b) => {
          const A = getCellValue(a.children[colIndex], type);
          const B = getCellValue(b.children[colIndex], type);
          if (direction === "az")   return A.localeCompare(B);
          if (direction === "za")   return B.localeCompare(A);
          if (direction === "asc")  return A - B;
          if (direction === "desc") return B - A;
          return 0;
        });
      }

      allRows.forEach(r => r.classList.add("d-none"));

      if (!visibleRows.length) {
        if (noResultsRow) {
          noResultsRow.classList.remove("d-none");
          tbody.appendChild(noResultsRow);
        }
        if (resultsCount) resultsCount.innerText = "0 Results";
        return;
      }

      if (noResultsRow) noResultsRow.classList.add("d-none");

      let counter = 1;
      visibleRows.forEach(row => {
        row.classList.remove("d-none");
        if (row.children[1]) row.children[1].innerText = counter++;
        tbody.appendChild(row);
      });

      if (noResultsRow) tbody.appendChild(noResultsRow);
      if (resultsCount) resultsCount.innerText = `${visibleRows.length} Results`;
    }

    // ----------------------------
    // Events
    // ----------------------------
    if (searchInput) searchInput.addEventListener("input", applySearchAndFilterAndSort);

    if (sortBtn && sortPanel) {
      sortBtn.addEventListener("click", e => {
        e.stopPropagation();
        const willOpen = !sortPanel.classList.contains("open");

        customSelects.forEach(s => s.classList.remove("open"));
        sortPanel.classList.toggle("open", willOpen);
        sortBtn.classList.toggle("active", willOpen);

        setTopSearchEnabled(!willOpen);
      });
    }

    function isInsideActions(target){
      return !!target.closest(".actions") || !!target.closest(".apply-btn") || !!target.closest(".reset-btn");
    }

    document.addEventListener("click", e => {
      const clickInside = container.contains(e.target);
      if (!clickInside) {
        if (sortPanel) sortPanel.classList.remove("open");
        if (sortBtn) sortBtn.classList.remove("active");
        customSelects.forEach(s => s.classList.remove("open"));
        setTopSearchEnabled(true);
        return;
      }

      if (isInsideActions(e.target)) return;

      if (!e.target.closest(".custom-select")) {
        customSelects.forEach(s => s.classList.remove("open"));
      }
    });

    // ✅ PATCH: stable custom select behavior (delegation per select, works for injected options)
    customSelects.forEach(select => {
      const trigger = select.querySelector(".custom-select-trigger");
      const optionsWrap = select.querySelector(".custom-options");
      if (!trigger || !optionsWrap) return;

      // store original trigger html
      if (!trigger.dataset.originalText) trigger.dataset.originalText = trigger.innerHTML;

      ensureDropdownSearch(select);

      trigger.addEventListener("click", e => {
        e.stopPropagation();
        const wasOpen = select.classList.contains("open");
        customSelects.forEach(s => s.classList.remove("open"));

        if (!wasOpen) {
          select.classList.add("open");
          setTopSearchEnabled(false);
          select.dispatchEvent(new Event("dropdown:open"));
        } else {
          select.classList.remove("open");
          setTopSearchEnabled(true);
        }
      });

      optionsWrap.addEventListener("click", (e) => {
        e.stopPropagation();

        // ignore clicks in sticky search area
        if (e.target.closest(".dropdown-search-wrap")) return;

        const optEl = e.target.closest(".custom-option");
        if (!optEl || !optionsWrap.contains(optEl)) return;

        const val = optEl.dataset.value;
        if (!val) return;

        // ✅ PATCH: remove resets trigger + clears selection
        if (val === "remove") {
          trigger.innerHTML = trigger.dataset.originalText || trigger.innerHTML;
          delete select.dataset.selected;
          select.classList.remove("open");
          setTopSearchEnabled(!(sortPanel && sortPanel.classList.contains("open")));
          return;
        }

        trigger.innerHTML = optEl.innerHTML;
        select.dataset.selected = val;
        select.classList.remove("open");
        setTopSearchEnabled(!(sortPanel && sortPanel.classList.contains("open")));
      });
    });

    // Apply / Reset
    const applyBtn = container.querySelector(".apply-btn");
    const resetBtn = container.querySelector(".reset-btn");

    if (applyBtn) {
      applyBtn.addEventListener("click", e => {
        e.preventDefault();
        e.stopPropagation();

        activeFilters = {};
        activeSort = null;

        customSelects.forEach(select => {
          const selected = select.dataset.selected;
          const field = select.dataset.field;
          if (!selected || selected === "remove") return;
          if (FIELD_TO_COL[field] === undefined) return;

          const colIndex = FIELD_TO_COL[field];

          if (["fullname", "filename", "uploaded_by"].includes(field)) {
            if (selected.endsWith("-az")) activeSort = { colIndex, direction: "az", type: "string" };
            else if (selected.endsWith("-za")) activeSort = { colIndex, direction: "za", type: "string" };
          }
          else if (["idnum", "total_records", "valid_count", "invalid_count", "duplicate_count"].includes(field)) {
            if (selected.endsWith("-asc")) activeSort = { colIndex, direction: "asc", type: "number" };
            else if (selected.endsWith("-desc")) activeSort = { colIndex, direction: "desc", type: "number" };
          }
          else if (field === "uploaded_at") {
            if (selected === "date-asc") activeSort = { colIndex, direction: "asc", type: "date" };
            else if (selected === "date-desc") activeSort = { colIndex, direction: "desc", type: "date" };
          }
          else {
            activeFilters[colIndex] = selected;
          }
        });

        applySearchAndFilterAndSort();
      });
    }

    if (resetBtn) {
      resetBtn.addEventListener("click", e => {
        e.preventDefault();
        e.stopPropagation();

        if (searchInput) searchInput.value = "";
        activeFilters = {};
        activeSort = null;

        customSelects.forEach(sel => {
          const trigger = sel.querySelector(".custom-select-trigger");
          if (trigger && trigger.dataset.originalText) trigger.innerHTML = trigger.dataset.originalText;
          delete sel.dataset.selected;

          const noMatch = sel.querySelector(".no-option-match");
          if (noMatch) noMatch.style.display = "none";
          const dd = sel.querySelector("input.dropdown-search");
          if (dd) dd.value = "";
          sel.querySelectorAll(".custom-option").forEach(o => (o.style.display = ""));
        });

        applySearchAndFilterAndSort();
      });
    }

    // Header click sort (unchanged)
    const headerCells = table.querySelectorAll("thead th");
    headerCells.forEach((th, index) => {
      if (index === 0) return;
      th.style.cursor = "pointer";
      th.addEventListener("click", function () {
        const type = detectType(index);
        const previousDir = th.dataset.sortDirection || "none";
        const newDir = previousDir === "asc" ? "desc" : "asc";

        headerCells.forEach(h => { if (h !== th) delete h.dataset.sortDirection; });
        th.dataset.sortDirection = newDir;

        activeSort = { colIndex: index, direction: newDir, type };
        applySearchAndFilterAndSort();
      });
    });

    // ✅ PATCH: Inject batch options for BOTH contexts
    populateBatchOptionsFromTable("batch");
    populateBatchOptionsFromTable("batch_number");

    applySearchAndFilterAndSort();
  }

  function initAll() {
    document.querySelectorAll(".search-container").forEach(container => {
      universalSearchEngine(container);
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAll);
  } else {
    initAll();
  }
})();
</script>
