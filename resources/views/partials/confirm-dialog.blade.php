<div id="confirm-dialog"
     class="hidden fixed inset-0 z-[10000]"
     hidden
     role="dialog"
     aria-modal="true"
     aria-labelledby="confirm-dialog-title"
     aria-describedby="confirm-dialog-message"
     aria-hidden="true">
    <div data-confirm-backdrop class="absolute inset-0 bg-gray-900/50"></div>
    <div class="relative z-10 flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-md bg-white rounded-lg shadow-xl">
            <div class="px-6 pt-6 pb-4">
                <div class="flex items-start gap-4">
                    <div data-confirm-icon-wrap class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center">
                        <i data-confirm-icon class="fas fa-question-circle text-medical-blue"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 id="confirm-dialog-title" class="text-lg font-semibold text-gray-900">Confirm</h3>
                        <p id="confirm-dialog-message" class="mt-2 text-sm text-gray-600 leading-relaxed"></p>
                        <p id="confirm-dialog-detail" class="mt-2 text-xs text-gray-500 leading-relaxed hidden"></p>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" data-confirm-cancel
                    class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-white focus:outline-none focus:ring-2 focus:ring-gray-300">
                    Cancel
                </button>
                <button type="button" data-confirm-accept
                    class="w-full sm:w-auto px-4 py-2 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 bg-medical-blue hover:bg-blue-700 focus:ring-medical-blue">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>
