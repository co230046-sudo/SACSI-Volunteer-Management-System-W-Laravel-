@php
  /**
   * Create/Edit Event Blade (single file)
   * Fixes:
   * - Event Type dropdown now has searchbar (same pattern as barangay)
   * - JS moved to /assets/create_event/js/script.js
   */

  use Carbon\Carbon;

  $rawErrors = $errors->all();

  $fieldMap = [
    'title' => 'Event Title',
    'start_datetime' => 'Start Date & Time',
    'end_datetime' => 'End Date & Time',
    'location_id' => 'Barangay',
    'district_id' => 'District',
    'venue' => 'Location (Specific Address)',
    'event_type_id' => 'Event Type',
    'max_volunteers' => 'Maximum Volunteers',
    'organizers' => 'Organizers',
    'organizers.name' => 'Organizers',
  ];

  $humanizeMessage = function(string $msg) use ($fieldMap) {
    $msg = preg_replace('/\blocation id\b/i', $fieldMap['location_id'], $msg);

    foreach ($fieldMap as $key => $label) {
      $msg = preg_replace('/\b' . preg_quote($key, '/') . '\b/i', $label, $msg);
    }

    $msg = preg_replace('/^The (.+) field is required\.$/i', 'Please provide: $1.', $msg);
    $msg = preg_replace('/^The (.+) must be a date\.$/i', 'Please use a valid date/time for: $1.', $msg);
    $msg = preg_replace('/^The (.+) must be a number\.$/i', 'Please use a valid number for: $1.', $msg);
    $msg = preg_replace('/^The (.+) must be after (.+)\.$/i', 'Please make sure $1 is after $2.', $msg);
    $msg = preg_replace('/^The (.+) must be after or equal to (.+)\.$/i', 'Please make sure $1 is after $2.', $msg);

    return $msg;
  };

  $detectFieldKey = function(string $msg) use ($fieldMap) {
    $lower = strtolower($msg);
    if (str_contains($lower, 'location id')) return 'location_id';

    foreach (array_keys($fieldMap) as $key) {
      if (str_contains($lower, strtolower($key))) return $key;
    }
    foreach ($fieldMap as $key => $label) {
      if (str_contains($lower, strtolower($label))) return $key;
    }
    return null;
  };

  $missingRequired = [];
  $fixThese = [];

  foreach ($rawErrors as $msg) {
    $pretty = $humanizeMessage($msg);
    $fieldKey = $detectFieldKey($msg);

    if (stripos($msg, 'field is required') !== false) {
      $missingRequired[] = $fieldKey && isset($fieldMap[$fieldKey]) ? $fieldMap[$fieldKey] : $pretty;
      continue;
    }
    $fixThese[] = $pretty;
  }

  $missingRequired = array_values(array_unique(array_filter($missingRequired)));
  $fixThese = array_values(array_unique(array_filter($fixThese)));

  $optionalNotes = [];
  if ($errors->any()) {
    $optionalNotes[] = 'Location (Specific Address) is optional — use it for street/landmark details.';
    $optionalNotes[] = 'Barangay is required (it sets the District automatically).';
    $optionalNotes[] = 'End Date & Time is required — make sure it is after the Start Date & Time.';
    $optionalNotes[] = 'At least one Organizer is required.';
  }

  $dup = session('duplicate_event');

  $eventId = $event->event_id ?? null;

  $valStart = old('start_datetime');
  if ($valStart === null && isset($event?->start_datetime)) {
    $valStart = Carbon::parse($event->start_datetime)->format('Y-m-d\TH:i');
  }

  $valEnd = old('end_datetime');
  if ($valEnd === null && isset($event?->end_datetime)) {
    $valEnd = Carbon::parse($event->end_datetime)->format('Y-m-d\TH:i');
  }

  $valLocationId = old('location_id', $event->location_id ?? '');
  $valDistrictId = old('district_id', $event->district_id ?? '');
  $valTypeId     = old('event_type_id', $event->event_type_id ?? '');

  // Seed trigger labels server-side (so reload keeps dropdown text even if JS fails)
  $selectedBarangayLabel = '';
  if ($valLocationId) {
    $selectedBarangayLabel = optional($locations->firstWhere('location_id', (int)$valLocationId))->barangay ?? '';
  }

  $selectedEventType = null;
  if ($valTypeId) {
    $selectedEventType = $eventTypes->firstWhere('event_type_id', (int)$valTypeId);
  }
  $selectedTypeLabel = $selectedEventType?->label ?? '';

  // Text-only triggers
  $eventTypeTrigger = $selectedTypeLabel
    ? '<span class="cs-left">' . e($selectedTypeLabel) . '</span><span class="cs-caret"><i class="fa-solid fa-chevron-down"></i></span>'
    : '<span class="cs-left">Select Event Type</span><span class="cs-caret"><i class="fa-solid fa-chevron-down"></i></span>';

  $barangayTrigger = $selectedBarangayLabel
    ? '<span class="cs-left"><i class="fa-solid fa-location-dot"></i> ' . e($selectedBarangayLabel) . '</span><span class="cs-caret"><i class="fa-solid fa-chevron-down"></i></span>'
    : '<span class="cs-left">Select Barangay</span><span class="cs-caret"><i class="fa-solid fa-chevron-down"></i></span>';

  // Organizers list (old() takes priority; else event organizers; else 1 blank row)
  $orgOldNames = old('organizers.name');
  $orgOldEmails = old('organizers.email');
  $orgOldContacts = old('organizers.contact');

  $orgList = [];

  if (is_array($orgOldNames)) {
    foreach ($orgOldNames as $i => $n) {
      $orgList[] = [
        'name' => $n ?? '',
        'email' => is_array($orgOldEmails) ? ($orgOldEmails[$i] ?? '') : '',
        'contact' => is_array($orgOldContacts) ? ($orgOldContacts[$i] ?? '') : '',
      ];
    }
  } elseif (isset($event) && $event && $event->relationLoaded('organizers')) {
    foreach ($event->organizers as $o) {
      $orgList[] = [
        'name' => $o->name ?? '',
        'email' => $o->email ?? '',
        'contact' => $o->contact ?? '',
      ];
    }
  }

  if (count($orgList) === 0) {
    $orgList[] = ['name' => '', 'email' => '', 'contact' => ''];
  }
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ ($isEdit ?? false) ? 'Edit Event' : 'Create Event' }}</title>

  <link rel="stylesheet" href="{{ asset('assets/create_event/css/create_event.css') }}">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
