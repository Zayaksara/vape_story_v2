# Use Case Diagram — Sistem POS Vape Story

Aktor & use case diturunkan **langsung dari route + controller + halaman nyata**
(`routes/web.php`, `routes/settings.php`, `config/fortify.php`, sidebar Admin & POS).

- **Aktor:** Admin (pemilik) dan Kasir.
- **Use case bersama:** Login, Logout, Kelola Profil, Kelola Keamanan (Password & 2FA).
- **Generalisasi:** Admin **mewarisi** semua use case Kasir — Admin juga dapat
  mengakses seluruh fitur POS (route `/pos/*` memakai middleware `IsCashier` yang
  mengizinkan `isCashier() || isAdmin()`). Sebaliknya, Kasir **tidak** bisa mengakses
  fitur Admin (route `/admin/*` admin-only).
- **Catatan registrasi:** registrasi publik **nonaktif** (Fortify tanpa
  `Features::registration()`); akun hanya dibuat Admin lewat *Kelola Akun Pengguna*.

---

## Versi A — PlantUML (UML Use Case ASLI) ✅ direkomendasikan

**Cara pakai:** buka <https://www.plantuml.com/plantuml/uml> → paste kode di bawah →
otomatis jadi gambar → klik PNG/SVG untuk unduh.
(Alternatif: extension "PlantUML" di VS Code.)

```plantuml
@startuml
left to right direction
skinparam packageStyle rectangle
skinparam actorStyle awesome

' ---- Pemampat jarak biar gambar tidak terlalu panjang ----
skinparam nodesep 8
skinparam ranksep 35
skinparam defaultFontSize 12
skinparam usecase {
  Padding 1
  Margin 1
}
skinparam rectangle {
  Padding 2
}

actor "Admin\n(Pemilik)" as Admin
actor "Kasir" as Kasir

rectangle "Sistem POS Vape Story" {

  ' ---- Use case bersama (di tengah, dipakai kedua aktor) ----
  usecase "Login" as UC_Login
  usecase "Logout" as UC_Logout
  usecase "Kelola Profil" as UC_Profil
  usecase "Kelola Keamanan\n(Password & 2FA)" as UC_Keamanan

  ' ====== MODUL ADMIN (back-office, admin-only) ======
  rectangle "Modul Admin (Back-office)" {
    usecase "Lihat Dashboard & Statistik" as UC_Dash
    usecase "Kelola Produk" as UC_Produk
    usecase "Kelola Kategori" as UC_Kategori
    usecase "Kelola Merek (Brand)" as UC_Brand
    usecase "Kelola Stok / Batch" as UC_Batch
    usecase "Kelola Promo & Diskon" as UC_Promo
    usecase "Kelola Akun Pengguna" as UC_Akun
    usecase "Lihat Laporan Penjualan" as UC_Laporan
    usecase "Pantau Transaksi Harian" as UC_Pantau
    usecase "Kelola Pengaturan Toko & Struk" as UC_Toko
    usecase "Lihat Audit & Neraca Keuangan" as UC_Audit
    usecase "Kelola Saldo Awal" as UC_Saldo
  }

  ' ====== MODUL KASIR / POS (kasir + admin) ======
  rectangle "Modul Kasir (POS)" {
    usecase "Lihat Dashboard POS" as UC_DashPos
    usecase "Lihat Katalog Produk" as UC_Katalog
    usecase "Proses Transaksi Penjualan" as UC_Transaksi
    usecase "Cetak Struk" as UC_Struk
    usecase "Lihat Riwayat Transaksi" as UC_Riwayat
    usecase "Proses Pengembalian Barang" as UC_Retur
    usecase "Hubungkan & Tes Printer (ESC-POS)" as UC_Printer

    ' ---- Sub use case (include) ----
    usecase "Potong Stok (FIFO)" as UC_Stok
    usecase "Kembalikan Stok" as UC_Restok
  }
}

' ===== Generalisasi aktor =====
' Admin mewarisi SEMUA use case Kasir (termasuk fitur POS),
' jadi use case bersama & POS cukup ditarik dari Kasir saja.
Admin --|> Kasir

' ===== Relasi Admin (use case KHUSUS admin) =====
Admin --> UC_Dash
Admin --> UC_Produk
Admin --> UC_Kategori
Admin --> UC_Brand
Admin --> UC_Batch
Admin --> UC_Promo
Admin --> UC_Akun
Admin --> UC_Laporan
Admin --> UC_Pantau
Admin --> UC_Toko
Admin --> UC_Audit
Admin --> UC_Saldo

' ===== Relasi Kasir (panah dibalik → Kasir tampil di SISI KANAN) =====
UC_Login     <-- Kasir
UC_Logout    <-- Kasir
UC_Profil    <-- Kasir
UC_Keamanan  <-- Kasir
UC_DashPos   <-- Kasir
UC_Katalog   <-- Kasir
UC_Transaksi <-- Kasir
UC_Riwayat   <-- Kasir
UC_Retur     <-- Kasir
UC_Printer   <-- Kasir

' ===== include / extend =====
UC_Transaksi ..> UC_Stok    : <<include>>
UC_Transaksi ..> UC_Struk   : <<extend>>
UC_Struk     ..> UC_Printer : <<include>>
UC_Retur     ..> UC_Restok  : <<include>>
UC_Audit     ..> UC_Saldo   : <<extend>>

@enduml
```

