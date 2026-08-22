<div id="pos-tab-counter" class="pos-tab-panel hidden">
    <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
        {{-- Cart 60% --}}
        <div class="w-full lg:w-[60%]">
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-[0_0_16px_rgba(17,17,26,0.1)]">
                <form method="POST" action="{{ route('pharmacy.pos.checkout') }}" id="walkin-checkout-form">
                    @csrf
                    <input type="hidden" name="mode" value="walk_in">
                    <input type="hidden" name="payment_amount" id="walkin-payment-amount">
                    <input type="hidden" name="payment_method" id="walkin-payment-method" value="cash">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                        <div>
                            <label for="walkin-patient-id" class="block text-sm font-medium text-gray-700 mb-1">Patient</label>
                            <select name="patient_id" id="walkin-patient-id" class="pos-patient-select w-full" required>
                                <option value="">Select patient</option>
                                @foreach($patients as $patient)
                                    <option value="{{ $patient->id }}">
                                        {{ $patient->name }}@if($patient->patient_no) ({{ $patient->patient_no }})@endif @if($patient->phone) — {{ $patient->phone }}@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="medicine-search-select" class="block text-sm font-medium text-gray-700 mb-1">Search medicine</label>
                            <div class="flex gap-2">
                                <select id="medicine-search-select" class="flex-1 min-w-0"></select>
                                <input type="number" id="counter-quantity" min="1" value="1" class="w-20 px-2 py-2 border border-gray-300 rounded-lg text-center">
                                <button type="button" id="add-to-cart-btn" class="px-3 py-2 rounded-lg bg-medical-blue text-white hover:bg-blue-700">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-x-auto rounded-xl border border-gray-200 mb-4">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left text-xs text-gray-500 uppercase">
                                    <th class="px-3 py-2">Medicine</th>
                                    <th class="px-3 py-2 w-20">Qty</th>
                                    <th class="px-3 py-2 w-24">Price</th>
                                    <th class="px-3 py-2 w-24">Subtotal</th>
                                    <th class="px-3 py-2 w-10"></th>
                                </tr>
                            </thead>
                            <tbody id="cart-body">
                                <tr id="cart-empty-row">
                                    <td colspan="5" class="px-3 py-10 text-center text-gray-500">Cart is empty. Search or pick from the grid.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-between items-center text-sm border-t border-gray-100 pt-3">
                        <span class="text-gray-600">Cart subtotal</span>
                        <span id="cart-subtotal" class="font-bold text-gray-900">0.00</span>
                    </div>
                </form>
            </div>
        </div>

        {{-- Product grid 40% --}}
        <div class="w-full lg:w-[40%]">
            <div class="hidden lg:block bg-white rounded-2xl p-4 sm:p-5 shadow-[0_0_16px_rgba(17,17,26,0.1)]"
                 id="medicine-grid-panel">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="font-semibold text-gray-800">Medicines</h2>
                    <button type="button" id="close-mobile-grid" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="medicine-grid" class="grid grid-cols-2 xl:grid-cols-2 gap-3 max-h-[520px] overflow-y-auto">
                    <p class="col-span-2 text-sm text-gray-500 text-center py-8">Loading medicines…</p>
                </div>
            </div>
            <button type="button"
                    id="open-mobile-grid"
                    class="lg:hidden mt-3 w-full py-3 rounded-xl bg-medical-blue text-white font-medium">
                Browse Medicines
            </button>
        </div>
    </div>
</div>
