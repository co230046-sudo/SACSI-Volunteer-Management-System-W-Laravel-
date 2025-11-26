<?php 
    $pageTitle = "Admin Profile"; // Updated Header Title
    include '../External_assets (shared)/PHP/auto-refresh.php'; 
    include '../External_assets (shared)/PHP/Universal-Header-Navbar.php';
    include '../External_assets (shared)/PHP/Universal-Back-Button.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile Document</title>

   <link rel="stylesheet" href="{{ asset('assets/admin-profile/Admin_profile.css') }}">
  <script src="{{ asset('assets/admin-profile/admin_profile.js') }}"></script>



    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200..1000;1,200..1000&display=swap" rel="stylesheet">

    <style>
      /* FIX: Ensure the profile photo is a perfect circle and is not stretched */
      .profile-photo {
          width: 130px;       /* Fixed width */
          height: 130px;      /* Equal fixed height */
          object-fit: cover;  /* Ensures the image fills the area without distortion */
          border-radius: 50% !important; /* Force circle shape */
      }
      /* Ensure the image container is relative for absolute positioning of the overlay */
      .profile-section td:first-child {
          position: relative; 
      }
      .profile-section.edit-mode .profile-photo {
          cursor: pointer;
          filter: brightness(0.7); 
      }
      .profile-section.edit-mode .profile-photo:hover {
          filter: brightness(0.5); 
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
          pointer-events: none; 
          opacity: 0;
          transition: opacity 0.2s ease;
          z-index: 10; 
      }
      .profile-section.edit-mode .profile-upload-overlay {
          opacity: 1; 
      }
      .profile-upload-overlay i {
          font-size: 2rem;
          margin-bottom: 0.5rem;
      }
    </style>
</head>
<body>
  
<section id="Student-Section" style="opacity:1;">
  <div class="container-fluid main-content py-4">
    <div class="student-section-wrapper">
      
      <div class="left-col">
        <div class="left-section" style="background-color: #f2f5f8;">

          <div class="profile-section p-3 border rounded mb-3" id="profileSection">
            <table class="table table-borderless w-100 mb-0">
              <tbody>
                <tr>
                  <td class="text-center align-middle" style="width:100%; position: relative;">
                    <img src="assets/adminpic.png" alt="Profile Photo" class="profile-photo mb-2 border rounded-circle" id="profileImage">
                    
                    <div class="profile-upload-overlay">
                        <i class="fas fa-camera"></i> Change Photo
                    </div>
                    
                    <input type="file" id="profileUpload" accept="image/*" style="display: none;">
                    
                    <h2 class="volunteer-name mb-1">Jane Smith</h2>
                    <p class="volunteer-title mb-2">System Administration</p> 
                  </td>

                  <td class="align-middle position-relative">
                    <div class="action-tools d-flex flex-column gap-2 position-absolute top-0 end-0 m-2">
  
                      <div class="info-card d-flex align-items-center gap-2 px-2 py-1">
                        <i class="fas fa-check-circle" title="Account Status"></i>
                        <span class="status-text active">Active</span>
                      </div>

                      <button class="info-card" title="Print Volunteer Info" onclick="printLeftColumn()">
                        <i class="fas fa-print"></i>
                        Print
                      </button>

                      <button class="info-card" title="Edit">
                        <i class="fas fa-edit"></i>
                        Edit
                      </button>

                      <button class="info-card" title="Facebook Profile" onclick="window.open('https://facebook.com', '_blank')">
                        <i class="fab fa-facebook"></i>
                        FB
                      </button>
                    </div>

                  </td>
                </tr>
              </tbody>
            </table>
          </div>


          <div class="volunteer-details p-3 border rounded mb-3 position-relative">
            <button class="copy-volunteer-btn" onclick="copyVolunteerData(this)">
              Copy <i class="fas fa-copy"></i>
            </button>
            <h4 class="text-center mb-3">Admin Details</h4> 
            <table class="table table-borderless mb-0">
              <tbody>
                <tr>
                  <td>
                    <div class="detail-card">
                      <h6><i class="fas fa-id-card"></i> Admin ID</h6>
                      <p>A-2024-001</p>
                    </div>
                  </td>
                  <td>
                    <div class="detail-card">
                      <h6><i class="fas fa-user"></i> Username</h6>
                      <p>jane.admin</p>
                    </div>
                  </td>
                  <td>
                    <div class="detail-card">
                      <h6><i class="fas fa-envelope"></i> Email</h6>
                      <p>jane.s@admin.com</p>
                    </div>
                  </td>
                  <td>
                    <div class="detail-card">
                      <h6><i class="fas fa-key"></i> Password</h6>
                      <p>********</p>
                    </div>
                  </td>
                </tr>

                <tr>
                  <td>
                    <div class="detail-card">
                      <h6><i class="fas fa-address-card"></i> Full Name</h6>
                      <p>Jane Smith</p>
                    </div>
                  </td>
                  <td>
                    <div class="detail-card">
                      <h6><i class="fas fa-user-shield"></i> Role</h6>
                      <p>Super Admin</p>
                    </div>
                  </td>
                  <td>
                    <div class="detail-card">
                      <h6><i class="fas fa-phone"></i> Contact #</h6>
                      <p>0917****123</p>
                    </div>
                  </td>
                  <td>
                    <div class="detail-card">
                      <h6><i class="fas fa-calendar-alt"></i> Account Created At</h6>
                      <p>2020-01-15</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="right-col">
        <div class="event-wrapper">
          <div class="events-section p-3 border rounded">
            <h4 class="events-title mb-3">Activity Log</h4> 
            <table class="table table-bordered mb-0 event-table">
              <tbody>
                <tr class="event-item">
                  <td class="event-name">
                    <a href="../Log_Details/Log_Details.php">
                      Database Backup & Maintenance
                      <span class="click-bubble"><i class="fa fa-eye"></i> View Log Entry</span>
                    </a>
                  </td>
                  <td class="event-datetime">Nov 14, 2025 - 11:00 PM</td>
                </tr>
                <tr class="event-item">
                  <td class="event-name">
                    <a href="../Log_Details/Log_Details.php">
                      User Account Creation: John Doe
                      <span class="click-bubble"><i class="fa fa-eye"></i> View Log Entry</span>
                    </a>
                  </td>
                  <td class="event-datetime">Nov 10, 2025 - 10:00 AM</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<script src="bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
  <script src="admin_profile.js"></script> 
</body>
</html>