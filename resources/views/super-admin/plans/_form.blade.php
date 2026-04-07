<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Plan Name *</label>
        <input type="text" name="name" value="{{ old('name', $plan->name ?? '') }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
        <input type="text" name="slug" value="{{ old('slug', $plan->slug ?? '') }}" placeholder="Auto-generated if blank"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
        @error('slug')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('description', $plan->description ?? '') }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Price (PKR) *</label>
        <input type="number" name="price" value="{{ old('price', $plan->price ?? 0) }}" min="0" step="0.01"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle *</label>
        <select name="billing_cycle" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
            @foreach(['monthly' => 'Monthly', 'yearly' => 'Yearly', 'lifetime' => 'Lifetime'] as $val => $label)
            <option value="{{ $val }}" {{ old('billing_cycle', $plan->billing_cycle ?? 'monthly') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" min="0"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
    </div>
    <div class="flex items-end">
        <label class="flex items-center">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-medical-blue focus:ring-medical-blue">
            <span class="ml-2 text-sm text-gray-700">Active</span>
        </label>
    </div>
</div>

<hr class="my-6 border-gray-200">

{{-- Limits --}}
<h3 class="text-sm font-semibold text-gray-700 mb-3">Plan Limits</h3>
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div>
        <label class="block text-xs text-gray-500 mb-1">Max Users</label>
        <input type="number" name="max_users" value="{{ old('max_users', $plan->getLimit('max_users') ?? '') }}" min="1" placeholder="Unlimited"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Max Patients</label>
        <input type="number" name="max_patients" value="{{ old('max_patients', $plan->getLimit('max_patients') ?? '') }}" min="1" placeholder="Unlimited"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
    </div>
    <div>
        <label class="block text-xs text-gray-500 mb-1">Max Doctors</label>
        <input type="number" name="max_doctors" value="{{ old('max_doctors', $plan->getLimit('max_doctors') ?? '') }}" min="1" placeholder="Unlimited"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
    </div>
</div>

<hr class="my-6 border-gray-200">

{{-- Modules --}}
<h3 class="text-sm font-semibold text-gray-700 mb-3">Included Modules *</h3>
@error('modules')<p class="mb-2 text-sm text-red-600">{{ $message }}</p>@enderror
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
    @php $selectedModules = old('modules', $plan->modules ?? []); @endphp
    @foreach($modules as $slug => $def)
    <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-medical-blue/30 transition-colors {{ in_array($slug, $selectedModules) ? 'border-medical-blue bg-blue-50' : 'border-gray-200' }}">
        <input type="checkbox" name="modules[]" value="{{ $slug }}"
               class="rounded border-gray-300 text-medical-blue focus:ring-medical-blue mr-3"
               {{ in_array($slug, $selectedModules) ? 'checked' : '' }}>
        <div>
            <span class="text-sm font-medium text-gray-800">{{ $def['name'] }}</span>
        </div>
    </label>
    @endforeach
</div>
