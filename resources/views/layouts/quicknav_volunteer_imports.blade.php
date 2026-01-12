{{-- quick_side_nav.blade.php (FULL FIX — reliable active section + visible active bubble + unique colors preserved + NO tooltip hover jitter) --}}

<style>
:root{
  --nav-text: #2f2f2f;
  --nav-muted: #817979;
  --nav-bg: #ffffff;
  --nav-shadow: 0 10px 30px rgba(0,0,0,0.12);
}

/* ===== Side Nav ===== */
.side-nav{
  position: fixed;
  top: 50%;
  left: 0;
  transform: translateY(-50%);
  z-index: 9000;

  width: 260px;
  border-radius: 0 16px 16px 0;
  background: var(--nav-bg);
  box-shadow: var(--nav-shadow);
  overflow: visible;

  display: flex;
  flex-direction: column;
  padding: 14px 10px 12px;
  gap: 10px;

  transition: width .25s ease, transform .25s ease, box-shadow .25s ease;
}
.side-nav.collapsed{
  width: 64px;
  padding: 12px 8px;
}
.side-nav:hover{ box-shadow: 0 14px 38px rgba(0,0,0,0.16); }

/* ===== Toggle Button ===== */
.toggle-btn{
  width: 44px;
  height: 44px;
  border-radius: 12px;
  border: 1px solid rgba(0,0,0,0.08);
  background: #fff;
  cursor: pointer;
  display: grid;
  place-items: center;
  margin: 2px auto 6px;
  transition: background .2s ease, transform .2s ease, border-color .2s ease;
}
.toggle-btn i{
  pointer-events: none !important;
  color: var(--nav-muted);
  font-size: 18px;
  transition: transform .25s ease, color .25s ease;
}
.side-nav:not(.collapsed) .toggle-btn i{ transform: rotate(180deg); }

/* ===== Links ===== */
.nav-links{
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 4px 2px;
}

.nav-links a{
  --accent: #e6202e;
  --accent-soft: rgba(230, 32, 46, 0.12);
  --accent-border: rgba(230, 32, 46, 0.25);

  position: relative;
  display: flex;
  align-items: center;
  gap: 12px;

  padding: 10px 10px;
  border-radius: 12px;

  color: var(--nav-text);
  text-decoration: none !important;
  font-size: 16px;
  font-weight: 600;

  transition: background .2s ease, color .2s ease, transform .2s ease, border-color .2s ease, box-shadow .2s ease;
}

.nav-links a i{
  font-size: 22px;
  width: 30px;
  text-align: center;

  color: var(--accent);
  opacity: .92;

  transition: color .2s ease, transform .2s ease, opacity .2s ease;
}

/* ✅ Unique colors */
.nav-links a.nav-danger{
  --accent: #e6202e;
  --accent-soft: rgba(230, 32, 46, 0.14);
  --accent-border: rgba(230, 32, 46, 0.34);
}
.nav-links a.nav-success{
  --accent: #16a34a;
  --accent-soft: rgba(22, 163, 74, 0.14);
  --accent-border: rgba(22, 163, 74, 0.34);
}
.nav-links a.nav-info{
  --accent: #2563eb;
  --accent-soft: rgba(37, 99, 235, 0.14);
  --accent-border: rgba(37, 99, 235, 0.34);
}

/* ✅ normal hover should NOT impersonate active */
.nav-links a:hover{
  background: transparent;
  transform: none;
  color: var(--nav-text);
}
.nav-links a:hover i{ opacity: 1; }

/* ===== ACTIVE (bubble look) ===== */
.nav-links a.active-link{
  background: var(--accent-soft) !important;
  color: var(--accent) !important;
  border: 1px solid var(--accent-border) !important;
  transform: translateX(2px) !important;
  box-shadow: 0 10px 26px rgba(0,0,0,0.10) !important;
}
.nav-links a.active-link i{
  color: var(--accent) !important;
  opacity: 1 !important;
  transform: scale(1.08) !important;
}

.nav-links a.active-link::before{
  content: "";
  position: absolute;
  left: -10px;
  top: 10px;
  bottom: 10px;
  width: 4px;
  border-radius: 999px;
  background: var(--accent);
}

