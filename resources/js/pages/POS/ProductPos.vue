<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useDebounceFn } from '@vueuse/core'
import {
    Search, X, Package, Eye,
    Zap, Wind, Layers, Tag,
    ChevronLeft, ChevronRight,
    BoxIcon, AlertTriangle, XCircle, Warehouse,
    TrendingUp, TrendingDown, Plus, Filter, Upload, Download, ArrowUpDown,
} from 'lucide-vue-next'
import type { Category, Product, ProductPageProps } from '@/types/pos'

import { Input }     from '@/components/ui/input'
import { Button }    from '@/components/ui/button'
import { Badge }     from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import {
    Select, SelectContent, SelectItem,
    SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
    Table, TableBody, TableCell, TableHead,
    TableHeader, TableRow,
} from '@/components/ui/table'
import {
    Sheet, SheetContent, SheetHeader,
    SheetTitle, SheetDescription,
} from '@/components/ui/sheet'
import {
    Tooltip, TooltipContent,
    TooltipProvider, TooltipTrigger,
} from '@/components/ui/tooltip'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'

import { index as posProductsRoute } from '@/routes/pos/products'

// ── Props ─────────────────────────────────────────────────────────────
const props = defineProps<
    ProductPageProps & {
        cashier: { id: string; name: string; email: string }
    }
>()

// ── Filter state ──────────────────────────────────────────────────────────────
const search       = ref(props.searchQuery ?? '')
const categorySlug = ref(props.selectedCategory?.slug ?? 'all')
const stockStatus  = ref(props.selectedStockStatus ?? 'all')
const isLoading    = ref(false)

const hasActiveFilters = computed(() =>
    !!(
        search.value ||
        (categorySlug.value && categorySlug.value !== 'all') ||
        (stockStatus.value && stockStatus.value !== 'all')
    ),
)

// ── Detail sheet ──────────────────────────────────────────────────────────────
const selectedProduct = ref<Product | null>(null)
const sheetOpen       = ref(false)

function openDetail(product: Product) {
    selectedProduct.value = product
    sheetOpen.value       = true
}

// ── Navigation ────────────────────────────────────────────────────────────────
function applyFilters() {
    isLoading.value = true
    router.get(
        posProductsRoute.url({
            query: {
                search: search.value || undefined,
                category: categorySlug.value === 'all' ? undefined : categorySlug.value,
                stock_status: stockStatus.value === 'all' ? undefined : stockStatus.value,
            },
        }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => { isLoading.value = false },
        },
    )
}

function resetFilters() {
    search.value       = ''
    categorySlug.value = 'all'
    stockStatus.value  = 'all'
    router.get(posProductsRoute.url(), {}, { preserveState: false })
}

function extractPageFromUrl(url: string | null): number | null {
    if (!url) {
        return null
    }

    try {
        const parsed = new URL(url, window.location.origin)
        const page = Number(parsed.searchParams.get('page'))
        return Number.isFinite(page) ? page : null
    } catch {
        return null
    }
}

function goToPage(page: number) {
    isLoading.value = true
    router.get(
        posProductsRoute.url({
            query: {
                page,
                search: search.value || undefined,
                category: categorySlug.value === 'all' ? undefined : categorySlug.value,
                stock_status: stockStatus.value === 'all' ? undefined : stockStatus.value,
            },
        }),
        {},
        {
            preserveState: true,
            preserveScroll: false,
            onFinish: () => { isLoading.value = false },
        },
    )
}

const debouncedSearch = useDebounceFn(applyFilters, 400)
watch(search, debouncedSearch)
watch([categorySlug, stockStatus], applyFilters)

// ── Derived stats (from current page data) ────────────────────────────
const statTersedia = computed(() => props.products.total)
const statTipis     = computed(() => props.products.data.filter(p => p.stock >= 3 && p.stock <= 9).length)
const statHabis     = computed(() => props.products.data.filter(p => p.stock === 0).length)

// ── Categories with "Semua" option ──────────────────────────────────
const categoriesWithAll = computed(() => [
    { id: 'all', name: 'Semua', slug: 'all' },
    ...props.categories
])

// ── Formatters ────────────────────────────────────────────────────────────────
function formatPrice(price: number): string {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
    }).format(price)
}

