# ERD — Sistem POS Vape Story

Entity Relationship Diagram dari database aplikasi (Laravel + PostgreSQL).
Sumber: hasil pembacaan langsung seluruh file migrasi & model Eloquent.

## Cara memvisualisasikan
1. Buka <https://mermaid.live>
2. Paste blok kode `erDiagram` di bawah ini.
3. Export ke PNG/SVG untuk dimasukkan ke laporan.
   (Alternatif: draw.io → Arrange → Insert → Advanced → Mermaid.)

> Catatan tipe key: tabel inti master memakai **UUID** sebagai primary key,
> sedangkan tabel transaksi POS (`sales`, `sale_items`, `sale_item_batches`,
> `promotions`) memakai **BIGINT auto-increment**. Ini tergambar di kolom PK.

---

```mermaid
erDiagram
    USERS ||--o{ SALES : "melakukan (user_id)"
    USERS ||--o{ RETURNS : "memproses (cashier_id)"
    USERS ||--o{ RETURNS : "menyetujui (approved_by)"

    CATEGORIES ||--o{ PRODUCTS : "mengelompokkan"
    BRANDS     ||--o{ PRODUCTS : "memiliki"

    PRODUCTS ||--o{ BATCHES    : "memiliki stok"
    PRODUCTS ||--o{ SALE_ITEMS : "dijual sebagai"

    PRODUCTS   }o--o{ PROMOTIONS : "promotion_product"
    PROMOTIONS ||--o{ PROMOTION_PRODUCT : ""
    PRODUCTS   ||--o{ PROMOTION_PRODUCT : ""

    SALES      ||--o{ SALE_ITEMS  : "berisi"
    SALES      ||--o{ RETURNS     : "dikembalikan (sale_id)"

    SALE_ITEMS ||--o{ SALE_ITEM_BATCHES : "dialokasikan (FIFO)"
    BATCHES    ||--o{ SALE_ITEM_BATCHES : "dikonsumsi"
    BATCHES    ||--o{ RETURN_ITEMS      : "dikembalikan ke"
    BATCHES    ||--o{ STOCK_MUTATIONS   : "mencatat mutasi"

    RETURNS ||--o{ RETURN_ITEMS : "berisi"

    USERS {
        uuid      id PK
        string    name
        string    email UK
        string    password
        enum      role "admin | cashier"
        string    two_factor_secret "nullable"
        timestamp two_factor_confirmed_at "nullable"
        string    remember_token
        timestamps created_at_updated_at
    }

    CATEGORIES {
        uuid    id PK
        string  name
        string  slug UK
        text    description "nullable"
        boolean is_active
        timestamps created_at_updated_at
    }

    BRANDS {
        uuid    id PK
        string  name
        string  slug UK
        text    description "nullable"
        string  logo "nullable"
        boolean is_active
        timestamp deleted_at "soft delete"
        timestamps created_at_updated_at
    }

    PRODUCTS {
        uuid    id PK
        string  code UK
        string  image "nullable"
        string  name
        uuid    category_id FK
        uuid    brand_id FK "nullable"
        decimal base_price "nullable"
        decimal nicotine_strength "nullable"
        string  flavor "nullable"
        decimal size_ml "nullable"
        boolean is_active
        integer min_stock
        timestamps created_at_updated_at
    }

    BATCHES {
        uuid    id PK
        uuid    product_id FK
        string  lot_number
        integer stock_quantity
        decimal cost_price "nullable"
        decimal promo_price "nullable"
        integer cukai_year "nullable"
        boolean is_promo
        timestamps created_at_updated_at
    }

    SALES {
        bigint  id PK
        uuid    user_id FK
        decimal total_amount
        decimal paid_amount
        decimal discount_amount
        string  discount_code "nullable"
        string  discount_label "nullable"
        decimal tax_amount
        string  payment_method
        string  status
        timestamps created_at_updated_at
    }

    SALE_ITEMS {
        bigint  id PK
        bigint  sale_id FK
        uuid    product_id FK
        integer quantity
        decimal unit_price
        decimal discount
        decimal promo_discount
        integer promo_units
        decimal total
        timestamps created_at_updated_at
    }

    SALE_ITEM_BATCHES {
        bigint  id PK
        bigint  sale_item_id FK
        uuid    batch_id FK
        integer quantity
        decimal unit_cost "snapshot HPP"
        decimal unit_price "snapshot harga jual"
        boolean is_promo
        integer returned_quantity
        boolean is_synthetic
        timestamps created_at_updated_at
    }

    RETURNS {
        uuid    id PK
        string  return_number UK
        bigint  sale_id FK "nullable"
        uuid    order_id FK "nullable (legacy)"
        uuid    cashier_id FK
        string  refund_method "nullable"
        uuid    approved_by FK "nullable"
        text    reason
        enum    status "pending|approved|rejected|processed"
        timestamp approved_at "nullable"
        text    notes "nullable"
        timestamps created_at_updated_at
    }

    RETURN_ITEMS {
        uuid    id PK
        uuid    return_id FK
        uuid    batch_id FK
        string  product_name "snapshot"
        integer quantity
        decimal unit_price "snapshot"
        decimal subtotal
        timestamps created_at_updated_at
    }

    STOCK_MUTATIONS {
        uuid    id PK
        uuid    batch_id FK
        enum    mutation_type "in|out|adjustment|return"
        integer quantity
        string  reference_type "polymorphic"
        uuid    reference_id "polymorphic"
        text    notes "nullable"
        timestamps created_at_updated_at
    }

    PROMOTIONS {
        bigint  id PK
        string  code UK
        string  name
        text    description "nullable"
        enum    type "percentage|fixed|bogo"
        decimal value
        decimal min_purchase
        decimal max_discount "nullable"
        integer usage_limit "nullable"
        integer used_count
        date    start_date
        date    end_date
        boolean is_active
        enum    target "all|specific"
        timestamps created_at_updated_at
    }

    PROMOTION_PRODUCT {
        bigint  id PK
        bigint  promotion_id FK
        uuid    product_id FK
        timestamps created_at_updated_at
    }

    STORE_SETTINGS {
        bigint  id PK
        string  name
        string  address "nullable"
        string  phone "nullable"
        string  tagline "nullable"
        string  logo_path "nullable"
        text    receipt_header "nullable"
        text    receipt_footer "nullable"
        boolean show_logo_on_receipt
        json    receipt_options "nullable"
        timestamps created_at_updated_at
    }

    OPENING_BALANCES {
        bigint  id PK
        date    as_of_date
        decimal cash
        decimal bank
        decimal inventory_value
        decimal fixed_assets
        decimal accounts_payable
        decimal other_liabilities
        decimal equity
        decimal retained_earnings
        text    notes "nullable"
        timestamps created_at_updated_at
    }
```

