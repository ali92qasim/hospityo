@extends('admin.layout')

@section('title', 'Create Prescription')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Create Prescription</h1>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('prescriptions.store') }}" id="prescription-form">
        @csrf
        
        @if($visit)
            <input type="hidden" name="visit_id" value="{{ $visit->id }}">
            
            <!-- Patient & Visit Info -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Patient</label>
                        <div class="text-sm text-gray-900">{{ $visit->patient->name }}</div>
                        <div class="text-xs text-gray-500">{{ $visit->patient->phone }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Doctor</label>
                        <div class="text-sm text-gray-900">Dr. {{ $visit->doctor->name }}</div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Visit</label>
                        <div class="text-sm text-gray-900">{{ $visit->visit_no }}</div>
                        <div class="text-xs text-gray-500">{{ $visit->visit_datetime->format('M d, Y') }}</div>
                    </div>
                </div>
            </div>
        @else
            <div class="mb-6">
                <label for="visit_id" class="block text-sm font-medium text-gray-700 mb-2">Select Visit</label>
                <select name="visit_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Visit</option>
                    <!-- Add visits here -->
                </select>
            </div>
        @endif

        <!-- Medicine Items -->
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-800">Prescription Items</h3>
                <button type="button" onclick="addMedicineRow()" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                    <i class="fas fa-plus mr-1"></i>Add Medicine
                </button>
            </div>
            
            <div id="medicine-rows">
                <div class="medicine-row border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                            <select name="medicines[0][medicine_id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                                <option value="">Select Medicine</option>
                                @foreach($medicines as $medicine)
                                    <option value="{{ $medicine->id }}" data-price="{{ $medicine->unit_price }}">
                                        {{ $medicine->name }} - {{ $medicine->strength }} (Stock: {{ $medicine->stock_quantity }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <input type="number" name="medicines[0][quantity]" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                            <input type="text" name="medicines[0][dosage]" placeholder="e.g., 1 tablet" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                            <select name="medicines[0][frequency]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                                <option value="">Select</option>
                                <option value="Once daily">Once daily</option>
                                <option value="Twice daily">Twice daily</option>
                                <option value="Three times daily">Three times daily</option>
                                <option value="Four times daily">Four times daily</option>
                                <option value="As needed">As needed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                            <input type="text" name="medicines[0][duration]" placeholder="e.g., 7 days" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
                        <input type="text" name="medicines[0][instructions]" placeholder="Special instructions" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="mb-6">
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Prescription Notes</label>
            <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Additional notes or instructions"></textarea>
        </div>

        <div class="flex justify-end space-x-4">
            <a href="{{ route('prescriptions.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Create Prescription
            </button>
        </div>
    </form>
</div>

<script>
let medicineRowIndex = 1;

function addMedicineRow() {
    const medicineRows = document.getElementById('medicine-rows');
    const newRow = document.createElement('div');
    newRow.className = 'medicine-row border border-gray-200 rounded-lg p-4 mb-4';
    newRow.innerHTML = `
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-gray-700">Medicine ${medicineRowIndex + 1}</span>
            <button type="button" onclick="removeMedicineRow(this)" class="text-red-600 hover:text-red-700">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                <select name="medicines[${medicineRowIndex}][medicine_id]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Medicine</option>
                    @foreach($medicines as $medicine)
                        <option value="{{ $medicine->id }}" data-price="{{ $medicine->unit_price }}">
                            {{ $medicine->name }} - {{ $medicine->strength }} (Stock: {{ $medicine->stock_quantity }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                <input type="number" name="medicines[${medicineRowIndex}][quantity]" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                <input type="text" name="medicines[${medicineRowIndex}][dosage]" placeholder="e.g., 1 tablet" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                <select name="medicines[${medicineRowIndex}][frequency]" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select</option>
                    <option value="Once daily">Once daily</option>
                    <option value="Twice daily">Twice daily</option>
                    <option value="Three times daily">Three times daily</option>
                    <option value="Four times daily">Four times daily</option>
                    <option value="As needed">As needed</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Duration</label>
                <input type="text" name="medicines[${medicineRowIndex}][duration]" placeholder="e.g., 7 days" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
            <input type="text" name="medicines[${medicineRowIndex}][instructions]" placeholder="Special instructions" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
        </div>
    `;
    medicineRows.appendChild(newRow);
    medicineRowIndex++;
}

function removeMedicineRow(button) {
    button.closest('.medicine-row').remove();
}
</script>
@endsection