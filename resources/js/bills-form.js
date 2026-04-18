import $ from 'jquery';
import select2 from 'select2';
select2(window, $);
import flatpickr from 'flatpickr';
import '../css/bills-form.css';

let itemIndex = 1;
let rawServiceOptions = '';
let rawInvestigationOptions = '';

$(function () {
    const $firstRow = $('.bill-item').first();
    rawServiceOptions = $firstRow.find('.service-select').html();
    rawInvestigationOptions = $firstRow.find('.investigation-select').html();

    // Patient & bill type Select2
    $('#patient_id').select2({ placeholder: 'Select Patient', allowClear: true, width: '100%' });
    $('#bill_type').select2({ placeholder: 'Select Type', allowClear: true, width: '100%', minimumResultsForSearch: Infinity });

    // Flatpickr on bill date
    flatpickr('#bill_date', { dateFormat: 'Y-m-d', defaultDate: new Date(), allowInput: true });

    // Init Select2 on first row
    initSelect2OnRow($firstRow);

    // Add item
    $('#addItem').on('click', function () {
        const row = $(`
            <div class="bill-item border border-gray-200 rounded-lg p-4 mb-3">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Item Type</label>
                        <select class="item-type-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="service">Service</option>
                            <option value="investigation">Investigation</option>
                        </select>
                    </div>
                    <div class="col-span-3 item-service-col">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Service</label>
                        <select name="items[${itemIndex}][service_id]" class="service-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                            ${rawServiceOptions}
                        </select>
                        <input type="hidden" name="items[${itemIndex}][investigation_id]" class="investigation-id-input" value="">
                    </div>
                    <div class="col-span-3 item-investigation-col hidden">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Investigation</label>
                        <select class="investigation-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                            ${rawInvestigationOptions}
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Description</label>
                        <input type="text" name="items[${itemIndex}][description]" placeholder="Description" class="description-input w-full px-2 py-2 border border-gray-300 rounded-lg text-sm" required>
                    </div>
                    <div class="col-span-1">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Qty</label>
                        <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" class="quantity w-full px-2 py-2 border border-gray-300 rounded-lg text-sm text-center" required>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Price</label>
                        <input type="number" name="items[${itemIndex}][unit_price]" step="0.01" class="unit-price w-full px-2 py-2 border border-gray-300 rounded-lg text-sm" required>
                    </div>
                    <div class="col-span-2 flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Total</label>
                            <span class="total-display block py-2 text-sm font-medium text-gray-700">0.00</span>
                        </div>
                        <button type="button" class="remove-item mb-1 p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `);

        $('#billItems').append(row);
        row.find('.service-select, .investigation-select').val('');
        initSelect2OnRow(row);
        itemIndex++;
    });

    // Remove item
    $(document).on('click', '.remove-item', function () {
        if ($('.bill-item').length > 1) {
            $(this).closest('.bill-item').remove();
            updateTotal();
        }
    });

    // Item type toggle — switch between service and investigation
    $(document).on('change', '.item-type-select', function () {
        const row = $(this).closest('.bill-item');
        const type = $(this).val();

        if (type === 'investigation') {
            row.find('.item-service-col').addClass('hidden');
            row.find('.item-investigation-col').removeClass('hidden');
            // Clear service fields
            row.find('.service-select').val('').trigger('change');
            row.find('select[name*="[service_id]"]').val('');
        } else {
            row.find('.item-investigation-col').addClass('hidden');
            row.find('.item-service-col').removeClass('hidden');
            // Clear investigation fields
            row.find('.investigation-select').val('').trigger('change');
            row.find('.investigation-id-input').val('');
        }
        // Clear price and description
        row.find('.unit-price').val('');
        row.find('.description-input').val('');
        row.find('.total-display').text('0.00');
        updateTotal();
    });

    // Service select → auto-fill price & description
    $(document).on('change', '.service-select', function () {
        const row = $(this).closest('.bill-item');
        const opt = $(this).find(':selected');
        if (opt.data('price')) {
            row.find('.unit-price').val(opt.data('price'));
            row.find('.description-input').val(opt.data('name') || opt.text().split(' - ')[0].trim());
        }
        // Clear investigation_id when service is selected
        row.find('.investigation-id-input').val('');
        updateTotal();
    });

    // Investigation select → auto-fill price, description, and hidden investigation_id
    $(document).on('change', '.investigation-select', function () {
        const row = $(this).closest('.bill-item');
        const opt = $(this).find(':selected');
        if (opt.val()) {
            row.find('.unit-price').val(opt.data('price'));
            row.find('.description-input').val(opt.data('name') || opt.text().split(' - ')[0].trim());
            row.find('.investigation-id-input').val(opt.val());
            // Clear service_id
            row.find('.service-select').val('');
        }
        updateTotal();
    });

    // Recalculate on input changes
    $(document).on('input change', '.quantity, .unit-price, #tax_amount, #discount_amount', function () {
        updateTotal();
    });
});

function initSelect2OnRow(row) {
    if (typeof $.fn.select2 !== 'function') return;
    try {
        row.find('.service-select').select2({ placeholder: 'Search service...', allowClear: true, width: '100%' });
        row.find('.investigation-select').select2({ placeholder: 'Search investigation...', allowClear: true, width: '100%' });
    } catch (e) {
        console.error('Select2 init error:', e);
    }
}

function updateTotal() {
    let subtotal = 0;
    $('.bill-item').each(function () {
        const qty = parseFloat($(this).find('.quantity').val()) || 0;
        const price = parseFloat($(this).find('.unit-price').val()) || 0;
        const lineTotal = qty * price;
        $(this).find('.total-display').text(lineTotal.toFixed(2));
        subtotal += lineTotal;
    });
    const tax = parseFloat($('#tax_amount').val()) || 0;
    const discount = parseFloat($('#discount_amount').val()) || 0;
    $('#totalAmount').text(document.querySelector('#totalAmount').textContent.charAt(0) + (subtotal + tax - discount).toFixed(2));
}