/* ===== Collapsed ===== */
.side-nav.collapsed .nav-links a{
  justify-content: center;
  padding: 12px 8px;
}
.side-nav.collapsed .nav-links a span{ display:none; }
.side-nav.collapsed .nav-links a i{ width:auto; margin:0; }

.side-nav.collapsed .nav-links a.active-link{
  background: var(--accent-soft) !important;
  border: 1px solid var(--accent-border) !important;
  box-shadow: 0 10px 26px rgba(0,0,0,0.12) !important;
  transform: translateX(0) !important;
}

/* =========================================================
   ✅ Tooltip FIX: NO MOVEMENT on hover (prevents jitter)
   - We keep the bridge, but tooltip stays in one spot
========================================================= */

.nav-links a .tooltip-bridge{
  position: absolute;
  top: 50%;
  left: 100%;
  transform: translateY(-50%);
  height: 56px;
  width: 28px;
  background: transparent;
  opacity: 0;
  pointer-events: none;
  z-index: 999999;
}
.nav-links a:hover .tooltip-bridge,
.nav-links a:focus-visible .tooltip-bridge{
  pointer-events: auto;
}

/* tooltip base */
.nav-links a::after,
.nav-links a::before{
  opacity: 0;
  pointer-events: none;
  transition: opacity .12s ease; /* ✅ only fade, no transform animation */
  will-change: opacity;
}

/* bubble */
.nav-links a::after{
  content: attr(data-tooltip);
  position: absolute;
  left: calc(100% + 14px);
  top: 50%;
  transform: translateY(-50%); /* ✅ fixed position */

  padding: 12px 14px;
  border-radius: 12px;
  color: #fff;
  background: rgba(20,20,20,0.96);
  box-shadow: 0 12px 34px rgba(0,0,0,0.30);

  font-size: 13.5px;
  line-height: 1.35;

  width: max-content;
  max-width: 380px;
  min-width: 220px;
  white-space: normal;
  word-break: break-word;

  z-index: 1000000;
}

/* arrow */
.nav-links a::before{
  content: "";
  position: absolute;
  left: calc(100% + 6px);
  top: 50%;
  transform: translateY(-50%); /* ✅ fixed position */

  border: 8px solid transparent;
  border-right-color: rgba(20,20,20,0.96);

  z-index: 1000000;
}

/* show tooltip (no shift) */
.nav-links a:hover::after,
.nav-links a:hover::before,
.nav-links a:focus-visible::after,
.nav-links a:focus-visible::before{
  opacity: 1;
  pointer-events: auto;
}

/* section scroll margin */
#import-Section-invalid,
#import-Section-valid,
#importlog-Section{
  scroll-margin-top: 120px;
}

html { scroll-behavior: smooth; }

/* ===== Mobile ===== */
@media (max-width: 576px){
  .side-nav{ top: auto; bottom: 16px; transform: none; border-radius: 16px; left: 12px; }
  .side-nav.collapsed{ width: 64px; }
  .nav-links a::after{ max-width: 320px; min-width: 200px; }
}
</style>

