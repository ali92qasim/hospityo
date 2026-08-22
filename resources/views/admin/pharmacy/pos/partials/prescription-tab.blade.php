<div id="pos-tab-prescription" class="pos-tab-panel">
    <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
        {{-- Queue 60% --}}
        <div class="w-full lg:w-[60%]">
            <div class="bg-white rounded-2xl overflow-hidden shadow-[0_0_16px_rgba(17,17,26,0.1)]">
                <div class="px-4 py-3 border-b border-gray-100">
                    <h2 class="font-semibold text-gray-800">Pending Prescriptions</h2>
                    <p class="text-xs text-gray-500 mt-0.5">In-house fulfillment queue</p>
                </div>
                <div class="overflow-x-auto max-h-[520px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Rx #</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Patient</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Doctor</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" id="prescription-queue-body">
                            @forelse($pendingPrescriptions as $prescription)
                                <tr class="cursor-pointer transition-colors hover:bg-blue-50"
                                    data-prescription-row
                                    data-url="{{ route('pharmacy.pos.prescription', $prescription) }}"
                                    data-prescription-id="{{ $prescription->id }}">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ $prescription->prescription_no }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ $prescription->patient->name }}</td>
                                    <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">Dr. {{ $prescription->doctor->name }}</td>
                                    <td class="px-4 py-3 text-gray-800 font-medium">{{ format_currency($prescription->total_amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-12 text-center text-gray-500">
                                        <i class="fas fa-prescription text-3xl text-gray-300 mb-2 block"></i>
                                        No pending in-house prescriptions.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Fulfillment 40% --}}
        <div class="w-full lg:w-[40%]">
            <div class="bg-white rounded-2xl p-4 sm:p-5 shadow-[0_0_16px_rgba(17,17,26,0.1)]">
                <h2 class="font-semibold text-gray-800 mb-1">Fulfillment</h2>
                <p class="text-xs text-gray-500 mb-4">Select a prescription from the queue</p>

                <form method="POST" action="{{ route('pharmacy.pos.checkout') }}" id="prescription-checkout-form">
                    @csrf
                    <input type="hidden" name="mode" value="prescription">
                    <input type="hidden" name="prescription_id" id="prescription-id">
                    <input type="hidden" name="patient_id" id="prescription-patient-id">
                    <input type="hidden" name="payment_amount" id="prescription-payment-amount">
                    <input type="hidden" name="payment_method" id="prescription-payment-method" value="cash">

                    <div id="prescription-patient-display" class="mb-4 hidden">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Patient</label>
                        <div id="prescription-patient-name" class="inline-flex items-center px-3 py-1.5 rounded-full bg-medical-light text-medical-blue text-sm font-medium"></div>
                    </div>

                    <div id="prescription-lines" class="mb-4 min-h-[140px] border border-dashed border-gray-200 rounded-xl p-4 text-sm text-gray-500">
                        Select a prescription to review medicines.
                    </div>

                    <div id="prescription-meta" class="hidden border-t border-gray-100 pt-3 space-y-1 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Line items</span>
                            <span id="prescription-item-count">0</span>
                        </div>
                        <div class="flex justify-between font-semibold text-gray-900">
                            <span>Subtotal</span>
                            <span id="prescription-subtotal">0.00</span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
