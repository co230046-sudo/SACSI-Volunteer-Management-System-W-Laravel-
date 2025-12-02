<style>
/* Reset Import Modal */
.reset-import-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    font-family: 'Segoe UI', Roboto, sans-serif;
}
.reset-import-modal.active {
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Overlay */
.reset-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Modal Box */
.reset-modal-box {
    background: #fff;
    border-radius: 16px;
    width: 90%;
    max-width: 500px;
    padding: 2rem;
    animation: fadeInUp 0.3s ease forwards;
    box-shadow: 0 12px 40px rgba(0,0,0,0.35);
}

/* Header */
.reset-modal-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    text-align: center;
}
.reset-modal-header h2 {
    font-size: 1.6rem;
    color: #B2000C;
    margin: 0;
}
.reset-modal-icon {
    font-size: 2rem;
    color: #B2000C;
}

/* Separator */
.reset-modal-separator {
    width: 85%;
    height: 1px;
    background: #ececec;
    margin: 1rem auto;
}

/* LEFT-ALIGNED MESSAGE AREA */
.reset-text-block {
    text-align: left !important;
    margin: 1rem auto 1.5rem;
    padding: 0 0.75rem;
    font-size: 1.07rem;
    line-height: 1.6;
    color: #333;
    word-break: break-word;
}

/* Buttons */
.reset-modal-buttons {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 1.5rem;
}

.reset-btn-cancel {
    background-color: #f1f1f1;
    color: #222;
    border: 1px solid #ccc;
    border-radius: 8px;
    padding: 10px 22px;
    font-size: .95rem;
    font-weight: 600;
    cursor: pointer;
}
.reset-btn-cancel:hover {
    background-color: #e2e2e2;
}

.reset-btn-confirm {
    background-color: #b2000c;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 10px 22px;
    font-size: .95rem;
    font-weight: 600;
    cursor: pointer;
}
.reset-btn-confirm:hover {
    background-color: #8e0009;
}

/* Success Styles */
.reset-success-icon {
    font-size: 2rem;
    color: #28a745;
}
.reset-success-title {
    color: #28a745 !important;
}
.reset-success-text {
    text-align: left !important;
}

.reset-success-text {
    font-size: 1.07rem;
    line-height: 1.75;       /* better readability */
    margin-top: 1.8rem;       /* proper spacing from header */
    padding: 0 0.75rem;       /* clean left/right padding */
    white-space: normal;      /* ensures proper wrapping */
}

.reset-success-text br {
    margin-bottom: 0.6rem;
    display: block;
    content: "";
}
</style>

<!-- CONFIRM RESET MODAL -->
<div id="resetImportModal" class="reset-import-modal">
    <div id="resetModalOverlay" class="reset-modal-overlay">
        <div class="reset-modal-box">

            <div class="reset-modal-header">
                <i class="fa-solid fa-rotate-left reset-modal-icon"></i>
                <h2>Clear Import Preview?</h2>
            </div>

            <hr class="reset-modal-separator">

            <div id="resetModalMessage" class="reset-text-block"></div>

            <div class="reset-modal-buttons">
                <button type="button" class="reset-btn-cancel" id="cancelResetModal">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </button>

                <form action="{{ route('volunteer.import.reset') }}" method="POST">
                    @csrf
                    <button type="submit" class="reset-btn-confirm" id="confirmResetBtn">
                        <i class="fa-solid fa-check"></i> Confirm
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>


<!-- SUCCESS MODAL -->
<div id="resetSuccessModal" class="reset-import-modal">
    <div id="resetSuccessOverlay" class="reset-modal-overlay">
        <div class="reset-modal-box">

            <div class="reset-modal-header">
                <i class="fa-solid fa-circle-check reset-success-icon"></i>
                <h2 class="reset-success-title">Success</h2>
            </div>

            <hr class="reset-modal-separator">

            <div id="resetSuccessMessage" class="reset-text-block reset-success-text"></div>

            <div class="reset-modal-buttons">
                <button type="button" class="reset-btn-confirm" id="resetSuccessOkBtn">
                    <i class="fa-solid fa-check"></i> OK
                </button>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    /* ==========================
       CONFIRM RESET MODAL
    ========================== */
    const resetModal = document.getElementById('resetImportModal');
    const resetOverlay = document.getElementById('resetModalOverlay');
    const openResetBtn = document.getElementById('openResetModal');
    const cancelResetBtn = document.getElementById('cancelResetModal');
    const confirmResetBtn = document.getElementById('confirmResetBtn');
    const resetModalMessage = document.getElementById('resetModalMessage');

    function openResetModal() {
        const validCount     = {{ session()->has('validEntries') ? count(session('validEntries')) : 0 }};
        const invalidCount   = {{ session()->has('invalidEntries') ? count(session('invalidEntries')) : 0 }};
        const duplicateCount = {{ session()->has('duplicateEntries') ? count(session('duplicateEntries')) : 0 }};
        const total = validCount + invalidCount + duplicateCount;

        resetModalMessage.innerHTML = `
            Are you sure you want to clear all imported entries?<br>
            <strong>This action cannot be undone.</strong><br><br>
            <span style="color:#B2000C; font-weight:700; font-size:1.05rem;">
                Rows to clear: ${total}
            </span>

            <div style="font-size:0.95rem; margin-top:8px; line-height:1.4;">
                <span style="color:#28a745;">Valid: ${validCount}</span><br>
                <span style="color:#B2000C;">Invalid: ${invalidCount}</span><br>
                <span style="color:#d38b00;">Duplicates: ${duplicateCount}</span>
            </div>
        `;

        resetModal.classList.add('active');
    }

    function closeResetModal() { resetModal.classList.remove('active'); }

    openResetBtn?.addEventListener('click', openResetModal);
    cancelResetBtn?.addEventListener('click', closeResetModal);
    resetOverlay?.addEventListener('click', e => { if (e.target === resetOverlay) closeResetModal(); });

    /* ==========================
       RESET SUCCESS MODAL
    ========================== */
    const successModal   = document.getElementById('resetSuccessModal');
    const successOverlay = document.getElementById('resetSuccessOverlay');
    const successMessage = document.getElementById('resetSuccessMessage');
    const successOkBtn   = document.getElementById('resetSuccessOkBtn');

    function showResetSuccess(msg) {
        successMessage.innerHTML = msg;
        successModal.classList.add('active');
    }

    function closeSuccess() { successModal.classList.remove('active'); }

    successOkBtn?.addEventListener('click', closeSuccess);
    successOverlay?.addEventListener('click', e => { if (e.target === successOverlay) closeSuccess(); });

    /* =====================================================
       AUTO SHOW RESET SUCCESS (Redirect)
    ===================================================== */
    @if(session('resetSuccess'))
        showResetSuccess(`{!! session('resetSuccess') !!}`);
    @endif

    /* =====================================================
       FLASH BAR “SHOW DETAILS” → OPEN RESET SUCCESS MODAL
    ===================================================== */
    document.addEventListener('click', function (e) {
        const link = e.target.closest('.reset-details-link');
        if (!link) return;

        e.preventDefault();

        const encoded = link.getAttribute('data-details');
        if (!encoded) return;

        const decoded = atob(encoded);  // decode details

        successMessage.innerHTML = decoded;  // insert HTML
        successModal.classList.add('active'); // open reset modal
    });

});
</script>
