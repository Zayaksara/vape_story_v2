# POS KASIR DASHBOARD — IMPLEMENTATION PROMPT

## STACK
- Vue 3 (Composition API, <script setup>)
- TypeScript (strict mode)
- Tailwind CSS v3
- Inertia.js v1 (Vue 3 adapter)
- Wayfinder (route helper)
- Vite

---

## ATURAN UTAMA — WAJIB DIIKUTI

1. SATU FILE = SATU TANGGUNG JAWAB. Tidak boleh ada logika cart di dalam 
   komponen UI, tidak boleh ada logika UI di dalam composable.

2. SEMUA STATE & LOGIKA ada di `usePos.ts` composable. Komponen hanya 
   menerima props dan melempar emit.

3. JANGAN gunakan any, as unknown, atau @ts-ignore. TypeScript harus strict.

4. JANGAN buat inline style. Semua styling pakai Tailwind class.

5. SETIAP komponen harus bisa berdiri sendiri (standalone) dan bisa 
   dirender tanpa parent. Ini memudahkan debugging manual.

6. SEMUA emit harus diberi nama yang eksplisit dan deskriptif.
   BURUK : emit('update', data)
   BAGUS : emit('add-to-cart', product)

---

## STRUKTUR FOLDER — BUAT PERSIS SEPERTI INI
resources/js/ │ ├── Pages/ │ └── Pos/ │ └── Dashboard.vue │ ├── components/ │ └── pos/ │ ├── PosHeader.vue │ ├── PosSearch.vue │ ├── CategoryNav.vue │ ├── ProductGrid.vue │ ├── ProductCard.vue │ ├── CartPanel.vue │ ├── CartItem.vue │ ├── CartSummary.vue │ ├── PaymentMethods.vue │ ├── PosToast.vue │ ├── modals/ │ │ ├── DiscountModal.vue │ │ ├── PaymentModal.vue │ │ └── ReceiptModal.vue │ └── CartDrawer.vue (untuk tablet) │ ├── composables/ │ └── usePos.ts │ └── types/ └── pos.ts

---

## LANGKAH 1 — TYPES DULU (types/pos.ts)

Buat file ini PERTAMA sebelum apapun:

```ts
export interface Category {
  id: number
  name: string
  slug: string
}

export interface Product {
  id: number
  name: string
  sku: string
  price: number
  stock: number
  category_id: number
  brand_logo?: string
  volume?: string
  image_url?: string
}

export interface CartItem {
  product: Product
  quantity: number
  subtotal: number
}

export interface Discount {
  code: string
  label: string
  type: 'percent' | 'fixed'
  value: number
  max_discount?: number
  min_purchase?: number
  expires_at?: string
}

export type PaymentMethod = 'cash' | 'debit' | 'qris' | 'ewallet'

export interface Transaction {
  id: string
  cashier_id: number
  cashier_name: string
  items: CartItem[]
  discount: Discount | null
  subtotal: number
  discount_amount: number
  tax_amount: number
  total: number
  payment_method: PaymentMethod
  cash_received?: number
  change?: number
  created_at: string
  status: 'success' | 'failed' | 'pending'
}

export interface PosPageProps {
  products: Product[]
  categories: Category[]
  cashier: {
    id: number
    name: string
    email: string
  }
  initial_trx_id: string
}
```
# LANGKAH 2 — COMPOSABLE (composables/usePos.ts)
Buat semua state dan computed di sini. JANGAN import komponen di sini. JANGAN ada template logic di sini.

