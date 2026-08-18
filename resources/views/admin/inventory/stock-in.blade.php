@extends('admin.layout')

@section('title', 'Stock In - Inventory Management')
@section('page-title', 'Add Stock')
@section('page-description', 'Add new medicine stock to inventory')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Add Stock</h3>
                <a href="{{ route('inventory.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Inventory
                </a>
            </div>
        </div>

        <form action="{{ route('inventory.process-stock-in') }}" method="POST" class="p-6">
            @csrf
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Medicine *</label>
                    <select name="medicine_id" id="medicine-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent select2-medicine" required>
                        <option value="">Select Medicine</option>
                        @foreach($medicines as $medicine)
                            <option value="{{ $medicine->id }}" 
                                    data-base-unit="{{ $medicine->baseUnit?->abbreviation ?? 'unit' }}"
                                    data-purchase-unit="{{ $medicine->purchaseUnit?->id ?? '' }}"
                                    @selected(old('medicine_id') == $medicine->id)>
                                {{ $medicine->name }} @if($medicine->generic_name)({{ $medicine->generic_name }})@endif
                            </option>
                        @endforeach
                    </select>
                    @error('medicine_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Batch Entry Mode *</label>
                    <div class="flex flex-wrap gap-6">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="stock_in_mode" value="new" class="text-medical-blue focus:ring-medical-blue"
                                   @checked(old('stock_in_mode', 'new') === 'new') onchange="setStockInMode('new')">
                            <span class="ml-2 text-sm text-gray-700">New batch</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="stock_in_mode" value="existing" class="text-medical-blue focus:ring-medical-blue"
                                   @checked(old('stock_in_mode', 'new') === 'existing') onchange="setStockInMode('existing')">
                            <span class="ml-2 text-sm text-gray-700">Add to existing batch</span>
                        </label>
                    </div>
                    @error('stock_in_mode')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="existing-batch-section" class="hidden space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Existing Batch *</label>
                        <select name="existing_batch_id" id="existing-batch-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent select2-batch">
                            <option value="">Select batch</option>
                        </select>
                        @error('existing_batch_id')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="batch-summary" class="hidden bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-700 space-y-1">
                        <p><span class="font-medium">Batch:</span> <span id="summary-batch-no">—</span></p>
                        <p><span class="font-medium">Expiry:</span> <span id="summary-expiry">—</span></p>
                        <p><span class="font-medium">Cost per base unit:</span> <span id="summary-unit-cost">—</span></p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unit *</label>
                    <select name="unit_id" id="unit-select" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent select2-unit" required>
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" data-base-unit="{{ $unit->base_unit_id ?? $unit->id }}" data-factor="{{ $unit->conversion_factor }}" data-abbrev="{{ $unit->abbreviation }}">
                                {{ $unit->name }} ({{ $unit->abbreviation }})
                            </option>
                        @endforeach
                    </select>
                    @error('unit_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label id="quantity-label" class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                        <input type="number" name="quantity" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('quantity')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="unit-cost-field">
                        <label id="unit-cost-label" class="block text-sm font-medium text-gray-700 mb-2">Unit Cost ({{ currency_symbol() }}) *</label>
                        <input type="number" name="unit_cost" id="unit-cost-input" step="0.01" min="0" value="{{ old('unit_cost') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <p id="unit-cost-hint" class="text-xs text-gray-500 mt-1 hidden"></p>
                        @error('unit_cost')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Supplier *</label>
                    <select name="supplier" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent select2-supplier" required>
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->name }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div id="new-batch-fields" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Batch Number</label>
                        <input type="text" name="batch_no" id="batch-no-input" value="{{ old('batch_no') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        @error('batch_no')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                        <input type="text" id="expiry-date" name="expiry_date" value="{{ old('expiry_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" placeholder="Select expiry date">
                        @error('expiry_date')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                    <input type="text" name="reference_no" placeholder="Invoice/PO number" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
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
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Add Stock
                </button>
            </div>
        </form>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .select2-container {
        width: 100% !important;
    }
    .select2-container--default .select2-selection--single {
        height: 42px;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px;
        padding-left: 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }
    .flatpickr-calendar {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    .flatpickr-day.selected {
        background: #0066CC;
        border-color: #0066CC;
    }
    .flatpickr-day:hover {
        background: #e5f3ff;
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
const batchesUrlTemplate = @json(route('inventory.medicines.batches', ['medicine' => '__MEDICINE__']));
const currencySymbol = @json(currency_symbol());
let batchCatalog = {};
let baseUnitAbbrev = 'unit';
let expiryPicker = null;

$(document).ready(function() {
    $('.select2-medicine').select2({
        placeholder: 'Select Medicine',
        allowClear: true,
        width: '100%'
    }).on('change', function() {
        updateUnits();
        if (getStockInMode() === 'existing') {
            fetchBatches(this.value);
        }
    });

    $('.select2-unit').select2({
        placeholder: 'Select Unit',
        allowClear: true,
        width: '100%'
    }).on('change', updateUnitLabels);

    $('.select2-supplier').select2({
        placeholder: 'Select Supplier',
        allowClear: true,
        width: '100%'
    });

    $('.select2-batch').select2({
        placeholder: 'Select batch',
        allowClear: true,
        width: '100%'
    }).on('change', updateBatchSummary);

    expiryPicker = flatpickr('#expiry-date', {
        dateFormat: 'Y-m-d',
        minDate: 'today',
        altInput: true,
        altFormat: 'F j, Y',
        allowInput: true
    });

    setStockInMode(getStockInMode());

    const medicineId = $('#medicine-select').val();
    if (medicineId && getStockInMode() === 'existing') {
        fetchBatches(medicineId, @json(old('existing_batch_id')));
    }
});

function getStockInMode() {
    const checked = document.querySelector('input[name="stock_in_mode"]:checked');
    return checked ? checked.value : 'new';
}

function setStockInMode(mode) {
    const isExisting = mode === 'existing';
    const existingSection = document.getElementById('existing-batch-section');
    const newBatchFields = document.getElementById('new-batch-fields');
    const unitCostInput = document.getElementById('unit-cost-input');
    const batchNoInput = document.getElementById('batch-no-input');
    const expiryInput = document.getElementById('expiry-date');

    existingSection.classList.toggle('hidden', !isExisting);
    newBatchFields.classList.toggle('hidden', isExisting);
    document.getElementById('unit-cost-field').classList.toggle('hidden', isExisting);

    unitCostInput.disabled = isExisting;
    unitCostInput.required = !isExisting;

    batchNoInput.disabled = isExisting;
    batchNoInput.required = !isExisting;

    expiryInput.disabled = isExisting;
    expiryInput.required = !isExisting;

    if (expiryPicker) {
        if (isExisting) {
            expiryPicker.clear();
        }
    }

    const batchSelect = $('#existing-batch-select');
    batchSelect.prop('required', isExisting);

    if (isExisting) {
        const medicineId = document.getElementById('medicine-select').value;
        if (medicineId) {
            fetchBatches(medicineId);
        } else {
            resetBatchSelect();
        }
    } else {
        resetBatchSelect();
        hideBatchSummary();
    }
}

function batchesUrl(medicineId) {
    return batchesUrlTemplate.replace('__MEDICINE__', medicineId);
}

function resetBatchSelect() {
    batchCatalog = {};
    const batchSelect = $('#existing-batch-select');
    batchSelect.empty().append(new Option('Select batch', '', false, false));
    batchSelect.val(null).trigger('change');
    hideBatchSummary();
}

function fetchBatches(medicineId, selectedBatchId = null) {
    if (!medicineId) {
        resetBatchSelect();
        return;
    }

    resetBatchSelect();

    fetch(batchesUrl(medicineId), {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(data => {
            baseUnitAbbrev = data.base_unit_abbrev || 'unit';
            batchCatalog = {};

            const batchSelect = $('#existing-batch-select');
            batchSelect.empty().append(new Option('Select batch', '', false, false));

            (data.batches || []).forEach(batch => {
                batchCatalog[batch.id] = batch;
                const expiryLabel = batch.expiry_date
                    ? formatDate(batch.expiry_date)
                    : 'No expiry';
                const label = batch.batch_no + ' — Exp: ' + expiryLabel + ' — ' + currencySymbol + ' ' + Number(batch.unit_cost).toFixed(2) + '/' + baseUnitAbbrev;
                batchSelect.append(new Option(label, batch.id, false, false));
            });

            if (selectedBatchId && batchCatalog[selectedBatchId]) {
                batchSelect.val(String(selectedBatchId)).trigger('change');
            } else {
                batchSelect.val(null).trigger('change');
            }
        })
        .catch(() => {
            resetBatchSelect();
        });
}

function updateBatchSummary() {
    const batchId = $('#existing-batch-select').val();
    const batch = batchCatalog[batchId];

    if (!batch) {
        hideBatchSummary();
        return;
    }

    document.getElementById('summary-batch-no').textContent = batch.batch_no || '—';
    document.getElementById('summary-expiry').textContent = batch.expiry_date
        ? formatDate(batch.expiry_date)
        : 'No expiry';
    document.getElementById('summary-unit-cost').textContent = currencySymbol + ' ' + Number(batch.unit_cost).toFixed(2) + ' / ' + baseUnitAbbrev;
    document.getElementById('batch-summary').classList.remove('hidden');
}

function hideBatchSummary() {
    document.getElementById('batch-summary').classList.add('hidden');
}

function formatDate(dateString) {
    const parts = dateString.split('-');
    if (parts.length !== 3) {
        return dateString;
    }

    const date = new Date(Number(parts[0]), Number(parts[1]) - 1, Number(parts[2]));
    return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function updateUnits() {
    const medicineSelect = document.getElementById('medicine-select');
    const unitSelect = document.getElementById('unit-select');

    if (!medicineSelect.value) {
        unitSelect.disabled = false;
        return;
    }
}

function updateUnitLabels() {
    const unitSelect = document.getElementById('unit-select');
    const opt = unitSelect.options[unitSelect.selectedIndex];
    const abbrev = opt?.dataset?.abbrev || '';

    if (!abbrev) {
        document.getElementById('quantity-label').textContent = 'Quantity *';
        document.getElementById('unit-cost-label').textContent = 'Unit Cost (' + currencySymbol + ') *';
        document.getElementById('unit-cost-hint').classList.add('hidden');
        return;
    }

    document.getElementById('quantity-label').textContent = 'Quantity (' + abbrev + ') *';
    document.getElementById('unit-cost-label').textContent = 'Cost per ' + abbrev + ' (' + currencySymbol + ') *';
    const hint = document.getElementById('unit-cost-hint');
    hint.textContent = 'Enter the price for one ' + abbrev + ' — not per tablet unless ' + abbrev + ' is your base unit.';
    hint.classList.remove('hidden');
}
</script>
@endpush
@endsection