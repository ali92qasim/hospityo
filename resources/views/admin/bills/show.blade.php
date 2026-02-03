@extends('admin.layout')

@section('title', 'Bill Details')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Bill #{{ $bill->bill_number }}</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">Bill Information</h2>
                    <p class="text-sm text-gray-600">{{ $bill->bill_date->format('M d, Y') }}</p>
                </div>
                <span class="bg-{{ $bill->status_color }}-100 text-{{ $bill->status_color }}-800 text-sm px-3 py-1 rounded-full capitalize">{{ $bill->status }}</span>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="font-medium text-gray-700 mb-2">Patient Details</h3>
                    <p class="text-gray-900">{{ $bill->patient->name }}</p>
                    <p class="text-gray-600">{{ $bill->patient->phone }}</p>
                    <p class="text-gray-600">{{ $bill->patient->email }}</p>
                </div>
                <div>
                    <h3 class="font-medium text-gray-700 mb-2">Bill Details</h3>
                    <p class="text-gray-900">Type: <span class="uppercase">{{ $bill->bill_type }}</span></p>
                    @if($bill->visit)
                        <p class="text-gray-600">Visit: {{ $bill->visit->visit_date->format('M d, Y') }}</p>
                    @endif
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="font-medium text-gray-700 mb-4">Bill Items</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($bill->billItems as $item)
                            <tr>
                                <td class="px-4 py-2">{{ $item->description }}</td>
                                <td class="px-4 py-2">{{ $item->quantity }}</td>
                                <td class="px-4 py-2">₨{{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-4 py-2">₨{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 border-t pt-4">
                    <div class="flex justify-end">
                        <div class="w-64">
                            <div class="flex justify-between py-2">
                                <span>Subtotal:</span>
                                <span>₨{{ number_format($bill->subtotal, 2) }}</span>
                            </div>
                            @if($bill->tax_amount > 0)
                            <div class="flex justify-between py-2">
                                <span>Tax:</span>
                                <span>₨{{ number_format($bill->tax_amount, 2) }}</span>
                            </div>
                            @endif
                            @if($bill->discount_amount > 0)
                            <div class="flex justify-between py-2">
                                <span>Discount:</span>
                                <span>-₨{{ number_format($bill->discount_amount, 2) }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between py-2 border-t font-semibold">
                                <span>Total:</span>
                                <span>₨{{ number_format($bill->total_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 text-green-600">
                                <span>Paid:</span>
                                <span>₨{{ number_format($bill->paid_amount, 2) }}</span>
                            </div>
                            @if($bill->due_amount > 0)
                            <div class="flex justify-between py-2 text-red-600 font-semibold">
                                <span>Due:</span>
                                <span>₨{{ number_format($bill->due_amount, 2) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div>
        @if($bill->due_amount > 0)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Add Payment</h3>
            <form method="POST" action="{{ route('bills.add-payment', $bill) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                    <input type="number" name="amount" step="0.01" max="{{ $bill->due_amount }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                    <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="upi">UPI</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cheque">Cheque</option>
                        <option value="insurance">Insurance</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                    <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                    <input type="text" name="reference_number" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                </div>
                <button type="submit" class="w-full bg-medical-blue text-white py-2 px-4 rounded-lg hover:bg-blue-700">Add Payment</button>
            </form>
        </div>
        @endif

        @if($bill->payments->count() > 0)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment History</h3>
            <div class="space-y-3">
                @foreach($bill->payments as $payment)
                <div class="border-l-4 border-green-500 pl-4">
                    <div class="flex justify-between">
                        <span class="font-medium">₨{{ number_format($payment->amount, 2) }}</span>
                        <span class="text-sm text-gray-500">{{ $payment->payment_date->format('M d, Y') }}</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                        @if($payment->reference_number)
                            - {{ $payment->reference_number }}
                        @endif
                    </div>
                    <div class="text-xs text-gray-500">By: {{ $payment->receivedBy->name }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<div class="flex justify-end space-x-3 mt-6">
    <a href="{{ route('bills.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Back</a>
    <a href="{{ route('bills.edit', $bill) }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">Edit Bill</a>
</div>
@endsection