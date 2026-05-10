<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import VueApexCharts from 'vue3-apexcharts'
import {
    TrendingUp,
    TrendingDown,
    Minus,
    DollarSign,
    ShoppingCart,
    Banknote,
    Package,
} from 'lucide-vue-next'
import AdminLayout from '@/layouts/admin/AdminLayout.vue'
import type { DashboardPageProps, Period } from '@/types/admin'


defineOptions({
    layout: (h: any, page: any) => h(AdminLayout, {}, () => page),
})

const props = defineProps<DashboardPageProps>()

const selectedPeriod = ref<Period>(props.period)
const customStart    = ref(props.date_range.start)
const customEnd      = ref(props.date_range.end)

const isDark = ref(document.documentElement.classList.contains('dark'))
onMounted(() => {
    const observer = new MutationObserver(() => {
        isDark.value = document.documentElement.classList.contains('dark')
    })
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

const periodOptions: { value: Period; label: string }[] = [
    { value: 'daily',     label: 'Hari Ini' },
    { value: 'weekly',    label: 'Minggu Ini' },
    { value: 'monthly',   label: 'Bulan Ini' },
    { value: 'quarterly', label: 'Kuartal Ini' },
    { value: 'yearly',    label: 'Tahun Ini' },
    { value: 'custom',    label: 'Kustom' },
]

const comparisonLabel = computed(() => {
    const map: Record<Period, string> = {
        daily:     'vs Kemarin',
        weekly:    'vs Minggu Lalu',
        monthly:   'vs Bulan Lalu',
        quarterly: 'vs Kuartal Lalu',
        yearly:    'vs Tahun Lalu',
        custom:    'vs Periode Sebelumnya',
    }
    return map[selectedPeriod.value]
})

function applyFilter() {
    const params: Record<string, string> = { period: selectedPeriod.value }
    if (selectedPeriod.value === 'custom') {
        params.start_date = customStart.value
        params.end_date   = customEnd.value
    }
    router.get('/admin/dashboard', params, {
        preserveState: true,
        preserveScroll: true,
        only: ['stats', 'period', 'date_range', 'revenue_trend', 'top_products', 'top_categories', 'top_brands', 'payment_methods'],
    })
}

watch(selectedPeriod, (val) => {
    if (val !== 'custom') applyFilter()
})

function formatCurrency(n: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR',
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    }).format(n)
}

function formatNumber(n: number): string {
    return new Intl.NumberFormat('id-ID').format(n)
}

function trendClass(pct: number): string {
    if (pct > 0) return 'trend-up'
    if (pct < 0) return 'trend-down'
    return 'trend-neutral'
}

const statCards = computed(() => [
    { title: 'Total Pendapatan', value: formatCurrency(props.stats.revenue.current),      change: props.stats.revenue.change_pct,       icon: DollarSign,  colorVar: '--primary'  },
    { title: 'Total Transaksi',  value: formatNumber(props.stats.transactions.current),   change: props.stats.transactions.change_pct,  icon: ShoppingCart, colorVar: '--chart-3' },
    { title: 'Total Keuntungan', value: formatCurrency(props.stats.profit.current),       change: props.stats.profit.change_pct,        icon: Banknote,    colorVar: '--chart-5'  },
    { title: 'Produk Terjual',   value: formatNumber(props.stats.products_sold.current),  change: props.stats.products_sold.change_pct, icon: Package,     colorVar: '--chart-1'  },
])

const chartTheme = computed(() => isDark.value ? 'dark' : 'light')

const chartBaseOptions = computed(() => ({
    chart: {
        background: 'transparent',
        toolbar: { show: false },
        animations: { enabled: true, speed: 400 },
        fontFamily: 'inherit',
    },
    theme: { mode: chartTheme.value as 'dark' | 'light' },
    tooltip: { theme: chartTheme.value, style: { fontSize: '12px' } },
    grid: {
        borderColor: isDark.value ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.07)',
        strokeDashArray: 4,
    },
}))

