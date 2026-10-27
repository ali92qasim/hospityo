@extends('admin.layout')

@section('title', 'Trashed Doctors - Hospital Management System')
@section('page-title', 'Trashed Doctors')
@section('page-description', 'Soft-deleted doctor records')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h3 class="text-base sm:text-lg font-semibold text-gray-800">Trashed Doctors</h3>
    @can('view doctors')
        <a href="{{ route('doctors.index') }}" class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 min-h-[44px] flex items-center text-sm sm:text-base">
            <i class="fas fa-arrow-left mr-2"></i>
            Back to Doctors
        </a>
    @endcan
</div>

<div class="bg-white rounded-lg shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-left text-gray-600">
            <tr>
                <th class="px-4 py-3">Doctor No</th>
                <th class="px-4 py-3">Name</th>
                <th class="px-4 py-3">Specialization</th>
                <th class="px-4 py-3">Deleted At</th>
                @can('restore doctors')
                    <th class="px-4 py-3">Actions</th>
                @endcan
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($doctors as $doctor)
                <tr>
                    <td class="px-4 py-3">{{ $doctor->doctor_no }}</td>
                    <td class="px-4 py-3">{{ $doctor->name }}</td>
                    <td class="px-4 py-3">{{ $doctor->specialization }}</td>
                    <td class="px-4 py-3">{{ optional($doctor->deleted_at)->format('Y-m-d H:i') }}</td>
                    @can('restore doctors')
                        <td class="px-4 py-3">
                            <form action="{{ route('doctors.restore', $doctor->id) }}" method="POST" class="inline">
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
                    <td colspan="{{ auth()->user()?->can('restore doctors') ? 5 : 4 }}" class="px-4 py-6 text-center text-gray-500">No trashed doctors.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $doctors->links() }}
</div>
@endsection
