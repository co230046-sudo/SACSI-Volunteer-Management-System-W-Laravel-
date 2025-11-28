/**
 * Volunteer list client: fetches /volunteers/data and renders cards client-side.
 */

const arrowUp   = document.getElementById('arrow-up');
const arrowDown = document.getElementById('arrow-down');
const cardsGrid = document.getElementById('cards-grid');
const gridCount = document.getElementById('grid-count');

let currentPage = 1;
let lastPage    = 1;
const perPage   = 9;

/* =====================================================================
   IMPORTANT: ALWAYS RESET PARAMS 
   (Old merging behavior is what broke BSIT & name searches)
===================================================================== */
let currentParams = {
    page: 1,
    per_page: perPage
};

/* =====================================================================
   URL BUILDER  
===================================================================== */
function buildUrl(params) {
    const url = new URL(window.location.origin + '/volunteers/data');
    for (const [k, v] of Object.entries(params)) {
        if (v !== undefined && v !== null && v !== "") {
            url.searchParams.set(k, v);
        }
    }
    return url.toString();
}

/* =====================================================================
   DEFAULT AVATAR
===================================================================== */
const DEFAULT_AVATAR = '/storage/defaults/default_user.png';

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/[&<>\"']/g, c => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;',
        '"': '&quot;', "'": '&#39;'
    })[c]);
}

/*
 * Abbreviate course names to uppercase initials while preserving
 * already-uppercase words (e.g. "BS Information Technology" -> "BSIT").
 */
function abbreviateCourse(name) {
    if (!name) return '—';
    // stop processing when the whole word 'major' appears (case-insensitive)
    const stopPattern = /\bmajor\b/i;
    let parts = name.split(/\s+/).filter(Boolean);

    // if 'major' appears, discard it and everything after it
    const stopIndex = parts.findIndex(word => stopPattern.test(word));
    if (stopIndex !== -1) parts = parts.slice(0, stopIndex);

    // ignore common stopwords (e.g. 'of') so they don't contribute letters
    const stopWords = new Set(['of']);
    parts = parts.filter(word => !stopWords.has(word.toLowerCase()));

    const abbr = parts
        .map(word => (word === word.toUpperCase() ? word : word[0].toUpperCase()))
        .join('');

    return abbr || '—';
}

/* =====================================================================
   CARD RENDERING
===================================================================== */
function renderCard(v) {
    const avatar = v.avatar_url || DEFAULT_AVATAR;
    const id = encodeURIComponent(v.volunteer_id);

    const a = document.createElement('a');
    a.className = 'student-card';
    a.href = `/volunteer-profile/${id}`;

    a.innerHTML = `
        <img src="${avatar}" 
            alt="${escapeHtml(v.full_name)}"
            class="avatar"
            onerror="this.onerror=null;this.src='${DEFAULT_AVATAR}'" />

        <div class="meta">
            <div class="name">${escapeHtml(v.full_name)}</div>

            
                <div class="badge">
                    <i class="fa-solid fa-graduation-cap"></i>
                    ${escapeHtml(abbreviateCourse(v.course?.course_name))}
                </div>
                <div class="badge">
                    <i class="fa-solid fa-layer-group"></i>
                    ${v.year_level ? v.year_level + " Year" : "—"}
                </div>
                <div class="badge">
                    <i class="fa-solid fa-location-dot"></i>
                    ${escapeHtml(v.barangay || "—")}
                </div>
            
        </div>
    `;

    return a;
}

/* =====================================================================
   GRID / OUTER CARD SIZE ADJUSTMENTS
   - Calculate number of rows from rendered cards and current columns
   - Measure one card height and the grid gap to compute required min-height
   - Keep outer card tall enough to contain cards + navigation
===================================================================== */

function getGridColumns() {
    const w = window.innerWidth;
    if (w >= 992) return 3;
    if (w >= 768) return 2;
    return 1;
}

function debounce(fn, wait = 100) {
    let t;
    return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn.apply(this, args), wait);
    };
}

