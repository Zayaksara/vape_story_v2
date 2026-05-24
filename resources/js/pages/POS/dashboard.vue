<!-- ================================================
     POS DASHBOARD - REDESIGNED WITH UI/UX PRO MAX
     Consistent design system, no redundancy, proper hierarchy
   =============================================== -->
<template>
  <!-- Error state modal -->
  <div
    v-if="error"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
  >
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
      <div class="text-center">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
          <svg class="h-6 w-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
        <h3 class="mt-4 text-lg font-semibold text-slate-800">Error</h3>
        <p class="mt-2 text-sm text-gray-500">{{ error }}</p>
        <div class="mt-6 flex gap-3">
          <button
            @click="error = null"
            class="flex-1 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-gray-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500"
          >
            Close
          </button>
          <button
            @click="error = null; searchQuery = ''; currentCategory = null;"
            class="flex-1 rounded-lg bg-teal-500 px-4 py-2 text-sm font-medium text-white hover:bg-teal-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500"
          >
            Reset Filter
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Main content -->
  <main class="flex flex-1 min-h-0 overflow-hidden">
    <!-- Left: Products -->
    <div class="flex-1 min-h-0 flex flex-col bg-(--pos-bg-primary) overflow-hidden">
      <!-- Search & Categories Section -->
      <div class="border-b border-gray-300 bg-(--pos-bg-primary) px-4 py-3.5">
        <!-- Search bar -->
        <div class="mb-3">
          <PosSearch
            v-model="searchQuery"
            @search="handleSearch"
          />
        </div>

        <!-- Category navigation - single tablist, horizontal scroll -->
        <nav
          class="overflow-x-auto pb-1 scrollbar-hide no-scrollbar"
          role="tablist"
          aria-label="Kategori produk"
        >
          <div class="inline-flex min-w-max gap-2">
            <button
              v-for="category in allCategories"
              :key="category.id"
              role="tab"
              :aria-selected="currentCategory === category.id"
              :aria-controls="`category-panel-${category.id}`"
              class="category-pill whitespace-nowrap rounded-full border px-4 py-2 text-sm font-medium transition-all duration-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 min-h-44px flex items-center justify-center"
              :style="{
                backgroundColor: currentCategory === category.id ? 'var(--pos-brand-primary)' : '#ffffff',
                color: currentCategory === category.id ? '#ffffff' : 'var(--pos-text-secondary)',
                borderColor: currentCategory === category.id ? 'var(--pos-brand-primary)' : 'var(--pos-border)',
                boxShadow: currentCategory === category.id ? '0 2px 8px rgba(20, 184, 166, 0.25)' : 'none'
              }"
              @click="currentCategory = category.id"
            >
              <span>{{ category.name }}</span>
              <span
                class="ml-2 rounded-full px-2 py-0.5 text-xs font-bold tabular-nums"
                :style="{
                  backgroundColor: currentCategory === category.id ? 'rgba(255,255,255,0.25)' : '#e5e7eb',
                  color: currentCategory === category.id ? '#ffffff' : 'var(--pos-text-muted)'
                }"
              >
                {{ category.id ? (productCounts[category.id] || 0) : props.products.length }}
              </span>
            </button>
          </div>
        </nav>
      </div>

      <!-- Product Grid - SCROLLABLE -->
      <div class="overflow-y-auto p-4.5 product-grid scrollable flex-1">
        <ProductGrid
          :products="filteredProducts"
          :loading="false"
          :search-query="searchQuery"

          @add-to-cart="addToCart"
        />
      </div>
    </div>

    <!-- Right:Panel (desktop) -->
    <aside class="hidden w-80 border border-(--pos-border) bg-(--pos-bg-primary) lg:flex flex-col min-h-0">
      <div class="flex-1 overflow-hidden pb-4">
        <CartPanel
          :cart="cart"
          :subtotal="subtotal"
          :discount-amount="discountAmount"
          :tax-amount="taxAmount"
          :total="total"
          :is-processing="isProcessing || isLoading"
          :cart-count="cartCount"
          :show-cart-button="true"
          :discount-label="discount?.label"
          @remove-item="removeFromCart"
          @update-quantity="updateQuantity"
          @clear-cart="clearCart"
          @process-payment="isPaymentModalOpen = true"
          @toggle-cart="isCartDrawerOpen = !isCartDrawerOpen"
          @open-discount="isDiscountModalOpen = true"
          @remove-discount="removeDiscount"
        />
      </div>
    </aside>
  </main>

  <!-- Cart drawer (mobile/tablet) -->
  <CartDrawer
    :model-value="isCartDrawerOpen"
    :cart="cart"
    :subtotal="subtotal"
    :discount-amount="discountAmount"
    :tax-amount="taxAmount"
    :total="total"
    :is-processing="isProcessing || isLoading"
    :cart-count="cartCount"
    :show-cart-button="true"
    :discount-label="discount?.label"
    @update:model-value="isCartDrawerOpen = $event"
    @remove-item="removeFromCart"
    @update-quantity="updateQuantity"
    @clear-cart="clearCart"
    @process-payment="isPaymentModalOpen = true"
    @open-discount="isDiscountModalOpen = true"
    @remove-discount="removeDiscount"
  />

  <!-- Modals (client-only — Teleport tidak SSR-friendly) -->
  <template v-if="isMounted">
    <DiscountModal
      :model-value="isDiscountModalOpen"
      :subtotal="subtotal"
      :applied-discount="discount"
      :discounts="props.promos ?? []"
      @update:model-value="isDiscountModalOpen = $event"
      @apply="applyDiscount"
    />

    <PaymentModal
      :model-value="isPaymentModalOpen"
      :total="total"
      :is-processing="isProcessing || isLoading"
      @update:model-value="isPaymentModalOpen = $event"
      @confirm="handlePaymentConfirm"
    />

    <ReceiptModal
      :model-value="isReceiptModalOpen"
      :transaction="lastTransaction"
      @update:model-value="handleCloseReceipt"
    />
  </template>

  <!-- Toast -->
  <PosToast
    v-if="toast"
    :model-value="toast.message"
    :type="toast.type"
    @update:model-value="toast = null"
  />
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'

