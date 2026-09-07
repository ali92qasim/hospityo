                @if(!$visit->admission)
                    <div class="space-y-6">
                        <h4 class="text-lg font-medium text-gray-800 mb-4">Select Bed for Admission</h4>
                        
                        <form action="{{ route('visits.admit', $visit) }}" method="POST" id="admission-form">
                            @csrf
                            <input type="hidden" name="bed_id" id="selected-bed-id">
                            
                            <!-- Ward Filter -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Ward</label>
                                <select id="ward-filter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                    <option value="">All Wards</option>
                                    @foreach($availableBeds->groupBy('ward.name') as $wardName => $beds)
                                        <option value="{{ $wardName }}">{{ $wardName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Bed Grid -->
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-6" id="bed-grid">
                                @foreach($availableBeds ?? [] as $bed)
                                    <div class="bed-card border-2 border-gray-200 rounded-lg p-4 cursor-pointer hover:border-medical-blue transition-colors" 
                                         data-bed-id="{{ $bed->id }}" 
                                         data-ward="{{ $bed->ward->name }}">
                                        <div class="text-center">
                                            <div class="w-12 h-12 mx-auto mb-2 bg-green-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-bed text-green-600 text-lg"></i>
                                            </div>
                                            <div class="font-medium text-gray-800">{{ $bed->bed_number }}</div>
                                            <div class="text-xs text-gray-500 mb-1">{{ $bed->ward->name }}</div>
                                            <div class="text-xs font-medium text-medical-blue">{{ ucfirst($bed->bed_type) }}</div>
                                            <div class="text-xs text-gray-600 mt-1">{{ currency_symbol() }}{{ number_format($bed->daily_rate, 0) }}/day</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            
                            <!-- Selected Bed Info -->
                            <div id="selected-bed-info" class="hidden bg-medical-light border border-medical-blue rounded-lg p-4 mb-4">
                                <div class="flex items-center">
                                    <i class="fas fa-bed text-medical-blue mr-2"></i>
                                    <span class="font-medium text-medical-blue">Selected: </span>
                                    <span id="selected-bed-details" class="ml-2"></span>
                                </div>
                            </div>
                            
                            <!-- Admission Notes -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Admission Notes</label>
                                <textarea name="admission_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter admission notes..."></textarea>
                            </div>
                            
                            <button type="submit" id="admit-btn" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <i class="fas fa-bed mr-2"></i>Admit Patient
                            </button>
                        </form>
                    </div>
                    
                @else
                    @php
                        $admission = $visit->admission;
                        $ipdBill = \App\Services\IpdDraftBillService::resolveForVisit($visit) ?? $visit->draftBill;
                        $finalBill = null;
                        if (! $ipdBill && $admission->status === 'discharged') {
                            $finalBill = $visit->bills()
                                ->where('bill_type', 'ipd')
                                ->where('status', '!=', 'draft')
                                ->latest('id')
                                ->first();
                        }
                        $settlement = $admission->status === 'active'
                            ? \App\Services\IpdDischargeBillingService::preview($visit)
                            : null;
                        $totalAdvances = $admission->total_advances;
                        $draftCharges = $admission->draft_bill_charges;
                        $availableCredit = $admission->credit_balance;
                    @endphp
                    <div class="space-y-6">
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                            <h4 class="text-lg font-medium text-purple-800 mb-4">Patient Admitted</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm text-purple-600">Ward:</span>
                                    <span class="ml-2 font-medium">{{ $admission->bed->ward->name }}</span>
                                </div>
                                <div>
                                    <span class="text-sm text-purple-600">Bed:</span>
                                    <span class="ml-2 font-medium">{{ $admission->bed->bed_number }}</span>
                                </div>
                                <div>
                                    <span class="text-sm text-purple-600">Admitted:</span>
                                    <span class="ml-2">{{ $admission->admission_date->format('M d, Y h:i A') }}</span>
                                </div>
                                @if($admission->discharge_date)
                                <div>
                                    <span class="text-sm text-purple-600">Discharged:</span>
                                    <span class="ml-2">{{ $admission->discharge_date->format('M d, Y h:i A') }}</span>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- IPD Billing panel --}}
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                                <h4 class="text-lg font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-file-invoice-dollar text-medical-blue mr-2"></i>IPD Billing
                                </h4>
                                @if($ipdBill)
                                    <span class="text-sm text-gray-600">
                                        {{ $ipdBill->bill_number }} Â·
                                        <span class="font-semibold text-medical-blue">{{ format_currency($ipdBill->total_amount) }}</span>
                                        <span class="ml-1 text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full">Draft</span>
                                    </span>
                                @elseif($finalBill)
                                    <span class="text-sm text-gray-600">
                                        {{ $finalBill->bill_number }} Â·
                                        <span class="font-semibold text-medical-blue">{{ format_currency($finalBill->total_amount) }}</span>
                                        <span class="ml-1 text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full capitalize">{{ $finalBill->status }}</span>
                                    </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                                <div class="rounded-lg bg-blue-50 border border-blue-100 p-3">
                                    <div class="text-xs text-blue-600 uppercase font-medium">Advances</div>
                                    <div class="text-lg font-bold text-blue-900">{{ format_currency($totalAdvances) }}</div>
                                </div>
                                <div class="rounded-lg bg-gray-50 border border-gray-200 p-3">
                                    <div class="text-xs text-gray-500 uppercase font-medium">{{ $ipdBill ? 'Draft Charges' : 'Bill Total' }}</div>
                                    <div class="text-lg font-bold text-gray-900">{{ format_currency($draftCharges) }}</div>
                                </div>
                                <div class="rounded-lg {{ $availableCredit >= 0 ? 'bg-green-50 border-green-100' : 'bg-amber-50 border-amber-100' }} border p-3">
                                    <div class="text-xs uppercase font-medium {{ $availableCredit >= 0 ? 'text-green-600' : 'text-amber-700' }}">
                                        {{ $availableCredit >= 0 ? 'Available Credit' : 'Amount Due' }}
                                    </div>
                                    <div class="text-lg font-bold {{ $availableCredit >= 0 ? 'text-green-800' : 'text-amber-800' }}">
                                        {{ format_currency(abs($availableCredit)) }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                @if(\App\Models\ModuleRegistry::allows(\App\Models\Tenant::current(), auth()->user(), 'billing'))
                                @if($ipdBill)
                                    <a href="{{ route('bills.show', $ipdBill) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                                        <i class="fas fa-eye mr-1"></i>View Draft Bill
                                    </a>
                                    <a href="{{ route('bills.edit', $ipdBill) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-sm border border-medical-blue text-medical-blue bg-white rounded-lg hover:bg-blue-50">
                                        <i class="fas fa-edit mr-1"></i>Add Charges
                                    </a>
                                    <a href="{{ route('bills.print', $ipdBill) }}" target="_blank"
                                       class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50">
                                        <i class="fas fa-print mr-1"></i>Print Draft
                                    </a>
                                    <a href="{{ route('bills.print', ['bill' => $ipdBill, 'interim' => 1]) }}" target="_blank"
                                       class="inline-flex items-center px-3 py-1.5 text-sm border border-purple-300 text-purple-700 bg-white rounded-lg hover:bg-purple-50">
                                        <i class="fas fa-file-alt mr-1"></i>Interim Invoice
                                    </a>
                                @elseif($finalBill)
                                    <a href="{{ route('bills.show', $finalBill) }}"
                                       class="inline-flex items-center px-3 py-1.5 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                                        <i class="fas fa-file-invoice-dollar mr-1"></i>View Final Invoice
                                    </a>
                                    <a href="{{ route('bills.print', $finalBill) }}" target="_blank"
                                       class="inline-flex items-center px-3 py-1.5 text-sm border border-gray-300 text-gray-700 bg-white rounded-lg hover:bg-gray-50">
                                        <i class="fas fa-print mr-1"></i>Print Invoice
                                    </a>
                                @else
                                    <p class="text-sm text-amber-700">No draft bill found for this admission.</p>
                                @endif
                                @endif
                            </div>

                            @if($admission->refund_amount > 0)
                                <div class="mt-4 rounded-lg bg-green-50 border border-green-200 p-3 text-sm text-green-800">
                                    <i class="fas fa-hand-holding-usd mr-1"></i>
                                    Refund issued at discharge:
                                    <strong>{{ format_currency($admission->refund_amount) }}</strong>
                                    via {{ str_replace('_', ' ', ucfirst($admission->refund_method ?? 'cash')) }}
                                    @if($admission->refunded_at)
                                        on {{ $admission->refunded_at->format('M d, Y h:i A') }}
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Advances --}}
                        <div class="bg-white border border-gray-200 rounded-lg p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                                <h4 class="text-md font-semibold text-gray-800 flex items-center">
                                    <i class="fas fa-hand-holding-usd mr-2 text-medical-blue"></i>Patient Advances
                                </h4>
                                <div class="px-3 py-1 text-sm rounded-full {{ $availableCredit >= 0 ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }} font-medium">
                                    {{ $availableCredit >= 0 ? 'Credit' : 'Due' }}: {{ format_currency(abs($availableCredit)) }}
                                </div>
                            </div>

                            @if($admission->status === 'active')
                                <form action="{{ route('visits.admission-advance', $visit) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Advance Amount</label>
                                            <input type="number" name="amount" step="0.01" min="0.01" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                                            <select name="payment_method" required
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                                                <option value="">Select</option>
                                                <option value="cash">Cash</option>
                                                <option value="card">Card</option>
                                                <option value="upi">UPI</option>
                                                <option value="bank_transfer">Bank Transfer</option>
                                                <option value="cheque">Cheque</option>
                                                <option value="insurance">Insurance</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date</label>
                                            <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Reference Number</label>
                                            <input type="text" name="reference_number" placeholder="Optional"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                        <textarea name="notes" rows="2" placeholder="Optional"
                                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"></textarea>
                                    </div>

                                    <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                        <i class="fas fa-plus mr-2"></i>Add Advance
                                    </button>
                                </form>
                            @endif

                            @if($admission->advances && $admission->advances->count() > 0)
                                <div class="mt-5">
                                    <div class="text-sm font-semibold text-gray-800 mb-2">Advance History</div>
                                    <div class="overflow-x-auto bg-white rounded-lg border border-gray-200">
                                        <table class="w-full">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @foreach($admission->advances as $adv)
                                                    <tr>
                                                        <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ $adv->payment_date->format('M d, Y') }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">{{ format_currency($adv->amount) }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-700 whitespace-nowrap">{{ str_replace('_', ' ', ucfirst($adv->payment_method)) }}</td>
                                                        <td class="px-4 py-2 text-sm text-gray-600">{{ $adv->reference_number ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if($admission->status === 'active')
                        <form action="{{ route('visits.discharge', $visit) }}" method="POST" class="bg-white border border-orange-200 rounded-lg p-6">
                            @csrf
                            <h4 class="text-lg font-semibold text-orange-800 mb-4">
                                <i class="fas fa-sign-out-alt mr-2"></i>Discharge & Finalize Bill
                            </h4>

                            @if($settlement)
                            <div class="mb-5 rounded-lg bg-orange-50 border border-orange-100 p-4">
                                <div class="text-sm font-semibold text-orange-900 mb-2">Settlement Preview</div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-orange-900">
                                    <div>Final bill total: <strong>{{ format_currency($settlement['bill_total']) }}</strong></div>
                                    <div>Advances to apply: <strong>{{ format_currency($settlement['advances_applied']) }}</strong></div>
                                    @if($settlement['refund_amount'] > 0)
                                        <div class="sm:col-span-2 text-green-800">
                                            Refund due to patient: <strong>{{ format_currency($settlement['refund_amount']) }}</strong>
                                        </div>
                                    @endif
                                    @if($settlement['amount_due'] > 0)
                                        <div class="sm:col-span-2 text-amber-800">
                                            Remaining due after advances: <strong>{{ format_currency($settlement['amount_due']) }}</strong>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endif

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Discharge Summary</label>
                                    <textarea name="discharge_summary" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>{{ old('discharge_summary') }}</textarea>
                                    @error('discharge_summary')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Discharge Notes</label>
                                    <textarea name="discharge_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">{{ old('discharge_notes') }}</textarea>
                                </div>

                                @if(($settlement['refund_amount'] ?? 0) > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Refund Method <span class="text-red-500">*</span>
                                        <span class="text-xs text-gray-500 font-normal">({{ format_currency($settlement['refund_amount']) }} to refund)</span>
                                    </label>
                                    <select name="refund_method" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                        <option value="">Select refund method</option>
                                        <option value="cash" @selected(old('refund_method') === 'cash')>Cash</option>
                                        <option value="card" @selected(old('refund_method') === 'card')>Card</option>
                                        <option value="upi" @selected(old('refund_method') === 'upi')>UPI</option>
                                        <option value="bank_transfer" @selected(old('refund_method') === 'bank_transfer')>Bank Transfer</option>
                                        <option value="cheque" @selected(old('refund_method') === 'cheque')>Cheque</option>
                                    </select>
                                    @error('refund_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                @endif

                                @if(($settlement['amount_due'] ?? 0) > 0)
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-amber-50 border border-amber-100 rounded-lg">
                                    <div class="sm:col-span-2 text-sm text-amber-800">
                                        Optional: collect part/all of the remaining due ({{ format_currency($settlement['amount_due']) }}) now.
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional Payment</label>
                                        <input type="number" name="additional_payment_amount" step="0.01" min="0" max="{{ $settlement['amount_due'] }}"
                                               value="{{ old('additional_payment_amount') }}"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                        @error('additional_payment_amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                                        <select name="additional_payment_method"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                            <option value="">Select</option>
                                            <option value="cash" @selected(old('additional_payment_method') === 'cash')>Cash</option>
                                            <option value="card" @selected(old('additional_payment_method') === 'card')>Card</option>
                                            <option value="upi" @selected(old('additional_payment_method') === 'upi')>UPI</option>
                                            <option value="bank_transfer" @selected(old('additional_payment_method') === 'bank_transfer')>Bank Transfer</option>
                                            <option value="cheque" @selected(old('additional_payment_method') === 'cheque')>Cheque</option>
                                            <option value="insurance" @selected(old('additional_payment_method') === 'insurance')>Insurance</option>
                                        </select>
                                        @error('additional_payment_method')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                @endif

                                <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-lg hover:bg-orange-700"
                                        data-confirm="Discharge patient and finalize the IPD bill?" data-confirm-title="Discharge patient" data-confirm-text="Discharge" data-confirm-variant="danger">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Discharge & Finalize Invoice
                                </button>
                            </div>
                        </form>
                        @endif
                    </div>
                @endif
