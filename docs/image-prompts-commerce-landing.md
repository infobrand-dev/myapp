# Image Prompts — Commerce Landing Page

Dokumen ini berisi prompt detail untuk setiap placeholder image di halaman landing Commerce.
Siap digunakan untuk DALL-E 3, Midjourney, atau tools generasi gambar lainnya.

---

## IMAGE 1
**Lokasi**: Hero section — panel kanan, dalam card "Sebelum/Sesudah"
**Ukuran target**: 700 × 400px (landscape)
**Tujuan**: Menunjukkan dashboard Commerce yang sedang aktif — memberi kesan "profesional, rapi, modern"

### Prompt (English — untuk DALL-E 3 / Midjourney):
```
A clean, modern SaaS dashboard interface for an e-commerce order management system. The screen shows:
- A sidebar navigation on the left with menu items (Orders, Storefront, Payments, Shipping, Affiliate)
- Main content area showing an order list table with columns: Order ID, Product Name, Customer Name, Amount, Payment Status (colored badges: "Paid" in green, "Pending" in yellow), Shipping Status
- Top section shows 4 KPI cards: "New Orders Today: 24", "Pending Payment: 5", "Ready to Ship: 12", "Completed Today: 8"
- UI style: clean white background, subtle card shadows, orange accent color (#ea580c), modern sans-serif font
- The UI looks like a professional B2B SaaS tool — similar to Notion, Linear, or Vercel dashboard aesthetics
- Screenshot/mockup style — flat-on perspective, no device frame
- No fictional brand logos
- Aspect ratio 16:9
```

---

## IMAGE 2 ✅ SUDAH ADA
**Status**: File tersedia — simpan ke `public/img/landing/commerce/img-2.png`
**Lokasi**: Section "Masalah" — kolom kanan, sebagai visual representasi pain point
**Ukuran target**: 600 × 800px (portrait)
**Tujuan**: Visualisasi rasa "kewalahan" bisnis tanpa sistem — empati dengan calon pelanggan

### Kenapa prompt lama gagal:
Kata-kata seperti `"flat illustration"`, `"Storyset"`, `"unDraw"` adalah library kartun vektor —
AI akan langsung generate gaya simplifed/chibi. Solusi: gunakan kata kunci **editorial illustration**,
referensikan seniman atau publikasi nyata, dan tambahkan negative prompt eksplisit.

### Prompt — DALL-E 3 (via ChatGPT atau API):
> DALL-E 3 tidak mendukung `--no`, jadi masukkan negative ke dalam kalimat positif.

```
Editorial-style digital illustration of a young Indonesian professional (woman, age 28–34,
wearing a moss-green or dusty-rose blouse, realistic human proportions — not stylized or simplified)
sitting at a wooden desk, visibly overwhelmed. Her right hand presses her temple, eyes slightly
squinting in stress.

The desk and surroundings show chaos of unorganized order management:
- An open laptop with 6–8 visible browser tabs (one clearly shows a green spreadsheet, another
  a chat bubble interface, another a purple inbox — no real brand logos)
- A smartphone lying face-up on the desk, screen showing a flood of unread notification icons
  (badge numbers: 47, 12, 9) — no real brand logos
- 8–10 colorful sticky notes (yellow, pink, orange) stuck to the monitor edge and desk,
  with scrawled handwriting (illegible scribbles, not real words)
- A stack of A4 papers with printed tables, some crumpled, one falling off the desk
- An overflowing pen cup, a cold coffee mug with a coffee ring stain underneath it

Lighting: warm side-light from a window (left side), casting soft golden-hour shadows.
The mood is relatable exhaustion — not despair, just the feeling of "there must be a better way."

Color palette: warm amber and cream tones (#fff7ed, #fcd34d, #ea580c) for the background
and desk surface. Cool muted tones for the screen glow. The woman's clothing is a darker
accent color (forest green or mauve) to make her stand out.

Rendering style: semi-realistic editorial digital painting — similar to how Harvard Business
Review or Forbes magazine commissions conceptual illustrations. Painterly brush texture on
surfaces. Realistic facial features with simplified but not cartoonish anatomy. NOT a vector
graphic, NOT a cartoon, NOT anime, NOT chibi, NOT isometric, NOT pixel art.

No text readable in the image. No brand logos.
Aspect ratio 3:4 (portrait).
```

