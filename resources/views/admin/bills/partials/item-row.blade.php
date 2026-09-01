@php
    $itemType = ! empty($item?->lab_test_id) ? 'lab' : (! empty($item?->imaging_study_id) ? 'imaging' : 'service');
    $lineTotal = ($item?->quantity ?? 1) * ($item?->unit_price ?? 0);
@endphp
<div class="bill-item border border-gray-200 rounded-lg p-4 mb-3">
    <div class="grid grid-cols-12 gap-3">
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-500 mb-1">Item Type</label>
            <select class="item-type-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="service" @selected($itemType === 'service')>Service</option>
                @if($canLabBillItems ?? true)
                    <option value="lab" @selected($itemType === 'lab')>Lab test</option>
                @endif
                @if($canImagingBillItems ?? true)
                    <option value="imaging" @selected($itemType === 'imaging')>Imaging study</option>
                @endif
            </select>
        </div>
        <div class="col-span-3 item-service-col {{ $itemType !== 'service' ? 'hidden' : '' }}">
            <label class="block text-xs font-medium text-gray-500 mb-1">Service</label>
            <select name="items[{{ $index }}][service_id]" class="service-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Select Service</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}"
                            data-price="{{ $service->price }}"
                            data-name="{{ $service->name }}"
                            @selected($item?->service_id == $service->id)>
                        {{ $service->name }} - {{ currency_symbol() }}{{ number_format($service->price, 0) }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" name="items[{{ $index }}][lab_test_id]" class="lab-test-id-input" value="{{ $itemType === 'lab' ? ($item?->lab_test_id ?? '') : '' }}">
            <input type="hidden" name="items[{{ $index }}][imaging_study_id]" class="imaging-study-id-input" value="{{ $itemType === 'imaging' ? ($item?->imaging_study_id ?? '') : '' }}">
        </div>
        <div class="col-span-3 item-lab-col {{ $itemType === 'lab' ? '' : 'hidden' }}">
            <label class="block text-xs font-medium text-gray-500 mb-1">Lab test</label>
            <select class="lab-test-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Select lab test</option>
                @foreach(($labTests ?? collect())->groupBy('category') as $category => $items)
                    <optgroup label="{{ ucwords(str_replace('-', ' ', $category)) }}">
                        @foreach($items as $inv)
                            <option value="{{ $inv->id }}" data-price="{{ $inv->price }}" data-name="{{ $inv->name }}" @selected($item?->lab_test_id == $inv->id)>
                                {{ $inv->name }} - {{ currency_symbol() }}{{ number_format($inv->price, 0) }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="col-span-3 item-imaging-col {{ $itemType === 'imaging' ? '' : 'hidden' }}">
            <label class="block text-xs font-medium text-gray-500 mb-1">Imaging study</label>
            <select class="imaging-study-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Select imaging study</option>
                @foreach(($imagingStudies ?? collect())->groupBy('category') as $category => $items)
                    <optgroup label="{{ ucwords(str_replace('-', ' ', $category)) }}">
                        @foreach($items as $inv)
                            <option value="{{ $inv->id }}" data-price="{{ $inv->price }}" data-name="{{ $inv->name }}" @selected($item?->imaging_study_id == $inv->id)>
                                {{ $inv->name }} - {{ currency_symbol() }}{{ number_format($inv->price, 0) }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
            <input type="text" name="items[{{ $index }}][description]" value="{{ $item?->description ?? '' }}"
                   placeholder="Description" class="description-input w-full px-2 py-2 border border-gray-300 rounded-lg text-sm" required>
        </div>
        <div class="col-span-1">
            <label class="block text-xs font-medium text-gray-500 mb-1">Qty</label>
            <input type="number" name="items[{{ $index }}][quantity]" value="{{ $item?->quantity ?? 1 }}" min="1"
                   class="quantity w-full px-2 py-2 border border-gray-300 rounded-lg text-sm text-center" required>
        </div>
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-700 mb-1">Price ({{ currency_symbol() }})</label>
            <input type="number" name="items[{{ $index }}][unit_price]" value="{{ $item?->unit_price ?? '' }}" step="0.01"
                   class="unit-price w-full px-2 py-2 border border-gray-300 rounded-lg text-sm" required>
        </div>
        <div class="col-span-2 flex items-end gap-2">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">Total</label>
                <span class="total-display block py-2 text-sm font-medium text-gray-700">{{ number_format($lineTotal, 2) }}</span>
            </div>
            <button type="button" class="remove-item mb-1 p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Remove">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>
