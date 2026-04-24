import flatpickr from 'flatpickr';
import '../css/date-picker.css';

document.addEventListener('DOMContentLoaded', function () {
    // Convert all type="date" inputs to flatpickr
    document.querySelectorAll('input[type="date"]').forEach(function (input) {
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
});
