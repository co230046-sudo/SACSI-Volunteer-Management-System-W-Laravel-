@php
    $idPrefix = $idPrefix ?? 'expected';

    $barId     = $barId     ?? ($idPrefix . '-search-bar');
    $searchId  = $searchId  ?? ($idPrefix . '-search');
    $toggleId  = $toggleId  ?? ($idPrefix . '-sort-toggle');
    $panelId   = $panelId   ?? ($idPrefix . '-sort-panel');
    $resetId   = $resetId   ?? ($idPrefix . '-reset-btn');
    $applyId   = $applyId   ?? ($idPrefix . '-apply-btn');
    $resultsId = $resultsId ?? ($idPrefix . '-results-count');

    $placeholder = $placeholder ?? 'Search...';
    $enableSmartDayTime = $enableSmartDayTime ?? true;

    $scheduleOptions = $scheduleOptions ?? [
        "7:30-8:20 AM","8:00-9:20 AM","8:00-10:50 AM","8:30-9:50 AM","8:30-11:30 AM","9:30-10:50 AM","11:00-12:20 AM",
        "12:30-1:50 PM","12:30-2:50 PM","2:00-3:20 PM","2:00-4:50 PM","3:30-4:50 PM","5:00-6:20 PM","6:30-7:20 PM",
        "6:30-8:50 PM","7:30-8:50 PM",
    ];
@endphp

