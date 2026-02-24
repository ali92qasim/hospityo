{{-- 
    Prescription Instructions Selector Component
    
    Usage in prescription forms:
    @include('admin.prescription-instructions._selector', ['selectedInstructions' => $prescription->instructions ?? []])
--}}

<div class="mb-4">
    <label for="instruction_ids" class="block text-sm font-medium text-gray-700 mb-2">
        Prescription Instructions
    </label>
    <select 
        name="instruction_ids[]" 
        id="instruction_ids" 
        multiple
        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue"
        style="min-height: 120px;"
    >
        @foreach(\App\Models\PrescriptionInstruction::active()->orderBy('title')->get() as $instruction)
            <option 
                value="{{ $instruction->id }}"
                {{ in_array($instruction->id, old('instruction_ids', $selectedInstructions->pluck('id')->toArray() ?? [])) ? 'selected' : '' }}
            >
                {{ $instruction->title ? $instruction->title . ' - ' : '' }}{{ Str::limit($instruction->instruction, 60) }}
            </option>
        @endforeach
    </select>
    <p class="text-sm text-gray-500 mt-1">
        Hold Ctrl (Windows) or Cmd (Mac) to select multiple instructions
    </p>
</div>
