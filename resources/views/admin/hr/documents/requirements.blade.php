@extends('admin.layout')

@section('title', 'Document Requirements')
@section('page-title', 'Document Requirements')

@section('content')
<!-- Back Link -->
<div class="mb-4">
    <a href="{{ route('hr.documents.index') }}" class="text-medical-blue hover:text-blue-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i>Back to Documents
    </a>
</div>

<!-- Requirements Table -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Document Requirements</h3>
                <p class="text-sm text-gray-600">Define which documents are required for employees</p>
            </div>
            <a href="{{ route('hr.documents.create-requirement') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Add Requirement
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Label</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicable To</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Mandatory</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Has Expiry</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Reminder Days</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($requirements as $requirement)
                <tr class="hover:bg-gray-50">
                    {{-- Label --}}
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $requirement->label }}</div>
                        @if($requirement->description)
                            <div class="text-xs text-gray-500">{{ $requirement->description }}</div>
                        @endif
                    </td>

                    {{-- Document Type --}}
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800 font-mono">{{ $requirement->document_type }}</span>
                    </td>

                    {{-- Applicable To --}}
                    <td class="px-6 py-4">
                        @php
                            $applicableBadges = [
                                'all' => 'bg-blue-100 text-blue-800',
                                'medical' => 'bg-green-100 text-green-800',
                                'nursing' => 'bg-teal-100 text-teal-800',
                                'admin' => 'bg-purple-100 text-purple-800',
                                'technical' => 'bg-orange-100 text-orange-800',
                                'support' => 'bg-gray-100 text-gray-800',
                            ];
                        @endphp
                        @if(str_starts_with($requirement->applicable_to, 'designation:'))
                            @php
                                $desigId = (int) str_replace('designation:', '', $requirement->applicable_to);
                                $desig = \App\Models\Designation::find($desigId);
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full bg-indigo-100 text-indigo-800">
                                Designation: {{ $desig->name ?? $desigId }}
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full {{ $applicableBadges[$requirement->applicable_to] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($requirement->applicable_to) }}
                            </span>
                        @endif
                    </td>

                    {{-- Mandatory --}}
                    <td class="px-6 py-4 text-center">
                        @if($requirement->is_mandatory)
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-check mr-1"></i>Yes
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">
                                No
                            </span>
                        @endif
                    </td>

                    {{-- Has Expiry --}}
                    <td class="px-6 py-4 text-center">
                        @if($requirement->has_expiry)
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-check mr-1"></i>Yes
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600">
                                No
                            </span>
                        @endif
                    </td>

                    {{-- Reminder Days --}}
                    <td class="px-6 py-4 text-sm text-gray-900 text-center">
                        {{ $requirement->expiry_reminder_days ?? '—' }}
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-4 text-sm font-medium">
                        <form action="{{ route('hr.documents.destroy-requirement', $requirement) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this requirement?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-clipboard-list text-4xl mb-4 text-gray-300"></i>
                        <p>No document requirements defined</p>
                        <a href="{{ route('hr.documents.create-requirement') }}" class="text-medical-blue hover:text-blue-700 text-sm mt-2 inline-block">
                            <i class="fas fa-plus mr-1"></i>Add your first requirement
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
