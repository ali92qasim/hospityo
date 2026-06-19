@extends('admin.layout')

@section('title', 'OT Schedule Calendar')
@section('page-title', 'OT Schedule')
@section('page-description', 'Visual calendar of all scheduled surgeries')

@push('styles')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />
<style>
    .fc-event { cursor: pointer !important; border-radius: 4px !important; font-size: 0.75rem !important; }
    .fc-event:hover { opacity: 0.85; }
    .fc-event-pill { color: #fff; font-weight: 500; padding: 2px 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fc-daygrid-event-dot { display: none !important; }
    .fc-event-time { display: none !important; }
    .fc .fc-button-primary { background-color: #0066CC !important; border-color: #0066CC !important; }
    .fc .fc-button-primary:hover { background-color: #0052a3 !important; }
    .fc-daygrid-day { cursor: pointer; }
    .fc-daygrid-day:hover { background-color: #f0f7ff !important; }
</style>
@endpush

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">OT Schedule Calendar</h3>
                <p class="text-sm text-gray-600">Click a date to schedule surgery or click an event to view details</p>
            </div>
            <div class="flex items-center gap-3">
                <select id="ot-theatre-filter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue">
                    <option value="">All Theatres</option>
                    @foreach($theatres as $theatre)
                        <option value="{{ $theatre->id }}">{{ $theatre->name }}</option>
                    @endforeach
                </select>
                <a href="{{ route('ot.surgeries.index') }}" class="px-3 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                    <i class="fas fa-list mr-1"></i>List
                </a>
                <a href="{{ route('ot.surgeries.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-plus mr-2"></i>Schedule
                </a>
            </div>
        </div>
    </div>

    <div class="px-6 pt-4 pb-2">
        <div class="flex flex-wrap gap-4 items-center text-xs text-gray-600">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block bg-blue-500"></span>Scheduled</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block bg-yellow-500"></span>In Progress</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block bg-green-500"></span>Completed</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block bg-red-500"></span>Cancelled</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full inline-block bg-orange-500"></span>Postponed</span>
        </div>
    </div>

    <div class="p-6">
        <div id="ot-calendar"></div>
    </div>
</div>

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('ot-calendar');
    if (!calendarEl) return;

    var theatreFilter = document.getElementById('ot-theatre-filter');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: false,
        selectable: true,
        dayMaxEvents: true,
        weekends: true,

        eventContent: function(arg) {
            var props = arg.event.extendedProps;
            return { html: '<div class="fc-event-pill">' + (props.patient || arg.event.title) + '</div>' };
        },

        events: function(info, successCallback, failureCallback) {
            var url = '/ot/calendar/events?start=' + info.startStr + '&end=' + info.endStr;
            if (theatreFilter && theatreFilter.value) {
                url += '&theatre_id=' + theatreFilter.value;
            }
            fetch(url)
                .then(function(r) { return r.json(); })
                .then(function(data) { successCallback(data); })
                .catch(function(err) {
                    console.error('OT Calendar error:', err);
                    successCallback([]);
                });
        },

        eventClick: function(info) {
            var url = info.event.extendedProps.showUrl;
            if (url) window.location.href = url;
        },

        dateClick: function(info) {
            window.location.href = '/ot/surgeries/create?date=' + info.dateStr;
        }
    });

    calendar.render();

    if (theatreFilter) {
        theatreFilter.addEventListener('change', function() {
            calendar.refetchEvents();
        });
    }
});
</script>
@endpush
@endsection
