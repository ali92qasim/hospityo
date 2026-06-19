/**
 * OT Surgery Show — Handles toggle of action forms (complete, cancel, postpone).
 */

document.addEventListener('DOMContentLoaded', function () {
    // Toggle helper
    function setupToggle(btnId, formId) {
        const btn = document.getElementById(btnId);
        const form = document.getElementById(formId);
        if (!btn || !form) return;

        btn.addEventListener('click', function () {
            form.classList.toggle('hidden');
            // Scroll into view when opening
            if (!form.classList.contains('hidden')) {
                form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }

    setupToggle('complete-btn', 'complete-form');
    setupToggle('cancel-btn', 'cancel-form');
    setupToggle('postpone-btn', 'postpone-form');
});
