@php
    use App\Support\PermissionRegistry;
    use Illuminate\Support\Str;

    $permissionGroups = PermissionRegistry::grouped();
    $selectedPermissions = old(
        'permissions',
        isset($role) ? $role->permissions->pluck('name')->all() : []
    );
@endphp

<div class="mb-6" data-role-permissions-form>
    <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>

    <div class="space-y-6 max-h-[32rem] overflow-y-auto border border-gray-200 rounded-lg p-4">
        @foreach($permissionGroups as $moduleSlug => $module)
            <section class="border-b border-gray-100 pb-5 last:border-b-0 last:pb-0" data-permission-module="{{ $moduleSlug }}">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-gray-800">{{ $module['label'] }}</h3>
                    <label class="flex items-center text-xs text-gray-500 cursor-pointer select-none">
                        <input type="checkbox"
                               class="role-module-select-all rounded border-gray-300 text-medical-blue focus:ring-medical-blue"
                               data-module="{{ $moduleSlug }}"
                               aria-label="Select all {{ $module['label'] }} permissions">
                        <span class="ml-2">Select all</span>
                    </label>
                </div>

                @foreach($module['groups'] as $groupSlug => $permissions)
                    <div class="mb-4 last:mb-0">
                        @if(count($module['groups']) > 1 || $groupSlug !== $moduleSlug)
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">
                                {{ Str::headline($groupSlug) }}
                            </h4>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                            @foreach($permissions as $permission)
                                <label class="flex items-start">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           value="{{ $permission }}"
                                           data-module="{{ $moduleSlug }}"
                                           class="role-permission-checkbox rounded border-gray-300 text-medical-blue focus:ring-medical-blue mt-0.5"
                                           {{ in_array($permission, $selectedPermissions, true) ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700">{{ $permission }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>
        @endforeach
    </div>

    @error('permissions')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@push('scripts')
    @vite(['resources/js/role-permissions-form.js'])
@endpush
