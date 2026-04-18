@extends('admin.layout')

@section('title', 'Create Bill')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Create Bill</h1>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('bills.store') }}" id="billForm">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">Patient</label>
                <select id="patient_id" name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <option value="">Select Patient</option>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->id }}">{{ $patient->name }} - {{ $patient->phone }}</option>
                    @endforeach
                </select>
                @error('patient_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="bill_type" class="block text-sm font-medium text-gray-700 mb-2">Bill Type</label>
                <select id="bill_type" name="bill_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <option value="">Select Type</option>
                    <option value="opd">OPD</option>
                    <option value="ipd">IPD</option>
                    <option value="emergency">Emergency</option>
                    <option value="investigation">Investigation</option>
                    <option value="pharmacy">Pharmacy</option>
                </select>
                @error('bill_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="bill_date" class="block text-sm font-medium text-gray-700 mb-2">Bill Date</label>
                <input type="text" id="bill_date" name="bill_date" value="{{ date('Y-m-d') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                @error('bill_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Bill Items</h3>
            <div id="billItems">
                <div class="bill-item border border-gray-200 rounded-lg p-4 mb-3">
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Item Type</label>
                            <select class="item-type-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="service">Service</option>
                                <option value="investigation">Investigation</option>
                            </select>
                        </div>
                        <div class="col-span-3 item-service-col">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Service</label>
                            <select name="items[0][service_id]" class="service-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">Select Service</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}" data-price="{{ $service->price }}" data-name="{{ $service->name }}">{{ $service->name }} - {{ currency_symbol() }}{{ number_format($service->price, 0) }}</option>
                                @endforeach
                            </select>
                            <input type="hidden" name="items[0][investigation_id]" class="investigation-id-input" value="">
                        </div>
                        <div class="col-span-3 item-investigation-col hidden">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Investigation</label>
                            <select class="investigation-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">Select Investigation</option>
                                @foreach($investigations->groupBy('category') as $category => $items)
                                    <optgroup label="{{ ucwords(str_replace('-', ' ', $category)) }}">
                                        @foreach($items as $inv)
                                            <option value="{{ $inv->id }}" data-price="{{ $inv->price }}" data-name="{{ $inv->name }}">{{ $inv->name }} - {{ currency_symbol() }}{{ number_format($inv->price, 0) }}</option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                            <input type="text" name="items[0][description]" placeholder="Description" class="description-input w-full px-2 py-2 border border-gray-300 rounded-lg text-sm" required>
                        </div>
                        <div class="col-span-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Qty</label>
                            <input type="number" name="items[0][quantity]" value="1" min="1" class="quantity w-full px-2 py-2 border border-gray-300 rounded-lg text-sm text-center" required>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Price ({{ currency_symbol() }})</label>
                            <input type="number" name="items[0][unit_price]" step="0.01" class="unit-price w-full px-2 py-2 border border-gray-300 rounded-lg text-sm" required>
                        </div>
                        <div class="col-span-2 flex items-end gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Total</label>
                                <span class="total-display block py-2 text-sm font-medium text-gray-700">0.00</span>
                            </div>
                            <button type="button" class="remove-item mb-1 p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Remove">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <button type="button" id="addItem" class="text-medical-blue hover:text-blue-700 text-sm font-medium">
                <i class="fas fa-plus mr-1"></i>Add Item
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label for="tax_amount" class="block text-sm font-medium text-gray-700 mb-2">Tax Amount</label>
                <input type="number" id="tax_amount" name="tax_amount" step="0.01" value="0" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
            </div>
            <div>
                <label for="discount_amount" class="block text-sm font-medium text-gray-700 mb-2">Discount Amount</label>
                <input type="number" id="discount_amount" name="discount_amount" step="0.01" value="0" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Total Amount</label>
                <div id="totalAmount" class="text-2xl font-bold text-medical-blue">{{ currency_symbol() }}0.00</div>
            </div>
        </div>

        <div class="mb-6">
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea id="notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"></textarea>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('bills.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Cancel</a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">Create Bill</button>
        </div>
    </form>
</div>

@vite(['resources/js/bills-form.js'])
@endsection