@include('layouts.page_loader')
@include('layouts.navbar')
@include('layouts.back_button')

<div class="Wrapper">
  <section class="edit-section" role="region" aria-label="Create/Edit Event">
    <h2>{{ ($isEdit ?? false) ? 'Edit Event' : 'Create Event' }}</h2>
    <div class="page-separator"></div>

    <form id="create-event-form"
          action="{{ ($isEdit ?? false) ? route('events.update', $eventId) : route('events.store') }}"
          method="POST">
      @csrf
      @if($isEdit ?? false)
        @method('PUT')
      @endif

      <input type="hidden" name="force_create" id="force_create" value="0">

      <div class="event-grid-3">
        <!-- COLUMN 1 -->
        <div class="ev-col">
          <div class="volunteer-info hint" data-hint="Required. Give your event a clear name (e.g., Coastal Cleanup Drive).">
            <span class="icon"><i class="fa-solid fa-pencil"></i></span>
            <input type="text"
                   placeholder="Event Title"
                   name="title"
                   value="{{ old('title', $event->title ?? '') }}"
                   required>
          </div>

          <div class="volunteer-info hint" data-hint="Optional. Specific address/venue (street, landmark). Not auto-filled by barangay.">
            <span class="icon"><i class="fa-solid fa-location-dot"></i></span>
            <input type="text"
                   placeholder="Location (Specific Address)"
                   name="venue"
                   value="{{ old('venue', $event->venue ?? '') }}">
          </div>

          <div class="volunteer-info hint" data-hint="Optional. Leave blank if unlimited.">
            <span class="icon"><i class="fa-solid fa-users"></i></span>
            <input type="number"
                   placeholder="Maximum Volunteers"
                   min="0"
                   name="max_volunteers"
                   value="{{ old('max_volunteers', $event->max_volunteers ?? '') }}">
          </div>

          <div class="volunteer-info hint" data-hint="Required. Helps categorize and filter events later.">
            <span class="icon"><i class="fa-solid fa-calendar-check"></i></span>

            <!-- ✅ Event Type now SEARCHABLE -->
            <div class="custom-select searchable" id="event-type-select" data-field="event_type_id">
              <div class="custom-select-trigger">{!! $eventTypeTrigger !!}</div>

              <input type="hidden" name="event_type_id" id="event_type_id_hidden" value="{{ $valTypeId }}">

              <div class="custom-options">
                <!-- ✅ Search box (same UX as barangay) -->
                <div class="search-box">
                  <i class="fa-solid fa-magnifying-glass search-icon"></i>
                  <input type="text" class="selectSearchInput" data-target="event-type-select" placeholder="Search event type...">
                </div>

                {{-- ✅ Manage Event Types --}}
                <span class="custom-option custom-option-add"
                      data-value="__add_event_type__"
                      data-label="Manage Event Types">
                  <i class="fa-solid fa-gear"></i> Manage Event Types
                </span>

                <div style="height: 6px;"></div>

                @foreach ($eventTypes as $type)
                  <span class="custom-option"
                        data-value="{{ $type->event_type_id }}"
                        data-label="{{ $type->label }}">
                    {{ $type->label }}
                  </span>
                @endforeach
              </div>
            </div>
          </div>
        </div>

        <!-- COLUMN 2 -->
        <div class="ev-col">
          <div class="volunteer-info hint" data-hint="Required. This is when the event officially begins.">
            <span class="icon"><i class="fa-solid fa-hourglass-start"></i></span>
            <input type="datetime-local"
                   name="start_datetime"
                   id="start_datetime"
                   class="datetime-input"
                   value="{{ $valStart }}"
                   required>
          </div>

          <div class="volunteer-info hint" data-hint="Required. Must be after the start date/time.">
            <span class="icon"><i class="fa-solid fa-hourglass-end"></i></span>
            <input type="datetime-local"
                   name="end_datetime"
                   id="end_datetime"
                   class="datetime-input"
                   value="{{ $valEnd }}"
                   required>
          </div>

          <div class="volunteer-info hint" data-hint="Required. Select the barangay where the event will happen.">
            <span class="icon"><i class="fa-solid fa-house"></i></span>

            <div class="custom-select searchable" id="barangay-select" data-field="location_id">
              <div class="custom-select-trigger">{!! $barangayTrigger !!}</div>

              <input type="hidden" name="location_id" id="location_id_hidden" value="{{ $valLocationId }}">

              <div class="custom-options">
                <div class="search-box">
                  <i class="fa-solid fa-magnifying-glass search-icon"></i>
                  <input type="text" id="barangaySearchInput" placeholder="Search barangay...">
                </div>

                @foreach ($locations as $loc)
                  <span class="custom-option"
                        data-value="{{ $loc->location_id }}"
                        data-label="{{ $loc->barangay }}"
                        data-district="{{ $loc->district_id }}">
                    <i class="fa-solid fa-location-dot"></i> {{ $loc->barangay }}
                  </span>
                @endforeach
              </div>
            </div>
          </div>

          <div class="volunteer-info hint" data-hint="Auto-filled based on Barangay selection.">
            <span class="icon"><i class="fa-solid fa-map-location-dot"></i></span>
            <input type="text"
                   id="districtDisplay"
                   placeholder="District"
                   value="{{ $valDistrictId ? ('District ' . $valDistrictId) : '' }}"
                   readonly>
            <input type="hidden" name="district_id" id="districtHidden" value="{{ $valDistrictId }}">
          </div>
        </div>

        <!-- COLUMN 3 (ORGANIZERS) -->
        <div class="organizers-col">
          <div class="organizers-head">
            <div class="organizers-title">
              <i class="fa-solid fa-users-gear"></i>
              <span>Organizers</span>
            </div>
            <div class="organizers-sub">Required: at least 1 organizer. Max 3. Use the pencil button for optional details.</div>
            <div class="organizers-actions">
              <button type="button" class="org-manage-btn" id="openManageOrganizersBtn">
                <i class="fa-solid fa-list-check"></i> Manage
              </button>
            </div>
          </div>

          <div class="organizers-stack" id="organizers-wrapper">
            @foreach($orgList as $i => $o)
              <div class="organizer-row">
                <input type="text"
                       name="organizers[name][]"
                       placeholder="Organizer Name"
                       class="organizer-input"
                       value="{{ $o['name'] }}"
                       {{ $i === 0 ? 'required' : '' }}>

                <!-- keep existing onclicks (script.js also supports delegation) -->
                <button type="button" class="org-btn org-btn-ghost" onclick="openOrganizerModal(this)" title="Details">
                  <i class="fa-solid fa-pen-to-square"></i>
                </button>

                <button type="button" class="org-btn org-btn-danger" onclick="removeOrganizer(this)" title="Remove">
                  <i class="fa-solid fa-xmark"></i>
                </button>

                <input type="hidden" name="organizers[email][]" value="{{ $o['email'] }}">
                <input type="hidden" name="organizers[contact][]" value="{{ $o['contact'] }}">
              </div>
            @endforeach
          </div>

          <button type="button" class="org-add-btn" onclick="addOrganizer()">
            <i class="fa-solid fa-plus"></i> Add organizer
          </button>
        </div>
      </div>

      <div class="creator-strip">
        <div class="volunteer-info">
          <span class="icon"><i class="fa-solid fa-user-pen"></i></span>
          <div class="creator-inner">
            <input type="text" value="Uploading as {{ Auth::guard('admin')->user()->username ?? 'Guest' }}" readonly>
          </div>
        </div>
      </div>

      <div class="description-box">
        <textarea name="description" placeholder="Description (optional)">{{ old('description', $event->description ?? '') }}</textarea>
      </div>

      <div class="submit-section">
        <button class="open-modal-btn" type="button" id="open-create-modal-btn">
          @if($isEdit ?? false)
            <i class="fa-solid fa-floppy-disk"></i> Save Changes
          @else
            <i class="fa-solid fa-calendar-plus"></i> Create Event
          @endif
        </button>

        <button class="cancel-btn" type="button" onclick="window.history.back()">
          <i class="fa-solid fa-arrow-left"></i> Back
        </button>
      </div>
    </form>
  </section>
