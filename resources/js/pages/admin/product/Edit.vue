<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3'
import { ArrowLeft, Plus, Pencil, Trash2, Check } from 'lucide-vue-next'
import { ref, reactive } from 'vue'
import AdminLayout from '@/layouts/admin/AdminLayout.vue'
import { index as adminProductsRoute, update as updateRoute } from '@/routes/admin/products'
import type { Product } from '@/types/pos'
import ProductFormFields from '@/pages/admin/product/ProductFormFields.vue'
import CurrencyInput from '@/components/admin/CurrencyInput.vue'
import ConfirmModal from '@/components/admin/ConfirmModal.vue'

defineOptions({
    layout: AdminLayout,
})

interface Batch {
    id: string
    lot_number: string
    stock_quantity: number
    cost_price: number
    promo_price?: number | null
    cukai_year?: number | null
    is_promo: boolean
}

const props = defineProps<{
    product:    Product & { base_price: number; flavor?: string; nicotine_strength?: number; size_ml?: number; is_active: boolean; batches?: Batch[] }
    categories: { id: string; name: string }[]
    brands:     { id: string; name: string }[]
}>()

const categoriesList = ref([...props.categories])
const brandsList = ref([...props.brands])

// ── Product form ──────────────────────────────────────────────────────────────

const form = useForm({
    code:              (props.product as any).code ?? '',
    name:              props.product.name,
    category_id:       props.product.category_id,
    brand_id:          props.product.brand_id ?? '',
    base_price:        String(props.product.base_price ?? ''),
    flavor:            props.product.flavor ?? '',
    nicotine_strength: props.product.nicotine_strength ? String(props.product.nicotine_strength) : '',
    size_ml:           props.product.size_ml ? String(props.product.size_ml) : '',
    is_active:         props.product.is_active,
    min_stock:         (props.product as any).min_stock ?? 0,
    image:             null as File | null,
    _method:           'PUT',
})

function onCategoryAdded(cat: { id: string; name: string }) {
    if (!categoriesList.value.find(c => c.id === cat.id)) {
        categoriesList.value.push(cat)
    }
}

function onBrandAdded(brand: { id: string; name: string }) {
    if (!brandsList.value.find(b => b.id === brand.id)) {
        brandsList.value.push(brand)
    }
}

function onCategoryUpdated(cat: { id: string; name: string }) {
    const i = categoriesList.value.findIndex(c => c.id === cat.id)
    if (i >= 0) categoriesList.value[i] = { ...categoriesList.value[i], ...cat }
}
function onCategoryDeleted(id: string) {
    categoriesList.value = categoriesList.value.filter(c => c.id !== id)
}
function onBrandUpdated(brand: { id: string; name: string }) {
    const i = brandsList.value.findIndex(b => b.id === brand.id)
    if (i >= 0) brandsList.value[i] = { ...brandsList.value[i], ...brand }
}
function onBrandDeleted(id: string) {
    brandsList.value = brandsList.value.filter(b => b.id !== id)
}

function submit() {
    form.post(updateRoute.url(props.product.id), { forceFormData: true })
}

// ── Batch management ──────────────────────────────────────────────────────────

const showAddBatch  = ref(false)
const editingBatch  = ref<string | null>(null)
const batchErrors   = ref<Record<string, string>>({})

const newBatch = reactive({
    lot_number:     '',
    stock_quantity: '',
    cost_price:     '',
    promo_price:    '',
    cukai_year:     '',
    is_promo:       false,
})

const editBatchData = reactive<Record<string, any>>({})

function startEditBatch(batch: Batch) {
    editingBatch.value = batch.id
    editBatchData[batch.id] = {
        lot_number:     batch.lot_number,
        stock_quantity: String(batch.stock_quantity),
        cost_price:     String(batch.cost_price ?? ''),
        promo_price:    batch.promo_price ? String(batch.promo_price) : '',
        cukai_year:     batch.cukai_year ? String(batch.cukai_year) : '',
        is_promo:       batch.is_promo,
    }
}

function cancelEditBatch() {
    editingBatch.value = null
    batchErrors.value = {}
}

