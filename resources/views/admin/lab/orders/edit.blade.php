@extends('admin.layout')

@section('title', 'Edit Investigation Order')
@section('page-title', 'Edit Investigation Order')
@section('page-description', 'Update laboratory test order')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('investigation-orders.update', $investigationOrder) }}" method="POST" id="order-form">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Patient <span class="text-red-500">*</span></label>
                <select id="patient_id" name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Patient</option>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ old('patient_id', $investigationOrder->patient_id) == $patient->id ? 'selected' : '' }}>
                            {{ $patient->name }} — {{ $patient->patient_no }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ordering Doctor <span class="text-red-500">*</span></label>
                <select id="doctor_id" name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Doctor</option>
                    @foreach($doctors as $doctor)
                        <option value="{{ $doctor->id }}" {{ old('doctor_id', $investigationOrder->doctor_id) == $doctor->id ? 'selected' : '' }}>
                            Dr. {{ $doctor->name }} — {{ $doctor->specialization }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Special Instructions</label>
                <textarea name="special_instructions" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">{{ old('special_instructions', $investigationOrder->special_instructions) }}</textarea>
            </div>
        </div>

        <div class="mb-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Investigations</h3>
                <button type="button" id="add-investigation-row" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-medical-blue bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors">
                    <i class="fas fa-plus mr-1"></i>Add Investigation
                </button>
            </div>

            <div class="overflow-x-auto border border-gray-200 rounded-lg">
                @php
                    $existingItems = old('items')
                        ? collect(old('items'))->map(fn($i) => (object)$i)
                        : $investigationOrder->items;
                @endphp
                <table class="w-full text-sm" id="items-table">
                    <thead class="bg-gray-50">
                        <tr class="text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <th class="px-4 py-3 text-left">Investigation</th>
                            <th class="px-4 py-3 text-center w-20">Qty</th>
                            <th class="px-4 py-3 text-center w-28">Priority</th>
                            <th class="px-4 py-3 text-center w-28">Location</th>
                            <th class="px-4 py-3 text-left">Clinical Notes</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body" data-row-index="{{ count($existingItems) }}">
                        @foreach($existingItems as $i => $item)
                        <tr class="item-row border-t border-gray-100">
                            <td class="px-4 py-2">
                                <select name="items[{{ $i }}][investigation_id]" class="investigation-select w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" required>
                                    <option value="">Select investigation...</option>
                                    @foreach($investigations->groupBy('category') as $category => $group)
                                        <optgroup label="{{ ucfirst($category ?: 'General') }}">
                                            @foreach($group as $inv)
                                                <option value="{{ $inv->id }}" {{ $item->investigation_id == $inv->id ? 'selected' : '' }}>
                                                    {{ $inv->name }} — {{ currency_symbol() }}{{ number_format($inv->price, 0) }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <input type="number" name="items[{{ $i }}][quantity]" value="{{ $item->quantity ?? 1 }}" min="1" max="99" class="w-full px-2 py-1.5 text-center border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" required>
                            </td>
                            <td class="px-4 py-2">
                                <select name="items[{{ $i }}][priority]" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" required>
                                    <option value="routine" {{ $item->priority === 'routine' ? 'selected' : '' }}>Routine</option>
                                    <option value="urgent"  {{ $item->priority === 'urgent'  ? 'selected' : '' }}>Urgent</option>
                                    <option value="stat"    {{ $item->priority === 'stat'    ? 'selected' : '' }}>STAT</option>
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <select name="items[{{ $i }}][test_location]" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" required>
                                    <option value="outdoor" {{ ($item->test_location ?? 'outdoor') === 'outdoor' ? 'selected' : '' }}>Outdoor</option>
                                    <option value="indoor"  {{ ($item->test_location ?? '') === 'indoor'  ? 'selected' : '' }}>Indoor</option>
                                </select>
                            </td>
                            <td class="px-4 py-2">
                                <input type="text" name="items[{{ $i }}][clinical_notes]" value="{{ $item->clinical_notes ?? '' }}" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" placeholder="Optional notes...">
                            </td>
                            <td class="px-4 py-2 text-center">
                                <button type="button" class="text-red-400 hover:text-red-600 transition-colors remove-row-btn remove-btn" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
            <a href="{{ route('investigation-orders.show', $investigationOrder) }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="bg-medical-blue text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Update Order
            </button>
        </div>
    </form>
</div>

@vite(['resources/js/investigation-orders-form.js'])
@endsection
