/**
 * In-app confirm dialog — Promise-based replacement for window.confirm().
 *
 * Usage:
 *   const ok = await confirmDialog({
 *     title: 'Import categories',
 *     message: 'Import "file.csv"?',
 *     detail: 'Existing rows with the same code will be updated.',
 *     confirmText: 'Import',
 *     cancelText: 'Cancel',
 *   });
 */

const CONFIRM_BUTTON_CLASSES = {
    primary: 'bg-medical-blue hover:bg-blue-700 focus:ring-medical-blue',
    success: 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
    danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
};

function normalizeOptions(options) {
    if (typeof options === 'string') {
        return { message: options };
    }

    return options ?? {};
}

function confirmDialog(options) {
    const {
        title = 'Confirm',
        message = 'Are you sure?',
        detail = '',
        confirmText = 'Confirm',
        cancelText = 'Cancel',
        variant = 'primary',
    } = normalizeOptions(options);

    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[10000] flex items-center justify-center p-4';
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-labelledby', 'confirm-dialog-title');
        overlay.setAttribute('aria-describedby', 'confirm-dialog-message');

        const backdrop = document.createElement('div');
        backdrop.className = 'absolute inset-0 bg-gray-900/50 transition-opacity';

        const panel = document.createElement('div');
        panel.className = 'relative w-full max-w-md bg-white rounded-lg shadow-xl transform transition-all';

        const confirmClasses = CONFIRM_BUTTON_CLASSES[variant] ?? CONFIRM_BUTTON_CLASSES.primary;

        panel.innerHTML = `
            <div class="px-6 pt-6 pb-4">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center">
                        <i class="fas fa-file-upload text-medical-blue"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 id="confirm-dialog-title" class="text-lg font-semibold text-gray-900">${escapeHtml(title)}</h3>
                        <p id="confirm-dialog-message" class="mt-2 text-sm text-gray-600 leading-relaxed">${escapeHtml(message)}</p>
                        ${detail ? `<p class="mt-2 text-xs text-gray-500 leading-relaxed">${escapeHtml(detail)}</p>` : ''}
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 rounded-b-lg flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" data-confirm-cancel
                    class="w-full sm:w-auto px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-white focus:outline-none focus:ring-2 focus:ring-gray-300">
                    ${escapeHtml(cancelText)}
                </button>
                <button type="button" data-confirm-accept
                    class="w-full sm:w-auto px-4 py-2 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 ${confirmClasses}">
                    ${escapeHtml(confirmText)}
                </button>
            </div>
        `;

        overlay.appendChild(backdrop);
        overlay.appendChild(panel);
        document.body.appendChild(overlay);
        document.body.classList.add('overflow-hidden');

        const acceptButton = panel.querySelector('[data-confirm-accept]');
        const cancelButton = panel.querySelector('[data-confirm-cancel]');

        let settled = false;

        const close = (result) => {
            if (settled) {
                return;
            }

            settled = true;
            document.removeEventListener('keydown', onKeyDown);
            document.body.classList.remove('overflow-hidden');
            overlay.remove();
            resolve(result);
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                close(false);
            }
        };

        acceptButton.addEventListener('click', () => close(true));
        cancelButton.addEventListener('click', () => close(false));
        backdrop.addEventListener('click', () => close(false));
        document.addEventListener('keydown', onKeyDown);

        requestAnimationFrame(() => acceptButton.focus());
    });
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

window.confirmDialog = confirmDialog;

export default confirmDialog;
