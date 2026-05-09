<template>
  <Teleport to="body">
    <Dialog :open="modelValue" @update:open="emitUpdate">
      <DialogContent class="w-[min(96vw,40rem)] max-h-[92dvh] overflow-hidden p-0">
        <div class="flex max-h-[92dvh] flex-col">
          <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
            <DialogHeader>
              <DialogTitle class="flex items-center gap-2 text-primary">
                <svg class="h-5 w-5 text-border" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Pembayaran
              </DialogTitle>
              <DialogDescription>
                Pilih metode pembayaran dan masukkan jumlah uang yang diterima
              </DialogDescription>
            </DialogHeader>

            <!-- Total Display (Card) -->
            <Card class="mb-5 border-2 border-primary">
              <CardContent class="pt-5 pb-5 text-center">
                <p class="text-sm mb-1 text-muted-foreground">Total Tagihan</p>
                <p class="text-3xl sm:text-4xl font-extrabold tracking-tight text-primary">
                  {{ formatPrice(total) }}
                </p>
              </CardContent>
            </Card>

        <!-- Payment Method Selection -->
        <div class="mb-5 space-y-2">
          <Label class="text-sm font-semibold">Metode Pembayaran</Label>
          <PaymentMethods
            v-model="localMethod"
            :disabled="isProcessing"
            class="grid grid-cols-2 gap-2 sm:grid-cols-4"
          />
        </div>

        <!-- Cash Input Section -->
        <div v-if="localMethod === 'cash'" class="mb-5 space-y-3">
          <!-- Quick money buttons -->
          <div class="grid grid-cols-3 gap-2 sm:grid-cols-5">
            <Button
              v-for="preset in cashPresets"
              :key="preset"
              type="button"
              variant="outline"
              class="h-9 text-xs"
              :disabled="isProcessing"
              @click="setCashReceived(preset)"
            >
              {{ formatCompactPrice(preset) }}
            </Button>
            <Button
              type="button"
              variant="outline"
              class="h-9 text-xs sm:col-span-1"
              :disabled="isProcessing"
              @click="setCashReceived(total)"
            >
              Uang Pas
            </Button>
          </div>

          <!-- Cash Input -->
          <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-lg font-medium text-muted-foreground">Rp</span>
            <Input
              id="cash-input"
              ref="cashInput"
              v-model.number="localCashReceived"
              type="number"
              inputmode="numeric"
              pattern="[0-9]*"
              class="w-full pl-10 pr-4 py-4 text-xl sm:text-2xl font-bold text-center tracking-wider"
              :class="{
                'border-green-500 focus-visible:ring-green-500': localCashReceived >= total && total > 0,
                'border-red-500 focus-visible:ring-red-500': localCashReceived < total && localCashReceived > 0
              }"
              placeholder="0"
              :disabled="isProcessing"
              @focus="selectAll"
            />
          </div>

          <!-- Change Display -->
          <div
            v-if="localCashReceived > 0"
            class="flex items-center justify-between rounded-xl px-4 py-3 border-2 transition-all"
            :class="change >= 0 ? 'border-green-500/50 bg-green-50 dark:bg-green-950/20' : 'border-red-500/50 bg-red-50 dark:bg-red-950/20'"
          >
            <div class="flex items-center gap-2">
              <svg v-if="change >= 0" class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <svg v-else class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="font-medium text-foreground">
                {{ change >= 0 ? 'Kembalian' : 'Belum Cukup' }}
              </span>
            </div>
            <span
              class="text-xl font-bold"
              :class="change >= 0 ? 'text-green-600' : 'text-red-600'"
            >
              {{ formatPrice(Math.abs(change)) }}
            </span>
          </div>
        </div>

        <!-- QRIS Section -->
        <div v-else-if="localMethod === 'qris'" class="mb-5">
          <div class="flex flex-col items-center justify-center rounded-xl border-2 border-border border-dashed p-4 text-center sm:p-6"
              :style="{ backgroundColor: 'var(--pos-bg-secondary)' }">
            <img
              v-if="showQrisImage"
              src="/storage/images/QRCODE.webp"
              alt="QRIS Payment Code"
              class="mb-2 h-auto w-full max-w-[220px] rounded-md object-contain"
              loading="lazy"
              @error="showQrisImage = false"
            />
            <QrCode v-else class="h-10 w-20 mb-3 text-muted-foreground" />
            <p class="text-xs text-muted-foreground">
              Scan QR untuk menyelesaikan pembayaran
            </p>
            <Button
              v-if="showQrisImage"
              type="button"
              variant="outline"
              class="mt-3 h-8 px-3 text-xs"
              :disabled="isProcessing"
              @click="isQrFullscreenOpen = true"
            >
              Perbesar QR
            </Button>
          </div>
        </div>

        <!-- Other Electronic Methods (Debit/E-Wallet) -->
        <div v-else class="mb-5">
          <div class="flex flex-col items-center justify-center rounded-xl border-2 border-border p-5 text-center"
               :style="{ backgroundColor: 'var(--pos-bg-secondary)' }">
            <CreditCard class="h-10 w-10 mb-2 text-primary" />
            <p class="text-sm font-medium mb-1 text-foreground">
              {{ paymentMethodLabel }}
            </p>
            <p class="text-xs text-muted-foreground">
              Arahkan pelanggan untuk menyelesaikan pembayaran non-tunai
            </p>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <DialogFooter class="shrink-0 border-t bg-background px-4 py-3 sm:px-6 sm:py-4 flex flex-col-reverse sm:flex-row gap-2 justify-end">
        <Button
          variant="outline"
          class="flex-1"
          :disabled="isProcessing"
          @click="close"
          
        >
          Batal
        </Button>
        <Button
          :disabled="isProcessing || (localMethod === 'cash' && localCashReceived < total && total > 0)"
          class="flex-1 gap-2"
          :class="localMethod === 'cash' && localCashReceived >= total ? '' : ''"
          @click="confirmPayment"
        >
          <svg v-if="isProcessing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <svg v-else-if="localMethod === 'cash' && change >= 0" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span>
            {{ isProcessing ? 'Memproses...' : (localMethod === 'cash' && change >= 0) ? 'Konfirmasi Pembayaran' : 'Konfirmasi' }}
          </span>
        </Button>
      </DialogFooter>
    </div>
  </DialogContent>
    </Dialog>

    <Dialog :open="isQrFullscreenOpen" @update:open="isQrFullscreenOpen = $event">
      <DialogContent class="w-[min(98vw,56rem)] max-h-[96dvh] overflow-hidden p-0">
        <div class="flex max-h-[96dvh] flex-col bg-black">
          <div class="flex items-center justify-between border-b border-white/20 px-4 py-3 text-white">
            <p class="text-sm font-semibold">QRIS - Fullscreen</p>
            <Button
              type="button"
              variant="ghost"
              class="h-8 px-3 text-xs text-white hover:bg-white/10"
              @click="isQrFullscreenOpen = false"
            >
              Tutup
            </Button>
          </div>

          <div class="flex min-h-0 flex-1 items-center justify-center p-4 sm:p-6">
            <img
              src="/storage/images/QRCODE.webp"
              alt="QRIS Payment Code Fullscreen"
              class="h-auto max-h-full w-full max-w-[560px] rounded-lg bg-white p-2 object-contain"
            />
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'
import type { PaymentMethod } from '@/types/pos'
import PaymentMethods from '../PaymentMethods.vue'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'

