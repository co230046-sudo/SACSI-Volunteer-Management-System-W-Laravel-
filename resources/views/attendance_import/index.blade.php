<?php $pageTitle = 'Attendance Import'; ?>

@php
  $hasRoster = $event->expectedVolunteers()->count() > 0;
  $isCompleted = ($event->status ?? null) === 'completed';
@endphp


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $pageTitle }}</title>

  <link rel="stylesheet" href="{{ asset('assets/attendance_import/css/styles.css') }}">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;600;700;800;900&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>

<body>
  @include('layouts.page_loader')
  @include('layouts.navbar')
  @include('layouts.back_button')

  {{-- Quick Tips Drawer Toggle --}}
  <button type="button" class="tips-fab" id="tipsToggle" aria-controls="tipsDrawer" aria-expanded="true">
    <i class="fa-solid fa-lightbulb"></i>
    <span>Quick tips</span>
    <i class="fa-solid fa-chevron-right tips-caret" id="tipsCaret"></i>
  </button>

  {{-- Quick Tips Drawer --}}
  <aside class="tips-drawer" id="tipsDrawer">
    <div class="tips-head">
      <div class="tips-title">
        <i class="fa-solid fa-lightbulb"></i> Quick tips
      </div>

      <button type="button" class="tips-close" id="tipsClose" aria-label="Close tips">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="tips-sub">Before uploading</div>

    <div class="guide-list mt-3">
      <div class="guide-item">
        <div class="guide-ico"><i class="fa-solid fa-file-csv"></i></div>
        <div>
          <div class="guide-title">Use the Google Form CSV</div>
          <div class="guide-sub">Export responses as CSV and upload here.</div>
        </div>
      </div>

      <div class="guide-item">
        <div class="guide-ico"><i class="fa-solid fa-hashtag"></i></div>
        <div>
          <div class="guide-title">Event code must match</div>
          <div class="guide-sub">Wrong codes become invalid rows.</div>
        </div>
      </div>

      <div class="guide-item">
        <div class="guide-ico"><i class="fa-solid fa-pen-to-square"></i></div>
        <div>
          <div class="guide-title">Fix inside Preview</div>
          <div class="guide-sub">Use Edit/Delete actions—no need to edit the CSV.</div>
        </div>
      </div>

      <div class="guide-item">
        <div class="guide-ico"><i class="fa-solid fa-ban"></i></div>
        <div>
          <div class="guide-title">Re-import is blocked</div>
          <div class="guide-sub">If already imported for this event, row is tagged and skipped.</div>
        </div>
      </div>
    </div>

    <div class="tips-note">
      Need help? Open <span class="soft-strong">Import Guide</span>.
    </div>
  </aside>

  <section class="import-wrap">
    <div class="import-card">

      {{-- TOP --}}
      <header class="import-top">
        <div class="top-left">
          <div class="page-kicker">
            <i class="fa-solid fa-file-import"></i>
            Attendance Import
          </div>

          <div class="title-row">
            <h1 class="page-h1">{{ $event->title }}</h1>

            <button type="button"
                    class="chip chip-btn"
                    id="eventCodeCopy"
                    data-code="{{ $event->event_code }}"
                    title="Copy Event Code">
              <i class="fa-solid fa-hashtag"></i>
              Code: <span class="chip-strong">{{ $event->event_code }}</span>
              <span class="chip-ico"><i class="fa-regular fa-copy"></i></span>
            </button>

            <span class="copy-toast" id="copyToast" role="status" aria-live="polite">Copied!</span>
          </div>

          <div class="subchips">
            <span class="subchip">
              <i class="fa-regular fa-calendar"></i>
              {{ $event->start_datetime?->format('F d, Y') ?? 'Date TBA' }}
            </span>
            <span class="subchip">
              <i class="fa-solid fa-location-dot"></i>
              {{ $event->venue ?: '—' }}
            </span>
            <span class="subchip">
              <i class="fa-solid fa-map"></i>
              {{ $event->location?->barangay ?? 'No barangay set' }}
            </span>

            {{-- ✅ Show status chip (nice UX with new gating) --}}
            <span class="subchip">
              <i class="fa-solid fa-circle-info"></i>
              Status: <span class="soft-strong">{{ strtoupper($event->status ?? '—') }}</span>
            </span>
          </div>
        </div>

        <div class="top-right">
          <button type="button" class="btn-softlink" data-bs-toggle="modal" data-bs-target="#importGuideModal">
            <i class="fa-regular fa-circle-question"></i> Import Guide
          </button>

          <a class="btn-softlink" href="{{ route('event.details.show', $event->event_id) }}">
            <i class="fa-regular fa-eye"></i> Back to Event
          </a>
        </div>
      </header>

      <div class="import-body">
        <div class="import-layout">
          <div class="import-main">

            {{-- Upload Panel --}}
            <div class="panel">
              <div class="panel-head">
                <div>
                  <div class="panel-title"><i class="fa-solid fa-upload"></i> Upload CSV</div>
                  <div class="panel-sub">Preview first before saving</div>
                </div>
              </div>

              {{-- ✅ Optional: frontend hint (backend still enforces) --}}
              @if(!$isCompleted)
                <div class="hint-line hint-line--danger">
                  Import is locked unless Event Status is <span class="soft-strong">COMPLETED</span>.
                  Current: <span class="soft-strong">{{ strtoupper($event->status ?? '—') }}</span>
                </div>
              @elseif(!$hasRoster)
                <div class="hint-line hint-line--danger">
                  Import is locked because this event has <span class="soft-strong">no expected volunteers</span> in the roster.
                </div>
              @endif


              <form
                action="{{ route('attendance.import.preview', $event->event_id) }}"
                method="POST"
                enctype="multipart/form-data"
                class="upload-form"
              >
                @csrf

                <div class="upload-row">
                  <label class="file-pill" for="csvFile">
                    <span class="file-pill__btn"><i class="fa-regular fa-file-lines"></i> Choose file</span>
                    <span class="file-pill__name" id="fileName">No file selected</span>
                    <input id="csvFile" type="file" name="csv_file" accept=".csv,.txt" required
                           {{ (!$isCompleted || !$hasRoster) ? 'disabled' : '' }}>
                  </label>

                  <button class="btn-export" type="submit" {{ (!$isCompleted || !$hasRoster) ? 'disabled' : '' }}>
                    <i class="fa-solid fa-magnifying-glass"></i> Preview Import
                  </button>

                  @if(!empty($preview))
                    <button class="btn-export btn-export--warn" type="button" onclick="document.getElementById('resetForm').submit()">
                      <i class="fa-solid fa-rotate-left"></i> Reset Preview
                    </button>
                  @endif
                </div>
              </form>

              <form id="resetForm" action="{{ route('attendance.import.reset', $event->event_id) }}" method="POST" class="d-none">
                @csrf
              </form>

              <div class="hint-line">
                CSV must include: Event Access Code, Full Name, School ID, School Email Address, Attendance Confirmation (Present/Walk-in). Ratings + comments optional.
              </div>
            </div>

            @if(!empty($preview))
              @php
                $counts = $preview['counts'] ?? ['total'=>0,'valid'=>0,'invalid'=>0,'duplicate'=>0,'already_imported'=>0];

                $validRows = $preview['valid'] ?? [];
                $invalidRows = $preview['invalid'] ?? [];
                $dupRows = $preview['duplicate'] ?? [];
                $alreadyRows = $preview['already_imported'] ?? [];

                $courseOptions = collect($validRows)
                  ->merge($invalidRows)
                  ->merge($alreadyRows)
                  ->map(fn($r) => trim((string)($r['course'] ?? '')))
                  ->filter()
                  ->unique()
                  ->sort()
                  ->values();
              @endphp

              {{-- Preview Summary --}}
              <div class="panel">
                <div class="preview-head">
                  <div>
                    <div class="preview-kicker">Preview Batch</div>
                    <div class="preview-meta">
                      <span class="td-mono">{{ $preview['batch'] ?? '—' }}</span>
                      <span class="dot">•</span>
                      <span class="td-muted">File: {{ $preview['filename'] ?? '—' }}</span>
                    </div>
                  </div>

                  <div class="badges-row">
                    <span class="mini-badge"><i class="fa-solid fa-list"></i> Total: {{ $counts['total'] ?? 0 }}</span>
                    <span class="mini-badge mini-badge--ok"><i class="fa-solid fa-check"></i> Valid: {{ $counts['valid'] ?? 0 }}</span>
                    <span class="mini-badge mini-badge--bad"><i class="fa-solid fa-triangle-exclamation"></i> Invalid: {{ $counts['invalid'] ?? 0 }}</span>
                    <span class="mini-badge mini-badge--warn"><i class="fa-solid fa-clone"></i> Duplicate (CSV): {{ $counts['duplicate'] ?? 0 }}</span>
                    <span class="mini-badge mini-badge--info"><i class="fa-solid fa-ban"></i> Already Imported: {{ $counts['already_imported'] ?? 0 }}</span>
                  </div>
                </div>

                <div class="divider-lite"></div>

                <div class="save-row">
                  <form action="{{ route('attendance.import.commit', $event->event_id) }}" method="POST">
                    @csrf
                    <button class="btn-save" {{ ($counts['valid'] ?? 0) <= 0 || !$hasRoster ? 'disabled' : '' }}>
                      <i class="fa-solid fa-floppy-disk"></i> Save Import
                    </button>
                  </form>

                  <div class="save-note">
                    Save will <span class="soft-strong">create new attendance</span> for this event.
                    Rows tagged as <span class="soft-strong">Already Imported</span> are skipped (no overwrite).
                  </div>
                </div>

                @if(!$hasRoster)
                  <div class="hint-line hint-line--danger mt-2">
                    ⚠ This preview cannot be saved because the event has no expected volunteers in the roster.
                  </div>
                @endif

                <div class="divider-lite"></div>

                {{-- Filter + Sort --}}
                <div class="filterbar">
                  <div class="search-pill">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input id="previewSearch" class="search-input" type="text" placeholder="Search (name / id / email)">
                  </div>

                  <div class="sort-pill">
                    <i class="fa-solid fa-filter"></i>
                    <select id="filterConfirm" class="sort-select">
                      <option value="">Confirm: All</option>
                      <option value="present">Confirm: Present</option>
                      <option value="walk-in">Confirm: Walk-in</option>
                    </select>
                  </div>

                  <div class="sort-pill">
                    <i class="fa-solid fa-graduation-cap"></i>
                    <select id="filterCourse" class="sort-select">
                      <option value="">Course: All</option>
                      @foreach($courseOptions as $c)
                        <option value="{{ strtolower($c) }}">{{ $c }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="sort-pill">
                    <i class="fa-solid fa-arrow-down-a-z"></i>
                    <select id="previewSort" class="sort-select">
                      <option value="name_asc">Sort: Name (A–Z)</option>
                      <option value="name_desc">Sort: Name (Z–A)</option>
                    </select>
                  </div>

                  <button class="btn-mini" type="button" id="clearFilters">
                    <i class="fa-solid fa-broom"></i> Clear
                  </button>
                </div>
              </div>

              {{-- VALID --}}
              <div class="panel" id="panelValid">
                <button class="panel-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#validBlock" aria-expanded="true">
                  <div class="panel-title">
                    <i class="fa-solid fa-check-circle"></i> Valid Rows
                    <span class="count-pill" id="countValid">{{ count($validRows) }}</span>
                  </div>
                  <i class="fa-solid fa-chevron-down caret rot"></i>
                </button>

                <div class="collapse show" id="validBlock">
                  <div class="table-shell mt-3">
                    <table class="table-lite" data-table="valid">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Volunteer</th>
                          <th>Name</th>
                          <th>Course</th>
                          <th>School ID</th>
                          <th>Email</th>
                          <th>Confirm</th>
                          <th>Rating</th>
                          <th>Comments</th>
                          <th class="th-actions">Actions</th>
                        </tr>
                      </thead>
                      <tbody id="tbodyValid">
                        @forelse($validRows as $v)
                          @php
                            $isWalkIn = (bool)($v['walk_in'] ?? false);
                            $course = $v['course'] ?? null;
                            $hasComments = !empty($v['feedback_text']) || !empty($v['improve_next_time']) || !empty($v['issues_encountered']) || !empty($v['other_comments']);
                            $avatar = $v['avatar_url'] ?? asset('storage/defaults/default_user.png');
                          @endphp

                          <tr
                            data-row="{{ $v['row'] ?? '' }}"
                            data-name="{{ strtolower($v['full_name'] ?? '') }}"
                            data-email="{{ strtolower($v['school_email'] ?? '') }}"
                            data-id="{{ strtolower($v['school_id'] ?? '') }}"
                            data-confirm="{{ $isWalkIn ? 'walk-in' : 'present' }}"
                            data-course="{{ strtolower((string)$course) }}"
                          >
                            <td class="td-mono">{{ $v['row'] ?? '' }}</td>

                            <td>
                              @if(!empty($v['volunteer_id']) && !empty($v['profile_url']))
                                <a class="v-mini" href="{{ $v['profile_url'] }}" target="_blank" rel="noopener">
                                  <img class="v-avatar" src="{{ $avatar }}" alt="avatar">
                                  <span class="v-pill"><i class="fa-solid fa-link"></i> Matched</span>
                                </a>
                              @elseif($isWalkIn)
                                <div class="v-mini">
                                  <img class="v-avatar" src="{{ asset('storage/defaults/default_user.png') }}" alt="avatar">
                                  <span class="v-pill v-pill--muted"><i class="fa-solid fa-person-walking"></i> Walk-in</span>
                                </div>
                              @else
                                <div class="v-mini">
                                  <img class="v-avatar" src="{{ asset('storage/defaults/default_user.png') }}" alt="avatar">
                                  <span class="v-pill v-pill--muted"><i class="fa-regular fa-id-badge"></i> No match</span>
                                </div>
                              @endif
                            </td>

                            <td class="td-strong">{{ $v['full_name'] ?? '' }}</td>
                            <td class="td-muted">{{ $course ?: '—' }}</td>
                            <td class="td-mono">{{ $v['school_id'] ?? '' }}</td>
                            <td class="td-muted">{{ $v['school_email'] ?? '' }}</td>

                            <td>
                              <span class="badge-pill {{ $isWalkIn ? 'badge-pill--warn' : 'badge-pill--ok' }}">
                                <i class="fa-solid {{ $isWalkIn ? 'fa-person-walking' : 'fa-user-check' }}"></i>
                                {{ $isWalkIn ? 'Walk-in' : 'Present' }}
                              </span>
                            </td>

                            <td class="td-mono">{{ $v['rating'] ?? '-' }}</td>

                            <td>
                              <button class="btn-mini btn-mini--ghost js-comments"
                                      type="button"
                                      {{ $hasComments ? '' : 'disabled' }}
                                      data-row='@json($v)'>
                                <i class="fa-regular fa-message"></i> View
                              </button>
                            </td>

                            <td class="td-actions">
                              <button class="btn-mini js-edit" type="button" data-row='@json($v)'>
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                              </button>

                              <button class="btn-mini btn-mini--danger js-delete"
                                      type="button"
                                      data-row-number="{{ (int)($v['row'] ?? 0) }}"
                                      data-bucket="valid">
                                <i class="fa-solid fa-trash"></i> Delete
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="10" class="td-empty">No valid rows.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              {{-- INVALID --}}
              <div class="panel" id="panelInvalid">
                <button class="panel-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#invalidBlock" aria-expanded="false">
                  <div class="panel-title">
                    <i class="fa-solid fa-triangle-exclamation"></i> Invalid Rows
                    <span class="count-pill" id="countInvalid">{{ count($invalidRows) }}</span>
                  </div>
                  <i class="fa-solid fa-chevron-down caret"></i>
                </button>

                <div class="collapse" id="invalidBlock">
                  <div class="table-shell mt-3">
                    <table class="table-lite" data-table="invalid">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Name</th>
                          <th>Course</th>
                          <th>School ID</th>
                          <th>Email</th>
                          <th>Confirm</th>
                          <th>What to fix</th>
                          <th class="th-actions">Actions</th>
                        </tr>
                      </thead>
                      <tbody id="tbodyInvalid">
                        @forelse($invalidRows as $inv)
                          @php
                            $isWalkIn = (bool)($inv['walk_in'] ?? false);
                            $course = $inv['course'] ?? null;
                          @endphp
                          <tr
                            data-row="{{ $inv['row'] ?? '' }}"
                            data-name="{{ strtolower($inv['full_name'] ?? '') }}"
                            data-email="{{ strtolower($inv['school_email'] ?? '') }}"
                            data-id="{{ strtolower($inv['school_id'] ?? '') }}"
                            data-confirm="{{ $isWalkIn ? 'walk-in' : 'present' }}"
                            data-course="{{ strtolower((string)$course) }}"
                          >
                            <td class="td-mono">{{ $inv['row'] ?? '' }}</td>
                            <td class="td-strong">{{ $inv['full_name'] ?? '' }}</td>
                            <td class="td-muted">{{ $course ?: '—' }}</td>
                            <td class="td-mono">{{ $inv['school_id'] ?? '' }}</td>
                            <td class="td-muted">{{ $inv['school_email'] ?? '' }}</td>

                            <td>
                              <span class="badge-pill {{ $isWalkIn ? 'badge-pill--warn' : 'badge-pill--ok' }}">
                                <i class="fa-solid {{ $isWalkIn ? 'fa-person-walking' : 'fa-user-check' }}"></i>
                                {{ $isWalkIn ? 'Walk-in' : 'Present' }}
                              </span>
                            </td>

                            <td class="td-errors">
                              @foreach(($inv['errors'] ?? []) as $field => $errs)
                                <div class="err-line">
                                  <span class="err-field">{{ $field }}</span>
                                  <span class="err-msg">{{ implode(' | ', (array)$errs) }}</span>
                                </div>
                              @endforeach
                            </td>

                            <td class="td-actions">
                              <button class="btn-mini js-edit" type="button" data-row='@json($inv)'>
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                              </button>
                              <button class="btn-mini btn-mini--danger js-delete"
                                      type="button"
                                      data-row-number="{{ (int)($inv['row'] ?? 0) }}"
                                      data-bucket="invalid">
                                <i class="fa-solid fa-trash"></i> Delete
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="8" class="td-empty">No invalid rows.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>

                  <div class="hint-line mt-3">
                    Tip: Use <span class="soft-strong">Edit</span> to fix rows inside Preview.
                    When a row becomes valid, it moves into Valid Rows automatically.
                  </div>
                </div>
              </div>

              {{-- ✅ ALREADY IMPORTED --}}
              <div class="panel" id="panelAlready">
                <button class="panel-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#alreadyBlock" aria-expanded="false">
                  <div class="panel-title">
                    <i class="fa-solid fa-ban"></i> Already Imported (in database)
                    <span class="count-pill" id="countAlready">{{ count($alreadyRows) }}</span>
                  </div>
                  <i class="fa-solid fa-chevron-down caret"></i>
                </button>

                <div class="collapse" id="alreadyBlock">
                  <div class="table-shell mt-3">
                    <table class="table-lite" data-table="already">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Reason</th>
                          <th>Name</th>
                          <th>Course</th>
                          <th>School ID</th>
                          <th>Email</th>
                          <th>Confirm</th>
                          <th class="th-actions">Actions</th>
                        </tr>
                      </thead>
                      <tbody id="tbodyAlready">
                        @forelse($alreadyRows as $a)
                          @php
                            $isWalkIn = (bool)($a['walk_in'] ?? false);
                            $course = $a['course'] ?? null;
                            $reason = $a['already_imported_reason'] ?? 'Already imported for this event.';
                          @endphp

                          <tr class="row-muted"
                            data-row="{{ $a['row'] ?? '' }}"
                            data-name="{{ strtolower($a['full_name'] ?? '') }}"
                            data-email="{{ strtolower($a['school_email'] ?? '') }}"
                            data-id="{{ strtolower($a['school_id'] ?? '') }}"
                            data-confirm="{{ $isWalkIn ? 'walk-in' : 'present' }}"
                            data-course="{{ strtolower((string)$course) }}"
                          >
                            <td class="td-mono">{{ $a['row'] ?? '' }}</td>
                            <td class="td-muted">{{ $reason }}</td>
                            <td class="td-strong">{{ $a['full_name'] ?? '' }}</td>
                            <td class="td-muted">{{ $course ?: '—' }}</td>
                            <td class="td-mono">{{ $a['school_id'] ?? '' }}</td>
                            <td class="td-muted">{{ $a['school_email'] ?? '' }}</td>

                            <td>
                              <span class="badge-pill {{ $isWalkIn ? 'badge-pill--warn' : 'badge-pill--ok' }}">
                                <i class="fa-solid {{ $isWalkIn ? 'fa-person-walking' : 'fa-user-check' }}"></i>
                                {{ $isWalkIn ? 'Walk-in' : 'Present' }}
                              </span>
                            </td>

                            <td class="td-actions">
                              {{-- allow edit to try fixing identity (might move back to valid) --}}
                              <button class="btn-mini js-edit" type="button" data-row='@json($a)'>
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                              </button>

                              {{-- allow delete to clean preview --}}
                              <button class="btn-mini btn-mini--danger js-delete"
                                      type="button"
                                      data-row-number="{{ (int)($a['row'] ?? 0) }}"
                                      data-bucket="already_imported">
                                <i class="fa-solid fa-trash"></i> Delete
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="8" class="td-empty">No already-imported rows.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>

                  <div class="hint-line mt-3">
                    These are detected in the database for this event and will be skipped on Save.
                    If you think it matched incorrectly, try <span class="soft-strong">Edit</span> (ID/Email/Name).
                  </div>
                </div>
              </div>

              {{-- DUPLICATE --}}
              <div class="panel" id="panelDup">
                <button class="panel-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#dupBlock" aria-expanded="false">
                  <div class="panel-title">
                    <i class="fa-solid fa-clone"></i> Duplicate Rows (inside this CSV)
                    <span class="count-pill" id="countDup">{{ count($dupRows) }}</span>
                  </div>
                  <i class="fa-solid fa-chevron-down caret"></i>
                </button>

                <div class="collapse" id="dupBlock">
                  <div class="table-shell mt-3">
                    <table class="table-lite" data-table="dup">
                      <thead>
                        <tr>
                          <th>#</th>
                          <th>Reason</th>
                          <th>Name</th>
                          <th>School ID</th>
                          <th>Email</th>
                          <th class="th-actions">Actions</th>
                        </tr>
                      </thead>
                      <tbody id="tbodyDup">
                        @forelse($dupRows as $d)
                          <tr data-row="{{ $d['row'] ?? '' }}">
                            <td class="td-mono">{{ $d['row'] ?? '' }}</td>
                            <td class="td-muted">{{ $d['reason'] ?? '' }}</td>
                            <td class="td-strong">{{ $d['data']['full_name'] ?? '' }}</td>
                            <td class="td-mono">{{ $d['data']['school_id'] ?? '' }}</td>
                            <td class="td-muted">{{ $d['data']['school_email'] ?? '' }}</td>
                            <td class="td-actions">
                              <button class="btn-mini btn-mini--danger js-delete"
                                      type="button"
                                      data-row-number="{{ (int)($d['row'] ?? 0) }}"
                                      data-bucket="duplicate">
                                <i class="fa-solid fa-trash"></i> Delete
                              </button>
                            </td>
                          </tr>
                        @empty
                          <tr><td colspan="6" class="td-empty">No duplicates.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>

                  <div class="hint-line mt-3">
                    Duplicates are skipped to prevent double saving. You can delete them here for a clean preview.
                  </div>
                </div>
              </div>
            @endif

          </div>
        </div>
      </div>

    </div>
  </section>

  {{-- Import Guide Modal --}}
  <div class="modal fade" id="importGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content" style="border-radius: 18px;">
        <div class="modal-header">
          <h5 class="modal-title" style="font-weight: 800;">
            <i class="fa-regular fa-circle-question me-2"></i> Import Guide
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="guide-list">
            <div class="guide-item">
              <div class="guide-ico"><i class="fa-solid fa-1"></i></div>
              <div>
                <div class="guide-title">Export CSV from Google Forms</div>
                <div class="guide-sub">Form → Responses → Download responses (.csv).</div>
              </div>
            </div>

            <div class="guide-item">
              <div class="guide-ico"><i class="fa-solid fa-2"></i></div>
              <div>
                <div class="guide-title">Upload → Preview</div>
                <div class="guide-sub">We must Preview first to validate rows and match volunteer profiles.</div>
              </div>
            </div>

            <div class="guide-item">
              <div class="guide-ico"><i class="fa-solid fa-3"></i></div>
              <div>
                <div class="guide-title">Fix rows inside Preview (Edit/Delete)</div>
                <div class="guide-sub">No need to open the CSV. Use Edit to correct Name/ID/Email/Confirm.</div>
              </div>
            </div>

            <div class="guide-item">
              <div class="guide-ico"><i class="fa-solid fa-4"></i></div>
              <div>
                <div class="guide-title">Save Import</div>
                <div class="guide-sub">Creates new attendance only; rows already in DB are skipped (no overwrite).</div>
              </div>
            </div>
          </div>

          <div class="hint-line mt-3">
            Present + Walk-in counts as actual attendance. Walk-ins don’t need a matching profile.
            Import is only allowed when event status is <span class="soft-strong">COMPLETED</span>.
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  {{-- COMMENTS MODAL --}}
  <div class="modal fade" id="commentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
      <div class="modal-content" style="border-radius: 18px;">
        <div class="modal-header">
          <h5 class="modal-title" style="font-weight: 800;">
            <i class="fa-regular fa-message me-2"></i> Feedback / Comments
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="comment-card">
            <div class="comment-name" id="cmName">—</div>
            <div class="comment-meta" id="cmMeta">—</div>

            <div class="divider-lite"></div>

            <div class="comment-block">
              <div class="comment-label">Rating</div>
              <div class="comment-text" id="cmRating">—</div>
            </div>

            <div class="comment-block">
              <div class="comment-label">Improve next time</div>
              <div class="comment-text" id="cmImprove">—</div>
            </div>

            <div class="comment-block">
              <div class="comment-label">Issues encountered</div>
              <div class="comment-text" id="cmIssues">—</div>
            </div>

            <div class="comment-block">
              <div class="comment-label">Other comments</div>
              <div class="comment-text" id="cmOther">—</div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  {{-- EDIT MODAL --}}
  <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content" style="border-radius: 18px;">
        <div class="modal-header">
          <h5 class="modal-title" style="font-weight: 800;">
            <i class="fa-solid fa-pen-to-square me-2"></i> Edit Preview Row
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <div class="hint-line mb-3">
            This edits the Preview only (no CSV changes). After editing, row may move between Valid/Invalid/Already Imported automatically.
          </div>

          <input type="hidden" id="edRow">

          <div class="mb-2">
            <label class="form-label fw-semibold">Full Name</label>
            <input type="text" class="form-control" id="edName">
          </div>

          <div class="mb-2">
            <label class="form-label fw-semibold">School ID</label>
            <input type="text" class="form-control" id="edId">
          </div>

          <div class="mb-2">
            <label class="form-label fw-semibold">School Email</label>
            <input type="email" class="form-control" id="edEmail">
          </div>

          <div class="mb-2">
            <label class="form-label fw-semibold">Confirm</label>
            <select class="form-select" id="edConfirm">
              <option value="Present">Present</option>
              <option value="Walk-in">Walk-in</option>
            </select>
          </div>

          <div class="text-muted small">
            Note: Course is matched from profile when available (or from CSV if included).
          </div>
        </div>

        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-primary" type="button" id="edSave">Save changes</button>
        </div>
      </div>
    </div>
  </div>

  {{-- DELETE CONFIRM MODAL --}}
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content" style="border-radius: 18px;">
        <div class="modal-header">
          <h5 class="modal-title" style="font-weight: 800;">
            <i class="fa-solid fa-trash me-2"></i> Delete Preview Row
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="hint-line mb-0">
            Are you sure you want to delete row <span class="soft-strong" id="delRowLabel">—</span> from preview?
            This won’t change the CSV file.
          </div>
          <input type="hidden" id="delRow">
          <input type="hidden" id="delBucket">
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button class="btn btn-danger" type="button" id="delConfirmBtn">Delete</button>
        </div>
      </div>
    </div>
  </div>

  {{-- STATUS MODAL --}}
  <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content" style="border-radius: 18px;">
        <div class="modal-header">
          <h5 class="modal-title" style="font-weight: 800;" id="statusTitle">
            <i class="fa-regular fa-circle-check me-2"></i> Status
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="hint-line mb-0" id="statusMessage">—</div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  {{-- Bootstrap bundle --}}
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  {{-- FULL JS --}}
  <script>
  document.addEventListener('DOMContentLoaded', () => {
    if (!window.bootstrap || !bootstrap.Modal) {
      console.error('Bootstrap Modal not found. Make sure bootstrap.bundle.min.js is loaded.');
      return;
    }

    const $ = (sel) => document.querySelector(sel);

    function modal(id){
      const el = document.getElementById(id);
      if (!el) { console.error('Missing modal element:', id); return null; }
      return bootstrap.Modal.getOrCreateInstance(el);
    }

    const CommentsModal = modal('commentsModal');
    const EditModal     = modal('editModal');
    const DeleteModal   = modal('deleteModal');
    const StatusModal   = modal('statusModal');

    function showStatus(kind, msg){
      if (!StatusModal) return;
      const title = document.getElementById('statusTitle');
      const body  = document.getElementById('statusMessage');

      title.innerHTML = (kind === 'error')
        ? `<i class="fa-regular fa-circle-xmark me-2"></i> Error`
        : `<i class="fa-regular fa-circle-check me-2"></i> Success`;

      body.textContent = msg || '—';
      StatusModal.show();
    }

    // file name label
    const csv = document.getElementById('csvFile');
    const fileName = document.getElementById('fileName');
    if (csv && fileName) {
      csv.addEventListener('change', () => {
        fileName.textContent = csv.files?.[0]?.name || 'No file selected';
      });
    }

    // Copy event code
    const copyBtn = document.getElementById('eventCodeCopy');
    const toast = document.getElementById('copyToast');

    function showToast(msg = 'Copied!') {
      if (!toast) return;
      toast.textContent = msg;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 1100);
    }

    async function copyTextRobust(text) {
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(text);
          return true;
        }
      } catch (e) {}
      try {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.setAttribute('readonly', '');
        ta.style.position = 'fixed';
        ta.style.top = '-9999px';
        ta.style.left = '-9999px';
        document.body.appendChild(ta);
        ta.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(ta);
        return ok;
      } catch (e) { return false; }
    }

    if (copyBtn) {
      copyBtn.addEventListener('click', async (e) => {
        e.preventDefault();
        const code = copyBtn.getAttribute('data-code') || '';
        const ok = await copyTextRobust(code);
        copyBtn.classList.toggle('copied', ok);
        showToast(ok ? 'Copied event code!' : 'Copy blocked');
        setTimeout(() => copyBtn.classList.remove('copied'), 900);
      });
    }

    // Tips drawer
    const tipsToggle = document.getElementById('tipsToggle');
    const tipsClose  = document.getElementById('tipsClose');
    const tipsDrawer = document.getElementById('tipsDrawer');
    const tipsCaret  = document.getElementById('tipsCaret');

    function setTips(open) {
      if (!tipsDrawer) return;
      tipsDrawer.classList.toggle('open', open);
      tipsToggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
      tipsCaret?.classList.toggle('rot', open);
      try { localStorage.setItem('attendanceTipsOpen', open ? '1' : '0'); } catch(e) {}
    }

    (function initTips() {
      let open = true;
      try { open = (localStorage.getItem('attendanceTipsOpen') !== '0'); } catch(e) {}
      setTips(open);
    })();

    tipsToggle?.addEventListener('click', () => setTips(!tipsDrawer?.classList.contains('open')));
    tipsClose?.addEventListener('click', () => setTips(false));

    // Active table (✅ includes alreadyBlock)
    function getActiveTbody() {
      const map = [
        { block: '#validBlock',   tbody: '#tbodyValid' },
        { block: '#invalidBlock', tbody: '#tbodyInvalid' },
        { block: '#alreadyBlock', tbody: '#tbodyAlready' },
        { block: '#dupBlock',     tbody: '#tbodyDup' },
      ];
      for (const m of map) {
        const el = document.querySelector(m.block);
        if (el && el.classList.contains('show')) return document.querySelector(m.tbody);
      }
      return document.querySelector('#tbodyValid') || document.querySelector('tbody');
    }

    // Filter + Sort
    const search        = document.getElementById('previewSearch');
    const filterConfirm = document.getElementById('filterConfirm');
    const filterCourse  = document.getElementById('filterCourse');
    const sortSel       = document.getElementById('previewSort');
    const clearFilters  = document.getElementById('clearFilters');

    function applyFilters() {
      const term   = (search?.value || '').toLowerCase().trim();
      const conf   = (filterConfirm?.value || '').toLowerCase().trim();
      const course = (filterCourse?.value || '').toLowerCase().trim();

      document.querySelectorAll('table[data-table] tbody tr').forEach(tr => {
        const trName = (tr.getAttribute('data-name') || '').toLowerCase();
        const trId   = (tr.getAttribute('data-id') || '').toLowerCase();
        const trMail = (tr.getAttribute('data-email') || '').toLowerCase();
        const trConf   = (tr.getAttribute('data-confirm') || '').toLowerCase();
        const trCourse = (tr.getAttribute('data-course') || '').toLowerCase();

        const okTerm = !term || trName.includes(term) || trId.includes(term) || trMail.includes(term) || tr.innerText.toLowerCase().includes(term);
        const okConf = !conf || trConf === conf;
        const okCourse = !course || trCourse === course;

        tr.style.display = (okTerm && okConf && okCourse) ? '' : 'none';
      });
    }

    function sortActiveByName(dir) {
      const tbody = getActiveTbody();
      if (!tbody) return;

      const rows = Array.from(tbody.querySelectorAll('tr'))
        .filter(r => r.querySelectorAll('td').length);

      rows.sort((a,b) => {
        const na = (a.getAttribute('data-name') || '').toLowerCase();
        const nb = (b.getAttribute('data-name') || '').toLowerCase();
        return (dir === 'desc') ? nb.localeCompare(na) : na.localeCompare(nb);
      });

      rows.forEach(r => tbody.appendChild(r));
      applyFilters();
    }

    search?.addEventListener('input', applyFilters);
    filterConfirm?.addEventListener('change', applyFilters);
    filterCourse?.addEventListener('change', applyFilters);

    sortSel?.addEventListener('change', () => {
      sortActiveByName(sortSel.value === 'name_desc' ? 'desc' : 'asc');
    });

    clearFilters?.addEventListener('click', () => {
      if (search) search.value = '';
      if (filterConfirm) filterConfirm.value = '';
      if (filterCourse) filterCourse.value = '';
      if (sortSel) sortSel.value = 'name_asc';
      applyFilters();
      sortActiveByName('asc');
    });

    // Rotate caret on collapse (✅ includes alreadyBlock)
    document.querySelectorAll('.panel-toggle').forEach(btn => {
      const caret = btn.querySelector('.caret');
      const target = btn.getAttribute('data-bs-target');
      if (!caret || !target) return;
      const el = document.querySelector(target);
      if (!el) return;
      el.addEventListener('show.bs.collapse', () => caret.classList.add('rot'));
      el.addEventListener('hide.bs.collapse', () => caret.classList.remove('rot'));
    });

    // ===== IMPORTANT PART: Event Delegation for buttons =====
    function safeParseRow(btn){
      const raw = btn.getAttribute('data-row');
      if (!raw) return null;
      try { return JSON.parse(raw); }
      catch (e) {
        console.error('Failed to parse data-row JSON:', e, raw);
        return null;
      }
    }

    document.addEventListener('click', (e) => {
      const commentsBtn = e.target.closest('.js-comments');
      const editBtn     = e.target.closest('.js-edit');
      const delBtn      = e.target.closest('.js-delete');

      // COMMENTS
      if (commentsBtn) {
        if (!CommentsModal) return;
        const row = safeParseRow(commentsBtn) || {};
        const setText = (id, val) => {
          const el = document.getElementById(id);
          if (el) el.textContent = (val ?? '—') || '—';
        };

        setText('cmName', row.full_name);
        setText('cmMeta', `${row.school_id || '—'} • ${row.school_email || '—'}`);
        setText('cmRating', (row.rating ?? '—'));
        setText('cmImprove', row.improve_next_time);
        setText('cmIssues', row.issues_encountered);
        setText('cmOther', row.other_comments || row.feedback_text || '—');

        CommentsModal.show();
        return;
      }

      // EDIT
      if (editBtn) {
        if (!EditModal) return;
        const row = safeParseRow(editBtn) || {};
        document.getElementById('edRow').value     = row.row || '';
        document.getElementById('edName').value    = row.full_name || '';
        document.getElementById('edId').value      = row.school_id || '';
        document.getElementById('edEmail').value   = row.school_email || '';
        document.getElementById('edConfirm').value = row.attendance_confirmation || 'Present';
        EditModal.show();
        return;
      }

      // DELETE
      if (delBtn) {
        if (!DeleteModal) return;
        const rowNumber = parseInt(delBtn.getAttribute('data-row-number') || '0', 10);
        const bucket = delBtn.getAttribute('data-bucket') || '';
        if (!rowNumber) return;

        document.getElementById('delRow').value = rowNumber;
        document.getElementById('delBucket').value = bucket;
        document.getElementById('delRowLabel').textContent = `#${rowNumber}`;
        DeleteModal.show();
        return;
      }
    });

    async function postJson(url, payload) {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.ok === false) throw new Error(data.message || 'Request failed');
      return data;
    }

    // Save edit
    document.getElementById('edSave')?.addEventListener('click', async () => {
      const row = parseInt(document.getElementById('edRow').value || '0', 10);
      const payload = {
        row,
        full_name: document.getElementById('edName').value,
        school_id: document.getElementById('edId').value,
        school_email: document.getElementById('edEmail').value,
        attendance_confirmation: document.getElementById('edConfirm').value
      };

      try {
        await postJson("{{ route('attendance.import.preview.update', $event->event_id) }}", payload);
        window.location.reload();
      } catch (e) {
        showStatus('error', e.message || 'Failed to update row.');
      }
    });

    // Confirm delete
    document.getElementById('delConfirmBtn')?.addEventListener('click', async () => {
      const row = parseInt(document.getElementById('delRow').value || '0', 10);
      const bucket = document.getElementById('delBucket').value || '';
      if (!row) return;

      try {
        await postJson("{{ route('attendance.import.preview.delete', $event->event_id) }}", { row, bucket });
        window.location.reload();
      } catch (e) {
        try { DeleteModal?.hide(); } catch(_) {}
        showStatus('error', e.message || 'Failed to delete row.');
      }
    });

    // show server messages
    (function showServerStatus(){
      const success = @json($success ?? session('success'));
      const error   = @json($error ?? session('error'));
      if (success) showStatus('success', success);
      if (error) showStatus('error', error);
    })();

    console.log('Attendance Import JS loaded ✅ (already_imported supported)');
  });
  </script>

</body>
</html>
