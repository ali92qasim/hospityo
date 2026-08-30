<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; }
        .page { position: relative; overflow: hidden; }
        .page-break { page-break-after: always; }
        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        .element { position: absolute; white-space: pre-wrap; }
    </style>
</head>
<body>
@foreach ($layout['pages'] as $page)
    <div
        class="page{{ ! $loop->last ? ' page-break' : '' }}"
        style="width: {{ $layout['width_mm'] }}mm; height: {{ $layout['height_mm'] }}mm;"
    >
        @if ($page['show_background'] && $layout['background_path'])
            <img
                class="background"
                src="file://{{ str_replace('\\', '/', $layout['background_path']) }}"
                alt=""
            >
        @endif

        @foreach ($page['elements'] as $element)
            <div
                class="element"
                style="left: {{ $element['x_mm'] }}mm;
                    top: {{ $element['y_mm'] }}mm;
                    font-size: {{ $element['font_size'] }}pt;
                    font-weight: {{ $element['font_weight'] }};
                    text-align: {{ $element['align'] }};"
            >{{ $element['text'] }}</div>
        @endforeach
    </div>
@endforeach
</body>
</html>
