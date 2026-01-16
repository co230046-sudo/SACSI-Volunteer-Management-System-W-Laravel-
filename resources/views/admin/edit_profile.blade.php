@include('layouts.navbar')
@include('layouts.page_loader')
@include('layouts.back_button')

<!-- CUSTOM CSS -->
<link rel="stylesheet" href="{{ asset('assets/admin_profile/edit_profile.css') }}">

<!-- BOOTSTRAP CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<div class="container mt-5 mb-5">
    <div class="edit-card shadow-lg">

        <h3 class="header-title">
            <i class="fa fa-user-edit text-danger"></i> Edit Profile
        </h3>

        <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <input type="hidden" name="admin_id" value="{{ $admin->admin_id }}">

            <!-- ================================
                 PROFILE PICTURE UPLOAD + PREVIEW
            ================================= -->
            <div class="upload-wrapper text-center mb-4">

                <div class="d-flex flex-column align-items-center">

                    <img id="previewImg" class="profile-preview mb-2"
                        src="{{ $admin->profile_picture ? asset('storage/'.$admin->profile_picture) : asset('assets/adminpic.png') }}"
                        alt="Preview">

                    <input type="file" id="profileInput" name="profile_picture"
                           accept="image/*" onchange="handleFileUpload(this)" hidden>

                    <button type="button" class="btn btn-outline-secondary mt-2"
                            onclick="document.getElementById('profileInput').click()">
                        <i class="fa fa-upload"></i> Upload Picture
                    </button>

                    <small id="file-name" class="text-muted mt-2">No file chosen</small>

                    <button type="button" id="see-photo-btn"
                            class="btn btn-outline-primary btn-sm mt-2"
                            style="display:none;"
                            onclick="openPhotoModal()">
                        <i class="fa fa-eye"></i> Preview Photo
                    </button>

                </div>
            </div>


            <!-- ================================
                 PROFILE DETAILS
            ================================= -->
            <label class="form-label fw-bold">Full Name</label>
            <div class="input-group mb-3">
                <span class="input-group-text icon-box"><i class="fa fa-user"></i></span>
                <input type="text" class="form-control custom-input"
                       name="full_name" value="{{ old('full_name', $admin->full_name) }}">
            </div>

            <label class="form-label fw-bold">Email</label>
            <div class="input-group mb-3">
                <span class="input-group-text icon-box"><i class="fa fa-envelope"></i></span>
                <input type="email" class="form-control custom-input"
                       name="email" value="{{ old('email', $admin->email) }}">
            </div>

            <label class="form-label fw-bold">Contact Number</label>
            <div class="input-group mb-3">
                <span class="input-group-text icon-box"><i class="fa fa-phone"></i></span>
                <input type="text" class="form-control custom-input"
                       name="contact_number" value="{{ old('contact_number', $admin->contact_number) }}">
            </div>

            @if(str_contains(strtolower($currentAdmin->role), 'super'))
            <label class="form-label fw-bold">Username</label>
            <div class="input-group mb-3">
                <span class="input-group-text icon-box"><i class="fa fa-id-badge"></i></span>
                <input type="text" class="form-control custom-input"
                       name="username" value="{{ old('username', $admin->username) }}">
            </div>

            <label class="form-label fw-bold">Role</label>
            <div class="input-group mb-3">
                <span class="input-group-text icon-box"><i class="fa fa-user-shield"></i></span>
                <select name="role" class="form-select custom-input">
                    <option value="admin" {{ $admin->role=='admin'?'selected':'' }}>Admin</option>
                    <option value="super_admin" {{ $admin->role=='super_admin'?'selected':'' }}>Super Admin</option>
                </select>
            </div>
            @endif


            <!-- ================================
                PASSWORD CHANGE SECTION
            ================================= -->
            <hr class="section-divider">

            <h4 class="subsection-title"><i class="fa fa-lock"></i> Change Password</h4>

            <!-- CURRENT PASSWORD -->
            <label class="form-label fw-bold">Current Password</label>
            <div class="input-group required-field mb-3">
                <div class="input-wrapper w-100 position-relative">
                    <i class="fa fa-lock icon"></i>

                    <input type="password" name="current_password"
                        class="form-control custom-input"
                        placeholder="Enter Current Password"
                        oninput="showEye(this)">

                    <i class="fa fa-eye toggle-password" onclick="togglePasswords(this)"></i>
                </div>
            </div>

            <!-- NEW PASSWORD -->
            <label class="form-label fw-bold">New Password</label>
            <div class="input-group required-field mb-1">
                <div class="input-wrapper w-100 position-relative">
                    <i class="fa fa-lock icon"></i>

                    <input type="password" id="new_password" name="new_password"
                        class="form-control custom-input"
                        placeholder="Enter New Password"
                        oninput="togglePasswordHint(this); showEye(this)">

                    <i class="fa fa-eye toggle-password" onclick="togglePasswords(this)"></i>
                </div>
            </div>

            <div class="password-hint text-danger small d-none" id="password-hint">
                Must be at least 8 characters, include 1 uppercase letter and 1 number.
            </div>

            <!-- CONFIRM PASSWORD -->
            <label class="form-label fw-bold mt-3">Confirm New Password</label>
            <div class="input-group required-field mb-1">
                <div class="input-wrapper w-100 position-relative">
                    <i class="fa fa-lock icon"></i>

                    <input type="password" id="password_confirmation"
                        name="new_password_confirmation"
                        class="form-control custom-input"
                        placeholder="Confirm New Password"
                        oninput="checkPasswordMatch(); showEye(this)">

                    <i class="fa fa-eye toggle-password" onclick="togglePasswords(this)"></i>
                </div>
            </div>



            <div id="password-match" class="text-danger small d-none">
                Passwords do not match.
            </div>

            <!-- SAVE BUTTON -->
            <button class="btn save-btn w-100 mt-3" type="submit">
                <i class="fa fa-save"></i> Save Changes
            </button>

        </form>

        <a href="{{ route('admin.profile', $admin->admin_id) }}" class="btn back-btn w-100 mt-3">
            <i class="fa fa-arrow-left"></i> Back to Profile
        </a>

    </div>
