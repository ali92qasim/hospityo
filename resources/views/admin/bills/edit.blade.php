@extends('admin.layout')

@section('title', 'Edit Bill')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">
        Edit Bill #{{ $bill->bill_number }}
        @if($bill->isDraft())
            <span class="ml-2 text-sm font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-full px-3 py-1">Draft</span>
        @endif
    </h1>
</div>

@if($bill->isDraft())
<div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3">
    <i class="fas fa-info-circle text-medical-blue mt-0.5"></i>
    <div>
        <p class="text-sm font-medium text-blue-800">Draft bill — add charges as the patient stays</p>
        <p class="text-sm text-blue-700">This is an estimate only. Accounting entries are posted when the bill is finalized.</p>
    </div>
</div>
@endif

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

        @if($bill->visit_id)
            <input type="hidden" name="visit_id" value="{{ $bill->visit_id }}">
        @endif

        @php $isIpdDraft = $bill->isDraft() && $bill->bill_type === 'ipd' && $bill->visit_id; @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label for="patient_id" class="block text-sm font-medium text-gray-700 mb-2">Patient</label>
                @if($isIpdDraft)
                    <input type="hidden" name="patient_id" value="{{ $bill->patient_id }}">
                    <div class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-800">
                        {{ $bill->patient->name }} - {{ $bill->patient->phone }}
                    </div>
                @else
                    <select id="patient_id" name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}" @selected($bill->patient_id == $patient->id)>
                                {{ $patient->name }} - {{ $patient->phone }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <div>
                <label for="bill_type" class="block text-sm font-medium text-gray-700 mb-2">Bill Type</label>
                @if($isIpdDraft)
                    <input type="hidden" name="bill_type" value="ipd">
                    <div class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-800 uppercase">IPD</div>
                @else
                    <select id="bill_type" name="bill_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="opd" @selected($bill->bill_type == 'opd')>OPD</option>
                        <option value="ipd" @selected($bill->bill_type == 'ipd')>IPD</option>
                        <option value="emergency" @selected($bill->bill_type == 'emergency')>Emergency</option>
                        <option value="investigation" @selected($bill->bill_type == 'investigation')>Investigation</option>
                        <option value="pharmacy" @selected($bill->bill_type == 'pharmacy')>Pharmacy</option>
                    </select>
                @endif
            </div>

            <div>
                <label for="bill_date" class="block text-sm font-medium text-gray-700 mb-2">Bill Date</label>
                <input type="text" id="bill_date" name="bill_date" value="{{ $bill->bill_date->format('Y-m-d') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Bill Items</h3>
            <div id="billItems">
                @forelse($bill->billItems as $index => $item)
                    @include('admin.bills.partials.item-row', ['index' => $index, 'item' => $item])
                @empty
                    @include('admin.bills.partials.item-row', ['index' => 0, 'item' => null])
                @endforelse
            </div>
            <button type="button" id="addItem" class="text-medical-blue hover:text-blue-700 text-sm font-medium">
                <i class="fas fa-plus mr-1"></i>Add Item
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tax</label>
                <input type="number" id="tax_amount" name="tax_amount" step="0.01" value="{{ $bill->tax_amount }}" readonly
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50 text-gray-700 cursor-not-allowed">
                <div id="tax-breakdown" class="mt-1 space-y-0.5"></div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Discount</label>

                @php $discountType = $bill->discount_type ?? 'fixed'; @endphp

                <div class="flex rounded-lg border border-gray-300 overflow-hidden focus-within:ring-2 focus-within:ring-medical-blue focus-within:border-transparent">
                    <select id="discount_type_select" name="discount_type"
                            class="px-3 py-2 bg-gray-50 border-r border-gray-300 text-sm text-gray-700 focus:outline-none cursor-pointer">
                        <option value="fixed" @selected($discountType === 'fixed')>{{ currency_symbol() }} Fixed</option>
                        <option value="percentage" @selected($discountType === 'percentage')>% Percent</option>
                    </select>
                    <input type="radio" name="discount_type" id="discount_type_fixed" value="fixed"
                           @checked($discountType === 'fixed') class="sr-only">
                    <input type="radio" name="discount_type" id="discount_type_percentage" value="percentage"
                           @checked($discountType === 'percentage') class="sr-only">
                    <input type="number" id="discount_input_value" step="0.01" min="0"
                           value="{{ $discountType === 'percentage' ? ($bill->discount_percentage ?? 0) : $bill->discount_amount }}"
                           placeholder="0"
                           class="flex-1 px-3 py-2 text-sm focus:outline-none min-w-0">
                </div>
                <p id="discount_input_hint" class="text-xs text-gray-400 mt-1">
                    {{ $discountType === 'percentage' ? 'Enter percentage (0–100)' : 'Enter fixed amount' }}
                </p>

                <input type="hidden" id="discount_amount" name="discount_amount" value="{{ $bill->discount_amount }}">
                <input type="hidden" id="discount_percentage" name="discount_percentage" value="{{ $bill->discount_percentage ?? 0 }}">

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
            <a href="{{ $isIpdDraft ? route('visits.workflow', $bill->visit_id) : route('bills.show', $bill) }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Cancel</a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                {{ $bill->isDraft() ? 'Save Draft' : 'Update Bill' }}
            </button>
        </div>
    </form>
</div>

<script>
    window._billItemCount = {{ $bill->billItems->count() ?: 1 }};
    window._currencySymbol = @json(currency_symbol());
    window._billPaidAmount = {{ $bill->paid_amount }};
    window._billDate = @json($bill->bill_date->format('Y-m-d'));
</script>

@vite(['resources/js/bills-form.js'])
@endsection
