<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
  ArrowLeft,
  Download,
  Search,
  Calendar,
  Layers,
  Package,
  Tags,
  CreditCard,
  Boxes,
  Undo2,
  TrendingUp,
  AlertTriangle,
  DollarSign,
  Wallet,
  ShoppingBag,
  Receipt,
  ChevronDown,
  ShoppingCart,
  X,
  FileText,
  FileType,
  Check,
} from 'lucide-vue-next'
import AdminLayout from '@/layouts/admin/AdminLayout.vue'
import { index as adminDashboardRoute } from '@/routes/admin/dashboard'

defineOptions({
  layout: (h: any, page: any) =>
    h(
      AdminLayout,
      {
        breadcrumbs: [
          { title: 'Dashboard', href: adminDashboardRoute.url() },
          { title: 'Laporan Penjualan' },
        ],
      },
      () => page,
    ),
})

// ─── Types ───────────────────────────────────────────────────────────────
type Period = 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'yearly' | 'custom'
type TabKey = 'category' | 'product' | 'brand' | 'payment' | 'stock' | 'returns'
type ExportType = 'category' | 'product' | 'brand' | 'payment' | 'stock_top' | 'stock_out' | 'returns'

interface CategoryRow { id: string; name: string; qty: number; revenue: number; profit: number; stock: number }
interface BrandRow extends CategoryRow {}
interface ProductRow {
  id: string; code: string; name: string;
  category: string | null; brand: string | null;
  qty: number; revenue: number; profit: number; stock: number;
}
interface PaymentRow { method: string; label: string; transactions: number; revenue: number; percentage: number }
interface TopSellingRow {
  id: string; code: string; name: string; category: string | null; brand: string | null;
  qty_sold: number; revenue: number; sales_percentage: number; stock_remaining: number;
}
interface OutOfStockRow {
  id: string; code: string; name: string;
  category: string | null; brand: string | null;
  last_sold_at: string | null;
}

interface ReturnItemRow {
  product_name: string
  quantity: number
  unit_price: number
  subtotal: number
}
interface ReturnRow {
  id: string
  return_number: string
  invoice_number: string
  reason: string
  notes: string | null
  status: string
  cashier_name: string
  created_at: string
  items: ReturnItemRow[]
  total_qty: number
  total_value: number
}
interface ReturnsData {
  list: ReturnRow[]
  totals: { total_returns: number; total_qty: number; total_value: number }
}

interface Category { id: string; name: string }
interface Brand { id: string; name: string }

const props = defineProps<{
  by_category: CategoryRow[]
  by_product: ProductRow[]
  by_brand: BrandRow[]
  by_payment_method: PaymentRow[]
  by_stock: { top_selling: TopSellingRow[]; out_of_stock: OutOfStockRow[] }
  by_returns: ReturnsData
  summary: { total_revenue: number; total_profit: number; total_items: number; total_transactions: number }
  period: Period
  date_range: { start: string; end: string }
  categories: Category[]
  brands: Brand[]
}>()

// ─── State ───────────────────────────────────────────────────────────────
const activeTab = ref<TabKey>('category')
const search = ref('')
const sortKey = ref<string>('revenue')
const sortDir = ref<'asc' | 'desc'>('desc')

const selectedPeriod = ref<Period>(props.period ?? 'monthly')
const customStart = ref(props.date_range.start)
const customEnd = ref(props.date_range.end)

const exportOpen = ref(false)

// ─── Shopping list ("Belanja?") modal state ─────────────────────────────
const shoppingOpen = ref(false)
const shoppingFormat = ref<'pdf' | 'word'>('pdf')
const shoppingIncludeOutOfStock = ref(true)
const shoppingIncludeTopSelling = ref(true)
const shoppingTopLimit = ref(20)
const shoppingSelectedCategories = ref<string[]>([])

function openShoppingModal() {
  // default: semua kategori dicentang
  shoppingSelectedCategories.value = props.categories.map(c => c.id)
  shoppingOpen.value = true
}

function toggleAllCategories(checked: boolean) {
  shoppingSelectedCategories.value = checked ? props.categories.map(c => c.id) : []
}

const allCategoriesChecked = computed(() =>
  props.categories.length > 0 &&
  shoppingSelectedCategories.value.length === props.categories.length,
)

function generateShoppingList() {
  const params = new URLSearchParams()
  params.set('format', shoppingFormat.value)
  params.set('period', selectedPeriod.value)
  if (selectedPeriod.value === 'custom') {
    params.set('start_date', customStart.value)
    params.set('end_date', customEnd.value)
  }
  params.set('include_out_of_stock', shoppingIncludeOutOfStock.value ? '1' : '0')
  params.set('include_top_selling', shoppingIncludeTopSelling.value ? '1' : '0')
  params.set('top_limit', String(shoppingTopLimit.value))
  for (const id of shoppingSelectedCategories.value) {
    params.append('categories[]', id)
  }
  const url = `/admin/reports/sales/shopping-list?${params.toString()}`
  if (shoppingFormat.value === 'pdf') {
    window.open(url, '_blank')
  } else {
    window.location.href = url
  }
  shoppingOpen.value = false
}

const tabs: { key: TabKey; label: string; icon: any }[] = [
  { key: 'category', label: 'Kategori',     icon: Layers },
  { key: 'product',  label: 'Produk',       icon: Package },
  { key: 'brand',    label: 'Merek',        icon: Tags },
  { key: 'payment',  label: 'Metode Bayar', icon: CreditCard },
  { key: 'stock',    label: 'Stok',         icon: Boxes },
  { key: 'returns',  label: 'Return',       icon: Undo2 },
]

