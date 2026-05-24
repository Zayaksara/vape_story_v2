# 09 — Katalog Produk (POS)

Daftar produk read-only untuk kasir di `/pos/products`: melihat stok, harga, status,
pencarian & filter. Tidak ada aksi edit/hapus (hanya lihat detail).

## Cakupan
- Kartu ringkasan (stok tersedia, stok habis, total kategori)
- Pencarian (trigram) & filter kategori/merek
- Tabel produk (kode, nama, brand, kategori, flavor, nikotin, size, harga, stok, status)
- Pagination

## Prasyarat
- Login sebagai kasir

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-09.1 | Muat katalog | Buka `/pos/products` | Tabel produk + kartu ringkasan tampil | Sesuai (61 jenis, 10 habis, 6 kategori) | ✅ |
| TC-09.2 | Read-only | Lihat kolom aksi | Hanya tombol lihat detail (tanpa edit/hapus) | Sesuai (sesuai role kasir) | ✅ |
| TC-09.3 | Pencarian | Ketik kata kunci | Hasil tersaring | Sesuai | ✅ |
| TC-09.4 | Filter kategori/merek | Pilih filter | Data tersaring | Sesuai | ✅ |
| TC-09.5 | **Pagination — nomor** | Klik tombol angka halaman | Pindah halaman | Sebelum perbaikan: tombol angka **disabled permanen** → **BUG-07** | ❌→✅ |
| TC-09.6 | Pagination — panah | Klik Prev/Next | Pindah halaman | Sesuai | ✅ |
| TC-09.7 | Lihat detail | Klik baris/tombol detail | Detail produk tampil | Sesuai | ✅ |

## Bug Ditemukan

### BUG-07 — Pagination nomor halaman tidak bisa diklik (klon BUG-01) (MAYOR)
- **Tingkat:** 🔴 Mayor (fungsional)
- **Gejala:** Sama seperti BUG-01 di admin — tombol nomor halaman selalu `disabled`,
  hanya panah Prev/Next yang berfungsi.
- **Akar masalah:** `extractPageFromUrl()` di `resources/js/pages/POS/ProductPos.vue`
  memakai `new URL(url, window.location.origin)` → gagal di SSR → hydration mismatch.
- **Perbaikan:** parsing `page` via regex bebas-`window` (identik dengan fix BUG-01):
  ```ts
  const match = url.match(/[?&]page=(\d+)/)
  return match ? Number(match[1]) : null
  ```
- **Verifikasi:** Setelah rebuild, tombol nomor halaman aktif.

## Catatan
- Warning hydration mismatch generik dari reka-ui `Tabs` (lihat isu lintas-halaman di
  README) muncul, tidak memengaruhi fungsi.
