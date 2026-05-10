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
  start: string      // YYYY-MM-DD
  end: string
  prev_start: string
  prev_end: string
}

export interface DashboardPageProps {
  stats: DashboardStats
  period: Period
  date_range: DateRange
}
