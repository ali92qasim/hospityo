document.addEventListener('DOMContentLoaded', () => {
    const tabButtons = document.querySelectorAll('.pos-tab-btn');
    const tabPanels = document.querySelectorAll('.pos-tab-panel');

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const tab = button.dataset.posTab;
            tabButtons.forEach((btn) => {
                btn.classList.remove('border-medical-blue', 'text-medical-blue');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            button.classList.add('border-medical-blue', 'text-medical-blue');
            button.classList.remove('border-transparent', 'text-gray-500');

            tabPanels.forEach((panel) => panel.classList.add('hidden'));
            document.getElementById(`pos-tab-${tab}`)?.classList.remove('hidden');
        });
    });

    const prescriptionForm = document.getElementById('prescription-checkout-form');
    const prescriptionIdInput = document.getElementById('prescription-id');
    const prescriptionPatientSelect = document.getElementById('prescription-patient-id');
    const prescriptionLines = document.getElementById('prescription-lines');
    const prescriptionPaymentAmount = document.getElementById('prescription-payment-amount');
    const prescriptionCheckoutBtn = document.getElementById('prescription-checkout-btn');

    document.querySelectorAll('.load-prescription-btn').forEach((button) => {
        button.addEventListener('click', async () => {
            const response = await fetch(button.dataset.url, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                alert('Unable to load prescription.');
                return;
            }

            const data = await response.json();
            prescriptionIdInput.value = data.id;
            prescriptionPatientSelect.value = data.patient_id;
            prescriptionPaymentAmount.value = data.total_amount.toFixed(2);
            prescriptionCheckoutBtn.disabled = false;

            prescriptionLines.innerHTML = `
                <div class="font-medium text-gray-800 mb-2">${data.prescription_no} — ${data.patient_name}</div>
                <ul class="space-y-2">
                    ${data.items.map((item) => `
                        <li class="flex justify-between text-gray-700">
                            <span>${item.name} × ${item.quantity} ${item.unit}</span>
                            <span>${item.total_price.toFixed(2)}</span>
                        </li>
                    `).join('')}
                </ul>
                <div class="mt-3 pt-3 border-t flex justify-between font-semibold text-gray-900">
                    <span>Total</span>
                    <span>${data.total_amount.toFixed(2)}</span>
                </div>
            `;
        });
    });

    const cart = [];
    const cartBody = document.getElementById('cart-body');
    const cartTotal = document.getElementById('cart-total');
    const walkinPaymentAmount = document.getElementById('walkin-payment-amount');
    const walkinCheckoutBtn = document.getElementById('walkin-checkout-btn');
    const walkinForm = document.getElementById('walkin-checkout-form');
    let selectedMedicine = null;

    if (window.jQuery && window.pharmacyPosConfig?.medicineSearchUrl) {
        window.jQuery('#medicine-search').select2({
            placeholder: 'Search medicine...',
            allowClear: true,
            ajax: {
                url: window.pharmacyPosConfig.medicineSearchUrl,
                dataType: 'json',
                delay: 250,
                data: (params) => ({ q: params.term || '' }),
                processResults: (data) => ({ results: data.results }),
            },
        }).on('select2:select', (event) => {
            selectedMedicine = event.params.data;
        }).on('select2:clear', () => {
            selectedMedicine = null;
        });
    }

    const renderCart = () => {
        cartBody.innerHTML = '';

        if (cart.length === 0) {
            cartBody.innerHTML = '<tr id="cart-empty-row"><td colspan="5" class="py-6 text-center text-gray-500">Cart is empty.</td></tr>';
            cartTotal.textContent = '0.00';
            walkinPaymentAmount.value = '0.00';
            walkinCheckoutBtn.disabled = true;
            return;
        }

        let total = 0;
        cart.forEach((item, index) => {
            total += item.quantity * item.unit_price;
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="py-2 pr-2">${item.name}</td>
                <td class="py-2 pr-2">${item.quantity}</td>
                <td class="py-2 pr-2">${item.unit_price.toFixed(2)}</td>
                <td class="py-2 pr-2">${(item.quantity * item.unit_price).toFixed(2)}</td>
                <td class="py-2"><button type="button" data-index="${index}" class="remove-cart-item text-red-600">Remove</button></td>
            `;
            cartBody.appendChild(row);
        });

        cartTotal.textContent = total.toFixed(2);
        walkinPaymentAmount.value = total.toFixed(2);
        walkinCheckoutBtn.disabled = false;
    };

    document.getElementById('add-to-cart-btn')?.addEventListener('click', () => {
        if (!selectedMedicine) {
            alert('Select a medicine first.');
            return;
        }

        const quantity = Math.max(1, parseInt(document.getElementById('counter-quantity').value || '1', 10));
        cart.push({
            medicine_id: selectedMedicine.id,
            name: selectedMedicine.text,
            quantity,
            unit_price: parseFloat(selectedMedicine.selling_price),
        });

        window.jQuery('#medicine-search').val(null).trigger('change');
        selectedMedicine = null;
        renderCart();
    });

    cartBody?.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-cart-item');
        if (!button) return;
        cart.splice(parseInt(button.dataset.index, 10), 1);
        renderCart();
    });

    walkinForm?.addEventListener('submit', () => {
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
    });
});
