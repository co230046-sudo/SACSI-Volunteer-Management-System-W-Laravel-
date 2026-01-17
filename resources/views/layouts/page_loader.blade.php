<!-- ✅ PAGE LOADER (PLACE THIS AS THE FIRST ELEMENT INSIDE <body>) -->
<div id="page-loader" aria-hidden="true">
    <div class="spinner"></div>
    <div class="loader-text">Loading...</div>
</div>

<style>
    #page-loader {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;

        background: #ffffff;

        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        gap: 10px;

        z-index: 9999;

        opacity: 1;
        pointer-events: all;

        transition: opacity 0.25s ease;
    }

    #page-loader.hidden {
        opacity: 0;
        pointer-events: none;
    }

    #page-loader .spinner {
        border: 6px solid #f3f3f3;
        border-top: 6px solid #B2000C;
        border-radius: 50%;

        width: 55px;
        height: 55px;

        animation: spin 1s linear infinite;
    }

    #page-loader .loader-text {
        font-size: 0.95rem;
        font-weight: 700;
        color: #444;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* ✅ OPTIONAL: nice fade-in for sections */
    section {
        opacity: 0;
        transition: opacity 0.5s ease;
    }

    section.visible {
        opacity: 1;
    }
</style>

<script>
    (function () {
        const LOADER_ID = "page-loader";

        function getLoader() {
            return document.getElementById(LOADER_ID);
        }

        function showLoader(text = "Loading...") {
            const loader = getLoader();
            if (!loader) return;

            const textEl = loader.querySelector(".loader-text");
            if (textEl) textEl.textContent = text;

            loader.classList.remove("hidden");

            // ✅ force repaint (VERY IMPORTANT)
            void loader.offsetHeight;
        }

        function hideLoader() {
            const loader = getLoader();
            if (!loader) return;
            loader.classList.add("hidden");
        }

        // ✅ If the page is still loading, show loader immediately
        if (document.readyState !== "complete") {
            showLoader("Loading...");
        }

        // ✅ Hide loader after everything loads
        window.addEventListener("load", function () {
            hideLoader();

            // ✅ OPTIONAL: animate sections nicely after load
            document.querySelectorAll("section").forEach((sec, index) => {
                setTimeout(() => sec.classList.add("visible"), index * 150);
            });
        });

        // ✅ If browser restores page using back/forward cache, loader must hide
        window.addEventListener("pageshow", function (e) {
            if (e.persisted) {
                hideLoader();
            }
        });

        // ✅ Show loader on refresh / navigation / leaving page
        window.addEventListener("beforeunload", function () {
            showLoader("Loading...");
        });

        // ✅ Show loader on ALL form submits (preview import, validate/save, etc.)
        document.addEventListener("submit", function (e) {
            if (e.target && e.target.tagName === "FORM") {
                showLoader("Processing...");
            }
        }, true);

        // ✅ Show loader on link navigation
        document.addEventListener("click", function (e) {
            const a = e.target.closest("a");
            if (!a) return;

            const href = a.getAttribute("href") || "";

            // Ignore anchors / empty links / JS links
            if (href === "" || href.startsWith("#") || href.startsWith("javascript:")) return;

            // Ignore new tab
            if (a.target === "_blank" || e.ctrlKey || e.metaKey) return;

            showLoader("Loading...");
        }, true);

        // ✅ Optional manual calls if you want later:
        window.showPageLoader = showLoader;
        window.hidePageLoader = hideLoader;
    })();
</script>