function productInitials(name: string): string {
    return name.split(' ').slice(0, 2).map(w => w[0]).join('').toUpperCase()
}

type StockVariant = 'default' | 'secondary' | 'destructive' | 'outline'
type StockInfo    = { label: string; variant: StockVariant; color: string }

function stockInfo(stock: number): StockInfo {
    if (stock === 0) return { label: 'Habis',       variant: 'destructive',  color: 'var(--pos-danger-text)' }
    if (stock <= 20) return { label: 'Stok Tipis',  variant: 'secondary',   color: 'var(--pos-warning-text)' }
    return                   { label: 'Tersedia',    variant: 'default',     color: 'var(--pos-success-text)' }
}

// Category icon mapping
const categoryIcons: Record<string, any> = {
    'Device':    Zap,
    'Liquid':    Wind,
    'Coil':      Layers,
    'Pod':       Package,
    'Aksesoris': Tag,
}

// Get category icon
function getCategoryIcon(categoryName: string): any {
    return categoryIcons[categoryName] || Package
}
</script>

<template>
    <TooltipProvider>
        <div
            class="pos-layout flex h-full min-h-0 flex-col"
            style="background: var(--pos-bg-primary); font-family: 'Poppins', sans-serif;"
        >
            <!-- ── Page Header ─────────────────────────────────────────────────── -->
            <div
                class="flex items-center justify-between border-b px-6 py-4"
                style="background: var(--pos-bg-secondary); border-color: var(--pos-border-strong);"
            >
                <div>
                    <h1 class="text-2xl font-bold" style="color: var(--pos-text-secondary); letter-spacing: 0.5px;">
                        Manajemen Produk
                    </h1>
                </div>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto px-6 py-5" style="color: var(--pos-bg-primary);">
                <!-- ── Tabs ───────────────────────────────────────────────────── -->
                <Tabs default-value="produk" class="w-full">
                    <TabsContent value="produk" class="mt-0">
                        <!-- ── Statistik Cards ─────────────────────────────────────────── -->
                        <div class="mb-6">
                            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4 xl:grid-cols-4">
                                <!-- Tersedia -->
                                <div
                                    class="flex items-center gap-3 rounded-xl border p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg"
                                    style="background: var(--pos-bg-secondary); border-color: var(--pos-border);"
                                >
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                                        style="background: linear-gradient(135deg, #22C55E 0%, #16A34A 100%);"
                                    >
                                        <BoxIcon class="h-6 w-6" style="color: var(--pos-text-inverse);" />
                                    </div>
                                    <div>
                                        <p
                                            class="text-3xl font-bold leading-none"
                                            style="color: var(--pos-success-text);"
                                        >
                                            {{ statTersedia }}
                                            <span class="text-lg font-semibold" style="color: var(--pos-text-muted);"> Jenis</span>
                                        </p>
                                        <p class="mt-1 text-sm font-medium" style="color: var(--pos-text-secondary);">Stok tersedia</p>
                                    </div>
                                </div>

                                <!-- Tipis -->
                                <div
                                    class="flex items-center gap-3 rounded-xl border p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg"
                                    style="background: var(--pos-bg-secondary); border-color: var(--pos-border);"
                                >
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                                        style="background: linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%);"
                                    >
                                        <AlertTriangle class="h-6 w-6" style="color: var(--pos-text-inverse);" />
                                    </div>
                                    <div>
                                        <p
                                            class="text-3xl font-bold leading-none"
                                            style="color: var(--pos-warning-text);"
                                        >
                                            {{ statTipis }}
                                            <span class="text-lg font-semibold" style="color: var(--pos-text-muted);"> Jenis</span>
                                        </p>
                                        <p class="mt-1 text-sm font-medium" style="color: var(--pos-text-secondary);">Stok segera habis (3-9 pcs)</p>
                                    </div>
                                </div>

                                <!-- Habis -->
                                <div
                                    class="flex items-center gap-3 rounded-xl border p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg"
                                    style="background: var(--pos-bg-secondary); border-color: var(--pos-border);"
                                >
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                                        style="background: linear-gradient(135deg, #EF4444 0%, #DC2626 100%);"
                                    >
                                        <XCircle class="h-6 w-6" style="color: var(--pos-text-inverse);" />
                                    </div>
                                    <div>
                                        <p
                                            class="text-3xl font-bold leading-none"
                                            style="color: var(--pos-danger-text);"
                                        >
                                            {{ statHabis }}
                                            <span class="text-lg font-semibold" style="color: var(--pos-text-muted);"> Jenis</span>
                                        </p>
                                        <p class="mt-1 text-sm font-medium" style="color: var(--pos-text-secondary);">Stok habis (0 pcs)</p>
                                    </div>
                                </div>

                                <!-- Total Produk -->
                                <div
                                    class="flex items-center gap-3 rounded-xl border p-4 transition-all duration-300 hover:scale-105 hover:shadow-lg"
                                    style="background: var(--pos-bg-secondary); border-color: var(--pos-border);"
                                >
                                    <div
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                                        style="background: linear-gradient(135deg, #8B5CF6 0%, #06B6D4 100%);"
                                    >
                                        <Package class="h-6 w-6" style="color: var(--pos-text-inverse);" />
                                    </div>
                                    <div>
                                        <p
                                            class="text-3xl font-bold leading-none"
                                            style="color: var(--pos-text-primary);"
                                        >
                                            {{ products.total }}
                                            <span class="text-lg font-semibold" style="color: var(--pos-text-muted);"> Jenis</span>
                                        </p>
                                        <p class="mt-1 text-sm font-medium" style="color: var(--pos-text-secondary);">Total produk</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── Category Quick Filter Chips ─────────────────────────────────────────── -->
                        <div class="mb-4">
                            <div class="flex gap-2 flex-wrap">
                                <button
                                    v-for="cat in categoriesWithAll"
                                    :key="cat.id"
                                    @click="categorySlug = cat.slug === 'all' ? 'all' : cat.slug"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-all hover:scale-105"
                                    :class="[
                                        categorySlug === cat.slug || (categorySlug === 'all' && cat.slug === 'all')
                                            ? 'bg-teal-600 text-teal-600 border-teal-600 shadow-sm'
                                            : 'bg-white text-gray-600 border-gray-200 hover:border-teal-300 hover:text-teal-600'
                                    ]"
                                    style="background: var(--pos-bg-secondary); border-color: var(--pos-border);"
                                >
                                    <component
                                        v-if="cat.slug !== 'all'"
                                        :is="getCategoryIcon(cat.name)"
                                        class="w-3 h-3"
                                    />
                                    {{ cat.name }}
                                    <span
                                        class="ml-0.5 px-1.5 py-0.5 rounded-full text-xs"
                                        :class="[
                                            categorySlug === cat.slug || (categorySlug === 'all' && cat.slug === 'all')
                                                ? 'bg-red'
                                                : 'bg-gray-50'
                                        ]"
                                    >
                                        {{ cat.slug === 'all' ? products.total : products.data.filter(p => p.category?.slug === cat.slug).length }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- ── Table Card ──────────────────────────────────────── -->
                        <div
                            class="overflow-hidden rounded-xl border shadow-lg transition-all duration-300"
                            style="background: #ffffff; border-color: var(--pos-border);"
                        >
                            <!-- Table toolbar -->
                            <div
                                class="flex flex-wrap items-center gap-3 border-b px-4 py-3"
                                style="border-color: var(--pos-border); background: var(--pos-brand-light);"
                            >
                                <!-- Left: Search -->
                                <div class="flex items-center overflow-hidden rounded-lg border transition-all duration-300" style="border-color: var(--pos-border);">
                                    <div class="relative">
                                        <Search
                                            class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2"
                                            style="color: var(--pos-text-light);"
                                        />
                                        <input
                                            v-model="search"
                                            type="text"
                                            placeholder="Cari produk, kode, flavor…"
                                            class="h-10 border-0 pl-8 pr-8 text-sm outline-none transition-all duration-300 focus:ring-2 focus:ring-offset-2"
                                            style="color: var(--pos-text-primary); width: 280px; background: var(--pos-bg-secondary); --tw-ring-color: var(--pos-brand-primary); --tw-ring-offset-color: var(--pos-brand-light);"
                                        />
                                        <button
                                            v-if="search"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 transition-all duration-300"
                                            style="color: var(--pos-text-light);"
                                            @click="search = ''"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Category -->
                                <Select v-model="categorySlug">
                                    <SelectTrigger
                                        class="h-10 border text-sm font-medium transition-all duration-300 hover:ring-2 hover:ring-offset-2"
                                        style="border-color: var(--pos-border); background: var(--pos-bg-secondary); color: var(--pos-text-primary); --tw-ring-color: var(--pos-brand-primary); --tw-ring-offset-color: var(--pos-brand-light);"
                                    >
                                        <SelectValue placeholder="Semua Kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Kategori</SelectItem>
                                        <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.slug">
                                            {{ cat.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>

                                <!-- Stock status -->
                                <Select v-model="stockStatus">
                                    <SelectTrigger
                                        class="h-10 border text-sm font-medium transition-all duration-300 hover:ring-2 hover:ring-offset-2"
                                        style="border-color: var(--pos-border); background: var(--pos-bg-secondary); color: var(--pos-text-primary); --tw-ring-color: var(--pos-brand-primary); --tw-ring-offset-color: var(--pos-brand-light);"
                                    >
                                        <SelectValue placeholder="Semua Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Status</SelectItem>
                                        <SelectItem value="tersedia">Tersedia</SelectItem>
                                        <SelectItem value="stok_rendah">Stok Tipis</SelectItem>
                                        <SelectItem value="habis">Stok Habis</SelectItem>
                                    </SelectContent>
                                </Select>

                                <!-- Active filter indicator -->
                                <span
                                    v-if="hasActiveFilters"
                                    class="cursor-pointer rounded-full px-3 py-1.5 text-sm font-bold transition-all duration-300 hover:scale-110"
                                    style="background: var(--pos-brand-primary); color: var(--pos-text-inverse); box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);"
                                    @click="resetFilters"
                                >
                                    Filter Aktif ✕
                                </span>
                            </div>
                        </div>

                            <!-- Table -->
                            <div class="w-full max-w-full overflow-x-auto pb-2">
                                <Table class="min-w-1480px">
                                    <TableHeader>
                                        <TableRow style="background: var(--pos-bg-secondary);">
                                            <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Image</TableHead>
                                            <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Code</TableHead>
                                            <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Name</TableHead>
                                            <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Brand</TableHead>
                                            <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Category</TableHead>
                                            <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Flavor</TableHead>
                                            <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Nicotine</TableHead>
                                            <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Size (ml)</TableHead>
                                            <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Base Price</TableHead>
                                            <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Stock</TableHead>
                                            <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Status</TableHead>
                                            <TableHead class="w-10 pr-4" />
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <!-- Loading skeleton -->
                                        <template v-if="isLoading">
                                            <TableRow v-for="n in 8" :key="n">
                                                <TableCell><Skeleton class="h-10 w-10 rounded-md" /></TableCell>
                                                <TableCell><Skeleton class="h-4 w-20" /></TableCell>
                                                <TableCell><Skeleton class="h-4 w-32" /></TableCell>
                                                <TableCell><Skeleton class="h-4 w-20" /></TableCell>
                                                <TableCell><Skeleton class="h-4 w-20" /></TableCell>
                                                <TableCell><Skeleton class="h-4 w-20" /></TableCell>
                                                <TableCell><Skeleton class="h-4 w-20" /></TableCell>
                                                <TableCell><Skeleton class="h-4 w-20" /></TableCell>
                                                <TableCell><Skeleton class="h-4 w-12" /></TableCell>
                                                <TableCell><Skeleton class="h-4 w-12" /></TableCell>
                                                <TableCell><Skeleton class="h-5 w-16 rounded-full" /></TableCell>
                                            </TableRow>
                                        </template>

                                        <!-- Data rows -->
                                        <template v-else-if="products.data.length" class="overflow-y-auto">
                                            <TableRow
                                                v-for="product in products.data"
                                                :key="product.id"
                                                class="group cursor-pointer transition-all duration-300 hover:bg-teal-500/10"
                                                style="border-color: var(--pos-border);"
                                                @click="openDetail(product)"
                                            >
                                                <TableCell>
                                                    <div
                                                        class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg transition-all duration-300 group-hover:scale-105"
                                                        style="background: var(--pos-brand-light); color: var(--pos-brand-primary);"
                                                    >
                                                        <img
                                                            v-if="product.image_url"
                                                            :src="product.image_url"
                                                            :alt="product.name"
                                                            class="h-full w-full object-cover"
                                                        />
                                                        <span v-else>{{ productInitials(product.name) }}</span>
                                                    </div>
                                                </TableCell>

                                                <TableCell>
                                                    <span class="font-mono text-xs font-semibold" style="color: var(--pos-brand-primary);">
                                                        {{ product.sku }}
                                                    </span>
                                                </TableCell>

                                                <TableCell>
                                                    <span class="text-sm font-semibold" style="color: var(--pos-text-primary);">
                                                        {{ product.name }}
                                                    </span>
                                                </TableCell>

                                                <TableCell>
                                                    <span class="text-sm font-medium" style="color: var(--pos-text-light);">
                                                        {{ product.brand?.name ?? '—' }}
                                                    </span>
                                                </TableCell>

                                                <TableCell>
                                                    <span class="text-sm font-medium" style="color: var(--pos-text-light);">
                                                        {{ product.category?.name ?? '—' }}
                                                    </span>
                                                </TableCell>

                                                <TableCell>
                                                    <span class="text-sm font-medium" style="color: var(--pos-text-light);">
                                                        {{ (product as any).flavor ?? '—' }}
                                                    </span>
                                                </TableCell>

                                                <TableCell>
                                                    <span class="text-sm tabular-nums" style="color: var(--pos-text-light);">
                                                        {{ (product as any).nicotine_strength ? `${(product as any).nicotine_strength} mg` : '—' }}
                                                    </span>
                                                </TableCell>

                                                <TableCell>
                                                    <span class="text-sm tabular-nums" style="color: var(--pos-text-light);">
                                                        {{ (product as any).size_ml ? `${(product as any).size_ml} ml` : '—' }}
                                                    </span>
                                                </TableCell>

                                                <TableCell>
                                                    <span class="text-sm font-bold tabular-nums" style="color: var(--pos-text-primary);">
                                                        {{ formatPrice((product as any).base_price ?? product.price) }}
                                                    </span>
                                                </TableCell>

                                                <TableCell>
                                                    <div class="flex items-center gap-1.5">
                                                        <span class="text-sm font-bold tabular-nums" :style="{ color: stockInfo(product.stock).color }">
                                                            {{ product.stock }}
                                                        </span>
                                                        <span class="text-xs" style="color: var(--pos-text-light);">unit</span>
                                                        <div v-if="product.stock > 0" class="w-16 h-1.5 rounded-full overflow-hidden" style="background: var(--pos-border);">
                                                            <div
                                                                class="h-full rounded-full transition-all duration-300"
                                                                :class="product.stock <= 20 ? 'bg-amber-400' : 'bg-emerald-400'"
                                                                :style="{ width: `${Math.min(100, (product.stock / 100) * 100)}%` }"
                                                            />
                                                        </div>
                                                    </div>
                                                </TableCell>

                                                <!-- Status -->
                                                <TableCell>
                                                    <span
                                                        class="rounded-full px-2.5 py-0.5 text-xs font-semibold transition-all duration-300"
                                                        :style="product.stock === 0
                                                            ? 'background: var(--pos-danger-bg); color: var(--pos-danger-text);'
                                                            : product.stock <= 20
                                                                ? 'background: var(--pos-warning-bg); color: var(--pos-warning-text);'
                                                                : 'background: var(--pos-success-bg); color: var(--pos-success-text);'"
                                                    >
                                                        {{ stockInfo(product.stock).label }}
                                                    </span>
                                                </TableCell>

                                                <!-- Aksi -->
                                                <TableCell class="pr-4" @click.stop>
                                                    <div class="flex items-center justify-end gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <Tooltip>
                                                            <TooltipTrigger as-child>
                                                                <button
                                                                    class="rounded-lg p-2 transition-all duration-300 hover:bg-teal-50 hover:text-teal-600"
                                                                    style="color: var(--pos-text-muted);"
                                                                    @click="openDetail(product)"
                                                                >
                                                                    <Eye class="h-4 w-4" />
                                                                </button>
                                                            </TooltipTrigger>
                                                            <TooltipContent>Lihat detail</TooltipContent>
                                                        </Tooltip>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        </template>

                                        <!-- Empty state -->
                                        <TableRow v-else>
                                            <TableCell colspan="12" class="py-16 text-center">
                                                <div class="flex flex-col items-center justify-center">
                                                    <Wind class="mx-auto mb-2 h-10 w-10" style="color: var(--pos-text-light); opacity: 0.2;" />
                                                    <p class="text-sm font-medium" style="color: var(--pos-text-light);">Produk tidak ditemukan</p>
                                                    <p class="mt-1 text-xs" style="color: var(--pos-text-muted);">Coba ubah kata kunci atau filter kategori</p>
                                                    <button
                                                        v-if="hasActiveFilters"
                                                        class="mt-3 rounded-lg px-4 py-1.5 text-sm font-semibold transition-all duration-300 hover:scale-105"
                                                        style="background: var(--pos-brand-primary); color: var(--pos-text-inverse); border: 2px solid transparent;"
                                                        @click="resetFilters"
                                                    >
                                                        Hapus semua filter
                                                    </button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>

                                <!-- ── Pagination ──────────────────────────────────── -->
                                <div
                                    class="flex items-center justify-between border-t px-4 py-3"
                                    style="border-color: var(--pos-border); background: var(--pos-brand-light);"
                                >
                                    <p class="text-sm font-medium" style="color: var(--pos-text-muted);">
                                        Menampilkan
                                        <strong style="color: var(--pos-text-primary);">{{ products.data.length }}</strong>
                                        dari
                                        <strong style="color: var(--pos-brand-primary);">{{ products.total }}</strong>
                                        produk
                                    </p>
                                    <div v-if="products.last_page > 1" class="flex items-center gap-1">
                                        <button
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border text-xs font-semibold transition-all duration-300 disabled:opacity-40 hover:enabled:scale-105 hover:shadow-md"
                                            style="border-color: var(--pos-border); background: var(--pos-bg-secondary); color: var(--pos-brand-primary); --tw-ring-color: var(--pos-brand-primary); --tw-ring-offset-color: var(--pos-brand-light);"
                                            :disabled="products.current_page === 1"
                                            @click="goToPage(products.current_page - 1)"
                                        >
                                            <ChevronLeft class="h-3.5 w-3.5" />
                                        </button>
                                        <template v-for="link in products.links.slice(1, -1)" :key="`${link.label}-${link.url}`">
                                            <button
                                                class="flex h-8 min-w-8 items-center justify-center rounded-lg border px-1.5 text-xs font-semibold transition-all duration-300 disabled:opacity-40 hover:enabled:scale-105 hover:shadow-md"
                                                :style="link.active
                                                    ? 'background: var(--pos-brand-primary); color: var(--pos-text-inverse); border-color: var(--pos-brand-primary);'
                                                    : 'background: var(--pos-bg-secondary); color: var(--pos-brand-primary); border-color: var(--pos-border);'"
                                                :disabled="!extractPageFromUrl(link.url)"
                                                v-html="link.label"
                                                @click="extractPageFromUrl(link.url) && goToPage(extractPageFromUrl(link.url) as number)"
                                            />
                                        </template>
                                        <button
                                            class="flex h-8 w-8 items-center justify-center rounded-lg border px-1.5 text-xs font-semibold transition-all duration-300 disabled:opacity-40 hover:enabled:scale-105 hover:shadow-md"
                                            style="border-color: var(--pos-border); background: #ffffff; color: #0891B2; --tw-ring-color: #22D3EE; --tw-ring-offset-color: #ECFEFF;"
                                            :disabled="products.current_page === products.last_page"
                                            @click="goToPage(products.current_page + 1)"
                                        >
                                            <ChevronRight class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </TabsContent>
                    </Tabs>
            </div>

            <!-- ── Detail Sheet ────────────────────────────────────────────────────── -->
            <Sheet v-model:open="sheetOpen">
                <SheetContent class="w-full overflow-y-auto p-5 sm:max-w-md sm:p-6">
                    <SheetHeader>
                        <SheetTitle class="text-xl font-bold" style="color: var(--pos-text-primary);">Detail Produk</SheetTitle>
                        <SheetDescription class="text-sm font-medium" style="color: var(--pos-text-muted);">Informasi lengkap produk</SheetDescription>
                    </SheetHeader>

                    <div v-if="selectedProduct" class="mt-5 flex flex-col gap-4">
                        <!-- Image -->
                        <div
                            class="aspect-square w-full overflow-hidden rounded-xl shadow-lg"
                            style="background: linear-gradient(135deg, var(--pos-brand-primary) 0%, var(--pos-brand-light) 100%);"
                        >
                            <img
                                v-if="selectedProduct.image_url"
                                :src="selectedProduct.image_url"
                                :alt="selectedProduct.name"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center">
                                <span class="text-4xl font-bold" style="color: var(--pos-text-inverse); opacity: 0.9;">
                                    {{ productInitials(selectedProduct.name) }}
                                </span>
                            </div>
                        </div>

                        <!-- Info stok & harga -->
                        <div class="space-y-3">
                            <div class="flex items-center gap-3 p-4 rounded-xl" style="background: var(--pos-brand-light);">
                                <div class="text-center">
                                    <p class="text-sm font-medium" style="color: var(--pos-text-muted);">Stok</p>
                                    <p class="text-2xl font-bold" style="color: var(--pos-brand-primary);">{{ selectedProduct.stock }}</p>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium" style="color: var(--pos-text-muted);">Harga Dasar</p>
                                    <p class="text-3xl font-bold" style="color: var(--pos-text-primary);">{{ formatPrice((selectedProduct as any).base_price ?? selectedProduct.price) }}</p>
                                </div>
                            </div>

                            <!-- Tombol Aksi -->
                            <Button
                                class="w-full h-12 text-base font-semibold transition-all duration-300 hover:scale-105 hover:shadow-lg"
                                style="background: var(--pos-brand-primary); color: var(--pos-text-inverse);"
                                @click="sheetOpen = false"
                            >
                                Tutup
                            </Button>
                        </div>

                        <!-- Detail tambahan -->
                        <div class="space-y-2">
                            <Separator />

                            <!-- Brand -->
                            <div class="flex items-center justify-between px-4 py-2">
                                <span class="text-sm font-medium" style="color: #64748B;">Brand</span>
                                <span class="text-base font-semibold" style="color: #164E63;">{{ selectedProduct.brand?.name ?? '—' }}</span>
                            </div>

                            <!-- Kategori -->
                            <div class="flex items-center justify-between px-4 py-2">
                                <span class="text-sm font-medium" style="color: #64748B;">Kategori</span>
                                <span class="text-base font-semibold" style="color: #164E63;">{{ selectedProduct.category?.name ?? '—' }}</span>
                            </div>

                            <!-- Flavor & Nicotine -->
                            <div class="flex items-center justify-between px-4 py-2">
                                <div>
                                    <span class="text-sm font-medium" style="color: #64748B;">Flavor</span>
                                    <span class="text-base font-semibold" style="color: #164E63;">{{ (selectedProduct as any).flavor ?? '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-sm font-medium" style="color: #64748B;">Nicotine</span>
                                    <span class="text-base font-semibold" style="color: #164E63;">{{ (selectedProduct as any).nicotine_strength ? `${(selectedProduct as any).nicotine_strength} mg` : '—' }}</span>
                                </div>
                            </div>

                            <!-- Volume -->
                            <div class="flex items-center justify-between px-4 py-2">
                                <span class="text-sm font-medium" style="color: #64748B;">Volume</span>
                                <span class="text-base font-semibold" style="color: #164E63;">{{ selectedProduct.volume ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                </SheetContent>
            </Sheet>
        </div>
    </TooltipProvider>
</template>

<style scoped>
/* Custom scrollbar untuk table */
:deep(.overflow-y-auto)::-webkit-scrollbar {
  width: 6px;
}

:deep(.overflow-y-auto)::-webkit-scrollbar-track {
  background: #ECFEFF;
  border-radius: 3px;
}

:deep(.overflow-y-auto)::-webkit-scrollbar-thumb {
  background: #0891B2;
  border-radius: 3px;
}

:deep(.overflow-y-auto)::-webkit-scrollbar-thumb:hover {
  background: #22D3EE;
  box-shadow: 0 0 0 2px rgba(8, 145, 178, 0.3);
}

/* Focus styles */
input:focus,
select:focus {
  outline: 2px solid #0891B2;
  outline-offset: 2px;
}
</style>