<nav class="side-nav collapsed" id="sideNav" aria-label="Quick Navigation">
  <button class="toggle-btn" id="toggleNav" type="button" aria-expanded="false">
    <i class="fas fa-chevron-right"></i>
  </button>

  <div class="nav-links">
    <a class="nav-danger" href="#import-Section-invalid"
       data-tooltip="Upload CSV files and review invalid entries for correction.">
      <i class="fas fa-tasks"></i>
      <span>Invalid Entries</span>
      <span class="tooltip-bridge" aria-hidden="true"></span>
    </a>

    <a class="nav-success" href="#import-Section-valid"
       data-tooltip="View verified volunteer entries successfully validated after import.">
      <i class="fas fa-user-check"></i>
      <span>Verified Entries</span>
      <span class="tooltip-bridge" aria-hidden="true"></span>
    </a>

    <a class="nav-info" href="#importlog-Section"
       data-tooltip="Access import logs with timestamps and uploader details.">
      <i class="fas fa-history"></i>
      <span>Import Logs</span>
      <span class="tooltip-bridge" aria-hidden="true"></span>
    </a>
  </div>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const sideNav   = document.getElementById("sideNav");
  const toggleBtn = document.getElementById("toggleNav");
  const links     = Array.from(document.querySelectorAll("#sideNav .nav-links a"));
  if (!sideNav || !toggleBtn || links.length === 0) return;

  function setExpanded(expanded){
    sideNav.classList.toggle("collapsed", !expanded);
    toggleBtn.setAttribute("aria-expanded", String(expanded));
  }
  setExpanded(!sideNav.classList.contains("collapsed"));

  toggleBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    setExpanded(sideNav.classList.contains("collapsed"));
  });

  document.addEventListener("click", (e) => {
    if (!sideNav.contains(e.target)) setExpanded(false);
  });

  function getHeaderOffset(){
    const header = document.querySelector(".navbar, .sticky-top, header");
    if (!header) return 0;
    const s = getComputedStyle(header);
    const isSticky = ["fixed","sticky"].includes(s.position);
    return isSticky ? header.getBoundingClientRect().height : 0;
  }

  const scrollContainer = document.querySelector(".scroll-container");
  const scrollEl = scrollContainer || window;

  function setActive(href){
    links.forEach(l => l.classList.remove("active-link"));
    const a = links.find(l => l.getAttribute("href") === href);
    if (a) a.classList.add("active-link");
  }

  const items = links.map(a => {
    const href = a.getAttribute("href");
    if (!href || !href.startsWith("#")) return null;
    const el = document.querySelector(href);
    if (!el) return null;
    return { href, el };
  }).filter(Boolean);
  if (!items.length) return;

  let lastActive = null;

  function computeBestSection(){
    const header = getHeaderOffset();
    const line = header + 140;

    let best = null;
    let bestDist = Infinity;

    for (const it of items) {
      const r = it.el.getBoundingClientRect();
      const inPlay = (r.bottom > line) && (r.top < window.innerHeight * 0.92);
      if (!inPlay) continue;

      const dist = Math.abs(r.top - line);
      if (dist < bestDist) {
        bestDist = dist;
        best = it;
      }
    }

    if (!best) {
      let passed = null;
      for (const it of items) {
        const r = it.el.getBoundingClientRect();
        if (r.top <= line) passed = it;
      }
      best = passed || items[0];
    }

    if (best && best.href !== lastActive) {
      lastActive = best.href;
      setActive(best.href);
    }
  }

  let ticking = false;
  function onScroll(){
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      computeBestSection();
      ticking = false;
    });
  }

  scrollEl.addEventListener("scroll", onScroll, { passive: true });
  document.addEventListener("scroll", onScroll, { passive: true, capture: true });
  window.addEventListener("resize", onScroll);

  function smoothScrollTo(target){
    const offset = getHeaderOffset() + 12;

    if (!scrollContainer) {
      const y = window.scrollY + target.getBoundingClientRect().top - offset;
      window.scrollTo({ top: y, behavior: "smooth" });
      return;
    }

    const parentRect = scrollContainer.getBoundingClientRect();
    const targetRect = target.getBoundingClientRect();
    const y = scrollContainer.scrollTop + (targetRect.top - parentRect.top) - offset;
    scrollContainer.scrollTo({ top: y, behavior: "smooth" });
  }

  links.forEach(a => {
    a.addEventListener("click", (e) => {
      const href = a.getAttribute("href");
      if (!href || !href.startsWith("#")) return;

      const target = document.querySelector(href);
      if (!target) return;

      e.preventDefault();
      history.pushState(null, "", href);

      setActive(href);
      lastActive = href;

      smoothScrollTo(target);

      setTimeout(computeBestSection, 120);
      setTimeout(computeBestSection, 350);
    });
  });

  window.addEventListener("hashchange", () => {
    if (location.hash && document.querySelector(location.hash)) {
      setActive(location.hash);
      lastActive = location.hash;
      setTimeout(computeBestSection, 60);
    }
  });

  if (location.hash && document.querySelector(location.hash)) {
    setActive(location.hash);
    lastActive = location.hash;
  } else {
    computeBestSection();
  }

  setTimeout(computeBestSection, 80);
});
</script>
