@extends('settings.shell')

@section('settings-section')
<div class="rounded-lg bg-white p-6 shadow">
    <h2 class="mb-1 text-lg font-semibold text-gray-800">Edit prescription print template</h2>
    <p class="mb-6 text-sm text-gray-500">Existing field positions are preserved for the visual editor.</p>

    <form method="POST" action="{{ route('settings.prescription-print-templates.update', $template) }}" enctype="multipart/form-data">
        @include('settings.prescription-print-templates._form')
    </form>
</div>
@endsection
