@extends('admin.layout')

@section('title', 'Edit Bill')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Bill #{{ $bill->bill_number }}</h1>
</div>

@if($bill->paid_amount > 0)
<div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3" id="payment-warning-banner">
    <i class="fas fa-exclamation-triangle text-amber-500 mt-0.5"></i>
    <div>
        <p class="text-sm font-medium text-amber-800">This bill has existing payments</p>
        <p class="text-sm text-amber-700">
            <strong>{{ format_currency($bill->paid_amount) }}</strong> has already been paid.
            If you reduce the total below the paid amount (e.g., by adding a discount), the excess will be
            recorded as a patient credit balance. Accounting entries will be automatically reconciled.
        </p>
    </div>
</div>
@endif

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('bills.update', $bill) }}" id="billForm">
        @csrf @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">Patient</label>
                <select id="patient_id" name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    @foreach($patients as $patient)
                        <option value="{{ $patient->id }}" {{ $bill->patient_id == $patient->id ? 'selected' : '' }}>
                            {{ $patient->name }} - {{ $patient->phone }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="bill_type" class="block text-sm font-medium text-gray-700 mb-2">Bill Type</label>
                <select id="bill_type" name="bill_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <option value="opd" {{ $bill->bill_type == 'opd' ? 'selected' : '' }}>OPD</option>
                    <option value="ipd" {{ $bill->bill_type == 'ipd' ? 'selected' : '' }}>IPD</option>
                    <option value="emergency" {{ $bill->bill_type == 'emergency' ? 'selected' : '' }}>Emergency</option>
                    <option value="lab" {{ $bill->bill_type == 'lab' ? 'selected' : '' }}>Lab</option>
                    <option value="pharmacy" {{ $bill->bill_type == 'pharmacy' ? 'selected' : '' }}>Pharmacy</option>
                </select>
            </div>

            <div>
                <label for="bill_date" class="block text-sm font-medium text-gray-700 mb-2">Bill Date</label>
                <input type="date" id="bill_date" name="bill_date" value="{{ $bill->bill_date->format('Y-m-d') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Bill Items</h3>
            <div id="billItems">
                @foreach($bill->billItems as $index => $item)
                <div class="bill-item grid grid-cols-12 gap-3 mb-3">
                    <div class="col-span-4">
                        <select name="items[{{ $index }}][service_id]" class="service-select w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Select Service</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" data-price="{{ $service->price }}" 
                                        {{ $item->service_id == $service->id ? 'selected' : '' }}>
                                    {{ $service->name }} - {{ currency_symbol() }}{{ $service->price }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-span-3">
                        <input type="text" name="items[{{ $index }}][description]" value="{{ $item->description }}" 
                               placeholder="Description" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                    <div class="col-span-2">
                        <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item->quantity }}" 
                               placeholder="Qty" min="1" class="quantity w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                    <div class="col-span-2">
                        <input type="number" name="items[{{ $index }}][unit_price]" value="{{ $item->unit_price }}" 
                               placeholder="Price" step="0.01" class="unit-price w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                    <div class="col-span-1">
                        <button type="button" class="remove-item bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600">×</button>
                    </div>
                </div>
                @endforeach
            </div>
            <button type="button" id="addItem" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">Add Item</button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label for="tax_amount" class="block text-sm font-medium text-gray-700 mb-2">Tax Amount</label>
                <input type="number" id="tax_amount" name="tax_amount" step="0.01" value="{{ $bill->tax_amount }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Discount</label>

                @php $discountType = $bill->discount_type ?? 'fixed'; @endphp

                {{-- Type + value on one row --}}
                <div class="flex rounded-lg border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-medical-blue focus-within:border-transparent">
                    {{-- Type selector --}}
                    <select id="discount_type_select" name="discount_type"
                            class="px-3 py-2 bg-gray-50 border-r border-gray-300 text-sm text-gray-700 focus:outline-none cursor-pointer">
                        <option value="fixed" {{ $discountType === 'fixed' ? 'selected' : '' }}>{{ currency_symbol() }} Fixed</option>
                        <option value="percentage" {{ $discountType === 'percentage' ? 'selected' : '' }}>% Percent</option>
                    </select>
                    {{-- Hidden radios kept for JS compatibility --}}
                    <input type="radio" name="discount_type" id="discount_type_fixed" value="fixed"
                           {{ $discountType === 'fixed' ? 'checked' : '' }} class="sr-only">
                    <input type="radio" name="discount_type" id="discount_type_percentage" value="percentage"
                           {{ $discountType === 'percentage' ? 'checked' : '' }} class="sr-only">
                    {{-- Value input --}}
                    <input type="number" id="discount_input_value" step="0.01" min="0"
                           value="{{ $discountType === 'percentage' ? ($bill->discount_percentage ?? 0) : $bill->discount_amount }}"
                           placeholder="0"
                           class="flex-1 px-3 py-2 text-sm focus:outline-none min-w-0">
                </div>
                <p id="discount_input_hint" class="text-xs text-gray-400 mt-1">
                    {{ $discountType === 'percentage' ? 'Enter percentage (0–100)' : 'Enter fixed amount' }}
                </p>

                {{-- Hidden fields submitted to server --}}
                <input type="hidden" id="discount_amount" name="discount_amount" value="{{ $bill->discount_amount }}">
                <input type="hidden" id="discount_percentage" name="discount_percentage" value="{{ $bill->discount_percentage ?? 0 }}">

                {{-- Computed amount shown when percentage mode --}}
                <div id="discount_computed_wrap" class="{{ $discountType === 'percentage' ? '' : 'hidden' }} mt-1 flex items-center gap-1 text-xs text-gray-500">
                    <i class="fas fa-equals"></i>
                    <span>Discount amount: </span>
                    <span id="discount_computed_amount" class="font-medium text-gray-700">{{ currency_symbol() }}{{ number_format($bill->discount_amount, 2) }}</span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Total Amount</label>
                <div id="totalAmount" class="text-2xl font-bold text-medical-blue">{{ format_currency($bill->total_amount) }}</div>
            </div>
        </div>

        <div class="mb-6">
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
            <textarea id="notes" name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ $bill->notes }}</textarea>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('bills.show', $bill) }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Cancel</a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">Update Bill</button>
        </div>
    </form>
</div>

<script>
    // Pass server-side values to the external JS module
    window._billItemCount = {{ $bill->billItems->count() }};
    window._currencySymbol = '{{ currency_symbol() }}';
    window._billPaidAmount = {{ $bill->paid_amount }};
</script>

@push('scripts')
    @vite('resources/js/bills-edit.js')
@endpush
@endsection