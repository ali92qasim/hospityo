@extends('admin.layout')

@section('title', 'Journal Entries')
@section('page-title', 'Journal Entries')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    {{-- Filter Bar --}}
    <div class="p-4 sm:p-6 border-b border-gray-200">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end gap-3 mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Journal Entries</h3>
            <a href="{{ route('accounting.create-journal-entry') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center text-sm">
                <i class="fas fa-plus mr-2"></i> New Entry
            </a>
        </div>
        <form method="GET" action="{{ route('accounting.journal-entries') }}" class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1 w-full sm:w-auto">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm"
                    placeholder="Entry # or description">
            </div>
            <div>
                <label for="from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                <input type="date" name="from" id="from" value="{{ request('from') }}"
                    class="border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
            </div>
            <div>
                <label for="to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                <input type="date" name="to" id="to" value="{{ request('to') }}"
                    class="border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
            </div>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors text-sm min-h-[42px]">
                <i class="fas fa-search mr-1"></i> Filter
            </button>
        </form>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entry #</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                    <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Debit</th>
                    <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Credit</th>
                    <th class="px-4 lg:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>
                    <th class="px-4 lg:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($entries as $entry)
                <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleLines('lines-{{ $entry->id }}')">
                    <td class="px-4 lg:px-6 py-3 text-sm font-medium text-medical-blue">{{ $entry->entry_number }}</td>
                    <td class="px-4 lg:px-6 py-3 text-sm text-gray-900">{{ $entry->entry_date->format('Y-m-d') }}</td>
                    <td class="px-4 lg:px-6 py-3 text-sm text-gray-700">{{ Str::limit($entry->description, 50) }}</td>
                    <td class="px-4 lg:px-6 py-3 text-sm text-right text-gray-900">{{ format_currency($entry->lines->sum('debit')) }}</td>
                    <td class="px-4 lg:px-6 py-3 text-sm text-right text-gray-900">{{ format_currency($entry->lines->sum('credit')) }}</td>
                    <td class="px-4 lg:px-6 py-3 text-center">
                        @if($entry->is_auto)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Auto</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Manual</span>
                        @endif
                    </td>
                    <td class="px-4 lg:px-6 py-3 text-sm text-gray-600">{{ $entry->createdBy?->name ?? '—' }}</td>
                    <td class="px-4 lg:px-6 py-3 text-sm text-center">
                        @if(!$entry->is_auto)
                        <a href="{{ route('accounting.edit-journal-entry', $entry) }}" class="text-medical-blue hover:text-blue-700" title="Edit" onclick="event.stopPropagation()">
                            <i class="fas fa-edit"></i>
                        </a>
                        @else
                        <span class="text-gray-300" title="Auto entries cannot be edited"><i class="fas fa-lock"></i></span>
                        @endif
                    </td>
                </tr>
                {{-- Expandable Lines --}}
                <tr id="lines-{{ $entry->id }}" class="hidden">
                    <td colspan="8" class="px-4 lg:px-6 py-3 bg-gray-50">
                        <table class="w-full text-sm">
                            <thead>
                                <tr>
                                    <th class="text-left text-xs text-gray-500 pb-2">Account</th>
                                    <th class="text-left text-xs text-gray-500 pb-2">Narration</th>
                                    <th class="text-right text-xs text-gray-500 pb-2">Debit</th>
                                    <th class="text-right text-xs text-gray-500 pb-2">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($entry->lines as $line)
                                <tr>
                                    <td class="py-1.5 text-gray-800">{{ $line->account->code }} — {{ $line->account->name }}</td>
                                    <td class="py-1.5 text-gray-600">{{ $line->narration ?? '—' }}</td>
                                    <td class="py-1.5 text-right text-gray-900">{{ $line->debit > 0 ? format_currency($line->debit) : '' }}</td>
                                    <td class="py-1.5 text-right text-gray-900">{{ $line->credit > 0 ? format_currency($line->credit) : '' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-journal-whills text-4xl mb-4 text-gray-300"></i>
                        <p>No journal entries found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($entries->hasPages())
    <div class="px-4 sm:px-6 py-4 border-t border-gray-200">
        {{ $entries->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
    function toggleLines(id) {
        const row = document.getElementById(id);
        if (row) row.classList.toggle('hidden');
    }
</script>
@endpush
@endsection