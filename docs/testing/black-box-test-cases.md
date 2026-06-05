# Black-Box Test Cases — Story Vape POS

> Dokumen pengujian black-box (uji dari sisi user, tanpa lihat kode). Jalankan manual oleh QA.
> Dibuat: 2026-06-04 · Berbasis route & validasi aktual di codebase.

## Cara Pakai
- **Status** diisi saat eksekusi: `PASS` / `FAIL` / `BLOCKED` / `N/A`.
- Kolom **Data Uji** = nilai konkret yang diketik/dipilih.
- **Expected Result** = hasil yang seharusnya muncul. Kalau aktual beda → `FAIL` + catat di kolom Catatan.
- Role: **Admin** (akses penuh) dan **Kasir/Cashier** (hanya `/pos/*`).

### Akun & Prasyarat
| Item | Keterangan |
|------|-----------|
| Akun Admin | email + password admin (bisa akses `/admin/*` & `/settings/store`) |
| Akun Kasir | email + password kasir (hanya `/pos/*`) |
| Data produk | minimal 2 produk aktif punya stok batch > 0; 1 produk stok 0 |
| Browser | Chrome/Edge terbaru, mode normal + tablet (responsif) |

---

## 1. Autentikasi & Settings

| ID | Skenario | Langkah | Data Uji | Expected Result | Status | Catatan |
|----|----------|---------|----------|-----------------|--------|---------|
| AUTH-01 | Login valid (admin) | Buka `/`, isi login, submit | email+pwd admin benar | Redirect ke `/admin/dashboard` | | |
| AUTH-02 | Login valid (kasir) | Login | email+pwd kasir benar | Redirect ke `/pos/dashboard` | | |
| AUTH-03 | Login password salah | Login | email benar, pwd salah | Tetap di login, muncul error kredensial; tidak redirect | | |
| AUTH-04 | Login email kosong | Submit form kosong | (kosong) | Validasi "email wajib diisi", tidak submit | | |
| AUTH-05 | Akses halaman tanpa login | Buka `/admin/products` langsung | — | Redirect ke halaman login | | |
| AUTH-06 | Kasir akses area admin | Login kasir, buka `/admin/dashboard` | — | Ditolak (403/redirect), tidak bisa masuk | | |
| AUTH-07 | Logout | Klik logout | — | Kembali ke login; back button tidak buka halaman ter-proteksi | | |
| AUTH-08 | Ganti password sukses | `/settings/security`, isi current+new+confirm | pwd lama benar, baru valid | Sukses, bisa login pakai pwd baru | | |
| AUTH-09 | Ganti password — current salah | Submit | current salah | Error, password tidak berubah | | |
| AUTH-10 | Ganti password — konfirmasi beda | Submit | new ≠ confirm | Error "konfirmasi tidak cocok" | | |
| AUTH-11 | Rate limit ganti password | Submit gagal 7x cepat (limit 6/menit) | — | Permintaan ke-7 di-throttle (429) | | |
| AUTH-12 | Update profil | `/settings/profile`, ubah nama, simpan | nama baru | Tersimpan, tampil nama baru | | |
| SET-01 | Update setting toko — nama wajib | `/settings/store`, kosongkan nama, simpan | nama kosong | Validasi "name required", gagal simpan | | |
| SET-02 | Update setting toko valid | Isi nama, alamat, telp, tagline, simpan | nilai valid | Toast "Pengaturan toko diperbarui." | | |
| SET-03 | Upload logo > 2MB | Pilih logo > 2MB | file 3MB | Ditolak (max 2048KB) | | |
| SET-04 | Upload logo non-gambar | Pilih file .pdf | sample.pdf | Ditolak (harus image) | | |
| SET-05 | Receipt header/footer panjang | Isi > 1000 char | 1001 char | Ditolak (max 1000) | | |
| SET-06 | Toggle receipt options | Centang/uncentang opsi struk, simpan | — | Tersimpan; **ReceiptPreview** ikut berubah (preview = sumber kebenaran struk) | | |
| SET-07 | Kasir akses setting toko | Login kasir, buka `/settings/store` | — | Ditolak (admin-only) | | |

---

## 2. POS / Transaksi

