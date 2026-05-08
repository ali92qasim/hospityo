@extends('admin.layout')

@section('title', 'Share Rules')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Share Rules</h1>
    <a href="{{ route('doctor-share.rules.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center">
        <i class="fas fa-plus mr-2"></i>Create Rule
    </a>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
        {{ session('success') }}
    </div>
@endif

@if($errors->has('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
        {{ $errors->first('error') }}
    </div>
@endif

<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('doctor-share.rules.index') }}" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Doctor</label>
            <select name="doctor_id" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                <option value="">All Doctors</option>
                @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                        {{ $doctor->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-filter mr-1"></i>Filter
            </button>
            @if(request('doctor_id') || request('status'))
                <a href="{{ route('doctor-share.rules.index') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2">
                    Clear
                </a>
            @endif
        </div>
    </form>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rule Level</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scope</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Share Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applies To</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rules as $rule)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if(is_null($rule->doctor_id))
                            <span class="text-xs px-2 py-1 rounded bg-purple-100 text-purple-800">Global</span>
                        @elseif($rule->doctor_id && ($rule->service_id || $rule->investigation_id))
                            <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-800">Specific</span>
                        @else
                            <span class="text-xs px-2 py-1 rounded bg-blue-100 text-blue-800">Doctor Default</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $rule->doctor->name ?? '— Global —' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $rule->service->name ?? ($rule->investigation->name ?? '— All —') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($rule->share_type === 'percentage')
                            <span class="text-xs px-2 py-1 rounded bg-indigo-100 text-indigo-800">Percentage</span>
                        @else
                            <span class="text-xs px-2 py-1 rounded bg-orange-100 text-orange-800">Fixed</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        @if($rule->share_type === 'percentage')
                            {{ $rule->share_value }}%
                        @else
                            {{ currency_symbol() }}{{ $rule->share_value }}
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ ucfirst($rule->applies_to) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($rule->is_active)
                            <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-800">Inactive</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <a href="{{ route('doctor-share.rules.edit', $rule) }}" class="text-medical-blue hover:text-blue-700 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" action="{{ route('doctor-share.rules.toggle', $rule) }}" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-gray-500 hover:text-gray-700 mr-3">
                                {{ $rule->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('doctor-share.rules.destroy', $rule) }}" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this rule?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <p class="mb-2">No share rules found.</p>
                        <a href="{{ route('doctor-share.rules.create') }}" class="text-medical-blue hover:text-blue-700">
                            Create your first rule
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4">
        {{ $rules->links() }}
    </div>
</div>
@endsection