</div>

{{-- ===========================
   MODALS (Bootstrap)
=========================== --}}

{{-- Validation Errors --}}
<div class="modal fade" id="validationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--danger">
        <h5 class="modal-title">
          <i class="fa-solid fa-circle-xmark"></i> Please fix the form
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        @if(count($missingRequired))
          <div class="soft-alert soft-alert-danger">
            <div class="soft-alert-title">
              <i class="fa-solid fa-triangle-exclamation"></i> Missing required information
            </div>
            <ul class="soft-checklist">
              @foreach($missingRequired as $item)
                <li><i class="fa-solid fa-circle"></i> {{ $item }}</li>
              @endforeach
            </ul>
            <div class="soft-alert-sub">
              These fields must be completed before you can continue.
            </div>
          </div>
        @endif

        @if(count($fixThese))
          <div class="soft-block">
            <div class="soft-block-title">
              <i class="fa-solid fa-screwdriver-wrench"></i> Please review
            </div>
            <ul class="soft-list">
              @foreach ($fixThese as $msg)
                <li>{{ $msg }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        @if(count($optionalNotes))
          <div class="soft-note">
            <div class="soft-note-title">
              <i class="fa-solid fa-circle-info"></i> Helpful reminders
            </div>
            <ul class="soft-list">
              @foreach($optionalNotes as $n)
                <li>{{ $n }}</li>
              @endforeach
            </ul>
          </div>
        @endif
      </div>

      <div class="modal-footer modal-soft-footer">
        <button type="button" class="btn modal-soft-btn modal-soft-btn--danger" data-bs-dismiss="modal">
          <i class="fa-solid fa-check"></i> Understood
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Duplicate Event --}}
<div class="modal fade" id="duplicateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--warning">
        <h5 class="modal-title">
          <i class="fa-solid fa-circle-exclamation"></i> Possible duplicate event detected
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <p class="soft-lead">
          An event with the same <b>Title</b>, <b>Barangay</b>, and <b>Start Date & Time</b> already exists.
        </p>

        @if(is_array($dup))
        <div class="soft-card">
          <div><span class="soft-k">Title:</span> {{ $dup['title'] }}</div>
          <div><span class="soft-k">Start:</span> {{ $dup['start'] }}</div>
          <div><span class="soft-k">Barangay:</span> {{ $dup['barangay'] }}</div>
        </div>
        @endif

        <p class="soft-sub">
          If this is the same event, please cancel and review.  
          If it is truly different, you may proceed.
        </p>
      </div>

      <div class="modal-footer modal-soft-footer">
        <button class="btn modal-soft-btn modal-soft-btn--ghost" data-bs-dismiss="modal">
          Review
        </button>
        <button class="btn modal-soft-btn modal-soft-btn--danger" id="dup-confirm-btn">
          Create anyway
        </button>
      </div>
    </div>
  </div>
