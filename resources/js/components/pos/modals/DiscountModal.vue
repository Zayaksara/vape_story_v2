<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="discount-modal fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
    >
      <!-- Backdrop -->
      <div
        class="absolute inset-0 backdrop-blur-sm bg-black/50 transition-opacity"
        @click="close"
      />

      <!-- Modal -->
      <div
        class="relative z-10 w-full max-w-[360px] rounded-2xl shadow-2xl animate-in fade-in zoom-in-95 duration-200"
        :style="{
          backgroundColor: 'var(--pos-bg-primary)'
        }"
      >
        <!-- Header -->
        <div class="border-b p-4"
             :style="{ borderBottomColor: 'var(--pos-border)' }">
          <h3 id="modal-title" class="text-lg font-bold"
              :style="{ color: 'var(--pos-text-primary)' }">
            Pilih Diskon
          </h3>
          <button
            class="absolute right-3 top-3 rounded-full p-1 transition-colors hover:bg-gray-100"
            :style="{ color: 'var(--pos-text-muted)' }"
            @click="close"
            aria-label="Tutup modal"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Search -->
        <div class="border-b p-4"
             :style="{ borderBottomColor: 'var(--pos-border)' }">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2"
                 :style="{ color: 'var(--pos-text-muted)' }"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
              ref="searchInput"
              v-model="searchQuery"
              type="text"
              placeholder="Cari voucher..."
              class="w-full rounded-lg border py-2 pl-9 pr-4 text-sm outline-none"
              :style="{
                borderColor: 'var(--pos-border)',
                backgroundColor: 'var(--pos-bg-secondary)'
              }"
            />
          </div>
        </div>

        <!-- List -->
        <div class="max-h-[400px] overflow-y-auto p-2">
          <div v-if="filteredDiscounts.length === 0" class="py-8 text-center">
            <p :style="{ color: 'var(--pos-text-muted)' }">Tidak ada voucher ditemukan</p>
          </div>

          <button
            v-for="d in filteredDiscounts"
            :key="d.code"
            class="discount-item w-full rounded-xl border p-3 text-left transition-all mb-2 last:mb-0"
            :style="{
              borderColor: discount?.code === d.code ? 'var(--pos-brand-primary)' : 'var(--pos-border)',
              backgroundColor: discount?.code === d.code ? 'var(--pos-brand-light)' : 'var(--pos-bg-primary)'
            }"
            @click="apply(d)"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                  <span class="font-medium"
                        :style="{ color: 'var(--pos-text-secondary)' }">{{ d.label }}</span>
                  <span
                    v-if="discount?.code === d.code"
                    class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold"
                    :style="{
                      backgroundColor: 'var(--pos-brand-primary)',
                      color: 'var(--pos-text-inverse)'
                    }"
                  >
                    Aktif
                  </span>
                </div>
                <p class="text-xs"
                   :style="{ color: 'var(--pos-text-muted)' }">{{ d.code }}</p>
              </div>
              <div class="shrink-0 text-right">
                <span class="text-sm font-bold"
                      :style="{ color: 'var(--pos-brand-primary)' }">
                  {{ d.value }}{{ d.type === 'percent' ? '%' : 'K' }}
                </span>
              </div>
            </div>

            <div v-if="d.min_purchase" class="mt-2 flex items-center gap-1">
              <span class="text-[10px]"
                    :style="{ color: 'var(--pos-text-muted)' }">
                Min. belanja {{ formatPrice(d.min_purchase) }}
              </span>
              <span
                v-if="subtotal < (d.min_purchase || 0)"
                class="ml-1 rounded px-1.5 py-0.5 text-[10px]"
                :style="{
                  backgroundColor: 'var(--pos-danger-bg)',
                  color: 'var(--pos-danger-text)'
                }"
              >
                Belum memenuhi
              </span>
              <span
                v-else
                class="ml-1 rounded px-1.5 py-0.5 text-[10px]"
                :style="{
                  backgroundColor: 'var(--pos-success-bg)',
                  color: 'var(--pos-success-text)'
                }"
              >
                Memenuhi
              </span>
            </div>

            <div v-if="d.expires_at" class="mt-1 text-[10px]"
                 :style="{ color: 'var(--pos-text-muted)' }">
              Kadaluarsa: {{ formatDate(d.expires_at) }}
            </div>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { Discount } from '@/types/pos'

const props = defineProps<{
  modelValue: boolean
  subtotal: number
  appliedDiscount?: Discount | null
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'apply': [discount: Discount]
}>()

const searchInput = ref<HTMLInputElement | null>(null)
const searchQuery = ref('')

// Mock discounts - would come from API
const availableDiscounts = ref<Discount[]>([
  {
    code: 'WELCOME10',
    label: 'Diskon Selamat Datang',
    type: 'percent',
    value: 10,
    min_purchase: 50000,
    expires_at: '2024-12-31T23:59:59Z',
  },
  {
    code: 'FIX25K',
    label: 'Potongan Rp 25.000',
    type: 'fixed',
    value: 25000,
    min_purchase: 100000,
    expires_at: '2024-12-31T23:59:59Z',
  },
  {
    code: 'VIP15',
    label: 'Diskon VIP Member',
    type: 'percent',
    value: 15,
    max_discount: 100000,
    expires_at: '2024-12-31T23:59:59Z',
  },
])

const discount = computed(() => props.appliedDiscount)

const filteredDiscounts = computed(() => {
  if (!searchQuery.value) {
    return availableDiscounts.value
  }

  return availableDiscounts.value.filter(d =>
    d.code.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
    d.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  )
})

function close() {
  emit('update:modelValue', false)
}

function apply(d: Discount) {
  if (d.min_purchase && props.subtotal < d.min_purchase) {
    return
  }

  emit('apply', d)
}

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

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('id-ID')
}
</script>
