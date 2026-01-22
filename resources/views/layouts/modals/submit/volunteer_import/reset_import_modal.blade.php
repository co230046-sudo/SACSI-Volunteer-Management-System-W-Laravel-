<style>
/* ==========================
   RESET IMPORT MODAL (Redesign)
   Matches class schedule modal feel
========================== */
.reset-import-modal{
    display:none;
    position:fixed;
    inset:0;
    z-index:9999;
    font-family:'Segoe UI', Roboto, sans-serif;
}
.reset-import-modal.active{
    display:flex;
    justify-content:center;
    align-items:center;
}

.reset-modal-overlay{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.55);
    display:flex;
    justify-content:center;
    align-items:center;
    padding:18px;
}

/* Modal box */
.reset-modal-box{
    width:100%;
    max-width:520px;
    background:#fff;
    border-radius:18px;
    box-shadow:0 18px 60px rgba(0,0,0,.35);
    overflow:hidden;
    transform:translateY(6px);
    animation:resetPop .18s ease-out forwards;
    border:1px solid rgba(0,0,0,.06);
}

/* Header area */
.reset-modal-top{
    padding:18px 20px 12px;
    display:flex;
    align-items:flex-start;
    gap:12px;
}

.reset-icon-wrap{
    flex:0 0 auto;
    width:42px;
    height:42px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(178,0,12,.10);
    border:1px solid rgba(178,0,12,.14);
}
.reset-icon-wrap i{
    font-size:18px;
    color:#B2000C;
}

.reset-title-wrap{
    flex:1 1 auto;
}
.reset-title{
    margin:0;
    font-size:1.22rem;
    font-weight:800;
    color:#B2000C;
    letter-spacing:.2px;
    line-height:1.2;
}
.reset-subtitle{
    margin-top:6px;
    font-size:.95rem;
    color:#666;
    line-height:1.35;
}

.reset-close{
    flex:0 0 auto;
    border:none;
    background:transparent;
    width:36px;
    height:36px;
    border-radius:10px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#333;
    cursor:pointer;
}
.reset-close:hover{
    background:rgba(0,0,0,.06);
}

/* Divider */
.reset-divider{
    height:1px;
    background:#eee;
    margin:0 20px;
}

/* Body */
.reset-body{
    padding:16px 20px 18px;
}

.reset-text-block{
    text-align:left !important;
    padding:0;
    margin:0;
    font-size:1rem;
    line-height:1.65;
    color:#333;
    word-break:break-word;
}

