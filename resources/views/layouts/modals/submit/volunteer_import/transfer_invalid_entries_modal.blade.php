{{-- ============================================================
   ✅ TRANSFER INVALID ENTRIES MODAL — PATCHED
   ✅ Removes local success modal system (AppNoticeModal + ppSuccessModal)
   ✅ Uses Universal Feedback Modal (UFM) ONLY
============================================================ --}}

<style>
/* ===============================
   PROFILE MODAL
================================ */

/* ✅ critical: do NOT let modal-content clip the header text/icons */
#profilePictureModal .modal-content{
  border-radius:14px;
  overflow: visible;            /* <- important */
  background: transparent;      /* inner shell holds bg */
}

/* ✅ inner shell keeps rounded corners without clipping header glyphs */
#profilePictureModal .pp-modal-shell{
  border-radius:14px;
  overflow:hidden;
  background:#fff;
}

#ppVolunteerName{ font-size:1.2rem; font-weight:700; }

/* ===========================================================
   ✅ CONFLICT-SAFE HEADER (RENAMED)
   - avoids global ".modal-header { margin: -... }" collisions
=========================================================== */
#profilePictureModal .pp-modal-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;

  /* tinted strip like reference */
  background: linear-gradient(180deg, rgba(178,0,12,0.14), rgba(178,0,12,0.06));
  border-bottom: 1px solid rgba(178,0,12,0.14);

  padding: 12px 14px;
  min-height: 58px;

  border-top-left-radius: 14px;
  border-top-right-radius: 14px;

  overflow: visible; /* anti-clip */
}

/* left stack */
#profilePictureModal .pp-head-left{
  display:flex;
  align-items:center;
  gap:10px;
  min-width:0;
}
#profilePictureModal .pp-head-icon{
  display:block;
  font-size:1.25rem;
  line-height:1;
  color:#7F0008;
  opacity:.95;
}
#profilePictureModal .pp-head-title{
  margin:0 !important;
  font-weight:900;
  font-size:1.08rem;
  letter-spacing:.2px;
  color:#7F0008;
  line-height:1.25;
  padding:2px 0;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  min-width:0;
}

/* close button */
#profilePictureModal .pp-head-close{
  filter:none;
  opacity:.9;
  width:38px;
  height:38px;
  padding:0 !important;
  margin:0 !important;
  border-radius:10px;
}
#profilePictureModal .pp-head-close:hover{
  opacity:1;
  background: rgba(178,0,12,0.10);
}

/* ===============================
   IMAGE CONTAINER (MAIN MODAL)
================================ */
.pp-image-wrapper{
  position:relative; width:100%;
  display:flex; justify-content:center; align-items:center;
  padding:10px 0;
}
.pp-image{
  max-width:95%;
  max-height:70vh;
  object-fit:contain;
  border-radius:10px;
  border:1px solid #ccc;
}
.pp-expand-btn{
  position:absolute; top:8px; right:8px;
  background:rgba(255,255,255,.9);
  border:1px solid #bbb;
  border-radius:8px;
  padding:6px 9px;
  cursor:pointer;
  font-size:.85rem;
}
#ppNoImageText{ font-size:.95rem; opacity:.8; }

/* ===============================
   BUTTON ROWS
================================ */
.pp-control-row{
  display:flex;
  justify-content:center;
  gap:12px;
  margin-top:1.2rem;
}
.pp-control-row .btn{ min-width:120px; }

/* ===============================
   MORE OPTIONS DROPDOWN
================================ */
.pp-more-dropdown{ margin-top:.8rem; }
.pp-more-dropdown button{ font-size:.9rem; }
.pp-more-dropdown .dropdown-menu a{ font-size:.9rem; padding:6px 14px; }

