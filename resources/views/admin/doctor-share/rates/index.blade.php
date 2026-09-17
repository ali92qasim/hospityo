@extends('admin.layout')

@section('title', 'Doctor Share Rates')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Doctor Share Rates</h1>
    <p class="mt-1 text-sm text-gray-600">Leave a cell empty when no rate applies. Zero is saved as an explicit 0% rate.</p>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="{{ route('doctor-share.rates.sync') }}">
    @csrf
    @method('PUT')

    @foreach($categoryOptions as $category => $label)
        <input type="hidden" name="categories[]" value="{{ $category }}">
    @endforeach

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Doctor</th>
                    @foreach($categoryOptions as $label)
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ $label }} %</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rateRows as $rowIndex => $row)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $row['doctor']->name }}
                            <input type="hidden" name="doctors[{{ $rowIndex }}][doctor_id]" value="{{ $row['doctor_id'] }}">
                        </td>
                        @foreach($categoryOptions as $category => $label)
                            <td class="px-4 py-3">
                                <input
                                    type="number"
                                    name="doctors[{{ $rowIndex }}][{{ $category }}]"
                                    value="{{ old("doctors.{$rowIndex}.{$category}", $row['rates'][$category]) }}"
                                    min="0"
                                    max="100"
                                    step="0.01"
                                    class="w-24 px-3 py-2 border border-gray-300 rounded-lg"
                                >
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($categoryOptions) + 1 }}" class="px-6 py-12 text-center text-gray-500">
                            No doctor share rates have been configured.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($rateRows->isNotEmpty())
        <div class="mt-4">
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Save Rates
            </button>
        </div>
    @endif
</form>
@endsection