const trendLabel = computed(() => {
    if (selectedPeriod.value === 'daily')     return 'Jam'
    if (selectedPeriod.value === 'yearly')    return 'Bulan'
    if (selectedPeriod.value === 'quarterly') return 'Minggu'
    return 'Tanggal'
})

const revenueTrendOptions = computed(() => ({
    ...chartBaseOptions.value,
    chart: { ...chartBaseOptions.value.chart, id: 'revenue-trend', type: 'area', height: 280 },
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.45, opacityTo: 0.02, stops: [0, 90, 100] },
    },
    xaxis: {
        categories: props.revenue_trend.map(p => p.period),
        labels: { style: { fontSize: '11px' }, rotate: -30, rotateAlways: false },
        title: { text: trendLabel.value, style: { fontSize: '11px' } },
    },
    yaxis: [
        {
            title: { text: 'Pendapatan (Rp)', style: { fontSize: '11px' } },
            labels: { formatter: (v: number) => formatCurrency(v), style: { fontSize: '10px' } },
        },
        {
            opposite: true,
            title: { text: 'Transaksi', style: { fontSize: '11px' } },
            labels: { formatter: (v: number) => formatNumber(v), style: { fontSize: '10px' } },
        },
    ],
    colors: ['hsl(221 83% 53%)', 'hsl(142 71% 45%)'],
    legend: { position: 'top' as const },
    tooltip: {
        ...chartBaseOptions.value.tooltip,
        shared: true, intersect: false,
        y: [
            { formatter: (v: number) => formatCurrency(v) },
            { formatter: (v: number) => formatNumber(v) + ' transaksi' },
        ],
    },
    noData: { text: 'Belum ada data penjualan', style: { fontSize: '13px' } },
}))

const revenueTrendSeries = computed(() => [
    { name: 'Pendapatan', data: props.revenue_trend.map(p => p.revenue) },
    { name: 'Transaksi',  data: props.revenue_trend.map(p => p.transactions) },
])

const barDataLabels = computed(() => ({
    enabled: true,
    formatter: (v: number) => formatCurrency(v),
    offsetX: 8,
    style: { fontSize: '10px', colors: [isDark.value ? '#ccc' : '#444'] },
}))

const barPlotOptions = {
    bar: { horizontal: true, borderRadius: 4, barHeight: '60%', dataLabels: { position: 'top' } },
}

const topProductsOptions = computed(() => ({
    ...chartBaseOptions.value,
    chart: { ...chartBaseOptions.value.chart, type: 'bar', height: 220 },
    plotOptions: barPlotOptions,
    dataLabels: barDataLabels.value,
    xaxis: {
        categories: props.top_products.map(p => p.name),
        labels: { formatter: (v: number) => formatCurrency(v), style: { fontSize: '10px' } },
    },
    colors: ['hsl(221 83% 53%)'],
    noData: { text: 'Belum ada data', style: { fontSize: '13px' } },
    tooltip: { ...chartBaseOptions.value.tooltip, y: { formatter: (v: number) => formatCurrency(v) } },
}))

const topProductsSeries = computed(() => [{ name: 'Pendapatan', data: props.top_products.map(p => p.revenue) }])

const donutColors = [
    'hsl(221 83% 53%)', 'hsl(142 71% 45%)', 'hsl(38 92% 50%)',
    'hsl(291 64% 42%)', 'hsl(15 75% 55%)',
]

const donutPlotOptions = (label: string, formatter: (w: any) => string) => ({
    pie: {
        donut: {
            size: '65%',
            labels: {
                show: true,
                total: { show: true, label, formatter },
            },
        },
    },
})

