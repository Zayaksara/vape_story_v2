# 08 — Dashboard POS & Transaksi (Kasir)

Layar kasir utama di `/pos/dashboard`: grid produk + keranjang + alur pembayaran.
Inilah tempat transaksi penjualan dibuat.

## Cakupan
- Grid produk (harga normal/promo, badge "CUKAI LAMA", indikator stok)
- Pencarian & filter kategori (tab)
- Keranjang: tambah/kurang qty, hapus, kosongkan, voucher
- Modal Pembayaran: metode (Cash/Transfer/QRIS/E-Wallet), nominal cepat, kembalian
- Struk (cetak thermal 58/80mm) & penyelesaian transaksi
- Pengurangan stok otomatis

## Prasyarat
- Login sebagai kasir (`cashier@vape.com` / `cashier123`)

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-08.1 | Muat layar kasir | Buka `/pos/dashboard` | Grid produk + panel Keranjang tampil | Sesuai (61 produk, tab kategori) | ✅ |
| TC-08.2 | Harga promo cukai lama | Lihat produk berbadge "CUKAI LAMA" | Harga normal dicoret + harga promo | Sesuai (mis. HQD Argus 3O Rp260.000→Rp224.000) | ✅ |
| TC-08.3 | Indikator stok | Lihat kartu produk | "246 pcs" / "Sisa 1" / "Habis" | Sesuai | ✅ |
| TC-08.4 | Tambah ke keranjang | Klik kartu produk | Item masuk keranjang, total terupdate | Sesuai | ✅ |
| TC-08.5 | Naikkan qty | Klik "Tambah jumlah" | Qty & subtotal naik (2 × Rp115.000 = Rp230.000) | Sesuai | ✅ |
| TC-08.6 | Buka pembayaran | Klik "Bayar Saja" | Modal "Pembayaran" terbuka, Total Tagihan benar | Sesuai | ✅ |
| TC-08.7 | Pilih metode | Lihat opsi | Cash/Bank Transfer/QRIS/E-Wallet | Sesuai | ✅ |
| TC-08.8 | Nominal cepat & kembalian | Klik "Rp 300 K" (total 230rb) | Kembalian = Rp 70.000 | Sesuai (300.000−230.000) | ✅ |
| TC-08.9 | Konfirmasi disabled | Sebelum input nominal | "Konfirmasi Pembayaran" disabled | Sesuai | ✅ |
| TC-08.10 | Proses pembayaran | Klik "Konfirmasi Pembayaran" | Struk muncul (Invoice, item, kembalian) | Sesuai (INV-1119) | ✅ |
| TC-08.11 | Struk & cetak | Lihat struk | Opsi Thermal 58/80mm, Print Nota, Selesai | Sesuai | ✅ |
| TC-08.12 | Selesaikan transaksi | Klik "Selesai" | Keranjang kosong, kembali ke grid | Sesuai | ✅ |
| TC-08.13 | Stok berkurang | Cek stok produk setelah jual 2 | Stok 292 → 290 | Sesuai | ✅ |
| TC-08.14 | Fokus input cash tanpa error | Buka modal / klik nominal cepat | Input ter-fokus tanpa error JS | Sesuai | ✅ |
