/**
 * OT Calendar — FullCalendar integration for Operation Theatre scheduling.
 * Follows the exact same pattern as the working appointments-calendar.js.
 */

import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('ot-calendar');
    if (!calendarEl) return;

    var theatreFilter = document.getElementById('ot-theatre-filter');

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
            var theatreId = theatreFilter ? theatreFilter.value : '';
            var url = '/ot/calendar/events?start=' + info.startStr + '&end=' + info.endStr;
            if (theatreId) {
                url += '&theatre_id=' + theatreId;
            }

            fetch(url)
                .then(function(response) { return response.json(); })
                .then(function(data) { successCallback(data); })
                .catch(function(error) {
                    console.error('[OT Calendar] Error:', error);
                    failureCallback(error);
                });
        },

        eventClick: function(info) {
            var showUrl = info.event.extendedProps.showUrl;
            if (showUrl) {
                window.location.href = showUrl;
            }
        },

        dateClick: function(info) {
            window.location.href = '/ot/surgeries/create?date=' + info.dateStr;
        }
    });

    calendar.render();

    // Theatre filter
    if (theatreFilter) {
        theatreFilter.addEventListener('change', function() {
            calendar.refetchEvents();
        });
    }
});
