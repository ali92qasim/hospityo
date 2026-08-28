/**
 * In-app confirm dialog — Promise-based replacement for the native browser confirm dialog.
 *
 * Markup lives in resources/views/partials/confirm-dialog.blade.php
 *
 * Usage:
 *   const ok = await confirmDialog({
 *     title: 'Import categories',
 *     message: 'Import "file.csv"?',
 *     detail: 'Existing rows with the same code will be updated.',
 *     confirmText: 'Import',
 *     cancelText: 'Cancel',
 *     variant: 'success',
 *   });
 *
 * Declarative usage:
 *   <form method="POST" data-confirm="Delete this item?" data-confirm-variant="danger" data-confirm-text="Delete">
 *   <input type="file" data-confirm-file="Import {filename}?" data-confirm-detail="Existing rows will be updated.">
 *   <button type="button" data-confirm="Mark sample collected?" data-confirm-url="/orders/1/collect-sample" data-confirm-reload="true">
 */

const CONFIRM_BUTTON_CLASSES = {
    primary: 'bg-medical-blue hover:bg-blue-700 focus:ring-medical-blue',
    success: 'bg-green-600 hover:bg-green-700 focus:ring-green-500',
    danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
};

const ICON_WRAP_CLASSES = {
    primary: 'bg-blue-50',
    success: 'bg-green-50',
    danger: 'bg-red-50',
};

const ICON_CLASSES = {
    primary: 'fas fa-question-circle text-medical-blue',
    success: 'fas fa-check-circle text-green-600',
    danger: 'fas fa-exclamation-triangle text-red-600',
};

const BASE_ACCEPT_CLASSES = 'w-full sm:w-auto px-4 py-2 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2';
const BASE_ICON_WRAP_CLASSES = 'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center';

function normalizeOptions(options) {
    if (typeof options === 'string') {
        return { message: options };
    }

    return options ?? {};
}

function inferVariant(message) {
    const text = String(message).toLowerCase();

    if (/(delete|remove|cancel|suspend|reject|destroy)/.test(text)) {
        return 'danger';
    }

    if (/(approve|import|dispense|verify|collect|start|discharge|paid|restore|transfer)/.test(text)) {
        return 'success';
    }

    return 'primary';
}

function csrfToken() {
    return window.csrf
        || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || '';
}

function getOverlay() {
    return document.getElementById('confirm-dialog');
}

function setText(element, value) {
    if (!element) {
        return;
    }

    element.textContent = value ?? '';
}

function applyVariant(overlay, variant) {
    const resolved = CONFIRM_BUTTON_CLASSES[variant] ? variant : 'primary';
    const acceptButton = overlay.querySelector('[data-confirm-accept]');
    const iconWrap = overlay.querySelector('[data-confirm-icon-wrap]');
    const icon = overlay.querySelector('[data-confirm-icon]');

    if (acceptButton) {
        acceptButton.className = `${BASE_ACCEPT_CLASSES} ${CONFIRM_BUTTON_CLASSES[resolved]}`;
    }

    if (iconWrap) {
        iconWrap.className = `${BASE_ICON_WRAP_CLASSES} ${ICON_WRAP_CLASSES[resolved]}`;
    }

    if (icon) {
        icon.className = ICON_CLASSES[resolved];
    }
}

function showOverlay(overlay) {
    overlay.classList.remove('hidden');
    overlay.removeAttribute('hidden');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.classList.add('overflow-hidden');
}

function hideOverlay(overlay) {
    overlay.classList.add('hidden');
    overlay.setAttribute('hidden', '');
    overlay.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('overflow-hidden');
}

function confirmDialog(options) {
    const {
        title = 'Confirm',
        message = 'Are you sure?',
        detail = '',
        confirmText = 'Confirm',
        cancelText = 'Cancel',
        variant = inferVariant(message),
    } = normalizeOptions(options);

    return new Promise((resolve) => {
        const overlay = getOverlay();

        if (!overlay) {
            resolve(false);
            return;
        }

        const titleEl = overlay.querySelector('#confirm-dialog-title');
        const messageEl = overlay.querySelector('#confirm-dialog-message');
        const detailEl = overlay.querySelector('#confirm-dialog-detail');
        const acceptButton = overlay.querySelector('[data-confirm-accept]');
        const cancelButton = overlay.querySelector('[data-confirm-cancel]');
        const backdrop = overlay.querySelector('[data-confirm-backdrop]');

        if (!acceptButton || !cancelButton || !backdrop) {
            resolve(false);
            return;
        }

        setText(titleEl, title);
        setText(messageEl, message);
        setText(acceptButton, confirmText);
        setText(cancelButton, cancelText);
        applyVariant(overlay, variant);

        if (detailEl) {
            if (detail) {
                setText(detailEl, detail);
                detailEl.classList.remove('hidden');
            } else {
                setText(detailEl, '');
                detailEl.classList.add('hidden');
            }
        }

        let settled = false;

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                close(false);
            }
        };

        const onAccept = () => close(true);
        const onCancel = () => close(false);

        const close = (result) => {
            if (settled) {
                return;
            }

            settled = true;
            document.removeEventListener('keydown', onKeyDown);
            acceptButton.removeEventListener('click', onAccept);
            cancelButton.removeEventListener('click', onCancel);
            backdrop.removeEventListener('click', onCancel);
            hideOverlay(overlay);
            resolve(result);
        };

        acceptButton.addEventListener('click', onAccept);
        cancelButton.addEventListener('click', onCancel);
        backdrop.addEventListener('click', onCancel);
        document.addEventListener('keydown', onKeyDown);

        showOverlay(overlay);
        requestAnimationFrame(() => acceptButton.focus());
    });
}