```ts
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import type {
  Product, CartItem, Discount,
  PaymentMethod, Transaction
} from '@/types/pos'

export function usePos(initialTrxId: string) {

  // ── STATE ──────────────────────────────────────────
  const cart            = ref<CartItem[]>([])
  const discount        = ref<Discount | null>(null)
  const paymentMethod   = ref<PaymentMethod>('cash')
  const cashReceived    = ref<number>(0)
  const transactionId   = ref<string>(initialTrxId)
  const isProcessing    = ref<boolean>(false)
  const lastTransaction = ref<Transaction | null>(null)

  // Modal states — tetap di composable agar Dashboard.vue bisa koordinasi
  const isDiscountModalOpen = ref(false)
  const isPaymentModalOpen  = ref(false)
  const isReceiptModalOpen  = ref(false)
  const isCartDrawerOpen    = ref(false)  // untuk tablet

  // Toast
  const toast = ref<{ message: string; type: 'success' | 'error' } | null>(null)

  // ── COMPUTED ───────────────────────────────────────
  const cartCount = computed(() =>
    cart.value.reduce((sum, i) => sum + i.quantity, 0)
  )
  const subtotal = computed(() =>
    cart.value.reduce((sum, i) => sum + i.subtotal, 0)
  )
  const discountAmount = computed(() => {
    if (!discount.value) return 0
    if (discount.value.type === 'fixed') return discount.value.value
    const raw = (subtotal.value * discount.value.value) / 100
    return discount.value.max_discount
      ? Math.min(raw, discount.value.max_discount)
      : raw
  })
  const taxBase   = computed(() => subtotal.value - discountAmount.value)
  const taxAmount = computed(() => Math.round(taxBase.value * 0.1))
  const total     = computed(() => taxBase.value + taxAmount.value)
  const change    = computed(() =>
    paymentMethod.value === 'cash'
      ? Math.max(0, cashReceived.value - total.value)
      : 0
  )

  // ── CART ACTIONS ───────────────────────────────────
  function addToCart(product: Product): void {
    if (product.stock <= 0) {
      showToast('Stok produk habis', 'error')
      return
    }
    const existing = cart.value.find(i => i.product.id === product.id)
    if (existing) {
      if (existing.quantity >= product.stock) {
        showToast('Stok tidak mencukupi', 'error')
        return
      }
      existing.quantity++
      existing.subtotal = existing.quantity * existing.product.price
    } else {
      cart.value.push({
        product,
        quantity: 1,
        subtotal: product.price,
      })
    }
  }

  function removeFromCart(productId: number): void {
    cart.value = cart.value.filter(i => i.product.id !== productId)
  }

  function updateQuantity(productId: number, qty: number): void {
    const item = cart.value.find(i => i.product.id === productId)
    if (!item) return
    if (qty <= 0) { removeFromCart(productId); return }
    if (qty > item.product.stock) {
      showToast('Stok tidak mencukupi', 'error')
      return
    }
    item.quantity = qty
    item.subtotal = qty * item.product.price
  }

  function clearCart(): void {
    cart.value = []
    discount.value = null
    cashReceived.value = 0
  }

  // ── DISCOUNT ACTIONS ───────────────────────────────
  // validateDiscount: return list voucher dari backend
  // Implementasi oleh backend — untuk sekarang return empty array
  async function validateDiscount(code: string): Promise<Discount[]> {
    // TODO: ganti dengan Wayfinder route saat backend siap
    // const res = await axios.get(route('pos.discount.validate', { code }))
    // return res.data
    return []
  }

  function applyDiscount(d: Discount): void {
    if (d.min_purchase && subtotal.value < d.min_purchase) {
      showToast(
        `Minimum pembelian Rp ${d.min_purchase.toLocaleString('id')}`,
        'error'
      )
      return
    }
    discount.value = d
    showToast(`Diskon "${d.label}" berhasil diterapkan`, 'success')
    isDiscountModalOpen.value = false
  }

  function removeDiscount(): void {
    discount.value = null
    showToast('Diskon dihapus', 'success')
  }

  // ── PAYMENT ────────────────────────────────────────
  // processPayment: kirim ke backend
  // Implementasi oleh backend — untuk sekarang simulasi sukses
  async function processPayment(): Promise<void> {
    if (cart.value.length === 0) {
      showToast('Keranjang kosong', 'error')
      return
    }
    isProcessing.value = true
    try {
      // TODO: ganti dengan Wayfinder route saat backend siap
      // router.post(route('pos.transaction.store'), payload, {
      //   onSuccess: (page) => { ... },
      //   onError: () => { ... },
      // })

      // SIMULASI — hapus saat backend siap
      await new Promise(r => setTimeout(r, 1000))
      lastTransaction.value = {
        id: transactionId.value,
        cashier_id: 0,
        cashier_name: 'Kasir',
        items: [...cart.value],
        discount: discount.value,
        subtotal: subtotal.value,
        discount_amount: discountAmount.value,
        tax_amount: taxAmount.value,
        total: total.value,
        payment_method: paymentMethod.value,
        cash_received: cashReceived.value || undefined,
        change: change.value || undefined,
        created_at: new Date().toISOString(),
        status: 'success',
      }
      isPaymentModalOpen.value = false
      isReceiptModalOpen.value = true

    } catch {
      showToast('Pembayaran gagal. Coba lagi.', 'error')
    } finally {
      isProcessing.value = false
    }
  }

  // ── POST TRANSACTION ───────────────────────────────
  function resetTransaction(): void {
    clearCart()
    lastTransaction.value = null
    isReceiptModalOpen.value = false
    // TODO: minta TRX ID baru dari backend
    // transactionId.value = nextTrxId dari response
  }

  // ── TOAST HELPER ───────────────────────────────────
  function showToast(message: string, type: 'success' | 'error' = 'success'): void {
    toast.value = { message, type }
    setTimeout(() => { toast.value = null }, 3000)
  }

  return {
    // state
    cart, discount, paymentMethod, cashReceived,
    transactionId, isProcessing, lastTransaction, toast,
    // modal states
    isDiscountModalOpen, isPaymentModalOpen,
    isReceiptModalOpen, isCartDrawerOpen,
    // computed
    cartCount, subtotal, discountAmount,
    taxAmount, total, change,
    // actions
    addToCart, removeFromCart, updateQuantity, clearCart,
    validateDiscount, applyDiscount, removeDiscount,
    processPayment, resetTransaction, showToast,
  }
}
```
#  LANGKAH 3 — KOMPONEN (urutan wajib, jangan dibalik)
Buat dalam urutan ini karena ada dependency:
1. PosToast.vue
2. ProductCard.vue
3. CategoryNav.vue
4. ProductGrid.vue       (pakai ProductCard)
5. PosSearch.vue
6. PosHeader.vue
7. CartItem.vue
8. CartSummary.vue
9. PaymentMethods.vue
10. CartPanel.vue        (pakai CartItem + CartSummary + PaymentMethods)
11. CartDrawer.vue       (wrap CartPanel untuk tablet)
12. DiscountModal.vue
13. PaymentModal.vue
14. ReceiptModal.vue
15. Dashboard.vue        (orkestrasi semua komponen + usePos)

