# 📘 USER GUIDE — Story Vape (POS & Admin)

Panduan pengguna lengkap untuk aplikasi **Story Vape**: sistem Point of Sale (POS) dan
manajemen toko vape. Dokumen ini mencakup pengenalan, spesifikasi, instalasi, dan panduan
penggunaan untuk **seluruh aktor** (Admin & Kasir).

> Versi dokumen: 1.0 · Tanggal: 5 Juni 2026

---

## Daftar Isi

1. [Pengenalan Aplikasi](#1-pengenalan-aplikasi)
2. [Spesifikasi Aplikasi](#2-spesifikasi-aplikasi)
3. [Panduan Instalasi](#3-panduan-instalasi)
4. [User Guide Semua Aktor](#4-user-guide-semua-aktor)
   - [4.1 Aktor & Hak Akses](#41-aktor--hak-akses)
   - [4.2 Memulai (Login & Logout)](#42-memulai-login--logout)
   - [4.3 Panduan Admin](#43-panduan-admin)
   - [4.4 Panduan Kasir](#44-panduan-kasir)
   - [4.5 Pengaturan Akun (Semua Aktor)](#45-pengaturan-akun-semua-aktor)

---

## 1. Pengenalan Aplikasi

**Story Vape** adalah aplikasi web (berbasis browser) untuk mengelola operasional toko
vape — mulai dari penjualan di kasir hingga pelaporan bisnis untuk pemilik. Aplikasi
dirancang **mobile-first / tablet-friendly** dan dapat dipasang sebagai **PWA** (Progressive
Web App) sehingga bisa dibuka layaknya aplikasi native di tablet maupun ponsel.

### Tujuan Aplikasi

- Mempercepat dan merapikan **transaksi penjualan** di kasir (POS).
- Mengelola **inventaris** (produk, merek, kategori, batch stok, masa berlaku cukai).
- Menyediakan **laporan & analitik** penjualan, keuntungan, dan rekomendasi belanja ulang.
- Mengelola **promo/diskon** dan **akun pengguna** (admin & kasir).
- Mencetak **struk thermal** langsung ke printer Bluetooth (ESC/POS) tanpa dialog browser.

### Fitur Utama

| Area | Fitur |
|------|-------|
| **Kasir (POS)** | Grid produk, pencarian & filter kategori, keranjang, voucher, pembayaran (Cash/Transfer/QRIS/E-Wallet), kembalian otomatis, struk thermal 58/80 mm, pengurangan stok otomatis |
| **Pengembalian** | Retur barang per item, alasan & metode refund, pengembalian stok otomatis (FIFO), pembulatan refund tunai ke Rp100 |
| **Inventaris** | Manajemen produk (CRUD), kategori, merek, batch stok, badge **"CUKAI LAMA"**, pencarian *typo-tolerant* |
| **Laporan** | Ringkasan revenue/profit, analisis per Produk/Merek/Metode Bayar/Stok/Return, export, shopping list (rekomendasi restock) |
| **Dashboard** | Kartu metrik, grafik tren per jam (Masa Lalu / Sekarang / Prediksi), perbandingan periode, top produk/kategori/merek |
| **Promo** | Diskon persentase / potongan tetap, periode aktif, kuota pemakaian, toggle aktif/nonaktif |
| **Pengaturan Toko** | Identitas toko, logo, tagline, kustomisasi isi struk (14 toggle), pratinjau struk live, printer Bluetooth |
| **Akun** | Manajemen pengguna (admin/kasir), profil, keamanan (ganti password, 2FA) |

### Karakteristik Khusus

- **Manajemen Cukai (Batch & FIFO):** stok dilacak per *batch*, lengkap dengan tahun cukai.
  Produk dengan batch cukai lama otomatis menampilkan badge **"CUKAI LAMA"**. Penjualan dan
  retur memakai metode **FIFO**, dan keuntungan dihitung dari snapshot HPP per batch.
- **Multi-aktor:** pemisahan akses tegas antara **Admin** (back-office) dan **Kasir** (POS).
- **Cetak struk ESC/POS:** dukungan printer thermal Bluetooth via **Web Bluetooth**, struk
  fisik konsisten dengan pratinjau di layar.

---

## 2. Spesifikasi Aplikasi

### 2.1 Arsitektur & Teknologi

| Lapisan | Teknologi |
|---------|-----------|
| **Backend** | PHP **8.3+**, Laravel **13** |
| **Frontend** | Vue **3** + Inertia.js (SPA tanpa REST manual) + TypeScript |
| **Styling** | Tailwind CSS **4**, komponen **shadcn-vue / reka-ui** |
| **Build tool** | Vite **8** |
| **Database** | PostgreSQL (disarankan — mendukung pencarian *trigram* typo-tolerant) atau SQLite/MySQL |
| **Autentikasi** | Laravel Fortify (login, ganti password, 2FA), Spatie Laravel Permission (role) |
| **PDF & Cetak** | mpdf (laporan PDF), Web Bluetooth + ESC/POS (struk thermal) |
| **Grafik** | ApexCharts (vue3-apexcharts) |
| **PWA** | Service worker + manifest, `start_url` → `/pos/dashboard` |

### 2.2 Kebutuhan Sistem (Server)

- **PHP** ≥ 8.3 dengan ekstensi standar Laravel (`pdo`, `mbstring`, `openssl`, `gd`, dll).
- **Composer** 2.x
- **Node.js** ≥ 20 dan **npm**
- **Database**: PostgreSQL 14+ (disarankan) — atau SQLite untuk pengembangan ringan.
- Ruang disk untuk penyimpanan logo toko (disk `public`).

### 2.3 Kebutuhan Sistem (Pengguna / Klien)

- **Browser modern:** Google Chrome atau Microsoft Edge (disarankan).
  - **Fitur printer Bluetooth (Web Bluetooth) hanya berjalan di Chrome/Edge**, melalui
    **HTTPS atau localhost**.
- **Perangkat:** desktop, tablet, atau ponsel (UI responsif & PWA).
- **Printer (opsional):** printer thermal Bluetooth ESC/POS, mis. **Codeshop CM-T58BL**,
  kertas 58 mm atau 80 mm.

### 2.4 Peran Pengguna (Role)

| Role | Akses |
|------|-------|
| **Admin** | Seluruh back-office (`/admin/*`) + dapat juga membuka POS (`/pos/*`) |
| **Kasir (Cashier)** | Hanya POS (`/pos/*`) + pengaturan akun pribadi |

---

## 3. Panduan Instalasi

> Instalasi ini untuk **menjalankan/menyiapkan server** aplikasi. Pengguna akhir (kasir/admin)
> cukup membuka URL aplikasi di browser — lihat [Bagian 4](#4-user-guide-semua-aktor).

### 3.1 Prasyarat

Pastikan sudah terpasang: **PHP 8.3+**, **Composer**, **Node.js 20+ & npm**, dan database
(**PostgreSQL** disarankan). Cek versi:

```bash
php -v
composer -V
node -v
npm -v
```

### 3.2 Langkah Instalasi

**1) Ambil kode & masuk ke folder proyek**

```bash
git clone <url-repository> story_vape
cd story_vape
```

**2) Pasang dependensi backend & frontend**

```bash
composer install
npm install
```

**3) Siapkan file environment**

```bash
# Salin contoh env
cp .env.example .env       # Windows PowerShell: Copy-Item .env.example .env

# Buat application key
php artisan key:generate
```

**4) Konfigurasi database di `.env`**

Untuk **PostgreSQL** (disarankan, agar pencarian produk *typo-tolerant* aktif):

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=vapor
DB_USERNAME=postgres
DB_PASSWORD=secret
```

Untuk **SQLite** (pengembangan cepat) — biarkan `DB_CONNECTION=sqlite`, lalu buat file:

```bash
# Windows PowerShell
New-Item -ItemType File database/database.sqlite
# macOS/Linux
touch database/database.sqlite
```

**5) Migrasi & isi data awal**

```bash
php artisan migrate --seed
```

Seeder bawaan (`DatabaseSeeder`) membuat **akun default**, pengaturan toko, serta data demo
produk & transaksi. Akun default:

| Role | Email | Password |
|------|-------|----------|
| **Admin** | `admin@vape.com` | `12345` |
| **Kasir** | `kasir@vape.com` | `12345` |
| **Kasir** | `cashier@vape.com` | `cashier123` |

> ⚠️ **Wajib ganti password** akun default sebelum dipakai di lingkungan nyata
> (lihat [4.5 Keamanan](#45-pengaturan-akun-semua-aktor)).

**6) (Opsional) Isi data demo transaksi 90 hari** — agar dashboard & laporan terisi penuh:

```bash
php artisan db:seed --class=TransactionDemoSeeder
# Atur volume via env (opsional):
# TX_SEED_DAYS=90 TX_SEED_MIN_PER_DAY=4 TX_SEED_MAX_PER_DAY=18
```

**7) Hubungkan storage publik** (agar logo toko tampil):

```bash
php artisan storage:link
```

### 3.3 Menjalankan Aplikasi

**Mode pengembangan** (server + queue + Vite sekaligus):

```bash
composer dev
```

Lalu buka **http://127.0.0.1:8000**.

Atau jalankan terpisah:

```bash
php artisan serve     # backend di :8000
npm run dev           # Vite dev server
```

**Mode produksi** (build aset lebih dulu):

```bash
npm run build
php artisan serve     # atau jalankan di belakang Nginx/Apache
```

> 💡 **Catatan PWA & Bluetooth:** fitur printer Bluetooth dan instalasi PWA hanya bekerja
> melalui **HTTPS** atau **localhost**. Untuk produksi, pastikan domain memakai sertifikat SSL.

### 3.4 Memasang sebagai PWA (di tablet/ponsel)

1. Buka URL aplikasi di **Chrome/Edge**.
2. Buka menu browser → **"Install app" / "Tambahkan ke layar utama"**.
3. Aplikasi akan terpasang dan membuka langsung ke **Dashboard POS** (`/pos/dashboard`).

---

## 4. User Guide Semua Aktor

> **Konvensi screenshot.** Tiap layar di bawah menyediakan *slot* untuk tangkapan layar.
> Simpan gambar di folder `docs/screenshots/` memakai **nama file yang tertera**, maka gambar
> akan otomatis tampil di bawah caption-nya. Resolusi disarankan: lebar **1280–1600 px**
> (desktop) atau resolusi penuh tablet. Hapus baris *"(belum ada gambar…)"* setelah gambar dipasang.

### 4.1 Aktor & Hak Akses

Aplikasi memiliki **dua aktor**:

| Aktor | Tujuan Login | Halaman Awal | Menu yang Terlihat |
|-------|--------------|--------------|--------------------|
| **Admin** | Mengelola toko & melihat laporan | `/admin/dashboard` | Dashboard, Produk, Laporan, Akun, Promo, Pengaturan Toko, Profil, Keamanan, + POS |
| **Kasir** | Melayani transaksi | `/pos/dashboard` | Dashboard POS, Katalog, Riwayat, Retur, Test Printer, Profil, Keamanan |

> Setelah login, aplikasi **otomatis mengarahkan** ke halaman awal sesuai role. Admin yang
> ingin membuka kasir dapat mengakses `/pos/dashboard`. Kasir **tidak dapat** membuka halaman
> admin (akan mendapat **403 Forbidden**).

---

### 4.2 Memulai (Login & Logout)

**Login**

1. Buka URL aplikasi → tampil **Splash screen** lalu halaman **Login**.
2. Masukkan **Email** dan **Password**.
3. Klik **Masuk**.
4. Aplikasi mengarahkan ke dashboard sesuai role Anda.

> Jika email/password salah, muncul pesan **"Kredensial tidak cocok"**. Halaman login juga
> menampilkan **tagline & alamat toko** yang diatur admin.

**Logout**

1. Klik **avatar/nama Anda** di pojok header.
2. Pilih **Keluar**.
3. Sesi berakhir dan Anda kembali ke halaman login.

![Splash screen](screenshots/4-2-splash.png)
*Gambar 4.2.1 — Splash screen. (belum ada gambar: `docs/screenshots/4-2-splash.png`)*

![Halaman Login](screenshots/4-2-login.png)
*Gambar 4.2.2 — Halaman Login. (belum ada gambar: `docs/screenshots/4-2-login.png`)*

---

### 4.3 Panduan Admin

Admin masuk ke `/admin/dashboard`. Navigasi utama ada di sidebar.

#### 4.3.1 Dashboard Admin (`/admin/dashboard`)

Ringkasan bisnis secara visual.

- **Kartu metrik:** Pendapatan, Transaksi, Keuntungan, Produk Terjual — masing-masing dengan
  indikator perubahan (mis. *"-57,1% vs Kemarin"*).
- **Filter periode:** Hari Ini, Minggu Ini, Bulan Ini, Kuartal, Tahun, atau **Kustom**
  (pilih rentang tanggal sendiri).
- **Grafik Tren Pendapatan (per jam):** tiga seri — *Masa Lalu*, *Sekarang*, *Prediksi*.
- **Tabel Perbandingan Periode:** Pendapatan/Transaksi/Keuntungan/Produk vs periode sebelumnya.
- **Donut Pembayaran Populer:** distribusi Tunai/Transfer/QRIS/E-Wallet.
- **Top 5 Produk / Kategori / Merek.**

**Cara pakai:** pilih periode di bagian atas → seluruh kartu & grafik ter-refresh otomatis.

![Dashboard Admin](screenshots/4-3-1-dashboard-admin.png)
*Gambar 4.3.1 — Dashboard Admin. (belum ada gambar: `docs/screenshots/4-3-1-dashboard-admin.png`)*

#### 4.3.2 Manajemen Produk (`/admin/products`)

Kelola katalog produk, kategori, merek, dan batch stok.

**Melihat & mencari:**
- Kartu ringkasan menampilkan total produk, stok habis, dan total kategori.
- **Cari produk** di kolom pencarian — *typo-tolerant* (ketik "manggo" tetap menemukan "Mango").
- **Filter** berdasarkan kategori atau merek; **klik header kolom** untuk mengurutkan.
- Badge **"CUKAI LAMA"** menandai produk dengan batch cukai lama; produk stok 0 berlabel **"Habis"**.
- Klik baris produk untuk membuka **detail** (sheet samping).

**Menambah produk:**
1. Klik **Tambah Produk**.
2. Isi form: nama, kode, merek, kategori, rasa (flavor), nikotin, ukuran, harga jual, dll.
3. Simpan.

**Mengubah / menghapus:**
- Klik ikon **edit** pada baris untuk mengubah.
- Klik ikon **hapus** → konfirmasi pada modal **"Hapus Produk?"**.

**Mengelola stok (batch):**
- Tambahkan **batch** baru per produk (jumlah, harga modal, tahun cukai) untuk menambah stok.
- Stok terkurang otomatis saat penjualan (FIFO) dan kembali saat retur.

> 💡 Kategori dan merek juga dikelola dari area ini (tambah/edit/hapus).

![Manajemen Produk — daftar](screenshots/4-3-2-produk-list.png)
*Gambar 4.3.2a — Manajemen Produk (daftar, filter, pencarian). (belum ada gambar: `docs/screenshots/4-3-2-produk-list.png`)*

![Manajemen Produk — form](screenshots/4-3-2-produk-form.png)
*Gambar 4.3.2b — Form tambah/edit produk. (belum ada gambar: `docs/screenshots/4-3-2-produk-form.png`)*

#### 4.3.3 Laporan Penjualan (`/admin/reports/sales`)

Analisis penjualan & keuntungan.

- **Kartu ringkasan:** Total Revenue, Total Profit, Item Terjual, Total Transaksi.
- **Filter periode:** Harian / Mingguan / Bulanan / Quarter / Tahunan / **Kustom**.
- **Tab analisis:** Produk, Merek, Metode Bayar, Stok, Return.
- **Pencarian dalam tab** & **sorting kolom**.
- **Export:** klik **Export** untuk mengunduh laporan; tersedia juga **export PDF**.
- **Shopping List ("Belanja?"):** rekomendasi produk yang perlu di-restock.

> Profit dihitung = Revenue − HPP (snapshot batch FIFO), sehingga akurat terhadap konsumsi stok.

![Laporan Penjualan](screenshots/4-3-3-laporan.png)
*Gambar 4.3.3 — Laporan Penjualan (ringkasan & tab analisis). (belum ada gambar: `docs/screenshots/4-3-3-laporan.png`)*

#### 4.3.4 Kelola Akun (`/admin/users`)

Manajemen pengguna admin & kasir.

- Kartu ringkasan: total akun, jumlah Admin, jumlah Cashier.
- **Cari** nama/email, **filter** berdasarkan role.
- **Tambah Akun:** klik **Tambah Akun**, isi nama/email/password/role, simpan.
  Email harus **unik**.
- **Edit / Hapus** akun lain. Akun Anda sendiri ditandai **"(Anda)"** dan tombol hapusnya
  **dinonaktifkan** (tidak bisa menghapus diri sendiri).

![Kelola Akun](screenshots/4-3-4-akun.png)
*Gambar 4.3.4 — Kelola Akun (daftar pengguna admin & kasir). (belum ada gambar: `docs/screenshots/4-3-4-akun.png`)*

#### 4.3.5 Promo & Diskon (`/admin/promotions`)

- Kartu ringkasan: Total / Aktif / Akan Datang / Berakhir.
- Kolom: Kode, Nama, Tipe, Nilai, Target, Periode, Pemakaian, Status.
- **Tipe promo:** **Persentase** (mis. 10%) atau **Potongan Tetap** (mis. Rp10.000).
- **Status** otomatis dari periode: dalam rentang = *Aktif*, lewat tanggal = *Berakhir*.
- **Tambah/Edit/Hapus** promo, dan **Toggle** untuk mengaktifkan/menonaktifkan.

![Promo & Diskon](screenshots/4-3-5-promo.png)
*Gambar 4.3.5 — Promo & Diskon. (belum ada gambar: `docs/screenshots/4-3-5-promo.png`)*

#### 4.3.6 Pengaturan Toko & Struk (`/settings/store`) — khusus Admin

Mengatur identitas toko dan kustomisasi struk.

**Identitas toko:**
- **Nama toko** (wajib), Alamat, Telepon, Tagline.
- **Logo** (PNG/JPG, maksimal **2 MB**). Tagline & alamat juga tampil di **halaman login**.

**Kustomisasi struk ("Konten Struk"):**
- **14 toggle** dalam 3 grup: *Header Toko*, *Info Transaksi*, *Detail Item & Total*.
- Tombol **"Centang semua"** / **"Hapus semua"**.
- **Footer struk custom** (teks multi-baris, tampil rata tengah).
- **Pratinjau struk live** dengan pilihan kertas **58 mm / 80 mm**, plus **"Cetak Pratinjau"**.

> 💡 Setiap perubahan format struk langsung terlihat di pratinjau. Simpan untuk menerapkan.

**Printer Bluetooth (ESC/POS)** — pada panel "Printer Bluetooth":
1. Pastikan memakai **Chrome/Edge** via **HTTPS/localhost**, printer menyala & dalam jangkauan.
2. Klik **Pair Printer** → pilih perangkat (mis. Codeshop CM-T58BL) → status menjadi **"Terhubung"**.
3. Printer akan **auto-reconnect** saat halaman dibuka kembali.
4. Halaman uji tersedia di `/pos/printer-test` (cetak teks custom + log koneksi).

![Pengaturan Toko & Struk](screenshots/4-3-6-toko-struk.png)
*Gambar 4.3.6a — Pengaturan Toko & kustomisasi struk (dengan pratinjau live). (belum ada gambar: `docs/screenshots/4-3-6-toko-struk.png`)*

![Panel Printer Bluetooth](screenshots/4-3-6-printer.png)
*Gambar 4.3.6b — Panel Printer Bluetooth (ESC/POS). (belum ada gambar: `docs/screenshots/4-3-6-printer.png`)*

---

### 4.4 Panduan Kasir

Kasir masuk ke `/pos/dashboard`. Inilah layar utama melayani penjualan.

#### 4.4.1 Membuat Transaksi (`/pos/dashboard`)

1. **Pilih produk:** klik kartu produk pada grid → masuk ke **Keranjang**.
   - Gunakan **pencarian** atau **tab kategori** untuk menemukan produk.
   - Kartu menampilkan harga normal/promo (harga promo untuk badge **"CUKAI LAMA"**) dan
     indikator stok (*"246 pcs"*, *"Sisa 1"*, atau *"Habis"*).
2. **Atur jumlah:** di keranjang, klik **+ / −** untuk menambah/mengurangi qty, atau hapus item.
   Subtotal & total ter-update otomatis. Bisa juga **kosongkan keranjang** atau pakai **voucher**.
3. **Bayar:** klik **Bayar Saja** → modal **Pembayaran** terbuka menampilkan **Total Tagihan**.
4. **Pilih metode pembayaran:** Cash / Bank Transfer / QRIS / E-Wallet.
   - Untuk **Cash**, masukkan nominal diterima atau pakai tombol **nominal cepat**
     (mis. "Rp 300 K"). **Kembalian** dihitung otomatis.
   - Tombol **Konfirmasi Pembayaran** baru aktif setelah nominal valid.
5. **Konfirmasi:** klik **Konfirmasi Pembayaran** → **struk** muncul (nomor invoice, item, kembalian).
6. **Cetak & selesai:**
   - Pilih ukuran kertas **58/80 mm**, klik **Print Nota** (atau cetak via **Printer BT**).
   - Klik **Selesai** → keranjang kosong, kembali ke grid.

> Setiap transaksi **otomatis mengurangi stok** dan langsung muncul di Riwayat serta metrik admin.

![Dashboard POS — grid & keranjang](screenshots/4-4-1-pos-grid.png)
*Gambar 4.4.1a — Layar kasir: grid produk + keranjang. (belum ada gambar: `docs/screenshots/4-4-1-pos-grid.png`)*

![Modal Pembayaran](screenshots/4-4-1-pos-bayar.png)
*Gambar 4.4.1b — Modal Pembayaran (metode, nominal cepat, kembalian). (belum ada gambar: `docs/screenshots/4-4-1-pos-bayar.png`)*

![Struk transaksi](screenshots/4-4-1-pos-struk.png)
*Gambar 4.4.1c — Struk hasil transaksi (58/80 mm). (belum ada gambar: `docs/screenshots/4-4-1-pos-struk.png`)*

#### 4.4.2 Katalog Produk (`/pos/products`)

Daftar produk untuk dilihat kasir (harga, stok, badge cukai), dengan pencarian, filter, dan
pagination. Berguna untuk mengecek ketersediaan/harga tanpa membuka keranjang.

![Katalog Produk POS](screenshots/4-4-2-katalog.png)
*Gambar 4.4.2 — Katalog Produk (POS). (belum ada gambar: `docs/screenshots/4-4-2-katalog.png`)*

#### 4.4.3 Riwayat Transaksi (`/pos/transactions/today`)

Menampilkan transaksi hari ini. Transaksi yang telah diretur tetap tampil dengan **nilai bersih**
dan badge **"Diretur"** agar konsisten dengan dashboard.

![Riwayat Transaksi](screenshots/4-4-3-riwayat.png)
*Gambar 4.4.3 — Riwayat Transaksi hari ini. (belum ada gambar: `docs/screenshots/4-4-3-riwayat.png`)*

#### 4.4.4 Pengembalian Barang / Retur (`/pos/returns`)

1. Buka **Retur** → tampil daftar transaksi hari ini, form retur, dan riwayat retur.
2. **Pilih transaksi** (mis. SALE-001119) → form terisi item transaksi tersebut.
3. **Tentukan jumlah retur** per item dengan stepper **+ / −** (maksimal = jumlah beli;
   untuk transaksi yang sudah pernah diretur, dibatasi ke **sisa qty**).
4. **Pilih alasan** (mis. "Barang rusak") dan **metode pengembalian uang** (Tunai/Transfer/QRIS/E-Wallet).
5. Klik **Proses Return** (aktif setelah alasan dipilih).
6. Retur tersimpan, **stok dikembalikan otomatis (FIFO)**, dan riwayat retur bertambah.

> 💡 Refund **tunai** dibulatkan ke kelipatan **Rp100** terdekat. Jika seluruh unit sebuah
> transaksi sudah diretur, statusnya menjadi **"Diretur penuh"**.

![Pengembalian Barang](screenshots/4-4-4-retur.png)
*Gambar 4.4.4 — Pengembalian Barang / Retur. (belum ada gambar: `docs/screenshots/4-4-4-retur.png`)*

#### 4.4.5 Test Printer (`/pos/printer-test`)

Halaman untuk menguji printer thermal Bluetooth:
1. **Pair Printer** (Chrome/Edge, HTTPS/localhost).
2. Ketik teks → **Cetak Test** → teks tercetak; log menampilkan status koneksi.
3. Tombol **Reconnect / Disconnect** untuk mengelola koneksi. Jika mencetak tanpa koneksi,
   muncul pesan **"Belum terhubung ke printer"**.

![Test Printer](screenshots/4-4-5-printer-test.png)
*Gambar 4.4.5 — Halaman Test Printer. (belum ada gambar: `docs/screenshots/4-4-5-printer-test.png`)*

---

### 4.5 Pengaturan Akun (Semua Aktor)

Tersedia untuk **Admin maupun Kasir** lewat menu **avatar → Pengaturan**.

**Profil (`/settings/profile`):**
- Ubah **Nama** dan **Email**, lalu **Simpan**. Muncul notifikasi konfirmasi.

**Keamanan (`/settings/security`):**
- **Ganti password** (dibatasi 6 percobaan per menit demi keamanan).
- Aktifkan **Two-Factor Authentication (2FA)** bila tersedia.

> Kasir hanya melihat menu **Profil** dan **Keamanan**. Menu **"Toko"** (Pengaturan Toko/Struk)
> hanya untuk **Admin**.

![Pengaturan Profil](screenshots/4-5-profil.png)
*Gambar 4.5a — Pengaturan Profil. (belum ada gambar: `docs/screenshots/4-5-profil.png`)*

![Pengaturan Keamanan](screenshots/4-5-keamanan.png)
*Gambar 4.5b — Pengaturan Keamanan (ganti password, 2FA). (belum ada gambar: `docs/screenshots/4-5-keamanan.png`)*

---

## Lampiran — Ringkasan Peta Halaman

| Halaman | URL | Akses |
|---------|-----|-------|
| Login | `/login` | Publik |
| Dashboard Admin | `/admin/dashboard` | Admin |
| Manajemen Produk | `/admin/products` | Admin |
| Laporan Penjualan | `/admin/reports/sales` | Admin |
| Kelola Akun | `/admin/users` | Admin |
| Promo & Diskon | `/admin/promotions` | Admin |
| Pengaturan Toko & Struk | `/settings/store` | Admin |
| Dashboard POS (Kasir) | `/pos/dashboard` | Admin & Kasir |
| Katalog Produk POS | `/pos/products` | Admin & Kasir |
| Riwayat Transaksi | `/pos/transactions/today` | Admin & Kasir |
| Pengembalian Barang | `/pos/returns` | Admin & Kasir |
| Test Printer | `/pos/printer-test` | Admin & Kasir |
| Profil | `/settings/profile` | Semua |
| Keamanan | `/settings/security` | Semua |

---

*Dokumen ini disusun berdasarkan kode sumber dan dokumentasi pengujian (blackbox) aplikasi
Story Vape. Untuk detail skenario uji per modul, lihat folder `docs/testing/`.*
