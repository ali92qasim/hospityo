                <div class="max-w-7xl mx-auto">
                    @if($workflowData['can_order_labs'])
                        <!-- Order Investigations Form -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center">
                                    <i class="fas fa-plus-circle text-medical-blue mr-2"></i>
                                    <h5 class="font-semibold text-gray-800">Order Investigations</h5>
                                </div>
                                <span class="text-xs text-gray-500">Select multiple investigations to order at once</span>
                            </div>
                                
                                <form action="{{ route('visits.order-multiple-lab-tests', $visit) }}" method="POST" id="lab-tests-form">
                                    @csrf

                                    @if($workflowData['show_order_doctor_picker'] ?? false)
                                        <div class="mb-4">
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Ordering Doctor (from care team)</label>
                                            <select name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                                                <option value="">Select Doctor</option>
                                                @foreach($visit->careTeam as $member)
                                                    <option value="{{ $member->doctor_id }}">
                                                        Dr. {{ $member->doctor->name }} - {{ $member->doctor->specialization }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('doctor_id')
                                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    @endif
                                    
                                    <!-- Dynamic Test Table -->
                                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                        <div class="flex items-center justify-between mb-4">
                                            <h6 class="text-sm font-medium text-gray-700 flex items-center">
                                                <i class="fas fa-flask text-gray-500 mr-2"></i>
                                                Test Selection
                                            </h6>
                                            <button type="button" onclick="addTestRow()" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-medical-blue bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors">
                                                <i class="fas fa-plus mr-1"></i>Add Test
                                            </button>
                                        </div>
                                        
                                        <div class="overflow-x-auto">
                                            <table class="w-full" id="tests-table">
                                                <thead>
                                                    <tr class="text-xs font-semibold text-gray-600 uppercase tracking-wider border-b-2 border-gray-200">
                                                        <th class="text-left py-3 pr-4">Investigation</th>
                                                        <th class="text-center py-3 px-3 w-20">Qty</th>
                                                        <th class="text-center py-3 px-3 w-32">Priority</th>
                                                        <th class="text-left py-3 px-3">Clinical Notes</th>
                                                        <th class="w-10"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="test-rows">
                                                    <tr class="test-row border-b border-gray-100 hover:bg-gray-25">
                                                        <td class="py-3 pr-4">
                                                            <select name="tests[0][lab_test_id]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" required>
                                                                <option value="">Select investigation...</option>
                                                                @php
                                                                    $groupedInvestigations = $investigations->groupBy('category');
                                                                    $categoryLabels = [
                                                                        'hematology'        => 'Hematology',
                                                                        'biochemistry'      => 'Biochemistry',
                                                                        'microbiology'      => 'Microbiology',
                                                                        'immunology'        => 'Immunology',
                                                                        'pathology'         => 'Pathology',
                                                                        'histopathology'    => 'Histopathology',
                                                                        'molecular'         => 'Molecular Biology',
                                                                        'x-ray'             => 'X-Ray',
                                                                        'ultrasound'        => 'Ultrasound',
                                                                        'ct-scan'           => 'CT Scan',
                                                                        'mri'               => 'MRI',
                                                                        'cardiology'        => 'Cardiology',
                                                                        'cardiac-diagnostics' => 'Cardiac Diagnostics',
                                                                        'radiology'         => 'Radiology',
                                                                    ];
                                                                @endphp
                                                                @foreach($groupedInvestigations as $cat => $catInvestigations)
                                                                    <optgroup label="{{ $categoryLabels[$cat] ?? ucwords(str_replace('-', ' ', $cat)) }}">
                                                                        @foreach($catInvestigations as $investigation)
                                                                        <option value="{{ $investigation->id }}">
                                                                            {{ $investigation->name }} - {{ currency_symbol() }}{{ number_format($investigation->price, 0) }}
                                                                        </option>
                                                                        @endforeach
                                                                    </optgroup>
                                                                @endforeach
                                                            </select>
                                                        </td>
                                                        <td class="py-3 px-3 text-center">
                                                            <input type="number" name="tests[0][quantity]" value="1" min="1" max="10" class="w-full px-2 py-2 text-sm text-center border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" required>
                                                        </td>
                                                        <td class="py-3 px-3">
                                                            <select name="tests[0][priority]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors priority-select" required>
                                                                <option value="routine">Routine</option>
                                                                <option value="urgent">Urgent</option>
                                                                <option value="stat">STAT</option>
                                                            </select>
                                                        </td>
                                                        <td class="py-3 px-3">
                                                            <input type="text" name="tests[0][clinical_notes]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue focus:border-medical-blue transition-colors" placeholder="Optional notes...">
                                                        </td>
                                                        <td class="py-3 text-center">
                                                            <button type="button" onclick="removeTestRow(this)" class="text-red-500 hover:text-red-700 p-1 rounded transition-colors" style="display: none;" title="Remove test">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Test Count Display -->
                                        <div class="mt-3 pt-3 border-t border-gray-200">
                                            <div class="flex items-center justify-between text-xs text-gray-500">
                                                <span id="test-count">1 test selected</span>
                                                <span>Use "Add Test" to select multiple tests</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Single Submit Button -->
                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <button type="submit" class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-medical-blue text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-sm">
                                            <i class="fas fa-flask mr-2"></i>
                                            Order Investigations
                                        </button>
                                        <button type="button" onclick="resetForm()" class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-gray-200 transition-all duration-200">
                                            <i class="fas fa-undo mr-2"></i>
                                            Reset
                                        </button>
                                    </div>
                                </form>
                        @else
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-3"></i>
                                    @if($workflowData['care_team_labs_message'] ?? false)
                                        <p class="text-yellow-800">{{ $workflowData['care_team_labs_message'] }}</p>
                                    @else
                                        <p class="text-yellow-800">Doctor must be assigned to order investigations.</p>
                                    @endif
                                </div>
                            </div>
                        @endif

                    <!-- Display All Investigation Orders -->
                    <div class="mt-8">
                        @php
                            // Flatten all items across all orders for this visit
                            $allOrderItems = $visit->labOrders->flatMap(fn($order) => $order->items->map(fn($item) => $item->setRelation('order', $order)));
                            $pendingOrders   = $allOrderItems->whereIn('status', ['ordered', 'collected', 'testing']);
                            $completedOrders = $allOrderItems->whereIn('status', ['verified', 'reported']);
                        @endphp

                        <div class="flex justify-between items-center mb-4">
                            <h4 class="text-lg font-medium text-gray-800">Ordered Investigations</h4>
                            <span class="text-sm text-gray-500">{{ $allOrderItems->count() }} {{ Str::plural('investigation', $allOrderItems->count()) }}</span>
                        </div>
                        
                        <div class="space-y-6">
                            <!-- Pending Investigations -->
                            @if($pendingOrders->count() > 0)
                                <section aria-labelledby="pending-tests-heading">
                                    <div class="flex flex-col sm:flex-row sm:items-center mb-4 gap-2">
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 bg-yellow-500 rounded-full mr-3 animate-pulse"></div>
                                            <h5 id="pending-tests-heading" class="text-base font-semibold text-gray-900">Pending Results</h5>
                                        </div>
                                        <span class="px-2.5 py-1 text-xs bg-yellow-100 text-yellow-800 rounded-full font-medium">{{ $pendingOrders->count() }}</span>
                                    </div>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                                        @foreach($pendingOrders as $labOrder)
                                            <div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                                                <div class="flex justify-between items-start mb-3">
                                                    <div class="flex-1 min-w-0">
                                                        <h6 class="text-base font-semibold text-gray-900 truncate mb-2">{{ $labOrder->investigation->name }}</h6>
                                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                                            @php
                                                                $typeConfig = [
                                                                    'hematology'   => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'biochemistry' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'microbiology' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'immunology'   => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'pathology'    => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'molecular'    => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'x-ray'        => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-x-ray'],
                                                                    'ultrasound'   => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-x-ray'],
                                                                    'ct-scan'      => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-x-ray'],
                                                                    'mri'          => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-x-ray'],
                                                                    'radiology'    => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-x-ray'],
                                                                    'cardiology'   => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-heartbeat']
                                                                ];
                                                                $type = $labOrder->investigation->category ?? 'pathology';
                                                                $typeStyle = $typeConfig[$type] ?? $typeConfig['pathology'];
                                                            @endphp
                                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium {{ $typeStyle['bg'] }} {{ $typeStyle['text'] }}">
                                                                <i class="fas {{ $typeStyle['icon'] }} mr-1"></i>
                                                                {{ ucfirst($type) }}
                                                            </span>
                                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium
                                                                {{ $labOrder->priority === 'stat' ? 'bg-red-600 text-white' : 
                                                                   ($labOrder->priority === 'urgent' ? 'bg-orange-600 text-white' : 'bg-blue-600 text-white') }}">
                                                                {{ strtoupper($labOrder->priority) }}
                                                            </span>
                                                        </div>
                                                        <p class="text-xs text-gray-600">
                                                            <i class="fas fa-calendar-alt mr-1"></i>
                                                            {{ $labOrder->order->ordered_at->format('M d, h:i A') }}
                                                        </p>
                                                    </div>
                                                </div>
                                                @if($labOrder->clinical_notes)
                                                    <div class="bg-white rounded p-2 mb-3 text-xs text-gray-700">
                                                        <i class="fas fa-notes-medical text-yellow-600 mr-1"></i>
                                                        {{ Str::limit($labOrder->clinical_notes, 60) }}
                                                    </div>
                                                @endif
                                                
                                                @if($labOrder->test_location === 'indoor')
                                                    <div class="mt-3 pt-3 border-t border-yellow-200">
                                                        <a href="{{ route('lab-orders.results.create', $labOrder) }}" 
                                                           class="inline-flex items-center px-3 py-2 bg-medical-blue text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all w-full justify-center">
                                                            <i class="fas fa-plus mr-2"></i>
                                                            Add Result
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                            
                            <!-- Completed Investigations -->
                            @if($completedOrders->count() > 0)
                                <section aria-labelledby="completed-tests-heading" class="mt-6">
                                    <div class="flex flex-col sm:flex-row sm:items-center mb-4 gap-2">
                                        <div class="flex items-center">
                                            <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                            <h5 id="completed-tests-heading" class="text-base font-semibold text-gray-900">Completed Results</h5>
                                        </div>
                                        <span class="px-2.5 py-1 text-xs bg-green-100 text-green-800 rounded-full font-medium">{{ $completedOrders->count() }}</span>
                                    </div>
                                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                                        @foreach($completedOrders as $labOrder)
                                            <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4 shadow-sm hover:shadow-md transition-shadow duration-200">
                                                <div class="flex justify-between items-start mb-3">
                                                    <div class="flex-1 min-w-0">
                                                        <h6 class="text-base font-semibold text-gray-900 truncate mb-2">{{ $labOrder->investigation->name }}</h6>
                                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                                            @php
                                                                $typeConfig = [
                                                                    'hematology'   => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'biochemistry' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'microbiology' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'immunology'   => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'pathology'    => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'molecular'    => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'icon' => 'fa-microscope'],
                                                                    'x-ray'        => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-x-ray'],
                                                                    'ultrasound'   => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-x-ray'],
                                                                    'ct-scan'      => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-x-ray'],
                                                                    'mri'          => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-x-ray'],
                                                                    'radiology'    => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'icon' => 'fa-x-ray'],
                                                                    'cardiology'   => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'icon' => 'fa-heartbeat']
                                                                ];
                                                                $type = $labOrder->investigation->category ?? 'pathology';
                                                                $typeStyle = $typeConfig[$type] ?? $typeConfig['pathology'];
                                                            @endphp
                                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium {{ $typeStyle['bg'] }} {{ $typeStyle['text'] }}">
                                                                <i class="fas {{ $typeStyle['icon'] }} mr-1"></i>
                                                                {{ ucfirst($type) }}
                                                            </span>
                                                            <span class="inline-flex items-center px-2 py-1 text-xs rounded-full font-medium bg-green-200 text-green-900">
                                                                <i class="fas fa-check-circle mr-1"></i>
                                                                Reported
                                                            </span>
                                                        </div>
                                                        <p class="text-xs text-gray-600">
                                                            <i class="fas fa-calendar-alt mr-1"></i>
                                                            {{ $labOrder->order->ordered_at->format('M d, h:i A') }}
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                @if($labOrder->result)
                                                    <div class="mt-3 pt-3 border-t border-green-200 flex gap-2">
                                                        <a href="{{ route('lab-results.report', $labOrder->result) }}" 
                                                           class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-all">
                                                            <i class="fas fa-file-medical mr-2"></i>View
                                                        </a>
                                                        <a href="{{ route('lab-results.report', $labOrder->result) }}?print=1" 
                                                           target="_blank"
                                                           class="inline-flex items-center justify-center px-3 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-all">
                                                            <i class="fas fa-print"></i>
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                            
                            <!-- Empty State -->
                            @if($allOrderItems->count() === 0)
                                <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-lg p-8 text-center">
                                    <i class="fas fa-clipboard-list text-gray-400 text-3xl mb-3"></i>
                                    <p class="text-gray-500">No investigations ordered yet</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
