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
import type { DashboardPageProps, Period, TrendPoint } from '@/types/admin'

// ── Pastel palette (light-mode friendly) ─────────────────────────────────────
const PALETTE = {
    teal:    '#5eead4',  // teal-300
    mint:    '#a7f3d0',  // emerald-200
    amber:   '#fde68a',  // amber-200
    rose:    '#fecaca',  // red-200
    violet:  '#c7d2fe',  // indigo-200
    sky:     '#bae6fd',  // sky-200
    peach:   '#fed7aa',  // orange-200
    lavender:'#ddd6fe',  // violet-200
}
const PALETTE_STRONG = {
    teal:    '#14b8a6',
    mint:    '#10b981',
    amber:   '#f59e0b',
    rose:    '#f43f5e',
    violet:  '#6366f1',
    sky:     '#0ea5e9',
    peach:   '#f97316',
    lavender:'#8b5cf6',
}
const PASTEL_COLORS    = [PALETTE.teal,    PALETTE.amber,    PALETTE.rose,    PALETTE.violet,    PALETTE.sky]
const PASTEL_STROKES   = [PALETTE_STRONG.teal, PALETTE_STRONG.amber, PALETTE_STRONG.rose, PALETTE_STRONG.violet, PALETTE_STRONG.sky]


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

function formatCurrencyCompact(n: number): string {
    if (n >= 1_000_000_000) return 'Rp ' + (n / 1_000_000_000).toFixed(1) + ' M'
    if (n >= 1_000_000)     return 'Rp ' + (n / 1_000_000).toFixed(1) + ' jt'
    if (n >= 1_000)         return 'Rp ' + (n / 1_000).toFixed(0) + ' rb'
    return 'Rp ' + Math.round(n)
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

const trendSummary = computed(() => props.revenue_trend.summary)

const formatBucketLabel = (key: string): string => {
    if (!key) return '-'
    try {
        const dt = new Date(key.replace(' ', 'T'))
        if (Number.isNaN(dt.getTime())) return key
        if (selectedPeriod.value === 'daily') {
            return dt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
        }
        return dt.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' })
    } catch {
        return key
    }
}

const revenueTrendOptions = computed(() => {
    const sum = trendSummary.value
    return {
        ...chartBaseOptions.value,
        chart: { ...chartBaseOptions.value.chart, id: 'revenue-trend', type: 'area' as const, height: 320 },
        dataLabels: {
            enabled: true,
            enabledOnSeries: [1],
            formatter: (v: number) => v > 0 ? formatCurrencyCompact(v) : '',
            background: { enabled: true, foreColor: '#0f766e', borderRadius: 4, padding: 3, opacity: 0.85, borderColor: PALETTE.teal },
            style: { fontSize: '9px', fontWeight: 600 },
            offsetY: -6,
        },
        stroke: {
            curve: 'smooth' as const,
            width: [2, 3, 2],
            dashArray: [0, 0, 6],
        },
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: [0.15, 0.5, 0.05], opacityTo: [0.01, 0.02, 0.01], stops: [0, 90, 100] },
        },
        markers: {
            size: [3, 4, 3],
            strokeWidth: 2,
            strokeColors: '#ffffff',
            hover: { size: 6 },
        },
        xaxis: {
            categories: props.revenue_trend.labels,
            labels: { style: { fontSize: '11px', colors: '#475569' }, rotate: -25, rotateAlways: false },
            title: { text: trendLabel.value, style: { fontSize: '11px', color: '#334155', fontWeight: 600 } },
            axisBorder: { color: '#e2e8f0' },
            axisTicks: { color: '#e2e8f0' },
        },
        yaxis: {
            title: { text: 'Pendapatan (Rp)', style: { fontSize: '11px', color: '#334155', fontWeight: 600 } },
            labels: { formatter: (v: number) => formatCurrencyCompact(v), style: { fontSize: '10px', colors: '#64748b' } },
        },
        annotations: {
            yaxis: sum.avg_revenue > 0 ? [
                {
                    y: sum.avg_revenue,
                    borderColor: PALETTE_STRONG.amber,
                    strokeDashArray: 4,
                    label: {
                        borderColor: PALETTE_STRONG.amber,
                        style: { color: '#78350f', background: PALETTE.amber, fontSize: '10px', fontWeight: 600 },
                        text: `Rata-rata ${formatCurrencyCompact(sum.avg_revenue)}`,
                        position: 'left',
                        offsetX: 80,
                    },
                },
                ...(sum.max_revenue > 0 ? [{
                    y: sum.max_revenue,
                    borderColor: PALETTE_STRONG.rose,
                    strokeDashArray: 4,
                    label: {
                        borderColor: PALETTE_STRONG.rose,
                        style: { color: '#881337', background: PALETTE.rose, fontSize: '10px', fontWeight: 600 },
                        text: `Tertinggi ${formatCurrencyCompact(sum.max_revenue)}`,
                        position: 'right',
                    },
                }] : []),
            ] : [],
        },
        colors: [PALETTE_STRONG.violet, PALETTE_STRONG.teal, PALETTE_STRONG.amber],
        legend: { position: 'top' as const, fontSize: '12px', fontWeight: 500, markers: { size: 8 } },
        tooltip: {
            ...chartBaseOptions.value.tooltip,
            shared: true, intersect: false,
            y: { formatter: (v: number) => formatCurrency(v) },
        },
        grid: { ...chartBaseOptions.value.grid, padding: { top: 10, right: 20, bottom: 0, left: 10 } },
        noData: { text: 'Belum ada data penjualan', style: { fontSize: '13px' } },
    }
})

