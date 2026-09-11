import JustValidate from 'just-validate';
import {
    bindRuntimeEmailCheck,
    createEmailAvailabilityChecker,
    justValidateFormConfig,
} from './email-availability.js';

const TIME_HI = /^([01]\d|2[0-3]):[0-5]\d$/;
const INTEGER = /^-?\d+$/;
const UNIQUE_EMAIL_MESSAGE = 'This email is already in use by another doctor or user.';

function padClock(hours, minutes) {
    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
}

function parseClock(value) {
    const raw = String(value ?? '').trim();

    if (TIME_HI.test(raw)) {
        return raw;
    }

    const match = raw.match(/^(\d{1,2}):([0-5]\d)(?:\s*(AM|PM))?$/i);

    if (!match) {
        return '';
    }

    let hours = Number(match[1]);
    const minutes = Number(match[2]);
    const ampm = match[3]?.toUpperCase();

    if (ampm === 'PM' && hours < 12) {
        hours += 12;
    } else if (ampm === 'AM' && hours === 12) {
        hours = 0;
    }

    const clock = padClock(hours, minutes);

    return TIME_HI.test(clock) ? clock : '';
}

export function readShiftClock(input) {
    if (!input) {
        return '';
    }

    const picker = input._flatpickr;
    const selected = picker?.selectedDates?.[0];

    if (selected instanceof Date && !Number.isNaN(selected.getTime())) {
        return padClock(selected.getHours(), selected.getMinutes());
    }

    return parseClock(input.value) || parseClock(picker?.altInput?.value);
}

function commitShiftClock(input) {
    const clock = readShiftClock(input);

    if (input && clock && input.value !== clock) {
        input.value = clock;
    }

    return clock;
}

const required = (attribute) => ({
    rule: 'required',
    errorMessage: `The ${attribute} field is required.`,
});

const maxLength = (attribute, max) => ({
    rule: 'maxLength',
    value: max,
    errorMessage: `The ${attribute} field must not be greater than ${max} characters.`,
});

const inList = (attribute, allowed) => ({
    validator: (value) => allowed.includes(value),
    errorMessage: `The selected ${attribute} is invalid.`,
});

export function initDoctorCreateValidation(form) {
    const shiftStart = form.querySelector('[name="shift_start"]');
    const shiftEnd = form.querySelector('[name="shift_end"]');
    const emailInput = form.querySelector('[name="email"]');
    const emailAvailableUrl = form.dataset.emailAvailableUrl;
    const isEmailAvailable = emailAvailableUrl
        ? createEmailAvailabilityChecker(emailAvailableUrl)
        : null;

    const validator = new JustValidate(form, {
        ...justValidateFormConfig,
        submitFormAutomatically: true,
    });

    validator
        .addField('[name="name"]', [required('name'), maxLength('name', 255)])
        .addField('[name="specialization"]', [required('specialization'), maxLength('specialization', 255)])
        .addField('[name="qualification"]', [required('qualification'), maxLength('qualification', 255)])
        .addField('[name="pmdc_number"]', [maxLength('pmdc number', 50)])
        .addField('[name="phone"]', [required('phone'), maxLength('phone', 20)])
        .addField('[name="email"]', [
            required('email'),
            { rule: 'email', errorMessage: 'The email field must be a valid email address.' },
            ...(isEmailAvailable
                ? [
                    {
                        validator: (value) => isEmailAvailable.isKnownAvailable(value),
                        errorMessage: UNIQUE_EMAIL_MESSAGE,
                    },
                    {
                        validator: isEmailAvailable,
                        errorMessage: UNIQUE_EMAIL_MESSAGE,
                    },
                ]
                : []),
        ])
        .addField('[name="gender"]', [required('gender'), inList('gender', ['male', 'female', 'other'])])
        .addField('[name="experience_years"]', [
            required('experience years'),
            {
                validator: (value) => INTEGER.test(String(value).trim()),
                errorMessage: 'The experience years field must be an integer.',
            },
            { rule: 'minNumber', value: 0, errorMessage: 'The experience years field must be at least 0.' },
            { rule: 'maxNumber', value: 50, errorMessage: 'The experience years field must not be greater than 50.' },
        ])
        .addField('[name="consultation_fee"]', [
            required('consultation fee'),
            { rule: 'number', errorMessage: 'The consultation fee field must be a number.' },
            { rule: 'minNumber', value: 0, errorMessage: 'The consultation fee field must be at least 0.' },
        ])
        .addField('[name="department_id"]', [required('department id')], {
            errorsContainer: '[data-error-slot="department_id"]',
        })
        .addField('[name="shift_start"]', [
            {
                validator: () => commitShiftClock(shiftStart) !== '',
                errorMessage: required('shift start').errorMessage,
            },
            {
                validator: () => TIME_HI.test(commitShiftClock(shiftStart)),
                errorMessage: 'The shift start field must match the format H:i.',
            },
        ])
        .addField('[name="shift_end"]', [
            {
                validator: () => commitShiftClock(shiftEnd) !== '',
                errorMessage: required('shift end').errorMessage,
            },
            {
                validator: () => TIME_HI.test(commitShiftClock(shiftEnd)),
                errorMessage: 'The shift end field must match the format H:i.',
            },
            {
                validator: () => {
                    const start = commitShiftClock(shiftStart);
                    const end = commitShiftClock(shiftEnd);

                    return TIME_HI.test(start) && TIME_HI.test(end) && end > start;
                },
                errorMessage: 'The shift end field must be a date after shift start.',
            },
        ])
        .addField('[name="status"]', [required('status'), inList('status', ['active', 'inactive'])]);

    bindRuntimeEmailCheck(emailInput, isEmailAvailable, validator);

    return validator;
}
