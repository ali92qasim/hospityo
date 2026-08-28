/**
 * Lab result share helpers:
 * - Copy report verify-link
 *
 * WhatsApp opens via a normal <a href="...?redirect=1"> so browsers do not block it.
 * Verify/finalize confirmation is handled globally by confirm-dialog.js.
 */

document.addEventListener('DOMContentLoaded', function () {
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
        }
    }
});
