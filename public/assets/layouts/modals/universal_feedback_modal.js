/*!
 * Universal Feedback Modal (UFM)
 * - Bootstrap 5 compatible
 * - Supports: success | error | warning | info
 * - Safe UTF-8 base64 decoding for data-details payloads
 */
(function () {
  "use strict";

  function qs(sel, root) { return (root || document).querySelector(sel); }

  // ✅ Proper UTF-8 base64 decode (fixes "âœ…" mojibake)
  function decodeBase64Utf8(b64) {
    try {
      const binStr = atob(String(b64).trim());
      const bytes = Uint8Array.from(binStr, c => c.charCodeAt(0));
      return new TextDecoder("utf-8").decode(bytes);
    } catch (e) {
      // last resort: return raw
      try { return atob(String(b64).trim()); } catch (_) { return String(b64); }
    }
  }

  function escText(s) {
    return String(s ?? "").replace(/[&<>"']/g, (c) => ({
      "&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"
    }[c]));
  }

  function variantClass(variant) {
    switch (variant) {
      case "success": return "ufm--success";
      case "error":   return "ufm--error";
      case "warning": return "ufm--warning";
      default:        return "ufm--info";
    }
  }

  function ensureModalExists() {
    return qs("#feedbackModal");
  }

  function show(opts) {
    const el = ensureModalExists();
    if (!el) {
      console.error("[UFM] #feedbackModal not found. Did you @include the blade?");
      return;
    }

    const variant  = (opts?.variant || "info").toLowerCase();
    const title    = opts?.title ?? "Notice";
    const subtitle = opts?.subtitle ?? "";
    const html     = opts?.html ?? "";

    el.classList.remove("ufm--success","ufm--error","ufm--warning","ufm--info","ufm--open");
    el.classList.add(variantClass(variant), "ufm--open");
    el.setAttribute("aria-hidden", "false");

    const $title    = qs("[data-ufm-title]", el);
    const $subtitle = qs("[data-ufm-subtitle]", el);
    const $body     = qs("[data-ufm-body]", el);

    if ($title)    $title.textContent = String(title);
    if ($subtitle) $subtitle.textContent = String(subtitle);
    if ($body)     $body.innerHTML = html; // html is from server OR your own templates

    // focus close button for accessibility
    const closeBtn = qs("[data-ufm-close]", el);
    if (closeBtn) closeBtn.focus({ preventScroll: true });
  }

  function hide() {
    const el = ensureModalExists();
    if (!el) return;
    el.classList.remove("ufm--open");
    el.setAttribute("aria-hidden", "true");
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

    // click on backdrop
    if (modal.classList.contains("ufm--open") && e.target === modal) {
      hide();
    }
  });

  // Esc closes
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") hide();
  });

  // ✅ Global delegation for any "Show Details" link using base64 payload
  // Works with: data-details="BASE64..." OR data-ufm-details="BASE64..."
  document.addEventListener("click", function (e) {
    const a = e.target.closest("[data-details], [data-ufm-details]");
    if (!a) return;

    // allow regular anchors without preventing
    if (a.tagName === "A") e.preventDefault();

    const b64 = a.getAttribute("data-ufm-details") || a.getAttribute("data-details");
    if (!b64) return;

    const decoded = decodeBase64Utf8(b64);

    // best-effort variant inference from class
    let variant = "info";
    const cls = a.className || "";
    if (cls.includes("error")) variant = "error";
    else if (cls.includes("success")) variant = "success";
    else if (cls.includes("warning")) variant = "warning";

    show({
      variant,
      title: "Details",
      subtitle: "",
      html: decoded
    });
  });

  window.FeedbackModal = { show, hide, decodeBase64Utf8 };
})();
