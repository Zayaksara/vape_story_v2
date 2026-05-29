<template>
  <div class="cart-panel flex h-full flex-col min-h-0 overflow-hidden" :style="{ backgroundColor: 'var(--pos-bg-primary)' }">
    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 p-4 shrink-0 border-b"
         :style="{ borderBottomColor: 'var(--pos-border)' }">
      <div class="flex min-w-0 items-center gap-2">
        <!-- Cart toggle button -->
        <button
          v-if="showCartButton"
          class="group flex h-10 w-10 items-center justify-center rounded-lg border bg-secondary text-sm font-medium transition-colors hover:bg-primary active:scale-95"
          :style="{
            borderColor: 'var(--pos-bg-border)',
            color: 'var(--pos-brand-primary)'
          }"
          @click="$emit('toggle-cart')"
          aria-label="Buka keranjang"
        >
          <div class="relative w-5 h-5">
            <img src="/images/icon/shopping-cart-21.svg" alt="Shopping Cart" class="h-5 w-5 transition-opacity duration-200 absolute inset-0 opacity-100 group-hover:opacity-0" />
            <img src="/images/icon/shopping-cart-22.svg" alt="Shopping Cart" class="h-5 w-5 transition-opacity duration-200 absolute inset-0 opacity-0 group-hover:opacity-100" />
            <span v-if="cartCount > 0" class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold text-white z-10">
              {{ cartCount > 99 ? '99+' : cartCount }}
            </span>
          </div>
        </button>

        <h2 class="text-lg font-bold" :style="{ color: 'var(--pos-text-primary)' }">Keranjang</h2>
      </div>

      <button
        v-if="cart.length > 0"
        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border transition-colors hover:bg-[var(--pos-bg-secondary)] active:scale-95"
        :style="{
          borderColor: 'var(--pos-brand-primary)',
          color: 'var(--pos-brand-primary)'
        }"
        @click="$emit('open-discount')"
        title="Terapkan Voucher"
        aria-label="Terapkan Voucher"
      >
        <Ticket class="h-5 w-5" />
      </button>

    </div>

    <button
      v-if="cart.length > 0"
      class="shrink-0 w-full px-3 py-3 text-xs font-medium transition-colors rounded-md md:text-sm"
      :style="{ color: 'var(--pos-danger-text)', backgroundColor: 'var(--pos-bg-danger)' }"
      @click="isClearConfirmOpen = true"
    >
      Kosongkan Keranjang
    </button>

    <!-- Confirm Clear Cart Modal -->
    <Dialog :open="isClearConfirmOpen" @update:open="isClearConfirmOpen = $event">
      <DialogContent class="w-[min(94vw,28rem)] p-0 overflow-hidden">
        <div class="p-5">
          <DialogHeader>
            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full"
                 :style="{ backgroundColor: 'var(--pos-bg-danger)' }">
              <AlertTriangle class="h-6 w-6" :style="{ color: 'var(--pos-danger-text)' }" />
            </div>
            <DialogTitle :style="{ color: 'var(--pos-text-primary)' }">
              Kosongkan Keranjang?
            </DialogTitle>
            <DialogDescription :style="{ color: 'var(--pos-text-muted)' }">
              Semua {{ cart.length }} item di keranjang akan dihapus.
              Tindakan ini tidak dapat dibatalkan.
            </DialogDescription>
          </DialogHeader>
        </div>
        <DialogFooter class="border-t px-5 py-4 flex flex-col-reverse sm:flex-row gap-2 justify-end"
                     :style="{ borderColor: 'var(--pos-border)', backgroundColor: 'var(--pos-bg-primary)' }">
          <Button variant="outline" class="flex-1" @click="isClearConfirmOpen = false">
            Batal
          </Button>
          <Button
            class="flex-1"
            :style="{ backgroundColor: 'var(--pos-danger-text)', color: 'var(--pos-text-inverse)' }"
            @click="handleClearCart"
          >
            Ya, Kosongkan
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
    <!-- Cart items - scrollable area -->
    <div class="flex-1 overflow-hidden py-1 min-h-0">
      <div class="h-full min-h-0 overflow-y-auto px-4">
        <div v-if="cart.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
        <svg class="mb-3 h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
        </svg>
        <p class="text-sm text-gray-400">Keranjang kosong</p>
        <p class="mt-1 text-xs text-gray-400">Silakan tambah produk</p>
      </div>

      <div v-else class="space-y-2">
        <CartItemComponent
          v-for="item in cart"
          :key="item.product.id"
          :item="item"
          @remove="$emit('remove-item', item.product.id)"
          @update-qty="(id, qty) => $emit('update-quantity', id, qty)"
        />
      </div>
    </div>
  </div>

    <div class="sticky bottom-0 z-10 border-t bg-[var(--pos-bg-primary)] shadow-[0_-10px_30px_-15px_rgba(0,0,0,0.12)]"
         :style="{ borderTopColor: 'var(--pos-border)' }">
      <div class="p-4">
        <CartSummary
          :subtotal="subtotal"
          :discount-amount="discountAmount"
          :tax-amount="taxAmount"
          :total="total"
          :discount-label="discountLabel"
        />
      </div>

      <!-- Process payment -->
      <button
        class="payment-button shrink-0 w-full rounded-none px-4 py-3.5 text-sm font-bold text-white transition-all hover:opacity-90 active:scale-95"
        :style="{
          backgroundColor: 'var(--pos-brand-primary)',
          boxShadow: '0 10px 25px -5px rgba(20, 184, 166, 0.35)'
        }"
        :disabled="isProcessing || cart.length === 0"
        @click="$emit('process-payment')"
      >
       <span v-if="!isProcessing">Bayar Saja {{ total > 0 ? formatPrice(total) : '' }}</span>
       <span v-else class="flex items-center justify-center gap-2">
         <svg class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
           <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
         </svg>
         Memproses...
       </span>
     </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Ticket, AlertTriangle } from 'lucide-vue-next'
import type { CartItem } from '@/types/pos'
import CartSummary from './CartSummary.vue'
import CartItemComponent from './CartItem.vue'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  cart: CartItem[]
  subtotal: number
  discountAmount: number
  taxAmount: number
  total: number
  isProcessing: boolean
  cartCount: number
  showCartButton: boolean
  discountLabel?: string
}>()

const emit = defineEmits<{
  'remove-item': [productId: string]
  'update-quantity': [productId: string, qty: number]
  'clear-cart': []
  'process-payment': []
  'update:payment-method': [method: string]
  'toggle-cart': []
  'open-discount': []
  'remove-discount': []
}>()

const isClearConfirmOpen = ref(false)

function handleClearCart() {
  emit('clear-cart')
  isClearConfirmOpen.value = false
}

function formatPrice(price: number): string {
  if (typeof price !== 'number' || isNaN(price)) {
    return 'Rp 0'
  }
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price)
}
</script>
