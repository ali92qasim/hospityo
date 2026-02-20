// Import Flatpickr
import flatpickr from 'flatpickr';

// Initialize Flatpickr on date inputs
document.addEventListener('DOMContentLoaded', function() {
    
    const startDateInput = document.querySelector('input[name="start_date"]');
    const endDateInput = document.querySelector('input[name="end_date"]');
    
    if (startDateInput) {
        try {
            flatpickr(startDateInput, {
                dateFormat: "Y-m-d",
                maxDate: "today",
                allowInput: true,
                onChange: function(selectedDates, dateStr, instance) {
                    // Update end date minDate when start date changes
                    if (endDateInput && endDateInput._flatpickr) {
                        endDateInput._flatpickr.set('minDate', dateStr);
                    }
                }
            });
        } catch (error) {
            console.error('Flatpickr start_date error:', error);
        }
    }
    
    if (endDateInput) {
        try {
            flatpickr(endDateInput, {
                dateFormat: "Y-m-d",
                maxDate: "today",
                allowInput: true,
                minDate: startDateInput?.value || null
            });
        } catch (error) {
            console.error('Flatpickr end_date error:', error);
        }
    }
});