const periodOptions: { value: Period; label: string }[] = [
  { value: 'daily',     label: 'Harian' },
  { value: 'weekly',    label: 'Mingguan' },
  { value: 'monthly',   label: 'Bulanan' },
  { value: 'quarterly', label: 'Quarter' },
  { value: 'yearly',    label: 'Tahunan' },
  { value: 'custom',    label: 'Kustom' },
]

const exportOptions = computed<{ value: ExportType; label: string }[]>(() => {
  const base: { value: ExportType; label: string }[] = [
    { value: 'category', label: 'Per Kategori' },
    { value: 'product',  label: 'Per Produk' },
    { value: 'brand',    label: 'Per Merek' },
    { value: 'payment',  label: 'Per Metode Bayar' },
    { value: 'stock_top', label: 'Stok Terlaris' },
    { value: 'stock_out', label: 'Stok Habis' },
    { value: 'returns', label: 'Return' },
  ]
  return base
})

// ─── Formatters ──────────────────────────────────────────────────────────
function formatRp(n: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR',
    minimumFractionDigits: 0, maximumFractionDigits: 0,
  }).format(n || 0)
}
function formatNum(n: number): string {
  return new Intl.NumberFormat('id-ID').format(n || 0)
}
function formatDate(iso: string | null): string {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
}

// ─── Sorting & filtering ────────────────────────────────────────────────
function setSort(key: string) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortKey.value = key
    sortDir.value = 'desc'
  }
}

function sortRows<T extends Record<string, any>>(rows: T[]): T[] {
  const arr = [...rows]
  arr.sort((a, b) => {
    const av = a[sortKey.value]
    const bv = b[sortKey.value]
    if (av == null && bv == null) return 0
    if (av == null) return 1
    if (bv == null) return -1
    if (typeof av === 'number' && typeof bv === 'number') {
      return sortDir.value === 'asc' ? av - bv : bv - av
    }
    return sortDir.value === 'asc'
      ? String(av).localeCompare(String(bv))
      : String(bv).localeCompare(String(av))
  })
  return arr
}

function matchesSearch(text: string): boolean {
  if (!search.value.trim()) return true
  return text.toLowerCase().includes(search.value.toLowerCase())
}

const filteredCategory = computed(() =>
  sortRows(props.by_category.filter(r => matchesSearch(r.name))),
)
const filteredBrand = computed(() =>
  sortRows(props.by_brand.filter(r => matchesSearch(r.name))),
)
const filteredProduct = computed(() =>
  sortRows(
    props.by_product.filter(
      r =>
        matchesSearch(r.name) ||
        (r.code && r.code.toLowerCase().includes(search.value.toLowerCase())) ||
        (r.category ?? '').toLowerCase().includes(search.value.toLowerCase()) ||
        (r.brand ?? '').toLowerCase().includes(search.value.toLowerCase()),
    ),
  ),
)
const filteredPayment = computed(() =>
  sortRows(props.by_payment_method.filter(r => matchesSearch(r.label))),
)
const filteredTopSelling = computed(() =>
  props.by_stock.top_selling.filter(
    r => matchesSearch(r.name) || (r.code && r.code.toLowerCase().includes(search.value.toLowerCase())),
  ),
)
const filteredOutOfStock = computed(() =>
  props.by_stock.out_of_stock.filter(
    r => matchesSearch(r.name) || (r.code && r.code.toLowerCase().includes(search.value.toLowerCase())),
  ),
)

const filteredReturns = computed(() => {
  const q = search.value.trim().toLowerCase()
  const list = props.by_returns?.list ?? []
  if (!q) return list
  return list.filter(r =>
    r.return_number.toLowerCase().includes(q) ||
    r.invoice_number.toLowerCase().includes(q) ||
    r.cashier_name.toLowerCase().includes(q) ||
    r.reason.toLowerCase().includes(q) ||
    r.items.some(it => it.product_name.toLowerCase().includes(q)),
  )
})

const expandedReturns = ref<Set<string>>(new Set())
function toggleReturn(id: string) {
  if (expandedReturns.value.has(id)) expandedReturns.value.delete(id)
  else expandedReturns.value.add(id)
}

function formatDateTime(iso: string | null): string {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
    + ' ' + d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}

// ─── Period change ──────────────────────────────────────────────────────
function applyPeriod() {
  const params: Record<string, string> = { period: selectedPeriod.value }
  if (selectedPeriod.value === 'custom') {
    params.start_date = customStart.value
    params.end_date = customEnd.value
  }
  router.get('/admin/reports/sales', params, {
    preserveScroll: true,
    preserveState: true,
    replace: true,
  })
}

watch(selectedPeriod, v => {
  if (v !== 'custom') applyPeriod()
})

// ─── Export ──────────────────────────────────────────────────────────────
function triggerExport(type: ExportType) {
  const params = new URLSearchParams()
  params.set('type', type)
  params.set('period', selectedPeriod.value)
  if (selectedPeriod.value === 'custom') {
    params.set('start_date', customStart.value)
    params.set('end_date', customEnd.value)
  }
  window.location.href = `/admin/reports/sales/export?${params.toString()}`
  exportOpen.value = false
}

// ─── Switch tab resets sort key sensibly ─────────────────────────────────
watch(activeTab, () => {
  sortKey.value = 'revenue'
  sortDir.value = 'desc'
  search.value = ''
})

const periodLabel = computed(() => {
  const start = new Date(props.date_range.start)
  const end = new Date(props.date_range.end)
  const fmt = (d: Date) =>
    d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
  if (props.date_range.start === props.date_range.end) return fmt(start)
  return `${fmt(start)} — ${fmt(end)}`
})
</script>

