<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  Printer,
  Download,
  ShoppingCart,
  DollarSign,
  Wallet,
  Package,
  Search,
  ChevronUp,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  Calendar,
  X
} from 'lucide-vue-next'
import type { TransactionReportProps, TransactionWithItems, PaymentMethod } from '@/types/pos'

import { Card, CardContent } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import {
  Table,
  TableHeader,
  TableBody,
  TableRow,
  TableHead,
  TableCell
} from '@/components/ui/table'

const props = defineProps<TransactionReportProps>()

// State
const searchQuery = ref('')
const paymentFilter = ref<PaymentMethod | 'all'>('all')
const expandedTransactions = ref<Set<string>>(new Set())
const isLoading = ref(false)
/** Dipakai agar jam di header ikut bergerak saat interval pembaruan. */
const clockTick = ref(0)

// Current date for navigation
const currentDate = ref(props.selectedDate)

// Payment method icons mapping (simple text labels instead of dynamic components)
const paymentMethodLabels: Record<PaymentMethod, string> = {
  cash: 'Tunai',
  e_wallet: 'E-Wallet',
  bank_transfer: 'Transfer',
  qris: 'QRIS',
}

// Format price to IDR
function formatPrice(amount: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount)
}

// Format time
function formatTime(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    timeZone: 'Asia/Jakarta',
  })
}

/** Jam seperti contoh cetak (10.30). */
function formatTimeDot(dateString: string): string {
  const parts = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: 'Asia/Jakarta',
  }).formatToParts(new Date(dateString))
  const h = parts.find(p => p.type === 'hour')?.value ?? '00'
  const m = parts.find(p => p.type === 'minute')?.value ?? '00'
  return `${h}.${m}`
}

/** YYYY-MM-DD di zona waktu lokal (hindari parse ISO UTC yang menggeser hari). */
function ymdFromDate(d: Date): string {
  const pad = (n: number) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

function parseYmdLocal(ymd: string): Date | null {
  const parts = ymd.split('-').map(Number)
  if (parts.length !== 3 || parts.some(n => Number.isNaN(n))) return null
  return new Date(parts[0], parts[1] - 1, parts[2], 12, 0, 0)
}

/**
 * Contoh: Sabtu, 9 Mei 2026 00:48 (24 jam, locale id-ID).
 */
function formatIdLongDateTime(d: Date): string {
  const datePart = d.toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    timeZone: 'Asia/Jakarta',
  })
  const timeParts = new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
    timeZone: 'Asia/Jakarta',
  }).formatToParts(d)
  const hh = timeParts.find(p => p.type === 'hour')?.value ?? '00'
  const mm = timeParts.find(p => p.type === 'minute')?.value ?? '00'
  return `${datePart} ${hh}:${mm}`
}

function formatIdLongDateFromYmd(ymd: string): string {
  const d = parseYmdLocal(ymd)
  if (!d) return ymd
  return d.toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
}

// Navigate date
function navigateDate(direction: 'prev' | 'next' | 'today') {
  if (direction === 'today') {
    currentDate.value = props.today
    refreshReport()
    return
  }

  const base = parseYmdLocal(currentDate.value)
  if (!base) return

  const next = new Date(base)
  if (direction === 'prev') {
    next.setDate(next.getDate() - 1)
  } else {
    next.setDate(next.getDate() + 1)
    const nextYmd = ymdFromDate(next)
    if (nextYmd > props.today) return
  }

  currentDate.value = ymdFromDate(next)
  refreshReport()
}

function onDatePickerChange(e: Event) {
  const v = (e.target as HTMLInputElement).value
  if (!v || v === currentDate.value) return
  currentDate.value = v
  refreshReport()
}

watch(
  () => props.selectedDate,
  v => {
    if (v && v !== currentDate.value) currentDate.value = v
  }
)

// Refresh report
function refreshReport() {
  expandedTransactions.value.clear()
  searchQuery.value = ''
  paymentFilter.value = 'all'
  isLoading.value = true
  router.get(
    '/pos/transactions/today',
    { date: currentDate.value },
    {
      preserveState: false,
      preserveScroll: true,
      onError: () => {
        isLoading.value = false
      },
      onFinish: () => {
        isLoading.value = false
      },
    }
  )
}