/* Highlight section */
.reset-summary{
    margin-top:14px;
    padding:12px 14px;
    border-radius:14px;
    border:1px solid rgba(178,0,12,.18);
    background:rgba(178,0,12,.06);
}
.reset-summary .reset-summary-title{
    font-size:.92rem;
    font-weight:800;
    color:#B2000C;
    margin-bottom:6px;
}
.reset-summary .reset-summary-row{
    display:flex;
    justify-content:space-between;
    gap:10px;
    font-size:.95rem;
    line-height:1.55;
}
.reset-chip{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 10px;
    border-radius:999px;
    font-size:.86rem;
    font-weight:800;
    border:1px solid rgba(0,0,0,.10);
    background:#fff;
}
.reset-chip-valid{ color:#1f7a39; border-color:rgba(40,167,69,.25); background:rgba(40,167,69,.08); }
.reset-chip-invalid{ color:#B2000C; border-color:rgba(178,0,12,.25); background:rgba(178,0,12,.08); }
.reset-chip-dup{ color:#a56b00; border-color:rgba(211,139,0,.25); background:rgba(211,139,0,.10); }

/* Footer buttons */
.reset-footer{
    padding:14px 20px 18px;
    display:flex;
    justify-content:flex-end;
    gap:10px;
    background:#fafafa;
    border-top:1px solid #eee;
}

.reset-btn{
    border-radius:12px;
    padding:10px 14px;
    font-size:.95rem;
    font-weight:800;
    cursor:pointer;
    border:1px solid transparent;
    display:inline-flex;
    align-items:center;
    gap:8px;
    transition:.15s ease;
    user-select:none;
}
.reset-btn i{ font-size:.95rem; }

/* Cancel */
.reset-btn-cancel{
    background:#fff;
    color:#222;
    border-color:rgba(0,0,0,.15);
}
.reset-btn-cancel:hover{
    background:rgba(0,0,0,.04);
}

/* Confirm */
.reset-btn-confirm{
    background:#B2000C;
    color:#fff;
    border-color:rgba(178,0,12,.55);
}
.reset-btn-confirm:hover{
    background:#8e0009;
}

/* Animation */
@keyframes resetPop{
    to { transform:translateY(0); }
}

/* Mobile tweaks */
@media (max-width: 520px){
    .reset-modal-top{ padding:16px 16px 10px; }
    .reset-body{ padding:14px 16px 16px; }
    .reset-footer{ padding:12px 16px 16px; }
}
</style>

<!-- CONFIRM RESET MODAL (Redesigned) -->
<div id="resetImportModal" class="reset-import-modal">
    <div id="resetModalOverlay" class="reset-modal-overlay">
        <div class="reset-modal-box" role="dialog" aria-modal="true" aria-labelledby="resetTitle">

            <div class="reset-modal-top">
                <div class="reset-icon-wrap">
                    <i class="fa-solid fa-rotate-left"></i>
                </div>

                <div class="reset-title-wrap">
                    <h2 id="resetTitle" class="reset-title">Clear Import Preview?</h2>
                    <div class="reset-subtitle">
                        This will remove all imported preview rows from your session.
                        <strong style="color:#B2000C;">This action can’t be undone.</strong>
                    </div>
                </div>

                <button type="button" class="reset-close" id="cancelResetModal" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="reset-divider"></div>

            <div class="reset-body">
                <div id="resetModalMessage" class="reset-text-block"></div>
            </div>

            <div class="reset-footer">
                <button type="button" class="reset-btn reset-btn-cancel" id="cancelResetModalAlt">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </button>

                <form action="{{ route('volunteer.import.reset') }}" method="POST" style="margin:0;">
                    @csrf
                    <button type="submit" class="reset-btn reset-btn-confirm" id="confirmResetBtn">
                        <i class="fa-solid fa-check"></i> Confirm
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
(function () {

    function decodeBase64Utf8(str) {
        try {
            return new TextDecoder("utf-8").decode(
                Uint8Array.from(atob(str), c => c.charCodeAt(0))
            );
        } catch (e) {
            return '';
        }
    }

    function runWhenReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    runWhenReady(() => {

        // Keep your existing variables if you already have them — this just supports the new second cancel button
        const resetModal = document.getElementById('resetImportModal');
        const resetOverlay = document.getElementById('resetModalOverlay');
        const openResetBtn = document.getElementById('openResetModal');
        const cancelResetBtn = document.getElementById('cancelResetModal');
        const cancelResetBtnAlt = document.getElementById('cancelResetModalAlt');
        const resetModalMessage = document.getElementById('resetModalMessage');

        function openResetModal() {
            const validCount     = {{ session()->has('validEntries') ? count(session('validEntries')) : 0 }};
            const invalidCount   = {{ session()->has('invalidEntries') ? count(session('invalidEntries')) : 0 }};
            const duplicateCount = {{ session()->has('duplicateEntries') ? count(session('duplicateEntries')) : 0 }};
            const total = validCount + invalidCount + duplicateCount;

            // Same content you had — styled better by the new CSS
            resetModalMessage.innerHTML = `
                <div class="reset-summary">
                    <div class="reset-summary-title">Rows to clear</div>
                    <div class="reset-summary-row" style="margin-bottom:10px;">
                        <div style="font-weight:800; color:#333;">Total</div>
                        <div style="font-weight:900; color:#B2000C;">${total}</div>
                    </div>

                    <div style="display:flex; flex-wrap:wrap; gap:8px;">
                        <span class="reset-chip reset-chip-valid">✅ Valid: ${validCount}</span>
                        <span class="reset-chip reset-chip-invalid">❌ Invalid: ${invalidCount}</span>
                        <span class="reset-chip reset-chip-dup">⚠️ Duplicates: ${duplicateCount}</span>
                    </div>
                </div>
            `;

            resetModal.classList.add('active');
        }

        function closeResetModal() { resetModal.classList.remove('active'); }

        openResetBtn?.addEventListener('click', openResetModal);
        cancelResetBtn?.addEventListener('click', closeResetModal);
        cancelResetBtnAlt?.addEventListener('click', closeResetModal);
        resetOverlay?.addEventListener('click', e => { if (e.target === resetOverlay) closeResetModal(); });

        /* =====================================================
           UNIVERSAL SUCCESS MODAL (after redirect)
           ✅ FIX: Use base64 session('resetDetails') (same as Show Details)
        ===================================================== */
        function openUniversal(html, title, subtitle) {
    if (typeof window.openUniversalModal === 'function') {
        window.openUniversalModal({
            title: title,
            subtitle: subtitle,
            html: html,
            type: 'success'
        });
        return true;
    }

    // HARD fallback for UFM structure
    const modal = document.getElementById('feedbackModal');
    if (!modal) return false;

    modal.querySelector('[data-ufm-title]').innerHTML = title || '';
    modal.querySelector('[data-ufm-subtitle]').innerHTML = subtitle || '';
    modal.querySelector('[data-ufm-body]').innerHTML = html || '';

    modal.classList.add('active');
    modal.setAttribute('aria-hidden','false');

    return true;
}

        // ✅ Auto-open success after redirect (base64-safe)
        @if(session('resetDetails'))
            setTimeout(() => {
                let decoded = '';
                decoded = decodeBase64Utf8(String(@json(session('resetDetails'))).trim());
                if (!decoded) return;

                openUniversal(
                    decoded,
                    'Reset Completed',
                    'Import preview cleared successfully.'
                );
            }, 120);
        @endif

        // ✅ Flash bar "Show Details" -> open Universal modal
        document.addEventListener('click', function (e) {
            const link = e.target.closest('.reset-details-link');
            if (!link) return;

            e.preventDefault();

            const encoded = link.getAttribute('data-details');
            if (!encoded) return;

            let decoded = '';
            decoded = decodeBase64Utf8(String(encoded).trim());
            if (!decoded) return;

            openUniversal(
                decoded,
                'Reset Completed',
                'Import preview cleared successfully.'
            );
        });

    });

})();
</script>
