# Struktur Database — Sistem POS Vape Story

Dokumen ini menjabarkan struktur tabel (kamus data) beserta **kunci** masing-masing.
Sumber: hasil pembacaan langsung seluruh file migrasi Laravel & model Eloquent.

## Keterangan Kunci (Key)

| Kode | Nama | Arti |
|------|------|------|
| **PK** | *Primary Key* | Kunci utama — pengenal unik tiap baris. |
| **FK** | *Foreign Key* | Kunci tamu — menghubungkan ke PK tabel lain (relasi). |
| **UK** | *Unique Key* | Nilai harus unik (tidak boleh kembar), tapi bukan kunci utama. |

> Catatan tipe PK: tabel **master** memakai **UUID** (`gen_random_uuid()`),
> sedangkan tabel **transaksi** (`sales`, `sale_items`, `sale_item_batches`,
> `promotions`, dll) memakai **BIGINT auto-increment**.

---

## Ringkasan Seluruh Kunci

### Primary Key (PK) tiap tabel

| Tabel | Kolom PK | Tipe |
|-------|----------|------|
| users | id | UUID |
| categories | id | UUID |
| brands | id | UUID |
| products | id | UUID |
| batches | id | UUID |
| sales | id | BIGINT |
| sale_items | id | BIGINT |
| sale_item_batches | id | BIGINT |
| returns | id | UUID |
| return_items | id | UUID |
| stock_mutations | id | UUID |
| promotions | id | BIGINT |
| promotion_product | id | BIGINT |
| store_settings | id | BIGINT |
| opening_balances | id | BIGINT |

### Foreign Key (FK) — relasi antar tabel

| Tabel | Kolom FK | Mengacu ke | Aksi Hapus |
|-------|----------|-----------|------------|
| products | category_id | categories(id) | CASCADE |
| products | brand_id | brands(id) | SET NULL |
| batches | product_id | products(id) | CASCADE |
| sales | user_id | users(id) | CASCADE |
| sale_items | sale_id | sales(id) | CASCADE |
| sale_items | product_id | products(id) | CASCADE |
| sale_item_batches | sale_item_id | sale_items(id) | CASCADE |
| sale_item_batches | batch_id | batches(id) | CASCADE |
| returns | sale_id | sales(id) | CASCADE |
| returns | order_id | orders(id) *(legacy)* | CASCADE |
| returns | cashier_id | users(id) | CASCADE |
| returns | approved_by | users(id) | SET NULL |
| return_items | return_id | returns(id) | CASCADE |
| return_items | batch_id | batches(id) | CASCADE |
| stock_mutations | batch_id | batches(id) | CASCADE |
| promotion_product | promotion_id | promotions(id) | CASCADE |
| promotion_product | product_id | products(id) | CASCADE |

### Unique Key (UK)

| Tabel | Kolom Unik |
|-------|-----------|
| users | email |
| categories | slug |
| brands | slug |
| products | code |
| returns | return_number |
| promotions | code |
| promotion_product | (promotion_id, product_id) *(kombinasi)* |

---

## Struktur Tabel Lengkap (Kamus Data)

