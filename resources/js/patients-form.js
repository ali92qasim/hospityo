import '../css/patients-form.css';

const form = document.getElementById('patient-create-form');

if (form) {
    import('./patient-create-validation.js').then(({ initPatientCreateValidation }) => {
        initPatientCreateValidation(form);
    });
}
