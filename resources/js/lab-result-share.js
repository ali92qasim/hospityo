/**
 * Lab result / order WhatsApp share — opens wa.me with verify-link message.
 * Expects a button with:
 *   data-share-url  → JSON endpoint that returns { whatsapp_url, share_url, phone }
 * Optional sibling #copy-report-link for copy-to-clipboard fallback.
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

    const shareBtn = document.getElementById('share-whatsapp-btn');
    if (!shareBtn) {
        return;
    }

    const copyBtn = document.getElementById('copy-report-link');
    let lastShareUrl = shareBtn.dataset.shareLink || '';

    if (copyBtn) {
        copyBtn.addEventListener('click', async function () {
            if (!lastShareUrl) {
                const loaded = await loadSharePayload(shareBtn.dataset.shareUrl);
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
    }

    shareBtn.addEventListener('click', async function () {
        const data = await loadSharePayload(shareBtn.dataset.shareUrl);
        if (!data) {
            return;
        }

        if (data.share_url) {
            lastShareUrl = data.share_url;
        }

        if (!data.phone) {
            notify(data.message || 'Patient mobile number is missing. Use Copy Link instead.', 'warning');
            return;
        }

        if (data.whatsapp_url) {
            window.open(data.whatsapp_url, '_blank');
        }
    });

    async function loadSharePayload(endpoint) {
        if (!endpoint) {
            notify('Share URL is not configured.', 'error');
            return null;
        }

        shareBtn.disabled = true;

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
                notify(data.message || 'Unable to share report.', 'error');
                return null;
            }

            return data;
        } catch (e) {
            notify('Unable to prepare share link.', 'error');
            return null;
        } finally {
            shareBtn.disabled = false;
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