import { QrCode, CreditCard, Banknote, Wallet } from 'lucide-vue-next'

const props = defineProps<{
  modelValue: boolean
  total: number
  isProcessing: boolean
}>()

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'confirm': [{ method: PaymentMethod; cashReceived?: number }]
}>()

const cashInput = ref<HTMLInputElement | null>(null)
const localMethod = ref<PaymentMethod>('cash')
const localCashReceived = ref<number>(0)
const showQrisImage = ref(true)
const isQrFullscreenOpen = ref(false)

watch(
  () => props.modelValue,
  (open) => {
    if (open) {
      localCashReceived.value = 0
      nextTick(() => cashInput.value?.focus())
    } else {
      localCashReceived.value = 0
    }
  }
)

watch(localMethod, () => {
  localCashReceived.value = 0
})

const change = computed(() =>
  localMethod.value === 'cash' ? Math.max(0, localCashReceived.value - props.total) : 0
)

const cashPresets = computed(() => [100000, 200000, 300000, 400000])

const paymentMethodLabel = computed(() => {
  const labels: Record<PaymentMethod, string> = {
    cash: 'Cash',
    bank_transfer: 'bank transfer',
    qris: 'QRIS',
    e_wallet: 'e_Wallet'
  }
  return labels[localMethod.value]
})

function close() {
  emit('update:modelValue', false)
}

function emitUpdate(value: boolean) {
  emit('update:modelValue', value)
}

function setCashReceived(preset: number) {
  localCashReceived.value = preset
  nextTick(() => cashInput.value?.focus())
}

function selectAll(event: FocusEvent) {
  const target = event.target as HTMLInputElement
  target.select()
}

function confirmPayment() {
  if (props.total > 0 && localMethod.value === 'cash' && localCashReceived.value < props.total) {
    return
  }

  emit('confirm', {
    method: localMethod.value,
    cashReceived: localMethod.value === 'cash' ? localCashReceived.value : undefined,
  })
}

function formatPrice(price: number): string {
  if (typeof price !== 'number' || isNaN(price) || price === 0) {
    return 'Rp 0'
  }
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(price)
}

function formatCompactPrice(price: number): string {
  if (price >= 1000000) {
    return 'Rp ' + (price / 1000000).toFixed(1) + ' Jt'
  } else if (price >= 1000) {
    return 'Rp ' + (price / 1000).toFixed(0) + ' K'
  }
  return 'Rp ' + price
}
</script>
