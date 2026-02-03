@extends('admin.layout')

@section('title', 'Stock Out - Inventory Management')
@section('page-title', 'Remove Stock')
@section('page-description', 'Remove medicine stock from inventory')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Remove Stock</h3>
                <a href="{{ route('inventory.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Inventory
                </a>
            </div>
        </div>

        <form action="{{ route('inventory.process-stock-out') }}" method="POST" class="p-6">
            @csrf
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Medicine *</label>
                    <select name="medicine_id" id="medicine-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required onchange="updateStock()">
                        <option value="">Select Medicine</option>
                        @foreach($medicines as $medicine)
                            <option value="{{ $medicine->id }}" data-stock="{{ $medicine->getCurrentStock() }}" data-unit="{{ $medicine->unit }}">
                                {{ $medicine->name }} (Available: {{ $medicine->getCurrentStock() }} {{ $medicine->unit }})
                            </option>
                        @endforeach
                    </select>
                    @error('medicine_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="stock-info" class="hidden bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-sm text-blue-800">Available Stock: <span id="available-stock">0</span> <span id="stock-unit"></span></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                    <input type="number" name="quantity" id="quantity-input" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    @error('quantity')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
                    <select name="reason" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="">Select Reason</option>
                        <option value="expired">Expired</option>
                        <option value="damaged">Damaged</option>
                        <option value="dispensed">Dispensed</option>
                        <option value="adjustment">Stock Adjustment</option>
                    </select>
                    @error('reason')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                    <input type="text" name="reference_no" placeholder="Reference number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    @error('reference_no')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" placeholder="Additional notes..."></textarea>
                    @error('notes')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('inventory.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-minus mr-2"></i>Remove Stock
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateStock() {
    const select = document.getElementById('medicine-select');
    const stockInfo = document.getElementById('stock-info');
    const availableStock = document.getElementById('available-stock');
    const stockUnit = document.getElementById('stock-unit');
    const quantityInput = document.getElementById('quantity-input');
    
    if (select.value) {
        const option = select.options[select.selectedIndex];
        const stock = option.dataset.stock;
        const unit = option.dataset.unit;
        
        availableStock.textContent = stock;
        stockUnit.textContent = unit;
        quantityInput.max = stock;
        stockInfo.classList.remove('hidden');
    } else {
        stockInfo.classList.add('hidden');
        quantityInput.max = '';
    }
}
</script>
@endsection