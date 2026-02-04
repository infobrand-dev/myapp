import express from 'express';
import cors from 'cors';
import qrcode from 'qrcode';
import pkg from 'whatsapp-web.js';
import { Server } from 'socket.io';

const { Client, LocalAuth } = pkg;

const app = express();
app.use(cors());
app.use(express.json());

const server = app.listen(process.env.WHATSAPP_BRO_PORT || 3020, () => {
  console.log(`WhatsApp Bro bridge running on :${process.env.WHATSAPP_BRO_PORT || 3020}`);
});

const io = new Server(server, {
  cors: { origin: '*' },
});

const clients = new Map(); // clientId -> { client, latestQr, ready }

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
    io.to(clientId).emit('message', { chatId: message.from, type: message.type });
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

app.get('/chats', async (req, res) => {
  const clientId = req.query.clientId || 'default';
  const { client, state } = getClient(clientId);
  if (!state.ready) return res.status(409).json({ message: 'Client not ready', qr: state.latestQr });
  try {
    const chats = await withTimeout(client.getChats());
    const items = chats.slice(0, 50).map((chat) => ({
      id: chat.id._serialized,
      name: chat.name || chat.id.user,
      unreadCount: chat.unreadCount || 0,
      lastMessage: chat.lastMessage?.body || '',
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
    const items = messages.map((message) => {
      let body = message.body;
      if (message.hasMedia) {
        body = `[${(message.type || 'media').toUpperCase()}] ${message.caption || ''}`.trim();
      } else if (message.type && message.type !== 'chat') {
        body = `[${message.type}] ${message.body || ''}`.trim();
      }
      return {
        id: message.id.id,
        body,
        type: message.type,
        hasMedia: message.hasMedia || false,
        caption: message.caption || '',
        from: message.from,
        author: message.author || null,
        fromMe: message.fromMe,
        timestamp: new Date(message.timestamp * 1000).toLocaleString(),
      };
    });
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
    await chat.sendMessage(message);
    res.json({ ok: true });
  } catch (error) {
    res.status(500).json({ ok: false });
  }
});
