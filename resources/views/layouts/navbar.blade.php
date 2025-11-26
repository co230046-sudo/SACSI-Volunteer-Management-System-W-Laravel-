{{-- resources/views/layouts/header.blade.php --}}

@php
    $pageTitle = $pageTitle ?? 'SACSI Volunteer Management';
@endphp

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
  /* ===========================
    Section Fade + Slide Animation
    =========================== */
  section {
    opacity: 0;
    transform: translateY(30px); /* initial position below */
  }

  #overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.3);
    z-index: 1040;
    display: none;
    transition: opacity 0.3s ease;
  }

  #overlay.active {
    display: block;
    opacity: 1;
  }
  .database-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 80px;
    z-index: 1000;
    background-color: #B2000C;
    color: white;
    padding: 1rem 2rem;
    box-sizing: border-box;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  }

  .header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
    position: relative;
  }

  .sacsi-logo {
    max-width: 3.4vw;      
    min-width: 40px;      
    height: auto;
    background-color: aliceblue;
    border-radius: 50%;   
    padding: 0.3rem;
  }

   /* Account Button: image only */
  .account-btn {
    padding: 0;
    border: none;
    background: transparent;
    cursor: pointer;
    display: flex;
    align-items: center;
  }

  .account-btn img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    border: 2px solid #fff;
    object-fit: cover; /* ensures image fills circle properly */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.4); /* nice shadow */
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    background-color: #fff; /* prevents dark edges on transparent images */
  }

  .account-btn img:hover {
    transform: scale(1.05);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.45);
  }


    /* Account dropdown with smooth animation */
  .account-box {
    position: absolute;
    top: 55px;
    right: 0;
    width: 260px;
    background: #fff;
    border: 2px solid #794242;
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    padding: 0.8rem;
    text-align: center;
    opacity: 0;
    transform: translateY(-10px);
    pointer-events: none;
    transition: opacity 0.3s ease, transform 0.3s ease;
    z-index: 1100;
  }

  .account-box.open {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
  }

  /* Account options buttons */
  .account-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    justify-content: flex-start;
    background: none;
    border: none;
    font-size: 1rem;
    color: #333;
    cursor: pointer;
    padding: 6px 10px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.25s ease;
  }

  .account-btn-text {
    color: #333;
    font-weight: 600;
    letter-spacing: 0.4px;
    text-transform: capitalize;
    transition: color 0.3s ease, transform 0.2s ease;
  }

  /* Hover effects */
  .account-btn:hover {
    background-color: #fff3f3;
    transform: scale(1.05)
  }

  .account-btn:hover .account-btn-text {
    color: #8b0009;
    transform: translateX(2px);
  }

  /* Icon style + hover tilt */
  .icon {
    font-size: 22px;
    color: #b30000;
    min-width: 26px;
    transition: transform 0.3s ease, color 0.3s ease;
  }

  .account-btn:hover .icon {
    transform: rotate(-10deg);
    color: #8b0009;
  }

  /* Logout special */
  .account-btn.logout {
    color: #b2000c;
  }

  .account-btn.logout:hover {
    background-color: #8b0009;
    color: #fff;
  }

  /* Logout button hover (icon + text both turn white) */
  .account-btn.logout:hover {
    background-color: #8b0009;
    color: #fff;
  }

  .account-btn.logout:hover .icon,
  .account-btn.logout:hover .account-btn-text {
    color: #fff;
  }

  .menu-btn {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 45px;
    height: 45px;
    border-radius: 10%;
    cursor: pointer;
    transition: transform 0.2s ease, background-color 0.3s ease, box-shadow 0.3s ease;
  }

  .menu-btn:hover {
    background-color: #ffeaea;
    box-shadow: 0 0 8px rgba(178,0,12,0.4);
    transform: scale(1.25); /* grow smoothly without shifting layout */
  }

  .Menu-icon {  
    color: #ffeaea;
    font-size: 2.5rem;
    transition: color 0.3s ease, transform 0.2s ease;
  }

  .menu-btn:hover .Menu-icon {
    color: #8b0009;
  }

  /* ===== Sidebar Container ===== */
  .sidebar {
    position: fixed;
    top: 0;
    right: 0;
    height: 100%;
    width: 300px;
    background-color: #fff;
    color: #333;
    font-size: 1.3rem;
    transform: translateX(100%);
    transition: transform 0.35s ease-in-out;
    z-index: 1050;
    box-shadow: -4px 0 15px rgba(0,0,0,0.15);
    display: flex;
    flex-direction: column;
    border-left: 3px solid #b2000c;
    overflow: hidden;
  }

  .sidebar.active {
    transform: translateX(0);
  }

  .sidebar-header {
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.53rem 1.2rem;
    background-color: #b2000c;
    color: white;
    border-bottom: 1px solid rgba(255,255,255,0.15);
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  }

  .sidebar-header h3 {
    margin: 0;
    font-weight: bold;
    font-size: 1.8rem;
  }

  .close-btn {
    background: none;
    border: none;
    color: white;
    font-size: 2.6rem;
    cursor: pointer;
    transition: transform 0.2s ease, color 0.2s ease;
  }

  .close-btn:hover {
    color: #ffcccc;
    transform: scale(1.3);
  }

  /* Sidebar Links */
  .sidebar-links {
    flex: 1;
    overflow-y: auto;
    padding: 0.5rem 0;
  }

  .sidebar-links a {
    color: #333;
    background: transparent;
    border: none;
    padding: 0.85rem 1.2rem;
    text-decoration: none;
    display: flex;
    align-items: center;
    border-left: 4px solid transparent;
    transition: all 0.3s ease;
  }

  .sidebar-links a i {
    color: #b2000c;
    margin-right: 12px;
    font-size: 1.2rem;
    transition: transform 0.3s ease, color 0.3s ease;
  }

  .sidebar-links a:hover {
    background-color: #fff2f2;
    color: #b2000c;
    border-left: 4px solid #b2000c;
    transform: translateX(-3px);
  }

  .sidebar-links a:hover i {
    transform: translateX(6px) scale(1.1);
  }

  /* Sidebar dividers */
  .sidebar-divider {
    border: none; /* remove default hr styles */
    border-top: 1px solid #b47a7aff; /* visible line */
    margin: 0.5rem 0;
  }

  /* Refined Upcoming Events (Table-Compatible Version) */
  .upcoming-events {
    flex: 1;
    padding: 0 0.6rem;
    margin-top: 0.75rem;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
  }

  .upcoming-events h5 {
    color: #b2000c;
    font-weight: 700;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 0.8rem;
  }

  .events-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
  }

  .events-table tr {
    background: #fdf0f0ff;
    border: 1px solid #f0b6b6;
    border-radius: 6px;
    transition: background-color 0.25s ease, transform 0.2s ease, box-shadow 0.3s ease;
  }

  .events-table tr:hover {
    background-color: #f1c4c4ff;
    transform: translateY(-2px);
    box-shadow: 0 2px 6px rgba(178,0,12,0.15);
  }

  .events-table td {
    border: 1px solid #b2000c;
    padding: 0.7rem 0.6rem;
    border-radius: 6px;
  }

  /* Event info styling */
  .event-info {
    display: flex;
    flex-direction: column;
  }

  .event-title {
    font-weight: 600;
    color: #333;
    font-size: 1rem;
  }

  /* ✨ Styled horizontal line */
  .event-info hr {
    border: none;
    height: 1px;
    background: linear-gradient(to right, #f0b6b6, #b2000c, #f0b6b6);
    margin: 0.5rem 0;
    opacity: 0.8;
    border-radius: 2px;
  }

  .event-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 8px;
  }

  .event-date {
    font-size: 0.85rem;
    color: #b2000c;
    background: transparent; /* no solid background */
    padding: 0;
    border-radius: 0;
    font-weight: 500;
    transition: color 0.25s ease;
  }

  /* Hover effect: invert colors */
  .events-table tr:hover .event-date {
    color: #a4000b;
  }

  /* Details Button */
  .detail-btn {
    background-color: #b2000c;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.8rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: background-color 0.3s ease, transform 0.2s ease;
  }

  .detail-btn:hover {
    background-color: #8b0009;
    transform: scale(1.05);
  }

  .detail-btn i {
    font-size: 0.85rem;
  }


  /* Details Button */
  .detail-btn {
    background-color: #b2000c;
    color: #fff;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.8rem;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: background-color 0.3s ease, transform 0.2s ease;
  }

  .detail-btn:hover {
    background-color: #8b0009;
    transform: scale(1.05);
  }

  .detail-btn i {
    font-size: 0.85rem;
  }


  /* Profile section */
  .sidebar-profile {
    padding: 1rem 1rem 0.8rem;
    text-align: center;
  }

  .Account-logo {
    width: 100px;
    height: auto;
    border-radius: 50%;
    background-color: #ffeaea;
    padding: 0.3rem;
    border: 2px solid #b2000c;
    transition: transform 0.3s ease;
  }

  .Account-logo:hover {
    transform: scale(1.06);
  }

  .logout-btn {
    background-color: #b2000c;
    color: #fff;
    border: none;
    padding: 0.5rem 0.8rem;
    border-radius: 6px;
    width: 100%;
    font-weight: 500;
    transition: background-color 0.3s ease, transform 0.2s ease;
  }

  .logout-btn:hover {
    background-color: #8b0009;
    transform: translateY(-2px);
  }

   /* Scroll Container */
  .scroll-container {
    margin-top: 20px;
    flex: 1;
    overflow-y: scroll;
    scroll-snap-type: y mandatory;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    position: relative;
  }

   /* Slide left-to-right fade-in animation for H1 */
  @keyframes slideFadeIn {
    0% {
      opacity: 0;
      transform: translateX(-50px);
    }
    100% {
      opacity: 1;
      transform: translateX(0);
    }
  }
  
  .page-title {
    flex: 1;
    text-align: center;
    margin: 0;
    font-size: 2rem;
    color: white;
    animation: slideFadeIn 0.6s ease forwards;
  }


  /* Nested submenu hidden by default */
  .sidebar-child {
    display: none;
    flex-direction: column;
    padding-left: 1.2rem; /* indent for child links */
  }
  /* Show submenu when active */
  .sidebar-child.active {
    display: flex;
  }
  
  /* Optional: rotate chevron when open */
  .sidebar-parent .toggle-chevron {
    transition: transform 0.3s ease;
  }
  .sidebar-parent.active .toggle-chevron {
    transform: rotate(180deg);
  }

  /* Ensure text aligns nicely next to icons */
  .sidebar-links a .link-text {
      flex: 1;              /* take remaining space */
      margin-left: 8px;     /* small spacing from icon */
      font-weight: 500;
      white-space: nowrap;  /* avoid breaking text into multiple lines */
  }

  /* Parent links: keep chevron right-aligned */
  .sidebar-parent .toggle-chevron {
      margin-left: auto;
  }


  .tooltip-logo {
    position: relative;
    display: inline-block;
  }

  .tooltip-logo::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: 20%; /* show tooltip above image */
    left: 160px;
    transform: translateX(-50%);
    background-color: #333;
    color: #fff;
    padding: 6px 10px;
    border-radius: 5px;
    font-size: 0.85rem;
    white-space: nowrap;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
    z-index: 9999;
  }

  .tooltip-logo:hover::after {
    opacity: 1;
  }   

  .account-dropdown-img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background-color: #fff;
    object-fit: cover;
    margin-bottom: 0.75rem;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.5);
    border: 3px solid transparent;
    transition: 
      border-color 0.3s ease,
      transform 0.25s ease,
      box-shadow 0.25s ease;
  }

  .account-dropdown-img:hover {
    border-color: #8b0009;
    transform: scale(1.05);
    box-shadow: 0 10px 18px rgba(0, 0, 0, 0.6);
  }
