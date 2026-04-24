<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tax Name *</label>
            <input type="text" name="name" value="{{ old('name', $tax?->name) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="e.g. GST" required>
            @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Code *</label>
            <input type="text" name="code" value="{{ old('code', $tax?->code) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue uppercase" placeholder="e.g. GST" required>
            @error('code')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Rate (%) *</label>
            <input type="number" name="percentage" value="{{ old('percentage', $tax?->percentage) }}" step="0.01" min="0" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            @error('percentage')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
    </div>

    <div class="flex items-center gap-6">
        <label class="flex items-center cursor-pointer">
            <input type="checkbox" name="is_inclusive" value="1" {{ old('is_inclusive', $tax?->is_inclusive) ? 'checked' : '' }} class="h-4 w-4 text-medical-blue border-gray-300 rounded">
            <span class="ml-2 text-sm text-gray-700">Tax inclusive (already included in item price)</span>
        </label>
        <label class="flex items-center cursor-pointer">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $tax?->is_active ?? true) ? 'checked' : '' }} class="h-4 w-4 text-green-500 border-gray-300 rounded">
            <span class="ml-2 text-sm text-gray-700">Active</span>
        </label>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
        <input type="text" name="description" value="{{ old('description', $tax?->description) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Optional description">
    </div>

    {{-- Apply To --}}
    <div class="border-t border-gray-200 pt-6">
        <h4 class="text-sm font-semibold text-gray-800 mb-3">Apply This Tax To</h4>
        @php
            $existingMappings = old('mappings', $tax?->mappings?->map(fn($m) => ['applicable_on' => $m->applicable_on, 'applicable_value' => $m->applicable_value])->toArray() ?? []);
            $hasGlobal = collect($existingMappings)->contains(fn($m) => $m['applicable_on'] === 'all');
            $selectedBillTypes = collect($existingMappings)->where('applicable_on', 'bill_type')->pluck('applicable_value')->toArray();
            $selectedCategories = collect($existingMappings)->where('applicable_on', 'service_category')->pluck('applicable_value')->toArray();
            $hasCategoryAll = in_array('all', $selectedCategories);
        @endphp

        <div class="space-y-4">
            {{-- Global --}}
            <label class="flex items-center p-3 border rounded-lg cursor-pointer {{ $hasGlobal ? 'border-medical-blue bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}" id="global-label">
                <input type="checkbox" name="apply_global" value="1" {{ $hasGlobal ? 'checked' : '' }}
                       class="h-4 w-4 text-medical-blue border-gray-300 rounded" onchange="toggleGlobal(this)">
                <div class="ml-3">
                    <span class="text-sm font-medium text-gray-900">Apply to all bills</span>
                    <p class="text-xs text-gray-500">This tax will be applied to every bill regardless of type</p>
                </div>
            </label>

            {{-- Specific options (hidden when global is checked) --}}
            <div id="specific-options" class="{{ $hasGlobal ? 'hidden' : '' }}">
                {{-- Bill Types --}}
                <div class="mb-4">
                    <p class="text-sm font-medium text-gray-700 mb-2">Or select specific bill types:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['opd' => 'OPD', 'ipd' => 'IPD', 'emergency' => 'Emergency', 'investigation' => 'Investigation', 'pharmacy' => 'Pharmacy'] as $val => $label)
                        <label class="flex items-center px-3 py-2 border rounded-lg cursor-pointer text-sm {{ in_array($val, $selectedBillTypes) ? 'border-medical-blue bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="checkbox" name="bill_types[]" value="{{ $val }}" {{ in_array($val, $selectedBillTypes) ? 'checked' : '' }}
                                   class="h-3.5 w-3.5 text-medical-blue border-gray-300 rounded mr-2">
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Service Categories --}}
                <div>
                    <p class="text-sm font-medium text-gray-700 mb-2">Or select service categories:</p>
                    <div class="flex flex-wrap gap-2">
                        <label class="flex items-center px-3 py-2 border rounded-lg cursor-pointer text-sm {{ $hasCategoryAll ? 'border-medical-blue bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="checkbox" name="service_categories[]" value="all" {{ $hasCategoryAll ? 'checked' : '' }}
                                   class="h-3.5 w-3.5 text-medical-blue border-gray-300 rounded mr-2">
                            All Categories
                        </label>
                        @foreach(['consultation' => 'Consultation', 'procedure' => 'Procedure', 'investigation' => 'Investigation', 'medication' => 'Medication', 'other' => 'Other'] as $val => $label)
                        <label class="flex items-center px-3 py-2 border rounded-lg cursor-pointer text-sm {{ in_array($val, $selectedCategories) ? 'border-medical-blue bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
                            <input type="checkbox" name="service_categories[]" value="{{ $val }}" {{ in_array($val, $selectedCategories) ? 'checked' : '' }}
                                   class="h-3.5 w-3.5 text-medical-blue border-gray-300 rounded mr-2">
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @error('mappings')<p class="text-red-500 text-sm mt-1">At least one option must be selected.</p>@enderror
    </div>
</div>

<div class="flex justify-end space-x-3 mt-8 pt-6 border-t">
    <a href="{{ route('taxes.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
    <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700"><i class="fas fa-save mr-2"></i>{{ $tax ? 'Update' : 'Create' }} Tax</button>
</div>

<script>
function toggleGlobal(checkbox) {
    var specific = document.getElementById('specific-options');
    var label = document.getElementById('global-label');
    if (checkbox.checked) {
        specific.classList.add('hidden');
        label.classList.add('border-medical-blue', 'bg-blue-50');
        label.classList.remove('border-gray-200');
    } else {
        specific.classList.remove('hidden');
        label.classList.remove('border-medical-blue', 'bg-blue-50');
        label.classList.add('border-gray-200');
    }
}
</script>