</div>


{{-- Organizer Details --}}
<div class="modal fade" id="organizerDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--info">
        <h5 class="modal-title">
          <i class="fa-solid fa-circle-info"></i> Organizer Details
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="email" id="orgEmail" placeholder="Email (optional)" class="organizer-detail-input">
        <input type="text" id="orgContact" placeholder="Contact (optional)" class="organizer-detail-input">
      </div>

      <div class="modal-footer modal-soft-footer">
        <button type="button" class="btn modal-soft-btn modal-soft-btn--ghost" data-bs-dismiss="modal">
          <i class="fa-solid fa-xmark"></i> Cancel
        </button>
        <button type="button" class="btn modal-soft-btn modal-soft-btn--danger" id="org-save-btn">
          <i class="fa-solid fa-check"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Organizer Duplicate --}}
<div class="modal fade" id="organizerDuplicateModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--warning">
        <h5 class="modal-title">
          <i class="fa-solid fa-triangle-exclamation"></i> Organizer already exists
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="organizerDuplicateMsg">
        This organizer already exists in your organizer directory.  
        Please select the existing organizer instead of creating a duplicate.
      </div>
      <div class="modal-footer modal-soft-footer">
        <button class="btn modal-soft-btn modal-soft-btn--danger" data-bs-dismiss="modal">
          OK
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Organizer Minimum --}}
<div class="modal fade" id="organizerMinimumModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--warning">
        <h5 class="modal-title">
          <i class="fa-solid fa-circle-exclamation"></i> Organizer Required
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">At least one organizer is required.</div>
      <div class="modal-footer modal-soft-footer">
        <button type="button" class="btn modal-soft-btn modal-soft-btn--danger" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