<template>
  <div class="adm-page px-6 py-5">
    <!-- Header -->
    <div class="mb-5 flex items-center justify-between gap-4">
      <button
        class="flex items-center gap-1.5 text-sm font-medium transition"
        style="color: var(--pos-text-muted);"
        @click="router.get(adminDashboardRoute.url())"
      >
        <ArrowLeft class="h-4 w-4" /> Kembali ke dashboard
      </button>
      <div class="flex items-center gap-2">
        <div class="flex items-center gap-2 rounded-md border bg-white px-3 py-2 text-xs"
             style="border-color: var(--pos-border); color: var(--pos-text-secondary);">
          <Calendar class="h-3.5 w-3.5" style="color: var(--pos-text-muted);" />
          {{ periodLabel }}
        </div>
        <select
          v-model="selectedPeriod"
          class="rounded-md border bg-white px-3 py-2 text-xs font-medium outline-none"
          style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
        >
          <option v-for="o in periodOptions" :key="o.value" :value="o.value">{{ o.label }}</option>
        </select>

        <!-- Belanja? button -->
        <button
          class="flex items-center gap-1.5 rounded-md border bg-white px-3 py-2 text-xs font-semibold shadow-sm transition hover:bg-gray-50"
          style="border-color: var(--pos-brand-primary); color: var(--pos-brand-dark);"
          @click="openShoppingModal"
        >
          <ShoppingCart class="h-3.5 w-3.5" /> Belanja?
        </button>

        <!-- Export dropdown -->
        <div class="relative">
          <button
            class="flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-semibold text-white shadow transition hover:opacity-95"
            style="background: var(--pos-brand-primary);"
            @click="exportOpen = !exportOpen"
          >
            <Download class="h-3.5 w-3.5" /> Export
            <ChevronDown class="h-3.5 w-3.5" />
          </button>
          <div
            v-if="exportOpen"
            class="absolute right-0 z-30 mt-1 w-52 overflow-hidden rounded-lg border bg-white shadow-lg"
            style="border-color: var(--pos-border);"
          >
            <p class="border-b px-3 py-2 text-[10px] font-bold uppercase tracking-wide"
               style="border-color: var(--pos-border); color: var(--pos-text-muted); background: var(--pos-bg-secondary);">
              Pilih Dimensi
            </p>
            <button
              v-for="o in exportOptions"
              :key="o.value"
              class="block w-full px-3 py-2 text-left text-xs transition hover:bg-gray-50"
              style="color: var(--pos-text-secondary);"
              @click="triggerExport(o.value)"
            >
              {{ o.label }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Custom date range -->
    <div v-if="selectedPeriod === 'custom'"
         class="mb-4 flex items-center gap-2 rounded-lg border bg-white p-3"
         style="border-color: var(--pos-border);">
      <span class="text-xs font-medium" style="color: var(--pos-text-muted);">Dari</span>
      <input v-model="customStart" type="date"
             class="rounded-md border px-2 py-1.5 text-xs"
             style="border-color: var(--pos-border); color: var(--pos-text-secondary);" />
      <span class="text-xs font-medium" style="color: var(--pos-text-muted);">Sampai</span>
      <input v-model="customEnd" type="date"
             class="rounded-md border px-2 py-1.5 text-xs"
             style="border-color: var(--pos-border); color: var(--pos-text-secondary);" />
      <button
        class="rounded-md px-3 py-1.5 text-xs font-semibold text-white"
        style="background: var(--pos-brand-primary);"
        @click="applyPeriod"
      >
        Terapkan
      </button>
    </div>

    <!-- Summary cards -->
    <div class="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
      <div class="rounded-lg border bg-white p-4" style="border-color: var(--pos-border); box-shadow: var(--pos-shadow);">
        <div class="mb-1.5 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide" style="color: var(--pos-text-muted);">
          <DollarSign class="h-3.5 w-3.5" /> Total Revenue
        </div>
        <p class="text-lg font-bold" style="color: var(--pos-text-primary);">{{ formatRp(summary.total_revenue) }}</p>
      </div>
      <div class="rounded-lg border bg-white p-4" style="border-color: var(--pos-border); box-shadow: var(--pos-shadow);">
        <div class="mb-1.5 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide" style="color: var(--pos-text-muted);">
          <TrendingUp class="h-3.5 w-3.5" /> Total Profit
        </div>
        <p class="text-lg font-bold" style="color: var(--pos-success-text);">{{ formatRp(summary.total_profit) }}</p>
      </div>
      <div class="rounded-lg border bg-white p-4" style="border-color: var(--pos-border); box-shadow: var(--pos-shadow);">
        <div class="mb-1.5 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide" style="color: var(--pos-text-muted);">
          <ShoppingBag class="h-3.5 w-3.5" /> Item Terjual
        </div>
        <p class="text-lg font-bold" style="color: var(--pos-text-primary);">{{ formatNum(summary.total_items) }}</p>
      </div>
      <div class="rounded-lg border bg-white p-4" style="border-color: var(--pos-border); box-shadow: var(--pos-shadow);">
        <div class="mb-1.5 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide" style="color: var(--pos-text-muted);">
          <Receipt class="h-3.5 w-3.5" /> Total Transaksi
        </div>
        <p class="text-lg font-bold" style="color: var(--pos-text-primary);">{{ formatNum(summary.total_transactions) }}</p>
      </div>
    </div>

    <!-- Tabs + content card -->
    <div class="overflow-hidden rounded-lg border bg-white"
         style="border-color: var(--pos-border); box-shadow: var(--pos-shadow);">
      <!-- Tab bar -->
      <div class="flex items-center gap-1 border-b px-2 py-2"
           style="border-color: var(--pos-border); background: var(--pos-bg-secondary);">
        <button
          v-for="t in tabs"
          :key="t.key"
          class="flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-semibold transition"
          :style="activeTab === t.key
            ? { background: 'var(--pos-brand-primary)', color: '#fff' }
            : { color: 'var(--pos-text-muted)', background: 'transparent' }"
          @click="activeTab = t.key"
        >
          <component :is="t.icon" class="h-3.5 w-3.5" />
          {{ t.label }}
        </button>
      </div>

      <!-- Search bar -->
      <div class="flex items-center gap-3 border-b px-4 py-3"
           style="border-color: var(--pos-border);">
        <div class="relative flex-1 max-w-md">
          <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2" style="color: var(--pos-text-muted);" />
          <input
            v-model="search"
            type="text"
            :placeholder="`Cari di ${tabs.find(t => t.key === activeTab)?.label.toLowerCase()}...`"
            class="w-full rounded-md border bg-white py-2 pl-9 pr-3 text-sm outline-none transition focus:ring-2"
            style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
          />
        </div>
      </div>

      <!-- Tab content -->
      <!-- ─── KATEGORI ────────────────────────────────────────────────── -->
      <div v-if="activeTab === 'category'" class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead style="background: var(--pos-bg-secondary);">
            <tr class="text-left">
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer" style="color: var(--pos-text-muted);" @click="setSort('name')">Kategori</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('qty')">Qty</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('revenue')">Revenue</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('profit')">Profit</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('stock')">Stok Saat Ini</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in filteredCategory" :key="row.id" class="border-t" style="border-color: var(--pos-border);">
              <td class="px-4 py-3 font-medium" style="color: var(--pos-text-secondary);">{{ row.name }}</td>
              <td class="px-4 py-3 text-right" style="color: var(--pos-text-secondary);">{{ formatNum(row.qty) }}</td>
              <td class="px-4 py-3 text-right font-semibold" style="color: var(--pos-text-primary);">{{ formatRp(row.revenue) }}</td>
              <td class="px-4 py-3 text-right" style="color: var(--pos-success-text);">{{ formatRp(row.profit) }}</td>
              <td class="px-4 py-3 text-right" style="color: var(--pos-text-secondary);">{{ formatNum(row.stock) }}</td>
            </tr>
            <tr v-if="!filteredCategory.length">
              <td colspan="5" class="px-4 py-12 text-center text-sm" style="color: var(--pos-text-muted);">
                Tidak ada data kategori untuk periode ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ─── PRODUK ─────────────────────────────────────────────────── -->
      <div v-else-if="activeTab === 'product'" class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead style="background: var(--pos-bg-secondary);">
            <tr class="text-left">
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer" style="color: var(--pos-text-muted);" @click="setSort('code')">Kode</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer" style="color: var(--pos-text-muted);" @click="setSort('name')">Nama Produk</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer" style="color: var(--pos-text-muted);" @click="setSort('category')">Kategori</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer" style="color: var(--pos-text-muted);" @click="setSort('brand')">Merek</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('qty')">Qty</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('revenue')">Revenue</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('profit')">Profit</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('stock')">Stok</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in filteredProduct" :key="row.id" class="border-t" style="border-color: var(--pos-border);">
              <td class="px-4 py-3 font-mono text-xs" style="color: var(--pos-text-muted);">{{ row.code }}</td>
              <td class="px-4 py-3 font-medium" style="color: var(--pos-text-secondary);">{{ row.name }}</td>
              <td class="px-4 py-3 text-xs" style="color: var(--pos-text-muted);">{{ row.category ?? '—' }}</td>
              <td class="px-4 py-3 text-xs" style="color: var(--pos-text-muted);">{{ row.brand ?? '—' }}</td>
              <td class="px-4 py-3 text-right" style="color: var(--pos-text-secondary);">{{ formatNum(row.qty) }}</td>
              <td class="px-4 py-3 text-right font-semibold" style="color: var(--pos-text-primary);">{{ formatRp(row.revenue) }}</td>
              <td class="px-4 py-3 text-right" style="color: var(--pos-success-text);">{{ formatRp(row.profit) }}</td>
              <td class="px-4 py-3 text-right">
                <span
                  class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                  :style="row.stock === 0
                    ? { background: 'var(--pos-danger-bg)', color: 'var(--pos-danger-text)' }
                    : { background: 'var(--pos-brand-light)', color: 'var(--pos-brand-dark)' }"
                >
                  {{ formatNum(row.stock) }}
                </span>
              </td>
            </tr>
            <tr v-if="!filteredProduct.length">
              <td colspan="8" class="px-4 py-12 text-center text-sm" style="color: var(--pos-text-muted);">
                Tidak ada data produk untuk periode ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ─── MEREK ──────────────────────────────────────────────────── -->
      <div v-else-if="activeTab === 'brand'" class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead style="background: var(--pos-bg-secondary);">
            <tr class="text-left">
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer" style="color: var(--pos-text-muted);" @click="setSort('name')">Merek</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('qty')">Qty</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('revenue')">Revenue</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('profit')">Profit</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('stock')">Stok Saat Ini</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in filteredBrand" :key="row.id" class="border-t" style="border-color: var(--pos-border);">
              <td class="px-4 py-3 font-medium" style="color: var(--pos-text-secondary);">{{ row.name }}</td>
              <td class="px-4 py-3 text-right" style="color: var(--pos-text-secondary);">{{ formatNum(row.qty) }}</td>
              <td class="px-4 py-3 text-right font-semibold" style="color: var(--pos-text-primary);">{{ formatRp(row.revenue) }}</td>
              <td class="px-4 py-3 text-right" style="color: var(--pos-success-text);">{{ formatRp(row.profit) }}</td>
              <td class="px-4 py-3 text-right" style="color: var(--pos-text-secondary);">{{ formatNum(row.stock) }}</td>
            </tr>
            <tr v-if="!filteredBrand.length">
              <td colspan="5" class="px-4 py-12 text-center text-sm" style="color: var(--pos-text-muted);">
                Tidak ada data merek untuk periode ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ─── METODE BAYAR ───────────────────────────────────────────── -->
      <div v-else-if="activeTab === 'payment'" class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead style="background: var(--pos-bg-secondary);">
            <tr class="text-left">
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer" style="color: var(--pos-text-muted);" @click="setSort('label')">Metode</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('transactions')">Transaksi</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('revenue')">Revenue</th>
              <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide cursor-pointer text-right" style="color: var(--pos-text-muted);" @click="setSort('percentage')">Persentase</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in filteredPayment" :key="row.method" class="border-t" style="border-color: var(--pos-border);">
              <td class="px-4 py-3">
                <span class="inline-flex items-center gap-2 rounded-md px-2 py-1 text-xs font-semibold"
                      style="background: var(--pos-brand-light); color: var(--pos-brand-dark);">
                  <Wallet class="h-3 w-3" /> {{ row.label }}
                </span>
              </td>
              <td class="px-4 py-3 text-right" style="color: var(--pos-text-secondary);">{{ formatNum(row.transactions) }}</td>
              <td class="px-4 py-3 text-right font-semibold" style="color: var(--pos-text-primary);">{{ formatRp(row.revenue) }}</td>
              <td class="px-4 py-3 text-right">
                <div class="inline-flex items-center gap-2">
                  <div class="h-1.5 w-20 overflow-hidden rounded-full" style="background: var(--pos-border);">
                    <div class="h-full rounded-full" :style="{ width: `${row.percentage}%`, background: 'var(--pos-brand-primary)' }"></div>
                  </div>
                  <span class="text-xs font-medium" style="color: var(--pos-text-secondary);">{{ row.percentage }}%</span>
                </div>
              </td>
            </tr>
            <tr v-if="!filteredPayment.length">
              <td colspan="4" class="px-4 py-12 text-center text-sm" style="color: var(--pos-text-muted);">
                Tidak ada transaksi untuk periode ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ─── RETURN ─────────────────────────────────────────────────── -->
      <div v-else-if="activeTab === 'returns'" class="p-4 space-y-4">
        <!-- Summary mini-cards -->
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
          <div class="rounded-lg border p-3" style="border-color: var(--pos-border); background: var(--pos-bg-secondary);">
            <p class="text-[11px] font-semibold uppercase tracking-wide" style="color: var(--pos-text-muted);">Total Return</p>
            <p class="mt-1 text-lg font-bold" style="color: var(--pos-text-primary);">
              {{ formatNum(props.by_returns?.totals?.total_returns ?? 0) }}
            </p>
          </div>
          <div class="rounded-lg border p-3" style="border-color: var(--pos-border); background: var(--pos-bg-secondary);">
            <p class="text-[11px] font-semibold uppercase tracking-wide" style="color: var(--pos-text-muted);">Total Qty Dikembalikan</p>
            <p class="mt-1 text-lg font-bold" style="color: var(--pos-text-primary);">
              {{ formatNum(props.by_returns?.totals?.total_qty ?? 0) }}
            </p>
          </div>
          <div class="rounded-lg border p-3" style="border-color: var(--pos-border); background: var(--pos-bg-secondary);">
            <p class="text-[11px] font-semibold uppercase tracking-wide" style="color: var(--pos-text-muted);">Total Nilai Return</p>
            <p class="mt-1 text-lg font-bold" style="color: var(--pos-danger-text);">
              {{ formatRp(props.by_returns?.totals?.total_value ?? 0) }}
            </p>
          </div>
        </div>

        <!-- Returns list -->
        <div class="overflow-x-auto rounded-lg border" style="border-color: var(--pos-border);">
          <table class="w-full text-sm">
            <thead style="background: var(--pos-bg-secondary);">
              <tr class="text-left">
                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">No. Return</th>
                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Tanggal</th>
                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Transaksi</th>
                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Kasir</th>
                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Alasan</th>
                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-right" style="color: var(--pos-text-muted);">Qty</th>
                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-right" style="color: var(--pos-text-muted);">Nilai</th>
                <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-center" style="color: var(--pos-text-muted);">Status</th>
                <th class="px-1 py-3 w-8"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!filteredReturns.length">
                <td colspan="9" class="px-4 py-12 text-center text-sm" style="color: var(--pos-text-muted);">
                  Tidak ada transaksi return pada periode ini.
                </td>
              </tr>
              <template v-for="r in filteredReturns" :key="r.id">
                <tr
                  class="cursor-pointer border-t transition hover:bg-gray-50"
                  style="border-color: var(--pos-border);"
                  @click="toggleReturn(r.id)"
                >
                  <td class="px-4 py-3 font-mono text-xs font-semibold" style="color: var(--pos-text-primary);">{{ r.return_number }}</td>
                  <td class="px-4 py-3 text-xs" style="color: var(--pos-text-muted);">{{ formatDateTime(r.created_at) }}</td>
                  <td class="px-4 py-3 font-mono text-xs" style="color: var(--pos-text-secondary);">{{ r.invoice_number }}</td>
                  <td class="px-4 py-3 text-xs" style="color: var(--pos-text-secondary);">{{ r.cashier_name }}</td>
                  <td class="px-4 py-3 text-xs" style="color: var(--pos-text-secondary);">{{ r.reason }}</td>
                  <td class="px-4 py-3 text-right text-xs font-semibold" style="color: var(--pos-text-primary);">{{ formatNum(r.total_qty) }}</td>
                  <td class="px-4 py-3 text-right text-xs font-bold" style="color: var(--pos-danger-text);">{{ formatRp(r.total_value) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span
                      class="inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold"
                      style="background: var(--pos-success-bg); color: var(--pos-success-text);"
                    >{{ r.status }}</span>
                  </td>
                  <td class="px-1 py-3 text-center">
                    <ChevronDown
                      :class="['h-3.5 w-3.5 transition-transform', expandedReturns.has(r.id) ? 'rotate-180' : '']"
                      style="color: var(--pos-text-light);"
                    />
                  </td>
                </tr>
                <tr v-if="expandedReturns.has(r.id)" style="background: var(--pos-bg-secondary);">
                  <td colspan="9" class="px-6 py-3">
                    <p class="mb-2 text-[11px] font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">
                      Detail Item ({{ r.items.length }})
                    </p>
                    <div class="space-y-1">
                      <div
                        v-for="(it, idx) in r.items"
                        :key="idx"
                        class="flex items-center justify-between text-xs"
                        style="color: var(--pos-text-secondary);"
                      >
                        <span>{{ it.quantity }}× {{ it.product_name }}</span>
                        <span class="font-semibold">{{ formatRp(it.subtotal) }}</span>
                      </div>
                    </div>
                    <p v-if="r.notes" class="mt-2 text-[11px] italic" style="color: var(--pos-text-muted);">
                      Catatan: {{ r.notes }}
                    </p>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ─── STOK ───────────────────────────────────────────────────── -->
      <div v-else-if="activeTab === 'stock'" class="p-4 space-y-5">
        <!-- Top selling -->
        <section>
          <div class="mb-3 flex items-center gap-2">
            <TrendingUp class="h-4 w-4" style="color: var(--pos-success-text);" />
            <h3 class="text-sm font-bold" style="color: var(--pos-text-primary);">Produk Terlaris</h3>
            <span class="text-xs" style="color: var(--pos-text-muted);">(ranking berdasarkan % kontribusi penjualan)</span>
          </div>
          <div class="overflow-x-auto rounded-lg border" style="border-color: var(--pos-border);">
            <table class="w-full text-sm">
              <thead style="background: var(--pos-bg-secondary);">
                <tr class="text-left">
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">#</th>
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Produk</th>
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Kategori</th>
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-right" style="color: var(--pos-text-muted);">Qty Terjual</th>
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-right" style="color: var(--pos-text-muted);">Revenue</th>
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-right" style="color: var(--pos-text-muted);">% Penjualan</th>
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide text-right" style="color: var(--pos-text-muted);">Stok Sisa</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in filteredTopSelling" :key="row.id" class="border-t" style="border-color: var(--pos-border);">
                  <td class="px-4 py-3">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold"
                          :style="idx < 3
                            ? { background: 'var(--pos-brand-primary)', color: '#fff' }
                            : { background: 'var(--pos-bg-secondary)', color: 'var(--pos-text-muted)' }">
                      {{ idx + 1 }}
                    </span>
                  </td>
                  <td class="px-4 py-3">
                    <p class="font-medium" style="color: var(--pos-text-secondary);">{{ row.name }}</p>
                    <p class="font-mono text-[10px]" style="color: var(--pos-text-muted);">{{ row.code }}</p>
                  </td>
                  <td class="px-4 py-3 text-xs" style="color: var(--pos-text-muted);">{{ row.category ?? '—' }}</td>
                  <td class="px-4 py-3 text-right font-semibold" style="color: var(--pos-text-primary);">{{ formatNum(row.qty_sold) }}</td>
                  <td class="px-4 py-3 text-right" style="color: var(--pos-text-secondary);">{{ formatRp(row.revenue) }}</td>
                  <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center gap-2">
                      <div class="h-1.5 w-16 overflow-hidden rounded-full" style="background: var(--pos-border);">
                        <div class="h-full rounded-full" :style="{ width: `${Math.min(row.sales_percentage * 4, 100)}%`, background: 'var(--pos-brand-primary)' }"></div>
                      </div>
                      <span class="text-xs font-semibold" style="color: var(--pos-brand-dark);">{{ row.sales_percentage }}%</span>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <span
                      class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold"
                      :style="row.stock_remaining === 0
                        ? { background: 'var(--pos-danger-bg)', color: 'var(--pos-danger-text)' }
                        : row.stock_remaining < 10
                          ? { background: 'var(--pos-warning-bg)', color: 'var(--pos-warning-text)' }
                          : { background: 'var(--pos-success-bg)', color: 'var(--pos-success-text)' }"
                    >
                      {{ row.stock_remaining === 0 ? 'HABIS' : formatNum(row.stock_remaining) }}
                    </span>
                  </td>
                </tr>
                <tr v-if="!filteredTopSelling.length">
                  <td colspan="7" class="px-4 py-8 text-center text-sm" style="color: var(--pos-text-muted);">
                    Tidak ada penjualan untuk periode ini.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Out of stock -->
        <section>
          <div class="mb-3 flex items-center gap-2">
            <AlertTriangle class="h-4 w-4" style="color: var(--pos-danger-text);" />
            <h3 class="text-sm font-bold" style="color: var(--pos-text-primary);">Stok Habis</h3>
            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                  style="background: var(--pos-danger-bg); color: var(--pos-danger-text);">
              {{ filteredOutOfStock.length }} produk
            </span>
          </div>
          <div class="overflow-x-auto rounded-lg border" style="border-color: var(--pos-border);">
            <table class="w-full text-sm">
              <thead style="background: var(--pos-bg-secondary);">
                <tr class="text-left">
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Kode</th>
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Produk</th>
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Kategori</th>
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Merek</th>
                  <th class="px-4 py-3 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Terakhir Terjual</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in filteredOutOfStock" :key="row.id" class="border-t" style="border-color: var(--pos-border);">
                  <td class="px-4 py-3 font-mono text-xs" style="color: var(--pos-text-muted);">{{ row.code }}</td>
                  <td class="px-4 py-3 font-medium" style="color: var(--pos-text-secondary);">{{ row.name }}</td>
                  <td class="px-4 py-3 text-xs" style="color: var(--pos-text-muted);">{{ row.category ?? '—' }}</td>
                  <td class="px-4 py-3 text-xs" style="color: var(--pos-text-muted);">{{ row.brand ?? '—' }}</td>
                  <td class="px-4 py-3 text-xs" style="color: var(--pos-text-secondary);">{{ formatDate(row.last_sold_at) }}</td>
                </tr>
                <tr v-if="!filteredOutOfStock.length">
                  <td colspan="5" class="px-4 py-8 text-center text-sm" style="color: var(--pos-text-muted);">
                    Tidak ada produk yang stoknya habis. 🎉
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </div>
    </div>

    <!-- Click-outside catcher for export dropdown -->
    <div v-if="exportOpen" class="fixed inset-0 z-20" @click="exportOpen = false"></div>

    <!-- ─── Belanja? Modal ──────────────────────────────────────────────── -->
    <Teleport to="body">
      <div
        v-if="shoppingOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        role="dialog"
        aria-modal="true"
      >
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="shoppingOpen = false" />
        <div
          class="adm-sheet relative z-10 flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl shadow-2xl animate-in fade-in zoom-in-95 duration-200"
          style="background: #fff;"
        >
          <!-- Header -->
          <div
            class="flex items-center justify-between border-b px-5 py-4"
            style="border-color: var(--pos-border); background: var(--pos-brand-light);"
          >
            <div class="flex items-center gap-2.5">
              <div class="flex h-9 w-9 items-center justify-center rounded-lg"
                   style="background: var(--pos-brand-primary); color: #fff;">
                <ShoppingCart class="h-4 w-4" />
              </div>
              <div>
                <h3 class="text-sm font-bold" style="color: var(--pos-brand-dark);">Buat Daftar Belanja</h3>
                <p class="text-[11px]" style="color: var(--pos-text-secondary);">Berdasarkan stok habis & produk terlaris</p>
              </div>
            </div>
            <button
              class="cursor-pointer rounded-full p-1.5 transition-colors hover:bg-white/60"
              style="color: var(--pos-text-muted);"
              @click="shoppingOpen = false"
            >
              <X class="h-4 w-4" />
            </button>
          </div>

          <!-- Body -->
          <div class="flex-1 space-y-5 overflow-y-auto p-5">

            <!-- Sumber data -->
            <div>
              <p class="mb-2 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">
                Sumber Data
              </p>
              <div class="space-y-2">
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition hover:bg-gray-50"
                       style="border-color: var(--pos-border);">
                  <input
                    v-model="shoppingIncludeOutOfStock"
                    type="checkbox"
                    class="h-4 w-4 rounded"
                    style="accent-color: var(--pos-brand-primary);"
                  />
                  <div class="flex-1">
                    <p class="text-sm font-medium" style="color: var(--pos-text-secondary);">Stok Habis</p>
                    <p class="text-xs" style="color: var(--pos-text-muted);">Produk dengan stok = 0 (wajib restok)</p>
                  </div>
                  <AlertTriangle class="h-4 w-4" style="color: var(--pos-danger-text);" />
                </label>

                <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition hover:bg-gray-50"
                       style="border-color: var(--pos-border);">
                  <input
                    v-model="shoppingIncludeTopSelling"
                    type="checkbox"
                    class="h-4 w-4 rounded"
                    style="accent-color: var(--pos-brand-primary);"
                  />
                  <div class="flex-1">
                    <p class="text-sm font-medium" style="color: var(--pos-text-secondary);">Stok Terlaris</p>
                    <p class="text-xs" style="color: var(--pos-text-muted);">Top produk terlaris periode aktif</p>
                  </div>
                  <TrendingUp class="h-4 w-4" style="color: var(--pos-success-text);" />
                </label>
              </div>

              <div v-if="shoppingIncludeTopSelling" class="mt-2 flex items-center gap-2 pl-3">
                <label class="text-xs" style="color: var(--pos-text-muted);">Jumlah top produk:</label>
                <input
                  v-model.number="shoppingTopLimit"
                  type="number"
                  min="1"
                  max="100"
                  class="w-20 rounded-md border px-2 py-1 text-xs"
                  style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
                />
              </div>
            </div>

            <!-- Filter Kategori -->
            <div>
              <div class="mb-2 flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">
                  Filter Kategori
                </p>
                <label class="flex cursor-pointer items-center gap-1.5 text-xs font-medium"
                       style="color: var(--pos-brand-dark);">
                  <input
                    type="checkbox"
                    :checked="allCategoriesChecked"
                    class="h-3.5 w-3.5 rounded"
                    style="accent-color: var(--pos-brand-primary);"
                    @change="toggleAllCategories(($event.target as HTMLInputElement).checked)"
                  />
                  Pilih Semua
                </label>
              </div>

              <div
                v-if="categories.length > 0"
                class="grid max-h-56 grid-cols-2 gap-1.5 overflow-y-auto rounded-lg border p-3"
                style="border-color: var(--pos-border); background: var(--pos-bg-secondary);"
              >
                <label
                  v-for="cat in categories"
                  :key="cat.id"
                  class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-xs transition hover:bg-white"
                  style="color: var(--pos-text-secondary);"
                >
                  <input
                    v-model="shoppingSelectedCategories"
                    type="checkbox"
                    :value="cat.id"
                    class="h-3.5 w-3.5 rounded"
                    style="accent-color: var(--pos-brand-primary);"
                  />
                  {{ cat.name }}
                </label>
              </div>
              <p v-else class="text-xs italic" style="color: var(--pos-text-muted);">
                Tidak ada kategori.
              </p>
              <p class="mt-1.5 text-[11px]" style="color: var(--pos-text-muted);">
                <Check class="inline h-3 w-3" style="color: var(--pos-brand-primary);" />
                {{ shoppingSelectedCategories.length }} kategori dipilih
                <span v-if="!shoppingSelectedCategories.length"> (kosongkan = semua kategori)</span>
              </p>
            </div>

            <!-- Format output -->
            <div>
              <p class="mb-2 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">
                Format Output
              </p>
              <div class="grid grid-cols-2 gap-2">
                <button
                  type="button"
                  class="flex items-center gap-2 rounded-lg border-2 p-3 text-left transition"
                  :style="shoppingFormat === 'pdf'
                    ? { borderColor: 'var(--pos-brand-primary)', background: 'var(--pos-brand-light)' }
                    : { borderColor: 'var(--pos-border)', background: '#fff' }"
                  @click="shoppingFormat = 'pdf'"
                >
                  <FileText class="h-5 w-5" style="color: var(--pos-danger-text);" />
                  <div>
                    <p class="text-sm font-bold" style="color: var(--pos-text-secondary);">PDF</p>
                    <p class="text-[10px]" style="color: var(--pos-text-muted);">Print / Save as PDF</p>
                  </div>
                </button>
                <button
                  type="button"
                  class="flex items-center gap-2 rounded-lg border-2 p-3 text-left transition"
                  :style="shoppingFormat === 'word'
                    ? { borderColor: 'var(--pos-brand-primary)', background: 'var(--pos-brand-light)' }
                    : { borderColor: 'var(--pos-border)', background: '#fff' }"
                  @click="shoppingFormat = 'word'"
                >
                  <FileType class="h-5 w-5" style="color: #2563eb;" />
                  <div>
                    <p class="text-sm font-bold" style="color: var(--pos-text-secondary);">Word (.doc)</p>
                    <p class="text-[10px]" style="color: var(--pos-text-muted);">Buka di MS Word</p>
                  </div>
                </button>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="flex justify-end gap-2 border-t px-5 py-4"
               style="border-color: var(--pos-border); background: var(--pos-bg-secondary);">
            <button
              type="button"
              class="cursor-pointer rounded-md border px-4 py-2 text-xs font-semibold transition hover:bg-white"
              style="border-color: var(--pos-border); color: var(--pos-text-secondary); background: #fff;"
              @click="shoppingOpen = false"
            >
              Batal
            </button>
            <button
              type="button"
              :disabled="!shoppingIncludeOutOfStock && !shoppingIncludeTopSelling"
              class="flex cursor-pointer items-center gap-1.5 rounded-md px-4 py-2 text-xs font-semibold text-white transition hover:opacity-95 disabled:opacity-50"
              style="background: var(--pos-brand-primary);"
              @click="generateShoppingList"
            >
              <Download class="h-3.5 w-3.5" />
              Buat {{ shoppingFormat === 'pdf' ? 'PDF' : 'Word' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.adm-page {
  --pos-bg-primary: #ffffff;
  --pos-bg-secondary: #f9fafb;
  --pos-border: #e5e7eb;
  --pos-border-strong: #d1d5db;
  --pos-text-primary: #1e293b;
  --pos-text-secondary: #334155;
  --pos-text-muted: #6b7280;
  --pos-text-light: #9ca3af;
  --pos-brand-primary: #14b8a6;
  --pos-brand-hover: #0f9488;
  --pos-brand-light: #ecfeff;
  --pos-brand-dark: #0d9488;
  --pos-success-text: #16a34a;
  --pos-success-bg: #dcfce7;
  --pos-warning-text: #d97706;
  --pos-warning-bg: #fef3c7;
  --pos-danger-text: #dc2626;
  --pos-danger-bg: #fee2e2;
  --pos-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
}

.adm-page input:focus,
.adm-page select:focus {
  --tw-ring-color: var(--pos-brand-primary);
  border-color: var(--pos-brand-primary);
}
</style>

<style>
/* Teleported modal needs --pos-* vars available outside .adm-page scope */
.adm-sheet {
  --pos-bg-primary: #ffffff;
  --pos-bg-secondary: #f9fafb;
  --pos-border: #e5e7eb;
  --pos-border-strong: #d1d5db;
  --pos-text-primary: #1e293b;
  --pos-text-secondary: #334155;
  --pos-text-muted: #6b7280;
  --pos-text-light: #9ca3af;
  --pos-brand-primary: #14b8a6;
  --pos-brand-hover: #0f9488;
  --pos-brand-light: #ecfeff;
  --pos-brand-dark: #0d9488;
  --pos-success-text: #16a34a;
  --pos-success-bg: #dcfce7;
  --pos-warning-text: #d97706;
  --pos-warning-bg: #fef3c7;
  --pos-danger-text: #dc2626;
  --pos-danger-bg: #fee2e2;
}
</style>