const topCategoriesOptions = computed(() => ({
    ...chartBaseOptions.value,
    chart: { ...chartBaseOptions.value.chart, type: 'donut', height: 260 },
    labels: props.top_categories.map(c => c.name),
    colors: donutColors,
    plotOptions: donutPlotOptions('Total', (w: any) =>
        formatCurrency(w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0))
    ),
    dataLabels: { enabled: false },
    legend: { position: 'bottom' as const, fontSize: '11px' },
    noData: { text: 'Belum ada data', style: { fontSize: '13px' } },
    tooltip: { ...chartBaseOptions.value.tooltip, y: { formatter: (v: number) => formatCurrency(v) } },
}))

const topCategoriesSeries = computed(() => props.top_categories.map(c => c.revenue))

const topBrandsOptions = computed(() => ({
    ...chartBaseOptions.value,
    chart: { ...chartBaseOptions.value.chart, type: 'bar', height: 220 },
    plotOptions: barPlotOptions,
    dataLabels: barDataLabels.value,
    xaxis: {
        categories: props.top_brands.map(b => b.name),
        labels: { formatter: (v: number) => formatCurrency(v), style: { fontSize: '10px' } },
    },
    colors: ['hsl(291 64% 42%)'],
    noData: { text: 'Belum ada data', style: { fontSize: '13px' } },
    tooltip: { ...chartBaseOptions.value.tooltip, y: { formatter: (v: number) => formatCurrency(v) } },
}))

const topBrandsSeries = computed(() => [{ name: 'Pendapatan', data: props.top_brands.map(b => b.revenue) }])

const paymentMethodLabel = (method: string): string => {
    const map: Record<string, string> = {
        cash:          'Tunai',
        e_wallet:      'E-Wallet',
        bank_transfer: 'Transfer Bank',
        qris:          'QRIS',
    }
    return map[method.toLowerCase()] ?? method
}

const paymentOptions = computed(() => ({
    ...chartBaseOptions.value,
    chart: { ...chartBaseOptions.value.chart, type: 'donut', height: 260 },
    labels: props.payment_methods.map(p => paymentMethodLabel(p.method)),
    colors: ['hsl(142 71% 45%)', 'hsl(221 83% 53%)', 'hsl(38 92% 50%)', 'hsl(15 75% 55%)', 'hsl(291 64% 42%)'],
    plotOptions: donutPlotOptions('Transaksi', (w: any) =>
        formatNumber(w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0))
    ),
    dataLabels: { enabled: false },
    legend: { position: 'bottom' as const, fontSize: '11px' },
    noData: { text: 'Belum ada data', style: { fontSize: '13px' } },
    tooltip: { ...chartBaseOptions.value.tooltip, y: { formatter: (v: number) => formatNumber(v) + ' transaksi' } },
}))

const paymentSeries = computed(() => props.payment_methods.map(p => p.count))

const comparisonRows = computed(() => [
    { label: 'Pendapatan',     cur: formatCurrency(props.stats.revenue.current),      prev: formatCurrency(props.stats.revenue.previous),      pct: props.stats.revenue.change_pct },
    { label: 'Transaksi',      cur: formatNumber(props.stats.transactions.current),   prev: formatNumber(props.stats.transactions.previous),   pct: props.stats.transactions.change_pct },
    { label: 'Keuntungan',     cur: formatCurrency(props.stats.profit.current),       prev: formatCurrency(props.stats.profit.previous),       pct: props.stats.profit.change_pct },
    { label: 'Produk Terjual', cur: formatNumber(props.stats.products_sold.current),  prev: formatNumber(props.stats.products_sold.previous),  pct: props.stats.products_sold.change_pct },
])
</script>

