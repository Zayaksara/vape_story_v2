<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
  Search,
  X,
  Calendar,
  ChevronLeft,
  ChevronRight,
  Undo2,
  Receipt,
  ShoppingBag,
  AlertCircle,
  CheckCircle2,
} from 'lucide-vue-next'

import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell,
} from '@/components/ui/table'

interface SaleItem {
  id: number
  product_id: string
  product_name: string
  product_code: string | null
  quantity: number
  unit_price: number
  total: number
}

interface SaleRow {
  id: number
  invoice_number: string
  status: string
  total_amount: number
  payment_method: string
  created_at: string
  has_return: boolean
  cashier: { id: string; name: string } | null
  items: SaleItem[]
}

interface ReturnRow {
  id: string
  return_number: string
  sale_id: number | null
  invoice_number: string
  reason: string
  notes: string | null
  status: string
  created_at: string
  cashier_name: string
  items: { id: string; product_name: string; quantity: number; unit_price: number; subtotal: number }[]
  total: number
}

const props = defineProps<{
  sales: SaleRow[]
  returns: ReturnRow[]
  selectedDate: string
  today: string
}>()

const searchQuery = ref('')
const currentDate = ref(props.selectedDate)
const selectedSaleId = ref<number | null>(null)
const returnQty = ref<Record<number, number>>({})
const flashMessage = ref<string>('')
const flashType = ref<'success' | 'error'>('success')

const form = useForm({
  sale_id: 0,
  reason: '',
  notes: '',
  items: [] as { sale_item_id: number; quantity: number }[],
})

const paymentMethodLabels: Record<string, string> = {
  cash: 'Tunai',
  e_wallet: 'E-Wallet',
  bank_transfer: 'Transfer',
  qris: 'QRIS',
}

function formatPrice(amount: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount || 0)
}

function formatTime(dateString: string): string {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatDateTime(dateString: string): string {
  if (!dateString) return '-'
  const d = new Date(dateString)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) + ' ' + formatTime(dateString)
}

const filteredSales = computed(() => {
  const q = searchQuery.value.trim().toLowerCase()
  return props.sales
    .filter((s) => s.status === 'completed' || s.status === 'partial_return')
    .filter((s) =>
      !q
        ? true
        : s.invoice_number.toLowerCase().includes(q) ||
          (s.cashier?.name ?? '').toLowerCase().includes(q),
    )
})

const selectedSale = computed<SaleRow | null>(() => {
  if (selectedSaleId.value == null) return null
  return props.sales.find((s) => s.id === selectedSaleId.value) ?? null
})

const returnTotal = computed(() => {
  if (!selectedSale.value) return 0
  return selectedSale.value.items.reduce((sum, item) => {
    const q = returnQty.value[item.id] ?? 0
    return sum + q * item.unit_price
  }, 0)
})

const hasReturnItems = computed(() =>
  Object.values(returnQty.value).some((q) => (q ?? 0) > 0),
)

function selectSale(sale: SaleRow) {
  if (sale.has_return) {
    flashType.value = 'error'
    flashMessage.value = 'Transaksi ini sudah pernah di-return.'
    return
  }
  selectedSaleId.value = sale.id
  returnQty.value = {}
  for (const item of sale.items) {
    returnQty.value[item.id] = 0
  }
  form.reason = ''
  form.notes = ''
  flashMessage.value = ''
}

function clearSelection() {
  selectedSaleId.value = null
  returnQty.value = {}
  form.reason = ''
  form.notes = ''
}

function adjustQty(itemId: number, delta: number, max: number) {
  const current = returnQty.value[itemId] ?? 0
  const next = Math.max(0, Math.min(max, current + delta))
  returnQty.value[itemId] = next
}

function setQty(itemId: number, value: number | string, max: number) {
  const n = Math.max(0, Math.min(max, Number(value) || 0))
  returnQty.value[itemId] = n
}

function navigateDate(dir: 'prev' | 'next' | 'today') {
  if (dir === 'today') {
    currentDate.value = props.today
    refresh()
    return
  }
  const [y, m, d] = currentDate.value.split('-').map(Number)
  const base = new Date(y, m - 1, d, 12)
  base.setDate(base.getDate() + (dir === 'next' ? 1 : -1))
  const pad = (n: number) => String(n).padStart(2, '0')
  const next = `${base.getFullYear()}-${pad(base.getMonth() + 1)}-${pad(base.getDate())}`
  if (dir === 'next' && next > props.today) return
  currentDate.value = next
  refresh()
}

