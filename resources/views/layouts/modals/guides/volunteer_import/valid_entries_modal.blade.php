<link rel="stylesheet" href="{{ asset('assets/volunteer_import/css/guide1&2.css') }}">

<style>
/* ===== Guide upgrades (safe, minimal) — same style as Guide 1 ===== */
.import-handling-modal .guide-steps{
  display:flex; flex-direction:column; gap:14px; margin-top:10px;
}
.import-handling-modal .step-card{
  border:1px solid rgba(0,0,0,0.08);
  border-radius:14px;
  padding:14px 14px 12px;
  background:#fff;
  box-shadow:0 10px 24px rgba(0,0,0,0.08);
}
.import-handling-modal .step-head{
  display:flex; align-items:center; justify-content:space-between; gap:10px;
  margin-bottom:6px;
}
.import-handling-modal .step-title{
  display:flex; align-items:center; gap:10px; margin:0; font-weight:800;
}
.import-handling-modal .step-title .badge-step{
  display:inline-flex; align-items:center; justify-content:center;
  width:28px; height:28px; border-radius:999px;
  background:rgba(230,32,46,.12); color:#a5161f; font-weight:900; font-size:.9rem;
}
.import-handling-modal details.guide-details{
  margin-top:10px;
  background:#fbfbfc;
  border:1px dashed rgba(0,0,0,0.12);
  border-radius:12px;
  padding:10px 12px;
}
.import-handling-modal details.guide-details > summary{
  cursor:pointer;
  font-weight:800;
  color:#374151;
  list-style:none;
  display:flex;
  align-items:center;
  gap:10px;
}
.import-handling-modal details.guide-details > summary::-webkit-details-marker{ display:none; }
.import-handling-modal .summary-pill{
  font-size:.78rem;
  font-weight:900;
  padding:3px 8px;
  border-radius:999px;
  background:rgba(13,110,253,.10);
  color:#0b5ed7;
  border:1px solid rgba(13,110,253,.18);
}
.import-handling-modal .details-body{
  margin-top:10px;
  color:#374151;
  line-height:1.55;
}
.import-handling-modal .hint-row{ display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
.import-handling-modal .hint{
  display:inline-flex; align-items:center; gap:8px;
  padding:6px 10px; border-radius:999px;
  font-weight:800; font-size:.82rem;
  border:1px solid rgba(0,0,0,0.08); background:#fff;
}
.import-handling-modal .hint.ok{ color:#146c43; background:#d1f7df; border-color:#a8e9c2; }
.import-handling-modal .hint.warn{ color:#b02a37; background:#ffe0e3; border-color:#ffb8c0; }
.import-handling-modal .hint.info{ color:#0b5ed7; background:#e7f1ff; border-color:#b6d4fe; }
</style>

<!-- Modal 2: Valid Entries Guide (UPGRADED) -->
<div class="import-handling-modal" id="importHandlingModal2">
  <div class="modal-overlay">
    <div class="modal-content wide-modal">
      <div class="modal-inner">

        <!-- Header -->
        <div class="modal-header">
          <h2><i class="fas fa-book modal-icon"></i> Valid Entries Guide</h2>
        </div>

        <!-- Content -->
        <div class="guide-content">
          <p>
            This guide covers your <strong>Verified Entries</strong> table:
            final review → optional edits → submit to database → confirm in import logs.
          </p>

          <div class="guide-steps">

            <!-- STEP 1 -->
            <div class="step-card">
              <div class="step-head">
                <h3 class="step-title"><span class="badge-step">1</span> Review Verified Entries</h3>
                <span class="summary-pill">Final check</span>
              </div>

              <p class="inline-paragraph">
                The Verified table contains records that are considered valid. Before submitting,
                scan for typos such as wrong School ID, wrong Contact Numbers, incorrect Course/Year, and incomplete details.
              </p>

              <details class="guide-details">
                <summary>
                  <i class="fa-solid fa-circle-info"></i> Details
                  <span class="summary-pill">What to look for</span>
                </summary>
                <div class="details-body">
                  <ul style="margin:0; padding-left:1.25rem;">
                    <li><strong>Status</strong> will show <em>Verified</em>.</li>
                    <li>Hover cells to see full values when truncated.</li>
                    <li>Use the search bar to quickly locate a name or ID.</li>
                  </ul>
                </div>
              </details>

              <video
                controls
                preload="metadata"
                playsinline
                style="width:100%; border-radius:12px;"
              >
                <source src="{{ asset('assets/volunteer_import/valid_entries_guide/videos/step_1_review_verified_entries.mp4') }}" type="video/mp4">
                Your browser does not support the video tag.
              </video>
            </div>

            <!-- STEP 2 -->
            <div class="step-card">
              <div class="step-head">
                <h3 class="step-title"><span class="badge-step">2</span> Submit to Database</h3>
                <span class="summary-pill">Save</span>
              </div>

              <p class="inline-paragraph">
                When everything looks correct, click:
                <span class="submit-section" style="display:inline-block; vertical-align:middle;">
                  <button type="button" class="btn btn-danger submit-database" aria-hidden="true">
                    <i class="fa-solid fa-database"></i> Submit
                  </button>
                </span>
                to save all verified entries to the database.
              </p>

              <details class="guide-details">
                <summary>
                  <i class="fa-solid fa-circle-info"></i> What happens after Submit?
                  <span class="summary-pill">Auto logging</span>
                </summary>
                <div class="details-body">
                  <ul style="margin:0; padding-left:1.25rem;">
                    <li>The system stores the verified records.</li>
                    <li>An entry is created in <strong>Import Logs</strong> containing file name, uploader, date/time, and counts.</li>
                    <li>You can verify the results in the Import Logs section.</li>
                  </ul>
                </div>
              </details>

              <video
                controls
                preload="metadata"
                playsinline
                style="width:100%; border-radius:12px;"
              >
                <source src="{{ asset('assets/volunteer_import/valid_entries_guide/videos/step_2_submit_to_database.mp4') }}" type="video/mp4">
                Your browser does not support the video tag.
              </video>
            </div>

            <!-- STEP 3 -->
            <div class="step-card">
              <div class="step-head">
                <h3 class="step-title"><span class="badge-step">3</span> Confirm in Import Logs</h3>
                <span class="summary-pill">History</span>
              </div>

              <p class="inline-paragraph">
                After submission, scroll to the <strong>Import Logs</strong> section to confirm:
                uploader name, timestamps, counts, and status.
              </p>

              <details class="guide-details">
                <summary>
                  <i class="fa-solid fa-clock-rotate-left"></i> What to check in logs
                  <span class="summary-pill">Counts</span>
                </summary>
                <div class="details-body">
                  <ul style="margin:0; padding-left:1.25rem;">
                    <li><strong>Total Records</strong>, <strong>Valid</strong>, <strong>Invalid</strong>, <strong>Duplicate</strong></li>
                    <li><strong>Status</strong> (completed/partial/failed/etc.)</li>
                    <li><strong>Remarks</strong> for important notes of the import</li>
                  </ul>
                </div>
              </details>

              <video
                controls
                preload="metadata"
                playsinline
                style="width:100%; border-radius:12px;"
              >
                <source src="{{ asset('assets/volunteer_import/valid_entries_guide/videos/step_3_confirm_import_logs.mp4') }}" type="video/mp4">
                Your browser does not support the video tag.
              </video>
            </div>

          </div><!-- /guide-steps -->
        </div><!-- /guide-content -->

        <!-- Footer -->
        <div class="modal-buttons">
          <button class="modal-btn cancel" type="button" onclick="closeModal('importHandlingModal2')">
            <i class="fa-solid fa-xmark"></i> Close
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
// guide2.js — Handles Valid Entries Guide modal (same behavior as Guide 1)
document.addEventListener("DOMContentLoaded", () => {
  const modal2 = document.getElementById("importHandlingModal2");
  if (!modal2) return;

  // overlay click closes
  modal2.querySelector(".modal-overlay")?.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal-overlay")) closeModal("importHandlingModal2");
  });

  // ESC closes
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal2.classList.contains("is-open")) closeModal("importHandlingModal2");
  });

  console.log("Guide 2 (Valid Entries) upgraded ✅");
});
</script>
