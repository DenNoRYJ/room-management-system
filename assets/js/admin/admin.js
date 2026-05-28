/**
 * ============================================================
 * TUPV RMS — Admin Panel Scripts
 * assets/js/admin/admin.js
 * ============================================================
 */

document.addEventListener("DOMContentLoaded", () => {
    
    /**
     * 1. Global Confirmation Dialogs
     * Intercepts clicks on any element with a 'data-confirm' attribute.
     * Prevents the default action if the user cancels the prompt.
     */
    document.body.addEventListener("click", (e) => {
        const confirmTarget = e.target.closest("[data-confirm]");
        
        if (confirmTarget) {
            const message = confirmTarget.getAttribute("data-confirm");
            if (!confirm(message)) {
                e.preventDefault();
            }
        }
    });

    /**
     * 2. Automated Form Submission
     * Automatically submits a form when an input/select with 
     * 'data-auto-submit' is changed. Useful for dropdown filters.
     */
    document.body.addEventListener("change", (e) => {
        if (e.target.closest("[data-auto-submit]")) {
            e.target.form.submit();
        }
    });
});