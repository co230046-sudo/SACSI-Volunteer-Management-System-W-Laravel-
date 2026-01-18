@include('layouts.navbar')
@include('layouts.page_loader')
@include('layouts.back_button')
<style>
#registerAdminModal .modal-content{
    border-radius:18px;
    border:none;
    box-shadow:0 10px 30px rgba(0,0,0,.15);
}

#registerAdminModal .modal-header{
    background:#b3263a;
    color:white;
    border-top-left-radius:18px;
    border-top-right-radius:18px;
}

#registerAdminModal .modal-title{
    font-weight:700;
}

#registerAdminModal .btn-close{
    filter:invert(1);
}

#registerAdminModal .modal-body{
    padding:25px;
}

#registerAdminModal .form-label{
    font-weight:600;
    color:#444;
}

#registerAdminModal input,
#registerAdminModal select{
    border-radius:10px;
    padding:10px 12px;
}

#registerAdminModal input:focus,
#registerAdminModal select:focus{
    border-color:#b3263a;
    box-shadow:0 0 0 2px rgba(179,38,58,.15);
}

#registerAdminModal .modal-footer{
    border-top:none;
    padding:20px;
}

#registerAdminModal .btn-success{
    background:#b3263a;
    border:none;
    border-radius:10px;
    font-weight:600;
}

#registerAdminModal .btn-success:hover{
    background:#8f1f30;
}

#registerAdminModal .btn-secondary{
    border-radius:10px;
}

#regPreviewImg{
    transition:all .3s ease;
}
#regPreviewImg:hover{
    transform:scale(1.05);
}
#registerAdminModal .modal-body .mb-3{
    margin-bottom: 14px !important;
}

#registerAdminModal label{
    margin-bottom: 4px;
    font-size: 14px;
    font-weight: 600;
}

#registerAdminModal input,
#registerAdminModal select{
    height: 44px;
    border-radius: 10px;
}

#registerAdminModal .modal-footer{
    padding: 16px 24px;
    display:flex;
    gap:12px;
}

#registerAdminModal .modal-footer .btn{
    flex:1;
    border-radius:10px;
    height:44px;
}

#registerAdminModal .upload-btn{
    margin-top:6px;
}

#registerAdminModal .profile-preview-wrapper{
    margin-bottom: 12px;
}

#registerAdminModal .password-eye{
    top: 38px !important;
}
.register-btn{
    background:#198754;
    color:#fff;
    border:none;
    border-radius:10px;
    padding:10px 18px;
    font-size:14px;
    font-weight:600;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    width: 383px;

    position:absolute;
    bottom:15px;
    right:15px;

    box-shadow:0 4px 10px rgba(0,0,0,0.15);
    transition:0.2s ease;
}

.register-btn:hover{
    background:#157347;
    transform:translateY(-1px);
}

</style>

<link rel="stylesheet" href="{{ asset('assets/admin_profile/profile_view.css') }}">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container py-4">
<div class="row">

<!-- LEFT : MAIN PROFILE -->
<div class="col-md-8">
<div class="card shadow-sm p-3 h-100 position-relative">


<div class="text-center">
    <img src="{{ $admin->profile_picture ? asset('storage/'.$admin->profile_picture) : asset('assets/adminpic.png') }}"
         class="rounded-circle mb-3"
         style="width:140px;height:140px;object-fit:cover;">

    <h2><i class="fa-solid fa-user-shield text-danger"></i> {{ $admin->full_name }}</h2>
    <p class="text-muted"><i class="fa-solid fa-id-badge"></i> {{ $admin->role }}</p>

    <a href="{{ route('admin.profile.edit', $admin->admin_id) }}" class="btn btn-primary mt-2">
        <i class="fa fa-edit"></i> Edit Profile
    </a>
</div>

<hr>

<h4><i class="fa-solid fa-circle-info text-primary me-2"></i> Account Details</h4>

<div class="details-list mt-3">

