import { readShiftClock } from '../../resources/js/doctor-create-validation.js';

function assertEqual(actual, expected, message) {
    if (actual !== expected) {
        console.error(`${message}: expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`);
        process.exit(1);
    }
}

assertEqual(
    readShiftClock({ value: '09:00' }),
    '09:00',
    'named H:i value should be accepted'
);

assertEqual(
    readShiftClock({
        value: '',
        _flatpickr: {
            selectedDates: [new Date(2026, 0, 1, 9, 0, 0)],
            altInput: { value: '9:00 AM' },
        },
    }),
    '09:00',
    'selected Flatpickr time should count even when the hidden named input is empty'
);

assertEqual(
    readShiftClock({
        value: '',
        _flatpickr: {
            selectedDates: [],
            altInput: { value: '5:00 PM' },
        },
    }),
    '17:00',
    'visible Flatpickr alt input should count when selectedDates is empty'
);

assertEqual(
    readShiftClock({
        value: '',
        _flatpickr: {
            selectedDates: [new Date(2026, 0, 1, 17, 0, 0)],
            altInput: { value: '5:00 PM' },
        },
    }),
    '17:00',
    'shift end selectedDates should convert to H:i'
);

assertEqual(
    readShiftClock({ value: '' }),
    '',
    'empty picker and input should stay empty'
);

assertEqual(
    readShiftClock({
        value: '',
        _flatpickr: {
            selectedDates: [],
            altInput: { value: '' },
        },
    }),
    '',
    'unselected Flatpickr fields should stay empty'
);
