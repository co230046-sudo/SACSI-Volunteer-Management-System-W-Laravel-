<style>
    /* Modal Overlay */
    .logout-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    justify-content: center;
    align-items: center;
    z-index: 9999;
    }

    /* Modal Box */
    .logout-modal {
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.25);
    padding: 30px 35px;
    max-width: 420px;
    width: 90%;
    text-align: center;
    animation: fadeInUp 0.3s ease;
    }

    /* Heading */
    .logout-modal h3 {
    margin-bottom: 12px;
    font-size: 1.4rem;
    font-weight: 700;
    color: #222; /* darker and bolder */
    }

    /* Message Text */
    .logout-modal p {
    margin-bottom: 25px;
    font-size: 1rem;
    line-height: 1.5;
    color: #444; /* more contrast */
    }

    /* Button Container */
    .logout-modal-buttons {
    display: flex;
    justify-content: center;
    gap: 16px;
    }

    /* Confirm Button */
    .confirm-btn {
    background-color: #b2000c;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 22px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.25s ease, transform 0.15s ease;
    }

    .confirm-btn:hover {
    background-color: #8e0009;
    transform: translateY(-1px);
    }

    /* Cancel Button */
    .cancel-btn {
    background-color: #f1f1f1;
    color: #222;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 10px 22px;
    font-size: 0.95rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.25s ease, transform 0.15s ease;
    }

    .cancel-btn:hover {
    background-color: #e2e2e2;
    transform: translateY(-1px);
    }

    /* Animation */
    @keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
    }

</style>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="logout-modal-overlay">
  <div class="logout-modal">
    <h3><i class="fa-solid fa-circle-exclamation" style="color:#d9534f;"></i> Confirm Logout</h3>
    <p>Are you sure you want to log out of your account?</p>

    <div class="logout-modal-buttons">
      <button type="button" class="cancel-btn" onclick="closeLogoutModal()">Cancel</button>
      <form id="logoutForm" action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="confirm-btn">Yes, Log Out</button>
      </form>
    </div>
  </div>
</div>


<script>
function openLogoutModal() {
  document.getElementById('logoutModal').style.display = 'flex';
}

function closeLogoutModal() {
  document.getElementById('logoutModal').style.display = 'none';
}

// Optional: close when clicking outside modal
window.addEventListener('click', function(e) {
  const modal = document.getElementById('logoutModal');
  if (e.target === modal) {
    closeLogoutModal();
  }
});
</script>
