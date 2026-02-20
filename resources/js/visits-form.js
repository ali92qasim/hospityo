import $ from 'jquery';
import select2 from 'select2';
import flatpickr from 'flatpickr';

// Initialize Select2
select2(window, $);

$(function() {
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
            flatpickr('#next-visit-date', {
                dateFormat: 'Y-m-d',
                minDate: 'today',
                allowInput: true,
                altInput: true,
                altFormat: 'F j, Y',
                locale: {
                    firstDayOfWeek: 1
                }
            });
        } catch (error) {
            console.error('Error initializing next visit date Flatpickr:', error);
        }
    }
});
