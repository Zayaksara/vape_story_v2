<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
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
  ChevronDown
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
const currentTime = ref('')

// Current date for navigation
const currentDate = ref(props.selectedDate)

// Payment method icons
const paymentMethodIcons: Record<PaymentMethod, any> = {
  cash: 'cash',
  e_wallet: 'e_wallet',
  bank_transfer: 'bank_transfer',
  qris: 'qris',
}

// Payment method labels
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
  })
}

// Format date for display
function formatDateDisplay(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleDateString('id-ID', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

// Navigate date
function navigateDate(direction: 'prev' | 'next' | 'today') {
  const date = new Date(currentDate.value)

  if (direction === 'prev') {
    date.setDate(date.getDate() - 1)
  } else if (direction === 'next') {
    date.setDate(date.getDate() + 1)
  } else if (direction === 'today') {
    currentDate.value = props.today
    refreshReport()
    return
  }

  currentDate.value = date.toISOString().split('T')[0]
  refreshReport()
}

// Refresh report
function refreshReport() {
  isLoading.value = true
  router.get(
    '/pos/reports/today-transaction',
    { date: currentDate.value },
    {
      preserveState: true,
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

// Computed: formatted date/time
const formattedDateTime = computed(() => {
  const now = new Date()
  const dateStr = formatDateDisplay(now.toISOString())
  const timeStr = now.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  })
  return `${dateStr} • ${timeStr}`
})

// Computed: sorted transactions
const sortedTransactions = computed(() => {
  return [...props.transactions].sort((a, b) => {
    return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  })
})

// Computed: filtered transactions
const filteredTransactions = computed(() => {
  return props.transactions.filter(transaction => {
    const matchSearch = !searchQuery.value ||
      transaction.invoice_number?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      transaction.cashier?.name?.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchPayment = paymentFilter.value === 'all' ||
      transaction.payment_method === paymentFilter.value

    return matchSearch && matchPayment
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
  return filteredTransactions.value.reduce((sum, t) => sum + (t.tax_amount || 0), 0)
})

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
  const now = new Date()
  const timeStr = now.toLocaleTimeString('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  })
  currentTime.value = timeStr
}

function handlePrint() {
  window.print()
}

function handleExport() {
  // TODO: Implement export functionality
  console.log('Export functionality to be implemented')
}
</script>

<template>
  <div class="pos-report-container">
    <!-- Header Section -->
    <header class="pos-report-header">
      <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold" style="color: var(--pos-text-inverse);">Laporan Penjualan Hari Ini</h1>
          <p class="text-sm" style="color: var(--pos-brand-light);">{{ formattedDateTime }} WIB</p>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="handlePrint"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-all duration-300 hover:scale-105 hover:shadow-lg"
            style="background: var(--pos-brand-primary); color: var(--pos-text-inverse);"
          >
            <Printer :size="16" />
            Cetak
          </button>
          <button
            @click="handleExport"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium transition-all duration-300 hover:scale-105 hover:shadow-lg"
            style="background: var(--pos-brand-primary); color: var(--pos-text-inverse); border: 2px solid transparent;"
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
              <p class="text-xs" style="color: var(--pos-text-light);">transaksi hari ini</p>
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
          <div class="flex items-center justify-between flex-wrap gap-2">
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
                  <ChevronRight class="h-3.5 w-3.5" />
                </button>
              </div>

              <!-- Payment Filter -->
              <select
                v-model="paymentFilter"
                class="h-10 border text-sm font-medium transition-all duration-300 focus:ring-2 focus:ring-offset-2"
                style="color: var(--pos-text-primary); background: var(--pos-brand-light); --tw-ring-color: var(--pos-brand-primary); --tw-ring-offset-color: var(--pos-brand-light);"
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

                <!-- Transaction rows -->
                <template v-for="transaction in sortedTransactions" :key="transaction.id">
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
                        <component
                          :is="paymentMethodIcons[transaction.payment_method]"
                          :size="13"
                          class="text-[var(--pos-text-muted)]"
                        />
                        <span class="text-xs" style="color: var(--pos-text-primary);">{{ paymentMethodLabels[transaction.payment_method] }}</span>
                      </div>
                    </TableCell>
                    <TableCell class="py-3 px-3">
                      <span class="text-xs" style="color: var(--pos-text-muted);">{{ transaction.cashier?.name || '-' }}</span>
                    </TableCell>
                    <TableCell class="py-3 px-3 text-right">
                      <span class="text-xs font-bold" style="color: var(--pos-text-primary);">{{ formatPrice(transaction.tax_amount || 0) }}</span>
                    </TableCell>
                    <TableCell class="py-3 px-3 text-center">
                      <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
                        :style="getStatusColor(transaction.status)"
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
                      <div class="space-y-1.5">
                        <div
                          v-for="item in transaction.items"
                          :key="item.id"
                          class="flex justify-between text-xs"
                          style="color: var(--pos-text-primary);"
                        >
                          <span>{{ item.quantity }}x {{ item.product?.name || 'Unknown' }}</span>
                          <span class="font-medium">{{ formatPrice(item.total || 0) }}</span>
                        </div>
                        <div class="h-px bg-[var(--pos-border)] my-2"></div>
                        <div class="flex justify-end">
                          <span class="text-xs font-bold" style="color: var(--pos-brand-primary);">Total: {{ formatPrice(transaction.tax_amount || 0) }}</span>
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
</style>