let calendar;
let flatpickrInstance;
const appointmentId = document.getElementById('appointment_id').value;

document.addEventListener('DOMContentLoaded', function() {
    initEditCalendar();
    initEditForm();
    initFlatpickr();
    initSelect2();
    
    // Auto-open modal for editing
    setTimeout(() => {
        openEditModal();
    }, 500);
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

function initEditCalendar() {
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
                .then(data => {
                    // Highlight the current appointment being edited
                    data.forEach(event => {
                        if (event.id == appointmentId) {
                            event.backgroundColor = '#f59e0b';
                            event.borderColor = '#f59e0b';
                        }
                    });
                    successCallback(data);
                })
                .catch(error => failureCallback(error));
        },
        dateClick: function(info) {
            if (flatpickrInstance) {
                flatpickrInstance.setDate(info.dateStr + ' 09:00');
            }
        },
        eventClick: function(info) {
            if (info.event.id == appointmentId) {
                openEditModal();
            }
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

function initEditForm() {
    document.getElementById('appointmentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitEditAppointment();
    });
}

function openEditModal() {
    document.getElementById('appointmentModal').classList.remove('hidden');
    
    // Set select2 values after modal is visible
    setTimeout(() => {
        $('#patient_id').val($('#patient_id option:selected').val()).trigger('change');
        $('#doctor_id').val($('#doctor_id option:selected').val()).trigger('change');
    }, 100);
}

function closeAppointmentModal() {
    document.getElementById('appointmentModal').classList.add('hidden');
}

function submitEditAppointment() {
    const formData = new FormData(document.getElementById('appointmentForm'));
    
    formData.append('_method', 'PUT');
    
    fetch(`/appointments/${appointmentId}`, {
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
                    return { success: true, message: 'Appointment updated successfully.' };
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
            showNotification(data.message, 'success');
            calendar.refetchEvents();
            setTimeout(() => {
                window.location.href = '/appointments';
            }, 1500);
        } else {
            showNotification('Error: ' + (data.message || 'Something went wrong'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while updating the appointment', 'error');
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