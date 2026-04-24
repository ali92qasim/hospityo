@extends('admin.layout')

@section('title', 'Tax Configuration')
@section('page-title', 'Tax Configuration')
@section('page-description', 'Manage tax rules and rates')

@section('content')
<div class="bg-white rounded-lg shadow-sm">
    <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-800">Tax Rules</h3>
                <p class="text-sm text-gray-500">{{ $taxes->total() }} tax rules configured</p>
            </div>
            <a href="{{ route('taxes.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
                <i class="fas fa-plus mr-2"></i>Add Tax
            </a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tax</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rate</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applies To</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($taxes as $tax)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $tax->name }}</div>
                        <div class="text-xs text-gray-500 font-mono">{{ $tax->code }}</div>
                    </td>
                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ $tax->percentage }}%</td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $tax->is_inclusive ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                            {{ $tax->is_inclusive ? 'Inclusive' : 'Exclusive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach($tax->mappings as $m)
                                <span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-600">
                                    {{ $m->applicable_on === 'all' ? 'All' : ucwords(str_replace('_', ' ', $m->applicable_on)) }}: {{ ucwords(str_replace('_', ' ', $m->applicable_value)) }}
                                </span>
                            @endforeach
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $tax->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $tax->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm space-x-2">
                        <a href="{{ route('taxes.edit', $tax) }}" class="text-medical-blue hover:text-blue-700"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('taxes.destroy', $tax) }}" method="POST" class="inline" onsubmit="return confirm('Delete this tax?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500"><i class="fas fa-percentage text-4xl text-gray-300 mb-3 block"></i>No tax rules configured yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($taxes->hasPages())<div class="px-6 py-4 border-t">{{ $taxes->links() }}</div>@endif
</div>
@endsection
