@extends('admin.layout')

@section('title', 'Stock In - Inventory Management')
@section('page-title', 'Add Stock')
@section('page-description', 'Add new medicine stock to inventory')

@section('content')
<div id="stock-in-page" class="w-full max-w-3xl mx-auto min-w-0 pb-24 sm:pb-8">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 sm:p-6 border-b border-gray-200">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Add Stock</h3>
                <a href="{{ route('inventory.index') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800 self-start sm:self-auto shrink-0">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Inventory
                </a>
            </div>
        </div>

        <form action="{{ route('inventory.process-stock-in') }}" method="POST" class="p-4 sm:p-6" id="stock-in-form">
            @csrf

            <div class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Medicine <span class="text-red-500">*</span></label>
                    <select name="medicine_id" id="medicine-select" class="stock-in-field w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent select2-medicine" required onchange="updateUnits()">
                        <option value="">Select Medicine</option>
                        @foreach($medicines as $medicine)
                            <option value="{{ $medicine->id }}"
                                    data-base-unit="{{ $medicine->baseUnit?->abbreviation ?? 'unit' }}"
                                    data-purchase-unit="{{ $medicine->purchaseUnit?->id ?? '' }}">
                                {{ $medicine->name }} @if($medicine->generic_name)({{ $medicine->generic_name }})@endif
                            </option>
                        @endforeach
                    </select>
                    @error('medicine_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Unit <span class="text-red-500">*</span></label>
                    <select name="unit_id" id="unit-select" class="stock-in-field w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent select2-unit" required>
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

                <div>
                    <label id="quantity-label" class="block text-sm font-medium text-gray-700 mb-2">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" min="1" inputmode="numeric" class="stock-in-field w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    @error('quantity')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label id="unit-cost-label" class="block text-sm font-medium text-gray-700 mb-2">Unit Cost ({{ currency_symbol() }}) <span class="text-red-500">*</span></label>
                    <input type="number" name="unit_cost" step="0.01" min="0" inputmode="decimal" class="stock-in-field w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <p id="unit-cost-hint" class="text-xs text-gray-500 mt-1 hidden break-words"></p>
                    @error('unit_cost')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Supplier <span class="text-red-500">*</span></label>
                    <select name="supplier" class="stock-in-field w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent select2-supplier" required>
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->name }}">{{ $supplier->name }}</option>
                        @endforeach
                    </select>
                    @error('supplier')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Batch Number <span class="text-red-500">*</span></label>
                    <input type="text" name="batch_no" class="stock-in-field w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    @error('batch_no')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date <span class="text-red-500">*</span></label>
                    <input type="text" id="expiry-date" name="expiry_date" class="stock-in-field w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" placeholder="Select expiry date" required autocomplete="off">
                    @error('expiry_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                    <input type="text" name="reference_no" placeholder="Invoice/PO number" class="stock-in-field w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    @error('reference_no')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="stock-in-field w-full px-3 py-2.5 sm:py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent resize-y min-h-[5rem]" placeholder="Additional notes..."></textarea>
                    @error('notes')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Desktop / tablet actions --}}
            <div class="hidden sm:flex sm:justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('inventory.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-center">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>Add Stock
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Mobile sticky actions — always reachable while scrolling --}}
<div class="sm:hidden fixed bottom-0 inset-x-0 z-30 bg-white border-t border-gray-200 shadow-[0_-4px_12px_rgba(0,0,0,0.08)] px-4 py-3">
    <div class="flex flex-col-reverse gap-2 max-w-3xl mx-auto">
        <a href="{{ route('inventory.index') }}" class="w-full text-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            Cancel
        </a>
        <button type="submit" form="stock-in-form" class="w-full px-6 py-3 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>Add Stock
        </button>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    #stock-in-page .stock-in-field {
        font-size: 16px;
        max-width: 100%;
        box-sizing: border-box;
    }
    @media (min-width: 640px) {
        #stock-in-page .stock-in-field {
            font-size: 0.875rem;
        }
    }

    #stock-in-page .select2-container {
        width: 100% !important;
        max-width: 100%;
        box-sizing: border-box;
    }
    #stock-in-page .select2-container--default .select2-selection--single {
        min-height: 44px;
        height: auto;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        box-sizing: border-box;
    }
    #stock-in-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.4;
        padding: 10px 2rem 10px 12px;
        white-space: normal;
        word-break: break-word;
    }
    #stock-in-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
        top: 0;
    }
    #stock-in-page .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }
    .select2-dropdown {
        max-width: calc(100vw - 2rem);
        box-sizing: border-box;
    }
    .select2-results__option {
        word-break: break-word;
    }

    .flatpickr-calendar {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
        max-width: calc(100vw - 2rem);
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
$(document).ready(function() {
    document.body.classList.remove('overflow-hidden');

    const select2Options = {
        allowClear: true,
        width: '100%',
        dropdownAutoWidth: false,
        dropdownParent: $(document.body),
    };

    $('.select2-medicine').select2({ ...select2Options, placeholder: 'Select Medicine' });
    $('.select2-unit').select2({ ...select2Options, placeholder: 'Select Unit' }).on('change', updateUnitLabels);
    $('.select2-supplier').select2({ ...select2Options, placeholder: 'Select Supplier' });

    flatpickr('#expiry-date', {
        dateFormat: 'Y-m-d',
        minDate: 'today',
        allowInput: true,
        disableMobile: false,
    });
});

function updateUnits() {
    const medicineSelect = document.getElementById('medicine-select');
    const unitSelect = document.getElementById('unit-select');

    if (!medicineSelect.value) {
        unitSelect.disabled = false;
    }
}

function updateUnitLabels() {
    const unitSelect = document.getElementById('unit-select');
    const opt = unitSelect.options[unitSelect.selectedIndex];
    const abbrev = opt?.dataset?.abbrev || '';
    const currencySymbol = @json(currency_symbol());

    if (!abbrev) {
        document.getElementById('quantity-label').innerHTML = 'Quantity <span class="text-red-500">*</span>';
        document.getElementById('unit-cost-label').innerHTML = 'Unit Cost (' + currencySymbol + ') <span class="text-red-500">*</span>';
        document.getElementById('unit-cost-hint').classList.add('hidden');
        return;
    }

    document.getElementById('quantity-label').innerHTML = 'Quantity (' + abbrev + ') <span class="text-red-500">*</span>';
    document.getElementById('unit-cost-label').innerHTML = 'Cost per ' + abbrev + ' (' + currencySymbol + ') <span class="text-red-500">*</span>';
    const hint = document.getElementById('unit-cost-hint');
    hint.textContent = 'Enter the price for one ' + abbrev + ' — not per tablet unless ' + abbrev + ' is your base unit.';
    hint.classList.remove('hidden');
}
</script>
@endpush
@endsection
