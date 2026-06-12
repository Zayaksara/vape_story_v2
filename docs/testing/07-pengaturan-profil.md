# 07 — Pengaturan Profil

Pengaturan akun di `/settings/profile`: ubah nama/email, navigasi sub-menu (Profil,
Keamanan, Toko), dan hapus akun.

## Cakupan
- Form Informasi Profil (Nama, Email, Simpan)
- Navigasi pengaturan: Profil / Keamanan / Toko
- Hapus Akun (dialog konfirmasi + password)

## Prasyarat
- Login sebagai admin

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-07.1 | Muat halaman profil | Buka `/settings/profile` | Form profil + menu samping tampil | Sesuai | ✅ |
| TC-07.2 | Data ter-prefill | Lihat field | Nama "Admin User", Email "admin@vape.com" terisi | Sesuai | ✅ |
| TC-07.3 | Navigasi sub-menu | Klik "Keamanan" / "Toko" | Pindah ke `/settings/security` / `/settings/store` | Sesuai | ✅ |
| TC-07.4 | Ubah nama & simpan | Edit Nama, klik Simpan | Tersimpan + notifikasi sukses | Sesuai | ✅ |
| TC-07.5 | Validasi email | Kosongkan/format salah email, simpan | Muncul pesan error | Sesuai | ✅ |
| TC-07.6 | Dialog hapus akun | Klik "Hapus Akun" | Dialog konfirmasi + input password muncul | Sesuai | ✅ |
| TC-07.7 | Hapus tanpa password | Submit dialog tanpa password | Validasi mencegah & fokus ke field password | Sesuai | ✅ |
| TC-07.8 | **Bahasa Indonesia** | Periksa seluruh teks panel | Semua teks berbahasa Indonesia | Sesuai | ✅ |
