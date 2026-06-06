@extends('admin.layout')

@section('title', 'Edit Journal Entry')
@section('page-title', 'Edit Journal Entry')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 sm:p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Edit Journal Entry — {{ $journalEntry->entry_number }}</h3>
            <p class="text-sm text-gray-500 mt-1">Debits must equal credits for the entry to be valid.</p>
        </div>

        @if($errors->any())
        <div class="mx-4 sm:mx-6 mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-sm text-red-800 font-medium"><i class="fas fa-exclamation-circle mr-1"></i> Please fix the following:</p>
            <ul class="text-sm text-red-700 list-disc list-inside mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        @if(session('error'))
        <div class="mx-4 sm:mx-6 mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-sm text-red-800"><i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}</p>
        </div>
        @endif

        <form action="{{ route('accounting.update-journal-entry', $journalEntry) }}" method="POST" class="p-4 sm:p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="entry_date" class="block text-sm font-medium text-gray-700 mb-1">Entry Date</label>
                    <input type="date" name="entry_date" id="entry_date"
                        value="{{ old('entry_date', $journalEntry->entry_date->format('Y-m-d')) }}" required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description / Reference</label>
                    <input type="text" name="description" id="description"
                        value="{{ old('description', $journalEntry->description) }}" required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                </div>
            </div>

            {{-- Journal Lines --}}
            <div>
                <div class="flex justify-between items-center mb-3">
                    <h4 class="text-sm font-semibold text-gray-700">Entry Lines</h4>
                    <button type="button" onclick="addLine()" class="text-sm text-medical-blue hover:text-blue-700">
                        <i class="fas fa-plus mr-1"></i> Add Line
                    </button>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-lg">
                    <table class="w-full text-sm" id="linesTable">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-2/5">Account</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase w-1/5">Narration</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-1/6">Debit</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase w-1/6">Credit</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 uppercase w-10"></th>
                            </tr>
                        </thead>
                        <tbody id="linesBody">
                            @foreach($journalEntry->lines as $i => $line)
                            <tr class="border-t border-gray-200">
                                <td class="px-3 py-2">
                                    <select name="lines[{{ $i }}][account_id]" required
                                        class="w-full border-gray-300 rounded text-sm focus:ring-medical-blue focus:border-medical-blue">
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ $line->account_id == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} — {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="text" name="lines[{{ $i }}][narration]" value="{{ $line->narration }}"
                                        class="w-full border-gray-300 rounded text-sm focus:ring-medical-blue focus:border-medical-blue"
                                        placeholder="Optional">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" name="lines[{{ $i }}][debit]" step="0.01" min="0" value="{{ $line->debit > 0 ? $line->debit : 0 }}"
                                        class="w-full border-gray-300 rounded text-sm text-right focus:ring-medical-blue focus:border-medical-blue debit-input"
                                        oninput="updateTotals()">
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" name="lines[{{ $i }}][credit]" step="0.01" min="0" value="{{ $line->credit > 0 ? $line->credit : 0 }}"
                                        class="w-full border-gray-300 rounded text-sm text-right focus:ring-medical-blue focus:border-medical-blue credit-input"
                                        oninput="updateTotals()">
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <button type="button" onclick="removeLine(this)" class="text-red-400 hover:text-red-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 border-t-2 border-gray-300">
                            <tr>
                                <td class="px-3 py-2 text-sm font-semibold text-gray-700" colspan="2">Totals</td>
                                <td class="px-3 py-2 text-right text-sm font-bold text-gray-900" id="totalDebit">0.00</td>
                                <td class="px-3 py-2 text-right text-sm font-bold text-gray-900" id="totalCredit">0.00</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="px-3 py-1">
                                    <p class="text-xs text-center" id="balanceStatus">
                                        <span class="text-gray-400">Checking...</span>
                                    </p>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-medical-blue text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                    <i class="fas fa-save mr-2"></i> Update Journal Entry
                </button>
                <a href="{{ route('accounting.journal-entries') }}" class="text-gray-600 hover:text-gray-800 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
var lineIndex = {{ $journalEntry->lines->count() }};

function addLine() {
    var tbody = document.getElementById('linesBody');
    var accountOptions = document.querySelector('select[name="lines[0][account_id]"]').innerHTML;

    var row = document.createElement('tr');
    row.className = 'border-t border-gray-200';
    row.innerHTML = `
        <td class="px-3 py-2">
            <select name="lines[${lineIndex}][account_id]" required
                class="w-full border-gray-300 rounded text-sm focus:ring-medical-blue focus:border-medical-blue">
                ${accountOptions}
            </select>
        </td>
        <td class="px-3 py-2">
            <input type="text" name="lines[${lineIndex}][narration]"
                class="w-full border-gray-300 rounded text-sm focus:ring-medical-blue focus:border-medical-blue"
                placeholder="Optional">
        </td>
        <td class="px-3 py-2">
            <input type="number" name="lines[${lineIndex}][debit]" step="0.01" min="0" value="0"
                class="w-full border-gray-300 rounded text-sm text-right focus:ring-medical-blue focus:border-medical-blue debit-input"
                oninput="updateTotals()">
        </td>
        <td class="px-3 py-2">
            <input type="number" name="lines[${lineIndex}][credit]" step="0.01" min="0" value="0"
                class="w-full border-gray-300 rounded text-sm text-right focus:ring-medical-blue focus:border-medical-blue credit-input"
                oninput="updateTotals()">
        </td>
        <td class="px-3 py-2 text-center">
            <button type="button" onclick="removeLine(this)" class="text-red-400 hover:text-red-600">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    lineIndex++;
}

function removeLine(btn) {
    var tbody = document.getElementById('linesBody');
    if (tbody.rows.length <= 2) {
        alert('A journal entry needs at least 2 lines.');
        return;
    }
    btn.closest('tr').remove();
    updateTotals();
}

function updateTotals() {
    var debits = document.querySelectorAll('.debit-input');
    var credits = document.querySelectorAll('.credit-input');

    var totalDebit = 0, totalCredit = 0;
    debits.forEach(function(el) { totalDebit += parseFloat(el.value) || 0; });
    credits.forEach(function(el) { totalCredit += parseFloat(el.value) || 0; });

    document.getElementById('totalDebit').textContent = totalDebit.toFixed(2);
    document.getElementById('totalCredit').textContent = totalCredit.toFixed(2);

    var status = document.getElementById('balanceStatus');
    var diff = Math.abs(totalDebit - totalCredit);

    if (totalDebit === 0 && totalCredit === 0) {
        status.innerHTML = '<span class="text-gray-400">Enter amounts above</span>';
    } else if (diff < 0.01) {
        status.innerHTML = '<span class="text-green-600 font-medium"><i class="fas fa-check-circle mr-1"></i>Balanced</span>';
    } else {
        status.innerHTML = '<span class="text-red-600 font-medium"><i class="fas fa-exclamation-circle mr-1"></i>Difference: ' + diff.toFixed(2) + '</span>';
    }
}

// Calculate on page load
document.addEventListener('DOMContentLoaded', updateTotals);
</script>
@endsection
