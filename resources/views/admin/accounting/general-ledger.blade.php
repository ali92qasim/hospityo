@extends('admin.layout')

@section('title', 'General Ledger')
@section('page-title', 'General Ledger')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    {{-- Filter Bar --}}
    <div class="p-4 sm:p-6 border-b border-gray-200">
        <form method="GET" action="{{ route('accounting.general-ledger') }}" class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1 w-full sm:w-auto">
                <label for="account_id" class="block text-sm font-medium text-gray-700 mb-1">Account</label>
                <select name="account_id" id="account_id"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    <option value="">Select Account</option>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ request('account_id') == $acc->id ? 'selected' : '' }}>
                            {{ $acc->code }} — {{ $acc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" name="from" id="from" value="{{ $from }}"
                    class="border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
            </div>
            <div>
                <label for="to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" name="to" id="to" value="{{ $to }}"
                    class="border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
            </div>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors text-sm min-h-[42px]">
                <i class="fas fa-filter mr-1"></i> Apply
            </button>
        </form>
    </div>

    {{-- Ledger Table --}}
    @if($account)
        <div class="p-4 sm:p-6 border-b border-gray-200 bg-gray-50">
            <h3 class="text-sm font-semibold text-gray-800">{{ $account->code }} — {{ $account->name }}</h3>
            <p class="text-xs text-gray-500">{{ ucfirst($account->type) }} Account &middot; {{ $from }} to {{ $to }}</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entry #</th>
                        <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                        <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Running Balance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php $runningBalance = 0; @endphp
                    @forelse($entries as $line)
                        @php
                            $isDebitNormal = in_array($account->type, ['asset', 'expense']);
                            $runningBalance += $isDebitNormal
                                ? ($line->debit - $line->credit)
                                : ($line->credit - $line->debit);
                        @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 lg:px-6 py-3 text-sm text-gray-900">{{ $line->journalEntry->entry_date->format('Y-m-d') }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-medical-blue font-medium">{{ $line->journalEntry->entry_number }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-gray-700">{{ $line->journalEntry->description ?? $line->narration ?? '—' }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-right text-gray-900">{{ $line->debit > 0 ? format_currency($line->debit) : '' }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-right text-gray-900">{{ $line->credit > 0 ? format_currency($line->credit) : '' }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-right font-medium {{ $runningBalance >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                {{ format_currency(abs($runningBalance)) }}{{ $runningBalance < 0 ? ' CR' : ' DR' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-file-alt text-4xl mb-4 text-gray-300"></i>
                                <p>No entries found for this period.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-12 text-center text-gray-500">
            <i class="fas fa-book-open text-4xl mb-4 text-gray-300"></i>
            <p>Select an account to view ledger</p>
        </div>
    @endif
</div>
@endsection