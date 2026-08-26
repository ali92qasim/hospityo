import JustValidate from 'just-validate';
import {
    bindRuntimeEmailCheck,
    createEmailAvailabilityChecker,
    justValidateFormConfig,
} from './email-availability.js';

const TIME_HI = /^([01]\d|2[0-3]):[0-5]\d$/;
const INTEGER = /^-?\d+$/;
const UNIQUE_EMAIL_MESSAGE = 'This email is already in use by another doctor or user.';

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
            required('shift start'),
            {
                validator: (value) => TIME_HI.test(value),
                errorMessage: 'The shift start field must match the format H:i.',
            },
        ])
        .addField('[name="shift_end"]', [
            required('shift end'),
            {
                validator: (value) => TIME_HI.test(value),
                errorMessage: 'The shift end field must match the format H:i.',
            },
            {
                validator: (value) => TIME_HI.test(shiftStart?.value) && value > shiftStart.value,
                errorMessage: 'The shift end field must be a date after shift start.',
            },
        ])
        .addField('[name="status"]', [required('status'), inList('status', ['active', 'inactive'])]);

    bindRuntimeEmailCheck(emailInput, isEmailAvailable, validator);

    return validator;
}
