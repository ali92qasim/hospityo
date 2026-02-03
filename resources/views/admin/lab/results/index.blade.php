@extends('admin.layout')

@section('title', 'Lab Results - Laboratory Information System')
@section('page-title', 'Lab Results')
@section('page-description', 'Manage laboratory test results')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div class="flex space-x-4">
        <select onchange="filterResults()" id="status-filter" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Status</option>
            <option value="preliminary">Preliminary</option>
            <option value="final">Final</option>
            <option value="corrected">Corrected</option>
        </select>
    </div>
</div>

<!-- Pending Orders Section -->
@if($pendingOrders->count() > 0)
<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
    <h4 class="text-lg font-medium text-yellow-800 mb-3">Pending Test Results</h4>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($pendingOrders as $order)
            <div class="bg-white border border-yellow-300 rounded-lg p-3">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <p class="font-medium text-gray-800">{{ $order->labTest->name }}</p>
                        <p class="text-sm text-gray-600">{{ $order->patient->name }}</p>
                        <p class="text-xs text-gray-500">{{ $order->order_number }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
                <a href="{{ route('lab-orders.results.create', $order) }}" class="block w-full bg-medical-blue text-white text-center px-3 py-2 rounded text-sm hover:bg-blue-700">
                    <i class="fas fa-plus mr-1"></i>Add Result
                </a>
            </div>
        @endforeach
    </div>
</div>
@endif

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Technician</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tested</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($results as $result)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $result->labOrder->order_number }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $result->labOrder->patient->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $result->labOrder->labTest->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full {{ $result->status === 'final' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ ucfirst($result->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $result->technician->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $result->tested_at->format('M d, Y H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('lab-results.show', $result) }}" class="text-blue-600 hover:text-blue-800 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('lab-results.edit', $result) }}" class="text-yellow-600 hover:text-yellow-800 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        @if($result->status === 'preliminary')
                            <button onclick="verifyResult({{ $result->id }})" class="text-green-600 hover:text-green-800 mr-3">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        @endif
                        <a href="{{ route('lab-results.report', $result) }}" class="text-purple-600 hover:text-purple-800" target="_blank">
                            <i class="fas fa-print"></i>
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No lab results found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $results->links() }}

<script>
function filterResults() {
    const status = document.getElementById('status-filter').value;
    const url = new URL(window.location);
    
    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    
    window.location = url;
}

function verifyResult(resultId) {
    if (confirm('Verify and finalize this result?')) {
        fetch(`/lab-results/${resultId}/verify`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        }).then(() => location.reload());
    }
}
</script>
@endsection