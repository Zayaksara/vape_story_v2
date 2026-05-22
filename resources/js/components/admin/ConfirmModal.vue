<script setup lang="ts">
import { AlertTriangle, X } from 'lucide-vue-next'

withDefaults(defineProps<{
  open: boolean
  title?: string
  message?: string
  detail?: string | null
  confirmLabel?: string
  cancelLabel?: string
  variant?: 'danger' | 'primary'
  processing?: boolean
}>(), {
  title: 'Konfirmasi',
  message: 'Apakah Anda yakin?',
  detail: null,
  confirmLabel: 'Hapus',
  cancelLabel: 'Batal',
  variant: 'danger',
  processing: false,
})

const emit = defineEmits<{
  confirm: []
  cancel: []
}>()

function onBackdrop() {
  emit('cancel')
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="open"
      class="fixed inset-0 z-[60] flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
    >
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="onBackdrop" />
      <div
        class="relative z-10 w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl animate-in fade-in zoom-in-95 duration-200"
      >
        <div class="flex items-start gap-4 px-6 pt-6 pb-4">
          <div
            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full"
            :style="variant === 'danger'
              ? 'background: #fee2e2; color: #dc2626;'
              : 'background: #ccfbf1; color: #0d9488;'"
          >
            <AlertTriangle class="h-6 w-6" />
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-base font-bold" style="color: #1e293b;">{{ title }}</h3>
            <p class="mt-1.5 text-sm leading-relaxed" style="color: #475569;">{{ message }}</p>
            <p v-if="detail" class="mt-3 rounded-md bg-gray-50 px-3 py-2 text-sm font-mono" style="color: #334155;">
              {{ detail }}
            </p>
          </div>
          <button
            type="button"
            class="cursor-pointer rounded-full p-1.5 transition-colors hover:bg-gray-100"
            style="color: #64748b;"
            aria-label="Tutup"
            @click="emit('cancel')"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="flex justify-end gap-2 border-t bg-gray-50 px-6 py-4" style="border-color: #e5e7eb;">
          <button
            type="button"
            class="cursor-pointer rounded-md border bg-white px-5 py-2.5 text-sm font-semibold transition hover:bg-gray-100"
            style="border-color: #e5e7eb; color: #475569;"
            :disabled="processing"
            @click="emit('cancel')"
          >
            {{ cancelLabel }}
          </button>
          <button
            type="button"
            class="cursor-pointer rounded-md px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-60"
            :style="variant === 'danger'
              ? 'background: #dc2626;'
              : 'background: #14b8a6;'"
            :disabled="processing"
            @click="emit('confirm')"
          >
            {{ processing ? 'Memproses…' : confirmLabel }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
