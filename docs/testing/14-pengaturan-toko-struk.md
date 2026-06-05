# 14 — Pengaturan Toko & Kustomisasi Struk (Admin)

Halaman `/settings/store` (khusus **admin**) untuk mengatur identitas toko (nama, alamat,
telepon, tagline, logo) dan **mengkustomisasi isi struk** lewat toggle per-bagian, footer
custom, serta pratinjau struk live (58/80 mm).

> ✅ **Diverifikasi via browser** (admin `admin@vape.com`) pada 2026-05-30. Ditemukan &
> **diperbaiki BUG-10** (checkbox tampil kosong saat load karena `receipt_options` tersimpan
> sebagai string `"1"`). Beberapa TC yang butuh file fisik/printer ditandai ⏳.

## Cakupan
- Identitas toko: Nama (wajib), Alamat, Telepon, Tagline, Logo (PNG/JPG ≤ 2 MB)
- Footer struk custom (multi-baris)
- **Konten Struk** — 14 toggle dalam 3 grup (Header Toko / Info Transaksi / Detail Item & Total)
- "Centang semua" / "Hapus semua"
- Pratinjau struk live (58 mm / 80 mm) + tombol "Cetak Pratinjau" (dialog print browser)
- Tagline & alamat tampil di halaman login

## Prasyarat
- Login sebagai **admin**

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Status |
|----|----------|---------|------------------------|--------|
| TC-14.1 | Akses halaman | Buka `/settings/store` sbg admin | Form identitas + konten struk + pratinjau tampil | ✅ |
| TC-14.2 | Nama wajib | Kosongkan "Nama toko", Simpan | Submit diblokir (atribut HTML `required`; server juga validasi) | ✅ |
| TC-14.3 | Simpan identitas | Ubah tagline → Simpan → reload | Tersimpan & persist setelah reload | ✅ (tagline uji persist, lalu dikembalikan) |
| TC-14.4 | Upload logo valid | Pilih PNG/JPG ≤ 2 MB → Simpan | Logo tersimpan, tampil di kotak preview | ⏳ tidak diuji (hindari menimpa logo toko yang ada) |
| TC-14.5 | Logo > 2 MB ditolak | Pilih gambar 12 MB → Simpan | Ditolak | ⚠️ Ditolak via **HTTP 413** (lihat catatan), bukan pesan `max:2048` |
| TC-14.6 | Logo bukan gambar | Upload `.txt` → Simpan | Validasi `image` menolak | ✅ "The logo field must be an image." (pesan masih EN) |
| TC-14.7 | Toggle konten struk | Matikan "Alamat"/"Baris diskon" → preview | Bagian hilang dari pratinjau live | ✅ (alamat & diskon hilang saat di-uncheck) |
| TC-14.8 | Centang/Hapus semua | Klik "Hapus semua" lalu "Centang semua" | Semua toggle off lalu on | ✅ (pratinjau menciut lalu kembali penuh + logo) |
| TC-14.9 | Footer custom | Isi footer multi-baris → preview | Teks footer tampil rata tengah di bawah struk | ✅ (footer "Terimakasih… @mfatihh14" tampil) |
| TC-14.10 | Ganti kertas 58/80 | Klik toggle 58 mm ↔ 80 mm | Pratinjau re-render sesuai lebar kertas | ✅ (re-render tanpa error) |
| TC-14.11 | Cetak pratinjau | Klik "Cetak Pratinjau" | Dialog print browser dgn area struk terisolasi | ⏳ perlu uji |
| TC-14.12 | Key liar ditolak | Kirim `receipt_options` key tak dikenal | `array_intersect_key` dgn `DEFAULT_RECEIPT_OPTIONS` membuangnya | ✅ (review kode) |
| TC-14.13 | Tagline di login | Set tagline → buka `/login` | Tagline tampil sebagai sambutan | ✅ ("Vape Story Sejak 2026") |
| TC-14.14 | Kasir tidak bisa akses | Buka `/settings/store` sbg kasir | **403 Forbidden** | ✅ (lihat [17-negative-otorisasi.md](17-negative-otorisasi.md)) |
| TC-14.15 | State checkbox saat load | Buka halaman, bandingkan checkbox vs pratinjau | Checkbox mencerminkan opsi aktif | ✅ (BUG-10 diperbaiki — semua checkbox tercentang sesuai opsi) |
| TC-14.16 | Round-trip simpan opsi | Uncheck "Logo toko" → Simpan → reload | Tersimpan sbg boolean `false`, checkbox tetap unchecked | ✅ (`"show_logo":false` di DB; tidak ada lagi string `"1"`) |

