@extends('admin.layout')

@section('title', 'Edit Investigation Order - Laboratory Information System')
@section('page-title', 'Edit Investigation Order')
@section('page-description', 'Update laboratory test order')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('lab-orders.update', $labOrder) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Patient</label>
                <select name="patient_id" id="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Patient</option>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ $labOrder->patient_id == $patient->id ? 'selected' : '' }}>{{ $patient->name }} - {{ $patient->phone }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Doctor</label>
                <select name="doctor_id" id="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Doctor</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}" {{ $labOrder->doctor_id == $doctor->id ? 'selected' : '' }}>Dr. {{ $doctor->name }} - {{ $doctor->specialization }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Investigation</label>
                <select name="lab_test_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Investigation</option>
                    @foreach($investigations as $investigation)
                        <option value="{{ $investigation->id }}" {{ $labOrder->lab_test_id == $investigation->id ? 'selected' : '' }}>{{ $investigation->name }} - ₨{{ number_format($investigation->price, 0) }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="routine" {{ $labOrder->priority == 'routine' ? 'selected' : '' }}>Routine</option>
                    <option value="urgent" {{ $labOrder->priority == 'urgent' ? 'selected' : '' }}>Urgent</option>
                    <option value="stat" {{ $labOrder->priority == 'stat' ? 'selected' : '' }}>STAT</option>
                </select>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Clinical Notes</label>
                <textarea name="clinical_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Clinical indication for test...">{{ $labOrder->clinical_notes }}</textarea>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Special Instructions</label>
                <textarea name="special_instructions" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Special handling or collection instructions...">{{ $labOrder->special_instructions }}</textarea>
            </div>
        </div>
        
        <div class="flex justify-end space-x-4 mt-6">
            <a href="{{ route('lab-orders.show', $labOrder) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Update Order
            </button>
        </div>
    </form>
</div>
@endsection

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<style>
.select2-container {
    width: 100% !important;
    box-sizing: border-box;
}
.select2-container--default .select2-selection--single {
    height: 42px;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    box-sizing: border-box;
    overflow: hidden;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 40px;
    padding-left: 12px;
    padding-right: 50px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.select2-container--default .select2-selection--single .select2-selection__clear {
    position: absolute;
    right: 28px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 16px;
    line-height: 1;
    width: 16px;
    height: 16px;
    text-align: center;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #6b7280 transparent transparent transparent;
    border-style: solid;
    border-width: 5px 4px 0 4px;
    height: 0;
    left: 50%;
    margin-left: -4px;
    margin-top: -2px;
    position: absolute;
    top: 50%;
    width: 0;
}
.select2-dropdown {
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #0066CC;
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #0066CC;
    box-shadow: 0 0 0 2px rgba(0, 102, 204, 0.2);
    outline: none;
}
.select2-container * {
    box-sizing: border-box;
}
</style>
<script>
$(document).ready(function() {
    $('#patient_id').select2({
        placeholder: 'Select Patient',
        allowClear: true,
        width: '100%'
    });
    
    $('#doctor_id').select2({
        placeholder: 'Select Doctor',
        allowClear: true,
        width: '100%'
    });
});
</script>
