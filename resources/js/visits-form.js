// Import styles
import '../css/visits-form.css';

// Import Flatpickr
import flatpickr from 'flatpickr';

console.log('Visits form module loaded');

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM ready, initializing Flatpickr...');
    
    // Initialize Flatpickr for visit datetime
    const visitDatetimeInput = document.querySelector('input[name="visit_datetime"]');
    console.log('Visit datetime input found:', visitDatetimeInput);
    
    if (visitDatetimeInput) {
        try {
            flatpickr(visitDatetimeInput, {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                minuteIncrement: 15,
                defaultDate: visitDatetimeInput.value || new Date(),
                allowInput: true
            });
            console.log('✓ Flatpickr initialized on visit_datetime');
        } catch (error) {
            console.error('Flatpickr visit_datetime error:', error);
        }
    } else {
        console.warn('Visit datetime input not found in DOM');
    }

    // Initialize Flatpickr for discharge datetime (only on edit page)
    const dischargeDatetimeInput = document.querySelector('input[name="discharge_datetime"]');
    console.log('Discharge datetime input found:', dischargeDatetimeInput);
    
    if (dischargeDatetimeInput) {
        try {
            flatpickr(dischargeDatetimeInput, {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                minuteIncrement: 15,
                defaultDate: dischargeDatetimeInput.value || null,
                allowInput: true
            });
            console.log('✓ Flatpickr initialized on discharge_datetime');
        } catch (error) {
            console.error('Flatpickr discharge_datetime error:', error);
        }
    }
});
