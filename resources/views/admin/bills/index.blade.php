@extends('admin.layout')

@section('title', 'Bills Management')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Bills Management</h1>
    <a href="{{ route('bills.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
        <i class="fas fa-plus mr-2"></i>Create Bill
    </a>
</div>

<table class="bills-table w-full invisible">
    <thead>
        <tr>
            <th>Bill #</th>
            <th>Patient</th>
            <th>Type</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
</table>
@vite(['resources/js/bills-index.js'])
@endsection
