@extends('settings.shell')

@inject('service', 'App\Services\PrescriptionPrintTemplateService')

@php
    $catalogLabels = \App\Support\PrescriptionPrintFieldCatalog::labels();
    $editorFields = $template->fields->mapWithKeys(fn ($field) => [
        $field->field_key => [
            'label' => $catalogLabels[$field->field_key],
            'x_mm' => $field->x_mm,
            'y_mm' => $field->y_mm,
            'font_size' => $field->font_size,
            'font_weight' => $field->font_weight,
            'align' => $field->align,
            'visible' => $field->visible,
        ],
    ]);
    $paperDimensions = $template->paper_size === 'Letter'
        ? ['width' => 215.9, 'height' => 279.4]
        : ['width' => 210, 'height' => 297];
    if ($template->orientation === 'landscape') {
        $paperDimensions = ['width' => $paperDimensions['height'], 'height' => $paperDimensions['width']];
    }
    $missingCoreFields = collect(\App\Support\PrescriptionPrintFieldCatalog::coreKeys())
        ->reject(fn ($key) => (bool) optional($template->fields->firstWhere('field_key', $key))->visible)
        ->map(fn ($key) => $catalogLabels[$key])
        ->values();
    $isComplete = $service->isComplete($template);
@endphp

@section('settings-section')
<div class="rounded-lg bg-white p-6 shadow">
    <h2 class="mb-1 text-lg font-semibold text-gray-800">Edit prescription print template</h2>
    <p class="mb-6 text-sm text-gray-500">Drag fields and resize the prescription region, then save your changes.</p>

    <form method="POST" action="{{ route('settings.prescription-print-templates.update', $template) }}" enctype="multipart/form-data">
        @include('settings.prescription-print-templates._form')
    </form>

    <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-gray-200 pt-5">
        @if($template->mode === 'overlay_physical')
            <a href="{{ route('settings.prescription-print-templates.calibration', $template) }}"
               class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Open calibration sheet
            </a>
        @endif

        @if(!$template->is_active)
            <form method="POST" action="{{ route('settings.prescription-print-templates.activate', $template) }}">
                @csrf
                <button type="submit" @disabled(!$isComplete)
                        class="rounded-lg px-4 py-2 text-sm font-medium {{ $isComplete ? 'bg-green-600 text-white hover:bg-green-700' : 'cursor-not-allowed bg-gray-200 text-gray-500' }}">
                    Activate template
                </button>
            </form>
        @else
            <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">Active template</span>
        @endif

        @if(!$isComplete)
            <p class="text-sm text-amber-700">
                Position and show these required fields before activation: {{ $missingCoreFields->join(', ') }}.
            </p>
        @endif
    </div>
</div>
@endsection

@vite(['resources/js/prescription-print-template-editor.js'])
