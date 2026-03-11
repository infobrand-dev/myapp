document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('[data-conversation-dashboard-card]');
    const configs = document.querySelectorAll('[data-conversation-dashboard-config]');

    cards.forEach((card, index) => {
        const configEl = configs[index];
        if (!configEl) {
            return;
        }

        let config = { openShare: 0, claimedShare: 0 };
        try {
            config = JSON.parse(configEl.textContent || '{}');
        } catch (_) {
            return;
        }

        const openBar = card.querySelector('[data-conversation-open]');
        const claimedBar = card.querySelector('[data-conversation-claimed]');

        requestAnimationFrame(() => {
            if (openBar) {
                openBar.style.width = `${Math.max(0, Math.min(100, Number(config.openShare || 0)))}%`;
            }

            if (claimedBar) {
                claimedBar.style.width = `${Math.max(0, Math.min(100, Number(config.claimedShare || 0)))}%`;
            }
        });
    });
});
