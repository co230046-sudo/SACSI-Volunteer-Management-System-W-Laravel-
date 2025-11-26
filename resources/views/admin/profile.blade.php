{{-- resources/views/admin/profile.blade.php --}}
@php
    $pageTitle = "Admin Profile";
@endphp

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle }}</title>

    <link rel="stylesheet" href="{{ asset('assets/admin-profile/Admin_profile.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/admin-profile/bootstrap-5.0.2-dist/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
      .profile-photo {
          width: 130px;
          height: 130px;
          object-fit: cover;
          border-radius: 50% !important;
      }
      .profile-section td:first-child {
          position: relative; 
      }
      .profile-upload-overlay {
          position: absolute; 
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          color: white;
          font-size: 1.1rem;
          font-weight: bold;
          opacity: 0;
          transition: opacity 0.2s ease;
      }
    </style>

</head>
<body>

@include('layouts.navbar')
@include('layouts.page_loader')

<a href="{{ url()->previous() }}" class="btn btn-light"
   style="position:absolute; left:20px; top:90px; z-index:1000; font-weight:bold;">
    <i class="fa fa-arrow-left"></i> Back
</a>

<section id="Student-Section" style="opacity:1;">
  <div class="container-fluid main-content py-4">
    <div class="student-section-wrapper">

      <!-- LEFT COLUMN -->
      <div class="left-col">
        <div class="left-section" style="background-color: #f2f5f8;">

          <!-- PROFILE SECTION -->
          <div class="profile-section p-3 border rounded mb-3">
            <table class="table table-borderless w-100 mb-0">
              <tbody>
                <tr>
                  <td class="text-center align-middle">
                    
                    {{-- Dynamic Profile Photo --}}
                    <img 
                        src="{{ $admin->profile_picture 
                            ? asset('storage/' . $admin->profile_picture) 
                            : asset('assets/defaults/default_profile_picture.png') }}"
                        alt="Profile Photo" 
                        class="profile-photo mb-2 border rounded-circle"
                    >

                    <h2 class="volunteer-name mb-1">{{ $admin->full_name }}</h2>
                    <p class="volunteer-title mb-2">{{ ucfirst($admin->role) }}</p>
                  </td>

                  <td class="align-middle position-relative">
                    <div class="action-tools d-flex flex-column gap-2 position-absolute top-0 end-0 m-2">

                      <div class="info-card d-flex align-items-center gap-2 px-2 py-1">
                        <i class="fas fa-check-circle"></i>
                        <span class="status-text active">{{ ucfirst($admin->status) }}</span>
                      </div>

                      <button class="info-card" onclick="printLeftColumn()">
                        <i class="fas fa-print"></i> Print
                      </button>

                      <button class="info-card" title="Edit">
                        <i class="fas fa-edit"></i> Edit
                      </button>

                    </div>
                  </td>

                </tr>
              </tbody>
            </table>
          </div>

          <!-- ADMIN DETAILS -->
          <div class="volunteer-details p-3 border rounded mb-3 position-relative">
            <h4 class="text-center mb-3">Admin Details</h4>

            <table class="table table-borderless mb-0">
              <tbody>
                <tr>
                  <td><div class="detail-card">
                      <h6><i class="fas fa-id-card"></i> Admin ID</h6>
                      <p>{{ $admin->admin_id }}</p>
                  </div></td>

                  <td><div class="detail-card">
                      <h6><i class="fas fa-user"></i> Username</h6>
                      <p>{{ $admin->username }}</p>
                  </div></td>

                  <td><div class="detail-card">
                      <h6><i class="fas fa-envelope"></i> Email</h6>
                      <p>{{ $admin->email }}</p>
                  </div></td>

                  <td><div class="detail-card">
                      <h6><i class="fas fa-key"></i> Password</h6>
                      <p>********</p>
                  </div></td>
                </tr>

                <tr>
                  <td><div class="detail-card">
                      <h6><i class="fas fa-address-card"></i> Full Name</h6>
                      <p>{{ $admin->full_name }}</p>
                  </div></td>

                  <td><div class="detail-card">
                      <h6><i class="fas fa-user-shield"></i> Role</h6>
                      <p>{{ ucfirst($admin->role) }}</p>
                  </div></td>

                  <td><div class="detail-card">
                      <h6><i class="fas fa-phone"></i> Contact #</h6>
                      <p>{{ $admin->contact_number ?? 'N/A' }}</p>
                  </div></td>

                  <td><div class="detail-card">
                      <h6><i class="fas fa-calendar-alt"></i> Account Created At</h6>
                      <p>{{ $admin->created_at->format('Y-m-d') }}</p>
                  </div></td>
                </tr>

              </tbody>
            </table>
          </div>

        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="right-col">
        <div class="event-wrapper">
          <div class="events-section p-3 border rounded">
            <h4 class="events-title mb-3">Recent Activity</h4>

            <p>This is where admin logs will appear.</p>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<script src="{{ asset('assets/admin-profile/bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('assets/admin-profile/admin_profile.js') }}"></script>

</body>
</html>
