@extends('admin.layout')

@section('title', 'Payroll Details')
@section('page-title', 'Payroll Details')

@section('content')
<div class="max-w-7xl mx-auto">

    <!-- Header Card -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">{{ $payrollRun->title }}</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $payrollRun->period_label }}
                    </p>
                    <div class="mt-2">
                        @php
                            $statusBadges = [
                                'draft'      => 'bg-yellow-100 text-yellow-800',
                                'processing' => 'bg-blue-100 text-blue-800',
                                'completed'  => 'bg-green-100 text-green-800',
                                'cancelled'  => 'bg-red-100 text-red-800',
                            ];
                            $badge = $statusBadges[$payrollRun->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-3 py-1 text-sm rounded-full {{ $badge }}">
                            {{ ucfirst($payrollRun->status) }}
                        </span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if($payrollRun->status === 'draft')
                        <form action="{{ route('hr.payroll.approve', $payrollRun) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to approve this payroll run?')">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center">
                                <i class="fas fa-check mr-2"></i>Approve
                            </button>
                        </form>
                        <form action="{{ route('hr.payroll.cancel', $payrollRun) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this payroll run?')">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('hr.payroll.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i>Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Employees</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $payrollRun->payslips->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-medical-blue text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Gross</p>
                    <p class="text-2xl font-bold text-gray-800">{{ format_currency($payrollRun->total_gross) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Deductions</p>
                    <p class="text-2xl font-bold text-red-600">{{ format_currency($payrollRun->total_deductions) }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-minus-circle text-red-600 text-xl"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">Total Net</p>
                    <p class="text-2xl font-bold text-medical-blue">{{ format_currency($payrollRun->total_net) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-wallet text-medical-blue text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Payslips Table -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-file-invoice-dollar mr-2 text-medical-blue"></i>Payslips
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Basic</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Allowances</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($payrollRun->payslips as $payslip)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($payslip->employee->photo)
                                    <img src="{{ asset('storage/' . $payslip->employee->photo) }}" alt="{{ $payslip->employee->full_name }}" class="w-10 h-10 rounded-full object-cover mr-3">
                                @else
                                    <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center text-white text-sm font-medium mr-3">
                                        {{ strtoupper(substr($payslip->employee->first_name, 0, 1) . substr($payslip->employee->last_name, 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $payslip->employee->full_name }}</div>
                                    <div class="text-xs text-gray-500">{{ $payslip->employee->employee_no }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $payslip->employee->department->name ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ format_currency($payslip->basic_salary) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ format_currency($payslip->total_allowances) }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ format_currency($payslip->gross_salary) }}</td>
                        <td class="px-6 py-4 text-sm text-red-600">{{ format_currency($payslip->total_deductions) }}</td>
                        <td class="px-6 py-4 text-sm font-bold text-gray-900">{{ format_currency($payslip->net_salary) }}</td>
                        <td class="px-6 py-4">
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
                        </td>
                        <td class="px-6 py-4 text-sm font-medium">
                            <a href="{{ route('hr.payroll.payslip', $payslip) }}" class="text-medical-blue hover:text-blue-700" title="View Payslip">
                                <i class="fas fa-file-alt"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-file-invoice text-4xl mb-4 text-gray-300"></i>
                            <p>No payslips generated</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