function onDateChange(e: Event) {
  const v = (e.target as HTMLInputElement).value
  if (!v) return
  currentDate.value = v
  refresh()
}

function refresh() {
  router.get('/pos/returns', { date: currentDate.value }, {
    preserveScroll: true,
    preserveState: false,
  })
}

function submitReturn() {
  if (!selectedSale.value) return
  if (!form.reason.trim()) {
    flashType.value = 'error'
    flashMessage.value = 'Alasan return wajib diisi.'
    return
  }
  const items = Object.entries(returnQty.value)
    .filter(([, q]) => (q ?? 0) > 0)
    .map(([id, q]) => ({ sale_item_id: Number(id), quantity: Number(q) }))

  if (items.length === 0) {
    flashType.value = 'error'
    flashMessage.value = 'Pilih minimal 1 item dengan jumlah > 0.'
    return
  }

  form.sale_id = selectedSale.value.id
  form.items = items

  form.post('/pos/returns', {
    preserveScroll: true,
    onSuccess: () => {
      flashType.value = 'success'
      flashMessage.value = 'Return berhasil diproses. Stok dikembalikan.'
      clearSelection()
    },
    onError: (errors) => {
      flashType.value = 'error'
      flashMessage.value = Object.values(errors).flat().join(' ') || 'Gagal memproses return.'
    },
  })
}

const isViewingToday = computed(() => currentDate.value === props.today)
const canGoNext = computed(() => currentDate.value < props.today)

function selectedDateLabel(): string {
  const [y, m, d] = currentDate.value.split('-').map(Number)
  const date = new Date(y, m - 1, d, 12)
  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}
</script>

