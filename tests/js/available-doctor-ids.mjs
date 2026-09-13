import { availableDoctorIds } from '../../resources/js/appointments-validation.js';

const sundayOnly = { id: 1, available_days: ['Sunday'] };
const fridayDoctor = { id: 2, available_days: ['Friday'] };
const mondayDoctor = { id: 3, available_days: ['Monday'] };
const friday = '2026-08-28 10:00';
const monday = '2026-09-14 09:00';

const fridayIds = availableDoctorIds([sundayOnly, fridayDoctor, mondayDoctor], friday).map(Number);

if (fridayIds.includes(1)) {
    console.error('Sunday-only doctor listed on Friday:', fridayIds);
    process.exit(1);
}

if (!fridayIds.includes(2)) {
    console.error('Friday doctor missing on Friday:', fridayIds);
    process.exit(1);
}

if (fridayIds.includes(3)) {
    console.error('Monday doctor listed on Friday:', fridayIds);
    process.exit(1);
}

const mondayIds = availableDoctorIds([sundayOnly, fridayDoctor, mondayDoctor], monday).map(Number);

if (!mondayIds.includes(3)) {
    console.error('Monday doctor missing on Monday:', mondayIds);
    process.exit(1);
}

if (mondayIds.includes(1) || mondayIds.includes(2)) {
    console.error('Non-Monday doctor listed on Monday:', mondayIds);
    process.exit(1);
}

process.exit(0);
