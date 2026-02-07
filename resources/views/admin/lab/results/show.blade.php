@extends('admin.layout')

@section('title', 'Lab Result Details - Laboratory Information System')
@section('page-title', 'Lab Result Details')
@section('page-description', 'View comprehensive laboratory test result analysis')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Enhanced Header Actions -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center mb-4 sm:mb-0">
            <a href="{{ route('lab-results.index') }}" class="text-gray-600 hover:text-gray-800 mr-4 p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Results
            </a>
            <div class="border-l border-gray-300 pl-4">
                <h1 class="text-xl font-bold text-gray-800">{{ $labResult->labOrder->labTest->name }}</h1>
                <p class="text-sm text-gray-600">Order #{{ $labResult->labOrder->order_number }} • {{ $labResult->labOrder->patient->name }}</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            @if($labResult->status === 'preliminary')
                <form action="{{ route('lab-results.verify', $labResult) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-lg hover:from-green-600 hover:to-green-700 transition-all shadow-md" 
                            onclick="return confirm('Verify and finalize this result? This action cannot be undone.')">
                        <i class="fas fa-check-circle mr-2"></i>Verify & Finalize
                    </button>
                </form>
            @endif
            <a href="{{ route('lab-results.report', $labResult) }}" target="_blank" 
               class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-6 py-3 rounded-lg hover:from-purple-600 hover:to-purple-700 transition-all shadow-md">
                <i class="fas fa-print mr-2"></i>Print Report
            </a>
            <button onclick="shareResult()" class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-3 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all shadow-md">
                <i class="fas fa-share mr-2"></i>Share
            </button>
        </div>
    </div>

    <!-- Status Alert -->
    @if($labResult->resultItems && $labResult->resultItems->filter(function($item) { return $item->isCritical(); })->count() > 0)
        <div class="bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 rounded-lg p-6 mb-6 shadow-sm">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl mr-3"></i>
                <div>
                    <h3 class="text-lg font-bold text-red-800">Critical Values Detected</h3>
                    <p class="text-red-700">This result contains critical values that require immediate attention.</p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">
        <!-- Main Content -->
        <div class="xl:col-span-3 space-y-6">
            <!-- Enhanced Test Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="bg-gradient-to-r from-medical-blue to-blue-600 text-white p-6 rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-flask text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold">Test Information</h3>
                                <p class="text-blue-100">Comprehensive test details and timeline</p>
                            </div>
                        </div>
                        <span class="px-4 py-2 rounded-full text-sm font-bold
                            {{ $labResult->status === 'final' ? 'bg-green-500 text-white' : 'bg-yellow-500 text-white' }}">
                            {{ $labResult->status === 'final' ? 'Final Report' : 'Preliminary' }}
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <div>
                            <h4 class="font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-clipboard-list text-medical-blue mr-2"></i>
                                Order Details
                            </h4>
                            <div class="space-y-3">
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-600 w-28 text-sm font-medium">Order #:</span>
                                    <span class="font-bold text-gray-900">{{ $labResult->labOrder->order_number }}</span>
                                </div>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-600 w-28 text-sm font-medium">Test Name:</span>
                                    <span class="text-gray-900">{{ $labResult->labOrder->labTest->name }}</span>
                                </div>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-600 w-28 text-sm font-medium">Priority:</span>
                                    <span class="px-3 py-1 text-xs rounded-full font-bold
                                        {{ $labResult->labOrder->priority === 'stat' ? 'bg-red-100 text-red-800' : 
                                           ($labResult->labOrder->priority === 'urgent' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
                                        {{ strtoupper($labResult->labOrder->priority) }}
                                    </span>
                                </div>
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <span class="text-gray-600 w-28 text-sm font-medium">Location:</span>
                                    <span class="px-3 py-1 text-xs rounded-full font-bold
                                        {{ $labResult->labOrder->test_location === 'indoor' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ $labResult->labOrder->test_location === 'indoor' ? 'In-House Lab' : 'External Lab' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-800 mb-4 flex items-center">
                                <i class="fas fa-clock text-medical-green mr-2"></i>
                                Timeline
                            </h4>
                            <div class="space-y-3">
                                <div class="flex items-center p-3 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                                    <div class="flex-1">
                                        <span class="text-blue-700 text-sm font-medium block">Ordered</span>
                                        <span class="text-blue-900 font-semibold">{{ $labResult->labOrder->ordered_at->format('M d, Y H:i') }}</span>
                                    </div>
                                    <i class="fas fa-check-circle text-blue-600"></i>
                                </div>
                                @if($labResult->labOrder->sample_collected_at)
                                    <div class="flex items-center p-3 bg-green-50 rounded-lg border-l-4 border-green-500">
                                        <div class="flex-1">
                                            <span class="text-green-700 text-sm font-medium block">Sample Collected</span>
                                            <span class="text-green-900 font-semibold">{{ $labResult->labOrder->sample_collected_at->format('M d, Y H:i') }}</span>
                                        </div>
                                        <i class="fas fa-vial text-green-600"></i>
                                    </div>
                                @endif
                                @if($labResult->tested_at)
                                    <div class="flex items-center p-3 bg-purple-50 rounded-lg border-l-4 border-purple-500">
                                        <div class="flex-1">
                                            <span class="text-purple-700 text-sm font-medium block">Testing Completed</span>
                                            <span class="text-purple-900 font-semibold">{{ $labResult->tested_at->format('M d, Y H:i') }}</span>
                                        </div>
                                        <i class="fas fa-microscope text-purple-600"></i>
                                    </div>
                                @endif
                                @if($labResult->verified_at)
                                    <div class="flex items-center p-3 bg-emerald-50 rounded-lg border-l-4 border-emerald-500">
                                        <div class="flex-1">
                                            <span class="text-emerald-700 text-sm font-medium block">Verified</span>
                                            <span class="text-emerald-900 font-semibold">{{ $labResult->verified_at->format('M d, Y H:i') }}</span>
                                        </div>
                                        <i class="fas fa-certificate text-emerald-600"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Test Results -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="bg-gradient-to-r from-medical-green to-green-600 text-white p-6 rounded-t-xl">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-chart-line text-white text-xl"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Laboratory Results</h3>
                            <p class="text-green-100">Detailed parameter analysis and values</p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    @if($labResult->resultItems && $labResult->resultItems->count() > 0)
                        <!-- Enhanced Parameter-based Results -->
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider">
                                            <i class="fas fa-vial mr-2"></i>Parameter
                                        </th>
                                        <th class="px-6 py-4 text-center text-sm font-bold text-gray-700 uppercase tracking-wider">
                                            <i class="fas fa-chart-bar mr-2"></i>Result
                                        </th>
                                        <th class="px-6 py-4 text-center text-sm font-bold text-gray-700 uppercase tracking-wider">
                                            <i class="fas fa-ruler mr-2"></i>Unit
                                        </th>
                                        <th class="px-6 py-4 text-center text-sm font-bold text-gray-700 uppercase tracking-wider">
                                            <i class="fas fa-balance-scale mr-2"></i>Reference
                                        </th>
                                        <th class="px-6 py-4 text-center text-sm font-bold text-gray-700 uppercase tracking-wider">
                                            <i class="fas fa-flag mr-2"></i>Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($labResult->resultItems as $index => $item)
                                        @php
                                            $rowClass = '';
                                            if ($item->isCritical()) {
                                                $rowClass = 'bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500';
                                            } elseif ($item->isAbnormal()) {
                                                $rowClass = 'bg-gradient-to-r from-orange-50 to-orange-100 border-l-4 border-orange-400';
                                            } else {
                                                $rowClass = 'bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-400';
                                            }
                                        @endphp
                                        <tr class="{{ $rowClass }} hover:shadow-md transition-all">
                                            <td class="px-6 py-4">
                                                <div class="flex items-center">
                                                    <div class="w-3 h-8 {{ $item->isAbnormal() ? 'bg-red-400' : 'bg-green-400' }} rounded-full mr-3"></div>
                                                    <div>
                                                        <div class="text-sm font-bold text-gray-900">{{ $item->parameter->name ?? 'N/A' }}</div>
                                                        @if($item->parameter->description)
                                                            <div class="text-xs text-gray-600">{{ Str::limit($item->parameter->description, 50) }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <span class="text-xl font-bold {{ $item->isAbnormal() ? 'text-red-700' : 'text-green-700' }}">
                                                    {{ $item->value }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-center text-sm text-gray-600 font-medium">
                                                {{ $item->unit ?? '-' }}
                                            </td>
                                            <td class="px-6 py-4 text-center text-sm text-gray-600 font-mono">
                                                {{ $item->parameter->reference_range ?? 'Not specified' }}
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                @if($item->flag && $item->flag !== 'N')
                                                    @php
                                                        $flagConfig = [
                                                            'H' => ['bg' => 'bg-orange-500', 'text' => 'text-white', 'icon' => 'fa-arrow-up', 'label' => 'High'],
                                                            'L' => ['bg' => 'bg-orange-500', 'text' => 'text-white', 'icon' => 'fa-arrow-down', 'label' => 'Low'],
                                                            'HH' => ['bg' => 'bg-red-600', 'text' => 'text-white', 'icon' => 'fa-exclamation-triangle', 'label' => 'Critical High'],
                                                            'LL' => ['bg' => 'bg-red-600', 'text' => 'text-white', 'icon' => 'fa-exclamation-triangle', 'label' => 'Critical Low'],
                                                            'A' => ['bg' => 'bg-yellow-500', 'text' => 'text-white', 'icon' => 'fa-question-circle', 'label' => 'Abnormal']
                                                        ];
                                                        $config = $flagConfig[$item->flag] ?? $flagConfig['A'];
                                                    @endphp
                                                    <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-bold {{ $config['bg'] }} {{ $config['text'] }} shadow-md">
                                                        <i class="fas {{ $config['icon'] }} mr-1"></i>
                                                        {{ $config['label'] }}
                                                    </div>
                                                @else
                                                    <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-bold bg-green-500 text-white shadow-md">
                                                        <i class="fas fa-check-circle mr-1"></i>
                                                        Normal
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                        @if($item->comment)
                                            <tr class="bg-blue-50">
                                                <td colspan="5" class="px-6 py-3">
                                                    <div class="flex items-start bg-blue-100 rounded-lg p-3">
                                                        <i class="fas fa-comment-medical text-blue-600 mr-2 mt-1"></i>
                                                        <div>
                                                            <span class="text-sm font-semibold text-blue-800">Technical Comment:</span>
                                                            <p class="text-sm text-blue-700 mt-1">{{ $item->comment }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Result Summary Cards -->
                        @php
                            $totalParams = $labResult->resultItems->count();
                            $abnormalParams = $labResult->resultItems->filter(function($item) { return $item->isAbnormal(); })->count();
                            $criticalParams = $labResult->resultItems->filter(function($item) { return $item->isCritical(); })->count();
                            $normalParams = $totalParams - $abnormalParams;
                        @endphp
                        
                        <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 rounded-lg text-center shadow-md">
                                <div class="text-2xl font-bold">{{ $totalParams }}</div>
                                <div class="text-sm opacity-90">Total Tests</div>
                            </div>
                            <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4 rounded-lg text-center shadow-md">
                                <div class="text-2xl font-bold">{{ $normalParams }}</div>
                                <div class="text-sm opacity-90">Normal</div>
                            </div>
                            <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-4 rounded-lg text-center shadow-md">
                                <div class="text-2xl font-bold">{{ $abnormalParams }}</div>
                                <div class="text-sm opacity-90">Abnormal</div>
                            </div>
                            <div class="bg-gradient-to-r from-red-500 to-red-600 text-white p-4 rounded-lg text-center shadow-md">
                                <div class="text-2xl font-bold">{{ $criticalParams }}</div>
                                <div class="text-sm opacity-90">Critical</div>
                            </div>
                        </div>
                    @else
                        <!-- Enhanced Text-based Results -->
                        <div class="bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-dashed border-gray-300 rounded-xl p-8 text-center">
                            <i class="fas fa-file-alt text-gray-400 text-4xl mb-4"></i>
                            @if($labResult->results && is_array($labResult->results) && count($labResult->results) > 0)
                                <div class="text-left max-w-2xl mx-auto space-y-4">
                                    @foreach($labResult->results as $key => $value)
                                        <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-sm">
                                            <span class="font-bold text-gray-700 text-lg block mb-2">{{ ucfirst(str_replace('_', ' ', $key)) }}</span>
                                            <div class="text-gray-800 text-base">{{ $value }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <h4 class="text-xl font-semibold text-gray-600 mb-2">No Detailed Results Available</h4>
                                <p class="text-gray-500">Please contact the laboratory for additional information.</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Enhanced Comments & Interpretation -->
            @if($labResult->comments || $labResult->interpretation)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white p-6 rounded-t-xl">
                        <div class="flex items-center">
                            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                                <i class="fas fa-stethoscope text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold">Clinical Information</h3>
                                <p class="text-purple-100">Professional comments and interpretation</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 space-y-6">
                        @if($labResult->comments)
                            <div class="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-yellow-500 rounded-lg p-6">
                                <div class="flex items-center mb-3">
                                    <i class="fas fa-flask text-yellow-600 mr-2 text-lg"></i>
                                    <h4 class="font-bold text-yellow-800 text-lg">Laboratory Comments</h4>
                                </div>
                                <p class="text-yellow-700 leading-relaxed">{{ $labResult->comments }}</p>
                            </div>
                        @endif
                        
                        @if($labResult->interpretation)
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 rounded-lg p-6">
                                <div class="flex items-center mb-3">
                                    <i class="fas fa-microscope text-blue-600 mr-2 text-lg"></i>
                                    <h4 class="font-bold text-blue-800 text-lg">Clinical Interpretation</h4>
                                </div>
                                <p class="text-blue-700 leading-relaxed">{{ $labResult->interpretation }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Enhanced Sidebar -->
        <div class="space-y-6">
            <!-- Patient Information Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 text-white p-4 rounded-t-xl">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user text-white"></i>
                        </div>
                        <h3 class="text-lg font-bold">Patient Information</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-id-card text-indigo-600 mr-3"></i>
                            <div>
                                <span class="text-xs text-gray-600 block">Full Name</span>
                                <span class="font-bold text-gray-900">{{ $labResult->labOrder->patient->name }}</span>
                            </div>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-hashtag text-indigo-600 mr-3"></i>
                            <div>
                                <span class="text-xs text-gray-600 block">Patient ID</span>
                                <span class="font-mono text-gray-900">{{ $labResult->labOrder->patient->patient_no }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-birthday-cake text-indigo-600 mr-2"></i>
                                <div>
                                    <span class="text-xs text-gray-600 block">Age</span>
                                    <span class="text-gray-900 font-medium">{{ $labResult->labOrder->patient->age }}y</span>
                                </div>
                            </div>
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-venus-mars text-indigo-600 mr-2"></i>
                                <div>
                                    <span class="text-xs text-gray-600 block">Gender</span>
                                    <span class="text-gray-900 font-medium">{{ ucfirst($labResult->labOrder->patient->gender) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-phone text-indigo-600 mr-3"></i>
                            <div>
                                <span class="text-xs text-gray-600 block">Contact</span>
                                <span class="text-gray-900">{{ $labResult->labOrder->patient->phone }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visit Information Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 text-white p-4 rounded-t-xl">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-clipboard-list text-white"></i>
                        </div>
                        <h3 class="text-lg font-bold">Visit Details</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-hashtag text-emerald-600 mr-3"></i>
                            <div>
                                <span class="text-xs text-gray-600 block">Visit Number</span>
                                <span class="font-mono text-gray-900">{{ $labResult->labOrder->visit->visit_no }}</span>
                            </div>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-hospital text-emerald-600 mr-3"></i>
                            <div>
                                <span class="text-xs text-gray-600 block">Visit Type</span>
                                <span class="uppercase text-gray-900 font-medium">{{ $labResult->labOrder->visit->visit_type }}</span>
                            </div>
                        </div>
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <i class="fas fa-calendar text-emerald-600 mr-3"></i>
                            <div>
                                <span class="text-xs text-gray-600 block">Visit Date</span>
                                <span class="text-gray-900">{{ $labResult->labOrder->visit->visit_datetime->format('M d, Y H:i') }}</span>
                            </div>
                        </div>
                        @if($labResult->labOrder->doctor)
                            <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                <i class="fas fa-user-md text-emerald-600 mr-3"></i>
                                <div>
                                    <span class="text-xs text-gray-600 block">Ordering Doctor</span>
                                    <span class="text-gray-900 font-medium">Dr. {{ $labResult->labOrder->doctor->name }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Clinical Notes Card -->
            @if($labResult->labOrder->clinical_notes)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 rounded-t-xl">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-notes-medical text-white"></i>
                            </div>
                            <h3 class="text-lg font-bold">Clinical Notes</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-gray-700 leading-relaxed">{{ $labResult->labOrder->clinical_notes }}</p>
                    </div>
                </div>
            @endif

            <!-- Staff Information Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="bg-gradient-to-r from-gray-600 to-gray-700 text-white p-4 rounded-t-xl">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-users text-white"></i>
                        </div>
                        <h3 class="text-lg font-bold">Laboratory Staff</h3>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-user-cog text-blue-600 mr-2"></i>
                                <span class="text-sm font-semibold text-blue-800">Laboratory Technician</span>
                            </div>
                            <div class="text-blue-900 font-medium">{{ $labResult->technician->name ?? 'Not specified' }}</div>
                            @if($labResult->tested_at)
                                <div class="text-xs text-blue-600 mt-1">{{ $labResult->tested_at->format('M d, Y H:i') }}</div>
                            @endif
                        </div>
                        
                        @if($labResult->pathologist)
                            <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-user-md text-green-600 mr-2"></i>
                                    <span class="text-sm font-semibold text-green-800">Pathologist</span>
                                </div>
                                <div class="text-green-900 font-medium">{{ $labResult->pathologist->name }}</div>
                                @if($labResult->verified_at)
                                    <div class="text-xs text-green-600 mt-1">{{ $labResult->verified_at->format('M d, Y H:i') }}</div>
                                @endif
                            </div>
                        @else
                            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-clock text-yellow-600 mr-2"></i>
                                    <span class="text-sm font-semibold text-yellow-800">Verification Status</span>
                                </div>
                                <div class="text-yellow-700 font-medium">Pending Professional Review</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function shareResult() {
    if (navigator.share) {
        navigator.share({
            title: 'Lab Result - {{ $labResult->labOrder->order_number }}',
            text: 'Laboratory test result for {{ $labResult->labOrder->patient->name }}',
            url: window.location.href
        });
    } else {
        // Fallback - copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link copied to clipboard!');
        });
    }
}
</script>
@endsection