<template>
  <div class="pos-return-container h-full min-h-0 overflow-y-auto">
    <!-- Header -->
    <header class="pos-return-header">
      <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex-1 space-y-2">
          <h1 class="text-xl font-bold flex items-center gap-2" style="color: var(--pos-text-inverse);">
            <Undo2 class="h-5 w-5" />
            Return Transaksi
          </h1>
          <p class="text-sm font-semibold" style="color: var(--pos-text-inverse);">
            Tanggal: {{ selectedDateLabel() }}
          </p>
          <p class="text-xs" style="color: var(--pos-brand-light);">
            Pilih transaksi → tentukan jumlah item yang dikembalikan → submit. Stok otomatis dikembalikan.
          </p>
          <div class="flex flex-wrap items-center gap-2 pt-1">
            <Button
              type="button"
              variant="secondary"
              size="icon"
              class="h-9 w-9 border border-white/30 bg-white/15 text-white hover:bg-white/25"
              @click="navigateDate('prev')"
            >
              <ChevronLeft class="h-4 w-4" />
            </Button>
            <div class="flex h-9 items-center gap-1.5 rounded-md border border-white/35 bg-white/95 px-2 text-gray-900 shadow-sm">
              <Calendar class="h-4 w-4 text-teal-700" />
              <input
                type="date"
                class="h-8 min-w-0 flex-1 bg-transparent text-sm outline-none"
                :value="currentDate"
                :max="props.today"
                @change="onDateChange"
              >
            </div>
            <Button
              type="button"
              variant="secondary"
              size="icon"
              class="h-9 w-9 border border-white/30 bg-white/15 text-white hover:bg-white/25 disabled:opacity-40"
              :disabled="!canGoNext"
              @click="navigateDate('next')"
            >
              <ChevronRight class="h-4 w-4" />
            </Button>
            <Button
              v-if="!isViewingToday"
              type="button"
              variant="secondary"
              class="h-9 border border-white/30 bg-white/15 px-3 text-xs font-medium text-white hover:bg-white/25"
              @click="navigateDate('today')"
            >
              Hari ini
            </Button>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-6 py-6 space-y-6">
      <!-- Flash -->
      <div
        v-if="flashMessage"
        class="flex items-center gap-2 rounded-md px-4 py-3 text-sm font-medium shadow-sm"
        :style="flashType === 'success'
          ? 'background: var(--pos-success-bg); color: var(--pos-success-text);'
          : 'background: var(--pos-danger-bg); color: var(--pos-danger-text);'"
      >
        <CheckCircle2 v-if="flashType === 'success'" class="h-4 w-4" />
        <AlertCircle v-else class="h-4 w-4" />
        <span class="flex-1">{{ flashMessage }}</span>
        <button class="opacity-60 hover:opacity-100" @click="flashMessage = ''"><X class="h-4 w-4" /></button>
      </div>

      <!-- 2-column: sales list + return form -->
      <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">
        <!-- Sales list -->
        <Card class="lg:col-span-2 border-0 shadow-sm" style="background: var(--pos-bg-primary);">
          <CardContent class="p-4 space-y-3">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-semibold flex items-center gap-1.5" style="color: var(--pos-text-primary);">
                <Receipt class="h-4 w-4" /> Daftar Transaksi
              </h2>
              <span class="text-xs" style="color: var(--pos-text-light);">{{ filteredSales.length }} transaksi</span>
            </div>

            <div class="relative">
              <Search :size="14" class="absolute left-2.5 top-1/2 -translate-y-1/2" style="color: var(--pos-text-light);" />
              <Input
                v-model="searchQuery"
                placeholder="Cari nomor struk / kasir..."
                class="h-9 border-0 pl-8 pr-3 text-sm"
                style="background: var(--pos-brand-light); color: var(--pos-text-primary);"
              />
            </div>

            <div class="space-y-2 max-h-[480px] overflow-y-auto pr-1">
              <div
                v-if="filteredSales.length === 0"
                class="text-center py-10 text-xs"
                style="color: var(--pos-text-light);"
              >
                Tidak ada transaksi.
              </div>

              <button
                v-for="sale in filteredSales"
                :key="sale.id"
                type="button"
                class="w-full text-left rounded-md border px-3 py-2.5 transition-all"
                :class="selectedSaleId === sale.id ? 'shadow' : 'hover:shadow-sm'"
                :style="selectedSaleId === sale.id
                  ? 'background: var(--pos-brand-light); border-color: var(--pos-brand-primary);'
                  : 'background: var(--pos-bg-secondary); border-color: var(--pos-border);'"
                :disabled="sale.has_return"
                @click="selectSale(sale)"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-mono font-semibold" style="color: var(--pos-text-primary);">
                    {{ sale.invoice_number }}
                  </span>
                  <span
                    v-if="sale.has_return"
                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                    style="background: var(--pos-warning-bg); color: var(--pos-warning-text);"
                  >Returned</span>
                  <span
                    v-else-if="sale.status === 'partial_return'"
                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                    style="background: var(--pos-warning-bg); color: var(--pos-warning-text);"
                  >Partial</span>
                  <span
                    v-else
                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                    style="background: var(--pos-success-bg); color: var(--pos-success-text);"
                  >OK</span>
                </div>
                <div class="mt-1 flex items-center justify-between text-xs" style="color: var(--pos-text-muted);">
                  <span>{{ formatTime(sale.created_at) }} · {{ paymentMethodLabels[sale.payment_method] ?? sale.payment_method }}</span>
                  <span class="font-bold" style="color: var(--pos-text-primary);">{{ formatPrice(sale.total_amount) }}</span>
                </div>
                <div class="mt-1 text-[11px]" style="color: var(--pos-text-light);">
                  Kasir: {{ sale.cashier?.name ?? '-' }} · {{ sale.items.length }} item
                </div>
              </button>
            </div>
          </CardContent>
        </Card>

        <!-- Return form -->
        <Card class="lg:col-span-3 border-0 shadow-sm" style="background: var(--pos-bg-primary);">
          <CardContent class="p-4 space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-semibold flex items-center gap-1.5" style="color: var(--pos-text-primary);">
                <ShoppingBag class="h-4 w-4" /> Form Return
              </h2>
              <button
                v-if="selectedSale"
                class="text-xs hover:underline"
                style="color: var(--pos-danger-text);"
                @click="clearSelection"
              >
                Batal
              </button>
            </div>

            <div
              v-if="!selectedSale"
              class="rounded-md border-2 border-dashed py-12 text-center text-sm"
              :style="'border-color: var(--pos-border); color: var(--pos-text-light);'"
            >
              Pilih transaksi di sebelah kiri untuk memulai return.
            </div>

            <div v-else class="space-y-4">
              <!-- Sale summary -->
              <div class="rounded-md p-3 text-xs" style="background: var(--pos-bg-secondary);">
                <div class="flex items-center justify-between">
                  <span class="font-mono font-semibold" style="color: var(--pos-text-primary);">
                    {{ selectedSale.invoice_number }}
                  </span>
                  <span style="color: var(--pos-text-muted);">{{ formatDateTime(selectedSale.created_at) }}</span>
                </div>
                <div class="mt-1 flex items-center justify-between" style="color: var(--pos-text-muted);">
                  <span>Kasir: {{ selectedSale.cashier?.name ?? '-' }}</span>
                  <span>{{ paymentMethodLabels[selectedSale.payment_method] ?? selectedSale.payment_method }}</span>
                </div>
              </div>

              <!-- Items table -->
              <div class="overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead class="text-xs" style="color: var(--pos-text-muted);">Produk</TableHead>
                      <TableHead class="text-xs text-right" style="color: var(--pos-text-muted);">Harga</TableHead>
                      <TableHead class="text-xs text-center" style="color: var(--pos-text-muted);">Beli</TableHead>
                      <TableHead class="text-xs text-center" style="color: var(--pos-text-muted);">Return</TableHead>
                      <TableHead class="text-xs text-right" style="color: var(--pos-text-muted);">Subtotal</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    <TableRow v-for="item in selectedSale.items" :key="item.id">
                      <TableCell class="py-2">
                        <div class="text-xs font-medium" style="color: var(--pos-text-primary);">{{ item.product_name }}</div>
                        <div v-if="item.product_code" class="text-[10px]" style="color: var(--pos-text-light);">
                          {{ item.product_code }}
                        </div>
                      </TableCell>
                      <TableCell class="py-2 text-right text-xs" style="color: var(--pos-text-secondary);">
                        {{ formatPrice(item.unit_price) }}
                      </TableCell>
                      <TableCell class="py-2 text-center text-xs" style="color: var(--pos-text-secondary);">
                        {{ item.quantity }}
                      </TableCell>
                      <TableCell class="py-2">
                        <div class="flex items-center justify-center gap-1">
                          <button
                            type="button"
                            class="h-7 w-7 rounded border text-sm"
                            :style="'border-color: var(--pos-border); color: var(--pos-text-secondary);'"
                            @click="adjustQty(item.id, -1, item.quantity)"
                          >−</button>
                          <input
                            type="number"
                            min="0"
                            :max="item.quantity"
                            :value="returnQty[item.id] ?? 0"
                            class="h-7 w-12 rounded border text-center text-xs"
                            :style="'border-color: var(--pos-border); color: var(--pos-text-primary);'"
                            @input="setQty(item.id, ($event.target as HTMLInputElement).value, item.quantity)"
                          >
                          <button
                            type="button"
                            class="h-7 w-7 rounded border text-sm"
                            :style="'border-color: var(--pos-border); color: var(--pos-text-secondary);'"
                            @click="adjustQty(item.id, 1, item.quantity)"
                          >+</button>
                        </div>
                      </TableCell>
                      <TableCell class="py-2 text-right text-xs font-semibold" style="color: var(--pos-text-primary);">
                        {{ formatPrice((returnQty[item.id] ?? 0) * item.unit_price) }}
                      </TableCell>
                    </TableRow>
                  </TableBody>
                </Table>
              </div>

              <!-- Reason + notes -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">
                    Alasan Return <span style="color: var(--pos-danger-text);">*</span>
                  </label>
                  <select
                    v-model="form.reason"
                    class="mt-1 h-9 w-full rounded-md border px-2 text-sm"
                    :style="'border-color: var(--pos-border); background: var(--pos-bg-primary); color: var(--pos-text-primary);'"
                  >
                    <option value="">Pilih alasan...</option>
                    <option value="Barang rusak">Barang rusak</option>
                    <option value="Barang cacat produksi">Barang cacat produksi</option>
                    <option value="Salah produk">Salah produk</option>
                    <option value="Customer berubah pikiran">Customer berubah pikiran</option>
                    <option value="Lainnya">Lainnya</option>
                  </select>
                </div>
                <div>
                  <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Catatan (opsional)</label>
                  <Input
                    v-model="form.notes"
                    placeholder="Detail tambahan..."
                    class="mt-1 h-9 text-sm"
                  />
                </div>
              </div>

              <!-- Summary + submit -->
              <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between rounded-md p-3"
                   style="background: var(--pos-bg-secondary);">
                <div>
                  <p class="text-xs" style="color: var(--pos-text-muted);">Total Return</p>
                  <p class="text-lg font-bold" style="color: var(--pos-brand-primary);">
                    {{ formatPrice(returnTotal) }}
                  </p>
                </div>
                <Button
                  type="button"
                  class="h-10 px-6 text-sm font-semibold"
                  :disabled="!hasReturnItems || form.processing"
                  :style="hasReturnItems
                    ? 'background: var(--pos-brand-primary); color: var(--pos-text-inverse);'
                    : 'background: var(--pos-border); color: var(--pos-text-muted); cursor: not-allowed;'"
                  @click="submitReturn"
                >
                  <Undo2 class="mr-1.5 h-4 w-4" />
                  {{ form.processing ? 'Memproses...' : 'Proses Return' }}
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Riwayat return -->
      <Card class="border-0 shadow-sm" style="background: var(--pos-bg-primary);">
        <CardContent class="p-4">
          <div class="flex items-center justify-between mb-3">
            <h2 class="text-sm font-semibold" style="color: var(--pos-text-primary);">Riwayat Return</h2>
            <span class="text-xs" style="color: var(--pos-text-light);">{{ props.returns.length }} catatan</span>
          </div>

          <div class="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead class="text-xs" style="color: var(--pos-text-muted);">No. Return</TableHead>
                  <TableHead class="text-xs" style="color: var(--pos-text-muted);">Tanggal</TableHead>
                  <TableHead class="text-xs" style="color: var(--pos-text-muted);">Transaksi</TableHead>
                  <TableHead class="text-xs" style="color: var(--pos-text-muted);">Alasan</TableHead>
                  <TableHead class="text-xs" style="color: var(--pos-text-muted);">Kasir</TableHead>
                  <TableHead class="text-xs text-right" style="color: var(--pos-text-muted);">Total</TableHead>
                  <TableHead class="text-xs text-center" style="color: var(--pos-text-muted);">Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                <TableRow v-if="props.returns.length === 0">
                  <TableCell colspan="7" class="text-center py-8 text-xs" style="color: var(--pos-text-light);">
                    Belum ada riwayat return.
                  </TableCell>
                </TableRow>
                <TableRow v-for="r in props.returns" :key="r.id">
                  <TableCell class="py-2 text-xs font-mono font-semibold" style="color: var(--pos-text-primary);">
                    {{ r.return_number }}
                  </TableCell>
                  <TableCell class="py-2 text-xs" style="color: var(--pos-text-muted);">
                    {{ formatDateTime(r.created_at) }}
                  </TableCell>
                  <TableCell class="py-2 text-xs font-mono" style="color: var(--pos-text-secondary);">
                    {{ r.invoice_number }}
                  </TableCell>
                  <TableCell class="py-2 text-xs" style="color: var(--pos-text-secondary);">
                    {{ r.reason }}
                  </TableCell>
                  <TableCell class="py-2 text-xs" style="color: var(--pos-text-muted);">
                    {{ r.cashier_name }}
                  </TableCell>
                  <TableCell class="py-2 text-xs text-right font-semibold" style="color: var(--pos-brand-primary);">
                    {{ formatPrice(r.total) }}
                  </TableCell>
                  <TableCell class="py-2 text-center">
                    <span
                      class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                      style="background: var(--pos-success-bg); color: var(--pos-success-text);"
                    >
                      {{ r.status }}
                    </span>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>
    </main>
  </div>
</template>

<style scoped>
.pos-return-container {
  min-height: 100vh;
  background: var(--pos-bg-secondary);
}

.pos-return-header {
  background: linear-gradient(135deg, var(--pos-brand-primary) 0%, var(--pos-brand-light) 100%);
}

:deep(.overflow-y-auto)::-webkit-scrollbar {
  width: 6px;
}
:deep(.overflow-y-auto)::-webkit-scrollbar-track {
  background: var(--pos-brand-light);
  border-radius: 3px;
}
:deep(.overflow-y-auto)::-webkit-scrollbar-thumb {
  background: var(--pos-brand-primary);
  border-radius: 3px;
}

input:focus,
select:focus {
  outline: 2px solid var(--pos-brand-primary);
  outline-offset: 2px;
}
</style>
