@extends('admin.layout')

@section('title', 'Visits - Hospital Management System')
@section('page-title', 'Patient Visits')
@section('page-description', 'Manage patient visits workflow')

@push('styles')
@vite(['resources/css/visits-form.css'])
@endpush

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center mb-4">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">Patient Visits</h3>
        </div>
        <div class="flex items-center space-x-3">
            <div class="relative">
                <input type="text"
                       id="search-visits"
                       placeholder="Search visits, patients..."
                       class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <div id="search-clear" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer hidden">
                    <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                </div>
            </div>
            <a href="{{ route('visits.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Register Visit
            </a>
        </div>
    </div>

    <div class="mb-4">
        <div class="flex items-center space-x-2 mb-3">
            <i class="fas fa-calendar text-gray-500"></i>
            <span class="text-sm font-medium text-gray-700">Date Filter:</span>
        </div>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-date-filter data-filter-value="" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-gray-800 text-white">
                All Time
            </button>
            <button type="button" data-date-filter data-filter-value="today" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200">
                Today
            </button>
            <button type="button" data-date-filter data-filter-value="yesterday" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200">
                Yesterday
            </button>
            <button type="button" data-date-filter data-filter-value="this_week" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-green-100 text-green-700 hover:bg-green-200">
                This Week
            </button>
            <button type="button" data-date-filter data-filter-value="last_week" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-green-100 text-green-700 hover:bg-green-200">
                Last Week
            </button>
            <button type="button" data-date-filter data-filter-value="this_month" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-purple-100 text-purple-700 hover:bg-purple-200">
                This Month
            </button>
            <button type="button" data-date-filter data-filter-value="last_month" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-purple-100 text-purple-700 hover:bg-purple-200">
                Last Month
            </button>
            <button type="button" data-date-filter data-filter-value="custom" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-orange-100 text-orange-700 hover:bg-orange-200">
                <i class="fas fa-calendar-alt mr-1"></i>Custom Range
            </button>
        </div>

        <div id="custom-date-range" class="hidden mt-3 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-end gap-3 flex-wrap">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="date" name="start_date"
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="date" name="end_date"
                           class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue text-sm">
                </div>
                <button type="button" id="apply-custom-date-range" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                    <i class="fas fa-filter mr-1"></i>Apply
                </button>
                <button type="button" id="clear-custom-date-range" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm">
                    <i class="fas fa-times mr-1"></i>Clear
                </button>
            </div>
        </div>
    </div>

    <div class="flex items-center space-x-2 mb-3">
        <i class="fas fa-filter text-gray-500"></i>
        <span class="text-sm font-medium text-gray-700">Status Filter:</span>
    </div>
    <div class="flex flex-wrap gap-2 mb-6">
        <button type="button" data-status-filter data-filter-value="" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-gray-800 text-white">
            All
        </button>
        <button type="button" data-status-filter data-filter-value="registered" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200">
            Registered
        </button>
        <button type="button" data-status-filter data-filter-value="vitals_recorded" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-green-100 text-green-700 hover:bg-green-200">
            Vitals Recorded
        </button>
        <button type="button" data-status-filter data-filter-value="with_doctor" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-purple-100 text-purple-700 hover:bg-purple-200">
            With Doctor
        </button>
        <button type="button" data-status-filter data-filter-value="completed" class="visit-filter-btn px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200">
            Completed
        </button>
    </div>
</div>

<table class="visits-table w-full invisible">
    <thead>
        <tr>
            <th>Visit Info</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
</table>

@push('scripts')
@vite(['resources/js/visits-index.js'])
@endpush
@endsection