{{-- Organizer Limit --}}
<div class="modal fade" id="organizerLimitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--warning">
        <h5 class="modal-title">
          <i class="fa-solid fa-ban"></i> Limit Reached
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">Maximum 3 organizers allowed.</div>
      <div class="modal-footer modal-soft-footer">
        <button type="button" class="btn modal-soft-btn modal-soft-btn--danger" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

{{-- Confirm Create/Update --}}
<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--warning">
        <h5 class="modal-title">
          <i class="fa-solid fa-circle-exclamation"></i>
          {{ ($isEdit ?? false) ? 'Confirm Update' : 'Confirm Create' }}
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        {{ ($isEdit ?? false) ? 'This will update the event details.' : 'This will create a new event and redirect you to attendance assignations.' }}
      </div>
      <div class="modal-footer modal-soft-footer">
        <button type="button" class="btn modal-soft-btn modal-soft-btn--ghost" data-bs-dismiss="modal">
          <i class="fa-solid fa-xmark"></i> Cancel
        </button>
        <button type="button" class="btn modal-soft-btn modal-soft-btn--danger" id="confirm-create-btn">
          <i class="fa-solid fa-check"></i> Yes, {{ ($isEdit ?? false) ? 'Update' : 'Create' }}
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Manage Event Types (your existing modal kept) --}}
<div class="modal fade" id="eventTypeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:760px;">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--info">
        <h5 class="modal-title">
          <i class="fa-solid fa-gear"></i> Manage Event Types
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="org-manage-top">
          <div class="org-manage-search" style="flex:1;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="eventTypeManageSearch" placeholder="Search event type...">
          </div>

          <button type="button" class="btn modal-soft-btn modal-soft-btn--danger" id="eventTypeAddNewBtn">
            <i class="fa-solid fa-plus"></i> Add New
          </button>
        </div>

        <div id="eventTypeManageList" class="org-manage-list"></div>

        <div class="soft-sub mt-2">
          Tip: delete is blocked if the type is already used by events.
        </div>
      </div>

      <div class="modal-footer modal-soft-footer">
        <button type="button" class="btn modal-soft-btn modal-soft-btn--ghost" data-bs-dismiss="modal">
          <i class="fa-solid fa-xmark"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Event Type Success --}}
