export interface Category {
  id: string
  name: string
  slug: string
}

export interface Brand {
  id: string
  name: string
  logo?: string
}

export interface Product {
  id: string
  name: string
  sku: string
  price: number
  stock: number
  category_id: string
  category?: Category
  brand_id?: string
  brand?: Brand
  brand_logo?: string
  volume?: string
  image_url?: string
}

export interface CartItem {
  product: Product
  quantity: number
  subtotal: number
}

export interface Discount {
  code: string
  label: string
  type: 'percent' | 'fixed'
  value: number
  max_discount?: number
  min_purchase?: number
  expires_at?: string
}

export type PaymentMethod = 'cash' | 'e_wallet' | 'bank_transfer' | 'qris'

export interface Transaction {
  id: string
  invoice_number: string
  cashier_id: number
  cashier_name: string
  items: CartItem[]
  discount: Discount | null
  subtotal: number
  discount_amount: number
  tax_amount: number
  total: number
  payment_method: PaymentMethod
  cash_received?: number
  change?: number
  created_at: string
  status: 'success' | 'failed' | 'pending'
}

export interface TransactionItem {
  id: string
  transaction_id: string
  product_id: string
  batch_id?: string
  quantity: number
  unit_price: number
  discount: number
  total: number
  product?: {
    id: string
    name: string
    code: string
  }
}

export interface TransactionWithItems extends Omit<Transaction, 'items'> {
  items: TransactionItem[]
  cashier?: {
    id: string
    name: string
  }
}

export interface DailySummary {
  total_transactions: number
  total_sales: number
  total_items: number
  payment_methods: {
    cash: number
    bank_transfer: number
    qris: number
    e_wallet: number
  }
}

export interface ProductPageProps {
  products: {
    data: Product[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    next_page_url: string | null
    prev_page_url: string | null
    links: Array<{ url: string | null; label: string; active: boolean }>
  }
  categories: Category[]
  units: string[]
  selectedCategory?: Category | null
  selectedStockStatus?: string | null
  selectedUnit?: string | null
  searchQuery?: string | null
}

export interface TransactionReportProps {
  transactions: TransactionWithItems[]
  summary: DailySummary
  selectedDate: string
  today: string
}

export interface PosPageProps {
  products: Product[]
  categories: Category[]
  cashier: {
    id: string  // UUID
    name: string
    email: string
  }
  initial_trx_id: string
}
