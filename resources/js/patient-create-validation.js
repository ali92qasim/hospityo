import JustValidate from 'just-validate';
import { justValidateFormConfig } from './email-availability.js';

const INTEGER = /^-?\d+$/;

const required = (attribute) => ({
    rule: 'required',
    errorMessage: `The ${attribute} field is required.`,
});

const maxLength = (attribute, max) => ({
    rule: 'maxLength',
    value: max,
    errorMessage: `The ${attribute} field must not be greater than ${max} characters.`,
});

const optionalInList = (attribute, allowed) => ({
    validator: (value) => value === '' || allowed.includes(value),
    errorMessage: `The selected ${attribute} is invalid.`,
});

export function initPatientCreateValidation(form) {
    const validator = new JustValidate(form, {
        ...justValidateFormConfig,
        submitFormAutomatically: true,
    });

    validator
        .addField('[name="name"]', [required('name'), maxLength('name', 255)])
        .addField('[name="gender"]', [
            required('gender'),
            {
                validator: (value) => ['male', 'female', 'other'].includes(value),
                errorMessage: 'The selected gender is invalid.',
            },
        ])
        .addField('[name="age"]', [
            required('age'),
            {
                validator: (value) => INTEGER.test(String(value).trim()),
                errorMessage: 'The age field must be an integer.',
            },
            { rule: 'minNumber', value: 1, errorMessage: 'The age field must be at least 1.' },
            { rule: 'maxNumber', value: 150, errorMessage: 'The age field must not be greater than 150.' },
        ])
        .addField('[name="phone"]', [required('phone'), maxLength('phone', 20)])
        .addField('[name="marital_status"]', [
            optionalInList('marital status', ['single', 'married', 'divorced', 'widowed']),
        ])
        .addField('[name="emergency_name"]', [maxLength('emergency name', 255)])
        .addField('[name="emergency_phone"]', [maxLength('emergency phone', 20)])
        .addField('[name="emergency_relation"]', [maxLength('emergency relation', 100)]);

    form.querySelectorAll('button[type="submit"]').forEach((button) => {
        button.addEventListener('click', () => {
            const existing = form.querySelector('[data-submit-intent]');

            if (!button.name) {
                existing?.remove();
                return;
            }

            const hidden = existing ?? Object.assign(document.createElement('input'), {
                type: 'hidden',
            });

            hidden.dataset.submitIntent = '1';
            hidden.name = button.name;
            hidden.value = button.value;

            if (!existing) {
                form.appendChild(hidden);
            }
        });
    });

    return validator;
}
