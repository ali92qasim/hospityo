@extends('admin.layout')

@section('title', 'Lab Orders - Laboratory Information System')
@section('page-title', 'Lab Orders')
@section('page-description', 'Manage laboratory test orders')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div class="flex space-x-4">
        <select onchange="filterOrders()" id="status-filter" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Status</option>
            <option value="ordered">Ordered</option>
            <option value="sample_collected">Sample Collected</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
        </select>
        <select onchange="filterOrders()" id="priority-filter" class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Priority</option>
            <option value="routine">Routine</option>
            <option value="urgent">Urgent</option>
            <option value="stat">STAT</option>
        </select>
    </div>
    <a href="{{ route('lab-orders.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i>New Order
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ordered</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($orders as $order)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $order->order_number }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $order->patient->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $order->labTest->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full {{ $order->priority === 'stat' ? 'bg-red-100 text-red-800' : ($order->priority === 'urgent' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                            {{ strtoupper($order->priority) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full {{ $order->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $order->ordered_at->format('M d, Y H:i') }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('lab-orders.show', $order) }}" class="text-blue-600 hover:text-blue-800 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        @if($order->status === 'ordered')
                            <button onclick="collectSample({{ $order->id }})" class="text-green-600 hover:text-green-800">
                                <i class="fas fa-vial"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">No lab orders found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $orders->links() }}

<script>
function filterOrders() {
    const status = document.getElementById('status-filter').value;
    const priority = document.getElementById('priority-filter').value;
    const url = new URL(window.location);
    
    if (status) url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    
    if (priority) url.searchParams.set('priority', priority);
    else url.searchParams.delete('priority');
    
    window.location = url;
}

function collectSample(orderId) {
    if (confirm('Collect sample for this order?')) {
        fetch(`/lab-orders/${orderId}/collect-sample`, {
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