### Prompt — Midjourney v6:
```
editorial concept illustration, young Indonesian professional woman 28yo, realistic proportions,
sitting at a wooden desk looking stressed and overwhelmed, hand on temple, warm side window light,
laptop with many open tabs on screen, smartphone with notification badge numbers, colorful sticky
notes on monitor, scattered A4 papers, cold coffee mug with ring stain, warm amber cream color
palette, semi-realistic painterly style, Forbes HBR magazine editorial illustration quality,
detailed textures, soft golden shadows, no cartoon, no vector, no anime, no chibi, no flat design
--ar 3:4 --v 6 --style raw --stylize 200
```

### Negative prompt (untuk Stable Diffusion / ComfyUI):
```
Negative: cartoon, anime, chibi, flat design, vector art, svg style, unDraw, Storyset, Freepik,
clip art, simple shapes, no outline cel-shading, 2D flat, low detail, logo, brand name, text,
watermark, blurry, oversaturated, distorted face, extra fingers
```

### Tips tambahan:
- Jika masih terlalu kartun di DALL-E: tambahkan `"oil painting texture, editorial realism, painted by a concept artist"` di awal prompt
- Jika proporsi wajah aneh: tambahkan `"symmetrical face, detailed realistic eyes, natural skin texture"`
- Untuk Midjourney: coba `--style raw` dulu. Kalau masih kartun, naikkan `--stylize` ke 500–750

---

## IMAGE 3
**Lokasi**: Section "Fitur/Modul" — di bawah kartu-kartu modul, full-width
**Ukuran target**: 1200 × 300px (wide landscape)
**Tujuan**: Diagram alur yang menunjukkan semua modul Commerce terhubung satu sama lain

### Prompt (English — untuk DALL-E 3 / Midjourney):
```
A clean horizontal flow diagram illustration showing connected e-commerce operations steps:
Step 1: "Storefront" — shopping bag icon, orange (#ea580c)
→ Arrow →
Step 2: "Order Masuk" — shopping cart icon, orange-amber (#f97316)
→ Arrow →
Step 3: "Konfirmasi Bayar" — credit card icon, amber (#fb923c)
→ Arrow →
Step 4: "Antrian Kirim" — truck icon, light orange (#fdba74)
→ Arrow →
Step 5: "Fulfillment" — package check icon, soft amber
→ Arrow →
Step 6: "Selesai ✓" — checkmark circle, green

Each step is a rounded pill/card with icon above and label below, connected by gradient arrows (orange to green).
Overall aesthetic: clean white background, modern SaaS infographic style, no gradients on the background.
Style: flat design, consistent icon set (Tabler/Feather icons style), professional B2B presentation.
Text labels should be in Indonesian as written above.
Aspect ratio: 4:1 (very wide, banner-like)
```

---

## IMAGE 4
**Lokasi**: Section "Cara Kerja" — di bawah 4 langkah cara kerja
**Ukuran target**: 800 × 360px (landscape)
**Tujuan**: Screenshot/mockup UI daftar order — membuat pembaca bisa membayangkan tampilan nyatanya

### Prompt (English — untuk DALL-E 3 / Midjourney):
```
A realistic UI mockup/screenshot of an order management table inside a web application. The table shows:
- Header row: "Order #", "Produk", "Pembeli", "Total", "Status Bayar", "Status Kirim", "Aksi"
- 5-6 sample data rows with realistic Indonesian-style data:
  - Order names like "ORD-20240601-001", "ORD-20240601-002", etc.
  - Product names: "Serum Vitamin C 30ml", "Tote Bag Canvas Premium", etc.
  - Customer names: Indonesian names (Rina, Budi, Sari, etc.)
  - Payment status badges with colors: "Lunas" (green pill), "Menunggu" (yellow pill), "Dibatalkan" (red pill)
  - Shipping status badges: "Dikirim" (blue pill), "Siap Kirim" (orange pill), "Selesai" (green pill)
  - Action buttons (small icon buttons for View/Edit)
- Clean white table on white background, subtle row hover state
- Modern SaaS aesthetic: similar to Airtable or Linear
- Top filter bar with: search field, status filter dropdown, date range picker
- Flat screenshot perspective, no device frame
```

---

## IMAGE 5
**Lokasi**: Section "Dampak Nyata" — kolom kiri, sebagai visual sosial proof
**Ukuran target**: 600 × 700px (portrait, slight landscape also works)
**Tujuan**: Membangun kepercayaan dengan wajah manusia nyata — emosi positif (lega, puas, in control)

