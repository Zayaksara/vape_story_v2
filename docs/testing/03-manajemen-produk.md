# 03 — Manajemen Produk

Halaman CRUD produk di `/admin/products`: daftar, filter, pencarian, pagination,
detail, tambah/edit/hapus, dan badge cukai.

## Cakupan
- Kartu ringkasan (total produk, stok habis, total kategori)
- Pencarian (trigram, typo-tolerant di PostgreSQL)
- Filter kategori & merek
- Sorting kolom
- Pagination
- Badge status & "CUKAI LAMA"
- Aksi: tambah, edit, hapus produk

## Prasyarat
- Login sebagai admin
- Minimal 61 produk ter-seed

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-03.1 | Muat daftar produk | Buka `/admin/products` | Tabel produk + kartu ringkasan tampil | Sesuai (61 produk, 10 habis, 6 kategori) | ✅ |
| TC-03.2 | Kolom tabel lengkap | Lihat header | Image, Code, Name, Brand, Category, Flavor, Nicotine, Size, Cukai, Selling Price, Stock, Status, Aksi | Sesuai | ✅ |
| TC-03.3 | Badge cukai lama | Lihat produk dgn batch cukai lama | Badge "CUKAI LAMA" + daftar tahun cukai (mis. 2026 2025) | Sesuai | ✅ |
| TC-03.4 | Status stok habis | Produk stok 0 | Badge "Habis", cukai "—" | Sesuai | ✅ |
| TC-03.5 | Pencarian produk | Ketik kata kunci di "Cari produk…" | Hasil tersaring + ranking relevansi | Sesuai | ✅ |
| TC-03.6 | Pencarian typo-tolerant | Ketik "manggo" | Produk "Mango" tetap muncul (trigram) | Sesuai (by design) | ✅ |
| TC-03.7 | Filter kategori | Pilih kategori | Hanya produk kategori tsb tampil | Sesuai | ✅ |
| TC-03.8 | Filter merek | Pilih merek | Hanya produk merek tsb tampil | Sesuai | ✅ |
| TC-03.9 | Sorting kolom | Klik header (mis. Selling Price) | Data terurut asc/desc lintas halaman | Sesuai | ✅ |
| TC-03.10 | **Pagination — panah** | Klik panah Next/Prev | Pindah halaman (`?page=N`) | Sesuai | ✅ |
| TC-03.11 | **Pagination — nomor** | Klik tombol angka (2/3/…) | Pindah ke halaman terkait | Sebelum perbaikan: tombol angka **disabled permanen** → **BUG-01** | ❌→✅ |
| TC-03.12 | Info jumlah | Lihat footer tabel | "Menampilkan 15 dari 61 produk" | Sesuai | ✅ |
| TC-03.13 | Buka detail produk | Klik baris produk | Sheet detail terbuka | Sesuai | ✅ |
| TC-03.14 | Tombol Tambah Produk | Klik "Tambah Produk" | Navigasi ke form create | Sesuai | ✅ |
| TC-03.15 | Hapus produk (konfirmasi) | Klik ikon hapus | Muncul `ConfirmModal` "Hapus Produk?" sebelum hapus | Sesuai | ✅ |
| TC-03.16 | Tanpa warning props | Buka halaman, cek console | Tidak ada "Missing required prop" | Sebelum perbaikan: ada warning → **BUG-02** | ❌→✅ |

## Bug Ditemukan

### BUG-01 — Tombol nomor halaman pagination tidak bisa diklik (MAYOR)
- **Tingkat:** 🔴 Mayor (fungsional)
- **Gejala:** Tombol angka halaman (1,2,3,…) selalu `disabled`; hanya panah Prev/Next yang
  berfungsi. Terjadi di semua halaman (termasuk saat URL sudah `?page=2`).
- **Akar masalah:** Fungsi `extractPageFromUrl()` memakai `new URL(url, window.location.origin)`.
  Saat **SSR**, `window` tidak ada → exception → `catch` mengembalikan `null` →
  atribut `disabled` ter-render di server. Build **production** Vue **tidak merektifikasi**
  hydration mismatch (sesuai catatan Vue), sehingga `disabled` dari server menetap di client.
- **Perbaikan** (`resources/js/pages/admin/ManajemenProduct.vue`): mengganti parsing menjadi
  bebas-`window` & deterministik di SSR/client:
  ```ts
  function extractPageFromUrl(url: string | null): number | null {
      if (!url) return null
      const match = url.match(/[?&]page=(\d+)/)
      return match ? Number(match[1]) : null
  }
  ```
- **Verifikasi:** Setelah `npm run build`, tombol angka aktif; klik "3" menavigasi ke `?page=3`.

### BUG-02 — Vue warning "Missing required prop: all_products / units" (MINOR)
- **Tingkat:** 🟡 Minor
- **Gejala:** Console memunculkan *Missing required prop: "all_products"* dan *"units"* di
  `ManajemenProduct`.
- **Akar masalah:** Interface bersama `ProductPageProps` (`resources/js/types/pos.ts`)
  mendeklarasikan `all_products` & `units` sebagai **wajib**, padahal controller admin tidak
  mengirimnya dan komponen tidak memakainya. (Halaman POS pun tidak memakainya.)
- **Perbaikan:** Menjadikan kedua properti **opsional** di `ProductPageProps`:
  `all_products?: Product[]` dan `units?: string[]`.
- **Verifikasi:** Warning hilang dari console setelah rebuild.
