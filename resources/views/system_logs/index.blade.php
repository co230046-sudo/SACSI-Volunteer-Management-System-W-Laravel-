<?php $pageTitle = 'System Logs'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>{{ $pageTitle }}</title>

  <link rel="stylesheet" href="{{ asset('assets/system_logs/css/system_logs.css') }}">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@500;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>

<body>
  @include('layouts.page_loader')
  @include('layouts.navbar')
  @include('layouts.back_button')

  @php
    $logs = $logs ?? collect();
    $availableActions = $availableActions ?? [];
    $availableCategories = $availableCategories ?? [
      'system'            => 'System',
      'auth'              => 'Authentication',
      'event'             => 'Event Management',
      'volunteer'         => 'Volunteer Management',
      'attendance'        => 'Attendance',
      'volunteer_import'  => 'Volunteer Import',
      'attendance_import' => 'Attendance Import',
      'import'            => 'Import (Other)',
    ];

    $guessCategory = function ($log) {
      $entityType = strtolower((string)($log->entity_type ?? ''));
      $action = strtolower((string)($log->action ?? ''));
      $details = strtolower((string)($log->details ?? ''));

      if (str_contains($action, 'login') || str_contains($action, 'logout') || str_contains($action, 'failed_login')) return 'auth';

      if (str_contains($action, 'import') || str_contains($details, 'import')) {
        if (str_contains($details, 'att-') || str_contains($details, 'attendance')) return 'attendance_import';
        if (str_contains($details, 'vol-') || str_contains($details, 'volunteer')) return 'volunteer_import';
        return 'import';
      }

      if (str_contains($entityType, 'event') || str_contains($details, 'event') || str_contains($details, 'organizer')) return 'event';
      if (str_contains($entityType, 'volunteer')) return 'volunteer';
      if (str_contains($entityType, 'attendance')) return 'attendance';

      return 'system';
    };

    $categoryLabel = function ($key) use ($availableCategories) {
      return $availableCategories[$key] ?? ucfirst(str_replace('_',' ', $key));
    };

    $adminRoute = function ($log) {
      if (!empty($log->admin_id)) {
        return route('admin.profile', ['id' => $log->admin_id]);
      }
      return null;
    };
  @endphp

  <section class="logs-wrap">
    <div class="logs-top-card">
      <header class="logs-header">
        <div class="logs-kicker">
          <i class="fa-regular fa-clipboard-list"></i>
          System Logs
        </div>
        <p class="logs-sub">Central audit trail for major actions across the system.</p>
      </header>

      <div class="logs-filters">
        <form method="GET" action="{{ route('system.logs.index') }}" id="logsFilterForm" novalidate>
          <div class="filters-grid">
            <div class="filter-field">
              <label class="filter-label" for="date_start">Start date</label>
              <input type="date" id="date_start" name="date_start" class="filter-input"
                     value="{{ request('date_start') }}">
            </div>

            <div class="filter-field">
              <label class="filter-label" for="date_end">End date</label>
              <input type="date" id="date_end" name="date_end" class="filter-input"
                     value="{{ request('date_end') }}">
            </div>

            <div class="filter-field">
              <label class="filter-label" for="action">Action</label>

              <input type="hidden" name="action" id="actionHidden" value="{{ request('action') }}">
              <div class="cselect" data-name="action">
                <button type="button" class="cselect-btn" aria-haspopup="listbox" aria-expanded="false">
                  <span class="cselect-value">{{ request('action') ?: 'All actions' }}</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>

                <div class="cselect-pop" role="listbox" tabindex="-1">
                  <div class="cselect-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search action..." autocomplete="off">
                  </div>
                  <div class="cselect-list">
                    <button type="button" class="cselect-item" data-value="">All actions</button>
                    @foreach($availableActions as $a)
                      <button type="button" class="cselect-item" data-value="{{ $a }}">{{ $a }}</button>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>

            <div class="filter-field">
              <label class="filter-label" for="category">Category</label>

              <input type="hidden" name="category" id="categoryHidden" value="{{ request('category') }}">
              <div class="cselect" data-name="category">
                <button type="button" class="cselect-btn" aria-haspopup="listbox" aria-expanded="false">
                  <span class="cselect-value">
                    {{ request('category') ? ($availableCategories[request('category')] ?? request('category')) : 'All categories' }}
                  </span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>

                <div class="cselect-pop" role="listbox" tabindex="-1">
                  <div class="cselect-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search category..." autocomplete="off">
                  </div>
                  <div class="cselect-list">
                    <button type="button" class="cselect-item" data-value="">All categories</button>
                    @foreach($availableCategories as $k => $label)
                      <button type="button" class="cselect-item" data-value="{{ $k }}">{{ $label }}</button>
                    @endforeach
                  </div>
                </div>
              </div>
            </div>

            <div class="filter-field filter-field--search">
              <label class="filter-label" for="q">Search</label>
              <div class="filter-input-wrap">
                <input type="text" id="q" name="q" class="filter-input"
                       placeholder="Search user, action, details..."
                       value="{{ request('q') }}">
                <span class="filter-icon"><i class="fa-solid fa-magnifying-glass"></i></span>
              </div>
            </div>

            <div class="filter-field filter-field--perpage">
              <label class="filter-label" for="per_page">Rows</label>
              <select id="per_page" name="per_page" class="filter-input filter-select-native">
                @foreach([3,5,8,10,12,15] as $n)
                  <option value="{{ $n }}" {{ (int)request('per_page', $perPage ?? 5) === $n ? 'selected' : '' }}>{{ $n }}</option>
                @endforeach
              </select>
            </div>

            <div class="filter-actions">
              <button type="button" class="btn-reset-filters" id="logsResetBtn">
                <i class="fa-solid fa-rotate-left"></i> Reset
              </button>
              <button type="submit" class="btn-apply-filters">
                <i class="fa-solid fa-filter"></i> Apply
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="logs-table-card">
      <table class="logs-table">
        <thead>
          <tr>
            <th class="col-time">Date &amp; Time</th>
            <th class="col-category">Category</th>
            <th class="col-action">Action</th>
            <th class="col-user">User</th>
            <th class="col-details">Details</th>
          </tr>
        </thead>

        <tbody>
        @forelse($logs as $log)
          @php
            $timestamp = $log->timestamp ? \Illuminate\Support\Carbon::parse($log->timestamp)->format('Y-m-d h:i A') : '—';

            $adminName =
              $log->admin->username
                ?? $log->admin->name
                ?? 'Unknown';

            $adminUrl = $adminRoute($log);

            $actionLabel = (string)($log->action ?? '—');

            // ✅ IMPORTANT: if controller provides details_decoded, use it.
            $detailsRaw = (string)($log->details_decoded ?? $log->details ?? '');

            $catKey = $guessCategory($log);
            $catLabel = $categoryLabel($catKey);

            $actionSlug = \Illuminate\Support\Str::slug(strtolower($actionLabel));
            $catSlug = \Illuminate\Support\Str::slug(strtolower($catKey));

            $rowId = 'log-' . ($log->fact_log_id ?? spl_object_id($log));

            $searchBlob = strtolower(
              $adminName.' '.$actionLabel.' '.$catLabel.' '.$detailsRaw.' '.($log->entity_type ?? '').' '.($log->entity_id ?? '')
            );
          @endphp

          <tr class="log-row" id="{{ $rowId }}"
              data-search="{{ e($searchBlob) }}">
            <td>
              <div class="log-time">{{ $timestamp }}</div>
            </td>

            <td>
              <span class="pill pill-cat log-cat--{{ $catSlug }}">{{ $catLabel }}</span>
            </td>

            <td>
              <span class="pill pill-act log-act--{{ $actionSlug }}">{{ $actionLabel }}</span>
            </td>

            <td>
              @if($adminUrl)
                <a class="admin-link" href="{{ $adminUrl }}">
                  <span class="admin-pill">{{ $adminName }}</span>
                </a>
              @else
                <span class="admin-pill">{{ $adminName }}</span>
              @endif
            </td>

            <td>
              <div class="log-details js-humanize"
                   data-row-id="{{ $rowId }}"
                   data-timestamp="{{ e($timestamp) }}"
                   data-admin="{{ e($adminName) }}"
                   data-admin-url="{{ e($adminUrl ?? '') }}"
                   data-action="{{ e($actionLabel) }}"
                   data-category="{{ e($catLabel) }}"
                   data-category-key="{{ e($catKey) }}"
                   data-entity-type="{{ e($log->entity_type ?? '') }}"
                   data-entity-id="{{ e($log->entity_id ?? '') }}"
                   data-raw="{{ e($detailsRaw) }}">
                {{ $detailsRaw ?: '—' }}
              </div>

              @if(!empty(trim($detailsRaw)))
                <button type="button"
                        class="more-link js-open-modal"
                        data-row="{{ $rowId }}">
                  Show full
                </button>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="empty-cell">
              <div class="logs-empty">
                <div class="logs-empty-icon"><i class="fa-regular fa-face-smile"></i></div>
                <div class="logs-empty-title">No logs found.</div>
                <div class="logs-empty-sub">Try adjusting the filters.</div>
              </div>
            </td>
          </tr>
        @endforelse
        </tbody>
      </table>

      @if(is_object($logs) && method_exists($logs, 'currentPage'))
        <div class="logs-pagination">
          <div class="pageline">
            <div class="pageline-left">
              Showing
              <strong>{{ $logs->firstItem() ?? 0 }}</strong>
              to
              <strong>{{ $logs->lastItem() ?? 0 }}</strong>
              of
              <strong>{{ $logs->total() }}</strong>
              results
            </div>

            <div class="pageline-right">
              @if($logs->onFirstPage())
                <span class="pbtn is-disabled"><i class="fa-solid fa-chevron-left"></i> Prev</span>
              @else
                <a class="pbtn" href="{{ $logs->previousPageUrl() }}"><i class="fa-solid fa-chevron-left"></i> Prev</a>
              @endif

              @php
                $start = max(1, $logs->currentPage() - 2);
                $end   = min($logs->lastPage(), $logs->currentPage() + 2);
              @endphp

              @if($start > 1)
                <a class="pnum" href="{{ $logs->url(1) }}">1</a>
                @if($start > 2) <span class="pdots">…</span> @endif
              @endif

              @for($p = $start; $p <= $end; $p++)
                @if($p === $logs->currentPage())
                  <span class="pnum is-active">{{ $p }}</span>
                @else
                  <a class="pnum" href="{{ $logs->url($p) }}">{{ $p }}</a>
                @endif
              @endfor

              @if($end < $logs->lastPage())
                @if($end < $logs->lastPage() - 1) <span class="pdots">…</span> @endif
                <a class="pnum" href="{{ $logs->url($logs->lastPage()) }}">{{ $logs->lastPage() }}</a>
              @endif

              @if($logs->hasMorePages())
                <a class="pbtn" href="{{ $logs->nextPageUrl() }}">Next <i class="fa-solid fa-chevron-right"></i></a>
              @else
                <span class="pbtn is-disabled">Next <i class="fa-solid fa-chevron-right"></i></span>
              @endif
            </div>
          </div>
        </div>
      @endif
    </div>
  </section>

  {{-- ✅ MODAL (was broken because markup was incomplete / missing backdrop/card/head/foot) --}}
  <div class="modal-backdrop" id="logModalBackdrop" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="logModalTitle">
      <div class="modal-head">
        <div class="modal-title" id="logModalTitle">Log details</div>
        <button type="button" class="modal-x" id="logModalClose" aria-label="Close">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="modal-body">
        <div class="modal-meta" id="logModalMeta"></div>

        {{-- Old vibe: chips + summary --}}
        <div class="modal-chips" id="logModalChips"></div>
        <div class="modal-summary" id="logModalSummary"></div>

        <div class="modal-section">
            <button type="button" class="raw-toggle" id="logModalRawToggle" aria-expanded="false">
                <span>Show raw details</span>
                <i class="fa-solid fa-chevron-down"></i>
            </button>

            <div class="raw-panel" id="logModalRawPanel" hidden>
                <pre class="modal-pre" id="logModalRaw"></pre>
            </div>
        </div>

      </div>

      <div class="modal-foot">
        <button type="button" class="btn-ghost" id="logModalClose2">Close</button>
        <button type="button" class="btn-primary" id="logModalJump">
          Show &amp; highlight row
        </button>
      </div>
    </div>
  </div>

  <script src="{{ asset('assets/system_logs/js/system_logs.js') }}"></script>
</body>
</html>
