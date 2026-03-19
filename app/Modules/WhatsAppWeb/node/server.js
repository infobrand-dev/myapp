import express from 'express';
import cors from 'cors';
import qrcode from 'qrcode';
import pkg from 'whatsapp-web.js';
import { Server } from 'socket.io';

const { Client, LocalAuth } = pkg;

const app = express();
app.use(cors());
app.use(express.json());

const port = process.env.WHATSAPP_WEB_PORT || process.env.WHATSAPP_BRO_PORT || 3020;

const server = app.listen(port, () => {
  console.log(`WhatsApp Web bridge running on :${port}`);
});

const io = new Server(server, {
  cors: { origin: '*' },
});

const clients = new Map(); // clientId -> { client, latestQr, ready }

// Optional forward to Laravel Conversations webhook
const forwardWebhook = process.env.WHATSAPP_WEB_WEBHOOK_URL || process.env.WHATSAPP_BRO_WEBHOOK_URL || null;
const forwardToken = process.env.WHATSAPP_WEB_WEBHOOK_TOKEN || process.env.WHATSAPP_BRO_WEBHOOK_TOKEN || null;

async function forwardMessage(clientId, payload) {
  if (!forwardWebhook || !forwardToken) return;
  try {
    await fetch(forwardWebhook, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        token: forwardToken,
        contact_id: payload.chatId || payload.from,
        contact_name: payload.author || payload.chatId || payload.from,
        message: payload.body,
        external_message_id: payload.id,
        direction: payload.fromMe ? 'out' : 'in',
        client_id: clientId,
        occurred_at: payload.timestampIso,
        type: payload.type,
        author: payload.author,
      }),
    });
  } catch (e) {
    console.error('forward webhook failed', e.message);
  }
}

function getClient(clientId = 'default') {
  if (clients.has(clientId)) return clients.get(clientId);

  const state = { latestQr: null, ready: false };
  const client = new Client({
    authStrategy: new LocalAuth({ clientId }),
    puppeteer: { headless: true },
  });

  client.on('qr', async (qr) => {
    state.latestQr = await qrcode.toDataURL(qr);
    state.ready = false;
    io.to(clientId).emit('qr', { qr: state.latestQr });
    io.to(clientId).emit('status', { ready: false, qr: state.latestQr });
  });

  const setReady = (ready) => {
    state.ready = ready;
    state.latestQr = ready ? null : state.latestQr;
    io.to(clientId).emit('status', { ready, qr: state.latestQr, info: client.info ? { name: client.info.pushname, wid: client.info.wid?.user } : null });
  };

  client.on('ready', () => setReady(true));
  client.on('authenticated', () => setReady(true));
  client.on('auth_failure', () => setReady(false));
  client.on('disconnected', () => setReady(false));

  client.on('message', (message) => {
    const eventChatId = message.fromMe ? (message.to || message.from) : message.from;
    io.to(clientId).emit('message', { chatId: eventChatId, type: message.type });
    forwardMessage(clientId, serializeMessage(message, eventChatId));
  });

  client.initialize();
  clients.set(clientId, { client, state });
  return { client, state };
}

io.on('connection', (socket) => {
  const clientId = socket.handshake.query.clientId || 'default';
  socket.join(clientId);
  const { client, state } = getClient(clientId);
  socket.emit('status', { ready: state.ready, qr: state.latestQr, info: client.info ? { name: client.info.pushname, wid: client.info.wid?.user } : null });
});

app.get('/status', async (req, res) => {
  const clientId = req.query.clientId || 'default';
  const { client, state } = getClient(clientId);
  const info = client.info ? { name: client.info.pushname, wid: client.info.wid?.user } : null;
  res.json({ ready: state.ready, qr: state.latestQr, info });
});

app.post('/logout', async (req, res) => {
  const clientId = req.query.clientId || 'default';
  const entry = clients.get(clientId);
  try {
    await entry?.client.logout();
  } catch (error) {
    // ignore
  }
  if (entry) {
    entry.state.latestQr = null;
    entry.state.ready = false;
  }
  res.json({ ok: true });
});