</style>


<script>
document.addEventListener("DOMContentLoaded", () => {
  const parents = document.querySelectorAll(".sidebar-parent");

  parents.forEach(parent => {
    const child = parent.nextElementSibling; // corresponding .sidebar-child
    const chevron = parent.querySelector(".toggle-chevron");

    parent.addEventListener("click", (e) => {
      if (e.target === chevron || chevron.contains(e.target)) {
        e.preventDefault();

        // Close all other submenus
        parents.forEach(p => {
          const c = p.nextElementSibling;
          if (p !== parent) {
            c.classList.remove("active");
            p.classList.remove("active");
          }
        });

        // Toggle the clicked submenu
        child.classList.toggle("active");
        parent.classList.toggle("active");
      }
    });
  });
});
</script>

<div id="overlay"></div>

<header class="database-header">
  <div class="header-content">
    <!-- Logo -->
    <a href="{{ url('home') }}" class="tooltip-logo" data-tooltip="Go to SACSI Homepage"><img src="{{ asset('assets/layouts/images/logos/sacsi-logo.png')}}" alt="SACSI Logo" class="sacsi-logo"></a>
    <!-- Page title --> 
    <h1 class="page-title">{{ $pageTitle }}</h1>
    <!-- Right side: account button + menu -->
    
    @php
      $admin = Auth::guard('admin')->user();
    @endphp

    <div style="display: flex; align-items: center; gap: 0.5rem;">
      <!-- Account Wrapper -->
      <div class="account-wrapper" style="position: relative;">

        <!-- Account Button -->
        <button class="account-btn">
          @if ($admin && $admin->profile_picture)
            <img src="{{ asset('storage/' . $admin->profile_picture) }}" alt="Profile" >
          @else
            <img src="{{ asset('assets/defaults/default_profile_picture.png') }}" alt="Default Profile">
          @endif
        </button>

        <!-- Account Dropdown -->
        <div class="account-box" style="text-align:center; padding:1rem;">
          @if ($admin && $admin->profile_picture)
            <img src="{{ asset('storage/' . $admin->profile_picture) }}" alt="Profile" class="account-dropdown-img">
          @else
          <img src="{{ asset('assets/defaults/default_profile_picture.png') }}" alt="Default Profile" class="account-dropdown-img">
          @endif

          <p style="margin:0.3rem 0; font-weight:bold; color:#8b0009; font-size:18px;">
            {{ $admin->username ?? 'Guest User' }}
          </p>  

          <hr style="border:none; border-top:2px solid #ad2d2dff; margin:0.5rem 0;">

          <!-- Edit Profile -->
         <button class="account-btn" onclick="window.location.href='{{ route('admin.profile') }}'">
          <i class="fa-solid fa-user-pen icon"></i>
          <span class="account-btn-text">Edit Profile</span>
      </button>


          <button class="account-btn">
            <i class="fa-solid fa-circle-question icon"></i>
            <span class="account-btn-text">User Guide</span>
          </button>
          <form action="{{ route('logout') }}" method="POST" onsubmit="return confirm('Are you sure you want to log out?')" style="display:inline;">
            @csrf
            <!-- Logout Button -->
            <button type="button" class="account-btn logout" onclick="openLogoutModal()">
              <i class="fa-solid fa-right-from-bracket icon"></i>
              <span class="account-btn-text">Log Out</span>
            </button>
          </form>
        </div>
      </div>

      <!-- Menu Button -->
      <div class="menu-btn" id="menuToggle" data-tooltip="Open Navigation Menu">
          <i class="fas fa-bars Menu-icon"></i>
      </div>
    </div>
  </div>
