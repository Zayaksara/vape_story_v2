<script setup lang="ts">
import { useForm, router } from '@inertiajs/vue3'
import { ArrowLeft } from 'lucide-vue-next'
import { ref } from 'vue'
import AdminLayout from '@/layouts/admin/AdminLayout.vue'
import { index as adminProductsRoute, store as storeRoute } from '@/routes/admin/products'
import { index as adminDashboardRoute } from '@/routes/admin/dashboard'
import ProductFormFields from '@/pages/admin/product/ProductFormFields.vue'
import CurrencyInput from '@/components/admin/CurrencyInput.vue'

defineOptions({
    layout: (h: any, page: any) => h(AdminLayout, {
        breadcrumbs: [
            { title: 'Dashboard', href: adminDashboardRoute.url() },
            { title: 'Manajemen Produk', href: adminProductsRoute.url() },
            { title: 'Tambah Produk' },
        ],
    }, () => page),
})

const props = defineProps<{
    categories: { id: string; name: string }[]
    brands:     { id: string; name: string }[]
}>()

const categoriesList = ref([...props.categories])

const form = useForm({
    code:                  '',
    name:                  '',
    category_id:           '',
    brand_id:              '',
    base_price:            '',
    flavor:                '',
    nicotine_strength:     '',
    size_ml:               '',
    description:           '',
    is_active:             true,
    image:                 null as File | null,
    batch_lot_number:      '',
    batch_expired_date:    '',
    batch_stock_quantity:  '',
    batch_cost_price:      '',
    batch_cukai_year:      '',
    batch_is_promo:        false,
})

function onCategoryAdded(cat: { id: string; name: string }) {
    if (!categoriesList.value.find(c => c.id === cat.id)) {
        categoriesList.value.push(cat)
    }
}

function submit() {
    form.post(storeRoute.url(), { forceFormData: true })
}
</script>

<template>
    <div class="adm-page px-6 py-5">
        <div class="mx-auto max-w-2xl">

            <button
                class="mb-5 flex items-center gap-1.5 text-sm font-medium transition"
                style="color: var(--pos-text-muted);"
                @click="router.get(adminProductsRoute.url())"
            >
                <ArrowLeft class="h-4 w-4" /> Kembali ke daftar produk
            </button>

            <div class="overflow-hidden rounded-lg border bg-white" style="border-color: var(--pos-border); box-shadow: var(--pos-shadow);">
                <div class="border-b px-6 py-4" style="border-color: var(--pos-border); background: #f8fafc;">
                    <h1 class="text-base font-bold" style="color: var(--pos-text-secondary);">Tambah Produk Baru</h1>
                </div>

                <form class="space-y-5 p-6" @submit.prevent="submit">

                    <ProductFormFields
                        :form="form"
                        :categories="categoriesList"
                        :brands="brands"
                        mode="create"
                        @category-added="onCategoryAdded"
                    />

                    <!-- ── Stok Awal ──────────────────────────────────────── -->
                    <div class="rounded-lg border" style="border-color: var(--pos-border);">
                        <div class="border-b px-4 py-3" style="border-color: var(--pos-border); background: #f8fafc;">
                            <p class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Stok Awal (Opsional)</p>
                            <p class="mt-0.5 text-xs" style="color: var(--pos-text-light);">Isi untuk langsung menambahkan stok awal setelah produk dibuat.</p>
                        </div>
                        <div class="space-y-4 p-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Jumlah Stok</label>
                                    <input v-model="form.batch_stock_quantity" type="number" min="0" placeholder="0"
                                        class="w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
                                        style="border-color: var(--pos-border); color: var(--pos-text-secondary);" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Harga Modal</label>
                                    <CurrencyInput v-model="form.batch_cost_price" placeholder="0" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Tanggal Kadaluarsa <span v-if="form.batch_stock_quantity" class="text-red-500">*</span></label>
                                    <input v-model="form.batch_expired_date" type="date"
                                        class="w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
                                        style="border-color: var(--pos-border); color: var(--pos-text-secondary);" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">No. Lot</label>
                                    <input v-model="form.batch_lot_number" type="text" placeholder="Auto-generate jika kosong"
                                        class="w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
                                        style="border-color: var(--pos-border); color: var(--pos-text-secondary);" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Tahun Cukai</label>
                                    <input v-model="form.batch_cukai_year" type="number" min="2000" max="2100" :placeholder="new Date().getFullYear().toString()"
                                        class="w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
                                        style="border-color: var(--pos-border); color: var(--pos-text-secondary);" />
                                </div>
                                <div class="flex items-end pb-2">
                                    <label class="flex cursor-pointer items-center gap-2">
                                        <input v-model="form.batch_is_promo" type="checkbox" class="h-4 w-4 rounded" />
                                        <span class="text-sm font-medium" style="color: var(--pos-text-secondary);">Cukai lama (Promo)</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-2 border-t pt-4" style="border-color: var(--pos-border);">
                        <button
                            type="button"
                            class="rounded-lg border px-4 py-2 text-sm font-semibold transition"
                            style="border-color: var(--pos-border); color: var(--pos-text-muted);"
                            @click="router.get(adminProductsRoute.url())"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-lg px-4 py-2 text-sm font-semibold transition disabled:opacity-60"
                            style="background: var(--pos-brand-primary); color: #fff;"
                        >
                            {{ form.processing ? 'Menyimpan…' : 'Simpan Produk' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
