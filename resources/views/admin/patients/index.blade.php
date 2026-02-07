@extends('admin.layout')

@section('title', 'Patients - Hospital Management System')
@section('page-title', 'Patients')
@section('page-description', 'Manage patient records and information')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Patient Records</h3>
                <p class="text-sm text-gray-600">Total: {{ $patients->total() }} patients</p>
            </div>
            <a href="{{ route('patients.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Add New Patient
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Info</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Emergency Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($patients as $patient)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $patient->name }}</div>
                                <div class="text-sm text-gray-500">{{ $patient->patient_no }}</div>
                                <div class="text-xs text-gray-400">{{ ucfirst($patient->gender) }}, {{ $patient->age }} years</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $patient->phone }}</div>
                        @if($patient->marital_status)
                            <div class="text-xs text-gray-500">{{ ucfirst($patient->marital_status) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $patient->emergency_name }}</div>
                        <div class="text-xs text-gray-500">{{ $patient->emergency_phone }} ({{ $patient->emergency_relation }})</div>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium space-x-2">
                        <a href="{{ route('patients.show', $patient) }}" class="text-medical-blue hover:text-blue-700" title="View Details">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('patients.history', $patient) }}" class="text-purple-600 hover:text-purple-700" title="Patient History">
                            <i class="fas fa-history"></i>
                        </a>
                        <a href="{{ route('patients.edit', $patient) }}" class="text-medical-green hover:text-green-700" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-user-injured text-4xl mb-4 text-gray-300"></i>
                        <p>No patients found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($patients->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $patients->links() }}
    </div>
    @endif
</div>
@endsection