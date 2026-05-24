<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { Search, CheckCircle2, AlertCircle, Settings, ArrowRight, Link2, ChevronDown, ChevronRight, AlertTriangle, Info, Sigma, FlaskConical } from 'lucide-vue-next'
import AdminLayout from '@/layouts/admin/AdminLayout.vue'

defineOptions({
  layout: (h: any, page: any) => h(AdminLayout, {}, () => page),
})

interface Allocation {
  id: number
  batch_id: string
  batch_lot: string | null
  cukai_year: number | null
  quantity: number
  returned_qty: number
  unit_cost: number
  unit_price: number
  is_promo: boolean
  is_synthetic: boolean
  line_cost: number
  line_revenue: number
}

interface SaleItem {
  id: number
  product_id: string
  product_name: string
  product_code: string | null
  quantity: number
  unit_price: number
  manual_discount: number
  promo_discount: number
  promo_units: number
  line_total: number
  hpp_total: number
  revenue_listed: number
  profit_pre_txn_discount: number
  has_allocations: boolean
  allocations: Allocation[]
}

interface Sale {
  id: number
  invoice: string
  created_at: string | null
  status: string
  payment_method: string
  cashier: string | null
  total_amount: number
  paid_amount: number
  txn_discount: number
  discount_code: string | null
  discount_label: string | null
  tax_amount: number
  hpp_total: number
  hpp_returned: number
  refunded: number
  net_revenue: number
  net_hpp: number
  manual_discount_total: number
  promo_savings: number
  profit: number
  profit_net: number
  has_return: boolean
  items: SaleItem[]
}

interface ReturnRow {
  id: string
  return_number: string
  sale_id: number | null
  invoice: string
  status: string
  reason: string
  created_at: string | null
  cashier: string | null
  total_refunded: number
  items: Array<{
    product_name: string
    batch_id: string
    batch_lot: string | null
    quantity: number
    unit_price: number
    subtotal: number
  }>
}

interface Mutation {
  id: string
  created_at: string | null
  type: string
  quantity: number
  product_name: string
  batch_lot: string | null
  cukai_year: number | null
  notes: string | null
  reference: string
}

interface BatchRow {
  id: string
  product_name: string
  lot_number: string
  cukai_year: number | null
  is_promo: boolean
  cost_price: number
  promo_price: number | null
  stock_quantity: number
  stock_value: number
  created_at: string | null
}

interface NeracaData {
  as_of_date: string | null
  period_start: string
  period_end: string
  opening: {
    cash: number; bank: number; inventory_value: number; fixed_assets: number
    accounts_payable: number; other_liabilities: number
    equity: number; retained_earnings: number; notes: string | null
  }
  assets: {
    cash: number; bank: number; inventory_value: number
    total_current_assets: number; fixed_assets: number; total: number
  }
  liabilities: { accounts_payable: number; other_liabilities: number; total: number }
  equity: { capital: number; retained_earnings: number; period_profit: number; total: number }
  period_cashflow: { cash_net: number; bank_net: number }
  total_liab_equity: number
  difference: number
  balanced: boolean
}

interface Totals {
  gross_revenue: number
  txn_discount: number
  manual_discount: number
  promo_savings: number
  net_revenue: number
  net_revenue_after_refund: number
  hpp: number
  hpp_returned: number
  profit: number
  profit_net: number
  tax: number
  refunded: number
  sales_count: number
  returns_count: number
  products_sold_net: number
  inventory_value: number
  cross_period_refund_count: number
  cross_period_refund_amount: number
}

const props = defineProps<{
  from: string
  to: string
  sales: Sale[]
  returns: ReturnRow[]
  mutations: Mutation[]
  batches: BatchRow[]
  totals: Totals
  neraca: NeracaData
}>()

const from = ref(props.from)
const to = ref(props.to)

function applyFilter() {
  router.get('/admin/__audit', { from: from.value, to: to.value }, { preserveState: false })
}

