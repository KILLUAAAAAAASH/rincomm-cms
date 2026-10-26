/* Module 9: customer-scoped read-only PPPoE session monitoring. */
(() => {
    'use strict';
    const root = document.querySelector('[data-live-pppoe-status]');
    if (!root) return;

    const label = root.querySelector('[data-live-pppoe-label]');
    const description = root.querySelector('[data-live-pppoe-description]');
    const url = root.dataset.statusUrl;
    if (!label || !description || !url) return;

    const descriptions = {
        online: 'An authenticated PPPoE session is active on the configured router.',
        offline: 'No matching PPPoE session was found on the configured router.',
        unavailable: 'Live router monitoring is unavailable or not configured.'
    };
    let pending = false;

    const render = (state, checkedAt = null) => {
        const status = Object.prototype.hasOwnProperty.call(descriptions, state) ? state : 'unavailable';
        label.dataset.state = status;
        label.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        description.textContent = descriptions[status];
        if (checkedAt && (status === 'online' || status === 'offline')) {
            const time = new Date(checkedAt);
            if (!Number.isNaN(time.getTime())) {
                description.textContent += ` Checked at ${time.toLocaleTimeString()}.`;
            }
        }
    };

    const refresh = async () => {
        if (pending || document.hidden) return;
        pending = true;
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 7000);
        try {
            const response = await fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' },
                signal: controller.signal
            });
            if (!response.ok) throw new Error('Status unavailable');
            const data = await response.json();
            render(data && data.status, data && data.checked_at);
        } catch (_error) {
            render('unavailable');
        } finally {
            window.clearTimeout(timeout);
            pending = false;
        }
    };

    void refresh();
    window.setInterval(refresh, 30000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) void refresh();
    });
})();
