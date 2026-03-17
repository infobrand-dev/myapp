<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-user-presence]').forEach((container) => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const heartbeatUrl = container.dataset.heartbeatUrl;
        const statusUrl = container.dataset.statusUrl;
        const select = container.querySelector('[data-user-presence-select]');
        const badge = container.querySelector('[data-user-presence-badge]');
        let isHidden = document.hidden;

        const badgeClass = (status) => {
            switch (status) {
                case 'online': return 'bg-green-lt text-green';
                case 'away': return 'bg-yellow-lt text-yellow';
                case 'busy': return 'bg-red-lt text-red';
                default: return 'bg-secondary-lt text-secondary';
            }
        };

        const updateBadge = (status) => {
            if (!badge || !status) return;
            badge.className = `badge ${badgeClass(status)}`;
            badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        };

        const postJson = async (url, payload) => {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error('request failed');
            }

            return response.json();
        };

        const heartbeat = async () => {
            if (isHidden || !heartbeatUrl) return;
            try {
                const data = await postJson(heartbeatUrl, {});
                updateBadge(data?.status);
            } catch (_) {
                // ignore transient heartbeat failures
            }
        };

        select?.addEventListener('change', async () => {
            try {
                const data = await postJson(statusUrl, { status: select.value });
                updateBadge(data?.status);
            } catch (_) {
                // ignore transient update failures
            }
        });

        document.addEventListener('visibilitychange', () => {
            isHidden = document.hidden;
            if (!isHidden) {
                heartbeat();
            }
        });

        heartbeat();
        window.setInterval(heartbeat, 30000);
    });
});
</script>
