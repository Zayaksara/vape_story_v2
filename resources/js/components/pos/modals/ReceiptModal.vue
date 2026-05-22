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
                class="receipt-content relative z-10 w-full max-w-sm animate-in overflow-hidden rounded-2xl shadow-2xl duration-200 zoom-in-95 fade-in"
                :style="{
                    backgroundColor: 'var(--pos-border-focus, #14b8a6)',
                }"
            >
                <!-- Receipt body (shared dengan live preview di Settings) -->
                <ReceiptPreview
                    :store-name="storeName"
                    :store-logo="storeLogo"
                    :store-address="storeAddress"
                    :store-phone="storePhone"
                    :receipt-header="receiptHeader"
                    :receipt-footer="receiptFooter"
                    :show-logo-on-receipt="showLogoOnReceipt"
                    :options="receiptOptions"
                    :transaction="transaction ?? null"
                />

                <!-- Footer -->
                <div
                    class="border-t p-4"
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
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            class="rounded-xl py-3 text-sm font-bold text-white transition-all hover:opacity-90 active:scale-95"
                            :style="{
                                backgroundColor: 'var(--pos-text-muted)',
                            }"
                            @click="printReceipt"
                        >
                            Print Nota
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
    </Teleport>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import type { Transaction } from '@/types/pos';
import ReceiptPreview from '@/components/pos/ReceiptPreview.vue';

const props = defineProps<{
    modelValue: boolean;
    transaction?: Transaction | null;
}>();

const page = usePage();
const storeName = computed(() => (page.props.storeName as string | undefined) ?? '');
const storeLogo = computed(() => (page.props.storeLogo as string | null | undefined) ?? null);
const storeAddress = computed(() => (page.props.storeAddress as string | null | undefined) ?? null);
const storePhone = computed(() => (page.props.storePhone as string | null | undefined) ?? null);
const receiptHeader = computed(() => (page.props.storeReceiptHeader as string | null | undefined) ?? null);
const receiptFooter = computed(() => (page.props.storeReceiptFooter as string | null | undefined) ?? null);
const showLogoOnReceipt = computed(() => Boolean(page.props.storeShowLogoOnReceipt));
const receiptOptions = computed(() => (page.props.storeReceiptOptions as Record<string, boolean> | undefined) ?? {});

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();

const printMode = ref<'58' | '80'>('58');

function close() {
    emit('update:modelValue', false);
}

function printReceipt(): void {
    const receiptContent = document.querySelector('.receipt-content') as HTMLElement | null;
    const body = document.body;
    if (receiptContent) {
        receiptContent.dataset.printMode = printMode.value;
    }
    body.dataset.thermalPrint = printMode.value;
    window.print();
    window.setTimeout(() => {
        if (receiptContent) {
            delete receiptContent.dataset.printMode;
        }
        delete body.dataset.thermalPrint;
    }, 300);
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
