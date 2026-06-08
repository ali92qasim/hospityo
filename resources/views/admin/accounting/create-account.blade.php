@extends('admin.layout')

@section('title', 'Create Account')
@section('page-title', 'Create Account')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 sm:p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">New Account</h3>
        </div>
        <form action="{{ route('accounting.store-account') }}" method="POST" class="p-4 sm:p-6 space-y-4">
            @csrf

            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Account Code</label>
                <input type="text" name="code" id="code" value="{{ old('code') }}" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm"
                    placeholder="e.g. 1001">
                @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm"
                    placeholder="e.g. Cash in Hand">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                <select name="type" id="type" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    <option value="">Select Type</option>
                    <option value="asset" {{ old('type') == 'asset' ? 'selected' : '' }}>Asset</option>
                    <option value="liability" {{ old('type') == 'liability' ? 'selected' : '' }}>Liability</option>
                    <option value="equity" {{ old('type') == 'equity' ? 'selected' : '' }}>Equity</option>
                    <option value="revenue" {{ old('type') == 'revenue' ? 'selected' : '' }}>Revenue</option>
                    <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Expense</option>
                </select>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">Parent Account</label>
                <select name="parent_id" id="parent_id"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    <option value="">None (Top Level)</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                            {{ $parent->code }} — {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="opening_balance" class="block text-sm font-medium text-gray-700 mb-1">Opening Balance</label>
                <input type="number" name="opening_balance" id="opening_balance" step="0.01" min="0" value="{{ old('opening_balance', 0) }}"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm"
                    placeholder="0.00">
                <p class="text-xs text-gray-400 mt-1">Leave as 0 if no opening balance. Creates a journal entry against Opening Balance Equity.</p>
                @error('opening_balance') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="3"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm"
                    placeholder="Optional description">{{ old('description') }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-medical-blue text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                    <i class="fas fa-save mr-2"></i> Save Account
                </button>
                <a href="{{ route('accounting.chart-of-accounts') }}" class="text-gray-600 hover:text-gray-800 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection