<link rel="stylesheet" href="{{ asset('assets/volunteer_import/css/guide1&2.css') }}">

<style>
/* ===== Guide upgrades (safe, minimal) ===== */
.import-handling-modal .guide-steps{
  display:flex;
  flex-direction:column;
  gap: 14px;
  margin-top: 10px;
}
.import-handling-modal .step-card{
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 14px;
  padding: 14px 14px 12px;
  background: #fff;
  box-shadow: 0 10px 24px rgba(0,0,0,0.08);
}
.import-handling-modal .step-head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  margin-bottom: 6px;
}
.import-handling-modal .step-title{
  display:flex;
  align-items:center;
  gap:10px;
  margin:0;
  font-weight: 800;
}
.import-handling-modal .step-title .badge-step{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  width:28px;
  height:28px;
  border-radius: 999px;
  background: rgba(230,32,46,.12);
  color:#a5161f;
  font-weight: 900;
  font-size: .9rem;
}
.import-handling-modal .step-sub{
  margin: 6px 0 10px;
  color:#4b5563;
  line-height: 1.5;
}
.import-handling-modal details.guide-details{
  margin-top: 10px;
  background: #fbfbfc;
  border: 1px dashed rgba(0,0,0,0.12);
  border-radius: 12px;
  padding: 10px 12px;
}
.import-handling-modal details.guide-details > summary{
  cursor:pointer;
  font-weight: 800;
  color:#374151;
  list-style:none;
  display:flex;
  align-items:center;
  gap:10px;
}
.import-handling-modal details.guide-details > summary::-webkit-details-marker{ display:none; }
.import-handling-modal .summary-pill{
  font-size:.78rem;
  font-weight: 900;
  padding: 3px 8px;
  border-radius: 999px;
  background: rgba(13,110,253,.10);
  color:#0b5ed7;
  border: 1px solid rgba(13,110,253,.18);
}
.import-handling-modal .details-body{
  margin-top: 10px;
  color:#374151;
  line-height: 1.55;
}
.import-handling-modal .hint-row{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top: 8px;
}
.import-handling-modal .hint{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 800;
  font-size: .82rem;
  border: 1px solid rgba(0,0,0,0.08);
  background: #fff;
}
.import-handling-modal .hint.ok{ color:#146c43; background:#d1f7df; border-color:#a8e9c2; }
.import-handling-modal .hint.warn{ color:#b02a37; background:#ffe0e3; border-color:#ffb8c0; }
.import-handling-modal .hint.info{ color:#0b5ed7; background:#e7f1ff; border-color:#b6d4fe; }

/* uploader field demo (matches old "pink readonly" look) */
.import-handling-modal .uploader-info input{
  max-width: 240px;
  display:inline-block;
  vertical-align: middle;
  background:#f8d7da;
  border:1px solid #f1b0b7;
  color:#842029;
  font-weight: 800;
  border-radius: 8px;
  padding: 8px 10px;
}

.import-handling-modal .video-placeholder{
  margin-top: 10px;
  border-radius: 12px;
}

/* --- GUIDE-ONLY: match your UI controls from screenshots --- */
/* Photo 1: simple inline arrow + text (not a pill button) */
.import-handling-modal .ui-inline-transfer{
  display:inline-flex;
  align-items:center;
  gap: 8px;
  font-weight: 800;
  color:#374151;
}
.import-handling-modal .ui-inline-transfer i{
  color:#374151;
}

/* Photo 2: red bulk button */
.import-handling-modal .btn-ui-bulk-verified{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding: 10px 16px;
  border-radius: 6px;
  border: 1px solid rgba(0,0,0,0.08);
  background:#dc3545; /* bootstrap danger */
  color:#fff;
  font-weight: 900;
  box-shadow: 0 6px 14px rgba(0,0,0,0.12);
}

/* Yellow reset pill */
.import-handling-modal .btn-ui-reset{
  display:inline-flex;
  align-items:center;
  gap:.55rem;
  padding: 8px 14px;
  border-radius: 999px;
  border: 1px solid rgba(255,193,7,.55);
  background: rgba(255,193,7,.18);
  color:#9a6a00;
  font-weight: 900;
}
.import-handling-modal .btn-ui-reset i{ color:#9a6a00; }
</style>

<!-- Modal 1: Import & Validation Guide (MERGED + UPGRADED) -->
<div class="import-handling-modal" id="importHandlingModal1">
  <div class="modal-overlay">
    <div class="modal-content wide-modal">
      <div class="modal-inner">

        <!-- Header -->
        <div class="modal-header">
          <h2><i class="fas fa-book modal-icon"></i> Import & Validation Guide</h2>
        </div>

        <!-- Content -->
        <div class="guide-content">
          <p>
            This guide shows the exact flow used in your <strong>Volunteer Imports</strong> page:
            upload → preview invalid entries → fix issues → move to verified → submit.
          </p>

          <div class="guide-steps">

            <!-- STEP 1 -->
            <div class="step-card">
              <div class="step-head">
                <h3 class="step-title"><span class="badge-step">1</span> Select CSV File</h3>
                <span class="summary-pill">Upload</span>
              </div>

              <p class="step-sub inline-paragraph">
                Click
                <span class="inline-control">
                  <button type="button"
                          class="btn btn-outline-secondary rounded-1 inline-btn js-demo-filebtn"
                          aria-hidden="true">
                    <i class="fa-solid fa-file-csv me-2"></i> Choose File
                  </button>
                  <span class="file-path inline-filepath js-demo-filepath">No file chosen</span>
                </span>
                to select your CSV file.
              </p>

              <details class="guide-details">
                <summary>
                  <i class="fa-solid fa-circle-info"></i> Details
                  <span class="summary-pill">Tips</span>
                </summary>
                <div class="details-body">
                  <ul style="margin:0; padding-left: 1.25rem;">
                    <li>Only <strong>.csv</strong> files are accepted.</li>
                    <li>The selected file name will appear beside the Choose File button.</li>
                    <li>If nothing changes, check if your file input is hidden and triggered by the button (that’s normal in your Blade).</li>
                  </ul>
                </div>
              </details>

              <div class="video-placeholder outline-cut">Video: Selecting a CSV file</div>
            </div>

            <!-- STEP 2 -->
            <div class="step-card">
              <div class="step-head">
                <h3 class="step-title"><span class="badge-step">2</span> Confirm uploader + Import</h3>
                <span class="summary-pill">Preview</span>
              </div>

              <p class="step-sub inline-paragraph">
                Your uploader name is auto-filled (read-only) like:
                <span class="uploader-info">
                  <input type="text" id="uploader-name" value="Uploading as admin" readonly aria-hidden="true">
                </span>
                then click
                <button class="btn btn-outline-secondary import-btn inline-btn" aria-hidden="true">
                  <i class="fa-solid fa-upload"></i> Import
                </button>
                to generate the preview tables.
              </p>

              <details class="guide-details">
                <summary>
                  <i class="fa-solid fa-circle-info"></i> Details
                  <span class="summary-pill">What happens</span>
                </summary>
                <div class="details-body">
                  After importing, you’ll see:
                  <ul style="margin:.4rem 0 0; padding-left: 1.25rem;">
                    <li><strong>Invalid Entries</strong> table (needs edits / ready)</li>
                    <li><strong>Verified Entries</strong> table (valid)</li>
                    <li><strong>Import Logs</strong> (history)</li>
                  </ul>

                  <div style="margin-top:10px;">
                    <div class="hint-row" style="margin-top:0;">
                      <span class="hint info"><i class="fa-solid fa-circle-info"></i> Import hides after preview</span>
                    </div>

                    <p style="margin:10px 0 0; color:#6b7280;">
                      After importing, the <strong>Import</strong> button may no longer show. Use the reset control to clear
                      the current preview and start over:
                    </p>

                    <div style="margin-top:10px;">
                      <button type="button" class="btn-ui-reset" aria-hidden="true">
                        <i class="fa-solid fa-rotate-left"></i> Use Clear Imports to reset
                      </button>
                    </div>
                  </div>
                </div>
              </details>

              <div class="video-placeholder outline-cut">Video: Uploading the CSV file + clearing imports</div>
            </div>

            <!-- STEP 3 -->
            <div class="step-card">
              <div class="step-head">
                <h3 class="step-title"><span class="badge-step">3</span> Review & Fix Invalid Entries</h3>
                <span class="summary-pill">Fix</span>
              </div>

              <p class="step-sub inline-paragraph">
                After import, you’ll start in <strong>Invalid Entries</strong>.
                Missing/invalid fields appear highlighted, and each row has a status:
                <span style="color:#b02a37; font-weight:800;">Needs edits</span> or
                <span style="color:#146c43; font-weight:800;">Ready</span>.
              </p>

              <div class="hint-row">
                <span class="hint warn"><i class="fa-solid fa-triangle-exclamation"></i> Needs edits</span>
                <span class="hint ok"><i class="fa-solid fa-circle-check"></i> Ready</span>
                <span class="hint info"><i class="fa-solid fa-circle-info"></i> Tooltips show reasons</span>
              </div>

              <details class="guide-details">
                <summary>
                  <i class="fa-solid fa-ellipsis-vertical"></i> Table actions (Edit Table)
                  <span class="summary-pill">Bulk tools</span>
                </summary>
                <div class="details-body">
                  Click
                  <button type="button" class="btn btn-sm btn-outline-secondary inline-small-btn" aria-hidden="true">
                    <i class="fa-solid fa-pen-to-square"></i> Edit Table
                  </button>
                  to reveal bulk actions:
                  <ul style="margin:.5rem 0 0; padding-left: 1.25rem;">
                    <li>
                      <button class="btn btn-outline-primary btn-sm" aria-hidden="true">
                        <i class="fa-solid fa-check-double"></i> Select All
                      </button>
                      — select rows
                    </li>
                    <li>
                      <button class="btn btn-outline-danger btn-sm" aria-hidden="true">
                        <i class="fa-solid fa-trash-can"></i> Delete
                      </button>
                      — remove selected
                    </li>
                    <li>
                      <button class="btn btn-outline-success btn-sm" aria-hidden="true">
                        <i class="fa-solid fa-copy"></i> Copy
                      </button>
                      — copy selected
                    </li>
                  </ul>
                </div>
              </details>

              <details class="guide-details">
                <summary>
                  <i class="fa-solid fa-ellipsis-vertical"></i> Row actions dropdown (Actions)
                  <span class="summary-pill">Per-entry</span>
                </summary>
                <div class="details-body">
                  Each row has an <strong>Actions</strong> dropdown. It includes:
                  <ul style="margin:.4rem 0 0; padding-left: 1.25rem;">
                    <li><i class="fa-solid fa-user-pen"></i> <strong>Edit</strong> — edit the row</li>
                    <li><i class="fa-solid fa-calendar-days"></i> <strong>Schedule</strong> — view/edit schedule</li>
                    <li><i class="fa-solid fa-image"></i> <strong>Photo</strong> — view photo / detect missing</li>
                    <li><i class="fa-solid fa-arrow-right"></i> <strong>Transfer to Verified</strong> — only enabled when the entry is <strong>Ready</strong></li>
                  </ul>

                  <div class="hint-row" style="margin-top:10px;">
                    <span class="hint ok"><i class="fa-solid fa-calendar-days"></i> green pill = schedule OK</span>
                    <span class="hint warn"><i class="fa-solid fa-calendar-days"></i> red pill = empty schedule</span>
                    <span class="hint ok"><i class="fa-solid fa-image"></i> green pill = photo OK</span>
                    <span class="hint warn"><i class="fa-solid fa-image"></i> red pill = missing/default photo</span>
                  </div>
                </div>
              </details>

              <div class="video-placeholder outline-cut">Video: Reviewing & correcting invalid entries</div>
            </div>

            <!-- STEP 4 -->
            <div class="step-card">
              <div class="step-head">
                <h3 class="step-title"><span class="badge-step">4</span> Validate (Move Ready to Verified)</h3>
                <span class="summary-pill">Validate</span>
              </div>

              <p class="step-sub">
                There is <strong>no separate “Validate” button</strong>.
                Validation now happens by moving entries to <strong>Verified</strong> (only when they’re <strong>Ready</strong>).
              </p>

              <details class="guide-details" open>
                <summary>
                  <i class="fa-solid fa-right-left"></i> Two ways to move entries
                  <span class="summary-pill">Bulk + Single</span>
                </summary>

                <div class="details-body">
                  <ol style="margin:0; padding-left: 1.25rem;">
                    <!-- Photo 1 style -->
                    <li style="margin-bottom:.75rem;">
                      <strong>Single move (per row)</strong>:
                      open the row’s <em>Actions</em> dropdown and click:
                      <div style="margin-top:.45rem; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                        <span class="ui-inline-transfer" aria-hidden="true">
                          <i class="fa-solid fa-arrow-right"></i> Transfer to Verified
                        </span>
                        <span style="color:#6b7280;">— only enabled when the entry is <strong>Ready</strong></span>
                      </div>
                    </li>

                    <!-- Photo 2 style -->
                    <li>
                      <strong>Bulk move (selected rows)</strong>:
                      enable <em>Edit Table</em>, select the <strong>Ready</strong> rows, then click:
                      <div style="margin-top:.55rem;">
                        <button type="button" class="btn-ui-bulk-verified" aria-hidden="true">
                          Move Selected to Verified
                        </button>
                      </div>
                    </li>
                  </ol>
                </div>
              </details>

              <div class="video-placeholder outline-cut">Video: Transfer to Verified (single + bulk)</div>
            </div>

            <!-- STEP 5 -->
            <div class="step-card">
              <div class="step-head">
                <h3 class="step-title"><span class="badge-step">5</span> Proceed to Valid Entries Guide</h3>
                <span class="summary-pill">Submit</span>
              </div>

              <p class="step-sub inline-paragraph">
                After moving entries to Verified, continue to:
                <span class="inline-control">
                  <button class="btn btn-outline-secondary import-btn"
                          type="button"
                          onclick="closeModal('importHandlingModal1'); openModal('importHandlingModal2');">
                    <i class="fas fa-book"></i> Valid Entries Guide
                  </button>
                </span>
                to finalize and submit verified records to the database.
              </p>

              <details class="guide-details">
                <summary>
                  <i class="fa-solid fa-circle-info"></i> Details
                  <span class="summary-pill">Reminder</span>
                </summary>
                <div class="details-body">
                  In the Verified table, you can still use:
                  <ul style="margin:.4rem 0 0; padding-left: 1.25rem;">
                    <li><strong>Edit Table</strong> actions (Select All / Delete / Copy)</li>
                    <li><strong>Actions</strong> dropdown (Edit / Schedule / Photo / Transfer back to Invalid)</li>
                    <li><strong>Submit</strong> button to save verified entries to the database</li>
                  </ul>
                </div>
              </details>
            </div>

          </div><!-- /guide-steps -->
        </div><!-- /guide-content -->

        <!-- Footer -->
        <div class="modal-buttons">
          <button class="modal-btn cancel" type="button" onclick="closeModal('importHandlingModal1')">
            <i class="fa-solid fa-xmark"></i> Close
          </button>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const modal1 = document.getElementById("importHandlingModal1");
  if (!modal1) return;

  // global open/close (shared by both guides)
  window.openModal = function(id){
    const modal = document.getElementById(id);
    if (modal) modal.classList.add("is-open");
  };

  window.closeModal = function(id){
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove("is-open");
  };

  // overlay click closes
  modal1.querySelector(".modal-overlay")?.addEventListener("click", (e) => {
    if (e.target.classList.contains("modal-overlay")) closeModal("importHandlingModal1");
  });

  // ESC closes
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal1.classList.contains("is-open")) {
      closeModal("importHandlingModal1");
    }
  });

  // demo file picker text (guide-only)
  const fileBtn  = modal1.querySelector(".js-demo-filebtn");
  const filePath = modal1.querySelector(".js-demo-filepath");
  if (fileBtn && filePath) {
    fileBtn.addEventListener("click", () => { filePath.textContent = "example_volunteers.csv"; });
  }

  console.log("Guide 1 (Import & Validation) merged + upgraded ✅");
});
</script>