---

## Versi B — Mermaid (alternatif, BUKAN UML murni)

**Cara pakai:** paste ke <https://mermaid.live>. Ini gaya *flowchart*, dipakai bila
hanya butuh gambaran cepat dan diizinkan dosen.

```mermaid
%%{init: {'flowchart': {'nodeSpacing': 18, 'rankSpacing': 28, 'padding': 4}}}%%
flowchart LR
    Admin([👤 Admin]):::actor
    Kasir([👤 Kasir]):::actor

    subgraph SISTEM["Sistem POS Vape Story"]
        Login(["Login"])
        Logout(["Logout"])
        Profil(["Kelola Profil"])
        Keamanan(["Kelola Keamanan (Password & 2FA)"])

        subgraph ADMIN["Modul Admin (Back-office)"]
            Dash(["Lihat Dashboard & Statistik"])
            Produk(["Kelola Produk"])
            Kategori(["Kelola Kategori"])
            Brand(["Kelola Merek (Brand)"])
            Batch(["Kelola Stok / Batch"])
            Promo(["Kelola Promo & Diskon"])
            Akun(["Kelola Akun Pengguna"])
            Laporan(["Lihat Laporan Penjualan"])
            Pantau(["Pantau Transaksi Harian"])
            Toko(["Kelola Pengaturan Toko & Struk"])
            Audit(["Lihat Audit & Neraca Keuangan"])
            Saldo(["Kelola Saldo Awal"])
        end

        subgraph KASIR["Modul Kasir (POS)"]
            DashPos(["Lihat Dashboard POS"])
            Katalog(["Lihat Katalog Produk"])
            Transaksi(["Proses Transaksi Penjualan"])
            Struk(["Cetak Struk"])
            Riwayat(["Lihat Riwayat Transaksi"])
            Retur(["Proses Pengembalian Barang"])
            Printer(["Hubungkan & Tes Printer (ESC-POS)"])
        end
    end

    Admin -. "mewarisi semua akses Kasir" .-> Kasir

    Admin --- Dash
    Admin --- Produk
    Admin --- Kategori
    Admin --- Brand
    Admin --- Batch
    Admin --- Promo
    Admin --- Akun
    Admin --- Laporan
    Admin --- Pantau
    Admin --- Toko
    Admin --- Audit
    Admin --- Saldo

    Login --- Kasir
    Logout --- Kasir
    Profil --- Kasir
    Keamanan --- Kasir
    DashPos --- Kasir
    Katalog --- Kasir
    Transaksi --- Kasir
    Riwayat --- Kasir
    Retur --- Kasir
    Printer --- Kasir

    Transaksi -. include .-> Struk
    Struk -. include .-> Printer

    classDef actor fill:#dbeafe,stroke:#1e40af,stroke-width:2px;
```

