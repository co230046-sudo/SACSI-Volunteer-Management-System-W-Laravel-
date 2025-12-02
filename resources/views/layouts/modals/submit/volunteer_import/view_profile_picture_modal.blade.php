<style>
/* ===============================
   PROFILE MODAL
================================ */
#profilePictureModal .modal-content {
    border-radius: 14px;
    overflow: hidden;
}
#ppVolunteerName {
    font-size: 1.2rem;
    font-weight: 700;
}

/* Prevent weird scroll with multiple modals */
.modal {
    overflow-y: auto !important;
}

/* ===============================
   IMAGE CONTAINER (MAIN MODAL)
================================ */
/* Larger preview, same style, no layout breaks */
.pp-image-wrapper {
    position: relative;
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 10px 0;
}

.pp-image {
    max-width: 95%;
    max-height: 70vh;    /* bigger image height */
    object-fit: contain;
    border-radius: 10px;
    border: 1px solid #ccc;
}

.pp-expand-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(255,255,255,0.9);
    border: 1px solid #bbb;
    border-radius: 8px;
    padding: 6px 9px;
    cursor: pointer;
    font-size: 0.85rem;
}
#ppNoImageText {
    font-size: 0.95rem;
    opacity: .8; 
}

/* ===============================
   BUTTON ROWS
================================ */
.pp-control-row {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 1.2rem;
}
.pp-control-row .btn {
    min-width: 120px;
}

/* ===============================
   MORE OPTIONS DROPDOWN
================================ */
.pp-more-dropdown {
    margin-top: 0.8rem;
}
.pp-more-dropdown button {
    font-size: 0.9rem;
}
.pp-more-dropdown .dropdown-menu a {
    font-size: 0.9rem;
    padding: 6px 14px;
}

/* ===============================
   FULLSCREEN OVERLAY (PREVIEW + CROP)
================================ */
.picture-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    justify-content: center;
    align-items: center;
    z-index: 999999;
}

.picture-expanded-modal {
    background: #fff;
    border-radius: 16px;
    padding: 15px 20px 18px;
    width: 96%;
    max-width: 960px;         /* size limit */
    max-height: 92vh;         /* adaptive but bounded */
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 35px rgba(0,0,0,0.35);
}

/* Header inside fullscreen */
.picture-expanded-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

/* Wrapper so image + cropper are centered and don't touch edges */
.picture-expanded-image-wrap {
    flex: 1;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 0; /* allow flexbox to shrink properly */
}

.picture-expanded-modal img {
    max-width: 100%;
    max-height: 72vh;   /* leave room for toolbar */
    border-radius: 10px;
    border: 1px solid #ddd;
    display: block;
    margin: 0 auto;
}

/* ===============================
   CROP TOOLBAR
================================ */
#cropControls {
    padding-top: 10px;
    border-top: 1px solid #eee;
    margin-top: 8px;
}

.crop-toolbar button {
    min-width: 80px;
}

/* spacing for tool groups */
.crop-toolbar {
    margin-bottom: 6px;
}

#cropAppliedToast {
    position: absolute;
    top: 14px;
    left: 50%;
    transform: translateX(-50%);
    background: #28a745;
    color: #fff;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 600;
    display: none;        /* stays hidden until JS shows it */
    opacity: 0;
    transition: opacity 0.25s ease-in-out;
    z-index: 99999999;
}

/* tiny screens: stack toolbar nicely */
@media (max-width: 576px) {
    .picture-expanded-modal {
        padding: 12px 12px 14px;
        max-width: 100%;
    }
}
</style>

<!-- PROFILE PICTURE MODAL -->
<div class="modal fade" id="profilePictureModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header custom-modal-header">
        <h5 class="modal-title">
          <i class="fa-solid fa-image me-2"></i> Profile Picture
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

        <!-- MORE OPTIONS -->
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

        <input type="file" id="ppFileInput" accept="image/*" class="d-none">

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
    </div>
  </div>
</div>