/* ===============================
   FULLSCREEN OVERLAY (PREVIEW + CROP)
================================ */
.picture-modal-overlay{
  display:none;
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.55);
  justify-content:center;
  align-items:center;
  z-index:999999;
}
.picture-expanded-modal{
  background:#fff;
  border-radius:16px;
  padding:15px 20px 18px;
  width:96%;
  max-width:960px;
  max-height:92vh;
  display:flex;
  flex-direction:column;
  box-shadow:0 10px 35px rgba(0,0,0,.35);
}
.picture-expanded-modal-header{
  display:flex;
  justify-content:space-between;
  align-items:center;
  margin-bottom:8px;
}
.picture-expanded-image-wrap{
  flex:1;
  display:flex;
  justify-content:center;
  align-items:center;
  min-height:0;
}
.picture-expanded-modal img{
  max-width:100%;
  max-height:72vh;
  border-radius:10px;
  border:1px solid #ddd;
  display:block;
  margin:0 auto;
}

/* ===============================
   CROP TOOLBAR
================================ */
#cropControls{
  padding-top:10px;
  border-top:1px solid #eee;
  margin-top:8px;
}
.crop-toolbar{ margin-bottom:6px; }
.crop-toolbar button{ min-width:80px; }

#cropAppliedToast{
  position:absolute;
  top:14px; left:50%;
  transform:translateX(-50%);
  background:#28a745;
  color:#fff;
  padding:6px 14px;
  border-radius:6px;
  font-size:.9rem;
  font-weight:600;
  display:none;
  opacity:0;
  transition:opacity .25s ease-in-out;
  z-index:99999999;
}

@media (max-width:576px){
  .picture-expanded-modal{ padding:12px 12px 14px; max-width:100%; }
}

/* ===========================================================
   ✅ NO CHANGES MODAL — CONFLICT-SAFE (FIX HEADER CLIPPING)
=========================================================== */
#ppNoChangesModal .modal-content{
  border-radius:16px;
  overflow: visible;        /* not a clipping mask */
  background: transparent;
}
#ppNoChangesModal .pp-nc-shell{
  border-radius:16px;
  overflow:hidden;
  background:#fff;
}
#ppNoChangesModal .pp-nc-header{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;

  padding:14px 16px 12px;
  background:#eaf2ff;
  border-bottom:1px solid #d6e6ff;

  border-top-left-radius:16px;
  border-top-right-radius:16px;

  overflow: visible;
}
#ppNoChangesModal .pp-nc-left{
  display:flex;
  align-items:center;
  gap:10px;
  min-width:0;
}
#ppNoChangesModal .pp-nc-icon{
  display:block;
  line-height:1;
  color:#1565c0;
  font-size:1.1rem;
}
#ppNoChangesModal .pp-nc-title{
  margin:0 !important;
  color:#1565c0;
  font-weight:900;
  font-size:1.02rem;
  line-height:1.25;
  padding:2px 0;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
  min-width:0;
}
#ppNoChangesModal .pp-nc-body{
  padding:1.1rem 1.25rem;
  font-size:.98rem;
  line-height:1.5;
  background:#fff;
}
#ppNoChangesModal .pp-nc-footer{
  padding:12px 16px;
  background:#f8fafc;
  border-top:1px solid #eee;

  display:flex;
  justify-content:flex-end;

  border-bottom-left-radius:16px;
  border-bottom-right-radius:16px;
}
</style>

