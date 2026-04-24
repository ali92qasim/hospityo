@extends('admin.layout')

@section('title', 'Chart of Accounts')
@section('page-title', 'Chart of Accounts')

@section('content')
<div class="mb-4 flex justify-end">
    <a href="{{ route('accounting.create-account') }}" class="bg-medical-blue text-white px-4 py-2.5 rounded-lg hover:bg-blue-700 transition-colors flex items-center text-sm">
        <i class="fas fa-plus mr-2"></i> Add Account
    </a>
</div>

@php
    $typeConfig = [
        'asset'     => ['label' => 'Assets',      'color' => 'bg-green-600'],
        'liability' => ['label' => 'Liabilities',  'color' => 'bg-red-600'],
        'equity'    => ['label' => 'Equity',       'color' => 'bg-blue-600'],
        'revenue'   => ['label' => 'Revenue',      'color' => 'bg-purple-600'],
        'expense'   => ['label' => 'Expenses',     'color' => 'bg-orange-500'],
    ];
@endphp

<div class="space-y-6">
    @foreach($typeConfig as $type => $config)
        @if(isset($grouped[$type]) && $grouped[$type]->count())
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="{{ $config['color'] }} px-4 sm:px-6 py-3">
                <h3 class="text-white font-semibold text-sm sm:text-base">{{ $config['label'] }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-4 lg:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent</th>
                            <th class="px-4 lg:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                            <th class="px-4 lg:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 lg:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($grouped[$type] as $account)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 lg:px-6 py-3 text-sm font-mono text-gray-900">{{ $account->code }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-gray-900">{{ $account->name }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-gray-500">{{ $account->parent?->name ?? '—' }}</td>
                            <td class="px-4 lg:px-6 py-3 text-sm text-right text-gray-900">
                                @php
                                    try {
                                        $balance = format_currency($account->getBalance());
                                    } catch (\Exception $e) {
                                        $balance = null;
                                    }
                                @endphp
                                @if($balance)
                                    {{ $balance }}
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 lg:px-6 py-3 text-center">
                                @if($account->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 lg:px-6 py-3 text-center">
                                <a href="#" class="text-medical-blue hover:text-blue-700 text-sm" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    @endforeach
</div>

@if($accounts->isEmpty())
<div class="bg-white rounded-lg shadow-sm px-6 py-12 text-center text-gray-500">
    <i class="fas fa-book text-4xl mb-4 text-gray-300"></i>
    <p>No accounts found. Start by adding your first account.</p>
</div>
@endif
@endsection