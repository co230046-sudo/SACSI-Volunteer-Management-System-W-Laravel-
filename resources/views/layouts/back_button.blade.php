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
✅ BACK BUTTON SCRIPT (STACK + HOMEPAGE FALLBACK)
================================ -->
<script>
(function () {
  // 👉 Adjust if you prefer: const HOME_URL = "{{ route('home') }}";
  const HOME_URL = "/home";
  const STORAGE_KEY = "pageHistory_v3";
  const MAX_STACK = 20;

  function normalize(urlOrPath) {
    if (!urlOrPath) return null;

    try {
      const u = new URL(urlOrPath, window.location.origin);
      let path = u.pathname;
      path = path.replace(/\/+$/, "") || "/";
      return path;
    } catch (e) {
      // Fallback for simple paths like "/home"
      let path = ("" + urlOrPath).split("#")[0].split("?")[0];
      if (!path.startsWith("/")) path = "/" + path;
      path = path.replace(/\/+$/, "") || "/";
      return path;
    }
  }

  const HOME_PATH = normalize(HOME_URL);

  function loadStack() {
    try {
      const raw = sessionStorage.getItem(STORAGE_KEY);
      if (!raw) return [];
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      return [];
    }
  }

  function saveStack(stack) {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(stack));
    } catch (e) {
      // ignore storage errors
    }
  }

  const currentPath = normalize(window.location.href);
  let historyStack = loadStack();

  // ================================
  // 🔄 BUILD / UPDATE STACK ON LOAD
  // ================================
  if (currentPath === HOME_PATH) {
    // Visiting home ALWAYS resets history:
    // older pages like /volunteer-import are forgotten.
    historyStack = [HOME_PATH];
    saveStack(historyStack);
  } else {
    if (!historyStack.length) {
      // First non-home page in this tab:
      // treat /home as logical root then this page.
      historyStack = [HOME_PATH, currentPath];
    } else {
      const last = normalize(historyStack[historyStack.length - 1]);
      if (last !== currentPath) {
        historyStack.push(currentPath);
        if (historyStack.length > MAX_STACK) {
          historyStack = historyStack.slice(-MAX_STACK);
        }
      }
    }
    saveStack(historyStack);
  }

  // ================================
  // ⬅️ BACK BUTTON HANDLER
  // ================================
  window.goBack = function () {
    let stack = loadStack();
    const cur = normalize(window.location.href);

    // Nothing stored? Go straight to home.
    if (!stack || !stack.length) {
      saveStack([HOME_PATH]);
      window.location.href = HOME_URL;
      return;
    }

    // Drop any trailing occurrences of the current page
    // (guards against weird duplicates).
    while (stack.length && normalize(stack[stack.length - 1]) === cur) {
      stack.pop();
    }

    // If there's nothing (or only home) behind us, go to home and stop.
    if (!stack.length || stack.length === 1) {
      saveStack([HOME_PATH]);
      window.location.href = HOME_URL;
      return;
    }

    // Previous logical page is now the last element.
    const targetPath = stack[stack.length - 1];

    // Keep the truncated stack (current page already removed).
    saveStack(stack);

    // Navigate to previous logical page.
    window.location.href = targetPath;
  };
})();
</script>
