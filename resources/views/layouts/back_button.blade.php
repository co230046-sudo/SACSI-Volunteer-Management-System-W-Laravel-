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

  const HOME_URL = "{{ route('home') }}";
  const STORAGE_KEY = "pageHistory_v4";
  const MAX_STACK = 20;

  function normalize(url) {
    try {
      const u = new URL(url, window.location.origin);
      return u.pathname.replace(/\/+$/, "") || "/";
    } catch {
      let p = (""+url).split("#")[0].split("?")[0];
      if (!p.startsWith("/")) p = "/" + p;
      return p.replace(/\/+$/, "") || "/";
    }
  }

  const HOME_PATH = normalize(HOME_URL);

  function loadStack() {
    try {
      return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || [];
    } catch {
      return [];
    }
  }

  function saveStack(stack) {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(stack));
    } catch {}
  }

  const currentPath = normalize(window.location.href);
  let stack = loadStack();

  // ==========================
  // BUILD STACK SAFELY
  // ==========================
  if (currentPath === HOME_PATH) {
    stack = [HOME_PATH];
  } else {
    if (!stack.length) {
      stack = [HOME_PATH, currentPath];
    } else {
      const last = normalize(stack[stack.length - 1]);
      if (last !== currentPath) {
        stack.push(currentPath);
        if (stack.length > MAX_STACK) stack.shift();
      }
    }
  }

  saveStack(stack);

  // ==========================
  // BACK BUTTON FUNCTION
  // ==========================
  window.goBack = function () {

    let stack = loadStack();
    const cur = normalize(window.location.href);

    // Remove current page duplicates
    while (stack.length && normalize(stack[stack.length - 1]) === cur) {
      stack.pop();
    }

    // Nothing usable → home
    if (!stack.length) {
      saveStack([HOME_PATH]);
      window.location.href = HOME_URL;
      return;
    }

    const target = stack.pop();

    // If invalid → home
    if (!target || target === cur) {
      saveStack([HOME_PATH]);
      window.location.href = HOME_URL;
      return;
    }

    saveStack(stack);

    // Navigate
    window.location.href = target;
  };

})();
</script>
