// Import styles
import '../css/ot-surgeries.css';

// Import jQuery first
import $ from 'jquery';

// Expose globally BEFORE plugins
window.$ = window.jQuery = $;

// Import Select2
import select2 from 'select2';
select2(window, $);

// Import FullCalendar
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

// Import Flatpickr
import flatpickr from 'flatpickr';

$(function() {
    var calendarEl = document.getElementById('surgery-calendar');
    if (!calendarEl) return;

    // ── FullCalendar ──────────────────────────────────────────────────────────
    var calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: false,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        eventContent: function(arg) {
            var props = arg.event.extendedProps;
            return {
                html: '<div class="fc-event-pill">' + (props.patient || arg.event.title) + '</div>'
            };
        },
        events: function(info, successCallback, failureCallback) {
            var theatreId = $('#theatre-filter').val();
            var url = '/ot/calendar/events?start=' + info.startStr + '&end=' + info.endStr;
            if (theatreId) url += '&theatre_id=' + theatreId;

            fetch(url)
                .then(function(response) { return response.json(); })
                .then(function(data) { successCallback(data); })
                .catch(function(error) {
                    console.error('OT Calendar error:', error);
                    successCallback([]);
                });
        },
        eventClick: function(info) {
            var url = info.event.extendedProps.showUrl;
            if (url) window.location.href = url;
        },
        dateClick: function(info) {
            openSurgeryModal();
            if (window.surgeryDatePicker) {
                window.surgeryDatePicker.setDate(info.dateStr + ' 09:00');
            }
        }
    });

    calendar.render();

    // Theatre filter
    $('#theatre-filter').on('change', function() {
        calendar.refetchEvents();
    });

    // ── Select2 on Patient & Doctor ───────────────────────────────────────────
    var $patientSelect = $('#surgery_patient_id');
    var $doctorSelect = $('#surgery_doctor_id');

    if ($patientSelect.length && $doctorSelect.length) {
        try {
            $patientSelect.select2({
                placeholder: 'Select Patient',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#surgeryModal')
            });
            $doctorSelect.select2({
                placeholder: 'Select Surgeon',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#surgeryModal')
            });
        } catch (err) {
            console.error('Select2 init error:', err);
        }
    }

    // ── Flatpickr on Scheduled Date & Time ────────────────────────────────────
    var datetimeInput = document.getElementById('surgery_datetime');
    if (datetimeInput) {
        window.surgeryDatePicker = flatpickr(datetimeInput, {
            enableTime: true,
            dateFormat: 'Y-m-d H:i',
            time_24hr: true,
            minDate: 'today',
            minuteIncrement: 5,
            allowInput: true
        });
    }

    // ── Modal Handling ────────────────────────────────────────────────────────
    window.openSurgeryModal = function() {
        $('#surgeryModal').removeClass('hidden');
        $('#surgeryForm')[0].reset();
        $patientSelect.val(null).trigger('change');
        $doctorSelect.val(null).trigger('change');
        if (window.surgeryDatePicker) {
            window.surgeryDatePicker.clear();
        }
    };

    window.closeSurgeryModal = function() {
        $('#surgeryModal').addClass('hidden');
    };

    // Close on backdrop
    $('#surgeryModal').on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('flex')) {
            closeSurgeryModal();
        }
    });

    // Close on Escape
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && !$('#surgeryModal').hasClass('hidden')) {
            closeSurgeryModal();
        }
    });

    // ── Form Submit (AJAX) ────────────────────────────────────────────────────
    $('#surgeryForm').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            patient_id: $patientSelect.val(),
            doctor_id: $doctorSelect.val(),
            scheduled_datetime: $('#surgery_datetime').val(),
            procedure_name: $('#surgery_procedure').val(),
            surgery_type: $('#surgery_type').val(),
            operation_theatre_id: $('#surgery_theatre').val() || null,
            anesthesia_type: $('#surgery_anesthesia').val() || null,
            procedure_code: $('#surgery_code').val() || null,
            pre_op_diagnosis: $('#surgery_diagnosis').val() || null,
            _token: $('input[name="_token"]').val()
        };

        $.ajax({
            url: '/ot/surgeries',
            method: 'POST',
            data: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(response) {
                closeSurgeryModal();
                calendar.refetchEvents();
                if (window.Toast) window.Toast.success('Surgery scheduled successfully');
            },
            error: function(xhr) {
                var errors = xhr.responseJSON ? xhr.responseJSON.errors : null;
                if (errors) {
                    var msg = '';
                    Object.values(errors).forEach(function(err) { msg += err[0] + '\n'; });
                    alert(msg);
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    alert(xhr.responseJSON.message);
                } else {
                    alert('Failed to schedule surgery');
                }
            }
        });
    });
});
