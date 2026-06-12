# 17 — Negative Testing & Otorisasi (Lintas Modul)

Kasus uji **negatif** (input salah/ekstrem) dan **otorisasi/akses** yang melengkapi modul
01–13 (yang sebagian besar happy-path). Sebagian sudah diverifikasi via browser pada
2026-05-30 (akun kasir `cashier@vape.com`).

## A. Otorisasi / Akses (kasir → URL admin)

Login sebagai **kasir**, lalu akses URL admin langsung via address bar.

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-17.1 | Kasir → Dashboard admin | Buka `/admin/dashboard` | Ditolak (403 Forbidden) | **403 Forbidden** | ✅ |
| TC-17.2 | Kasir → Manajemen produk | Buka `/admin/products` | Ditolak (403) | **403 Forbidden** | ✅ |
| TC-17.3 | Kasir → Pengaturan toko | Buka `/settings/store` | Ditolak (403) | **403 Forbidden** | ✅ |
| TC-17.4 | Tamu (belum login) → POS | Buka `/pos/dashboard` tanpa sesi | Redirect ke `/login` | ⏳ perlu uji |
| TC-17.5 | Tamu → admin | Buka `/admin/dashboard` tanpa sesi | Redirect ke `/login` | ⏳ perlu uji |
| TC-17.6 | Admin → menu "Toko" tampil | Login admin, lihat settings nav | Menu "Toko" muncul (kasir tidak) | Sesuai ("Toko — Identitas & struk") | ✅ |

## B. Login (negatif)

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-17.7 | Kredensial salah | Email benar + password salah → Masuk | Pesan "Email atau password salah." (ID) | Sesuai | ✅ |
| TC-17.8 | Field kosong | Submit email & password kosong | Tetap di `/login`, tidak login (tervalidasi) | Sesuai | ✅ |
| TC-17.9 | Email tidak terdaftar | Email acak + password apa pun | "Email atau password salah." | ⏳ perlu uji |

## C. POS / Transaksi (negatif)

| ID | Skenario | Langkah | Hasil yang Diharapkan | Status |
|----|----------|---------|------------------------|--------|
| TC-17.10 | Bayar < total (tunai) | Input nominal tunai < Total Tagihan | "Konfirmasi Pembayaran" tetap disabled | ⏳ perlu uji |
| TC-17.11 | Bayar tanpa nominal | Buka modal, langsung Konfirmasi | Tombol disabled (lihat TC-08.9) | ✅ (tercover modul 08) |
| TC-17.12 | Checkout keranjang kosong | "Bayar Saja" tanpa item | Tombol disabled | ✅ (terlihat: "Bayar Saja" disabled saat kosong) |
| TC-17.13 | Qty melebihi stok | Tambah qty > stok tersedia | Dibatasi ke stok / dicegah | ⏳ perlu uji |
| TC-17.14 | Jual produk habis | Klik produk "Habis" | Tidak bisa ditambah ke keranjang | ⏳ perlu uji |

## D. Manajemen Produk / Akun (negatif) — perlu admin

| ID | Skenario | Langkah | Hasil yang Diharapkan | Status |
|----|----------|---------|------------------------|--------|
| TC-17.15 | Tambah produk field wajib kosong | Submit form produk kosong | Validasi menolak, pesan error per field | Sesuai | ✅ |
| TC-17.16 | Email akun duplikat | Buat user dgn email sudah ada | Validasi `unique` menolak | ⏳ perlu admin |
| TC-17.17 | Harga/stok negatif | Isi harga/stok < 0 | Validasi menolak | ⏳ perlu admin |
| TC-17.18 | Retur > qty beli | Set qty retur > qty dibeli | Dibatasi ke qty beli | ✅ (tercover TC-11.12) |