// Toggle transaction expansion
function toggleTransaction(transactionId: string) {
  if (expandedTransactions.value.has(transactionId)) {
    expandedTransactions.value.delete(transactionId)
  } else {
    expandedTransactions.value.add(transactionId)
  }
}

// Check if transaction is expanded
function isTransactionExpanded(transactionId: string): boolean {
  return expandedTransactions.value.has(transactionId)
}

// Calculate payment method percentage
function getPaymentMethodPercentage(method: PaymentMethod): number {
  const total = props.summary.total_sales
  if (total === 0) return 0
  const amount = props.summary.payment_methods[method]
  return (amount / total) * 100
}

// Get average transaction value
function getAverageTransactionValue(): number {
  if (props.summary.total_transactions === 0) return 0
  return props.summary.total_sales / props.summary.total_transactions
}

const isViewingToday = computed(() => currentDate.value === props.today)

const canGoNext = computed(() => currentDate.value < props.today)

/** Tanggal transaksi yang sedang ditampilkan (satu hari penuh, data tidak digabung antar hari). */
const reportSelectedDateLabel = computed(() => formatIdLongDateFromYmd(currentDate.value))

/** Waktu tampilan / pembaruan halaman (bukan tanggal transaksi). */
const displayClockLabel = computed(() => {
  void clockTick.value
  return `${formatIdLongDateTime(new Date())} WIB`
})

const statsDayHint = computed(() =>
  isViewingToday.value ? 'transaksi hari ini' : 'transaksi untuk tanggal ini saja'
)

// Computed: sorted transactions
const sortedTransactions = computed(() => {
  return [...props.transactions].sort((a, b) => {
    return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  })
})

// Computed: filtered transactions - FIXED: Now uses filteredTransactions in template
const filteredTransactions = computed(() => {
  return [...props.transactions]
    .filter(transaction => {
      const matchSearch = !searchQuery.value ||
        transaction.invoice_number?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
        transaction.cashier?.name?.toLowerCase().includes(searchQuery.value.toLowerCase())

      const matchPayment = paymentFilter.value === 'all' ||
        transaction.payment_method === paymentFilter.value

      return matchSearch && matchPayment
    })
    .sort((a, b) => {
      return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    })
})

// Computed: statistics calculations
const averageTransaction = computed(() => {
  if (props.summary.total_transactions === 0) return 0
  return Math.round(props.summary.total_sales / props.summary.total_transactions)
})

const cashTotal = computed(() => {
  return props.summary.payment_methods.cash || 0
})

const cashTransactionCount = computed(() => {
  return props.transactions.filter(t => t.payment_method === 'cash').length
})

const totalItems = computed(() => {
  return props.summary.total_items
})

const filteredTotal = computed(() => {
  return filteredTransactions.value.reduce((sum, t) => sum + (t.total || 0), 0)
})

const reportMetaTitle = computed(
  () => props.report_title ?? 'Laporan Penjualan Harian Vape Story'
)

const reportMetaAddress = computed(
  () =>
    props.store_address ??
    'Jl. Raya Kedawung No.02, Panembahan, Kec. Plered, Kabupaten Cirebon, Jawa Barat 45154'
)

/** Untuk header cetak: periode + waktu cetak dokumen. */
const reportPrintPeriodLine = computed(() => `Periode transaksi: ${reportSelectedDateLabel.value}`)

/** Diisi tepat saat Cetak agar jam tidak tertinggal satu render. */
const printSheetGeneratedLine = ref('')

interface PrintLineRow {
  time: string
  productName: string
  quantity: number
  sellingTotal: number
  paymentLabel: string
  hppTotal: number
  profit: number
}