const withTimeout = (promise, ms = 8000) =>
  Promise.race([
    promise,
    new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), ms)),
  ]);

const parseBool = (value) => ['1', 'true', 'yes', 'on'].includes(String(value || '').toLowerCase());

const normalizeMessageBody = (message) => {
  let body = message.body;
  if (message.hasMedia) {
    body = `[${(message.type || 'media').toUpperCase()}] ${message.caption || ''}`.trim();
  } else if (message.type && message.type !== 'chat') {
    body = `[${message.type}] ${message.body || ''}`.trim();
  }

  return body;
};

const serializeMessage = (message, chatId = null) => ({
  id: message.id.id,
  body: normalizeMessageBody(message),
  type: message.type === 'chat' ? 'text' : message.type,
  hasMedia: message.hasMedia || false,
  caption: message.caption || '',
  from: message.from,
  author: message.author || null,
  fromMe: message.fromMe,
  chatId: chatId || message.from,
  timestamp: message.timestamp || null,
  timestampIso: message.timestamp ? new Date(message.timestamp * 1000).toISOString() : null,
  timestampLabel: message.timestamp ? new Date(message.timestamp * 1000).toLocaleString() : '',
});

app.get('/chats', async (req, res) => {
  const clientId = req.query.clientId || 'default';
  const { client, state } = getClient(clientId);
  if (!state.ready) return res.status(409).json({ message: 'Client not ready', qr: state.latestQr });
  try {
    const limit = Math.max(1, Math.min(Number(req.query.limit || 50), 200));
    const activeOnly = parseBool(req.query.activeOnly);
    const chats = await withTimeout(client.getChats());
    const items = chats
      .filter((chat) => !activeOnly || !chat.isArchived)
      .slice(0, limit)
      .map((chat) => ({
      id: chat.id._serialized,
      name: chat.name || chat.id.user,
      unreadCount: chat.unreadCount || 0,
      lastMessage: chat.lastMessage?.body || '',
      lastMessageAt: chat.lastMessage?.timestamp ? new Date(chat.lastMessage.timestamp * 1000).toISOString() : null,
      isGroup: Boolean(chat.isGroup),
      isArchived: Boolean(chat.isArchived),
    }));
    res.json(items);
  } catch (error) {
    res.status(500).json([]);
  }
});

app.get('/chats/:chatId/messages', async (req, res) => {
  const clientId = req.query.clientId || 'default';
  const { client, state } = getClient(clientId);
  if (!state.ready) return res.status(409).json({ message: 'Client not ready', qr: state.latestQr });
  try {
    const { chatId } = req.params;
    const limit = Number(req.query.limit || 50);
    const chat = await withTimeout(client.getChatById(chatId));
    const messages = await withTimeout(chat.fetchMessages({ limit }));
    const items = messages.map((message) => ({
      ...serializeMessage(message, chatId),
      timestamp: message.timestamp ? new Date(message.timestamp * 1000).toLocaleString() : '',
    }));
    res.json(items);
  } catch (error) {
    res.status(500).json([]);
  }
});

app.post('/chats/:chatId/messages', async (req, res) => {
  const clientId = req.query.clientId || 'default';
  const { client, state } = getClient(clientId);
  if (!state.ready) return res.status(409).json({ message: 'Client not ready', qr: state.latestQr });
  try {
    const { chatId } = req.params;
    const message = req.body.message;
    const chat = await client.getChatById(chatId);
    const sent = await chat.sendMessage(message);
    res.json({
      ok: true,
      message: {
        id: sent?.id?.id || null,
        body: sent?.body || message,
        type: sent?.type || 'chat',
        from: sent?.from || chatId,
        author: sent?.author || null,
        fromMe: sent?.fromMe ?? true,
      },
    });
  } catch (error) {
    res.status(500).json({ ok: false });
  }
});
