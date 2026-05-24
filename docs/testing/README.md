# Dokumentasi Blackbox Testing — Story Vape (Admin)

Dokumen ini berisi skenario pengujian **blackbox** (dari sudut pandang pengguna, tanpa
melihat kode) untuk seluruh alur **admin**, mengikuti urutan:

`Splash → Login → Dashboard → Manajemen Produk → Laporan Penjualan → Kelola Akun → Promo & Diskon → Pengaturan Profil`

Pengujian dilakukan pada aplikasi yang benar-benar dijalankan (browser otomatis), dengan
database yang sudah diisi data demo **90 hari** transaksi via `TransactionDemoSeeder`.

## Lingkungan Uji

| Item | Nilai |
|------|-------|
| URL | `http://127.0.0.1:8000` |
| Database | PostgreSQL (`vapor`) |
| Data demo | 61 produk, 173 batch, **1.118 transaksi** (24 Feb–24 Mei 2026) |
| Tanggal uji | 2026-05-24 |

### Akun Uji

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@vape.com` | `12345` |
| Kasir | `cashier@vape.com` | `cashier123` |
| Kasir | `kasir@vape.com` | ⚠️ password di DB sudah diubah (≠ `12345` dari seeder); pakai `cashier@vape.com` untuk uji POS, atau jalankan ulang `UserSeeder` untuk reset ke `12345` |

### Cara mengisi ulang data demo

```bash
# Hanya transaksi (produk & batch sudah ada)
php artisan db:seed --class=TransactionDemoSeeder

# Atur volume via env (opsional)
TX_SEED_DAYS=90 TX_SEED_MIN_PER_DAY=4 TX_SEED_MAX_PER_DAY=18
```

## Daftar Modul

| No | Modul | File | Status |
|----|-------|------|--------|
| 01 | Splash & Login | [01-splash-login.md](01-splash-login.md) | ✅ Lulus (1 bug minor diperbaiki) |
| 02 | Dashboard Admin | [02-dashboard-admin.md](02-dashboard-admin.md) | ✅ Lulus |
| 03 | Manajemen Produk | [03-manajemen-produk.md](03-manajemen-produk.md) | ✅ Lulus (2 bug diperbaiki) |
| 04 | Laporan Penjualan | [04-laporan-penjualan.md](04-laporan-penjualan.md) | ✅ Lulus |
| 05 | Kelola Akun | [05-kelola-akun.md](05-kelola-akun.md) | ✅ Lulus |
| 06 | Promo & Diskon | [06-promo-diskon.md](06-promo-diskon.md) | ✅ Lulus |
| 07 | Pengaturan Profil | [07-pengaturan-profil.md](07-pengaturan-profil.md) | ✅ Lulus (1 bug diperbaiki) |
| 08 | Dashboard POS & Transaksi | [08-pos-dashboard-transaksi.md](08-pos-dashboard-transaksi.md) | ✅ Lulus (1 bug diperbaiki) |
| 09 | Katalog Produk (POS) | [09-katalog-produk-pos.md](09-katalog-produk-pos.md) | ✅ Lulus (1 bug diperbaiki) |
| 10 | Riwayat Transaksi (POS) | [10-riwayat-transaksi-pos.md](10-riwayat-transaksi-pos.md) | ✅ Lulus (BUG-08 diperbaiki) |
| 11 | Pengembalian Barang | [11-pengembalian-barang.md](11-pengembalian-barang.md) | ✅ Lulus (BUG-09 diperbaiki) |
| 12 | Pengaturan (POS) | [12-pengaturan-pos.md](12-pengaturan-pos.md) | ✅ Lulus |
| 13 | Cross-Account (POS→Admin) | [13-cross-account.md](13-cross-account.md) | ✅ Lulus |

## Ringkasan Bug yang Ditemukan & Diperbaiki

| ID | Modul | Tingkat | Deskripsi | Status |
|----|-------|---------|-----------|--------|
| BUG-01 | Manajemen Produk | 🔴 Mayor (fungsional) | Tombol **nomor halaman** pagination permanen ter-`disabled` — hanya panah Prev/Next yang bisa diklik. Akibat hydration mismatch SSR (`extractPageFromUrl` memakai `window.location.origin`). | ✅ Diperbaiki |
| BUG-02 | Manajemen Produk | 🟡 Minor | Vue warning *"Missing required prop: all_products / units"* karena props `required` di interface bersama tapi tak dikirim controller. | ✅ Diperbaiki |
| BUG-03 | Pengaturan Profil | 🟠 Sedang (UX/i18n) | Seluruh panel profil berbahasa Inggris ("Profile information", "Save", "Delete account", dll). | ✅ Diperbaiki |
| BUG-04 | Global (header) | 🟡 Minor (i18n) | Header menampilkan "Welcome, {nama}" dalam bahasa Inggris. | ✅ Diperbaiki |
| BUG-05 | Login | 🟡 Minor (i18n) | Pesan kredensial salah berbahasa Inggris ("These credentials do not match our records."). | ✅ Diperbaiki |
| BUG-06 | Dashboard POS | 🟠 Sedang | `TypeError: cashInput.value?.focus is not a function` di modal pembayaran (ref menempel ke komponen, bukan elemen native). | ✅ Diperbaiki |
| BUG-07 | Katalog Produk POS | 🔴 Mayor (fungsional) | Klon BUG-01 — tombol nomor halaman pagination disabled permanen di `ProductPos.vue`. | ✅ Diperbaiki |
| BUG-08 | Riwayat/History & Cross-account | 🟠 Sedang | Transaksi yang diretur (`partial_return`/`returned`) **hilang** dari History Pembayaran admin & Riwayat POS, padahal Dashboard tetap menghitungnya → tidak konsisten. | ✅ Diperbaiki (Opsi 1: tampil + nilai bersih + badge "Diretur") |
| BUG-09 | Pengembalian Barang | 🟡 Minor | Saat transaksi habis diretur **bertahap** (beberapa kali retur), status sale tetap `partial_return` (tidak jadi `returned`) karena hanya menghitung qty retur terakhir, bukan akumulasi. | ✅ Diperbaiki (akumulasi returned dari alokasi) |

### Catatan: Isu lintas-halaman (belum diperbaiki — low priority)

- **Hydration mismatch `ConfirmModal` / komponen ber-Teleport**: muncul warning
  *"Hydration node mismatch"* di beberapa halaman (Produk, Laporan). Tidak memengaruhi
  fungsi (modal tetap bekerja), umum terjadi pada komponen Teleport di SSR. Disarankan
  membungkus konten Teleport dengan `<ClientOnly>` bila ingin menghilangkan warning.

## Legenda Status Kasus Uji

- ✅ **Lulus** — hasil sesuai ekspektasi
- ❌ **Gagal** — ada bug (lihat kolom catatan)
- ⚠️ **Lulus dgn catatan** — berfungsi tapi ada anomali minor