<template>
    <Head title="Dashboard Pintar" />

    <div class="adm-page">
        <!-- Period Filter -->
        <div class="filter-bar">
            <div class="filter-tabs">
                <button
                    v-for="opt in periodOptions"
                    :key="opt.value"
                    class="filter-tab"
                    :class="{ 'filter-tab--active': selectedPeriod === opt.value }"
                    @click="selectedPeriod = opt.value"
                >
                    {{ opt.label }}
                </button>
            </div>

            <div v-if="selectedPeriod === 'custom'" class="custom-range">
                <input v-model="customStart" type="date" class="date-input" :max="customEnd" />
                <span class="date-sep">s/d</span>
                <input v-model="customEnd" type="date" class="date-input" :min="customStart" />
                <button class="apply-btn" @click="applyFilter">Terapkan</button>
            </div>
        </div>

        <!-- Period hint — sits on dark page bg → pos-text-primary -->
        <p class="period-hint">
            {{ date_range.start }} – {{ date_range.end }} &nbsp; &nbsp; {{ comparisonLabel }}
            ({{ date_range.prev_start }} – {{ date_range.prev_end }})
        </p>

        <!-- 4 KPI Cards -->
        <div class="stats-grid">
            <div v-for="card in statCards" :key="card.title" class="stat-card">
                <div class="stat-card__top">
                    <span class="stat-card__label">{{ card.title }}</span>
                    <div
                        class="stat-icon"
                        :style="{ background: `color-mix(in srgb, var(${card.colorVar}) 15%, transparent)` }"
                    >
                        <component
                            :is="card.icon"
                            :size="18"
                            :style="{ color: `var(${card.colorVar})` }"
                        />
                    </div>
                </div>
                <p class="stat-card__value">{{ card.value }}</p>
                <div class="stat-card__trend" :class="trendClass(card.change)">
                    <TrendingUp   v-if="card.change > 0"  :size="13" />
                    <TrendingDown v-else-if="card.change < 0" :size="13" />
                    <Minus        v-else :size="13" />
                    <span class="trend-pct">{{ card.change > 0 ? '+' : '' }}{{ card.change }}%</span>
                    <span class="trend-label">{{ comparisonLabel }}</span>
                </div>
            </div>
        </div>

        <!-- Tren Pendapatan (Area chart, full width) -->
        <div class="chart-card chart-card--full">
            <div class="chart-card__header">
                <h3 class="chart-card__title">Tren Pendapatan</h3>
                <span class="chart-card__sub">{{ trendLabel }} dalam periode yang dipilih</span>
            </div>
            <VueApexCharts type="area" height="280" :options="revenueTrendOptions" :series="revenueTrendSeries" />
        </div>

        <!-- Row 2: Perbandingan Periode + Pembayaran Populer -->
        <div class="chart-row">
            <div class="chart-card">
                <div class="chart-card__header">
                    <h3 class="chart-card__title">Perbandingan Periode</h3>
                    <span class="chart-card__sub">{{ comparisonLabel }}</span>
                </div>
                <table class="cmp-table">
                    <thead>
                        <tr>
                            <th>Metrik</th>
                            <th>Periode Ini</th>
                            <th>Periode Lalu</th>
                            <th>Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in comparisonRows" :key="row.label">
                            <td class="cmp-label">{{ row.label }}</td>
                            <td class="cmp-cur">{{ row.cur }}</td>
                            <td class="cmp-prev">{{ row.prev }}</td>
                            <td :class="['cmp-pct', trendClass(row.pct)]">
                                <span>{{ row.pct > 0 ? '+' : '' }}{{ row.pct }}%</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="chart-card">
                <div class="chart-card__header">
                    <h3 class="chart-card__title">Pembayaran Populer</h3>
                    <span class="chart-card__sub">Distribusi metode pembayaran</span>
                </div>
                <VueApexCharts type="donut" height="260" :options="paymentOptions" :series="paymentSeries" />
            </div>
        </div>

        <!-- Row 3: Top 5 Produk + Top 5 Kategori -->
        <div class="chart-row">
            <div class="chart-card">
                <div class="chart-card__header">
                    <h3 class="chart-card__title">Top 5 Produk</h3>
                    <span class="chart-card__sub">Berdasarkan pendapatan</span>
                </div>
                <VueApexCharts type="bar" height="220" :options="topProductsOptions" :series="topProductsSeries" />
                <div class="table-scroll">
                    <table class="rank-table" v-if="top_products.length > 0">
                        <thead>
                            <tr><th>#</th><th>Produk</th><th>Qty</th><th>Pendapatan</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="(p, i) in top_products" :key="p.name">
                                <td class="rank-num">{{ i + 1 }}</td>
                                <td class="rank-name">{{ p.name }}</td>
                                <td class="rank-qty">{{ formatNumber(p.qty) }}</td>
                                <td class="rank-rev">{{ formatCurrency(p.revenue) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="chart-empty">Belum ada data produk</p>
                </div>
            </div>

            <div class="chart-card">
                <div class="chart-card__header">
                    <h3 class="chart-card__title">Top 5 Kategori</h3>
                    <span class="chart-card__sub">Distribusi pendapatan per kategori</span>
                </div>
                <VueApexCharts type="donut" height="260" :options="topCategoriesOptions" :series="topCategoriesSeries" />
                <div class="table-scroll">
                    <table class="rank-table" v-if="top_categories.length > 0">
                        <thead>
                            <tr><th>#</th><th>Kategori</th><th>Qty</th><th>Pendapatan</th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="(c, i) in top_categories" :key="c.name">
                                <td class="rank-num">{{ i + 1 }}</td>
                                <td class="rank-name">{{ c.name }}</td>
                                <td class="rank-qty">{{ formatNumber(c.qty) }}</td>
                                <td class="rank-rev">{{ formatCurrency(c.revenue) }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-else class="chart-empty">Belum ada data kategori</p>
                </div>
            </div>
        </div>

        <!-- Row 4: Top 5 Merek (full width) -->
        <div class="chart-card chart-card--full">
            <div class="chart-card__header">
                <h3 class="chart-card__title">Top 5 Merek</h3>
                <span class="chart-card__sub">Merek dengan pendapatan tertinggi</span>
            </div>
            <VueApexCharts type="bar" height="220" :options="topBrandsOptions" :series="topBrandsSeries" />
            <p v-if="top_brands.length === 0" class="chart-empty">Belum ada data merek</p>
        </div>
    </div>
</template>

<style scoped>
/* ── Page wrapper ────────────────────────────────────────────────────────── */
.adm-page {
    width: 100%;
    max-width: 1280px;
    margin: 0 auto;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    box-sizing: border-box;
}

/* ── Filter bar — white card bg → pos-text-secondary ────────────────────── */
.filter-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
}

.filter-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
}

.filter-tab {
    padding: 0.3rem 0.85rem;
    border-radius: calc(var(--radius) - 2px);
    border: 1px solid var(--border);
    background: transparent;
    color: var(--pos-text-muted);
    font-size: 0.8125rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}

.filter-tab:hover {
    background: var(--accent);
    color: var(--accent-foreground);
    border-color: transparent;
}

.filter-tab:focus-visible {
    outline: 2px solid var(--ring);
    outline-offset: 2px;
}

.filter-tab--active {
    background: var(--primary);
    color: var(--primary-foreground);
    border-color: var(--primary);
}

.custom-range {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.date-input {
    height: 2rem;
    padding: 0 0.5rem;
    border: 1px solid var(--border);
    border-radius: calc(var(--radius) - 2px);
    background: var(--input);
    color: var(--pos-text-secondary);
    font-size: 0.8125rem;
}

.date-sep {
    color: var(--pos-text-muted);
    font-size: 0.8125rem;
}

.apply-btn {
    height: 2rem;
    padding: 0 0.75rem;
    background: var(--primary);
    color: var(--primary-foreground);
    border: none;
    border-radius: calc(var(--radius) - 2px);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s;
}

.apply-btn:hover        { opacity: 0.88; }
.apply-btn:focus-visible { outline: 2px solid var(--ring); outline-offset: 2px; }

/* ── Period hint — dark page bg → pos-text-primary ──────────────────────── */
.period-hint {
    font-size: 0.8125rem;
    color: var(--pos-text-primary);
    margin: 0;
}

/* ── KPI Stats grid ──────────────────────────────────────────────────────── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    overflow: hidden;
}

.stat-card__top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

/* white card bg → pos-text-muted for label */
.stat-card__label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--pos-text-muted);
}

