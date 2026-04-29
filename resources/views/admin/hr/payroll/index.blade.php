@extends('admin.layout')

@section('title', 'Payroll')
@section('page-title', 'Payroll')

@section('content')
<!-- Generate Payroll Form -->
<div class="bg-white rounded-lg shadow-sm mb-6">
    <div class="p-6 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800">
            <i class="fas fa-calculator mr-2 text-medical-blue"></i>Generate Payroll
        </h3>
    </div>
    <div class="p-6">
        <form action="{{ route('hr.payroll.generate') }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                <select name="year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    @for($y = 2024; $y <= 2027; $y++)
                        <option value="{{ $y }}" {{ (old('year', now()->year) == $y) ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
                @error('year') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                <select name="month" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ (old('month', now()->month) == $m) ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                        </option>
                    @endforeach
                </select>
                @error('month') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-play mr-2"></i>Generate Payroll
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Payroll Runs Table -->
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Payroll Runs</h3>
                <p class="text-sm text-gray-600">Total: {{ $payrollRuns->total() }} runs</p>
            </div>
            <a href="{{ route('hr.payroll.components') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center">
                <i class="fas fa-cogs mr-2"></i>Salary Components
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month/Year</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employees</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($payrollRuns as $run)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $run->title }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        {{ \Carbon\Carbon::create()->month($run->month)->format('F') }} {{ $run->year }}
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $statusBadges = [
                                'draft'      => 'bg-yellow-100 text-yellow-800',
                                'processing' => 'bg-blue-100 text-blue-800',
                                'completed'  => 'bg-green-100 text-green-800',
                                'cancelled'  => 'bg-red-100 text-red-800',
                            ];
                            $badge = $statusBadges[$run->status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full {{ $badge }}">
                            {{ ucfirst($run->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $run->payslips_count ?? $run->payslips->count() }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ format_currency($run->total_gross) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ format_currency($run->total_net) }}</td>
                    <td class="px-6 py-4 text-sm text-gray-900">{{ $run->createdBy->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm font-medium">
                        <a href="{{ route('hr.payroll.show', $run) }}" class="text-medical-blue hover:text-blue-700" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-money-check-alt text-4xl mb-4 text-gray-300"></i>
                        <p>No payroll runs found</p>
                        <p class="text-sm mt-1">Generate your first payroll using the form above</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payrollRuns->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">
        {{ $payrollRuns->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection
