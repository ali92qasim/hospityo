@php
    $disabled = $disabled ?? false;
@endphp

<div
    data-landmark="workflow-doctor-assignment"
    class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500 {{ $disabled ? 'opacity-50 pointer-events-none' : '' }}"
>
    <h4 class="text-lg font-medium text-gray-800 mb-4">
        <i class="fas fa-user-md text-green-600 mr-2"></i>Doctor Assignment
    </h4>

    @if(!$visit->doctor_id || ($visit->doctor_id && $visit->status !== 'completed'))
        <form action="{{ route('visits.assign-doctor', $visit) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ $visit->doctor_id ? 'Change Doctor' : 'Assign Doctor' }}
                </label>
                <select name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Doctor</option>
                    @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}" {{ $visit->doctor_id == $doctor->id ? 'selected' : '' }}>
                        Dr. {{ $doctor->name }} - {{ $doctor->specialization }}
                    </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                <i class="fas fa-user-md mr-2"></i>{{ $visit->doctor_id ? 'Update Doctor' : 'Assign Doctor' }}
            </button>
        </form>
    @else
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                    <div>
                        <p class="font-medium text-green-800">Dr. {{ $visit->doctor->name }}</p>
                        <p class="text-sm text-green-600">{{ $visit->doctor->specialization }}</p>
                    </div>
                </div>
                <span class="text-xs text-green-600 bg-green-100 px-2 py-1 rounded">
                    <i class="fas fa-lock mr-1"></i>Locked
                </span>
            </div>
        </div>
    @endif
</div>
