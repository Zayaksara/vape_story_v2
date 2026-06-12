┌──────────────────┐
│   USER           │
│  Admin / Kasir   │
│  (browser / PWA) │
│   ┌──────────┐   │
│   │  Vue.js  │   │  ← Vue render di sini (di device user)
│   └──────────┘   │
└────────┬─────────┘
         │ HTTPS (Inertia)
         ▼
┌─────────────────────────────────┐
│        RENDER (Cloud Server)     │   ← Render membungkus Laravel
│   ┌─────────────────────────┐    │
│   │       Laravel           │    │
│   │  (serve Vue + API/logic)│    │
│   └───────────┬─────────────┘    │
└───────────────│──────────────────┘
                │ pdo_pgsql
                ▼
        ┌───────────────┐
        │  PostgreSQL   │
        └───────────────┘


   REQUEST (turun)              RESPONSE (naik balik)
   ───────────────             ─────────────────────
   USER                            USER
    │ klik/submit                   ▲ halaman tampil
    ▼                               │
   Vue (browser)                   Vue render data
    │                               ▲
    ▼                               │
   Laravel (di Render)             Laravel olah hasil
    │ "ambil/simpan data"           ▲ dapat baris data
    ▼                               │
   PostgreSQL  ◄──── BERHENTI DI SINI ──── data dikirim balik
   (simpan & ambil data)
