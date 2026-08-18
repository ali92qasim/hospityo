import $ from 'jquery';
import select2 from 'select2';

select2(window, $);

let itemIndex = 1;
let rawMedicineOptions = '';
let rawInstructionOptions = '';
const currencySymbol = document.getElementById('prescription-form')?.dataset?.currencySymbol ?? '';

function updateMedicinePriceHint(select) {
    const item = select.closest('.prescription-item');
    if (!item) return;

    const hint = item.querySelector('.medicine-price-hint');
    if (!hint) return;

    const option = select.options[select.selectedIndex];
    const price = option?.dataset?.price;
    const abbrev = option?.dataset?.unitAbbrev || '';

    if (!select.value || !price || parseFloat(price) <= 0) {
        hint.classList.add('hidden');
        hint.textContent = '';
        return;
    }

    hint.textContent = `Est. ${currencySymbol} ${parseFloat(price).toFixed(2)} / ${abbrev}`;
    hint.classList.remove('hidden');
}

$(function () {
    // Capture raw options BEFORE Select2 transforms them
    rawMedicineOptions = $('.prescription-item').first().find('.medicine-select').html();
    rawInstructionOptions = $('.prescription-item').first().find('.instruction-select').html();

    // Init Select2 on the first row
    initSelect2OnRow($('.prescription-item').first());

    // Add row
    window.addPrescriptionItem = function () {
        const row = $(`
            <div class="prescription-item border border-gray-200 rounded-lg p-3 mb-3">
                <div class="flex items-start gap-3">
                    <div class="flex-[2] min-w-0">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Medicine</label>
                        <select name="medicines[${itemIndex}][medicine_id]" class="medicine-select w-full" required>
                            ${rawMedicineOptions}
                        </select>
                        <p class="medicine-price-hint text-xs text-gray-500 mt-1 hidden"></p>
                    </div>
                    <div class="flex-[2] min-w-0">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Instruction</label>
                        <select name="medicines[${itemIndex}][instruction_id]" class="instruction-select w-full">
                            ${rawInstructionOptions}
                        </select>
                    </div>
                    <div class="w-20 flex-shrink-0">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Qty</label>
                        <input type="number" name="medicines[${itemIndex}][quantity]" value="1" min="1" max="999"
                               class="w-full px-2 py-2 text-sm text-center border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                    </div>
                    <div class="pt-5 flex-shrink-0">
                        <button type="button" class="remove-item-btn p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);

        $('#prescription-items').append(row);

        // Reset the selects to blank then init Select2
        row.find('.medicine-select').val('');
        row.find('.instruction-select').val('');
        initSelect2OnRow(row);

        itemIndex++;
    };

    // Remove row (delegated)
    $(document).on('click', '.remove-item-btn', function () {
        if ($('.prescription-item').length > 1) {
            $(this).closest('.prescription-item').remove();
        }
    });

    $(document).on('change', '.medicine-select', function () {
        updateMedicinePriceHint(this);
    });
});

function initSelect2OnRow(row) {
    if (typeof $.fn.select2 !== 'function') return;

    try {
        row.find('.medicine-select').select2({
            placeholder: 'Search medicine...',
            allowClear: true,
            width: '100%',
        });

        row.find('.instruction-select').select2({
            placeholder: 'Select instruction',
            allowClear: true,
            width: '100%',
        });
    } catch (e) {
        console.error('Select2 init error:', e);
    }
}
