@extends('layouts.admin')

@section('title', 'WhatsApp Web')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Conversations</div>
            <h2 class="page-title">WhatsApp Web</h2>
        </div>
        @can('whatsapp_web.manage_settings')
        <div class="col-auto">
            <a href="{{ route('whatsappweb.settings.edit') }}" class="btn btn-outline-secondary">
                <i class="ti ti-settings me-1"></i>Settings
            </a>
        </div>
        @endcan
    </div>
</div>

{{-- ============================================================
     PANEL OFFLINE — tampil saat bridge tidak dapat dijangkau
     JS akan sembunyikan panel ini begitu bridge berhasil dikontak
     ============================================================ --}}
<div id="wa-offline-panel" class="{{ $bridgeStatus['reachable'] ? 'd-none' : '' }} mb-4">

    <div class="alert alert-danger mb-3">
        <div class="d-flex align-items-start gap-2">
            <i class="ti ti-wifi-off" style="font-size:1.2rem; margin-top:2px; color:var(--tblr-danger);"></i>
            <div>
                <div class="fw-semibold">WhatsApp Web Bridge tidak aktif</div>
                <div class="text-muted small mt-1">{{ $bridgeStatus['error'] ?? 'Bridge tidak dapat dijangkau.' }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title mb-0">
                <i class="ti ti-server me-2"></i>Background Services yang Harus Berjalan
            </h3>
        </div>
        <div class="list-group list-group-flush">

            {{-- 1. Bridge Server --}}
            <div class="list-group-item py-3">
                <div class="d-flex align-items-start gap-3">
                    <i class="ti ti-brand-whatsapp mt-1" style="font-size:1.3rem; color:var(--tblr-green);"></i>
                    <div class="flex-fill">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold">1. Node.js WhatsApp Web Bridge</span>
                            <span class="badge bg-red-lt text-red">Offline</span>
                        </div>
                        <p class="text-muted small mb-2">
                            Server Node.js yang menghubungkan WhatsApp Web (bukan API resmi) dengan aplikasi ini.
                            Harus berjalan terus-menerus di server, terpisah dari PHP/Laravel.
                        </p>
                        <div class="mb-2">
                            <div class="text-muted small mb-1">Development / pertama kali:</div>
                            <code class="d-block user-select-all bg-dark text-white px-3 py-2 rounded small">
                                cd {{ base_path('app/Modules/WhatsAppWeb/node') }} && npm install && node server.js
                            </code>
                        </div>
                        <div class="mb-2">
                            <div class="text-muted small mb-1">Production (PM2 — recommended):</div>
                            <code class="d-block user-select-all bg-dark text-white px-3 py-2 rounded small">
                                pm2 start server.js --name wa-bridge --cwd {{ base_path('app/Modules/WhatsAppWeb/node') }}
                            </code>
                        </div>
                        <div class="text-muted small">
                            Bridge berjalan di port <strong>3020</strong> secara default (ubah via env <code>WHATSAPP_WEB_PORT</code>).
                            URL yang dikonfigurasi: <code>{{ $bridgeUrl ?: '(belum diisi)' }}</code>
                        </div>
                        @if($bridgeStatus['url_missing'] ?? false)
                            <div class="mt-2">
                                <a href="{{ route('whatsappweb.settings.edit') }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-settings me-1"></i>Konfigurasi Bridge URL
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 2. Queue Worker --}}
            <div class="list-group-item py-3">
                <div class="d-flex align-items-start gap-3">
                    <i class="ti ti-stack-2 mt-1" style="font-size:1.3rem; color:var(--tblr-azure);"></i>
                    <div class="flex-fill">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold">2. Laravel Queue Worker</span>
                            <span class="badge bg-secondary-lt text-secondary">Perlu dicek manual</span>
                        </div>
                        <p class="text-muted small mb-2">
                            Memproses pengiriman pesan WhatsApp keluar (<code>SendWhatsAppWebMessage</code> job) secara
                            asynchronous. Tanpa worker ini, pesan yang dikirim agen tidak akan terkirim ke WhatsApp.
                        </p>
                        <code class="d-block user-select-all bg-dark text-white px-3 py-2 rounded small">
                            php artisan queue:work --tries=3 --timeout=120
                        </code>
                        <div class="text-muted small mt-1">Gunakan Supervisor atau PM2 agar otomatis restart jika crash.</div>
                    </div>
                </div>
            </div>

            {{-- 3. Scheduler --}}
            <div class="list-group-item py-3">
                <div class="d-flex align-items-start gap-3">
                    <i class="ti ti-clock mt-1" style="font-size:1.3rem; color:var(--tblr-azure);"></i>
                    <div class="flex-fill">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="fw-semibold">3. Laravel Scheduler (Cron)</span>
                            <span class="badge bg-secondary-lt text-secondary">Perlu dicek manual</span>
                        </div>
                        <p class="text-muted small mb-2">
                            Menjalankan tugas terjadwal seperti pengecekan kesehatan koneksi WhatsApp setiap 10 menit.
                            Harus dipanggil setiap menit oleh cron di server.
                        </p>
                        <code class="d-block user-select-all bg-dark text-white px-3 py-2 rounded small">
                            * * * * * php {{ base_path() }}/artisan schedule:run >> /dev/null 2>&1
                        </code>
                    </div>
                </div>
            </div>

        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <p class="text-muted small mb-0" id="wa-offline-hint">
                Setelah bridge berjalan, status akan terdeteksi otomatis — atau klik <strong>Cek Ulang</strong>.
            </p>
            <div class="d-flex gap-2">
                @can('whatsapp_web.manage_settings')
                <a href="{{ route('whatsappweb.settings.edit') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-settings me-1"></i>Settings
                </a>
                <button type="button" class="btn btn-success btn-sm" id="wa-start-btn"
                    data-start-url="{{ route('whatsappweb.bridge.start') }}"
                    data-stop-url="{{ route('whatsappweb.bridge.stop') }}">
                    <i class="ti ti-player-play me-1"></i>Start Bridge
                </button>
                @endcan
                <button type="button" class="btn btn-primary btn-sm" id="wa-recheck-btn">
                    <i class="ti ti-refresh me-1"></i>Cek Ulang
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================================================
     MAIN PANEL — state connect (QR) dan state chat (connected)
     ============================================================ --}}
