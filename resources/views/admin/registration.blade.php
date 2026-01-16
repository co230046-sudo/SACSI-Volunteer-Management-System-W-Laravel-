@include('layouts.navbar')
@include('layouts.page_loader')
@include('layouts.back_button')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    background: #f4f6fa !important;
    font-family: 'Nunito', sans-serif;
    margin-top: 90px !important;
}

.register-card {
    max-width: 650px;
    margin: auto;
    background: #ffffff;
    border-radius: 18px;
    padding: 30px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(15px); }
    to { opacity: 1; transform: translateY(0); }
}

.btn-submit {
    background: #b3263a !important;
    border-radius: 10px;
    padding: 12px;
    font-size: 16px;
    font-weight: bold;
}
.btn-submit:hover {
    background: #8f1f30 !important;
}

.form-label {
    font-weight: 700;
}

.profile-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #b3263a;
}

.input-wrapper {
    position: relative;
    width: 100%;
}

.input-wrapper .icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #888;
}

.input-wrapper input {
    padding-left: 38px;
}

.password-hint,
#password-match {
    font-size: 13px;
}

/* PHOTO MODAL */
.photo-modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    justify-content: center;
    align-items: center;
}

.photo-modal-content {
    position: relative;
    background: #fff;
    padding: 15px;
    border-radius: 12px;
    max-width: 420px;
}

.modal-photo {
    width: 100%;
    max-height: 420px;
    object-fit: contain;
    border-radius: 10px;
}

.close-photo-modal {
    position: absolute;
    top: 8px; right: 12px;
    font-size: 28px;
    cursor: pointer;
    color: #b3263a;
    font-weight: bold;
}
input[type="file"] {
    display: none !important;
}
.upload-wrapper {
    display: flex;
    justify-content: center;
}

.profile-preview {
    width: 130px;
    height: 130px;
    border-radius: 50%;
    border: 4px solid #b3263a;
    object-fit: cover;
}

</style>


<div class="container mt-5 mb-5">
    <div class="register-card">

        <h3 class="text-center fw-bold mb-3">
            <i class="fa fa-user-plus text-danger"></i> Register New Admin
        </h3>

        <p class="text-center text-muted mb-4">
            Create a new administrator account for the system.
        </p>

        @if ($errors->any())
            <div class="alert alert-danger">
                <strong>There were some errors:</strong>
                <ul class="mt-2 mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <!-- FORM START -->
        <form action="{{ route('admin.user.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <!-- PROFILE PICTURE -->
            <div class="upload-wrapper text-center mb-4">

                <div class="d-flex flex-column align-items-center">

                    <!-- Preview Image -->
                    <img id="previewImg" class="profile-preview mb-2"
                        src="{{ asset('assets/adminpic.png') }}" alt="">

                    <!-- Hidden File Input -->
                    <input type="file" id="profileInput" name="profile_picture"
                        accept="image/*" onchange="handleFileUpload(this)" hidden>

                    <!-- Upload Button -->
                    <button type="button" class="btn btn-outline-secondary mt-2"
                            onclick="document.getElementById('profileInput').click()">
                        <i class="fa fa-upload"></i> Upload Picture
                    </button>

                    <!-- File Name -->
                    <small id="file-name" class="text-muted mt-2">
                        No file chosen
                    </small>

                    <!-- Preview Image Button -->
                    <button type="button" id="see-photo-btn"
                            class="btn btn-outline-primary btn-sm mt-2"
                            style="display:none;"
                            onclick="openPhotoModal()">
                        <i class="fa fa-eye"></i> Preview Photo
                    </button>

                </div>
            </div>





            <!-- FULL NAME -->
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>

            <!-- USERNAME -->
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
            </div>

            <!-- EMAIL -->
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <!-- CONTACT NUMBER -->
            <div class="mb-3">
                <label class="form-label">Contact Number (Optional)</label>
                <input type="text" name="contact_number" class="form-control">
            </div>

            <!-- ROLE -->
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="">Select Role</option>
                    <option value="super_admin">Super Admin</option>
                    <option value="admin">Admin</option>
                </select>
            </div>


            <!-- PASSWORD -->
            <div class="input-group required-field mb-3">
                <label class="form-label fw-bold">Password</label>
                <div class="input-wrapper position-relative">
                    <i class="fa fa-lock icon"></i>

                    <input type="password" class="form-control" id="password"
                           name="password" placeholder="Password" required
                           oninput="togglePasswordHint(this)">

                    <i class="fa fa-eye toggle-password"
                       onclick="togglePasswords(this)"
                       style="display:none; position:absolute; right:15px; top:50%; transform:translateY(-50%); cursor:pointer;"></i>
                </div>

                <div class="password-hint mt-1 text-danger small d-none" id="password-hint">
                    Must be at least 8 characters, include 1 uppercase letter and 1 number.
                </div>
            </div>

            <!-- CONFIRM PASSWORD -->
            <div class="input-group required-field mb-3">
                <label class="form-label fw-bold">Confirm Password</label>
                <div class="input-wrapper position-relative">
                    <i class="fa fa-lock icon"></i>

                    <input type="password" class="form-control" id="password_confirmation"
                           name="password_confirmation" placeholder="Confirm Password"
                           required oninput="checkPasswordMatch()">

                    <i class="fa fa-eye toggle-password"
                        onclick="togglePasswords(this)"
                        style="position:absolute; right:15px; top:50%; transform:translateY(-50%); cursor:pointer;"></i>

                </div>

                <div id="password-match" class="text-danger small d-none">
                    Passwords do not match.
                </div>
            </div>

            <button type="submit" class="btn btn-submit w-100">
                <i class="fa fa-save"></i> Create Admin Account
            </button>

        </form>
        <!-- FORM END -->

    </div>
