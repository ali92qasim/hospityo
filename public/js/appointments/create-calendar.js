let calendar;
let isEditMode = false;
let flatpickrInstance;

document.addEventListener('DOMContentLoaded', function() {
    initCreateCalendar();
    initCreateForm();
    initFlatpickr();
    initSelect2();
});

function initFlatpickr() {
    flatpickrInstance = flatpickr("#appointment_datetime", {
        enableTime: true,
        dateFormat: "Y-m-d H:i",
        time_24hr: false,
        minDate: "today",
        minuteIncrement: 30,
        defaultHour: 9,
        defaultMinute: 0,
        disable: [
            function(date) {
                // Disable Sundays (0 = Sunday)
                return (date.getDay() === 0);
            }
        ],
        locale: {
            firstDayOfWeek: 1 // Start week on Monday
        }
    });
}

function initSelect2() {
    $('#patient_id').select2({
        placeholder: 'Select Patient',
        allowClear: true,
        dropdownParent: $('#appointmentModal'),
        width: '100%'
    });
    
    $('#doctor_id').select2({
        placeholder: 'Select Doctor',
        allowClear: true,
        dropdownParent: $('#appointmentModal'),
        width: '100%'
    });
}

function initCreateCalendar() {
    const calendarEl = document.getElementById('calendar');
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: function(fetchInfo, successCallback, failureCallback) {
            const doctorId = document.getElementById('doctor-filter').value;
            fetch(`/calendar/events?doctor_id=${doctorId}`)
                .then(response => response.json())
                .then(data => successCallback(data))
                .catch(error => failureCallback(error));
        },
        dateClick: function(info) {
            openAppointmentModal(info.dateStr);
        },
        eventClick: function(info) {
            openEditModal(info.event);
        },
        eventMouseEnter: function(info) {
            info.el.style.cursor = 'pointer';
        },
        eventMouseLeave: function(info) {
            info.el.style.cursor = 'default';
        },
        height: 'auto',
        eventColor: '#0066CC',
        eventDisplay: 'block'
    });

    calendar.render();

    // Filter by doctor
    document.getElementById('doctor-filter').addEventListener('change', function() {
        calendar.refetchEvents();
    });
}

function initCreateForm() {
    document.getElementById('appointmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitAppointment();
    });
}

function openAppointmentModal(date = null) {
    isEditMode = false;
    document.getElementById('modal-title').textContent = 'Schedule Appointment';
    document.getElementById('submit-text').textContent = 'Schedule Appointment';
    document.getElementById('status-field').classList.add('hidden');
    document.getElementById('appointmentForm').reset();
    document.getElementById('appointment_id').value = '';
    
    if (date && flatpickrInstance) {
        flatpickrInstance.setDate(date + ' 09:00');
    }
    
    document.getElementById('appointmentModal').classList.remove('hidden');
}

function openEditModal(event) {
    isEditMode = true;
    document.getElementById('modal-title').textContent = 'Edit Appointment';
    document.getElementById('submit-text').textContent = 'Update Appointment';
    document.getElementById('status-field').classList.remove('hidden');
    
    const appointment = event.extendedProps.appointment;
    document.getElementById('appointment_id').value = event.id;
    $('#patient_id').val(appointment.patient_id).trigger('change');
    $('#doctor_id').val(appointment.doctor_id).trigger('change');
    
    // Set datetime using flatpickr
    if (flatpickrInstance && appointment.appointment_datetime) {
        flatpickrInstance.setDate(appointment.appointment_datetime);
    }
    
    document.getElementById('status').value = appointment.status;
    document.getElementById('reason').value = appointment.reason || '';
    document.getElementById('notes').value = appointment.notes || '';
    
    document.getElementById('appointmentModal').classList.remove('hidden');
}

function closeAppointmentModal() {
    document.getElementById('appointmentModal').classList.add('hidden');
    document.getElementById('appointmentForm').reset();
    $('#patient_id').val(null).trigger('change');
    $('#doctor_id').val(null).trigger('change');
}

function submitAppointment() {
    const formData = new FormData(document.getElementById('appointmentForm'));
    const appointmentId = document.getElementById('appointment_id').value;
    
    const url = isEditMode ? 
        `/appointments/${appointmentId}` : 
        '/appointments';
    
    if (isEditMode) {
        formData.append('_method', 'PUT');
    }
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        if (response.ok) {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch {
                    return { success: true, message: 'Appointment saved successfully.' };
                }
            });
        } else {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch {
                    throw new Error('Server error');
                }
            });
        }
    })
    .then(data => {
        if (data.success) {
            closeAppointmentModal();
            calendar.refetchEvents();
            showNotification(data.message, 'success');
        } else {
            showNotification('Error: ' + (data.message || 'Something went wrong'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while saving the appointment', 'error');
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}