function optionsFromElement(element, extras = {}) {
    let message = element.getAttribute('data-confirm')
        || element.getAttribute('data-confirm-file')
        || 'Are you sure?';

    if (extras.filename) {
        message = message.replaceAll('{filename}', extras.filename);
    }

    return {
        title: element.getAttribute('data-confirm-title') || 'Confirm',
        message,
        detail: element.getAttribute('data-confirm-detail') || '',
        confirmText: element.getAttribute('data-confirm-text') || 'Confirm',
        cancelText: element.getAttribute('data-confirm-cancel') || 'Cancel',
        variant: element.getAttribute('data-confirm-variant') || inferVariant(message),
    };
}

function confirmSource(form, submitter) {
    if (submitter?.hasAttribute('data-confirm')) {
        return submitter;
    }

    if (form.hasAttribute('data-confirm')) {
        return form;
    }

    return form.querySelector('[data-confirm]');
}

function isSubmitTrigger(element) {
    if (!element) {
        return false;
    }

    if (element.matches('form')) {
        return false;
    }

    if (element.matches('button[type="button"], input[type="button"]')) {
        return false;
    }

    return Boolean(element.closest('form'));
}

async function runConfirmedAction(trigger) {
    const url = trigger.getAttribute('data-confirm-url');

    if (url) {
        const method = (trigger.getAttribute('data-confirm-method') || 'POST').toUpperCase();

        try {
            await fetch(url, {
                method,
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
            });
        } catch (error) {
            window.Toast?.error('Request failed. Please try again.');
            return;
        }

        if (trigger.getAttribute('data-confirm-reload') === 'true') {
            window.location.reload();
        }

        return;
    }

    const toast = trigger.getAttribute('data-confirm-toast');

    if (toast) {
        const type = trigger.getAttribute('data-confirm-toast-type') || 'info';

        if (window.Toast && typeof window.Toast[type] === 'function') {
            window.Toast[type](toast);
        }

        return;
    }

    const form = trigger.matches('form') ? trigger : trigger.closest('form');

    if (form) {
        HTMLFormElement.prototype.submit.call(form);
    }
}

function bindConfirmTriggers() {
    if (bindConfirmTriggers.bound) {
        return;
    }

    bindConfirmTriggers.bound = true;

    document.addEventListener('submit', function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const source = confirmSource(form, event.submitter);

        if (!source || !source.getAttribute('data-confirm')) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        confirmDialog(optionsFromElement(source)).then(function (confirmed) {
            if (confirmed) {
                HTMLFormElement.prototype.submit.call(form);
            }
        });
    }, true);

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-confirm]');

        if (!trigger || isSubmitTrigger(trigger) || trigger.matches('input[type="file"]')) {
            return;
        }

        event.preventDefault();

        confirmDialog(optionsFromElement(trigger)).then(function (confirmed) {
            if (confirmed) {
                runConfirmedAction(trigger);
            }
        });
    });

    document.addEventListener('change', function (event) {
        const input = event.target;

        if (!(input instanceof HTMLInputElement) || input.type !== 'file') {
            return;
        }

        if (!input.hasAttribute('data-confirm-file')) {
            return;
        }

        if (!input.files || !input.files.length) {
            return;
        }

        const filename = input.files[0].name;
        const form = input.closest('form');

        confirmDialog(optionsFromElement(input, { filename })).then(function (confirmed) {
            if (confirmed && form) {
                HTMLFormElement.prototype.submit.call(form);
                return;
            }

            input.value = '';
        });
    });
}

bindConfirmTriggers();

window.confirmDialog = confirmDialog;

export default confirmDialog;
