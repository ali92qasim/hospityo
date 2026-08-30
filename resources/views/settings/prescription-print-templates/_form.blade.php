@csrf
@if(isset($template))
    @method('PUT')
@endif

<div class="grid grid-cols-1 gap-5 md:grid-cols-2">
    <div>
        <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Name</label>
        <input id="name" name="name" type="text" required
               value="{{ old('name', $template->name ?? '') }}"
               class="w-full rounded-lg border-gray-300 focus:border-medical-blue focus:ring-medical-blue">
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="doctor_id" class="mb-1 block text-sm font-medium text-gray-700">Doctor</label>
        <select id="doctor_id" name="doctor_id"
                class="w-full rounded-lg border-gray-300 focus:border-medical-blue focus:ring-medical-blue">
            <option value="">Clinic default</option>
            @foreach($doctors as $doctor)
                <option value="{{ $doctor->id }}" @selected((string) old('doctor_id', $template->doctor_id ?? '') === (string) $doctor->id)>
                    {{ $doctor->name }}
                </option>
            @endforeach
        </select>
        @error('doctor_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="mode" class="mb-1 block text-sm font-medium text-gray-700">Mode</label>
        <select id="mode" name="mode" class="w-full rounded-lg border-gray-300">
            <option value="overlay_physical" @selected(old('mode', $template->mode ?? 'overlay_physical') === 'overlay_physical')>Overlay physical paper</option>
            <option value="digitized_background" @selected(old('mode', $template->mode ?? '') === 'digitized_background')>Digitized background</option>
        </select>
        @error('mode')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="background_image" class="mb-1 block text-sm font-medium text-gray-700">Background image</label>
        <input id="background_image" name="background_image" type="file" accept="image/*"
               class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <p class="mt-1 text-xs text-gray-500">Required for digitized backgrounds; optional for physical overlays.</p>
        @error('background_image')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="paper_size" class="mb-1 block text-sm font-medium text-gray-700">Paper size</label>
        <select id="paper_size" name="paper_size" class="w-full rounded-lg border-gray-300">
            @foreach(['A4', 'Letter'] as $paperSize)
                <option value="{{ $paperSize }}" @selected(old('paper_size', $template->paper_size ?? 'A4') === $paperSize)>{{ $paperSize }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="orientation" class="mb-1 block text-sm font-medium text-gray-700">Orientation</label>
        <select id="orientation" name="orientation" class="w-full rounded-lg border-gray-300">
            @foreach(['portrait' => 'Portrait', 'landscape' => 'Landscape'] as $value => $label)
                <option value="{{ $value }}" @selected(old('orientation', $template->orientation ?? 'portrait') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="rx_start_y" class="mb-1 block text-sm font-medium text-gray-700">Prescription start Y (mm)</label>
        <input id="rx_start_y" name="rx_start_y" type="number" step="0.01" min="0" required
               value="{{ old('rx_start_y', $template->rx_start_y ?? 80) }}"
               class="w-full rounded-lg border-gray-300">
    </div>

    <div>
        <label for="rx_row_height" class="mb-1 block text-sm font-medium text-gray-700">Prescription row height (mm)</label>
        <input id="rx_row_height" name="rx_row_height" type="number" step="0.01" min="0.01" required
               value="{{ old('rx_row_height', $template->rx_row_height ?? 8) }}"
               class="w-full rounded-lg border-gray-300">
    </div>

    <div>
        <label for="rx_max_rows" class="mb-1 block text-sm font-medium text-gray-700">Maximum prescription rows</label>
        <input id="rx_max_rows" name="rx_max_rows" type="number" min="1" required
               value="{{ old('rx_max_rows', $template->rx_max_rows ?? 12) }}"
               class="w-full rounded-lg border-gray-300">
    </div>

    <div>
        <label for="rx_overflow_policy" class="mb-1 block text-sm font-medium text-gray-700">Overflow policy</label>
        <select id="rx_overflow_policy" name="rx_overflow_policy" class="w-full rounded-lg border-gray-300">
            <option value="second_page_plain" @selected(old('rx_overflow_policy', $template->rx_overflow_policy ?? 'second_page_plain') === 'second_page_plain')>Continue on plain second page</option>
            <option value="shrink_font" @selected(old('rx_overflow_policy', $template->rx_overflow_policy ?? '') === 'shrink_font')>Shrink font</option>
            <option value="cap_with_note" @selected(old('rx_overflow_policy', $template->rx_overflow_policy ?? '') === 'cap_with_note')>Cap with continuation note</option>
        </select>
    </div>
</div>

@foreach(\App\Support\PrescriptionPrintFieldCatalog::keys() as $key)
    @php($field = $fields[$key] ?? [])
    <input type="hidden" name="fields[{{ $key }}][x_mm]" value="{{ old("fields.$key.x_mm", data_get($field, 'x_mm')) }}">
    <input type="hidden" name="fields[{{ $key }}][y_mm]" value="{{ old("fields.$key.y_mm", data_get($field, 'y_mm')) }}">
    <input type="hidden" name="fields[{{ $key }}][font_size]" value="{{ old("fields.$key.font_size", data_get($field, 'font_size')) }}">
    <input type="hidden" name="fields[{{ $key }}][font_weight]" value="{{ old("fields.$key.font_weight", data_get($field, 'font_weight')) }}">
    <input type="hidden" name="fields[{{ $key }}][align]" value="{{ old("fields.$key.align", data_get($field, 'align')) }}">
    <input type="hidden" name="fields[{{ $key }}][visible]" value="{{ old("fields.$key.visible", data_get($field, 'visible') ? 1 : 0) }}">
@endforeach

<div class="mt-6 flex items-center gap-3">
    <button type="submit" class="rounded-lg bg-medical-blue px-4 py-2 text-sm font-medium text-white hover:opacity-90">
        {{ isset($template) ? 'Save changes' : 'Create template' }}
    </button>
    <a href="{{ route('settings.prescription-print-templates.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Cancel</a>
</div>
