@php
    $catalog = $catalog ?? $kind ?? 'lab';
    $kind = $catalog;
    $itemField = $itemField ?? 'lab_test_id';
    $formAction = $formAction ?? route('visits.order-multiple-lab-tests', $visit);
    $formId = $catalog.'-tests-form';
    $rowsId = $catalog.'-test-rows';
    $countId = $catalog.'-test-count';
    $grouped = $kindInvestigations->groupBy('category');
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-6" data-catalog="{{ $catalog }}">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <i class="fas {{ $sectionIcon }} text-medical-blue mr-2"></i>
            <h5 class="font-semibold text-gray-800">{{ $sectionTitle }}</h5>
        </div>
        <span class="text-xs text-gray-500">One {{ $catalog }} order per submit</span>
    </div>

    <form action="{{ $formAction }}" method="POST" id="{{ $formId }}" data-catalog-form="{{ $catalog }}" data-item-field="{{ $itemField }}">
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
            </div>
        @endif

        <div class="bg-gray-50 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h6 class="text-sm font-medium text-gray-700 flex items-center">
                    <i class="fas {{ $sectionIcon }} text-gray-500 mr-2"></i>
                    Selection
                </h6>
                <button type="button" onclick="addTestRow('{{ $kind }}')" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-medical-blue bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 transition-colors">
                    <i class="fas fa-plus mr-1"></i>Add
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="text-xs font-semibold text-gray-600 uppercase tracking-wider border-b-2 border-gray-200">
                            <th class="text-left py-3 pr-4">{{ $catalog === 'lab' ? 'Lab test' : 'Imaging study' }}</th>
                            <th class="text-center py-3 px-3 w-20">Qty</th>
                            <th class="text-center py-3 px-3 w-32">Priority</th>
                            <th class="text-left py-3 px-3">Notes</th>
                            <th class="w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="{{ $rowsId }}" class="test-rows" data-kind="{{ $kind }}">
                        <tr class="test-row border-b border-gray-100">
                            <td class="py-3 pr-4">
                                <select name="tests[0][{{ $itemField }}]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md" required>
                                    <option value="">Select...</option>
                                    @foreach($grouped as $cat => $catInvestigations)
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
                                <input type="number" name="tests[0][quantity]" value="1" min="1" max="10" class="w-full px-2 py-2 text-sm text-center border border-gray-300 rounded-md" required>
                            </td>
                            <td class="py-3 px-3">
                                <select name="tests[0][priority]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md" required>
                                    <option value="routine">Routine</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="stat">STAT</option>
                                </select>
                            </td>
                            <td class="py-3 px-3">
                                <input type="text" name="tests[0][clinical_notes]" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md" placeholder="Optional notes...">
                            </td>
                            <td class="py-3 text-center">
                                <button type="button" onclick="removeTestRow(this, '{{ $kind }}')" class="text-red-500 hover:text-red-700 p-1 rounded" style="display: none;" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 pt-3 border-t border-gray-200 text-xs text-gray-500">
                <span id="{{ $countId }}">1 selected</span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-3">
            <button type="submit" class="flex-1 inline-flex items-center justify-center px-6 py-3 bg-medical-blue text-white font-medium rounded-lg hover:bg-blue-700">
                <i class="fas {{ $sectionIcon }} mr-2"></i>
                {{ $submitLabel }}
            </button>
            <button type="button" onclick="resetKindForm('{{ $kind }}')" class="px-6 py-3 border border-gray-300 text-gray-700 font-medium rounded-lg hover:bg-gray-50">
                Reset
            </button>
        </div>
    </form>
</div>