function saveBatch(batch: Batch) {
    const data = editBatchData[batch.id]
    router.put(`/admin/products/${props.product.id}/batches/${batch.id}`, data, {
        preserveScroll: true,
        onSuccess: () => { editingBatch.value = null; batchErrors.value = {} },
        onError: (errors) => { batchErrors.value = errors as Record<string, string> },
    })
}

function addBatch() {
    router.post(`/admin/products/${props.product.id}/batches`, { ...newBatch }, {
        preserveScroll: true,
        onSuccess: () => {
            showAddBatch.value      = false
            batchErrors.value       = {}
            newBatch.lot_number     = ''
            newBatch.stock_quantity = ''
            newBatch.cost_price     = ''
            newBatch.promo_price    = ''
            newBatch.cukai_year     = ''
            newBatch.is_promo       = false
        },
        onError: (errors) => { batchErrors.value = errors as Record<string, string> },
    })
}

const confirmDeleteBatchOpen = ref(false)
const pendingDeleteBatch = ref<Batch | null>(null)
const deletingBatch = ref(false)

function deleteBatch(batch: Batch) {
    pendingDeleteBatch.value = batch
    confirmDeleteBatchOpen.value = true
}

function confirmDeleteBatch() {
    const batch = pendingDeleteBatch.value
    if (!batch) return
    deletingBatch.value = true
    router.delete(`/admin/products/${props.product.id}/batches/${batch.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingBatch.value = false
            confirmDeleteBatchOpen.value = false
            pendingDeleteBatch.value = null
        },
    })
}

function formatPrice(n: number) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n)
}
</script>

<template>
    <div class="adm-page px-6 py-5">
        <div class="mx-auto max-w-2xl">

            <button class="mb-5 flex items-center gap-1.5 text-sm font-medium transition" style="color: var(--pos-text-muted);" @click="router.get(adminProductsRoute.url())">
                <ArrowLeft class="h-4 w-4" /> Kembali ke daftar produk
            </button>

            <!-- ── Info Produk ──────────────────────────────────────────────── -->
            <div class="mb-5 overflow-hidden rounded-lg border bg-white" style="border-color: var(--pos-border); box-shadow: var(--pos-shadow);">
                <div class="border-b px-6 py-4" style="border-color: var(--pos-border); background: #f8fafc;">
                    <h1 class="text-base font-bold" style="color: var(--pos-text-secondary);">Edit Produk</h1>
                    <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">{{ product.name }}</p>
                </div>

                <form class="space-y-5 p-6" @submit.prevent="submit">

                    <ProductFormFields
                        :form="form"
                        :categories="categoriesList"
                        :brands="brandsList"
                        :initial-image-url="product.image_url ?? null"
                        mode="edit"
                        @category-added="onCategoryAdded"
                        @category-updated="onCategoryUpdated"
                        @category-deleted="onCategoryDeleted"
                        @brand-added="onBrandAdded"
                        @brand-updated="onBrandUpdated"
                        @brand-deleted="onBrandDeleted"
                    />

                    <div class="flex justify-end gap-2 border-t pt-4" style="border-color: var(--pos-border);">
                        <button type="button" class="rounded-lg border px-4 py-2 text-sm font-semibold transition" style="border-color: var(--pos-border); color: var(--pos-text-muted);" @click="router.get(adminProductsRoute.url())">
                            Batal
                        </button>
                        <button type="submit" :disabled="form.processing" class="rounded-lg px-4 py-2 text-sm font-semibold transition disabled:opacity-60" style="background: var(--pos-brand-primary); color: #fff;">
                            {{ form.processing ? 'Menyimpan…' : 'Simpan Perubahan' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── Manajemen Stok / Batch ───────────────────────────────────── -->
            <div class="overflow-hidden rounded-lg border bg-white" style="border-color: var(--pos-border); box-shadow: var(--pos-shadow);">
                <div class="flex items-center justify-between border-b px-6 py-4" style="border-color: var(--pos-border); background: #f8fafc;">
                    <div>
                        <h2 class="text-sm font-bold" style="color: var(--pos-text-secondary);">Manajemen Stok</h2>
                        <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Total: <strong>{{ (product.batches ?? []).reduce((s, b) => s + b.stock_quantity, 0) }}</strong> unit dari {{ (product.batches ?? []).length }} batch</p>
                    </div>
                    <button
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                        style="background: var(--pos-brand-primary); color: #fff;"
                        @click="showAddBatch = !showAddBatch"
                    >
                        <Plus class="h-3.5 w-3.5" /> Tambah Batch
                    </button>
                </div>

                <!-- Form tambah batch -->
                <div v-if="showAddBatch" class="space-y-3 border-b p-4" style="border-color: var(--pos-border); background: #f0f9ff;">
                    <p class="text-xs font-semibold uppercase tracking-wide" style="color: var(--pos-brand-primary);">Batch Baru</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold" style="color: var(--pos-text-muted);">Jumlah Stok <span class="text-red-500">*</span></label>
                            <input v-model="newBatch.stock_quantity" type="number" min="0" placeholder="0" class="w-full rounded-md border px-3 py-1.5 text-sm outline-none" style="border-color: var(--pos-border);" />
                            <p v-if="batchErrors.stock_quantity" class="mt-1 text-xs text-red-500">{{ batchErrors.stock_quantity }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold" style="color: var(--pos-text-muted);">Harga Modal <span class="text-red-500">*</span></label>
                            <CurrencyInput v-model="newBatch.cost_price" placeholder="0" />
                            <p v-if="batchErrors.cost_price" class="mt-1 text-xs text-red-500">{{ batchErrors.cost_price }}</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold" style="color: var(--pos-text-muted);">No. Lot</label>
                            <input v-model="newBatch.lot_number" type="text" placeholder="Auto-generate" class="w-full rounded-md border px-3 py-1.5 text-sm outline-none" style="border-color: var(--pos-border);" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold" style="color: var(--pos-text-muted);">Tahun Cukai</label>
                            <input v-model="newBatch.cukai_year" type="number" :placeholder="new Date().getFullYear().toString()" class="w-full rounded-md border px-3 py-1.5 text-sm outline-none" style="border-color: var(--pos-border);" />
                        </div>
                        <div class="col-span-2 flex items-center gap-3">
                            <label class="flex cursor-pointer items-center gap-2">
                                <input v-model="newBatch.is_promo" type="checkbox" class="h-4 w-4 rounded" />
                                <span class="text-sm" style="color: var(--pos-text-secondary);">Cukai lama (Promo)</span>
                            </label>
                        </div>
                        <div v-if="newBatch.is_promo" class="col-span-2">
                            <label class="mb-1 block text-xs font-semibold" style="color: var(--pos-text-muted);">Harga Jual Promo <span class="text-red-500">*</span></label>
                            <CurrencyInput v-model="newBatch.promo_price" placeholder="0" />
                            <p class="mt-1 text-[11px]" style="color: var(--pos-text-muted);">Harga ini dipakai di POS selama stok promo masih ada.</p>
                            <p v-if="batchErrors.promo_price" class="mt-1 text-xs text-red-500">{{ batchErrors.promo_price }}</p>
                        </div>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-semibold" style="background: var(--pos-brand-primary); color: #fff;" @click="addBatch">Simpan Batch</button>
                        <button type="button" class="rounded-lg border px-3 py-1.5 text-xs font-semibold" style="border-color: var(--pos-border); color: var(--pos-text-muted);" @click="showAddBatch = false">Batal</button>
                    </div>
                </div>

                <!-- Daftar batch -->
                <div v-if="(product.batches ?? []).length === 0" class="py-10 text-center text-sm" style="color: var(--pos-text-muted);">
                    Belum ada stok. Klik "Tambah Batch" untuk menambah.
                </div>

                <div v-else class="divide-y" style="border-color: var(--pos-border);">
                    <div v-for="batch in product.batches" :key="batch.id" class="px-5 py-3">

                        <!-- View mode -->
                        <div v-if="editingBatch !== batch.id" class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 flex-1 items-center gap-4">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-xs font-bold" style="background: var(--pos-brand-light); color: var(--pos-brand-primary);">
                                    {{ batch.stock_quantity }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold" style="color: var(--pos-text-secondary);">
                                        {{ batch.lot_number }}
                                        <span v-if="batch.is_promo" class="ml-1 rounded-full px-1.5 py-0.5 text-[10px] font-bold" style="background: var(--pos-warning-bg); color: var(--pos-warning-text);">CUKAI LAMA</span>
                                    </p>
                                    <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">
                                        Modal: {{ formatPrice(batch.cost_price) }}
                                        <span v-if="batch.is_promo && batch.promo_price"> · Harga Promo: <strong>{{ formatPrice(batch.promo_price) }}</strong></span>
                                        <span v-if="batch.cukai_year"> · Cukai {{ batch.cukai_year }}</span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex shrink-0 items-center gap-1">
                                <button class="rounded p-1.5 transition hover:bg-gray-100" style="color: var(--pos-text-muted);" @click="startEditBatch(batch)">
                                    <Pencil class="h-3.5 w-3.5" />
                                </button>
                                <button class="rounded p-1.5 transition hover:bg-red-50" style="color: var(--pos-danger-text);" @click="deleteBatch(batch)">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>

                        <!-- Edit mode -->
                        <div v-else class="space-y-3">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold" style="color: var(--pos-text-muted);">Jumlah Stok</label>
                                    <input v-model="editBatchData[batch.id].stock_quantity" type="number" min="0" class="w-full rounded-md border px-3 py-1.5 text-sm outline-none" style="border-color: var(--pos-border);" />
                                    <p v-if="batchErrors.stock_quantity" class="mt-1 text-xs text-red-500">{{ batchErrors.stock_quantity }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold" style="color: var(--pos-text-muted);">Harga Modal</label>
                                    <CurrencyInput v-model="editBatchData[batch.id].cost_price" placeholder="0" />
                                    <p v-if="batchErrors.cost_price" class="mt-1 text-xs text-red-500">{{ batchErrors.cost_price }}</p>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold" style="color: var(--pos-text-muted);">No. Lot</label>
                                    <input v-model="editBatchData[batch.id].lot_number" type="text" class="w-full rounded-md border px-3 py-1.5 text-sm outline-none" style="border-color: var(--pos-border);" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold" style="color: var(--pos-text-muted);">Tahun Cukai</label>
                                    <input v-model="editBatchData[batch.id].cukai_year" type="number" class="w-full rounded-md border px-3 py-1.5 text-sm outline-none" style="border-color: var(--pos-border);" />
                                </div>
                                <div class="col-span-2 flex items-center gap-3">
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input v-model="editBatchData[batch.id].is_promo" type="checkbox" class="h-4 w-4 rounded" />
                                        <span class="text-sm" style="color: var(--pos-text-secondary);">Cukai lama (Promo)</span>
                                    </label>
                                </div>
                                <div v-if="editBatchData[batch.id].is_promo" class="col-span-2">
                                    <label class="mb-1 block text-xs font-semibold" style="color: var(--pos-text-muted);">Harga Jual Promo <span class="text-red-500">*</span></label>
                                    <CurrencyInput v-model="editBatchData[batch.id].promo_price" placeholder="0" />
                                    <p v-if="batchErrors.promo_price" class="mt-1 text-xs text-red-500">{{ batchErrors.promo_price }}</p>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" class="flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-semibold" style="background: var(--pos-brand-primary); color: #fff;" @click="saveBatch(batch)">
                                    <Check class="h-3.5 w-3.5" /> Simpan
                                </button>
                                <button type="button" class="rounded-lg border px-3 py-1.5 text-xs font-semibold" style="border-color: var(--pos-border); color: var(--pos-text-muted);" @click="cancelEditBatch">
                                    Batal
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>

        <ConfirmModal
            :open="confirmDeleteBatchOpen"
            title="Hapus Batch?"
            message="Stok pada batch ini akan ikut hilang. Tindakan tidak bisa dibatalkan."
            :detail="pendingDeleteBatch ? `${pendingDeleteBatch.lot_number} · stok ${pendingDeleteBatch.stock_quantity}` : null"
            confirm-label="Ya, Hapus Batch"
            :processing="deletingBatch"
            @confirm="confirmDeleteBatch"
            @cancel="confirmDeleteBatchOpen = false"
        />
    </div>
</template>