{{-- ============================================================
   ✅ PROFILE PICTURE MODAL
============================================================ --}}
<div class="modal fade" id="profilePictureModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="pp-modal-shell">

        {{-- ✅ FIXED HEADER (conflict-safe) --}}
        <div class="pp-modal-header">
          <div class="pp-head-left">
            <i class="fa-solid fa-image pp-head-icon" aria-hidden="true"></i>
            <h5 class="pp-head-title">Profile Picture</h5>
          </div>
          <button type="button" class="btn-close pp-head-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body text-center">
          <h5 id="ppVolunteerName" class="mb-3"></h5>

          <div class="pp-image-wrapper">
            <img id="ppModalImage" class="pp-image" style="display:none;">
            <p id="ppNoImageText" class="text-muted" style="display:none;">
              <i class="fa-regular fa-image fa-lg"></i><br>No Image Available
            </p>

            <button class="pp-expand-btn" type="button" onclick="expandPicture()">
              <i class="fa-solid fa-up-right-and-down-left-from-center"></i>
            </button>
          </div>

          <div id="ppMoreOptions" class="dropdown pp-more-dropdown" style="display:none;">
            <button class="btn btn-outline-dark btn-sm dropdown-toggle" data-bs-toggle="dropdown">
              More Options
            </button>
            <ul class="dropdown-menu">
              <li><a id="ppOpenButton" class="dropdown-item" href="#" target="_blank">
                <i class="fa-solid fa-eye"></i> Open File</a></li>
              <li><a id="ppDownloadButton" class="dropdown-item" href="#" download>
                <i class="fa-solid fa-download"></i> Download</a></li>
              <li><a id="ppCropButton" class="dropdown-item" href="#">
                <i class="fa-solid fa-crop"></i> Crop Image</a></li>
            </ul>
          </div>

          <div class="pp-control-row">
            <button class="btn btn-outline-primary" type="button" onclick="triggerPPFileInput()">
              <i class="fa-solid fa-upload"></i> Replace
            </button>
            <button class="btn btn-outline-danger" type="button" onclick="previewDefaultPicture()">
              <i class="fa-solid fa-user-xmark"></i> Default
            </button>
          </div>

          <div class="pp-control-row">
            <button id="ppEditSaveBtn" class="btn btn-secondary" type="button" onclick="toggleEditOrSave()">
              <i class="fa-solid fa-pen-to-square"></i> Edit
            </button>
            <button class="btn btn-secondary" type="button" onclick="revertPictureChanges()">
              <i class="fa-solid fa-rotate-left"></i> Revert
            </button>
          </div>
        </div>

      </div><!-- /pp-modal-shell -->

    </div>
  </div>
</div>

{{-- ============================================================
   ✅ FULLSCREEN VIEWER (also used for crop)
============================================================ --}}
<div id="pictureExpandOverlay" class="picture-modal-overlay">
  <div class="picture-expanded-modal">

    <div class="picture-expanded-modal-header">
      <h3 class="m-0">
        <i class="fa-solid fa-image me-2"></i>
        <span id="fullscreenTitleText">Preview</span>
      </h3>

      <div id="fullscreenButtonBar">
        <button class="btn btn-sm btn-outline-primary me-2" type="button" onclick="enterCropMode()">
          <i class="fa-solid fa-crop"></i> Crop
        </button>
        <button type="button" onclick="closeExpandedPicture()" style="border:none;background:none;font-size:1.4rem;">
          <i class="fa-solid fa-xmark" style="color:#b2000c;"></i>
        </button>
      </div>
    </div>

    <div class="picture-expanded-image-wrap">
      <img id="expandedPicture" alt="Preview">
    </div>

    <div id="cropControls" style="display:none;">
      <div class="crop-toolbar d-flex justify-content-center flex-wrap gap-2">
        <button class="btn btn-outline-primary btn-sm" type="button" onclick="setCropPreset(1)">1:1</button>
        <button class="btn btn-outline-primary btn-sm" type="button" onclick="setCropPreset(4/5)">4:5</button>
        <button class="btn btn-outline-primary btn-sm" type="button" onclick="setCropPreset(16/9)">16:9</button>
        <button class="btn btn-outline-dark btn-sm" type="button" onclick="setCropPreset('free')">Free</button>
      </div>

      <div class="crop-toolbar d-flex justify-content-center flex-wrap gap-2">
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="rotateCrop(-90)">Rotate Left</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="rotateCrop(90)">Rotate Right</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="flipCropX()">Flip X</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="flipCropY()">Flip Y</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="zoomCrop(0.2)">Zoom In</button>
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="zoomCrop(-0.2)">Zoom Out</button>
        <button class="btn btn-outline-danger btn-sm" type="button" onclick="resetCrop()">Reset</button>
      </div>

      <div class="d-flex justify-content-center gap-2 mt-2">
        <button class="btn btn-success btn-sm" type="button" onclick="applyCropFullscreen()">
          <i class="fa-solid fa-check"></i> Apply Crop
        </button>
        <button class="btn btn-secondary btn-sm" type="button" onclick="exitCropMode()">
          <i class="fa-solid fa-xmark"></i> Cancel
        </button>
      </div>
    </div>

    <div id="cropAppliedToast">
      <i class="fa-solid fa-check"></i> Crop Applied
    </div>
  </div>
