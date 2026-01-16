@include('layouts.navbar')
@include('layouts.page_loader')
@include('layouts.back_button')

<link rel="stylesheet" href="{{ asset('assets/admin_profile/profile_view.css') }}">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<div class="container py-4">

    <div class="card shadow-sm p-4">

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

        <h4><i class="fa-solid fa-circle-info text-primary me-2"></i>Account Details</h4>

       <div class="details-list mt-3">

            <div class="detail-row">
                <div class="detail-icon">
                    <i class="fa-solid fa-envelope"></i>
                </div>
                <div class="detail-label">Email:</div>
                <div class="detail-value">{{ $admin->email }}</div>
            </div>

            <div class="detail-row">
                <div class="detail-icon">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="detail-label">Username:</div>
                <div class="detail-value">{{ $admin->username }}</div>
                    </div>

            <div class="detail-row">
                <div class="detail-icon">
                    <i class="fa-solid fa-phone"></i>
                </div>
                <div class="detail-label">Contact:</div>
                <div class="detail-value">{{ $admin->contact_number ?? 'Not Provided' }}</div>
            </div>

        </div>


        <hr>

    </div>

</div>

<!-- Add Bootstrap JS at bottom -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