### 1. `users` — Pengguna (Admin & Kasir)

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | UUID | **PK** | Pengenal unik pengguna |
| name | VARCHAR | | Nama pengguna |
| email | VARCHAR | **UK** | Email login (unik) |
| password | VARCHAR | | Kata sandi (ter-*hash*) |
| role | ENUM | | `admin` \| `cashier` |
| two_factor_secret | TEXT | | Kunci 2FA (nullable) |
| two_factor_confirmed_at | TIMESTAMP | | Waktu aktivasi 2FA (nullable) |
| remember_token | VARCHAR | | Token "ingat saya" |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 2. `categories` — Kategori Produk

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | UUID | **PK** | Pengenal kategori |
| name | VARCHAR | | Nama kategori |
| slug | VARCHAR | **UK** | Slug URL (unik) |
| description | TEXT | | Deskripsi (nullable) |
| is_active | BOOLEAN | | Status aktif |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 3. `brands` — Merek Produk

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | UUID | **PK** | Pengenal merek |
| name | VARCHAR | | Nama merek |
| slug | VARCHAR | **UK** | Slug URL (unik) |
| description | TEXT | | Deskripsi (nullable) |
| logo | VARCHAR | | Path logo (nullable) |
| is_active | BOOLEAN | | Status aktif |
| deleted_at | TIMESTAMP | | *Soft delete* (nullable) |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 4. `products` — Produk

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | UUID | **PK** | Pengenal produk |
| code | VARCHAR | **UK** | Kode/SKU produk (unik) |
| image | VARCHAR | | Path gambar (nullable) |
| name | VARCHAR | | Nama produk |
| category_id | UUID | **FK** → categories | Kategori produk |
| brand_id | UUID | **FK** → brands | Merek (nullable) |
| base_price | DECIMAL | | Harga dasar (nullable) |
| nicotine_strength | DECIMAL | | Kadar nikotin mg/ml (nullable) |
| flavor | VARCHAR | | Rasa (nullable) |
| size_ml | DECIMAL | | Volume ml (nullable) |
| is_active | BOOLEAN | | Status aktif |
| min_stock | INTEGER | | Batas stok minimum |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 5. `batches` — Batch/Lot Stok

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | UUID | **PK** | Pengenal batch |
| product_id | UUID | **FK** → products | Produk pemilik batch |
| lot_number | VARCHAR | | Nomor lot/batch |
| stock_quantity | INTEGER | | Jumlah stok saat ini |
| cost_price | DECIMAL | | Harga modal/HPP (nullable) |
| promo_price | DECIMAL | | Harga promo cukai lama (nullable) |
| cukai_year | INTEGER | | Tahun cukai (nullable) |
| is_promo | BOOLEAN | | Penanda batch promo |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 6. `sales` — Transaksi Penjualan

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | BIGINT | **PK** | Pengenal transaksi |
| user_id | UUID | **FK** → users | Kasir/admin pembuat transaksi |
| total_amount | DECIMAL | | Total nilai transaksi |
| paid_amount | DECIMAL | | Jumlah dibayar |
| discount_amount | DECIMAL | | Nilai diskon |
| discount_code | VARCHAR | | Kode voucher (nullable) |
| discount_label | VARCHAR | | Label diskon (nullable) |
| tax_amount | DECIMAL | | Nilai pajak |
| payment_method | VARCHAR | | Metode bayar |
| status | VARCHAR | | Status transaksi |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 7. `sale_items` — Item Transaksi

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | BIGINT | **PK** | Pengenal item |
| sale_id | BIGINT | **FK** → sales | Transaksi induk |
| product_id | UUID | **FK** → products | Produk yang dijual |
| quantity | INTEGER | | Jumlah |
| unit_price | DECIMAL | | Harga satuan |
| discount | DECIMAL | | Diskon item |
| promo_discount | DECIMAL | | Diskon promo |
| promo_units | INTEGER | | Jumlah unit kena promo |
| total | DECIMAL | | Subtotal item |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 8. `sale_item_batches` — Alokasi Item ke Batch (FIFO)

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | BIGINT | **PK** | Pengenal alokasi |
| sale_item_id | BIGINT | **FK** → sale_items | Item transaksi |
| batch_id | UUID | **FK** → batches | Batch yang dikonsumsi |
| quantity | INTEGER | | Qty dari batch ini |
| unit_cost | DECIMAL | | Snapshot HPP per unit |
| unit_price | DECIMAL | | Snapshot harga jual per unit |
| is_promo | BOOLEAN | | Penanda alokasi promo |
| returned_quantity | INTEGER | | Qty sudah diretur |
| is_synthetic | BOOLEAN | | Alokasi hasil backfill (data lama) |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 9. `returns` — Pengembalian Barang

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | UUID | **PK** | Pengenal retur |
| return_number | VARCHAR | **UK** | Nomor retur (unik) |
| sale_id | BIGINT | **FK** → sales | Transaksi yang diretur (nullable) |
| order_id | UUID | **FK** → orders | Order lama (nullable, *legacy*) |
| cashier_id | UUID | **FK** → users | Kasir yang memproses |
| refund_method | VARCHAR | | Metode refund (nullable) |
| approved_by | UUID | **FK** → users | Admin penyetuju (nullable) |
| reason | TEXT | | Alasan retur |
| status | ENUM | | `pending`\|`approved`\|`rejected`\|`processed` |
| approved_at | TIMESTAMP | | Waktu disetujui (nullable) |
| notes | TEXT | | Catatan (nullable) |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 10. `return_items` — Item Pengembalian

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | UUID | **PK** | Pengenal item retur |
| return_id | UUID | **FK** → returns | Retur induk |
| batch_id | UUID | **FK** → batches | Batch tujuan pengembalian stok |
| product_name | VARCHAR | | Snapshot nama produk |
| quantity | INTEGER | | Jumlah dikembalikan |
| unit_price | DECIMAL | | Snapshot harga saat retur |
| subtotal | DECIMAL | | Subtotal retur |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 11. `stock_mutations` — Riwayat Mutasi Stok

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | UUID | **PK** | Pengenal mutasi |
| batch_id | UUID | **FK** → batches | Batch yang berubah stok |
| mutation_type | ENUM | | `in`\|`out`\|`adjustment`\|`return` |
| quantity | INTEGER | | Jumlah perubahan |
| reference_type | VARCHAR | | Tipe sumber (polymorphic) |
| reference_id | UUID | | ID sumber (polymorphic) |
| notes | TEXT | | Catatan (nullable) |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 12. `promotions` — Promo & Diskon

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | BIGINT | **PK** | Pengenal promo |
| code | VARCHAR | **UK** | Kode voucher (unik) |
| name | VARCHAR | | Nama promo |
| description | TEXT | | Deskripsi (nullable) |
| type | ENUM | | `percentage`\|`fixed`\|`bogo` |
| value | DECIMAL | | Nilai diskon |
| min_purchase | DECIMAL | | Minimal pembelian |
| max_discount | DECIMAL | | Maksimal diskon (nullable) |
| usage_limit | INTEGER | | Batas pemakaian (nullable) |
| used_count | INTEGER | | Jumlah terpakai |
| start_date | DATE | | Tanggal mulai |
| end_date | DATE | | Tanggal berakhir |
| is_active | BOOLEAN | | Status aktif |
| target | ENUM | | `all`\|`specific` |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 13. `promotion_product` — Pivot Promo ↔ Produk (M:N)

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | BIGINT | **PK** | Pengenal baris |
| promotion_id | BIGINT | **FK** → promotions | Promo |
| product_id | UUID | **FK** → products | Produk |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |
| *(promotion_id, product_id)* | | **UK** | Kombinasi unik (cegah duplikat) |

