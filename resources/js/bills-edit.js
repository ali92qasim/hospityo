/**
 * Bill Edit Form — Handles dynamic item management, discount calculation,
 * and overpayment warnings when editing a bill that has existing payments.
 */

let itemIndex = window._billItemCount || 1;
const currencySymbol = window._currencySymbol || '';
const paidAmount = parseFloat(window._billPaidAmount) || 0;

document.addEventListener('DOMContentLoaded', function () {
    // Add item
    document.getElementById('addItem').addEventListener('click', function () {
        const billItems = document.getElementById('billItems');
        const firstItem = document.querySelector('.bill-item');
        if (!firstItem) return;

        const newItem = firstItem.cloneNode(true);

        newItem.querySelectorAll('input, select').forEach(function (input) {
            const oldName = input.name;
            if (oldName) {
                input.name = oldName.replace(/\[\d+\]/, '[' + itemIndex + ']');
            }
            if (input.type !== 'button') {
                input.value = input.type === 'number' && input.classList.contains('quantity') ? '1' : '';
            }
            if (input.tagName === 'SELECT') {
                input.selectedIndex = 0;
            }
        });

        billItems.appendChild(newItem);
        itemIndex++;
        updateTotal();
    });

    // Remove item
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-item')) {
            if (document.querySelectorAll('.bill-item').length > 1) {
                e.target.closest('.bill-item').remove();
                updateTotal();
            }
        }
    });

    // Service selection change
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('service-select')) {
            const option = e.target.selectedOptions[0];
            const row = e.target.closest('.bill-item');
            const priceInput = row.querySelector('.unit-price');
            const descInput = row.querySelector('input[name*="[description]"]');

            if (option.dataset.price) {
                priceInput.value = option.dataset.price;
                descInput.value = option.text.split(' - ')[0];
            }
            updateTotal();
        }

        if (e.target.classList.contains('quantity') || e.target.classList.contains('unit-price') ||
            e.target.id === 'tax_amount') {
            updateTotal();
        }
    });

    // Discount type select
    var discountTypeSelect = document.getElementById('discount_type_select');
    if (discountTypeSelect) {
        discountTypeSelect.addEventListener('change', function () {
            var isPercentage = this.value === 'percentage';

            document.getElementById('discount_type_fixed').checked = !isPercentage;
            document.getElementById('discount_type_percentage').checked = isPercentage;

            document.getElementById('discount_input_hint').textContent =
                isPercentage ? 'Enter percentage (0–100)' : 'Enter fixed amount';

            var computedWrap = document.getElementById('discount_computed_wrap');
            computedWrap.classList.toggle('hidden', !isPercentage);

            var inputVal = document.getElementById('discount_input_value');
            inputVal.value = '0';
            inputVal.max = isPercentage ? '100' : '';

            if (isPercentage) {
                document.getElementById('discount_percentage').value = '0';
                computeDiscountFromPercentage();
            } else {
                document.getElementById('discount_amount').value = '0';
                document.getElementById('discount_percentage').value = '0';
            }
            updateTotal();
        });
    }

    // Visible discount input changed
    var discountInput = document.getElementById('discount_input_value');
    if (discountInput) {
        discountInput.addEventListener('input', function () {
            var isPercentage = document.getElementById('discount_type_select').value === 'percentage';
            if (isPercentage) {
                document.getElementById('discount_percentage').value = this.value;
                computeDiscountFromPercentage();
            } else {
                document.getElementById('discount_amount').value = this.value;
            }
            updateTotal();
        });
    }

    // Initialize total calculation
    updateTotal();
});

function computeDiscountFromPercentage() {
    var percentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
    var subtotal = getSubtotal();
    var discountAmount = (percentage / 100) * subtotal;
    document.getElementById('discount_amount').value = discountAmount.toFixed(2);

    var computedEl = document.getElementById('discount_computed_amount');
    if (computedEl) {
        computedEl.textContent = currencySymbol + discountAmount.toFixed(2);
    }
}

function getSubtotal() {
    var subtotal = 0;
    document.querySelectorAll('.bill-item').forEach(function (item) {
        var qty = parseFloat(item.querySelector('.quantity').value) || 0;
        var price = parseFloat(item.querySelector('.unit-price').value) || 0;
        subtotal += qty * price;
    });
    return subtotal;
}

function updateTotal() {
    var subtotal = getSubtotal();

    // If percentage mode, recompute discount_amount from current subtotal
    var discountTypeEl = document.getElementById('discount_type_select');
    if (discountTypeEl && discountTypeEl.value === 'percentage') {
        computeDiscountFromPercentage();
    }

    var tax = parseFloat(document.getElementById('tax_amount').value) || 0;
    var discount = parseFloat(document.getElementById('discount_amount').value) || 0;
    var total = subtotal + tax - discount;

    document.getElementById('totalAmount').textContent = currencySymbol + total.toFixed(2);

    // Show overpayment warning dynamically if paid > new total
    updateOverpaymentWarning(total);
}

/**
 * Show/update a dynamic overpayment warning below the total when the user
 * is about to reduce the bill total below the already-paid amount.
 */
function updateOverpaymentWarning(newTotal) {
    var warningEl = document.getElementById('overpayment-edit-warning');

    if (paidAmount > 0 && paidAmount > newTotal) {
        var credit = (paidAmount - newTotal).toFixed(2);

        if (!warningEl) {
            warningEl = document.createElement('div');
            warningEl.id = 'overpayment-edit-warning';
            warningEl.className = 'mt-2 text-sm text-blue-700 bg-blue-50 border border-blue-200 rounded-lg p-3';
            var totalEl = document.getElementById('totalAmount');
            totalEl.parentNode.appendChild(warningEl);
        }

        warningEl.innerHTML = '<i class="fas fa-info-circle mr-1"></i>' +
            'Patient credit of <strong>' + currencySymbol + credit + '</strong> ' +
            'will be recorded (paid exceeds new total).';
        warningEl.classList.remove('hidden');
    } else if (warningEl) {
        warningEl.classList.add('hidden');
    }
}