</div>


<!-- ================================
     PHOTO VIEW MODAL
================================ -->
<div id="photoModal" class="photo-modal">
    <div class="photo-modal-content">
        <span class="close-photo-modal" onclick="closePhotoModal()">&times;</span>
        <img id="photoPreview" class="modal-photo">
    </div>
</div>


<!-- ================================
     TOASTS FOR SUCCESS/ERROR/VALIDATION
================================ -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">

    @if(session('success'))
    <div class="toast text-bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold">
                <i class="fa fa-check-circle me-2"></i> {{ session('success') }}
            </div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    @endif

    @if(session('error'))
    <div class="toast text-bg-danger border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body fw-bold">
                <i class="fa fa-times-circle me-2"></i> {{ session('error') }}
            </div>
            <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
    @endif

    @if($errors->any())
        @foreach($errors->all() as $error)
        <div class="toast text-bg-danger border-0 mb-2" role="alert">
            <div class="d-flex">
                <div class="toast-body fw-bold">
                    <i class="fa fa-exclamation-circle me-2"></i> {{ $error }}
                </div>
                <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        @endforeach
    @endif

</div>


<!-- ================================
     JS SECTION
================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.toast').forEach(toast => {
        new bootstrap.Toast(toast, { delay: 3500 }).show();
    });
});

function showEye(input){
    let icon = input.parentElement.querySelector(".toggle-password");
    icon.style.display = "block";
}

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

    if (!pattern.test(input.value)) hint.classList.remove("d-none");
    else hint.classList.add("d-none");
}

function checkPasswordMatch() {
    let pass = document.getElementById("new_password").value;
    let confirm = document.getElementById("password_confirmation").value;
    let msg = document.getElementById("password-match");

    if (confirm.length > 0) {
        msg.classList.remove("d-none");
        if (pass === confirm) msg.classList.add("d-none");
    }
}

let uploadedImageURL = "";

function handleFileUpload(input) {
    const file = input.files[0];

    if (file) {
        uploadedImageURL = URL.createObjectURL(file);
        document.getElementById("previewImg").src = uploadedImageURL;
        document.getElementById("file-name").textContent = file.name;
        document.getElementById("see-photo-btn").style.display = "inline-block";
    }
}

function openPhotoModal() {
    if (uploadedImageURL) {
        document.getElementById("photoPreview").src = uploadedImageURL;
        document.getElementById("photoModal").style.display = "flex";
    }
}

function closePhotoModal() {
    document.getElementById("photoModal").style.display = "none";
}
</script>
