import { ref, computed } from 'vue'
import type {
  Product, CartItem, Discount,
  PaymentMethod, Transaction
} from '@/types/pos'

export function usePos(initialTrxId: string) {

  // ── STATE ──────────────────────────────────────────
  const cart = ref<CartItem[]>([])
  const discount = ref<Discount | null>(null)
  const paymentMethod = ref<PaymentMethod>('cash')
  const cashReceived = ref<number>(0)
  const transactionId = ref<string>(initialTrxId)
  const isProcessing = ref<boolean>(false)
  const lastTransaction = ref<Transaction | null>(null)

  // Modal states
  const isDiscountModalOpen = ref<boolean>(false)
  const isPaymentModalOpen = ref<boolean>(false)
  const isReceiptModalOpen = ref<boolean>(false)
  const isCartDrawerOpen = ref<boolean>(false)

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
    if (!discount.value) {
      return 0
    }

    if (discount.value.type === 'fixed') {
      return discount.value.value
    }

    const raw = (subtotal.value * discount.value.value) / 100

    return discount.value.max_discount
      ? Math.min(raw, discount.value.max_discount)
      : raw
  })
  const taxBase = computed(() => subtotal.value - discountAmount.value)
  const taxAmount = computed(() => 0)
  const total = computed(() => taxBase.value + taxAmount.value)
  const change = computed(() =>
    paymentMethod.value === 'cash'
      ? Math.max(0, cashReceived.value - total.value)
      : 0
  )

  // ── CART ACTIONS ───────────────────────────────────
  function addToCart(product: Product): void {
    // Validate product data (accept both number and string IDs for UUID support)
    if (!product || (typeof product.id !== 'number' && typeof product.id !== 'string')) {
      showToast('Produk tidak valid', 'error')
      return
    }

    if (product.stock <= 0) {
      showToast('Stok produk habis', 'error')
      return
    }

    // Ensure price is a valid number
    const validPrice = typeof product.price === 'number' && !isNaN(product.price) ? product.price : 0

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
        subtotal: validPrice,
      })
    }
  }

  function removeFromCart(productId: number | string): void {
    cart.value = cart.value.filter(i => i.product.id !== productId)
  }

  function updateQuantity(productId: number | string, qty: number): void {
    const item = cart.value.find(i => i.product.id === productId)

    if (!item) {
      return
    }

    if (qty <= 0) {
      removeFromCart(productId)
      return
    }

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
  async function validateDiscount(code: string): Promise<Discount[]> {
    // TODO: implement backend validation
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
  async function processPayment(): Promise<void> {
    if (cart.value.length === 0) {
      showToast('Keranjang kosong', 'error')
      return
    }

    isProcessing.value = true

    try {
      const payload = {
        items: cart.value.map((item) => ({
          product_id: item.product.id,
          quantity: item.quantity,
          unit_price: item.product.price,
          discount: 0,
          total: item.subtotal,
        })),
        subtotal_amount: subtotal.value,
        total_amount: total.value,
        paid_amount: paymentMethod.value === 'cash' ? cashReceived.value : total.value,
        discount_amount: discountAmount.value,
        tax_amount: 0,
        payment_method: paymentMethod.value,
      }

      const response = await fetch('/pos/payment/process', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': (
            document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null
          )?.content ?? '',
        },
        body: JSON.stringify(payload),
      })

      if (!response.ok) {
        const errorPayload = await response.json().catch(() => null)
        throw new Error(errorPayload?.message ?? 'Pembayaran gagal diproses oleh server')
      }

      const result = await response.json()
      lastTransaction.value = {
        id: result.sale?.id ?? transactionId.value,
        invoice_number: result.sale?.invoice_number ?? `INV-${String(result.sale?.id ?? transactionId.value).slice(-8).toUpperCase()}`,
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
    // TODO: get new transaction ID from backend
  }

  // ── TOAST HELPER ───────────────────────────────────
  function showToast(message: string, type: 'success' | 'error' = 'success'): void {
    toast.value = { message, type }
    setTimeout(() => {
      toast.value = null
    }, 3000)
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
