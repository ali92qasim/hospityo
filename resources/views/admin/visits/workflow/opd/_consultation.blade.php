<div class="border border-gray-200 rounded-lg mb-4">
    <button type="button" onclick="toggleAccordion('gpe')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
        <span class="font-medium text-gray-800">GPE (General Physical Examination)</span>
        <i id="gpe-icon" class="fas fa-chevron-down text-gray-500"></i>
    </button>
    <div id="gpe-content" class="hidden p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Chest</label>
                <input type="text" name="gpe_chest" value="{{ old('gpe_chest', $visit->consultation?->gpe_chest) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter chest examination findings">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Abdomen</label>
                <input type="text" name="gpe_abdomen" value="{{ old('gpe_abdomen', $visit->consultation?->gpe_abdomen) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter abdomen examination findings">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">CVS</label>
                <input type="text" name="gpe_cvs" value="{{ old('gpe_cvs', $visit->consultation?->gpe_cvs) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter CVS examination findings">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">CNS</label>
                <input type="text" name="gpe_cns" value="{{ old('gpe_cns', $visit->consultation?->gpe_cns) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter CNS examination findings">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pupils</label>
                <input type="text" name="gpe_pupils" value="{{ old('gpe_pupils', $visit->consultation?->gpe_pupils) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter pupils examination findings">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Conjunctiva</label>
                <input type="text" name="gpe_conjunctiva" value="{{ old('gpe_conjunctiva', $visit->consultation?->gpe_conjunctiva) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter conjunctiva examination findings">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nails</label>
                <input type="text" name="gpe_nails" value="{{ old('gpe_nails', $visit->consultation?->gpe_nails) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter nails examination findings">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Throat</label>
                <input type="text" name="gpe_throat" value="{{ old('gpe_throat', $visit->consultation?->gpe_throat) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter throat examination findings">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sclera</label>
                <input type="text" name="gpe_sclera" value="{{ old('gpe_sclera', $visit->consultation?->gpe_sclera) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter sclera examination findings">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">GCS</label>
                <input type="text" name="gpe_gcs" value="{{ old('gpe_gcs', $visit->consultation?->gpe_gcs) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Enter GCS score">
            </div>
        </div>
    </div>
</div>