| ID | Skenario | Langkah | Data Uji | Expected Result | Status | Catatan |
|----|----------|---------|----------|-----------------|--------|---------|
| POS-01 | Tambah produk ke keranjang | Buka `/pos/dashboard`, klik produk | produk stok>0 | Masuk keranjang, subtotal terhitung | | |
| POS-02 | Naik/turun qty | Ubah qty item | qty 1→3 | Total item & subtotal update | | |
| POS-03 | Hapus item dari keranjang | Klik hapus | — | Item hilang, total update | | |
| POS-04 | Bayar cash — uang pas | Checkout, metode cash | paid = total | Sukses "Payment processed successfully!", stok berkurang | | |
| POS-05 | Bayar cash — uang lebih | Checkout | paid > total | Sukses, kembalian benar (paid−total) | | |
| POS-06 | Bayar cash — uang kurang | Checkout | paid < total | Tombol bayar disabled / ditolak; transaksi tidak terbentuk | | |
| POS-07 | Metode non-cash | Checkout pilih qris/bank_transfer/e_wallet | salah satu valid | Sukses tersimpan dgn payment_method sesuai | | |
| POS-08 | Metode pembayaran invalid | Kirim payment_method di luar daftar | "gopay" | Validasi gagal (422), in:cash,bank_transfer,qris,e_wallet | | |
| POS-09 | Keranjang kosong | Checkout tanpa item | items=[] | Ditolak (items required) | | |
| POS-10 | Qty melebihi stok | Beli qty > total stok batch | qty 999 | 422 "Stok tidak mencukupi untuk salah satu produk." | | |
| POS-11 | Produk stok 0 | Coba tambah produk stok 0 | — | Tidak bisa dijual / ditolak saat proses | | |
| POS-12 | Diskon manual per item | Set diskon item | discount 5000 | Total item = harga−diskon, tidak negatif | | |
| POS-13 | Diskon negatif | Kirim discount negatif | -100 | Ditolak (min:0) | | |
| POS-14 | Diskon transaksi | Terapkan diskon total | valid | total_amount = subtotal−diskon, min 0 | | |
| POS-15 | Promo cukai lama (FIFO) | Beli produk yg punya batch promo | qty span 2 batch | Batch promo dipakai dulu; harga campuran benar; promo_discount tercatat | | |
| POS-16 | Cetak struk setelah bayar | Selesai bayar → struk muncul | — | ReceiptModal tampil, isi sesuai (item, total, kembalian, header/footer toko) | | |
| POS-17 | Struk scrollable di tablet | Buka struk di viewport tablet | — | Modal bisa di-scroll, tombol aksi tetap terlihat | | |
| POS-18 | Konsistensi stok pasca-bayar | Cek stok produk setelah POS-04 | — | Stok berkurang sesuai qty; ada StockMutation tipe SALE | | |
| POS-19 | Transaksi hari ini | Buka `/pos/transactions/today` | — | Transaksi baru muncul di list | | |

### Retur

| ID | Skenario | Langkah | Data Uji | Expected Result | Status | Catatan |
|----|----------|---------|----------|-----------------|--------|---------|
| RET-01 | Buka halaman retur | `/pos/returns` | — | List penjualan hari ini + status retur | | |
| RET-02 | Filter tanggal | Pilih tanggal lain | tanggal valid | List penjualan tanggal tsb | | |
| RET-03 | Retur sebagian | Pilih sale, retur sebagian qty + alasan | qty < dibeli | Sukses "Stok sudah dikembalikan."; remaining_quantity berkurang | | |
| RET-04 | Retur tanpa alasan | Submit tanpa reason | reason kosong | Ditolak (reason required) | | |
| RET-05 | Retur qty 0 / negatif | Submit qty<1 | 0 | Ditolak (min:1) | | |
| RET-06 | Retur melebihi sisa | Retur qty > remaining | qty besar | Ditolak oleh ReturnService (error ditampilkan) | | |
| RET-07 | Stok kembali pasca-retur | Cek stok setelah RET-03 | — | Stok bertambah sesuai qty retur | | |
| RET-08 | Sale fully returned | Retur seluruh item | semua qty | status jadi `returned`, ditandai fully returned | | |

---

## 3. Admin / Produk

