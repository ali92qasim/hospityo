<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; }
        .page { position: relative; overflow: hidden; }
        .tick { position: absolute; color: #222; font-size: 6pt; }
        .top-tick { top: 0; height: 4mm; border-left: 0.2mm solid #222; padding-left: 1mm; }
        .left-tick { left: 0; width: 4mm; border-top: 0.2mm solid #222; padding-top: 1mm; }
        .crosshair-horizontal {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 20mm;
            margin-left: -10mm;
            border-top: 0.2mm solid #222;
        }
        .crosshair-vertical {
            position: absolute;
            left: 50%;
            top: 50%;
            height: 20mm;
            margin-top: -10mm;
            border-left: 0.2mm solid #222;
        }
        .element { position: absolute; white-space: pre-wrap; }
    </style>
</head>
<body>
<div
    class="page"
    style="width: {{ $layout['width_mm'] }}mm; height: {{ $layout['height_mm'] }}mm;"
>
    @for ($x = 10; $x < $layout['width_mm']; $x += 10)
        <div class="tick top-tick" style="left: {{ $x }}mm;">{{ $x }}</div>
    @endfor

    @for ($y = 10; $y < $layout['height_mm']; $y += 10)
        <div class="tick left-tick" style="top: {{ $y }}mm;">{{ $y }}</div>
    @endfor

    <div class="crosshair-horizontal"></div>
    <div class="crosshair-vertical"></div>

    @foreach ($layout['pages'][0]['elements'] as $element)
        @if (str_starts_with($element['text'], '+ '))
            <div
                class="element"
                style="left: {{ $element['x_mm'] }}mm;
                    top: {{ $element['y_mm'] }}mm;
                    font-size: {{ $element['font_size'] }}pt;"
            >{{ $element['text'] }}</div>
        @endif
    @endforeach
</div>
</body>
</html>
