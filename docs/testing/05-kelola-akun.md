# 05 — Kelola Akun

Manajemen pengguna di `/admin/users`: daftar akun, filter role, tambah/edit/hapus akun.
Dibatasi `throttle:30,1`.

## Cakupan
- Kartu ringkasan (total akun, jumlah Admin, jumlah Cashier)
- Pencarian nama/email
- Filter role
- Tambah/Edit/Hapus akun
- Proteksi hapus diri sendiri

## Prasyarat
- Login sebagai admin

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-05.1 | Muat daftar akun | Buka `/admin/users` | Tabel akun + kartu ringkasan tampil | Sesuai (4 akun, 1 Admin, 3 Cashier) | ✅ |
| TC-05.2 | Kolom tabel | Lihat header | Pengguna, Email, Role, Bergabung, Aksi | Sesuai | ✅ |
| TC-05.3 | Penanda akun sendiri | Lihat baris admin yg login | Label "(Anda)" di samping nama | Sesuai | ✅ |
| TC-05.4 | Proteksi hapus diri | Lihat tombol hapus akun sendiri | Tombol hapus **disabled** | Sesuai (mencegah hapus diri sendiri) | ✅ |
| TC-05.5 | Pencarian | Ketik nama/email | Baris tersaring | Sesuai | ✅ |
| TC-05.6 | Filter role | Pilih "Admin"/"Cashier" | Hanya role tsb tampil | Sesuai | ✅ |
| TC-05.7 | Tambah akun | Klik "Tambah Akun", isi form, simpan | Akun baru muncul di tabel | Sesuai | ✅ |
| TC-05.8 | Edit akun | Klik ikon edit, ubah, simpan | Perubahan tersimpan | Sesuai | ✅ |
| TC-05.9 | Hapus akun lain | Klik hapus akun selain diri | Akun terhapus setelah konfirmasi | Sesuai | ✅ |
| TC-05.10 | Validasi email unik | Tambah akun dgn email duplikat | Muncul error validasi | Sesuai | ✅ |
| TC-05.11 | Rate limit | Lakukan banyak request cepat | Dibatasi `throttle:30,1` | Sesuai (by design) | ✅ |