</div>


<!-- PHOTO PREVIEW MODAL -->
<div id="photoModal" class="photo-modal">
    <div class="photo-modal-content">
        <span class="close-photo-modal" onclick="closePhotoModal()">&times;</span>
        <img id="photoPreview" src="" class="modal-photo">
    </div>
</div>

<!-- SUCCESS MODAL -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4" style="border-radius:18px;">

            <i class="fa fa-check-circle text-success" style="font-size:60px;"></i>

            <h4 class="fw-bold mt-3">Account Created!</h4>
            <p class="text-muted">The new admin account has been successfully registered.</p>

            <button class="btn btn-success w-100 mt-3" data-bs-dismiss="modal">Continue</button>

        </div>
    </div>
</div>


<script>
// ALL YOUR EXISTING JS — unchanged
function togglePasswords(icon) {
    let input = icon.parentElement.querySelector("input");

    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye","fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash","fa-eye");
    }
}


function togglePasswordHint(input) {
    let hint = document.getElementById("password-hint");
    let icon = input.nextElementSibling;
    icon.style.display = "block";

    let pattern = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

    if (!pattern.test(input.value)) {
        hint.classList.remove("d-none");
    } else {
        hint.classList.add("d-none");
    }
}

function checkPasswordMatch() {
    let pass = document.getElementById("password").value;
    let confirm = document.getElementById("password_confirmation").value;
    let msg = document.getElementById("password-match");

    if (confirm.length > 0) {
        msg.classList.remove("d-none");
        if (pass === confirm) {
            msg.classList.add("d-none");
        }
    }
}

let uploadedImageURL = "";
let modalShownOnce = false;

function handleFileUpload(input) {
    const file = input.files[0];
    const seePhotoBtn = document.getElementById("see-photo-btn");
    const fileNameDisplay = document.getElementById("file-name");

    if (file) {
        let imgURL = URL.createObjectURL(file);

        document.getElementById("previewImg").src = imgURL;
        fileNameDisplay.textContent = file.name;
        seePhotoBtn.style.display = "inline-block";

        uploadedImageURL = imgURL;

    } else {
        fileNameDisplay.textContent = "No file chosen";
        seePhotoBtn.style.display = "none";
        uploadedImageURL = "";
    }
}


function openPhotoModal() {
    const modal = document.getElementById("photoModal");
    const preview = document.getElementById("photoPreview");

    if (uploadedImageURL) {
        preview.src = uploadedImageURL;
        modal.style.display = "flex";
    }
}

function closePhotoModal() {
    document.getElementById("photoModal").style.display = "none";
}

window.addEventListener("click", function(event) {
    const modal = document.getElementById("photoModal");
    if (event.target === modal) {
        closePhotoModal();
    }
});

// SUCCESS MODAL SHOW
@if (session('success'))
    let successModal = new bootstrap.Modal(document.getElementById('successModal'));
    successModal.show();
@endif
</script>
