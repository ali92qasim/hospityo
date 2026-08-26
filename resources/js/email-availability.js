export function createEmailAvailabilityChecker(url) {
    let cachedEmail = '';
    let cachedAvailable = true;
    let controller = null;

    const check = (value) => () => {
        const email = String(value ?? '').trim();

        if (email === '') {
            return Promise.resolve(true);
        }

        if (email.toLowerCase() === cachedEmail) {
            return Promise.resolve(cachedAvailable);
        }

        controller?.abort();
        controller = new AbortController();

        const separator = url.includes('?') ? '&' : '?';

        return fetch(`${url}${separator}email=${encodeURIComponent(email)}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            signal: controller.signal,
        })
            .then((response) => (response.ok ? response.json() : Promise.reject()))
            .then((payload) => {
                cachedEmail = email.toLowerCase();
                cachedAvailable = payload.available !== false;

                return cachedAvailable;
            })
            .catch(() => true);
    };

    check.isKnownAvailable = (value) => {
        const email = String(value ?? '').trim().toLowerCase();

        if (email === '' || email !== cachedEmail) {
            return true;
        }

        return cachedAvailable;
    };

    return check;
}

export function bindRuntimeEmailCheck(emailInput, isEmailAvailable, validator) {
    if (!isEmailAvailable || !emailInput) {
        return;
    }

    let debounceId;

    const checkEmailNow = () => {
        isEmailAvailable(emailInput.value)().then(() => {
            validator.revalidateField('[name="email"]');
        });
    };

    emailInput.addEventListener('input', () => {
        clearTimeout(debounceId);
        debounceId = setTimeout(checkEmailNow, 350);
    });

    emailInput.addEventListener('blur', () => {
        clearTimeout(debounceId);
        checkEmailNow();
    });
}

export const justValidateFormConfig = {
    errorFieldCssClass: 'border-red-500',
    errorLabelCssClass: 'just-validate-error-label',
    errorFieldStyle: {},
    focusInvalidField: true,
    lockForm: true,
    validateBeforeSubmitting: true,
    submitFormAutomatically: true,
};
