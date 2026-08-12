@if(session('warning'))
    <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg flex items-start">
        <i class="fas fa-exclamation-triangle mr-3 mt-0.5"></i>
        <span>{{ session('warning') }}</span>
    </div>
@endif