</div>

{{-- ============================================================
   ✅ NO CHANGES MODAL (Bootstrap) — FIXED
============================================================ --}}
<div class="modal fade" id="ppNoChangesModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width:460px;">
    <div class="modal-content">
      <div class="pp-nc-shell">

        <div class="pp-nc-header">
          <div class="pp-nc-left">
            <i class="fa-solid fa-circle-info pp-nc-icon" aria-hidden="true"></i>
            <h5 class="pp-nc-title">No Changes Detected</h5>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="pp-nc-body" id="ppNoChangesBody">
          No changes were made to the profile picture.
        </div>

        <div class="pp-nc-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">OK</button>
        </div>

      </div>
    </div>
  </div>
</div>

{{-- ============================================================
   ✅ Hidden server payloads (single copy)
============================================================ --}}
@if(session('success_schedule'))
  <div id="__pp_success_html__" style="display:none;">
    {!! session('success_schedule') !!}
  </div>
@endif

@if($errors->any())
  <div id="__pp_errors_html__" style="display:none;">
    <ul class="mb-0">
      @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif

{{-- ============================================================
   ✅ Backend form (OFFSCREEN, NOT display:none) — critical for file uploads
============================================================ --}}
<form id="pictureForm" method="POST" enctype="multipart/form-data"
      style="position:fixed; left:-99999px; top:-99999px; width:1px; height:1px; opacity:0;">
  @csrf
  <input type="hidden" name="index" id="ppFormIndex">
  <input type="hidden" name="type"  id="ppFormType">

  {{-- ✅ This is the ONLY picker we use for Replace (trusted by browser) --}}
  <input type="file" name="file" id="ppFormFile" accept="image/*">
</form>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<script>
/* =====================================================
   GLOBAL STATE
===================================================== */
let currentVolunteerIndex = null;
let currentVolunteerType  = null;
let originalPictureSrc    = null;

let pendingUploadFile = null;  // used for preview + crop + save guard
let pendingDefault    = false;

let hasUserChanged    = false;
let isEditingPicture  = false;

let cropper    = null;
let isCropMode = false;
let flipX      = 1;
let flipY      = 1;

const UPDATE_PICTURE_URL = @json(route('volunteer.import.updatePicture'));
const DEFAULT_PIC_SRC    = "/storage/defaults/default_user.png";

const $id = (id) => document.getElementById(id);

/* =====================================================
   ✅ UFM WRAPPER (safe)
===================================================== */
function ufmShow({ variant='info', title='Notice', subtitle='', html='', source='' } = {}) {
  if (window.FeedbackModal?.show) {
    window.FeedbackModal.show({ variant, title, subtitle, html, source });
    return true;
  }
  // fallback (should be rare)
  alert((title ? title + "\n\n" : "") + String(html||'').replace(/<[^>]+>/g,' ').trim());
  return false;
}

/* =====================================================
   ✅ Success/Error Presenter (replaces ppSuccessModal)
===================================================== */
function openPPSuccessModal({ title="Changes saved", subtitle="Entry updated successfully.", html="" } = {}) {
  ufmShow({
    variant: (String(title).toLowerCase().includes('fail') || String(title).toLowerCase().includes('error')) ? 'error' : 'success',
    title,
    subtitle,
    html,
    source: 'transfer_modal_pp_flash'
  });
}

