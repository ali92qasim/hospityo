@extends('admin.layout')

@section('title', 'New Admission - Hospital Management System')
@section('page-title', 'New Admission')
@section('page-description', 'Register a new inpatient admission')

@push('styles')
@vite(['resources/css/visits-form.css'])
@endpush

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">New Admission</h3>
                <a href="{{ route('visits.index', ['visit_type' => 'ipd']) }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Admitted Patients
                </a>
            </div>
        </div>

        @include('admin.visits.create._form', ['visitType' => 'ipd'])
    </div>
</div>

@push('scripts')
@vite(['resources/js/visits-form.js'])
@endpush
@endsection
