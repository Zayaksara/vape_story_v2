<template>
  <div
    v-if="modelValue"
    class="fixed bottom-4 right-4 z-50 transform transition-all duration-300 ease-out animate-slide-up"
    role="alert"
    aria-live="polite"
  >
    <div
    class="flex items-center gap-3 rounded-xl px-5 py-4 shadow-lg"
    :style="{
      backgroundColor: 'var(--pos-bg-primary)',
      borderLeft: '4px solid transparent',
      borderColor: 'var(--pos-border)'
    }"
    :class="{
      'border-l-green-500': type === 'success',
      'border-l-red-500': type === 'error',
    }"
    >
      <svg
        v-if="type === 'success'"
        class="h-5 w-5 shrink-0"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
        :style="{ color: 'var(--pos-success-text)' }"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
      <svg
      v-if="type === 'error'"
      class="h-5 w-5 shrink-0"
      fill="none"
      stroke="currentColor"
      viewBox="0 0 24 24"
      :style="{ color: 'var(--pos-danger-text)' }"
      >
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
      <span class="text-sm font-medium" :style="{ color: 'var(--pos-text-secondary)' }">{{ modelValue }}</span>
      <button
        class="ml-2 rounded-full p-1 transition-colors hover:bg-gray-100"
         :style="{ color: 'var(--pos-text-muted)' }"
        @click="$emit('update:modelValue', '')"
        aria-label="Tutup notifikasi"
      >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: string
  type?: 'success' | 'error'
}>()

defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>
