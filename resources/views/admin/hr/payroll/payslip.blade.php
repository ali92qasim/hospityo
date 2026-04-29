@extends('admin.layout')

@section('title', 'Payslip')
@section('page-title', 'Payslip')

@section('content')
<div class="max-w-4xl mx-auto">

    <!-- Employee Header -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center">
                    @if($payslip->employee->photo)
                        <img src="{{ asset('storage/' . $payslip->employee->photo) }}" alt="{{ $payslip->employee->full_name }}" class="w-16 h-16 rounded-full object-cover mr-4">
                    @else
                        <div class="w-16 h-16 bg-medical-blue rounded-full flex items-center justify-center text-white text-xl font-bold mr-4">
                            {{ strtoupper(substr($payslip->employee->first_name, 0, 1) . substr($payslip->employee->last_name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">{{ $payslip->employee->full_name }}</h2>
                        <p class="text-sm text-gray-600">{{ $payslip->employee->employee_no }} &bull; {{ $payslip->employee->department->name ?? '—' }} &bull; {{ $payslip->employee->designation->name ?? '—' }}</p>
                        <p class="text-sm text-gray-500 mt-1">
                            Payslip for <span class="font-medium">{{ \Carbon\Carbon::create()->month($payslip->payrollRun->month)->format('F') }} {{ $payslip->payrollRun->year }}</span>
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('hr.payroll.print-payslip', $payslip) }}" target="_blank" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                        <i class="fas fa-print mr-2"></i>Print
                    </a>
                    <a href="{{ route('hr.payroll.show', $payslip->payrollRun) }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Attendance Summary -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-medium text-gray-800 flex items-center">
                <i class="fas fa-calendar-check mr-2 text-medical-blue"></i>Attendance Summary
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                <div class="text-center p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Working Days</p>
                    <p class="text-xl font-bold text-gray-800">{{ $payslip->working_days ?? 0 }}</p>
                </div>
                <div class="text-center p-3 bg-green-50 rounded-lg">
                    <p class="text-sm text-gray-600">Present</p>
                    <p class="text-xl font-bold text-green-600">{{ $payslip->present_days ?? 0 }}</p>
                </div>
                <div class="text-center p-3 bg-red-50 rounded-lg">
                    <p class="text-sm text-gray-600">Absent</p>
                    <p class="text-xl font-bold text-red-600">{{ $payslip->absent_days ?? 0 }}</p>
                </div>
                <div class="text-center p-3 bg-yellow-50 rounded-lg">
                    <p class="text-sm text-gray-600">Leave</p>
                    <p class="text-xl font-bold text-yellow-600">{{ $payslip->leave_days ?? 0 }}</p>
                </div>
                <div class="text-center p-3 bg-blue-50 rounded-lg">
                    <p class="text-sm text-gray-600">Overtime Hours</p>
                    <p class="text-xl font-bold text-blue-600">{{ $payslip->overtime_hours ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Earnings -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-lg font-medium text-gray-800 flex items-center">
                    <i class="fas fa-plus-circle mr-2 text-green-500"></i>Earnings
                </h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Component</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @php
                            $earnings = $payslip->earnings_breakdown ?? [];
                        @endphp
                        @forelse($earnings as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-gray-900">{{ $item['component'] ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-900 text-right">{{ format_currency($item['amount'] ?? 0) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-center text-gray-500 text-sm">No earnings data</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-green-50">
                        <tr>
                            <td class="px-6 py-3 text-sm font-bold text-gray-900">Total Earnings</td>
                            <td class="px-6 py-3 text-sm font-bold text-green-600 text-right">{{ format_currency($payslip->gross_salary) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Deductions -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-lg font-medium text-gray-800 flex items-center">
                    <i class="fas fa-minus-circle mr-2 text-red-500"></i>Deductions
                </h4>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Component</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @php
                            $deductions = $payslip->deductions_breakdown ?? [];
                        @endphp
                        @forelse($deductions as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-gray-900">{{ $item['component'] ?? '—' }}</td>
                            <td class="px-6 py-3 text-sm text-gray-900 text-right">{{ format_currency($item['amount'] ?? 0) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="2" class="px-6 py-4 text-center text-gray-500 text-sm">No deductions</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-red-50">
                        <tr>
                            <td class="px-6 py-3 text-sm font-bold text-gray-900">Total Deductions</td>
                            <td class="px-6 py-3 text-sm font-bold text-red-600 text-right">{{ format_currency($payslip->total_deductions) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Net Salary Summary -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 text-center">
                <div class="p-4 bg-green-50 rounded-lg">
                    <p class="text-sm text-gray-600">Gross Salary</p>
                    <p class="text-2xl font-bold text-green-600">{{ format_currency($payslip->gross_salary) }}</p>
                </div>
                <div class="p-4 bg-red-50 rounded-lg">
                    <p class="text-sm text-gray-600">Total Deductions</p>
                    <p class="text-2xl font-bold text-red-600">{{ format_currency($payslip->total_deductions) }}</p>
                </div>
                <div class="p-4 bg-blue-50 rounded-lg border-2 border-medical-blue">
                    <p class="text-sm text-gray-600">Net Salary</p>
                    <p class="text-3xl font-bold text-medical-blue">{{ format_currency($payslip->net_salary) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Status & Mark as Paid -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-medium text-gray-800 flex items-center">
                <i class="fas fa-credit-card mr-2 text-medical-blue"></i>Payment Information
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Payment Status</span>
                    <span>
                        @php
                            $paymentBadges = [
                                'paid'    => 'bg-green-100 text-green-800',
                                'unpaid'  => 'bg-red-100 text-red-800',
                                'partial' => 'bg-yellow-100 text-yellow-800',
                            ];
                            $paymentBadge = $paymentBadges[$payslip->payment_status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $paymentBadge }}">
                            {{ ucfirst($payslip->payment_status) }}
                        </span>
                    </span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Payment Method</span>
                    <span class="font-medium">{{ ucwords(str_replace('_', ' ', $payslip->payment_method ?? '—')) }}</span>
                </div>
            </div>

            @if($payslip->payment_status !== 'paid')
            <div class="border-t border-gray-200 pt-6">
                <h5 class="text-md font-medium text-gray-800 mb-4">Mark as Paid</h5>
                <form action="{{ route('hr.payroll.mark-paid', $payslip) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                        <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="">Select Method</option>
                            <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="cash" {{ old('payment_method') == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="cheque" {{ old('payment_method') == 'cheque' ? 'selected' : '' }}>Cheque</option>
                            <option value="online" {{ old('payment_method') == 'online' ? 'selected' : '' }}>Online</option>
                        </select>
                        @error('payment_method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', now()->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('payment_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors" onclick="return confirm('Mark this payslip as paid?')">
                            <i class="fas fa-check-circle mr-2"></i>Mark as Paid
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
