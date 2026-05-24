<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowLeft, Banknote, CreditCard, Boxes, Tag, TrendingUp } from 'lucide-vue-next'
import AdminLayout from '@/layouts/admin/AdminLayout.vue'

defineOptions({
  layout: (h: any, page: any) => h(AdminLayout, {}, () => page),
})

interface CashIn { id: number; invoice: string; created_at: string; amount: number; status: string }
interface CashOut { id: string; return_number: string; invoice: string; created_at: string; amount: number }
interface BankIn extends CashIn { payment_method: string }
interface BankOut extends CashOut { payment_method: string }
interface BatchRow { id: string; product_name: string; lot_number: string; cukai_year: number | null; is_promo: boolean; cost_price: number; stock: number; value: number }
interface DiscountRow { id: number; invoice: string; created_at: string; txn_discount: number; manual_discount: number; promo_discount: number; discount_code: string | null; discount_label: string | null; total_amount: number }
interface ProfitRow { id: number; invoice: string; created_at: string; payment_method: string; status: string; total_amount: number; net_revenue: number; net_hpp: number; profit: number }

const props = defineProps<{
  from: string; to: string; as_of_date: string | null
  opening: {
    cash: number; bank: number; inventory_value: number; fixed_assets: number
    accounts_payable: number; other_liabilities: number; equity: number; retained_earnings: number; notes: string | null
  }
  cash: { opening: number; in_list: CashIn[]; out_list: CashOut[]; in_sum: number; out_sum: number; ending: number }
  bank: { opening: number; in_list: BankIn[]; out_list: BankOut[]; in_sum: number; out_sum: number; ending: number }
  inventory: { batches: BatchRow[]; total: number }
  discounts: DiscountRow[]
  profit_rows: ProfitRow[]
  profit_period: number
  retained_accum: number
}>()

const from = ref(props.from)
const to = ref(props.to)
function applyFilter() {
  router.get('/admin/__audit/neraca-detail', { from: from.value, to: to.value }, { preserveState: false })
}

function fmt(n: number): string {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0)
}
function fmtDate(iso: string | null): string {
  if (!iso) return '-'
  return new Date(iso).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
}
function paymentLabel(m: string): string {
  const map: Record<string, string> = { cash: 'Tunai', bank_transfer: 'Transfer', qris: 'QRIS', e_wallet: 'E-Wallet' }
  return map[m] ?? m
}

type SectionKey = 'cash' | 'bank' | 'inventory' | 'discount' | 'profit' | 'equity'
const activeSection = ref<SectionKey>('cash')

const sections: { key: SectionKey; label: string; icon: any; total: () => number }[] = [
  { key: 'cash',      label: 'Kas',         icon: Banknote,    total: () => props.cash.ending },
  { key: 'bank',      label: 'Bank / QRIS / E-Wallet', icon: CreditCard, total: () => props.bank.ending },
  { key: 'inventory', label: 'Persediaan',  icon: Boxes,       total: () => props.inventory.total },
  { key: 'discount',  label: 'Diskon',      icon: Tag,         total: () => discountTotal.value },
  { key: 'profit',    label: 'Laba Periode', icon: TrendingUp, total: () => props.profit_period },
  { key: 'equity',    label: 'Modal & Hutang', icon: Banknote, total: () =>
      props.opening.equity + props.opening.retained_earnings + props.retained_accum + props.profit_period
      - props.opening.accounts_payable - props.opening.other_liabilities },
]

const discountTotal = computed(() =>
  props.discounts.reduce((s, r) => s + r.txn_discount + r.manual_discount + r.promo_discount, 0),
)
</script>

