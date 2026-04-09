import $ from 'jquery';
import select2 from 'select2';
select2(window, $);
import flatpickr from 'flatpickr';
import '../css/purchases-form.css';

let itemIndex = 1;
let rawMedicineOptions = '';

$(function () {
    // Capture raw medicine options before Select2 transforms them
    rawMedicineOptions = $('.item-row').first().find('.medicine-select').html();

    // Init Select2 on supplier
    $('#supplier-select').select2({
        placeholder: 'Search supplier...',
        allowClear: true,
        width: '100%',
    });

    // Init Select2 on first medicine row
    initSelect2OnRow($('.item-row').first());

    // Flatpickr on order date
    flatpickr('#order-date', {
        dateFormat: 'Y-m-d',
        defaultDate: new Date(),
        allowInput: true,
    });

    // Flatpickr on expected delivery
    flatpickr('#expected-delivery', {
        dateFormat: 'Y-m-d',
        allowInput: true,
    });

    // Add item row
    window.addItem = function () {
        const row = $(`
            <tr class="item-row">
                <td class="px-4 py-3">
                    <select name="items[${itemIndex}][medicine_id]" class="medicine-select w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
                        ${rawMedicineOptions}
                    </select>
                </td>
                <td class="px-4 py-3">
                    <input type="number" name="items[${itemIndex}][quantity]" min="1" class="w-full px-2 py-1 border border-gray-300 rounded text-sm quantity-input" required>
                </td>
                <td class="px-4 py-3">
                    <input type="number" name="items[${itemIndex}][unit_price]" step="0.01" min="0" class="w-full px-2 py-1 border border-gray-300 rounded text-sm price-input" required>
                </td>
                <td class="px-4 py-3">
                    <span class="total-display">0.00</span>
                </td>
                <td class="px-4 py-3 text-center">
                    <button type="button" class="remove-item-btn text-red-600 hover:text-red-800">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);

        $('#items-table').append(row);
        row.find('.medicine-select').val('');
        initSelect2OnRow(row);
        bindRowEvents(row);
        itemIndex++;
    };

    // Remove item (delegated)
    $(document).on('click', '.remove-item-btn', function () {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
        }
    });

    // Calculate total on input change (delegated)
    $(document).on('input change', '.quantity-input, .price-input', function () {
        const row = $(this).closest('tr');
        const qty = parseFloat(row.find('.quantity-input').val()) || 0;
        const price = parseFloat(row.find('.price-input').val()) || 0;
        row.find('.total-display').text((qty * price).toFixed(2));
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
    } catch (e) {
        console.error('Select2 init error:', e);
    }
}

function bindRowEvents(row) {
    // Events are delegated, nothing extra needed
}