// Import components
import CartDrawer from '@/components/pos/CartDrawer.vue'
import CartPanel from '@/components/pos/CartPanel.vue'
import DiscountModal from '@/components/pos/modals/DiscountModal.vue'
import PaymentModal from '@/components/pos/modals/PaymentModal.vue'
import ReceiptModal from '@/components/pos/modals/ReceiptModal.vue'
import PosSearch from '@/components/pos/PosSearch.vue'
import PosToast from '@/components/pos/PosToast.vue'
import ProductGrid from '@/components/pos/ProductGrid.vue'
import { usePos } from '@/composables/usePos'
import type { Product, Category, Discount } from '@/types/pos'

const props = defineProps<{
  products: Product[]
  categories: Category[]
  cashier: {
    id: string  // UUID
    name: string
    email: string
  }
  initial_trx_id: string
  promos?: Discount[]
}>()

// Use the main sidebar's toggle (already available via SidebarProvider)
const {
  cart,
  discount,
  paymentMethod,
  cashReceived,
  isProcessing,
  lastTransaction,
  toast,
  isDiscountModalOpen,
  isPaymentModalOpen,
  isReceiptModalOpen,
  isCartDrawerOpen,
  cartCount,
  subtotal,
  discountAmount,
  taxAmount,
  total,
  change,
  addToCart,
  removeFromCart,
  updateQuantity,
  clearCart,
  validateDiscount,
  applyDiscount,
  removeDiscount,
  processPayment,
  resetTransaction,
  showToast,
} = usePos(props.initial_trx_id)

// Search state
const searchQuery = ref('')
const currentCategory = ref<number | string | null>(null)

// Loading and error states
const isLoading = ref(false)
const error = ref<string | null>(null)

// Client-only flag (cegah SSR hydration mismatch untuk konten time/Teleport)
const isMounted = ref(false)

// Time
const currentTime = ref('')