.stat-icon {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: calc(var(--radius) - 2px);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

/* white card bg → pos-text-secondary for primary value */
.stat-card__value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--pos-text-secondary);
    margin: 0;
    line-height: 1.2;
}

.stat-card__trend {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
}

.trend-pct   { font-weight: 700; }
.trend-label { color: var(--pos-text-muted); font-weight: 400; margin-left: 0.15rem; }

.trend-up      { color: var(--success); }
.trend-down    { color: var(--destructive); }
.trend-neutral { color: var(--pos-text-muted); }

/* ── Chart cards — white card bg → pos-text-secondary ───────────────────── */
.chart-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    min-width: 0;
    overflow: hidden;
}

.chart-card--full { width: 100%; }

.chart-card__header {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.chart-card__title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--pos-text-secondary);
    margin: 0;
}

.chart-card__sub {
    font-size: 0.75rem;
    color: var(--pos-text-muted);
}

.chart-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

/* ── Table scroll wrapper ────────────────────────────────────────────────── */
.table-scroll {
    overflow-x: auto;
    overflow-y: hidden;
}

/* ── Comparison table ────────────────────────────────────────────────────── */
.cmp-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8125rem;
}

.cmp-table th {
    text-align: left;
    padding: 0.4rem 0.5rem;
    color: var(--pos-text-muted);
    font-weight: 500;
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}

