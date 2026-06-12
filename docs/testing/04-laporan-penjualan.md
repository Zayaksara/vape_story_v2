# 04 — Laporan Penjualan

Halaman laporan di `/admin/reports/sales`: ringkasan revenue/profit, tab analisis
(Produk/Merek/Metode Bayar/Stok/Return), filter periode, export, dan shopping list.

## Cakupan

- Kartu ringkasan (Total Revenue, Total Profit, Item Terjual, Total Transaksi)
- Filter periode (Harian/Mingguan/Bulanan/Quarter/Tahunan/Kustom)
- Tab: Produk, Merek, Metode Bayar, Stok, Return
- Pencarian dalam tab
- Sorting kolom
- Tombol Export & "Belanja?" (shopping list)

## Prasyarat

- Login sebagai admin
- Data demo 90 hari ter-seed

## Kasus Uji


| ID       | Skenario            | Langkah                          | Hasil yang Diharapkan                                                 | Hasil                                           | Status |
| -------- | ------------------- | -------------------------------- | --------------------------------------------------------------------- | ----------------------------------------------- | ------ |
| TC-04.1  | Muat laporan        | Buka `/admin/reports/sales`      | Kartu ringkasan + tabel produk tampil                                 | Sesuai                                          | ✅      |
| TC-04.2  | Ringkasan terisi    | Periode "Bulanan" (Mei 2026)     | Revenue Rp 253.831.000, Profit Rp 67.860.500, Item 998, Transaksi 313 | Sesuai                                          | ✅      |
| TC-04.3  | Ganti periode       | Pilih "Harian"/"Tahunan"/dll     | Ringkasan & tabel ter-refresh                                         | Sesuai                                          | ✅      |
| TC-04.4  | Periode Kustom      | Pilih "Kustom" + rentang         | Filter rentang diterapkan                                             | Sesuai                                          | ✅      |
| TC-04.5  | Tab Produk          | Default                          | Tabel Kode/Nama/Kategori/Merek/Qty/Revenue/Profit/Stok                | Sesuai                                          | ✅      |
| TC-04.6  | Tab Merek           | Klik "Merek"                     | Agregasi per merek                                                    | Sesuai                                          | ✅      |
| TC-04.7  | Tab Metode Bayar    | Klik "Metode Bayar"              | Distribusi per metode                                                 | Sesuai                                          | ✅      |
| TC-04.8  | Tab Stok            | Klik "Stok"                      | Data stok produk                                                      | Sesuai                                          | ✅      |
| TC-04.9  | Tab Return          | Klik "Return"                    | Data retur                                                            | Sesuai                                          | ✅      |
| TC-04.10 | Pencarian dalam tab | Ketik di "Cari di produk…"       | Baris tersaring                                                       | Sesuai                                          | ✅      |
| TC-04.11 | Sorting kolom       | Klik header (Revenue/Profit/Qty) | Urutan berubah                                                        | Sesuai                                          | ✅      |
| TC-04.12 | Export              | Klik "Export"                    | Unduh file laporan                                                    | Sesuai (endpoint `reports/sales/export`)        | ✅      |
| TC-04.13 | Shopping List       | Klik "Belanja?"                  | Tampil rekomendasi belanja/restock                                    | Sesuai (endpoint `reports/sales/shopping-list`) | ✅      |
| TC-04.14 | Tombol kembali      | Klik "Kembali ke dashboard"      | Navigasi ke dashboard                                                 | Sesuai                                          | ✅      |
| TC-04.15 | Konsistensi profit  | Periksa kolom Profit             | Profit = Revenue − HPP (snapshot batch F IFO)                         | Konsisten dgn data seed                         | ✅      |
