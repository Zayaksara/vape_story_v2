<template>
  <div
    class="product-card group relative flex flex-col overflow-hidden rounded-2xl border transition-all duration-200 hover:shadow-md"
    :style="{
      borderColor: 'var(--pos-border-focus)',
      backgroundColor: 'var(--pos-bg-primary)'
    }"
    :class="{ 'opacity-50 pointer-events-none': product.stock <= 0 }"
    @click="handleAdd"
  >
    <!-- Stock badge -->
    <div class="absolute right-3 top-3 z-10">
      <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="stockBadgeClass"
      >
        {{ stockBadgeText }}
      </span>
    </div>

    <!-- Image area -->
    <div class="aspect-square w-full overflow-hidden"
         :style="{ backgroundColor: 'var(--pos-bg-secondary)' }">
      <img
        v-if="product.image_url"
        :src="product.image_url"
        :alt="product.name"
        class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
      />
      <div v-else class="flex h-full items-center justify-center"
           :style="{ backgroundColor: 'var(--pos-bg-secondary)' }">
        <svg class="h-12 w-12"
             :style="{ color: 'var(--pos-text-light)' }"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
    </div>

    <!-- Content -->
    <div class="flex flex-1 flex-col gap-1.5 p-3"
         :style="{ backgroundColor: 'var(--pos-bg-primary)' }">
      <h3 class="line-clamp-2 text-xs font-medium leading-snug"
          :style="{ color: 'var(--pos-text-primary)' }">
        {{ product.name }}
      </h3>

      <!-- Extra info row -->
      <div class="flex items-center gap-2 text-[10px]"
           :style="{ color: 'var(--pos-text-light)' }">
        <span v-if="product.sku" class="uppercase tracking-wide">{{ product.sku }}</span>
        <span v-if="product.volume" class=" pl-2"
              :style="{ borderLeftColor: 'var(--pos-border)' }">{{ product.volume }}</span>
      </div>

      <!-- Price -->
      <div class="mt-auto pt-1">
        <div class="text-lg font-bold leading-none tracking-tight"
             :style="{ color: 'var(--pos-brand-primary)' }">
          {{ formatPrice(product.price) }}
        </div>
      </div>

      <!-- Add button overlay on hover -->
      <div
        class="add-button absolute inset-0 flex items-center justify-center opacity-0 transition-opacity group-hover:opacity-100"
        :class="product.stock <= 0 ? 'hidden' : ''"
        :style="{ backgroundColor: 'rgba(0,0,0,0.2)' }"
      >
        <div
          class="flex h-10 w-10 items-center justify-center rounded-full shadow-md"
          :style="{
            backgroundColor: 'var(--pos-brand-primary)',
            color: 'var(--pos-text-inverse)'
          }"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 4v16m8-8H4" />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Product } from '@/types/pos'

const props = defineProps<{
  product: Product
}>()

const emit = defineEmits<{
  'add-to-cart': [product: Product]
}>()

function handleAdd() {
  if (props.product.stock <= 0) {
    return
  }
  emit('add-to-cart', props.product)
}

const stockBadgeClass = computed(() => {
  const stock = props.product.stock

  if (typeof stock !== 'number' || isNaN(stock)) {
    return 'bg-gray-100 text-gray-500'
  }

  if (stock <= 0) {
    return 'bg-red-100 text-red-600'
  }

  if (stock <= 4) {
    return 'bg-amber-100 text-amber-600'
  }

  if (stock <= 10) {
    return 'bg-blue-100 text-blue-600'
  }

  return 'bg-gray-100 text-gray-600'
})

const stockBadgeText = computed(() => {
  const stock = props.product.stock

  if (typeof stock !== 'number' || isNaN(stock)) {
    return '0 pcs'
  }

  if (stock <= 0) {
    return 'Habis'
  }

  if (stock <= 4) {
    return `Sisa ${stock}`
  }

  return `${stock} pcs`
})

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
