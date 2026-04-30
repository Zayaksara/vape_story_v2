<template>
  <nav class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide" role="tablist" aria-label="Kategori produk">
    <button
      v-for="category in allCategories"
      :key="category.id"
      role="tab"
      :aria-selected="modelValue === category.id"
      class="category-pill whitespace-nowrap rounded-full px-4 py-2 text-sm font-medium transition-all duration-150"
      :style="{
        backgroundColor: modelValue === category.id ? 'var(--pos-brand-primary)' : 'var(--pos-bg-secondary)',
        color: modelValue === category.id ? 'var(--pos-text-inverse)' : 'var(--pos-text-secondary)',
        borderColor: 'transparent'
      }"
      @click="$emit('update:modelValue', category.id)"
    >
      <span>{{ category.name }}</span>
      <span
        class="ml-1.5 rounded-full px-1.5 py-0.5 text-xs font-bold"
        :style="{
          backgroundColor: 'rgba(255,255,255,0.3)',
          color: modelValue === category.id ? 'var(--pos-text-inverse)' : 'var(--pos-text-muted)'
        }"
      >
        {{ productCounts[category.id] || 0 }}
      </span>
    </button>
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Category } from '@/types/pos'

const allCategories = computed(() => [
  { id: null as any, name: 'Semua' },
  ...props.categories,
])

const props = defineProps<{
  categories: Category[]
  modelValue: number | null
  productCounts: Record<number, number>
}>()

defineEmits<{
  'update:modelValue': [id: number | null]
}>()
</script>
