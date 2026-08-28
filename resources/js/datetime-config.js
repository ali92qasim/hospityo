function phpDateToFlatpickr(phpFormat) {
    return phpFormat || 'd/m/Y';
}

export function appTimezone() {
    return window.appConfig?.timezone
        || Intl.DateTimeFormat().resolvedOptions().timeZone
        || 'UTC';
}

export function uses12Hour() {
    const format = window.appConfig?.timeFormat || 'h:i A';

    return format.includes('h');
}

export function flatpickrDateTimeConfig(extra = {}) {
    const twelve = uses12Hour();
    const dateFormat = phpDateToFlatpickr(window.appConfig?.dateFormat);

    return {
        enableTime: true,
        dateFormat: 'Y-m-d H:i',
        altInput: true,
        altFormat: twelve ? `${dateFormat} h:i K` : `${dateFormat} H:i`,
        time_24hr: !twelve,
        ...extra,
    };
}

export function flatpickrTimeConfig(extra = {}) {
    const twelve = uses12Hour();

    return {
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        altInput: true,
        altFormat: twelve ? 'h:i K' : 'H:i',
        time_24hr: !twelve,
        ...extra,
    };
}

export function fullCalendarTimeConfig() {
    const twelve = uses12Hour();
    const timeFormat = {
        hour: 'numeric',
        minute: '2-digit',
        hour12: twelve,
        ...(twelve ? { meridiem: 'short' } : {}),
    };

    return {
        timeZone: appTimezone(),
        eventTimeFormat: timeFormat,
        slotLabelFormat: timeFormat,
    };
}

if (typeof window !== 'undefined') {
    window.datetimeConfig = {
        appTimezone,
        uses12Hour,
        flatpickrDateTimeConfig,
        flatpickrTimeConfig,
        fullCalendarTimeConfig,
    };
}