<div class="detail-row">
    <div class="detail-icon"><i class="fa-solid fa-envelope"></i></div>
    <div class="detail-label">Email:</div>
    <div class="detail-value">{{ $admin->email }}</div>
</div>

<div class="detail-row">
    <div class="detail-icon"><i class="fa-solid fa-user"></i></div>
    <div class="detail-label">Username:</div>
    <div class="detail-value">{{ $admin->username }}</div>
</div>

<div class="detail-row">
    <div class="detail-icon"><i class="fa-solid fa-phone"></i></div>
    <div class="detail-label">Contact:</div>
    <div class="detail-value">{{ $admin->contact_number ?? 'Not Provided' }}</div>
</div>

</div>

</div>
</div>

<!-- RIGHT : ADMIN LIST -->
<div class="col-md-4">
<div class="card shadow-sm p-3 h-100">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="fa-solid fa-user-plus me-2"></i>Other Administrators
    </h5>
</div>


<div style="max-height:520px; overflow-y:auto;">

@forelse($allAdmins as $other)

<div class="card border-0 shadow-sm mb-3 p-2 text-center">

<img src="{{ $other->profile_picture ? asset('storage/'.$other->profile_picture) : asset('assets/adminpic.png') }}"
     class="rounded-circle mx-auto mb-2"
     style="width:70px;height:70px;object-fit:cover;">

<h6 class="mb-0">{{ $other->full_name }}</h6>
<small class="text-muted">{{ $other->role }}</small>

<button type="button"
        onclick="openAdminProfile({{ $other->admin_id }})"
        class="btn btn-outline-primary btn-sm mt-2">
    View Profile
</button>

</div>

@empty
<p class="text-muted text-center">No other administrators found.</p>
@endforelse
<button class="register-btn"
        data-bs-toggle="modal"
        data-bs-target="#registerAdminModal">
    <i class="fa fa-user-plus me-2"></i> Register New User
</button>



</div>
</div>
</div>

</div>
</div>

<!-- MODAL -->
<div class="modal fade" id="adminProfileModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-md">
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">
<i class="fa-solid fa-user-shield text-danger me-2"></i> Admin Profile
</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body py-4 px-4">


<img id="modalProfilePic"
     class="rounded-circle mb-3"
     style="width:120px;height:120px;object-fit:cover;">

<h5 id="modalName"></h5>
<p class="text-muted" id="modalRole"></p>

<hr>

<p><strong>Username:</strong> <span id="modalUsername"></span></p>
<p><strong>Email:</strong> <span id="modalEmail"></span></p>
<p><strong>Contact:</strong> <span id="modalContact"></span></p>

</div>

<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
</div>

</div>
</div>
</div>
<div class="modal fade" id="registerAdminModal" tabindex="-1">
<div class="modal-dialog modal-dialog-centered modal-md">
<div class="modal-content">

<div class="modal-header">
<h5 class="modal-title">
<i class="fa-solid fa-user-plus text-success me-2"></i> Register New Admin
</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<form action="{{ route('admin.user.store') }}" method="POST" enctype="multipart/form-data">
@csrf

<div class="modal-body">
<div class="text-center mb-3">

<img id="regPreviewImg"
     src="{{ asset('assets/adminpic.png') }}"
     class="rounded-circle mb-2"
     style="width:110px;height:110px;object-fit:cover;border:3px solid #b3263a;">

<br>

<input type="file" id="regProfileInput" name="profile_picture"
       accept="image/*" hidden
       onchange="handleRegPhoto(this)">

<button type="button" class="btn btn-outline-danger btn-sm mt-2 rounded-pill px-3"
       onclick="document.getElementById('regProfileInput').click()">
<i class="fa fa-upload"></i> Upload Picture
</button>

</div>
<div class="mb-3">
<label class="form-label">Full Name</label>
<input type="text" name="full_name" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Username</label>
<input type="text" name="username" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Contact Number</label>
<input type="text" name="contact_number" class="form-control">
</div>

