<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ImagePlus } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePrinter } from '@/composables/usePrinter';
import { buildReceiptBytes } from '@/lib/escposReceipt';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import SettingsRoleLayout from '@/layouts/SettingsRoleLayout.vue';
import ReceiptPreview from '@/components/pos/ReceiptPreview.vue';
import type { Transaction } from '@/types/pos';

type ReceiptOptions = Record<string, boolean>;

const DEFAULT_RECEIPT_OPTIONS: ReceiptOptions = {
    show_logo: false,
    show_store_name: true,
    show_address: true,
    show_phone: true,
    show_datetime: true,
    show_invoice_number: true,
    show_cashier: true,
    show_item_unit_line: true,
    show_subtotal: true,
    show_discount_row: true,
    show_payment_method: true,
    show_cash_received: true,
    show_change: true,
    show_footer_text: true,
};

const RECEIPT_OPTION_GROUPS = [
    {
        title: 'Header Toko',
        items: [
            { key: 'show_logo', label: 'Logo toko' },
            { key: 'show_store_name', label: 'Nama toko' },
            { key: 'show_address', label: 'Alamat' },
            { key: 'show_phone', label: 'Nomor telepon' },
        ],
    },
    {
        title: 'Info Transaksi',
        items: [
            { key: 'show_datetime', label: 'Tanggal & waktu' },
            { key: 'show_invoice_number', label: 'Nomor invoice' },
            { key: 'show_cashier', label: 'Nama kasir' },
        ],
    },
    {
        title: 'Detail Item & Total',
        items: [
            { key: 'show_item_unit_line', label: 'Harga × qty per item' },
            { key: 'show_subtotal', label: 'Baris subtotal' },
            { key: 'show_discount_row', label: 'Baris diskon' },
            { key: 'show_payment_method', label: 'Metode pembayaran' },
            { key: 'show_cash_received', label: 'Jumlah dibayar (tunai)' },
            { key: 'show_change', label: 'Kembalian (tunai)' },
            { key: 'show_footer_text', label: 'Teks footer custom' },
        ],
    },
] as const;

type StoreSetting = {
    id: number;
    name: string;
    address: string | null;
    phone: string | null;
    tagline: string | null;
    logo_path: string | null;
    receipt_header: string | null;
    receipt_footer: string | null;
    show_logo_on_receipt: boolean;
    receipt_options_resolved: ReceiptOptions;
};

const props = defineProps<{
    store: StoreSetting;
}>();

defineOptions({
    layout: SettingsRoleLayout,
});

const form = useForm({
    _method: 'patch',
    name: props.store.name ?? '',
    address: props.store.address ?? '',
    phone: props.store.phone ?? '',
    tagline: props.store.tagline ?? '',
    receipt_footer: props.store.receipt_footer ?? '',
    receipt_options: { ...DEFAULT_RECEIPT_OPTIONS, ...(props.store.receipt_options_resolved ?? {}) } as ReceiptOptions,
    logo: null as File | null,
});

function toggleAllReceiptOptions(value: boolean) {
    for (const key of Object.keys(form.receipt_options)) {
        form.receipt_options[key] = value;
    }
}

const printMode = ref<'58' | '80'>('58');
const previewRef = ref<HTMLElement | null>(null);

// ── Bluetooth Printer (composable) ──────────────────────────────
const printer = usePrinter();
const btError = ref<string | null>(null);

async function handleConnect() {
    btError.value = null;
    await printer.pair();
    if (printer.status.value === 'error') btError.value = printer.lastMessage.value;
}

async function handleReconnect() {
    btError.value = null;
    const ok = await printer.tryAutoConnect();
    if (!ok) btError.value = printer.lastMessage.value;
}

async function handleBtPrint() {
    btError.value = null;
    try {
        const bytes = await buildReceiptBytes({
            store: {
                name: form.name,
                address: form.address,
                phone: form.phone,
            },
            transaction: sampleTransaction.value,
            paperWidth: printMode.value === '80' ? 80 : 58,
            options: form.receipt_options,
            footerText: form.receipt_footer,
            logoUrl: previewLogoUrl.value,
        });
        await printer.printBytes(bytes);
    } catch (err: any) {
        btError.value = err?.message ?? String(err);
    }
}

