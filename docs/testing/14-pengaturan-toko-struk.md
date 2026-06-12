# 14 — Pengaturan Toko & Kustomisasi Struk (Admin)

Halaman `/settings/store` (khusus **admin**) untuk mengatur identitas toko (nama, alamat,
telepon, tagline, logo) dan **mengkustomisasi isi struk** lewat toggle per-bagian, footer
custom, serta pratinjau struk live (58/80 mm).

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
| TC-14.11 | Cetak pratinjau | Klik "Cetak Pratinjau" | Dialog print browser dgn area struk terisolasi | ✅ |
| TC-14.12 | Key liar ditolak | Kirim `receipt_options` key tak dikenal | `array_intersect_key` dgn `DEFAULT_RECEIPT_OPTIONS` membuangnya | ✅ (review kode) |
| TC-14.13 | Tagline di login | Set tagline → buka `/login` | Tagline tampil sebagai sambutan | ✅ ("Vape Story Sejak 2026") |
| TC-14.14 | Kasir tidak bisa akses | Buka `/settings/store` sbg kasir | **403 Forbidden** | ✅ (lihat [17-negative-otorisasi.md](17-negative-otorisasi.md)) |
| TC-14.15 | State checkbox saat load | Buka halaman, bandingkan checkbox vs pratinjau | Checkbox mencerminkan opsi aktif | ✅ (BUG-10 diperbaiki — semua checkbox tercentang sesuai opsi) |
| TC-14.16 | Round-trip simpan opsi | Uncheck "Logo toko" → Simpan → reload | Tersimpan sbg boolean `false`, checkbox tetap unchecked | ✅ (`"show_logo":false` di DB; tidak ada lagi string `"1"`) |
