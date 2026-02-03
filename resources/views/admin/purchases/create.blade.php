@extends('admin.layout')

@section('title', 'Create Purchase Order - Hospital Management System')
@section('page-title', 'Create Purchase Order')
@section('page-description', 'Create new medicine purchase order')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Purchase Order Details</h3>
                <a href="{{ route('purchases.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Purchase Orders
                </a>
            </div>
        </div>

        <form action="{{ route('purchases.store') }}" method="POST" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Supplier *</label>
                    <select name="supplier_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order Date *</label>
                    <input type="date" name="order_date" value="{{ date('Y-m-d') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    @error('order_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expected Delivery</label>
                    <input type="date" name="expected_delivery" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    @error('expected_delivery')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h4 class="text-lg font-medium text-gray-800">Order Items</h4>
                    <button type="button" onclick="addItem()" class="bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700">
                        <i class="fas fa-plus mr-1"></i>Add Item
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border border-gray-200 rounded-lg">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Medicine</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Quantity</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Unit Price (₨)</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Total (₨)</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-gray-700">Action</th>
                            </tr>
                        </thead>
                        <tbody id="items-table">
                            <tr class="item-row">
                                <td class="px-4 py-3">
                                    <select name="items[0][medicine_id]" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
                                        <option value="">Select Medicine</option>
                                        @foreach($medicines as $medicine)
                                            <option value="{{ $medicine->id }}">{{ $medicine->name }} ({{ $medicine->generic_name }})</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="items[0][quantity]" min="1" class="w-full px-2 py-1 border border-gray-300 rounded text-sm quantity-input" required onchange="calculateTotal(this)">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" name="items[0][unit_price]" step="0.01" min="0" class="w-full px-2 py-1 border border-gray-300 rounded text-sm price-input" required onchange="calculateTotal(this)">
                                </td>
                                <td class="px-4 py-3">
                                    <span class="total-display">0.00</span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                <textarea name="notes" rows="3" 
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                          placeholder="Additional notes for this purchase order..."></textarea>
            </div>

            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('purchases.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Create Purchase Order
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let itemIndex = 1;

function addItem() {
    const tbody = document.getElementById('items-table');
    const newRow = document.createElement('tr');
    newRow.className = 'item-row';
    newRow.innerHTML = `
        <td class="px-4 py-3">
            <select name="items[${itemIndex}][medicine_id]" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
                <option value="">Select Medicine</option>
                @foreach($medicines as $medicine)
                    <option value="{{ $medicine->id }}">{{ $medicine->name }} ({{ $medicine->generic_name }})</option>
                @endforeach
            </select>
        </td>
        <td class="px-4 py-3">
            <input type="number" name="items[${itemIndex}][quantity]" min="1" class="w-full px-2 py-1 border border-gray-300 rounded text-sm quantity-input" required onchange="calculateTotal(this)">
        </td>
        <td class="px-4 py-3">
            <input type="number" name="items[${itemIndex}][unit_price]" step="0.01" min="0" class="w-full px-2 py-1 border border-gray-300 rounded text-sm price-input" required onchange="calculateTotal(this)">
        </td>
        <td class="px-4 py-3">
            <span class="total-display">0.00</span>
        </td>
        <td class="px-4 py-3 text-center">
            <button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-800">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(newRow);
    itemIndex++;
}

function removeItem(button) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length > 1) {
        button.closest('tr').remove();
    }
}

function calculateTotal(input) {
    const row = input.closest('tr');
    const quantity = row.querySelector('.quantity-input').value || 0;
    const price = row.querySelector('.price-input').value || 0;
    const total = quantity * price;
    row.querySelector('.total-display').textContent = total.toFixed(2);
}
</script>
@endsection