export type Period = 'daily' | 'weekly' | 'monthly' | 'quarterly' | 'yearly' | 'custom'

export interface StatMetric {
    current: number
    previous: number
    change_pct: number
}

export interface DashboardStats {
    revenue: StatMetric
    transactions: StatMetric
    profit: StatMetric
    products_sold: StatMetric
}

export interface DateRange {
    start: string // YYYY-MM-DD
    end: string
    prev_start: string
    prev_end: string
}

export interface TrendPoint {
    period: string // "2026-05-10" or "2026-05-10 14:00" for hourly
    revenue: number
    transactions: number
}

export interface TrendSummary {
    max_revenue: number
    max_period: string
    min_revenue: number
    min_period: string
    avg_revenue: number
    total_revenue: number
    total_transactions: number
}

export interface RevenueTrend {
    current: TrendPoint[]
    previous: TrendPoint[]
    forecast: TrendPoint[]
    labels: string[]
    summary: TrendSummary
}

export interface TopItem {
    name: string
    revenue: number
    qty: number
}

export interface PaymentMethod {
    method: string
    count: number
    revenue: number
}

export interface DashboardPageProps {
    stats: DashboardStats
    period: Period
    date_range: DateRange
    revenue_trend: RevenueTrend
    top_products: TopItem[]
    top_categories: TopItem[]
    top_brands: TopItem[]
    payment_methods: PaymentMethod[]
}
