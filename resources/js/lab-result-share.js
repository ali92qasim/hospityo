/**
 * Lab result share helpers:
 * - Copy report verify-link
 * - Confirm finalize on verify form
 *
 * WhatsApp opens via a normal <a href="...?redirect=1"> so browsers do not block it.
 */

document.addEventListener('DOMContentLoaded', function () {
    const verifyForm = document.getElementById('verify-result-form');
    if (verifyForm) {
        verifyForm.addEventListener('submit', function (event) {
            const button = verifyForm.querySelector('[data-confirm]');
            const message = button?.dataset.confirm;
            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    }

    const copyBtn = document.getElementById('copy-report-link');
    if (!copyBtn) {
        return;
    }

    let lastShareUrl = '';

    copyBtn.addEventListener('click', async function () {
        if (!lastShareUrl) {
            const loaded = await loadSharePayload(copyBtn.dataset.shareUrl);
            if (!loaded?.share_url) {
                notify('Report link is not available yet.', 'error');
                return;
            }
            lastShareUrl = loaded.share_url;
        }

        try {
            await navigator.clipboard.writeText(lastShareUrl);
            notify('Report link copied.', 'success');
        } catch (e) {
            notify('Could not copy link. Please copy it manually.', 'error');
        }
    });

    async function loadSharePayload(endpoint) {
        if (!endpoint) {
            notify('Share URL is not configured.', 'error');
            return null;
        }

        copyBtn.disabled = true;

        try {
            const response = await fetch(endpoint, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (!response.ok) {
                notify(data.message || 'Unable to prepare report link.', 'error');
                return null;
            }

            return data;
        } catch (e) {
            notify('Unable to prepare report link.', 'error');
            return null;
        } finally {
            copyBtn.disabled = false;
        }
    }

    function notify(message, type) {
        if (window.Toast && typeof window.Toast[type] === 'function') {
            window.Toast[type](message);
            return;
        }

        alert(message);
    }
});