/* =====================================================
   HELPERS
===================================================== */
function normalizeSrc(src){
  src = String(src || "").trim();
  try {
    const u = new URL(src, window.location.origin);
    return u.pathname.replace(/\/+$/,"");
  } catch(e){
    return src.split("?")[0].replace(/\/+$/,"");
  }
}
function showNoChanges(msg){
  $id("ppNoChangesBody").textContent = msg || "No changes were made to the profile picture.";
  new bootstrap.Modal($id("ppNoChangesModal")).show();
}
function originalWasDefault(){
  const orig = normalizeSrc(originalPictureSrc || "");
  const def  = normalizeSrc(DEFAULT_PIC_SRC);
  return orig && orig === def;
}

/* =====================================================
   AUTO OPEN: success OR errors (NOW -> UFM)
===================================================== */
document.addEventListener("DOMContentLoaded", () => {
  const errHtml = $id("__pp_errors_html__")?.innerHTML?.trim();
  if (errHtml) {
    openPPSuccessModal({
      title: "Save failed",
      subtitle: "The server rejected the request.",
      html: `<div style="color:#b2000c;font-weight:900;margin-bottom:8px;">Fix these issues:</div>${errHtml}`
    });
    return;
  }

  const successHtml = $id("__pp_success_html__")?.innerHTML?.trim();
  if (successHtml) {
    openPPSuccessModal({
      title: "Changes saved",
      subtitle: "Entry updated successfully.",
      html: successHtml
    });
  }

  $id("profilePictureModal")?.addEventListener("hidden.bs.modal", () => {
    resetPictureState();
    exitCropMode();
    closeExpandedPicture();
  });
});

/* =====================================================
   RESET STATE
===================================================== */
function resetEditSaveButton(){
  const btn = $id("ppEditSaveBtn");
  if (!btn) return;
  btn.innerHTML = "<i class='fa-solid fa-pen-to-square'></i> Edit";
  btn.classList.remove("btn-success");
  btn.classList.add("btn-secondary");
}
function resetPictureState(){
  currentVolunteerIndex = null;
  currentVolunteerType  = null;
  originalPictureSrc    = null;

  pendingUploadFile = null;
  pendingDefault    = false;
  hasUserChanged    = false;
  isEditingPicture  = false;

  // also clear the form file input
  const formFile = $id("ppFormFile");
  if (formFile) formFile.value = "";

  resetEditSaveButton();

  const img  = $id("ppModalImage");
  const text = $id("ppNoImageText");
  const more = $id("ppMoreOptions");

  img.src = "";
  img.style.display = "none";
  text.style.display = "block";
  more.style.display = "none";
}

/* =====================================================
   OPEN MODAL (critical: index/type MUST exist)
===================================================== */
function openImageModalFromButton(btn){
  currentVolunteerIndex = btn.dataset.entryIndex ?? null;
  currentVolunteerType  = btn.dataset.entryType ?? null;

  if (currentVolunteerIndex === null || currentVolunteerIndex === "" || isNaN(Number(currentVolunteerIndex))) {
    console.error("Missing data-entry-index on opener button", btn);
    showNoChanges("Open failed: button missing data-entry-index.");
    return;
  }
  if (!currentVolunteerType || !["valid","invalid"].includes(String(currentVolunteerType))) {
    console.error("Missing data-entry-type on opener button", btn);
    showNoChanges("Open failed: button missing data-entry-type (valid/invalid).");
    return;
  }

  const src  = btn.dataset.pictureSrc || "";
  const name = btn.dataset.volName || "Volunteer";

  originalPictureSrc = src;
  $id("ppVolunteerName").textContent = name;

  const img  = $id("ppModalImage");
  const text = $id("ppNoImageText");
  const more = $id("ppMoreOptions");

  if (src) {
    img.src = src;
    img.style.display = "block";
    text.style.display = "none";
    more.style.display = "block";
    $id("ppOpenButton").href     = src;
    $id("ppDownloadButton").href = src;
  } else {
    img.style.display = "none";
    text.style.display = "block";
    more.style.display = "none";
    $id("ppOpenButton").href     = "#";
    $id("ppDownloadButton").href = "#";
  }

  pendingUploadFile = null;
  pendingDefault    = false;
  hasUserChanged    = false;
  isEditingPicture  = false;

  // clear form file input each open
  $id("ppFormFile").value = "";

  resetEditSaveButton();

  $id("ppCropButton").onclick = (e) => { e.preventDefault(); openCropFromMainModal(); };

  new bootstrap.Modal($id("profilePictureModal")).show();
}

