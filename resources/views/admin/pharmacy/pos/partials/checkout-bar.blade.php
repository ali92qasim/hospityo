<div class="fixed bottom-0 left-0 right-0 z-40 bg-white border-t border-gray-200 shadow-[0_-4px_20px_rgba(17,17,26,0.08)]">
    <div class="max-w-[1600px] mx-auto px-3 sm:px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-4 text-sm">
            <span class="text-gray-600">
                Items: <strong id="pos-item-count" class="text-gray-900">0</strong>
            </span>
            <span class="text-gray-600">
                Total Payable:
                <strong id="pos-total-payable" class="text-green-800 text-base ml-1">0.00</strong>
            </span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    id="pos-pay-btn"
                    disabled
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-medical-blue text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-money-check-alt"></i>
                Pay
            </button>
            <button type="button"
                    id="pos-express-cash-btn"
                    disabled
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-semibold hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-money-bill-wave"></i>
                Express Cash
            </button>
            <button type="button"
                    id="pos-cancel-btn"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700">
                <i class="fas fa-times"></i>
                Cancel
            </button>
        </div>
    </div>
</div>
