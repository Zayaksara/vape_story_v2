export interface Category {
  id: string
  name: string
  slug: string
}

export interface Product {
  id: string
  name: string
  sku: string
  price: number
  stock: number
  category_id: string
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

export type PaymentMethod = 'cash' | 'debit' | 'qris' | 'ewallet'

export interface Transaction {
  id: string
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
