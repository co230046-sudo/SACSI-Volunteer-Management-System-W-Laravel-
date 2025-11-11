<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SACSI - Admin Register</title>

  {{-- CSS --}}
  <link rel="stylesheet" href="{{ asset('assets/authentication/css/auth.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <!-- Background -->
  <div class="background-container">
    @for ($i = 1; $i <= 9; $i++)
      <img src="{{ asset("assets/authentication/img/background/bg{$i}.png") }}" class="background-image" alt="Background {{ $i }}">
    @endfor
  </div>

  <!-- Main Content: Two Columns -->
  <div class="content-wrapper two-columns">
    <!-- Left Side: Logo & Title -->
    <div class="left-side">
      <div class="logo-circle">
        <img src="{{ asset('assets/authentication/img/sacsi-logo.png') }}" alt="App Logo" class="app-logo">
        <h1 class="app-title">SACSI Volunteer Management System</h1>
      </div>
    </div>

    <!-- Right Side: Registration Form -->
    <div class="right-side">
      <form action="{{ route('auth.register.submit') }}" method="POST" class="form-card" enctype="multipart/form-data">
        @csrf
        <h1>Registration</h1>

        {{-- Full Name --}}
        <div class="input-group required-field">
          <i class="fa fa-user icon"></i>
          <input type="text" name="full_name" placeholder="Full Name" value="{{ old('full_name') }}" required>
        </div>


        {{-- Username --}}
        <div class="input-group required-field">
          <i class="fa fa-id-badge icon"></i>
          <input type="text" name="username" placeholder="Username" value="{{ old('username') }}" required>
        </div>

        {{-- Email --}}
        <div class="input-group required-field">
          <i class="fa fa-envelope icon"></i>
          <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
        </div>

        {{-- Role Dropdown --}}
        <div class="custom-dropdown input-group required-field">
          <i class="fa-solid fa-user-gear icon"></i>
          <div class="dropdown-selected">Select Role</div>
          <ul class="dropdown-options">
            <li data-value="super_admin">Super Admin</li>
            <li data-value="admin">Admin</li>
          </ul>
        </div>
        <input type="hidden" name="role" id="role-hidden" value="{{ old('role') }}">

        {{-- Password --}}
        <div class="input-group required-field">
          <div class="input-wrapper">
            <i class="fa fa-lock icon"></i>
            <input type="password" id="password" name="password" placeholder="Password" required oninput="togglePasswordHint(this)">
            <i class="fa fa-eye toggle-password" onclick="togglePasswords(this)" style="display:none;"></i>
          </div>
          <div class="password-hint" id="password-hint">
            Must be at least 8 characters, include 1 uppercase letter and 1 number.
          </div>
        </div>

        {{-- Confirm Password --}}
        <div class="input-group required-field">
          <i class="fa fa-lock icon"></i>
          <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm Password" required>
        </div>


        {{-- Profile Picture --}}
        <div class="file-input-group required-field">
          <i class="fa fa-upload file-icon"></i>
          <label id="file-label" class="custom-file-upload">
            <p id="file-name" style="padding-left: 30px; color: #555;">Upload Picture</p>
            <input type="file" id="profile-picture" name="profile_picture" accept="image/*" onchange="handleFileUpload(this)" required>
          </label>
          <button id="see-photo-btn" class="see-photo-btn" style="display:none;" type="button" onclick="openPhotoModal()">See Photo</button>
        </div>

        {{-- Display all errors --}}
        @if($errors->any())
          <div style="margin-bottom:10px;">
            @foreach ($errors->all() as $error)
              <small class="error" style="display:block; margin-bottom:10px; background-color: #c72a2a; padding:12px; border-radius: 12px;">
                {{ $error }}
              </small>
            @endforeach
          </div>
        @endif

        <hr class="form-divider">
        <button type="submit" class="btn-primary">Sign Up</button>
        <p class="switch-link">
          Already have an account?
          <a href="{{ route('auth.login') }}">Back to Login</a>
        </p>
      </form>
    </div>
  </div>

  <!-- Image Preview Modal -->
  <div id="photoModal" class="photo-modal">
    <div class="photo-modal-content">
      <span class="close" onclick="closePhotoModal()">&times;</span>
      <img id="photoPreview" src="" alt="Uploaded Image Preview">
    </div>
  </div>

  {{-- JS --}}
  <script src="{{ asset('assets/authentication/js/auth.js') }}"></script>

  {{-- Password visibility --}}
  <script>
  // Password toggle logic remains unchanged
  const passwordInput = document.getElementById('password');
  const confirmInput = document.getElementById('password_confirmation');
  const eyeIcon = document.querySelector('.toggle-password');

  passwordInput.addEventListener('input', function () {
    eyeIcon.style.display = this.value.trim() !== '' ? 'block' : 'none';
  });

  function togglePasswords(el) {
    [passwordInput, confirmInput].forEach(input => {
      input.type = input.type === 'password' ? 'text' : 'password';
    });
    el.classList.toggle('fa-eye');
    el.classList.toggle('fa-eye-slash');
  }

  // File upload + modal
  let uploadedImageURL = "";
  let modalShownOnce = false; // ensure modal shows once automatically

  function handleFileUpload(input) {
    const file = input.files[0];
    const seePhotoBtn = document.getElementById("see-photo-btn");
    const fileNameDisplay = document.getElementById("file-name");

    if (file) {
      uploadedImageURL = URL.createObjectURL(file);
      fileNameDisplay.textContent = file.name; // show file name
      seePhotoBtn.style.display = "inline-block";

      // Show modal automatically only once
      if (!modalShownOnce) {
        openPhotoModal();
        modalShownOnce = true;
      }
    } else {
      fileNameDisplay.textContent = "Upload Picture";
      seePhotoBtn.style.display = "none";
      uploadedImageURL = "";
      modalShownOnce = false;
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
    if (event.target === modal) closePhotoModal();
  });
</script>

    {{-- Password Hint --}}
    <script>
      function togglePasswordHint(input) {
        const hint = document.getElementById('password-hint');
        if (input.value.length > 0) {
          hint.classList.add('visible');
        } else {
          hint.classList.remove('visible');
        }
      }

    </script>

    {{-- Custom Role Dropdown --}}
    <script>
        document.querySelectorAll('.custom-dropdown').forEach(dropdown => {
    const selected = dropdown.querySelector('.dropdown-selected');
    const options = dropdown.querySelector('.dropdown-options');
    const hiddenInput = document.getElementById('role-hidden');

    // Toggle dropdown
    selected.addEventListener('click', () => {
        dropdown.classList.toggle('focused');
        options.style.display = options.style.display === 'block' ? 'none' : 'block';
    });

    // Select option
    options.querySelectorAll('li').forEach(option => {
        option.addEventListener('click', () => {
            selected.textContent = option.textContent;
            hiddenInput.value = option.getAttribute('data-value'); // <-- important
            options.style.display = 'none';
            dropdown.classList.remove('focused');
        });
    });

    // Close if clicking outside
    document.addEventListener('click', e => {
        if (!dropdown.contains(e.target)) {
            options.style.display = 'none';
            dropdown.classList.remove('focused');
        }
    });
});


    </script>
</body>
</html>
