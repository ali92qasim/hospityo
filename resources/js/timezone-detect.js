function detectHospitalTimezone() {
    if (window.appConfig?.timezoneAutoSet !== false) {
        return;
    }

    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    const url = window.appConfig?.detectTimezoneUrl;

    if (!timezone || !url) {
        return;
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': window.appConfig?.csrf
                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || '',
        },
        body: JSON.stringify({ timezone }),
    })
        .then((response) => (response.ok ? response.json() : null))
        .then((data) => {
            if (!data) {
                return;
            }

            window.appConfig.timezoneAutoSet = true;

            if (data.timezone) {
                window.appConfig.timezone = data.timezone;
            }

            if (data.updated) {
                window.location.reload();
            }
        })
        .catch(() => {
            // Detection is best-effort; Settings can still be saved manually.
        });
}

detectHospitalTimezone();