## Catatan teknis (review kode)
- `StoreController::update` memvalidasi: `name` wajib, `address/phone/tagline/receipt_*`
  nullable, `logo` `image\|max:2048`, `receipt_options` array of boolean.
- Hanya key yang ada di `StoreSetting::DEFAULT_RECEIPT_OPTIONS` yang disimpan (whitelist),
  key liar dibuang — bagus untuk keamanan input.
- Logo lama dihapus dari disk `public` saat upload baru.
- **Fitur NPWP dihapus** (2026-05-30) atas permintaan; tidak ada lagi field/validasi NPWP.

## BUG-10 — Checkbox "Konten Struk" tampil kosong saat load (SEDANG) — ✅ DIPERBAIKI
- **Tingkat:** 🟠 Sedang (UX menyesatkan; data tetap aman)
- **Gejala:** Saat `/settings/store` dibuka, **semua** checkbox "Konten Struk" tampil
  **tidak tercentang**, padahal opsinya aktif (pratinjau tetap menampilkan Nama, Alamat,
  Subtotal, Diskon, dll).
- **Akar masalah (sebenarnya):** `receipt_options` tersimpan sebagai **string `"1"`/`"0"`**,
  bukan boolean. Ini efek `forceFormData: true` saat submit (FormData menyerialkan boolean
  jadi string). Akibatnya:
  - Checkbox `v-model="form.receipt_options[key]"` membandingkan string `"1"` secara *loose*
    ke `true` → tidak cocok → tampil **unchecked**.
  - `ReceiptPreview` menganggap `"1"` *truthy* → tetap menampilkan bagian.
  - SSR me-render `checked` (dari truthiness `"1"`) ≠ klien (unchecked) → **hydration mismatch**.
- **Perbaikan:**
  - `StoreSetting::getReceiptOptionsResolvedAttribute()` — cast tiap nilai ke boolean via
    `filter_var(..., FILTER_VALIDATE_BOOLEAN)` (memperbaiki data lama saat dibaca).
  - `StoreController::update()` — cast `receipt_options` ke boolean sebelum disimpan
    (mencegah string `"1"/"0"` tertulis lagi).
- **Verifikasi (2026-05-30):** Semua checkbox kini tercentang sesuai opsi aktif saat load;
  uncheck "Logo toko" + Simpan + reload → tersimpan sebagai `"show_logo":false` (boolean).
- **Catatan:** Warning *"Hydration completed but contains mismatches"* masih sesekali muncul
  dari **tanggal dinamis** (`new Date()`) di pratinjau contoh (waktu server ≠ klien) — kosmetik,
  tidak terkait BUG-10, satu kategori dgn catatan Teleport di bawah.

## Catatan tambahan dari uji (2026-05-30)
- **Upload logo sangat besar (12 MB) → HTTP 413 "Content Too Large".** File ditolak server
  (limit `upload_max_filesize`/`post_max_size` PHP) **sebelum** mencapai rule Laravel
  `max:2048`, sehingga pengguna mendapat error 413 mentah, bukan pesan ramah "maks 2 MB".
  Saran: tambah penanganan 413 di klien + validasi ukuran sisi-klien sebelum submit, atau
  selaraskan `upload_max_filesize` agar rule `max:2048` yang menanganinya.
- **Pesan validasi logo masih bahasa Inggris** ("The logo field must be an image.") —
  konsistensi i18n minor (bandingkan dgn BUG-03/04/05 yang sudah di-ID-kan).
- TC-14.4 (upload logo valid) sengaja **tidak diuji** agar tidak menimpa/menghapus logo
  toko yang sudah terpasang. Uji manual bila perlu dengan logo cadangan.

## Yang masih perlu diuji manual (admin)
Login admin, lalu jalankan TC-14.1–14.11. Fokus ke sinkronisasi **pratinjau ↔ struk asli**
(lihat memori proyek: setiap perubahan format struk harus konsisten dengan `ReceiptPreview.vue`).
