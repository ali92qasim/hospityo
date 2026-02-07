<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Report - {{ $labResult->labOrder->order_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'medical-blue': '#0066CC',
                        'medical-green': '#00A86B',
                        'medical-light': '#F0F8FF',
                        'medical-gray': '#6B7280'
                    }
                }
            }
        }
    </script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; }
            .print-break { page-break-before: always; }
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 6rem;
            color: rgba(0, 102, 204, 0.1);
            z-index: -1;
            pointer-events: none;
        }
        
        .result-table th {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .critical-high { 
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 4px solid #dc2626;
        }
        
        .critical-low { 
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 4px solid #dc2626;
        }
        
        .abnormal-high { 
            background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
            border-left: 4px solid #ea580c;
        }
        
        .abnormal-low { 
            background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
            border-left: 4px solid #ea580c;
        }
        
        .normal-result {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }
    </style>
</head>
<body class="bg-white">
    <!-- Watermark -->
    @if($labResult->status === 'preliminary')
        <div class="watermark">PRELIMINARY</div>
    @endif

    <div class="max-w-4xl mx-auto p-8">
        <!-- Print Controls -->
        <div class="no-print mb-6 flex justify-between items-center bg-gray-50 p-4 rounded-lg">
            <div class="flex space-x-3">
                <button onclick="window.print()" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-print mr-2"></i>Print Report
                </button>
                <button onclick="downloadPDF()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    <i class="fas fa-file-pdf mr-2"></i>Download PDF
                </button>
                <button onclick="emailReport()" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                    <i class="fas fa-envelope mr-2"></i>Email Report
                </button>
            </div>
            <div class="text-sm text-gray-600">
                <i class="fas fa-info-circle mr-1"></i>
                Report generated on {{ now()->format('M d, Y \a\t H:i') }}
            </div>
        </div>

        <!-- Hospital Header -->
        <div class="border-b-4 border-medical-blue pb-6 mb-8">
            <div class="flex justify-between items-start">
                <div class="flex items-center">
                    <div class="w-16 h-16 bg-medical-blue rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-hospital text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-medical-blue mb-1">Hospityo</h1>
                        <p class="text-gray-600 text-lg">Laboratory Services Department</p>
                        <p class="text-sm text-gray-500">Accredited Laboratory • ISO 15189:2012</p>
                    </div>
                </div>
                <div class="text-right">
                    <div class="bg-medical-light p-4 rounded-lg border border-medical-blue">
                        <h2 class="text-xl font-bold text-medical-blue mb-2">LABORATORY REPORT</h2>
                        <div class="text-sm text-gray-700 space-y-1">
                            <p><strong>Report ID:</strong> {{ $labResult->id }}</p>
                            <p><strong>Order #:</strong> {{ $labResult->labOrder->order_number }}</p>
                            <p><strong>Report Date:</strong> {{ now()->format('M d, Y') }}</p>
                            <p><strong>Report Time:</strong> {{ now()->format('H:i:s') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patient & Test Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Patient Information Card -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-medical-blue rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Patient Information</h3>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="space-y-3">
                        <div>
                            <span class="font-semibold text-gray-600 block">Full Name</span>
                            <span class="text-gray-900 font-medium text-lg">{{ $labResult->labOrder->patient->name }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600 block">Patient ID</span>
                            <span class="text-gray-900 font-mono">{{ $labResult->labOrder->patient->patient_no }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600 block">Date of Birth</span>
                            <span class="text-gray-900">{{ $labResult->labOrder->patient->date_of_birth ?? 'Not specified' }}</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <span class="font-semibold text-gray-600 block">Age</span>
                            <span class="text-gray-900">{{ $labResult->labOrder->patient->age }} years</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600 block">Gender</span>
                            <span class="text-gray-900">{{ ucfirst($labResult->labOrder->patient->gender) }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600 block">Contact</span>
                            <span class="text-gray-900">{{ $labResult->labOrder->patient->phone }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Test Information Card -->
            <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6">
                <div class="flex items-center mb-4">
                    <div class="w-10 h-10 bg-medical-green rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-flask text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800">Test Information</h3>
                </div>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="space-y-3">
                        <div>
                            <span class="font-semibold text-gray-600 block">Test Name</span>
                            <span class="text-gray-900 font-medium">{{ $labResult->labOrder->labTest->name }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600 block">Visit Number</span>
                            <span class="text-gray-900 font-mono">{{ $labResult->labOrder->visit->visit_no }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600 block">Ordering Doctor</span>
                            <span class="text-gray-900">Dr. {{ $labResult->labOrder->doctor->name ?? 'N/A' }}</span>
                        </div>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <span class="font-semibold text-gray-600 block">Priority</span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold
                                {{ $labResult->labOrder->priority === 'stat' ? 'bg-red-100 text-red-800' : 
                                   ($labResult->labOrder->priority === 'urgent' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ strtoupper($labResult->labOrder->priority) }}
                            </span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600 block">Sample Collection</span>
                            <span class="text-gray-900">{{ $labResult->labOrder->sample_collected_at ? $labResult->labOrder->sample_collected_at->format('M d, Y H:i') : 'Not recorded' }}</span>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-600 block">Test Location</span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold
                                {{ $labResult->labOrder->test_location === 'indoor' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                {{ $labResult->labOrder->test_location === 'indoor' ? 'In-House Laboratory' : 'External Laboratory' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Results Section -->
        <div class="mb-8">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-r from-medical-blue to-blue-600 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-chart-line text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-800">Laboratory Results</h3>
                    <p class="text-gray-600">Detailed test parameters and values</p>
                </div>
            </div>
            
            @if($labResult->resultItems && $labResult->resultItems->count() > 0)
                <!-- Enhanced Parameter-based Results -->
                <div class="bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                    <table class="w-full result-table">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <th class="px-6 py-4 text-left text-sm font-bold text-gray-700 uppercase tracking-wider border-b-2 border-gray-200">
                                    <i class="fas fa-vial mr-2"></i>Parameter
                                </th>
                                <th class="px-6 py-4 text-center text-sm font-bold text-gray-700 uppercase tracking-wider border-b-2 border-gray-200">
                                    <i class="fas fa-chart-bar mr-2"></i>Result
                                </th>
                                <th class="px-6 py-4 text-center text-sm font-bold text-gray-700 uppercase tracking-wider border-b-2 border-gray-200">
                                    <i class="fas fa-ruler mr-2"></i>Unit
                                </th>
                                <th class="px-6 py-4 text-center text-sm font-bold text-gray-700 uppercase tracking-wider border-b-2 border-gray-200">
                                    <i class="fas fa-balance-scale mr-2"></i>Reference Range
                                </th>
                                <th class="px-6 py-4 text-center text-sm font-bold text-gray-700 uppercase tracking-wider border-b-2 border-gray-200">
                                    <i class="fas fa-flag mr-2"></i>Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($labResult->resultItems as $index => $item)
                                @php
                                    $rowClass = '';
                                    if ($item->flag === 'HH' || $item->flag === 'LL') {
                                        $rowClass = 'critical-high';
                                    } elseif ($item->flag === 'H' || $item->flag === 'L') {
                                        $rowClass = 'abnormal-high';
                                    } elseif ($item->flag === 'N' || !$item->flag) {
                                        $rowClass = 'normal-result';
                                    }
                                @endphp
                                <tr class="{{ $rowClass }} {{ $index % 2 === 0 ? 'bg-opacity-50' : 'bg-opacity-30' }} border-b border-gray-100 hover:bg-opacity-70 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-2 h-8 {{ $item->isAbnormal() ? 'bg-red-400' : 'bg-green-400' }} rounded-full mr-3"></div>
                                            <div>
                                                <div class="text-sm font-bold text-gray-900">{{ $item->parameter->name ?? 'N/A' }}</div>
                                                @if($item->parameter->description)
                                                    <div class="text-xs text-gray-600">{{ $item->parameter->description }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="text-lg font-bold {{ $item->isAbnormal() ? 'text-red-700' : 'text-green-700' }}">
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
                                                    'H' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'icon' => 'fa-arrow-up', 'label' => 'High'],
                                                    'L' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'icon' => 'fa-arrow-down', 'label' => 'Low'],
                                                    'HH' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-exclamation-triangle', 'label' => 'Critical High'],
                                                    'LL' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-exclamation-triangle', 'label' => 'Critical Low'],
                                                    'A' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'icon' => 'fa-question-circle', 'label' => 'Abnormal']
                                                ];
                                                $config = $flagConfig[$item->flag] ?? $flagConfig['A'];
                                            @endphp
                                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-bold {{ $config['bg'] }} {{ $config['text'] }} border-2 border-current">
                                                <i class="fas {{ $config['icon'] }} mr-1"></i>
                                                {{ $config['label'] }}
                                            </div>
                                        @else
                                            <div class="inline-flex items-center px-3 py-2 rounded-full text-xs font-bold bg-green-100 text-green-800 border-2 border-green-200">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Normal
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                @if($item->comment)
                                    <tr class="bg-blue-50 border-b border-blue-200">
                                        <td colspan="5" class="px-6 py-3">
                                            <div class="flex items-start">
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

                <!-- Summary Statistics -->
                @php
                    $totalParams = $labResult->resultItems->count();
                    $abnormalParams = $labResult->resultItems->filter(function($item) { return $item->isAbnormal(); })->count();
                    $criticalParams = $labResult->resultItems->filter(function($item) { return $item->isCritical(); })->count();
                    $normalParams = $totalParams - $abnormalParams;
                @endphp
                
                <div class="mt-6 grid grid-cols-4 gap-4">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold">{{ $totalParams }}</div>
                        <div class="text-sm opacity-90">Total Parameters</div>
                    </div>
                    <div class="bg-gradient-to-r from-green-500 to-green-600 text-white p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold">{{ $normalParams }}</div>
                        <div class="text-sm opacity-90">Normal Results</div>
                    </div>
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 text-white p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold">{{ $abnormalParams }}</div>
                        <div class="text-sm opacity-90">Abnormal Results</div>
                    </div>
                    <div class="bg-gradient-to-r from-red-500 to-red-600 text-white p-4 rounded-lg text-center">
                        <div class="text-2xl font-bold">{{ $criticalParams }}</div>
                        <div class="text-sm opacity-90">Critical Results</div>
                    </div>
                </div>
            @else
                <!-- Enhanced Text-based Results -->
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 border-2 border-dashed border-gray-300 rounded-xl p-8 text-center">
                    <i class="fas fa-file-alt text-gray-400 text-4xl mb-4"></i>
                    @if($labResult->results && is_array($labResult->results) && count($labResult->results) > 0)
                        <div class="text-left max-w-2xl mx-auto">
                            @foreach($labResult->results as $key => $value)
                                <div class="mb-4 p-4 bg-white rounded-lg border border-gray-200">
                                    <span class="font-bold text-gray-700 text-lg">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                    <div class="text-gray-800 mt-2 text-base">{{ $value }}</div>
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

        <!-- Enhanced Clinical Notes & Interpretation -->
        @if($labResult->labOrder->clinical_notes || $labResult->comments || $labResult->interpretation)
            <div class="mb-8">
                <div class="flex items-center mb-6">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-purple-600 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-stethoscope text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold text-gray-800">Clinical Information</h3>
                        <p class="text-gray-600">Notes and professional interpretation</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 gap-6">
                    @if($labResult->labOrder->clinical_notes)
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-l-4 border-blue-500 rounded-lg p-6">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-user-md text-blue-600 mr-2"></i>
                                <h4 class="font-bold text-blue-800 text-lg">Ordering Physician Notes</h4>
                            </div>
                            <p class="text-blue-700 leading-relaxed">{{ $labResult->labOrder->clinical_notes }}</p>
                        </div>
                    @endif
                    
                    @if($labResult->comments)
                        <div class="bg-gradient-to-r from-yellow-50 to-amber-50 border-l-4 border-yellow-500 rounded-lg p-6">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-flask text-yellow-600 mr-2"></i>
                                <h4 class="font-bold text-yellow-800 text-lg">Laboratory Comments</h4>
                            </div>
                            <p class="text-yellow-700 leading-relaxed">{{ $labResult->comments }}</p>
                        </div>
                    @endif
                    
                    @if($labResult->interpretation)
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 border-l-4 border-green-500 rounded-lg p-6">
                            <div class="flex items-center mb-3">
                                <i class="fas fa-microscope text-green-600 mr-2"></i>
                                <h4 class="font-bold text-green-800 text-lg">Clinical Interpretation</h4>
                            </div>
                            <p class="text-green-700 leading-relaxed">{{ $labResult->interpretation }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Enhanced Flag Legend -->
        @if($labResult->resultItems && $labResult->resultItems->where('flag', '!=', 'N')->count() > 0)
            <div class="mb-8">
                <div class="bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-200 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Result Flag Legend
                    </h3>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="flex items-center p-3 bg-green-50 rounded-lg border border-green-200">
                            <div class="w-8 h-8 bg-green-100 text-green-800 rounded-full text-center font-bold flex items-center justify-center mr-3 text-sm">N</div>
                            <span class="text-green-800 font-medium">Normal Range</span>
                        </div>
                        <div class="flex items-center p-3 bg-orange-50 rounded-lg border border-orange-200">
                            <div class="w-8 h-8 bg-orange-100 text-orange-800 rounded-full text-center font-bold flex items-center justify-center mr-3 text-sm">H/L</div>
                            <span class="text-orange-800 font-medium">High/Low</span>
                        </div>
                        <div class="flex items-center p-3 bg-red-50 rounded-lg border border-red-200">
                            <div class="w-8 h-8 bg-red-600 text-white rounded-full text-center font-bold flex items-center justify-center mr-3 text-xs">HH/LL</div>
                            <span class="text-red-800 font-medium">Critical Values</span>
                        </div>
                        <div class="flex items-center p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                            <div class="w-8 h-8 bg-yellow-100 text-yellow-800 rounded-full text-center font-bold flex items-center justify-center mr-3 text-sm">A</div>
                            <span class="text-yellow-800 font-medium">Abnormal</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Enhanced Signatures & Verification -->
        <div class="border-t-2 border-gray-200 pt-8 mb-8">
            <div class="flex items-center mb-6">
                <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full flex items-center justify-center mr-4">
                    <i class="fas fa-certificate text-white text-xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-bold text-gray-800">Professional Verification</h3>
                    <p class="text-gray-600">Laboratory staff signatures and timestamps</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Technician Section -->
                <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user-cog text-white"></i>
                        </div>
                        <h4 class="text-lg font-bold text-blue-800">Laboratory Technician</h4>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm font-semibold text-blue-700 block">Performed By:</span>
                            <span class="text-blue-900 font-medium text-lg">{{ $labResult->technician->name ?? 'Not specified' }}</span>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-blue-700 block">Test Date & Time:</span>
                            <span class="text-blue-900 font-mono">{{ $labResult->tested_at ? $labResult->tested_at->format('M d, Y \a\t H:i:s') : 'Not recorded' }}</span>
                        </div>
                        @if($labResult->tested_at)
                            <div class="mt-4 pt-4 border-t border-blue-200">
                                <div class="flex items-center text-blue-700">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <span class="text-sm font-medium">Test Completed</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Pathologist Section -->
                <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-xl p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-green-600 rounded-full flex items-center justify-center mr-3">
                            <i class="fas fa-user-md text-white"></i>
                        </div>
                        <h4 class="text-lg font-bold text-green-800">Pathologist Verification</h4>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <span class="text-sm font-semibold text-green-700 block">Verified By:</span>
                            <span class="text-green-900 font-medium text-lg">{{ $labResult->pathologist->name ?? 'Pending Verification' }}</span>
                        </div>
                        <div>
                            <span class="text-sm font-semibold text-green-700 block">Verification Date & Time:</span>
                            <span class="text-green-900 font-mono">{{ $labResult->verified_at ? $labResult->verified_at->format('M d, Y \a\t H:i:s') : 'Pending' }}</span>
                        </div>
                        @if($labResult->verified_at)
                            <div class="mt-4 pt-4 border-t border-green-200">
                                <div class="flex items-center text-green-700">
                                    <i class="fas fa-certificate mr-2"></i>
                                    <span class="text-sm font-medium">Professionally Verified</span>
                                </div>
                            </div>
                        @else
                            <div class="mt-4 pt-4 border-t border-yellow-200 bg-yellow-50 -mx-6 -mb-6 px-6 pb-6 rounded-b-xl">
                                <div class="flex items-center text-yellow-700">
                                    <i class="fas fa-clock mr-2"></i>
                                    <span class="text-sm font-medium">Awaiting Professional Review</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Status Badge -->
        <div class="text-center mb-8">
            @if($labResult->status === 'final')
                <div class="inline-flex items-center px-8 py-4 rounded-full text-lg font-bold bg-gradient-to-r from-green-500 to-green-600 text-white shadow-lg">
                    <i class="fas fa-certificate mr-3 text-xl"></i>
                    FINAL VERIFIED REPORT
                    <i class="fas fa-seal-check ml-3 text-xl"></i>
                </div>
                <p class="text-sm text-gray-600 mt-2">This report has been reviewed and approved by a qualified pathologist</p>
            @else
                <div class="inline-flex items-center px-8 py-4 rounded-full text-lg font-bold bg-gradient-to-r from-yellow-500 to-orange-500 text-white shadow-lg">
                    <i class="fas fa-clock mr-3 text-xl"></i>
                    PRELIMINARY REPORT
                    <i class="fas fa-exclamation-triangle ml-3 text-xl"></i>
                </div>
                <p class="text-sm text-gray-600 mt-2">This is a preliminary report pending pathologist verification</p>
            @endif
        </div>

        <!-- QR Code & Digital Verification -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-200 rounded-xl p-6 mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h4 class="font-bold text-gray-800 mb-2">Digital Verification</h4>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Report ID:</strong> {{ $labResult->id }}</p>
                        <p><strong>Generated:</strong> {{ now()->format('M d, Y \a\t H:i:s T') }}</p>
                        <p><strong>Checksum:</strong> {{ strtoupper(substr(md5($labResult->id . $labResult->updated_at), 0, 8)) }}</p>
                    </div>
                </div>
                <div class="text-center">
                    <div class="w-20 h-20 bg-white border-2 border-gray-300 rounded-lg flex items-center justify-center">
                        <i class="fas fa-qrcode text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">QR Verification</p>
                </div>
            </div>
        </div>

        <!-- Enhanced Footer -->
        <div class="border-t-2 border-medical-blue pt-6 text-center">
            <div class="mb-4">
                <h4 class="text-lg font-bold text-medical-blue mb-2">Hospityo Laboratory Services</h4>
                <p class="text-sm text-gray-600">Accredited by CAP • CLIA Certified • ISO 15189:2012 Compliant</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-500 mb-4">
                <div>
                    <i class="fas fa-phone mr-1"></i>
                    Emergency: (555) 123-4567
                </div>
                <div>
                    <i class="fas fa-envelope mr-1"></i>
                    lab@hospityo.com
                </div>
                <div>
                    <i class="fas fa-globe mr-1"></i>
                    www.hospityo.com/lab
                </div>
            </div>
            
            <div class="text-xs text-gray-400 border-t border-gray-200 pt-4">
                <p class="mb-1">This report was generated electronically by Hospityo Laboratory Information System</p>
                <p class="mb-1">{{ now()->format('l, F d, Y \a\t H:i:s T') }}</p>
                <p>For questions about this report, please contact the laboratory at the numbers above.</p>
            </div>
        </div>
    </div>

    <script>
        // Enhanced Print Functionality
        function printReport() {
            window.print();
        }
        
        // PDF Download Simulation
        function downloadPDF() {
            alert('PDF download functionality would be implemented here.\nThis would generate a PDF version of the report.');
            // In a real implementation, this would call a backend endpoint to generate PDF
        }
        
        // Email Report Simulation
        function emailReport() {
            const email = prompt('Enter email address to send report:');
            if (email) {
                alert(`Report would be sent to: ${email}\nThis functionality would be implemented on the backend.`);
                // In a real implementation, this would call a backend endpoint to send email
            }
        }
        
        // Auto-print when opened with print parameter
        if (window.location.search.includes('print=1')) {
            window.onload = function() {
                setTimeout(() => {
                    window.print();
                }, 1000);
            }
        }
        
        // Print optimization
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
        });
        
        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'p':
                        e.preventDefault();
                        printReport();
                        break;
                    case 's':
                        e.preventDefault();
                        downloadPDF();
                        break;
                }
            }
        });
    </script>
</body>
</html>