// Import styles
import '../css/appointments-calendar.css';

// Import jQuery first
import $ from 'jquery';

// Expose globally BEFORE plugins
window.$ = window.jQuery = $;

// Import Select2 properly
import select2 from 'select2';
select2(window, $);

// Import dependencies
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import flatpickr from 'flatpickr';

console.log('Appointments calendar module loaded');

// Initialize calendar when DOM is ready (jQuery way)
$(function() {
    console.log('DOM ready, initializing calendar...');
    
    const calendarEl = document.getElementById('calendar');
    
    if (!calendarEl) {
        console.warn('Calendar element not found');
        return;
    }

    let calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: true,
        selectable: true,
        selectMirror: true,
        dayMaxEvents: true,
        weekends: true,
        events: function(info, successCallback, failureCallback) {
            const doctorId = $('#doctor-filter').val();
            let url = '/calendar/events?start=' + info.startStr + '&end=' + info.endStr;
            if (doctorId) {
                url += '&doctor_id=' + doctorId;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => {
                    console.error('Error fetching events:', error);
                    failureCallback(error);
                });
        },
        dateClick: function(info) {
            openAppointmentModal();
            const datetime = info.dateStr + ' 09:00';
            if (window.flatpickrInstance) {
                window.flatpickrInstance.setDate(datetime);
            }
        },
        eventClick: function(info) {
            const appointmentId = info.event.id;
            loadAppointmentData(appointmentId);
        },
        eventDrop: function(info) {
            updateAppointmentDateTime(info.event.id, info.event.start);
        },
        eventResize: function(info) {
            updateAppointmentDateTime(info.event.id, info.event.start);
        }
    });

    calendar.render();
    console.log('✓ Calendar rendered');

    // Doctor filter change
    $('#doctor-filter').on('change', function() {
        calendar.refetchEvents();
    });

    // Initialize Select2 for patient and doctor dropdowns
    const $patientSelect = $('#patient_id');
    const $doctorSelect = $('#doctor_id');
    
    if ($patientSelect.length && $doctorSelect.length) {
        // Ensure Select2 exists before using it
        if (typeof $.fn.select2 !== 'function') {
            console.error('Select2 is not loaded properly');
            return;
        }

        try {
            $patientSelect.select2({
                placeholder: 'Select Patient',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#appointmentModal')
            });
            
            $doctorSelect.select2({
                placeholder: 'Select Doctor',
                allowClear: true,
                width: '100%',
                dropdownParent: $('#appointmentModal')
            });
            
            console.log('✓ Select2 initialized on patient and doctor dropdowns');
        } catch (error) {
            console.error('Select2 initialization error:', error);
        }
    } else {
        console.warn('Patient or Doctor select not found');
    }

    // Initialize Flatpickr for appointment datetime
    const appointmentDatetimeInput = document.getElementById('appointment_datetime');
    
    if (appointmentDatetimeInput) {
        try {
            window.flatpickrInstance = flatpickr(appointmentDatetimeInput, {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                minDate: "today",
                minuteIncrement: 15,
                allowInput: true
            });
            console.log('✓ Flatpickr initialized on appointment_datetime');
        } catch (error) {
            console.error('Flatpickr initialization error:', error);
        }
    } else {
        console.warn('Appointment datetime input not found');
    }

    // Form submission
    $('#appointmentForm').on('submit', function(e) {
        e.preventDefault();
        
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
            _token: $('input[name="_token"]').val()
        };

        if (method === 'PUT') {
            formData._method = 'PUT';
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: formData,
            success: function(response) {
                closeAppointmentModal();
                calendar.refetchEvents();
                showNotification('Success', 'Appointment saved successfully', 'success');
            },
            error: function(xhr) {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    let errorMessage = '';
                    Object.values(errors).forEach(error => {
                        errorMessage += error[0] + '\n';
                    });
                    showNotification('Error', errorMessage, 'error');
                } else {
                    showNotification('Error', 'Failed to save appointment', 'error');
                }
            }
        });
    });

    // Make functions globally available
    window.openAppointmentModal = openAppointmentModal;
    window.closeAppointmentModal = closeAppointmentModal;
    window.loadAppointmentData = loadAppointmentData;
    window.updateAppointmentDateTime = updateAppointmentDateTime;
    window.showNotification = showNotification;
    
    function openAppointmentModal() {
        $('#appointmentModal').removeClass('hidden');
        $('#appointment_id').val('');
        $('#appointmentForm')[0].reset();
        $('#patient_id, #doctor_id').val(null).trigger('change');
        $('#status-field').addClass('hidden');
        $('#modal-title').text('Schedule Appointment');
        $('#submit-text').text('Schedule Appointment');
    }

    function closeAppointmentModal() {
        $('#appointmentModal').addClass('hidden');
        $('#appointmentForm')[0].reset();
        $('#patient_id, #doctor_id').val(null).trigger('change');
    }

    function loadAppointmentData(appointmentId) {
        $.ajax({
            url: `/appointments/${appointmentId}`,
            method: 'GET',
            success: function(appointment) {
                $('#appointment_id').val(appointment.id);
                $('#patient_id').val(appointment.patient_id).trigger('change');
                $('#doctor_id').val(appointment.doctor_id).trigger('change');
                if (window.flatpickrInstance) {
                    window.flatpickrInstance.setDate(appointment.appointment_datetime);
                }
                $('#reason').val(appointment.reason);
                $('#notes').val(appointment.notes);
                $('#status').val(appointment.status);
                $('#status-field').removeClass('hidden');
                $('#modal-title').text('Edit Appointment');
                $('#submit-text').text('Update Appointment');
                $('#appointmentModal').removeClass('hidden');
            },
            error: function() {
                showNotification('Error', 'Failed to load appointment data', 'error');
            }
        });
    }

    function updateAppointmentDateTime(appointmentId, newDate) {
        const datetime = newDate.toISOString().slice(0, 16).replace('T', ' ');
        
        $.ajax({
            url: `/appointments/${appointmentId}`,
            method: 'POST',
            data: {
                _method: 'PUT',
                appointment_datetime: datetime,
                _token: $('input[name="_token"]').val()
            },
            success: function() {
                showNotification('Success', 'Appointment time updated', 'success');
            },
            error: function() {
                showNotification('Error', 'Failed to update appointment time', 'error');
                calendar.refetchEvents();
            }
        });
    }

    function showNotification(title, message, type) {
        // Simple notification - you can replace with a better notification library
        const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
        const notification = `
            <div class="fixed top-4 right-4 ${bgColor} text-white px-6 py-4 rounded-lg shadow-lg z-50" id="notification">
                <div class="font-bold">${title}</div>
                <div>${message}</div>
            </div>
        `;
        
        $('body').append(notification);
        
        setTimeout(() => {
            $('#notification').fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
});
