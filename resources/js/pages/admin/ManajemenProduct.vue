<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useDebounceFn } from '@vueuse/core'
import {
    Search, X, Package, Eye, Pencil, Trash2,
    Zap, Wind, Layers, Tag, Plus,
    ChevronLeft, ChevronRight,
    ChevronsUpDown, ArrowUp, ArrowDown,
    BoxIcon, AlertTriangle, XCircle, Warehouse,
} from 'lucide-vue-next'
import type { Category, Product, ProductPageProps } from '@/types/pos'

import { Input }     from '@/components/ui/input'
import { Button }    from '@/components/ui/button'
import { Badge }     from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Skeleton }  from '@/components/ui/skeleton'
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

import AdminLayout from '@/layouts/admin/AdminLayout.vue'
import { index as adminProductsRoute, create as createRoute, destroy as destroyRoute } from '@/routes/admin/products'
import { index as adminDashboardRoute } from '@/routes/admin/dashboard'

// ── Layout ────────────────────────────────────────────────────────────────────

defineOptions({
    layout: (h: any, page: any) => h(AdminLayout, {
        breadcrumbs: [
            { title: 'Dashboard', href: adminDashboardRoute.url() },
            { title: 'Manajemen Produk' },
        ],
    }, () => page),
})

// ── Props ─────────────────────────────────────────────────────────────────────

