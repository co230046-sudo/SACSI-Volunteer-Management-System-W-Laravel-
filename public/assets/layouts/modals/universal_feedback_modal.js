/*!
 * Universal Feedback Modal (UFM)
 * - Bootstrap 5 compatible (custom modal markup)
 * - Supports: success | error | warning | info
 * - Safe UTF-8 base64 decoding for data-details payloads
 * ✅ PATCH: single-fire lock to prevent double/triple auto-open
 * ✅ PATCH: Details click handler is "hard to steal":
 *    - capture listeners on BOTH window + document
 *    - stopImmediatePropagation to beat legacy handlers
 *    - supports spans + anchors + legacy classes
 *    - supports data-details OR data-ufm-details OR hidden payload fallbacks
 */
(function () {
  "use strict";

  function qs(sel, root) { return (root || document).querySelector(sel); }

  // ✅ Proper UTF-8 base64 decode (fixes mojibake)
  function decodeBase64Utf8(b64) {
    try {
      const binStr = atob(String(b64).trim());
      const bytes = Uint8Array.from(binStr, c => c.charCodeAt(0));
      return new TextDecoder("utf-8").decode(bytes);
    } catch (e) {
      try { return atob(String(b64).trim()); } catch (_) { return String(b64 || ""); }
    }
  }

  function variantClass(variant) {
    switch (variant) {
      case "success": return "ufm--success";
      case "error":
      case "danger":  return "ufm--error";
      case "warning": return "ufm--warning";
      default:        return "ufm--info";
    }
  }

  function ensureModalExists() {
    return qs("#feedbackModal");
  }

  // ✅ GLOBAL SINGLE-FIRE LOCK (prevents multiple modals on same page load)
  if (!window.__UFM_STATE__) window.__UFM_STATE__ = {};
  if (typeof window.__UFM_STATE__.opened !== "boolean") window.__UFM_STATE__.opened = false;
  if (!window.__UFM_STATE__.openedBy) window.__UFM_STATE__.openedBy = null;

  /**
   * opts.force = true       -> bypass lock (rare)
   * opts.source             -> debugging tag
   * opts.userAction = true  -> bypass lock for clicks (details links)
   */
  function show(opts) {
    const el = ensureModalExists();
    if (!el) {
      console.error("[UFM] #feedbackModal not found. Did you @include('layouts.modals.universal_feedback_modal')?");
      return;
    }

    const variant    = String(opts?.variant || "info").toLowerCase();
    const title      = opts?.title ?? "Notice";
    const subtitle   = opts?.subtitle ?? "";
    const html       = opts?.html ?? "";
    const force      = !!opts?.force;
    const userAction = !!opts?.userAction;
    const source     = opts?.source || "unknown";

    // ✅ Block duplicate auto-fires (but allow user-initiated opens like "Details")
    if (!force && !userAction && window.__UFM_STATE__.opened) {
      // leave a breadcrumb
      console.warn("[UFM BLOCKED] Duplicate modal prevented:", {
        blockedSource: source,
        openedBy: window.__UFM_STATE__.openedBy
      });
      return;
    }

    // Lock only for non-userAction opens
    if (!userAction) {
      window.__UFM_STATE__.opened = true;
      window.__UFM_STATE__.openedBy = source;
    }

    el.classList.remove("ufm--success","ufm--error","ufm--warning","ufm--info","ufm--open");
    el.classList.add(variantClass(variant), "ufm--open");
    el.setAttribute("aria-hidden", "false");

    const $title    = qs("[data-ufm-title]", el);
    const $subtitle = qs("[data-ufm-subtitle]", el);
    const $body     = qs("[data-ufm-body]", el);

    if ($title)    $title.textContent = String(title);
    if ($subtitle) $subtitle.textContent = String(subtitle);
    if ($body)     $body.innerHTML = html;

    const closeBtn = qs("[data-ufm-close]", el);
    if (closeBtn) closeBtn.focus({ preventScroll: true });
  }

  function hide() {
    const el = ensureModalExists();
    if (!el) return;
    el.classList.remove("ufm--open");
    el.setAttribute("aria-hidden", "true");
  }

  function resetLock() {
    window.__UFM_STATE__.opened = false;
    window.__UFM_STATE__.openedBy = null;
  }

  // Close on backdrop click / X / Close button
  document.addEventListener("click", function (e) {
    const modal = ensureModalExists();
    if (!modal) return;

    if (e.target.closest("[data-ufm-close]")) {
      e.preventDefault();
      hide();
      return;
    }

    if (modal.classList.contains("ufm--open") && e.target === modal) {
      hide();
    }
  });

  // Esc closes
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") hide();
  });

  /* ===========================================================
     ✅ DETAILS / SEE MORE handler (hard to steal)
  ============================================================ */

  // Avoid double-binding if this JS is ever included twice
  if (!window.__UFM_DETAILS_BOUND__) {
    window.__UFM_DETAILS_BOUND__ = true;

    function readHiddenB64Fallback() {
      // These are optional; include whichever your project uses.
      const ids = [
        "__updateDetails_b64__",       // (recommended) from session('updateDetails')
        "__server_success_b64__",
        "__ev_success_b64__",
        "__last_success_b64__"
      ];
      for (const id of ids) {
        const el = document.getElementById(id);
        const val = (el?.textContent || el?.innerHTML || "").trim();
        if (val) return val;
      }
      return "";
    }

    function bindDetailsDelegation(root) {
      root.addEventListener("click", function (e) {
        const el = e.target.closest(
          "[data-details], [data-ufm-details], " +
          ".update-details-link, .success-details-link, .error-details-link, " +
          ".move-details-link, .deleted-details-link, .restored-details-link, " +
          ".reset-details-link, .show-modal-details, .see-more-link"
        );
        if (!el) return;

        // If it's a real anchor, prevent navigation
        if (el.tagName === "A") e.preventDefault();

        // Win the click war
        e.stopPropagation();
        e.stopImmediatePropagation();

        // Payload priority:
        // 1) explicit attr payload
        // 2) hidden session fallback (optional)
        let b64 = el.getAttribute("data-ufm-details") || el.getAttribute("data-details") || "";
        if (!b64) b64 = readHiddenB64Fallback();

        if (!b64) {
          show({
            variant: "warning",
            title: "Details unavailable",
            subtitle: "",
            html: "<div style='font-weight:700;'>No details payload found.</div>",
            userAction: true,
            source: "details_click_missing_payload"
          });
          return;
        }

        const decoded = decodeBase64Utf8(b64);

        let variant = "info";
        const cls = (el.className || "").toLowerCase();
        if (cls.includes("error") || cls.includes("danger")) variant = "error";
        else if (cls.includes("success") || cls.includes("update")) variant = "success";
        else if (cls.includes("warning")) variant = "warning";

        show({
          variant,
          title: "Details",
          subtitle: "",
          html: decoded,
          userAction: true,
          source: "details_click"
        });
      }, true); // capture
    }

    // ✅ bind on BOTH window and document (more resilient)
    bindDetailsDelegation(window);
    bindDetailsDelegation(document);
  }

  window.FeedbackModal = { show, hide, decodeBase64Utf8, resetLock };
})();