# SPESIFIKASI TIAP KOMPONEN
PosToast.vue
Props  : message: string, type: 'success' | 'error'
Emits  : -
Posisi : fixed bottom-4 right-4 z-50
Animasi: transition slide-up + fade
Auto   : parent yang atur show/hide via v-if

## ProductCard.vue
Props  : product: Product
Emits  : 'add-to-cart' (product: Product)
Rules  :
  - Nama produk: line-clamp-2 (tidak terpotong)
  - Harga: font-bold text-primary (teal), lebih besar dari nama
  - Stock badge warna:
      stock > 10  → bg-slate-100 text-slate-500
      stock 5-10  → bg-amber-100 text-amber-600
      stock 1-4   → bg-red-100 text-red-500
      stock 0     → card opacity-50 pointer-events-none
  - Hover: border teal + shadow teal ringan
  - Active/click: scale-95 transition
  - Height: KONSISTEN h-auto min-h dengan padding fixed

## CategoryNav.vue
  Props  : categories: Category[], modelValue: number | null,
         productCounts: Record<number, number>
Emits  : 'update:modelValue' (id: number | null)
Rules  :
  - overflow-x-auto, flex flex-nowrap, scrollbar-hide
  - Tab aktif: bg-primary text-primary-foreground
  - Tab badge count: tampilkan jumlah produk per kategori
  - "Semua" selalu di posisi pertama

## ProductGrid.vue
Props  : products: Product[], loading?: boolean
Emits  : 'add-to-cart' (product: Product)
Rules  :
  - flex-1 overflow-y-auto (SCROLLABLE, bukan body)
  - grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4
  - gap-3 p-4 content-start
  - Empty state: ilustrasi + teks "Produk tidak ditemukan"
  - Loading state: skeleton cards (gunakan animate-pulse)   

## PosHeader.vue
Props  : cashierName: string, transactionId: string,
         currentTime: string, cartCount: number,
         showCartButton: boolean  (true di bawah lg)
Emits  : 'toggle-cart'
Rules  :
  - h-14 flex items-center px-4
  - Kiri: logo/nama toko
  - Kanan: TRX ID badge + jam + cart toggle button (tablet only)
  - Cart button tampilkan cartCount badge

