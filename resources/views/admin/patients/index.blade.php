@extends('admin.layout')

@section('title', 'Patients - Hospital Management System')
@section('page-title', 'Patients')
@section('page-description', 'Manage patient records and information')

@section('content')
<div id="patients-index"
     @can('create visits') data-can-create-visits="1" @endcan
     @can('edit patients') data-can-edit="1" @endcan
     @can('delete patients') data-can-delete="1" @endcan
     @if(\App\Models\ModuleRegistry::allows(\App\Models\Tenant::current(), auth()->user(), 'visits')) data-allow-visits="1" @endif
     @if(\App\Models\ModuleRegistry::allows(\App\Models\Tenant::current(), auth()->user(), 'emergency')) data-allow-emergency="1" @endif
     data-quick-register-url="{{ route('visits.quick-register') }}">
<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-base sm:text-lg font-semibold text-gray-800">Patient Records</h3>
    </div>
    <div class="flex items-center gap-2">
        @can('delete patients')
        <a href="{{ route('patients.trashed') }}" class="border border-gray-300 text-gray-700 px-4 py-2.5 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center min-h-[44px] text-sm sm:text-base">
            <i class="fas fa-trash mr-2"></i>
            Trash
        </a>
        @endcan
        @can('create patients')
        <a href="{{ route('patients.create') }}" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center min-h-[44px] text-sm sm:text-base">
            <i class="fas fa-plus mr-2"></i>
            Add New Patient
        </a>
        @endcan
    </div>
</div>

<table class="patients-table w-full invisible">
    <thead>
        <tr>
            <th>Patient Info</th>
            <th>Contact</th>
            <th>Emergency Contact</th>
            <th>Actions</th>
        </tr>
    </thead>
</table>
</div>
@vite(['resources/js/patients-index.js'])
@endsection