const revenueTrendSeries = computed(() => {
    const buckets = props.revenue_trend
    const currentHasData = buckets.current.some((p: TrendPoint) => p.revenue > 0)

    return [
        { name: 'Masa Lalu',     data: buckets.previous.map((p: TrendPoint) => p.revenue) },
        { name: 'Masa Sekarang', data: buckets.current.map((p: TrendPoint)  => p.revenue) },
        ...(currentHasData ? [{ name: 'Prediksi (MA)', data: buckets.forecast.map((p: TrendPoint) => p.revenue) }] : []),
    ]
})

const hasRevenueTrend = computed(() => {
    const t = props.revenue_trend
    return (
        (t.current.some(p => p.revenue > 0 || p.transactions > 0)) ||
        (t.previous.some(p => p.revenue > 0 || p.transactions > 0))
    )
})

const hasTopProducts   = computed(() => props.top_products.some(p => (p.revenue ?? 0) > 0))
const hasTopCategories = computed(() => props.top_categories.some(c => (c.revenue ?? 0) > 0))
const hasTopBrands     = computed(() => props.top_brands.some(b => (b.revenue ?? 0) > 0))
const hasPayments      = computed(() => props.payment_methods.some(p => (p.count ?? 0) > 0))

const barDataLabels = computed(() => ({
    enabled: true,
    formatter: (v: number) => formatCurrencyCompact(v),
    offsetX: 28,
    style: { fontSize: '10px', fontWeight: 600, colors: ['#334155'] },
}))

const barPlotOptions = {
    bar: {
        horizontal: true,
        borderRadius: 6,
        barHeight: '65%',
        distributed: true,
        dataLabels: { position: 'top' },
    },
}

const topProductsOptions = computed(() => ({
    ...chartBaseOptions.value,
    chart: { ...chartBaseOptions.value.chart, type: 'bar' as const, height: 240 },
    plotOptions: barPlotOptions,
    dataLabels: barDataLabels.value,
    xaxis: {
        categories: props.top_products.map(p => p.name),
        title: { text: 'Pendapatan (Rp)', style: { fontSize: '11px', color: '#334155', fontWeight: 600 } },
        labels: { formatter: (v: number) => formatCurrencyCompact(v), style: { fontSize: '10px', colors: '#64748b' } },
    },
    yaxis: { labels: { style: { fontSize: '11px', colors: '#475569' } } },
    colors: PASTEL_COLORS,
    legend: { show: false },
    noData: { text: 'Belum ada data', style: { fontSize: '13px' } },
    tooltip: { ...chartBaseOptions.value.tooltip, y: { formatter: (v: number) => formatCurrency(v) } },
}))

const topProductsSeries = computed(() => [{ name: 'Pendapatan', data: props.top_products.map(p => p.revenue) }])

