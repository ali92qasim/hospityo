@extends('admin.layout')

@section('title', 'Pharmacy POS')
@section('page-title', 'Pharmacy POS')
@section('page-description', 'Fulfill prescriptions and walk-in counter sales')

@section('content')
<div id="pharmacy-pos" class="space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">{{ session('error') }}</div>
    @endif

    <div class="border-b border-gray-200">
        <nav class="-mb-px flex gap-4">
            <button type="button" data-pos-tab="queue" class="pos-tab-btn border-b-2 border-medical-blue text-medical-blue px-4 py-2 text-sm font-medium">
                Prescription Queue
            </button>
            <button type="button" data-pos-tab="counter" class="pos-tab-btn border-b-2 border-transparent text-gray-500 px-4 py-2 text-sm font-medium">
                Counter Sale
            </button>
        </nav>
    </div>

    <div id="pos-tab-queue" class="pos-tab-panel grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="font-semibold text-gray-800">Pending In-House Prescriptions</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rx #</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($pendingPrescriptions as $prescription)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $prescription->prescription_no }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ $prescription->patient->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">Dr. {{ $prescription->doctor->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ format_currency($prescription->total_amount) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button"
                                            class="load-prescription-btn text-medical-blue hover:text-blue-700 text-sm font-medium"
                                            data-url="{{ route('pharmacy.pos.prescription', $prescription) }}">
                                        Open
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">No pending in-house prescriptions.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Fulfill Prescription</h3>
            <form method="POST" action="{{ route('pharmacy.pos.checkout') }}" id="prescription-checkout-form">
                @csrf
                <input type="hidden" name="mode" value="prescription">
                <input type="hidden" name="prescription_id" id="prescription-id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient</label>
                    <select name="patient_id" id="prescription-patient-id" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select from prescription queue</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->name }} @if($patient->phone)({{ $patient->phone }})@endif</option>
                        @endforeach
                    </select>
                </div>

                <div id="prescription-lines" class="mb-4 min-h-[120px] border border-dashed border-gray-200 rounded-lg p-4 text-sm text-gray-500">
                    Select a prescription from the queue to review medicines.
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount</label>
                        <input type="number" step="0.01" min="0" name="payment_amount" id="prescription-payment-amount" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="upi">UPI</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 disabled:opacity-50" id="prescription-checkout-btn" disabled>
                    Fulfill &amp; Bill
                </button>
            </form>
        </div>
    </div>

    <div id="pos-tab-counter" class="pos-tab-panel hidden grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Add Medicines</h3>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Medicine</label>
                <select id="medicine-search" class="w-full"></select>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                    <input type="number" id="counter-quantity" min="1" value="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                </div>
                <div class="flex items-end">
                    <button type="button" id="add-to-cart-btn" class="w-full bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-semibold text-gray-800 mb-4">Counter Checkout</h3>
            <form method="POST" action="{{ route('pharmacy.pos.checkout') }}" id="walkin-checkout-form">
                @csrf
                <input type="hidden" name="mode" value="walk_in">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient</label>
                    <select name="patient_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                        <option value="">Select Patient</option>
                        @foreach($patients as $patient)
                            <option value="{{ $patient->id }}">{{ $patient->name }} @if($patient->phone)({{ $patient->phone }})@endif</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4 overflow-x-auto">
                    <table class="min-w-full text-sm" id="cart-table">
                        <thead>
                            <tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pr-2">Medicine</th>
                                <th class="py-2 pr-2">Qty</th>
                                <th class="py-2 pr-2">Price</th>
                                <th class="py-2 pr-2">Total</th>
                                <th class="py-2"></th>
                            </tr>
                        </thead>
                        <tbody id="cart-body">
                            <tr id="cart-empty-row">
                                <td colspan="5" class="py-6 text-center text-gray-500">Cart is empty.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center mb-4 text-sm">
                    <span class="font-medium text-gray-700">Cart Total</span>
                    <span id="cart-total" class="font-semibold text-gray-900">0.00</span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Amount</label>
                        <input type="number" step="0.01" min="0" name="payment_amount" id="walkin-payment-amount" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="upi">UPI</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 disabled:opacity-50" id="walkin-checkout-btn" disabled>
                    Complete Sale
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.pharmacyPosConfig = {
        medicineSearchUrl: @json(route('pharmacy.pos.medicines.search')),
    };
</script>
@vite(['resources/js/pharmacy-pos.js'])
@endpush