</header>

  {{-- Include both guide modals --}}
  @include('layouts.modals.submit.authentication.logout_modal')

  <div id="sidebarMenu" class="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <h3>Navigation</h3>
        <button class="close-btn" id="closeSidebar">&times;</button>
    </div>

    <!-- Sidebar Links -->
    <div class="sidebar-links list-group">
        @if(!empty($pageTitle) && $pageTitle !== "SACSI Volunteer Management")
            <a href="{{ url('/') }}">
                <i class="fas fa-home me-2"></i><span class="link-text">Home</span>
            </a>
        @endif

        <a href="routegoeshere">
            <i class="fas fa-user-graduate me-2"></i><span class="link-text">Volunteer List</span>
        </a>

        <!-- Import Volunteers -->
        <a href="routegoeshere" class="sidebar-parent">
            <i class="fa-solid fa-upload fa-3x"></i><span class="link-text">Import Volunteer</span>
            <i class="fas fa-chevron-down toggle-chevron"></i>
        </a>
        <div class="sidebar-child">
            <a href="routegoeshere#handling-Section">
                <i class="fas fa-tasks"></i><span class="link-text">Import & Validation</span>
            </a>
            <a href="routegoeshere#import-Section">
                <i class="fas fa-user-check"></i><span class="link-text">Verified Entries</span>
            </a>
            <a href="routegoeshere#importlog-Section">
                <i class="fas fa-history"></i><span class="link-text">Import Logs</span>
            </a>
            <hr>
        </div>

        <a href="routegoeshere">
            <i class="fas fa-calendar-plus"></i><span class="link-text">Create Event</span>
        </a>

        <!-- Event Manager -->
        <a href="routegoeshere" class="sidebar-parent">
            <i class="fas fa-tasks"></i><span class="link-text">Event Manager</span>
            <i class="fas fa-chevron-down toggle-chevron"></i>
        </a>
        <div class="sidebar-child">
            <a href="routegoeshere#UpcomingEvents-Section">
                <i class="fas fa-calendar-check"></i><span class="link-text">Upcoming</span>
            </a>
            <a href="routegoeshere#OngoingEvents-Section">
                <i class="fas fa-hourglass-half"></i><span class="link-text">Ongoing</span>
            </a>
            <a href="routegoeshere#CompletedEvent-Section">
                <i class="fas fa-clipboard-check"></i><span class="link-text">Completed</span>
            </a>
            <a href="routegoeshere#CancelledEvent-Section">
                <i class="fas fa-ban"></i><span class="link-text">Canceled</span>
            </a>
            <hr>
        </div>

        <a href="{{ route('dashboard') }}">
            <i class="fas fa-chart-line me-2"></i><span class="link-text">Dashboard</span>
        </a>

        <a href="routegoeshere" class="nav-link system-log-link">
            <i class="fas fa-clipboard-list me-2"></i><span class="link-text">System Logs</span>
        </a>

        @if(!empty($pageTitle) && $pageTitle !== "SACSI Volunteer Management")
            <div class="sidebar-divider"></div>

            <div class="upcoming-events">
                <h5>Upcoming Events</h5>
                <table class="events-table">
                    <tbody>
                        @foreach($upcomingEvents ?? [] as $event)
                            <tr>
                                <td>
                                    <div class="event-info">
                                        <span class="event-title">{{ $event['title'] }}</span>
                                        <hr>
                                        <div class="event-meta">
                                            <span class="event-date">{{ $event['date'] }}</span>
                                            <span class="event-date">{{ $event['time'] }}</span>
                                            <button class="detail-btn" onclick="alert('{{ $event['details'] }}')">
                                                Details
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
  </div>


<script>
    // Toggle account dropdown with smooth animation
  document.addEventListener('DOMContentLoaded', () => {
    const accountBtn = document.querySelector('.account-btn');
    const accountBox = document.querySelector('.account-box');

    accountBtn.addEventListener('click', () => {
      accountBox.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
      if (!accountBtn.contains(e.target) && !accountBox.contains(e.target)) {
        accountBox.classList.remove('open');
      }
    });
  });
</script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('overlay');
    const menuToggle = document.getElementById('menuToggle');
    const closeSidebar = document.getElementById('closeSidebar');

    const openSidebar = () => {
        sidebar.classList.add('active');
        overlay.classList.add('active');
    };

    const closeSidebarFn = () => {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
    };

    menuToggle.addEventListener('click', openSidebar);
    closeSidebar.addEventListener('click', closeSidebarFn);
    overlay.addEventListener('click', closeSidebarFn);
  });
</script>