<div class="mb-3">
<label class="form-label">Role</label>
<select name="role" class="form-select" required>
    <option value="admin">Admin</option>
    <option value="super_admin">Super Admin</option>
</select>
</div>

<div class="mb-3 position-relative">
<label class="form-label">Password</label>
<input type="password" name="password" id="regPassword" class="form-control" required
       oninput="validatePassword()">
<i class="fa fa-eye position-absolute"
   style="right:15px;top:42px;cursor:pointer;"
   onclick="toggleRegPassword('regPassword', this)"></i>

<small id="passwordRules" class="text-danger d-none">
Must be at least 8 characters, contain 1 uppercase letter and 1 number.
</small>
</div>

<div class="mb-3 position-relative">
<label class="form-label">Confirm Password</label>
<input type="password" name="password_confirmation" id="regConfirm" class="form-control" required
       oninput="checkRegPasswordMatch()">
<i class="fa fa-eye position-absolute"
   style="right:15px;top:42px;cursor:pointer;"
   onclick="toggleRegPassword('regConfirm', this)"></i>

<small id="regPassMsg" class="text-danger d-none">
Passwords do not match.
</small>
</div>


<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button class="btn btn-success w-100 py-2">
    <i class="fa fa-save me-2"></i> Register Admin
</button>

</div>

</form>

</div>
</div>
</div>

@if(session('success'))
<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
    <div id="successToast" class="toast align-items-center text-bg-success border-0 show" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fa fa-check-circle me-2"></i>
                {{ session('success') }}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
function openAdminProfile(adminId)
{
fetch(`/admin/profile/view/${adminId}`)
.then(res => res.json())
.then(res => {

if(!res.success) return;

const data = res.data;

document.getElementById('modalProfilePic').src = data.profile_picture;
document.getElementById('modalName').innerText = data.name;
document.getElementById('modalRole').innerText = data.role;
document.getElementById('modalUsername').innerText = data.username;
document.getElementById('modalEmail').innerText = data.email;
document.getElementById('modalContact').innerText = data.contact_number ?? 'Not provided';

const modal = new bootstrap.Modal(document.getElementById('adminProfileModal'));
modal.show();
});
}

function handleRegPhoto(input){
    if(input.files[0]){
        document.getElementById('regPreviewImg').src =
            URL.createObjectURL(input.files[0]);
    }
}

function toggleRegPassword(id, icon){
    const input = document.getElementById(id);
    if(input.type === "password"){
        input.type = "text";
        icon.classList.replace("fa-eye","fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash","fa-eye");
    }
}

function checkRegPasswordMatch(){
    let p = document.getElementById('regPassword').value;
    let c = document.getElementById('regConfirm').value;
    let msg = document.getElementById('regPassMsg');

    if(c.length){
        msg.classList.toggle('d-none', p === c);
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("#registerAdminModal form");

    form.addEventListener("keydown", function(e){
        if(e.key === "Enter"){
            e.preventDefault();
        }
    });
});

function validatePassword(){
    const pass = document.getElementById("regPassword").value;
    const rules = document.getElementById("passwordRules");

    const pattern = /^(?=.*[A-Z])(?=.*\d).{8,}$/;

    if(pass.length){
        if(!pattern.test(pass)){
            rules.classList.remove("d-none");
        }else{
            rules.classList.add("d-none");
        }
    }else{
        rules.classList.add("d-none");
    }
}


</script>
@if(session('success'))
<script>
document.addEventListener("DOMContentLoaded", function(){

    // Show success toast
    const toastEl = document.getElementById('successToast');
    if(toastEl){
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }

    // Close registration modal if open
    const regModalEl = document.getElementById('registerAdminModal');
    if(regModalEl){
        const modal = bootstrap.Modal.getInstance(regModalEl);
        if(modal){
            modal.hide();
        }
    }

});
</script>
@endif

