/**
 * Volunteer list client: fetches /volunteers/data and renders cards client-side.
 */

const arrowUp   = document.getElementById('arrow-up');
const arrowDown = document.getElementById('arrow-down');
const cardsGrid = document.getElementById('cards-grid');
const gridCount = document.getElementById('grid-count');

let currentPage = 1;
let lastPage    = 1;
const perPage   = 12;

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

            <div class="badge-grid">
                <div class="badge">
                    <i class="fa-solid fa-graduation-cap"></i>
                    ${escapeHtml(v.course?.course_name || "—")}
                </div>
                <div class="badge">
                    <i class="fa-solid fa-layer-group"></i>
                    ${v.year_level ? v.year_level + " Year" : "—"}
                </div>
                <div class="badge">
                    <i class="fa-solid fa-location-dot"></i>
                    ${escapeHtml(v.barangay || "—")}
                </div>
                <div class="badge">
                    <i class="fa-solid fa-map"></i>
                    District ${escapeHtml(v.district || "—")}
                </div>
            </div>
        </div>
    `;

    return a;
}

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
    fetchPage({ page: 1 });
});
