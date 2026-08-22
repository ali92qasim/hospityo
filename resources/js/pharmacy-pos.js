import $ from 'jquery';
import select2 from 'select2';
import Toast from './toast';
import confirmDialog from './confirm-dialog';
import '../css/pharmacy-pos-form.css';

select2(window, $);

const SELECTED_ROW_CLASSES = ['bg-blue-50', 'ring-2', 'ring-inset', 'ring-medical-blue'];
const MOBILE_GRID_OPEN_CLASSES = ['fixed', 'inset-0', 'z-50', 'bg-white', 'overflow-y-auto', 'p-4', 'block'];

$(function () {
    const $root = $('#pharmacy-pos');
    if (!$root.length) {
        return;
    }

    const currency = window.appConfig?.currency ?? '';
    const medicineSearchUrl = $root.data('medicine-search-url');

    const formatMoney = (amount) => `${currency}${Number(amount).toFixed(2)}`;

    const flashSuccess = $root.data('flash-success');
    const flashError = $root.data('flash-error');
    if (flashSuccess) {
        Toast.success(String(flashSuccess));
    }
    if (flashError) {
        Toast.error(String(flashError));
    }

    const validateStock = (medicine, quantity) => {
        if (!medicine.manage_stock) {
            return null;
        }

        const available = Number(medicine.available_stock ?? 0);
        const name = medicine.text || medicine.name;
        const unit = medicine.unit || 'unit';

        if (quantity > available) {
            return `Insufficient stock for ${name}. Available: ${available} ${unit}, required: ${quantity}.`;
        }

        return null;
    };

    const notifyStockIssue = (message) => {
        if (message) {
            Toast.warning(message);
            return true;
        }

        return false;
    };

    // Shared checkout state — declared before tab activation
    const totalPayableEl = document.getElementById('pos-total-payable');
    const itemCountEl = document.getElementById('pos-item-count');
    const payBtn = document.getElementById('pos-pay-btn');
    const expressCashBtn = document.getElementById('pos-express-cash-btn');
    const cancelBtn = document.getElementById('pos-cancel-btn');

    let activeMode = 'prescription';
    let currentTotal = 0;
    let currentItemCount = 0;
    let pendingSubmit = null;

    const getActiveTab = () => ($('#pos-tab-counter').hasClass('hidden') ? 'prescription' : 'counter');

    const updateCheckoutState = () => {
        activeMode = getActiveTab();
        if (totalPayableEl) {
            totalPayableEl.textContent = formatMoney(currentTotal);
        }
        if (itemCountEl) {
            itemCountEl.textContent = String(currentItemCount);
        }
        const canCheckout = currentTotal > 0 && currentItemCount > 0;
        if (payBtn) payBtn.disabled = !canCheckout;
        if (expressCashBtn) expressCashBtn.disabled = !canCheckout;
    };

    // Clock
    const clockEl = document.getElementById('pos-clock');
    const updateClock = () => {
        if (!clockEl) return;
        const now = new Date();
        clockEl.textContent = now.toLocaleString(undefined, {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };
    updateClock();
    setInterval(updateClock, 60000);

    // Tabs
    const tabButtons = document.querySelectorAll('.pos-tab-btn');
    const tabPanels = document.querySelectorAll('.pos-tab-panel');
    const storageKey = 'pharmacy-pos-active-tab';

    const activateTab = (tab) => {
        tabButtons.forEach((btn) => {
            const isActive = btn.dataset.posTab === tab;
            btn.classList.toggle('border-medical-blue', isActive);
            btn.classList.toggle('text-medical-blue', isActive);
            btn.classList.toggle('border-transparent', !isActive);
            btn.classList.toggle('text-gray-500', !isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });
        tabPanels.forEach((panel) => panel.classList.add('hidden'));
        document.getElementById(`pos-tab-${tab}`)?.classList.remove('hidden');
        sessionStorage.setItem(storageKey, tab);
        updateCheckoutState();
    };

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => activateTab(button.dataset.posTab));
    });

    const savedTab = sessionStorage.getItem(storageKey);
    if (savedTab === 'counter') {
        activateTab('counter');
    }

    // Payment modal
    const paymentModal = document.getElementById('pos-payment-modal');
    const modalTotalEl = document.getElementById('modal-total-payable');
    const modalMethodEl = document.getElementById('modal-payment-method');
    const modalAmountEl = document.getElementById('modal-payment-amount');
    const modalChangeEl = document.getElementById('modal-change-amount');
    const modalConfirmBtn = document.getElementById('modal-confirm-pay-btn');
    const modalCashFields = document.getElementById('modal-cash-fields');
    const modalCreditNote = document.getElementById('modal-credit-note');

    const isCreditPayment = () => modalMethodEl?.value === 'credit';

    const syncPaymentModalFields = () => {
        if (!modalMethodEl) return;

        const credit = isCreditPayment();
        modalCashFields?.classList.toggle('hidden', credit);
        modalCreditNote?.classList.toggle('hidden', !credit);

        if (credit) {
            modalAmountEl.value = '0';
            modalChangeEl.textContent = formatMoney(0);
        } else if (!modalAmountEl.value || parseFloat(modalAmountEl.value) === 0) {
            modalAmountEl.value = currentTotal.toFixed(2);
            updateModalChange();
        }
    };

    const openModal = () => {
        if (!paymentModal) return;
        paymentModal.classList.remove('hidden');
        modalTotalEl.textContent = formatMoney(currentTotal);
        modalMethodEl.value = 'cash';
        modalAmountEl.value = currentTotal.toFixed(2);
        modalChangeEl.textContent = formatMoney(0);
        syncPaymentModalFields();
        modalAmountEl.focus();
    };

    const closeModal = () => paymentModal?.classList.add('hidden');

    paymentModal?.querySelectorAll('[data-close-modal]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && paymentModal && !paymentModal.classList.contains('hidden')) {
            closeModal();
        }
    });

    const updateModalChange = () => {
        if (isCreditPayment()) {
            modalChangeEl.textContent = formatMoney(0);
            return;
        }

        const tendered = parseFloat(modalAmountEl.value || '0');
        const change = Math.max(0, tendered - currentTotal);
        modalChangeEl.textContent = formatMoney(change);
    };

    modalAmountEl?.addEventListener('input', updateModalChange);
    modalMethodEl?.addEventListener('change', syncPaymentModalFields);

    const setSelectedPrescriptionRow = (row) => {
        document.querySelectorAll('[data-prescription-row]').forEach((item) => {
            item.classList.remove(...SELECTED_ROW_CLASSES);
        });
        row?.classList.add(...SELECTED_ROW_CLASSES);
    };

    // Prescription tab
    const prescriptionForm = document.getElementById('prescription-checkout-form');
    const prescriptionIdInput = document.getElementById('prescription-id');
    const prescriptionPatientInput = document.getElementById('prescription-patient-id');
    const prescriptionPaymentAmount = document.getElementById('prescription-payment-amount');
    const prescriptionPaymentMethod = document.getElementById('prescription-payment-method');
    const prescriptionLines = document.getElementById('prescription-lines');
    const prescriptionMeta = document.getElementById('prescription-meta');
    const prescriptionPatientDisplay = document.getElementById('prescription-patient-display');
    const prescriptionPatientName = document.getElementById('prescription-patient-name');
    const prescriptionItemCount = document.getElementById('prescription-item-count');
    const prescriptionSubtotal = document.getElementById('prescription-subtotal');

    const clearPrescriptionSelection = () => {
        prescriptionIdInput.value = '';
        prescriptionPatientInput.value = '';
        prescriptionLines.innerHTML = 'Select a prescription to review medicines.';
        prescriptionMeta.classList.add('hidden');
        prescriptionPatientDisplay.classList.add('hidden');
        setSelectedPrescriptionRow(null);
        currentTotal = 0;
        currentItemCount = 0;
        updateCheckoutState();
    };

    const loadPrescription = (row) => {
        const url = row.dataset.url;

        $.ajax({
            url,
            method: 'GET',
            dataType: 'json',
            headers: { Accept: 'application/json' },
        })
            .done((data) => {
                setSelectedPrescriptionRow(row);

                prescriptionIdInput.value = data.id;
                prescriptionPatientInput.value = data.patient_id;
                prescriptionPatientName.textContent = data.patient_name;
                prescriptionPatientDisplay.classList.remove('hidden');

                prescriptionLines.innerHTML = `
                    <div class="font-medium text-gray-800 mb-3">${data.prescription_no}</div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-500 uppercase border-b">
                                <th class="pb-2 px-1">Medicine</th>
                                <th class="pb-2 px-1 w-16">Qty</th>
                                <th class="pb-2 px-1 w-20 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${data.items.map((item) => `
                                <tr class="even:bg-gray-50">
                                    <td class="py-2 px-1 pr-2">${item.name}</td>
                                    <td class="py-2 px-1">${item.quantity} ${item.unit}</td>
                                    <td class="py-2 px-1 text-right">${formatMoney(item.total_price)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;

                prescriptionItemCount.textContent = String(data.items.length);
                prescriptionSubtotal.textContent = formatMoney(data.total_amount);
                prescriptionMeta.classList.remove('hidden');

                currentTotal = data.total_amount;
                currentItemCount = data.items.length;
                updateCheckoutState();
            })
            .fail(() => Toast.error('Unable to load prescription.'));
    };

    document.querySelectorAll('[data-prescription-row]').forEach((row) => {
        row.addEventListener('click', () => loadPrescription(row));
    });

    // Counter tab cart
    const cart = [];
    const cartBody = document.getElementById('cart-body');
    const cartSubtotal = document.getElementById('cart-subtotal');
    const walkinForm = document.getElementById('walkin-checkout-form');
    const walkinPaymentAmount = document.getElementById('walkin-payment-amount');
    const walkinPaymentMethod = document.getElementById('walkin-payment-method');
    const medicineGrid = document.getElementById('medicine-grid');
    const gridPanel = document.getElementById('medicine-grid-panel');
    let lastSelectedMedicine = null;

    const renderCart = () => {
        cartBody.innerHTML = '';

        if (cart.length === 0) {
            cartBody.innerHTML = '<tr><td colspan="5" class="px-3 py-10 text-center text-gray-500">Cart is empty. Search or pick from the grid.</td></tr>';
            cartSubtotal.textContent = formatMoney(0);
            currentTotal = 0;
            currentItemCount = 0;
            updateCheckoutState();
            return;
        }

        let total = 0;
        cart.forEach((item, index) => {
            const lineTotal = item.quantity * item.unit_price;
            total += lineTotal;
            const row = document.createElement('tr');
            row.className = index % 2 === 1 ? 'even:bg-gray-50' : '';
            row.innerHTML = `
                <td class="px-3 py-2 font-medium text-gray-800">${item.name}</td>
                <td class="px-3 py-2">
                    <input type="number" min="1" value="${item.quantity}" data-cart-qty="${index}"
                           class="w-16 px-2 py-1 border border-gray-300 rounded text-center text-sm">
                </td>
                <td class="px-3 py-2">${formatMoney(item.unit_price)}</td>
                <td class="px-3 py-2">${formatMoney(lineTotal)}</td>
                <td class="px-3 py-2"><button type="button" data-index="${index}" class="remove-cart-item text-red-600 hover:text-red-800"><i class="fas fa-trash-alt"></i></button></td>
            `;
            cartBody.appendChild(row);
        });

        cartSubtotal.textContent = formatMoney(total);
        currentTotal = total;
        currentItemCount = cart.length;
        updateCheckoutState();
    };

    const addToCart = (medicine, quantity = 1) => {
        if (!medicine?.id) return false;

        const existing = cart.find((item) => item.medicine_id === medicine.id);
        const nextQuantity = existing ? existing.quantity + quantity : quantity;
        const stockIssue = validateStock(medicine, nextQuantity);

        if (notifyStockIssue(stockIssue)) {
            return false;
        }

        if (existing) {
            existing.quantity = nextQuantity;
        } else {
            cart.push({
                medicine_id: medicine.id,
                name: medicine.text || medicine.name,
                quantity: nextQuantity,
                unit_price: parseFloat(medicine.selling_price),
                available_stock: Number(medicine.available_stock ?? 0),
                manage_stock: Boolean(medicine.manage_stock),
                unit: medicine.unit || 'unit',
            });
        }

        renderCart();
        return true;
    };

    const formatMedicineOption = (medicine) => {
        if (!medicine.id) {
            return medicine.text;
        }

        const outOfStock = medicine.manage_stock && medicine.available_stock <= 0;
        const stockText = outOfStock
            ? 'Out of stock'
            : `Stock: ${medicine.available_stock} ${medicine.unit}`;

        return $(`
            <div class="py-0.5">
                <div class="text-sm font-medium text-gray-900">${medicine.text}</div>
                <div class="text-xs ${outOfStock ? 'text-red-600' : 'text-gray-500'}">${stockText} · ${formatMoney(medicine.selling_price)}</div>
            </div>
        `);
    };

    const renderMedicineGrid = (medicines) => {
        if (!medicineGrid) return;

        if (!medicines.length) {
            medicineGrid.innerHTML = '<p class="col-span-2 text-sm text-gray-500 text-center py-8">No medicines found.</p>';
            return;
        }

        medicineGrid.innerHTML = medicines.map((med) => {
            const outOfStock = med.manage_stock && med.available_stock <= 0;
            const stockLabel = outOfStock
                ? '<span class="text-red-600 font-medium">Out of stock</span>'
                : `<span class="text-gray-500">Stock: ${med.available_stock} ${med.unit}</span>`;

            return `
                <button type="button"
                        class="text-left bg-white border rounded-xl p-3 shadow-sm transition-transform hover:-translate-y-0.5 hover:shadow-md ${outOfStock ? 'border-red-200 opacity-80' : 'border-gray-100'}"
                        data-medicine-id="${med.id}"
                        data-medicine-name="${med.text}"
                        data-medicine-price="${med.selling_price}"
                        data-medicine-stock="${med.available_stock}"
                        data-medicine-manage-stock="${med.manage_stock ? '1' : '0'}"
                        data-medicine-unit="${med.unit}">
                    <div class="font-semibold text-gray-900 text-sm leading-snug mb-1">${med.text}</div>
                    <div class="text-xs mb-2">${stockLabel}</div>
                    <div class="text-sm font-bold text-medical-blue">${formatMoney(med.selling_price)}</div>
                </button>
            `;
        }).join('');

        medicineGrid.querySelectorAll('[data-medicine-id]').forEach((tile) => {
            tile.addEventListener('click', () => {
                const qty = Math.max(1, parseInt($('#counter-quantity').val() || '1', 10));
                addToCart({
                    id: parseInt(tile.dataset.medicineId, 10),
                    text: tile.dataset.medicineName,
                    selling_price: tile.dataset.medicinePrice,
                    available_stock: parseInt(tile.dataset.medicineStock, 10),
                    manage_stock: tile.dataset.medicineManageStock === '1',
                    unit: tile.dataset.medicineUnit,
                }, qty);
            });
        });
    };

    const fetchMedicines = (term = '') => {
        if (!medicineSearchUrl) return;

        $.ajax({
            url: medicineSearchUrl,
            method: 'GET',
            dataType: 'json',
            data: { q: term },
        })
            .done((data) => renderMedicineGrid(data.results ?? []))
            .fail(() => {
                Toast.error('Unable to load medicines.');
                renderMedicineGrid([]);
            });
    };

    // Select2 — patient dropdown
    const $patientSelect = $('#walkin-patient-id');
    if ($patientSelect.length && typeof $.fn.select2 === 'function') {
        $patientSelect.select2({
            placeholder: 'Search patient by name or number…',
            allowClear: true,
            width: '100%',
        });
    }

    // Select2 — medicine search (name / SKU via AJAX)
    const $medicineSelect = $('#medicine-search-select');
    if ($medicineSelect.length && typeof $.fn.select2 === 'function') {
        $medicineSelect.select2({
            placeholder: 'Search by name or SKU…',
            allowClear: true,
            width: '100%',
            minimumInputLength: 1,
            ajax: {
                url: medicineSearchUrl,
                dataType: 'json',
                delay: 250,
                data: (params) => ({ q: params.term || '' }),
                processResults: (data) => ({
                    results: (data.results ?? []).map((med) => ({
                        id: med.id,
                        text: med.text,
                        medicine: med,
                    })),
                }),
            },
            templateResult: (data) => {
                if (data.loading || !data.medicine) {
                    return data.text;
                }
                return formatMedicineOption(data.medicine);
            },
            templateSelection: (data) => data.text || data.id,
        });

        $medicineSelect.on('select2:select', (event) => {
            const medicine = event.params.data.medicine;
            lastSelectedMedicine = medicine;
            const qty = Math.max(1, parseInt($('#counter-quantity').val() || '1', 10));
            if (addToCart(medicine, qty)) {
                $medicineSelect.val(null).trigger('change');
                lastSelectedMedicine = null;
            }
        });
    }

    $('#add-to-cart-btn').on('click', () => {
        if (!$medicineSelect.length || typeof $.fn.select2 !== 'function') {
            Toast.info('Search and select a medicine first.');
            return;
        }

        const selectedData = $medicineSelect.select2('data')[0];
        const medicine = selectedData?.medicine ?? lastSelectedMedicine;

        if (!medicine) {
            Toast.info('Search and select a medicine first.');
            return;
        }

        const qty = Math.max(1, parseInt($('#counter-quantity').val() || '1', 10));
        if (addToCart(medicine, qty)) {
            $medicineSelect.val(null).trigger('change');
            lastSelectedMedicine = null;
        }
    });

    cartBody?.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('.remove-cart-item');
        if (removeBtn) {
            cart.splice(parseInt(removeBtn.dataset.index, 10), 1);
            renderCart();
        }
    });

    cartBody?.addEventListener('change', (event) => {
        const qtyInput = event.target.closest('[data-cart-qty]');
        if (!qtyInput) return;

        const index = parseInt(qtyInput.dataset.cartQty, 10);
        const item = cart[index];
        const newQty = Math.max(1, parseInt(qtyInput.value || '1', 10));
        const stockIssue = validateStock(item, newQty);

        if (notifyStockIssue(stockIssue)) {
            qtyInput.value = String(item.quantity);
            return;
        }

        item.quantity = newQty;
        renderCart();
    });

    const openMobileGrid = () => {
        if (!gridPanel) return;
        gridPanel.classList.remove('hidden');
        gridPanel.classList.add(...MOBILE_GRID_OPEN_CLASSES);
        fetchMedicines('');
    };

    const closeMobileGrid = () => {
        if (!gridPanel) return;
        gridPanel.classList.remove(...MOBILE_GRID_OPEN_CLASSES);
        gridPanel.classList.add('hidden', 'lg:block');
    };

    document.getElementById('open-mobile-grid')?.addEventListener('click', openMobileGrid);
    document.getElementById('close-mobile-grid')?.addEventListener('click', closeMobileGrid);

    fetchMedicines('');

    const submitCheckout = (method, amount) => {
        if (activeMode === 'prescription') {
            if (!prescriptionIdInput.value) {
                Toast.info('Select a prescription first.');
                return;
            }
            prescriptionPaymentMethod.value = method;
            prescriptionPaymentAmount.value = amount.toFixed(2);
            prescriptionForm.submit();
            return;
        }

        if (!$patientSelect.val()) {
            Toast.info('Select a patient first.');
            return;
        }
        if (cart.length === 0) {
            Toast.info('Cart is empty.');
            return;
        }

        walkinForm.querySelectorAll('[data-cart-field]').forEach((field) => field.remove());
        cart.forEach((item, index) => {
            ['medicine_id', 'quantity', 'unit_price'].forEach((key) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = `items[${index}][${key}]`;
                input.value = item[key === 'medicine_id' ? 'medicine_id' : key];
                input.dataset.cartField = '1';
                walkinForm.appendChild(input);
            });
        });

        walkinPaymentMethod.value = method;
        walkinPaymentAmount.value = amount.toFixed(2);
        walkinForm.submit();
    };

    payBtn?.addEventListener('click', () => {
        if (currentTotal <= 0) return;
        pendingSubmit = () => {
            const method = modalMethodEl.value;
            const amount = method === 'credit' ? 0 : parseFloat(modalAmountEl.value || '0');
            submitCheckout(method, amount);
        };
        openModal();
    });

    modalConfirmBtn?.addEventListener('click', () => {
        pendingSubmit?.();
        closeModal();
    });

    expressCashBtn?.addEventListener('click', () => {
        if (currentTotal <= 0) return;
        submitCheckout('cash', currentTotal);
    });

    cancelBtn?.addEventListener('click', async () => {
        const ok = await confirmDialog({
            title: 'Clear selection',
            message: 'Clear the current prescription or cart?',
            confirmText: 'Clear',
            variant: 'danger',
        });

        if (!ok) return;

        clearPrescriptionSelection();
        cart.length = 0;
        renderCart();
        $medicineSelect.val(null).trigger('change');
        $patientSelect.val(null).trigger('change');
        lastSelectedMedicine = null;
    });

    updateCheckoutState();
});
