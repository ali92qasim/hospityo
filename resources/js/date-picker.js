import flatpickr from 'flatpickr';
import '../css/date-picker.css';

function initDatePickers(root = document) {
    // Convert all type="date" inputs to flatpickr
    root.querySelectorAll('input[type="date"]').forEach(function (input) {
        // Skip if already initialized
        if (input._flatpickr) return;

        // Change type to text so flatpickr takes over
        input.type = 'text';

        flatpickr(input, {
            dateFormat: 'Y-m-d',
            allowInput: true,
            defaultDate: input.value || null,
        });
    });
}

function initTimePickers(root = document) {
    // Time-only picker (Flatpickr) for HR shift fields, etc.
    root.querySelectorAll('input.js-time-picker').forEach(function (input) {
        // Skip if already initialized
        if (input._flatpickr) return;

        // Change type to text so flatpickr takes over (avoid native time UI inconsistencies)
        if (input.type === 'time') input.type = 'text';

        const defaultHour = input.dataset.defaultHour ? parseInt(input.dataset.defaultHour, 10) : undefined;
        const defaultMinute = input.dataset.defaultMinute ? parseInt(input.dataset.defaultMinute, 10) : undefined;

        flatpickr(input, {
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',
            time_24hr: true,
            minuteIncrement: 15,
            allowInput: true,
            defaultDate: input.value || null,
            ...(Number.isFinite(defaultHour) ? { defaultHour } : {}),
            ...(Number.isFinite(defaultMinute) ? { defaultMinute } : {}),
        });
    });
}

function initAll(root = document) {
    initDatePickers(root);
    initTimePickers(root);
}

// Run immediately (covers cases where DOMContentLoaded already fired)
initAll(document);

// Full page loads
document.addEventListener('DOMContentLoaded', () => initAll(document));

// SPA-like navigations (if present)
document.addEventListener('turbo:load', () => initAll(document));
document.addEventListener('livewire:navigated', () => initAll(document));
document.addEventListener('livewire:load', () => initAll(document));
