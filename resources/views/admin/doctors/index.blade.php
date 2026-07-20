@extends('admin.layout')

@section('title', 'Doctors - Hospital Management System')
@section('page-title', 'Doctors')
@section('page-description', 'Manage medical staff and doctors')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h3 class="text-lg font-semibold text-gray-800">Medical Staff</h3>
    </div>
    <a href="{{ route('doctors.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
        <i class="fas fa-plus mr-2"></i>
        Add New Doctor
    </a>
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
@vite(['resources/js/doctors-index.js'])
@endsection
