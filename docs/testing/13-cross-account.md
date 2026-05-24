# 13 — Cross-Account: POS → Admin

Verifikasi bahwa transaksi & retur yang dibuat di akun **kasir** terefleksi dengan benar
di akun **admin** (dashboard, riwayat, laporan), dan dampak stok konsisten.

## Skenario Uji End-to-End
1. Login kasir → buat transaksi **SALE-001119** (Geekvape Blue Razz AO × 2 = Rp230.000, cash).
2. Login kasir → retur **1 unit** dari SALE-001119 (Rp115.000, refund cash).
3. Login admin → cek dampak di dashboard, History Pembayaran, laporan.

## Hasil Verifikasi

### Dashboard Admin (Hari Ini) — ✅ KONSISTEN

| Metrik | Sebelum | Sesudah (sale +230.000, retur −115.000) | Status |
|--------|---------|------------------------------------------|--------|
| Total Pendapatan | Rp 10.620.000 | **Rp 10.735.000** (+230.000 −115.000) | ✅ |
| Total Transaksi | 18 | **19** (+1) | ✅ |
| Produk Terjual | 50 | **51** (+2 −1 retur) | ✅ |
| Total Keuntungan | Rp 2.798.550 | **Rp 2.823.850** | ✅ |

→ Dashboard menetralkan retur dengan benar (revenue & qty bersih).

### Stok — ✅ KONSISTEN
- Setelah jual 2: 292 → 290.
- Setelah retur 1: 290 → 291.
- Konsisten antara katalog POS, manajemen produk admin, dan DB.

### Riwayat Transaksi POS — ✅ (sebelum retur)
- SALE-001119 tampil dengan benar (12.10, Tunai, Cashier, Rp230.000).

### History Pembayaran Admin — ✅ setelah perbaikan BUG-08
- Setelah retur **sebagian**: SALE-001119 tampil "Rp 115.000 · **Diretur sebagian**",
  Total Penjualan = Rp 10.735.000 (= Dashboard).
- Setelah retur **penuh** (unit terakhir): SALE-001119 tampil "Rp 0 · **Diretur penuh**",
  Total Penjualan kembali Rp 10.620.000, Item Terjual 50 (= Dashboard).

## BUG-08 — Transaksi yang diretur hilang dari laporan harian (SEDANG) — ✅ DIPERBAIKI

- **Tingkat:** 🟠 Sedang (konsistensi data / pelaporan)
- **Gejala:** Begitu sebuah transaksi diretur (status `completed` → `partial_return`/`returned`),
  transaksi itu **tidak lagi muncul** di Admin "History Pembayaran" maupun POS
  "Riwayat Transaksi", padahal **Dashboard tetap menghitungnya** → angka tidak konsisten.
- **Akar masalah:** Kedua `TodayTransactionController` memfilter `where('status','completed')`,
  sementara `ReturnService` mengubah status sale menjadi `partial_return`/`returned`.
  Helper `totalsFor()` & `bucketSales()` (admin) juga memfilter `completed` saja.
- **Perbaikan (Opsi 1 — nilai bersih, dipilih):**
  - `Admin\TodayTransactionController` & `POS\TodayTransactionController`:
    - Query memakai `whereIn('status', ['completed','partial_return','returned'])`.
    - `mapSalesToTransactions` menghitung **nilai bersih** per transaksi:
      `total_amount = round(gross − refund)`, `net_quantity = qty_jual − qty_retur`,
      menambah flag `is_returned`, `gross_amount`, `returned_amount`, dan status asli sale.
    - Sumber refund = `return_items.subtotal` (retur non-rejected) — selaras Dashboard.
    - `totalsFor()` & `bucketSales()` admin disesuaikan (status inklusif + kurangi refund).
  - Frontend `admin/ReportTodayTransaction.vue` & `POS/ReportTodayTransaction.vue`:
    badge status berlabel **"Berhasil" / "Diretur sebagian" / "Diretur penuh"**.
- **Verifikasi:** Total laporan harian kini **persis sama** dengan Dashboard (lihat di atas).

## BUG-09 — Status tidak jadi `returned` saat habis diretur bertahap (MINOR) — ✅ DIPERBAIKI

- **Tingkat:** 🟡 Minor
- **Gejala:** Transaksi yang diretur **bertahap** (mis. 1 unit, lalu 1 unit lagi) sampai
  semua unit kembali, statusnya tetap `partial_return` — seharusnya `returned`.
- **Akar masalah:** `ReturnService` menentukan status dari `$totalReturnedQty` (qty retur
  **kali ini** saja), bukan akumulasi seluruh retur.
- **Perbaikan:** status dihitung dari **akumulasi** `returned_quantity` seluruh alokasi sale:
  `status = (totalReturnedAll >= allOriginalQty) ? 'returned' : 'partial_return'`.
- **Verifikasi:** Setelah retur unit terakhir, status 1119 menjadi `returned` dan tampil
  "Diretur penuh".

## Kebijakan Pembulatan Uang (retur)
- **Disimpan & dihitung sebagai rupiah utuh** (`round()`), tidak ada pecahan sen.
- **Refund tunai dibulatkan ke kelipatan Rp100** terdekat (`ReturnService::roundRefund`),
  karena pecahan < Rp100 tidak praktis di laci kas.
- **Refund non-tunai** (transfer/QRIS/e-wallet) tetap **eksak** ke rupiah utuh.

## Retur ulang sampai habis — ✅
- Transaksi `partial_return` tetap dapat diretur lagi; form membatasi qty ke
  `remaining_quantity` (qty beli − sudah diretur) dan menampilkan "sudah return: X".
- Setelah semua unit diretur, transaksi terkunci (`is_fully_returned`) dan status `returned`.
