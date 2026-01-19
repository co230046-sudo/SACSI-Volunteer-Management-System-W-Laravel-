{{--
| Universal Feedback Modal (UFM) - OLD STYLE, no font icons
| Target path:
|   resources/views/layouts/modals/universal_feedback_modal.blade.php
| Works with JS:
|   public/assets/layouts/modals/universal_feedback_modal.js (your fixed2.js)
--}}

<div id="feedbackModal" class="ufm" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="ufm__panel" role="document" tabindex="-1">
        <div class="ufm__header">
            <div class="ufm__headLeft">
                <span class="ufm__icon" aria-hidden="true">
                    <span class="ufm__svg ufm__svg--success">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                    </span>
                    <span class="ufm__svg ufm__svg--error">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
                    </span>
                    <span class="ufm__svg ufm__svg--warning">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>
                    </span>
                    <span class="ufm__svg ufm__svg--info">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                    </span>
                </span>

                <div class="ufm__titles">
                    <div class="ufm__title" data-ufm-title>Notice</div>
                    <div class="ufm__subtitle" data-ufm-subtitle></div>
                </div>
            </div>

            <button type="button" class="ufm__x" data-ufm-close aria-label="Close">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"/><path d="M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="ufm__body" data-ufm-body></div>

        <div class="ufm__footer">
            <button type="button" class="btn btn-outline-secondary" data-ufm-close>Close</button>
        </div>
    </div>
</div>

