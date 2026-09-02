import { availableDoctorIds } from '../../resources/js/appointments-validation.js';

const sundayOnly = { id: 1, available_days: ['Sunday'] };
const fridayDoctor = { id: 2, available_days: ['Friday'] };
const friday = '2026-08-28 10:00';

const ids = availableDoctorIds([sundayOnly, fridayDoctor], friday).map(Number);

if (ids.includes(1)) {
    console.error('Sunday-only doctor listed on Friday:', ids);
    process.exit(1);
}

if (!ids.includes(2)) {
    console.error('Friday doctor missing on Friday:', ids);
    process.exit(1);
}

process.exit(0);
