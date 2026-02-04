@extends('layouts.admin')

@section('content')
<div id="whatsapp-bro" data-bridge-url="{{ $bridgeUrl }}" data-client-id="{{ auth()->id() }}">
    {{-- QR / Connect state --}}
    <div id="wa-connect" class="row g-3">
        <div class="col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">WhatsApp Bro</h3>
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
                <button id="wa-logout-2" class="btn btn-outline-danger btn-sm" type="button">Logout</button>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Chat</h3>
            </div>
            <div class="card-body">
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
    const container = document.getElementById('whatsapp-bro');
    const bridgeUrl = container?.dataset.bridgeUrl;
    const clientId = container?.dataset.clientId || 'default';
    const statusEl = document.getElementById('wa-status');
    const qrEl = document.getElementById('wa-qr');
    const refreshBtn = document.getElementById('wa-refresh');
    const logoutBtn = document.getElementById('wa-logout');
    const logoutBtn2 = document.getElementById('wa-logout-2');
    const chatListEl = document.getElementById('wa-chat-list');
    const messagesEl = document.getElementById('wa-messages');
    const sendForm = document.getElementById('wa-send-form');
    const messageInput = document.getElementById('wa-message-input');
    const sendButton = sendForm.querySelector('button');
    const sectionConnect = document.getElementById('wa-connect');
    const sectionChat = document.getElementById('wa-chat');
    const waAccount = document.getElementById('wa-account');
    let activeChatId = null;

    const api = (path, options = {}) => fetch(`${bridgeUrl}${path}${path.includes('?') ? '&' : '?'}clientId=${clientId}`, {
        headers: { 'Content-Type': 'application/json' },
        ...options,
    });

    const setStatus = (type, text) => {
        statusEl.className = `alert alert-${type}`;
        statusEl.textContent = text;
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
                const badge = chat.unreadCount ? `<span class="badge bg-azure text-azure-fg">${chat.unreadCount}</span>` : '';
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
            handleStatusPayload(data);
        } catch (error) {
            setStatus('danger', 'Gagal terhubung ke bridge server.');
            toggleState(false);
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
    [logoutBtn, logoutBtn2].forEach(btn => btn?.addEventListener('click', async () => {
        await api('/logout', { method: 'POST' });
        await loadStatus();
    }));

    sendForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const body = messageInput.value.trim();
        if (!body || !activeChatId) return;
        messageInput.value = '';
        await api(`/chats/${activeChatId}/messages`, {
            method: 'POST',
            body: JSON.stringify({ message: body }),
        });
        await loadMessages(activeChatId);
    });

    loadStatus();
    setInterval(loadStatus, 20000);

    // Socket.IO realtime
    if (window.io) {
        const socket = io(bridgeUrl, { query: { clientId } });
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
