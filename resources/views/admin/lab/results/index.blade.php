@extends('admin.layout')

@section('title', 'Investigation Results - Hospital Management System')
@section('page-title', 'Investigation Results')
@section('page-description', 'Manage pathology, radiology, and cardiology test results')

@section('content')
<div id="lab-results-index">

<!-- Pending Orders by Patient/Visit -->
@if(count($pendingOrders) > 0)
<div class="mb-8">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Pending Test Results by Patient</h3>
    <div class="space-y-4">
        @foreach($pendingOrders as $groupKey => $orders)
            @php
                $firstOrder = collect($orders)->first();
                $patient = $firstOrder?->patient;
                $visit = $firstOrder?->visit;
            @endphp
            @if(!$patient) @continue @endif
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <!-- Patient Header -->
                <div class="bg-blue-50 px-6 py-4 border-b border-blue-200">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                        <div>
                            <h4 class="text-lg font-semibold text-blue-900">{{ $patient->name }}</h4>
                            <div class="flex items-center space-x-4 text-sm text-blue-700">
                                <span><i class="fas fa-phone mr-1"></i>{{ $patient->phone }}</span>
                                <span><i class="fas fa-calendar mr-1"></i>{{ $patient->date_of_birth?->format('M d, Y') ?? 'N/A' }}</span>
                                <span><i class="fas fa-clipboard-list mr-1"></i>{{ $visit?->visit_no }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                {{ $orders->sum(fn($o) => $o->items->count()) }} test{{ $orders->sum(fn($o) => $o->items->count()) > 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tests Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test Name</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Priority</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Location</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ordered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($orders as $order)
                                @foreach($order->items->whereNotIn('status', ['reported','verified','cancelled']) as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs text-gray-500 font-mono">{{ $order->order_number }}</td>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-gray-900">{{ $item->investigation?->name }}</div>
                                        @if($item->investigation?->parameters?->count() > 0)
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $item->investigation->parameters->count() }} parameter{{ $item->investigation->parameters->count() > 1 ? 's' : '' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium
                                            {{ $item->priority === 'stat' ? 'bg-red-100 text-red-800' :
                                               ($item->priority === 'urgent' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
                                            {{ strtoupper($item->priority) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $statusConfig = [
                                                'ordered'   => ['bg' => 'bg-gray-100',   'text' => 'text-gray-800',   'label' => 'Ordered'],
                                                'collected' => ['bg' => 'bg-blue-100',   'text' => 'text-blue-800',   'label' => 'Sample Collected'],
                                                'testing'   => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'label' => 'In Progress'],
                                            ];
                                            $config = $statusConfig[$item->status] ?? $statusConfig['ordered'];
                                        @endphp
                                        <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium {{ $config['bg'] }} {{ $config['text'] }}">
                                            {{ $config['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium
                                            {{ $item->test_location === 'indoor' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                            <i class="fas {{ $item->test_location === 'indoor' ? 'fa-building' : 'fa-external-link-alt' }} mr-1"></i>
                                            {{ $item->test_location === 'indoor' ? 'Indoor' : 'External' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $order->ordered_at->format('M d, H:i') }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Action Button -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                    @php
                        $batchParams = ['patient_id' => $patient->id];
                        if ($visit) $batchParams['visit_id'] = $visit->id;
                    @endphp
                    <a href="{{ route('lab-results.create-batch', $batchParams) }}"
                       class="inline-flex items-center px-4 py-2 bg-medical-blue text-white font-medium rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-flask mr-2"></i>Enter Results for All Tests
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@else
<div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-lg p-8 text-center mb-8">
    <i class="fas fa-clipboard-list text-gray-400 text-3xl mb-3"></i>
    <p class="text-gray-500">No pending Investigation orders found</p>
</div>
@endif

<!-- Completed Results -->
<div class="mb-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Completed Results</h3>
    <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
        <table class="lab-results-table w-full invisible min-w-[980px]">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Patient</th>
                    <th>Tests</th>
                    <th>Status</th>
                    <th>Reported</th>
                    <th>Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

</div>

@vite(['resources/js/lab-results-index.js'])
@endsection
