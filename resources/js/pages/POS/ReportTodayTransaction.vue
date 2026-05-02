<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { Wallet, CreditCard, QrCode, Smartphone, ChevronLeft, ChevronRight, Calendar, RefreshCw, ChevronDown, ChevronUp } from 'lucide-vue-next'
import type { TransactionReportProps, TransactionWithItems, PaymentMethod } from '@/types/pos'

// Import shadcn components
import { Table, TableBody, TableCaption, TableCell, TableEmpty, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'

const props = defineProps<TransactionReportProps>()

// State
const currentDate = ref(props.selectedDate)
const isLoading = ref(false)
const expandedTransactions = ref<Set<string>>(new Set())

// Payment method icons
const paymentMethodIcons: Record<PaymentMethod, any> = {
  cash: Wallet,
  e_wallet: Smartphone,
  bank_transfer: CreditCard,
  qris: QrCode,
}

// Payment method labels
const paymentMethodLabels: Record<PaymentMethod, string> = {
  cash: 'Cash',
  e_wallet: 'E-Wallet',
  bank_transfer: 'Bank Transfer',
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
    second: '2-digit',
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

// Computed: sorted transactions
const sortedTransactions = computed(() => {
  return [...props.transactions].sort((a, b) => {
    return new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
  })
})

onMounted(() => {
  // Initialize with any needed setup
})
</script>

<template>
  <div class="container mx-auto p-6 space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-800">Laporan Transaksi Harian</h1>
          <p class="text-sm text-gray-500 mt-1">
            {{ formatDateDisplay(currentDate) }}
          </p>
        </div>
        <Button
          variant="outline"
          size="sm"
          @click="refreshReport"
          :disabled="isLoading"
          class="gap-2"
        >
          <RefreshCw :class="{ 'animate-spin': isLoading }" class="h-4 w-4" />
          Refresh
        </Button>
      </div>

      <!-- Date Navigation -->
      <div class="flex items-center gap-2">
        <Button
          variant="outline"
          size="sm"
          @click="navigateDate('prev')"
          :disabled="isLoading"
          class="gap-1"
        >
          <ChevronLeft class="h-4 w-4" />
          Previous
        </Button>
        <Button
          variant="outline"
          size="sm"
          @click="navigateDate('today')"
          :disabled="isLoading"
        >
          <Calendar class="h-4 w-4 mr-1" />
          Today
        </Button>
        <Button
          variant="outline"
          size="sm"
          @click="navigateDate('next')"
          :disabled="isLoading || currentDate === props.today"
          class="gap-1"
        >
          Next
          <ChevronRight class="h-4 w-4" />
        </Button>
        <Input
          type="date"
          :value="currentDate"
          @input="(e: any) => { currentDate = e.target.value; refreshReport() }"
          :max="props.today"
          class="w-auto"
        />
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Total Sales -->
      <Card>
        <CardContent class="pt-6">
          <div class="space-y-2">
            <p class="text-sm font-medium text-gray-500">Total Penjualan</p>
            <p class="text-2xl font-bold text-teal-600">
              {{ formatPrice(summary.total_sales) }}
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Total Transactions -->
      <Card>
        <CardContent class="pt-6">
          <div class="space-y-2">
            <p class="text-sm font-medium text-gray-500">Total Transaksi</p>
            <p class="text-2xl font-bold text-slate-800">
              {{ summary.total_transactions }}
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Total Items -->
      <Card>
        <CardContent class="pt-6">
          <div class="space-y-2">
            <p class="text-sm font-medium text-gray-500">Total Item</p>
            <p class="text-2xl font-bold text-slate-800">
              {{ summary.total_items }}
            </p>
          </div>
        </CardContent>
      </Card>

      <!-- Average Transaction -->
      <Card>
        <CardContent class="pt-6">
          <div class="space-y-2">
            <p class="text-sm font-medium text-gray-500">Rata-rata Transaksi</p>
            <p class="text-2xl font-bold text-slate-800">
              {{ formatPrice(getAverageTransactionValue()) }}
            </p>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Payment Method Breakdown -->
    <Card>
      <CardContent class="pt-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Metode Pembayaran</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <div
            v-for="(amount, method) in summary.payment_methods"
            :key="method"
            class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 bg-gray-50"
          >
            <component
              :is="paymentMethodIcons[method as PaymentMethod]"
              class="h-5 w-5 text-teal-600"
            />
            <div class="flex-1">
              <p class="text-sm font-medium text-slate-700">
                {{ paymentMethodLabels[method as PaymentMethod] }}
              </p>
              <p class="text-lg font-bold text-slate-800">
                {{ formatPrice(amount) }}
              </p>
              <p class="text-xs text-gray-500">
                {{ getPaymentMethodPercentage(method as PaymentMethod).toFixed(1) }}%
              </p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Transaction Table -->
    <Card>
      <CardContent class="pt-6">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Daftar Transaksi</h3>

        <Table>
          <TableCaption>
            Total {{ transactions.length }} transaksi pada {{ formatDateDisplay(currentDate) }}
          </TableCaption>
          <TableHeader>
            <TableRow>
              <TableHead>No. Invoice</TableHead>
              <TableHead>Waktu</TableHead>
              <TableHead>Kasir</TableHead>
              <TableHead>Metode Pembayaran</TableHead>
              <TableHead class="text-right">Total</TableHead>
              <TableHead class="text-center">Detail</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableEmpty v-if="transactions.length === 0">
              <div class="py-8 text-center text-gray-500">
                <p class="text-lg font-medium">Tidak ada transaksi</p>
                <p class="text-sm mt-1">Belum ada transaksi pada tanggal ini</p>
              </div>
            </TableEmpty>

            <template v-else>
              <template v-for="transaction in sortedTransactions" :key="transaction.id">
                <!-- Main transaction row -->
                <TableRow class="cursor-pointer hover:bg-gray-50" @click="toggleTransaction(transaction.id)">
                  <TableCell class="font-medium">
                    {{ transaction.id.slice(0, 8).toUpperCase() }}
                  </TableCell>
                  <TableCell>
                    {{ formatTime(transaction.created_at) }}
                  </TableCell>
                  <TableCell>
                    {{ transaction.cashier_name || transaction.cashier?.name || '-' }}
                  </TableCell>
                  <TableCell>
                    <div class="flex items-center gap-2">
                      <component
                        :is="paymentMethodIcons[transaction.payment_method]"
                        class="h-4 w-4 text-teal-600"
                      />
                      <span class="capitalize">
                        {{ paymentMethodLabels[transaction.payment_method] }}
                      </span>
                    </div>
                  </TableCell>
                  <TableCell class="text-right font-semibold">
                    {{ formatPrice(transaction.total) }}
                  </TableCell>
                  <TableCell class="text-center">
                    <Button
                      variant="ghost"
                      size="sm"
                      class="h-8 w-8 p-0 mx-auto"
                      @click.stop="toggleTransaction(transaction.id)"
                    >
                      <ChevronDown
                        v-if="!isTransactionExpanded(transaction.id)"
                        class="h-4 w-4"
                      />
                      <ChevronUp
                        v-else
                        class="h-4 w-4"
                      />
                    </Button>
                  </TableCell>
                </TableRow>

                <!-- Expanded items row -->
                <TableRow v-if="isTransactionExpanded(transaction.id)">
                  <TableCell :colspan="6" class="p-0">
                    <div class="p-4 bg-gray-50 space-y-3">
                      <!-- Transaction items -->
                      <div class="space-y-2">
                        <h4 class="text-sm font-semibold text-slate-700">Item Pembelian</h4>
                        <div class="space-y-1">
                          <div
                            v-for="item in transaction.items"
                            :key="item.id"
                            class="flex justify-between items-center text-sm py-1 px-2 rounded bg-white border"
                          >
                            <div class="flex-1">
                              <p class="font-medium text-slate-700">
                                {{ item.product?.name || 'Unknown Product' }}
                              </p>
                              <p class="text-xs text-gray-500">
                                {{ item.quantity }} x {{ formatPrice(item.unit_price) }}
                              </p>
                            </div>
                            <p class="font-semibold text-slate-800">
                              {{ formatPrice(item.total) }}
                            </p>
                          </div>
                        </div>
                      </div>

                      <Separator />

                      <!-- Transaction summary -->
                      <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                          <span class="text-gray-600">Subtotal</span>
                          <span class="font-medium">{{ formatPrice(transaction.subtotal) }}</span>
                        </div>
                        <div v-if="transaction.discount_amount > 0" class="flex justify-between text-green-600">
                          <span>Diskon</span>
                          <span class="font-medium">-{{ formatPrice(transaction.discount_amount) }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span class="text-gray-600">Pajak (11%)</span>
                          <span class="font-medium">{{ formatPrice(transaction.tax_amount) }}</span>
                        </div>
                        <Separator class="my-2" />
                        <div class="flex justify-between text-base font-bold">
                          <span>Total</span>
                          <span class="text-teal-600">{{ formatPrice(transaction.total) }}</span>
                        </div>
                        <div v-if="transaction.payment_method === 'cash' && transaction.cash_received" class="flex justify-between text-sm">
                          <span class="text-gray-600">Tunai Diterima</span>
                          <span class="font-medium">{{ formatPrice(transaction.cash_received) }}</span>
                        </div>
                        <div v-if="transaction.payment_method === 'cash' && transaction.change !== undefined" class="flex justify-between text-sm">
                          <span class="text-gray-600">Kembalian</span>
                          <span class="font-medium text-green-600">{{ formatPrice(transaction.change) }}</span>
                        </div>
                        <div class="flex justify-between text-sm pt-1">
                          <span class="text-gray-600">Status</span>
                          <span
                            class="font-medium px-2 py-0.5 rounded text-xs"
                            :class="{
                              'bg-green-100 text-green-700': transaction.status === 'success',
                              'bg-red-100 text-red-700': transaction.status === 'failed',
                              'bg-yellow-100 text-yellow-700': transaction.status === 'pending',
                            }"
                          >
                            {{ transaction.status.toUpperCase() }}
                          </span>
                        </div>
                      </div>
                    </div>
                  </TableCell>
                </TableRow>
              </template>
            </template>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  </div>
</template>

<style scoped>
/* Ensure proper focus states */
button:focus-visible {
  outline: 2px solid rgb(20, 184, 166);
  outline-offset: 2px;
}

/* Smooth transitions */
* {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}
</style>
