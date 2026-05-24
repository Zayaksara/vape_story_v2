# 06 — Promo & Diskon

Manajemen promo di `/admin/promotions`: daftar promo, filter status/tipe, tambah/edit/hapus,
dan toggle aktif/nonaktif.

## Cakupan
- Kartu ringkasan (total, aktif, akan datang, berakhir)
- Pencarian kode/nama promo
- Filter status & tipe
- Kolom: Kode, Nama, Tipe, Nilai, Target, Periode, Pemakaian, Status
- Tambah/Edit/Hapus promo
- Toggle status

## Prasyarat
- Login sebagai admin

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-06.1 | Muat daftar promo | Buka `/admin/promotions` | Tabel promo + kartu ringkasan tampil | Sesuai (2 promo: 1 aktif, 1 berakhir) | ✅ |
| TC-06.2 | Kartu ringkasan | Lihat 4 kartu | Total / Aktif / Akan datang / Berakhir | Sesuai | ✅ |
| TC-06.3 | Tipe Persentase | Lihat promo "WELCOME TO CLUB" | Tipe "Persentase", nilai "10%" | Sesuai | ✅ |
| TC-06.4 | Tipe Potongan Tetap | Lihat promo "WELCOME TO YA" | Tipe "Potongan Tetap", nilai "Rp 10.000" | Sesuai | ✅ |
| TC-06.5 | Status berdasar periode | Bandingkan tanggal vs hari ini | Promo lewat tanggal = "Berakhir"; dalam rentang = "Aktif" | Sesuai | ✅ |
| TC-06.6 | Counter pemakaian | Lihat kolom Pemakaian | Format "terpakai / kuota" (mis. "0 / ∞") | Sesuai | ✅ |
| TC-06.7 | Pencarian | Ketik kode/nama | Baris tersaring | Sesuai | ✅ |
| TC-06.8 | Filter status | Pilih "Aktif"/"Berakhir" | Hanya status tsb tampil | Sesuai | ✅ |
| TC-06.9 | Filter tipe | Pilih "Persentase"/"Potongan Tetap" | Hanya tipe tsb tampil | Sesuai | ✅ |
| TC-06.10 | Tambah promo | Klik "Tambah Promo", isi, simpan | Promo baru muncul | Sesuai | ✅ |
| TC-06.11 | Edit promo | Klik edit, ubah, simpan | Perubahan tersimpan | Sesuai | ✅ |
| TC-06.12 | Toggle status | Klik badge/tombol status | Status aktif/nonaktif berubah (endpoint `toggle`) | Sesuai | ✅ |
| TC-06.13 | Hapus promo | Klik hapus + konfirmasi | Promo terhapus | Sesuai | ✅ |

## Catatan
- Tidak ditemukan bug fungsional pada modul ini.
- Voucher pada data seed transaksi (mis. `HEMAT10K`) bersifat label sintetis pada
  tabel `sales` dan tidak menambah counter pemakaian promo nyata — wajar untuk data demo.
