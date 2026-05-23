<div id="pwa-install-card" class="pwa-install-card" hidden>
    <div class="pwa-install-card__body">
        <div class="pwa-install-card__icon">
            <i class="ti ti-device-mobile"></i>
        </div>
        <div class="pwa-install-card__content">
            <div class="pwa-install-card__title">Pasang aplikasi</div>
            <div class="pwa-install-card__text" id="pwa-install-card-text">
                Simpan {{ config('app.name') }} ke layar utama agar lebih cepat dibuka.
            </div>
        </div>
    </div>
    <div class="pwa-install-card__actions">
        <button type="button" class="btn btn-sm btn-outline-secondary" id="pwa-install-later">
            Nanti
        </button>
        <button type="button" class="btn btn-sm btn-primary" id="pwa-install-action">
            Install
        </button>
    </div>
</div>

<style>
    .pwa-install-card {
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        z-index: 1080;
        width: min(24rem, calc(100vw - 2rem));
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 18px;
        background: rgba(255, 255, 255, .97);
        box-shadow: 0 18px 48px rgba(15, 23, 42, .16);
        backdrop-filter: blur(14px);
    }

    .pwa-install-card__body {
        display: flex;
        gap: .85rem;
        padding: 1rem 1rem .75rem;
    }

    .pwa-install-card__icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(37, 99, 235, .08);
        color: #1d4ed8;
        font-size: 1.2rem;
        flex: 0 0 auto;
    }

    .pwa-install-card__content {
        min-width: 0;
    }

    .pwa-install-card__title {
        font-weight: 700;
        color: #0f172a;
        margin-bottom: .2rem;
    }

    .pwa-install-card__text {
        color: #475569;
        font-size: .94rem;
        line-height: 1.45;
    }

    .pwa-install-card__actions {
        display: flex;
        justify-content: flex-end;
        gap: .6rem;
        padding: 0 1rem 1rem;
    }

    @media (max-width: 575.98px) {
        .pwa-install-card {
            right: .75rem;
            bottom: .75rem;
            width: calc(100vw - 1.5rem);
        }
    }
</style>

<script>
    (() => {
        const root = document.getElementById('pwa-install-card');
        if (!root) return;

        const textEl = document.getElementById('pwa-install-card-text');
        const actionBtn = document.getElementById('pwa-install-action');
        const laterBtn = document.getElementById('pwa-install-later');
        const storagePrefix = 'meetra:pwa:install:';
        const standalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

        if (standalone) {
            return;
        }

        const now = Date.now();
        const visitsKey = storagePrefix + 'visits';
        const shownAtKey = storagePrefix + 'shown_at';
        const dismissedAtKey = storagePrefix + 'dismissed_at';
        const acceptedAtKey = storagePrefix + 'accepted_at';
        const iosTipAtKey = storagePrefix + 'ios_tip_at';
        const VISIT_THRESHOLD = 3;
        const SHOW_COOLDOWN_MS = 7 * 24 * 60 * 60 * 1000;
        const DISMISS_COOLDOWN_MS = 21 * 24 * 60 * 60 * 1000;
        const IOS_COOLDOWN_MS = 30 * 24 * 60 * 60 * 1000;

        let deferredPrompt = null;
        let currentMode = null;

        const readNumber = (key) => Number.parseInt(localStorage.getItem(key) || '0', 10) || 0;
        const writeNumber = (key, value) => localStorage.setItem(key, String(value));
        const hideCard = () => {
            root.hidden = true;
        };

        const showCard = (mode) => {
            if (sessionStorage.getItem(storagePrefix + 'shown_session') === '1') {
                return;
            }

            currentMode = mode;
            if (mode === 'ios') {
                textEl.textContent = 'Di iPhone/iPad, buka menu Share di Safari lalu pilih Add to Home Screen.';
                actionBtn.hidden = true;
            } else {
                textEl.textContent = 'Simpan {{ config('app.name') }} ke layar utama agar lebih cepat dibuka.';
                actionBtn.hidden = false;
                actionBtn.textContent = 'Install';
            }

            root.hidden = false;
            sessionStorage.setItem(storagePrefix + 'shown_session', '1');
            writeNumber(shownAtKey, now);
        };

        const hasRecentCooldown = (key, cooldownMs) => {
            const ts = readNumber(key);
            return ts > 0 && (now - ts) < cooldownMs;
        };

        const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
        const isSafari = /^((?!chrome|android).)*safari/i.test(window.navigator.userAgent);
        const visits = readNumber(visitsKey) + 1;
        writeNumber(visitsKey, visits);

        const registerServiceWorker = () => {
            if (!('serviceWorker' in navigator)) return;
            navigator.serviceWorker.register('/sw.js').catch(() => {});
        };

        registerServiceWorker();

        if (readNumber(acceptedAtKey) > 0 || visits < VISIT_THRESHOLD || hasRecentCooldown(dismissedAtKey, DISMISS_COOLDOWN_MS) || hasRecentCooldown(shownAtKey, SHOW_COOLDOWN_MS)) {
            window.addEventListener('beforeinstallprompt', (event) => {
                event.preventDefault();
                deferredPrompt = event;
            });
            return;
        }

        window.addEventListener('beforeinstallprompt', (event) => {
            event.preventDefault();
            deferredPrompt = event;
            showCard('prompt');
        });

        window.addEventListener('appinstalled', () => {
            writeNumber(acceptedAtKey, Date.now());
            hideCard();
        });

        if (isIos && isSafari && !hasRecentCooldown(iosTipAtKey, IOS_COOLDOWN_MS)) {
            writeNumber(iosTipAtKey, now);
            window.setTimeout(() => showCard('ios'), 1200);
        }

        laterBtn.addEventListener('click', () => {
            writeNumber(dismissedAtKey, Date.now());
            hideCard();
        });

        actionBtn.addEventListener('click', async () => {
            if (!deferredPrompt) {
                hideCard();
                return;
            }

            deferredPrompt.prompt();
            try {
                const choice = await deferredPrompt.userChoice;
                if (choice?.outcome === 'accepted') {
                    writeNumber(acceptedAtKey, Date.now());
                } else {
                    writeNumber(dismissedAtKey, Date.now());
                }
            } catch (_) {
                writeNumber(dismissedAtKey, Date.now());
            } finally {
                deferredPrompt = null;
                hideCard();
            }
        });
    })();
</script>
