<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { useDebounceFn } from '@vueuse/core'
import {
    Search, X, Package, Eye,
    Zap, Wind, Layers, Tag,
    ChevronLeft, ChevronRight,
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'

import { index as posProductsRoute } from '@/routes/pos/products'

// ── Props ─────────────────────────────────────────────────────────────────────

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

// ── Derived stats (from current page data) ────────────────────────────────────

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
    <div
        class="pos-layout flex h-full min-h-0 flex-col"
        style="background: var(--pos-bg-primary); font-family: 'Nunito Sans', sans-serif;"
    >
        <!-- ── Page Header ─────────────────────────────────────────────────── -->
        <div
            class="flex items-center justify-between border-b px-6 py-4"
            style="background: #ffffff; border-color: var(--pos-border);"
        >
            <h1 class="text-xl font-bold" style="color: var(--pos-text-secondary);">
                Produk
            </h1>
        </div>

        <div class="flex-1 min-h-0 overflow-y-auto px-6 py-5" style="color: var(--pos-bg-primary);">

            <!-- ── Tabs ───────────────────────────────────────────────────── -->
            <Tabs default-value="produk" class="w-full">

                <TabsContent value="produk" class="mt-0">

                    <!-- ── Ringkasan ───────────────────────────────────────── -->
                    <div class="mb-5">
                        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">

                            <!-- Tersedia -->
                            <div
                                class="flex items-center gap-3 rounded-lg border p-4"
                                style="background: #fff; border-color: var(--pos-border);"
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                                    style="background: var(--pos-bg-success);"
                                >
                                    <BoxIcon class="h-5 w-5" style="color: var(--pos-success-text);" />
                                </div>
                                <div>
                                    <p
                                        class="text-2xl font-bold leading-none"
                                        style="color: var(--pos-text-secondary);"
                                    >
                                        {{ statTersedia }}
                                        <span class="text-sm font-semibold"> Jenis</span>
                                    </p>
                                    <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Stok tersedia</p>
                                </div>
                            </div>

                            <!-- Tipis -->
                            <div
                                class="flex items-center gap-3 rounded-lg border p-4"
                                style="background: #fff; border-color: var(--pos-border);"
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                                    style="background: var(--pos-bg-warning);"
                                >
                                    <AlertTriangle class="h-5 w-5" style="color: var(--pos-warning-text);" />
                                </div>
                                <div>
                                    <p
                                        class="text-2xl font-bold leading-none"
                                        style="color: var(--pos-text-secondary);"
                                    >
                                        {{ statTipis }}
                                        <span class="text-sm font-semibold"> Jenis</span>
                                    </p>
                                    <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Stok segera habis</p>
                                </div>
                            </div>

                            <!-- Habis -->
                            <div
                                class="flex items-center gap-3 rounded-lg border p-4"
                                style="background: #fff; border-color: var(--pos-border);"
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                                    style="background: var(--pos-bg-danger);"
                                >
                                    <XCircle class="h-5 w-5" style="color: var(--pos-danger-text);" />
                                </div>
                                <div>
                                    <p
                                        class="text-2xl font-bold leading-none"
                                        style="color: var(--pos-text-secondary);"
                                    >
                                        {{ statHabis }}
                                        <span class="text-sm font-semibold"> Jenis</span>
                                    </p>
                                    <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Stok habis</p>
                                </div>
                            </div>

                            <!-- Kategori -->
                            <div
                                class="flex items-center gap-3 rounded-lg border p-4"
                                style="background: #fff; border-color: var(--pos-border);"
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                                    style="background: var(--pos-brand-light);"
                                >
                                    <Warehouse class="h-5 w-5" style="color: var(--pos-brand-primary);" />
                                </div>
                                <div>
                                    <p
                                        class="text-2xl font-bold leading-none"
                                        style="color: var(--pos-text-secondary);"
                                    >
                                        {{ categories.length }}
                                        <span class="text-sm font-semibold"> Kategori</span>
                                    </p>
                                    <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Total kategori</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── Table Card ──────────────────────────────────────── -->
                    <div
                        class="overflow-hidden rounded-lg border"
                        style="background: #fff; border-color: var(--pos-border); box-shadow: var(--pos-shadow);"
                    >

                        <!-- Table toolbar -->
                        <div
                            class="flex flex-wrap items-center gap-2 border-b px-4 py-3"
                            style="border-color: var(--pos-border); background: #f8fafc;"
                        >
                            <!-- Left: Search -->
                            <div class="flex items-center overflow-hidden rounded-md border" style="border-color: var(--pos-border);">
                                <div class="relative">
                                    <Search
                                        class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2"
                                        style="color: var(--pos-text-muted);"
                                    />
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Cari produk, kode, flavor…"
                                        class="h-8 border-0 pl-8 pr-8 text-xs outline-none transition"
                                        style="color: var(--pos-text-secondary); width: 240px; background: #fff;"
                                    />
                                    <button
                                        v-if="search"
                                        class="absolute right-2 top-1/2 -translate-y-1/2"
                                        style="color: var(--pos-text-muted);"
                                        @click="search = ''"
                                    >
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

                            <!-- Category -->
                            <Select v-model="categorySlug">
                                <SelectTrigger
                                    class="h-8 w-40 border text-xs"
                                    style="border-color: var(--pos-border);"
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
                                    class="h-8 w-36 border text-xs"
                                    style="border-color: var(--pos-border);"
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
                                class="cursor-pointer rounded-full px-2 py-0.5 text-xs font-semibold"
                                style="background: var(--pos-brand-light); color: var(--pos-brand-primary);"
                                @click="resetFilters"
                            >
                                Filter Aktif ✕
                            </span>
                        </div>

                        <!-- Table -->
                        <div class="w-full max-w-full overflow-x-auto pb-2">
                        <Table class="min-w-[1480px]">
                            <TableHeader>
                                <TableRow style="background: #f1f5f9;">
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
                                        <TableCell><Skeleton class="h-4 w-12" /></TableCell>
                                        <TableCell><Skeleton class="h-4 w-12" /></TableCell>
                                        <TableCell><Skeleton class="h-4 w-12" /></TableCell>
                                        <TableCell><Skeleton class="h-4 w-24" /></TableCell>
                                        <TableCell><Skeleton class="h-5 w-16 rounded-full" /></TableCell>
                                        <TableCell />
                                    </TableRow>
                                </template>

                                <!-- Data rows -->
                                <template v-else-if="products.data.length" class="overflow-y-auto">
                                    <TableRow
                                        v-for="product in products.data"
                                        :key="product.id"
                                        class="group cursor-pointer transition-colors hover:bg-[var(--pos-bg-accent)]"
                                        style="border-color: var(--pos-border);"
                                        @click="openDetail(product)"
                                    >

                                        <TableCell>
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-md text-xs font-bold"
                                                style="background: var(--pos-bg-accent); color: var(--pos-brand-dark);"
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
                                            <span class="text-sm font-semibold" style="color: var(--pos-text-secondary);">
                                                {{ product.name }}
                                            </span>
                                        </TableCell>

                                        <TableCell>
                                            <span class="text-sm" style="color: var(--pos-text-secondary);">
                                                {{ product.brand?.name ?? '—' }}
                                            </span>
                                        </TableCell>

                                        <TableCell>
                                            <span class="text-sm" style="color: var(--pos-text-secondary);">
                                                {{ product.category?.name ?? '—' }}
                                            </span>
                                        </TableCell>

                                        <TableCell>
                                            <span class="text-sm" style="color: var(--pos-text-secondary);">
                                                {{ (product as any).flavor ?? '—' }}
                                            </span>
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
                                            <span class="text-sm font-bold tabular-nums" :style="{ color: stockInfo(product.stock).color }">
                                                {{ product.stock }}
                                            </span>
                                        </TableCell>

                                        <!-- Status -->
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
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <button
                                                        class="rounded p-1 opacity-0 transition-opacity group-hover:opacity-100"
                                                        style="color: var(--pos-brand-primary);"
                                                        @click="openDetail(product)"
                                                    >
                                                        <Eye class="h-4 w-4" />
                                                    </button>
                                                </TooltipTrigger>
                                                <TooltipContent>Lihat detail</TooltipContent>
                                            </Tooltip>
                                        </TableCell>
                                    </TableRow>
                                </template>

                                <!-- Empty state -->
                                <TableRow v-else>
                                    <TableCell colspan="10" class="py-16 text-center">
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

                        <!-- ── Pagination ──────────────────────────────────── -->
                        <div
                            class="flex items-center justify-between border-t px-4 py-3"
                            style="border-color: var(--pos-border); background: #f8fafc;"
                        >
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

                </TabsContent>

                <!-- Gudang tab placeholder -->
                <TabsContent value="gudang" class="mt-0">
                    <div
                        class="flex flex-col items-center justify-center rounded-lg border py-20"
                        style="background: #fff; border-color: var(--pos-border);"
                    >
                        <Warehouse class="mb-3 h-12 w-12 opacity-20" style="color: var(--pos-text-muted);" />
                        <p class="text-sm font-medium" style="color: var(--pos-text-muted);">Fitur Gudang belum tersedia</p>
                    </div>
                </TabsContent>
            </Tabs>
        </div>

        <!-- ── Detail Sheet ────────────────────────────────────────────────── -->
        <Sheet v-model:open="sheetOpen">
            <SheetContent class="w-full overflow-y-auto p-5 sm:max-w-md sm:p-6">
                <SheetHeader>
                    <SheetTitle>Detail Produk</SheetTitle>
                    <SheetDescription>Informasi lengkap produk</SheetDescription>
                </SheetHeader>

                <div v-if="selectedProduct" class="mt-5 flex flex-col gap-4">
                    <!-- Image -->
                    <div
                        class="aspect-square w-full overflow-hidden rounded-xl"
                        style="background: var(--pos-bg-accent);"
                    >
                        <img
                            v-if="selectedProduct.image_url"
                            :src="selectedProduct.image_url"
                            :alt="selectedProduct.name"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center">
                            <span class="text-5xl font-bold" style="color: var(--pos-brand-primary); opacity: 0.3;">
                                {{ productInitials(selectedProduct.name) }}
                            </span>
                        </div>
                    </div>

                    <!-- Name -->
                    <div class="space-y-1">
                        <h2 class="text-lg font-bold" style="color: var(--pos-text-secondary);">{{ selectedProduct.name }}</h2>
                        <p class="font-mono text-xs mt-1" style="color: var(--pos-text-muted);">{{ selectedProduct.sku }}</p>
                    </div>

                    <Separator />

                    <!-- Status badge -->
                    <div class="flex flex-wrap gap-2">
                        <span
                            class="rounded-full px-3 py-1 text-xs font-semibold"
                            :style="selectedProduct.stock === 0
                                ? 'background: var(--pos-danger-bg); color: var(--pos-danger-text);'
                                : selectedProduct.stock <= 20
                                    ? 'background: var(--pos-warning-bg); color: var(--pos-warning-text);'
                                    : 'background: var(--pos-success-bg); color: var(--pos-success-text);'"
                        >
                            {{ stockInfo(selectedProduct.stock).label }}
                        </span>
                        <span
                            v-if="selectedProduct.volume"
                            class="rounded-full border px-3 py-1 text-xs font-semibold"
                            style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
                        >
                            {{ selectedProduct.volume }}
                        </span>
                    </div>

                    <!-- Detail rows -->
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
                        ]" :key="i" class="flex items-center justify-between px-4 py-2.5"
                            style="border-color: var(--pos-border);"
                        >
                            <span class="text-xs" style="color: var(--pos-text-muted);">{{ row.label }}</span>
                            <span class="text-sm font-semibold" style="color: var(--pos-text-secondary);">{{ row.value }}</span>
                        </div>
                    </div>
                </div>
            </SheetContent>
        </Sheet>

    </div>
    </TooltipProvider>
</template>