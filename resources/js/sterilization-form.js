/**
 * Sterilization Form — Handles conditional field display and action toggles.
 */

document.addEventListener('DOMContentLoaded', function () {
    // ── Create form: show/hide fields based on target type ──
    var targetTypeSelect = document.getElementById('target-type');
    var theatreField = document.getElementById('theatre-field');
    var instrumentField = document.getElementById('instrument-field');
    var setNameField = document.getElementById('set-name-field');

    if (targetTypeSelect) {
        function toggleTargetFields() {
            var val = targetTypeSelect.value;
            if (theatreField) theatreField.classList.toggle('hidden', val !== 'theatre');
            if (instrumentField) instrumentField.classList.toggle('hidden', val !== 'individual_instrument');
            if (setNameField) setNameField.classList.toggle('hidden', val !== 'instrument_set');
        }
        targetTypeSelect.addEventListener('change', toggleTargetFields);
        toggleTargetFields(); // initial state
    }

    // ── Show page: toggle fail form ──
    var failBtn = document.getElementById('btn-fail-scheduled');
    var failForm = document.getElementById('form-fail-scheduled');
    if (failBtn && failForm) {
        failBtn.addEventListener('click', function () {
            failForm.classList.toggle('hidden');
        });
    }
});
