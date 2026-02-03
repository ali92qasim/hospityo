@extends('admin.layout')

@section('title', 'Doctors - Hospital Management System')
@section('page-title', 'Doctors')
@section('page-description', 'Manage medical staff and doctors')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Medical Staff</h3>
                <p class="text-sm text-gray-600">Total: {{ $doctors->total() }} doctors</p>
            </div>
            <a href="{{ route('doctors.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>
                Add New Doctor
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor Info</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Specialization</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Schedule</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($doctors as $doctor)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-medical-green rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user-md text-white"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $doctor->name }}</div>
                                <div class="text-sm text-gray-500">{{ $doctor->doctor_no }}</div>
                                <div class="text-xs text-gray-400">{{ $doctor->experience_years }} years exp.</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $doctor->specialization }}</div>
                        <div class="text-xs text-gray-500">{{ $doctor->qualification }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $doctor->phone }}</div>
                        <div class="text-xs text-gray-500">{{ $doctor->email }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">{{ $doctor->shift_start }} - {{ $doctor->shift_end }}</div>
                        <div class="text-xs text-gray-500">${{ number_format($doctor->consultation_fee, 2) }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $doctor->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($doctor->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium space-x-2">
                        <a href="{{ route('doctors.show', $doctor) }}" class="text-medical-blue hover:text-blue-700">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('doctors.edit', $doctor) }}" class="text-medical-green hover:text-green-700">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('doctors.destroy', $doctor) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-user-md text-4xl mb-4 text-gray-300"></i>
                        <p>No doctors found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($doctors->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $doctors->links() }}
    </div>
    @endif
</div>
@endsection