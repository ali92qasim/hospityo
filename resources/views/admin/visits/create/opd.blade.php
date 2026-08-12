@extends('admin.layout')

@section('title', 'New OPD Patient - Hospital Management System')
@section('page-title', 'New OPD Patient')
@section('page-description', 'Register a new outpatient visit')

@push('styles')
@vite(['resources/css/visits-form.css'])
@endpush

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">New OPD Patient</h3>
                <a href="{{ route('visits.index', ['visit_type' => 'opd']) }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to OPD
                </a>
            </div>
        </div>

        @include('admin.visits.create._form', ['visitType' => 'opd'])
    </div>
</div>

@push('scripts')
@vite(['resources/js/visits-form.js'])
@endpush
@endsection
