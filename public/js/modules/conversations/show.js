/******/ (() => { // webpackBootstrap
/*!********************************************************!*\
  !*** ./app/Modules/Conversations/resources/js/show.js ***!
  \********************************************************/
function _toConsumableArray(r) { return _arrayWithoutHoles(r) || _iterableToArray(r) || _unsupportedIterableToArray(r) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(r, a) { if (r) { if ("string" == typeof r) return _arrayLikeToArray(r, a); var t = {}.toString.call(r).slice(8, -1); return "Object" === t && r.constructor && (t = r.constructor.name), "Map" === t || "Set" === t ? Array.from(r) : "Arguments" === t || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(t) ? _arrayLikeToArray(r, a) : void 0; } }
function _iterableToArray(r) { if ("undefined" != typeof Symbol && null != r[Symbol.iterator] || null != r["@@iterator"]) return Array.from(r); }
function _arrayWithoutHoles(r) { if (Array.isArray(r)) return _arrayLikeToArray(r); }
function _arrayLikeToArray(r, a) { (null == a || a > r.length) && (a = r.length); for (var e = 0, n = Array(a); e < a; e++) n[e] = r[e]; return n; }
function _regenerator() { /*! regenerator-runtime -- Copyright (c) 2014-present, Facebook, Inc. -- license (MIT): https://github.com/babel/babel/blob/main/packages/babel-helpers/LICENSE */ var e, t, r = "function" == typeof Symbol ? Symbol : {}, n = r.iterator || "@@iterator", o = r.toStringTag || "@@toStringTag"; function i(r, n, o, i) { var c = n && n.prototype instanceof Generator ? n : Generator, u = Object.create(c.prototype); return _regeneratorDefine2(u, "_invoke", function (r, n, o) { var i, c, u, f = 0, p = o || [], y = !1, G = { p: 0, n: 0, v: e, a: d, f: d.bind(e, 4), d: function d(t, r) { return i = t, c = 0, u = e, G.n = r, a; } }; function d(r, n) { for (c = r, u = n, t = 0; !y && f && !o && t < p.length; t++) { var o, i = p[t], d = G.p, l = i[2]; r > 3 ? (o = l === n) && (u = i[(c = i[4]) ? 5 : (c = 3, 3)], i[4] = i[5] = e) : i[0] <= d && ((o = r < 2 && d < i[1]) ? (c = 0, G.v = n, G.n = i[1]) : d < l && (o = r < 3 || i[0] > n || n > l) && (i[4] = r, i[5] = n, G.n = l, c = 0)); } if (o || r > 1) return a; throw y = !0, n; } return function (o, p, l) { if (f > 1) throw TypeError("Generator is already running"); for (y && 1 === p && d(p, l), c = p, u = l; (t = c < 2 ? e : u) || !y;) { i || (c ? c < 3 ? (c > 1 && (G.n = -1), d(c, u)) : G.n = u : G.v = u); try { if (f = 2, i) { if (c || (o = "next"), t = i[o]) { if (!(t = t.call(i, u))) throw TypeError("iterator result is not an object"); if (!t.done) return t; u = t.value, c < 2 && (c = 0); } else 1 === c && (t = i["return"]) && t.call(i), c < 2 && (u = TypeError("The iterator does not provide a '" + o + "' method"), c = 1); i = e; } else if ((t = (y = G.n < 0) ? u : r.call(n, G)) !== a) break; } catch (t) { i = e, c = 1, u = t; } finally { f = 1; } } return { value: t, done: y }; }; }(r, o, i), !0), u; } var a = {}; function Generator() {} function GeneratorFunction() {} function GeneratorFunctionPrototype() {} t = Object.getPrototypeOf; var c = [][n] ? t(t([][n]())) : (_regeneratorDefine2(t = {}, n, function () { return this; }), t), u = GeneratorFunctionPrototype.prototype = Generator.prototype = Object.create(c); function f(e) { return Object.setPrototypeOf ? Object.setPrototypeOf(e, GeneratorFunctionPrototype) : (e.__proto__ = GeneratorFunctionPrototype, _regeneratorDefine2(e, o, "GeneratorFunction")), e.prototype = Object.create(u), e; } return GeneratorFunction.prototype = GeneratorFunctionPrototype, _regeneratorDefine2(u, "constructor", GeneratorFunctionPrototype), _regeneratorDefine2(GeneratorFunctionPrototype, "constructor", GeneratorFunction), GeneratorFunction.displayName = "GeneratorFunction", _regeneratorDefine2(GeneratorFunctionPrototype, o, "GeneratorFunction"), _regeneratorDefine2(u), _regeneratorDefine2(u, o, "Generator"), _regeneratorDefine2(u, n, function () { return this; }), _regeneratorDefine2(u, "toString", function () { return "[object Generator]"; }), (_regenerator = function _regenerator() { return { w: i, m: f }; })(); }
function _regeneratorDefine2(e, r, n, t) { var i = Object.defineProperty; try { i({}, "", {}); } catch (e) { i = 0; } _regeneratorDefine2 = function _regeneratorDefine(e, r, n, t) { function o(r, n) { _regeneratorDefine2(e, r, function (e) { return this._invoke(r, n, e); }); } r ? i ? i(e, r, { value: n, enumerable: !t, configurable: !t, writable: !t }) : e[r] = n : (o("next", 0), o("throw", 1), o("return", 2)); }, _regeneratorDefine2(e, r, n, t); }
function asyncGeneratorStep(n, t, e, r, o, a, c) { try { var i = n[a](c), u = i.value; } catch (n) { return void e(n); } i.done ? t(u) : Promise.resolve(u).then(r, o); }
function _asyncToGenerator(n) { return function () { var t = this, e = arguments; return new Promise(function (r, o) { var a = n.apply(t, e); function _next(n) { asyncGeneratorStep(a, r, o, _next, _throw, "next", n); } function _throw(n) { asyncGeneratorStep(a, r, o, _next, _throw, "throw", n); } _next(void 0); }); }; }
document.addEventListener('DOMContentLoaded', function () {
  var _config$oldestMessage, _config$latestMessage, _sidebarUnreadBadge$d;
  var configElement = document.getElementById('conversation-show-config');
  var dashboardRoot = document.getElementById('conv-dashboard-root');
  if (!configElement || !dashboardRoot) {
    return;
  }
  var config = {};
  try {
    config = JSON.parse(configElement.textContent || '{}');
  } catch (_) {
    return;
  }
  var chatPane = document.getElementById('chat-pane');
  var sidebarUnreadBadge = document.getElementById('sidebar-module-badge-conversation_unread_total');
  var chatLastMessageTime = document.getElementById('chat-last-message-time');
  var detailLastMessageTime = document.getElementById('detail-last-message-time');
  var detailOwnerName = document.getElementById('detail-owner-name');
  var liveChatAssignmentStatus = document.getElementById('livechat-assignment-status');
  var liveChatAssignmentNote = document.getElementById('livechat-assignment-note');
  var liveChatAssignmentLock = document.getElementById('livechat-assignment-lock');
  var activeInboxPreview = document.querySelector('.conv-item.active .conv-item-preview');
  var activeConversationBadge = document.querySelector('.conv-item.active .badge');
  var sendForm = document.getElementById('send-form');
  var mediaForm = document.getElementById('media-form');
  var templateForm = document.getElementById('template-form');
  var messageInput = document.getElementById('message-input');
  var sendFeedback = document.getElementById('send-feedback');
  var mediaFileInput = document.getElementById('media-file-input');
  var mediaUploadTitle = document.getElementById('media-upload-title');
  var mediaUploadFile = document.getElementById('media-upload-file');
  var mediaUploadCancel = document.getElementById('media-upload-cancel');
  var mediaUploadChange = document.getElementById('media-upload-change');
  var mediaUploadSubmit = document.getElementById('media-upload-submit');
  var mediaCaptionInput = document.getElementById('media-caption-input');
  var mediaPickerButtons = Array.from(document.querySelectorAll('[data-media-picker]'));
  var mobileBackInbox = document.getElementById('mobile-back-inbox');
  var mobileOpenDetail = document.getElementById('mobile-open-detail');
  var mobileBackChat = document.getElementById('mobile-back-chat');
  var lockSpan = document.getElementById('lock-remaining');
  var chatLoader = document.getElementById('chat-loader');
  var filterTabs = document.querySelectorAll('#conversation-filter-tabs [data-filter]');
  var conversationSearch = document.getElementById('conversation-search');
  var startUserForm = document.querySelector('#start-conversation-modal form');
  var startUserPicker = document.getElementById('start-user-picker');
  var startUserId = document.getElementById('start-user-id');
  var startUserResults = document.getElementById('start-user-results');
  var startUserInvalid = document.getElementById('start-user-invalid');
  var enableWebNotifBtn = document.getElementById('enable-web-notif-btn');
  var conversationItems = Array.from(document.querySelectorAll('.conv-list .conv-item'));
  var conversationEmpty = document.getElementById('conversation-empty-state');
  var chatHistorySentinel = document.getElementById('chat-history-sentinel');
  var convId = Number(config.convId || 0);
  var lockedUntil = config.lockedUntil || '';
  var messagesEndpoint = config.messagesEndpoint || '';
  var messagesSinceEndpoint = config.messagesSinceEndpoint || '';
  var markReadEndpoint = config.markReadEndpoint || '';
  var conversationUrl = config.conversationUrl || window.location.href;
  var csrfToken = config.csrfToken || '';
  var startUserSearchEndpoint = config.startUserSearchEndpoint || '';
  var channel = config.channel || '';
  var liveChatAgentTypingEndpoint = config.liveChatAgentTypingEndpoint || '';
  var liveChatStatusEndpoint = config.liveChatStatusEndpoint || '';
  var presenceHeartbeatEndpoint = config.presenceHeartbeatEndpoint || '';
  var oldestMessageId = (_config$oldestMessage = config.oldestMessageId) !== null && _config$oldestMessage !== void 0 ? _config$oldestMessage : null;
  var latestMessageId = (_config$latestMessage = config.latestMessageId) !== null && _config$latestMessage !== void 0 ? _config$latestMessage : null;
  var hasMoreMessages = Boolean(config.hasMoreMessages);
  var loadingOlder = false;
  var pollingInFlight = false;
  var activeFilter = 'all';
  var unseenIncomingCount = 0;
  var sidebarUnreadCount = Number((_sidebarUnreadBadge$d = sidebarUnreadBadge === null || sidebarUnreadBadge === void 0 ? void 0 : sidebarUnreadBadge.dataset.count) !== null && _sidebarUnreadBadge$d !== void 0 ? _sidebarUnreadBadge$d : 0) || 0;
  var readSyncInFlight = false;
  var sendInFlight = false;
  var userSearchTimer = null;
  var userSearchController = null;
  var activeMediaPickerKind = 'file';
  var pollingTimer = null;
  var olderMessagesObserver = null;
  var hasRealtimeChannel = false;
  var typingTimer = null;
  var typingLastSentAt = 0;
  var presenceHeartbeatTimer = null;
  var visitorTypingActive = false;
  var basePageTitle = document.title;
  var maxRenderedMessages = 120;
  var defaultChatLastMessageText = (chatLastMessageTime === null || chatLastMessageTime === void 0 ? void 0 : chatLastMessageTime.dataset.defaultText) || (chatLastMessageTime === null || chatLastMessageTime === void 0 ? void 0 : chatLastMessageTime.textContent) || '';
  var defaultActiveInboxPreview = (activeInboxPreview === null || activeInboxPreview === void 0 ? void 0 : activeInboxPreview.dataset.defaultPreview) || (activeInboxPreview === null || activeInboxPreview === void 0 ? void 0 : activeInboxPreview.textContent) || '';
  var renderedMessageIds = new Set(Array.from(document.querySelectorAll('.chat-row[data-message-id]')).map(function (el) {
    return Number(el.dataset.messageId);
  }).filter(function (id) {
    return Number.isFinite(id) && id > 0;
  }));
  var mediaPickerConfig = {
    file: {
      label: 'Kirim file',
      submitLabel: 'Kirim file',
      accept: '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip'
    },
    image: {
      label: 'Kirim image',
      submitLabel: 'Kirim image',
      accept: 'image/*'
    },
    video: {
      label: 'Kirim video',
      submitLabel: 'Kirim video',
      accept: 'video/*'
    }
  };
  var isMobile = function isMobile() {
    return window.matchMedia('(max-width: 991.98px)').matches;
  };
  var isChatVisible = function isChatVisible() {
    return !isMobile() || dashboardRoot.classList.contains('mobile-view-chat');
  };
  var isPageActive = function isPageActive() {
    return document.visibilityState === 'visible' && document.hasFocus();
  };
  var isNearBottom = function isNearBottom() {
    return chatPane ? chatPane.scrollHeight - chatPane.scrollTop - chatPane.clientHeight < 80 : true;
  };
  var lastRenderedRow = function lastRenderedRow() {
    if (!chatPane) {
      return null;
    }
    var rows = chatPane.querySelectorAll('.chat-row[data-message-id]');
    return rows.length ? rows[rows.length - 1] : null;
  };
  var scrollChatToBottom = function scrollChatToBottom() {
    var behavior = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'auto';
    if (!chatPane) {
      return;
    }
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        chatPane.scrollTo({
          top: chatPane.scrollHeight,
          behavior: behavior
        });
      });
    });
  };
  var ensureLatestMessageVisible = function ensureLatestMessageVisible() {
    var behavior = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'auto';
    if (!chatPane) {
      return;
    }
    var sync = function sync() {
      var lastRow = lastRenderedRow();
      if (lastRow) {
        lastRow.scrollIntoView({
          block: 'end',
          behavior: behavior
        });
      }
      chatPane.scrollTop = chatPane.scrollHeight;
    };
    requestAnimationFrame(function () {
      sync();
      requestAnimationFrame(sync);
    });
  };
  var bindDeferredMediaScroll = function bindDeferredMediaScroll(scope) {
    if (!scope) {
      return;
    }
    var mediaNodes = scope.querySelectorAll('img, video');
    mediaNodes.forEach(function (mediaNode) {
      if (mediaNode.dataset.chatScrollBound === '1') {
        return;
      }
      mediaNode.dataset.chatScrollBound = '1';
      var syncScroll = function syncScroll() {
        if (isNearBottom()) {
          scrollChatToBottom();
        }
      };
      mediaNode.addEventListener('load', syncScroll, {
        once: true
      });
      mediaNode.addEventListener('loadedmetadata', syncScroll, {
        once: true
      });
    });
  };
  var refreshUnreadUi = function refreshUnreadUi() {
    if (sidebarUnreadBadge) {
      sidebarUnreadBadge.dataset.count = String(Math.max(0, sidebarUnreadCount));
      sidebarUnreadBadge.textContent = String(Math.max(0, sidebarUnreadCount));
      sidebarUnreadBadge.classList.toggle('d-none', sidebarUnreadCount <= 0);
    }
    document.title = sidebarUnreadCount > 0 ? "(".concat(sidebarUnreadCount, ") ").concat(basePageTitle) : basePageTitle;
  };
  var syncReadToServer = function syncReadToServer() {
    if (readSyncInFlight || !markReadEndpoint) {
      return;
    }
    readSyncInFlight = true;
    fetch(markReadEndpoint, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken
      }
    })["finally"](function () {
      readSyncInFlight = false;
    });
  };
  var clearUnread = function clearUnread() {
    if (unseenIncomingCount > 0) {
      sidebarUnreadCount = Math.max(0, sidebarUnreadCount - unseenIncomingCount);
    }
    unseenIncomingCount = 0;
    var activeConversationItem = document.querySelector('.conv-item.active');
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
  var setSendFeedback = function setSendFeedback() {
    var message = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
    var variant = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 'danger';
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
  var resetMediaComposer = function resetMediaComposer() {
    if (!mediaForm || !mediaUploadTitle || !mediaUploadFile || !mediaUploadSubmit) {
      return;
    }
    mediaForm.reset();
    mediaForm.classList.add('d-none');
    activeMediaPickerKind = 'file';
    mediaFileInput === null || mediaFileInput === void 0 || mediaFileInput.removeAttribute('accept');
    mediaUploadTitle.textContent = 'Pilih media';
    mediaUploadFile.textContent = 'Belum ada file dipilih.';
    mediaUploadSubmit.textContent = 'Kirim media';
  };
  var openMediaPicker = function openMediaPicker(kind) {
    if (!mediaFileInput) {
      return;
    }
    var pickerConfig = mediaPickerConfig[kind] || mediaPickerConfig.file;
    activeMediaPickerKind = Object.prototype.hasOwnProperty.call(mediaPickerConfig, kind) ? kind : 'file';
    if (pickerConfig.accept) {
      mediaFileInput.setAttribute('accept', pickerConfig.accept);
    } else {
      mediaFileInput.removeAttribute('accept');
    }
    mediaFileInput.click();
  };
  var syncMediaComposerUi = function syncMediaComposerUi() {
    var _mediaFileInput$files;
    if (!mediaForm || !mediaFileInput || !mediaUploadTitle || !mediaUploadFile || !mediaUploadSubmit) {
      return;
    }
    var file = (_mediaFileInput$files = mediaFileInput.files) === null || _mediaFileInput$files === void 0 ? void 0 : _mediaFileInput$files[0];
    if (!file) {
      resetMediaComposer();
      return;
    }
    var pickerConfig = mediaPickerConfig[activeMediaPickerKind] || mediaPickerConfig.file;
    mediaForm.classList.remove('d-none');
    mediaUploadTitle.textContent = pickerConfig.label;
    mediaUploadFile.textContent = file.name;
    mediaUploadSubmit.textContent = pickerConfig.submitLabel;
    mediaCaptionInput === null || mediaCaptionInput === void 0 || mediaCaptionInput.focus();
  };
  var updateMessageRelatedUi = function updateMessageRelatedUi(msg) {
    if (chatLastMessageTime) {
      chatLastMessageTime.textContent = 'Last Message: just now';
      chatLastMessageTime.dataset.defaultText = 'Last Message: just now';
      chatLastMessageTime.classList.remove('is-typing');
    }
    if (detailLastMessageTime) {
      detailLastMessageTime.textContent = 'just now';
    }
    if (activeInboxPreview) {
      activeInboxPreview.textContent = ((msg === null || msg === void 0 ? void 0 : msg.body) || 'New message').toString();
      activeInboxPreview.dataset.defaultPreview = ((msg === null || msg === void 0 ? void 0 : msg.body) || 'New message').toString();
    }
  };
  var setVisitorTypingState = function setVisitorTypingState(isTyping) {
    visitorTypingActive = Boolean(isTyping);
    if (chatLastMessageTime) {
      chatLastMessageTime.textContent = visitorTypingActive ? 'Visitor sedang mengetik...' : chatLastMessageTime.dataset.defaultText || defaultChatLastMessageText;
      chatLastMessageTime.classList.toggle('is-typing', visitorTypingActive);
    }
    if (activeInboxPreview) {
      activeInboxPreview.textContent = visitorTypingActive ? 'Visitor sedang mengetik...' : activeInboxPreview.dataset.defaultPreview || defaultActiveInboxPreview;
    }
  };
  var updateAssignmentUi = function updateAssignmentUi(assignment) {
    if (!assignment) {
      return;
    }
    var ownerName = assignment.owner_name || 'Unassigned';
    var claimable = Boolean(assignment.claimable);
    var claimedByMe = Boolean(assignment.claimed_by_me);
    if (detailOwnerName) {
      detailOwnerName.textContent = ownerName;
    }
    var activeConversationItem = document.querySelector('.conv-item.active');
    if (activeConversationItem) {
      activeConversationItem.dataset.assignment = claimable ? 'unsigned' : 'assigned';
    }
    if (liveChatAssignmentStatus) {
      liveChatAssignmentStatus.textContent = claimedByMe ? 'Conversation ini sedang Anda tangani.' : claimable ? 'Conversation ini belum di-assign.' : "Conversation ini sedang dipegang ".concat(ownerName, ".");
    }
    if (liveChatAssignmentNote) {
      liveChatAssignmentNote.textContent = claimedByMe ? 'Anda bisa invite anggota lain bila perlu kolaborasi atau release jika ingin melepaskan ownership.' : claimable ? 'Claim conversation dulu agar ownership dan respon tetap rapi sebelum membalas visitor.' : 'Tunggu lock berakhir, minta owner release, atau kolaborasi sebagai participant jika sudah diundang.';
    }
    if (liveChatAssignmentLock) {
      if (claimable) {
        liveChatAssignmentLock.textContent = 'Available to claim';
      } else if (assignment.locked_until) {
        liveChatAssignmentLock.textContent = "Locked until ".concat(new Date(assignment.locked_until).toLocaleTimeString([], {
          hour: '2-digit',
          minute: '2-digit'
        }));
      } else {
        liveChatAssignmentLock.textContent = 'Locked';
      }
    }
  };
  var notifyIncoming = function notifyIncoming(name, body) {
    var notifier = window.MyAppNotifier;
    if (!notifier || typeof notifier.show !== 'function') {
      return;
    }
    notifier.show("New message from ".concat(name), body, conversationUrl, "conv-".concat(convId));
  };
  var refreshWebNotifButton = function refreshWebNotifButton() {
    var _notifier$supportsNot, _notifier$permission;
    var notifier = window.MyAppNotifier;
    if (!enableWebNotifBtn || !notifier || !((_notifier$supportsNot = notifier.supportsNotifications) !== null && _notifier$supportsNot !== void 0 && _notifier$supportsNot.call(notifier))) {
      return;
    }
    var currentPermission = ((_notifier$permission = notifier.permission) === null || _notifier$permission === void 0 ? void 0 : _notifier$permission.call(notifier)) || 'denied';
    enableWebNotifBtn.classList.toggle('d-none', currentPermission !== 'default');
  };
  var initWebNotifButton = function initWebNotifButton() {
    refreshWebNotifButton();
    enableWebNotifBtn === null || enableWebNotifBtn === void 0 || enableWebNotifBtn.addEventListener('click', /*#__PURE__*/_asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee() {
      var notifier, granted;
      return _regenerator().w(function (_context) {
        while (1) switch (_context.n) {
          case 0:
            notifier = window.MyAppNotifier;
            if (!(!notifier || typeof notifier.ensurePermission !== 'function')) {
              _context.n = 1;
              break;
            }
            return _context.a(2);
          case 1:
            _context.n = 2;
            return notifier.ensurePermission(true);
          case 2:
            granted = _context.v;
            if (granted) {
              enableWebNotifBtn.classList.add('d-none');
            } else {
              refreshWebNotifButton();
            }
          case 3:
            return _context.a(2);
        }
      }, _callee);
    })));
  };
  var setMobileView = function setMobileView(view) {
    dashboardRoot.classList.remove('mobile-view-inbox', 'mobile-view-chat', 'mobile-view-detail');
    dashboardRoot.classList.add("mobile-view-".concat(view));
    if (view === 'chat') {
      ensureLatestMessageVisible();
      if (document.hasFocus()) {
        clearUnread();
      }
    }
  };
  var initMobileView = function initMobileView() {
    if (!isMobile()) {
      return;
    }
    var openChat = sessionStorage.getItem('conv-open-chat') === '1';
    sessionStorage.removeItem('conv-open-chat');
    setMobileView(openChat ? 'chat' : 'inbox');
  };
  var normalize = function normalize(text) {
    return (text || '').toString().toLowerCase().trim();
  };
  var hideUserResults = function hideUserResults() {
    if (!startUserResults) {
      return;
    }
    startUserResults.classList.remove('show');
    startUserResults.innerHTML = '';
  };
  var escapeHtml = function escapeHtml(value) {
    return (value || '').toString().replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
  };
  var formatMessageHtml = function formatMessageHtml(value) {
    var escaped = escapeHtml(value);
    return escaped.replace(/`([^`\n]+)`/g, '<code>$1</code>').replace(/\*([^\*\n]+)\*/g, '<strong>$1</strong>').replace(/_([^_\n]+)_/g, '<em>$1</em>').replace(/~([^~\n]+)~/g, '<s>$1</s>').replace(/\r\n|\r|\n/g, '<br>');
  };
  var initials = function initials(name) {
    var _parts$, _parts$2;
    var parts = (name || '').trim().split(/\s+/).filter(Boolean);
    var first = ((_parts$ = parts[0]) === null || _parts$ === void 0 ? void 0 : _parts$[0]) || '?';
    var second = ((_parts$2 = parts[1]) === null || _parts$2 === void 0 ? void 0 : _parts$2[0]) || '';
    return (first + second).toUpperCase();
  };
  var avatarTone = function avatarTone(name) {
    var tones = ['avatar-tone-1', 'avatar-tone-2', 'avatar-tone-3', 'avatar-tone-4', 'avatar-tone-5'];
    var hash = 0;
    var source = (name || '').toString();
    for (var i = 0; i < source.length; i++) {
      hash = (hash << 5) - hash + source.charCodeAt(i);
      hash |= 0;
    }
    return tones[Math.abs(hash) % tones.length];
  };
  var avatarUrl = function avatarUrl(raw) {
    if (!raw) {
      return '';
    }
    var value = raw.toString();
    if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/')) {
      return value;
    }
    return '/storage/' + value.replace(/^\/+/, '');
  };
  var isPreviewableMediaUrl = function isPreviewableMediaUrl(raw) {
    var value = (raw || '').toString().trim();
    return value.startsWith('http://') || value.startsWith('https://') || value.startsWith('/');
  };
  var buildMediaHtml = function buildMediaHtml(msg) {
    var type = (msg.type || '').toString().toLowerCase();
    var mediaUrl = (msg.media_url || '').toString();
    var mediaMime = (msg.media_mime || '').toString();
    var filename = (msg.filename || msg.body || "".concat(type || 'file')).toString();
    if (!['image', 'video', 'audio', 'document', 'file'].includes(type)) {
      return '';
    }
    if (type === 'image' && isPreviewableMediaUrl(mediaUrl)) {
      return "<div class=\"chat-media\"><a href=\"".concat(escapeHtml(mediaUrl), "\" target=\"_blank\" rel=\"noopener noreferrer\"><img src=\"").concat(escapeHtml(mediaUrl), "\" alt=\"").concat(escapeHtml(msg.body || 'Image'), "\"></a></div>");
    }
    if (type === 'video' && isPreviewableMediaUrl(mediaUrl)) {
      return "<div class=\"chat-media\"><video controls preload=\"metadata\"><source src=\"".concat(escapeHtml(mediaUrl), "\" type=\"").concat(escapeHtml(mediaMime || 'video/mp4'), "\"></video></div>");
    }
    if (type === 'audio' && isPreviewableMediaUrl(mediaUrl)) {
      return "<div class=\"chat-media\"><audio controls preload=\"none\"><source src=\"".concat(escapeHtml(mediaUrl), "\" type=\"").concat(escapeHtml(mediaMime || 'audio/mpeg'), "\"></audio></div>");
    }
    var fileInner = "\n            <span class=\"chat-file-icon\">FILE</span>\n            <span>\n                <span class=\"chat-file-name d-block\">".concat(escapeHtml(filename || 'Document'), "</span>\n                <span class=\"chat-file-meta d-block\">").concat(escapeHtml(mediaMime || type.toUpperCase()), "</span>\n            </span>");
    if (isPreviewableMediaUrl(mediaUrl)) {
      return "<div class=\"chat-media\"><a href=\"".concat(escapeHtml(mediaUrl), "\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"chat-file-card\">").concat(fileInner, "</a></div>");
    }
    return "<div class=\"chat-media\"><div class=\"chat-file-card\">".concat(fileInner, "</div></div>");
  };
  var buildMessageNode = function buildMessageNode(msg) {
    var _msg$user$name, _msg$user, _msg$user$avatar, _msg$user2, _msg$id, _msg$created_at;
    var name = (_msg$user$name = (_msg$user = msg.user) === null || _msg$user === void 0 ? void 0 : _msg$user.name) !== null && _msg$user$name !== void 0 ? _msg$user$name : msg.direction === 'out' ? 'You' : 'System';
    var state = "".concat(msg.direction === 'out' ? 'Outgoing' : 'Incoming').concat(msg.status ? ' | ' + msg.status : '');
    var avatar = avatarUrl((_msg$user$avatar = (_msg$user2 = msg.user) === null || _msg$user2 === void 0 ? void 0 : _msg$user2.avatar) !== null && _msg$user$avatar !== void 0 ? _msg$user$avatar : '');
    var avatarHtml = avatar ? "<img src=\"".concat(escapeHtml(avatar), "\" alt=\"").concat(escapeHtml(name), "\">") : "<span class=\"chat-avatar-fallback ".concat(avatarTone(name), "\">").concat(escapeHtml(initials(name)), "</span>");
    var mediaHtml = buildMediaHtml(msg);
    var bodyHtml = msg.body ? "<div class=\"chat-message-text\">".concat(formatMessageHtml(msg.body), "</div>") : '';
    var wrapper = document.createElement('div');
    wrapper.className = "chat-row chat-row-".concat(msg.direction === 'out' ? 'out' : 'in', " d-flex align-items-end gap-2");
    wrapper.dataset.messageId = (_msg$id = msg.id) !== null && _msg$id !== void 0 ? _msg$id : '';
    wrapper.innerHTML = "\n            <div class=\"chat-avatar\">".concat(avatarHtml, "</div>\n            <div class=\"chat-bubble ").concat(msg.direction === 'out' ? 'chat-bubble-out' : 'chat-bubble-in', "\">\n                <div class=\"chat-head d-flex align-items-center justify-content-between gap-2\">\n                    <span class=\"chat-sender\">").concat(escapeHtml(name), "</span>\n                    <span class=\"chat-state\">").concat(escapeHtml(state), "</span>\n                </div>\n                <div class=\"chat-message-body\">").concat(mediaHtml).concat(bodyHtml, "</div>\n                <div class=\"chat-meta\">").concat((_msg$created_at = msg.created_at) !== null && _msg$created_at !== void 0 ? _msg$created_at : '', "</div>\n            </div>");
    return wrapper;
  };
  var getRenderedRows = function getRenderedRows() {
    return chatPane ? Array.from(chatPane.querySelectorAll('.chat-row[data-message-id]')) : [];
  };
  var refreshOldestVisibleMessage = function refreshOldestVisibleMessage() {
    var firstVisibleRow = getRenderedRows()[0];
    var firstVisibleId = Number(firstVisibleRow === null || firstVisibleRow === void 0 ? void 0 : firstVisibleRow.dataset.messageId);
    if (Number.isFinite(firstVisibleId) && firstVisibleId > 0) {
      oldestMessageId = firstVisibleId;
    }
  };
  var ensureOlderLoaderVisible = function ensureOlderLoaderVisible() {
    if (!chatLoader) {
      return;
    }
    chatLoader.classList.remove('d-none');
    chatLoader.textContent = 'Scroll up to load older messages...';
  };
  var pruneRenderedMessages = function pruneRenderedMessages() {
    var mode = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'keep-latest';
    if (!chatPane) {
      return;
    }
    var rows = getRenderedRows();
    var overflow = rows.length - maxRenderedMessages;
    if (overflow <= 0) {
      return;
    }
    var rowsToRemove = mode === 'keep-top' ? rows.slice(-overflow) : rows.slice(0, overflow);
    var removedTopHeight = 0;
    rowsToRemove.forEach(function (row) {
      var rowId = Number(row.dataset.messageId);
      if (mode !== 'keep-top') {
        removedTopHeight += row.getBoundingClientRect().height;
      }
      if (Number.isFinite(rowId) && rowId > 0) {
        renderedMessageIds["delete"](rowId);
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
  var appendIfNew = function appendIfNew(msg) {
    var shouldScrollToBottom = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
    var id = Number(msg.id);
    if (Number.isFinite(id) && renderedMessageIds.has(id)) {
      return false;
    }
    if (Number.isFinite(id) && id > 0) {
      renderedMessageIds.add(id);
      latestMessageId = Math.max(Number(latestMessageId || 0), id);
    }
    var wrapper = buildMessageNode(msg);
    chatPane === null || chatPane === void 0 || chatPane.appendChild(wrapper);
    bindDeferredMediaScroll(wrapper);
    if (shouldScrollToBottom && chatPane) {
      scrollChatToBottom();
    }
    pruneRenderedMessages('keep-latest');
    updateMessageRelatedUi(msg);
    return true;
  };
  var loadOlderMessages = /*#__PURE__*/function () {
    var _ref2 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee2() {
      var _payload$oldest_id, prevHeight, prevTop, url, response, payload, list, _chatHistorySentinel$, fragment, insertAnchor, newHeight, _t;
      return _regenerator().w(function (_context2) {
        while (1) switch (_context2.p = _context2.n) {
          case 0:
            if (!(!chatPane || loadingOlder || !hasMoreMessages || !oldestMessageId || !messagesEndpoint)) {
              _context2.n = 1;
              break;
            }
            return _context2.a(2);
          case 1:
            loadingOlder = true;
            if (chatLoader) {
              chatLoader.textContent = 'Loading older messages...';
            }
            _context2.p = 2;
            prevHeight = chatPane.scrollHeight;
            prevTop = chatPane.scrollTop;
            url = "".concat(messagesEndpoint, "?before_id=").concat(encodeURIComponent(oldestMessageId), "&limit=30");
            _context2.n = 3;
            return fetch(url, {
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            });
          case 3:
            response = _context2.v;
            if (response.ok) {
              _context2.n = 4;
              break;
            }
            throw new Error('load failed');
          case 4:
            _context2.n = 5;
            return response.json();
          case 5:
            payload = _context2.v;
            list = Array.isArray(payload.messages) ? payload.messages : [];
            if (list.length) {
              fragment = document.createDocumentFragment();
              list.forEach(function (msg) {
                var id = Number(msg.id);
                if (Number.isFinite(id) && renderedMessageIds.has(id)) {
                  return;
                }
                if (Number.isFinite(id) && id > 0) {
                  renderedMessageIds.add(id);
                }
                var node = buildMessageNode(msg);
                bindDeferredMediaScroll(node);
                fragment.appendChild(node);
              });
              insertAnchor = (_chatHistorySentinel$ = chatHistorySentinel === null || chatHistorySentinel === void 0 ? void 0 : chatHistorySentinel.nextSibling) !== null && _chatHistorySentinel$ !== void 0 ? _chatHistorySentinel$ : chatPane.firstChild;
              chatPane.insertBefore(fragment, insertAnchor);
              newHeight = chatPane.scrollHeight;
              chatPane.scrollTop = newHeight - prevHeight + prevTop;
              pruneRenderedMessages('keep-top');
            }
            oldestMessageId = (_payload$oldest_id = payload.oldest_id) !== null && _payload$oldest_id !== void 0 ? _payload$oldest_id : oldestMessageId;
            hasMoreMessages = Boolean(payload.has_more);
            if (chatLoader) {
              if (!hasMoreMessages) {
                chatLoader.classList.add('d-none');
              } else {
                chatLoader.textContent = 'Scroll up to load older messages...';
              }
            }
            _context2.n = 7;
            break;
          case 6:
            _context2.p = 6;
            _t = _context2.v;
            if (chatLoader) {
              chatLoader.textContent = 'Failed to load older messages.';
            }
          case 7:
            _context2.p = 7;
            loadingOlder = false;
            return _context2.f(7);
          case 8:
            return _context2.a(2);
        }
      }, _callee2, null, [[2, 6, 7, 8]]);
    }));
    return function loadOlderMessages() {
      return _ref2.apply(this, arguments);
    };
  }();
  var pollLatestMessages = /*#__PURE__*/function () {
    var _ref3 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee3() {
      var url, response, payload, list, shouldStickBottom, _t2;
      return _regenerator().w(function (_context3) {
        while (1) switch (_context3.p = _context3.n) {
          case 0:
            if (!(pollingInFlight || !isPageActive() || !messagesSinceEndpoint)) {
              _context3.n = 1;
              break;
            }
            return _context3.a(2);
          case 1:
            pollingInFlight = true;
            _context3.p = 2;
            url = "".concat(messagesSinceEndpoint, "?after_id=").concat(encodeURIComponent(latestMessageId || 0), "&limit=20");
            _context3.n = 3;
            return fetch(url, {
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
              }
            });
          case 3:
            response = _context3.v;
            if (response.ok) {
              _context3.n = 4;
              break;
            }
            throw new Error('poll failed');
          case 4:
            _context3.n = 5;
            return response.json();
          case 5:
            payload = _context3.v;
            list = Array.isArray(payload.messages) ? payload.messages : [];
            if (list.length) {
              shouldStickBottom = isNearBottom();
              list.forEach(function (msg) {
                var inserted = appendIfNew(msg, shouldStickBottom);
                if (!inserted) {
                  return;
                }
                var incomingOutOfView = msg.direction === 'in' && (document.hidden || !document.hasFocus() || !isChatVisible());
                if (incomingOutOfView) {
                  var _msg$user$name2, _msg$user3, _msg$body;
                  unseenIncomingCount += 1;
                  sidebarUnreadCount += 1;
                  refreshUnreadUi();
                  var senderName = (_msg$user$name2 = (_msg$user3 = msg.user) === null || _msg$user3 === void 0 ? void 0 : _msg$user3.name) !== null && _msg$user$name2 !== void 0 ? _msg$user$name2 : 'Contact';
                  notifyIncoming(senderName, (_msg$body = msg.body) !== null && _msg$body !== void 0 ? _msg$body : '');
                } else if (msg.direction === 'in') {
                  clearUnread();
                }
              });
            }
            if (payload.latest_id) {
              latestMessageId = Math.max(Number(latestMessageId || 0), Number(payload.latest_id || 0));
            }
            _context3.n = 6;
            return syncLiveChatStatus();
          case 6:
            _context3.n = 8;
            break;
          case 7:
            _context3.p = 7;
            _t2 = _context3.v;
          case 8:
            _context3.p = 8;
            pollingInFlight = false;
            return _context3.f(8);
          case 9:
            return _context3.a(2);
        }
      }, _callee3, null, [[2, 7, 8, 9]]);
    }));
    return function pollLatestMessages() {
      return _ref3.apply(this, arguments);
    };
  }();
  var stopPolling = function stopPolling() {
    if (!pollingTimer) {
      return;
    }
    clearInterval(pollingTimer);
    pollingTimer = null;
  };
  var startPolling = function startPolling() {
    if (pollingTimer) {
      return;
    }
    pollingTimer = setInterval(pollLatestMessages, 4000);
  };
  function refreshPollingState() {
    if (!isPageActive()) {
      stopPolling();
      return;
    }
    startPolling();
  }
  var pingPresenceHeartbeat = /*#__PURE__*/function () {
    var _ref4 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee4() {
      var _t3;
      return _regenerator().w(function (_context4) {
        while (1) switch (_context4.p = _context4.n) {
          case 0:
            if (!(channel !== 'live_chat' || !presenceHeartbeatEndpoint || document.hidden)) {
              _context4.n = 1;
              break;
            }
            return _context4.a(2);
          case 1:
            _context4.p = 1;
            _context4.n = 2;
            return fetch(presenceHeartbeatEndpoint, {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json'
              }
            });
          case 2:
            _context4.n = 4;
            break;
          case 3:
            _context4.p = 3;
            _t3 = _context4.v;
          case 4:
            return _context4.a(2);
        }
      }, _callee4, null, [[1, 3]]);
    }));
    return function pingPresenceHeartbeat() {
      return _ref4.apply(this, arguments);
    };
  }();
  var syncLiveChatStatus = /*#__PURE__*/function () {
    var _ref5 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee5() {
      var _payload$typing, response, payload, _t4;
      return _regenerator().w(function (_context5) {
        while (1) switch (_context5.p = _context5.n) {
          case 0:
            if (!(channel !== 'live_chat' || !liveChatStatusEndpoint || document.hidden)) {
              _context5.n = 1;
              break;
            }
            return _context5.a(2);
          case 1:
            _context5.p = 1;
            _context5.n = 2;
            return fetch(liveChatStatusEndpoint, {
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
              }
            });
          case 2:
            response = _context5.v;
            if (response.ok) {
              _context5.n = 3;
              break;
            }
            throw new Error('status failed');
          case 3:
            _context5.n = 4;
            return response.json();
          case 4:
            payload = _context5.v;
            setVisitorTypingState(Boolean(payload === null || payload === void 0 || (_payload$typing = payload.typing) === null || _payload$typing === void 0 ? void 0 : _payload$typing.visitor));
            updateAssignmentUi((payload === null || payload === void 0 ? void 0 : payload.assignment) || null);
            _context5.n = 6;
            break;
          case 5:
            _context5.p = 5;
            _t4 = _context5.v;
          case 6:
            return _context5.a(2);
        }
      }, _callee5, null, [[1, 5]]);
    }));
    return function syncLiveChatStatus() {
      return _ref5.apply(this, arguments);
    };
  }();
  var sendLiveChatTyping = /*#__PURE__*/function () {
    var _ref6 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee6() {
      var text, nowMs, _t5;
      return _regenerator().w(function (_context6) {
        while (1) switch (_context6.p = _context6.n) {
          case 0:
            if (!(channel !== 'live_chat' || !liveChatAgentTypingEndpoint || !messageInput)) {
              _context6.n = 1;
              break;
            }
            return _context6.a(2);
          case 1:
            text = (messageInput.value || '').trim();
            if (text) {
              _context6.n = 2;
              break;
            }
            return _context6.a(2);
          case 2:
            nowMs = Date.now();
            if (!(nowMs - typingLastSentAt < 2500)) {
              _context6.n = 3;
              break;
            }
            return _context6.a(2);
          case 3:
            typingLastSentAt = nowMs;
            _context6.p = 4;
            _context6.n = 5;
            return fetch(liveChatAgentTypingEndpoint, {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json'
              }
            });
          case 5:
            _context6.n = 7;
            break;
          case 6:
            _context6.p = 6;
            _t5 = _context6.v;
          case 7:
            return _context6.a(2);
        }
      }, _callee6, null, [[4, 6]]);
    }));
    return function sendLiveChatTyping() {
      return _ref6.apply(this, arguments);
    };
  }();
  var applyConversationFilters = function applyConversationFilters() {
    var query = normalize(conversationSearch === null || conversationSearch === void 0 ? void 0 : conversationSearch.value);
    var visibleCount = 0;
    conversationItems.forEach(function (item) {
      var name = normalize(item.dataset.name);
      var assignment = item.dataset.assignment || 'assigned';
      var matchFilter = activeFilter === 'all' || assignment === activeFilter;
      var matchQuery = !query || name.includes(query);
      var visible = matchFilter && matchQuery;
      item.classList.toggle('d-none', !visible);
      if (visible) {
        visibleCount++;
      }
    });
    if (conversationEmpty) {
      conversationEmpty.classList.toggle('d-none', visibleCount > 0 || !conversationItems.length);
    }
  };
  var renderUserResults = function renderUserResults(items, query) {
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
    items.forEach(function (item) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'user-search-item';
      btn.textContent = item.text;
      btn.dataset.userId = item.id;
      btn.addEventListener('click', function () {
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
  var searchUsersRemote = /*#__PURE__*/function () {
    var _ref7 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee7(query) {
      var url, response, payload, _t6;
      return _regenerator().w(function (_context7) {
        while (1) switch (_context7.p = _context7.n) {
          case 0:
            if (!(!startUserPicker || !startUserId || !startUserResults || !startUserSearchEndpoint)) {
              _context7.n = 1;
              break;
            }
            return _context7.a(2);
          case 1:
            startUserId.value = '';
            if (!(query.length < 2)) {
              _context7.n = 2;
              break;
            }
            renderUserResults([], query);
            return _context7.a(2);
          case 2:
            if (userSearchController) {
              userSearchController.abort();
            }
            userSearchController = new AbortController();
            startUserResults.innerHTML = '<div class="user-search-note">Searching...</div>';
            startUserResults.classList.add('show');
            _context7.p = 3;
            url = "".concat(startUserSearchEndpoint, "?q=").concat(encodeURIComponent(query), "&limit=15");
            _context7.n = 4;
            return fetch(url, {
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json'
              },
              signal: userSearchController.signal
            });
          case 4:
            response = _context7.v;
            if (response.ok) {
              _context7.n = 5;
              break;
            }
            throw new Error('search failed');
          case 5:
            _context7.n = 6;
            return response.json();
          case 6:
            payload = _context7.v;
            renderUserResults(Array.isArray(payload.items) ? payload.items : [], query);
            _context7.n = 8;
            break;
          case 7:
            _context7.p = 7;
            _t6 = _context7.v;
            startUserResults.innerHTML = '<div class="user-search-note">Failed to search users.</div>';
            startUserResults.classList.add('show');
          case 8:
            return _context7.a(2);
        }
      }, _callee7, null, [[3, 7]]);
    }));
    return function searchUsersRemote(_x) {
      return _ref7.apply(this, arguments);
    };
  }();
  var sendMessageForm = /*#__PURE__*/function () {
    var _ref8 = _asyncToGenerator(/*#__PURE__*/_regenerator().m(function _callee8(formEl) {
      var submitBtn, response, payload, _payload$errors, _payload$errors2, _payload$errors3, message, msg, _t7;
      return _regenerator().w(function (_context8) {
        while (1) switch (_context8.p = _context8.n) {
          case 0:
            if (!(!formEl || sendInFlight)) {
              _context8.n = 1;
              break;
            }
            return _context8.a(2);
          case 1:
            sendInFlight = true;
            setSendFeedback('');
            submitBtn = formEl.querySelector('button[type="submit"]');
            if (submitBtn) {
              submitBtn.disabled = true;
            }
            _context8.p = 2;
            _context8.n = 3;
            return fetch(formEl.action, {
              method: 'POST',
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json'
              },
              body: new FormData(formEl)
            });
          case 3:
            response = _context8.v;
            _context8.n = 4;
            return response.json()["catch"](function () {
              return {};
            });
          case 4:
            payload = _context8.v;
            if (response.ok) {
              _context8.n = 5;
              break;
            }
            message = (payload === null || payload === void 0 ? void 0 : payload.message) || (payload === null || payload === void 0 || (_payload$errors = payload.errors) === null || _payload$errors === void 0 || (_payload$errors = _payload$errors.body) === null || _payload$errors === void 0 ? void 0 : _payload$errors[0]) || (payload === null || payload === void 0 || (_payload$errors2 = payload.errors) === null || _payload$errors2 === void 0 || (_payload$errors2 = _payload$errors2.template_id) === null || _payload$errors2 === void 0 ? void 0 : _payload$errors2[0]) || (payload === null || payload === void 0 || (_payload$errors3 = payload.errors) === null || _payload$errors3 === void 0 || (_payload$errors3 = _payload$errors3.media_file) === null || _payload$errors3 === void 0 ? void 0 : _payload$errors3[0]) || 'Failed to send message.';
            setSendFeedback(message, 'danger');
            return _context8.a(2);
          case 5:
            msg = payload === null || payload === void 0 ? void 0 : payload.message;
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
            _context8.n = 7;
            break;
          case 6:
            _context8.p = 6;
            _t7 = _context8.v;
            setSendFeedback('Network error while sending message.', 'danger');
          case 7:
            _context8.p = 7;
            sendInFlight = false;
            if (submitBtn) {
              submitBtn.disabled = false;
            }
            return _context8.f(7);
          case 8:
            return _context8.a(2);
        }
      }, _callee8, null, [[2, 6, 7, 8]]);
    }));
    return function sendMessageForm(_x2) {
      return _ref8.apply(this, arguments);
    };
  }();
  var initTemplateSelector = function initTemplateSelector() {
    var tplSelect = document.getElementById('template_id');
    var tplVars = document.getElementById('tpl-vars');
    var tplLang = document.getElementById('tpl_lang');
    var tplPreview = document.getElementById('tpl-preview');
    function extractPlaceholders(text) {
      if (!text) {
        return [];
      }
      var matches = _toConsumableArray(text.matchAll(/\{\{(\d+)\}\}/g));
      return _toConsumableArray(new Set(matches.map(function (match) {
        return parseInt(match[1], 10);
      }))).sort(function (a, b) {
        return a - b;
      });
    }
    function renderVars() {
      var _opt$textContent$matc, _opt$textContent$matc2;
      if (!tplSelect || !tplVars || !tplLang || !tplPreview) {
        return;
      }
      var opt = tplSelect.selectedOptions[0];
      if (!opt) {
        return;
      }
      var body = opt.getAttribute('data-body') || '';
      var header = opt.getAttribute('data-header') || '';
      var placeholders = _toConsumableArray(new Set([].concat(_toConsumableArray(extractPlaceholders(body)), _toConsumableArray(extractPlaceholders(header)))));
      tplVars.innerHTML = '';
      tplLang.value = (_opt$textContent$matc = (_opt$textContent$matc2 = opt.textContent.match(/\((.*?)\)/)) === null || _opt$textContent$matc2 === void 0 ? void 0 : _opt$textContent$matc2[1]) !== null && _opt$textContent$matc !== void 0 ? _opt$textContent$matc : '';
      tplPreview.innerHTML = body ? "Preview body:<br>".concat(formatMessageHtml(body)) : '';
      placeholders.forEach(function (idx) {
        var col = document.createElement('div');
        col.className = 'col-md-6';
        col.innerHTML = "\n                    <div class=\"input-group\">\n                        <span class=\"input-group-text\">&#123;&#123;".concat(idx, "&#125;&#125;</span>\n                        <input type=\"text\" class=\"form-control\" name=\"template_params[").concat(idx, "]\" placeholder=\"Isi untuk &#123;&#123;").concat(idx, "&#125;&#125;\" required>\n                    </div>");
        tplVars.appendChild(col);
      });
      if (!placeholders.length) {
        tplVars.innerHTML = '<div class="text-muted small ms-1">Tidak ada placeholder.</div>';
      }
    }
    tplSelect === null || tplSelect === void 0 || tplSelect.addEventListener('change', renderVars);
    if (tplSelect) {
      renderVars();
    }
  };
  bindDeferredMediaScroll(chatPane);
  ensureLatestMessageVisible();
  setTimeout(function () {
    return ensureLatestMessageVisible();
  }, 80);
  initMobileView();
  initWebNotifButton();
  initTemplateSelector();
  refreshUnreadUi();
  refreshPollingState();
  applyConversationFilters();
  conversationItems.forEach(function (item) {
    item.addEventListener('click', function () {
      if (!isMobile()) {
        return;
      }
      sessionStorage.setItem('conv-open-chat', '1');
    });
  });
  filterTabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      activeFilter = tab.dataset.filter || 'all';
      filterTabs.forEach(function (item) {
        return item.classList.toggle('active', item === tab);
      });
      applyConversationFilters();
    });
  });
  var searchTimer = null;
  conversationSearch === null || conversationSearch === void 0 || conversationSearch.addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyConversationFilters, 120);
  });
  mobileBackInbox === null || mobileBackInbox === void 0 || mobileBackInbox.addEventListener('click', function () {
    return setMobileView('inbox');
  });
  mobileOpenDetail === null || mobileOpenDetail === void 0 || mobileOpenDetail.addEventListener('click', function () {
    return setMobileView('detail');
  });
  mobileBackChat === null || mobileBackChat === void 0 || mobileBackChat.addEventListener('click', function () {
    return setMobileView('chat');
  });
  window.addEventListener('focus', function () {
    if (isChatVisible()) {
      clearUnread();
    }
    refreshPollingState();
  });
  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'visible' && isChatVisible()) {
      clearUnread();
    }
    refreshPollingState();
  });
  window.addEventListener('resize', function () {
    if (!isMobile()) {
      dashboardRoot.classList.remove('mobile-view-inbox', 'mobile-view-chat', 'mobile-view-detail');
      ensureLatestMessageVisible();
      if (document.hasFocus()) {
        clearUnread();
      }
    } else if (!dashboardRoot.classList.contains('mobile-view-inbox') && !dashboardRoot.classList.contains('mobile-view-chat') && !dashboardRoot.classList.contains('mobile-view-detail')) {
      setMobileView('inbox');
    }
  });
  window.addEventListener('load', function () {
    return ensureLatestMessageVisible();
  });
  startUserPicker === null || startUserPicker === void 0 || startUserPicker.addEventListener('input', function () {
    if (startUserInvalid) {
      startUserInvalid.classList.add('d-none');
    }
    var query = (startUserPicker.value || '').trim();
    clearTimeout(userSearchTimer);
    userSearchTimer = setTimeout(function () {
      return searchUsersRemote(query);
    }, 250);
  });
  startUserPicker === null || startUserPicker === void 0 || startUserPicker.addEventListener('focus', function () {
    var query = (startUserPicker.value || '').trim();
    searchUsersRemote(query);
  });
  document.addEventListener('click', function (event) {
    if (!startUserResults || !startUserPicker) {
      return;
    }
    if (startUserResults.contains(event.target) || startUserPicker.contains(event.target)) {
      return;
    }
    hideUserResults();
  });
  startUserForm === null || startUserForm === void 0 || startUserForm.addEventListener('submit', function (event) {
    if (!(startUserId !== null && startUserId !== void 0 && startUserId.value)) {
      event.preventDefault();
      startUserPicker === null || startUserPicker === void 0 || startUserPicker.focus();
      if (startUserInvalid) {
        startUserInvalid.classList.remove('d-none');
      }
    }
  });
  if (chatPane && chatHistorySentinel && 'IntersectionObserver' in window) {
    olderMessagesObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          loadOlderMessages();
        }
      });
    }, {
      root: chatPane,
      threshold: 0.1
    });
    olderMessagesObserver.observe(chatHistorySentinel);
  } else if (chatPane) {
    chatPane.addEventListener('scroll', function () {
      if (chatPane.scrollTop <= 48) {
        loadOlderMessages();
      }
    });
  }
  sendForm === null || sendForm === void 0 || sendForm.addEventListener('submit', function (event) {
    event.preventDefault();
    sendMessageForm(sendForm);
  });
  templateForm === null || templateForm === void 0 || templateForm.addEventListener('submit', function (event) {
    event.preventDefault();
    sendMessageForm(templateForm);
  });
  mediaForm === null || mediaForm === void 0 || mediaForm.addEventListener('submit', function (event) {
    event.preventDefault();
    sendMessageForm(mediaForm);
  });
  mediaPickerButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      openMediaPicker(button.dataset.mediaPicker || 'file');
    });
  });
  mediaFileInput === null || mediaFileInput === void 0 || mediaFileInput.addEventListener('change', syncMediaComposerUi);
  mediaUploadCancel === null || mediaUploadCancel === void 0 || mediaUploadCancel.addEventListener('click', function () {
    resetMediaComposer();
    messageInput === null || messageInput === void 0 || messageInput.focus();
  });
  mediaUploadChange === null || mediaUploadChange === void 0 || mediaUploadChange.addEventListener('click', function () {
    openMediaPicker(activeMediaPickerKind);
  });
  messageInput === null || messageInput === void 0 || messageInput.addEventListener('input', function () {
    if (typingTimer) {
      clearTimeout(typingTimer);
    }
    typingTimer = setTimeout(function () {
      sendLiveChatTyping();
    }, 250);
  });
  if (lockSpan && lockedUntil) {
    var lockTimer = null;
    var tick = function tick() {
      var diff = (new Date(lockedUntil) - new Date()) / 1000;
      if (diff <= 0) {
        var _lockSpan$parentEleme;
        lockSpan.textContent = 'expired';
        (_lockSpan$parentEleme = lockSpan.parentElement) === null || _lockSpan$parentEleme === void 0 || _lockSpan$parentEleme.classList.replace('text-bg-secondary', 'text-bg-warning');
        if (lockTimer) {
          clearInterval(lockTimer);
        }
        return;
      }
      var minutes = Math.floor(diff / 60);
      var seconds = Math.floor(diff % 60);
      lockSpan.textContent = "".concat(minutes, "m ").concat(seconds.toString().padStart(2, '0'), "s");
    };
    tick();
    lockTimer = setInterval(tick, 1000);
  }
  if (window.Echo && convId > 0) {
    window.Echo["private"]("conversations.".concat(convId)).listen('App\\Modules\\Conversations\\Events\\ConversationMessageCreated', function (event) {
      var msg = event.message;
      var shouldStickBottom = isNearBottom();
      var inserted = appendIfNew(msg, shouldStickBottom);
      if (!inserted) {
        return;
      }
      var incomingOutOfView = msg.direction === 'in' && (document.hidden || !document.hasFocus() || !isChatVisible());
      if (incomingOutOfView) {
        var _msg$user$name3, _msg$user4, _msg$body2;
        unseenIncomingCount += 1;
        sidebarUnreadCount += 1;
        refreshUnreadUi();
        var senderName = (_msg$user$name3 = (_msg$user4 = msg.user) === null || _msg$user4 === void 0 ? void 0 : _msg$user4.name) !== null && _msg$user$name3 !== void 0 ? _msg$user$name3 : 'Contact';
        notifyIncoming(senderName, (_msg$body2 = msg.body) !== null && _msg$body2 !== void 0 ? _msg$body2 : '');
      } else if (msg.direction === 'in') {
        sidebarUnreadCount = Math.max(0, sidebarUnreadCount);
        clearUnread();
      }
    });
  }
  refreshPollingState();
  pingPresenceHeartbeat();
  syncLiveChatStatus();
  if (channel === 'live_chat' && presenceHeartbeatEndpoint) {
    presenceHeartbeatTimer = setInterval(pingPresenceHeartbeat, 30000);
  }
});
/******/ })()
;