function adjustOuterCardHeightPrecise() {
    const outer = document.querySelector('.outer-card');
    const cardsGrid = document.getElementById('cards-grid');
    const nav = document.querySelector('.navigation');
    if (!outer || !cardsGrid) return;

    const cards = cardsGrid.children.length;
    if (!cards) {
        outer.style.minHeight = '';
        return;
    }

    const columns = getGridColumns();
    const rows = Math.ceil(cards / columns);

    const firstCard = cardsGrid.querySelector('.student-card');
    const cardHeight = firstCard ? firstCard.offsetHeight : 220; // fallback

    const gridStyles = getComputedStyle(cardsGrid);
    const gap = parseInt(gridStyles.rowGap || gridStyles.gap || 22, 10) || 22;
    const paddingTop = parseInt(gridStyles.paddingTop || 0, 10) || 0;
    const paddingBottom = parseInt(gridStyles.paddingBottom || 0, 10) || 0;

    const navHeight = nav ? nav.offsetHeight : 0;

    // outer padding (1rem) and a small buffer
    const outerPadding = 16 * 2; // top + bottom approx
    const buffer = 24;

    const total = rows * cardHeight + (rows - 1) * gap + paddingTop + paddingBottom + navHeight + outerPadding + buffer;

    outer.style.minHeight = total + 'px';
}

// Recalculate after resize and initial render
window.addEventListener('resize', debounce(adjustOuterCardHeightPrecise, 120));
document.addEventListener('DOMContentLoaded', () => {
    // initial attempt; actual adjustment will run after fetchPage populates
    adjustOuterCardHeightPrecise();
});

/* =====================================================================
   FETCH PAGE
===================================================================== */
async function fetchPage(params = {}) {

    // 🚨 THIS FIXES YOUR SEARCH ISSUE:
    // Completely rebuild param state every time.
    currentParams = {
        page: params.page ?? 1,
        per_page: perPage,
        search: params.search ?? "",
        sort: params.sort ?? "",
        course_id: params.course_id ?? "",
        barangay: params.barangay ?? "",
        district: params.district ?? "",
        year_level: params.year_level ?? "",
        day: params.day ?? "",
        schedule_day: params.schedule_day ?? ""
    };

    const url = buildUrl(currentParams);

    try {
        const res = await fetch(url, {
            headers: { 'Accept': 'application/json' }
        });

        if (!res.ok) throw new Error("API error");

        const json = await res.json();

        cardsGrid.innerHTML = "";
        json.data.forEach(v => cardsGrid.appendChild(renderCard(v)));

        // Ensure outer card fits rendered cards and navigation
        adjustOuterCardHeightPrecise();
        // run again shortly after layout settles (images, fonts)
        setTimeout(adjustOuterCardHeightPrecise, 160);

        gridCount.textContent = `${json.total} students`;

        currentPage = json.current_page || 1;
        lastPage    = json.last_page || 1;

        const nav = document.querySelector(".navigation");
        if (nav) nav.style.display = lastPage > 1 ? "flex" : "none";

        arrowUp.classList.toggle("disabled", currentPage <= 1);
        arrowDown.classList.toggle("disabled", currentPage >= lastPage);

    } catch (err) {
        console.error("❌ Fetch error:", err);
    }
}

/* =====================================================================
   PAGINATION
===================================================================== */
arrowUp.addEventListener('click', e => {
    e.preventDefault();
    if (currentPage > 1) fetchPage({ ...currentParams, page: currentPage - 1 });
});

arrowDown.addEventListener('click', e => {
    e.preventDefault();
    if (currentPage < lastPage) fetchPage({ ...currentParams, page: currentPage + 1 });
});

/* =====================================================================
   INITIAL LOAD
===================================================================== */
document.addEventListener("DOMContentLoaded", () => {
    // Enable compact-list layout by default
    const gridContainer = document.querySelector('.grid-container');
    if (gridContainer) gridContainer.classList.add('compact-list');

    fetchPage({ page: 1 });
});
