@extends('admin.layout')

@section('title', 'Bills Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Bills Management</h1>
    <a href="{{ route('bills.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
        <i class="fas fa-plus mr-2"></i>Create Bill
    </a>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bill #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($bills as $bill)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">{{ $bill->bill_number }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ $bill->patient->name }}</div>
                        <div class="text-sm text-gray-500">{{ $bill->patient->phone }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded uppercase">{{ $bill->bill_type }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">₨{{ number_format($bill->total_amount, 2) }}</div>
                        @if($bill->due_amount > 0)
                            <div class="text-sm text-red-500">Due: ₨{{ number_format($bill->due_amount, 2) }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="bg-{{ $bill->status_color }}-100 text-{{ $bill->status_color }}-800 text-xs px-2 py-1 rounded capitalize">{{ $bill->status }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $bill->bill_date->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('bills.show', $bill) }}" class="text-medical-blue hover:text-blue-700 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('bills.edit', $bill) }}" class="text-medical-blue hover:text-blue-700 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('bills.destroy', $bill) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4">
        {{ $bills->links() }}
    </div>
</div>
@endsection