import type { Product } from '@/types/pos'

/**
 * Stock status computation for POS products
 * Used in both table view (ProductPos.vue) and card view (ProductCard.vue)
 */
export function usePosProduct() {
  // ── STOCK STATUS LABELS ─────────────────────────────────
  const stockStatusLabel = (stock: number): string => {
    if (stock === 0) {
      return 'Habis'
    }
    if (stock <= 20) {
      return 'Stok Rendah'
    }
    return 'Tersedia'
  }

  // ── STOCK STATUS CLASSES ───────────────────────────────
  const stockStatusClass = (stock: number): string => {
    if (stock === 0) {
      return 'bg-destructive/10 text-destructive'
    }
    if (stock <= 20) {
      return 'bg-accent/10 text-accent'
    }
    return 'bg-primary/10 text-primary'
  }

  // ── CARD BADGE CLASSES ─────────────────────────────────
  const stockBadgeClass = (stock: number): string => {
    if (stock <= 0) {
      return 'bg-red-100 text-red-600'
    }
    if (stock <= 4) {
      return 'bg-amber-100 text-amber-600'
    }
    if (stock <= 10) {
      return 'bg-blue-100 text-blue-600'
    }
    return 'bg-gray-100 text-gray-600'
  }

  // ── CARD BADGE TEXT ───────────────────────────────────
  const stockBadgeText = (stock: number): string => {
    if (stock <= 0) {
      return 'Habis'
    }
    if (stock <= 4) {
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