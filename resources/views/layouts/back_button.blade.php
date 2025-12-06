<style>
  :root{
    /* toned-down palette */
    --back-accent: #b23a48;          /* softer red */
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
    letter-spacing: .2px;

    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);

    transition:
      transform .22s cubic-bezier(.2,.8,.2,1),
      box-shadow .22s ease,
      filter .22s ease;
  }

  /* remove the “whole outline/shine” layers */
  .back-button::before,
  .back-button::after{
    content: none !important;
  }

  /* Icon bubble (NO outline) */
  .back-button .icon-wrap{
    width: 34px;
    height: 34px;
    border-radius: 12px;
    display: grid;
    place-items: center;

    background: rgba(255,255,255,.16);
    box-shadow: none !important;   /* ✅ no outline */
    border: none !important;       /* ✅ no outline */

    transition: transform .22s ease, background .22s ease;
  }

  .back-button i{
    font-size: 18px;
    line-height: 1;
    transform: translateX(0);
    transition: transform .22s ease;
  }

  .back-button .label{
    line-height: 1;
    white-space: nowrap;
  }

  .back-button:hover{
    transform: translateY(-2px) scale(1.03);
    box-shadow: var(--back-shadow-hover);
    filter: saturate(1.03);
  }
  .back-button:hover .icon-wrap{
    transform: scale(1.04);
    background: rgba(255,255,255,.20);
  }
  .back-button:hover i{ transform: translateX(-2px); }

  .back-button:active{
    transform: translateY(0) scale(.98);
    box-shadow: 0 10px 24px rgba(0,0,0,.16);
    background: linear-gradient(180deg, var(--back-accent-darker), var(--back-accent-dark));
  }

  .back-button:focus-visible{
    outline: none;
    box-shadow: 0 0 0 4px var(--back-soft), var(--back-shadow);
  }

  @media (max-width: 576px){
    .back-button{
      top: 78px;
      left: 12px;
      padding: 10px 12px;
    }
    .back-button .label{ display:none; }
  }

  @media (max-width: 992px){
    .back-button{
      top: 86px;
      padding: 9px 13px;
      font-size: .92rem;
    }
    .back-button .icon-wrap{ width: 32px; height: 32px; border-radius: 11px; }
    .back-button i{ font-size: 17px; }
  }

  @media (prefers-reduced-motion: reduce){
    .back-button, .back-button *{ transition:none !important; }
  }
</style>

<button class="back-button" type="button" onclick="goBack()" aria-label="Go back">
  <span class="icon-wrap" aria-hidden="true">
    <i class="fas fa-arrow-left"></i>
  </span>
  <span class="label">Back</span>
</button>

<script>
(function () {
  const HOMEPAGE_URL = "/home"; // Laravel home route
  const MAX_STACK = 20;

  // Use FULL normalized path (no query params)
  const currentPath = window.location.pathname.replace(/\/+$/, "");

  // Load history
  let historyStack = [];
  try { historyStack = JSON.parse(sessionStorage.getItem("pageHistory") || "[]"); }
  catch { historyStack = []; }

  // Push current path if new
  if (currentPath && historyStack[historyStack.length - 1] !== currentPath) {
    historyStack.push(currentPath);
    if (historyStack.length > MAX_STACK) historyStack.shift();
    sessionStorage.setItem("pageHistory", JSON.stringify(historyStack));
  }

  // Detect ABAB or AAA loop
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

  function safeHome() {
    sessionStorage.removeItem("pageHistory");
    window.location.href = HOMEPAGE_URL;
  }

  window.goBack = function () {
    if (isLoopDetected()) return safeHome();

    if (window.history.length > 1) {
      const before = currentPath;

      window.history.back();

      setTimeout(() => {
        const now = window.location.pathname.replace(/\/+$/, "");
        if (now === before) safeHome();
      }, 200);

      return;
    }

    safeHome();
  };
})();
</script>
