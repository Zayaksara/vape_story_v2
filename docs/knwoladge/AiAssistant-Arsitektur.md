# AI Assistant — Arsitektur & Pipeline

> Dokumen rancangan untuk fitur **AI Assistant** (chatbot toko Story Vape).
> Status: **rancangan / belum diimplementasi**. Tanggal: 2026-05-30.

## 1. Tujuan

Asisten AI berbentuk tombol mengambang (FAB) di **layout admin** yang bisa:

1. **Knowledge toko** — tanya-jawab stok, produk, harga, penjualan, transaksi.
2. **Akuntansi** — konsep umum (HPP, margin) + angka nyata toko (omzet, laba, modal).
3. **Forecasting** — perkiraan penjualan/restok berbasis metode statistik (bukan tebakan AI).

**Prinsip utama:** AI **tidak menghitung** dan **tidak dilatih (no training)**.
Semua angka dihitung oleh PHP/MySQL; Gemini hanya memilih alat & menerjemahkan
hasil jadi bahasa natural.

## 2. Keputusan teknis (sudah disepakati)

| Aspek | Keputusan |
|---|---|
| Penyedia AI | **Google Gemini 2.0 Flash** (free tier) |
| Letak API key | **Backend** (`.env` → `config/services.php`), tidak pernah ke frontend |
| Arsitektur data | **Function Calling** (AI memilih "tools" untuk query data) |
| Lokasi UI | **Admin saja** — FAB (komponen lama `AiAssistantFab.vue` diaktifkan) |
| Riwayat chat | **Per sesi browser** (state frontend, tanpa tabel DB) |
| Forecasting | **Statistik klasik di PHP** (adaptif), AI hanya menjelaskan |
| Training | **Tidak ada** — system prompt + tools saja |

## 3. Arsitektur tingkat tinggi

```
┌──────────────────────────────────────────────────────────────────┐
│ FRONTEND (Vue / Inertia)                                           │
│   AiAssistantFab.vue  ── input chat, bubble, loading, history(state)│
└───────────────┬──────────────────────────────────────────────────┘
                │  POST /admin/ai/chat  { message, history[] }
                ▼
┌──────────────────────────────────────────────────────────────────┐
│ BACKEND (Laravel, middleware: auth + admin)                        │
│                                                                    │
│   AiChatController ──► GeminiClient ──► AiToolRegistry             │
│        (orkestrasi)     (HTTP +          (skema tools)             │
│                         loop tool-call)        │                   │
│                                                ▼                   │
│                                         AiToolService             │
│                                         (query read-only DB)       │
│                                                │                   │
│                                  ┌─────────────┼─────────────┐     │
│                                  ▼             ▼             ▼     │
│                            Knowledge      Akuntansi    Forecasting │
│                            (Sale,Product) (HPP,laba)  (ForecastSvc)│
└───────────────┬──────────────────────────────────────────────────┘
                │  HTTPS
                ▼
        ┌───────────────────┐
        │ Google Gemini API │  (menalar + memilih tool + menyusun jawaban)
        └───────────────────┘
```

## 4. Pipeline satu pertanyaan (alur detail)

Contoh user bertanya: *"Omzet minggu ini berapa, dan perkiraan minggu depan?"*

```
1. Vue kirim  POST /admin/ai/chat
   body: { message, history[] }

2. AiChatController susun payload:
   - system prompt (peran asisten + aturan)
   - history percakapan
   - message terbaru
   - daftar tool (dari AiToolRegistry)
   → GeminiClient->chat(payload)

3. Gemini balas: "panggil tool getSalesSummary(period='this_week')"
   (functionCall, BUKAN jawaban final)

4. GeminiClient eksekusi → AiToolService->getSalesSummary('this_week')
   → query MySQL → { omzet: 4.2jt, transaksi: 28, ... }

5. (Gemini boleh minta tool lagi) → getForecast(metric='revenue', horizon='next_week')
   → ForecastService hitung (lihat §6) → { estimasi: 4.6jt, metode:'MA-7', interval:[...] }

6. Hasil tool dikembalikan ke Gemini.

7. Gemini susun jawaban final bahasa Indonesia:
   "Omzet minggu ini Rp4,2 jt (28 transaksi). Perkiraan minggu depan
    ±Rp4,6 jt (metode moving average; estimasi, bukan jaminan)."

8. Controller balas JSON → Vue render bubble.
```

> **Loop function-calling:** langkah 3–6 bisa berulang (maks. mis. 5 putaran)
> sampai Gemini berhenti meminta tool dan memberi jawaban final.

## 5. Komponen (unit kecil, tanggung jawab tunggal)

| Komponen | Lokasi | Tanggung jawab | Tahu soal |
|---|---|---|---|
| `AiAssistantFab.vue` | `resources/js/components/admin/` | UI chat (input, bubble, loading, history state) | UI saja |
| `AiChatController` | `app/Http/Controllers/Admin/` | Terima request, orkestrasi, balas JSON | HTTP |
| `GeminiClient` | `app/Services/Ai/` | HTTP ke Gemini + kelola loop tool-call | Gemini saja |
| `AiToolRegistry` | `app/Services/Ai/` | Definisi skema tool (JSON) + pemetaan ke method | Daftar tool |
| `AiToolService` | `app/Services/Ai/` | Jalankan query read-only data toko | Data toko |
| `ForecastService` | `app/Services/Ai/` | Rumus statistik forecasting | Matematika |
| config `gemini` | `config/services.php` | Simpan key & model | — |
| route | `routes/web.php` (grup admin) | `POST admin/ai/chat` | — |

