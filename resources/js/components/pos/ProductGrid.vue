<template>
  <div class="flex">
    <!-- Empty state -->
    <div
      v-if="!loading && filteredProducts.length === 0"
      class="flex flex-col items-center justify-center py-16 text-center w-full"
    >
      <img
        src="/images/icon/no-task.svg"
        alt="No data"
        class="mb-4 h-16 w-16"
      />
      <p class="text-lg font-medium text-gray-500">
        Tidak ada data yang ditampilkan
      </p>
    </div>

    <!-- Loading skeleton -->
    <div v-else-if="loading" class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4 w-full">
      <div
        v-for="i in 8"
        :key="i"
        class="rounded-2xl bg-white p-3 animate-pulse"
      >
        <div class="aspect-square w-full rounded-xl bg-gray-100" />
        <div class="mt-3 h-4 w-3/4 rounded bg-gray-200" />
        <div class="mt-2 h-3 w-1/2 rounded bg-gray-200" />
      </div>
    </div>

    <!-- Product grid -->
    <div
      v-else
      class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-4 w-full"
      :class="{ 'overflow-y-auto': maxHeight }"
      :style="maxHeight ? { maxHeight: maxHeight + 'px' } : {}"
    >
      <ProductCard
        v-for="product in filteredProducts"
        :key="product.id"
        :product="product"
        @add-to-cart="$emit('add-to-cart', product)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Product, Category } from '@/types/pos'
import ProductCard from './ProductCard.vue'

const props = defineProps<{
  products: Product[]
  loading?: boolean
  maxHeight?: number  // Optional: jika ingin scroll di dalam grid
}>()

defineEmits<{
  'add-to-cart': [product: Product]
}>()

// Note: Filtering is now handled in parent component (dashboard.vue)
// This component just displays the products it receives
const filteredProducts = computed(() => props.products)
</script>
