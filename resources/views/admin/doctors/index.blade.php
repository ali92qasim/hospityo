@extends('admin.layout')

@section('title', 'Doctors - Hospital Management System')
@section('page-title', 'Doctors')
@section('page-description', 'Manage medical staff and doctors')

@section('content')
<div id="doctors-index"
     @can('edit doctors') data-can-edit="1" @endcan
     @can('delete doctors') data-can-delete="1" @endcan>
<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-lg font-semibold text-gray-800">Medical Staff</h3>
    </div>
    <div class="flex items-center gap-2">
        @can('restore doctors')
        <a href="{{ route('doctors.trashed') }}" class="border border-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors flex items-center">
            <i class="fas fa-trash mr-2"></i>
            Trash
        </a>
        @endcan
        @can('create doctors')
        <a href="{{ route('doctors.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
            <i class="fas fa-plus mr-2"></i>
            Add New Doctor
        </a>
        @endcan
    </div>
</div>

<table class="doctors-table w-full invisible">
    <thead>
        <tr>
            <th>Doctor Info</th>
            <th>Specialization</th>
            <th>Contact</th>
            <th>Schedule</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
</table>
</div>
@vite(['resources/js/doctors-index.js'])
@endsection