### Daftar tools tahap 1
- `getSalesSummary(period)` — omzet, jumlah transaksi, rata-rata.
- `getTopProducts(period, limit)` — produk terlaris.
- `getLowStock(threshold)` — stok menipis/habis.
- `getProductInfo(name)` — harga & stok produk tertentu.
- `getProfit(period)` — laba kotor (omzet − HPP via batch cost).
- `getAccountingSnapshot(period)` — ringkasan akuntansi (modal stok, omzet, laba).
- `getForecast(metric, horizon)` — perkiraan (delegasi ke `ForecastService`).

## 6. Strategi forecasting (ADAPTIF terhadap volume data)

> ⚠️ **PENTING — data akan di-reset ke 0 saat produksi (cold-start).**
> Sistem harus mulai dari nol dan "menyala" otomatis seiring data bertambah.
> Tidak ada penyetelan manual: `ForecastService` mengecek umur/jumlah data lalu
> memilih metode yang pantas. Knowledge & akuntansi tetap jalan walau data 0
> (jawab jujur: "belum ada penjualan / stok kosong").

### Perilaku cold-start (setelah reset data)

| Umur data | Perilaku forecast |
|---|---|
| **0–3 hari** | Tidak meramal. Jawab jujur: data belum cukup; knowledge & akuntansi tetap aktif |
| **3–14 hari** | **Moving Average** + disclaimer kuat ("estimasi sangat kasar") |
| **≥ 14 hari** | **Holt-Winters** aktif (pola mingguan mulai terbaca) |
| **≥ 8 minggu** | Akurasi stabil, interval menyempit |

Forecasting otomatis aktif sendiri saat transaksi harian masuk — tanpa ubah kode.

### Metode dipilih otomatis berdasarkan volume histori

| Histori tersedia | Metode | Catatan |
|---|---|---|
| < 14 hari (**sekarang**) | **Moving Average** + rata-rata sederhana | Estimasi kasar, disclaimer kuat |
| ≥ 14 hari (2+ siklus mingguan) | **Holt-Winters / Exponential Smoothing** | Tangkap tren + musiman mingguan |
| Restok per produk (permintaan jarang) | **Croston / SBA** + safety stock | Untuk produk yang tak laku tiap hari |
| Pembanding (selalu) | **Seasonal naive** | Patokan akurasi |

**Aturan jujur:** setiap hasil forecast WAJIB menyertakan (a) metode yang dipakai,
(b) label "estimasi", (c) rentang/interval bila ada. AI dilarang mengarang angka —
angka hanya dari `ForecastService`.

### Dasar ilmiah (referensi)
- Hyndman & Athanasopoulos — *Forecasting: Principles and Practice* (otexts.com/fpp3).
- Winters (1960), *Management Science* — exponential smoothing.
- Gardner (2006), *Int. J. of Forecasting* — exponential smoothing state of the art.
- Croston (1972), *Operational Research Quarterly* — permintaan terputus.
- Syntetos & Boylan (2005), *Int. J. of Forecasting* — koreksi SBA.
- Makridakis dkk (2018), *PLOS ONE* + M4 Competition (2020) — metode statistik
  sederhana sering ≥ ML pada data bisnis (dukungan untuk "tanpa ML").

## 7. Keamanan & error handling

- Key Gemini **hanya backend**; route di grup `admin` (middleware `admin`).
- Semua tools **read-only** — AI tak bisa mengubah/menghapus data.
- Timeout HTTP ke Gemini; pesan ramah saat gagal/limit/kuota habis.
- Batasi panjang `history` & jumlah putaran tool-call (anti-loop).
- Validasi input (panjang pesan maksimum).

## 8. Beban & biaya (ringkas)

| Item | Beban |
|---|---|
| Query data (knowledge/akuntansi) | Milidetik (MySQL) |
| Forecasting (PHP) | Milidetik (aritmatika) |
| Panggilan Gemini | 0 beban server; latensi ~1–3 dtk |
| Kuota | Free tier Gemini 2.0 Flash cukup untuk 1 toko |
| Training | **Tidak ada** |

## 9. Tahapan

- **Tahap 1 (target):** FAB chat aktif + 7 tools + forecasting adaptif (MA dulu).
- **Tahap 2 (nanti):** Holt-Winters & Croston aktif saat data ≥ 2 minggu,
  promo/rekomendasi lebih canggih, opsi simpan riwayat ke DB.

## 10. Testing (rencana)

- Unit test tiap method `AiToolService` & `ForecastService` (angka cocok data seed).
- Feature test `AiChatController` dengan `Http::fake()` Gemini (termasuk skenario functionCall).
- Uji akses non-admin ditolak.
