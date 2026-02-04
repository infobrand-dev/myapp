import express from 'express';
import cors from 'cors';
import qrcode from 'qrcode';
import pkg from 'whatsapp-web.js';

const { Client, LocalAuth } = pkg;

const app = express();
app.use(cors());
app.use(express.json());

const client = new Client({
  authStrategy: new LocalAuth({ clientId: 'whatsapp-bro' }),
  puppeteer: { headless: true },
});

let latestQr = null;

client.on('qr', async (qr) => {
  latestQr = await qrcode.toDataURL(qr);
});

client.on('ready', () => {
  latestQr = null;
});

client.on('authenticated', () => {
  latestQr = null;
});

client.on('auth_failure', () => {
  latestQr = null;
});

client.on('disconnected', () => {
  latestQr = null;
});

client.initialize();

app.get('/status', async (req, res) => {
  const info = client.info ? { name: client.info.pushname, wid: client.info.wid?.user } : null;
  const ready = Boolean(client.info);
  res.json({ ready, qr: latestQr, info });
});

app.post('/logout', async (req, res) => {
  try {
    await client.logout();
  } catch (error) {
    // ignore
  }
  latestQr = null;
  res.json({ ok: true });
});

app.get('/chats', async (req, res) => {
  try {
    const chats = await client.getChats();
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
  try {
    const { chatId } = req.params;
    const limit = Number(req.query.limit || 50);
    const chat = await client.getChatById(chatId);
    const messages = await chat.fetchMessages({ limit });
    const items = messages.map((message) => ({
      id: message.id.id,
      body: message.body,
      fromMe: message.fromMe,
      timestamp: new Date(message.timestamp * 1000).toLocaleString(),
    }));
    res.json(items);
  } catch (error) {
    res.status(500).json([]);
  }
});

app.post('/chats/:chatId/messages', async (req, res) => {
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

const port = process.env.WHATSAPP_BRO_PORT || 3020;
app.listen(port, () => {
  console.log(`WhatsApp Bro bridge running on :${port}`);
});
