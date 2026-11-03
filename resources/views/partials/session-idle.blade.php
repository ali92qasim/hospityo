@php
    $webAuthenticated = auth()->check();
    $superAdminAuthenticated = auth()->guard('super_admin')->check();
@endphp

@if($webAuthenticated || $superAdminAuthenticated)
@php
    try {
        $idleMinutes = $webAuthenticated
            ? \App\Support\SessionIdleTimeout::minutes()
            : (int) config('security.idle_timeout_minutes', 30);
        $idleWarningSeconds = $webAuthenticated
            ? \App\Support\SessionIdleTimeout::warningSeconds()
            : max(10, (int) config('security.idle_warning_seconds', 60));
    } catch (\Throwable) {
        $idleMinutes = (int) config('security.idle_timeout_minutes', 30);
        $idleWarningSeconds = max(10, (int) config('security.idle_warning_seconds', 60));
    }

    $logoutUrl = $superAdminAuthenticated && ! $webAuthenticated
        ? route('super-admin.logout')
        : route('logout');
@endphp
<script>
    window.appConfig = window.appConfig || {};
    window.appConfig.sessionIdle = {
        timeoutMinutes: {{ (int) $idleMinutes }},
        warningSeconds: {{ (int) $idleWarningSeconds }},
        logoutUrl: @json($logoutUrl),
        csrf: @json(csrf_token()),
    };
</script>
<div id="session-idle-warning"
     class="fixed inset-0 z-[80] hidden items-center justify-center bg-black/40 p-4"
     role="dialog"
     aria-modal="true"
     aria-labelledby="session-idle-warning-title"
     aria-hidden="true">
    <div class="w-full max-w-md rounded-lg bg-white shadow-xl p-6">
        <h2 id="session-idle-warning-title" class="text-lg font-semibold text-gray-900">
            Session expiring soon
        </h2>
        <p class="mt-2 text-sm text-gray-600">
            You will be signed out due to inactivity in
            <span id="session-idle-countdown" class="font-semibold text-gray-900">60</span>
            seconds.
        </p>
        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button type="button"
                    id="session-idle-logout-now"
                    class="w-full sm:w-auto px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                Sign out now
            </button>
            <button type="button"
                    id="session-idle-stay"
                    class="w-full sm:w-auto px-4 py-2 rounded-lg bg-medical-blue text-white hover:bg-blue-700">
                Stay signed in
            </button>
        </div>
    </div>
</div>
@endif
