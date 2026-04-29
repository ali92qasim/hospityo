@extends('admin.layout')

@section('title', 'Salary Structure — ' . $employee->full_name)
@section('page-title', 'Employee Salary Structure')

@section('content')
<div class="max-w-4xl mx-auto">

    <!-- Employee Header -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center">
                    @if($employee->photo)
                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->full_name }}" class="w-16 h-16 rounded-full object-cover mr-4">
                    @else
                        <div class="w-16 h-16 bg-medical-blue rounded-full flex items-center justify-center text-white text-xl font-bold mr-4">
                            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Salary Structure — {{ $employee->full_name }}</h2>
                        <p class="text-sm text-gray-600">{{ $employee->employee_no }} &bull; {{ $employee->department->name ?? '—' }} &bull; {{ $employee->designation->name ?? '—' }}</p>
                        <p class="text-sm text-gray-500 mt-1">
                            Basic Salary: <span class="font-bold text-green-600">{{ $employee->basic_salary ? format_currency($employee->basic_salary) : '—' }}</span>
                        </p>
                    </div>
                </div>
                <a href="{{ route('hr.employees.show', $employee) }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Employee
                </a>
            </div>
        </div>
    </div>

    <!-- Salary Components Form -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-sliders-h mr-2 text-medical-blue"></i>Salary Components
            </h3>
            <p class="text-sm text-gray-600 mt-1">Override default amounts for this employee. Leave blank to use the default value.</p>
        </div>

        <form action="{{ route('hr.payroll.update-employee-salary', $employee) }}" method="POST">
            @csrf

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Component Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Override</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($components as $component)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                {{ $component->name }}
                                <span class="text-xs text-gray-500 font-mono ml-1">({{ $component->code }})</span>
                            </td>
                            <td class="px-6 py-4">
                                @if($component->type === 'allowance')
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Allowance</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Deduction</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                @if($component->calculation === 'percentage')
                                    {{ $component->default_amount }}% of {{ ucwords(str_replace('_', ' ', $component->percentage_of ?? 'basic_salary')) }}
                                @else
                                    {{ format_currency($component->default_amount) }}
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <input type="number" name="overrides[{{ $component->id }}]"
                                       value="{{ old('overrides.' . $component->id, $overrides[$component->id] ?? '') }}"
                                       step="0.01" min="0" placeholder="Use default"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm">
                                @error('overrides.' . $component->id) <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-cogs text-3xl mb-2 text-gray-300"></i>
                                <p>No salary components defined</p>
                                <a href="{{ route('hr.payroll.components') }}" class="mt-2 inline-block text-medical-blue hover:underline">
                                    Add salary components first
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($components->count() > 0)
            <div class="p-6 border-t border-gray-200 flex justify-end gap-3">
                <a href="{{ route('hr.employees.show', $employee) }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-save mr-2"></i>Save Salary Structure
                </button>
            </div>
            @endif
        </form>
    </div>
</div>
@endsection
