@extends('admin.layout')

@section('title', 'Record Deposit')
@section('page-title', 'Record Deposit')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 sm:p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Record Deposit</h3>
            <p class="text-sm text-gray-500 mt-1">Deposit funds into a cash or bank account from a source account.</p>
        </div>

        @if(session('error'))
        <div class="mx-4 sm:mx-6 mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-sm text-red-800"><i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}</p>
        </div>
        @endif

        <form action="{{ route('accounting.process-deposit') }}" method="POST" class="p-4 sm:p-6 space-y-4">
            @csrf

            <div>
                <label for="to_account_id" class="block text-sm font-medium text-gray-700 mb-1">Deposit To (Cash/Bank Account)</label>
                <select name="to_account_id" id="to_account_id" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    <option value="">Select Target Account</option>
                    @foreach($cashAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('to_account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->code }} — {{ $account->name }}
                        </option>
                    @endforeach
                </select>
                @error('to_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="from_account_id" class="block text-sm font-medium text-gray-700 mb-1">Source Account</label>
                <select name="from_account_id" id="from_account_id" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    <option value="">Select Source Account</option>
                    @foreach($sourceAccounts as $account)
                        <option value="{{ $account->id }}" {{ old('from_account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->code }} — {{ $account->name }}
                        </option>
                    @endforeach
                </select>
                @error('from_account_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                    <input type="number" name="amount" id="amount" step="0.01" min="0.01" value="{{ old('amount') }}" required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm"
                        placeholder="0.00">
                    @error('amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" name="date" id="date" value="{{ old('date', date('Y-m-d')) }}" required
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    @error('date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <input type="text" name="description" id="description" value="{{ old('description') }}" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm"
                    placeholder="e.g. Cash deposit from daily collections">
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-medical-blue text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                    <i class="fas fa-arrow-down mr-2"></i> Record Deposit
                </button>
                <a href="{{ route('accounting.chart-of-accounts') }}" class="text-gray-600 hover:text-gray-800 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
