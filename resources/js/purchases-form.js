import $ from 'jquery';
import select2 from 'select2';
select2(window, $);
import flatpickr from 'flatpickr';
import '../css/purchases-form.css';

let itemIndex = 1;
let rawMedicineOptions = '';
const currencySymbol = document.querySelector('.price-header')?.textContent.match(/\(([^)]+)\)/)?.[1] ?? '';

function getUnitsForMedicine(medicineId) {
    const medicine = window._purchaseMedicineUnits?.[medicineId];
    if (!medicine) return [];

    const baseUnitId = medicine.base_unit_id;
    return (window._allUnits ?? []).filter(
        (u) => u.base_unit_id === baseUnitId || u.id === baseUnitId
    );
}

function populateUnitSelect($select, medicineId) {
    const units = getUnitsForMedicine(medicineId);
    $select.empty().append('<option value="">Select unit</option>');

    units.forEach((u) => {
        $select.append(
            $('<option></option>').val(u.id).text(`${u.name} (${u.abbreviation})`).attr('data-abbrev', u.abbreviation)
        );
    });

    $select.val('');
}

function updatePriceHeader($row) {
    const abbrev = $row.find('.unit-select option:selected').data('abbrev') || '';
    const label = abbrev ? `Price per ${abbrev} (${currencySymbol})` : `Price (${currencySymbol})`;
    $('.price-header').text(label);
}

function onMedicineChange($row) {
    const medicineId = $row.find('.medicine-select').val();
    populateUnitSelect($row.find('.unit-select'), medicineId);
    updatePriceHeader($row);
}

function onUnitChange($row) {
    updatePriceHeader($row);
}

$(function () {
    rawMedicineOptions = $('.item-row').first().find('.medicine-select').html();

    $('#supplier-select').select2({
        placeholder: 'Search supplier...',
        allowClear: true,
        width: '100%',
    });

    initSelect2OnRow($('.item-row').first());

    flatpickr('#order-date', {
        dateFormat: 'Y-m-d',
        defaultDate: new Date(),
        allowInput: true,
    });

    flatpickr('#expected-delivery', {
        dateFormat: 'Y-m-d',
        allowInput: true,
    });

    window.addItem = function () {
        const row = $(`
            <tr class="item-row">
                <td class="px-4 py-3">
                    <select name="items[${itemIndex}][medicine_id]" class="medicine-select w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
                        ${rawMedicineOptions}
                    </select>
                </td>
                <td class="px-4 py-3">
                    <select name="items[${itemIndex}][unit_id]" class="unit-select w-full px-2 py-1 border border-gray-300 rounded text-sm" required>
                        <option value="">Select unit</option>
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

    $(document).on('click', '.remove-item-btn', function () {
        if ($('.item-row').length > 1) {
            $(this).closest('tr').remove();
        }
    });

    $(document).on('change', '.medicine-select', function () {
        onMedicineChange($(this).closest('tr'));
    });

    $(document).on('change', '.unit-select', function () {
        onUnitChange($(this).closest('tr'));
    });

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
    // Events are delegated
}
