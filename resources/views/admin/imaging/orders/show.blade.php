@extends('admin.layout')

@section('title', 'Imaging Order — {{ $imagingOrder->order_number }}')
@section('page-title', 'Imaging Order')
@section('page-description', 'View imaging order details')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">{{ $imagingOrder->order_number }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $imagingOrder->items->count() }} {{ Str::plural('study', $imagingOrder->items->count()) }}
                    &bull; Ordered {{ $imagingOrder->ordered_at?->format('M d, Y h:i A') }}
                </p>
            </div>
            <div class="flex items-center space-x-3">
                @php
                    $statusColors = [
                        'ordered'   => 'bg-blue-100 text-blue-800',
                        'testing'   => 'bg-purple-100 text-purple-800',
                        'verified'  => 'bg-orange-100 text-orange-800',
                        'reported'  => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                    ];
                @endphp
                <span class="px-3 py-1 text-sm rounded-full {{ $statusColors[$imagingOrder->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst(str_replace('_', ' ', $imagingOrder->status)) }}
                </span>
                @if($imagingOrder->status === 'ordered')
                    <a href="{{ route('imaging.orders.edit', $imagingOrder) }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                @endif
                <a href="{{ route('imaging.orders.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Back
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Patient</h4>
                <p class="font-medium text-gray-800">{{ $imagingOrder->patient?->name }}</p>
                <p class="text-sm text-gray-500">{{ $imagingOrder->patient?->patient_no }}</p>
            </div>
            <div class="bg-white rounded-lg shadow-sm p-4">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Ordering Doctor</h4>
                <p class="font-medium text-gray-800">Dr. {{ $imagingOrder->doctor?->name }}</p>
            </div>
            @if($imagingOrder->special_instructions)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="text-xs font-semibold text-yellow-700 uppercase tracking-wider mb-2">Special Instructions</h4>
                <p class="text-sm text-yellow-800">{{ $imagingOrder->special_instructions }}</p>
            </div>
            @endif
        </div>

        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h4 class="font-semibold text-gray-800">Imaging studies</h4>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3 text-left">Study</th>
                            <th class="px-4 py-3 text-center">Qty</th>
                            <th class="px-4 py-3 text-center">Priority</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($imagingOrder->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ $item->imagingStudy?->name }}</p>
                                @if($item->clinical_notes)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $item->clinical_notes }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 text-xs rounded-full {{ $item->priority === 'stat' ? 'bg-red-100 text-red-800' : ($item->priority === 'urgent' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600') }}">
                                    {{ strtoupper($item->priority) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-800">{{ ucfirst($item->status) }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">No studies on this order.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
