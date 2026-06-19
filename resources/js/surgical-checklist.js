/**
 * Surgical Safety Checklist — Real-time toggle via AJAX.
 *
 * Handles checkbox toggling and phase completion confirmation.
 */

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('checklist-container');
    if (!container) return;

    const toggleUrlTemplate = container.dataset.toggleUrl;
    const completePhaseUrl = container.dataset.completePhaseUrl;
    const csrfToken = container.dataset.csrf;

    // Handle checkbox changes
    container.addEventListener('change', function (e) {
        if (!e.target.classList.contains('checklist-checkbox')) return;

        const checkbox = e.target;
        const itemId = checkbox.dataset.itemId;
        const isChecked = checkbox.checked;
        const url = toggleUrlTemplate.replace('__ITEM_ID__', itemId);

        // Optimistic UI — apply strikethrough immediately
        const label = checkbox.closest('.checklist-item');
        const textSpan = label ? label.querySelector('span.text-sm') : null;
        if (textSpan) {
            if (isChecked) {
                textSpan.classList.add('line-through', 'text-gray-400');
            } else {
                textSpan.classList.remove('line-through', 'text-gray-400');
            }
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ is_checked: isChecked, notes: null }),
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (!data.success) {
                // Revert on failure
                checkbox.checked = !isChecked;
                if (textSpan) {
                    textSpan.classList.toggle('line-through');
                    textSpan.classList.toggle('text-gray-400');
                }
            }
            // After toggling, re-count and show "Confirm Phase" button if needed
            updatePhaseProgress();
        })
        .catch(function () {
            checkbox.checked = !isChecked;
            if (textSpan) {
                textSpan.classList.toggle('line-through');
                textSpan.classList.toggle('text-gray-400');
            }
        });
    });

    // Handle "Confirm Phase" button clicks
    container.addEventListener('click', function (e) {
        const btn = e.target.closest('.complete-phase-btn');
        if (!btn) return;

        const phase = btn.dataset.phase;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Confirming...';

        fetch(completePhaseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ phase: phase }),
        })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data.success) {
                // Replace button with "Done" badge
                btn.outerHTML = '<span class="bg-green-100 text-green-700 px-2 py-0.5 text-xs rounded-full"><i class="fas fa-check mr-1"></i>Done</span>';
                // Disable all checkboxes in this phase
                var phaseEl = document.getElementById('phase-' + phase);
                if (phaseEl) {
                    phaseEl.querySelectorAll('.checklist-checkbox').forEach(function (cb) {
                        cb.disabled = true;
                    });
                }
                // Reload to show next phase button
                window.location.reload();
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-double mr-1"></i>Confirm Phase';
                alert(data.message || 'Failed to confirm phase.');
            }
        })
        .catch(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-double mr-1"></i>Confirm Phase';
        });
    });

    function updatePhaseProgress() {
        ['sign_in', 'time_out', 'sign_out'].forEach(function (phase) {
            var phaseEl = document.getElementById('phase-' + phase);
            if (!phaseEl) return;
            var checkboxes = phaseEl.querySelectorAll('.checklist-checkbox');
            var total = checkboxes.length;
            var checked = 0;
            checkboxes.forEach(function (cb) { if (cb.checked) checked++; });
            var progressEl = document.getElementById('progress-' + phase);
            if (progressEl) progressEl.textContent = checked + '/' + total;
        });
    }
});
