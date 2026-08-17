@extends('admin.layout')

@section('title', 'Lab Orders - Laboratory Information System')
@section('page-title', 'Lab Orders')
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

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div class="flex flex-wrap items-center gap-3">
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
    <a href="{{ route('lab.orders.create') }}" class="inline-flex items-center bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm font-medium">
        <i class="fas fa-plus mr-2"></i>New Lab Order
    </a>
</div>

@include('admin.shared.diagnostics._orders-list', ['orders' => $orders, 'isLabSurface' => true])

@if($orders->hasPages())
<div class="mt-6">
    {{ $orders->links() }}
</div>
@endif

<script>
function filterOrders() {
    const status   = document.getElementById('status-filter').value;
    const priority = document.getElementById('priority-filter').value;
    const url      = new URL(window.location);
    status   ? url.searchParams.set('status', status)     : url.searchParams.delete('status');
    priority ? url.searchParams.set('priority', priority) : url.searchParams.delete('priority');
    window.location = url;
}
</script>
@endsection
