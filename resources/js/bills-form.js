import $ from 'jquery';
import select2 from 'select2';
select2(window, $);
import flatpickr from 'flatpickr';
import '../css/bills-form.css';

let itemIndex = 1;
let rawServiceOptions = '';

$(function () {
    // Capture raw service options before Select2 transforms them
    rawServiceOptions = $('.bill-item').first().find('.service-select').html();

    // Patient dropdown
    $('#patient_id').select2({ placeholder: 'Select Patient', allowClear: true, width: '100%' });

    // Bill type dropdown
    $('#bill_type').select2({ placeholder: 'Select Type', allowClear: true, width: '100%', minimumResultsForSearch: Infinity });

    // Init Select2 on first service row
    initSelect2OnRow($('.bill-item').first());

    // Flatpickr on bill date
    flatpickr('#bill_date', { dateFormat: 'Y-m-d', defaultDate: new Date(), allowInput: true });

    // Add item
    $('#addItem').on('click', function () {
        const row = $(`
            <div class="bill-item grid grid-cols-12 gap-3 mb-3">
                <div class="col-span-4">
                    <select name="items[${itemIndex}][service_id]" class="service-select w-full px-3 py-2 border border-gray-300 rounded-lg">
                        ${rawServiceOptions}
                    </select>
                </div>
                <div class="col-span-3">
                    <input type="text" name="items[${itemIndex}][description]" placeholder="Description" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                </div>
                <div class="col-span-2">
                    <input type="number" name="items[${itemIndex}][quantity]" placeholder="Qty" value="1" min="1" class="quantity w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                </div>
                <div class="col-span-2">
                    <input type="number" name="items[${itemIndex}][unit_price]" placeholder="Price" step="0.01" class="unit-price w-full px-3 py-2 border border-gray-300 rounded-lg" required>
                </div>
                <div class="col-span-1">
                    <button type="button" class="remove-item bg-red-500 text-white px-3 py-2 rounded-lg hover:bg-red-600">×</button>
                </div>
            </div>
        `);

        $('#billItems').append(row);
        row.find('.service-select').val('');
        initSelect2OnRow(row);
        itemIndex++;
    });

    // Remove item (delegated)
    $(document).on('click', '.remove-item', function () {
        if ($('.bill-item').length > 1) {
            $(this).closest('.bill-item').remove();
            updateTotal();
        }
    });

    // Service select auto-fill price + description
    $(document).on('change', '.service-select', function () {
        const option = $(this).find(':selected');
        const row = $(this).closest('.bill-item');
        if (option.data('price')) {
            row.find('.unit-price').val(option.data('price'));
            row.find('input[name*="[description]"]').val(option.text().split(' - ')[0].trim());
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
    } catch (e) {
        console.error('Select2 init error:', e);
    }
}

function updateTotal() {
    let subtotal = 0;
    $('.bill-item').each(function () {
        const qty = parseFloat($(this).find('.quantity').val()) || 0;
        const price = parseFloat($(this).find('.unit-price').val()) || 0;
        subtotal += qty * price;
    });
    const tax = parseFloat($('#tax_amount').val()) || 0;
    const discount = parseFloat($('#discount_amount').val()) || 0;
    $('#totalAmount').text('₨' + (subtotal + tax - discount).toFixed(2));
}