function updateTime() {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

// ── Semantic-ish fuzzy search (client-side, trigram-style) ──────────────────
// Bigram set similarity (Sørensen-Dice) — toleran terhadap typo & partial
// match. Skor 0..1. Cocok untuk daftar produk yg sudah ada di memory.
function bigrams(s: string): string[] {
  const t = ` ${s.toLowerCase()} `.replace(/\s+/g, ' ')
  const out: string[] = []
  for (let i = 0; i < t.length - 1; i++) out.push(t.slice(i, i + 2))
  return out
}
function diceSimilarity(a: string, b: string): number {
  if (!a || !b) return 0
  if (a === b) return 1
  const aGrams = bigrams(a)
  const bGrams = bigrams(b)
  if (aGrams.length === 0 || bGrams.length === 0) return 0
  const bMap = new Map<string, number>()
  for (const g of bGrams) bMap.set(g, (bMap.get(g) ?? 0) + 1)
  let intersection = 0
  for (const g of aGrams) {
    const c = bMap.get(g) ?? 0
    if (c > 0) {
      intersection++
      bMap.set(g, c - 1)
    }
  }
  return (2 * intersection) / (aGrams.length + bGrams.length)
}

/** Skor produk vs query: ambil similarity tertinggi dari field penting,
 *  plus bonus kalau ada substring match (ILIKE-like). */
function productScore(p: Product, q: string): number {
  if (!q) return 0
  const ql = q.toLowerCase()
  const fields = [p.name, p.sku, (p as any).flavor ?? '', p.brand?.name ?? '', p.category?.name ?? '']
  let best = 0
  for (const f of fields) {
    if (!f) continue
    const fl = String(f).toLowerCase()
    const sim = diceSimilarity(fl, ql)
    const substringBonus = fl.includes(ql) ? 0.35 : 0
    const score = Math.max(sim, substringBonus + sim * 0.5)
    if (score > best) best = score
  }
  return best
}

// Filtered + ranked products
const filteredProducts = computed(() => {
  const q = searchQuery.value.trim()
  const threshold = 0.22 // 0..1; sesuaikan untuk lebih/kurang permisif

  // Tanpa search: filter kategori saja, urutan original.
  if (!q) {
    return props.products.filter((p) =>
      !currentCategory.value || p.category_id === currentCategory.value
    )
  }

  // Dengan search: filter kategori → skor tiap produk → buang yg di bawah threshold → urutkan desc.
  return props.products
    .filter((p) => !currentCategory.value || p.category_id === currentCategory.value)
    .map((p) => ({ p, score: productScore(p, q) }))
    .filter(({ score }) => score >= threshold)
    .sort((a, b) => b.score - a.score)
    .map(({ p }) => p)
})

// Category product counts
const productCounts = computed(() => {
  const counts: Record<number | string, number> = {}
  props.categories.forEach((cat) => {
    counts[cat.id] = props.products.filter(p => p.category_id === cat.id).length
  })
  return counts
})

// All categories including "Semua" (All)
const allCategories = computed(() => [
  { id: null as any, name: 'Semua' },
  ...props.categories,
])

function handleSearch(value: string) {
  searchQuery.value = value
}

async function handlePaymentConfirm({ method, cashReceived: received }: { method: any; cashReceived?: number }) {
  try {
    isLoading.value = true
    error.value = null
    cashReceived.value = received || 0
    paymentMethod.value = method
    await processPayment()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Payment failed'
    
  } finally {
    isLoading.value = false
  }
}

function handleCloseReceipt() {
  resetTransaction()
  router.reload({ only: ['products'] })
}

onMounted(() => {
  isMounted.value = true
  updateTime()
  const interval = setInterval(updateTime, 60000)
  onUnmounted(() => {
    clearInterval(interval)
  })
})
</script>

<style scoped>
/* Custom scrollbar for category tabs */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

/* Ensure proper background layers */
.bg-light-gray {
  background-color: var(--pos-bg-secondary);
}

/* Focus visible states */
.category-pill:focus-visible {
  outline: 2px solid var(--pos-brand-primary);
  outline-offset: 2px;
}
</style>