<template>
  <div class="mx-auto max-w-6xl space-y-4 p-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <button class="flex items-center gap-1.5 text-sm font-medium" style="color:#64748b;" @click="router.get('/admin/__audit')">
        <ArrowLeft class="h-4 w-4" /> Kembali ke Audit
      </button>
      <div class="flex items-center gap-2">
        <input v-model="from" type="date" class="rounded-md border px-2 py-1.5 text-xs" style="border-color:#e2e8f0;" />
        <span class="text-xs" style="color:#64748b;">s/d</span>
        <input v-model="to" type="date" class="rounded-md border px-2 py-1.5 text-xs" style="border-color:#e2e8f0;" />
        <button class="rounded-md px-3 py-1.5 text-xs font-semibold text-white" style="background:#14b8a6;" @click="applyFilter">Terapkan</button>
      </div>
    </div>

    <div class="rounded-lg border bg-white p-4" style="border-color:#e2e8f0;">
      <h1 class="text-base font-bold" style="color:#0f172a;">Tracing Sumber Neraca</h1>
      <p class="text-[11px]" style="color:#64748b;">
        Telusuri asal-usul setiap angka di Neraca. Periode: <strong>{{ from }}</strong> — <strong>{{ to }}</strong>
        <span v-if="as_of_date">· Saldo awal sejak {{ as_of_date }}</span>
      </p>
    </div>

    <!-- Section selector -->
    <div class="flex flex-wrap gap-1 rounded-md border bg-white p-1" style="border-color:#e2e8f0;">
      <button
        v-for="s in sections"
        :key="s.key"
        class="flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-semibold transition"
        :style="activeSection === s.key
          ? 'background:#0f766e; color:#fff;'
          : 'color:#475569;'"
        @click="activeSection = s.key"
      >
        <component :is="s.icon" class="h-3.5 w-3.5" />
        {{ s.label }}
        <span class="ml-1 rounded-full bg-white/20 px-1.5 py-0.5 text-[10px] tabular-nums"
              :style="activeSection === s.key ? '' : 'background:#f1f5f9; color:#475569;'">
          {{ fmt(s.total()) }}
        </span>
      </button>
    </div>

    <!-- ─── KAS ───────────────────────────────────────────────────────── -->
    <div v-if="activeSection === 'cash'" class="space-y-3">
      <div class="grid grid-cols-4 gap-2 rounded-lg border bg-white p-4" style="border-color:#e2e8f0;">
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Saldo Awal</p>
          <p class="text-sm font-bold tabular-nums" style="color:#0f172a;">{{ fmt(cash.opening) }}</p></div>
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Kas Masuk</p>
          <p class="text-sm font-bold tabular-nums" style="color:#047857;">+ {{ fmt(cash.in_sum) }}</p></div>
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Kas Keluar (Refund)</p>
          <p class="text-sm font-bold tabular-nums" style="color:#be123c;">− {{ fmt(cash.out_sum) }}</p></div>
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Saldo Akhir</p>
          <p class="text-sm font-bold tabular-nums" style="color:#0f766e;">{{ fmt(cash.ending) }}</p></div>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <div class="overflow-hidden rounded-lg border bg-white" style="border-color:#e2e8f0;">
          <div class="border-b px-3 py-2 text-xs font-bold" style="border-color:#e2e8f0; background:#ecfdf5; color:#047857;">
            Penjualan Tunai ({{ cash.in_list.length }})
          </div>
          <div class="max-h-96 overflow-y-auto">
            <table class="w-full text-[11px]">
              <thead style="background:#f8fafc;">
                <tr style="color:#64748b;">
                  <th class="px-2 py-1.5 text-left">Invoice</th>
                  <th class="px-2 py-1.5 text-left">Waktu</th>
                  <th class="px-2 py-1.5 text-right">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="t in cash.in_list" :key="t.id" class="border-t" style="border-color:#f1f5f9;">
                  <td class="px-2 py-1 font-mono" style="color:#0f766e;">{{ t.invoice }}</td>
                  <td class="px-2 py-1" style="color:#64748b;">{{ fmtDate(t.created_at) }}</td>
                  <td class="px-2 py-1 text-right tabular-nums" style="color:#047857;">{{ fmt(t.amount) }}</td>
                </tr>
                <tr v-if="!cash.in_list.length"><td colspan="3" class="px-2 py-4 text-center" style="color:#94a3b8;">Tidak ada penjualan tunai.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="overflow-hidden rounded-lg border bg-white" style="border-color:#e2e8f0;">
          <div class="border-b px-3 py-2 text-xs font-bold" style="border-color:#e2e8f0; background:#fff1f2; color:#be123c;">
            Refund Tunai ({{ cash.out_list.length }})
          </div>
          <div class="max-h-96 overflow-y-auto">
            <table class="w-full text-[11px]">
              <thead style="background:#f8fafc;">
                <tr style="color:#64748b;">
                  <th class="px-2 py-1.5 text-left">No. Return</th>
                  <th class="px-2 py-1.5 text-left">Invoice</th>
                  <th class="px-2 py-1.5 text-left">Waktu</th>
                  <th class="px-2 py-1.5 text-right">Jumlah</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="r in cash.out_list" :key="r.id" class="border-t" style="border-color:#f1f5f9;">
                  <td class="px-2 py-1 font-mono" style="color:#be123c;">{{ r.return_number }}</td>
                  <td class="px-2 py-1 font-mono" style="color:#0f766e;">{{ r.invoice }}</td>
                  <td class="px-2 py-1" style="color:#64748b;">{{ fmtDate(r.created_at) }}</td>
                  <td class="px-2 py-1 text-right tabular-nums" style="color:#be123c;">{{ fmt(r.amount) }}</td>
                </tr>
                <tr v-if="!cash.out_list.length"><td colspan="4" class="px-2 py-4 text-center" style="color:#94a3b8;">Tidak ada refund tunai.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── BANK ──────────────────────────────────────────────────────── -->
    <div v-if="activeSection === 'bank'" class="space-y-3">
      <div class="grid grid-cols-4 gap-2 rounded-lg border bg-white p-4" style="border-color:#e2e8f0;">
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Saldo Awal</p>
          <p class="text-sm font-bold tabular-nums" style="color:#0f172a;">{{ fmt(bank.opening) }}</p></div>
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Masuk</p>
          <p class="text-sm font-bold tabular-nums" style="color:#047857;">+ {{ fmt(bank.in_sum) }}</p></div>
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Keluar (Refund)</p>
          <p class="text-sm font-bold tabular-nums" style="color:#be123c;">− {{ fmt(bank.out_sum) }}</p></div>
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Saldo Akhir</p>
          <p class="text-sm font-bold tabular-nums" style="color:#0f766e;">{{ fmt(bank.ending) }}</p></div>
      </div>

      <div class="grid gap-3 md:grid-cols-2">
        <div class="overflow-hidden rounded-lg border bg-white" style="border-color:#e2e8f0;">
          <div class="border-b px-3 py-2 text-xs font-bold" style="border-color:#e2e8f0; background:#ecfdf5; color:#047857;">
            Penjualan Non-Tunai ({{ bank.in_list.length }})
          </div>
          <div class="max-h-96 overflow-y-auto">
            <table class="w-full text-[11px]">
              <thead style="background:#f8fafc;"><tr style="color:#64748b;">
                <th class="px-2 py-1.5 text-left">Invoice</th>
                <th class="px-2 py-1.5 text-left">Metode</th>
                <th class="px-2 py-1.5 text-left">Waktu</th>
                <th class="px-2 py-1.5 text-right">Jumlah</th>
              </tr></thead>
              <tbody>
                <tr v-for="t in bank.in_list" :key="t.id" class="border-t" style="border-color:#f1f5f9;">
                  <td class="px-2 py-1 font-mono" style="color:#0f766e;">{{ t.invoice }}</td>
                  <td class="px-2 py-1" style="color:#475569;">{{ paymentLabel(t.payment_method) }}</td>
                  <td class="px-2 py-1" style="color:#64748b;">{{ fmtDate(t.created_at) }}</td>
                  <td class="px-2 py-1 text-right tabular-nums" style="color:#047857;">{{ fmt(t.amount) }}</td>
                </tr>
                <tr v-if="!bank.in_list.length"><td colspan="4" class="px-2 py-4 text-center" style="color:#94a3b8;">Tidak ada penjualan non-tunai.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="overflow-hidden rounded-lg border bg-white" style="border-color:#e2e8f0;">
          <div class="border-b px-3 py-2 text-xs font-bold" style="border-color:#e2e8f0; background:#fff1f2; color:#be123c;">
            Refund Non-Tunai ({{ bank.out_list.length }})
          </div>
          <div class="max-h-96 overflow-y-auto">
            <table class="w-full text-[11px]">
              <thead style="background:#f8fafc;"><tr style="color:#64748b;">
                <th class="px-2 py-1.5 text-left">No. Return</th>
                <th class="px-2 py-1.5 text-left">Invoice</th>
                <th class="px-2 py-1.5 text-left">Metode</th>
                <th class="px-2 py-1.5 text-right">Jumlah</th>
              </tr></thead>
              <tbody>
                <tr v-for="r in bank.out_list" :key="r.id" class="border-t" style="border-color:#f1f5f9;">
                  <td class="px-2 py-1 font-mono" style="color:#be123c;">{{ r.return_number }}</td>
                  <td class="px-2 py-1 font-mono" style="color:#0f766e;">{{ r.invoice }}</td>
                  <td class="px-2 py-1" style="color:#475569;">{{ paymentLabel(r.payment_method) }}</td>
                  <td class="px-2 py-1 text-right tabular-nums" style="color:#be123c;">{{ fmt(r.amount) }}</td>
                </tr>
                <tr v-if="!bank.out_list.length"><td colspan="4" class="px-2 py-4 text-center" style="color:#94a3b8;">Tidak ada refund non-tunai.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ─── PERSEDIAAN ────────────────────────────────────────────────── -->
    <div v-if="activeSection === 'inventory'" class="space-y-3">
      <div class="rounded-lg border bg-white p-4" style="border-color:#e2e8f0;">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Total Nilai Persediaan</p>
            <p class="text-lg font-bold tabular-nums" style="color:#0f766e;">{{ fmt(inventory.total) }}</p>
            <p class="text-[11px]" style="color:#64748b;">{{ inventory.batches.length }} batch dengan stok &gt; 0</p>
          </div>
        </div>
      </div>
      <div class="overflow-hidden rounded-lg border bg-white" style="border-color:#e2e8f0;">
        <div class="max-h-[600px] overflow-y-auto">
          <table class="w-full text-[11px]">
            <thead style="background:#f8fafc;"><tr style="color:#64748b;">
              <th class="px-2 py-1.5 text-left">Produk</th>
              <th class="px-2 py-1.5 text-left">Lot</th>
              <th class="px-2 py-1.5 text-left">Cukai</th>
              <th class="px-2 py-1.5 text-right">HPP/Unit</th>
              <th class="px-2 py-1.5 text-right">Stok</th>
              <th class="px-2 py-1.5 text-right">Nilai</th>
            </tr></thead>
            <tbody>
              <tr v-for="b in inventory.batches" :key="b.id" class="border-t" style="border-color:#f1f5f9;">
                <td class="px-2 py-1 font-medium" style="color:#0f172a;">{{ b.product_name }}</td>
                <td class="px-2 py-1 font-mono" style="color:#64748b;">{{ b.lot_number }}</td>
                <td class="px-2 py-1">
                  <span :style="b.is_promo ? 'background:#fffbeb; color:#b45309; padding:1px 6px; border-radius:9999px;' : 'color:#475569;'">
                    {{ b.cukai_year ?? '-' }}{{ b.is_promo ? ' · promo' : '' }}
                  </span>
                </td>
                <td class="px-2 py-1 text-right tabular-nums" style="color:#be123c;">{{ fmt(b.cost_price) }}</td>
                <td class="px-2 py-1 text-right tabular-nums" style="color:#0f172a;">{{ b.stock }}</td>
                <td class="px-2 py-1 text-right tabular-nums font-semibold" style="color:#0f766e;">{{ fmt(b.value) }}</td>
              </tr>
              <tr v-if="!inventory.batches.length"><td colspan="6" class="px-2 py-6 text-center" style="color:#94a3b8;">Tidak ada batch dengan stok &gt; 0.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ─── DISKON ────────────────────────────────────────────────────── -->
    <div v-if="activeSection === 'discount'" class="space-y-3">
      <div class="grid grid-cols-3 gap-2 rounded-lg border bg-white p-4" style="border-color:#e2e8f0;">
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Diskon Transaksi (Voucher)</p>
          <p class="text-sm font-bold tabular-nums" style="color:#b45309;">{{ fmt(discounts.reduce((s,r)=>s+r.txn_discount,0)) }}</p></div>
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Diskon Manual</p>
          <p class="text-sm font-bold tabular-nums" style="color:#b45309;">{{ fmt(discounts.reduce((s,r)=>s+r.manual_discount,0)) }}</p></div>
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Penghematan Promo Cukai</p>
          <p class="text-sm font-bold tabular-nums" style="color:#b45309;">{{ fmt(discounts.reduce((s,r)=>s+r.promo_discount,0)) }}</p></div>
      </div>
      <div class="overflow-hidden rounded-lg border bg-white" style="border-color:#e2e8f0;">
        <div class="max-h-[600px] overflow-y-auto">
          <table class="w-full text-[11px]">
            <thead style="background:#f8fafc;"><tr style="color:#64748b;">
              <th class="px-2 py-1.5 text-left">Invoice</th>
              <th class="px-2 py-1.5 text-left">Waktu</th>
              <th class="px-2 py-1.5 text-left">Voucher</th>
              <th class="px-2 py-1.5 text-right">Diskon Txn</th>
              <th class="px-2 py-1.5 text-right">Diskon Manual</th>
              <th class="px-2 py-1.5 text-right">Promo Cukai</th>
              <th class="px-2 py-1.5 text-right">Total Dibayar</th>
            </tr></thead>
            <tbody>
              <tr v-for="d in discounts" :key="d.id" class="border-t" style="border-color:#f1f5f9;">
                <td class="px-2 py-1 font-mono" style="color:#0f766e;">{{ d.invoice }}</td>
                <td class="px-2 py-1" style="color:#64748b;">{{ fmtDate(d.created_at) }}</td>
                <td class="px-2 py-1" style="color:#475569;">{{ d.discount_code ?? '-' }} {{ d.discount_label ? '· '+d.discount_label : '' }}</td>
                <td class="px-2 py-1 text-right tabular-nums" style="color:#b45309;">{{ d.txn_discount > 0 ? '−'+fmt(d.txn_discount) : '-' }}</td>
                <td class="px-2 py-1 text-right tabular-nums" style="color:#b45309;">{{ d.manual_discount > 0 ? '−'+fmt(d.manual_discount) : '-' }}</td>
                <td class="px-2 py-1 text-right tabular-nums" style="color:#b45309;">{{ d.promo_discount > 0 ? '−'+fmt(d.promo_discount) : '-' }}</td>
                <td class="px-2 py-1 text-right tabular-nums font-semibold" style="color:#0f172a;">{{ fmt(d.total_amount) }}</td>
              </tr>
              <tr v-if="!discounts.length"><td colspan="7" class="px-2 py-6 text-center" style="color:#94a3b8;">Tidak ada diskon di periode ini.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ─── LABA ──────────────────────────────────────────────────────── -->
    <div v-if="activeSection === 'profit'" class="space-y-3">
      <div class="grid grid-cols-3 gap-2 rounded-lg border bg-white p-4" style="border-color:#e2e8f0;">
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Net Revenue Periode</p>
          <p class="text-sm font-bold tabular-nums" style="color:#047857;">{{ fmt(profit_rows.reduce((s,r)=>s+r.net_revenue,0)) }}</p></div>
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Net HPP Periode</p>
          <p class="text-sm font-bold tabular-nums" style="color:#be123c;">{{ fmt(profit_rows.reduce((s,r)=>s+r.net_hpp,0)) }}</p></div>
        <div><p class="text-[10px] font-semibold uppercase" style="color:#64748b;">Laba Bersih Periode</p>
          <p class="text-sm font-bold tabular-nums" :style="{ color: profit_period >= 0 ? '#0f766e' : '#be123c' }">{{ fmt(profit_period) }}</p></div>
      </div>
      <div class="overflow-hidden rounded-lg border bg-white" style="border-color:#e2e8f0;">
        <div class="max-h-[600px] overflow-y-auto">
          <table class="w-full text-[11px]">
            <thead style="background:#f8fafc;"><tr style="color:#64748b;">
              <th class="px-2 py-1.5 text-left">Invoice</th>
              <th class="px-2 py-1.5 text-left">Waktu</th>
              <th class="px-2 py-1.5 text-left">Metode</th>
              <th class="px-2 py-1.5 text-left">Status</th>
              <th class="px-2 py-1.5 text-right">Net Revenue</th>
              <th class="px-2 py-1.5 text-right">Net HPP</th>
              <th class="px-2 py-1.5 text-right">Profit</th>
            </tr></thead>
            <tbody>
              <tr v-for="p in profit_rows" :key="p.id" class="border-t" style="border-color:#f1f5f9;">
                <td class="px-2 py-1 font-mono" style="color:#0f766e;">{{ p.invoice }}</td>
                <td class="px-2 py-1" style="color:#64748b;">{{ fmtDate(p.created_at) }}</td>
                <td class="px-2 py-1" style="color:#475569;">{{ paymentLabel(p.payment_method) }}</td>
                <td class="px-2 py-1" style="color:#475569;">{{ p.status }}</td>
                <td class="px-2 py-1 text-right tabular-nums" style="color:#047857;">{{ fmt(p.net_revenue) }}</td>
                <td class="px-2 py-1 text-right tabular-nums" style="color:#be123c;">{{ fmt(p.net_hpp) }}</td>
                <td class="px-2 py-1 text-right tabular-nums font-bold" :style="{ color: p.profit >= 0 ? '#047857' : '#be123c' }">{{ fmt(p.profit) }}</td>
              </tr>
              <tr v-if="!profit_rows.length"><td colspan="7" class="px-2 py-6 text-center" style="color:#94a3b8;">Tidak ada penjualan di periode ini.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ─── MODAL & HUTANG ────────────────────────────────────────────── -->
    <div v-if="activeSection === 'equity'" class="space-y-3">
      <div class="rounded-lg border bg-white p-4" style="border-color:#e2e8f0;">
        <h3 class="mb-3 text-sm font-bold" style="color:#0f172a;">Modal &amp; Hutang</h3>
        <table class="w-full text-xs">
          <tbody>
            <tr><td class="py-1.5 font-semibold" style="color:#0d9488;">Ekuitas</td><td></td></tr>
            <tr><td class="py-1 pl-4" style="color:#334155;">Modal Pemilik (dari saldo awal)</td>
                <td class="py-1 text-right tabular-nums" style="color:#0f172a;">{{ fmt(opening.equity) }}</td></tr>
            <tr><td class="py-1 pl-4" style="color:#334155;">Laba Ditahan (dari saldo awal)</td>
                <td class="py-1 text-right tabular-nums" style="color:#0f172a;">{{ fmt(opening.retained_earnings) }}</td></tr>
            <tr><td class="py-1 pl-4" style="color:#334155;">Akumulasi Laba sejak {{ as_of_date }} sampai {{ from }}</td>
                <td class="py-1 text-right tabular-nums" :style="{ color: retained_accum >= 0 ? '#047857' : '#be123c' }">{{ fmt(retained_accum) }}</td></tr>
            <tr><td class="py-1 pl-4" style="color:#334155;">Laba Periode Berjalan ({{ from }} — {{ to }})</td>
                <td class="py-1 text-right tabular-nums" :style="{ color: profit_period >= 0 ? '#047857' : '#be123c' }">{{ fmt(profit_period) }}</td></tr>

            <tr><td colspan="2" class="pt-3 font-semibold" style="color:#0d9488;">Kewajiban</td></tr>
            <tr><td class="py-1 pl-4" style="color:#334155;">Hutang Usaha</td>
                <td class="py-1 text-right tabular-nums" style="color:#0f172a;">{{ fmt(opening.accounts_payable) }}</td></tr>
            <tr><td class="py-1 pl-4" style="color:#334155;">Hutang Lain</td>
                <td class="py-1 text-right tabular-nums" style="color:#0f172a;">{{ fmt(opening.other_liabilities) }}</td></tr>
            <tr v-if="opening.notes"><td colspan="2" class="pt-2 text-[10px] italic" style="color:#64748b;">Catatan saldo awal: {{ opening.notes }}</td></tr>
          </tbody>
        </table>
        <p class="mt-3 text-[11px]" style="color:#64748b;">
          Angka modal dan hutang berasal dari <a href="/admin/__audit/opening-balance" class="font-semibold hover:underline" style="color:#0d9488;">Saldo Awal</a>.
          Perubahan ke kategori ini saat ini hanya melalui form Saldo Awal.
        </p>
      </div>
    </div>
  </div>
</template>