onMounted(() => {
    // Coba auto-detect printer tersimpan (tanpa dialog) — silent kalau gagal
    if (printer.supported && !printer.ready.value) {
        printer.tryAutoConnect().catch(() => { /* ignore */ });
    }
});

function handleTestPrint() {
    const node = previewRef.value;
    if (!node) return;
    node.dataset.printMode = printMode.value;
    document.body.dataset.thermalPrint = printMode.value;
    document.body.dataset.receiptPreviewPrint = 'true';
    window.print();
    window.setTimeout(() => {
        if (node) delete node.dataset.printMode;
        delete document.body.dataset.thermalPrint;
        delete document.body.dataset.receiptPreviewPrint;
    }, 300);
}

function submit() {
    form.post('/settings/store', {
        forceFormData: true,
        preserveScroll: true,
    });
}

// ── Live preview state ──────────────────────────────────────────
const localLogoUrl = ref<string | null>(null);

function onLogoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.logo = file;

    if (localLogoUrl.value) {
        URL.revokeObjectURL(localLogoUrl.value);
        localLogoUrl.value = null;
    }
    if (file) {
        localLogoUrl.value = URL.createObjectURL(file);
    }
}

onBeforeUnmount(() => {
    if (localLogoUrl.value) URL.revokeObjectURL(localLogoUrl.value);
});

const previewLogoUrl = computed<string | null>(() => {
    if (localLogoUrl.value) return localLogoUrl.value;
    if (props.store.logo_path) return `/storage/${props.store.logo_path}`;
    return null;
});

// Dummy transaksi untuk simulasi tampilan struk
const sampleTransaction = computed<Transaction>(() => ({
    id: 'PREVIEW',
    invoice_number: 'INV-PREVIEW-001',
    cashier_id: 1,
    cashier_name: 'Kasir Demo',
    items: [
        {
            product: { id: '1', name: 'Liquid Mango Ice 30ml', sku: 'LMI-30', price: 95000, stock: 10, category_id: '1' },
            quantity: 1,
            subtotal: 95000,
        },
        {
            product: { id: '2', name: 'Coil RBA 0.3 ohm', sku: 'CRBA-03', price: 45000, stock: 20, category_id: '2' },
            quantity: 2,
            subtotal: 90000,
        },
    ],
    discount: null,
    subtotal: 185000,
    discount_amount: 10000,
    tax_amount: 0,
    total: 175000,
    payment_method: 'cash',
    cash_received: 200000,
    change: 25000,
    created_at: new Date().toISOString(),
    status: 'success',
})) as unknown as import('vue').ComputedRef<Transaction>;
</script>

