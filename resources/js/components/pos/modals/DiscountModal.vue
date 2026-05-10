<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="adm-sheet discount-modal fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="discount-modal-title"
    >
      <!-- Backdrop -->
      <div
        class="absolute inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
        @click="close"
      />

      <!-- Modal -->
      <div
        class="relative z-10 w-full max-w-[400px] overflow-hidden rounded-2xl shadow-2xl animate-in fade-in zoom-in-95 duration-200"
        style="background: #ffffff;"
      >
        <!-- Header -->
        <div
          class="relative flex items-start justify-between gap-3 border-b px-5 py-4"
          style="border-color: var(--pos-border); background: var(--pos-brand-light);"
        >
          <div class="flex items-center gap-2.5">
            <div
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
              style="background: #fff;"
            >
              <Tag class="h-4 w-4" style="color: var(--pos-brand-primary);" />
            </div>
            <div>
              <h3
                id="discount-modal-title"
                class="text-sm font-bold"
                style="color: var(--pos-brand-dark);"
              >
                Pilih Voucher
              </h3>
              <p class="text-[11px]" style="color: var(--pos-text-secondary);">
                {{ discounts.length }} voucher tersedia
              </p>
            </div>
          </div>
          <button
            class="cursor-pointer rounded-full p-1.5 transition-colors hover:bg-white/60"
            style="color: var(--pos-text-muted);"
            aria-label="Tutup modal"
            @click="close"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Search -->
        <div class="border-b px-4 py-3" style="border-color: var(--pos-border); background: #f8fafc;">
          <div class="relative">
            <Search
              class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2"
              style="color: var(--pos-text-muted);"
            />
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              placeholder="Cari kode atau nama voucher…"
              class="h-9 w-full rounded-md border pl-8 pr-3 text-xs outline-none transition"
              style="border-color: var(--pos-border); background: #fff; color: var(--pos-text-secondary);"
            />
          </div>
        </div>

        <!-- List -->
        <div class="max-h-[420px] overflow-y-auto p-3">
          <div v-if="filteredDiscounts.length === 0" class="py-12 text-center">
            <Tag class="mx-auto mb-2 h-10 w-10" style="color: var(--pos-text-muted); opacity: 0.3;" />
            <p class="text-sm font-medium" style="color: var(--pos-text-muted);">
              {{ discounts.length === 0 ? 'Belum ada voucher aktif' : 'Voucher tidak ditemukan' }}
            </p>
            <p class="mt-1 text-xs" style="color: var(--pos-text-light);">
              {{ discounts.length === 0 ? 'Buat promo di Manajemen Promo' : 'Coba kata kunci lain' }}
            </p>
          </div>

          <button
            v-for="d in filteredDiscounts"
            :key="d.code"
            class="mb-2 w-full cursor-pointer rounded-lg border p-3 text-left transition-all last:mb-0 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-60"
            :style="{
              borderColor: discount?.code === d.code ? 'var(--pos-brand-primary)' : 'var(--pos-border)',
              background: discount?.code === d.code ? 'var(--pos-brand-light)' : '#fff',
            }"
            :disabled="!canApply(d)"
            @click="apply(d)"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span class="text-sm font-semibold" style="color: var(--pos-text-secondary);">
                    {{ d.label }}
                  </span>
                  <span
                    v-if="discount?.code === d.code"
                    class="inline-flex items-center gap-1 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold"
                    style="background: var(--pos-brand-primary); color: #fff;"
                  >
                    <CheckCircle2 class="h-3 w-3" /> Aktif
                  </span>
                </div>
                <p class="mt-0.5 font-mono text-[11px]" style="color: var(--pos-text-muted);">
                  {{ d.code }}
                </p>
              </div>
              <div class="shrink-0 text-right">
                <span class="text-base font-bold" style="color: var(--pos-brand-primary);">
                  {{ d.type === 'percent' ? `${d.value}%` : formatPrice(d.value) }}
                </span>
                <p class="text-[10px]" style="color: var(--pos-text-muted);">
                  {{ d.type === 'percent' ? 'Persentase' : 'Potongan' }}
                </p>
              </div>
            </div>

            <div class="mt-2 flex flex-wrap items-center gap-1.5">
              <span
                v-if="d.min_purchase"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                :style="subtotal < (d.min_purchase || 0)
                  ? 'background: var(--pos-bg-danger); color: var(--pos-danger-text);'
                  : 'background: var(--pos-bg-success); color: var(--pos-success-text);'"
              >
                Min. {{ formatPrice(d.min_purchase) }}
                <span class="ml-1 opacity-80">
                  {{ subtotal < (d.min_purchase || 0) ? '· belum cukup' : '· memenuhi' }}
                </span>
              </span>

              <span
                v-if="d.max_discount"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold"
                style="background: #f1f5f9; color: var(--pos-text-secondary);"
              >
                Maks. {{ formatPrice(d.max_discount) }}
              </span>

              <span
                v-if="d.expires_at"
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px]"
                style="background: var(--pos-bg-warning); color: var(--pos-warning-text);"
              >
                Berakhir {{ formatDate(d.expires_at) }}
              </span>
            </div>
          </button>
        </div>

        <!-- Footer -->
        <div
          class="flex items-center justify-between border-t px-4 py-3"
          style="border-color: var(--pos-border); background: #f8fafc;"
        >
          <p class="text-[11px]" style="color: var(--pos-text-muted);">
            Subtotal: <strong style="color: var(--pos-text-secondary);">{{ formatPrice(subtotal) }}</strong>
          </p>
          <button
            class="cursor-pointer rounded-md border px-3 py-1.5 text-xs font-semibold transition hover:bg-white"
            style="border-color: var(--pos-border); color: var(--pos-text-secondary); background: #fff;"
            @click="close"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Search, X, Tag, CheckCircle2 } from 'lucide-vue-next'
