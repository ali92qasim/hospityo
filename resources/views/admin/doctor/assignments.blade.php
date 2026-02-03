@extends('admin.layout')

@section('title', 'My Assignments - Hospityo')
@section('page-title', 'My Assignments')
@section('page-description', 'All assigned patients')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-800">All Assigned Patients</h3>
            <a href="{{ route('dashboard') }}" class="text-medical-blue hover:text-blue-700">
                <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
            </a>
        </div>
    </div>

    @if($assignedPatients->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit Time</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($assignedPatients as $visit)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $visit->patient->name }}</div>
                                <div class="text-sm text-gray-500">{{ $visit->patient->patient_no }} • {{ $visit->patient->phone }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                @if($visit->visit_type === 'opd') bg-blue-100 text-blue-800
                                @elseif($visit->visit_type === 'ipd') bg-purple-100 text-purple-800
                                @else bg-red-100 text-red-800 @endif">
                                {{ strtoupper($visit->visit_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                {{ ucfirst(str_replace('_', ' ', $visit->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $visit->visit_datetime->format('M d, Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('visits.workflow', $visit) }}" class="text-medical-blue hover:text-blue-700">
                                    <i class="fas fa-stethoscope"></i> Consult
                                </a>
                                <form action="{{ route('visits.check-patient', $visit) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-green-600 hover:text-green-700 ml-2" 
                                            onclick="return confirm('Mark this patient as checked?')">
                                        <i class="fas fa-check"></i> Check
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $assignedPatients->links() }}
        </div>
    @else
        <div class="text-center py-12">
            <i class="fas fa-user-friends text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No patients assigned</h3>
            <p class="text-gray-500">You don't have any assigned patients at the moment.</p>
        </div>
    @endif
</div>
@endsection