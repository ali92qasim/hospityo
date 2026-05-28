@php
    $currentTenant = \App\Models\Tenant::current();
    $sidebarMenu   = app(\App\Services\SidebarService::class)->build(auth()->user(), $currentTenant);
@endphp

<aside id="sidebar" class="fixed left-0 top-0 w-64 h-full bg-white shadow-lg border-r border-gray-200 overflow-y-auto z-40 -translate-x-full lg:translate-x-0 transition-transform duration-300">
    <div class="p-4 border-b border-gray-200">
        <div class="flex items-center px-4 py-3">
            <div class="bg-medical-blue rounded-lg p-2 flex items-center justify-center mr-3">
                <i class="fas fa-hospital text-white text-lg"></i>
            </div>
            <h1 class="text-xl font-bold text-medical-blue">Hospityo</h1>
        </div>
    </div>

    <nav class="mt-6 pb-6">
        <ul class="space-y-2 px-4">
            @foreach($sidebarMenu as $menuItem)

                @if($menuItem['type'] === 'link')
                    {{-- Standalone link --}}
                    @php
                        $isActive = collect($menuItem['patterns'])->contains(fn($p) => request()->routeIs($p));
                    @endphp
                    <li {{ $loop->first ? '' : 'class="pt-1"' }}>
                        <a href="{{ route($menuItem['route']) }}"
                           class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ $isActive ? 'bg-medical-light text-medical-blue' : '' }}">
                            <i class="fas {{ $menuItem['icon'] }} mr-3 w-5"></i>
                            <span>{{ $menuItem['label'] }}</span>
                        </a>
                    </li>

                @elseif($menuItem['type'] === 'group')
                    {{-- Collapsible group --}}
                    @php
                        $groupActive = collect($menuItem['patterns'])->contains(fn($p) => request()->routeIs($p));
                    @endphp
                    <li class="pt-4">
                        <button onclick="toggleSubmenu('{{ $menuItem['id'] }}')"
                                class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hover:text-gray-700 transition-colors">
                            <span>{{ $menuItem['label'] }}</span>
                            <i id="{{ $menuItem['id'] }}-icon"
                               class="fas fa-chevron-down text-xs transition-transform {{ $groupActive ? 'rotate-180' : '' }}"></i>
                        </button>
                    </li>
                    <div id="{{ $menuItem['id'] }}-submenu"
                         class="space-y-1 {{ $groupActive ? '' : 'hidden' }}">
                        @foreach($menuItem['items'] as $child)
                            @php
                                $childActive = collect($child['patterns'])->contains(fn($p) => request()->routeIs($p));
                            @endphp
                            <li>
                                <a href="{{ route($child['route']) }}"
                                   class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ $childActive ? 'bg-medical-light text-medical-blue' : '' }}">
                                    <i class="fas {{ $child['icon'] }} mr-3 text-xs w-5"></i>
                                    <span>{{ $child['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </div>

                @endif
            @endforeach

            {{-- Subscription (always at bottom for admins) --}}
            @if($currentTenant && auth()->user()->hasAnyRole(['Super Admin', 'Hospital Administrator']))
                @if($currentTenant->plan)
                <li class="pt-6 mt-4 border-t border-gray-200">
                    <a href="{{ route('subscription.index') }}"
                       class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('subscription.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-crown mr-3 w-5 text-yellow-500"></i>
                        <div class="min-w-0 flex-1">
                            <span class="block text-sm">Subscription</span>
                            <span class="block text-xs text-gray-400 truncate">{{ $currentTenant->plan->name }} Plan</span>
                        </div>
                    </a>
                </li>
                @else
                <li class="pt-6 mt-4 border-t border-gray-200">
                    <a href="{{ route('subscription.index') }}"
                       class="flex items-center px-4 py-3 rounded-lg bg-yellow-50 text-yellow-700 hover:bg-yellow-100 transition-colors">
                        <i class="fas fa-arrow-up mr-3 w-5"></i>
                        <div class="min-w-0 flex-1">
                            <span class="block text-sm font-medium">Upgrade Plan</span>
                            <span class="block text-xs text-yellow-600">No plan selected</span>
                        </div>
                    </a>
                </li>
                @endif
            @endif
        </ul>
    </nav>
</aside>

<script>
function toggleSubmenu(menuId) {
    const submenu = document.getElementById(menuId + '-submenu');
    const icon    = document.getElementById(menuId + '-icon');
    if (!submenu) return;
    if (submenu.classList.contains('hidden')) {
        submenu.classList.remove('hidden');
        if (icon) icon.classList.add('rotate-180');
    } else {
        submenu.classList.add('hidden');
        if (icon) icon.classList.remove('rotate-180');
    }
}
</script>
