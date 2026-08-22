<div id="pos-payment-modal" class="fixed inset-0 z-50 hidden" aria-modal="true" role="dialog">
    <div class="absolute inset-0 bg-slate-900/45" data-close-modal></div>
    <div class="relative flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-[0_0_16px_rgba(17,17,26,0.1)] p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Payment</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" data-close-modal aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="space-y-4">
                <div class="rounded-lg bg-gray-50 p-4 flex justify-between items-center">
                    <span class="text-sm text-gray-600">Total payable</span>
                    <span id="modal-total-payable" class="text-lg font-bold text-green-800">0.00</span>
                </div>

                <div>
                    <label for="modal-payment-method" class="block text-sm font-medium text-gray-700 mb-1">Payment method</label>
                    <select id="modal-payment-method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="upi">UPI</option>
                        <option value="bank_transfer">Bank Transfer</option>
                    </select>
                </div>

                <div>
                    <label for="modal-payment-amount" class="block text-sm font-medium text-gray-700 mb-1">Amount tendered</label>
                    <input type="number"
                           id="modal-payment-amount"
                           step="0.01"
                           min="0"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                </div>

                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Change</span>
                    <span id="modal-change-amount" class="font-semibold text-gray-900">0.00</span>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-2">
                <button type="button" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50" data-close-modal>
                    Cancel
                </button>
                <button type="button" id="modal-confirm-pay-btn" class="px-4 py-2 rounded-lg bg-medical-blue text-white font-semibold hover:bg-blue-700">
                    Confirm Pay
                </button>
            </div>
        </div>
    </div>
</div>
