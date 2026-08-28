import '../css/appointments-calendar.css';

import $ from 'jquery';

window.$ = window.jQuery = $;

import select2 from 'select2';
select2(window, $);

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import flatpickr from 'flatpickr';
import {
    availableDoctorIds,
    doctorScheduleMessage,
    findDoctorSchedule,
    formatLocalDateTime,
    initAppointmentValidation,
    isPastCalendarDate,
    readDoctorSchedules,
} from './appointments-validation.js';
import { flatpickrDateTimeConfig, fullCalendarTimeConfig } from './datetime-config';

$(function () {
    const calendarEl = document.getElementById('calendar');
    const appointmentForm = document.getElementById('appointmentForm');

    if (!calendarEl || !appointmentForm) {
        return;
    }

    let pastAppointmentLock = false;
    let appointmentValidator = null;
    let suppressFieldRevalidate = false;

    const calendar = new Calendar(calendarEl, {
        ...fullCalendarTimeConfig(),
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        editable: true,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        selectAllow: function (info) {
            return !isPastCalendarDate(info.startStr);
        },
        eventContent: function (arg) {
            const props = arg.event.extendedProps;
            return {
                html: `<div class="fc-event-pill">${props.patient || arg.event.title}</div>`,
            };
        },
        eventDidMount: function (info) {
            info.el.setAttribute('tabindex', '0');
            info.el.setAttribute('aria-label', appointmentTooltipAria(info.event));
            info.el.addEventListener('focus', () => showAppointmentTooltip(info.el, info.event));
            info.el.addEventListener('blur', hideAppointmentTooltip);
        },
        eventMouseEnter: function (info) {
            showAppointmentTooltip(info.el, info.event);
        },
        eventMouseLeave: function () {
            hideAppointmentTooltip();
        },
        eventDragStart: function () {
            hideAppointmentTooltip();
        },
        events: function (info, successCallback, failureCallback) {
            const doctorId = $('#doctor-filter').val();
            let url = '/calendar/events?start=' + info.startStr + '&end=' + info.endStr;
            if (doctorId) {
                url += '&doctor_id=' + doctorId;
            }

            fetch(url)
                .then((response) => response.json())
                .then((data) => successCallback(data))
                .catch((error) => {
                    failureCallback(error);
                });
        },
        dateClick: function (info) {
            hideAppointmentTooltip();
            if (isPastCalendarDate(info.dateStr)) {
                showNotification('Error', 'Appointments cannot be booked on a past date.', 'error');
                return;
            }

            openAppointmentModal();
            const datetime = info.dateStr + ' 09:00';
            if (window.flatpickrInstance) {
                window.flatpickrInstance.setDate(datetime);
            }
            appointmentValidator?.revalidateField('[name="appointment_datetime"]');
        },
        eventClick: function (info) {
            hideAppointmentTooltip();
            loadAppointmentData(info.event.id);
        },
        eventDrop: function (info) {
            const message = dropConstraintMessage(info.event, info.oldEvent);
            if (message) {
                info.revert();
                showNotification('Error', message, 'error');
                return;
            }
            updateAppointmentDateTime(info.event.id, info.event.start);
        },
        eventResize: function (info) {
            const message = dropConstraintMessage(info.event, info.oldEvent);
            if (message) {
                info.revert();
                showNotification('Error', message, 'error');
                return;
            }
            updateAppointmentDateTime(info.event.id, info.event.start);
        },
    });

    calendar.render();

    $('#doctor-filter').on('change', function () {
        calendar.refetchEvents();
    });

    const $patientSelect = $('#patient_id');
    const $doctorSelect = $('#doctor_id');
    const $allDoctorOptions = $doctorSelect.find('option').clone();
    const doctorSelect2Config = {
        placeholder: 'Select Doctor',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#appointmentModal'),
    };

    if ($patientSelect.length && $doctorSelect.length && typeof $.fn.select2 === 'function') {
        $patientSelect.select2({
            placeholder: 'Select Patient',
            allowClear: true,
            width: '100%',
            dropdownParent: $('#appointmentModal'),
        });

        $doctorSelect.select2(doctorSelect2Config);
    }

    const appointmentDatetimeInput = document.getElementById('appointment_datetime');

    if (appointmentDatetimeInput) {
        window.flatpickrInstance = flatpickr(appointmentDatetimeInput, {
            ...flatpickrDateTimeConfig({
                minDate: 'today',
                minuteIncrement: 15,
                allowInput: false,
                disable: [isUnavailablePickerDate],
                onChange: function () {
                    filterDoctorOptions();
                    appointmentValidator?.revalidateField('[name="appointment_datetime"]');
                },
            }),
        });
    }

    $patientSelect.on('change', function () {
        if (pastAppointmentLock || suppressFieldRevalidate) {
            return;
        }
        appointmentValidator?.revalidateField('[name="patient_id"]');
    });

    $doctorSelect.on('change', function () {
        if (pastAppointmentLock) {
            return;
        }

        applyDoctorScheduleToPicker();

        if (suppressFieldRevalidate) {
            return;
        }

        appointmentValidator?.revalidateField('[name="doctor_id"]');
        appointmentValidator?.revalidateField('[name="appointment_datetime"]');
    });

    appointmentValidator = initAppointmentValidation(appointmentForm, {
        isPastAppointmentLock: () => pastAppointmentLock,
        onSuccess: submitAppointment,
    });

    $('#open-appointment-modal').on('click', function () {
        hideAppointmentTooltip();
        openAppointmentModal();
    });

    $('.js-close-appointment-modal').on('click', function () {
        closeAppointmentModal();
    });

    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && !$('#appointmentModal').hasClass('hidden')) {
            closeAppointmentModal();
        }
    });

    function currentDoctorSchedule() {
        return findDoctorSchedule(readDoctorSchedules(), $doctorSelect.val());
    }

    function filterDoctorOptions() {
        const datetime = String($('#appointment_datetime').val() || '').trim();
        const availableIds = new Set(
            availableDoctorIds(readDoctorSchedules(), datetime).map((id) => Number(id)),
        );
        const selectedId = $doctorSelect.val();
        const keepSelected = Boolean($('#appointment_id').val());
        const useSelect2 = typeof $.fn.select2 === 'function' && $doctorSelect.hasClass('select2-hidden-accessible');

        if (useSelect2) {
            $doctorSelect.select2('destroy');
        }

        $doctorSelect.empty();

        $allDoctorOptions.each(function () {
            if (!this.value) {
                $doctorSelect.append($(this).clone());
                return;
            }

            const available = availableIds.has(Number(this.value));
            const retainAssigned = keepSelected && String(this.value) === String(selectedId);

            if (available || retainAssigned) {
                $doctorSelect.append($(this).clone());
            }
        });

        if (selectedId && $doctorSelect.find('option').filter(function () {
            return String(this.value) === String(selectedId);
        }).length) {
            $doctorSelect.val(selectedId);
        } else if (selectedId) {
            $doctorSelect.val(null);
        }

        if (typeof $.fn.select2 === 'function') {
            $doctorSelect.select2(doctorSelect2Config);
        }

        if (selectedId && !$doctorSelect.val()) {
            $doctorSelect.trigger('change');
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function humanizeStatus(status) {
        const label = String(status || '').replace(/_/g, ' ').trim();

        if (!label) {
            return '—';
        }

        return label.replace(/\b\w/g, (char) => char.toUpperCase());
    }

    function formatTooltipDateTime(date) {
        if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
            return '—';
        }

        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const hour24 = date.getHours();
        const hour12 = hour24 % 12 || 12;
        const minutes = String(date.getMinutes()).padStart(2, '0');
        const meridiem = hour24 >= 12 ? 'PM' : 'AM';

        return `${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()} ${hour12}:${minutes} ${meridiem}`;
    }

    function appointmentTooltipRows(event) {
        const props = event.extendedProps || {};
        const rows = [
            ['Patient', props.patient || event.title || '—'],
            ['Doctor', props.doctor ? `Dr. ${props.doctor}` : '—'],
            ['When', formatTooltipDateTime(event.start)],
            ['Status', humanizeStatus(props.status)],
        ];

        if (props.reason) {
            rows.push(['Reason', props.reason]);
        }

        return rows;
    }

    function appointmentTooltipAria(event) {
        return appointmentTooltipRows(event)
            .map(([label, value]) => `${label}: ${value}`)
            .join(', ');
    }

    function appointmentTooltipHtml(event) {
        return appointmentTooltipRows(event)
            .map(([label, value]) => `<dt>${escapeHtml(label)}</dt><dd>${escapeHtml(value)}</dd>`)
            .join('');
    }

    function tooltipElement() {
        let el = document.getElementById('appointment-event-tooltip');

        if (!el) {
            el = document.createElement('div');
            el.id = 'appointment-event-tooltip';
            el.className = 'appointment-event-tooltip';
            el.setAttribute('role', 'tooltip');
            document.body.appendChild(el);
        }

        return el;
    }

    function showAppointmentTooltip(anchor, event) {
        const el = tooltipElement();
        el.innerHTML = `<dl>${appointmentTooltipHtml(event)}</dl>`;
        el.classList.add('is-visible');

        const rect = anchor.getBoundingClientRect();
        const tooltipRect = el.getBoundingClientRect();
        let left = rect.left;
        let top = rect.bottom + 8;

        if (left + tooltipRect.width > window.innerWidth - 8) {
            left = window.innerWidth - tooltipRect.width - 8;
        }

        if (top + tooltipRect.height > window.innerHeight - 8) {
            top = rect.top - tooltipRect.height - 8;
        }

        el.style.left = `${Math.max(8, left)}px`;
        el.style.top = `${Math.max(8, top)}px`;
    }

    function hideAppointmentTooltip() {
        const el = document.getElementById('appointment-event-tooltip');

        if (!el) {
            return;
        }

        el.classList.remove('is-visible');
    }

    function isUnavailablePickerDate(date) {
        const schedule = currentDoctorSchedule();

        if (!schedule) {
            return false;
        }

        const days = Array.isArray(schedule.available_days)
            ? schedule.available_days.filter(Boolean)
            : [];

        if (days.length === 0) {
            return true;
        }

        const weekday = date.toLocaleDateString('en-US', { weekday: 'long' });

        return !days.includes(weekday);
    }

    function applyDoctorScheduleToPicker() {
        if (!window.flatpickrInstance) {
            return;
        }

        const schedule = currentDoctorSchedule();

        window.flatpickrInstance.set('disable', [isUnavailablePickerDate]);

        if (schedule?.shift_start && schedule?.shift_end) {
            window.flatpickrInstance.set('minTime', String(schedule.shift_start).slice(0, 5));
            window.flatpickrInstance.set('maxTime', String(schedule.shift_end).slice(0, 5));
        } else {
            window.flatpickrInstance.set('minTime', null);
            window.flatpickrInstance.set('maxTime', null);
        }
    }

    function dropConstraintMessage(event, oldEvent) {
        if (oldEvent?.start && isPastCalendarDate(formatLocalDateTime(oldEvent.start))) {
            return 'Past appointments can only update status and notes.';
        }

        const datetime = formatLocalDateTime(event.start);
        if (isPastCalendarDate(datetime)) {
            return 'Appointments cannot be booked on a past date.';
        }

        const doctorId = event.extendedProps.doctor_id;
        const schedule = findDoctorSchedule(readDoctorSchedules(), doctorId);

        return doctorScheduleMessage(schedule, datetime);
    }

    function setPastAppointmentLock(locked) {
        pastAppointmentLock = Boolean(locked);
        $('#patient_id, #doctor_id').next('.select2').toggleClass('pointer-events-none opacity-60', pastAppointmentLock);
        $('#appointment_datetime').toggleClass('bg-gray-50', pastAppointmentLock);

        if (window.flatpickrInstance) {
            window.flatpickrInstance.set('clickOpens', !pastAppointmentLock);
            window.flatpickrInstance.set('minDate', pastAppointmentLock ? null : 'today');
            window.flatpickrInstance.set('allowInput', false);
        }
    }

    function openAppointmentModal() {
        setPastAppointmentLock(false);
        suppressFieldRevalidate = true;
        $('#appointmentModal').removeClass('hidden');
        $('#appointment_id').val('');
        $('#appointmentForm')[0].reset();
        $('#patient_id, #doctor_id').val(null).trigger('change');
        $('#status-field').addClass('hidden');
        $('#modal-title').text('Schedule Appointment');
        $('#submit-text').text('Schedule Appointment');
        applyDoctorScheduleToPicker();
        if (window.flatpickrInstance) {
            window.flatpickrInstance.setDate(formatLocalDateTime(new Date()), true);
        }
        filterDoctorOptions();
        suppressFieldRevalidate = false;
    }

    function closeAppointmentModal() {
        suppressFieldRevalidate = true;
        $('#appointmentModal').addClass('hidden');
        $('#appointmentForm')[0].reset();
        $('#patient_id, #doctor_id').val(null).trigger('change');
        setPastAppointmentLock(false);
        suppressFieldRevalidate = false;
    }

    function submitAppointment() {
        const appointmentId = $('#appointment_id').val();
        const url = appointmentId ? `/appointments/${appointmentId}` : '/appointments';
        const method = appointmentId ? 'PUT' : 'POST';

        const formData = {
            patient_id: $('#patient_id').val(),
            doctor_id: $('#doctor_id').val(),
            appointment_datetime: $('#appointment_datetime').val(),
            reason: $('#reason').val(),
            notes: $('#notes').val(),
            status: $('#status').val() || 'scheduled',
            _token: $('input[name="_token"]').val(),
        };

        if (method === 'PUT') {
            formData._method = 'PUT';
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function () {
                closeAppointmentModal();
                calendar.refetchEvents();
                showNotification('Success', 'Appointment saved successfully', 'success');
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    let errorMessage = '';
                    Object.values(errors).forEach((error) => {
                        errorMessage += error[0] + '\n';
                    });
                    showNotification('Error', errorMessage, 'error');
                } else {
                    showNotification('Error', 'Failed to save appointment', 'error');
                }
            },
        });
    }

    function loadAppointmentData(appointmentId) {
        $.ajax({
            url: `/appointments/${appointmentId}`,
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (appointment) {
                suppressFieldRevalidate = true;
                $('#appointment_id').val(appointment.id);
                setPastAppointmentLock(isPastCalendarDate(appointment.appointment_datetime));
                $('#patient_id').val(appointment.patient_id).trigger('change');
                $('#doctor_id').val(appointment.doctor_id).trigger('change');
                if (window.flatpickrInstance && appointment.appointment_datetime) {
                    window.flatpickrInstance.setDate(appointment.appointment_datetime);
                }
                filterDoctorOptions();
                if (!pastAppointmentLock) {
                    applyDoctorScheduleToPicker();
                }
                $('#reason').val(appointment.reason || '');
                $('#notes').val(appointment.notes || '');
                $('#status').val(appointment.status || 'scheduled');
                $('#status-field').removeClass('hidden');
                $('#modal-title').text('Edit Appointment');
                $('#submit-text').text('Update Appointment');
                $('#appointmentModal').removeClass('hidden');
                suppressFieldRevalidate = false;
            },
            error: function () {
                showNotification('Error', 'Failed to load appointment data', 'error');
            },
        });
    }

    function updateAppointmentDateTime(appointmentId, newDate) {
        const datetime = formatLocalDateTime(newDate);

        $.ajax({
            url: `/appointments/${appointmentId}`,
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            data: {
                _method: 'PUT',
                appointment_datetime: datetime,
                _token: $('input[name="_token"]').val(),
            },
            success: function () {
                showNotification('Success', 'Appointment time updated', 'success');
            },
            error: function (xhr) {
                const errors = xhr.responseJSON?.errors;
                const message = errors ? Object.values(errors)[0][0] : 'Failed to update appointment time';
                showNotification('Error', message, 'error');
                calendar.refetchEvents();
            },
        });
    }

    function showNotification(title, message, type) {
        const text = title ? title + ': ' + message : message;
        if (type === 'success') {
            window.Toast?.success(text);
        } else if (type === 'error') {
            window.Toast?.error(text);
        } else if (type === 'warning') {
            window.Toast?.warning(text);
        } else {
            window.Toast?.info(text);
        }
    }
});
