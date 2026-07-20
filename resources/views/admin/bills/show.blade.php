@extends('admin.layout')

@section('title', 'Bill Details')

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <h1 class="text-2xl font-bold text-gray-800">Bill #{{ $bill->bill_number }}</h1>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('bills.edit', $bill) }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700">
            <i class="fas fa-edit mr-1"></i>Edit
        </a>
        <a href="{{ route('bills.print', $bill) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50">
            <i class="fas fa-print mr-1"></i>{{ $bill->isDraft() ? 'Print Draft' : 'Print' }}
        </a>
    </div>
</div>

@if($bill->isDraft())
<div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3">
    <i class="fas fa-file-alt text-blue-500 mt-0.5"></i>
    <div>
        <p class="text-sm font-medium text-blue-800">Draft Bill (Estimate)</p>
        <p class="text-sm text-blue-700">
            This is a running IPD draft for the admission — not a formal invoice.
            Add charges during the stay. Accounting and payments are deferred until discharge finalization.
        </p>
    </div>
</div>
@endif

@if($bill->paid_amount > $bill->total_amount)
<div class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-start gap-3" id="overpayment-banner">
    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
    <div>
        <p class="text-sm font-medium text-blue-800">Patient Credit Balance</p>
        <p class="text-sm text-blue-700">
            This bill was modified after payment. The patient has a credit balance of
            <strong>{{ format_currency($bill->paid_amount - $bill->total_amount) }}</strong>
            which can be applied to future bills or refunded.
        </p>
    </div>