function fmt(n: number): string {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)
}
function fmtDate(iso: string | null): string {
  if (!iso) return '-'
  return new Date(iso).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const expandedSales = ref<Set<number>>(new Set())
function toggleSale(id: number) {
  if (expandedSales.value.has(id)) expandedSales.value.delete(id)
  else expandedSales.value.add(id)
}

const tab = ref<'neraca' | 'sales' | 'returns' | 'mutations' | 'batches'>('neraca')

// Tiap kartu di-tag dengan fitur lain yang menampilkan angka yang sama,
// supaya auditor langsung tau "angka ini muncul juga di halaman X".
const totalsCards = computed(() => [
  { label: 'Gross Revenue (sebelum diskon txn)', value: fmt(props.totals.gross_revenue), tone: 'slate',
    sources: [],
    formula: 'Σ ( total_amount + diskon_txn ) tiap transaksi',
    method: 'total_amount sudah dipotong voucher, jadi diskon transaksi ditambahkan kembali untuk mendapat nilai jual sebelum diskon level transaksi. Telusuri di tab Sales kolom Total + Diskon Txn. Sumber: tabel sales.' },
  { label: 'Diskon Transaksi (voucher)', value: '−' + fmt(props.totals.txn_discount), tone: 'amber',
    sources: ['Laporan Penjualan · tab Return'],
    formula: 'Σ sales.discount_amount',
    method: 'Voucher/diskon di level keseluruhan transaksi (bukan per item). Lihat tab Sales kolom Diskon Txn. Sumber: kolom discount_amount tiap sale.' },
  { label: 'Diskon Manual per Item', value: '−' + fmt(props.totals.manual_discount), tone: 'amber',
    sources: [],
    formula: 'Σ sale_items.discount',
    method: 'Diskon yang diketik kasir per baris produk. Lihat detail sale (expand) kolom Disk. Manual. Sumber: kolom discount tiap sale_item.' },
  { label: 'Penghematan Promo Cukai', value: '−' + fmt(props.totals.promo_savings), tone: 'amber',
    sources: ['POS Transaksi Hari Ini · cukai lama'],
    formula: 'Σ sale_items.promo_discount',
    method: 'Selisih harga normal vs harga promo cukai lama, dijumlah per item. Lihat detail sale kolom Promo Cukai. Sumber: kolom promo_discount.' },
  { label: 'Net Revenue (sebelum refund)', value: fmt(props.totals.net_revenue), tone: 'emerald',
    sources: ['Laporan Penjualan · Total Revenue (gross)'],
    formula: 'Σ sales.total_amount',
    method: 'Total yang benar-benar dibayar customer (sudah net semua diskon), belum dikurangi refund. Sumber: kolom total_amount tiap sale.' },
  { label: 'Total Pendapatan (setelah refund)', value: fmt(props.totals.net_revenue_after_refund), tone: 'emerald',
    sources: ['Dashboard · Total Pendapatan', 'Laporan Penjualan · Total Revenue'],
    formula: 'Σ total_amount − Σ refund',
    method: 'Net Revenue dikurangi seluruh nilai retur (Σ return_items.subtotal, kecuali status rejected). Inilah angka "Total Pendapatan" di Dashboard.' },
  { label: 'Total HPP (FIFO bruto)', value: fmt(props.totals.hpp), tone: 'rose',
    sources: [],
    formula: 'Σ ( unit_cost × qty ) tiap alokasi batch',
    method: 'Modal pokok barang terjual, dihitung per alokasi FIFO (tabel sale_item_batches), sebelum dikurangi retur. Telusuri di expand sale → baris batch (modal × qty).' },
  { label: 'HPP Dikembalikan (Return)', value: '−' + fmt(props.totals.hpp_returned), tone: 'amber',
    sources: [],
    formula: 'Σ ( unit_cost × returned_qty ) tiap alokasi',
    method: 'Bagian HPP yang batal karena barang diretur ke batch. Sumber: kolom returned_quantity × unit_cost di sale_item_batches.' },
  { label: 'Refund ke Customer', value: '−' + fmt(props.totals.refunded), tone: 'rose',
    sources: ['Laporan Penjualan · tab Return · Total Nilai Return'],
    formula: 'Σ return_items.subtotal (excl. rejected)',
    method: 'Uang yang dikembalikan ke customer atas retur yang tidak ditolak. Telusuri di tab Returns kolom Total. Sumber: tabel return_items.' },
  { label: 'Profit Kotor (nilai jual − modal)', value: fmt(props.totals.profit), tone: 'slate',
    sources: [],
    formula: 'Σ unit_price×(qty−retur) − Σ unit_cost×(qty−retur)',
    method: 'Laba "murni jualan": harga jual tercatat dikurangi modal, atas unit yang benar-benar tinggal di customer (qty dikurangi retur). TIDAK dipotong diskon transaksi/manual/promo, dan unit yang diretur dikeluarkan. Selisih dengan Profit Bersih = total diskon yang diberikan. Telusuri di expand sale → baris batch (jual − modal per unit).' },
  { label: 'Total Keuntungan (Profit Bersih)', value: fmt(props.totals.profit_net), tone: 'teal',
    sources: ['Dashboard · Total Keuntungan', 'Laporan Penjualan · Total Profit'],
    formula: 'Σ [ (total_amount − refund) − (HPP − HPP retur) ]',
    method: 'Laba final setelah retur: pendapatan bersih dikurangi HPP bersih, per transaksi. Formula identik dengan Dashboard & Laporan Penjualan. Lihat tab Sales kolom Profit Bersih.' },
  { label: 'Pajak Tercatat', value: fmt(props.totals.tax), tone: 'slate',
    sources: [],
    formula: 'Σ sales.tax_amount',
    method: 'Pajak yang tercatat di tiap transaksi. Sumber: kolom tax_amount tiap sale.' },
  { label: 'Nilai Persediaan (live)', value: fmt(props.totals.inventory_value), tone: 'slate',
    sources: ['Manajemen Produk · nilai stok', 'Neraca · Aset Lancar · Persediaan'],
    formula: 'Σ ( stock_quantity × cost_price ) semua batch',
    method: 'Nilai stok saat ini berbasis modal FIFO per batch — tidak tergantung rentang tanggal. Telusuri di tab Batches kolom Nilai Stok.' },
  { label: 'Total Transaksi', value: String(props.totals.sales_count), tone: 'slate',
    sources: ['Dashboard · Total Transaksi', 'Laporan Penjualan · Total Transaksi'],
    formula: 'COUNT(sales)',
    method: 'Jumlah sale berstatus completed / partial_return / returned dalam rentang tanggal. Sama dengan jumlah baris di tab Sales.' },
  { label: 'Produk Terjual (net)', value: String(props.totals.products_sold_net), tone: 'slate',
    sources: ['Dashboard · Produk Terjual', 'Laporan Penjualan · Item Terjual'],
    formula: 'Σ ( qty − returned_qty ) tiap item',
    method: 'Jumlah unit terjual bersih setelah dikurangi unit yang diretur. Sumber: sale_items.quantity dikurangi returned_quantity alokasi.' },
  { label: 'Jumlah Return', value: String(props.totals.returns_count), tone: 'slate',
    sources: ['Laporan Penjualan · tab Return · Total Return'],
    formula: 'COUNT(returns excl. rejected)',
    method: 'Banyaknya transaksi retur yang tidak ditolak dalam rentang. Sama dengan jumlah baris di tab Returns.' },
])

const expandedCards = ref<Set<number>>(new Set())
function toggleCard(i: number) {
  if (expandedCards.value.has(i)) expandedCards.value.delete(i)
  else expandedCards.value.add(i)
}
const allCardsExpanded = computed(() => expandedCards.value.size === totalsCards.value.length)
function toggleAllCards() {
  if (allCardsExpanded.value) expandedCards.value = new Set()
  else expandedCards.value = new Set(totalsCards.value.map((_, i) => i))
}

function toneStyle(t: string) {
  switch (t) {
    case 'emerald': return { bg: '#ecfdf5', color: '#047857', border: '#a7f3d0' }
    case 'rose':    return { bg: '#fff1f2', color: '#be123c', border: '#fecdd3' }
    case 'amber':   return { bg: '#fffbeb', color: '#b45309', border: '#fde68a' }
    case 'teal':    return { bg: '#f0fdfa', color: '#0f766e', border: '#99f6e4' }
    default:        return { bg: '#f8fafc', color: '#334155', border: '#e2e8f0' }
  }
}
</script>

<template>
  <div class="audit-page px-6 py-5 space-y-5">
    <header class="flex flex-wrap items-end gap-3">
      <div>
        <h1 class="text-xl font-bold" style="color: #0f172a;">Audit Keuangan</h1>
        <p class="text-xs" style="color: #64748b;">
          Halaman tersembunyi — akses lewat URL <code>/admin/__audit</code>. Untuk verifikasi pendapatan, HPP, profit, diskon, promo cukai, return, dan mutasi stok.
        </p>
      </div>
      <div class="ml-auto flex items-center gap-2">
        <label class="text-xs font-semibold" style="color: #334155;">Dari</label>
        <input v-model="from" type="date" class="h-8 rounded border px-2 text-xs" style="border-color: #e2e8f0;" />
        <label class="text-xs font-semibold" style="color: #334155;">Sampai</label>
        <input v-model="to" type="date" class="h-8 rounded border px-2 text-xs" style="border-color: #e2e8f0;" />
        <button class="h-8 rounded px-3 text-xs font-semibold" style="background: #14b8a6; color: #fff;" @click="applyFilter">Terapkan</button>
      </div>
    </header>

    <!-- Cross-period refund warning -->
    <div
      v-if="totals.cross_period_refund_count > 0"
      class="flex items-start gap-2 rounded-md border p-3"
      style="background:#fffbeb; border-color:#fde68a;"
    >
      <AlertTriangle class="h-4 w-4 mt-0.5 shrink-0" style="color:#b45309;" />
      <div class="text-xs" style="color:#78350f;">
        <strong>{{ totals.cross_period_refund_count }} retur</strong> di periode ini berasal dari sale di luar rentang ini
        (total {{ fmt(totals.cross_period_refund_amount) }}).
        Refund ini mengurangi kas/bank periode sekarang tapi profit sale aslinya tetap tercatat di periode lampau —
        laporan periode lampau bisa terlihat lebih untung daripada realitas akhirnya. Gabungkan dengan rentang yang lebih luas untuk gambaran utuh.
      </div>
    </div>

    <!-- Summary cards — tiap angka di-tag dengan fitur lain yang menampilkannya -->
    <div class="flex items-center justify-between">
      <p class="text-[11px]" style="color:#64748b;">
        Klik <Info class="inline h-3 w-3 align-text-bottom" /> di tiap kartu untuk melihat rumus & metode perhitungannya.
      </p>
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-[11px] font-semibold transition hover:opacity-90"
        style="background:#f1f5f9; color:#334155;"
        @click="toggleAllCards"
      >
        <Info class="h-3 w-3" />
        {{ allCardsExpanded ? 'Tutup semua rumus' : 'Buka semua rumus' }}
      </button>
    </div>
    <div class="grid grid-cols-2 gap-2 md:grid-cols-3 lg:grid-cols-4">
      <div
        v-for="(c, i) in totalsCards"
        :key="i"
        class="rounded-lg border p-3"
        :style="{ background: toneStyle(c.tone).bg, borderColor: toneStyle(c.tone).border }"
      >
        <div class="flex items-start justify-between gap-2">
          <p class="text-[10px] font-semibold uppercase tracking-wider" :style="{ color: toneStyle(c.tone).color }">
            {{ c.label }}
          </p>
          <button
            type="button"
            class="shrink-0 rounded p-0.5 transition hover:bg-black/5"
            :title="expandedCards.has(i) ? 'Sembunyikan rumus' : 'Lihat rumus & metode'"
            @click="toggleCard(i)"
          >
            <Info class="h-3.5 w-3.5" :style="{ color: toneStyle(c.tone).color, opacity: expandedCards.has(i) ? 1 : 0.55 }" />
          </button>
        </div>
        <p class="mt-1 text-lg font-bold tabular-nums" :style="{ color: toneStyle(c.tone).color }">{{ c.value }}</p>
        <div v-if="c.sources.length" class="mt-1.5 flex flex-wrap gap-1">
          <span
            v-for="src in c.sources"
            :key="src"
            class="inline-flex items-center gap-1 rounded-sm px-1.5 py-0.5 text-[9px] font-medium"
            style="background: rgba(15, 23, 42, 0.06); color: #475569;"
          >
            <Link2 class="h-2.5 w-2.5" />
            {{ src }}
          </span>
        </div>

        <!-- Rumus & metode — untuk verifikasi manual -->
        <div v-if="expandedCards.has(i)" class="mt-2 space-y-2 border-t pt-2" style="border-color: rgba(15,23,42,0.1);">
          <div>
            <div class="flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider" style="color:#64748b;">
              <Sigma class="h-2.5 w-2.5" /> Rumus
            </div>
            <code class="mt-0.5 block rounded bg-black/5 px-1.5 py-1 font-mono text-[10px] leading-snug" style="color:#0f172a;">{{ c.formula }}</code>
          </div>
          <div>
            <div class="flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider" style="color:#64748b;">
              <FlaskConical class="h-2.5 w-2.5" /> Metode
            </div>
            <p class="mt-0.5 text-[10px] leading-snug" style="color:#475569;">{{ c.method }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 border-b" style="border-color: #e2e8f0;">
      <button
        v-for="t in (['neraca','sales','returns','mutations','batches'] as const)"
        :key="t"
        class="cursor-pointer px-4 py-2 text-xs font-semibold border-b-2 -mb-px"
        :style="tab === t
          ? 'color: #0f766e; border-color: #14b8a6;'
          : 'color: #64748b; border-color: transparent;'"
        @click="tab = t"
      >
        {{ t === 'neraca' ? 'Neraca'
          : t === 'sales' ? `Sales (${sales.length})`
          : t === 'returns' ? `Returns (${returns.length})`
          : t === 'mutations' ? `Stock Mutations (${mutations.length})`
          : `Batches (${batches.length})` }}
      </button>
    </div>

    <!-- NERACA TAB -->
    <div v-if="tab === 'neraca'" class="space-y-3">
      <div class="rounded-lg border bg-white p-4" style="border-color: #e2e8f0;">
        <div class="mb-3 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold" style="color: #0f172a;">Laporan Neraca (Balance Sheet)</h3>
            <p class="text-[11px]" style="color: #64748b;">
              Per {{ fmtDate(neraca.period_end + 'T23:59:59') }}
              <span v-if="neraca.as_of_date">· Saldo awal {{ neraca.as_of_date }}</span>
            </p>
          </div>
          <div class="flex items-center gap-2">
            <a
              :href="`/admin/__audit/neraca-detail?from=${from}&to=${to}`"
              class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-semibold hover:opacity-90"
              style="background:#0f766e; color:#fff;"
            >
              <Search class="h-3.5 w-3.5" /> Tracing Sumber
            </a>
            <div
              class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold"
              :style="neraca.balanced
                ? 'background:#ecfdf5; color:#047857;'
                : 'background:#fff1f2; color:#be123c;'"
            >
              <CheckCircle2 v-if="neraca.balanced" class="h-3.5 w-3.5" />
              <AlertCircle v-else class="h-3.5 w-3.5" />
              {{ neraca.balanced ? 'Seimbang' : 'Selisih: ' + fmt(neraca.difference) }}
            </div>
          </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2">
          <!-- ASET -->
          <div class="rounded-md border" style="border-color: #e2e8f0;">
            <div class="border-b px-3 py-2 text-xs font-bold uppercase tracking-wide" style="border-color:#e2e8f0; background:#f8fafc; color:#334155;">
              Aset
            </div>
            <table class="w-full text-xs">
              <tbody>
                <tr><td colspan="2" class="px-3 pt-2 text-[10px] font-bold uppercase" style="color:#64748b;">Aset Lancar</td></tr>
                <tr><td class="px-3 py-1.5" style="color:#334155;">Kas</td>
                    <td class="px-3 py-1.5 text-right tabular-nums" style="color:#0f172a;">{{ fmt(neraca.assets.cash) }}</td></tr>
                <tr><td class="px-3 py-1.5" style="color:#334155;">Bank / E-Wallet / QRIS</td>
                    <td class="px-3 py-1.5 text-right tabular-nums" style="color:#0f172a;">{{ fmt(neraca.assets.bank) }}</td></tr>
                <tr><td class="px-3 py-1.5" style="color:#334155;">Persediaan (live FIFO)</td>
                    <td class="px-3 py-1.5 text-right tabular-nums" style="color:#0f172a;">{{ fmt(neraca.assets.inventory_value) }}</td></tr>
                <tr style="background:#f8fafc;">
                    <td class="px-3 py-1.5 font-semibold" style="color:#0f172a;">Total Aset Lancar</td>
                    <td class="px-3 py-1.5 text-right font-semibold tabular-nums" style="color:#0f172a;">{{ fmt(neraca.assets.total_current_assets) }}</td></tr>

                <tr><td colspan="2" class="px-3 pt-3 text-[10px] font-bold uppercase" style="color:#64748b;">Aset Tetap</td></tr>
                <tr><td class="px-3 py-1.5" style="color:#334155;">Etalase, peralatan, dll</td>
                    <td class="px-3 py-1.5 text-right tabular-nums" style="color:#0f172a;">{{ fmt(neraca.assets.fixed_assets) }}</td></tr>

                <tr style="background:#ecfdf5;">
                    <td class="px-3 py-2 font-bold" style="color:#047857;">TOTAL ASET</td>
                    <td class="px-3 py-2 text-right font-bold tabular-nums" style="color:#047857;">{{ fmt(neraca.assets.total) }}</td></tr>
              </tbody>
            </table>
          </div>

          <!-- KEWAJIBAN + EKUITAS -->
          <div class="rounded-md border" style="border-color: #e2e8f0;">
            <div class="border-b px-3 py-2 text-xs font-bold uppercase tracking-wide" style="border-color:#e2e8f0; background:#f8fafc; color:#334155;">
              Kewajiban &amp; Ekuitas
            </div>
            <table class="w-full text-xs">
              <tbody>
                <tr><td colspan="2" class="px-3 pt-2 text-[10px] font-bold uppercase" style="color:#64748b;">Kewajiban</td></tr>
                <tr><td class="px-3 py-1.5" style="color:#334155;">Hutang Usaha</td>
                    <td class="px-3 py-1.5 text-right tabular-nums" style="color:#0f172a;">{{ fmt(neraca.liabilities.accounts_payable) }}</td></tr>
                <tr><td class="px-3 py-1.5" style="color:#334155;">Hutang Lain</td>
                    <td class="px-3 py-1.5 text-right tabular-nums" style="color:#0f172a;">{{ fmt(neraca.liabilities.other_liabilities) }}</td></tr>
                <tr style="background:#f8fafc;">
                    <td class="px-3 py-1.5 font-semibold" style="color:#0f172a;">Total Kewajiban</td>
                    <td class="px-3 py-1.5 text-right font-semibold tabular-nums" style="color:#0f172a;">{{ fmt(neraca.liabilities.total) }}</td></tr>

                <tr><td colspan="2" class="px-3 pt-3 text-[10px] font-bold uppercase" style="color:#64748b;">Ekuitas</td></tr>
                <tr><td class="px-3 py-1.5" style="color:#334155;">Modal Pemilik</td>
                    <td class="px-3 py-1.5 text-right tabular-nums" style="color:#0f172a;">{{ fmt(neraca.equity.capital) }}</td></tr>
                <tr><td class="px-3 py-1.5" style="color:#334155;">Laba Ditahan (akumulasi)</td>
                    <td class="px-3 py-1.5 text-right tabular-nums" style="color:#0f172a;">{{ fmt(neraca.equity.retained_earnings) }}</td></tr>
                <tr><td class="px-3 py-1.5" style="color:#334155;">Laba Periode Berjalan</td>
                    <td class="px-3 py-1.5 text-right tabular-nums" :style="{ color: neraca.equity.period_profit >= 0 ? '#047857' : '#be123c' }">{{ fmt(neraca.equity.period_profit) }}</td></tr>
                <tr style="background:#f8fafc;">
                    <td class="px-3 py-1.5 font-semibold" style="color:#0f172a;">Total Ekuitas</td>
                    <td class="px-3 py-1.5 text-right font-semibold tabular-nums" style="color:#0f172a;">{{ fmt(neraca.equity.total) }}</td></tr>

                <tr style="background:#ecfdf5;">
                    <td class="px-3 py-2 font-bold" style="color:#047857;">TOTAL KEWAJIBAN + EKUITAS</td>
                    <td class="px-3 py-2 text-right font-bold tabular-nums" style="color:#047857;">{{ fmt(neraca.total_liab_equity) }}</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="mt-3 grid gap-3 md:grid-cols-2 text-[11px]">
          <div class="rounded-md border p-3" style="border-color:#e2e8f0; background:#fafafa;">
            <p class="mb-1 font-semibold" style="color:#64748b;">Cashflow di Periode Ini</p>
            <div class="flex justify-between"><span style="color:#334155;">Kas masuk bersih</span>
              <span class="tabular-nums" :style="{ color: neraca.period_cashflow.cash_net >= 0 ? '#047857' : '#be123c' }">{{ fmt(neraca.period_cashflow.cash_net) }}</span></div>
            <div class="flex justify-between"><span style="color:#334155;">Bank/QRIS/E-Wallet bersih</span>
              <span class="tabular-nums" :style="{ color: neraca.period_cashflow.bank_net >= 0 ? '#047857' : '#be123c' }">{{ fmt(neraca.period_cashflow.bank_net) }}</span></div>
          </div>
          <div class="rounded-md border p-3" style="border-color:#e2e8f0; background:#fafafa;">
            <p class="mb-1 font-semibold" style="color:#64748b;">Saldo Awal (Cutoff Pembukuan)</p>
            <div class="flex justify-between"><span style="color:#334155;">Kas awal</span><span class="tabular-nums" style="color:#0f172a;">{{ fmt(neraca.opening.cash) }}</span></div>
            <div class="flex justify-between"><span style="color:#334155;">Bank awal</span><span class="tabular-nums" style="color:#0f172a;">{{ fmt(neraca.opening.bank) }}</span></div>
            <div class="flex justify-between"><span style="color:#334155;">Modal awal</span><span class="tabular-nums" style="color:#0f172a;">{{ fmt(neraca.opening.equity) }}</span></div>
            <div v-if="neraca.opening.notes" class="mt-1 text-[10px] italic" style="color:#64748b;">{{ neraca.opening.notes }}</div>
            <a href="/admin/__audit/opening-balance" class="mt-2 inline-flex items-center gap-1 text-[11px] font-semibold hover:underline" style="color:#0d9488;">
              <Settings class="h-3 w-3" /> Atur saldo awal
              <ArrowRight class="h-3 w-3" />
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- SALES TAB -->
    <div v-if="tab === 'sales'" class="overflow-hidden rounded-lg border bg-white" style="border-color: #e2e8f0;">
      <table class="w-full text-xs">
        <thead style="background: #f1f5f9;">
          <tr class="text-left" style="color: #475569;">
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Invoice</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Waktu</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Kasir</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Bayar</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Total</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Diskon Txn</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Promo</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">HPP</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Refund</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Profit Kotor</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Profit Bersih</th>
            <th class="px-3 py-2"></th>
          </tr>
        </thead>
        <tbody>
          <template v-for="s in sales" :key="s.id">
            <tr class="border-t cursor-pointer hover:bg-slate-50" style="border-color: #e2e8f0;" @click="toggleSale(s.id)">
              <td class="px-3 py-2 font-mono font-semibold" style="color: #0f766e;">{{ s.invoice }}</td>
              <td class="px-3 py-2" style="color: #334155;">{{ fmtDate(s.created_at) }}</td>
              <td class="px-3 py-2" style="color: #334155;">{{ s.cashier ?? '-' }}</td>
              <td class="px-3 py-2 capitalize" style="color: #334155;">{{ s.payment_method }}</td>
              <td class="px-3 py-2 text-right tabular-nums font-semibold" style="color: #0f172a;">{{ fmt(s.total_amount) }}</td>
              <td class="px-3 py-2 text-right tabular-nums" style="color: #b45309;">{{ s.txn_discount ? '−'+fmt(s.txn_discount) : '-' }}</td>
              <td class="px-3 py-2 text-right tabular-nums" style="color: #b45309;">{{ s.promo_savings ? '−'+fmt(s.promo_savings) : '-' }}</td>
              <td class="px-3 py-2 text-right tabular-nums" style="color: #be123c;">{{ fmt(s.hpp_total) }}</td>
              <td class="px-3 py-2 text-right tabular-nums" style="color: #be123c;">{{ s.refunded ? '−'+fmt(s.refunded) : '-' }}</td>
              <td class="px-3 py-2 text-right tabular-nums" :style="{ color: s.profit >= 0 ? '#047857' : '#be123c' }">{{ fmt(s.profit) }}</td>
              <td class="px-3 py-2 text-right tabular-nums font-bold" :style="{ color: s.profit_net >= 0 ? '#047857' : '#be123c' }">{{ fmt(s.profit_net) }}</td>
              <td class="px-3 py-2 text-right" style="color: #94a3b8;">
                <ChevronDown v-if="expandedSales.has(s.id)" class="ml-auto h-3.5 w-3.5" />
                <ChevronRight v-else class="ml-auto h-3.5 w-3.5" />
              </td>
            </tr>
            <tr v-if="expandedSales.has(s.id)" style="background: #f8fafc;">
              <td colspan="12" class="px-4 py-3">
                <div v-if="s.has_return" class="mb-2 flex items-center gap-1 text-[11px] font-semibold" style="color: #be123c;">
                  <AlertTriangle class="h-3 w-3" /> Transaksi ini memiliki return.
                </div>
                <div v-if="s.discount_code" class="mb-2 text-[11px]" style="color: #64748b;">
                  Voucher: <strong style="color: #334155;">{{ s.discount_code }}</strong> — {{ s.discount_label }}
                </div>
                <table class="w-full text-[11px]">
                  <thead>
                    <tr style="color: #64748b;">
                      <th class="px-2 py-1 text-left">Produk</th>
                      <th class="px-2 py-1 text-right">Qty</th>
                      <th class="px-2 py-1 text-right">Harga Cat.</th>
                      <th class="px-2 py-1 text-right">Disk. Manual</th>
                      <th class="px-2 py-1 text-right">Promo Cukai</th>
                      <th class="px-2 py-1 text-right">Total Item</th>
                      <th class="px-2 py-1 text-right">HPP</th>
                    </tr>
                  </thead>
                  <tbody>
                    <template v-for="it in s.items" :key="it.id">
                      <tr class="border-t" style="border-color: #e2e8f0;">
                        <td class="px-2 py-1 font-semibold" style="color: #0f172a;">
                          {{ it.product_name }}
                          <span v-if="!it.has_allocations" class="ml-1 rounded bg-amber-100 px-1 text-[9px] font-bold uppercase" style="color: #b45309;">legacy</span>
                        </td>
                        <td class="px-2 py-1 text-right tabular-nums">{{ it.quantity }}</td>
                        <td class="px-2 py-1 text-right tabular-nums">{{ fmt(it.unit_price) }}</td>
                        <td class="px-2 py-1 text-right tabular-nums" style="color: #b45309;">{{ it.manual_discount ? '−'+fmt(it.manual_discount) : '-' }}</td>
                        <td class="px-2 py-1 text-right tabular-nums" style="color: #b45309;">{{ it.promo_discount ? '−'+fmt(it.promo_discount) : '-' }}</td>
                        <td class="px-2 py-1 text-right tabular-nums font-semibold">{{ fmt(it.line_total) }}</td>
                        <td class="px-2 py-1 text-right tabular-nums" style="color: #be123c;">{{ fmt(it.hpp_total) }}</td>
                      </tr>
                      <tr v-for="a in it.allocations" :key="a.id" style="background: #fff;">
                        <td colspan="7" class="px-6 py-1">
                          <span class="inline-flex items-center gap-2 text-[10px]" style="color: #475569;">
                            <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono">batch {{ a.batch_lot ?? a.batch_id.slice(0,8) }}</span>
                            <span v-if="a.cukai_year" class="rounded px-1.5 py-0.5" :style="a.is_promo ? 'background:#fef3c7;color:#b45309;' : 'background:#e0f2fe;color:#0369a1;'">
                              cukai {{ a.cukai_year }}{{ a.is_promo ? ' (promo)' : '' }}
                            </span>
                            <span v-if="a.is_synthetic" class="rounded px-1.5 py-0.5 font-bold uppercase" style="background:#fef2f2;color:#b91c1c;" title="Alokasi sintetis hasil backfill — HPP berdasarkan AVG cost batch, bukan FIFO aktual.">
                              sintetis
                            </span>
                            <span>qty <strong>{{ a.quantity }}</strong></span>
                            <span v-if="a.returned_qty > 0" style="color:#be123c;">(returned {{ a.returned_qty }})</span>
                            <span>@ jual {{ fmt(a.unit_price) }}</span>
                            <span>modal {{ fmt(a.unit_cost) }}</span>
                            <span style="color:#047857;">= profit/unit {{ fmt(a.unit_price - a.unit_cost) }}</span>
                          </span>
                        </td>
                      </tr>
                    </template>
                  </tbody>
                </table>
              </td>
            </tr>
          </template>
          <tr v-if="!sales.length">
            <td colspan="10" class="px-3 py-8 text-center" style="color: #94a3b8;">Tidak ada transaksi pada rentang ini.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- RETURNS TAB -->
    <div v-if="tab === 'returns'" class="overflow-hidden rounded-lg border bg-white" style="border-color: #e2e8f0;">
      <table class="w-full text-xs">
        <thead style="background: #f1f5f9;">
          <tr class="text-left" style="color: #475569;">
            <th class="px-3 py-2 font-bold uppercase tracking-wide">No. Return</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Invoice</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Waktu</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Kasir</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Status</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Alasan</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Total</th>
          </tr>
        </thead>
        <tbody>
          <template v-for="r in returns" :key="r.id">
            <tr class="border-t" style="border-color: #e2e8f0;">
              <td class="px-3 py-2 font-mono" style="color: #0f766e;">{{ r.return_number }}</td>
              <td class="px-3 py-2 font-mono">{{ r.invoice }}</td>
              <td class="px-3 py-2">{{ fmtDate(r.created_at) }}</td>
              <td class="px-3 py-2">{{ r.cashier ?? '-' }}</td>
              <td class="px-3 py-2 capitalize">{{ r.status }}</td>
              <td class="px-3 py-2">{{ r.reason }}</td>
              <td class="px-3 py-2 text-right tabular-nums font-bold" style="color: #be123c;">{{ fmt(r.total_refunded) }}</td>
            </tr>
            <tr style="background: #f8fafc;">
              <td colspan="7" class="px-6 py-2">
                <div v-for="(it, idx) in r.items" :key="idx" class="text-[11px]" style="color: #475569;">
                  • <strong style="color: #334155;">{{ it.product_name }}</strong> ×{{ it.quantity }} @ {{ fmt(it.unit_price) }}
                  → kembali ke batch <span class="font-mono">{{ it.batch_lot ?? it.batch_id.slice(0,8) }}</span>
                  = <strong>{{ fmt(it.subtotal) }}</strong>
                </div>
              </td>
            </tr>
          </template>
          <tr v-if="!returns.length">
            <td colspan="7" class="px-3 py-8 text-center" style="color: #94a3b8;">Tidak ada return pada rentang ini.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- MUTATIONS TAB -->
    <div v-if="tab === 'mutations'" class="overflow-hidden rounded-lg border bg-white" style="border-color: #e2e8f0;">
      <table class="w-full text-xs">
        <thead style="background: #f1f5f9;">
          <tr class="text-left" style="color: #475569;">
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Waktu</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Tipe</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Produk</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Batch</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Cukai</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Qty</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Referensi</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Catatan</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="m in mutations" :key="m.id" class="border-t" style="border-color: #e2e8f0;">
            <td class="px-3 py-2">{{ fmtDate(m.created_at) }}</td>
            <td class="px-3 py-2">
              <span
                class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                :style="m.type === 'in' ? 'background:#dcfce7;color:#16a34a;'
                  : m.type === 'out' ? 'background:#fee2e2;color:#dc2626;'
                  : m.type === 'return' ? 'background:#dbeafe;color:#1d4ed8;'
                  : 'background:#fef3c7;color:#b45309;'"
              >{{ m.type }}</span>
            </td>
            <td class="px-3 py-2">{{ m.product_name }}</td>
            <td class="px-3 py-2 font-mono">{{ m.batch_lot ?? '-' }}</td>
            <td class="px-3 py-2">{{ m.cukai_year ?? '-' }}</td>
            <td class="px-3 py-2 text-right tabular-nums font-semibold">{{ m.quantity }}</td>
            <td class="px-3 py-2 font-mono text-[10px]" style="color: #64748b;">{{ m.reference }}</td>
            <td class="px-3 py-2" style="color: #475569;">{{ m.notes }}</td>
          </tr>
          <tr v-if="!mutations.length">
            <td colspan="8" class="px-3 py-8 text-center" style="color: #94a3b8;">Tidak ada mutasi pada rentang ini.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- BATCHES TAB -->
    <div v-if="tab === 'batches'" class="overflow-hidden rounded-lg border bg-white" style="border-color: #e2e8f0;">
      <table class="w-full text-xs">
        <thead style="background: #f1f5f9;">
          <tr class="text-left" style="color: #475569;">
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Produk</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Lot</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Cukai</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Modal</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Promo Price</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Sisa Stok</th>
            <th class="px-3 py-2 text-right font-bold uppercase tracking-wide">Nilai Stok</th>
            <th class="px-3 py-2 font-bold uppercase tracking-wide">Dibuat</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="b in batches" :key="b.id" class="border-t" style="border-color: #e2e8f0;">
            <td class="px-3 py-2 font-semibold" style="color: #0f172a;">{{ b.product_name }}</td>
            <td class="px-3 py-2 font-mono">{{ b.lot_number }}</td>
            <td class="px-3 py-2">
              <span v-if="b.cukai_year"
                class="rounded px-1.5 py-0.5 text-[10px] font-semibold"
                :style="b.is_promo ? 'background:#fef3c7;color:#b45309;' : 'background:#e0f2fe;color:#0369a1;'">
                {{ b.cukai_year }}{{ b.is_promo ? ' • promo' : '' }}
              </span>
              <span v-else style="color: #94a3b8;">-</span>
            </td>
            <td class="px-3 py-2 text-right tabular-nums">{{ fmt(b.cost_price) }}</td>
            <td class="px-3 py-2 text-right tabular-nums">{{ b.promo_price !== null ? fmt(b.promo_price) : '-' }}</td>
            <td class="px-3 py-2 text-right tabular-nums font-bold">{{ b.stock_quantity }}</td>
            <td class="px-3 py-2 text-right tabular-nums" style="color: #0f766e;">{{ fmt(b.stock_value) }}</td>
            <td class="px-3 py-2">{{ fmtDate(b.created_at) }}</td>
          </tr>
          <tr v-if="!batches.length">
            <td colspan="8" class="px-3 py-8 text-center" style="color: #94a3b8;">Belum ada batch.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<style scoped>
.audit-page {
  background: #f9fafb;
  min-height: 100vh;
}
</style>
