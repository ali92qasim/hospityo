@extends('admin.layout')

@section('title', 'Investigation Orders - Laboratory Information System')
@section('page-title', 'Investigation Orders')
@section('page-description', 'Manage laboratory test orders')

@section('content')

@if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
@endif

{{-- Toolbar --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div class="flex flex-wrap gap-2">
        <select onchange="filterOrders()" id="status-filter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">All Status</option>
            <option value="ordered"   {{ request('status') === 'ordered'   ? 'selected' : '' }}>Ordered</option>
            <option value="collected" {{ request('status') === 'collected' ? 'selected' : '' }}>Sample Collected</option>
            <option value="testing"   {{ request('status') === 'testing'   ? 'selected' : '' }}>Testing</option>
            <option value="verified"  {{ request('status') === 'verified'  ? 'selected' : '' }}>Verified</option>
            <option value="reported"  {{ request('status') === 'reported'  ? 'selected' : '' }}>Reported</option>
        </select>
        <select onchange="filterOrders()" id="priority-filter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">All Priority</option>
            <option value="routine" {{ request('priority') === 'routine' ? 'selected' : '' }}>Routine</option>
            <option value="urgent"  {{ request('priority') === 'urgent'  ? 'selected' : '' }}>Urgent</option>
            <option value="stat"    {{ request('priority') === 'stat'    ? 'selected' : '' }}>STAT</option>
        </select>
    </div>
    <a href="{{ route('investigation-orders.create') }}" class="inline-flex items-center bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">
        <i class="fas fa-plus mr-2"></i>New Order
    </a>
</div>

{{-- Orders --}}
<div class="space-y-6">
@forelse($orders as $order)
@php
    $pendingItems   = $order->items->whereIn('status', ['ordered', 'collected', 'testing']);
    $completedItems = $order->items->whereIn('status', ['verified', 'reported']);
    $totalItems     = $order->items->count();
    $doneCount      = $completedItems->count();
@endphp

<div class="bg-white rounded-lg shadow-sm overflow-hidden">

    {{-- Order Header --}}
    <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-gray-800">{{ $order->order_number }}</span>
                    @php
                        $orderStatusColors = [
                            'ordered'   => 'bg-blue-100 text-blue-800',
                            'collected' => 'bg-yellow-100 text-yellow-800',
                            'testing'   => 'bg-purple-100 text-purple-800',
                            'verified'  => 'bg-orange-100 text-orange-800',
                            'reported'  => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $orderStatusColors[$order->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                    </span>
                    <span class="text-xs text-gray-400">{{ $totalItems }} test{{ $totalItems !== 1 ? 's' : '' }}</span>
                </div>
                <div class="flex items-center gap-3 mt-1 text-sm text-gray-600">
                    <span class="font-medium text-gray-800">{{ $order->patient?->name }}</span>
                    <span class="text-gray-400">·</span>
                    <span>{{ $order->patient?->patient_no }}</span>
                    @if($order->doctor)
                        <span class="text-gray-400">·</span>
                        <span><i class="fas fa-user-md mr-1 text-gray-400 text-xs"></i>Dr. {{ $order->doctor->name }}</span>
                    @endif
                    <span class="text-gray-400">·</span>
                    <span class="text-xs text-gray-500"><i class="fas fa-clock mr-1"></i>{{ $order->ordered_at?->format('M d, Y H:i') }}</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            @if($order->status === 'ordered')
                <button onclick="collectSample({{ $order->id }})"
                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-lg hover:bg-yellow-100 transition-colors">
                    <i class="fas fa-vial mr-1.5"></i>Collect Sample
                </button>
                <a href="{{ route('investigation-orders.edit', $order) }}"
                   class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors">
                    <i class="fas fa-edit mr-1.5"></i>Edit
                </a>
                <form action="{{ route('investigation-orders.destroy', $order) }}" method="POST" class="inline"
                      onsubmit="return confirm('Delete this order and all its items? This cannot be undone.')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                        <i class="fas fa-trash mr-1.5"></i>Delete
                    </button>
                </form>
            @endif
            <a href="{{ route('investigation-orders.show', $order) }}"
               class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-medical-blue bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                <i class="fas fa-eye mr-1.5"></i>View Order
            </a>
        </div>
    </div>

    {{-- Investigations Body --}}
    <div class="p-6">

        {{-- Pending Items --}}
        @if($pendingItems->count() > 0)
            <div class="{{ $completedItems->count() > 0 ? 'mb-6' : '' }}">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></div>
                    <span class="text-sm font-semibold text-gray-700">Pending Results</span>
                    <span class="px-2 py-0.5 text-xs bg-yellow-100 text-yellow-800 rounded-full font-medium">{{ $pendingItems->count() }}</span>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($pendingItems as $item)
                    @php
                        $typeConfig = [
                            'pathology'  => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                            'radiology'  => ['bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'icon' => 'fa-x-ray'],
                            'cardiology' => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'icon' => 'fa-heartbeat'],
                        ];
                        $cat       = $item->investigation?->category ?? 'pathology';
                        $typeStyle = $typeConfig[$cat] ?? $typeConfig['pathology'];
                    @endphp
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1 min-w-0">
                                <h6 class="text-base font-semibold text-gray-900 truncate mb-2">{{ $item->investigation?->name ?? 'Unknown' }}</h6>
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium {{ $typeStyle['bg'] }} {{ $typeStyle['text'] }}">
                                        <i class="fas {{ $typeStyle['icon'] }} mr-1"></i>{{ ucfirst($cat) }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium
                                        {{ $item->priority === 'stat' ? 'bg-red-600 text-white' : ($item->priority === 'urgent' ? 'bg-orange-600 text-white' : 'bg-blue-600 text-white') }}">
                                        {{ strtoupper($item->priority) }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium bg-gray-100 text-gray-600">
                                        <i class="fas {{ $item->test_location === 'indoor' ? 'fa-building' : 'fa-external-link-alt' }} mr-1"></i>
                                        {{ $item->test_location === 'indoor' ? 'Indoor' : 'External' }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600">
                                    <i class="fas fa-calendar-alt mr-1"></i>{{ $order->ordered_at?->format('M d, h:i A') }}
                                </p>
                            </div>
                        </div>
                        @if($item->clinical_notes)
                            <div class="bg-white rounded p-2 mb-3 text-xs text-gray-700">
                                <i class="fas fa-notes-medical text-yellow-600 mr-1"></i>
                                {{ Str::limit($item->clinical_notes, 60) }}
                            </div>
                        @endif
                        @if($item->test_location === 'indoor')
                            <div class="mt-3 pt-3 border-t border-yellow-200">
                                <a href="{{ route('lab-orders.results.create', $order) }}"
                                   class="inline-flex items-center px-3 py-2 bg-medical-blue text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all w-full justify-center">
                                    <i class="fas fa-plus mr-2"></i>Add Result
                                </a>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Completed Items --}}
        @if($completedItems->count() > 0)
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span class="text-sm font-semibold text-gray-700">Completed Results</span>
                    <span class="px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded-full font-medium">{{ $completedItems->count() }}</span>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                    @foreach($completedItems as $item)
                    @php
                        $typeConfig = [
                            'pathology'  => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                            'radiology'  => ['bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'icon' => 'fa-x-ray'],
                            'cardiology' => ['bg' => 'bg-red-100',    'text' => 'text-red-800',    'icon' => 'fa-heartbeat'],
                        ];
                        $cat       = $item->investigation?->category ?? 'pathology';
                        $typeStyle = $typeConfig[$cat] ?? $typeConfig['pathology'];
                    @endphp
                    <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <div class="flex justify-between items-start mb-3">
                            <div class="flex-1 min-w-0">
                                <h6 class="text-base font-semibold text-gray-900 truncate mb-2">{{ $item->investigation?->name ?? 'Unknown' }}</h6>
                                <div class="flex flex-wrap items-center gap-2 mb-2">
                                    <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium {{ $typeStyle['bg'] }} {{ $typeStyle['text'] }}">
                                        <i class="fas {{ $typeStyle['icon'] }} mr-1"></i>{{ ucfirst($cat) }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium bg-green-200 text-green-900">
                                        <i class="fas fa-check-circle mr-1"></i>Reported
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600">
                                    <i class="fas fa-calendar-alt mr-1"></i>{{ $order->ordered_at?->format('M d, h:i A') }}
                                </p>
                            </div>
                        </div>
                        @if($item->result)
                            <div class="mt-3 pt-3 border-t border-green-200 flex gap-2">
                                <a href="{{ route('lab-results.show', $item->result) }}"
                                   class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-all">
                                    <i class="fas fa-file-medical mr-2"></i>View
                                </a>
                                <a href="{{ route('lab-results.report', $item->result) }}?print=1"
                                   target="_blank"
                                   class="inline-flex items-center justify-center px-3 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-all">
                                    <i class="fas fa-print"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Empty --}}
        @if($totalItems === 0)
            <div class="text-center py-4 text-gray-400 text-sm">No investigations on this order.</div>
        @endif
    </div>
</div>
@empty
    <div class="bg-white rounded-lg shadow-sm border-2 border-dashed border-gray-200 p-12 text-center">
        <i class="fas fa-flask text-gray-300 text-4xl mb-3"></i>
        <p class="text-gray-500 font-medium">No investigation orders found</p>
        <a href="{{ route('investigation-orders.create') }}" class="mt-3 inline-flex items-center text-sm text-medical-blue hover:underline">
            <i class="fas fa-plus mr-1"></i>Create the first order
        </a>
    </div>
@endforelse
</div>

{{ $orders->links() }}

<script>
function filterOrders() {
    const status   = document.getElementById('status-filter').value;
    const priority = document.getElementById('priority-filter').value;
    const url      = new URL(window.location);
    status   ? url.searchParams.set('status', status)     : url.searchParams.delete('status');
    priority ? url.searchParams.set('priority', priority) : url.searchParams.delete('priority');
    window.location = url;
}

function collectSample(orderId) {
    if (confirm('Mark sample as collected for this order?')) {
        fetch(`/investigation-orders/${orderId}/collect-sample`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        }).then(() => location.reload());
    }
}
</script>
@endsection