.cmp-table td {
    padding: 0.5rem 0.5rem;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.cmp-table tr:last-child td { border-bottom: none; }

.cmp-label { font-weight: 500; color: var(--pos-text-secondary); }
.cmp-cur   { font-weight: 600; color: var(--pos-text-secondary); }
.cmp-prev  { color: var(--pos-text-muted); }
.cmp-pct   { font-weight: 700; white-space: nowrap; }
.cmp-pct.trend-up      { color: var(--success); }
.cmp-pct.trend-down    { color: var(--destructive); }
.cmp-pct.trend-neutral { color: var(--pos-text-muted); }

/* ── Rank table ──────────────────────────────────────────────────────────── */
.rank-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.775rem;
}

.rank-table th {
    text-align: left;
    padding: 0.3rem 0.4rem;
    color: var(--pos-text-muted);
    font-weight: 500;
    border-bottom: 1px solid var(--border);
}

.rank-table td {
    padding: 0.35rem 0.4rem;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

.rank-table tr:last-child td { border-bottom: none; }

.rank-num  { color: var(--pos-text-muted); width: 1.5rem; font-weight: 600; }
.rank-name { font-weight: 500; color: var(--pos-text-secondary); max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.rank-qty  { color: var(--pos-text-muted); text-align: right; }
.rank-rev  { font-weight: 600; color: var(--pos-text-secondary); text-align: right; }

.chart-empty {
    text-align: center;
    color: var(--pos-text-muted);
    font-size: 0.8125rem;
    padding: 1.5rem 0;
    margin: 0;
}

/* ── Scrollbar ───────────────────────────────────────────────────────────── */
.adm-page::-webkit-scrollbar {
    width: 8px;
}

.adm-page::-webkit-scrollbar-track {
    background: var(--card);
}

.adm-page::-webkit-scrollbar-thumb {
    background: var(--border);
    border-radius: 4px;
    transition: background 0.2s;
}

.adm-page::-webkit-scrollbar-thumb:hover {
    background: var(--pos-text-muted);
}

/* Firefox scrollbar */
.adm-page {
    scrollbar-width: thin;
    scrollbar-color: var(--border) var(--card);
}

/* ── Responsive ──────────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .chart-row { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
    .stats-grid       { grid-template-columns: 1fr 1fr; }
    .stat-card__value { font-size: 1.25rem; }
}
</style>
