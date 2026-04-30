<template>
  <div class="cart-item flex items-center gap-3 rounded-xl border p-3 shadow-sm"
       :style="{
         borderColor: 'var(--pos-border)',
         backgroundColor: 'var(--pos-bg-primary)',
       }">
    <!-- Product image -->
    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg"
         :style="{ backgroundColor: 'var(--pos-bg-secondary)' }">
      <img
        v-if="item.product.image_url"
        :src="item.product.image_url"
        :alt="item.product.name"
        class="h-full w-full object-cover"
      />
      <div v-else class="flex h-full items-center justify-center"
           :style="{ backgroundColor: 'var(--pos-bg-secondary)' }">
        <svg class="h-6 w-6"
             :style="{ color: 'var(--pos-text-light)' }"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
    </div>

    <!-- Info -->
    <div class="flex min-w-0 flex-1 flex-col gap-0.5">
      <h4 class="truncate text-xs font-medium"
          :style="{ color: 'var(--pos-text-primary)' }">
        {{ item.product.name }}
      </h4>
      <p class="text-[10px]"
         :style="{ color: 'var(--pos-text-primary)' }">
        {{ formatPrice(item.product.price) }} /pcs
      </p>
      <div class="flex items-center gap-2 pt-0.5">
        <div class="qty-btn flex items-center gap-1 rounded-lg border bg-white"
             :style="{ borderColor: 'var(--pos-border)' }">
          <button
            class="flex h-6 w-6 items-center justify-center rounded-l-lg transition-colors hover:bg-gray-100"
            :style="{
              color: item.quantity <= 1 ? 'var(--pos-danger-text)' : 'var(--pos-text-muted)'
            }"
            @click="handleRemove"
            :aria-label="item.quantity <= 1 ? 'Hapus item' : 'Kurangi jumlah'"
          >
            <svg v-if="item.quantity <= 1" class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <svg v-else class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
            </svg>
          </button>
          <span class="w-8 text-center text-xs font-medium">{{ item.quantity }}</span>
          <button
            class="flex h-6 w-6 items-center justify-center rounded-r-lg transition-colors hover:bg-gray-100"
            :style="{
              color: 'var(--pos-brand-primary)',
              opacity: item.quantity >= item.product.stock ? 0.5 : 1
            }"
            :disabled="item.quantity >= item.product.stock"
            @click="handleIncrease"
            aria-label="Tambah jumlah"
          >
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
          </button>
        </div>
        <span class="text-xs font-medium"
              :style="{ color: 'var(--pos-brand-primary)' }">
          {{ formatPrice(item.subtotal) }}
        </span>
      </div>
    </div>

    <!-- Delete button -->
    <button
      class="shrink-0 rounded-full p-1.5 transition-colors hover:bg-red-50"
      :style="{ color: 'var(--pos-text-muted)' }"
      @click="$emit('remove', item.product.id)"
      aria-label="Hapus item"
    >
      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import type { CartItem } from '@/types/pos'

const props = defineProps<{
  item: CartItem
}>()

const emit = defineEmits<{
  'remove': [productId: string]
  'update-qty': [productId: string, qty: number]
}>()

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

function handleRemove() {
  if (props.item.quantity <= 1) {
    emit('remove', props.item.product.id)
  } else {
    emit('update-qty', props.item.product.id, props.item.quantity - 1)
  }
}

function handleIncrease() {
  emit('update-qty', props.item.product.id, props.item.quantity + 1)
}
</script>
