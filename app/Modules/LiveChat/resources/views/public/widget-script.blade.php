(function () {
  if (window.MyAppLiveChatLoaded) return;
  window.MyAppLiveChatLoaded = true;

  var bootstrapUrl = @json($bootstrapUrl);
  var messageUrl = @json($messageUrl);
  var pollUrl = @json($pollUrl);
  var eventsUrl = @json(route('live-chat.api.events', $widget->widget_token));
  var typingUrl = @json(route('live-chat.api.typing', $widget->widget_token));
  var storageKey = "myapp_live_chat_{{ $widget->id }}";
  var state = { visitorKey: "", visitorToken: "", latestId: 0, opened: false, pollIntervalMs: 8000, conversationStatus: "open" };
  var eventSource = null;
  var typingTimer = null;
  var lastTypingSentAt = 0;
  var sending = false;
  var renderedMessageIds = {};
  var launcherLabel = @json($widget->launcher_label ?: 'Chat');
  var widgetPosition = @json($widget->position === 'left' ? 'left' : 'right');
  var widgetLogoUrl = @json($widget->logo_url ?: '');
  var headerBgColor = @json($widget->header_bg_color ?: ($widget->theme_color ?: '#206bc4'));
  var visitorBubbleColor = @json($widget->visitor_bubble_color ?: ($widget->theme_color ?: '#206bc4'));
  var agentBubbleColor = @json($widget->agent_bubble_color ?: '#ffffff');
  var themeColor = @json($widget->theme_color ?: '#206bc4');

  try {
    var persisted = JSON.parse(localStorage.getItem(storageKey) || "{}");
    if (persisted && persisted.visitorKey) {
      state.visitorKey = persisted.visitorKey;
      state.visitorToken = persisted.visitorToken || "";
      state.latestId = parseInt(persisted.latestId || 0, 10) || 0;
    }
  } catch (e) {}

  function persist() {
    try {
      localStorage.setItem(storageKey, JSON.stringify({
        visitorKey: state.visitorKey,
        visitorToken: state.visitorToken,
        latestId: state.latestId
      }));
    } catch (e) {}
  }

  function el(tag, attrs) {
    var node = document.createElement(tag);
    attrs = attrs || {};
    Object.keys(attrs).forEach(function (key) {
      if (key === "text") node.textContent = attrs[key];
      else if (key === "html") node.innerHTML = attrs[key];
      else node.setAttribute(key, attrs[key]);
    });
    return node;
  }

  function escapeHtml(value) {
    return (value || "").toString()
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function setFeedback(message, kind) {
    feedback.textContent = message || "";
    feedback.className = "myapp-livechat-feedback" + (message ? " is-visible is-" + (kind || "danger") : "");
  }

  function setSending(nextState) {
    sending = !!nextState;
    input.disabled = sending || state.conversationStatus === "closed";
    send.disabled = sending || state.conversationStatus === "closed";
    send.textContent = sending ? "Mengirim..." : "Kirim";
  }

  function appendMessage(message) {
    var direction = message && message.direction === "out" ? "agent" : "visitor";
    var body = message && message.body ? message.body : "";
    var messageId = message && message.id ? String(message.id) : "";
    if (!body) return;
    if (messageId && renderedMessageIds[messageId]) return;
    if (messageId) renderedMessageIds[messageId] = true;
    var item = el("div", { class: "myapp-livechat-msg myapp-livechat-msg-" + direction });
    item.textContent = body;
    list.appendChild(item);
    list.scrollTop = list.scrollHeight;
  }

  function syncConversationState(payload) {
    var nextStatus = payload && payload.conversation && payload.conversation.status ? payload.conversation.status : "open";
    var presence = payload && payload.presence && payload.presence.agent ? payload.presence.agent : "offline";
    var agentTyping = Boolean(payload && payload.typing && payload.typing.agent);
    state.conversationStatus = nextStatus;
    input.disabled = sending || nextStatus === "closed";
    send.disabled = sending || nextStatus === "closed";
    if (nextStatus === "closed") {
      statusBadge.textContent = "Percakapan ditutup";
      statusBadge.className = "myapp-livechat-badge myapp-livechat-badge-closed";
    } else if (agentTyping) {
      statusBadge.textContent = "Agent sedang mengetik";
      statusBadge.className = "myapp-livechat-badge myapp-livechat-badge-open";
    } else {
      statusBadge.textContent = presence === "online" ? "Agent online" : (presence === "away" ? "Agent away" : (presence === "busy" ? "Agent busy" : "Agent offline"));
      statusBadge.className = presence === "online"
        ? "myapp-livechat-badge myapp-livechat-badge-open"
        : "myapp-livechat-badge myapp-livechat-badge-neutral";
    }
    closedNote.style.display = nextStatus === "closed" ? "block" : "none";
  }

  function api(url, options) {
    options = options || {};
    options.headers = Object.assign({ "Content-Type": "application/json" }, options.headers || {});
    return fetch(url, options).then(function (response) {
      return response.json();
    });
  }

  function bootstrap() {
    return api(bootstrapUrl, {
      method: "POST",
      body: JSON.stringify({
        visitor_key: state.visitorKey || null,
        visitor_token: state.visitorToken || null,
        page_url: window.location.href
      })
    }).then(function (payload) {
      if (payload.visitor_key) {
        state.visitorKey = payload.visitor_key;
        state.visitorToken = payload.visitor_token || "";
        state.pollIntervalMs = parseInt(payload.poll_interval_ms || 8000, 10) || 8000;
        syncConversationState(payload);
        persist();
      }
    });
  }

  function poll() {
    if (!state.opened || document.hidden || !state.visitorKey || !state.visitorToken) return;
    var url = new URL(pollUrl, bootstrapUrl);
    url.searchParams.set("visitor_key", state.visitorKey);
    url.searchParams.set("visitor_token", state.visitorToken);
    if (state.latestId > 0) url.searchParams.set("after_id", String(state.latestId));

    fetch(url.toString()).then(function (response) {
      return response.json();
    }).then(function (payload) {
      (payload.messages || []).forEach(function (message) {
        appendMessage(message);
      });
      syncConversationState(payload);
      if (payload.latest_id) {
        state.latestId = payload.latest_id;
        persist();
      }
    }).catch(function () {});
  }

  function connectStream() {
    if (!window.EventSource || !state.visitorKey || !state.visitorToken) return false;
    if (eventSource) {
      eventSource.close();
      eventSource = null;
    }

    var url = new URL(eventsUrl, bootstrapUrl);
    url.searchParams.set("visitor_key", state.visitorKey);
    url.searchParams.set("visitor_token", state.visitorToken);
    if (state.latestId > 0) url.searchParams.set("after_id", String(state.latestId));

    eventSource = new EventSource(url.toString(), { withCredentials: false });
    eventSource.addEventListener("conversation.update", function (event) {
      try {
        var payload = JSON.parse(event.data || "{}");
        (payload.messages || []).forEach(function (message) {
          appendMessage(message);
        });
        syncConversationState(payload);
        if (payload.latest_id) {
          state.latestId = payload.latest_id;
          persist();
        }
      } catch (e) {}
    });
    eventSource.onerror = function () {
      if (eventSource) {
        eventSource.close();
        eventSource = null;
      }
    };

    return true;
  }

  function sendTyping() {
    if (!state.visitorKey || !state.visitorToken || state.conversationStatus === "closed") return;
    var nowMs = Date.now();
    if ((nowMs - lastTypingSentAt) < 2500) return;
    lastTypingSentAt = nowMs;
    api(typingUrl, {
      method: "POST",
      body: JSON.stringify({
        visitor_key: state.visitorKey,
        visitor_token: state.visitorToken
      })
    }).catch(function () {});
  }

  var style = el("style", { text:
    ".myapp-livechat-root{position:fixed;" + widgetPosition + ":20px;bottom:20px;z-index:99999;font-family:Arial,sans-serif}" +
    ".myapp-livechat-button{min-width:56px;height:56px;border-radius:999px;border:0;background:" + escapeHtml(themeColor) + ";color:#fff;cursor:pointer;box-shadow:0 10px 30px rgba(0,0,0,.2);padding:0 18px;font-weight:600}" +
    ".myapp-livechat-panel{width:320px;max-width:calc(100vw - 24px);height:460px;background:#fff;border:1px solid #dce1e7;border-radius:18px;box-shadow:0 24px 60px rgba(15,23,42,.25);overflow:hidden;display:none;margin-bottom:12px}" +
    ".myapp-livechat-panel.open{display:flex;flex-direction:column}" +
    ".myapp-livechat-header{padding:14px 16px;background:" + escapeHtml(headerBgColor) + ";color:#fff}" +
    ".myapp-livechat-header-top{display:flex;align-items:center;justify-content:space-between;gap:8px}" +
    ".myapp-livechat-header-brand{display:flex;align-items:center;gap:10px;min-width:0}" +
    ".myapp-livechat-header-brand strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}" +
    ".myapp-livechat-logo{width:34px;height:34px;border-radius:999px;background:rgba(255,255,255,.18);object-fit:cover;flex-shrink:0}" +
    ".myapp-livechat-badge{font-size:11px;padding:4px 8px;border-radius:999px}" +
    ".myapp-livechat-badge-open{background:rgba(255,255,255,.18);color:#fff}" +
    ".myapp-livechat-badge-closed{background:#fff1f2;color:#be123c}" +
    ".myapp-livechat-badge-neutral{background:rgba(255,255,255,.18);color:#f8fafc}" +
    ".myapp-livechat-list{flex:1;overflow:auto;padding:14px;background:#f6f8fb;display:flex;flex-direction:column;gap:10px}" +
    ".myapp-livechat-msg{max-width:85%;padding:10px 12px;border-radius:14px;font-size:14px;line-height:1.4}" +
    ".myapp-livechat-msg-agent{background:" + escapeHtml(agentBubbleColor) + ";color:#1f2937;align-self:flex-start;border:1px solid #e5e7eb}" +
    ".myapp-livechat-msg-visitor{background:" + escapeHtml(visitorBubbleColor) + ";color:#fff;align-self:flex-end}" +
    ".myapp-livechat-form{display:flex;gap:8px;padding:12px;border-top:1px solid #e5e7eb;background:#fff}" +
    ".myapp-livechat-input{flex:1;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;font-size:14px}" +
    ".myapp-livechat-send{border:0;border-radius:10px;background:#111827;color:#fff;padding:10px 14px;cursor:pointer}" +
    ".myapp-livechat-note{display:none;padding:10px 12px;font-size:12px;background:#fff7ed;color:#9a3412;border-top:1px solid #fed7aa}" +
    ".myapp-livechat-feedback{display:none;padding:8px 12px;font-size:12px;border-top:1px solid #e5e7eb}" +
    ".myapp-livechat-feedback.is-visible{display:block}" +
    ".myapp-livechat-feedback.is-danger{background:#fff1f2;color:#be123c}" +
    ".myapp-livechat-feedback.is-success{background:#ecfdf3;color:#166534}"
  });
  document.head.appendChild(style);

  var root = el("div", { class: "myapp-livechat-root" });
  var panel = el("div", { class: "myapp-livechat-panel" });
  var brandHtml = widgetLogoUrl
    ? "<img src='" + escapeHtml(widgetLogoUrl) + "' alt='Logo' class='myapp-livechat-logo'>"
    : "<span class='myapp-livechat-logo'></span>";
  var header = el("div", { class: "myapp-livechat-header", html: "<div class='myapp-livechat-header-top'><div class='myapp-livechat-header-brand'>" + brandHtml + "<strong>{{ addslashes($widget->website_name ?: $widget->name) }}</strong></div><span class='myapp-livechat-badge myapp-livechat-badge-open'>Online</span></div><div style='font-size:12px;opacity:.9;margin-top:4px;'>{{ addslashes($widget->welcome_text ?: 'Halo, ada yang bisa kami bantu?') }}</div>" });
  var list = el("div", { class: "myapp-livechat-list" });
  var statusBadge = header.querySelector(".myapp-livechat-badge");
  var form = el("form", { class: "myapp-livechat-form" });
  var input = el("input", { class: "myapp-livechat-input", placeholder: "Tulis pesan...", autocomplete: "off" });
  var send = el("button", { class: "myapp-livechat-send", type: "submit", text: "Kirim" });
  var closedNote = el("div", { class: "myapp-livechat-note", text: "Percakapan ini ditutup oleh agent. Kirim pesan baru dari inbox untuk membuka kembali." });
  var feedback = el("div", { class: "myapp-livechat-feedback" });
  var button = el("button", { class: "myapp-livechat-button", type: "button", text: launcherLabel });

  form.appendChild(input);
  form.appendChild(send);
  panel.appendChild(header);
  panel.appendChild(list);
  panel.appendChild(closedNote);
  panel.appendChild(feedback);
  panel.appendChild(form);
  root.appendChild(panel);
  root.appendChild(button);
  document.body.appendChild(root);

  button.addEventListener("click", function () {
    state.opened = !state.opened;
    panel.classList.toggle("open", state.opened);
    if (state.opened) {
      if (!connectStream()) poll();
    } else if (eventSource) {
      eventSource.close();
      eventSource = null;
    }
  });

  form.addEventListener("submit", function (event) {
    event.preventDefault();
    var body = (input.value || "").trim();
    if (!body || !state.visitorKey || !state.visitorToken || sending) return;
    setFeedback("", "danger");
    setSending(true);

    api(messageUrl, {
      method: "POST",
      body: JSON.stringify({
        visitor_key: state.visitorKey,
        visitor_token: state.visitorToken,
        body: body,
        page_url: window.location.href
      })
    }).then(function (payload) {
      if (payload.message && payload.message.id) {
        appendMessage(payload.message);
        state.latestId = payload.message.id;
        syncConversationState(payload);
        persist();
        input.value = "";
        setFeedback("Pesan terkirim.", "success");
        window.setTimeout(function () { setFeedback("", "success"); }, 2000);
      } else {
        setFeedback("Pesan gagal dikirim. Coba lagi.", "danger");
      }
    }).catch(function () {
      setFeedback("Pesan gagal dikirim. Periksa koneksi atau allowed domains.", "danger");
    }).finally(function () {
      setSending(false);
      input.focus();
    });
  });
  input.addEventListener("input", function () {
    if (typingTimer) clearTimeout(typingTimer);
    typingTimer = window.setTimeout(sendTyping, 250);
  });

  bootstrap().then(function () {
    function pollLoop() {
      if (!eventSource) poll();
      window.setTimeout(pollLoop, state.pollIntervalMs);
    }
    pollLoop();
  });
})();
