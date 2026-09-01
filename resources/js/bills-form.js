import $ from 'jquery';
import select2 from 'select2';
select2(window, $);
import flatpickr from 'flatpickr';
import '../css/bills-form.css';

let itemIndex = window._billItemCount ?? 1;
let rawServiceOptions = '';
let rawLabTestOptions = '';
let rawImagingStudyOptions = '';
let rawItemTypeOptions = '';
const currencySymbol = window._currencySymbol || '';
const paidAmount = parseFloat(window._billPaidAmount) || 0;

$(function () {
    const $firstRow = $('.bill-item').first();
    rawServiceOptions = $firstRow.find('.service-select').html();
    rawLabTestOptions = $firstRow.find('.lab-test-select').html();
    rawImagingStudyOptions = $firstRow.find('.imaging-study-select').html();
    rawItemTypeOptions = $firstRow.find('.item-type-select').html();

    // Patient & bill type Select2
    $('#patient_id').select2({ placeholder: 'Select Patient', allowClear: true, width: '100%' });
    $('#bill_type').select2({ placeholder: 'Select Type', allowClear: true, width: '100%', minimumResultsForSearch: Infinity });

    // Flatpickr on bill date
    flatpickr('#bill_date', {
        dateFormat: 'Y-m-d',
        defaultDate: window._billDate || new Date(),
        allowInput: true,
    });

    // Init Select2 on all existing rows
    $('.bill-item').each(function () {
        initSelect2OnRow($(this));
    });

    // Add item
    $('#addItem').on('click', function () {
        const row = $(`
            <div class="bill-item border border-gray-200 rounded-lg p-4 mb-3">
                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Item Type</label>
                        <select class="item-type-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                            ${rawItemTypeOptions}
                        </select>
                    </div>
                    <div class="col-span-3 item-service-col">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Service</label>
                        <select name="items[${itemIndex}][service_id]" class="service-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                            ${rawServiceOptions}
                        </select>
                        <input type="hidden" name="items[${itemIndex}][lab_test_id]" class="lab-test-id-input" value="">
                        <input type="hidden" name="items[${itemIndex}][imaging_study_id]" class="imaging-study-id-input" value="">
                    </div>
                    <div class="col-span-3 item-lab-col hidden">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Lab test</label>
                        <select class="lab-test-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                            ${rawLabTestOptions}
                        </select>
                    </div>
                    <div class="col-span-3 item-imaging-col hidden">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Imaging study</label>
                        <select class="imaging-study-select w-full px-2 py-2 border border-gray-300 rounded-lg text-sm">
                            ${rawImagingStudyOptions}
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
        row.find('.service-select, .lab-test-select, .imaging-study-select').val('');
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

    // Item type toggle
    $(document).on('change', '.item-type-select', function () {
        const row = $(this).closest('.bill-item');
        const type = $(this).val();

        row.find('.item-service-col, .item-lab-col, .item-imaging-col').addClass('hidden');
        if (type === 'lab') {
            row.find('.item-lab-col').removeClass('hidden');
        } else if (type === 'imaging') {
            row.find('.item-imaging-col').removeClass('hidden');
        } else {
            row.find('.item-service-col').removeClass('hidden');
        }

        row.find('.service-select, .lab-test-select, .imaging-study-select').val('').trigger('change');
        row.find('select[name*="[service_id]"]').val('');
        row.find('.lab-test-id-input, .imaging-study-id-input').val('');
        row.find('.unit-price').val('');
        row.find('.description-input').val('');
        row.find('.total-display').text('0.00');
        updateTotal();
    });

    $(document).on('change', '.service-select', function () {
        const row = $(this).closest('.bill-item');
        const opt = $(this).find(':selected');
        if (opt.data('price')) {
            row.find('.unit-price').val(opt.data('price'));
            row.find('.description-input').val(opt.data('name') || opt.text().split(' - ')[0].trim());
        }
        row.find('.lab-test-id-input, .imaging-study-id-input').val('');
        updateTotal();
    });

    $(document).on('change', '.lab-test-select', function () {
        const row = $(this).closest('.bill-item');
        const opt = $(this).find(':selected');
        if (opt.val()) {
            row.find('.unit-price').val(opt.data('price'));
            row.find('.description-input').val(opt.data('name') || opt.text().split(' - ')[0].trim());
            row.find('.lab-test-id-input').val(opt.val());
            row.find('.imaging-study-id-input').val('');
            row.find('.service-select').val('');
        }
        updateTotal();
    });

    $(document).on('change', '.imaging-study-select', function () {
        const row = $(this).closest('.bill-item');
        const opt = $(this).find(':selected');
        if (opt.val()) {
            row.find('.unit-price').val(opt.data('price'));
            row.find('.description-input').val(opt.data('name') || opt.text().split(' - ')[0].trim());
            row.find('.imaging-study-id-input').val(opt.val());
            row.find('.lab-test-id-input').val('');
            row.find('.service-select').val('');
        }
        updateTotal();
    });

    // Recalculate on input changes
    $(document).on('input change', '.quantity, .unit-price', function () {
        updateTotal();
    });

    // Discount type select → update hint text and sync hidden radio + recompute
    $(document).on('change', '#discount_type_select', function () {
        const isPercentage = $(this).val() === 'percentage';

        // Keep hidden radios in sync (used by updateTotal / computeDiscountFromPercentage)
        $('#discount_type_fixed').prop('checked', !isPercentage);
        $('#discount_type_percentage').prop('checked', isPercentage);

        // Update hint
        $('#discount_input_hint').text(
            isPercentage ? 'Enter percentage (0–100)' : 'Enter fixed amount'
        );

        // Show/hide computed amount row
        $('#discount_computed_wrap').toggleClass('hidden', !isPercentage);

        // Reset the visible input
        $('#discount_input_value').val('0').attr('max', isPercentage ? 100 : '');

        if (isPercentage) {
            computeDiscountFromPercentage();
        } else {
            $('#discount_amount').val('0');
            $('#discount_percentage').val('0');
        }
        updateTotal();
    });

    // Visible discount input changed
    $(document).on('input', '#discount_input_value', function () {
        const isPercentage = $('#discount_type_select').val() === 'percentage';
        if (isPercentage) {
            $('#discount_percentage').val($(this).val());
            computeDiscountFromPercentage();
        } else {
            $('#discount_amount').val($(this).val());
        }
        updateTotal();
    });

    // Recalculate tax when bill type changes
    $('#bill_type').on('change', function () {
        calculateTax();
    });

    updateTotal();
});

function initSelect2OnRow(row) {
    if (typeof $.fn.select2 !== 'function') return;
    try {
        row.find('.service-select').select2({ placeholder: 'Search service...', allowClear: true, width: '100%' });
        row.find('.lab-test-select').select2({ placeholder: 'Search lab test...', allowClear: true, width: '100%' });
        row.find('.imaging-study-select').select2({ placeholder: 'Search imaging study...', allowClear: true, width: '100%' });
    } catch (e) {
        console.error('Select2 init error:', e);
    }
}

function getSubtotal() {
    let subtotal = 0;
    $('.bill-item').each(function () {
        const qty = parseFloat($(this).find('.quantity').val()) || 0;
        const price = parseFloat($(this).find('.unit-price').val()) || 0;
        subtotal += qty * price;
    });
    return subtotal;
}

function computeDiscountFromPercentage() {
    const percentage = parseFloat($('#discount_percentage').val()) || 0;
    const subtotal = getSubtotal();
    const discountAmount = (percentage / 100) * subtotal;
    $('#discount_amount').val(discountAmount.toFixed(2));

    // Update the computed display
    const symbol = window._currencySymbol || '';
    $('#discount_computed_amount').text(symbol + discountAmount.toFixed(2));
}

var taxTimer = null;
function calculateTax() {
    clearTimeout(taxTimer);
    taxTimer = setTimeout(function () {
        var subtotal = getSubtotal();
        var billType = $('#bill_type').val();

        if (subtotal <= 0 || !billType) {
            $('#tax_amount').val('0');
            $('#tax-breakdown').html('');
            updateTotal();
            return;
        }

        $.ajax({
            url: '/taxes/calculate',
            method: 'POST',
            data: {
                _token: $('input[name="_token"]').val(),
                bill_type: billType,
                subtotal: subtotal
            },
            success: function (res) {
                $('#tax_amount').val(res.total_tax);
                var html = '';
                res.breakdown.forEach(function (t) {
                    html += '<p class="text-xs text-gray-500">' + t.name + ' (' + t.percentage + '%) = ' + t.amount.toFixed(2) + '</p>';
                });
                $('#tax-breakdown').html(html);
                updateTotal();
            },
            error: function () {
                $('#tax_amount').val('0');
                $('#tax-breakdown').html('');
                updateTotal();
            }
        });
    }, 300);
}

function updateTotal() {
    let subtotal = getSubtotal();
    $('.bill-item').each(function () {
        const qty = parseFloat($(this).find('.quantity').val()) || 0;
        const price = parseFloat($(this).find('.unit-price').val()) || 0;
        $(this).find('.total-display').text((qty * price).toFixed(2));
    });

    // If percentage mode, recompute discount_amount from current subtotal
    const discountType = $('#discount_type_select').val() || $('input[name="discount_type"]:checked').val();
    if (discountType === 'percentage') {
        computeDiscountFromPercentage();
    }

    const tax = parseFloat($('#tax_amount').val()) || 0;
    const discount = parseFloat($('#discount_amount').val()) || 0;
    const total = subtotal + tax - discount;
    const symbol = currencySymbol || window.appConfig?.currency || '';

    $('#totalAmount').text(symbol + total.toFixed(2));

    updateOverpaymentWarning(total);

    // Recalculate tax when subtotal changes
    calculateTax();
}

function updateOverpaymentWarning(newTotal) {
    if (paidAmount <= 0 || paidAmount <= newTotal) {
        $('#overpayment-edit-warning').addClass('hidden');
        return;
    }

    const credit = (paidAmount - newTotal).toFixed(2);
    const symbol = currencySymbol || window.appConfig?.currency || '';

    if (!$('#overpayment-edit-warning').length) {
        $('#totalAmount').after(
            '<div id="overpayment-edit-warning" class="mt-2 text-sm text-blue-700 bg-blue-50 border border-blue-200 rounded-lg p-3"></div>'
        );
    }

    $('#overpayment-edit-warning')
        .html('<i class="fas fa-info-circle mr-1"></i>Patient credit of <strong>' + symbol + credit + '</strong> will be recorded (paid exceeds new total).')
        .removeClass('hidden');
}
