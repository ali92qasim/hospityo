@extends('admin.layout')

@section('title', 'Edit Bill')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Bill #{{ $bill->bill_number }}</h1>
</div>

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
                                    {{ $service->name }} - ₨{{ $service->price }}
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
                <label for="discount_amount" class="block text-sm font-medium text-gray-700 mb-2">Discount Amount</label>
                <input type="number" id="discount_amount" name="discount_amount" step="0.01" value="{{ $bill->discount_amount }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Total Amount</label>
                <div id="totalAmount" class="text-2xl font-bold text-medical-blue">₨{{ number_format($bill->total_amount, 2) }}</div>
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
let itemIndex = {{ $bill->billItems->count() }};

document.getElementById('addItem').addEventListener('click', function() {
    const billItems = document.getElementById('billItems');
    const newItem = document.querySelector('.bill-item').cloneNode(true);
    
    newItem.querySelectorAll('input, select').forEach(input => {
        const oldName = input.name;
        if (oldName) {
            input.name = oldName.replace(/\[\d+\]/, `[${itemIndex}]`);
        }
        if (input.type !== 'button') {
            input.value = input.type === 'number' && input.classList.contains('quantity') ? '1' : '';
        }
        if (input.tagName === 'SELECT') {
            input.selectedIndex = 0;
        }
    });
    
    billItems.appendChild(newItem);
    itemIndex++;
    updateTotal();
});

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item')) {
        if (document.querySelectorAll('.bill-item').length > 1) {
            e.target.closest('.bill-item').remove();
            updateTotal();
        }
    }
});

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('service-select')) {
        const option = e.target.selectedOptions[0];
        const priceInput = e.target.closest('.bill-item').querySelector('.unit-price');
        const descInput = e.target.closest('.bill-item').querySelector('input[name*="[description]"]');
        
        if (option.dataset.price) {
            priceInput.value = option.dataset.price;
            descInput.value = option.text.split(' - ')[0];
        }
        updateTotal();
    }
    
    if (e.target.classList.contains('quantity') || e.target.classList.contains('unit-price') || 
        e.target.id === 'tax_amount' || e.target.id === 'discount_amount') {
        updateTotal();
    }
});

function updateTotal() {
    let subtotal = 0;
    
    document.querySelectorAll('.bill-item').forEach(item => {
        const qty = parseFloat(item.querySelector('.quantity').value) || 0;
        const price = parseFloat(item.querySelector('.unit-price').value) || 0;
        subtotal += qty * price;
    });
    
    const tax = parseFloat(document.getElementById('tax_amount').value) || 0;
    const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
    const total = subtotal + tax - discount;
    
    document.getElementById('totalAmount').textContent = `₨${total.toFixed(2)}`;
}

// Initialize total calculation
updateTotal();
</script>
@endsection