### 14. `store_settings` — Pengaturan Toko & Struk

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | BIGINT | **PK** | Pengenal (singleton) |
| name | VARCHAR | | Nama toko |
| address | VARCHAR | | Alamat (nullable) |
| phone | VARCHAR | | Telepon (nullable) |
| tagline | VARCHAR | | Tagline (nullable) |
| logo_path | VARCHAR | | Path logo (nullable) |
| receipt_header | TEXT | | Header struk (nullable) |
| receipt_footer | TEXT | | Footer struk (nullable) |
| show_logo_on_receipt | BOOLEAN | | Tampilkan logo di struk |
| receipt_options | JSON | | Opsi konten struk (nullable) |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

### 15. `opening_balances` — Saldo Awal Pembukuan

| Kolom | Tipe | Key | Keterangan |
|-------|------|-----|------------|
| id | BIGINT | **PK** | Pengenal saldo awal |
| as_of_date | DATE | | Tanggal cutoff |
| cash | DECIMAL | | Kas tunai |
| bank | DECIMAL | | Saldo bank/e-wallet/QRIS |
| inventory_value | DECIMAL | | Nilai persediaan |
| fixed_assets | DECIMAL | | Aset tetap |
| accounts_payable | DECIMAL | | Hutang usaha |
| other_liabilities | DECIMAL | | Hutang lain |
| equity | DECIMAL | | Modal awal |
| retained_earnings | DECIMAL | | Laba ditahan |
| notes | TEXT | | Catatan (nullable) |
| created_at / updated_at | TIMESTAMP | | Waktu dibuat/diubah |

---

## Catatan
- Tabel pendukung sistem Laravel (`password_reset_tokens`, `sessions`, `cache`,
  `jobs`) tidak dicantumkan karena bukan bagian dari logika bisnis.
- Tabel `orders`, `order_items`, `transactions`, `transaction_items` merupakan
  *legacy* (desain awal sebelum alur `sales`) dan tidak digunakan pada alur aktif.