<template>
    <Head title="Pengaturan Toko" />

    <h1 class="sr-only">Pengaturan Toko</h1>

    <section class="space-y-6">
        <Heading
            title="Identitas Toko"
            description="Informasi ini ditampilkan di header struk dan halaman login."
        />

        <form class="space-y-5" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="store-name">Nama toko</Label>
                <Input id="store-name" v-model="form.name" placeholder="cth. Story Vape" required />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="store-address">Alamat</Label>
                <Input id="store-address" v-model="form.address" placeholder="Jl. ..." />
                <InputError :message="form.errors.address" />
            </div>

            <div class="grid gap-2">
                <Label for="store-phone">Nomor telepon</Label>
                <Input id="store-phone" v-model="form.phone" placeholder="08xx-xxxx-xxxx" />
                <InputError :message="form.errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="store-tagline">Tagline</Label>
                <Input
                    id="store-tagline"
                    v-model="form.tagline"
                    maxlength="100"
                    placeholder="cth. Vape Premium Sejak 2020"
                />
                <p class="text-xs text-muted-foreground">Muncul di halaman login sebagai sambutan.</p>
                <InputError :message="form.errors.tagline" />
            </div>

            <div class="grid gap-2">
                <Label for="store-logo">Logo</Label>
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted">
                        <img
                            v-if="store.logo_path"
                            :src="`/storage/${store.logo_path}`"
                            alt="Logo toko"
                            class="h-full w-full object-contain"
                        />
                        <ImagePlus v-else class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <Input id="store-logo" type="file" accept="image/*" class="cursor-pointer" @change="onLogoChange" />
                </div>
                <p class="text-xs text-muted-foreground">PNG/JPG, maks 2 MB.</p>
                <InputError :message="form.errors.logo" />
            </div>

            <Separator />

            <Heading
                variant="small"
                title="Kustom Struk"
                description="Teks tambahan opsional di bawah struk pembayaran."
            />

            <div class="grid gap-2">
                <Label for="receipt-footer">Footer struk</Label>
                <textarea
                    id="receipt-footer"
                    v-model="form.receipt_footer"
                    rows="5"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    placeholder="Terima kasih!&#10;Barang tidak dapat ditukar&#10;kecuali rusak/garansi.&#10;&#10;** Produk 18+ **&#10;Mengandung nikotin"
                />
                <p class="text-xs text-muted-foreground">
                    Tampil di bagian paling bawah struk (rata tengah). Pisahkan baris dengan Enter.
                </p>
                <InputError :message="form.errors.receipt_footer" />
            </div>

            <Separator />

            <!-- ── Konten Struk ─────────────────────────────────────── -->
            <div class="space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Konten Struk"
                        description="Centang bagian yang ingin ditampilkan di struk pembayaran."
                    />
                    <div class="flex shrink-0 items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-8 text-xs"
                            @click="toggleAllReceiptOptions(true)"
                        >
                            Centang semua
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            class="h-8 text-xs"
                            @click="toggleAllReceiptOptions(false)"
                        >
                            Hapus semua
                        </Button>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-3">
                    <div
                        v-for="group in RECEIPT_OPTION_GROUPS"
                        :key="group.title"
                        class="space-y-2 rounded-lg border bg-card p-4"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                            {{ group.title }}
                        </p>
                        <div class="space-y-2 pt-1">
                            <label
                                v-for="item in group.items"
                                :key="item.key"
                                class="flex items-center gap-2 text-sm text-foreground"
                            >
                                <input
                                    v-model="form.receipt_options[item.key]"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-input text-primary focus:ring-ring"
                                />
                                <span class="leading-tight">{{ item.label }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-2">
                <Button :disabled="form.processing">Simpan perubahan</Button>
            </div>
        </form>

        <Separator />

        <!-- ── Live Preview Struk ─────────────────────────────────────── -->
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                variant="small"
                title="Pratinjau Struk"
                description="Tampilan struk diperbarui otomatis saat kamu mengetik. Belum tersimpan sampai klik Simpan."
            />
            <div class="flex shrink-0 items-center gap-2">
                <div
                    class="flex h-8 overflow-hidden rounded-md border"
                    role="group"
                    aria-label="Ukuran kertas"
                >
                    <button
                        type="button"
                        class="px-3 text-xs font-semibold transition"
                        :class="printMode === '58'
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-background text-muted-foreground hover:bg-muted'"
                        @click="printMode = '58'"
                    >
                        58 mm
                    </button>
                    <button
                        type="button"
                        class="border-l px-3 text-xs font-semibold transition"
                        :class="printMode === '80'
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-background text-muted-foreground hover:bg-muted'"
                        @click="printMode = '80'"
                    >
                        80 mm
                    </button>
                </div>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 text-xs"
                    @click="handleTestPrint"
                >
                    Cetak Pratinjau
                </Button>
            </div>
        </div>

        <!-- ── Printer Bluetooth ───────────────────────────────────── -->
        <div class="rounded-lg border bg-card p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="space-y-1">
                    <p class="text-sm font-semibold">Printer Bluetooth (ESC/POS)</p>
                    <p class="text-xs text-muted-foreground">
                        Hubungkan ke printer thermal Bluetooth (cth. Codeshop CM-T58BL).
                        Cetak akan menggunakan data pratinjau di atas — bukan via dialog cetak browser.
                    </p>
                    <p v-if="printer.device.value" class="text-xs">
                        Perangkat: <span class="font-mono">{{ printer.deviceName.value ?? '(tanpa nama)' }}</span>
                        <span
                            class="ml-2 font-semibold"
                            :class="{
                                'text-green-600': printer.ready.value,
                                'text-yellow-600': printer.status.value === 'connecting',
                                'text-red-600': printer.status.value === 'error',
                                'text-gray-500': printer.status.value === 'idle' && !printer.ready.value,
                            }"
                        >
                            {{ printer.ready.value ? 'Terhubung' : (printer.status.value === 'connecting' ? 'Menghubungkan…' : 'Belum siap') }}
                        </span>
                    </p>
                    <p v-else-if="printer.lastMessage.value" class="text-xs text-muted-foreground">
                        {{ printer.lastMessage.value }}
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-8 text-xs"
                        :disabled="printer.status.value === 'connecting' || printer.ready.value"
                        @click="handleConnect"
                    >
                        {{ printer.status.value === 'connecting' ? 'Mencari…' : (printer.ready.value ? 'Terhubung' : 'Pair Printer') }}
                    </Button>
                    <Button
                        v-if="!printer.ready.value && printer.device.value"
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-8 text-xs"
                        @click="handleReconnect"
                    >
                        Reconnect
                    </Button>
                    <Button
                        v-if="printer.device.value"
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-8 text-xs"
                        @click="printer.disconnect"
                    >
                        Disconnect
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        class="h-8 text-xs"
                        :disabled="!printer.ready.value || printer.printing.value"
                        @click="handleBtPrint"
                    >
                        {{ printer.printing.value ? 'Mencetak…' : 'Cetak via Printer BT' }}
                    </Button>
                </div>
            </div>
            <div
                v-if="btError"
                class="mt-3 rounded border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-700"
            >
                {{ btError }}
            </div>
            <div v-if="!printer.supported" class="mt-3 rounded border border-red-300 bg-red-50 px-3 py-2 text-xs text-red-700">
                Browser ini tidak mendukung Web Bluetooth. Gunakan Chrome / Edge di Android atau desktop, dan akses via HTTPS / localhost.
            </div>
        </div>

        <div class="rounded-lg border bg-muted/30 p-6">
            <div
                ref="previewRef"
                class="receipt-content mx-auto w-full max-w-sm overflow-hidden rounded-2xl border bg-background shadow-sm"
            >
                <ReceiptPreview
                    :store-name="form.name || 'Nama Toko'"
                    :store-address="form.address || null"
                    :store-phone="form.phone || null"
                    :store-logo="previewLogoUrl"
                    :paper-width="printMode === '80' ? 80 : 58"
                    :transaction="sampleTransaction"
                    :options="form.receipt_options"
                    :footer-text="form.receipt_footer || null"
                    invoice-fallback="INV-PREVIEW-001"
                />
            </div>
            <p class="mt-3 text-center text-xs text-muted-foreground">
                Data transaksi di atas hanya contoh — bukan transaksi nyata.
                <span class="block">Klik "Cetak Pratinjau" untuk tes ke printer dengan data dummy.</span>
            </p>
        </div>
    </section>
</template>

<style>
@media print {
    body[data-receipt-preview-print='true'] * {
        visibility: hidden !important;
    }

    body[data-receipt-preview-print='true'] .receipt-content,
    body[data-receipt-preview-print='true'] .receipt-content * {
        visibility: visible !important;
        color: #000 !important;
        background: #fff !important;
        box-shadow: none !important;
        text-shadow: none !important;
        filter: grayscale(100%) !important;
        -webkit-print-color-adjust: economy !important;
        print-color-adjust: economy !important;
    }

    body[data-receipt-preview-print='true'] .receipt-content {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        margin: 0 auto !important;
        border-radius: 0 !important;
        border: 0 !important;
        box-shadow: none !important;
    }

    body[data-receipt-preview-print='true'][data-thermal-print='58'] .receipt-content {
        width: 58mm !important;
        max-width: 58mm !important;
    }

    body[data-receipt-preview-print='true'][data-thermal-print='80'] .receipt-content {
        width: 80mm !important;
        max-width: 80mm !important;
    }

    @page {
        margin: 8mm;
        size: auto;
    }
}
</style>
