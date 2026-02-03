@extends('admin.layout')

@section('title', 'Prescription Details')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-800">Prescription Details</h1>
        <div class="flex space-x-2">
            @if($prescription->status === 'pending')
                <form method="POST" action="{{ route('prescriptions.dispense', $prescription) }}" class="inline">
                    @csrf
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700" onclick="return confirm('Dispense this prescription?')">
                        <i class="fas fa-check mr-2"></i>Dispense
                    </button>
                </form>
            @endif
            <a href="{{ route('prescriptions.index') }}" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                <i class="fas fa-arrow-left mr-2"></i>Back
            </a>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Prescription Info -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">{{ $prescription->prescription_no }}</h2>
                    <p class="text-sm text-gray-500">Prescribed on {{ $prescription->prescribed_date->format('M d, Y h:i A') }}</p>
                </div>
                @php
                    $statusColors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'dispensed' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800'
                    ];
                @endphp
                <span class="px-3 py-1 text-sm rounded-full {{ $statusColors[$prescription->status] }}">
                    {{ ucfirst($prescription->status) }}
                </span>
            </div>

            <!-- Patient & Doctor Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="font-medium text-gray-800 mb-2">Patient Information</h3>
                    <div class="text-sm text-gray-600">
                        <p><strong>Name:</strong> {{ $prescription->patient->name }}</p>
                        <p><strong>Phone:</strong> {{ $prescription->patient->phone }}</p>
                        <p><strong>Age:</strong> {{ $prescription->patient->age }} years</p>
                        <p><strong>Gender:</strong> {{ ucfirst($prescription->patient->gender) }}</p>
                    </div>
                </div>
                <div>
                    <h3 class="font-medium text-gray-800 mb-2">Doctor Information</h3>
                    <div class="text-sm text-gray-600">
                        <p><strong>Name:</strong> Dr. {{ $prescription->doctor->name }}</p>
                        <p><strong>Specialization:</strong> {{ $prescription->doctor->specialization }}</p>
                        <p><strong>Visit:</strong> {{ $prescription->visit->visit_no }}</p>
                    </div>
                </div>
            </div>

            <!-- Prescription Items -->
            <div>
                <h3 class="font-medium text-gray-800 mb-4">Prescribed Medicines</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Dosage</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Frequency</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($prescription->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-900">{{ $item->medicine->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $item->medicine->strength }}</div>
                                    @if($item->instructions)
                                        <div class="text-xs text-blue-600 mt-1">{{ $item->instructions }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $item->dosage }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $item->frequency }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $item->duration }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $item->quantity }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">₨{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-right font-medium text-gray-800">Total Amount:</td>
                                <td class="px-4 py-3 font-bold text-gray-900">₨{{ number_format($prescription->total_amount, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if($prescription->notes)
                <div class="mt-6">
                    <h3 class="font-medium text-gray-800 mb-2">Notes</h3>
                    <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded">{{ $prescription->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Summary Card -->
    <div>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="font-medium text-gray-800 mb-4">Prescription Summary</h3>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Total Items:</span>
                    <span class="text-sm font-medium">{{ $prescription->items->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Total Quantity:</span>
                    <span class="text-sm font-medium">{{ $prescription->items->sum('quantity') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Total Amount:</span>
                    <span class="text-sm font-bold text-medical-blue">₨{{ number_format($prescription->total_amount, 2) }}</span>
                </div>
                <hr>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600">Status:</span>
                    <span class="text-sm font-medium">{{ ucfirst($prescription->status) }}</span>
                </div>
                @if($prescription->dispensed_date)
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600">Dispensed:</span>
                        <span class="text-sm">{{ $prescription->dispensed_date->format('M d, Y') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection