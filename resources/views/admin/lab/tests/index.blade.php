@extends('admin.layout')

@section('title', 'Investigations - Laboratory Information System')
@section('page-title', 'Investigations')
@section('page-description', 'Manage investigation definitions')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div class="flex space-x-4">
        <input type="text" placeholder="Search tests..." class="px-3 py-2 border border-gray-300 rounded-lg">
        <select class="px-3 py-2 border border-gray-300 rounded-lg">
            <option value="">All Categories</option>
            <option value="hematology">Hematology</option>
            <option value="biochemistry">Biochemistry</option>
            <option value="microbiology">Microbiology</option>
            <option value="immunology">Immunology</option>
            <option value="pathology">Pathology</option>
            <option value="molecular">Molecular</option>
        </select>
    </div>
    <a href="{{ route('lab-tests.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
        <i class="fas fa-plus mr-2"></i>Add Test
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sample</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">TAT</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($tests as $test)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $test->code }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $test->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                            {{ ucfirst($test->category) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst($test->sample_type) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">₨{{ number_format($test->price, 0) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $test->turnaround_time }}h</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full {{ $test->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $test->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('lab-tests.show', $test) }}" class="text-blue-600 hover:text-blue-800 mr-3">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('lab-tests.edit', $test) }}" class="text-yellow-600 hover:text-yellow-800 mr-3">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="{{ route('lab-tests.destroy', $test) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800" onclick="return confirm('Delete this test?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">No investigations found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{ $tests->links() }}
@endsection