<div class="modal fade" id="eventTypeSuccessModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--info">
        <h5 class="modal-title">
          <i class="fa-solid fa-circle-check"></i> Event type saved successfully
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="soft-lead">{{ session('submit_success') }}</div>
        <div class="soft-sub">
          You can now select this event type from the dropdown.
        </div>
      </div>
      <div class="modal-footer modal-soft-footer">
        <button class="btn modal-soft-btn modal-soft-btn--danger" data-bs-dismiss="modal">
          OK
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Organizer Saved --}}
<div class="modal fade" id="organizerSavedModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--info">
        <h5 class="modal-title">
          <i class="fa-solid fa-circle-check"></i> Organizer updated successfully
        </h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="organizerSavedMsg">
        Organizer information has been saved successfully.
      </div>
      <div class="modal-footer modal-soft-footer">
        <button class="btn modal-soft-btn modal-soft-btn--danger" data-bs-dismiss="modal">
          OK
        </button>
      </div>
    </div>
  </div>
</div>
{{-- Manage Organizers --}}
<div class="modal fade" id="manageOrganizersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width:760px;">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--info">
        <h5 class="modal-title">
          <i class="fa-solid fa-users-gear"></i> Manage Organizers
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="org-manage-top">
          <div class="org-manage-search" style="flex:1;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="orgManageSearch" placeholder="Search organizer name / email / contact...">
          </div>
        </div>
        <!-- Slot selector (Option B) -->
        <div class="org-slotbar" id="orgSlotBar" style="margin: 14px 0 10px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
          <div style="font-weight:900; opacity:.75;">Assign to:</div>
          <div class="btn-group" role="group" aria-label="Organizer slots">
            <button type="button" class="btn btn-outline-danger org-slot-btn active" data-slot="0">
              Organizer 1
            </button>
            <button type="button" class="btn btn-outline-danger org-slot-btn" data-slot="1">
              Organizer 2
            </button>
            <button type="button" class="btn btn-outline-danger org-slot-btn" data-slot="2">
              Organizer 3
            </button>
          </div>
          <div class="soft-sub" style="margin-left:auto;">
            Tip: Select a slot, then click an organizer to assign.
          </div>
        </div>
        
        <div id="orgManageList" class="org-manage-list"></div>
      </div>
      <div class="modal-footer modal-soft-footer">
        <button type="button" class="btn modal-soft-btn modal-soft-btn--ghost" data-bs-dismiss="modal">
          <i class="fa-solid fa-xmark"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="softActionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--warning" id="softActionHeader">
        <h5 class="modal-title" id="softActionTitle">
          <i class="fa-solid fa-triangle-exclamation"></i> Notice
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="softActionBody">
        Something happened.
      </div>
      <div class="modal-footer modal-soft-footer">
        <button type="button" class="btn modal-soft-btn modal-soft-btn--danger" data-bs-dismiss="modal">
          <i class="fa-solid fa-check"></i> Ok
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="softConfirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--warning" id="softConfirmHeader">
        <h5 class="modal-title" id="softConfirmTitle">
          <i class="fa-solid fa-triangle-exclamation"></i> Confirm
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="softConfirmBody">Are you sure?</div>
      <div class="modal-footer modal-soft-footer">
        <button type="button" class="btn modal-soft-btn modal-soft-btn--ghost" id="softConfirmCancel" data-bs-dismiss="modal">
          Cancel
        </button>
        <button type="button" class="btn modal-soft-btn modal-soft-btn--danger" id="softConfirmOk">
          Yes
        </button>
      </div>
    </div>
  </div>
