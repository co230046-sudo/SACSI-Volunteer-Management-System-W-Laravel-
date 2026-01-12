{{-- ==================================================================
    CLASS SCHEDULE MODAL (Volunteer List -> Add Volunteer)
    Suggested path:
    resources/views/layouts/modals/submit/volunteer_list/class_schedule_modal.blade.php

    IMPORTANT:
    - Keep these IDs as-is because assets/volunteer_list/js/script.js binds to them.
    - No JS here (handled by script.js).
================================================================== --}}

<style>
  /* Crimson themed schedule modal (scoped) */
  #vlScheduleModal .modal-content{ border-radius:18px; overflow:hidden; border:0; }
  #vlScheduleModal .modal-header{
    background: linear-gradient(135deg, #a2343f, #5b1a20);
    color:#fff;
    border-bottom: none;
  }
  #vlScheduleModal .modal-title{ font-weight: 950; letter-spacing:.2px; }
  #vlScheduleModal .btn-close{ filter: invert(1); opacity: .9; }
  #vlScheduleModal .modal-body{ background:#fff7f8; }

  #vlScheduleModal .vl-schTable{ font-size: .86rem; }
  #vlScheduleModal .vl-schTable thead th{
    background: rgba(162,52,63,.10);
    color:#111827;
    border-color: rgba(162,52,63,.18);
    font-weight: 950;
  }
  #vlScheduleModal .vl-schTable td{ border-color: rgba(162,52,63,.18); }
  #vlScheduleModal .vl-schIndex{ font-weight: 950; color:#6b7280; }

  #vlScheduleModal .vl-schSelect{
    border-radius: 12px;
    border: 1px solid rgba(17,24,39,.12);
    font-weight: 850;
  }
  #vlScheduleModal .vl-schSelect:focus{
    border-color: rgba(162,52,63,.55);
    box-shadow: 0 0 0 .2rem rgba(162,52,63,.16);
  }

  #vlScheduleModal .modal-footer{ background:#fff; border-top: none; }
  #vlScheduleModal .btn-danger{
    background:#a2343f;
    border-color:#a2343f;
    font-weight: 950;
  }
  #vlScheduleModal .btn-danger:hover{ background:#b83f4c; border-color:#b83f4c; }
</style>

<div class="modal fade" id="vlScheduleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mb-0">
          <i class="fa-solid fa-calendar-days me-2"></i> Class Schedule
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
        <button type="button" class="btn btn-outline-secondary btn-sm" id="vlScheduleClear">
          <i class="fa-solid fa-eraser me-1"></i> Clear All
        </button>

        <div>
          <button type="button" class="btn btn-light btn-sm me-1" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger btn-sm" id="vlScheduleSave">
            <i class="fa-solid fa-check me-1"></i> Save Schedule
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
