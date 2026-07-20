@php
    $itemType = ! empty($item?->investigation_id) ? 'investigation' : 'service';
    $lineTotal = ($item?->quantity ?? 1) * ($item?->unit_price ?? 0);
@endphp
<div class="bill-item border border-gray-200 rounded-lg p-4 mb-3">
    <div class="grid grid-cols-12 gap-3">
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-500 mb-1">Item Type</label>
            <select class="item-type-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="service" @selected($itemType === 'service')>Service</option>
                <option value="investigation" @selected($itemType === 'investigation')>Investigation</option>
            </select>
        </div>
        <div class="col-span-3 item-service-col {{ $itemType === 'investigation' ? 'hidden' : '' }}">
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
            <input type="hidden" name="items[{{ $index }}][investigation_id]" class="investigation-id-input" value="{{ $itemType === 'investigation' ? ($item?->investigation_id ?? '') : '' }}">
        </div>
        <div class="col-span-3 item-investigation-col {{ $itemType === 'service' ? 'hidden' : '' }}">
            <label class="block text-xs font-medium text-gray-500 mb-1">Investigation</label>
            <select class="investigation-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Select Investigation</option>
                @foreach($investigations->groupBy('category') as $category => $items)
                    <optgroup label="{{ ucwords(str_replace('-', ' ', $category)) }}">
                        @foreach($items as $inv)
                            <option value="{{ $inv->id }}"
                                    data-price="{{ $inv->price }}"
                                    data-name="{{ $inv->name }}"
                                    @selected($item?->investigation_id == $inv->id)>
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
            <label class="block text-xs font-medium text-gray-500 mb-1">Price ({{ currency_symbol() }})</label>
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