<div class="search-container" id="{{ $barId }}" data-prefix="{{ $idPrefix }}">
  <div class="search-row">
    <div class="search-box">
      <input
        type="text"
        class="table-search"
        id="{{ $searchId }}"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
      >
      <span class="icon"><i class="fas fa-search"></i></span>
    </div>

    @if(!empty($resultsId))
      <div class="results-count" id="{{ $resultsId }}">0</div>
    @endif

    <div class="sort-by" id="{{ $toggleId }}" role="button" tabindex="0" aria-expanded="false">
      <span class="label">Filter &amp; Sort</span>
      <i class="fa-solid fa-filter filter-icon"></i>
      <span class="icon">⏷</span>
    </div>
  </div>

  <div class="sort-options" id="{{ $panelId }}" aria-hidden="true">
    <div class="custom-select" data-field="sort">
      <div class="custom-select-trigger"
           role="button"
           tabindex="0"
           data-original-text="<i class='fa-solid fa-arrow-down-wide-short'></i> Sort">
        <i class="fa-solid fa-arrow-down-wide-short"></i> Sort
      </div>

      <div class="custom-options" data-field="sort">
        <span class="custom-option" data-value="name_asc"><i class="fa-solid fa-user"></i> Name (A → Z)</span>
        <span class="custom-option" data-value="name_desc"><i class="fa-solid fa-user"></i> Name (Z → A)</span>
        <span class="custom-option" data-value="course_asc"><i class="fa-solid fa-graduation-cap"></i> Course (A → Z)</span>
        <span class="custom-option" data-value="course_desc"><i class="fa-solid fa-graduation-cap"></i> Course (Z → A)</span>
        <span class="custom-option" data-value="remove"><i class="fa-solid fa-ban"></i> Remove Sort (default)</span>
      </div>
    </div>

    <div class="actions">
      <div class="right-actions">
        <button type="button" class="reset-btn" id="{{ $resetId }}">
          <i class="fa-solid fa-rotate-left"></i> Reset
        </button>
        <button type="button" class="apply-btn" id="{{ $applyId }}">
          <i class="fa-solid fa-check"></i> Apply
        </button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const prefix = @json($idPrefix);
  const enableSmart = @json((bool)$enableSmartDayTime);

  const root   = document.getElementById(@json($barId));
  const toggle = document.getElementById(@json($toggleId));
  const panel  = document.getElementById(@json($panelId));
  const input  = document.getElementById(@json($searchId));
  const reset  = document.getElementById(@json($resetId));
  const apply  = document.getElementById(@json($applyId));

  if (!root || !toggle || !panel || !input || !reset || !apply) return;

  const DEFAULT_SORT = "remove";   // IMPORTANT: no-sort default
  let openSelect = null;

  const TIME_BLOCKS = @json(array_values($scheduleOptions));
  const DAY_NAMES = ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"];

  function debounce(fn, wait = 250){
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), wait); };
  }

  function closeAllSelects(){
    root.querySelectorAll(".custom-select").forEach(s => s.classList.remove("open"));
    openSelect = null;
  }

  function currentSortValue(){
    const select = root.querySelector(".custom-select[data-field='sort']");
    const v = select?.dataset?.value;
    return v ? v : DEFAULT_SORT;
  }

  function markActiveOption(select, value){
    select.querySelectorAll(".custom-option").forEach(o => {
      o.classList.toggle("is-active", (o.dataset.value === value));
    });
  }

  // Ghost input (smart day/time suggestion)
  let ghostInput = null;
  let currentSuggestion = "";

  function setupGhostInput(){
    if (!enableSmart) return;

    const wrapper = input.closest(".search-box");
    if (!wrapper) return;

    wrapper.style.position = "relative";

    ghostInput = document.createElement("input");
    ghostInput.type = "text";
    ghostInput.className = "search-ghost-input";
    ghostInput.setAttribute("aria-hidden", "true");
    ghostInput.tabIndex = -1;

    wrapper.insertBefore(ghostInput, input);
  }

  function buildSuggestion(raw){
    if (!enableSmart) return "";
    const text = (raw || "");
    if (!text.trim()) return "";
    const lower = text.toLowerCase();

    let matchedDay = null;
    let dayEndIdx = -1;

    for (const d of DAY_NAMES){
      const full = d.toLowerCase();
      const short = full.slice(0,3);
      let idx = lower.indexOf(full);
      let len = full.length;
      if (idx === -1){ idx = lower.indexOf(short); len = short.length; }
      if (idx !== -1){ matchedDay = d; dayEndIdx = idx + len; break; }
    }

    let searchArea = text;
    if (matchedDay && dayEndIdx !== -1) searchArea = text.substring(dayEndIdx);

    const timeMatch = searchArea.match(/(\d{1,2}\s*:\s*\d{0,2})/);
    const timeFragment = timeMatch ? timeMatch[1].replace(/\s+/g, "") : null;

    let bestBlock = null;
    if (timeFragment){
      for (const block of TIME_BLOCKS){
        const mainPart = block.replace(/\s+(AM|PM)$/i, "");
        if (mainPart.startsWith(timeFragment)){ bestBlock = block; break; }
      }
    }

    if (matchedDay && bestBlock){
      const typedTrim = text.trimEnd();
      const target = matchedDay + " " + bestBlock;
      if (target.toLowerCase().startsWith(typedTrim.toLowerCase())) return target;
      return typedTrim + " " + bestBlock;
    }

    if (!matchedDay && bestBlock){
      const typedTrim = text.trimEnd();
      const target = bestBlock;
      if (target.toLowerCase().startsWith(typedTrim.toLowerCase())) return target;
      return typedTrim + (typedTrim.endsWith(" ") ? "" : " ") + bestBlock;
    }

    return "";
  }

  function updateGhostSuggestion(){
    if (!ghostInput || !enableSmart) return;
    const raw = input.value || "";
    const suggestion = buildSuggestion(raw);
    currentSuggestion = suggestion || "";
    ghostInput.value = (!suggestion || suggestion.toLowerCase() === raw.toLowerCase()) ? "" : suggestion;
  }

  function emitUpdate(payload){
    // keep your page JS behavior (it reads window.__EXPECTED_SORT / __MODAL_SORT)
    const key = "__" + String(prefix || "").toUpperCase() + "_SORT";
    window[key] = payload.sort;

    window.dispatchEvent(new CustomEvent("eventDetailsSearchBar:apply", {
      detail: { prefix, ...payload }
    }));
  }

  function getPayload(){
    return { search: input.value || "", sort: currentSortValue() };
  }

  function setPanelOpen(open){
    panel.classList.toggle("open", open);
    toggle.classList.toggle("active", open);
    toggle.setAttribute("aria-expanded", open ? "true" : "false");
    panel.setAttribute("aria-hidden", open ? "false" : "true");
    if (!open) closeAllSelects();
  }

  // Toggle panel (click + keyboard)
  toggle.addEventListener("click", (e) => {
    e.stopPropagation();
    setPanelOpen(!panel.classList.contains("open"));
  });

  toggle.addEventListener("keydown", (e) => {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      setPanelOpen(!panel.classList.contains("open"));
    }
  });

  document.addEventListener("click", (e) => {
    if (!root.contains(e.target)){
      setPanelOpen(false);
    }
  });

  // Custom select behavior
  root.querySelectorAll(".custom-select").forEach(select => {
    const trigger = select.querySelector(".custom-select-trigger");
    const options = Array.from(select.querySelectorAll(".custom-option"));
    if (!trigger) return;

    function setSelectOpen(open){
      select.classList.toggle("open", open);
      openSelect = open ? select : null;
    }

    trigger.addEventListener("click", (e) => {
      e.stopPropagation();
      if (openSelect && openSelect !== select) closeAllSelects();
      setSelectOpen(!select.classList.contains("open"));
    });

    trigger.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        e.stopPropagation();
        if (openSelect && openSelect !== select) closeAllSelects();
        setSelectOpen(!select.classList.contains("open"));
      }
    });

    options.forEach(opt => {
      opt.addEventListener("click", (e) => {
        e.stopPropagation();
        const val = opt.dataset.value || DEFAULT_SORT;
        select.dataset.value = val;
        trigger.innerHTML = opt.innerHTML;
        markActiveOption(select, val);
        setSelectOpen(false);
      });
    });
  });

  apply.addEventListener("click", () => {
    emitUpdate(getPayload());
    setPanelOpen(false);
  });

  reset.addEventListener("click", () => {
    input.value = "";
    updateGhostSuggestion();

    root.querySelectorAll(".custom-select").forEach(select => {
      const trigger = select.querySelector(".custom-select-trigger");
      if (trigger?.dataset?.originalText) trigger.innerHTML = trigger.dataset.originalText;
      select.dataset.value = DEFAULT_SORT;
      markActiveOption(select, DEFAULT_SORT);
    });

    emitUpdate({ search: "", sort: DEFAULT_SORT });
  });

  input.addEventListener("input", () => updateGhostSuggestion());
  input.addEventListener("input", debounce(() => emitUpdate(getPayload()), 250));

  input.addEventListener("keydown", (e) => {
    if (!enableSmart) return;
    if (e.key === "Tab" && currentSuggestion){
      const raw = input.value || "";
      if (currentSuggestion.toLowerCase().startsWith(raw.toLowerCase())){
        e.preventDefault();
        input.value = currentSuggestion;
        updateGhostSuggestion();
        emitUpdate(getPayload());
      }
    }
  });

  // init
  setupGhostInput();
  updateGhostSuggestion();

  const sortSelect = root.querySelector(".custom-select[data-field='sort']");
  if (sortSelect){
    sortSelect.dataset.value = sortSelect.dataset.value || DEFAULT_SORT;
    markActiveOption(sortSelect, sortSelect.dataset.value);
  }

  // IMPORTANT: first emit locks default state ("remove", no sort)
  emitUpdate({ search: "", sort: DEFAULT_SORT });
})();
</script>
