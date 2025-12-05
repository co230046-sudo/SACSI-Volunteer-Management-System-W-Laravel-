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
  z-index: 200;

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

.side-nav:not(.collapsed) .toggle-btn i{
  transform: rotate(180deg);
}

/* ===== Links ===== */
.nav-links{
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 4px 2px;
}

/* per-link theme (default) */
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

  transition: background .2s ease, color .2s ease, transform .2s ease, border-color .2s ease;
}

.nav-links a i{
  font-size: 22px;
  color: var(--nav-muted);
  width: 30px;
  text-align: center;
  transition: color .2s ease, transform .2s ease;
}

/* ✅ Colors */
.nav-links a.nav-danger{
  --accent: #e6202e;
  --accent-soft: rgba(230, 32, 46, 0.12);
  --accent-border: rgba(230, 32, 46, 0.28);
}
.nav-links a.nav-success{
  --accent: #16a34a;
  --accent-soft: rgba(22, 163, 74, 0.12);
  --accent-border: rgba(22, 163, 74, 0.28);
}
.nav-links a.nav-info{
  --accent: #2563eb;
  --accent-soft: rgba(37, 99, 235, 0.12);
  --accent-border: rgba(37, 99, 235, 0.28);
}

.nav-links a:hover{
  background: var(--accent-soft);
  color: var(--accent);
  transform: translateX(2px);
}
.nav-links a:hover i{
  color: var(--accent);
  transform: scale(1.06);
}

/* ===== ACTIVE ===== */
.nav-links a.active-link{
  background: color-mix(in srgb, var(--accent) 14%, white);
  color: var(--accent);
  border: 1px solid var(--accent-border);
}
.nav-links a.active-link i{ color: var(--accent); }

/* left accent bar */
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

/* ===== Tooltip (bigger) ===== */
.nav-links a::after{
  content: attr(data-tooltip);
  position: absolute;
  left: calc(100% + 12px);
  top: 50%;
  transform: translateY(-50%) translateX(6px);
  opacity: 0;
  pointer-events: none;

  padding: 12px 14px;
  border-radius: 12px;
  color: #fff;
  background: rgba(25,25,25,0.95);
  box-shadow: 0 10px 28px rgba(0,0,0,0.22);

  font-size: 13.5px;
  line-height: 1.35;

  width: max-content;
  max-width: 380px;
  min-width: 240px;
  white-space: normal;
  word-break: break-word;

  transition: opacity .2s ease, transform .2s ease;
  z-index: 999;
}
.nav-links a:hover::after{
  opacity: 1;
  transform: translateY(-50%) translateX(12px);
}

/* ✅ if you have sticky header, adjust this value */
#import-Section-invalid,
#import-Section-valid,
#importlog-Section{
  scroll-margin-top: 90px;
}

html { scroll-behavior: smooth; } /* works when window scrolls */

/* if you have a scrollable wrapper, add scroll-behavior there too */
.database-container,
.data-table-container,
.table-controls,
main,
body {
  scroll-behavior: smooth;
}

/* ===== Mobile tweaks ===== */
@media (max-width: 576px){
  .side-nav{ top: auto; bottom: 16px; transform: none; border-radius: 16px; left: 12px; }
  .side-nav.collapsed{ width: 64px; }
  .nav-links a::after{ max-width: 320px; min-width: 200px; }
}
</style>

<nav class="side-nav collapsed" id="sideNav" aria-label="Quick Navigation">
  <button class="toggle-btn" id="toggleNav" type="button" aria-expanded="false">
    <i id="toggleIcon" class="fas fa-chevron-right"></i>
  </button>

  <div class="nav-links">
    <a class="nav-danger" href="#import-Section-invalid"
       data-tooltip="Upload CSV files and review invalid entries for correction.">
      <i class="fas fa-tasks"></i>
      <span>Invalid Entries</span>
    </a>

    <a class="nav-success" href="#import-Section-valid"
       data-tooltip="View verified volunteer entries successfully validated after import.">
      <i class="fas fa-user-check"></i>
      <span>Verified Entries</span>
    </a>

    <a class="nav-info" href="#importlog-Section"
       data-tooltip="Access import logs with timestamps and uploader details.">
      <i class="fas fa-history"></i>
      <span>Import Logs</span>
    </a>
  </div>
</nav>

<script>
(function(){
  const sideNav   = document.getElementById("sideNav");
  const toggleBtn = document.getElementById("toggleNav");
  const links     = Array.from(document.querySelectorAll("#sideNav .nav-links a"));

  if (!sideNav || !toggleBtn || !links.length) return;

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

  // ---- helpers ----
  function findScrollParent(el){
    let p = el.parentElement;
    while (p && p !== document.body) {
      const s = getComputedStyle(p);
      const canScrollY = (s.overflowY === "auto" || s.overflowY === "scroll") && p.scrollHeight > p.clientHeight;
      if (canScrollY) return p;
      p = p.parentElement;
    }
    return window; // page scroll
  }

  function getHeaderOffset(){
    // ✅ change selector if your header is different
    const header = document.querySelector(".navbar, .sticky-top, header");
    if (!header) return 0;
    const s = getComputedStyle(header);
    const isSticky = ["fixed","sticky"].includes(s.position);
    return isSticky ? header.getBoundingClientRect().height : 0;
  }

  function smoothScrollToSection(target){
    const offset = getHeaderOffset() + 12; // extra breathing space
    const scrollParent = findScrollParent(target);

    if (scrollParent === window) {
      const y = window.scrollY + target.getBoundingClientRect().top - offset;
      window.scrollTo({ top: y, behavior: "smooth" });
      return;
    }

    // scrollable container
    const parentRect = scrollParent.getBoundingClientRect();
    const targetRect = target.getBoundingClientRect();
    const y = scrollParent.scrollTop + (targetRect.top - parentRect.top) - offset;

    scrollParent.scrollTo({ top: y, behavior: "smooth" });
  }

  // Smooth scroll + active link
  links.forEach(a => {
    a.addEventListener("click", (e) => {
      const href = a.getAttribute("href");
      if (!href || !href.startsWith("#")) return;

      const target = document.querySelector(href);
      if (!target) return;

      e.preventDefault();
      history.pushState(null, "", href);

      smoothScrollToSection(target);

      links.forEach(l => l.classList.remove("active-link"));
      a.classList.add("active-link");
    });
  });

})();
</script>
