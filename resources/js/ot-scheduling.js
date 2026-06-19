/**
 * OT Scheduling — Conflict Detection
 *
 * Checks for double-booking when the user selects a theatre + date + time.
 * Shows a warning banner if there are overlapping surgeries in that slot.
 */

document.addEventListener('DOMContentLoaded', function () {
    const theatreSelect = document.querySelector('[name="operation_theatre_id"]');
    const dateInput = document.querySelector('[name="scheduled_date"]');
    const startTimeInput = document.querySelector('[name="scheduled_start_time"]');
    const endTimeInput = document.querySelector('[name="scheduled_end_time"]');
    const form = document.querySelector('form');

    if (!theatreSelect || !dateInput || !startTimeInput || !endTimeInput) return;

    // Get conflict check URL and surgery ID (for edit exclusion) from data attributes
    const conflictUrl = form.dataset.conflictUrl || '';
    const excludeSurgeryId = form.dataset.surgeryId || '';

    // Conflict banner container — inject after the Schedule card
    let conflictBanner = document.getElementById('ot-conflict-banner');
    if (!conflictBanner) {
        conflictBanner = document.createElement('div');
        conflictBanner.id = 'ot-conflict-banner';
        conflictBanner.className = 'hidden mb-4';
        // Insert before the Schedule card's parent or after it
        const scheduleCard = theatreSelect.closest('.bg-white');
        if (scheduleCard && scheduleCard.parentNode) {
            scheduleCard.parentNode.insertBefore(conflictBanner, scheduleCard.nextSibling);
        }
    }

    function checkConflicts() {
        const theatreId = theatreSelect.value;
        const date = dateInput.value;
        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;

        // Only check when theatre, date, and at least start time are provided
        if (!theatreId || !date || !startTime) {
            hideBanner();
            return;
        }

        const params = new URLSearchParams({
            operation_theatre_id: theatreId,
            scheduled_date: date,
            scheduled_start_time: startTime,
        });
        if (endTime) params.append('scheduled_end_time', endTime);
        if (excludeSurgeryId) params.append('exclude_surgery_id', excludeSurgeryId);

        fetch(conflictUrl + '?' + params.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.conflicts && data.conflicts.length > 0) {
                showBanner(data.conflicts);
            } else {
                hideBanner();
            }
        })
        .catch(() => {
            hideBanner();
        });
    }

    function showBanner(conflicts) {
        let html = '<div class="bg-yellow-50 border border-yellow-300 text-yellow-800 px-4 py-3 rounded-lg">';
        html += '<div class="flex items-start"><i class="fas fa-exclamation-triangle mr-2 mt-0.5"></i>';
        html += '<div><p class="font-medium text-sm">Scheduling Conflict Detected</p>';
        html += '<ul class="mt-1 text-xs space-y-1">';
        conflicts.forEach(function (c) {
            html += '<li>• <strong>' + escapeHtml(c.procedure_name) + '</strong> — ';
            html += escapeHtml(c.time_range) + ' (Patient: ' + escapeHtml(c.patient_name) + ')</li>';
        });
        html += '</ul>';
        html += '<p class="text-xs mt-2 text-yellow-700">You can still save, but the theatre will be double-booked.</p>';
        html += '</div></div></div>';

        conflictBanner.innerHTML = html;
        conflictBanner.classList.remove('hidden');
    }

    function hideBanner() {
        conflictBanner.innerHTML = '';
        conflictBanner.classList.add('hidden');
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }

    // Team row management (moved from inline scripts)
    window.otTeamIndex = parseInt(form.dataset.teamIndex || '0', 10);

    window.addTeamRow = function () {
        const container = document.getElementById('teamRows');
        const template = document.getElementById('userOptionsTemplate');
        const userOptions = template ? template.innerHTML : '<option value="">Select Team Member</option>';
        const row = document.createElement('div');
        row.className = 'grid grid-cols-12 gap-3 team-row';
        row.innerHTML =
            '<div class="col-span-6"><select name="team[' + window.otTeamIndex + '][user_id]" class="w-full border-gray-300 rounded-lg text-sm">' + userOptions + '</select></div>' +
            '<div class="col-span-4"><select name="team[' + window.otTeamIndex + '][role]" class="w-full border-gray-300 rounded-lg text-sm">' +
            '<option value="assistant_surgeon">Assistant Surgeon</option>' +
            '<option value="anesthetist">Anesthetist</option>' +
            '<option value="nurse">Nurse</option>' +
            '<option value="technician">Technician</option>' +
            '</select></div>' +
            '<div class="col-span-2 flex items-center"><button type="button" onclick="removeTeamRow(this)" class="text-red-400 hover:text-red-600 text-sm"><i class="fas fa-times"></i></button></div>';
        container.appendChild(row);
        window.otTeamIndex++;
    };

    window.removeTeamRow = function (btn) {
        btn.closest('.team-row').remove();
    };

    // Debounce helper
    let debounceTimer;
    function debounce(fn, delay) {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fn, delay);
    }

    // Listen for changes on relevant fields
    [theatreSelect, dateInput, startTimeInput, endTimeInput].forEach(function (el) {
        el.addEventListener('change', function () {
            debounce(checkConflicts, 300);
        });
    });

    // Initial check (for edit form with pre-filled values)
    if (theatreSelect.value && dateInput.value && startTimeInput.value) {
        debounce(checkConflicts, 500);
    }
});