const donutColors = PASTEL_COLORS

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
    chart: { ...chartBaseOptions.value.chart, type: 'donut' as const, height: 280 },
    labels: props.top_categories.map(c => c.name),
    colors: donutColors,
    stroke: { width: 2, colors: ['#ffffff'] },
    plotOptions: donutPlotOptions('Total', (w: any) =>
        formatCurrencyCompact(w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0))
    ),
    dataLabels: {
        enabled: true,
        formatter: (val: number) => val.toFixed(0) + '%',
        style: { fontSize: '11px', fontWeight: 700, colors: ['#334155'] },
        dropShadow: { enabled: false },
    },
    legend: { position: 'bottom' as const, fontSize: '11px', fontWeight: 500, markers: { size: 8 } },
    noData: { text: 'Belum ada data', style: { fontSize: '13px' } },
    tooltip: { ...chartBaseOptions.value.tooltip, y: { formatter: (v: number) => formatCurrency(v) } },
}))

const topCategoriesSeries = computed(() => props.top_categories.map(c => c.revenue))

const topBrandsOptions = computed(() => ({
    ...chartBaseOptions.value,
    chart: { ...chartBaseOptions.value.chart, type: 'bar' as const, height: 240 },
    plotOptions: barPlotOptions,
    dataLabels: barDataLabels.value,
    xaxis: {
        categories: props.top_brands.map(b => b.name),
        title: { text: 'Pendapatan (Rp)', style: { fontSize: '11px', color: '#334155', fontWeight: 600 } },
        labels: { formatter: (v: number) => formatCurrencyCompact(v), style: { fontSize: '10px', colors: '#64748b' } },
    },
    yaxis: { labels: { style: { fontSize: '11px', colors: '#475569' } } },
    colors: [PALETTE.violet, PALETTE.lavender, PALETTE.sky, PALETTE.mint, PALETTE.peach],
    legend: { show: false },
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
    chart: { ...chartBaseOptions.value.chart, type: 'donut' as const, height: 280 },
    labels: props.payment_methods.map(p => paymentMethodLabel(p.method)),
    colors: [PALETTE.teal, PALETTE.amber, PALETTE.sky, PALETTE.rose, PALETTE.violet],
    stroke: { width: 2, colors: ['#ffffff'] },
    plotOptions: donutPlotOptions('Transaksi', (w: any) =>
        formatNumber(w.globals.seriesTotals.reduce((a: number, b: number) => a + b, 0))
    ),
    dataLabels: {
        enabled: true,
        formatter: (val: number) => val.toFixed(0) + '%',
        style: { fontSize: '11px', fontWeight: 700, colors: ['#334155'] },
        dropShadow: { enabled: false },
    },
    legend: { position: 'bottom' as const, fontSize: '11px', fontWeight: 500, markers: { size: 8 } },
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

        <!-- Tren Pendapatan (Area chart, full width) — Past vs Present vs Forecast -->
        <div class="chart-card chart-card--full">
            <div class="chart-card__header chart-card__header--row">
                <div>
                    <h3 class="chart-card__title">Tren Pendapatan</h3>
                    <span class="chart-card__sub">{{ trendLabel }} • Masa Lalu vs Masa Sekarang vs Prediksi</span>
                </div>
                <div class="trend-summary">
                    <div class="trend-summary__item">
                        <span class="trend-summary__label">Tertinggi</span>
                        <span class="trend-summary__value trend-summary__value--rose">
                            {{ formatCurrencyCompact(trendSummary.max_revenue) }}
                        </span>
                        <span class="trend-summary__hint">{{ formatBucketLabel(trendSummary.max_period) }}</span>
                    </div>
                    <div class="trend-summary__item">
                        <span class="trend-summary__label">Rata-rata</span>
                        <span class="trend-summary__value trend-summary__value--amber">
                            {{ formatCurrencyCompact(trendSummary.avg_revenue) }}
                        </span>
                        <span class="trend-summary__hint">per {{ trendLabel.toLowerCase() }}</span>
                    </div>
                    <div class="trend-summary__item">
                        <span class="trend-summary__label">Total Transaksi</span>
                        <span class="trend-summary__value trend-summary__value--teal">
                            {{ formatNumber(trendSummary.total_transactions) }}
                        </span>
                        <span class="trend-summary__hint">{{ formatCurrencyCompact(trendSummary.total_revenue) }}</span>
                    </div>
                </div>
            </div>
            <VueApexCharts
                v-if="hasRevenueTrend"
                type="area"
                height="320"
                :options="revenueTrendOptions"
                :series="revenueTrendSeries"
            />
            <div v-else class="chart-empty-state" style="height: 320px">
                <p class="chart-empty-title">Belum ada data penjualan</p>
                <p class="chart-empty-sub">Belum ada transaksi untuk periode ini</p>
            </div>
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
                <VueApexCharts
                    v-if="hasPayments"
                    type="donut"
                    height="260"
                    :options="paymentOptions"
                    :series="paymentSeries"
                />
                <div v-else class="chart-empty-state" style="height: 260px">
                    <p class="chart-empty-title">Belum ada data pembayaran</p>
                    <p class="chart-empty-sub">Belum ada transaksi pada periode ini</p>
                </div>
            </div>
        </div>

        <!-- Row 3: Top 5 Produk + Top 5 Kategori -->
        <div class="chart-row">
            <div class="chart-card">
                <div class="chart-card__header">
                    <h3 class="chart-card__title">Top 5 Produk</h3>
                    <span class="chart-card__sub">Berdasarkan pendapatan</span>
                </div>
                <VueApexCharts
                    v-if="hasTopProducts"
                    type="bar"
                    height="220"
                    :options="topProductsOptions"
                    :series="topProductsSeries"
                />
                <div v-else class="chart-empty-state" style="height: 220px">
                    <p class="chart-empty-title">Belum ada produk terjual</p>
                    <p class="chart-empty-sub">Top produk akan muncul setelah ada penjualan</p>
                </div>
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
                <VueApexCharts
                    v-if="hasTopCategories"
                    type="donut"
                    height="260"
                    :options="topCategoriesOptions"
                    :series="topCategoriesSeries"
                />
                <div v-else class="chart-empty-state" style="height: 260px">
                    <p class="chart-empty-title">Belum ada data kategori</p>
                    <p class="chart-empty-sub">Distribusi kategori akan muncul setelah ada penjualan</p>
                </div>
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
            <VueApexCharts
                v-if="hasTopBrands"
                type="bar"
                height="220"
                :options="topBrandsOptions"
                :series="topBrandsSeries"
            />
            <div v-else class="chart-empty-state" style="height: 220px">
                <p class="chart-empty-title">Belum ada data merek</p>
                <p class="chart-empty-sub">Top merek akan muncul setelah ada penjualan</p>
            </div>
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
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    overflow: visible;
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
    background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
    color: #ffffff;
    border-color: #0d9488;
    font-weight: 600;
    box-shadow:
        0 2px 6px rgba(20, 184, 166, 0.35),
        0 0 0 2px rgba(20, 184, 166, 0.12);
}

.filter-tab--active:hover {
    background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
    color: #ffffff;
    border-color: #0f766e;
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

.chart-card__header--row {
    flex-direction: row;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

/* ── Trend summary chips ────────────────────────────────────────────────── */
.trend-summary {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.trend-summary__item {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.1rem;
    padding: 0.5rem 0.75rem;
    border-radius: calc(var(--radius) - 2px);
    background: color-mix(in srgb, var(--muted) 55%, transparent);
    min-width: 110px;
}

.trend-summary__label {
    font-size: 0.6875rem;
    font-weight: 500;
    color: var(--pos-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.trend-summary__value {
    font-size: 0.95rem;
    font-weight: 700;
    line-height: 1.1;
}

.trend-summary__value--rose  { color: #e11d48; }
.trend-summary__value--amber { color: #d97706; }
.trend-summary__value--teal  { color: #0d9488; }

.trend-summary__hint {
    font-size: 0.6875rem;
    color: var(--pos-text-muted);
    font-weight: 500;
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

/* ── Chart empty state (when no data) ───────────────────────────────────── */
.chart-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    background: color-mix(in srgb, var(--muted) 40%, transparent);
    border: 1px dashed var(--border);
    border-radius: calc(var(--radius) - 2px);
}

.chart-empty-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--pos-text-secondary);
    margin: 0;
}

.chart-empty-sub {
    font-size: 0.75rem;
    color: var(--pos-text-muted);
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