</div>
<div class="modal fade" id="eventTypeAddModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:520px;">
    <div class="modal-content modal-soft">
      <div class="modal-header modal-soft-header modal-soft-header--info">
        <h5 class="modal-title">
          <i class="fa-solid fa-plus"></i> Add Event Type
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="soft-sub mb-2">Enter a label for the new event type.</div>
        <input type="text"
              id="eventTypeAddLabel"
              class="form-control"
              placeholder="e.g., Coastal Cleanup, Seminar, Tree Planting"
              autocomplete="off">
        <div class="soft-sub mt-2" style="opacity:.8;">
          Tip: Keep it short and consistent (Title Case).
        </div>
      </div>
      <div class="modal-footer modal-soft-footer">
        <button type="button" class="btn modal-soft-btn modal-soft-btn--ghost" data-bs-dismiss="modal">
          <i class="fa-solid fa-xmark"></i> Cancel
        </button>
        <button type="button" class="btn modal-soft-btn modal-soft-btn--danger" id="eventTypeAddSaveBtn">
          <i class="fa-solid fa-check"></i> Save
        </button>
      </div>
    </div>
  </div>
</div>

{{--  Auto-open modals --}}
@if ($errors->any())
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const m = new bootstrap.Modal(document.getElementById('validationModal'));
    m.show();
  });
</script>
@endif
@if (session()->has('duplicate_event') && !($isEdit ?? false))
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const m = new bootstrap.Modal(document.getElementById('duplicateModal'));
    m.show();
  });
</script>
@endif
@if(session('submit_success'))
<script>
  document.addEventListener("DOMContentLoaded", () => {
    const m = new bootstrap.Modal(document.getElementById('eventTypeSuccessModal'));
    m.show();
  });
</script>
@endif

<script>
window.ORGANIZERS_API_URL = "{{ route('organizers.index') }}";
window.ORGANIZER_UPDATE_URL = "{{ url('/events/organizers') }}";
window.ORGANIZER_DELETE_URL = "{{ url('/events/organizers') }}";
window.EVENT_TYPES_API_URL = "{{ route('event-types.json') }}";
window.EVENT_TYPE_UPDATE_URL = "{{ url('/events/event-types') }}";
window.EVENT_TYPE_DELETE_URL = "{{ url('/events/event-types') }}";
window.EVENT_TYPE_STORE_URL  = "{{ route('event-types.store') }}";
window.CSRF_TOKEN = "{{ csrf_token() }}";
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('assets/create_event/js/script.js') }}"></script>

</body>
</html>
