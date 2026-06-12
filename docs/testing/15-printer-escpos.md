# 15 — Printer Thermal Bluetooth (ESC/POS)

Cetak struk langsung ke printer thermal Bluetooth via **Web Bluetooth + ESC/POS**, tanpa
dialog print browser. Tersedia di halaman **Pengaturan Toko** (panel "Printer Bluetooth")
dan halaman uji `/pos/printer-test` (Codeshop CM-T58BL).

## Cakupan
- Deteksi dukungan Web Bluetooth (fallback pesan bila tak didukung)
- Pairing printer (dialog perangkat Bluetooth)
- Auto-reconnect printer tersimpan saat halaman dibuka
- Cetak struk ESC/POS (data dari pratinjau, bukan dialog browser)
- Halaman uji `/pos/printer-test`: cetak teks custom + log koneksi

## Prasyarat
- Chrome/Edge (Android atau desktop), diakses via **HTTPS atau localhost**
- Printer thermal Bluetooth (mis. Codeshop CM-T58BL) menyala & dalam jangkauan

## Kasus Uji

| ID | Skenario | Langkah | Hasil yang Diharapkan | Hasil | Status |
|----|----------|---------|------------------------|-------|--------|
| TC-15.1 | Dukungan browser | Buka panel printer di Chrome | Tidak ada peringatan "tidak mendukung" | Sesuai | ✅ |
| TC-15.2 | Browser tak didukung | Buka di browser tanpa Web Bluetooth | Muncul peringatan merah + saran Chrome/Edge | Sesuai | ✅ |
| TC-15.3 | Pairing printer | Klik "Pair Printer" → pilih perangkat | Status → "Terhubung" (hijau) | Sesuai | ✅ |
| TC-15.4 | Cetak via printer BT | Klik "Cetak via Printer BT" | Struk tercetak sesuai pratinjau (58/80 mm) | Sesuai | ✅ |
| TC-15.5 | Pilihan kertas | Set 58 mm vs 80 mm lalu cetak | Lebar struk cetak mengikuti pilihan | Sesuai | ✅ |
| TC-15.6 | Auto-reconnect | Muat ulang halaman | Printer tersimpan otomatis tersambung (silent) | Sesuai | ✅ |
| TC-15.7 | Reconnect manual | Putus lalu klik "Reconnect" | Tersambung kembali tanpa dialog | Sesuai | ✅ |
| TC-15.8 | Disconnect | Klik "Disconnect" | Status kembali "Belum siap", tombol pair aktif | Sesuai | ✅ |
| TC-15.9 | Printer test page | Buka `/pos/printer-test`, ketik teks, "Cetak Test" | Teks tercetak (double height/width), log "Selesai mencetak" | Sesuai | ✅ |
| TC-15.10 | Cetak tanpa koneksi | "Cetak Test" sebelum connect | Log error "Belum terhubung ke printer" | Sesuai | ✅ |
| TC-15.11 | Printer terputus saat sesi | Matikan printer saat terhubung | Event `gattserverdisconnected` → status "Printer terputus." | Sesuai | ✅ |