<!-- FULLSCREEN VIEWER (ALSO USED FOR CROPPING) -->
<div id="pictureExpandOverlay" class="picture-modal-overlay">
    <div class="picture-expanded-modal">

        <!-- HEADER -->
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

        <!-- IMAGE AREA -->
        <div class="picture-expanded-image-wrap">
            <img id="expandedPicture" alt="Preview">
        </div>

        <!-- CROP TOOLBAR -->
        <div id="cropControls" style="display:none;">

            <!-- PRESETS -->
            <div class="crop-toolbar d-flex justify-content-center flex-wrap gap-2">
                <button class="btn btn-outline-primary btn-sm" type="button" onclick="setCropPreset(1)">1:1</button>
                <button class="btn btn-outline-primary btn-sm" type="button" onclick="setCropPreset(4/5)">4:5</button>
                <button class="btn btn-outline-primary btn-sm" type="button" onclick="setCropPreset(16/9)">16:9</button>
                <button class="btn btn-outline-dark btn-sm" type="button" onclick="setCropPreset('free')">Free</button>
            </div>

            <!-- ADVANCED TOOLS -->
            <div class="crop-toolbar d-flex justify-content-center flex-wrap gap-2">
                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="rotateCrop(-90)">Rotate Left</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="rotateCrop(90)">Rotate Right</button>

                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="flipCropX()">Flip X</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="flipCropY()">Flip Y</button>

                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="zoomCrop(0.2)">Zoom In</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="zoomCrop(-0.2)">Zoom Out</button>

                <button class="btn btn-outline-danger btn-sm" type="button" onclick="resetCrop()">Reset</button>
            </div>

            <!-- APPLY / CANCEL -->
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

<!-- ===========================================================
     NO CHANGES MADE MODAL (INFO – BLUE)
=========================================================== -->
<div id="noChangesModal" class="submit-error-modal">
    <div class="submit-error-overlay">
        <div class="submit-error-box" style="max-width:420px;">

            <div class="submit-error-header" style="color:#1565c0;">
                <i class="fa-solid fa-circle-info submit-error-icon"
                   style="color:#1565c0;"></i>
                <h2 style="color:#1565c0;">No Changes Detected</h2>
            </div>

            <hr class="submit-error-separator">

            <div class="submit-error-text" style="text-align:center;">
                No changes were made to the profile picture.
            </div>

            <div class="submit-error-buttons">
                <button type="button" id="closeNoChangesModal" class="file-btn-gray">
                    OK
                </button>
            </div>

        </div>
    </div>
</div>

<!-- HIDDEN BACKEND FORM -->
<form id="pictureForm" method="POST" enctype="multipart/form-data" style="display:none;">
    @csrf
    <input type="hidden" name="index" id="ppFormIndex">
    <input type="hidden" name="type"  id="ppFormType">
    <input type="file"   name="file" id="ppFormFile">
</form>