<div id="whatsapp-web"
    data-bridge-url="{{ $bridgeUrl }}"
    data-bridge-token="{{ $bridgeToken ?? '' }}"
    data-client-id="{{ auth()->id() }}"
    data-send-url-template="{{ route('whatsappweb.chats.messages.send', ['chatId' => '__CHAT_ID__']) }}"
    data-sync-url-template="{{ route('whatsappweb.chats.sync', ['chatId' => '__CHAT_ID__']) }}"
    data-sync-active-url="{{ route('whatsappweb.sync.active') }}">

    {{-- QR / Connect state --}}
    <div id="wa-connect" class="row g-3">
        <div class="col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">WhatsApp Web</h3>
                    <button id="wa-logout" class="btn btn-sm btn-outline-danger" type="button">Logout</button>
                </div>
                <div class="card-body text-center">
                    <div id="wa-status" class="alert alert-info">Menghubungkan ke WhatsApp...</div>
                    <div class="my-3">
                        <img id="wa-qr" src="" alt="QR Code" class="img-fluid border rounded" style="max-width: 280px; display:none;">
                    </div>
                    <button id="wa-refresh" class="btn btn-outline-primary" type="button">Refresh Status</button>
                    <p class="text-muted mt-3 mb-0 small">Scan QR untuk login. Pastikan bridge server berjalan.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Chat state --}}
    <div id="wa-chat" class="d-none">
        <div class="card mb-3">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <div class="text-secondary text-uppercase fw-bold small">WhatsApp Connected</div>
                    <div id="wa-account" class="fw-bold"></div>
                </div>
                <div class="d-flex gap-2">
                    <button id="wa-sync-active" class="btn btn-outline-primary btn-sm" type="button">Sync Semua Chat Aktif</button>
                    <button id="wa-logout-2" class="btn btn-outline-danger btn-sm" type="button">Logout</button>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center w-100">
                    <h3 class="card-title mb-0">Chat</h3>
                    <div class="d-flex gap-2 align-items-center">
                        <label class="small text-muted mb-0" for="wa-sync-limit">Histori</label>
                        <input type="number" id="wa-sync-limit" class="form-control form-control-sm" value="150" min="1" max="500" style="width: 90px;">
                        <button id="wa-sync-selected" class="btn btn-outline-primary btn-sm" type="button" disabled>Sync Chat Ini</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div id="wa-sync-feedback" class="alert d-none mb-3"></div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="list-group" id="wa-chat-list" style="max-height: 70vh; overflow:auto;">
                            <div class="text-muted small p-2">Memuat chat...</div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="border rounded p-3 mb-3" id="wa-messages" style="height: 55vh; overflow:auto; background:#f8f9fa;">
                            <div class="text-muted small">Pilih chat untuk melihat pesan.</div>
                        </div>
                        <form id="wa-send-form" class="d-flex gap-2">
                            <input type="text" id="wa-message-input" class="form-control" placeholder="Ketik pesan..." disabled>
                            <button class="btn btn-primary" type="submit" disabled>Kirim</button>
                        </form>
                        <div class="text-muted small mt-2">Catatan: tampilan ringkas, bukan clone penuh WhatsApp Web.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ $bridgeUrl }}/socket.io/socket.io.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('whatsapp-web');
    const bridgeUrl = container?.dataset.bridgeUrl;
    const bridgeToken = container?.dataset.bridgeToken || '';
    const clientId = container?.dataset.clientId || 'default';
    const sendUrlTemplate = container?.dataset.sendUrlTemplate || '';
    const syncUrlTemplate = container?.dataset.syncUrlTemplate || '';
    const syncActiveUrl = container?.dataset.syncActiveUrl || '';
    const statusEl = document.getElementById('wa-status');
    const qrEl = document.getElementById('wa-qr');
    const refreshBtn = document.getElementById('wa-refresh');
    const recheckBtn = document.getElementById('wa-recheck-btn');
    const offlinePanel = document.getElementById('wa-offline-panel');
    const logoutBtn = document.getElementById('wa-logout');
    const logoutBtn2 = document.getElementById('wa-logout-2');
    const chatListEl = document.getElementById('wa-chat-list');
    const messagesEl = document.getElementById('wa-messages');
    const sendForm = document.getElementById('wa-send-form');
    const messageInput = document.getElementById('wa-message-input');
    const sendButton = sendForm.querySelector('button');
    const syncFeedback = document.getElementById('wa-sync-feedback');
    const syncLimitInput = document.getElementById('wa-sync-limit');
    const syncSelectedBtn = document.getElementById('wa-sync-selected');
    const syncActiveBtn = document.getElementById('wa-sync-active');
    const sectionConnect = document.getElementById('wa-connect');
    const sectionChat = document.getElementById('wa-chat');
    const waAccount = document.getElementById('wa-account');
    let activeChatId = null;

    const api = (path, options = {}) => fetch(`${bridgeUrl}${path}${path.includes('?') ? '&' : '?'}clientId=${clientId}`, {
        headers: {
            'Content-Type': 'application/json',
            ...(bridgeToken ? { 'X-Bridge-Token': bridgeToken } : {}),
        },
        ...options,
    });

    const setStatus = (type, text) => {
        statusEl.className = `alert alert-${type}`;
        statusEl.textContent = text;
    };

    const setSyncFeedback = (type, text) => {
        if (!syncFeedback) return;
        syncFeedback.className = `alert alert-${type} mb-3`;
        syncFeedback.textContent = text;
        syncFeedback.classList.remove('d-none');
    };

    const clearSyncFeedback = () => {
        syncFeedback?.classList.add('d-none');
    };

    const showOfflinePanel = () => offlinePanel?.classList.remove('d-none');
    const hideOfflinePanel = () => {
        offlinePanel?.classList.add('d-none');
        // Bridge came online — stop aggressive polling, resume normal 20s interval
        if (aggressivePollTimer) {
            clearInterval(aggressivePollTimer);
            aggressivePollTimer = null;
        }
    };

    // Aggressive polling after start command (3s x 20 = max 60s then back to normal)
    let aggressivePollCount = 0;
    let aggressivePollTimer = null;
    const startAggressivePoll = () => {
        aggressivePollCount = 0;
        clearInterval(aggressivePollTimer);
        aggressivePollTimer = setInterval(async () => {
            aggressivePollCount++;
            await loadStatus();
            if (aggressivePollCount >= 20) {
                clearInterval(aggressivePollTimer);
            }
        }, 3000);
    };

    const toggleState = (ready) => {
        if (ready) {
            sectionConnect.classList.add('d-none');
            sectionChat.classList.remove('d-none');
        } else {
            sectionChat.classList.add('d-none');
            sectionConnect.classList.remove('d-none');
            activeChatId = null;
            messageInput.disabled = true;
            sendButton.disabled = true;
            if (syncSelectedBtn) syncSelectedBtn.disabled = true;
            renderMessages([]);
            renderChatList([]);
        }
    };

    const renderChatList = (chats) => {
        chatListEl.innerHTML = '';
        if (!chats.length) {
            chatListEl.innerHTML = '<div class="text-muted small p-2">Belum ada chat.</div>';
            return;
        }
        chats.forEach((chat) => {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = `list-group-item list-group-item-action d-flex justify-content-between align-items-center ${chat.id === activeChatId ? 'active' : ''}`;
                const badge = chat.unreadCount ? `<span class="badge bg-azure-lt text-azure">${chat.unreadCount}</span>` : '';
                item.innerHTML = `
                    <div>
                        <div class="fw-bold">${chat.name}</div>
                        <div class="small text-muted">${chat.lastMessage || ''}</div>
                    </div>
                    ${badge}
                `;
            item.addEventListener('click', () => {
                activeChatId = chat.id;
                loadMessages(chat.id);
                renderChatList(chats);
                messageInput.disabled = false;
                sendButton.disabled = false;
                if (syncSelectedBtn) syncSelectedBtn.disabled = false;
            });
            chatListEl.appendChild(item);
        });
    };

    const renderMessages = (messages) => {
        messagesEl.innerHTML = '';
        if (!messages.length) {
            messagesEl.innerHTML = '<div class="text-muted small">Belum ada pesan.</div>';
            return;
        }
        messages.forEach((msg) => {
            const bubble = document.createElement('div');
            bubble.className = `mb-2 d-flex ${msg.fromMe ? 'justify-content-end' : 'justify-content-start'}`;
            const timeClass = msg.fromMe ? 'text-white-50' : 'text-muted';
            const typeBadge = msg.type && msg.type !== 'chat'
                ? `<span class="badge bg-azure-lt text-azure me-2">${msg.type}</span>`
                : '';
            const authorLabel = msg.author && !msg.fromMe ? `<div class="small fw-bold mb-1">${msg.author}</div>` : '';
            bubble.innerHTML = `<div class="px-3 py-2 rounded ${msg.fromMe ? 'bg-primary text-white' : 'bg-white border'}" style="max-width: 70%;">
                ${authorLabel}
                <div class="small">${typeBadge}${msg.body}</div>
                <div class="${timeClass} small mt-1">${msg.timestamp}</div>
            </div>`;
            messagesEl.appendChild(bubble);
        });
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    const handleStatusPayload = (data) => {
        if (data.ready) {
            toggleState(true);
            waAccount.textContent = data.info?.name || 'WhatsApp User';
            loadChats();
            return;
        }
        toggleState(false);
        if (data.qr) {
            setStatus('warning', 'Scan QR untuk login.');
            qrEl.src = data.qr;
            qrEl.style.display = 'inline-block';
        } else {
            setStatus('info', 'Menunggu QR code...');
            qrEl.style.display = 'none';
        }
    };

    const loadStatus = async () => {
        try {
            const response = await api('/status');
            const data = await response.json();
            hideOfflinePanel();
            handleStatusPayload(data);
        } catch (error) {
            setStatus('danger', 'Gagal terhubung ke bridge server.');
            toggleState(false);
            showOfflinePanel();
        }
    };

    const loadChats = async () => {
        try {
            const response = await api('/chats');
            const data = await response.json();
            renderChatList(data);
        } catch (error) {
            renderChatList([]);
        }
    };

    const loadMessages = async (chatId) => {
        try {
            const response = await api(`/chats/${chatId}/messages?limit=50`);
            const data = await response.json();
            renderMessages(data);
        } catch (error) {
            renderMessages([]);
        }
    };

    refreshBtn.addEventListener('click', loadStatus);
    recheckBtn?.addEventListener('click', loadStatus);

    const startBtn = document.getElementById('wa-start-btn');
    const hintEl   = document.getElementById('wa-offline-hint');

    startBtn?.addEventListener('click', async () => {
        const startUrl = startBtn.dataset.startUrl;
        startBtn.disabled = true;
        startBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Starting...';
        if (hintEl) hintEl.textContent = 'Mengirim perintah start ke server...';

        try {
            const response = await fetch(startUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
            });
            const data = await response.json();

            if (data.ok) {
                if (hintEl) hintEl.textContent = data.message + ' Polling setiap 3 detik...';
                startAggressivePoll();
            } else {
                if (hintEl) hintEl.innerHTML = '<span class="text-danger"><i class="ti ti-alert-circle me-1"></i>' + data.message + '</span>';
            }
        } catch (e) {
            if (hintEl) hintEl.innerHTML = '<span class="text-danger">Gagal menghubungi server.</span>';
        } finally {
            startBtn.disabled = false;
            startBtn.innerHTML = '<i class="ti ti-player-play me-1"></i>Start Bridge';
        }
    });

    [logoutBtn, logoutBtn2].forEach(btn => btn?.addEventListener('click', async () => {
        await api('/logout', { method: 'POST' });
        await loadStatus();
    }));

    sendForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = messageInput.value.trim();
        if (!body || !activeChatId) return;
        messageInput.value = '';
        const response = await fetch(sendUrlTemplate.replace('__CHAT_ID__', encodeURIComponent(activeChatId)), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ message: body, client_id: clientId }),
        });
        if (!response.ok) {
            return;
        }
        await loadMessages(activeChatId);
        await loadChats();
    });

    syncSelectedBtn?.addEventListener('click', async () => {
        if (!activeChatId) return;
        clearSyncFeedback();
        syncSelectedBtn.disabled = true;
        try {
            const response = await fetch(syncUrlTemplate.replace('__CHAT_ID__', encodeURIComponent(activeChatId)), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    client_id: clientId,
                    limit: Number(syncLimitInput?.value || 150),
                }),
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Sync chat gagal.');
            }
            const result = data.result || {};
            setSyncFeedback('success', `Sync chat selesai. Imported ${result.imported_count || 0}, skipped ${result.deduplicated_count || 0}.`);
            await loadChats();
        } catch (error) {
            setSyncFeedback('danger', error.message || 'Sync chat gagal.');
        } finally {
            syncSelectedBtn.disabled = !activeChatId;
        }
    });

    syncActiveBtn?.addEventListener('click', async () => {
        clearSyncFeedback();
        syncActiveBtn.disabled = true;
        try {
            const response = await fetch(syncActiveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    client_id: clientId,
                    chat_limit: 50,
                    message_limit: Number(syncLimitInput?.value || 150),
                }),
            });
            const data = await response.json();
            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Sync semua chat aktif gagal.');
            }
            const summary = data.summary || {};
            setSyncFeedback('success', `Sync ${summary.chat_count || 0} chat selesai. Imported ${summary.imported_count || 0}, skipped ${summary.deduplicated_count || 0}.`);
            await loadChats();
        } catch (error) {
            setSyncFeedback('danger', error.message || 'Sync semua chat aktif gagal.');
        } finally {
            syncActiveBtn.disabled = false;
        }
    });

    loadStatus();
    setInterval(loadStatus, 20000);

    // Socket.IO realtime
    if (window.io) {
        const socket = io(bridgeUrl, {
            query: {
                clientId,
                ...(bridgeToken ? { bridgeToken } : {}),
            },
            auth: bridgeToken ? { token: bridgeToken } : {},
        });
        socket.on('status', handleStatusPayload);
        socket.on('qr', (payload) => handleStatusPayload({ ready: false, qr: payload.qr }));
        socket.on('ready', () => handleStatusPayload({ ready: true, info: {} }));
        socket.on('message', async (payload) => {
            if (activeChatId === payload.chatId) {
                await loadMessages(activeChatId);
            }
            await loadChats();
        });
        socket.on('disconnected', () => handleStatusPayload({ ready: false }));
    }
});
</script>
@endpush
