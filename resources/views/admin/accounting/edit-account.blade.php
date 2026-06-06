@extends('admin.layout')

@section('title', 'Edit Account')
@section('page-title', 'Edit Account')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-4 sm:p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800">Edit Account — {{ $account->code }}</h3>
        </div>
        <form action="{{ route('accounting.update-account', $account) }}" method="POST" class="p-4 sm:p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Account Code</label>
                <input type="text" name="code" id="code" value="{{ old('code', $account->code) }}" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Account Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $account->name) }}" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Account Type</label>
                <select name="type" id="type" required
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    <option value="asset" {{ old('type', $account->type) == 'asset' ? 'selected' : '' }}>Asset</option>
                    <option value="liability" {{ old('type', $account->type) == 'liability' ? 'selected' : '' }}>Liability</option>
                    <option value="equity" {{ old('type', $account->type) == 'equity' ? 'selected' : '' }}>Equity</option>
                    <option value="revenue" {{ old('type', $account->type) == 'revenue' ? 'selected' : '' }}>Revenue</option>
                    <option value="expense" {{ old('type', $account->type) == 'expense' ? 'selected' : '' }}>Expense</option>
                </select>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="parent_id" class="block text-sm font-medium text-gray-700 mb-1">Parent Account</label>
                <select name="parent_id" id="parent_id"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">
                    <option value="">None (Top Level)</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}" {{ old('parent_id', $account->parent_id) == $parent->id ? 'selected' : '' }}>
                            {{ $parent->code }} — {{ $parent->name }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" id="description" rows="3"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-medical-blue focus:border-medical-blue text-sm">{{ old('description', $account->description) }}</textarea>
                @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="flex items-center">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1"
                           {{ old('is_active', $account->is_active) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-medical-blue focus:ring-medical-blue">
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="bg-medical-blue text-white px-6 py-2.5 rounded-lg hover:bg-blue-700 transition-colors text-sm">
                    <i class="fas fa-save mr-2"></i> Update Account
                </button>
                <a href="{{ route('accounting.chart-of-accounts') }}" class="text-gray-600 hover:text-gray-800 text-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