/** Baris rinci untuk cetak harian — urut dari transaksi terawal seperti contoh fisik. */
const dailyPrintRows = computed<PrintLineRow[]>(() => {
  const sorted = [...filteredTransactions.value].sort(
    (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
  )
  const rows: PrintLineRow[] = []
  for (const t of sorted) {
    for (const item of t.items ?? []) {
      const hpp = item.hpp_total ?? 0
      const profit =
        item.profit !== undefined && item.profit !== null
          ? item.profit
          : Math.round((item.total ?? 0) - hpp)

      rows.push({
        time: formatTimeDot(t.created_at),
        productName: item.product?.name ?? '-',
        quantity: item.quantity,
        sellingTotal: item.total ?? 0,
        paymentLabel: paymentMethodLabels[t.payment_method] ?? String(t.payment_method),
        hppTotal: hpp,
        profit,
      })
    }
  }
  return rows
})

const dailyPrintTotals = computed(() => {
  const rows = dailyPrintRows.value
  let selling = 0
  let hpp = 0
  let profit = 0
  for (const r of rows) {
    selling += r.sellingTotal
    hpp += r.hppTotal
    profit += r.profit
  }
  return { selling, hpp, profit }
})

const cashierAccountLabel = computed(() => props.cashier?.name ?? props.cashier?.email ?? '-')

// Get status color
function getStatusColor(status: string): { bg: string; text: string } {
  switch (status) {
    case 'success':
      return { bg: 'bg-green-100', text: 'text-green-700' }
    case 'pending':
      return { bg: 'bg-amber-100', text: 'text-amber-700' }
    case 'failed':
      return { bg: 'bg-red-100', text: 'text-red-700' }
    default:
      return { bg: 'bg-gray-100', text: 'text-gray-700' }
  }
}

onMounted(() => {
  updateTime()
  const interval = setInterval(updateTime, 60000)

  onUnmounted(() => {
    clearInterval(interval)
  })
})

function updateTime() {
  clockTick.value++
}

async function handlePrint() {
  printSheetGeneratedLine.value = `Dicetak: ${formatIdLongDateTime(new Date())} WIB`
  document.body.dataset.reportPrint = 'true'
  await nextTick()
  window.print()
  window.setTimeout(() => {
    delete document.body.dataset.reportPrint
  }, 300)
}

function handleExport() {
  const headers = [
    'Tanggal',
    'Jam',
    'Invoice',
    'Metode',
    'Kasir',
    'Status',
    'Total',
    'Jumlah Item',
  ]

  const rows = filteredTransactions.value.map((transaction) => {
    const createdAt = new Date(transaction.created_at)
    return [
      createdAt.toLocaleDateString('id-ID'),
      createdAt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
      transaction.invoice_number ?? '-',
      paymentMethodLabels[transaction.payment_method] ?? transaction.payment_method,
      transaction.cashier?.name ?? '-',
      transaction.status,
      String(transaction.total || 0),
      String(transaction.items?.length ?? 0),
    ]
  })

  const csv = [headers, ...rows]
    .map((row) => row.map((col) => `"${String(col).replace(/"/g, '""')}"`).join(','))
    .join('\n')

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `laporan-penjualan-${currentDate.value}.csv`
  document.body.appendChild(a)
  a.click()
  document.body.removeChild(a)
  URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="pos-report-container h-full min-h-0 overflow-y-auto">
    <!-- UI layar -->
    <div class="screen-only space-y-0">
    <!-- Header Section -->
    <header class="pos-report-header">
      <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex-1 space-y-2">
          <h1 class="text-xl font-bold" style="color: var(--pos-text-inverse);">Laporan Penjualan Harian</h1>
          <p class="text-sm font-semibold" style="color: var(--pos-text-inverse);">
            Tanggal transaksi: {{ reportSelectedDateLabel }}
          </p>
          <p class="text-xs" style="color: var(--pos-brand-light);">
            Tampilan diperbarui: {{ displayClockLabel }}
          </p>
          <div class="flex flex-wrap items-center gap-2 pt-1">
            <Button
              type="button"
              variant="secondary"
              size="icon"
              class="h-9 w-9 shrink-0 border border-white/30 bg-white/15 text-white hover:bg-white/25"
              aria-label="Hari sebelumnya"
              @click="navigateDate('prev')"
            >
              <ChevronLeft class="h-4 w-4" />
            </Button>
            <div class="flex h-9 items-center gap-1.5 rounded-md border border-white/35 bg-white/95 px-2 text-gray-900 shadow-sm">
              <Calendar class="h-4 w-4 shrink-0 text-teal-700" />
              <input
                type="date"
                class="h-8 min-w-0 flex-1 bg-transparent text-sm outline-none"
                :value="currentDate"
                :max="props.today"
                aria-label="Pilih tanggal transaksi"
                @change="onDatePickerChange"
              >
            </div>
            <Button
              type="button"
              variant="secondary"
              size="icon"
              class="h-9 w-9 shrink-0 border border-white/30 bg-white/15 text-white hover:bg-white/25 disabled:opacity-40"
              aria-label="Hari berikutnya"
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
        <div class="flex shrink-0 flex-wrap items-center gap-3">
          <button
            type="button"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-all duration-300 hover:scale-105 hover:shadow-lg"
            style="background: var(--pos-brand-primary); color: var(--pos-text-inverse);"
            @click="handlePrint"
          >
            <Printer :size="16" />
            Cetak
          </button>
          <button
            type="button"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-all duration-300 hover:scale-105 hover:shadow-lg"
            style="background: var(--pos-brand-primary); color: var(--pos-text-inverse); border: 2px solid transparent;"
            @click="handleExport"
          >
            <Download :size="16" />
            Export
          </button>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-6 space-y-6">
      <!-- Statistics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Total Transactions -->
        <Card class="border-0 shadow-sm transition-all duration-300 hover:scale-105 hover:shadow-lg" style="background: var(--pos-bg-secondary);">
          <CardContent class="p-4 flex items-center gap-3">
            <div class="rounded-xl p-2.5" style="background: rgba(20, 184, 166, 0.1);">
              <ShoppingCart :size="20" style="color: var(--pos-brand-primary);" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium" style="color: var(--pos-text-muted);">Total Transaksi</p>
              <p class="text-lg font-bold truncate" style="color: var(--pos-text-primary);">{{ props.summary.total_transactions }}</p>
              <p class="text-xs" style="color: var(--pos-text-light);">{{ statsDayHint }}</p>
            </div>
          </CardContent>
        </Card>

        <!-- Total Sales -->
        <Card class="border-0 shadow-sm transition-all duration-300 hover:scale-105 hover:shadow-lg" style="background: var(--pos-bg-secondary);">
          <CardContent class="p-4 flex items-center gap-3">
            <div class="rounded-xl p-2.5" style="background: rgba(34, 197, 94, 0.1);">
              <DollarSign :size="20" style="color: var(--pos-success-text);" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium" style="color: var(--pos-text-muted);">Total Penjualan</p>
              <p class="text-lg font-bold truncate" style="color: var(--pos-text-primary);">{{ formatPrice(props.summary.total_sales) }}</p>
              <p class="text-xs" style="color: var(--pos-text-light);">Rata-rata {{ formatPrice(averageTransaction) }}/trx</p>
            </div>
          </CardContent>
        </Card>

        <!-- Cash Total -->
        <Card class="border-0 shadow-sm transition-all duration-300 hover:scale-105 hover:shadow-lg" style="background: var(--pos-bg-secondary);">
          <CardContent class="p-4 flex items-center gap-3">
            <div class="rounded-xl p-2.5" style="background: rgba(34, 197, 94, 0.1);">
              <Wallet :size="20" style="color: var(--pos-brand-primary);" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium" style="color: var(--pos-text-muted);">Tunai Masuk</p>
              <p class="text-lg font-bold truncate" style="color: var(--pos-text-primary);">{{ formatPrice(cashTotal) }}</p>
              <p class="text-xs" style="color: var(--pos-text-light);">{{ cashTransactionCount }} transaksi</p>
            </div>
          </CardContent>
        </Card>

        <!-- Items Sold -->
        <Card class="border-0 shadow-sm transition-all duration-300 hover:scale-105 hover:shadow-lg" style="background: var(--pos-bg-secondary);">
          <CardContent class="p-4 flex items-center gap-3">
            <div class="rounded-xl p-2.5" style="background: rgba(239, 68, 68, 0.1);">
              <Package :size="20" style="color: var(--pos-danger-text);" />
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium" style="color: var(--pos-text-muted);">Item Terjual</p>
              <p class="text-lg font-bold truncate" style="color: var(--pos-text-primary);">{{ totalItems }}</p>
              <p class="text-xs" style="color: var(--pos-text-light);">total item</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Transaction Table -->
      <Card class="border-0 shadow-sm" style="background: var(--pos-bg-secondary);">
        <div class="px-4 pt-4 pb-3">
          <div class="flex items-center justify-between flex-wrap p-5">
            <h2 class="text-sm font-semibold" style="color: var(--pos-text-primary);">Daftar Transaksi</h2>
            <div class="flex items-center gap-2">
              <!-- Search Input -->
              <div class="relative">
                <Search
                    :size="14"
                    class="absolute left-2.5 top-1/2 -translate-y-1/2"
                    style="color: var(--pos-text-light);"
                />
                <Input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Cari struk / kasir..."
                    class="h-10 border-0 pl-8 pr-3 text-sm transition-all duration-300 focus:ring-2 focus:ring-offset-2"
                    style="color: var(--pos-text-primary); width: 200px; background: var(--pos-brand-light); --tw-ring-color: var(--pos-brand-primary); --tw-ring-offset-color: var(--pos-brand-light);"
                />
                <button
                    v-if="searchQuery"
                    class="absolute right-2 top-1/2 -translate-y-1/2 transition-all duration-300"
                    style="color: var(--pos-text-light);"
                    @click="searchQuery = ''"
                >
                  <X class="h-3.5 w-3.5" />
                </button>
              </div>

              <!-- Payment Filter -->
              <select
                v-model="paymentFilter"
                class="h-10 text-sm font-medium transition-all duration-300 focus:ring-2 focus:ring-offset-2"
                style="color: var(--pos-text-secondary); background: var(--pos-brand-light); --tw-ring-color: var(--pos-brand-primary); --tw-ring-offset-color: var(--pos-brand-light);"
              >
                <option value="all">Semua</option>
                <option value="cash">Tunai</option>
                <option value="qris">QRIS</option>
                <option value="bank_transfer">Transfer</option>
                <option value="e_wallet">E-Wallet</option>
              </select>
            </div>
          </div>

          <!-- Table Content -->
          <div class="overflow-y-auto max-h-96">
            <Table>
              <TableHeader>
                <TableRow style="background: var(--pos-bg-secondary);">
                  <TableHead class="text-xs font-semibold px-3 py-2" style="color: var(--pos-text-muted);">Jam</TableHead>
                  <TableHead class="text-xs font-semibold px-3 py-2" style="color: var(--pos-text-muted);">No. Struk</TableHead>
                  <TableHead class="text-xs font-semibold px-3 py-2" style="color: var(--pos-text-muted);">Pembayaran</TableHead>
                  <TableHead class="text-xs font-semibold px-3 py-2" style="color: var(--pos-text-muted);">Kasir</TableHead>
                  <TableHead class="text-xs font-semibold px-3 py-2 text-right" style="color: var(--pos-text-muted);">Jumlah</TableHead>
                  <TableHead class="text-xs font-semibold px-3 py-2 text-center" style="color: var(--pos-text-muted);">Status</TableHead>
                  <TableHead class="text-xs font-semibold px-1 py-2 w-8" />
                </TableRow>
              </TableHeader>
              <TableBody>
                <!-- Empty state -->
                <TableRow v-if="filteredTransactions.length === 0">
                  <TableCell colspan="7" class="text-center py-8 text-xs" style="color: var(--pos-text-light);">Tidak ada transaksi ditemukan</TableCell>
                </TableRow>

                <!-- Transaction rows - FIXED: Use filteredTransactions instead of sortedTransactions -->
                <template v-for="transaction in filteredTransactions" :key="transaction.id">
                  <!-- Main row -->
                  <TableRow
                    class="cursor-pointer transition-all duration-300 hover:bg-teal-500/5"
                    :class="{ 'border-b-0': isTransactionExpanded(transaction.id) }"
                    style="border-color: var(--pos-border);"
                    @click="toggleTransaction(transaction.id)"
                  >
                    <TableCell class="py-3 px-3">
                      <span class="text-xs font-medium" style="color: var(--pos-text-muted);">{{ formatTime(transaction.created_at) }}</span>
                    </TableCell>
                    <TableCell class="py-3 px-3">
                      <span class="text-xs font-mono font-semibold" style="color: var(--pos-text-primary);">{{ transaction.invoice_number }}</span>
                    </TableCell>
                    <TableCell class="py-3 px-3">
                      <div class="flex items-center gap-1.5">
                        <!-- FIXED: Remove dynamic component, use simple text label -->
                        <span class="text-xs" style="color: var(--pos-text-primary);">{{ paymentMethodLabels[transaction.payment_method] }}</span>
                      </div>
                    </TableCell>
                    <TableCell class="py-3 px-3">
                      <span class="text-xs" style="color: var(--pos-text-muted);">{{ transaction.cashier?.name || '-' }}</span>
                    </TableCell>
                    <TableCell class="py-3 px-3 text-right">
                      <span class="text-xs font-bold" style="color: var(--pos-text-primary);">{{ formatPrice(transaction.total || 0) }}</span>
                    </TableCell>
                    <TableCell class="py-3 px-3 text-center">
                      <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        :class="[getStatusColor(transaction.status).bg, getStatusColor(transaction.status).text]"
                      >
                        {{ transaction.status }}
                      </span>
                    </TableCell>
                    <TableCell class="py-3 px-1 text-center">
                      <component
                        :is="isTransactionExpanded(transaction.id) ? ChevronUp : ChevronDown"
                        :size="14"
                        class="text-[var(--pos-text-light)]"
                      />
                    </TableCell>
                  </TableRow>

                  <!-- Expanded details -->
                  <TableRow v-if="isTransactionExpanded(transaction.id)" class="bg-[var(--pos-brand-light)]">
                    <TableCell colspan="7" class="px-4 py-3">
                      <div class="space-y-1.5 max-h-64 overflow-y-auto">
                        <div
                          v-for="item in transaction.items"
                          :key="item.id"
                          class="grid grid-cols-3 gap-2 text-xs pb-1.5 border-b border-[var(--pos-border)]"
                          style="color: var(--pos-text-primary);"
                        >
                          <div class="col-span-2 min-w-0">
                            <div class="flex gap-1 items-start">
                              <span class="shrink-0 font-medium">{{ item.quantity }}x</span>
                              <span class="truncate">{{ item.product?.name || 'Unknown' }}</span>
                            </div>
                          </div>
                          <div class="text-right font-medium shrink-0">{{ formatPrice(item.total || 0) }}</div>
                        </div>
                        <div class="pt-2 border-t-2 border-[var(--pos-border)] flex justify-end">
                          <span class="text-xs font-bold" style="color: var(--pos-brand-primary);">Total: {{ formatPrice(transaction.total || 0) }}</span>
                        </div>
                      </div>
                    </TableCell>
                  </TableRow>
                </template>
              </TableBody>
            </Table>
          </div>

          <!-- Table Footer -->
          <div class="px-4 py-3 flex justify-between items-center border-t" style="border-color: var(--pos-border);">
            <span class="text-xs" style="color: var(--pos-text-light);">Menampilkan {{ filteredTransactions.length }} dari {{ props.transactions.length }} transaksi</span>
            <span class="text-xs font-bold" style="color: var(--pos-brand-primary);">Total: {{ formatPrice(filteredTotal) }}</span>
          </div>
          </div>
        </Card>
    </main>
    </div>

    <!-- Cetak: format tabel seperti contoh fisik -->
    <div class="daily-report-print-sheet print-only-sheet text-black">
      <div class="print-header-center mb-6 text-center leading-relaxed">
        <h1 class="text-base font-bold uppercase tracking-wide">
          {{ reportMetaTitle }}
        </h1>
        <p class="text-sm font-bold">{{ reportPrintPeriodLine }}</p>
        <p class="text-xs font-medium">{{ printSheetGeneratedLine }}</p>
        <p class="max-w-xl mx-auto text-xs normal-case">{{ reportMetaAddress }}</p>
        <p class="text-sm font-semibold">Kasir: {{ cashierAccountLabel }}</p>
      </div>

      <table class="daily-print-table w-full border-collapse text-center text-xs">
        <thead>
          <tr>
            <th class="border border-black px-2 py-1.5 font-bold">Waktu</th>
            <th class="border border-black px-2 py-1.5 font-bold">Barang</th>
            <th class="border border-black px-2 py-1.5 font-bold">Jumlah</th>
            <th class="border border-black px-2 py-1.5 font-bold">Harga jual</th>
            <th class="border border-black px-2 py-1.5 font-bold">Pembayaran</th>
            <th class="border border-black px-2 py-1.5 font-bold">HPP</th>
            <th class="border border-black px-2 py-1.5 font-bold">Untung</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="dailyPrintRows.length === 0">
            <td colspan="7" class="border border-black px-2 py-8 text-gray-600">
              Tidak ada penjualan pada tanggal ini
            </td>
          </tr>
          <tr v-for="(row, idx) in dailyPrintRows" :key="idx">
            <td class="border border-black px-2 py-1">{{ row.time }}</td>
            <td class="border border-black px-2 py-1 text-left">{{ row.productName }}</td>
            <td class="border border-black px-2 py-1">{{ row.quantity }}</td>
            <td class="border border-black px-2 py-1">{{ formatPrice(row.sellingTotal) }}</td>
            <td class="border border-black px-2 py-1">{{ row.paymentLabel }}</td>
            <td class="border border-black px-2 py-1">{{ formatPrice(row.hppTotal) }}</td>
            <td class="border border-black px-2 py-1">{{ formatPrice(row.profit) }}</td>
          </tr>
        </tbody>
        <tfoot v-if="dailyPrintRows.length > 0">
          <tr>
            <td colspan="3" class="border border-black px-2 py-2 font-bold uppercase">
              TOTAL PENJUALAN
            </td>
            <td class="border border-black px-2 py-2 font-bold">{{ formatPrice(dailyPrintTotals.selling) }}</td>
            <td class="border border-black px-2 py-2">&nbsp;</td>
            <td class="border border-black px-2 py-2 font-bold">{{ formatPrice(dailyPrintTotals.hpp) }}</td>
            <td class="border border-black px-2 py-2 font-bold">{{ formatPrice(dailyPrintTotals.profit) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>

<style scoped>
.pos-report-container {
  min-height: 100vh;
  background: var(--pos-bg-secondary);
}

.pos-report-header {
  background: linear-gradient(135deg, var(--pos-brand-primary) 0%, var(--pos-brand-light) 100%);
}

/* Custom scrollbar for table */
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

:deep(.overflow-y-auto)::-webkit-scrollbar-thumb:hover {
  background: var(--pos-brand-hover);
  box-shadow: 0 0 0 2px rgba(20, 184, 166, 0.3);
}

/* Focus styles */
input:focus,
select:focus {
  outline: 2px solid var(--pos-brand-primary);
  outline-offset: 2px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .pos-report-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .grid {
    grid-template-columns: 1fr;
  }
}

@media print {
  body[data-report-print='true'] .screen-only {
    display: none !important;
  }

  body[data-report-print='true'] .print-only-sheet {
    display: block !important;
  }

  body[data-report-print='true'] .pos-report-container {
    min-height: 0 !important;
    overflow: visible !important;
    background: #fff !important;
  }

  body[data-report-print='true'] .daily-report-print-sheet {
    padding: 1rem;
    font-family: ui-sans-serif, system-ui, sans-serif;
  }

}

/* Layar: sembunyikan lembar cetak */
.print-only-sheet {
  display: none;
}
</style>

<style>
/* Elemen layout/dev di luar halaman ini — sembunyikan saat cetak laporan */
@media print {
  body[data-report-print='true'] .pos__header,
  body[data-report-print='true'] [data-sidebar='sidebar'],
  body[data-report-print='true'] .toaster.group,
  body[data-report-print='true'] #phpdebugbar,
  body[data-report-print='true'] .phpdebugbar,
  body[data-report-print='true'] .phpdebugbar-openhandler {
    display: none !important;
  }

  body[data-report-print='true'] .pos-layout,
  body[data-report-print='true'] [data-slot='sidebar-inset'] {
    overflow: visible !important;
    height: auto !important;
    min-height: 0 !important;
  }

  @page {
    margin: 12mm;
  }
}
</style>
