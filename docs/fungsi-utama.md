# Hasil Implementasi — Penjelasan Fungsi-Fungsi Utama

Bagian ini menjelaskan fungsi-fungsi inti yang membangun sistem POS Vape Story,
dikelompokkan per modul. Setiap fungsi disertai lokasi berkas agar mudah ditelusuri.

---

## 1. Autentikasi & Otorisasi

| Fungsi / Komponen | Lokasi | Penjelasan |
|-------------------|--------|------------|
| Login & sesi | Laravel Fortify | Menangani proses login, verifikasi kredensial, hashing password (bcrypt), dan autentikasi dua faktor (2FA). |
| `IsAdmin::handle()` | `app/Http/Middleware/IsAdmin.php` | Mencegat permintaan ke area admin; menolak (HTTP 403) bila pengguna bukan admin. |
| `IsCashier::handle()` | `app/Http/Middleware/IsCashier.php` | Membatasi akses area POS hanya untuk kasir dan admin. |
| Redirect dashboard | `routes/web.php` (`/dashboard`) | Setelah login, mengarahkan pengguna sesuai peran: admin → dashboard admin, kasir → dashboard POS. |

---

## 2. Transaksi Penjualan (POS) — Fungsi Inti Sistem

| Fungsi | Lokasi | Penjelasan |
|--------|--------|------------|
| `ProcessPaymentController::process()` | `app/Http/Controllers/POS/ProcessPaymentController.php` | **Fungsi paling inti.** Memproses pembayaran dalam satu transaksi database atomik: (1) membuat record `Sale`, (2) memotong stok dengan metode **FIFO** (batch promo/cukai lama diprioritaskan), (3) membekukan *snapshot* HPP & harga jual ke `sale_item_batches`, (4) mencatat mutasi stok, (5) menghitung total akhir. |

**Cara kerja ringkas `process()`:**
1. Memvalidasi data keranjang (produk, qty, harga, metode bayar).
2. Membungkus seluruh proses dengan `DB::beginTransaction()` … `commit()`/`rollBack()`
   agar stok dan uang tidak pernah tercatat setengah jadi.
3. Untuk tiap item, mengambil batch ber-stok dengan urutan FIFO dan mengunci baris
   (`lockForUpdate()`) untuk mencegah dua kasir menjual stok yang sama bersamaan.
4. Memotong stok per batch, mencatat harga & HPP saat itu sebagai *snapshot* permanen.
5. Menyimpan total bersih transaksi (sudah memperhitungkan promo & diskon).

---

## 3. Pengembalian Barang (Retur)

| Fungsi | Lokasi | Penjelasan |
|--------|--------|------------|
| `ReturnService::processSaleReturn()` | `app/Services/ReturnService.php` | Memproses retur dari transaksi POS: mengembalikan stok ke batch asal (urutan LIFO), mencatat `ReturnItem`, dan menambah `returned_quantity` agar barang tidak bisa diretur melebihi jumlah pembelian. |
| `ReturnService::roundRefund()` | `app/Services/ReturnService.php` | Membulatkan nilai refund: tunai dibulatkan ke Rp100 terdekat, non-tunai eksak. |
| `ReturnService::generateReturnNumber()` | `app/Services/ReturnService.php` | Membuat nomor retur unik berformat `RET-YYYYMM-XXXX`. |

**Catatan logika:** status transaksi diperbarui berdasarkan **akumulasi** seluruh retur
(bukan retur terakhir saja), sehingga transaksi yang habis diretur bertahap tetap
berstatus `returned`.

---

## 4. Manajemen Stok / Batch

| Fungsi | Lokasi | Penjelasan |
|--------|--------|------------|
| `InventoryService::restock()` | `app/Services/InventoryService.php` | Menambah stok dengan membuat batch baru (lot, jumlah, harga modal). |
| `InventoryService::adjustStock()` | `app/Services/InventoryService.php` | Menyesuaikan stok batch (penambahan/pengurangan), menolak bila hasilnya negatif. |
| `InventoryService::getStockReport()` | `app/Services/InventoryService.php` | Menghasilkan laporan stok beserta total kuantitas dan nilai modal. |
| `BatchObserver` | `app/Observers/BatchObserver.php` | Mencatat mutasi stok otomatis saat batch dibuat/diubah. |

---

## 5. Manajemen Produk, Kategori & Merek

