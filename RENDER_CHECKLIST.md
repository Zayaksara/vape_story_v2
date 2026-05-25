# Deploy Checklist: Render (5 Menit)

Checklist singkat sebelum klik deploy.

## Sebelum Deploy

- [ ] **GitHub:** Repo `story_vape` sudah ada di GitHub (public atau private)
- [ ] **Git:** `git push origin main` sudah berhasil, kode terbaru di GitHub
- [ ] **render.yaml:** File ini ada di root folder repo
  ```bash
  ls render.yaml  # harus ada
  ```
- [ ] **Composer/npm:** Local development berjalan lancar
  ```bash
  composer install
  npm install && npm run build
  ```

## Deploy (Langkah Demi Langkah)

1. **Login ke Render**
   - Buka https://render.com
   - Click **Sign Up / Log In** dengan GitHub

2. **Authorize GitHub**
   - Render minta akses repo
   - Approve

3. **Deploy Blueprint**
   - Dashboard Render → **New +** → **Blueprint**
   - Pilih repo `story_vape`
   - Render show preview konfigurasi (1 Web + 1 PostgreSQL)
   - Click **Deploy** (jangan ubah apa-apa)

4. **Tunggu Build**
   - Render build ~3-5 menit
   - Monitor di **Deployments** tab
   - Lihat logs jika ada error

5. **Selesai!**
   - URL aplikasi: `https://story-vape.onrender.com`
   - Database: auto-created + migrated
   - Bisa langsung akses & test

## Jika Ada Error

1. Klik **Logs** di Render dashboard
2. Cari red/error lines
3. Common fix: push kode lagi (`git push`) → Render re-deploy

## Next: Testing Aplikasi

- [ ] Buka https://story-vape.onrender.com
- [ ] Test login, dashboard, POS checkout
- [ ] Test inventory, laporan
- [ ] Cek database (inventory, transactions) tersimpan

## Catatan

- **First load:** ~30 detik (server spin up). Normal.
- **Data hilang saat re-deploy?** File uploads iya, database tidak. Database persisten.
- **Mau ubah kode?** Edit di lokal, `git push` → Render auto-redeploy.

---

Done! Dashboard Anda live di Render. 🚀
