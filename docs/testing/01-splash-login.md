# 01 — Splash Screen & Login

Modul autentikasi: halaman pembuka (splash), form login, validasi, dan redirect
berbasis peran.

## Cakupan

- Splash screen (`/`)
- Form login (`/login`)
- Validasi kredensial salah/kosong
- Redirect berdasarkan role (admin → dashboard admin, kasir → dashboard POS)

## Prasyarat

- Aplikasi berjalan di `http://127.0.0.1:8000`
- Akun admin & kasir tersedia (lihat README)

## Kasus Uji


| ID      | Skenario                | Langkah                                                      | Hasil yang Diharapkan                                                                              | Hasil                                                                                   | Status |
| ------- | ----------------------- | ------------------------------------------------------------ | -------------------------------------------------------------------------------------------------- | --------------------------------------------------------------------------------------- | ------ |
| TC-01.1 | Tampil splash screen    | Buka `/`                                                     | Logo + teks "STORY VAPE" + tagline "premium vape sejak 2020" tampil                                | Sesuai                                                                                  | ✅      |
| TC-01.2 | Tampil halaman login    | Buka `/login`                                                | Form Email, Password, tombol "Masuk", link "Lupa password?", checkbox "Ingat saya", carousel slide | Sesuai                                                                                  | ✅      |
| TC-01.3 | Login kredensial salah  | Email `admin@vape.com`, password `salahpassword`, klik Masuk | Tetap di `/login`, muncul pesan error, tidak masuk                                                 | Muncul pesan error                                                                      | ✅      |
| TC-01.4 | Bahasa pesan error      | Lihat pesan error pada TC-01.3                               | Pesan **berbahasa Indonesia**                                                                      | Sebelum perbaikan: "These credentials do not match our records." (Inggris) → **BUG-05** | ✅      |
| TC-01.5 | Login admin valid       | Email `admin@vape.com`, password `12345`, klik Masuk         | Redirect ke `/admin/dashboard`                                                                     | Sesuai                                                                                  | ✅      |
| TC-01.6 | Redirect role kasir     | Login `kasir@vape.com` / `12345`                             | Redirect ke `/pos/dashboard` (bukan admin)                                                         | Sesuai (by design route `/dashboard`)                                                   | ✅      |
| TC-01.7 | Toggle lihat password   | Klik ikon "Show password"                                    | Karakter password tampil/tersembunyi                                                               | Sesuai                                                                                  | ✅      |
| TC-01.8 | Field kosong            | Klik Masuk tanpa isi                                         | Validasi mencegah submit / muncul error required                                                   | Sesuai                                                                                  | ✅      |
| TC-01.9 | Akses admin tanpa login | Buka `/admin/dashboard` saat belum login                     | Diarahkan ke `/login`                                                                              | Sesuai (middleware `auth`)                                                              | ✅      |


