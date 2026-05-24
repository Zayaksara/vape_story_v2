# 10 — Riwayat Transaksi (POS)

Riwayat transaksi harian kasir di `/pos/transactions/today` ("Laporan Penjualan Harian").

## Cakupan
- Navigasi tanggal (hari sebelumnya/berikutnya, pilih tanggal)
- Kartu ringkasan (Total Transaksi, Total Penjualan, Tunai Masuk, Item Terjual)
- Daftar transaksi (jam, no. struk, pembayaran, kasir, jumlah, status)
- Pencarian & filter metode bayar
- Cetak / Export

## Prasyarat
- Login sebagai kasir

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-10.1 | Muat riwayat | Buka `/pos/transactions/today` | Ringkasan + daftar transaksi hari ini | Sesuai | ✅ |
| TC-10.2 | Ringkasan akurat | Setelah transaksi baru | Total Transaksi 19, Penjualan Rp10.850.000, Item 52 | Sesuai (naik dari 18/Rp10.620.000/50) | ✅ |
| TC-10.3 | Transaksi baru tampil | Cari SALE-001119 | Baris "12.10 · SALE-001119 · Tunai · Cashier · Rp230.000 · success" | Sesuai | ✅ |
| TC-10.4 | Navigasi tanggal | Klik "Hari sebelumnya" | Data tanggal sebelumnya dimuat | Sesuai | ✅ |
| TC-10.5 | Hari berikutnya (hari ini) | Tombol "Hari berikutnya" | Disabled saat sudah di hari ini | Sesuai | ✅ |
| TC-10.6 | Filter metode | Pilih "Tunai"/"QRIS"/dll | Baris tersaring per metode | Sesuai | ✅ |
| TC-10.7 | Pencarian struk/kasir | Ketik nomor/nama | Baris tersaring | Sesuai | ✅ |
| TC-10.8 | Detail transaksi | Klik baris | Detail item transaksi tampil | Sesuai | ✅ |
| TC-10.9 | Cetak / Export | Klik tombol | Cetak/unduh laporan | Sesuai | ✅ |
| TC-10.10 | Transaksi diretur tetap tampil | Setelah retur sebagian SALE-001119 | Transaksi tetap muncul dgn nilai **bersih** + badge "Diretur sebagian" | Sebelum perbaikan: **hilang** → **BUG-08** | ❌→✅ |
| TC-10.11 | Total = Dashboard | Bandingkan Total Penjualan vs Dashboard | Sama persis (nilai bersih) | Sesuai setelah BUG-08 | ✅ |

## Catatan terkait (BUG-08 — sudah diperbaiki)
- Riwayat ini dulu memfilter `status='completed'` sehingga transaksi yang diretur hilang.
  Setelah perbaikan **BUG-08 (Opsi 1)**, transaksi diretur tetap tampil dengan **nilai
  bersih** + badge status, dan total laporan = Dashboard. Detail di
  [13-cross-account.md](13-cross-account.md).
