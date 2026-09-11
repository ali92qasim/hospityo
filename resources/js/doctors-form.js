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
import { flatpickrTimeConfig } from './datetime-config';

// Centralized error handler
function handlePluginError(pluginName, error) {
    const message = `${pluginName} initialization failed.`;

    if ($ && $.error) {
        $.error(message);
    } else {
        throw new Error(`${message} ${error?.message || ''}`);
    }
}

const flatpickrConfig = {
    ...flatpickrTimeConfig({
        minuteIncrement: 15,
    }),
};

function bindTimePickerChange(input, onChange) {
    const picker = input?._flatpickr;

    if (!picker || typeof onChange !== 'function') {
        return;
    }

    const existing = picker.config.onChange;
    const hooks = (Array.isArray(existing) ? existing : [existing]).filter(Boolean);

    if (!hooks.includes(onChange)) {
        picker.config.onChange = [...hooks, onChange];
    }
}

function initTimePicker(input, { defaultHour, defaultMinute, onChange }) {
    if (!input) {
        return;
    }

    if (input._flatpickr) {
        bindTimePickerChange(input, onChange);
        return;
    }

    try {
        flatpickr(input, {
            ...flatpickrConfig,
            defaultHour,
            defaultMinute,
            onChange,
        });
    } catch (error) {
        handlePluginError('Flatpickr', error);
    }
}

$(function () {
    const form = document.getElementById('doctor-create-form');
    const validationReady = form
        ? import('./doctor-create-validation.js').then(({ initDoctorCreateValidation }) =>
            initDoctorCreateValidation(form)
        )
        : null;

    const revalidate = (selector) => {
        validationReady?.then((validator) => validator.revalidateField(selector));
    };

    const $departmentSelect = $('select[name="department_id"]');

    if ($departmentSelect.length) {
        if (typeof $.fn.select2 !== 'function') {
            handlePluginError('Select2', new Error('Select2 is not loaded properly.'));
            return;
        }

        $departmentSelect.select2({
            placeholder: 'Select Department',
            allowClear: true,
            width: '100%',
            theme: 'default',
        });

        $departmentSelect.on('change', () => revalidate('[name="department_id"]'));
    }

    initTimePicker(document.querySelector('input[name="shift_start"]'), {
        defaultHour: 9,
        defaultMinute: 0,
        onChange: () => {
            revalidate('[name="shift_start"]');
            revalidate('[name="shift_end"]');
        },
    });

    initTimePicker(document.querySelector('input[name="shift_end"]'), {
        defaultHour: 17,
        defaultMinute: 0,
        onChange: () => revalidate('[name="shift_end"]'),
    });
});
