/**
 * Client-side idle timer for HIPAA automatic logoff UX.
 * Server middleware remains authoritative; this only improves walk-away coverage.
 */

const ACTIVITY_EVENTS = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'];

function readConfig() {
    const cfg = window.appConfig?.sessionIdle;
    if (!cfg || !cfg.logoutUrl || !cfg.timeoutMinutes) {
        return null;
    }

    const timeoutMs = Math.max(1, Number(cfg.timeoutMinutes)) * 60 * 1000;
    const warningMs = Math.max(10, Number(cfg.warningSeconds) || 60) * 1000;

    return {
        timeoutMs,
        warningMs: Math.min(warningMs, timeoutMs - 1000),
        logoutUrl: cfg.logoutUrl,
        csrf: cfg.csrf || window.csrf || document.querySelector('meta[name="csrf-token"]')?.content,
    };
}

function postLogout(logoutUrl, csrf) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = logoutUrl;
    form.style.display = 'none';

    const token = document.createElement('input');
    token.type = 'hidden';
    token.name = '_token';
    token.value = csrf || '';
    form.appendChild(token);

    document.body.appendChild(form);
    form.submit();
}

function initSessionIdle() {
    try {
        const config = readConfig();
        const warningEl = document.getElementById('session-idle-warning');
        if (!config || !warningEl) {
            return;
        }

        const countdownEl = document.getElementById('session-idle-countdown');
        const stayBtn = document.getElementById('session-idle-stay');
        const logoutBtn = document.getElementById('session-idle-logout-now');

        let lastActivity = Date.now();
        let warningVisible = false;
        let logoutStarted = false;
        let countdownTimer = null;

        const hideWarning = () => {
            warningVisible = false;
            warningEl.classList.add('hidden');
            warningEl.classList.remove('flex');
            warningEl.setAttribute('aria-hidden', 'true');
            if (countdownTimer) {
                clearInterval(countdownTimer);
                countdownTimer = null;
            }
        };

        const showWarning = () => {
            if (warningVisible || logoutStarted) {
                return;
            }
            warningVisible = true;
            warningEl.classList.remove('hidden');
            warningEl.classList.add('flex');
            warningEl.setAttribute('aria-hidden', 'false');

            const endsAt = lastActivity + config.timeoutMs;
            const tick = () => {
                const remaining = Math.max(0, Math.ceil((endsAt - Date.now()) / 1000));
                if (countdownEl) {
                    countdownEl.textContent = String(remaining);
                }
                if (remaining <= 0) {
                    forceLogout();
                }
            };
            tick();
            countdownTimer = setInterval(tick, 1000);
        };

        const forceLogout = () => {
            if (logoutStarted) {
                return;
            }
            logoutStarted = true;
            hideWarning();
            try {
                postLogout(config.logoutUrl, config.csrf);
            } catch (e) {
                window.location.href = config.logoutUrl;
            }
        };

        const markActivity = () => {
            if (logoutStarted) {
                return;
            }
            lastActivity = Date.now();
            if (warningVisible) {
                hideWarning();
            }
        };

        ACTIVITY_EVENTS.forEach((eventName) => {
            document.addEventListener(eventName, markActivity, { passive: true });
        });

        stayBtn?.addEventListener('click', (event) => {
            event.preventDefault();
            markActivity();
        });

        logoutBtn?.addEventListener('click', (event) => {
            event.preventDefault();
            forceLogout();
        });

        setInterval(() => {
            try {
                if (logoutStarted) {
                    return;
                }
                const idleFor = Date.now() - lastActivity;
                if (idleFor >= config.timeoutMs) {
                    forceLogout();
                    return;
                }
                if (idleFor >= config.timeoutMs - config.warningMs) {
                    showWarning();
                }
            } catch (e) {
                // Keep the page usable if the idle UI fails.
            }
        }, 1000);
    } catch (e) {
        // Never block the app if idle tracking fails to start.
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSessionIdle);
} else {
    initSessionIdle();
}
