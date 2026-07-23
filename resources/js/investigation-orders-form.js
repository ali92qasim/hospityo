import $ from 'jquery';
import select2 from 'select2';
select2(window, $);
import '../css/investigation-orders-form.css';

let rowIndex = 1;
let rawInvestigationOptions = '';

$(function () {
    const $itemsBody = $('#items-body');

    rowIndex = parseInt($itemsBody.data('row-index'), 10) || $('.item-row').length;

    rawInvestigationOptions = $('.item-row').first().find('.investigation-select').html();

    initPatientDoctorSelect2();

    $('.item-row').each(function () {
        initSelect2OnRow($(this));
    });

    $('.investigation-select').each(function () {
        checkDuplicate(this);
    });

    $('#add-investigation-row').on('click', addRow);

    $(document).on('click', '.remove-row-btn', function () {
        removeRow($(this));
    });

    $('#order-form').on('submit', function (e) {
        if (hasDuplicates()) {
            e.preventDefault();
            alert('Please remove duplicate investigations before submitting.');
        }
    });

    syncRemoveButtons();
});

function initPatientDoctorSelect2() {
    if (typeof $.fn.select2 !== 'function') {
        return;
    }

    $('#patient_id').select2({
        placeholder: 'Search patient by name or number...',
        allowClear: true,
        width: '100%',
    });

    $('#doctor_id').select2({
        placeholder: 'Search doctor by name or specialization...',
        allowClear: true,
        width: '100%',
    });
}

function initSelect2OnRow(row) {
    const $select = row.find('.investigation-select');

    if (!$select.length || typeof $.fn.select2 !== 'function') {
        return;
    }

    try {
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.select2({
            placeholder: 'Search investigation...',
            allowClear: true,
            width: '100%',
        });

        $select.off('change.investigationOrder select2:select.investigationOrder select2:clear.investigationOrder');
        $select.on('change.investigationOrder select2:select.investigationOrder select2:clear.investigationOrder', function () {
            checkDuplicate(this);
        });
    } catch (e) {
        console.error('Select2 init error:', e);
    }
}

function getSelectedIds(excludeSelect) {
    return $('.investigation-select')
        .not(excludeSelect)
        .map(function () {
            return $(this).val();
        })
        .get()
        .filter((value) => value !== '' && value != null);
}

function checkDuplicate(select) {
    const $select = $(select);
    const td = $select.closest('td')[0];
    let warning = td.querySelector('.dup-warning');

    if (!warning) {
        warning = document.createElement('p');
        warning.className = 'dup-warning text-xs text-red-600 mt-1';
        warning.textContent = 'Already added. Remove the duplicate row.';
        td.appendChild(warning);
    }

    const value = $select.val();
    const isDup = value !== '' && value != null && getSelectedIds(select).includes(String(value));
    warning.style.display = isDup ? 'block' : 'none';

    const $selection = $select.next('.select2-container').find('.select2-selection');
    if ($selection.length) {
        $selection.toggleClass('select2-selection--duplicate-error', isDup);
    } else {
        select.classList.toggle('border-red-400', isDup);
    }
}

function hasDuplicates() {
    const ids = $('.investigation-select')
        .map(function () {
            return $(this).val();
        })
        .get()
        .filter((value) => value !== '' && value != null);

    return ids.length !== new Set(ids).size;
}

function addRow() {
    const row = $(`
        <tr class="item-row border-t border-gray-100">
            <td class="px-4 py-2">
                <select name="items[${rowIndex}][investigation_id]" class="investigation-select w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" required>
                    ${rawInvestigationOptions}
                </select>
            </td>
            <td class="px-4 py-2">
                <input type="number" name="items[${rowIndex}][quantity]" value="1" min="1" max="99" class="w-full px-2 py-1.5 text-center border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" required>
            </td>
            <td class="px-4 py-2">
                <select name="items[${rowIndex}][priority]" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" required>
                    <option value="routine" selected>Routine</option>
                    <option value="urgent">Urgent</option>
                    <option value="stat">STAT</option>
                </select>
            </td>
            <td class="px-4 py-2">
                <select name="items[${rowIndex}][test_location]" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" required>
                    <option value="outdoor" selected>Outdoor</option>
                    <option value="indoor">Indoor</option>
                </select>
            </td>
            <td class="px-4 py-2">
                <input type="text" name="items[${rowIndex}][clinical_notes]" class="w-full px-2 py-1.5 border border-gray-300 rounded-md focus:ring-2 focus:ring-medical-blue text-sm" placeholder="Optional notes...">
            </td>
            <td class="px-4 py-2 text-center">
                <button type="button" class="text-red-400 hover:text-red-600 transition-colors remove-row-btn remove-btn" title="Remove">
                    <i class="fas fa-times"></i>
                </button>
            </td>
        </tr>
    `);

    $('#items-body').append(row);
    row.find('.investigation-select').val('');
    initSelect2OnRow(row);
    syncRemoveButtons();
    rowIndex++;
}

function removeRow($btn) {
    const $row = $btn.closest('tr');
    const $select = $row.find('.investigation-select');

    if ($select.hasClass('select2-hidden-accessible')) {
        try {
            $select.select2('destroy');
        } catch (e) {
            console.error('Select2 destroy error:', e);
        }
    }

    $row.remove();
    syncRemoveButtons();

    $('.investigation-select').each(function () {
        checkDuplicate(this);
    });
}

function syncRemoveButtons() {
    const rows = $('.item-row');

    rows.each(function () {
        const $btn = $(this).find('.remove-btn');
        if ($btn.length) {
            $btn.css('display', rows.length > 1 ? 'inline' : 'none');
        }
    });
}