<!-- CROPPERJS CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <script>
    /* =====================================================
    GLOBAL STATE
    ===================================================== */
    let currentVolunteerIndex = null;
    let currentVolunteerType  = null;
    let originalPictureSrc    = null;

    let pendingUploadFile = null;
    let pendingDefault    = false;

    let hasUserChanged    = false;    // NEW — blocks saving unless true
    let hasAppliedCrop    = false;    // NEW — for crop toast

    let isEditingPicture  = false;

    let cropper    = null;
    let isCropMode = false;
    let flipX      = 1;
    let flipY      = 1;

    const UPDATE_PICTURE_URL = @json(route('volunteer.import.updatePicture'));

    const $id = (id) => document.getElementById(id);

    /* =====================================================
    RESET STATE
    ===================================================== */
    function resetPictureState() {
        currentVolunteerIndex = null;
        currentVolunteerType  = null;
        originalPictureSrc    = null;

        pendingUploadFile = null;
        pendingDefault    = false;
        hasUserChanged    = false;
        hasAppliedCrop    = false;
        isEditingPicture  = false;

        resetEditSaveButton();

        const img  = $id("ppModalImage");
        const text = $id("ppNoImageText");
        const more = $id("ppMoreOptions");

        img.src = "";
        img.style.display = "none";
        text.style.display = "block";
        more.style.display = "none";
    }

    document.addEventListener("DOMContentLoaded", () => {
        const modalEl = $id("profilePictureModal");
        if (modalEl) {
            modalEl.addEventListener("hidden.bs.modal", resetPictureState);
        }

        $id("closeNoChangesModal").onclick = () =>
            $id("noChangesModal").classList.remove("active");
    });

    /* =====================================================
    OPEN MODAL
    ===================================================== */
    function openImageModalFromButton(btn) {
        currentVolunteerIndex = btn.dataset.entryIndex;
        currentVolunteerType  = btn.dataset.entryType;

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
        hasAppliedCrop    = false;
        isEditingPicture  = false;

        resetEditSaveButton();

        $id("ppCropButton").onclick = (e) => {
            e.preventDefault();
            openCropFromMainModal();
        };

        new bootstrap.Modal($id("profilePictureModal")).show();
    }

    /* =====================================================
    FULLSCREEN PREVIEW
    ===================================================== */
    function expandPicture() {
        exitCropMode();
        const src = $id("ppModalImage").src;
        if (!src) return;

        $id("expandedPicture").src = src;
        $id("fullscreenTitleText").textContent = "Preview";
        $id("pictureExpandOverlay").style.display = "flex";
    }

    function closeExpandedPicture() {
        exitCropMode();
        $id("pictureExpandOverlay").style.display = "none";
    }

    function openCropFromMainModal() {
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
    function enterCropMode() {
        const img = $id("expandedPicture");
        if (!img.src) return;

        isCropMode = true;
        flipX = flipY = 1;

        $id("fullscreenButtonBar").style.display = "none";
        $id("cropControls").style.display = "block";

        if (cropper) cropper.destroy();

        cropper = new Cropper(img, {
            aspectRatio: 1,
            viewMode: 1,
            autoCropArea: 1,
            background: false
        });
    }

    function exitCropMode() {
        isCropMode = false;
        $id("fullscreenButtonBar").style.display = "block";
        $id("cropControls").style.display = "none";

        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    }

    function rotateCrop(d) { cropper && cropper.rotate(d); }
    function zoomCrop(v)   { cropper && cropper.zoom(v); }

    function flipCropX() { if (cropper){ flipX=-flipX; cropper.scaleX(flipX);} }
    function flipCropY() { if (cropper){ flipY=-flipY; cropper.scaleY(flipY);} }

    function resetCrop() {
        if (!cropper) return;
        cropper.reset();
        flipX = flipY = 1;
    }

    /* =====================================================
    CROP APPLIED (TOAST + STATE)
    ===================================================== */
    function showCropAppliedToast() {
        const toast = $id("cropAppliedToast");
        toast.style.display = "block";

        setTimeout(() => (toast.style.opacity = 1), 20);

        setTimeout(() => {
            toast.style.opacity = 0;
            setTimeout(() => (toast.style.display = "none"), 250);
        }, 1500);
    }

    function applyCropFullscreen() {
        if (!cropper) return;

        cropper.getCroppedCanvas().toBlob(blob => {
            if (!blob) return;

            pendingUploadFile = new File([blob],"cropped.png",{type:"image/png"});
            pendingDefault    = false;
            hasUserChanged    = true;
            hasAppliedCrop    = true;

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

            showCropAppliedToast();

            if (!isEditingPicture) toggleEditOrSave();
            exitCropMode();
        });
    }

    /* =====================================================
    FILE REPLACE
    ===================================================== */
    function triggerPPFileInput() {
        const input = $id("ppFileInput");
        input.value = "";
        input.click();

        input.onchange = () => {
            if (!input.files.length) return;
            pendingUploadFile = input.files[0];
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
    function previewDefaultPicture() {
        pendingUploadFile = null;
        pendingDefault    = true;
        hasUserChanged    = true;
        hasAppliedCrop    = false;

        if (!isEditingPicture) toggleEditOrSave();

        $id("ppModalImage").src = "/storage/defaults/default_user.png";
        $id("ppModalImage").style.display = "block";
        $id("ppNoImageText").style.display = "none";
        $id("ppMoreOptions").style.display = "none";
    }

    /* =====================================================
    REVERT TO ORIGINAL
    ===================================================== */
    function revertPictureChanges() {
        pendingUploadFile = null;
        pendingDefault    = false;
        hasUserChanged    = false;
        hasAppliedCrop    = false;

        isEditingPicture  = false;
        resetEditSaveButton();

        const img = $id("ppModalImage");
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
    EDIT / SAVE BUTTON
    ===================================================== */
    function toggleEditOrSave() {
        const btn = $id("ppEditSaveBtn");

        if (!isEditingPicture) {
            isEditingPicture = true;
            btn.innerHTML = "<i class='fa-solid fa-save'></i> Save";
            btn.classList.replace("btn-secondary", "btn-success");
        } else {

            if (!hasUserChanged) {
                $id("noChangesModal").classList.add("active");
                return;
            }

            savePictureChanges();
        }
    }

    function resetEditSaveButton() {
        const btn = $id("ppEditSaveBtn");
        btn.innerHTML = "<i class='fa-solid fa-pen-to-square'></i> Edit";
        btn.classList.replace("btn-success", "btn-secondary");
    }

    /* =====================================================
    SAVE PICTURE
    ===================================================== */
    function savePictureChanges() {
        const form = $id("pictureForm");

        [...form.querySelectorAll("input[name='set_default']")].forEach(el => el.remove());

        $id("ppFormIndex").value = currentVolunteerIndex;
        $id("ppFormType").value  = currentVolunteerType;
        form.action = UPDATE_PICTURE_URL;

        if (pendingDefault && !pendingUploadFile) {
            $id("ppFormFile").value = "";
            const hidden = document.createElement("input");
            hidden.type = "hidden";
            hidden.name = "set_default";
            hidden.value = "1";
            form.appendChild(hidden);
        }

        if (pendingUploadFile) {
            const dt = new DataTransfer();
            dt.items.add(pendingUploadFile);
            $id("ppFormFile").files = dt.files;
        }

        const modalInstance = bootstrap.Modal.getInstance($id("profilePictureModal"));
        if (modalInstance) modalInstance.hide();

        form.submit();
    }
    </script>

    <script>
    let cropToastTimer = null;

    /* SHOW TOAST */
    function showCropAppliedToast() {
        const toast = document.getElementById("cropAppliedToast");
        if (!toast) return;

        toast.style.display = "block";

        requestAnimationFrame(() => {
            toast.style.opacity = "1";
        });

        if (cropToastTimer) clearTimeout(cropToastTimer);

        cropToastTimer = setTimeout(() => {
            toast.style.opacity = "0";

            setTimeout(() => {
                toast.style.display = "none";
            }, 250);
        }, 1500);
    }

    /* HIDE IMMEDIATELY (when closing crop mode) */
    function hideCropAppliedToast() {
        const toast = document.getElementById("cropAppliedToast");
        if (!toast) return;

        if (cropToastTimer) clearTimeout(cropToastTimer);

        toast.style.opacity = "0";
        toast.style.display = "none";
    }

    /* Hook into Apply Crop button */
    function applyCropFullscreen() {
        if (!cropper) return;

        cropper.getCroppedCanvas().toBlob(blob => {
            if (!blob) return;

            pendingUploadFile = new File([blob], "cropped.png", { type: "image/png" });
            pendingDefault    = false;

            const reader = new FileReader();
            reader.onload = e => {
                document.getElementById("expandedPicture").src = e.target.result;
                document.getElementById("ppModalImage").src    = e.target.result;
            };
            reader.readAsDataURL(pendingUploadFile);

            if (!isEditingPicture) toggleEditOrSave();

            exitCropMode();
            showCropAppliedToast();   // ← show toast after crop is applied
        });
    }
</script>
