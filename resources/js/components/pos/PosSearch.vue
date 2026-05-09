<template>
  <div class="pos__search relative flex items-center gap-2">
    <div
      class="flex flex-1 items-center gap-2 rounded-xl px-4 py-2.5 shadow-sm transition-all duration-200"
      :style="{
        backgroundColor: 'var(--pos-brand-secondary)',
        border: '1px solid var(--pos-brand-primary)',
        boxShadow: isFocused ? '0 0 0 3px var(--pos-brand-light)' : 'none'
      }"
    >
      <!-- Search icon - white -->
      <svg
        class="h-5 w-5 shrink-0"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        :style="{ color: 'var(--pos-text-muted)' }"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>

      <!-- Search input - white text, transparent bg -->
      <input
        ref="searchInput"
        v-model="localQuery"
        type="text"
        placeholder="Cari produk..."
        class="flex-1 bg-transparent text-sm outline-none"
        :style="{ color: 'var(--pos-text-secondary)' }"
        aria-label="Cari produk"
        @focus="isFocused = true"
        @blur="isFocused = false"
        @input="handleInput"
      />

      <!-- Clear button - white with hover -->
      <button
        v-if="localQuery"
        class="rounded-full p-1 transition-colors duration-200 hover:bg-white/20"
        :style="{ color: 'var(--pos-text-inverse)' }"
        @click="clearSearch"
        aria-label="Bersihkan pencarian"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
  modelValue: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'search': [value: string]
}>()

const searchInput = ref<HTMLInputElement | null>(null)
const localQuery = ref(props.modelValue)
const isFocused = ref(false)

watch(
  () => props.modelValue,
  (val) => {
    if (val !== localQuery.value) {
      localQuery.value = val
    }
  }
)

function handleInput() {
  emit('update:modelValue', localQuery.value)
  // Debounced search emit
  emit('search', localQuery.value)
}

function clearSearch() {
  localQuery.value = ''
  emit('update:modelValue', '')
  emit('search', '')
  searchInput.value?.focus()
}
</script>
