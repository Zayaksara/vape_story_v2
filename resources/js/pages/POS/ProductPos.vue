<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import type { Product, Category, ProductPageProps } from '@/types/pos'
import {
  Table,
  TableBody,
  TableCaption,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'

const props = defineProps<ProductPageProps>()

// Local state for search and category filter
const searchQuery = ref(props.searchQuery || '')
const selectedCategoryId = ref(props.selectedCategory?.id || null)
const isLoading = ref(false)

// Debounce function
let debounceTimeout: ReturnType<typeof setTimeout> | null = null

function updateSearch(value: string) {
  searchQuery.value = value

  if (debounceTimeout) {
    clearTimeout(debounceTimeout)
  }

  debounceTimeout = setTimeout(() => {
    updateUrl()
  }, 500)
}

// Update URL with filters
function updateUrl() {
  const params: Record<string, string | null> = {}

  if (searchQuery.value) {
    params.search = searchQuery.value
  }

  if (selectedCategoryId.value) {
    params.category = selectedCategoryId.value as string
  }

  router.get(route('pos.products.index'), params, {
    preserveState: true,
    preserveScroll: true,
    onStart: () => {
      isLoading.value = true
    },
    onFinish: () => {
      isLoading.value = false
    }
  })
}

// Select category
function selectCategory(category: Category | null) {
  selectedCategoryId.value = category?.id || null
  updateUrl()
}

// Clear all filters
function clearFilters() {
  searchQuery.value = ''
  selectedCategoryId.value = null
  updateUrl()
}

// Get category name by ID
function getCategoryName(categoryId: string): string {
  const category = props.categories.find(cat => cat.id === categoryId)
  return category?.name || '-'
}

// Format price
function formatPrice(price: number): string {
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

// Get stock badge class
function getStockBadgeClass(stock: number): string {
  if (stock <= 0) {
    return 'bg-red-100 text-red-700'
  }
  if (stock <= 4) {
    return 'bg-amber-100 text-amber-700'
  }
  if (stock <= 10) {
    return 'bg-blue-100 text-blue-700'
  }
  return 'bg-green-100 text-green-700'
}

// Get stock badge text
function getStockBadgeText(stock: number): string {
  if (stock <= 0) {
    return 'Habis'
  }
  if (stock <= 4) {
    return `Sisa ${stock}`
  }
  return `${stock} pcs`
}

// All categories including "Semua" (All)
const allCategories = computed(() => [
  { id: '' as any, name: 'Semua', slug: '' },
  ...props.categories,
])

// Watch for URL changes to sync local state
watch(
  () => props.searchQuery,
  (newValue) => {
    if (newValue !== searchQuery.value) {
      searchQuery.value = newValue || ''
    }
  }
)

watch(
  () => props.selectedCategory?.id,
  (newValue) => {
    if (newValue !== selectedCategoryId.value) {
      selectedCategoryId.value = newValue || null
    }
  }
)
</script>

<template>
  <div class="container mx-auto p-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-slate-900">Produk</h1>
      <p class="text-sm text-slate-600 mt-1">Kelola dan lihat daftar produk tersedia</p>
    </div>

    <!-- Search and Category Filter -->
    <div class="mb-6 space-y-4">
      <!-- Search input using shadcn Input -->
      <div class="max-w-md">
        <Input
          type="text"
          placeholder="Cari produk berdasarkan nama atau SKU..."
          :model-value="searchQuery"
          @update:model-value="updateSearch"
          :disabled="isLoading"
        />
      </div>

      <!-- Category buttons using shadcn Button -->
      <div class="flex flex-wrap gap-2">
        <Button
          v-for="category in allCategories"
          :key="category.id"
          :variant="selectedCategoryId === category.id ? 'default' : 'outline'"
          :disabled="isLoading"
          @click="selectCategory(category.id ? category : null)"
          size="sm"
        >
          {{ category.name }}
        </Button>

        <!-- Clear filters button -->
        <Button
          v-if="searchQuery || selectedCategoryId"
          variant="ghost"
          :disabled="isLoading"
          @click="clearFilters"
          size="sm"
        >
          Reset
        </Button>
      </div>
    </div>

    <!-- Product Table using shadcn Table -->
    <div class="rounded-md border">
      <Table>
        <TableCaption v-if="props.products.length === 0 && !isLoading">
          Tidak ada produk tersedia
        </TableCaption>
        <TableCaption v-else>
          Menampilkan {{ props.products.length }} produk
        </TableCaption>

        <TableHeader>
          <TableRow>
            <TableHead>Nama Produk</TableHead>
            <TableHead>SKU</TableHead>
            <TableHead>Kategori</TableHead>
            <TableHead class="text-right">Harga</TableHead>
            <TableHead class="text-center">Stok</TableHead>
            <TableHead>Volume</TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <!-- Loading state -->
          <TableRow v-if="isLoading">
            <TableCell :colspan="6" class="text-center py-8">
              <div class="flex items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-500"></div>
                <span class="ml-2 text-sm text-slate-600">Memuat produk...</span>
              </div>
            </TableCell>
          </TableRow>

          <!-- Empty state -->
          <TableRow v-else-if="props.products.length === 0">
            <TableCell :colspan="6" class="text-center py-12">
              <div class="flex flex-col items-center justify-center space-y-3">
                <svg
                  class="h-12 w-12 text-slate-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                  />
                </svg>
                <div class="text-center">
                  <p class="text-sm font-medium text-slate-900">Tidak ada produk ditemukan</p>
                  <p class="text-sm text-slate-500 mt-1">
                    {{ searchQuery || selectedCategoryId
                      ? 'Coba ubah kata kunci pencarian atau filter kategori'
                      : 'Belum ada produk yang tersedia' }}
                  </p>
                </div>
                <Button
                  v-if="searchQuery || selectedCategoryId"
                  variant="outline"
                  size="sm"
                  @click="clearFilters"
                  :disabled="isLoading"
                >
                  Hapus Filter
                </Button>
              </div>
            </TableCell>
          </TableRow>

          <!-- Product rows -->
          <TableRow
            v-else
            v-for="product in props.products"
            :key="product.id"
            class="hover:bg-slate-50"
          >
            <TableCell class="font-medium text-slate-900">
              {{ product.name }}
            </TableCell>
            <TableCell class="text-slate-600 font-mono text-sm">
              {{ product.sku }}
            </TableCell>
            <TableCell class="text-slate-600">
              {{ getCategoryName(product.category_id) }}
            </TableCell>
            <TableCell class="text-right font-semibold text-teal-600">
              {{ formatPrice(product.price) }}
            </TableCell>
            <TableCell class="text-center">
              <span
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                :class="getStockBadgeClass(product.stock)"
              >
                {{ getStockBadgeText(product.stock) }}
              </span>
            </TableCell>
            <TableCell class="text-slate-600 text-sm">
              {{ product.volume || '-' }}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>

    <!-- Footer info -->
    <div v-if="props.products.length > 0" class="mt-4 text-sm text-slate-500">
      <p>
        Total {{ props.products.length }} produk
        <span v-if="selectedCategoryId"> dalam kategori "{{ getCategoryName(selectedCategoryId) }}"</span>
        <span v-if="searchQuery"> cocok dengan "{{ searchQuery }}"</span>
      </p>
    </div>
  </div>
</template>

<style scoped>
/* Custom styles for table */
:deep(.table) {
  width: 100%;
  caption-side: bottom;
  font-size: 0.875rem;
}

/* Ensure proper text alignment */
:deep(td.text-right),
:deep(th.text-right) {
  text-align: right;
}

:deep(td.text-center),
:deep(th.text-center) {
  text-align: center;
}
</style>
