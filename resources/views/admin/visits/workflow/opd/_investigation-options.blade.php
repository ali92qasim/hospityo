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
