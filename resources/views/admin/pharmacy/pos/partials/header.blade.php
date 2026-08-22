<header class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-30">
    <div class="max-w-[1600px] mx-auto px-3 sm:px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            @include('partials.logo')
            <div>
                <h1 class="text-lg font-bold text-gray-900 leading-tight">POS Counter</h1>
                <p class="text-xs text-gray-500">Pharmacy point of sale</p>
            </div>
        </div>

        <div class="flex items-center gap-3 sm:gap-4">
            <span id="pos-clock" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-medical-blue text-white text-sm font-medium"></span>
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center gap-2 text-sm text-gray-600 hover:text-medical-blue transition-colors">
                <i class="fas fa-arrow-left text-xs"></i>
                Back to Admin
            </a>
        </div>
    </div>
</header>
