<!-- ================================
✅ BACK BUTTON STYLES
================================ -->
<style>
  :root{
    --back-accent: #b23a48;
    --back-accent-dark: #8f2c37;
    --back-accent-darker: #6f222b;
    --back-soft: rgba(178, 58, 72, .16);
    --back-shadow: 0 10px 28px rgba(0,0,0,.16);
    --back-shadow-hover: 0 16px 40px rgba(0,0,0,.20);
  }

  .back-button{
    position: fixed;
    top: 92px;
    left: 18px;
    z-index: 1001;
    border: 0;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 14px;
    color: #fff;
    background: linear-gradient(180deg, var(--back-accent), var(--back-accent-dark));
    box-shadow: var(--back-shadow);
    font-size: 0.98rem;
    font-weight: 700;
    backdrop-filter: blur(8px);
    transition: all .22s ease;
  }

  .back-button .icon-wrap{
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: rgba(255,255,255,.16);
  }

  .back-button i{ font-size: 18px; }

  .back-button:hover{
    transform: translateY(-2px) scale(1.03);
    box-shadow: var(--back-shadow-hover);
  }

  .back-button:active{
    transform: scale(.97);
  }

  @media (max-width: 576px){
    .back-button .label{ display:none; }
  }
</style>

<!-- ================================
✅ BACK BUTTON HTML
================================ -->
<button class="back-button" type="button" onclick="goBack()" aria-label="Go back">
  <span class="icon-wrap">
    <i class="fas fa-arrow-left"></i>
  </span>
  <span class="label">Back</span>
</button>

<!-- ================================
✅ BACK BUTTON SCRIPT (FINAL FIX)
================================ -->
<script>
(function () {
  const FALLBACK_URL = document.referrer || "/home"; // ✅ real last page
  const MAX_STACK = 20;

  const currentPath = window.location.pathname.replace(/\/+$/, "");

  let historyStack = [];
  try {
    historyStack = JSON.parse(sessionStorage.getItem("pageHistory") || "[]");
  } catch {
    historyStack = [];
  }

  // ✅ Save visited pages
  if (currentPath && historyStack[historyStack.length - 1] !== currentPath) {
    historyStack.push(currentPath);
    if (historyStack.length > MAX_STACK) historyStack.shift();
    sessionStorage.setItem("pageHistory", JSON.stringify(historyStack));
  }

  function isLoopDetected() {
    if (historyStack.length >= 4) {
      const h = historyStack.slice(-4);
      if (h[0] === h[2] && h[1] === h[3]) return true;
    }
    if (historyStack.length >= 3) {
      const h = historyStack.slice(-3);
      if (h[0] === h[1] && h[1] === h[2]) return true;
    }
    return false;
  }

  function safeFallback() {
    sessionStorage.removeItem("pageHistory");
    window.location.href = FALLBACK_URL;
  }

  // ✅ BACK BUTTON HANDLER
  window.goBack = function () {
    if (isLoopDetected()) return safeFallback();

    if (window.history.length > 1) {
      const before = currentPath;

      window.history.back();

      setTimeout(() => {
        const now = window.location.pathname.replace(/\/+$/, "");
        if (now === before) safeFallback();
      }, 250);

      return;
    }

    safeFallback();
  };
})();
</script>
