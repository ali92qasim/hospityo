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

@if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form
    id="doctor-share-rates-form"
    method="POST"
    action="{{ route('doctor-share.rates.sync') }}"
    data-added-ids="{{ $rateRows->pluck('doctor_id')->values()->toJson() }}"
>
    @csrf
    @method('PUT')

    <input type="hidden" name="doctors" value="">

    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="min-w-64">
                <label for="doctor-share-doctor-select" class="block text-sm font-medium text-gray-700 mb-1">
                    Add Doctor
                </label>
                <select
                    id="doctor-share-doctor-select"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                >
                    <option value="">Select a doctor</option>
                    @foreach($doctors as $doctor)
                        <option
                            value="{{ $doctor->id }}"
                            @disabled($rateRows->contains('doctor_id', $doctor->id))
                        >{{ $doctor->name }}</option>
                    @endforeach
                </select>
            </div>
            <button
                id="doctor-share-add-row"
                type="button"
                class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <i class="fas fa-plus mr-2"></i>Add Row
            </button>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                        @foreach($categoryOptions as $label)
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $label }} %</th>
                        @endforeach
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody id="doctor-share-rate-rows" class="divide-y divide-gray-200">
                    @foreach($rateRows as $rowIndex => $row)
                        <tr data-rate-row class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                <span data-doctor-name>{{ $row['doctor']->name }}</span>
                                <input data-doctor-id type="hidden" name="doctors[{{ $rowIndex }}][doctor_id]" value="{{ $row['doctor_id'] }}">
                            </td>
                            @foreach($categoryOptions as $category => $label)
                                <td class="px-4 py-3">
                                    <input
                                        data-rate-input
                                        data-category="{{ $category }}"
                                        aria-label="{{ $row['doctor']->name }} {{ $label }} percentage"
                                        type="number"
                                        name="doctors[{{ $rowIndex }}][{{ $category }}]"
                                        value="{{ old("doctors.{$rowIndex}.{$category}", $row['rates'][$category]) }}"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                                    >
                                </td>
                            @endforeach
                            <td class="px-4 py-3 text-right">
                                <button type="button" data-remove-row class="text-red-600 hover:text-red-700">
                                    <i class="fas fa-trash mr-1"></i>Remove
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    <tr data-empty-row @class(['hidden' => $rateRows->isNotEmpty()])>
                        <td colspan="{{ count($categoryOptions) + 2 }}" class="px-6 py-12 text-center text-gray-500">
                            No doctors added. Select a doctor above to create a rate row.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="px-4 py-4 border-t flex justify-end">
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Save Rates
            </button>
        </div>
    </div>
</form>

<template id="doctor-share-rate-row-template">
    <tr data-rate-row class="hover:bg-gray-50">
        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
            <span data-doctor-name></span>
            <input data-doctor-id type="hidden">
        </td>
        @foreach($categoryOptions as $category => $label)
            <td class="px-4 py-3">
                <input
                    data-rate-input
                    data-category="{{ $category }}"
                    aria-label="{{ $label }} percentage"
                    type="number"
                    min="0"
                    max="100"
                    step="0.01"
                    class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                >
            </td>
        @endforeach
        <td class="px-4 py-3 text-right">
            <button type="button" data-remove-row class="text-red-600 hover:text-red-700">
                <i class="fas fa-trash mr-1"></i>Remove
            </button>
        </td>
    </tr>
</template>

@vite(['resources/js/doctor-share-rates-form.js'])
@endsection
