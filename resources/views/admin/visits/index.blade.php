@extends('admin.layout')

@section('title', 'Visits - Hospital Management System')
@section('page-title', 'Patient Visits')
@section('page-description', 'Manage patient visits workflow')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Patient Visits</h3>
                <p class="text-sm text-gray-600">Total: {{ $visits->total() }} visits</p>
            </div>
            <div class="flex items-center space-x-3">
                <!-- Search Field -->
                <div class="relative">
                    <input type="text" 
                           id="search-visits" 
                           placeholder="Search visits, patients..." 
                           value="{{ request('search') }}"
                           class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <div id="search-clear" class="absolute inset-y-0 right-0 pr-3 flex items-center cursor-pointer {{ request('search') ? '' : 'hidden' }}">
                        <i class="fas fa-times text-gray-400 hover:text-gray-600"></i>
                    </div>
                </div>
                <a href="{{ route('visits.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Register Visit
                </a>
            </div>
        </div>

        <!-- Status Filters -->
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('visits.index') }}" class="px-3 py-1 text-sm rounded-full {{ !request('status') ? 'bg-gray-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                All
            </a>
            <a href="{{ route('visits.index', ['status' => 'registered']) }}" class="px-3 py-1 text-sm rounded-full {{ request('status') == 'registered' ? 'bg-blue-500 text-white' : 'bg-blue-100 text-blue-700 hover:bg-blue-200' }}">
                Registered
            </a>
            <a href="{{ route('visits.index', ['status' => 'vitals_recorded']) }}" class="px-3 py-1 text-sm rounded-full {{ request('status') == 'vitals_recorded' ? 'bg-green-500 text-white' : 'bg-green-100 text-green-700 hover:bg-green-200' }}">
                Vitals Recorded
            </a>
            <a href="{{ route('visits.index', ['status' => 'with_doctor']) }}" class="px-3 py-1 text-sm rounded-full {{ request('status') == 'with_doctor' ? 'bg-purple-500 text-white' : 'bg-purple-100 text-purple-700 hover:bg-purple-200' }}">
                With Doctor
            </a>
            <a href="{{ route('visits.index', ['status' => 'completed']) }}" class="px-3 py-1 text-sm rounded-full {{ request('status') == 'completed' ? 'bg-gray-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Completed
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Visit Info</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($visits as $visit)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            @php
                                $typeColors = [
                                    'opd' => 'bg-blue-500',
                                    'ipd' => 'bg-green-500',
                                    'emergency' => 'bg-red-500'
                                ];
                            @endphp
                            <div class="w-10 h-10 {{ $typeColors[$visit->visit_type] }} rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-clipboard-list text-white"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $visit->visit_no }}</div>
                                <div class="text-sm text-gray-500">{{ strtoupper($visit->visit_type) }}</div>
                                <div class="text-xs text-gray-400">{{ $visit->visit_datetime->format('M d, Y h:i A') }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $visit->patient->name }}</div>
                        <div class="text-xs text-gray-500">{{ $visit->patient->patient_no }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($visit->doctor)
                            <div class="text-sm text-gray-900">Dr. {{ $visit->doctor->name }}</div>
                            <div class="text-xs text-gray-500">{{ $visit->doctor->department->name ?? 'No Department' }}</div>
                        @else
                            <span class="text-sm text-gray-400">Not assigned</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $statusColors = [
                                'registered' => 'bg-blue-100 text-blue-800',
                                'vitals_recorded' => 'bg-green-100 text-green-800',
                                'with_doctor' => 'bg-purple-100 text-purple-800',
                                'tests_ordered' => 'bg-yellow-100 text-yellow-800',
                                'tests_completed' => 'bg-orange-100 text-orange-800',
                                'completed' => 'bg-gray-100 text-gray-800',
                                'admitted' => 'bg-purple-100 text-purple-800',
                                'triaged' => 'bg-red-100 text-red-800',
                                'discharged' => 'bg-orange-100 text-orange-800'
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $statusColors[$visit->status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $visit->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <a href="{{ route('visits.workflow', $visit) }}" class="text-medical-blue hover:text-blue-700">
                            <i class="fas fa-tasks mr-1"></i>Workflow
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-clipboard-list text-4xl mb-4 text-gray-300"></i>
                        <p>No visits found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($visits->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $visits->links() }}
    </div>
    @endif
</div>
@endsection