---

## Daftar Use Case (untuk narasi laporan) — dipetakan ke route nyata

| Aktor | Use Case | Sumber (route / halaman) |
|-------|----------|--------------------------|
| Admin & Kasir | Login, Logout | Fortify (`/login`, `/logout`) |
| Admin & Kasir | Kelola Profil | `settings/profile` → `ProfileController` |
| Admin & Kasir | Kelola Keamanan (Password & 2FA) | `settings/security`, `settings/password` → `SecurityController`, Fortify 2FA |
| Admin | Lihat Dashboard & Statistik | `admin/dashboard` → `Admin\DashboardController` |
| Admin | Kelola Produk | `admin/products` (CRUD) → `Admin\ProductController` |
| Admin | Kelola Kategori / Merek / Stok-Batch | `admin/categories`, `admin/brands`, `admin/products/{}/batches` |
| Admin | Kelola Promo & Diskon | `admin/promotions` (CRUD + toggle) → `Admin\PromotionController` |
| Admin | Kelola Akun Pengguna | `admin/users` (CRUD) → `Admin\UserController` |
| Admin | Lihat Laporan Penjualan | `admin/reports/sales` (+ export, pdf, shopping-list) |
| Admin | Pantau Transaksi Harian | `admin/transactions/today` → `Admin\TodayTransactionController` |
| Admin | Kelola Pengaturan Toko & Struk | `settings/store` → `Settings\StoreController` (admin-only) |
| Admin | Lihat Audit & Neraca Keuangan | `admin/__audit`, `__audit/neraca-detail` → `Admin\AuditController` *(tersembunyi, akses via URL)* |
| Admin | Kelola Saldo Awal | `admin/__audit/opening-balance` → `Admin\OpeningBalanceController` *(dari halaman audit)* |
| Kasir (+Admin) | Lihat Dashboard POS | `pos/dashboard` → `POS\DashboardController` |
| Kasir (+Admin) | Lihat Katalog Produk | `pos/products` → `POS\ProductController` |
| Kasir (+Admin) | Proses Transaksi Penjualan | `pos/payment/process` → `POS\ProcessPaymentController` |
| Kasir (+Admin) | Lihat Riwayat Transaksi | `pos/transactions/today` → `POS\TodayTransactionController` |
| Kasir (+Admin) | Proses Pengembalian Barang | `pos/returns` (index/store) → `POS\ReturnController` |
| Kasir (+Admin) | Hubungkan & Tes Printer (ESC-POS) | `pos/printer-test` + `PrinterStatusBadge` (Web Bluetooth) |

**Catatan relasi:**
- *Admin* **«generalization»** *Kasir* — Admin mewarisi seluruh use case Kasir, sehingga Admin juga dapat mengakses semua fitur POS (di samping fitur khusus admin). Kasir tidak mewarisi use case admin.
- *Proses Transaksi Penjualan* **«include»** *Potong Stok (FIFO)* — pemotongan stok (alokasi batch FIFO) selalu terjadi saat transaksi.
- *Proses Transaksi Penjualan* **«extend»** *Cetak Struk* — pencetakan struk bersifat opsional setelah pembayaran.
- *Cetak Struk* **«include»** *Hubungkan & Tes Printer (ESC-POS)* — struk dicetak lewat printer ESC-POS yang dipasangkan via Web Bluetooth.
- *Proses Pengembalian Barang* **«include»** *Kembalikan Stok* — stok selalu dikembalikan saat retur diproses.
- *Lihat Audit & Neraca Keuangan* **«extend»** *Kelola Saldo Awal* — penyetelan saldo awal (neraca pembuka) dilakukan dari halaman audit yang tersembunyi.
