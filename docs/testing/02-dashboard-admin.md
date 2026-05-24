# 02 — Dashboard Admin

Halaman ringkasan bisnis di `/admin/dashboard`. Menampilkan metrik, grafik tren,
perbandingan periode, dan top produk/kategori/merek.

## Cakupan
- Kartu metrik (Pendapatan, Transaksi, Keuntungan, Produk Terjual)
- Filter periode (Hari Ini, Minggu Ini, Bulan Ini, Kuartal, Tahun, Kustom)
- Grafik Tren Pendapatan (per jam) — Masa Lalu vs Sekarang vs Prediksi
- Tabel Perbandingan Periode (vs periode sebelumnya)
- Donut Pembayaran Populer
- Top 5 Produk / Kategori / Merek

## Prasyarat
- Login sebagai admin
- Data demo 90 hari ter-seed (agar grafik & perbandingan terisi)

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-02.1 | Muat dashboard | Buka `/admin/dashboard` | Semua kartu & grafik tampil tanpa error | Sesuai, tanpa error console | ✅ |
| TC-02.2 | Kartu metrik terisi | Periode default "Hari Ini" | 4 kartu menampilkan angka (mis. Pendapatan Rp 10.620.000, Transaksi 18) | Sesuai | ✅ |
| TC-02.3 | Indikator perubahan | Lihat badge % di tiap kartu | Persentase + arah (mis. "-57.1% vs Kemarin") | Sesuai | ✅ |
| TC-02.4 | Ganti periode | Klik "Bulan Ini" | Data & grafik ter-refresh sesuai rentang bulan | Sesuai | ✅ |
| TC-02.5 | Periode Kustom | Klik "Kustom", pilih rentang | Filter rentang tanggal diterapkan | Sesuai | ✅ |
| TC-02.6 | Grafik tren per jam | Lihat "Tren Pendapatan" | Area chart 3 seri (Masa Lalu/Sekarang/Prediksi), sumbu jam 00–23 | Sesuai | ✅ |
| TC-02.7 | Tabel perbandingan | Lihat "Perbandingan Periode" | Baris Pendapatan/Transaksi/Keuntungan/Produk + selisih % | Sesuai (mis. Pendapatan Rp10.620.000 vs Rp24.748.000) | ✅ |
| TC-02.8 | Donut pembayaran | Lihat "Pembayaran Populer" | Distribusi Tunai/Transfer/QRIS/E-Wallet, total transaksi | Sesuai (39%/17%/28%/17%) | ✅ |
| TC-02.9 | Top 5 Produk | Lihat panel + tabel | 5 produk terurut pendapatan, ada Qty & Rp | Sesuai | ✅ |
| TC-02.10 | Top 5 Kategori | Lihat donut + tabel | 5 kategori, % distribusi, total Rp | Sesuai | ✅ |
| TC-02.11 | Top 5 Merek | Lihat bar chart | 5 merek terurut pendapatan | Sesuai | ✅ |
| TC-02.12 | Konsistensi angka | Bandingkan kartu vs ringkasan grafik | Total di kartu = total di ringkasan chart | Konsisten (Rp 10.6 jt / 18 transaksi) | ✅ |

## Catatan
- Dengan data 90 hari, seluruh grafik & tabel terisi penuh sehingga tampilan
  "data banyak" terbukti (omzet kumulatif ±Rp943 juta).
- Tidak ditemukan bug fungsional pada modul ini.
- Header global "Welcome, ..." → lihat **BUG-04** (diperbaiki ke "Selamat datang, ...").