## CartItem.vue
Props  : item: CartItem
Emits  : 'remove' (productId: number),
         'update-qty' (productId: number, qty: number)
Rules  :
  - Nama produk line-clamp-1
  - Qty control: tombol − dan + dengan border
  - Tombol − merah jika qty = 1 (artinya akan remove)
  - Harga per item + subtotal item
  - Tombol delete (🗑) di kanan

## CartSummary.vue
Props  : subtotal: number, discountAmount: number,
         taxAmount: number, total: number,
         discountLabel?: string
Rules  :
  - PURE DISPLAY, tidak ada logika
  - Baris diskon hanya muncul jika discountAmount > 0
  - Total: font-bold text-lg text-primary
  - Semua angka format Rupiah: toLocaleString('id-ID')

## PaymentMethods.vue
Props  : modelValue: PaymentMethod, disabled: boolean
Emits  : 'update:modelValue' (method: PaymentMethod)
Methods: Cash | Debit Card | E-Wallet | QRIS
Rules  :
  - grid grid-cols-4 gap-2
  - Metode aktif: border-primary bg-primary/10 text-primary
  - Icon + label di bawahnya

## CartDrawer.vue
Rules  :
  - Hanya tampil di bawah lg breakpoint (< 1024px)
  - fixed inset-y-0 right-0 z-50 w-[300px]
  - Overlay backdrop: fixed inset-0 z-40 bg-black/50
  - Animasi: translate-x-full → translate-x-0 (transition-transform)
  - Wrap CartPanel di dalamnya
  - Klik backdrop atau swipe kanan = tutup drawer

## DiscountModal.vue
Props  : modelValue: boolean, subtotal: number
Emits  : 'update:modelValue', 'apply' (discount: Discount)
Rules  :
  - Teleport to="body"
  - Backdrop blur: backdrop-blur-sm bg-black/50
  - Modal: w-[360px] rounded-2xl bg-card shadow-2xl
  - Search input + tombol Cari
  - List hasil: nama voucher + deskripsi + expired date + tombol Terapkan
  - Voucher tidak memenuhi min_purchase: tombol disabled + tooltip
  - Voucher sudah diterapkan: badge "✓ Aktif"

## PaymentModal.vue
Props  : modelValue: boolean, total: number,
         isProcessing: boolean
Emits  : 'update:modelValue',
         'confirm' ({ method, cashReceived })
Rules  :
  - Teleport to="body"
  - Total tagihan tampil BESAR di tengah (text-3xl font-bold)
  - Pilih metode bayar (4 tombol)
  - Jika cash: input "Uang Diterima" + tampilkan kembalian auto-hitung
  - Jika QRIS: placeholder area QR code
  - Tombol Konfirmasi: disabled sampai metode dipilih
  - Jika cash & cashReceived < total: Konfirmasi disabled
  - Klik backdrop: TIDAK langsung tutup, tapi tanya konfirmasi
  - Loading state saat isProcessing = true

# LANGKAH 4 — DASHBOARD.VUE (TERAKHIR)
Dashboard hanya boleh berisi:

Import semua komponen pos/
useProps dari Inertia
usePos() composable
Computed filteredProducts (filter berdasar category + search)
Template layout grid
Wire props dan emits antar komponen
TIDAK BOLEH ADA:

Logika cart langsung di Dashboard
State selain yang dari usePos()
Inline style apapun

# CHECKLIST SEBELUM SELESAI
Setelah semua komponen dibuat, verifikasi: [ ] Body tidak bisa di-scroll (overflow hidden) [ ] Hanya ProductGrid dan CartItems yang bisa scroll [ ] Cart summary + payment button SELALU visible tanpa scroll [ ] Product grid 2 kolom di 768px, 3 kolom di 1024px, 4 kolom di 1536px [ ] Cart drawer muncul di bawah 1024px, panel static di atas 1024px [ ] Stock badge 3 warna berjalan benar [ ] addToCart tidak bisa exceed stock [ ] Toast muncul dan auto-dismiss 3 detik [ ] DiscountModal min_purchase validation bekerja [ ] PaymentModal konfirmasi button disabled jika cash < total [ ] ReceiptModal tidak bisa ditutup dengan backdrop [ ] resetTransaction membersihkan semua state [ ] TypeScript tidak ada error (jalankan tsc --noEmit)