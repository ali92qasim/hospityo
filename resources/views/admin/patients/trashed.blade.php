@extends('admin.layout')

@section('title', 'Trashed Patients - Hospital Management System')
@section('page-title', 'Trashed Patients')
@section('page-description', 'Soft-deleted patient records')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Trashed Patients</h3>
    @can('view patients')
        <a href="{{ route('patients.index') }}" class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 min-h-[44px] flex items-center text-sm sm:text-base">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Patients
        </a>
    @endcan
</div>

<div class="bg-white rounded-lg shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-600">
            <tr>
                <th class="px-4 py-3">Patient No</th>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Phone</th>
                <th class="px-4 py-3">Deleted At</th>
                @can('restore patients')
                    <th class="px-4 py-3">Actions</th>
                @endcan
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($patients as $patient)
                <tr>
                    <td class="px-4 py-3">{{ $patient->patient_no }}</td>
                    <td class="px-4 py-3">{{ $patient->name }}</td>
                    <td class="px-4 py-3">{{ $patient->phone }}</td>
                    <td class="px-4 py-3">{{ optional($patient->deleted_at)->format('Y-m-d H:i') }}</td>
                    @can('restore patients')
                        <td class="px-4 py-3">
                            <form action="{{ route('patients.restore', $patient->id) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 bg-medical-green text-white rounded-lg hover:bg-green-700">
                                    <i class="fas fa-undo mr-1"></i>
                                    Restore
                                </button>
                            </form>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr>
                    <td colspan="{{ auth()->user()?->can('restore patients') ? 5 : 4 }}" class="px-4 py-6 text-center text-gray-500">No trashed patients.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $patients->links() }}
</div>
@endsection