### Prompt (English — untuk DALL-E 3 / Midjourney):
```
A professional lifestyle photograph of a young Indonesian business owner (woman or man, age 28-38, wearing smart casual clothing — light colored top, clean minimal style) sitting at a modern minimalist desk, smiling while looking at an open laptop.

The laptop screen should show a colorful dashboard with charts and order data (blurred/not fully readable but visually evident it's a business dashboard).

Environment:
- Clean, modern home office or co-working space
- Warm natural window lighting from the left side
- Minimal desk decor: small plant, coffee cup
- Background slightly blurred (bokeh), neutral beige/white wall
- Warm, confident, inviting mood

Photography style:
- DSLR quality, natural colors
- NOT stock photo pose — person should look genuinely engaged, not forced smile
- Warm color temperature, soft shadows

No brand logos, no text overlays.
```

---

## IMAGE 6a, 6b, 6c
**Lokasi**: Section "Testimonial" — avatar foto masing-masing testimonial
**Ukuran target**: 120 × 120px (square, akan ditampilkan sebagai circle)
**Tujuan**: Headshot portrait untuk testimonial — membuat kutipan terasa nyata

### Prompt untuk IMAGE 6a (Rizky A. — Owner skincare):
```
Professional headshot portrait of a young Indonesian man, age 28-34, with a confident and friendly expression. He's wearing a clean white or light blue button-up shirt. Background is subtly blurred, neutral gray or warm beige. Soft, even studio-style lighting. Professional but approachable — not overly formal. Square format (1:1). High quality, realistic photography style, no illustrated.
```

### Prompt untuk IMAGE 6b (Dian M. — Manajer Operasional):
```
Professional headshot portrait of a young Indonesian woman, age 26-33, with a calm and competent expression. She's wearing smart casual clothing — dark blazer or neat top. Hair pulled back or tidy. Background is blurred, neutral light background. Natural studio lighting, soft shadows. Confident professional woman aesthetic. Square format (1:1). High quality, realistic photography style.
```

### Prompt untuk IMAGE 6c (Bimo S. — Co-founder):
```
Professional headshot portrait of a young Indonesian man, age 30-38, with an entrepreneurial and approachable look. Wearing casual-smart clothing — navy t-shirt or open collar shirt. Slight smile, direct eye contact. Background neutral, slightly blurred. Natural warm lighting. Square format (1:1). High quality, realistic photography style.
```

---

## IMAGE 7
**Lokasi**: Final CTA Band — kolom kanan, sebagai visual pemanis
**Ukuran target**: 500 × 400px (portrait-landscape)
**Tujuan**: Visual yang memberi rasa "sukses, pertumbuhan, bisnis yang berjalan lancar"

### Prompt (English — untuk DALL-E 3 / Midjourney):
```
A modern flat illustration showing a thriving small business e-commerce operation:
- Center: A cheerful storefront/shop icon with an open sign
- Around it: floating elements representing success — boxes neatly stacked (packages ready to ship), a upward trending chart/graph arrow, a phone showing order notifications, small floating stars and sparkles
- Style: clean flat illustration with light depth/shadows, vector-quality aesthetic
- Color palette: white, orange (#ea580c), amber (#f97316), with a few dark navy (#0f172a) accents — to work on the dark blue gradient background of the CTA section
- Mood: optimistic, energetic, positive — "things are running smoothly"
- No text inside the illustration
- Aspect ratio: 5:4
```

---

## Tips Penggunaan

### Untuk DALL-E 3 (via ChatGPT atau API):
- Gunakan prompt bahasa Inggris di atas langsung
- Tambahkan di akhir: `"--ar 16:9"` untuk landscape, atau sesuaikan
- Pilih style `"vivid"` untuk ilustrasi, `"natural"` untuk foto realistis

### Untuk Midjourney:
- Paste prompt langsung di Discord bot
- Tambahkan `--ar 16:9` / `--ar 1:1` / `--ar 4:1` sesuai spesifikasi
- Tambahkan `--v 6` untuk kualitas terbaik
- Untuk foto realistis: tambahkan `--style raw`
- Untuk ilustrasi flat: tambahkan `--style raw --stylize 50`

### Untuk Stable Diffusion:
- Gunakan model: `dreamshaper_8` untuk ilustrasi, `realistic_vision_v5` untuk foto
- CFG Scale: 7-9
- Steps: 30-40

### Ukuran file yang direkomendasikan untuk web:
| Image | Format | Max Size |
|-------|--------|----------|
| 1, 3, 4 | WebP | 150 KB |
| 2, 5 | WebP | 200 KB |
| 6a, 6b, 6c | WebP | 50 KB |
| 7 | WebP | 120 KB |

Setelah generate, simpan di: `public/img/landing/commerce/`
Dan ganti placeholder di blade dengan: `<img src="{{ asset('img/landing/commerce/img-N.webp') }}" alt="..." ...>`
