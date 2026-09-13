import $ from 'jquery';
import select2 from 'select2';
import flatpickr from 'flatpickr';
import { isDoctorListedForDatetime } from './appointments-validation.js';

// Initialize Select2
select2(window, $);

$(function() {
    // Initialize Select2 for patient dropdown on visit create page
    if ($('#patient_id').length && typeof $.fn.select2 !== 'undefined') {
        try {
            $('#patient_id').select2({
                placeholder: 'Search patient by name, number or phone...',
                allowClear: true,
                width: '100%',
            });
        } catch (error) {
            console.error('Error initializing patient Select2:', error);
        }
    }

    // Initialize Select2 for allergies dropdown
    if ($('#allergies-select').length && typeof $.fn.select2 !== 'undefined') {
        try {
            $('#allergies-select').select2({
                placeholder: 'Select or type allergies',
                allowClear: true,
                tags: true, // Allow custom entries
                width: '100%',
                tokenSeparators: [','],
                createTag: function (params) {
                    var term = params.term.trim(); // Use native trim instead of $.trim
                    
                    if (term === '') {
                        return null;
                    }
                    
                    // Check if the term already exists in the options
                    var exists = false;
                    var $select = $(this.$element);
                    $select.find('option').each(function() {
                        if ($(this).val().toLowerCase() === term.toLowerCase()) {
                            exists = true;
                            return false;
                        }
                    });
                    
                    if (exists) {
                        return null;
                    }
                    
                    return {
                        id: term,
                        text: term,
                        newTag: true
                    };
                },
                templateResult: function(data) {
                    if (data.loading) {
                        return data.text;
                    }
                    
                    if (data.newTag) {
                        return $('<span class="select2-new-tag"><i class="fas fa-plus-circle mr-2"></i>' + data.text + ' <span class="select2-new-tag-label">(Add New)</span></span>');
                    }
                    return data.text;
                },
                templateSelection: function(data) {
                    // Return just the text for selected items (no icon in chips)
                    return data.text;
                }
            });
            
            // Handle when a new tag is added
            $('#allergies-select').on('select2:select', function(e) {
                var data = e.params.data;
                
                // If it's a new tag, we'll let the backend handle saving it
                if (data.newTag) {
                    console.log('New allergy will be saved:', data.text);
                }
            });
            
        } catch (error) {
            console.error('Error initializing allergies Select2:', error);
        }
    }
    
    // Initialize Flatpickr for next visit date
    if ($('#next-visit-date').length) {
        try {
            const schedule = readAssignedDoctorSchedule();
            const nextVisitInput = document.getElementById('next-visit-date');
            const unavailableMessage = 'The selected doctor is not available on this day.';

            flatpickr('#next-visit-date', {
                dateFormat: 'Y-m-d',
                minDate: 'today',
                allowInput: true,
                altInput: true,
                altFormat: 'F j, Y',
                locale: {
                    firstDayOfWeek: 1
                },
                disable: schedule
                    ? [function (date) {
                        return !isDoctorListedForDatetime(schedule, localDateString(date));
                    }]
                    : [],
            });

            nextVisitInput?.form?.addEventListener('submit', function (event) {
                const value = String(nextVisitInput.value || '').trim();

                if (!value || !schedule) {
                    return;
                }

                if (!isDoctorListedForDatetime(schedule, value)) {
                    event.preventDefault();
                    showNextVisitDateError(unavailableMessage);
                }
            });
        } catch (error) {
            console.error('Error initializing next visit date Flatpickr:', error);
        }
    }
});

function readAssignedDoctorSchedule() {
    const el = document.querySelector('#assigned-doctor-schedule');

    if (!el) {
        return null;
    }

    try {
        const parsed = JSON.parse(el.textContent);

        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch {
        return null;
    }
}

function localDateString(date) {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

function showNextVisitDateError(message) {
    const slot = document.querySelector('[data-error-slot="next_visit_date"]');

    if (slot) {
        slot.textContent = message;
    }
}