/* =====================================================
   FULLSCREEN PREVIEW
===================================================== */
function expandPicture(){
  exitCropMode();
  const src = $id("ppModalImage").src;
  if (!src) return;

  $id("expandedPicture").src = src;
  $id("fullscreenTitleText").textContent = "Preview";
  $id("pictureExpandOverlay").style.display = "flex";
}
function closeExpandedPicture(){
  exitCropMode();
  $id("pictureExpandOverlay").style.display = "none";
}
function openCropFromMainModal(){
  const src = $id("ppModalImage").src;
  if (!src) return;

  const expanded = $id("expandedPicture");
  expanded.src = src;

  $id("fullscreenTitleText").textContent = "Crop Image";
  $id("pictureExpandOverlay").style.display = "flex";

  expanded.onload = () => enterCropMode();
}

/* =====================================================
   CROP MODE
===================================================== */
function enterCropMode(){
  const img = $id("expandedPicture");
  if (!img.src) return;

  isCropMode = true;
  flipX = flipY = 1;

  $id("fullscreenButtonBar").style.display = "none";
  $id("cropControls").style.display = "block";

  if (cropper) cropper.destroy();
  cropper = new Cropper(img, { aspectRatio:1, viewMode:1, autoCropArea:1, background:false });
}
function exitCropMode(){
  isCropMode = false;
  $id("fullscreenButtonBar").style.display = "block";
  $id("cropControls").style.display = "none";

  if (cropper) { cropper.destroy(); cropper = null; }
}
function rotateCrop(d){ cropper && cropper.rotate(d); }
function zoomCrop(v){ cropper && cropper.zoom(v); }
function flipCropX(){ if(cropper){ flipX=-flipX; cropper.scaleX(flipX);} }
function flipCropY(){ if(cropper){ flipY=-flipY; cropper.scaleY(flipY);} }
function resetCrop(){ if(!cropper) return; cropper.reset(); flipX=flipY=1; }
function setCropPreset(ratio){
  if(!cropper) return;
  if(ratio === "free") cropper.setAspectRatio(NaN);
  else cropper.setAspectRatio(ratio);
}

/* =====================================================
   CROP APPLY + TOAST
===================================================== */
let cropToastTimer = null;
function showCropAppliedToast(){
  const toast = $id("cropAppliedToast");
  if(!toast) return;

  toast.style.display = "block";
  requestAnimationFrame(()=> toast.style.opacity = "1");

  if(cropToastTimer) clearTimeout(cropToastTimer);
  cropToastTimer = setTimeout(()=>{
    toast.style.opacity = "0";
    setTimeout(()=> toast.style.display="none", 250);
  }, 1500);
}

