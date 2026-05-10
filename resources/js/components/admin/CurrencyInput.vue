<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  modelValue: string | number | null | undefined
  placeholder?: string
  required?: boolean
  id?: string
}>(), {
  placeholder: '0',
  required: false,
  id: undefined,
})

const emit = defineEmits<{ 'update:modelValue': [value: string] }>()

const display = computed({
  get() {
    const raw = String(props.modelValue ?? '').replace(/[^\d]/g, '')
    if (!raw) return ''
    return Number(raw).toLocaleString('id-ID')
  },
  set(v: string) {
    const digits = v.replace(/[^\d]/g, '')
    emit('update:modelValue', digits)
  },
})
</script>

<template>
  <div class="relative">
    <span
      class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs font-semibold"
      style="color: var(--pos-text-muted);"
    >
      Rp
    </span>
    <input
      :id="id"
      v-model="display"
      type="text"
      inputmode="numeric"
      autocomplete="off"
      :placeholder="placeholder"
      :required="required"
      class="w-full rounded-md border py-2 pl-9 pr-3 text-sm outline-none transition focus:ring-2"
      style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
    />
  </div>
</template>