| Fungsi | Lokasi | Penjelasan |
|--------|--------|------------|
| CRUD Produk | `app/Http/Controllers/Admin/ProductController.php` | Membuat, mengubah, menghapus, dan menampilkan produk beserta unggah gambar. |
| Accessor `getStockAttribute()` | `app/Models/Product.php` | Menghitung total stok produk dari seluruh batch secara otomatis. |
| Accessor `getPromoPriceAttribute()` / `getPromoStockAttribute()` | `app/Models/Product.php` | Menghitung harga & stok promo (cukai lama) untuk ditampilkan di katalog. |
| CRUD Kategori & Merek | `Admin/CategoryController.php`, `Admin/BrandController.php` | Mengelola data master kategori dan merek produk. |

---

## 6. Manajemen Promo & Diskon

| Fungsi | Lokasi | Penjelasan |
|--------|--------|------------|
| `PromotionController::store()` / `update()` | `app/Http/Controllers/Admin/PromotionController.php` | Membuat/mengubah promo (tipe persen, nominal tetap, atau BOGO) dan mengaitkannya ke produk tertentu via `sync()`. |
| `PromotionController::toggle()` | idem | Mengaktifkan/menonaktifkan promo dengan satu klik. |
| `PromotionController::validatePayload()` | idem | Memvalidasi data promo: kode unik, rentang tanggal valid (`end_date` ≥ `start_date`), tipe & target sah. |

---

## 7. Laporan Penjualan & Keuangan

| Fungsi | Lokasi | Penjelasan |
|--------|--------|------------|
| `ReportSaleController::index()` | `app/Http/Controllers/Admin/ReportSaleController.php` | Menyusun laporan penjualan: agregasi per kategori, merek, produk, metode bayar, stok, dan retur, plus ringkasan revenue/profit bersih. |
| `ReportSaleController::resolvePeriod()` | idem | Menentukan rentang tanggal sesuai periode (harian, mingguan, bulanan, kuartal, tahunan, atau kustom). |
| `ReportSaleController::fifoHppSubquery()` | idem | Inti perhitungan laba: menghitung **HPP bersih** dan **nilai refund** dari alokasi FIFO (`sale_item_batches`) sehingga profit akurat setelah retur. |
| `ReportSaleController::export()` | idem | Mengekspor laporan ke **CSV** (kompatibel Excel, dengan BOM UTF-8). |
| `ReportSaleController::pdf()` | idem | Mencetak laporan ke **PDF** rapi menggunakan pustaka mPDF. |
| `ReportSaleController::shoppingList()` | idem | Menghasilkan daftar belanja (produk habis & terlaris) dalam format PDF/Word. |

---

## 8. Manajemen Akun Pengguna

| Fungsi | Lokasi | Penjelasan |
|--------|--------|------------|
| `UserController::store()` | `app/Http/Controllers/Admin/UserController.php` | Membuat akun baru (admin/kasir) dengan validasi & password ter-hash. |
| `UserController::update()` | idem | Mengubah data akun; mencegah admin menurunkan peran dirinya sendiri atau admin terakhir. |
| `UserController::destroy()` | idem | Menghapus akun secara **soft delete** (data tetap ada) sehingga riwayat transaksi tidak ikut terhapus; aksi dicatat ke log audit. |
| `UserController::strongPassword()` | idem | Mendefinisikan kebijakan kata sandi kuat: min 8 karakter, huruf besar-kecil, angka, dan cek kebocoran (Have I Been Pwned). |

---

## 9. Pengaturan Toko & Struk

| Fungsi | Lokasi | Penjelasan |
|--------|--------|------------|
| `StoreController::update()` | `app/Http/Controllers/Settings/StoreController.php` | Menyimpan identitas toko (nama, alamat, telepon, logo) serta opsi konten struk. |
| `StoreSetting::getReceiptOptionsResolvedAttribute()` | `app/Models/StoreSetting.php` | Menggabungkan opsi struk tersimpan dengan default dan memastikan nilainya boolean murni (perbaikan BUG-10). |
| Cetak struk ESC-POS | Frontend POS (Web Bluetooth) | Mencetak struk transaksi ke printer thermal ESC-POS. |

---

## 10. Dashboard

| Fungsi | Lokasi | Penjelasan |
|--------|--------|------------|
| Dashboard Admin | `app/Http/Controllers/Admin/DashboardController.php` | Menampilkan ringkasan statistik bisnis (penjualan, produk, stok rendah). |
| Dashboard POS | `app/Http/Controllers/POS/DashboardController.php` | Antarmuka utama kasir untuk memulai transaksi & melihat ringkasan harian. |

---

### Catatan
Fungsi-fungsi di atas merupakan inti logika bisnis. Fungsi pendukung (penampilan
halaman/`index()` murni, validasi standar, dan *accessor* kecil) tidak seluruhnya
dirinci agar pembahasan tetap fokus pada alur utama: **penjualan (FIFO) → snapshot
HPP → retur → laporan**.
