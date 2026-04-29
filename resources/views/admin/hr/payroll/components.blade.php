@extends('admin.layout')

@section('title', 'Salary Components')
@section('page-title', 'Salary Components')

@section('content')
<div class="max-w-6xl mx-auto">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Salary Components</h1>
            <p class="text-sm text-gray-600">Manage allowances and deductions for payroll</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('hr.payroll.create-component') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-plus mr-2"></i>Add Component
            </a>
            <a href="{{ route('hr.payroll.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>Back to Payroll
            </a>
        </div>
    </div>

    <!-- Allowances Section -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-plus-circle mr-2 text-green-500"></i>Allowances
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calculation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taxable</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php $allowances = $components->where('type', 'allowance'); @endphp
                    @forelse($allowances as $component)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $component->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ $component->code }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Allowance</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($component->calculation) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @if($component->calculation === 'percentage')
                                {{ $component->default_amount }}% of {{ ucwords(str_replace('_', ' ', $component->percentage_of ?? 'basic_salary')) }}
                            @else
                                {{ format_currency($component->default_amount) }}
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($component->is_taxable)
                                <span class="text-green-600"><i class="fas fa-check-circle"></i> Yes</span>
                            @else
                                <span class="text-gray-400"><i class="fas fa-times-circle"></i> No</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-medium space-x-2">
                            <a href="{{ route('hr.payroll.edit-component', $component) }}" class="text-medical-blue hover:text-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('hr.payroll.destroy-component', $component) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this component?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-plus-circle text-3xl mb-2 text-gray-300"></i>
                            <p>No allowance components defined</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Deductions Section -->
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-minus-circle mr-2 text-red-500"></i>Deductions
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Calculation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Default Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taxable</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php $deductionComponents = $components->where('type', 'deduction'); @endphp
                    @forelse($deductionComponents as $component)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $component->name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-600 font-mono">{{ $component->code }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Deduction</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">{{ ucfirst($component->calculation) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @if($component->calculation === 'percentage')
                                {{ $component->default_amount }}% of {{ ucwords(str_replace('_', ' ', $component->percentage_of ?? 'basic_salary')) }}
                            @else
                                {{ format_currency($component->default_amount) }}
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm">
                            @if($component->is_taxable)
                                <span class="text-green-600"><i class="fas fa-check-circle"></i> Yes</span>
                            @else
                                <span class="text-gray-400"><i class="fas fa-times-circle"></i> No</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-medium space-x-2">
                            <a href="{{ route('hr.payroll.edit-component', $component) }}" class="text-medical-blue hover:text-blue-700" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('hr.payroll.destroy-component', $component) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this component?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-minus-circle text-3xl mb-2 text-gray-300"></i>
                            <p>No deduction components defined</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
