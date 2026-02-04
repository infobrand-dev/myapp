@extends('layouts.admin')

@section('content')
<div class="row g-3" data-bridge-url="{{ $bridgeUrl }}" id="whatsapp-bro">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">WhatsApp Bro</h3>
            </div>
            <div class="card-body">
                <div id="wa-status" class="alert alert-info">Menghubungkan ke WhatsApp...</div>
                <div class="text-center mb-3">
                    <img id="wa-qr" src="" alt="QR Code" class="img-fluid border rounded" style="max-width: 260px; display:none;">
                </div>
                <button id="wa-refresh" class="btn btn-outline-primary w-100 mb-2" type="button">Refresh Status</button>
                <button id="wa-logout" class="btn btn-outline-danger w-100" type="button">Logout</button>
                <p class="text-muted mt-3 mb-0 small">
                    Scan QR di atas dengan WhatsApp untuk login. Pastikan bridge server berjalan.
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title mb-0">Chat</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="list-group" id="wa-chat-list" style="max-height: 520px; overflow:auto;">
                            <div class="text-muted small p-2">Belum ada chat.</div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="border rounded p-3 mb-3" id="wa-messages" style="height: 420px; overflow:auto; background:#f8f9fa;">
                            <div class="text-muted small">Pilih chat untuk melihat pesan.</div>
                        </div>
                        <form id="wa-send-form" class="d-flex gap-2">
                            <input type="text" id="wa-message-input" class="form-control" placeholder="Ketik pesan..." disabled>
                            <button class="btn btn-primary" type="submit" disabled>Kirim</button>
                        </form>
                        <div class="text-muted small mt-2">Catatan: tampilan chat ini adalah ringkas, bukan clone penuh WhatsApp Web.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const container = document.getElementById('whatsapp-bro');
    const bridgeUrl = container?.dataset.bridgeUrl;
    const statusEl = document.getElementById('wa-status');
    const qrEl = document.getElementById('wa-qr');
    const refreshBtn = document.getElementById('wa-refresh');
    const logoutBtn = document.getElementById('wa-logout');
    const chatListEl = document.getElementById('wa-chat-list');
    const messagesEl = document.getElementById('wa-messages');
    const sendForm = document.getElementById('wa-send-form');
    const messageInput = document.getElementById('wa-message-input');
    const sendButton = sendForm.querySelector('button');
    let activeChatId = null;

    const api = (path, options = {}) => fetch(`${bridgeUrl}${path}`, {
        headers: { 'Content-Type': 'application/json' },
        ...options,
    });

    const setStatus = (type, text) => {
        statusEl.className = `alert alert-${type}`;
        statusEl.textContent = text;
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
            item.className = `list-group-item list-group-item-action ${chat.id === activeChatId ? 'active' : ''}`;
            item.innerHTML = `<div class="d-flex justify-content-between">
                <span>${chat.name}</span>
                ${chat.unreadCount ? `<span class="badge bg-primary">${chat.unreadCount}</span>` : ''}
            </div>
            <div class="small text-muted">${chat.lastMessage || ''}</div>`;
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
            bubble.innerHTML = `<div class="px-3 py-2 rounded ${msg.fromMe ? 'bg-primary text-white' : 'bg-white border'}" style="max-width: 70%;">
                <div class="small">${msg.body}</div>
                <div class="text-muted small mt-1">${msg.timestamp}</div>
            </div>`;
            messagesEl.appendChild(bubble);
        });
        messagesEl.scrollTop = messagesEl.scrollHeight;
    };

    const loadStatus = async () => {
        try {
            const response = await api('/status');
            const data = await response.json();
            if (data.ready) {
                setStatus('success', `Connected as ${data.info?.name || 'WhatsApp User'}.`);
                qrEl.style.display = 'none';
                await loadChats();
                return;
            }
            if (data.qr) {
                setStatus('warning', 'Scan QR untuk login.');
                qrEl.src = data.qr;
                qrEl.style.display = 'inline-block';
            } else {
                setStatus('info', 'Menunggu QR code...');
                qrEl.style.display = 'none';
            }
        } catch (error) {
            setStatus('danger', 'Gagal terhubung ke bridge server.');
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
    logoutBtn.addEventListener('click', async () => {
        await api('/logout', { method: 'POST' });
        activeChatId = null;
        messageInput.disabled = true;
        sendButton.disabled = true;
        renderMessages([]);
        await loadStatus();
    });

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
</script>
@endpush