import type { Discount } from '@/types/pos'

const props = withDefaults(defineProps<{
  modelValue: boolean
  subtotal: number
  appliedDiscount?: Discount | null
  discounts?: Discount[]
}>(), {
  appliedDiscount: null,
  discounts: () => [],
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'apply': [discount: Discount]
}>()

const searchInput = ref<HTMLInputElement | null>(null)
const searchQuery = ref('')

const discount = computed(() => props.appliedDiscount)

const filteredDiscounts = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  if (!q) return props.discounts
  return props.discounts.filter(d =>
    d.code.toLowerCase().includes(q) ||
    d.label.toLowerCase().includes(q),
  )
})

function canApply(d: Discount): boolean {
  if (!d.min_purchase) return true
  return props.subtotal >= d.min_purchase
}

function close() {
  emit('update:modelValue', false)
}

function apply(d: Discount) {
  if (!canApply(d)) return
  emit('apply', d)
}

function formatPrice(price: number): string {
  if (typeof price !== 'number' || isNaN(price)) return 'Rp 0'
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price)
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('id-ID', {
    day: '2-digit', month: 'short', year: 'numeric',
  })
}
</script>

<style>
.discount-modal.adm-sheet {
  --pos-bg-primary: #ffffff;
  --pos-bg-secondary: #f9fafb;
  --pos-bg-accent: #ccfbf1;
  --pos-bg-danger: #fee2e2;
  --pos-bg-warning: #fef3c7;
  --pos-bg-success: #dcfce7;
  --pos-border: #e5e7eb;
  --pos-text-primary: #0f172a;
  --pos-text-secondary: #1e293b;
  --pos-text-muted: #64748b;
  --pos-text-light: #94a3b8;
  --pos-brand-primary: #14b8a6;
  --pos-brand-light: #ecfeff;
  --pos-brand-dark: #0d9488;
  --pos-success-text: #16a34a;
  --pos-warning-text: #d97706;
  --pos-danger-text: #dc2626;
}

.discount-modal input:focus {
  border-color: var(--pos-brand-primary);
  outline: 2px solid var(--pos-brand-light);
  outline-offset: 1px;
}
</style>
