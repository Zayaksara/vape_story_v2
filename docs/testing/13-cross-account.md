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

## Kebijakan Pembulatan Uang (retur)
- **Disimpan & dihitung sebagai rupiah utuh** (`round()`), tidak ada pecahan sen.
- **Refund tunai dibulatkan ke kelipatan Rp100** terdekat (`ReturnService::roundRefund`),
  karena pecahan < Rp100 tidak praktis di laci kas.
- **Refund non-tunai** (transfer/QRIS/e-wallet) tetap **eksak** ke rupiah utuh.

## Retur ulang sampai habis — ✅
- Transaksi `partial_return` tetap dapat diretur lagi; form membatasi qty ke
  `remaining_quantity` (qty beli − sudah diretur) dan menampilkan "sudah return: X".
- Setelah semua unit diretur, transaksi terkunci (`is_fully_returned`) dan status `returned`.