| ID | Skenario | Langkah | Data Uji | Expected Result | Status | Catatan |
|----|----------|---------|----------|-----------------|--------|---------|
| PRD-01 | List produk + nonaktif | `/admin/products` | — | Tampil semua termasuk nonaktif (badge status) | | |
| PRD-02 | Cari produk | Ketik di search | nama parsial | List terfilter | | |
| PRD-03 | Filter kategori/brand | Pilih kategori & brand | — | List sesuai filter | | |
| PRD-04 | Filter status stok | Pilih stock_status | low/out | List sesuai | | |
| PRD-05 | Sorting | Klik sort kolom | sort+dir | Urutan berubah asc/desc | | |
| PRD-06 | Tambah produk valid | Create, isi wajib, simpan | code, name, category, base_price | "Produk berhasil ditambahkan.", muncul di list | | |
| PRD-07 | Tambah — nama kosong | Submit | name kosong | "Nama produk wajib diisi." | | |
| PRD-08 | Tambah — kategori kosong | Submit | category_id kosong | "Kategori wajib dipilih." | | |
| PRD-09 | Tambah — harga kosong/negatif | Submit | base_price = -1 | "Harga Jual tidak boleh negatif." | | |
| PRD-10 | Auto-generate code | Submit tanpa code | code kosong, name diisi | Code di-generate dari nama (UPPER_SNAKE) | | |
| PRD-11 | Code duplikat | Tambah produk dgn code sudah ada | code existing | "Kode produk ini sudah digunakan." | | |
| PRD-12 | Upload gambar invalid | Pilih file .txt | sample.txt | "File harus berupa gambar." | | |
| PRD-13 | Gambar > 2MB | Upload 3MB | — | "Ukuran gambar maksimal 2MB." | | |
| PRD-14 | Tambah dgn batch awal | Isi batch_stock_quantity > 0 | stok+cost | Batch terbentuk, stok muncul | | |
| PRD-15 | Edit produk tanpa ganti gambar | Edit field lain, simpan | — | Tersimpan; gambar lama TIDAK hilang | | |
| PRD-16 | Edit ganti gambar | Upload gambar baru | valid | Gambar lama terhapus, baru tampil | | |
| PRD-17 | Hapus produk | Klik hapus + konfirmasi | — | "Produk berhasil dihapus.", hilang dari list | | |
| PRD-18 | Kasir akses produk admin | Login kasir, buka `/admin/products` | — | Ditolak (403) | | |
| CAT-01 | Tambah/edit/hapus kategori | CRUD kategori | valid | Berhasil, tercermin di filter | | |
| BRN-01 | Tambah/edit/hapus brand | CRUD brand | valid | Berhasil | | |
| BAT-01 | Tambah batch ke produk | Tambah batch | stok>0 | Stok produk bertambah | | |
| BAT-02 | Edit/hapus batch | Ubah/hapus | — | Stok ter-update | | |
| PROMO-01 | Tambah promosi | Create promo | valid | Muncul di list | | |
| PROMO-02 | Toggle promosi aktif | Klik toggle | — | Status aktif/nonaktif berubah | | |
| PROMO-03 | Hapus promosi | Hapus | — | Hilang dari list | | |
| USR-01 | Tambah user | `/admin/users`, create | valid | User baru muncul | | |
| USR-02 | Edit/hapus user | Ubah/hapus | — | Berhasil | | |
| USR-03 | Rate limit user (30/menit) | >30 request cepat | — | Di-throttle (429) | | |

---

## 4. Laporan & Dashboard

| ID | Skenario | Langkah | Data Uji | Expected Result | Status | Catatan |
|----|----------|---------|----------|-----------------|--------|---------|
| DSH-01 | Dashboard admin tampil | `/admin/dashboard` | — | KPI, tren, forecast ter-render tanpa error | | |
| DSH-02 | Filter periode dashboard | Ganti rentang tanggal | hari/minggu/bulan | Angka & grafik ter-update | | |
| DSH-03 | Dashboard tanpa data | Periode tanpa transaksi | — | Tampil 0 / empty state, tidak error | | |
| DSH-04 | Dashboard POS (kasir) | `/pos/dashboard` | — | Tampil sesuai role kasir | | |
| RPT-01 | Laporan penjualan | `/admin/reports/sales` | — | Tabel/agregat per kategori, brand, produk, payment | | |
| RPT-02 | Filter periode laporan | Set rentang tanggal | valid | Data sesuai rentang | | |
| RPT-03 | Rentang tanggal terbalik | start > end | — | Divalidasi / ditangani, tidak error fatal | | |
| RPT-04 | Export Excel/CSV | Klik export | — | File ter-download, isi sesuai filter | | |
| RPT-05 | Export PDF | Klik PDF | — | PDF ter-generate, layout & total benar | | |
| RPT-06 | Shopping list | Buka shopping-list | — | Daftar restock ter-generate | | |
| RPT-07 | Transaksi hari ini (admin) | `/admin/transactions/today` | — | List transaksi hari ini akurat | | |
| AUD-01 | Halaman audit (hidden) | Buka `/admin/__audit` | — | Neraca tampil (akses via URL, tanpa link sidebar) | | |
| AUD-02 | Detail neraca | `/admin/__audit/neraca-detail` | — | Rincian neraca tampil | | |
| AUD-03 | Opening balance | Edit & simpan saldo awal | valid | Tersimpan, neraca ter-update | | |

---

## 5. Cross-cutting (Non-fungsional ringan)

| ID | Skenario | Expected Result | Status | Catatan |
|----|----------|-----------------|--------|---------|
| X-01 | Responsif tablet (POS, login, splash) | Layout rapi, tombol tidak terpotong | | |
| X-02 | PWA start_url | Buka PWA → landing `/pos/dashboard` | | |
| X-03 | Session expired saat transaksi | Diarahkan login, data tidak korup | | |
| X-04 | Double-submit bayar (klik 2x cepat) | Hanya 1 transaksi terbentuk | | |
| X-05 | Refresh saat keranjang terisi | Perilaku jelas (persist / kosong) tanpa error | | |
| X-06 | Input angka via teks | Field numerik tolak huruf | | |
| X-07 | Karakter spesial / XSS di nama | Tersimpan/escaped aman, tidak ter-eksekusi | | |

---

### Ringkasan Eksekusi
| Area | Total | Pass | Fail | Blocked |
|------|-------|------|------|---------|
| Auth & Settings | 19 | | | |
| POS & Retur | 27 | | | |
| Admin & Produk | 26 | | | |
| Laporan & Dashboard | 17 | | | |
| Cross-cutting | 7 | | | |
| **Total** | **96** | | | |
