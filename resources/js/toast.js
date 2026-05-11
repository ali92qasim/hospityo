/**
 * Toast notification system — used globally across the admin panel.
 *
 * Usage (from any JS file or inline script):
 *   window.Toast.success('15 investigations imported.')
 *   window.Toast.error('Something went wrong.')
 *   window.Toast.info('Processing in background…')
 *   window.Toast.warning('3 rows had errors.')
 *
 * A toast returned by .info() / .success() / etc. can be dismissed early:
 *   const t = window.Toast.info('Working…', 0)   // 0 = no auto-dismiss
 *   t.dismiss()
 */

const ICONS = {
    success: `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                   viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                   d="M5 13l4 4L19 7"/></svg>`,
    error:   `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                   viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                   d="M6 18L18 6M6 6l12 12"/></svg>`,
    warning: `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                   viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                   d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>`,
    info:    `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2"
                   viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round"
                   d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/></svg>`,
};

const COLORS = {
    success: 'bg-green-600',
    error:   'bg-red-600',
    warning: 'bg-yellow-500',
    info:    'bg-blue-600',
};

// Spinner used for persistent info toasts (e.g. "processing…")
const SPINNER = `<svg class="w-5 h-5 flex-shrink-0 animate-spin" fill="none" viewBox="0 0 24 24">
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
</svg>`;

function getContainer() {
    let el = document.getElementById('toast-container');
    if (!el) {
        el = document.createElement('div');
        el.id = 'toast-container';
        el.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-2 w-80 max-w-[calc(100vw-2rem)]';
        document.body.appendChild(el);
    }
    return el;
}

/**
 * Show a toast.
 *
 * @param {string}  message
 * @param {'success'|'error'|'warning'|'info'} type
 * @param {number}  duration  ms before auto-dismiss; 0 = never auto-dismiss
 * @param {boolean} spinner   show a spinner instead of the type icon
 * @returns {{ dismiss: Function, update: Function }}
 */
function show(message, type = 'info', duration = 4000, spinner = false) {
    const container = getContainer();
    const color     = COLORS[type] ?? COLORS.info;
    const icon      = spinner ? SPINNER : (ICONS[type] ?? ICONS.info);

    const el = document.createElement('div');
    el.className = [
        color,
        'text-white rounded-lg shadow-lg px-4 py-3 flex items-start gap-3',
        'transform transition-all duration-300 translate-x-full opacity-0',
    ].join(' ');

    el.innerHTML = `
        <span class="icon-slot mt-0.5">${icon}</span>
        <span class="message-slot flex-1 text-sm leading-snug">${message}</span>
        <button class="flex-shrink-0 opacity-70 hover:opacity-100 ml-1 mt-0.5" aria-label="Dismiss">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    `;

    container.appendChild(el);

    // Slide in
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            el.classList.remove('translate-x-full', 'opacity-0');
        });
    });

    let timer = null;

    const dismiss = () => {
        clearTimeout(timer);
        el.classList.add('opacity-0', 'translate-x-full');
        el.addEventListener('transitionend', () => el.remove(), { once: true });
    };

    const update = (newMessage, newType, newSpinner = false) => {
        clearTimeout(timer);
        const newColor  = COLORS[newType ?? type] ?? color;
        const newIcon   = newSpinner ? SPINNER : (ICONS[newType ?? type] ?? ICONS.info);

        // Swap colour classes
        Object.values(COLORS).forEach(c => el.classList.remove(c));
        el.classList.add(newColor);

        el.querySelector('.icon-slot').innerHTML    = newIcon;
        el.querySelector('.message-slot').innerHTML = newMessage;

        if (duration > 0) {
            timer = setTimeout(dismiss, duration);
        }
    };

    el.querySelector('button').addEventListener('click', dismiss);

    if (duration > 0) {
        timer = setTimeout(dismiss, duration);
    }

    return { dismiss, update };
}

const Toast = {
    success: (msg, duration = 5000)              => show(msg, 'success', duration),
    error:   (msg, duration = 7000)              => show(msg, 'error',   duration),
    warning: (msg, duration = 6000)              => show(msg, 'warning', duration),
    info:    (msg, duration = 4000)              => show(msg, 'info',    duration),
    loading: (msg, duration = 0)                 => show(msg, 'info',    duration, true),
};

window.Toast = Toast;

export default Toast;
