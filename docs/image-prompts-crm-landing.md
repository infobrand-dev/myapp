# Image Prompts — CRM Landing Page

Dokumen prompt detail untuk setiap placeholder image di halaman landing CRM.
**Pelajaran dari Commerce:** Hindari kata "flat illustration", "Storyset", "unDraw" — trigger kartun.
Gunakan referensi editorial (Forbes, HBR, Fast Company) + negative prompt eksplisit.

Tema warna CRM: **biru/indigo** (#2563eb, #3b82f6, #eff6ff, #dbeafe)

---

## IMAGE 1
**Lokasi**: Hero section — dalam card kanan, di bawah panel Sebelum/Sesudah
**Ukuran target**: 700 × 360px (landscape)
**Tujuan**: Memberi gambaran nyata tampilan CRM — pipeline kanban yang rapi dan profesional

### Prompt — DALL-E 3:
```
A high-fidelity UI mockup screenshot of a CRM pipeline kanban board inside a modern SaaS
web application. The board shows 5 columns representing sales stages:

Column 1 "New Lead" — 3 deal cards (blue left border)
Column 2 "Contacted" — 4 deal cards (indigo left border)
Column 3 "Proposal Sent" — 2 deal cards (violet left border)
Column 4 "Negotiation" — 2 deal cards (amber left border)
Column 5 "Closing" — 1 deal card (green left border)

Each deal card shows:
- Customer company name (generic Indonesian-sounding: "PT Maju Bersama", "CV Sejahtera", etc.)
- Estimated deal value (e.g., "Rp 45.000.000")
- A small circular avatar photo placeholder with initials
- A small colored badge showing urgency (red "Overdue", yellow "Today", gray "Upcoming")
- A thin horizontal progress bar at the bottom

Top of screen: a minimal toolbar with search bar, "Add Deal" button (dark), and filter pills.
Left sidebar: navigation icons (Pipeline, Contacts, Follow-up, Reports, Settings).

UI aesthetic: clean white background, subtle card shadows (box-shadow 0 2px 8px rgba),
blue accent color (#2563eb), Inter or Plus Jakarta Sans font style.
Similar aesthetic to Linear.app, Notion, or Pipedrive.
Flat-on screenshot perspective — no device frame, no perspective distortion.
No fictional brand logos. No real CRM tool logos.
Aspect ratio 16:9.
```

### Prompt — Midjourney v6:
```
high fidelity SaaS CRM kanban pipeline dashboard UI screenshot, 5 stage columns New Lead
Contacted Proposal Negotiation Closing, deal cards with Indonesian company names and deal values
in Rupiah, colored status badges overdue today upcoming, blue indigo accent color scheme,
clean white background, subtle card shadows, minimal sidebar navigation, professional B2B
SaaS aesthetic similar to Linear or Pipedrive, flat-on perspective no device frame
--ar 16:9 --v 6 --style raw
```

### Negative prompt (SD):
```
Negative: cartoon, anime, isometric, 3D rendered, perspective distortion, device mockup frame,
Mac/Windows chrome, stock photo, photo of person, blurry, watermark, logo
```

---

## IMAGE 2
**Lokasi**: Section "Masalah" — kolom kanan
**Ukuran target**: 600 × 760px (portrait)
**Tujuan**: Visual empati — sales rep yang kewalahan tanpa sistem yang jelas

### ⚠️ Pelajaran dari Commerce:
Jangan gunakan: `"flat illustration"`, `"Storyset"`, `"unDraw"`, `"cartoon"`, `"friendly"`
Gunakan: `"editorial concept art"`, `"semi-realistic"`, `"HBR/Forbes illustration quality"`

### Prompt — DALL-E 3:
```
Editorial concept illustration of a young Indonesian male sales representative (age 25–32,
wearing a smart casual light blue oxford shirt, realistic human proportions — not stylized,
not simplified, not cartoon) standing next to a desk, looking overwhelmed and slightly
frustrated. He holds a smartphone in one hand.

Around him, floating visual metaphors of chaos representing unorganized lead management:
- Multiple overlapping speech bubbles from different directions (representing WhatsApp, email,
  phone calls — no real brand logos, just abstract chat bubble shapes)
- A scattered stack of business cards on the desk
- A notebook open with messy handwriting (illegible scribbles, not real words)
- A laptop showing a cluttered spreadsheet (rows of data, no real brand logos)
- Small sticky notes with "Follow up???" scrawled on them
- A clock on the wall showing 6pm (late hour)
- A whiteboard in the background covered in erased marks and scattered notes

Lighting: cool blue-white office lighting, slight warm accent from the laptop screen glow.
The overall mood: relatable stress of "I know I'm missing something important."

Color palette: cool blue and white tones (#eff6ff, #dbeafe, #2563eb) for the background
and desk. His clothing is a medium blue that harmonizes with the palette.
One or two warm amber accent elements (sticky note color, clock hands) as contrast.

Rendering style: semi-realistic editorial digital painting. Think Fast Company or Harvard
Business Review commissioned concept art. Painterly textures on surfaces. Realistic face
anatomy with expressive but natural features. Clean composition with clear focal point on
the person.

NOT a vector graphic. NOT a cartoon. NOT anime. NOT chibi. NOT isometric.
NOT a flat 2D illustration. NOT stock photography.

No text readable in the image. No brand logos. No app names.
Aspect ratio 3:4 (portrait).
```

### Prompt — Midjourney v6:
```
editorial concept illustration, young Indonesian male professional 28yo, realistic proportions,
smart casual light blue oxford shirt, standing at office desk looking stressed and overwhelmed,
holding smartphone, surrounded by floating chat bubbles sticky notes scattered business cards
open messy spreadsheet laptop, cool blue and white office color palette, semi-realistic
painterly style, Fast Company HBR editorial illustration quality, expressive realistic face,
soft cool office lighting with warm laptop glow accent, no cartoon no vector no anime no chibi
--ar 3:4 --v 6 --style raw --stylize 250
```

### Negative prompt (SD):
```
Negative: cartoon, anime, chibi, flat vector, unDraw, Storyset, Freepik clip art, simple shapes,
cel shading, 2D flat, low detail, distorted face, extra fingers, text readable, brand logo,
watermark, blurry background in focus, oversaturated, isometric, 3D render style
```

---

## IMAGE 3
**Lokasi**: Section "Fitur" — di bawah grid kartu fitur, full-width
**Ukuran target**: 1200 × 280px (ultra-wide landscape / banner)
**Tujuan**: Diagram alur CRM dari lead masuk sampai deal closed

### Prompt — DALL-E 3:
```
A clean, modern horizontal flow diagram illustration showing the CRM sales process steps,
designed for a B2B SaaS landing page:

Step 1: "Lead Masuk" — person/contact icon, dark blue (#1e3a8a)
→ Animated gradient arrow →
Step 2: "Dicatat ke Pipeline" — kanban/list icon, blue (#2563eb)
→ Arrow →
Step 3: "Follow-Up Queue" — bell/reminder icon, indigo (#4f46e5)
→ Arrow →
Step 4: "Customer 360" — user profile with timeline icon, violet (#7c3aed)
→ Arrow →
Step 5: "Deal Closing" — handshake icon, teal (#0d9488)
→ Arrow →
Step 6: "Won ✓" — trophy/checkmark icon, green (#16a34a)

Each step is a rounded pill card (height ~80px) with:
- Icon centered at top
- Bold label text below

Cards connected by thin gradient arrows flowing left to right.
Background: clean white (#ffffff), no gradient background.
Overall feel: crisp SaaS product infographic, similar to what Notion or HubSpot uses
in their product documentation.

Style: clean digital illustration, NOT cartoon, NOT 3D, NOT isometric.
Text labels are in Indonesian as written above.
Aspect ratio 4:1 (very wide).
```

### Prompt — Midjourney v6:
```
clean horizontal SaaS flow diagram, 6 steps CRM pipeline process Lead Masuk Pipeline
Follow-Up Customer 360 Closing Won, rounded pill cards with icons and Indonesian labels,
gradient arrows connecting each step, dark blue to green color progression, white background,
professional B2B product documentation style similar to Notion HubSpot infographic,
no cartoon no 3D no isometric, crisp vector-quality digital illustration
--ar 4:1 --v 6 --style raw --stylize 100
```

---

## IMAGE 4
**Lokasi**: Section "Cara Kerja" — di bawah 4 langkah, full-width dalam section
**Ukuran target**: 800 × 340px (landscape)
**Tujuan**: Mockup UI follow-up queue — membuat fitur terasa nyata dan familiar

### Prompt — DALL-E 3:
```
A realistic UI screenshot mockup of a CRM follow-up queue page inside a web application.
The interface shows:

TOP: Three tab buttons: "Hari Ini (5)" selected/active, "Overdue (2)", "Upcoming (8)"
The active tab "Hari Ini" has a blue underline indicator.

FOLLOW-UP LIST (5 items visible):
Each list item is a row card showing:
- Left: colored urgency dot (red for overdue, amber for today, gray for upcoming)
- Customer name (Indonesian names): e.g., "PT Sumber Rejeki", "Bapak Arif Santoso"
- Deal name below customer: e.g., "Proposal Software HR", "Penawaran Paket Enterprise"
- Center: assigned sales rep avatar (small circular photo placeholder with initials)
- Right: scheduled time "10:00", "13:30", "Tomorrow 09:00"
- Far right: action buttons (small icon buttons: phone, note, done checkmark)

One row has a red "OVERDUE" badge — it's visually highlighted with a very light red background.

HEADER: Page title "Follow-Up Queue", search bar, filter dropdown (All Stages, My Leads)

UI style: clean white, blue accent (#2563eb), modern SaaS design similar to Linear or
Todoist task view. Subtle row separators, hover state visible on one row.
Flat-on perspective, no device frame. Aspect ratio 16:9.
```

### Prompt — Midjourney v6:
```
realistic SaaS CRM follow-up queue UI screenshot, tab navigation Hari Ini 5 Overdue 2
Upcoming 8, list of follow-up items with customer names in Indonesian deal names urgency
colored dots scheduled times sales rep avatars action icon buttons, one row highlighted red
overdue badge, blue accent color scheme clean white background, Linear Todoist task view
aesthetic, flat-on perspective no device frame
--ar 16:9 --v 6 --style raw
```

---

## IMAGE 5
**Lokasi**: Section "Dampak Nyata" — kolom kiri
**Ukuran target**: 600 × 700px (portrait)
**Tujuan**: Wajah manusia nyata yang memberi rasa kepercayaan — pemilik bisnis yang in-control

### Prompt — DALL-E 3:
```
A professional lifestyle photograph of an Indonesian business owner or sales manager
(man or woman, age 30–42, wearing smart casual business attire — dark navy blazer or
structured top, clean professional look) sitting at a modern office desk, smiling with
quiet confidence while reviewing a laptop screen.

The laptop screen shows a colorful CRM pipeline dashboard (blurred but visible — colored
kanban columns, deal cards, charts).

Environment details:
- Modern Indonesian office or co-working space
- Clean minimal desk with a few items: pen holder, coffee mug, small plant
- Natural window lighting from the left, creating soft shadows
- Background: slightly blurred open office space with people working (bokeh effect)
- Warm, professional, aspirational atmosphere — "this person has their business under control"

Photography style:
- DSLR camera quality, natural color grading (not oversaturated)
- The subject is NOT posing stiffly — they look genuinely engaged, relaxed confidence
- Warm-neutral color temperature
- NOT stock photo aesthetic (no forced smile, no cheesy pose)

No brand logos visible. No text overlays. No fictional app interfaces (laptop screen blurred).
Aspect ratio 5:7 (portrait).
```

### Prompt — Midjourney v6:
```
professional lifestyle photography Indonesian business owner 35yo man smart casual navy blazer,
sitting at modern office desk smiling with quiet confidence, laptop showing colorful CRM
dashboard blurred, natural window lighting from left soft bokeh background open office,
warm professional atmosphere aspirational mood, DSLR quality natural color grading not
oversaturated, not stock photo not stiff pose, relaxed genuine confidence
--ar 5:7 --v 6 --style raw
```

---

## IMAGE 6a, 6b, 6c — Testimonial Headshots
**Lokasi**: Section "Testimonial" — avatar masing-masing
**Ukuran target**: 120 × 120px (square, ditampilkan sebagai circle)
**Tujuan**: Headshot portrait untuk membuat testimonial terasa nyata

### IMAGE 6a — Hendra K. (Sales Manager, 35-42 thn):
```
Professional headshot portrait of an Indonesian man, age 35–42, with a confident and
experienced look. He's wearing a white or light gray dress shirt, slightly open collar —
professional but not overly formal. Short neat hair. Direct eye contact with camera,
a slight natural smile showing competence. Background: blurred warm neutral office tones.
Soft, even studio-quality lighting. Square format 1:1. Realistic photography, high quality,
not illustrated, not AI-looking-fake.
```

### IMAGE 6b — Kartika R. (Founder, 30-38 thn):
```
Professional headshot portrait of an Indonesian woman, age 30–38, with a sharp and
entrepreneurial look. She's wearing a structured dark blazer or tailored top — founder
aesthetic, modern professional. Hair neatly styled (down or pulled back). Confident,
direct gaze. Genuine smile, not forced. Background: blurred neutral light gray or white.
Natural studio lighting, soft shadows. Square format 1:1. Realistic photography, high quality.
```

### IMAGE 6c — Fariz M. (Account Executive, 25-32 thn):
```
Professional headshot portrait of a young Indonesian man, age 25–32, with a friendly
and energetic look. He's wearing a smart casual outfit — clean polo or button-up shirt,
no tie. Relaxed but professional. Bright genuine smile. Background: slightly blurred,
neutral modern setting. Natural warm lighting. Square format 1:1. Realistic photography,
high quality, not illustrated.
```

---

## IMAGE 7
**Lokasi**: Final CTA Band — kolom kanan (di atas dark gradient background)
**Ukuran target**: 500 × 380px (landscape)
**Tujuan**: Visual "winning" dan pertumbuhan — melengkapi pesan CTA yang kuat

### Prompt — DALL-E 3:
```
A modern, minimal digital illustration designed to sit on a dark navy blue gradient background.
The illustration shows a visual metaphor for sales success and growth:

- Center element: an upward trending graph/chart made of deal card shapes, each card
  progressively larger and moving from left (small, blue) to right (large, green "Won")
- Small trophy icon at the top right of the chart
- Floating around: small circular avatar bubbles (representing satisfied customers/contacts)
- Subtle floating numbers suggesting growth: "+24 deals", "Win rate ↑"
- Small pipeline connector lines linking the elements together

Color palette: WHITE, light blue (#93c5fd), teal (#5eead4), and soft green (#86efac)
— designed to stand out clearly against a dark navy/indigo background
All shapes have slight luminous glow, as if lit from within

Style: clean minimal vector-quality digital illustration, geometric but with organic
rounded shapes, similar to abstract SaaS marketing illustrations by companies like
Stripe, Linear, or Vercel (their landing page hero illustrations).
NOT cartoon. NOT 3D. NOT complex/busy. Simple, elegant, premium feeling.

No text inside the illustration (text will be overlaid separately).
Aspect ratio 5:4.
```

### Prompt — Midjourney v6:
```
minimal abstract digital illustration for dark background, upward trending sales pipeline
visualization, deal cards flowing left to right from blue to green Won stage, trophy icon
floating avatars circular customer bubbles, white light blue teal soft green color palette
designed for dark navy backdrop, luminous glow effect, clean geometric rounded shapes,
Stripe Linear Vercel landing page illustration aesthetic, no text, no cartoon, not busy
--ar 5:4 --v 6 --style raw --stylize 300
```

---

## Status Tracker

| Image | Status | File path |
|-------|--------|-----------|
| 1 — Kanban Dashboard Mockup | ⏳ Belum | `public/img/landing/crm/img-1.png` |
| 2 — Sales Rep Overwhelmed | ⏳ Belum | `public/img/landing/crm/img-2.png` |
| 3 — Flow Diagram | ⏳ Belum | `public/img/landing/crm/img-3.png` |
| 4 — Follow-Up Queue UI | ⏳ Belum | `public/img/landing/crm/img-4.png` |
| 5 — Owner/Manager Lifestyle | ⏳ Belum | `public/img/landing/crm/img-5.png` |
| 6a — Hendra headshot | ⏳ Belum | `public/img/landing/crm/img-6a.png` |
| 6b — Kartika headshot | ⏳ Belum | `public/img/landing/crm/img-6b.png` |
| 6c — Fariz headshot | ⏳ Belum | `public/img/landing/crm/img-6c.png` |
| 7 — CTA Growth Illustration | ⏳ Belum | `public/img/landing/crm/img-7.png` |

## Tips Penggunaan

### Untuk DALL-E 3:
- Gunakan prompt bahasa Inggris di atas langsung via ChatGPT atau API
- Untuk foto realistis: pilih style `"natural"` bukan `"vivid"`
- Untuk ilustrasi: pilih `"vivid"` tapi tetap cantumkan negative keywords di dalam prompt

### Untuk Midjourney v6:
- Selalu tambahkan `--style raw` untuk mengurangi "AI look"
- Naikkan `--stylize` (150-300) untuk ilustrasi editorial agar lebih artistik
- Untuk foto realistis: turunkan `--stylize` ke 50-100

### Folder tujuan:
```
public/img/landing/crm/
```
Buat folder ini sebelum menyimpan file.
