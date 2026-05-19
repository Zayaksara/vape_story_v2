import type { Product } from '@/types/pos'

/**
 * Stock status computation for POS products
 * Used in both table view (ProductPos.vue) and card view (ProductCard.vue)
 */
export function usePosProduct() {
  // ── STOCK STATUS LABELS ─────────────────────────────────
  const stockStatusLabel = (stock: number, minStock: number = 0): string => {
    if (stock === 0) {
      return 'Habis'
    }
    if (minStock > 0 && stock <= minStock) {
      return 'Stok Tipis'
    }
    return 'Tersedia'
  }

  // ── STOCK STATUS CLASSES ───────────────────────────────
  const stockStatusClass = (stock: number, minStock: number = 0): string => {
    if (stock === 0) {
      return 'bg-destructive/10 text-destructive'
    }
    if (minStock > 0 && stock <= minStock) {
      return 'bg-accent/10 text-accent'
    }
    return 'bg-primary/10 text-primary'
  }

  // ── CARD BADGE CLASSES ─────────────────────────────────
  const stockBadgeClass = (stock: number, minStock: number = 0): string => {
    if (stock <= 0) {
      return 'bg-red-100 text-red-600'
    }
    if (minStock > 0 && stock <= minStock) {
      return 'bg-amber-100 text-amber-600'
    }
    return 'bg-gray-100 text-gray-600'
  }

  // ── CARD BADGE TEXT ───────────────────────────────────
  const stockBadgeText = (stock: number, minStock: number = 0): string => {
    if (stock <= 0) {
      return 'Habis'
    }
    if (minStock > 0 && stock <= minStock) {
      return `Sisa ${stock}`
    }
    return `${stock} pcs`
  }

  // ── FORMAT CURRENCY ───────────────────────────────────
  const formatPrice = (price: number): string => {
    if (typeof price !== 'number' || isNaN(price)) {
      return 'Rp 0'
    }
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0,
    }).format(price)
  }

  // ── RESOLVE PRODUCT UNIT ───────────────────────────────
  const resolveProductUnit = (product: Product): string => {
    // Product uses volume (from size_ml/battery_mah) instead of unit
    return product.volume ?? 'pcs'
  }

  return {
    stockStatusLabel,
    stockStatusClass,
    stockBadgeClass,
    stockBadgeText,
    formatPrice,
    resolveProductUnit,
  }
}