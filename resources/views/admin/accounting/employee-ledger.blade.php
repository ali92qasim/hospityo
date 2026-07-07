@extends('admin.layout')

@section('title', 'Employee Salary Ledger')
@section('page-title', 'Employee Salary Ledger')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-4 sm:p-6 border-b border-gray-200">
        <form method="GET" action="{{ route('accounting.employee-ledger') }}" class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1 w-full sm:w-auto">
                <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
                <select name="employee_id" id="employee_id"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    <option value="">Select Employee</option>
                    @foreach($employees as $item)
                        <option value="{{ $item->id }}" {{ (string) $employeeId === (string) $item->id ? 'selected' : '' }}>
                            {{ $item->full_name }} ({{ $item->employee_no }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors text-sm min-h-[42px]">
                <i class="fas fa-filter mr-1"></i> Apply
            </button>
        </form>
    </div>

    @if($employeeId)
        @if($employee?->expenseAccount)
            <div class="px-4 sm:px-6 py-4 bg-gray-50 border-b border-gray-200 text-sm text-gray-700">
                <span class="font-medium">Expense account:</span>
                {{ $employee->expenseAccount->code }} — {{ $employee->expenseAccount->name }}
                <span class="ml-4 font-medium">Balance:</span>
                {{ format_currency($balance) }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entry #</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Narration</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($lines as $line)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 lg:px-6 py-3 text-sm text-gray-900">{{ $line->created_at->format('Y-m-d') }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-medical-blue font-medium">{{ $line->journalEntry->entry_number ?? '—' }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-gray-700">{{ $line->narration ?? $line->journalEntry->description ?? '—' }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-right text-gray-900">{{ $line->debit > 0 ? format_currency($line->debit) : '' }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-right text-gray-900">{{ $line->credit > 0 ? format_currency($line->credit) : '' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-user-tie text-4xl mb-4 text-gray-300"></i>
                                <p>No salary expense entries found for this employee.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($lines->count())
                <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                    <tr>
                        <td colspan="3" class="px-4 lg:px-6 py-3 text-sm font-semibold text-gray-800">Totals</td>
                        <td class="px-4 lg:px-6 py-3 text-sm text-right font-semibold text-gray-900">{{ format_currency($lines->sum('debit')) }}</td>
                        <td class="px-4 lg:px-6 py-3 text-sm text-right font-semibold text-gray-900">{{ format_currency($lines->sum('credit')) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    @else
        <div class="px-6 py-12 text-center text-gray-500">
            <i class="fas fa-user-tie text-4xl mb-4 text-gray-300"></i>
            <p>Select an employee to view their salary expense ledger</p>
        </div>
    @endif
</div>
@endsection
