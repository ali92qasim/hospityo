@extends('admin.layout')

@section('title', 'Close Fiscal Year — ' . $fiscalYear->name)
@section('page-title', 'Close Fiscal Year')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Pre-Close Summary: {{ $fiscalYear->name }}</h3>
            <p class="text-sm text-gray-600 mt-1">
                Period: {{ $fiscalYear->start_date->format('M d, Y') }} — {{ $fiscalYear->end_date->format('M d, Y') }}
            </p>
        </div>

        <div class="p-6 space-y-6">
            {{-- Summary Stats --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase">Journal Entries</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($entryCount) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase">Total Debits</p>
                    <p class="text-2xl font-bold text-gray-800">{{ format_currency($totals->total_debit) }}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-xs text-gray-500 uppercase">Total Credits</p>
                    <p class="text-2xl font-bold text-gray-800">{{ format_currency($totals->total_credit) }}</p>
                </div>
            </div>

            {{-- P&L Summary --}}
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-blue-800 mb-2">Period P&L</h4>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">Revenue:</span>
                        <span class="font-medium text-green-700">{{ format_currency($revenue) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Expenses:</span>
                        <span class="font-medium text-red-700">{{ format_currency($expenses) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Net Income:</span>
                        <span class="font-bold {{ $netIncome >= 0 ? 'text-green-700' : 'text-red-700' }}">
                            {{ format_currency(abs($netIncome)) }}{{ $netIncome < 0 ? ' (Loss)' : '' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Anomalies --}}
            @if($unbalancedEntries->count() > 0)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-red-800 mb-2">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Unbalanced Entries ({{ $unbalancedEntries->count() }})
                </h4>
                <p class="text-xs text-red-700 mb-2">These entries have debits ≠ credits. Fix them before closing.</p>
                <ul class="text-xs text-red-700 space-y-1 max-h-32 overflow-y-auto">
                    @foreach($unbalancedEntries->take(10) as $entry)
                    <li>{{ $entry->entry_number }} — {{ $entry->description }} (DR: {{ format_currency($entry->lines->sum('debit')) }}, CR: {{ format_currency($entry->lines->sum('credit')) }})</li>
                    @endforeach
                    @if($unbalancedEntries->count() > 10)
                    <li class="font-medium">... and {{ $unbalancedEntries->count() - 10 }} more</li>
                    @endif
                </ul>
            </div>
            @endif

            @if($emptyEntries->count() > 0)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-yellow-800 mb-2">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Empty Entries ({{ $emptyEntries->count() }})
                </h4>
                <p class="text-xs text-yellow-700">These journal entries have no lines. They may be artifacts from failed operations.</p>
            </div>
            @endif

            @if($unbalancedEntries->isEmpty() && $emptyEntries->isEmpty())
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-green-800">
                    <i class="fas fa-check-circle mr-1"></i>
                    No anomalies found. All entries are balanced and complete.
                </p>
            </div>
            @endif

            {{-- Warning --}}
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <p class="text-sm text-amber-800 font-medium">
                    <i class="fas fa-lock mr-1"></i>
                    Closing this fiscal year is irreversible. After closing:
                </p>
                <ul class="text-sm text-amber-700 mt-2 list-disc list-inside space-y-1">
                    <li>No new journal entries can be posted to dates within this period</li>
                    <li>Bills with dates in this period cannot be created or edited</li>
                    <li>Payments dated in this period cannot be added or modified</li>
                    <li>System reversals (for corrections) are still allowed</li>
                </ul>
            </div>

            {{-- Confirmation Form --}}
            <form method="POST" action="{{ route('accounting.fiscal-years.close', $fiscalYear) }}" class="border-t border-gray-200 pt-6">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        To confirm, type: <code class="bg-gray-100 px-2 py-0.5 rounded text-red-700 font-mono">CLOSE {{ $fiscalYear->name }}</code>
                    </label>
                    <input type="text" name="confirmation" autocomplete="off" placeholder="Type the confirmation text above"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                    @error('confirmation')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('accounting.fiscal-years') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        <i class="fas fa-lock mr-2"></i>Close Fiscal Year Permanently
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
