@extends('admin.layout')

@section('title', 'Record Usage — ' . $surgery->surgery_number)
@section('page-title', 'Record Consumable Usage')

@push('scripts')
@vite(['resources/js/ot-consumable-usage.js'])
@endpush

@section('content')
<div class="max-w-4xl mx-auto">

    {{-- Surgery Info --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm font-medium text-blue-800">{{ $surgery->surgery_number }} — {{ $surgery->procedure_name }}</p>
        <p class="text-xs text-blue-600">Patient: {{ $surgery->patient?->name }} · Surgeon: Dr. {{ $surgery->doctor?->name }}</p>
    </div>

    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    {{-- Already recorded --}}
    @if($surgery->consumableUsages->count() > 0)
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-4 border-b border-gray-200">
            <h4 class="text-sm font-semibold text-gray-700">Previously Recorded Usage</h4>
        </div>
        <div class="p-4">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-xs text-gray-500 uppercase">
                        <th class="text-left pb-2">Item</th>
                        <th class="text-left pb-2">Qty</th>
                        <th class="text-left pb-2">Serial</th>
                        <th class="text-left pb-2">Recorded</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($surgery->consumableUsages as $u)
                    <tr>
                        <td class="py-2 text-gray-800">{{ $u->consumable?->name }}</td>
                        <td class="py-2 text-gray-700">{{ $u->quantity_used }}</td>
                        <td class="py-2 text-gray-500">{{ $u->serial_number ?? '—' }}</td>
                        <td class="py-2 text-gray-400 text-xs">{{ $u->created_at?->format('d M H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Record New Usage --}}
    <form action="{{ route('ot.consumables.record-usage', $surgery) }}" method="POST" id="usage-form"
          data-consumables-json="{{ $consumables->toJson() }}">
        @csrf

        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-4 border-b border-gray-200">
                <h4 class="text-sm font-semibold text-gray-700">Add Consumable Usage</h4>
            </div>
            <div class="p-4">
                <div class="space-y-3" id="usage-rows">
                    {{-- JS will add rows --}}
                </div>
                <button type="button" id="add-usage-row" class="mt-3 text-sm text-medical-blue hover:text-blue-700">
                    <i class="fas fa-plus mr-1"></i>Add Item
                </button>

                {{-- Hidden template for consumable options --}}
                <select id="consumable-options-template" class="hidden">
                    <option value="">Select Consumable</option>
                    @foreach($consumables as $c)
                        <option value="{{ $c->id }}" data-stock="{{ $c->current_stock }}" data-unit="{{ $c->unit }}" data-serial="{{ $c->requires_serial_tracking ? '1' : '0' }}">
                            {{ $c->name }} ({{ $c->current_stock }} {{ $c->unit }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex justify-end space-x-4">
            <a href="{{ route('ot.surgeries.show', $surgery) }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-sm">
                <i class="fas fa-save mr-2"></i>Record Usage
            </button>
        </div>
    </form>
</div>
@endsection
