# 12 — Pengaturan (POS / Kasir)

Pengaturan akun untuk kasir, diakses via menu user (avatar) → "Pengaturan", mengarah ke
`/settings/profile`.

## Cakupan
- Akses pengaturan dari menu user POS
- Sub-menu: Profil, Keamanan (Toko TIDAK tampil untuk kasir — khusus admin)
- Form Informasi Profil (Nama, Email, Simpan)
- Keluar (logout)

## Prasyarat
- Login sebagai kasir

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-12.1 | Buka menu user | Klik avatar "C Cashier" | Muncul menu "Pengaturan" & "Keluar" | Sesuai | ✅ |
| TC-12.2 | Akses pengaturan | Klik "Pengaturan" / buka `/settings/profile` | Halaman pengaturan tampil | Sesuai | ✅ |
| TC-12.3 | Sub-menu sesuai role | Lihat navigasi pengaturan | "Profil" & "Keamanan" saja (tanpa "Toko") | Sesuai (Toko khusus admin) | ✅ |
| TC-12.4 | Bahasa Indonesia | Periksa teks panel | "Informasi Profil", "Nama", "Simpan", dst | Sesuai (efek perbaikan BUG-03) | ✅ |
| TC-12.5 | Data ter-prefill | Lihat field | Nama "Cashier", email "cashier@vape.com" | Sesuai | ✅ |
| TC-12.6 | Ubah & simpan | Edit nama, Simpan | Tersimpan + notifikasi | Sesuai | ✅ |
| TC-12.7 | Logout | Klik "Keluar" | Sesi berakhir, kembali ke splash/login | Sesuai | ✅ |

