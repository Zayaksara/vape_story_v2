# 11 — Pengembalian Barang (Return)

Proses retur di `/pos/returns`: pilih transaksi → tentukan qty item yang dikembalikan →
pilih alasan & metode refund → proses. Stok otomatis dikembalikan (FIFO).

## Cakupan

- Daftar transaksi hari ini (pilih sumber retur)
- Form Return: qty per item, alasan, **metode pengembalian uang** (refund_method)
- Total Return & proses
- Riwayat Return
- Pengembalian stok ke batch (FIFO) & update `sale_item_batches.returned_quantity`

## Prasyarat

- Login sebagai kasir
- Ada transaksi yang bisa diretur (mis. SALE-001119)

## Kasus Uji


| ID       | Skenario                        | Langkah                               | Hasil yang Diharapkan                                  | Hasil                                                 | Status |
| -------- | ------------------------------- | ------------------------------------- | ------------------------------------------------------ | ----------------------------------------------------- | ------ |
| TC-11.1  | Muat halaman retur              | Buka `/pos/returns`                   | Daftar transaksi + form + riwayat                      | Sesuai (19 transaksi)                                 | ✅      |
| TC-11.2  | Pilih transaksi                 | Klik SALE-001119                      | Form Return terisi item transaksi                      | Sesuai (Geekvape Blue Razz AO, beli 2)                | ✅      |
| TC-11.3  | Stepper qty retur               | Klik "+"                              | Qty retur 0→1, subtotal Rp115.000                      | Sesuai                                                | ✅      |
| TC-11.4  | Proses disabled                 | Sebelum pilih alasan                  | "Proses Return" disabled                               | Sesuai                                                | ✅      |
| TC-11.5  | Pilih alasan                    | Pilih "Barang rusak"                  | Alasan terpasang                                       | Sesuai                                                | ✅      |
| TC-11.6  | Metode refund                   | Lihat dropdown                        | Tunai/Transfer/QRIS/E-Wallet (refund_method)           | Sesuai                                                | ✅      |
| TC-11.7  | Proses retur                    | Klik "Proses Return"                  | Retur tersimpan, riwayat bertambah                     | Sesuai (RET-202605-0002, processed)                   | ✅      |
| TC-11.8  | Riwayat return                  | Lihat tabel "Riwayat Return"          | Baris retur baru muncul                                | Sesuai (1 catatan)                                    | ✅      |
| TC-11.9  | Stok dikembalikan               | Cek stok produk                       | Stok 290 → 291 (1 unit kembali)                        | Sesuai                                                | ✅      |
| TC-11.10 | Tracking alokasi FIFO           | Cek `sale_item_batches`               | `returned_quantity` = 1 dari qty 2                     | Sesuai                                                | ✅      |
| TC-11.11 | refund_method tersimpan         | Cek record retur                      | `refund_method = cash`                                 | Sesuai                                                | ✅      |
| TC-11.12 | Tidak bisa retur > beli         | Coba qty retur > qty beli             | Dicegah (maks = qty beli)                              | Sesuai                                                | ✅      |
| TC-11.13 | Retur ulang (bertahap)          | Retur lagi transaksi `partial_return` | Form batasi ke `remaining_quantity`, "sudah return: X" | Sesuai                                                | ✅      |
| TC-11.14 | Habis diretur → status returned | Retur unit terakhir                   | Status sale jadi `returned` ("Diretur penuh")          | Sesuai | ✅      |
| TC-11.15 | Stok pulih penuh                | Setelah retur semua unit              | Stok kembali ke nilai sebelum jual (292)               | Sesuai                                                | ✅      |
| TC-11.16 | Pembulatan refund tunai         | Refund cash dgn pecahan               | Dibulatkan ke Rp100 terdekat                           | Sesuai (`roundRefund`)                                | ✅      |

