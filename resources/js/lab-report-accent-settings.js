document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-lab-report-accent-settings]');
    if (!root) {
        return;
    }

    const picker = document.getElementById('lab-report-accent-picker');
    const hexInput = document.getElementById('lab-report-accent-hex');
    const banner = document.getElementById('lab-report-accent-contrast');
    const previews = document.getElementById('lab-report-accent-previews');
    const colorFrame = document.getElementById('lab-report-accent-preview-color');
    const bwFrame = document.getElementById('lab-report-accent-preview-bw');

    if (!picker || !hexInput || !banner) {
        return;
    }

    const HEX_RE = /^#[0-9A-Fa-f]{6}$/;
    const MIN_RATIO = 3.0;
    const PREVIEW_DEBOUNCE_MS = 250;
    let previewTimer = null;

    function channel(c) {
        return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
    }

    function ratioAgainstWhite(hex) {
        const value = String(hex || '').trim().toUpperCase();
        if (!HEX_RE.test(value)) {
            return 0;
        }

        const r = parseInt(value.slice(1, 3), 16) / 255;
        const g = parseInt(value.slice(3, 5), 16) / 255;
        const b = parseInt(value.slice(5, 7), 16) / 255;
        const L = channel(r) * 0.2126 + channel(g) * 0.7152 + channel(b) * 0.0722;
        const Lwhite = 1.0;
        const lighter = Math.max(L, Lwhite);
        const darker = Math.min(L, Lwhite);

        return (lighter + 0.05) / (darker + 0.05);
    }

    function normalizeHex(raw) {
        let value = String(raw || '').trim();
        if (!value.startsWith('#')) {
            value = `#${value}`;
        }

        return value.toUpperCase();
    }

    function applyBanner(hex) {
        const ratio = ratioAgainstWhite(hex);
        const fails = ratio < MIN_RATIO;
        const display = Math.round(ratio * 10) / 10;
        const okClass = banner.dataset.okClass || '';
        const warnClass = banner.dataset.warnClass || '';

        banner.className = `rounded-lg border px-3 py-2 text-xs ${fails ? warnClass : okClass}`;

        if (fails) {
            banner.textContent =
                `Contrast vs white page: ${display}:1 — this accent may be nearly invisible on screen and as a pale gray when printed in black & white. Consider a darker color. You can still save.`;
        } else {
            banner.textContent =
                `Contrast vs white page: ${display}:1 — OK for chrome (UI graphics threshold ≈ 3:1).`;
        }
    }

    function previewUrlFor(hex) {
        const base = previews?.dataset.previewUrl;
        if (!base) {
            return null;
        }

        const url = new URL(base, window.location.origin);
        url.searchParams.set('accent', hex);
        url.searchParams.set('embed', '1');

        return url.toString();
    }

    function loadPreviews(hex) {
        if (!HEX_RE.test(hex) || !colorFrame || !bwFrame) {
            return;
        }

        const src = previewUrlFor(hex);
        if (!src) {
            return;
        }

        colorFrame.src = src;
        bwFrame.src = src;
    }

    function schedulePreviews(hex) {
        clearTimeout(previewTimer);
        previewTimer = setTimeout(() => loadPreviews(hex), PREVIEW_DEBOUNCE_MS);
    }

    function syncFromPicker() {
        const hex = normalizeHex(picker.value);
        hexInput.value = hex;
        applyBanner(hex);
        schedulePreviews(hex);
    }

    function syncFromHex() {
        const hex = normalizeHex(hexInput.value);
        if (!HEX_RE.test(hex)) {
            return;
        }

        hexInput.value = hex;
        picker.value = hex.toLowerCase();
        applyBanner(hex);
        schedulePreviews(hex);
    }

    picker.addEventListener('input', syncFromPicker);
    picker.addEventListener('change', syncFromPicker);
    hexInput.addEventListener('input', syncFromHex);
    hexInput.addEventListener('change', syncFromHex);

    const initialHex = normalizeHex(hexInput.value || picker.value);
    applyBanner(initialHex);
    loadPreviews(initialHex);
});
