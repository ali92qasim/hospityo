@if(!$visit->triage)
    <form action="{{ route('visits.triage', $visit) }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Priority Level</label>
                <select name="priority_level" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Priority</option>
                    <option value="critical" class="text-red-600">Critical - Immediate</option>
                    <option value="urgent" class="text-orange-600">Urgent - 15 mins</option>
                    <option value="less_urgent" class="text-yellow-600">Less Urgent - 60 mins</option>
                    <option value="non_urgent" class="text-green-600">Non-Urgent - 120 mins</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Pain Scale (0-10)</label>
                <input type="number" name="pain_scale" min="0" max="10" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
            </div>
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Chief Complaint</label>
                <input type="text" name="chief_complaint" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            </div>
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Triage Notes</label>
                <textarea name="triage_notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue"></textarea>
            </div>
        </div>
        <button type="submit" class="mt-4 bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
            <i class="fas fa-exclamation-triangle mr-2"></i>Complete Triage
        </button>
    </form>
@else
    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
        <h4 class="text-lg font-medium text-red-800 mb-4">Triage Completed</h4>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="text-sm text-red-600">Priority:</span>
                <span class="ml-2 px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">{{ ucfirst(str_replace('_', ' ', $visit->triage->priority_level)) }}</span>
            </div>
            <div>
                <span class="text-sm text-red-600">Pain Scale:</span>
                <span class="ml-2 font-medium">{{ $visit->triage->pain_scale ?? 'N/A' }}/10</span>
            </div>
            <div class="col-span-2">
                <span class="text-sm text-red-600">Chief Complaint:</span>
                <p class="mt-1">{{ $visit->triage->chief_complaint }}</p>
            </div>
        </div>
    </div>
@endif
