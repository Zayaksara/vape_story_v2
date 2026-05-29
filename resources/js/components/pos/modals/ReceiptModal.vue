<template>
    <Teleport to="body">
        <div
            v-if="modelValue"
            class="receipt-modal fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="receipt-title"
        >
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close" />

            <div
                class="receipt-content relative z-10 flex max-h-[90dvh] w-full max-w-sm flex-col animate-in overflow-hidden rounded-2xl shadow-2xl duration-200 zoom-in-95 fade-in"
                :style="{
                    backgroundColor: 'var(--pos-border-focus, #14b8a6)',
                }"
            >
                <!-- Receipt body (shared dengan live preview di Settings) -->
                <div class="min-h-0 flex-1 overflow-y-auto">
                    <ReceiptPreview
                        :store-name="storeName"
                        :store-address="storeAddress"
                        :store-phone="storePhone"
                        :store-logo="storeLogo"
                        :paper-width="printMode === '80' ? 80 : 58"
                        :transaction="transaction ?? null"
                        :options="receiptOptions"
                        :footer-text="receiptFooterText"
                    />
                </div>

                <!-- Footer -->
                <div
                    class="shrink-0 border-t p-4"
                    :style="{
                        backgroundColor: 'var(--pos-bg-primary)'
                    }"
                >
                    <div class="mb-3 grid grid-cols-2 gap-2">
                        <button
                            class="rounded-lg border px-3 py-2 text-xs font-semibold transition-all"
                            :style="printMode === '58'
                                ? 'background: var(--pos-brand-primary); color: #fff; border-color: var(--pos-brand-primary);'
                                : 'background: transparent; color: var(--pos-text-primary); border-color: var(--pos-border);'"
                            @click="printMode = '58'"
                        >
                            Thermal 58mm
                        </button>
                        <button
                            class="rounded-lg border px-3 py-2 text-xs font-semibold transition-all"
                            :style="printMode === '80'
                                ? 'background: var(--pos-brand-primary); color: #fff; border-color: var(--pos-brand-primary);'
                                : 'background: transparent; color: var(--pos-text-primary); border-color: var(--pos-border);'"
                            @click="printMode = '80'"
                        >
                            Thermal 80mm
                        </button>
                    </div>
                    <p
                        v-if="printError"
                        class="mb-2 rounded border border-red-300 bg-red-50 px-2 py-1 text-[11px] text-red-700"
                    >
                        {{ printError }}
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            class="rounded-xl py-3 text-sm font-bold text-white transition-all hover:opacity-90 active:scale-95 disabled:opacity-60"
                            :style="{
                                backgroundColor: printer.ready.value
                                    ? 'var(--pos-brand-primary)'
                                    : 'var(--pos-text-muted)',
                            }"
                            :disabled="printer.printing.value"
                            @click="handleThermalPrint"
                        >
                            {{ printer.printing.value ? 'Mencetak…' : 'Print Termal' }}
                        </button>
                        <button
                            class="rounded-xl py-3 text-sm font-bold text-white transition-all hover:opacity-90 active:scale-95"
                            :style="{
                                backgroundColor: 'var(--pos-brand-primary)',
                                boxShadow:
                                    '0 10px 25px -5px rgba(20, 184, 166, 0.35)',
                            }"
                            @click="close"
                        >
                            Selesai
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Printer not connected dialog ────────────────────── -->
        <div
            v-if="modelValue && showNotConnected"
            class="fixed inset-0 z-[60] flex items-center justify-center p-4"
            role="alertdialog"
            aria-modal="true"
        >
            <div class="absolute inset-0 bg-black/50" @click="showNotConnected = false" />
            <div
                class="relative z-10 w-full max-w-xs rounded-2xl bg-white p-5 text-center shadow-2xl"
            >
                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><line x1="12" y1="8" x2="12" y2="12" /><line x1="12" y1="16" x2="12.01" y2="16" /></svg>
                </div>
                <p class="text-sm font-bold text-gray-900">Printer Termal Tidak Ditemukan</p>
                <p class="mt-1 text-xs text-gray-600">
                    {{ printer.supported
                        ? 'Sambungkan printer Bluetooth (ESC/POS) terlebih dahulu untuk mencetak.'
                        : 'Browser ini tidak mendukung Web Bluetooth. Gunakan Chrome/Edge via HTTPS atau localhost.' }}
                </p>
                <p
                    v-if="printer.hasSavedDevice.value && printer.savedDeviceName.value"
                    class="mt-2 rounded bg-teal-50 px-2 py-1 text-[11px] text-teal-800"
                >
                    Printer tersimpan: <span class="font-semibold">{{ printer.savedDeviceName.value }}</span>
                </p>
                <p
                    v-if="printError"
                    class="mt-2 rounded border border-red-300 bg-red-50 px-2 py-1 text-[11px] text-red-700"
                >
                    {{ printError }}
                </p>
                <div class="mt-4 grid gap-2" :class="printer.hasSavedDevice.value ? 'grid-cols-1' : 'grid-cols-2'">
                    <button
                        v-if="printer.hasSavedDevice.value"
                        class="rounded-lg bg-teal-600 py-2 text-xs font-semibold text-white hover:bg-teal-700 disabled:opacity-60"
                        :disabled="!printer.supported || connecting"
                        @click="handleReconnectFromModal"
                    >
                        {{ connecting ? 'Menghubungkan…' : 'Hubungkan Ulang' }}
                    </button>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            class="rounded-lg border border-gray-300 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                            @click="showNotConnected = false"
                        >
                            Tutup
                        </button>
                        <button
                            class="rounded-lg py-2 text-xs font-semibold text-white hover:opacity-90 disabled:opacity-60"
                            :class="printer.hasSavedDevice.value ? 'bg-gray-700' : 'bg-teal-600 hover:bg-teal-700'"
                            :disabled="!printer.supported || connecting"
                            @click="handlePairFromModal"
                        >
                            {{ printer.hasSavedDevice.value ? 'Pair Printer Baru' : (connecting ? 'Menghubungkan…' : 'Pair Printer') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import type { Transaction } from '@/types/pos';
import ReceiptPreview from '@/components/pos/ReceiptPreview.vue';
import { usePrinter } from '@/composables/usePrinter';
import { buildReceiptBytes } from '@/lib/escposReceipt';

const props = defineProps<{
    modelValue: boolean;
    transaction?: Transaction | null;
}>();

const page = usePage();
const storeName = computed(() => (page.props.storeName as string | undefined) ?? '');
const storeAddress = computed(() => (page.props.storeAddress as string | null | undefined) ?? null);
const storePhone = computed(() => (page.props.storePhone as string | null | undefined) ?? null);
const storeLogo = computed(() => (page.props.storeLogo as string | null | undefined) ?? null);
const receiptOptions = computed(() => (page.props.storeReceiptOptions as Record<string, boolean> | undefined) ?? {});
const receiptFooterText = computed(() => (page.props.storeReceiptFooter as string | null | undefined) ?? null);

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();

const printMode = ref<'58' | '80'>('58');

const printer = usePrinter();
const showNotConnected = ref(false);
const connecting = ref(false);
const printError = ref<string | null>(null);

onMounted(() => {
    if (printer.supported && !printer.ready.value) {
        printer.tryAutoConnect().catch(() => { /* ignore */ });
    }
});

// Setiap modal dibuka, coba reconnect otomatis (silent) ke printer tersimpan.
watch(() => props.modelValue, (open) => {
    if (open && printer.supported && !printer.ready.value) {
        printer.tryAutoConnect().catch(() => { /* ignore */ });
    }
});

function close() {
    emit('update:modelValue', false);
}

async function handleThermalPrint(): Promise<void> {
    printError.value = null;
    if (!printer.supported) {
        showNotConnected.value = true;
        return;
    }
    if (!printer.ready.value) {
        // Coba reconnect senyap dulu sebelum minta pairing manual
        const ok = await printer.tryAutoConnect();
        if (!ok) {
            showNotConnected.value = true;
            return;
        }
    }
    if (!props.transaction) {
        printError.value = 'Tidak ada data transaksi.';
        return;
    }
    try {
        const bytes = await buildReceiptBytes({
            store: {
                name: storeName.value,
                address: storeAddress.value,
                phone: storePhone.value,
            },
            transaction: props.transaction,
            paperWidth: printMode.value === '80' ? 80 : 58,
            options: receiptOptions.value,
            footerText: receiptFooterText.value,
            logoUrl: storeLogo.value,
        });
        await printer.printBytes(bytes);
    } catch (err: any) {
        printError.value = err?.message ?? String(err);
    }
}

async function handleReconnectFromModal(): Promise<void> {
    connecting.value = true;
    printError.value = null;
    try {
        const ok = await printer.tryAutoConnect();
        if (ok) {
            showNotConnected.value = false;
            await handleThermalPrint();
        } else {
            printError.value = printer.lastMessage.value
                ?? 'Tidak bisa menemukan printer tersimpan. Pair ulang printer.';
        }
    } catch (err: any) {
        printError.value = err?.message ?? String(err);
    } finally {
        connecting.value = false;
    }
}

async function handlePairFromModal(): Promise<void> {
    connecting.value = true;
    printError.value = null;
    try {
        const ok = await printer.pair();
        if (ok) {
            showNotConnected.value = false;
            await handleThermalPrint();
        } else {
            printError.value = printer.lastMessage.value ?? 'Gagal menghubungkan printer.';
        }
    } catch (err: any) {
        printError.value = err?.message ?? String(err);
    } finally {
        connecting.value = false;
    }
}
</script>

<style scoped>
/* Print-specific styles */
@media print {
    @page {
        margin: 8mm;
        size: auto;
    }

    .receipt-modal {
        position: fixed !important;
        inset: 0 !important;
        display: block !important;
        padding: 0 !important;
        background: #fff !important;
    }

    .receipt-content {
        max-width: 80mm !important;
        margin: 0 auto !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        border: 0 !important;
        background: #fff !important;
    }

    .receipt-modal > div:first-child,
    .receipt-content > div:last-child {
        display: none !important;
    }

    body * {
        visibility: hidden !important;
    }

    .receipt-modal,
    .receipt-modal * {
        visibility: visible !important;
        color: #000 !important;
        background: #fff !important;
        box-shadow: none !important;
        text-shadow: none !important;
        filter: grayscale(100%) !important;
        -webkit-print-color-adjust: economy !important;
        print-color-adjust: economy !important;
    }

    .receipt-modal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        margin: 0 !important;
    }

    .receipt-body {
        margin: 0 !important;
        padding: 10px !important;
    }

    body[data-thermal-print='58'] .receipt-content {
        width: 58mm !important;
        max-width: 58mm !important;
    }

    body[data-thermal-print='80'] .receipt-content {
        width: 80mm !important;
        max-width: 80mm !important;
    }
}

@media print and (max-width: 768px) {
    .receipt-body {
        padding: 15px !important;
    }
}
</style>
