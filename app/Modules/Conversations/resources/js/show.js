document.addEventListener('DOMContentLoaded', () => {
    const configElement = document.getElementById('conversation-show-config');
    const dashboardRoot = document.getElementById('conv-dashboard-root');

    if (!configElement || !dashboardRoot) {
        return;
    }

    let config = {};
    try {
        config = JSON.parse(configElement.textContent || '{}');
    } catch (_) {
        return;
    }

    const chatPane = document.getElementById('chat-pane');
    const sidebarUnreadBadge = document.getElementById('sidebar-module-badge-conversation_unread_total');
    const chatLastMessageTime = document.getElementById('chat-last-message-time');
    const detailLastMessageTime = document.getElementById('detail-last-message-time');
    const activeInboxPreview = document.querySelector('.conv-item.active .conv-item-preview');
    const activeConversationBadge = document.querySelector('.conv-item.active .badge');
    const sendForm = document.getElementById('send-form');
    const mediaForm = document.getElementById('media-form');
    const templateForm = document.getElementById('template-form');
    const messageInput = document.getElementById('message-input');
    const sendFeedback = document.getElementById('send-feedback');
    const mediaFileInput = document.getElementById('media-file-input');
    const mediaUploadTitle = document.getElementById('media-upload-title');
    const mediaUploadFile = document.getElementById('media-upload-file');
    const mediaUploadCancel = document.getElementById('media-upload-cancel');
    const mediaUploadChange = document.getElementById('media-upload-change');
    const mediaUploadSubmit = document.getElementById('media-upload-submit');
    const mediaCaptionInput = document.getElementById('media-caption-input');
    const mediaPickerButtons = Array.from(document.querySelectorAll('[data-media-picker]'));
    const mobileBackInbox = document.getElementById('mobile-back-inbox');
    const mobileOpenDetail = document.getElementById('mobile-open-detail');
    const mobileBackChat = document.getElementById('mobile-back-chat');
    const lockSpan = document.getElementById('lock-remaining');
    const chatLoader = document.getElementById('chat-loader');
    const filterTabs = document.querySelectorAll('#conversation-filter-tabs [data-filter]');
    const conversationSearch = document.getElementById('conversation-search');
    const startUserForm = document.querySelector('#start-conversation-modal form');
    const startUserPicker = document.getElementById('start-user-picker');
    const startUserId = document.getElementById('start-user-id');
    const startUserResults = document.getElementById('start-user-results');
    const startUserInvalid = document.getElementById('start-user-invalid');
    const enableWebNotifBtn = document.getElementById('enable-web-notif-btn');
    const conversationItems = Array.from(document.querySelectorAll('.conv-list .conv-item'));
    const conversationEmpty = document.getElementById('conversation-empty-state');
    const chatHistorySentinel = document.getElementById('chat-history-sentinel');

    const convId = Number(config.convId || 0);
    const lockedUntil = config.lockedUntil || '';
    const messagesEndpoint = config.messagesEndpoint || '';
    const messagesSinceEndpoint = config.messagesSinceEndpoint || '';
    const markReadEndpoint = config.markReadEndpoint || '';
    const conversationUrl = config.conversationUrl || window.location.href;
    const csrfToken = config.csrfToken || '';
    const startUserSearchEndpoint = config.startUserSearchEndpoint || '';

    let oldestMessageId = config.oldestMessageId ?? null;
    let latestMessageId = config.latestMessageId ?? null;
    let hasMoreMessages = Boolean(config.hasMoreMessages);
    let loadingOlder = false;
    let pollingInFlight = false;
    let activeFilter = 'all';
    let unseenIncomingCount = 0;
    let sidebarUnreadCount = Number(sidebarUnreadBadge?.dataset.count ?? 0) || 0;
    let readSyncInFlight = false;
    let sendInFlight = false;
    let userSearchTimer = null;
    let userSearchController = null;
    let activeMediaPickerKind = 'file';
    let pollingTimer = null;
    let olderMessagesObserver = null;
    let hasRealtimeChannel = false;

    const basePageTitle = document.title;
    const maxRenderedMessages = 120;
    const renderedMessageIds = new Set(
        Array.from(document.querySelectorAll('.chat-row[data-message-id]'))
            .map((el) => Number(el.dataset.messageId))
            .filter((id) => Number.isFinite(id) && id > 0)
    );

    const mediaPickerConfig = {
        file: {
            label: 'Kirim file',
            submitLabel: 'Kirim file',
            accept: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip',
        },
        image: {
            label: 'Kirim image',
            submitLabel: 'Kirim image',
            accept: 'image/*',
        },
        video: {
            label: 'Kirim video',
            submitLabel: 'Kirim video',
            accept: 'video/*',
        },
    };

    const isMobile = () => window.matchMedia('(max-width: 991.98px)').matches;
    const isChatVisible = () => !isMobile() || dashboardRoot.classList.contains('mobile-view-chat');
    const isPageActive = () => document.visibilityState === 'visible' && document.hasFocus();
    const isNearBottom = () => chatPane
        ? ((chatPane.scrollHeight - chatPane.scrollTop - chatPane.clientHeight) < 80)
        : true;
    const scrollChatToBottom = (behavior = 'auto') => {
        if (!chatPane) {
            return;
        }
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                chatPane.scrollTo({
                    top: chatPane.scrollHeight,
                    behavior,
                });
            });
        });
    };
    const bindDeferredMediaScroll = (scope) => {
        if (!scope) {
            return;
        }
        const mediaNodes = scope.querySelectorAll('img, video');
        mediaNodes.forEach((mediaNode) => {
            if (mediaNode.dataset.chatScrollBound === '1') {
                return;
            }
            mediaNode.dataset.chatScrollBound = '1';
            const syncScroll = () => {
                if (isNearBottom()) {
                    scrollChatToBottom();
                }
            };
            mediaNode.addEventListener('load', syncScroll, { once: true });
            mediaNode.addEventListener('loadedmetadata', syncScroll, { once: true });
        });
    };
    const refreshUnreadUi = () => {
        if (sidebarUnreadBadge) {
            sidebarUnreadBadge.dataset.count = String(Math.max(0, sidebarUnreadCount));
            sidebarUnreadBadge.textContent = String(Math.max(0, sidebarUnreadCount));
            sidebarUnreadBadge.classList.toggle('d-none', sidebarUnreadCount <= 0);
        }
        document.title = sidebarUnreadCount > 0 ? `(${sidebarUnreadCount}) ${basePageTitle}` : basePageTitle;
    };
    const syncReadToServer = () => {
        if (readSyncInFlight || !markReadEndpoint) {
            return;
        }
        readSyncInFlight = true;
        fetch(markReadEndpoint, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        }).finally(() => {
            readSyncInFlight = false;
        });
    };
    const clearUnread = () => {
        if (unseenIncomingCount > 0) {
            sidebarUnreadCount = Math.max(0, sidebarUnreadCount - unseenIncomingCount);
        }
        unseenIncomingCount = 0;
        const activeConversationItem = document.querySelector('.conv-item.active');
        if (activeConversationItem) {
            activeConversationItem.dataset.unreadCount = '0';
        }
        if (activeConversationBadge) {
            activeConversationBadge.classList.add('d-none');
            activeConversationBadge.textContent = '0';
        }
        refreshUnreadUi();
        syncReadToServer();
    };
    const setSendFeedback = (message = '', variant = 'danger') => {
        if (!sendFeedback) {
            return;
        }
        if (!message) {
            sendFeedback.classList.add('d-none');
            sendFeedback.textContent = '';
            sendFeedback.classList.remove('text-danger', 'text-success');
            return;
        }
        sendFeedback.textContent = message;
        sendFeedback.classList.remove('d-none', 'text-danger', 'text-success');
        sendFeedback.classList.add(variant === 'success' ? 'text-success' : 'text-danger');
    };
    const resetMediaComposer = () => {
        if (!mediaForm || !mediaUploadTitle || !mediaUploadFile || !mediaUploadSubmit) {
            return;
        }
        mediaForm.reset();
        mediaForm.classList.add('d-none');
        activeMediaPickerKind = 'file';
        mediaFileInput?.removeAttribute('accept');
        mediaUploadTitle.textContent = 'Pilih media';
        mediaUploadFile.textContent = 'Belum ada file dipilih.';
        mediaUploadSubmit.textContent = 'Kirim media';
    };
    const openMediaPicker = (kind) => {
        if (!mediaFileInput) {
            return;
        }
        const pickerConfig = mediaPickerConfig[kind] || mediaPickerConfig.file;
        activeMediaPickerKind = Object.prototype.hasOwnProperty.call(mediaPickerConfig, kind) ? kind : 'file';
        if (pickerConfig.accept) {
            mediaFileInput.setAttribute('accept', pickerConfig.accept);
        } else {
            mediaFileInput.removeAttribute('accept');
        }
        mediaFileInput.click();
    };
    const syncMediaComposerUi = () => {
        if (!mediaForm || !mediaFileInput || !mediaUploadTitle || !mediaUploadFile || !mediaUploadSubmit) {
            return;
        }
        const file = mediaFileInput.files?.[0];
        if (!file) {
            resetMediaComposer();
            return;
        }

        const pickerConfig = mediaPickerConfig[activeMediaPickerKind] || mediaPickerConfig.file;
        mediaForm.classList.remove('d-none');
        mediaUploadTitle.textContent = pickerConfig.label;
        mediaUploadFile.textContent = file.name;
        mediaUploadSubmit.textContent = pickerConfig.submitLabel;
        mediaCaptionInput?.focus();
    };
    const updateMessageRelatedUi = (msg) => {
        if (chatLastMessageTime) {
            chatLastMessageTime.textContent = 'Last Message: just now';
        }
        if (detailLastMessageTime) {
            detailLastMessageTime.textContent = 'just now';
        }
        if (activeInboxPreview) {
            activeInboxPreview.textContent = (msg?.body || 'New message').toString();
        }
    };
    const notifyIncoming = (name, body) => {
        const notifier = window.MyAppNotifier;
        if (!notifier || typeof notifier.show !== 'function') {
            return;
        }
        notifier.show(`New message from ${name}`, body, conversationUrl, `conv-${convId}`);
    };
    const refreshWebNotifButton = () => {
        const notifier = window.MyAppNotifier;
        if (!enableWebNotifBtn || !notifier || !notifier.supportsNotifications?.()) {
            return;
        }
        const currentPermission = notifier.permission?.() || 'denied';
        enableWebNotifBtn.classList.toggle('d-none', currentPermission !== 'default');
    };
    const initWebNotifButton = () => {
        refreshWebNotifButton();
        enableWebNotifBtn?.addEventListener('click', async () => {
            const notifier = window.MyAppNotifier;
            if (!notifier || typeof notifier.ensurePermission !== 'function') {
                return;
            }
            const granted = await notifier.ensurePermission(true);
            if (granted) {
                enableWebNotifBtn.classList.add('d-none');
            } else {
                refreshWebNotifButton();
            }
        });
    };
    const setMobileView = (view) => {
        dashboardRoot.classList.remove('mobile-view-inbox', 'mobile-view-chat', 'mobile-view-detail');
        dashboardRoot.classList.add(`mobile-view-${view}`);
        if (view === 'chat' && document.hasFocus()) {
            clearUnread();
        }
    };
    const initMobileView = () => {
        if (!isMobile()) {
            return;
        }
        const openChat = sessionStorage.getItem('conv-open-chat') === '1';
        sessionStorage.removeItem('conv-open-chat');
        setMobileView(openChat ? 'chat' : 'inbox');
    };
    const normalize = (text) => (text || '').toString().toLowerCase().trim();
    const hideUserResults = () => {
        if (!startUserResults) {
            return;
        }
        startUserResults.classList.remove('show');
        startUserResults.innerHTML = '';
    };
    const escapeHtml = (value) => (value || '')
        .toString()
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    const formatMessageHtml = (value) => {
        const escaped = escapeHtml(value);

        return escaped
            .replace(/`([^`\n]+)`/g, '<code>$1</code>')
            .replace(/\*([^\*\n]+)\*/g, '<strong>$1</strong>')
            .replace(/_([^_\n]+)_/g, '<em>$1</em>')
            .replace(/~([^~\n]+)~/g, '<s>$1</s>')
            .replace(/\r\n|\r|\n/g, '<br>');
    };
    const initials = (name) => {
        const parts = (name || '').trim().split(/\s+/).filter(Boolean);
        const first = parts[0]?.[0] || '?';
        const second = parts[1]?.[0] || '';
        return (first + second).toUpperCase();
    };
    const avatarTone = (name) => {
        const tones = ['avatar-tone-1', 'avatar-tone-2', 'avatar-tone-3', 'avatar-tone-4', 'avatar-tone-5'];
        let hash = 0;
        const source = (name || '').toString();
        for (let i = 0; i < source.length; i++) {
            hash = ((hash << 5) - hash) + source.charCodeAt(i);
            hash |= 0;
        }
        return tones[Math.abs(hash) % tones.length];
    };
    const avatarUrl = (raw) => {
        if (!raw) {
            return '';
        }
        const value = raw.toString();
        if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) {
            return value;
        }
        return '/storage/' + value.replace(/^\/+/, '');
    };
    const isPreviewableMediaUrl = (raw) => {
        const value = (raw || '').toString().trim();
        return value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/');
    };
    const buildMediaHtml = (msg) => {
        const type = (msg.type || '').toString().toLowerCase();
        const mediaUrl = (msg.media_url || '').toString();
        const mediaMime = (msg.media_mime || '').toString();
        const filename = (msg.filename || msg.body || `${type || 'file'}`).toString();

        if (!['image', 'video', 'audio', 'document', 'file'].includes(type)) {
            return '';
        }

        if (type === 'image' && isPreviewableMediaUrl(mediaUrl)) {
            return `<div class="chat-media"><a href="${escapeHtml(mediaUrl)}" target="_blank" rel="noopener noreferrer"><img src="${escapeHtml(mediaUrl)}" alt="${escapeHtml(msg.body || 'Image')}"></a></div>`;
        }

        if (type === 'video' && isPreviewableMediaUrl(mediaUrl)) {
            return `<div class="chat-media"><video controls preload="metadata"><source src="${escapeHtml(mediaUrl)}" type="${escapeHtml(mediaMime || 'video/mp4')}"></video></div>`;
        }

        if (type === 'audio' && isPreviewableMediaUrl(mediaUrl)) {
            return `<div class="chat-media"><audio controls preload="none"><source src="${escapeHtml(mediaUrl)}" type="${escapeHtml(mediaMime || 'audio/mpeg')}"></audio></div>`;
        }

        const fileInner = `
            <span class="chat-file-icon">FILE</span>
            <span>
                <span class="chat-file-name d-block">${escapeHtml(filename || 'Document')}</span>
                <span class="chat-file-meta d-block">${escapeHtml(mediaMime || type.toUpperCase())}</span>
            </span>`;

        if (isPreviewableMediaUrl(mediaUrl)) {
            return `<div class="chat-media"><a href="${escapeHtml(mediaUrl)}" target="_blank" rel="noopener noreferrer" class="chat-file-card">${fileInner}</a></div>`;
        }

        return `<div class="chat-media"><div class="chat-file-card">${fileInner}</div></div>`;
    };
    const buildMessageNode = (msg) => {
        const name = msg.user?.name ?? (msg.direction === 'out' ? 'You' : 'System');
        const state = `${msg.direction === 'out' ? 'Outgoing' : 'Incoming'}${msg.status ? ' | ' + msg.status : ''}`;
        const avatar = avatarUrl(msg.user?.avatar ?? '');
        const avatarHtml = avatar
            ? `<img src="${escapeHtml(avatar)}" alt="${escapeHtml(name)}">`
            : `<span class="chat-avatar-fallback ${avatarTone(name)}">${escapeHtml(initials(name))}</span>`;
        const mediaHtml = buildMediaHtml(msg);
        const bodyHtml = msg.body ? `<div class="chat-message-text">${formatMessageHtml(msg.body)}</div>` : '';

        const wrapper = document.createElement('div');
        wrapper.className = `chat-row chat-row-${msg.direction === 'out' ? 'out' : 'in'} d-flex align-items-end gap-2`;
        wrapper.dataset.messageId = msg.id ?? '';
        wrapper.innerHTML = `
            <div class="chat-avatar">${avatarHtml}</div>
            <div class="chat-bubble ${msg.direction === 'out' ? 'chat-bubble-out' : 'chat-bubble-in'}">
                <div class="chat-head d-flex align-items-center justify-content-between gap-2">
                    <span class="chat-sender">${escapeHtml(name)}</span>
                    <span class="chat-state">${escapeHtml(state)}</span>
                </div>
                <div class="chat-message-body">${mediaHtml}${bodyHtml}</div>
                <div class="chat-meta">${msg.created_at ?? ''}</div>
            </div>`;
        return wrapper;
    };
    const getRenderedRows = () => chatPane
        ? Array.from(chatPane.querySelectorAll('.chat-row[data-message-id]'))
        : [];
    const refreshOldestVisibleMessage = () => {
        const firstVisibleRow = getRenderedRows()[0];
        const firstVisibleId = Number(firstVisibleRow?.dataset.messageId);
        if (Number.isFinite(firstVisibleId) && firstVisibleId > 0) {
            oldestMessageId = firstVisibleId;
        }
    };
    const ensureOlderLoaderVisible = () => {
        if (!chatLoader) {
            return;
        }
        chatLoader.classList.remove('d-none');
        chatLoader.textContent = 'Scroll up to load older messages...';
    };
    const pruneRenderedMessages = (mode = 'keep-latest') => {
        if (!chatPane) {
            return;
        }
        const rows = getRenderedRows();
        const overflow = rows.length - maxRenderedMessages;
        if (overflow <= 0) {
            return;
        }

        const rowsToRemove = mode === 'keep-top'
            ? rows.slice(-overflow)
            : rows.slice(0, overflow);

        let removedTopHeight = 0;
        rowsToRemove.forEach((row) => {
            const rowId = Number(row.dataset.messageId);
            if (mode !== 'keep-top') {
                removedTopHeight += row.getBoundingClientRect().height;
            }
            if (Number.isFinite(rowId) && rowId > 0) {
                renderedMessageIds.delete(rowId);
            }
            row.remove();
        });

        if (mode !== 'keep-top' && removedTopHeight > 0) {
            chatPane.scrollTop = Math.max(0, chatPane.scrollTop - removedTopHeight);
            hasMoreMessages = true;
            ensureOlderLoaderVisible();
        }

        refreshOldestVisibleMessage();
    };
    const appendIfNew = (msg, shouldScrollToBottom = false) => {
        const id = Number(msg.id);
        if (Number.isFinite(id) && renderedMessageIds.has(id)) {
            return false;
        }
        if (Number.isFinite(id) && id > 0) {
            renderedMessageIds.add(id);
            latestMessageId = Math.max(Number(latestMessageId || 0), id);
        }
        const wrapper = buildMessageNode(msg);
        chatPane?.appendChild(wrapper);
        bindDeferredMediaScroll(wrapper);
        if (shouldScrollToBottom && chatPane) {
            scrollChatToBottom();
        }
        pruneRenderedMessages('keep-latest');
        updateMessageRelatedUi(msg);
        return true;
    };
    const loadOlderMessages = async () => {
        if (!chatPane || loadingOlder || !hasMoreMessages || !oldestMessageId || !messagesEndpoint) {
            return;
        }
        loadingOlder = true;
        if (chatLoader) {
            chatLoader.textContent = 'Loading older messages...';
        }

        try {
            const prevHeight = chatPane.scrollHeight;
            const prevTop = chatPane.scrollTop;
            const url = `${messagesEndpoint}?before_id=${encodeURIComponent(oldestMessageId)}&limit=30`;
            const response = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) {
                throw new Error('load failed');
            }

            const payload = await response.json();
            const list = Array.isArray(payload.messages) ? payload.messages : [];
            if (list.length) {
                const fragment = document.createDocumentFragment();
                list.forEach((msg) => {
                    const id = Number(msg.id);
                    if (Number.isFinite(id) && renderedMessageIds.has(id)) {
                        return;
                    }
                    if (Number.isFinite(id) && id > 0) {
                        renderedMessageIds.add(id);
                    }
                    const node = buildMessageNode(msg);
                    bindDeferredMediaScroll(node);
                    fragment.appendChild(node);
                });
                const insertAnchor = chatHistorySentinel?.nextSibling ?? chatPane.firstChild;
                chatPane.insertBefore(fragment, insertAnchor);
                const newHeight = chatPane.scrollHeight;
                chatPane.scrollTop = newHeight - prevHeight + prevTop;
                pruneRenderedMessages('keep-top');
            }

            oldestMessageId = payload.oldest_id ?? oldestMessageId;
            hasMoreMessages = Boolean(payload.has_more);
            if (chatLoader) {
                if (!hasMoreMessages) {
                    chatLoader.classList.add('d-none');
                } else {
                    chatLoader.textContent = 'Scroll up to load older messages...';
                }
            }
        } catch (_) {
            if (chatLoader) {
                chatLoader.textContent = 'Failed to load older messages.';
            }
        } finally {
            loadingOlder = false;
        }
    };
    const pollLatestMessages = async () => {
        if (pollingInFlight || !isPageActive() || !messagesSinceEndpoint) {
            return;
        }
        pollingInFlight = true;
        try {
            const url = `${messagesSinceEndpoint}?after_id=${encodeURIComponent(latestMessageId || 0)}&limit=20`;
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });
            if (!response.ok) {
                throw new Error('poll failed');
            }
            const payload = await response.json();
            const list = Array.isArray(payload.messages) ? payload.messages : [];
            if (list.length) {
                const shouldStickBottom = isNearBottom();
                list.forEach((msg) => {
                    const inserted = appendIfNew(msg, shouldStickBottom);
                    if (!inserted) {
                        return;
                    }
                    const incomingOutOfView = msg.direction === 'in' && (document.hidden || !document.hasFocus() || !isChatVisible());
                    if (incomingOutOfView) {
                        unseenIncomingCount += 1;
                        sidebarUnreadCount += 1;
                        refreshUnreadUi();
                        const senderName = msg.user?.name ?? 'Contact';
                        notifyIncoming(senderName, msg.body ?? '');
                    } else if (msg.direction === 'in') {
                        clearUnread();
                    }
                });
            }
            if (payload.latest_id) {
                latestMessageId = Math.max(Number(latestMessageId || 0), Number(payload.latest_id || 0));
            }
        } catch (_) {
            // keep silent; polling is best-effort fallback
        } finally {
            pollingInFlight = false;
        }
    };
    const stopPolling = () => {
        if (!pollingTimer) {
            return;
        }
        clearInterval(pollingTimer);
        pollingTimer = null;
    };
    const startPolling = () => {
        if (pollingTimer || hasRealtimeChannel) {
            return;
        }
        pollingTimer = setInterval(pollLatestMessages, 4000);
    };
    function refreshPollingState() {
        if (hasRealtimeChannel || !isPageActive()) {
            stopPolling();
            return;
        }
        startPolling();
    }
    const applyConversationFilters = () => {
        const query = normalize(conversationSearch?.value);
        let visibleCount = 0;
        conversationItems.forEach((item) => {
            const name = normalize(item.dataset.name);
            const assignment = item.dataset.assignment || 'assigned';
            const matchFilter = activeFilter === 'all' || assignment === activeFilter;
            const matchQuery = !query || name.includes(query);
            const visible = matchFilter && matchQuery;
            item.classList.toggle('d-none', !visible);
            if (visible) {
                visibleCount++;
            }
        });
        if (conversationEmpty) {
            conversationEmpty.classList.toggle('d-none', visibleCount > 0 || !conversationItems.length);
        }
    };
    const renderUserResults = (items, query) => {
        if (!startUserResults) {
            return;
        }
        startUserResults.innerHTML = '';
        if (!query || query.length < 2) {
            startUserResults.innerHTML = '<div class="user-search-note">Type at least 2 characters.</div>';
            startUserResults.classList.add('show');
            return;
        }
        if (!items.length) {
            startUserResults.innerHTML = '<div class="user-search-note">No users found.</div>';
            startUserResults.classList.add('show');
            return;
        }
        items.forEach((item) => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'user-search-item';
            btn.textContent = item.text;
            btn.dataset.userId = item.id;
            btn.addEventListener('click', () => {
                if (startUserPicker) {
                    startUserPicker.value = item.text;
                }
                if (startUserId) {
                    startUserId.value = String(item.id);
                }
                if (startUserInvalid) {
                    startUserInvalid.classList.add('d-none');
                }
                hideUserResults();
            });
            startUserResults.appendChild(btn);
        });
        startUserResults.classList.add('show');
    };
    const searchUsersRemote = async (query) => {
        if (!startUserPicker || !startUserId || !startUserResults || !startUserSearchEndpoint) {
            return;
        }
        startUserId.value = '';
        if (query.length < 2) {
            renderUserResults([], query);
            return;
        }
        if (userSearchController) {
            userSearchController.abort();
        }
        userSearchController = new AbortController();
        startUserResults.innerHTML = '<div class="user-search-note">Searching...</div>';
        startUserResults.classList.add('show');
        try {
            const url = `${startUserSearchEndpoint}?q=${encodeURIComponent(query)}&limit=15`;
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
                signal: userSearchController.signal,
            });
            if (!response.ok) {
                throw new Error('search failed');
            }
            const payload = await response.json();
            renderUserResults(Array.isArray(payload.items) ? payload.items : [], query);
        } catch (_) {
            startUserResults.innerHTML = '<div class="user-search-note">Failed to search users.</div>';
            startUserResults.classList.add('show');
        }
    };
    const sendMessageForm = async (formEl) => {
        if (!formEl || sendInFlight) {
            return;
        }
        sendInFlight = true;
        setSendFeedback('');
        const submitBtn = formEl.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
        }

        try {
            const response = await fetch(formEl.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    Accept: 'application/json',
                },
                body: new FormData(formEl),
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = payload?.message
                    || payload?.errors?.body?.[0]
                    || payload?.errors?.template_id?.[0]
                    || payload?.errors?.media_file?.[0]
                    || 'Failed to send message.';
                setSendFeedback(message, 'danger');
                return;
            }

            const msg = payload?.message;
            if (msg) {
                appendIfNew(msg, true);
            }

            if (formEl === sendForm && messageInput) {
                messageInput.value = '';
                messageInput.focus();
            }
            if (formEl === templateForm) {
                formEl.reset();
            }
            if (formEl === mediaForm) {
                resetMediaComposer();
            }
        } catch (_) {
            setSendFeedback('Network error while sending message.', 'danger');
        } finally {
            sendInFlight = false;
            if (submitBtn) {
                submitBtn.disabled = false;
            }
        }
    };
    const initTemplateSelector = () => {
        const tplSelect = document.getElementById('template_id');
        const tplVars = document.getElementById('tpl-vars');
        const tplLang = document.getElementById('tpl_lang');
        const tplPreview = document.getElementById('tpl-preview');

        function extractPlaceholders(text) {
            if (!text) {
                return [];
            }
            const matches = [...text.matchAll(/\{\{(\d+)\}\}/g)];
            return [...new Set(matches.map((match) => parseInt(match[1], 10)))].sort((a, b) => a - b);
        }

        function renderVars() {
            if (!tplSelect || !tplVars || !tplLang || !tplPreview) {
                return;
            }
            const opt = tplSelect.selectedOptions[0];
            if (!opt) {
                return;
            }
            const body = opt.getAttribute('data-body') || '';
            const header = opt.getAttribute('data-header') || '';
            const placeholders = [...new Set([...extractPlaceholders(body), ...extractPlaceholders(header)])];
            tplVars.innerHTML = '';
            tplLang.value = opt.textContent.match(/\((.*?)\)/)?.[1] ?? '';
            tplPreview.innerHTML = body ? `Preview body:<br>${formatMessageHtml(body)}` : '';
            placeholders.forEach((idx) => {
                const col = document.createElement('div');
                col.className = 'col-md-6';
                col.innerHTML = `
                    <div class="input-group">
                        <span class="input-group-text">&#123;&#123;${idx}&#125;&#125;</span>
                        <input type="text" class="form-control" name="template_params[${idx}]" placeholder="Isi untuk &#123;&#123;${idx}&#125;&#125;" required>
                    </div>`;
                tplVars.appendChild(col);
            });
            if (!placeholders.length) {
                tplVars.innerHTML = '<div class="text-muted small ms-1">Tidak ada placeholder.</div>';
            }
        }

        tplSelect?.addEventListener('change', renderVars);
        if (tplSelect) {
            renderVars();
        }
    };

    bindDeferredMediaScroll(chatPane);
    scrollChatToBottom();
    initMobileView();
    initWebNotifButton();
    initTemplateSelector();
    refreshUnreadUi();
    refreshPollingState();
    applyConversationFilters();

    conversationItems.forEach((item) => {
        item.addEventListener('click', () => {
            if (!isMobile()) {
                return;
            }
            sessionStorage.setItem('conv-open-chat', '1');
        });
    });
    filterTabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            activeFilter = tab.dataset.filter || 'all';
            filterTabs.forEach((item) => item.classList.toggle('active', item === tab));
            applyConversationFilters();
        });
    });
    let searchTimer = null;
    conversationSearch?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(applyConversationFilters, 120);
    });
    mobileBackInbox?.addEventListener('click', () => setMobileView('inbox'));
    mobileOpenDetail?.addEventListener('click', () => setMobileView('detail'));
    mobileBackChat?.addEventListener('click', () => setMobileView('chat'));
    window.addEventListener('focus', () => {
        if (isChatVisible()) {
            clearUnread();
        }
        refreshPollingState();
    });
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && isChatVisible()) {
            clearUnread();
        }
        refreshPollingState();
    });
    window.addEventListener('resize', () => {
        if (!isMobile()) {
            dashboardRoot.classList.remove('mobile-view-inbox', 'mobile-view-chat', 'mobile-view-detail');
            if (document.hasFocus()) {
                clearUnread();
            }
        } else if (!dashboardRoot.classList.contains('mobile-view-inbox')
            && !dashboardRoot.classList.contains('mobile-view-chat')
            && !dashboardRoot.classList.contains('mobile-view-detail')) {
            setMobileView('inbox');
        }
    });
    startUserPicker?.addEventListener('input', () => {
        if (startUserInvalid) {
            startUserInvalid.classList.add('d-none');
        }
        const query = (startUserPicker.value || '').trim();
        clearTimeout(userSearchTimer);
        userSearchTimer = setTimeout(() => searchUsersRemote(query), 250);
    });
    startUserPicker?.addEventListener('focus', () => {
        const query = (startUserPicker.value || '').trim();
        searchUsersRemote(query);
    });
    document.addEventListener('click', (event) => {
        if (!startUserResults || !startUserPicker) {
            return;
        }
        if (startUserResults.contains(event.target) || startUserPicker.contains(event.target)) {
            return;
        }
        hideUserResults();
    });
    startUserForm?.addEventListener('submit', (event) => {
        if (!startUserId?.value) {
            event.preventDefault();
            startUserPicker?.focus();
            if (startUserInvalid) {
                startUserInvalid.classList.remove('d-none');
            }
        }
    });
    if (chatPane && chatHistorySentinel && 'IntersectionObserver' in window) {
        olderMessagesObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    loadOlderMessages();
                }
            });
        }, {
            root: chatPane,
            threshold: 0.1,
        });
        olderMessagesObserver.observe(chatHistorySentinel);
    } else if (chatPane) {
        chatPane.addEventListener('scroll', () => {
            if (chatPane.scrollTop <= 48) {
                loadOlderMessages();
            }
        });
    }
    sendForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessageForm(sendForm);
    });
    templateForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessageForm(templateForm);
    });
    mediaForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        sendMessageForm(mediaForm);
    });
    mediaPickerButtons.forEach((button) => {
        button.addEventListener('click', () => {
            openMediaPicker(button.dataset.mediaPicker || 'file');
        });
    });
    mediaFileInput?.addEventListener('change', syncMediaComposerUi);
    mediaUploadCancel?.addEventListener('click', () => {
        resetMediaComposer();
        messageInput?.focus();
    });
    mediaUploadChange?.addEventListener('click', () => {
        openMediaPicker(activeMediaPickerKind);
    });

    if (lockSpan && lockedUntil) {
        let lockTimer = null;
        const tick = () => {
            const diff = (new Date(lockedUntil) - new Date()) / 1000;
            if (diff <= 0) {
                lockSpan.textContent = 'expired';
                lockSpan.parentElement?.classList.replace('text-bg-secondary', 'text-bg-warning');
                if (lockTimer) {
                    clearInterval(lockTimer);
                }
                return;
            }
            const minutes = Math.floor(diff / 60);
            const seconds = Math.floor(diff % 60);
            lockSpan.textContent = `${minutes}m ${seconds.toString().padStart(2, '0')}s`;
        };
        tick();
        lockTimer = setInterval(tick, 1000);
    }

    if (window.Echo && convId > 0) {
        hasRealtimeChannel = true;
        stopPolling();
        window.Echo.private(`conversations.${convId}`)
            .listen('App\\Modules\\Conversations\\Events\\ConversationMessageCreated', (event) => {
                const msg = event.message;
                const shouldStickBottom = isNearBottom();
                const inserted = appendIfNew(msg, shouldStickBottom);
                if (!inserted) {
                    return;
                }

                const incomingOutOfView = msg.direction === 'in' && (document.hidden || !document.hasFocus() || !isChatVisible());
                if (incomingOutOfView) {
                    unseenIncomingCount += 1;
                    sidebarUnreadCount += 1;
                    refreshUnreadUi();
                    const senderName = msg.user?.name ?? 'Contact';
                    notifyIncoming(senderName, msg.body ?? '');
                } else if (msg.direction === 'in') {
                    sidebarUnreadCount = Math.max(0, sidebarUnreadCount);
                    clearUnread();
                }
            });
    } else {
        refreshPollingState();
    }
});
