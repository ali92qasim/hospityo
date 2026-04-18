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
            <optgroup label="Pathology (Lab)">
                <option value="hematology">Hematology</option>
                <option value="biochemistry">Biochemistry</option>
                <option value="microbiology">Microbiology</option>
                <option value="immunology">Immunology</option>
                <option value="histopathology">Histopathology</option>
                <option value="molecular">Molecular Biology</option>
            </optgroup>
            <optgroup label="Radiology (Imaging)">
                <option value="x-ray">X-Ray</option>
                <option value="ultrasound">Ultrasound</option>
                <option value="ct-scan">CT Scan</option>
                <option value="mri">MRI</option>
            </optgroup>
            <optgroup label="Cardiology">
                <option value="cardiac-diagnostics">Cardiac Diagnostics</option>
            </optgroup>
        </select>
    </div>
    <a href="{{ route('investigations.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">TAT</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase w-32">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($tests as $test)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap font-medium">{{ $test->code }}</td>
                    <td class="px-6 py-4">{{ $test->name }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $catColors = [
                                'hematology' => 'bg-red-100 text-red-800',
                                'biochemistry' => 'bg-yellow-100 text-yellow-800',
                                'microbiology' => 'bg-green-100 text-green-800',
                                'immunology' => 'bg-indigo-100 text-indigo-800',
                                'histopathology' => 'bg-pink-100 text-pink-800',
                                'molecular' => 'bg-cyan-100 text-cyan-800',
                                'x-ray' => 'bg-purple-100 text-purple-800',
                                'ultrasound' => 'bg-blue-100 text-blue-800',
                                'ct-scan' => 'bg-orange-100 text-orange-800',
                                'mri' => 'bg-teal-100 text-teal-800',
                                'cardiac-diagnostics' => 'bg-rose-100 text-rose-800',
                            ];
                        @endphp
                        <span class="px-2 py-1 text-xs rounded-full font-medium {{ $catColors[$test->category] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ ucwords(str_replace('-', ' ', $test->category)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ currency_symbol() }}{{ number_format($test->price, 0) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ $test->turnaround_time ?? '-' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs rounded-full {{ $test->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $test->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('investigations.show', $test->id) }}" class="text-blue-600 hover:text-blue-800" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('investigations.edit', $test->id) }}" class="text-yellow-600 hover:text-yellow-800" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('investigations.destroy', $test->id) }}" method="POST" class="inline" onsubmit="return confirm('Delete this test?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
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