const props = defineProps<
    ProductPageProps & {
        selectedStockStatus?: string | null
        searchQuery?: string | null
        selectedCategory?: Category | null
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
        adminProductsRoute.url({
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
    router.get(adminProductsRoute.url(), {}, { preserveState: false })
}

function extractPageFromUrl(url: string | null): number | null {
    if (!url) return null
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
        adminProductsRoute.url({
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

function goToCreate() {
    router.get(createRoute.url())
}

function goToEdit(product: Product) {
    router.get(`/admin/products/${product.id}/edit`)
}

function confirmDelete(product: Product) {
    if (!confirm(`Hapus produk "${product.name}"? Tindakan ini tidak dapat dibatalkan.`)) return
    router.delete(destroyRoute.url(product.id), {
        preserveScroll: true,
    })
}

const debouncedSearch = useDebounceFn(applyFilters, 400)
watch(search, debouncedSearch)
watch([categorySlug, stockStatus], applyFilters)

// ── Sorting (client-side, current page) ───────────────────────────────────────

type SortKey =
    | 'sku' | 'name' | 'brand' | 'category' | 'flavor'
    | 'nicotine' | 'size' | 'base_price' | 'stock' | 'status'
type SortDir = 'asc' | 'desc'

const sortKey = ref<SortKey | null>(null)
const sortDir = ref<SortDir>('asc')

function toggleSort(key: SortKey) {
    if (sortKey.value !== key) {
        sortKey.value = key
        sortDir.value = 'asc'
    } else if (sortDir.value === 'asc') {
        sortDir.value = 'desc'
    } else {
        sortKey.value = null
        sortDir.value = 'asc'
    }
}

function sortValue(p: Product, key: SortKey): string | number {
    const a = p as any
    switch (key) {
        case 'sku':        return p.sku ?? ''
        case 'name':       return p.name ?? ''
        case 'brand':      return p.brand?.name ?? ''
        case 'category':   return p.category?.name ?? ''
        case 'flavor':     return a.flavor ?? ''
        case 'nicotine':   return Number(a.nicotine_strength ?? -1)
        case 'size':       return Number(a.size_ml ?? -1)
        case 'base_price': return Number(a.base_price ?? p.price ?? 0)
        case 'stock':      return Number(p.stock ?? 0)
        case 'status':     return p.stock === 0 ? 0 : p.stock <= 20 ? 1 : 2
    }
}

const sortedProducts = computed<Product[]>(() => {
    const list = [...props.products.data]
    if (!sortKey.value) return list
    const k = sortKey.value
    const dir = sortDir.value === 'asc' ? 1 : -1
    return list.sort((a, b) => {
        const av = sortValue(a, k)
        const bv = sortValue(b, k)
        if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir
        return String(av).localeCompare(String(bv), 'id', { sensitivity: 'base' }) * dir
    })
})

// ── Derived stats ─────────────────────────────────────────────────────────────

const statTersedia  = computed(() => props.products.total)
const statTipis     = computed(() => props.products.data.filter(p => p.stock > 0 && p.stock <= 20).length)
const statHabis     = computed(() => props.products.data.filter(p => p.stock === 0).length)

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
    if (stock === 0)  return { label: 'Habis',       variant: 'destructive', color: 'var(--pos-danger-text)' }
    if (stock <= 20)  return { label: 'Stok Tipis',  variant: 'secondary',   color: 'var(--pos-warning-text)' }
    return                   { label: 'Tersedia',    variant: 'default',     color: 'var(--pos-success-text)' }
}
</script>

<template>
    <TooltipProvider>
    <div class="adm-page px-6 py-5">

        <!-- ── Summary Cards ──────────────────────────────────────────────── -->
        <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">

            <div class="flex items-center gap-3 rounded-lg border p-4 bg-white" style="border-color: var(--pos-border);">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: var(--pos-bg-success);">
                    <BoxIcon class="h-5 w-5" style="color: var(--pos-success-text);" />
                </div>
                <div>
                    <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
                        {{ statTersedia }}<span class="text-sm font-semibold"> Jenis</span>
                    </p>
                    <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Total produk</p>
                </div>
            </div>

            <div class="flex items-center gap-3 rounded-lg border p-4 bg-white" style="border-color: var(--pos-border);">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: var(--pos-bg-warning);">
                    <AlertTriangle class="h-5 w-5" style="color: var(--pos-warning-text);" />
                </div>
                <div>
                    <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
                        {{ statTipis }}<span class="text-sm font-semibold"> Jenis</span>
                    </p>
                    <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Stok segera habis</p>
                </div>
            </div>

            <div class="flex items-center gap-3 rounded-lg border p-4 bg-white" style="border-color: var(--pos-border);">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: var(--pos-bg-danger);">
                    <XCircle class="h-5 w-5" style="color: var(--pos-danger-text);" />
                </div>
                <div>
                    <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
                        {{ statHabis }}<span class="text-sm font-semibold"> Jenis</span>
                    </p>
                    <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Stok habis</p>
                </div>
            </div>

            <div class="flex items-center gap-3 rounded-lg border p-4 bg-white" style="border-color: var(--pos-border);">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: var(--pos-brand-light);">
                    <Warehouse class="h-5 w-5" style="color: var(--pos-brand-primary);" />
                </div>
                <div>
                    <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
                        {{ categories.length }}<span class="text-sm font-semibold"> Kategori</span>
                    </p>
                    <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Total kategori</p>
                </div>
            </div>
        </div>

        <!-- ── Table Card ──────────────────────────────────────────────────── -->
        <div class="overflow-hidden rounded-lg border bg-white" style="border-color: var(--pos-border); box-shadow: var(--pos-shadow);">

            <!-- Toolbar -->
            <div class="flex flex-wrap items-center gap-2 border-b px-4 py-3" style="border-color: var(--pos-border); background: #f8fafc;">
                <!-- Search -->
                <div class="flex items-center overflow-hidden rounded-md border" style="border-color: var(--pos-border);">
                    <div class="relative">
                        <Search class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2" style="color: var(--pos-text-muted);" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari produk, kode, flavor…"
                            class="h-8 border-0 pl-8 pr-8 text-xs outline-none transition"
                            style="color: var(--pos-text-secondary); width: 240px; background: #fff;"
                        />
                        <button v-if="search" class="absolute right-2 top-1/2 -translate-y-1/2" style="color: var(--pos-text-muted);" @click="search = ''">
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>
                    <button
                        class="h-8 border-l px-3 text-xs font-semibold transition"
                        style="border-color: var(--pos-border); background: var(--pos-brand-primary); color: #fff;"
                        @click="applyFilters"
                    >
                        Cari
                    </button>
                </div>

                <!-- Category filter -->
                <Select v-model="categorySlug">
                    <SelectTrigger class="h-8 w-40 border text-xs" style="border-color: var(--pos-border);">
                        <SelectValue placeholder="Semua Kategori" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Kategori</SelectItem>
                        <SelectItem v-for="cat in categories" :key="cat.id" :value="cat.slug">
                            {{ cat.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <!-- Stock status filter -->
                <Select v-model="stockStatus">
                    <SelectTrigger class="h-8 w-36 border text-xs" style="border-color: var(--pos-border);">
                        <SelectValue placeholder="Semua Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua Status</SelectItem>
                        <SelectItem value="tersedia">Tersedia</SelectItem>
                        <SelectItem value="stok_rendah">Stok Tipis</SelectItem>
                        <SelectItem value="habis">Stok Habis</SelectItem>
                    </SelectContent>
                </Select>

                <span
                    v-if="hasActiveFilters"
                    class="cursor-pointer rounded-full px-2 py-0.5 text-xs font-semibold"
                    style="background: var(--pos-brand-light); color: var(--pos-brand-primary);"
                    @click="resetFilters"
                >
                    Filter Aktif ✕
                </span>

                <!-- Spacer -->
                <div class="ml-auto">
                    <button
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                        style="background: var(--pos-brand-primary); color: #fff;"
                        @click="goToCreate"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        Tambah Produk
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="w-full max-w-full overflow-x-auto pb-2">
            <Table class="min-w-[1500px]">
                <TableHeader>
                    <TableRow style="background: #f1f5f9;">
                        <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Image</TableHead>
                        <TableHead
                            v-for="col in [
                                { key: 'sku',        label: 'Code' },
                                { key: 'name',       label: 'Name' },
                                { key: 'brand',      label: 'Brand' },
                                { key: 'category',   label: 'Category' },
                                { key: 'flavor',     label: 'Flavor' },
                                { key: 'nicotine',   label: 'Nicotine' },
                                { key: 'size',       label: 'Size (ml)' },
                                { key: 'base_price', label: 'Base Price' },
                                { key: 'stock',      label: 'Stock' },
                                { key: 'status',     label: 'Status' },
                            ]"
                            :key="col.key"
                            class="cursor-pointer select-none text-xs font-bold uppercase tracking-wide hover:bg-[var(--pos-bg-accent)]"
                            :style="{ color: sortKey === col.key ? 'var(--pos-brand-primary)' : 'var(--pos-text-muted)' }"
                            :aria-sort="sortKey === col.key ? (sortDir === 'asc' ? 'ascending' : 'descending') : 'none'"
                            @click="toggleSort(col.key as SortKey)"
                        >
                            <span class="inline-flex items-center gap-1">
                                {{ col.label }}
                                <ArrowUp v-if="sortKey === col.key && sortDir === 'asc'" class="h-3 w-3" />
                                <ArrowDown v-else-if="sortKey === col.key && sortDir === 'desc'" class="h-3 w-3" />
                                <ChevronsUpDown v-else class="h-3 w-3 opacity-40" />
                            </span>
                        </TableHead>
                        <TableHead class="w-20 pr-4 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Aksi</TableHead>
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
                            <TableCell><Skeleton class="h-4 w-12" /></TableCell>
                            <TableCell><Skeleton class="h-4 w-12" /></TableCell>
                            <TableCell><Skeleton class="h-4 w-24" /></TableCell>
                            <TableCell><Skeleton class="h-4 w-12" /></TableCell>
                            <TableCell><Skeleton class="h-5 w-16 rounded-full" /></TableCell>
                            <TableCell />
                        </TableRow>
                    </template>

                    <!-- Data rows -->
                    <template v-else-if="sortedProducts.length">
                        <TableRow
                            v-for="product in sortedProducts"
                            :key="product.id"
                            class="group cursor-pointer transition-colors hover:bg-[var(--pos-bg-accent)]"
                            style="border-color: var(--pos-border);"
                            @click="openDetail(product)"
                        >
                            <TableCell>
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-md text-xs font-bold" style="background: var(--pos-bg-accent); color: var(--pos-brand-dark);">
                                    <img v-if="product.image_url" :src="product.image_url" :alt="product.name" class="h-full w-full object-cover" />
                                    <span v-else>{{ productInitials(product.name) }}</span>
                                </div>
                            </TableCell>

                            <TableCell>
                                <span class="font-mono text-xs font-semibold" style="color: var(--pos-brand-primary);">{{ product.sku }}</span>
                            </TableCell>

                            <TableCell>
                                <span class="text-sm font-semibold" style="color: var(--pos-text-secondary);">{{ product.name }}</span>
                            </TableCell>

                            <TableCell>
                                <span class="text-sm" style="color: var(--pos-text-secondary);">{{ product.brand?.name ?? '—' }}</span>
                            </TableCell>

                            <TableCell>
                                <span class="text-sm" style="color: var(--pos-text-secondary);">{{ product.category?.name ?? '—' }}</span>
                            </TableCell>

                            <TableCell>
                                <span class="text-sm" style="color: var(--pos-text-secondary);">{{ (product as any).flavor ?? '—' }}</span>
                            </TableCell>

                            <TableCell>
                                <span class="text-sm tabular-nums" style="color: var(--pos-text-secondary);">
                                    {{ (product as any).nicotine_strength ? `${(product as any).nicotine_strength} mg` : '—' }}
                                </span>
                            </TableCell>

                            <TableCell>
                                <span class="text-sm tabular-nums" style="color: var(--pos-text-secondary);">
                                    {{ (product as any).size_ml ? `${(product as any).size_ml} ml` : '—' }}
                                </span>
                            </TableCell>

                            <TableCell>
                                <span class="text-sm font-semibold tabular-nums" style="color: var(--pos-text-secondary);">
                                    {{ formatPrice((product as any).base_price ?? product.price) }}
                                </span>
                            </TableCell>

                            <TableCell>
                                <span class="text-sm font-bold tabular-nums" :style="{ color: stockInfo(product.stock).color }">{{ product.stock }}</span>
                            </TableCell>

                            <TableCell>
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold"
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
                                <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button class="rounded p-1" style="color: var(--pos-brand-primary);" @click="openDetail(product)">
                                                <Eye class="h-4 w-4" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent>Lihat detail</TooltipContent>
                                    </Tooltip>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button class="rounded p-1" style="color: var(--pos-text-muted);" @click="goToEdit(product)">
                                                <Pencil class="h-4 w-4" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent>Edit produk</TooltipContent>
                                    </Tooltip>
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <button class="rounded p-1" style="color: var(--pos-danger-text);" @click="confirmDelete(product)">
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </TooltipTrigger>
                                        <TooltipContent>Hapus produk</TooltipContent>
                                    </Tooltip>
                                </div>
                            </TableCell>
                        </TableRow>
                    </template>

                    <!-- Empty state -->
                    <TableRow v-else>
                        <TableCell colspan="12" class="py-16 text-center">
                            <Package class="mx-auto mb-2 h-10 w-10" style="color: var(--pos-text-muted); opacity: 0.3;" />
                            <p class="text-sm font-medium" style="color: var(--pos-text-muted);">Produk tidak ditemukan</p>
                            <p class="mt-1 text-xs" style="color: var(--pos-text-light);">Coba ubah kata kunci atau filter</p>
                            <button
                                v-if="hasActiveFilters"
                                class="mt-3 rounded-lg px-4 py-1.5 text-xs font-semibold transition"
                                style="background: var(--pos-brand-primary); color: #fff;"
                                @click="resetFilters"
                            >
                                Hapus semua filter
                            </button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-between border-t px-4 py-3" style="border-color: var(--pos-border); background: #f8fafc;">
                <p class="text-xs" style="color: var(--pos-text-muted);">
                    Menampilkan
                    <strong style="color: var(--pos-text-secondary);">{{ products.data.length }}</strong>
                    dari
                    <strong style="color: var(--pos-text-secondary);">{{ products.total }}</strong>
                    produk
                </p>

                <div v-if="products.last_page > 1" class="flex items-center gap-1">
                    <button
                        class="flex h-7 w-7 items-center justify-center rounded border text-xs transition disabled:opacity-40"
                        style="border-color: var(--pos-border);"
                        :disabled="products.current_page === 1"
                        @click="goToPage(products.current_page - 1)"
                    >
                        <ChevronLeft class="h-3.5 w-3.5" />
                    </button>

                    <template v-for="link in products.links.slice(1, -1)" :key="`${link.label}-${link.url}`">
                        <button
                            class="flex h-7 min-w-7 items-center justify-center rounded border px-1.5 text-xs font-semibold transition disabled:opacity-40"
                            :style="link.active
                                ? 'background: var(--pos-brand-primary); color: #fff; border-color: var(--pos-brand-primary);'
                                : 'border-color: var(--pos-border); color: var(--pos-text-secondary);'"
                            :disabled="!extractPageFromUrl(link.url)"
                            v-html="link.label"
                            @click="extractPageFromUrl(link.url) && goToPage(extractPageFromUrl(link.url) as number)"
                        />
                    </template>

                    <button
                        class="flex h-7 w-7 items-center justify-center rounded border text-xs transition disabled:opacity-40"
                        style="border-color: var(--pos-border);"
                        :disabled="products.current_page === products.last_page"
                        @click="goToPage(products.current_page + 1)"
                    >
                        <ChevronRight class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Detail Sheet ─────────────────────────────────────────────────── -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent class="adm-sheet w-full overflow-y-auto p-5 sm:max-w-md sm:p-6">
                <SheetHeader>
                    <SheetTitle>Detail Produk</SheetTitle>
                    <SheetDescription>Informasi lengkap produk</SheetDescription>
                </SheetHeader>

                <div v-if="selectedProduct" class="mt-5 flex flex-col gap-4">
                    <div class="flex flex-col items-center gap-3 rounded-xl p-5 text-center" style="background: var(--pos-brand-light);">
                        <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-xl" style="background: #fff;">
                            <img v-if="selectedProduct.image_url" :src="selectedProduct.image_url" :alt="selectedProduct.name" class="h-full w-full object-cover" />
                            <span v-else class="text-2xl font-bold" style="color: var(--pos-brand-primary);">{{ productInitials(selectedProduct.name) }}</span>
                        </div>
                        <div>
                            <p class="text-base font-bold" style="color: var(--pos-brand-dark);">{{ selectedProduct.name }}</p>
                            <p class="mt-1 font-mono text-xs" style="color: var(--pos-text-secondary);">{{ selectedProduct.sku }}</p>
                        </div>
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                :style="selectedProduct.stock === 0
                                    ? 'background: #fff; color: var(--pos-danger-text);'
                                    : selectedProduct.stock <= 20
                                        ? 'background: #fff; color: var(--pos-warning-text);'
                                        : 'background: #fff; color: var(--pos-success-text);'"
                            >
                                {{ stockInfo(selectedProduct.stock).label }}
                            </span>
                            <span v-if="selectedProduct.volume" class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold" style="background: #fff; color: var(--pos-text-secondary);">
                                {{ selectedProduct.volume }}
                            </span>
                        </div>
                    </div>

                    <Separator />

                    <div class="flex flex-col divide-y rounded-lg border" style="border-color: var(--pos-border);">
                        <div v-for="(row, i) in [
                            { label: 'Kode Produk', value: selectedProduct.sku },
                            { label: 'Harga Dasar', value: formatPrice((selectedProduct as any).base_price ?? selectedProduct.price) },
                            { label: 'Stok',        value: String(selectedProduct.stock) + ' unit' },
                            { label: 'Flavor',      value: (selectedProduct as any).flavor ?? '—' },
                            { label: 'Nikotin',     value: (selectedProduct as any).nicotine_strength ? `${(selectedProduct as any).nicotine_strength} mg` : '—' },
                            { label: 'Volume',      value: selectedProduct.volume ?? '—' },
                            { label: 'Kategori',    value: selectedProduct.category?.name ?? '—' },
                            { label: 'Brand',       value: selectedProduct.brand?.name ?? '—' },
                        ]" :key="i" class="flex items-center justify-between px-4 py-2.5">
                            <span class="text-xs" style="color: var(--pos-text-muted);">{{ row.label }}</span>
                            <span class="max-w-[60%] truncate text-right text-sm font-semibold" style="color: var(--pos-text-secondary);" :title="String(row.value)">{{ row.value }}</span>
                        </div>
                    </div>

                    <!-- Quick actions -->
                    <div class="flex gap-2 pt-1">
                        <button
                            class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold transition hover:opacity-90"
                            style="background: var(--pos-brand-primary); color: #fff;"
                            @click="sheetOpen = false; goToEdit(selectedProduct)"
                        >
                            <Pencil class="h-3.5 w-3.5" /> Edit
                        </button>
                        <button
                            class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition"
                            style="border-color: var(--pos-danger-text); color: var(--pos-danger-text);"
                            @click="sheetOpen = false; confirmDelete(selectedProduct)"
                        >
                            <Trash2 class="h-3.5 w-3.5" /> Hapus
                        </button>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

    </div>
    </TooltipProvider>
</template>

<style scoped>
.adm-page {
    --pos-bg-primary: #ffffff;
    --pos-bg-secondary: #f9fafb;
    --pos-bg-accent: #ccfbf1;
    --pos-bg-danger: #fee2e2;
    --pos-bg-warning: #fef3c7;
    --pos-bg-success: #dcfce7;
    --pos-border: #e5e7eb;
    --pos-border-strong: #d1d5db;
    --pos-text-primary: #1e293b;
    --pos-text-secondary: #334155;
    --pos-text-muted: #6b7280;
    --pos-text-light: #9ca3af;
    --pos-brand-primary: #14b8a6;
    --pos-brand-hover: #0f9488;
    --pos-brand-light: #ecfeff;
    --pos-brand-dark: #0d9488;
    --pos-success-text: #16a34a;
    --pos-warning-text: #d97706;
    --pos-danger-text: #dc2626;
    --pos-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    background: var(--pos-bg-secondary);
    color: var(--pos-text-primary);
}

textarea:focus,
input:focus {
    border-color: var(--pos-brand-primary);
}
</style>

<style>
/* Sheet/Dialog di-teleport ke <body>, di luar scope .adm-page.
     Re-deklarasi token POS + paksa kontras: bg terang, text gelap. */
.adm-sheet {
    --pos-bg-primary: #ffffff;
    --pos-bg-secondary: #f9fafb;
    --pos-bg-accent: #ccfbf1;
    --pos-bg-danger: #fee2e2;
    --pos-bg-warning: #fef3c7;
    --pos-bg-success: #dcfce7;
    --pos-border: #e5e7eb;
    --pos-border-strong: #d1d5db;
    --pos-text-primary: #0f172a;
    --pos-text-secondary: #1e293b;
    --pos-text-muted: #64748b;
    --pos-text-light: #94a3b8;
    --pos-brand-primary: #14b8a6;
    --pos-brand-hover: #0f9488;
    --pos-brand-light: #ecfeff;
    --pos-brand-dark: #0d9488;
    --pos-success-text: #16a34a;
    --pos-warning-text: #d97706;
    --pos-danger-text: #dc2626;

    background: #ffffff !important;
    color: var(--pos-text-secondary);
}

.adm-sheet [data-slot='sheet-title'],
.adm-sheet [data-slot='dialog-title'] {
    color: var(--pos-text-primary);
    font-weight: 700;
}

.adm-sheet [data-slot='sheet-description'],
.adm-sheet [data-slot='dialog-description'] {
    color: var(--pos-text-muted);
}

.adm-sheet label {
    color: var(--pos-text-secondary);
}

.adm-sheet input,
.adm-sheet textarea,
.adm-sheet select {
    background: #ffffff;
    color: var(--pos-text-primary);
    border-color: var(--pos-border);
}

.adm-sheet input::placeholder,
.adm-sheet textarea::placeholder {
    color: var(--pos-text-light);
}

.adm-sheet input:focus,
.adm-sheet textarea:focus,
.adm-sheet select:focus {
    border-color: var(--pos-brand-primary);
    outline: 2px solid var(--pos-brand-light);
    outline-offset: 1px;
}

.adm-sheet hr,
.adm-sheet [role='separator'] {
    border-color: var(--pos-border);
    background: var(--pos-border);
}
</style>
