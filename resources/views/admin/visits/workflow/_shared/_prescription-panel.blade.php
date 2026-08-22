@if($workflowData['can_prescribe'])
    <div class="space-y-6">
        @if($visit->prescriptions->count() > 0)
            <div>
                <h4 class="text-lg font-medium text-gray-800 mb-4">Existing Prescriptions</h4>
                @foreach($visit->prescriptions as $prescription)
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-3">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h5 class="font-medium text-green-800">Prescription #{{ $prescription->id }}</h5>
                                <p class="text-sm text-green-600">{{ $prescription->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full {{ $prescription->status === 'dispensed' ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($prescription->status) }}
                            </span>
                        </div>
                        <div class="space-y-2">
                            @foreach($prescription->items as $item)
                                <div class="flex justify-between items-center text-sm">
                                    <div>
                                        <span class="font-medium">{{ $item->medicine->name }}</span>
                                        <span class="text-gray-600">- {{ $item->quantity }} {{ $item->medicine->unit }}</span>
                                    </div>
                                    <span class="text-gray-500">{{ $item->dosage }}</span>
                                </div>
                                @if($item->instructions)
                                    <p class="text-xs text-gray-600 ml-2">{{ $item->instructions }}</p>
                                @endif
                            @endforeach
                        </div>
                        @if($prescription->notes)
                            <div class="mt-3 pt-3 border-t border-green-200">
                                <p class="text-sm text-green-700"><strong>Notes:</strong> {{ $prescription->notes }}</p>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h4 class="text-lg font-medium text-gray-800 mb-4">Create New Prescription</h4>
            <form action="{{ route('visits.prescription', $visit) }}" method="POST" id="prescription-form" data-save-tab="prescription">
                @csrf
                @if($workflowData['show_order_doctor_picker'] ?? false)
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prescribing Doctor (from care team)</label>
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
                <div id="prescription-items">
                    <div class="prescription-item border border-gray-200 rounded-lg p-3 mb-3">
                        <div class="flex items-start gap-3">
                            <div class="flex-[2] min-w-0">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Medicine</label>
                                <select name="medicines[0][medicine_id]" class="medicine-select w-full" required>
                                    <option value="">Select Medicine</option>
                                    @foreach($medicines ?? [] as $medicine)
                                        <option value="{{ $medicine->id }}">{{ $medicine->name }}{{ $medicine->strength ? ' ('.$medicine->strength.')' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex-[2] min-w-0">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Instruction</label>
                                <select name="medicines[0][instruction_id]" class="instruction-select w-full">
                                    <option value="">Select Instruction</option>
                                    @php
                                        $groupedInstructions = \App\Models\PrescriptionInstruction::active()
                                            ->orderBy('category')
                                            ->orderBy('instruction')
                                            ->get()
                                            ->groupBy('category');
                                        $categoryLabels = [
                                            'frequency' => 'تعدد (Frequency)',
                                            'meal' => 'کھانا (Meal)',
                                            'time' => 'وقت (Time)',
                                            'duration' => 'مدت (Duration)',
                                            'conditional' => 'شرطی (Conditional)',
                                            'injection' => 'انجیکشن (Injection)',
                                        ];
                                    @endphp
                                    @foreach(['frequency', 'meal', 'time', 'duration', 'conditional', 'injection'] as $category)
                                        @if($groupedInstructions->has($category))
                                            <optgroup label="{{ $categoryLabels[$category] }}">
                                                @foreach($groupedInstructions[$category] as $instruction)
                                                    <option value="{{ $instruction->id }}">{{ $instruction->instruction }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    @endforeach
                                    @if($groupedInstructions->has(''))
                                        <optgroup label="دیگر (Other)">
                                            @foreach($groupedInstructions[''] as $instruction)
                                                <option value="{{ $instruction->id }}">{{ $instruction->instruction }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                            </div>
                            <div class="w-20 flex-shrink-0">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Qty</label>
                                <input type="number" name="medicines[0][quantity]" value="1" min="1" max="999"
                                       class="w-full px-2 py-2 text-sm text-center border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                            </div>
                            <div class="pt-5 flex-shrink-0">
                                <button type="button" class="remove-item-btn p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Remove">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" onclick="addPrescriptionItem()" class="mb-4 text-medical-blue hover:text-blue-700">
                    <i class="fas fa-plus mr-1"></i>Add Another Medicine
                </button>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Where will the patient get these medicines?</label>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="fulfillment_type" value="in_house" class="text-medical-blue focus:ring-medical-blue" checked>
                            In-house pharmacy
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" name="fulfillment_type" value="external" class="text-medical-blue focus:ring-medical-blue">
                            External pharmacy
                        </label>
                    </div>
                    @error('fulfillment_type')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Prescription Notes</label>
                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Additional notes or instructions..."></textarea>
                </div>

                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                    <i class="fas fa-prescription mr-2"></i>Create Prescription
                </button>
            </form>
        </div>
    </div>
@else
    <div class="text-center py-8">
        <i class="fas fa-user-md text-4xl text-gray-300 mb-4"></i>
        @if($workflowData['care_team_prescribe_message'] ?? false)
            <p class="text-gray-500">{{ $workflowData['care_team_prescribe_message'] }}</p>
        @else
            <p class="text-gray-500">Please assign a doctor first to create prescriptions.</p>
        @endif
    </div>
@endif