</div>
@endif

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
                                <td class="px-4 py-2">{{ format_currency($item->unit_price) }}</td>
                                <td class="px-4 py-2">{{ format_currency($item->total_price) }}</td>
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
                                <span>{{ format_currency($bill->subtotal) }}</span>
                            </div>
                            @if($bill->tax_amount > 0)
                            <div class="flex justify-between py-2">
                                <span>Tax:</span>
                                <span>{{ format_currency($bill->tax_amount) }}</span>
                            </div>
                            @if($bill->tax_details)
                                @foreach($bill->tax_details as $td)
                                <div class="flex justify-between py-1 pl-4 text-xs text-gray-500">
                                    <span>{{ $td['name'] }} ({{ $td['percentage'] }}%)</span>
                                    <span>{{ format_currency($td['amount']) }}</span>
                                </div>
                                @endforeach
                            @endif
                            @endif
                            @if($bill->discount_amount > 0)
                            <div class="flex justify-between py-2">
                                <span>Discount:</span>
                                <span>-{{ format_currency($bill->discount_amount) }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between py-2 border-t font-semibold">
                                <span>Total:</span>
                                <span>{{ format_currency($bill->total_amount) }}</span>
                            </div>
                            <div class="flex justify-between py-2 text-green-600">
                                <span>Paid:</span>
                                <span>{{ format_currency($bill->paid_amount) }}</span>
                            </div>
                            @if($bill->due_amount > 0)
                            <div class="flex justify-between py-2 text-red-600 font-semibold">
                                <span>Due:</span>
                                <span>{{ format_currency($bill->due_amount) }}</span>
                            </div>
                            @endif
                            @if($bill->paid_amount > $bill->total_amount)
                            <div class="flex justify-between py-2 text-blue-600 font-semibold">
                                <span>Patient Credit:</span>
                                <span>{{ format_currency($bill->paid_amount - $bill->total_amount) }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div>
        @if($bill->isDraft())
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-2">Draft — Payments Disabled</h3>
            <p class="text-sm text-blue-700">Payments will be available after this draft is finalized at discharge.</p>
        </div>
        @elseif($bill->due_amount > 0)
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

        @php
            $ipdAdmission = $bill->bill_type === 'ipd' ? $bill->visit?->admission : null;
        @endphp
        @if($ipdAdmission && ($ipdAdmission->advances->count() > 0 || $ipdAdmission->refund_amount > 0))
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">IPD Admission Settlement</h3>
            <p class="text-sm text-gray-600 mb-4">
                Advance payments are recorded on the admission. At discharge, advances are applied to this bill and any unused credit is refunded separately.
            </p>

            @if($ipdAdmission->advances->count() > 0)
            <div class="mb-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Advances Received During Stay</h4>
                <div class="space-y-2">
                    @foreach($ipdAdmission->advances as $advance)
                    <div class="flex justify-between text-sm border-l-4 border-blue-400 pl-3 py-1">
                        <div>
                            <span class="font-medium">{{ format_currency($advance->amount) }}</span>
                            <span class="text-gray-500"> · {{ $advance->payment_date->format('M d, Y') }}</span>
                            <span class="text-gray-500"> · {{ ucfirst(str_replace('_', ' ', $advance->payment_method)) }}</span>
                            @if($advance->reference_number)
                                <span class="text-gray-400"> · {{ $advance->reference_number }}</span>
                            @endif
                        </div>
                        <span class="text-xs text-gray-500">{{ $advance->receivedBy?->name }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="mt-2 text-sm font-medium text-gray-800">
                    Total advances: {{ format_currency($ipdAdmission->total_advances) }}
                </div>
            </div>
            @endif

            @if($ipdAdmission->refund_amount > 0)
            <div class="rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-800">
                <i class="fas fa-hand-holding-usd mr-1"></i>
                Refunded at discharge: <strong>{{ format_currency($ipdAdmission->refund_amount) }}</strong>
                via {{ ucfirst(str_replace('_', ' ', $ipdAdmission->refund_method ?? 'cash')) }}
                @if($ipdAdmission->refunded_at)
                    on {{ $ipdAdmission->refunded_at->format('M d, Y h:i A') }}
                @endif
            </div>
            @endif
        </div>
        @endif

        @if($bill->payments->count() > 0)
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Payment History</h3>
            <div class="space-y-3">
                @foreach($bill->payments as $payment)
                <div class="border-l-4 border-green-500 pl-4">
                    <div class="flex justify-between">
                        <span class="font-medium">{{ format_currency($payment->amount) }}</span>
                        <span class="text-sm text-gray-500">{{ $payment->payment_date->format('M d, Y') }}</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        @if($payment->payment_method === 'advance')
                            Advance applied from admission
                        @else
                            {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                        @endif
                        @if($payment->reference_number)
                            - {{ $payment->reference_number }}
                        @endif
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <span class="text-xs text-gray-500">By: {{ $payment->receivedBy->name }}</span>
                        <div class="flex gap-2">
                            @can('edit payments')
                            <button type="button" class="text-xs text-medical-blue hover:text-blue-700"
                                    onclick="editPayment(this)"
                                    data-update-url="{{ route('bills.update-payment', [$bill, $payment]) }}"
                                    data-amount="{{ $payment->amount }}"
                                    data-method="{{ $payment->payment_method }}"
                                    data-date="{{ $payment->payment_date->format('Y-m-d') }}"
                                    data-reference="{{ $payment->reference_number }}"
                                    data-notes="{{ $payment->notes }}"
                                    title="Edit payment">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            @endcan
                            @can('delete payments')
                            <form method="POST" action="{{ route('bills.remove-payment', [$bill, $payment]) }}" class="inline"
                                  onsubmit="return confirm('Are you sure you want to remove this payment? This will reverse the accounting entry.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700" title="Remove payment">
                                    <i class="fas fa-times"></i> Remove
                                </button>
                            </form>
                            @endcan
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<div class="flex justify-end space-x-3 mt-6">
    <a href="{{ route('bills.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">Back</a>
    <a href="{{ route('bills.print', $bill) }}" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
        <i class="fas fa-print mr-2"></i>Print Bill
    </a>
    <a href="{{ route('bills.edit', $bill) }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">Edit Bill</a>
</div>

{{-- Edit Payment Modal --}}
@can('edit payments')
<div id="editPaymentModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" onclick="closeEditPaymentModal()"></div>
        <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Edit Payment</h3>
                <button onclick="closeEditPaymentModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <form id="editPaymentForm" method="POST">
                @csrf @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                        <input type="number" name="amount" id="editPaymentAmount" step="0.01" min="0.01"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                        <select name="payment_method" id="editPaymentMethod"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="upi">UPI</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="insurance">Insurance</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                        <input type="date" name="payment_date" id="editPaymentDate"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                        <input type="text" name="reference_number" id="editPaymentRef"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" id="editPaymentNotes" rows="2"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeEditPaymentModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">Update Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<script>
function editPayment(button) {
    var form = document.getElementById('editPaymentForm');
    form.action = button.dataset.updateUrl;
    document.getElementById('editPaymentAmount').value = button.dataset.amount;
    document.getElementById('editPaymentMethod').value = button.dataset.method;
    document.getElementById('editPaymentDate').value = button.dataset.date;
    document.getElementById('editPaymentRef').value = button.dataset.reference || '';
    document.getElementById('editPaymentNotes').value = button.dataset.notes || '';
    document.getElementById('editPaymentModal').classList.remove('hidden');
}

function closeEditPaymentModal() {
    document.getElementById('editPaymentModal').classList.add('hidden');
}
</script>
@endsection
