@extends('admin.layout')

@section('title', 'Investigation Order — {{ $investigationOrder->order_number }}')
@section('page-title', 'Investigation Order')
@section('page-description', 'View order details and investigations')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Header card --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">{{ $investigationOrder->order_number }}</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $investigationOrder->items->count() }} investigation{{ $investigationOrder->items->count() !== 1 ? 's' : '' }}
                    &bull; Ordered {{ $investigationOrder->ordered_at?->format('M d, Y h:i A') }}
                </p>
            </div>
            <div class="flex items-center space-x-3">
                @php
                    $statusColors = [
                        'ordered'   => 'bg-blue-100 text-blue-800',
                        'collected' => 'bg-yellow-100 text-yellow-800',
                        'testing'   => 'bg-purple-100 text-purple-800',
                        'verified'  => 'bg-orange-100 text-orange-800',
                        'reported'  => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800',
                    ];
                @endphp
                <span class="px-3 py-1 text-sm rounded-full {{ $statusColors[$investigationOrder->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst(str_replace('_', ' ', $investigationOrder->status)) }}
                </span>
                @if($investigationOrder->status === 'ordered')
                    <a href="{{ route('investigation-orders.edit', $investigationOrder) }}" class="inline-flex items-center px-3 py-1.5 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                        <i class="fas fa-edit mr-1"></i>Edit
                    </a>
                @endif
                @if($investigationOrder->items->contains(fn ($item) => $item->status === 'reported'))
                    <a href="{{ route('investigation-orders.report', $investigationOrder) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-800 text-white rounded-lg hover:bg-gray-900">
                        <i class="fas fa-print mr-1"></i>Print Report
                    </a>
                @endif
                <a href="{{ route('investigation-orders.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Back
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Patient / Doctor / Notes --}}
        <div class="space-y-4">
            <div class="bg-white rounded-lg shadow-sm p-4">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Patient</h4>
                <p class="font-medium text-gray-800">{{ $investigationOrder->patient?->name }}</p>
                <p class="text-sm text-gray-500">{{ $investigationOrder->patient?->patient_no }}</p>
                <p class="text-sm text-gray-500">{{ $investigationOrder->patient?->age }} yrs &bull; {{ ucfirst($investigationOrder->patient?->gender) }}</p>
                <p class="text-sm text-gray-500">{{ $investigationOrder->patient?->phone }}</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-4">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Ordering Doctor</h4>
                <p class="font-medium text-gray-800">Dr. {{ $investigationOrder->doctor?->name }}</p>
                <p class="text-sm text-gray-500">{{ $investigationOrder->doctor?->specialization }}</p>
            </div>

            @if($investigationOrder->special_instructions)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="text-xs font-semibold text-yellow-700 uppercase tracking-wider mb-2">Special Instructions</h4>
                <p class="text-sm text-yellow-800">{{ $investigationOrder->special_instructions }}</p>
            </div>
            @endif

            {{-- Timeline --}}
            <div class="bg-white rounded-lg shadow-sm p-4">
                <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Timeline</h4>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center">
                        <div class="w-2.5 h-2.5 bg-blue-500 rounded-full mr-3 flex-shrink-0"></div>
                        <div>
                            <span class="font-medium">Ordered</span>
                            <div class="text-gray-500 text-xs">{{ $investigationOrder->ordered_at?->format('M d, Y h:i A') }}</div>
                        </div>
                    </div>
                    @if($investigationOrder->sample_collected_at)
                    <div class="flex items-center">
                        <div class="w-2.5 h-2.5 bg-yellow-500 rounded-full mr-3 flex-shrink-0"></div>
                        <div>
                            <span class="font-medium">Sample Collected</span>
                            <div class="text-gray-500 text-xs">{{ $investigationOrder->sample_collected_at->format('M d, Y h:i A') }}</div>
                        </div>
                    </div>
                    @endif
                    @if($investigationOrder->completed_at)
                    <div class="flex items-center">
                        <div class="w-2.5 h-2.5 bg-green-500 rounded-full mr-3 flex-shrink-0"></div>
                        <div>
                            <span class="font-medium">Completed</span>
                            <div class="text-gray-500 text-xs">{{ $investigationOrder->completed_at->format('M d, Y h:i A') }}</div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="bg-white rounded-lg shadow-sm p-4 space-y-2">
                @if($investigationOrder->status === 'ordered')
                    <form action="{{ route('investigation-orders.collect-sample', $investigationOrder) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 text-sm">
                            <i class="fas fa-vial mr-2"></i>Mark Sample Collected
                        </button>
                    </form>
                @endif
                @if($investigationOrder->status === 'collected')
                    <form action="{{ route('investigation-orders.receive-sample', $investigationOrder) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-sm">
                            <i class="fas fa-check mr-2"></i>Mark Sample Received
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Right: Investigations table --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h4 class="font-semibold text-gray-800">Investigations</h4>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3 text-left">Investigation</th>
                            <th class="px-4 py-3 text-center">Qty</th>
                            <th class="px-4 py-3 text-center">Priority</th>
                            <th class="px-4 py-3 text-center">Location</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Result</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($investigationOrder->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-gray-800">{{ $item->investigation?->name }}</p>
                                @if($item->clinical_notes)
                                    <p class="text-xs text-gray-500 mt-0.5">{{ $item->clinical_notes }}</p>
                                @endif
                                <p class="text-xs text-gray-400">{{ currency_symbol() }}{{ number_format($item->investigation?->price ?? 0, 0) }}</p>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $item->quantity }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-0.5 text-xs rounded-full {{ $item->priority === 'stat' ? 'bg-red-100 text-red-800' : ($item->priority === 'urgent' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-600') }}">
                                    {{ strtoupper($item->priority) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center text-gray-600 capitalize">{{ $item->test_location }}</td>
                            <td class="px-4 py-3 text-center">
                                @php $sc = ['ordered'=>'bg-blue-100 text-blue-800','collected'=>'bg-yellow-100 text-yellow-800','testing'=>'bg-purple-100 text-purple-800','verified'=>'bg-orange-100 text-orange-800','reported'=>'bg-green-100 text-green-800','cancelled'=>'bg-red-100 text-red-800']; @endphp
                                <span class="px-2 py-0.5 text-xs rounded-full {{ $sc[$item->status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->hasResult())
                                    <a href="{{ route('investigation-orders.report', $investigationOrder) }}" class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-green-600 rounded hover:bg-green-700" target="_blank">
                                        <i class="fas fa-print mr-1"></i>View Report
                                    </a>
                                @else
                                    @if(in_array($investigationOrder->status, ['testing', 'collected']))
                                        <a href="{{ route('lab-orders.results.create', $investigationOrder) }}" class="text-medical-blue hover:text-blue-800 text-xs font-medium">
                                            <i class="fas fa-plus mr-1"></i>Add
                                        </a>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-400">No investigations on this order.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($investigationOrder->items->count())
                    <tfoot class="bg-gray-50 border-t border-gray-200">
                        <tr>
                            <td class="px-4 py-2 text-xs font-semibold text-gray-600">Total</td>
                            <td colspan="3"></td>
                            <td colspan="2" class="px-4 py-2 text-right text-xs font-semibold text-gray-700">
                                {{ currency_symbol() }}{{ number_format($investigationOrder->items->sum(fn($i) => ($i->investigation?->price ?? 0) * $i->quantity), 0) }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
