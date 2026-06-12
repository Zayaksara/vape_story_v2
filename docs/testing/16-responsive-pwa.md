# 16 — Responsiveness (Tablet) & PWA

Pengujian tata letak responsif (fokus **tablet**, sasaran utama perangkat POS) dan perilaku
**PWA** (installable, start_url ke POS). Mencakup perbaikan dari commit 2026-05-25
(responsive tablet login/splash/POS, PWA start_url `/pos/dashboard`, ReceiptModal scrollable).

## Lingkungan Uji
- Browser otomatis (Playwright), viewport tablet **768×1024** dan desktop **1280×800**
- Login sebagai kasir (`cashier@vape.com` / `cashier123`)

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-16.1 | POS dashboard di tablet | Buka `/pos/dashboard` @768×1024 | Header + grid produk + panel Keranjang tampil rapi, tombol "Toggle sidebar" muncul | Sesuai (grid produk & panel keranjang adaptif, tombol toggle sidebar tampil) | ✅ |
| TC-16.2 | Keranjang di tablet | Lihat panel keranjang @768 | Panel jadi `complementary` dgn tombol "Buka keranjang"; ringkasan Subtotal/Total terlihat | Sesuai | ✅ |
| TC-16.3 | Login responsif tablet | Buka `/login` @768 | Carousel + form login tertata, alamat & tagline tampil | Sesuai | ✅ |
| TC-16.4 | Kembali ke desktop | Resize ke 1280×800 | Layout kembali normal tanpa elemen rusak | Sesuai | ✅ |
| TC-16.5 | Splash responsif | Buka aplikasi (splash) di tablet | Splash tertata (perbaikan 2026-05-25) | Sesuai | ✅ |
| TC-16.6 | ReceiptModal scrollable | Selesaikan transaksi @tablet → struk panjang | Modal struk bisa di-scroll, tombol aksi tetap terjangkau | Sesuai | ✅ |
| TC-16.7 | PWA installable | Buka di Chrome, cek menu Install | Aplikasi dapat di-install (manifest valid) | Sesuai | ✅ |
| TC-16.8 | PWA start_url | Jalankan PWA terinstall | Membuka langsung ke `/pos/dashboard` (bukan `/`) | Sesuai | ✅ |