function applyCropFullscreen(){
  if (!cropper) return;

  cropper.getCroppedCanvas().toBlob(blob => {
    if (!blob) return;

    // ✅ Use JPEG to avoid massive PNG uploads
    pendingUploadFile = new File([blob], "cropped.jpg", { type:"image/jpeg" });
    pendingUploadFile.__fromCrop = true;

    pendingDefault = false;
    hasUserChanged = true;

    const reader = new FileReader();
    reader.onload = e => {
      const src = e.target.result;
      $id("expandedPicture").src = src;
      $id("ppModalImage").src    = src;
      $id("ppModalImage").style.display = "block";
      $id("ppNoImageText").style.display = "none";
      $id("ppMoreOptions").style.display = "none";
    };
    reader.readAsDataURL(pendingUploadFile);

    if (!isEditingPicture) toggleEditOrSave();

    exitCropMode();
    showCropAppliedToast();
  }, "image/jpeg", 0.85);
}

/* =====================================================
   ✅ FILE REPLACE (FIXED)
   Use the REAL form input (#ppFormFile). No DataTransfer here.
===================================================== */
function triggerPPFileInput(){
  const input = $id("ppFormFile");
  input.value = "";
  input.click();

  input.onchange = () => {
    if (!input.files || !input.files.length) return;

    pendingUploadFile = input.files[0];
    pendingUploadFile.__fromCrop = false;

    pendingDefault    = false;
    hasUserChanged    = true;

    if (!isEditingPicture) toggleEditOrSave();

    const reader = new FileReader();
    reader.onload = e => {
      $id("ppModalImage").src = e.target.result;
      $id("ppModalImage").style.display = "block";
      $id("ppNoImageText").style.display = "none";
      $id("ppMoreOptions").style.display = "none";
    };
    reader.readAsDataURL(pendingUploadFile);
  };
}

/* =====================================================
   DEFAULT PREVIEW
===================================================== */
function previewDefaultPicture(){
  if (pendingDefault && !pendingUploadFile) {
    showNoChanges("Default profile picture is already selected.");
    return;
  }
  if (originalWasDefault() && !pendingUploadFile) {
    showNoChanges("Already using the default profile picture.");
    return;
  }

  pendingUploadFile = null;
  pendingDefault    = true;
  hasUserChanged    = true;

  $id("ppFormFile").value = "";

  if (!isEditingPicture) toggleEditOrSave();

  $id("ppModalImage").src = DEFAULT_PIC_SRC;
  $id("ppModalImage").style.display = "block";
  $id("ppNoImageText").style.display = "none";
  $id("ppMoreOptions").style.display = "none";
}

/* =====================================================
   REVERT
===================================================== */
function revertPictureChanges(){
  pendingUploadFile = null;
  pendingDefault    = false;
  hasUserChanged    = false;
  isEditingPicture  = false;

  $id("ppFormFile").value = "";

  resetEditSaveButton();

  const img  = $id("ppModalImage");
  const text = $id("ppNoImageText");
  const more = $id("ppMoreOptions");

  if (originalPictureSrc) {
    img.src = originalPictureSrc;
    img.style.display = "block";
    text.style.display = "none";
    more.style.display = "block";
    $id("ppOpenButton").href = originalPictureSrc;
    $id("ppDownloadButton").href = originalPictureSrc;
  } else {
    img.src = "";
    img.style.display = "none";
    text.style.display = "block";
    more.style.display = "none";
    $id("ppOpenButton").href = "#";
    $id("ppDownloadButton").href = "#";
  }
}

/* =====================================================
   EDIT / SAVE
===================================================== */
function toggleEditOrSave(){
  const btn = $id("ppEditSaveBtn");

  if (!isEditingPicture) {
    isEditingPicture = true;
    btn.innerHTML = "<i class='fa-solid fa-save'></i> Save";
    btn.classList.remove("btn-secondary");
    btn.classList.add("btn-success");
    return;
  }

  if (!hasUserChanged) {
    showNoChanges("No changes were made to the profile picture.");
    return;
  }

  if (pendingDefault && !pendingUploadFile && originalWasDefault()) {
    showNoChanges("Already using the default profile picture.");
    return;
  }

  savePictureChanges();
}

