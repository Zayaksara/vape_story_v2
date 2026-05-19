<template>
  <div class="cart-summary space-y-4 pb-1" >
    <!-- Subtotal -->
    <div class="flex items-center justify-between text-sm">
      <span :style="{ color: 'var(--pos-text-light)' }">Subtotal</span>
      <span class="font-medium" :style="{ color: 'var(--pos-text-light)' }">{{ formatPrice(subtotal) }}</span>
    </div>

    <!-- Discount -->
    <div v-if="discountAmount > 0" class="flex items-center justify-between text-sm">
      <span :style="{ color: 'var(--pos-text-muted)' }">
        Diskon
        <span v-if="discountLabel" class="text-xs" :style="{ color: 'var(--pos-danger-text)' }">({{ discountLabel }})</span>
      </span>
      <span class="font-medium" :style="{ color: 'var(--pos-danger-text)' }">-{{ formatPrice(discountAmount) }}</span>
    </div>

    <div class="my-2 border-t" :style="{ borderTopColor: 'var(--pos-border)' }" />

    <!-- Total -->
    <div class="flex items-center justify-between text-base font-semibold">
      <span :style="{ color: 'var(--pos-text-primary)' }">Total</span>
      <span :style="{ color: 'var(--pos-text-primary)' }">{{ formatPrice(total) }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  subtotal: number
  discountAmount: number
  taxAmount: number
  total: number
  discountLabel?: string
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
</script>
