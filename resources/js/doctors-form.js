// Import styles
import '../css/doctors-form.css';

// Import jQuery first
import $ from 'jquery';

// Expose globally BEFORE plugins
window.$ = window.jQuery = $;

// Import Select2 properly
import select2 from 'select2';
select2(window, $);

// Import Flatpickr
import flatpickr from 'flatpickr';

// Centralized error handler
function handlePluginError(pluginName, error) {
    const message = `${pluginName} initialization failed.`;
    
    // Use jQuery error mechanism
    if ($ && $.error) {
        $.error(message);
    } else {
        throw new Error(`${message} ${error?.message || ''}`);
    }
}

// DOM Ready (jQuery way – best practice when using jQuery plugins)
$(function () {

    /* ==============================
       SELECT2 INITIALIZATION
    ============================== */

    const $departmentSelect = $('select[name="department_id"]');

    if ($departmentSelect.length) {

        // Ensure Select2 exists before using it
        if (typeof $.fn.select2 !== 'function') {
            handlePluginError('Select2', new Error('Select2 is not loaded properly.'));
            return;
        }

        $departmentSelect.select2({
            placeholder: 'Select Department',
            allowClear: true,
            width: '100%',
            theme: 'default'
        });

    }

    /* ==============================
       FLATPICKR INITIALIZATION
    ============================== */

    const shiftStartInput = document.querySelector('input[name="shift_start"]');
    const shiftEndInput   = document.querySelector('input[name="shift_end"]');

    const flatpickrConfig = {
        enableTime: true,
        noCalendar: true,
        dateFormat: "H:i",
        time_24hr: true,
        minuteIncrement: 15
    };

    if (shiftStartInput) {
        try {
            flatpickr(shiftStartInput, {
                ...flatpickrConfig,
                defaultHour: 9,
                defaultMinute: 0
            });
        } catch (error) {
            handlePluginError('Flatpickr (shift_start)', error);
        }
    }

    if (shiftEndInput) {
        try {
            flatpickr(shiftEndInput, {
                ...flatpickrConfig,
                defaultHour: 17,
                defaultMinute: 0
            });
        } catch (error) {
            handlePluginError('Flatpickr (shift_end)', error);
        }
    }

});
