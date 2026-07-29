<div id="gpe-tab-content" class="tab-content hidden">

    @php

        $careTeam = $visit->careTeam;

        $hasCareTeam = $careTeam->isNotEmpty();

        $authDoctorOnCareTeam = $authDoctor && $careTeam->contains('doctor_id', $authDoctor->id);

        $canSubmitGpe = $hasCareTeam && (empty($authDoctor) || $authDoctorOnCareTeam);

    @endphp



    <div class="space-y-6">

        <div>

            <h4 class="text-lg font-medium text-gray-800 mb-2">Record General Physical Examination</h4>

            <p class="text-sm text-gray-600 mb-4">

                Document each examination separately. The examining doctor must be an active member of the Care Team.

            </p>



            @if(! $hasCareTeam)

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">

                    <p class="text-sm text-amber-800 mb-3">

                        <i class="fas fa-exclamation-circle mr-1"></i>

                        Add at least one doctor to the Care Team before recording GPE.

                    </p>

                    <button type="button"

                            onclick="openCareTeamTab()"

                            class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-sm">

                        <i class="fas fa-users mr-2"></i>Go to Care Team

                    </button>

                </div>

            @elseif($authDoctor && $authDoctorOnCareTeam)

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-sm text-blue-800">

                    <i class="fas fa-info-circle mr-1"></i>

                    Recording examination as <strong>Dr. {{ $authDoctor->name }}</strong>

                </div>

            @elseif($authDoctor && ! $authDoctorOnCareTeam)

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">

                    <p class="text-sm text-amber-800 mb-3">

                        <i class="fas fa-exclamation-circle mr-1"></i>

                        You are not on the care team for this admission. Ask staff to add you in the Care Team tab, or record GPE using a staff account.

                    </p>

                    <button type="button"

                            onclick="openCareTeamTab()"

                            class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 text-sm">

                        <i class="fas fa-users mr-2"></i>Go to Care Team

                    </button>

                </div>

            @endif



            @if($canSubmitGpe)

                <form action="{{ route('visits.gpe-records.store', $visit) }}" method="POST" class="bg-white border border-gray-200 rounded-lg p-6" data-save-tab="gpe-tab">

                    @csrf



                    @if(empty($authDoctor))

                        <div class="mb-4">

                            <label class="block text-sm font-medium text-gray-700 mb-2">Examining Doctor (from care team)</label>

                            <select name="doctor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>

                                <option value="">Select Doctor</option>

                                @foreach($careTeam as $member)

                                    <option value="{{ $member->doctor_id }}" @selected(old('doctor_id') == $member->doctor_id)>

                                        Dr. {{ $member->doctor->name }} - {{ $member->doctor->specialization }}

                                    </option>

                                @endforeach

                            </select>

                            @error('doctor_id')

                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>

                            @enderror

                        </div>

                    @endif



                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        <div>

                            <label class="block text-sm font-medium text-gray-700 mb-2">Chest</label>

                            <input type="text" name="gpe_chest" value="{{ old('gpe_chest') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter chest examination findings">

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-gray-700 mb-2">Abdomen</label>

                            <input type="text" name="gpe_abdomen" value="{{ old('gpe_abdomen') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter abdomen examination findings">

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-gray-700 mb-2">CVS</label>

                            <input type="text" name="gpe_cvs" value="{{ old('gpe_cvs') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter CVS examination findings">

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-gray-700 mb-2">CNS</label>

                            <input type="text" name="gpe_cns" value="{{ old('gpe_cns') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter CNS examination findings">

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-gray-700 mb-2">Pupils</label>

                            <input type="text" name="gpe_pupils" value="{{ old('gpe_pupils') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter pupils examination findings">

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-gray-700 mb-2">Conjunctiva</label>

                            <input type="text" name="gpe_conjunctiva" value="{{ old('gpe_conjunctiva') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter conjunctiva examination findings">

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-gray-700 mb-2">Nails</label>

                            <input type="text" name="gpe_nails" value="{{ old('gpe_nails') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter nails examination findings">

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-gray-700 mb-2">Throat</label>

                            <input type="text" name="gpe_throat" value="{{ old('gpe_throat') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter throat examination findings">

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-gray-700 mb-2">Sclera</label>

                            <input type="text" name="gpe_sclera" value="{{ old('gpe_sclera') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter sclera examination findings">

                        </div>

                        <div>

                            <label class="block text-sm font-medium text-gray-700 mb-2">GCS</label>

                            <input type="text" name="gpe_gcs" value="{{ old('gpe_gcs') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter GCS score">

                        </div>

                    </div>



                    <div class="mt-4">

                        <label class="block text-sm font-medium text-gray-700 mb-2">Remarks <span class="text-gray-500 font-normal">(optional)</span></label>

                        <textarea name="remarks" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Additional examination remarks...">{{ old('remarks') }}</textarea>

                        @error('remarks')

                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>

                        @enderror

                        @error('gpe_chest')

                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>

                        @enderror

                    </div>



                    <button type="submit" class="mt-4 bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">

                        <i class="fas fa-plus-circle mr-2"></i>Add GPE Record

                    </button>

                </form>

            @endif

        </div>



        <div>

            <h4 class="text-lg font-medium text-gray-800 mb-4">GPE History</h4>

            <div class="space-y-4 max-h-96 overflow-y-auto">

                @forelse($visit->ipdGpeRecords as $record)

                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">

                        <div class="flex justify-between items-start mb-3">

                            <div>

                                <h5 class="font-medium text-gray-800">{{ $record->created_at->format('M d, Y h:i A') }}</h5>

                                <p class="text-sm text-gray-500 mt-1">

                                    @if($record->doctor)

                                        Dr. {{ $record->doctor->name }}

                                    @endif

                                    @if($record->recordedBy)

                                        · recorded by {{ $record->recordedBy->name }}

                                    @endif

                                </p>

                            </div>

                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">

                            @foreach($record->systemFindings() as $label => $value)

                                @if(filled($value))

                                    <div><span class="text-medical-blue font-medium">{{ $label }}:</span> {{ $value }}</div>

                                @endif

                            @endforeach

                        </div>

                        @if(filled($record->remarks))

                            <div class="mt-3 pt-3 border-t border-gray-200 text-sm text-gray-700">

                                <span class="font-medium text-gray-600">Remarks:</span> {{ $record->remarks }}

                            </div>

                        @endif

                    </div>

                @empty

                    <p class="text-gray-500 text-center py-4">No GPE records yet for this admission.</p>

                @endforelse

            </div>

        </div>

    </div>

</div>

