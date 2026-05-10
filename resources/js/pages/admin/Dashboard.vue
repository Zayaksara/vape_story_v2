<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import {
    TrendingUp,
    TrendingDown,
    Minus,
    DollarSign,
    ShoppingCart,
    Banknote,
    Package,
    Activity,
    BarChart2,
    Layers,
    PieChart,
    Tag,
    Star,
    CreditCard,
} from 'lucide-vue-next'
import { dashboard } from '@/routes'
import type { DashboardPageProps, Period } from '@/types/admin'

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
            { title: 'Dashboard Pintar' },
        ],
    },
})

const props = defineProps<DashboardPageProps>()

const selectedPeriod = ref<Period>(props.period)
const customStart    = ref(props.date_range.start)
const customEnd      = ref(props.date_range.end)

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
        only: ['stats', 'period', 'date_range'],
    })
}

watch(selectedPeriod, (val) => {
    if (val !== 'custom') applyFilter()
})

// ─── Formatters ──────────────────────────────────────────────────────────────

function formatCurrency(n: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
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

// ─── Data ────────────────────────────────────────────────────────────────────

const statCards = computed(() => [
    {
        title:    'Total Pendapatan',
        value:    formatCurrency(props.stats.revenue.current),
        change:   props.stats.revenue.change_pct,
        icon:     DollarSign,
        colorVar: '--primary',
    },
    {
        title:    'Total Transaksi',
        value:    formatNumber(props.stats.transactions.current),
        change:   props.stats.transactions.change_pct,
        icon:     ShoppingCart,
        colorVar: '--chart-3',
    },
    {
        title:    'Total Keuntungan',
        value:    formatCurrency(props.stats.profit.current),
        change:   props.stats.profit.change_pct,
        icon:     Banknote,
        colorVar: '--chart-5',
    },
    {
        title:    'Produk Terjual',
        value:    formatNumber(props.stats.products_sold.current),
        change:   props.stats.products_sold.change_pct,
        icon:     Package,
        colorVar: '--chart-1',
    },
])

const comingSoonSections = [
    { title: 'Tren Pendapatan',      icon: Activity,  desc: 'Grafik tren pendapatan harian dalam periode yang dipilih.' },
    { title: 'Performa Jualan',      icon: BarChart2,  desc: 'Perbandingan volume penjualan antar periode.' },
    { title: 'Perbandingan Periode', icon: Layers,     desc: 'Analisis detail selisih dua periode secara berdampingan.' },
    { title: 'Per Kategori',         icon: PieChart,   desc: 'Distribusi pendapatan berdasarkan kategori produk.' },
    { title: 'Per Produk',           icon: Tag,        desc: 'Rincian penjualan dan profit per produk.' },
    { title: 'Per Merek',            icon: Star,       desc: 'Kontribusi pendapatan dari setiap merek.' },
    { title: 'Top 5 Produk',         icon: TrendingUp, desc: 'Produk dengan kuantitas atau pendapatan tertinggi.' },
    { title: 'Top 5 Kategori',       icon: TrendingUp, desc: 'Kategori dengan pendapatan tertinggi dalam periode.' },
    { title: 'Pembayaran Populer',   icon: CreditCard, desc: 'Metode pembayaran yang paling sering digunakan.' },
]
</script>

<template>
    <Head title="Dashboard Pintar" />

    <div class="adm-page">

        <!-- ── Period Filter ─────────────────────────────────────────────── -->
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
                <input v-model="customEnd"   type="date" class="date-input" :min="customStart" />
                <button class="apply-btn" @click="applyFilter">Terapkan</button>
            </div>
        </div>

        <!-- ── Period hint ───────────────────────────────────────────────── -->
        <p class="period-hint">
            {{ date_range.start }} – {{ date_range.end }} &nbsp;·&nbsp; {{ comparisonLabel }}
            ({{ date_range.prev_start }} – {{ date_range.prev_end }})
        </p>

        <!-- ── 4 KPI Cards ───────────────────────────────────────────────── -->
        <div class="stats-grid">
            <div v-for="card in statCards" :key="card.title" class="stat-card">
                <div class="stat-card__top">
                    <span class="stat-card__label">{{ card.title }}</span>
                    <div class="stat-icon" :style="`background: color-mix(in srgb, var(${card.colorVar}) 15%, transparent);`">
                        <component
                            :is="card.icon"
                            :size="18"
                            :style="`color: var(${card.colorVar});`"
                        />
                    </div>
                </div>
                <p class="stat-card__value">{{ card.value }}</p>
                <div class="stat-card__trend" :class="trendClass(card.change)">
                    <TrendingUp   v-if="card.change > 0" :size="13" />
                    <TrendingDown v-else-if="card.change < 0" :size="13" />
                    <Minus        v-else :size="13" />
                    <span class="trend-pct">{{ card.change > 0 ? '+' : '' }}{{ card.change }}%</span>
                    <span class="trend-label">{{ comparisonLabel }}</span>
                </div>
            </div>
        </div>

        <!-- ── Coming Soon sections ──────────────────────────────────────── -->
        <div class="cs-grid">
            <div v-for="section in comingSoonSections" :key="section.title" class="cs-card">
                <div class="cs-card__header">
                    <component :is="section.icon" :size="17" class="cs-icon" />
                    <h3 class="cs-title">{{ section.title }}</h3>
                </div>
                <p class="cs-desc">{{ section.desc }}</p>
                <span class="cs-badge">Segera Hadir</span>
            </div>
        </div>

    </div>
</template>

<style scoped>
/* ── Page wrapper ──────────────────────────────────────────────────────── */
.adm-page {
    max-width: 1280px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

/* ── Filter bar ────────────────────────────────────────────────────────── */
.filter-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
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
    color: var(--card-muted-foreground);
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
    color: var(--card-foreground);
    font-size: 0.8125rem;
}

.date-sep {
    color: var(--card-muted-foreground);
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

.apply-btn:hover { opacity: 0.88; }
.apply-btn:focus-visible { outline: 2px solid var(--ring); outline-offset: 2px; }

/* ── Period hint ───────────────────────────────────────────────────────── */
.period-hint {
    font-size: 0.8125rem;
    color: var(--card-muted-foreground);
    margin: 0;
}

/* ── KPI Stats grid ────────────────────────────────────────────────────── */
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
}

.stat-card__top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.stat-card__label {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--card-muted-foreground);
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

.stat-card__value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--card-foreground);
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
.trend-label { color: var(--card-muted-foreground); font-weight: 400; margin-left: 0.15rem; }

.trend-up      { color: var(--success); }
.trend-down    { color: var(--destructive); }
.trend-neutral { color: var(--card-muted-foreground); }

/* ── Coming Soon grid ──────────────────────────────────────────────────── */
.cs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.cs-card {
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    opacity: 0.72;
}

.cs-card__header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cs-icon  { color: var(--primary); flex-shrink: 0; }

.cs-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--card-foreground);
    margin: 0;
}

.cs-desc {
    font-size: 0.8125rem;
    color: var(--card-muted-foreground);
    margin: 0;
    line-height: 1.55;
    flex: 1;
}

.cs-badge {
    align-self: flex-start;
    margin-top: 0.25rem;
    padding: 0.18rem 0.6rem;
    background: color-mix(in srgb, var(--warning) 15%, transparent);
    color: var(--warning);
    border: 1px solid color-mix(in srgb, var(--warning) 30%, transparent);
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

/* ── Responsive ────────────────────────────────────────────────────────── */
@media (max-width: 640px) {
    .adm-page           { padding: 1rem; }
    .stats-grid         { grid-template-columns: 1fr 1fr; }
    .cs-grid            { grid-template-columns: 1fr; }
    .stat-card__value   { font-size: 1.25rem; }
}
</style>
