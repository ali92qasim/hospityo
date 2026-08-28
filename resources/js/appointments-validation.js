import JustValidate from 'just-validate';
import { justValidateFormConfig } from './email-availability.js';

const required = (attribute) => ({
    rule: 'required',
    errorMessage: `The ${attribute} field is required.`,
});

export function isPastCalendarDate(value) {
    const datePart = calendarDatePart(value);

    if (!datePart) {
        return false;
    }

    return datePart < localDatePart(new Date());
}

function calendarDatePart(value) {
    if (value instanceof Date && !Number.isNaN(value.getTime())) {
        return localDatePart(value);
    }

    const raw = String(value || '').trim();
    const localMatch = raw.match(/^(\d{4}-\d{2}-\d{2})(?:[ T]\d{2}:\d{2})?/);

    if (localMatch && !raw.includes('T') && !raw.endsWith('Z')) {
        return localMatch[1];
    }

    const parsed = new Date(raw);

    if (Number.isNaN(parsed.getTime())) {
        return '';
    }

    return localDatePart(parsed);
}

function localDatePart(date) {
    return [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
    ].join('-');
}

export function formatLocalDateTime(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}`;
}

export function readDoctorSchedules(root = document) {
    const el = root.querySelector('#doctor-schedules-data');

    if (!el) {
        return [];
    }

    try {
        const parsed = JSON.parse(el.textContent);

        return Array.isArray(parsed) ? parsed : [];
    } catch {
        return [];
    }
}

export function findDoctorSchedule(schedules, doctorId) {
    return schedules.find((schedule) => Number(schedule.id) === Number(doctorId)) || null;
}

export function isDoctorListedForDatetime(schedule, datetimeStr) {
    if (!schedule) {
        return false;
    }

    const days = Array.isArray(schedule.available_days)
        ? schedule.available_days.filter((day) => typeof day === 'string' && day !== '')
        : [];

    if (days.length === 0) {
        return false;
    }

    const date = parseAppointmentDateOrDatetime(datetimeStr);

    if (!date) {
        return false;
    }

    const weekday = date.toLocaleDateString('en-US', { weekday: 'long' });

    if (!days.includes(weekday)) {
        return false;
    }

    if (!hasClockTime(datetimeStr)) {
        return true;
    }

    const minutes = (date.getHours() * 60) + date.getMinutes();
    const start = timeToMinutes(schedule.shift_start);
    const end = timeToMinutes(schedule.shift_end);

    return minutes >= start && minutes <= end;
}

export function availableDoctorIds(schedules, datetimeStr, now = new Date()) {
    if (!Array.isArray(schedules)) {
        return [];
    }

    const value = String(datetimeStr || '').trim() || localDatePart(now);

    return schedules
        .filter((schedule) => isDoctorListedForDatetime(schedule, value))
        .map((schedule) => schedule.id);
}

const OUTSIDE_HOURS_PREFIX = "The selected time is outside the doctor's availability";

export function formatShiftClock(time) {
    return String(time || '').slice(0, 5);
}

export function outsideHoursMessage(shiftStart, shiftEnd) {
    return `${OUTSIDE_HOURS_PREFIX} (${formatShiftClock(shiftStart)} to ${formatShiftClock(shiftEnd)}).`;
}

export function isOutsideHoursMessage(message) {
    return typeof message === 'string' && message.startsWith(OUTSIDE_HOURS_PREFIX);
}

export function doctorScheduleMessage(schedule, datetimeStr) {
    if (!schedule) {
        return null;
    }

    const days = Array.isArray(schedule.available_days)
        ? schedule.available_days.filter((day) => typeof day === 'string' && day !== '')
        : [];

    if (days.length === 0) {
        return 'The selected doctor has no available days scheduled.';
    }

    const date = parseAppointmentDatetime(datetimeStr);

    if (!date) {
        return null;
    }

    const weekday = date.toLocaleDateString('en-US', { weekday: 'long' });

    if (!days.includes(weekday)) {
        return 'The selected doctor is not available on this day.';
    }

    const minutes = (date.getHours() * 60) + date.getMinutes();
    const start = timeToMinutes(schedule.shift_start);
    const end = timeToMinutes(schedule.shift_end);

    if (minutes < start || minutes > end) {
        return outsideHoursMessage(schedule.shift_start, schedule.shift_end);
    }

    return null;
}

function hasClockTime(value) {
    return /^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/.test(String(value || '').trim());
}

function parseAppointmentDateOrDatetime(value) {
    const timed = parseAppointmentDatetime(value);

    if (timed) {
        return timed;
    }

    const match = String(value || '').trim().match(/^(\d{4}-\d{2}-\d{2})/);

    if (!match) {
        return null;
    }

    const [year, month, day] = match[1].split('-').map(Number);

    return new Date(year, month - 1, day);
}

function parseAppointmentDatetime(value) {
    const match = String(value || '').trim().match(/^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2})/);

    if (!match) {
        return null;
    }

    const [year, month, day] = match[1].split('-').map(Number);
    const [hour, minute] = match[2].split(':').map(Number);

    return new Date(year, month - 1, day, hour, minute);
}

function timeToMinutes(time) {
    const parts = String(time || '').slice(0, 5).split(':');

    return ((parseInt(parts[0], 10) || 0) * 60) + (parseInt(parts[1], 10) || 0);
}

export function initAppointmentValidation(form, options = {}) {
    const getDoctorId = options.getDoctorId || (() => form.querySelector('[name="doctor_id"]')?.value);
    const getSchedules = options.getSchedules || (() => readDoctorSchedules());
    const isPastAppointmentLock = options.isPastAppointmentLock || (() => false);

    const validator = new JustValidate(form, {
        ...justValidateFormConfig,
        submitFormAutomatically: false,
    });

    const currentSchedule = () => findDoctorSchedule(getSchedules(), getDoctorId());

    validator
        .addField('[name="patient_id"]', [required('patient id')], {
            errorsContainer: '[data-error-slot="patient_id"]',
        })
        .addField('[name="doctor_id"]', [required('doctor id')], {
            errorsContainer: '[data-error-slot="doctor_id"]',
        })
        .addField('[name="appointment_datetime"]', [
            required('appointment datetime'),
            {
                validator: (value) => isPastAppointmentLock() || !isPastCalendarDate(value),
                errorMessage: 'Appointments cannot be booked on a past date.',
            },
            {
                validator: (value) => {
                    if (isPastAppointmentLock() || !value || !getDoctorId()) {
                        return true;
                    }

                    const days = currentSchedule()?.available_days;

                    return Array.isArray(days) && days.filter(Boolean).length > 0;
                },
                errorMessage: 'The selected doctor has no available days scheduled.',
            },
            {
                validator: (value) => {
                    if (isPastAppointmentLock() || !value || !getDoctorId()) {
                        return true;
                    }

                    const message = doctorScheduleMessage(currentSchedule(), value);

                    return message !== 'The selected doctor is not available on this day.';
                },
                errorMessage: 'The selected doctor is not available on this day.',
            },
            {
                validator: (value) => {
                    if (isPastAppointmentLock() || !value || !getDoctorId()) {
                        return true;
                    }

                    return !isOutsideHoursMessage(doctorScheduleMessage(currentSchedule(), value));
                },
                errorMessage: (value) => (
                    doctorScheduleMessage(currentSchedule(), value)
                    || outsideHoursMessage(currentSchedule()?.shift_start, currentSchedule()?.shift_end)
                ),
            },
        ]);

    if (typeof options.onSuccess === 'function') {
        validator.onSuccess((event) => {
            event?.preventDefault();
            options.onSuccess(event);
        });
    }

    return validator;
}
