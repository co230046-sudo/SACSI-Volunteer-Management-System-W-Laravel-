<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SACSI - Login</title>

  {{-- CSS --}}
  <link rel="stylesheet" href="{{ asset('assets/authentication/css/auth.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
  <!-- Background Slideshow -->
  <div class="background-container">
    <img src="{{ asset('assets/authentication/img/background/bg1.png') }}" class="background-image" alt="Background 1">
    <img src="{{ asset('assets/authentication/img/background/bg2.png') }}" class="background-image" alt="Background 2">
    <img src="{{ asset('assets/authentication/img/background/bg3.png') }}" class="background-image" alt="Background 3">
    <img src="{{ asset('assets/authentication/img/background/bg4.png') }}" class="background-image" alt="Background 4">
    <img src="{{ asset('assets/authentication/img/background/bg5.png') }}" class="background-image" alt="Background 5">
    <img src="{{ asset('assets/authentication/img/background/bg6.png') }}" class="background-image" alt="Background 6">
    <img src="{{ asset('assets/authentication/img/background/bg7.png') }}" class="background-image" alt="Background 7">
    <img src="{{ asset('assets/authentication/img/background/bg8.png') }}" class="background-image" alt="Background 8">
    <img src="{{ asset('assets/authentication/img/background/bg9.png') }}" class="background-image" alt="Background 9">
  </div>

  <!-- Main Content -->
  <div class="content-wrapper two-columns">
    <!-- Left Side: Logo and Title -->
    <div class="left-side">
        <div class="logo-circle">
            <img src="{{ asset('assets/authentication/img/sacsi-logo.png') }}" alt="App Logo" class="app-logo">
        </div>
        <h1 class="app-title" style="font-size: 2.6rem; text-align:center;">SACSI Volunteer Management System</h1>
    </div>

    <!-- Right Side: Login Form -->
    <div class="right-side">
        <form action="{{ route('auth.login.submit') }}" method="POST" class="form-card">
            @csrf
            <h1>Login</h1>

            {{-- Username / Email / Full Name --}}
            <div class="input-group required-field">
                <i class="fa fa-user icon"></i>
                <input type="text" name="username" placeholder="Username, Email, or Full Name" value="{{ old('username') }}" required>
            </div>

            {{-- Password --}}
            <div class="input-group required-field">
                <div class="input-wrapper">
                    <i class="fa fa-lock icon"></i>
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="fa fa-eye toggle-password" onclick="togglePassword(this)" style="display:none;"></i>
                </div>
            </div>

            {{-- Display ALL errors below password --}}
            @if($errors->any() || session('login_error'))
                <div style="margin-bottom:10px;">
                    @foreach ($errors->all() as $error)
                        <small class="error" style="display:block; margin-bottom:10px; background-color:#c72a2a; padding:12px; border-radius:12px;">
                            {{ $error }}
                        </small>
                    @endforeach

                    @if(session('login_error'))
                        <small class="error" style="display:block; margin-bottom:10px; background-color:#c72a2a; padding:12px; border-radius:12px;">
                            {{ session('login_error') }}
                        </small>
                    @endif
                </div>
            @endif

            <hr class="form-divider">

            <button type="submit" class="btn-primary">Login</button>

            <p class="switch-link">
                Register New Admin?
                <a href="{{ route('auth.register') }}">Register</a>
            </p>
        </form>
    </div>
  </div>


  {{-- JS --}}
  <script src="{{ asset('assets/authentication/js/auth.js') }}"></script>

  {{-- Show Password --}}
  <script>
    const passwordInput = document.querySelector('input[name="password"]');
    const eyeIcon = document.querySelector('.toggle-password');

    // Show eye icon only when typing
    passwordInput.addEventListener('input', function() {
        eyeIcon.style.display = this.value.trim() !== '' ? 'block' : 'none';
    });

    // Toggle password visibility
    function togglePassword(el) {
        const input = el.parentElement.querySelector('input');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        el.classList.toggle('fa-eye', !isPassword);
        el.classList.toggle('fa-eye-slash', isPassword);
    }
  </script>
</body>
</html>