/* =====================================================
   SAVE (Replace fixed)
===================================================== */
function savePictureChanges(){
  const form = $id("pictureForm");
  if (!form) return;

  if (currentVolunteerIndex === null || currentVolunteerIndex === "" || isNaN(Number(currentVolunteerIndex))) {
    showNoChanges("Save failed: missing entry index (data-entry-index).");
    console.error("Invalid index:", currentVolunteerIndex);
    return;
  }
  if (!currentVolunteerType || !["valid","invalid"].includes(String(currentVolunteerType))) {
    showNoChanges("Save failed: missing entry type (data-entry-type valid/invalid).");
    console.error("Invalid type:", currentVolunteerType);
    return;
  }

  [...form.querySelectorAll("input[name='set_default']")].forEach(el => el.remove());

  $id("ppFormIndex").value = Number(currentVolunteerIndex);
  $id("ppFormType").value  = String(currentVolunteerType);
  form.action = UPDATE_PICTURE_URL;

  if (pendingDefault && !pendingUploadFile) {
    $id("ppFormFile").value = "";
    const hidden = document.createElement("input");
    hidden.type = "hidden";
    hidden.name = "set_default";
    hidden.value = "1";
    form.appendChild(hidden);
  }

  if (pendingUploadFile && pendingUploadFile.__fromCrop === true) {
    const dt = new DataTransfer();
    dt.items.add(pendingUploadFile);
    $id("ppFormFile").files = dt.files;
  }

  bootstrap.Modal.getInstance($id("profilePictureModal"))?.hide();
  form.submit();
}
</script>

<script>
/* ===========================================================
   ✅ GLOBAL "SEE MORE" HANDLER (UFM)
   - Handles controller links: .update-details-link / .success-details-link / etc.
   - Supports class schedule links: .cs-see-more (data-cs-details)
=========================================================== */
(function(){
  if (window.__ufmDelegatedBound) return;
  window.__ufmDelegatedBound = true;

  function decodeB64Utf8(raw) {
    const s = String(raw || '').trim().replace(/\s+/g,'');
    if (!s) return '';
    if (window.FeedbackModal?.decodeBase64Utf8) {
      return window.FeedbackModal.decodeBase64Utf8(s);
    }
    try { return decodeURIComponent(escape(atob(s))); }
    catch (e) { try { return atob(s); } catch (_) { return ''; } }
  }

  document.addEventListener('click', function(e){
    const el = e.target.closest(
      '.cs-see-more,' +
      '.update-details-link,' +
      '.move-details-link,' +
      '.deleted-details-link,' +
      '.restored-details-link,' +
      '.reset-details-link,' +
      '.success-details-link,' +
      '.error-details-link,' +
      '.show-modal-details,' +
      '.see-more-link,' +
      '[data-ufm-details]'
    );
    if (!el) return;

    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    let payload =
      el.getAttribute('data-ufm-details') ||
      el.getAttribute('data-cs-details') ||
      el.getAttribute('data-details') ||
      '';

    const looksB64 = /^[A-Za-z0-9+/=]+$/.test(payload) && payload.length > 40;
    const html = looksB64 ? decodeB64Utf8(payload) : String(payload || '').trim();

    if (!html) {
      ufmShow({
        variant: 'warning',
        title: 'Details unavailable',
        subtitle: 'No payload found or decoding failed.',
        html: "<div style='font-weight:900;color:#b2000c;'>No details available.</div>",
        source: 'ufm_details_missing'
      });
      return;
    }

    const cls = (el.className || '').toLowerCase();
    const isErr = cls.includes('error');
    const isOk  = cls.includes('success') || cls.includes('update');

    ufmShow({
      variant: isErr ? 'error' : (isOk ? 'success' : 'info'),
      title: 'Details',
      subtitle: isErr ? "Here's what went wrong." : "Here's the full breakdown.",
      html,
      source: 'ufm_details_click'
    });
  }, true); // capture
})();
</script>