---

## Penjelasan relasi utama (untuk narasi laporan)

| Relasi | Kardinalitas | Keterangan |
|--------|--------------|------------|
| Users → Sales | 1 : N | Satu kasir/admin membuat banyak transaksi penjualan. |
| Categories → Products | 1 : N | Satu kategori memuat banyak produk. |
| Brands → Products | 1 : N | Satu merek memiliki banyak produk (opsional/nullable). |
| Products → Batches | 1 : N | Satu produk punya banyak batch stok (per lot/tahun cukai). |
| Sales → Sale_Items | 1 : N | Satu transaksi berisi banyak item. |
| Sale_Items → Sale_Item_Batches | 1 : N | Tiap item dialokasikan ke satu/beberapa batch (FIFO) untuk hitung HPP. |
| Batches → Sale_Item_Batches | 1 : N | Satu batch bisa dipakai di banyak penjualan. |
| Products ↔ Promotions | M : N | Via tabel pivot `promotion_product` (promo bisa berlaku ke banyak produk). |
| Sales → Returns | 1 : N | Satu transaksi dapat memiliki pengembalian (retur). |
| Returns → Return_Items | 1 : N | Satu retur berisi beberapa item yang dikembalikan. |
| Batches → Stock_Mutations | 1 : N | Setiap perubahan stok (masuk/keluar/penyesuaian/retur) tercatat. |

## Tabel pendukung (tidak terhubung relasi transaksi)
- **store_settings** — konfigurasi toko & struk (singleton, 1 baris).
- **opening_balances** — saldo awal pembukuan (referensi laporan keuangan).

## Catatan tabel legacy
Tabel `orders`, `order_items`, `transactions`, `transaction_items` merupakan
sisa desain awal sebelum alur POS berbasis `sales` diterapkan. Kolom
`returns.order_id` dipertahankan (nullable) untuk kompatibilitas data lama,
namun alur aktif menggunakan `returns.sale_id`. **Tidak perlu ditampilkan di ERD
laporan** kecuali ingin mendokumentasikan riwayat migrasi.
```
