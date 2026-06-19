@extends('admin.layout')

@section('title', 'Operation Theatres')
@section('page-title', 'Operation Theatres')
@section('page-description', 'Manage operation theatre rooms')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Operation Theatres</h1>
    <a href="{{ route('ot.theatres.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center text-sm">
        <i class="fas fa-plus mr-2"></i>Add Theatre
    </a>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($theatres as $theatre)
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-5">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $theatre->name }}</h3>
                    <p class="text-xs text-gray-500 capitalize">{{ $theatre->type }} · Floor {{ $theatre->floor ?? 'N/A' }}</p>
                </div>
                @php
                    $statusColors = [
                        'available'   => 'bg-green-100 text-green-800',
                        'occupied'    => 'bg-red-100 text-red-800',
                        'maintenance' => 'bg-yellow-100 text-yellow-800',
                    ];
                @endphp
                <span class="px-2 py-0.5 text-xs rounded-full {{ $statusColors[$theatre->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ ucfirst($theatre->status) }}
                </span>
            </div>

            <div class="text-sm text-gray-600 mb-3">
                <i class="fas fa-calendar-check mr-1 text-gray-400"></i>
                {{ $theatre->surgeries_count }} scheduled surgeries
            </div>

            @if($theatre->notes)
            <p class="text-xs text-gray-500 mb-3">{{ Str::limit($theatre->notes, 80) }}</p>
            @endif

            <a href="{{ route('ot.theatres.edit', $theatre) }}" class="text-sm text-medical-blue hover:text-blue-700">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12 text-gray-400">
        <i class="fas fa-door-open text-4xl mb-3"></i>
        <p>No operation theatres configured yet.</p>
    </div>
    @endforelse
</div>
@endsection
