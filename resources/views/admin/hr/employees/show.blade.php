@extends('admin.layout')

@section('title', 'Employee Details')
@section('page-title', 'Employee Details')

@section('content')
<div class="max-w-5xl mx-auto">

    <!-- Header Card -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center">
                    @if($employee->photo)
                        <img src="{{ asset('storage/' . $employee->photo) }}" alt="{{ $employee->full_name }}" class="w-20 h-20 rounded-full object-cover mr-4">
                    @else
                        <div class="w-20 h-20 bg-medical-blue rounded-full flex items-center justify-center text-white text-2xl font-bold mr-4">
                            {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h3 class="text-xl font-semibold text-gray-800">{{ $employee->full_name }}</h3>
                        <p class="text-sm text-gray-600">{{ $employee->employee_no }} &bull; {{ $employee->designation->name ?? '—' }} &bull; {{ $employee->department->name ?? '—' }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            @php
                                $statusBadges = [
                                    'active'     => 'bg-green-100 text-green-800',
                                    'on_leave'   => 'bg-yellow-100 text-yellow-800',
                                    'suspended'  => 'bg-orange-100 text-orange-800',
                                    'terminated' => 'bg-red-100 text-red-800',
                                    'resigned'   => 'bg-gray-100 text-gray-800',
                                ];
                                $typeBadges = [
                                    'full_time' => 'bg-blue-100 text-blue-800',
                                    'part_time' => 'bg-purple-100 text-purple-800',
                                    'contract'  => 'bg-orange-100 text-orange-800',
                                    'intern'    => 'bg-cyan-100 text-cyan-800',
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs rounded-full {{ $statusBadges[$employee->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucwords(str_replace('_', ' ', $employee->status)) }}
                            </span>
                            <span class="px-2 py-1 text-xs rounded-full {{ $typeBadges[$employee->employment_type] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucwords(str_replace('_', ' ', $employee->employment_type)) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex space-x-2">
                    <a href="{{ route('hr.employees.edit', $employee) }}" class="px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </a>
                    <a href="{{ route('hr.employees.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Personal Information -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-medium text-gray-800 flex items-center">
                <i class="fas fa-user mr-2 text-medical-blue"></i>
                Personal Information
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">First Name</span>
                    <span class="font-medium">{{ $employee->first_name }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Last Name</span>
                    <span class="font-medium">{{ $employee->last_name }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Email</span>
                    <span class="font-medium">{{ $employee->email }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Phone</span>
                    <span class="font-medium">{{ $employee->phone ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">CNIC</span>
                    <span class="font-medium">{{ $employee->cnic ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Gender</span>
                    <span class="font-medium">{{ ucfirst($employee->gender) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Date of Birth</span>
                    <span class="font-medium">{{ $employee->date_of_birth?->format('M d, Y') ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Blood Group</span>
                    <span class="font-medium">{{ $employee->blood_group ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Address</span>
                    <span class="font-medium">{{ $employee->address ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">City</span>
                    <span class="font-medium">{{ $employee->city ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Employment Details -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-medium text-gray-800 flex items-center">
                <i class="fas fa-briefcase mr-2 text-medical-blue"></i>
                Employment Details
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Employee No</span>
                    <span class="font-medium text-medical-blue">{{ $employee->employee_no }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Department</span>
                    <span class="font-medium">{{ $employee->department->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Designation</span>
                    <span class="font-medium">{{ $employee->designation->name ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Employment Type</span>
                    <span class="font-medium">{{ ucwords(str_replace('_', ' ', $employee->employment_type)) }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Joining Date</span>
                    <span class="font-medium">{{ $employee->joining_date?->format('M d, Y') ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Default Shift</span>
                    <span class="font-medium">{{ ucfirst($employee->default_shift ?? '—') }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Shift Time</span>
                    <span class="font-medium">{{ $employee->shift_start ?? '—' }} - {{ $employee->shift_end ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Probation End Date</span>
                    <span class="font-medium">{{ $employee->probation_end_date?->format('M d, Y') ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Contract End Date</span>
                    <span class="font-medium">{{ $employee->contract_end_date?->format('M d, Y') ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Salary & Banking -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-medium text-gray-800 flex items-center">
                <i class="fas fa-money-bill-wave mr-2 text-green-500"></i>
                Salary & Banking
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Basic Salary</span>
                    <span class="font-medium text-green-600">{{ $employee->basic_salary ? format_currency($employee->basic_salary) : '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Bank Name</span>
                    <span class="font-medium">{{ $employee->bank_name ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Account No</span>
                    <span class="font-medium">{{ $employee->bank_account_no ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Branch</span>
                    <span class="font-medium">{{ $employee->bank_branch ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100 md:col-span-2">
                    <span class="text-gray-600">Expense Account</span>
                    @if($employee->expenseAccount)
                        <span class="font-medium text-right">
                            {{ $employee->expenseAccount->code }} — {{ $employee->expenseAccount->name }}
                            <a href="{{ route('accounting.employee-ledger', ['employee_id' => $employee->id]) }}" class="block text-xs text-medical-blue hover:underline mt-1">
                                View salary ledger
                            </a>
                        </span>
                    @else
                        <span class="font-medium text-gray-400">Not provisioned</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Contact -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-medium text-gray-800 flex items-center">
                <i class="fas fa-phone-alt mr-2 text-red-500"></i>
                Emergency Contact
            </h4>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Name</span>
                    <span class="font-medium">{{ $employee->emergency_contact_name ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Phone</span>
                    <span class="font-medium">{{ $employee->emergency_contact_phone ?? '—' }}</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">Relation</span>
                    <span class="font-medium">{{ $employee->emergency_contact_relation ?? '—' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-medium text-gray-800 flex items-center">
                <i class="fas fa-file-alt mr-2 text-purple-500"></i>
                Documents
            </h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($employee->documents as $document)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-900">{{ $document->title }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full bg-purple-100 text-purple-800">
                                {{ ucwords(str_replace('_', ' ', $document->document_type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @if($document->expiry_date)
                                <span class="{{ $document->expiry_date->isPast() ? 'text-red-600 font-medium' : '' }}">
                                    {{ $document->expiry_date->format('M d, Y') }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm font-medium space-x-2">
                            <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="text-medical-blue hover:text-blue-700">
                                <i class="fas fa-download"></i>
                            </a>
                            <form action="{{ route('hr.employees.delete-document', $document) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this document?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-file-alt text-3xl mb-2 text-gray-300"></i>
                            <p>No documents uploaded yet</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Upload Form -->
        <div class="p-6 border-t border-gray-200">
            <h5 class="text-md font-medium text-gray-800 mb-4">Upload New Document</h5>
            <form action="{{ route('hr.employees.upload-document', $employee) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                        <input type="text" name="title" value="{{ old('title') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Document Type *</label>
                        <select name="document_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="">Select Type</option>
                            <option value="cnic" {{ old('document_type') == 'cnic' ? 'selected' : '' }}>CNIC</option>
                            <option value="degree" {{ old('document_type') == 'degree' ? 'selected' : '' }}>Degree</option>
                            <option value="contract" {{ old('document_type') == 'contract' ? 'selected' : '' }}>Contract</option>
                            <option value="certification" {{ old('document_type') == 'certification' ? 'selected' : '' }}>Certification</option>
                            <option value="license" {{ old('document_type') == 'license' ? 'selected' : '' }}>License</option>
                            <option value="other" {{ old('document_type') == 'other' ? 'selected' : '' }}>Other</option>
                        </select>
                        @error('document_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">File *</label>
                        <input type="file" name="file"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                        <input type="date" name="expiry_date" value="{{ old('expiry_date') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        @error('expiry_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                        <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Optional notes about this document"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        @error('notes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 flex items-center">
                        <i class="fas fa-upload mr-2"></i>
                        Upload Document
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($employee->notes)
    <!-- Notes -->
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200">
            <h4 class="text-lg font-medium text-gray-800 flex items-center">
                <i class="fas fa-sticky-note mr-2 text-yellow-500"></i>
                Notes
            </h4>
        </div>
        <div class="p-6">
            <p class="text-gray-600 bg-gray-50 p-3 rounded-lg whitespace-pre-line">{{ $employee->notes }}</p>
        </div>
    </div>
    @endif
</div>
@endsection
