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
                <!-- Receipt content -->
                <div
                    :style="{ backgroundColor: 'var(--pos-bg-primary, #1e293b)' }"
                    class="receipt-body p-5"
                >
                    <!-- Header -->
                    <div class="mb-4 text-center">
                        <div
                            class="mb-2 flex items-center justify-center gap-2"
                        >
                            <div
                                class="h-8 w-8 rounded-full"
                                :style="{
                                    backgroundColor: 'var(--pos-brand-primary)',
                                }"
                            >
                                <svg
                                    class="h-full w-full p-2"
                                    :style="{
                                        color: 'var(--pos-text-inverse)',
                                    }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                        </div>
                        <h2
                            id="receipt-title"
                            class="text-lg font-bold"
                            :style="{ color: 'var(--pos-text-primary)' }"
                        >
                            Pembayaran Berhasil
                        </h2>
                        <p
                            class="text-xs"
                            :style="{ color: 'var(--pos-text-primary)' }"
                        >
                            {{
                                transaction?.created_at
                                    ? new Date(
                                          transaction.created_at,
                                      ).toLocaleDateString('id-ID')
                                    : '-'
                            }}
                        </p>
                        <p
                            class="font-mono text-xs"
                            :style="{ color: 'var(--pos-text-primary)' }"
                        >
                            {{ transaction?.id }}
                        </p>
                    </div>

                    <!-- Items -->
                    <div
                        class="space-y-2 py-4"
                        :style="{
                            borderTop: '1px solid var(--pos-border)',
                            borderBottom: '1px solid var(--pos-border)',
                        }"
                    >
                        <div
                            v-for="item in transaction?.items || []"
                            :key="item.product.id"
                            class="flex items-start gap-2"
                        >
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded text-[10px]"
                                :style="{
                                    backgroundColor: 'var(--pos-bg-secondary)',
                                    color: 'var(--pos-text-secondary)',
                                }"
                            >
                                {{ item.quantity }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-xs font-medium"
                                    :style="{
                                        color: 'var(--pos-text-primary)',
                                    }"
                                >
                                    {{ item.product.name }}
                                </p>
                                <p
                                    class="text-[10px]"
                                    :style="{ color: 'var(--pos-text-primary)' }"
                                >
                                    {{ formatPrice(item.product.price) }} ×
                                    {{ item.quantity }}
                                </p>
                            </div>
                            <span
                                class="shrink-0 text-xs font-medium"
                                :style="{ color: 'var(--pos-text-primary)' }"
                                >{{ formatPrice(item.subtotal) }}</span
                            >
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="space-y-1.5 py-4">
                        <div class="flex justify-between text-xs">
                            <span :style="{ color: 'var(--pos-text-primary)' }"
                                >Subtotal</span
                            >
                            <span>{{
                                formatPrice(transaction?.subtotal || 0)
                            }}</span>
                        </div>
                        <div
                            v-if="(transaction?.discount_amount || 0) > 0"
                            class="flex justify-between text-xs"
                        >
                            <span :style="{ color: 'var(--pos-text-primary)' }"
                                >Diskon</span
                            >
                            <span :style="{ color: 'var(--pos-danger-text)' }"
                                >-{{
                                    formatPrice(
                                        transaction?.discount_amount || 0,
                                    )
                                }}</span
                            >
                        </div>
                        <div class="flex justify-between text-xs">
                            <span :style="{ color: 'var(--pos-text-primary)' }"
                                >Metode</span
                            >
                            <span class="capitalize">{{
                                transaction?.payment_method
                            }}</span>
                        </div>
                        <div
                            v-if="transaction?.payment_method === 'cash'"
                            class="flex justify-between text-xs"
                        >
                            <span :style="{ color: 'var(--pos-text-primary)' }"
                                >Dibayar</span
                            >
                            <span>{{
                                formatPrice(transaction?.cash_received || 0)
                            }}</span>
                        </div>
                        <div
                            v-if="transaction?.payment_method === 'cash'"
                            class="flex justify-between text-xs"
                        >
                            <span :style="{ color: 'var(--pos-text-primary)' }"
                                >Kembalian</span
                            >
                            <span>{{
                                formatPrice(transaction?.change || 0)
                            }}</span>
                        </div>
                        <div
                            class="my-2 border-t"
                            :style="{ borderTopColor: 'var(--pos-border)' }"
                        />
                        <div class="flex justify-between">
                            <span
                                class="font-medium"
                                :style="{ color: 'var(--pos-text-primary)' }"
                                >Total</span
                            >
                            <span
                                class="text-lg font-bold"
                                :style="{ color: 'var(--pos-brand-primary)' }"
                                >{{
                                    formatPrice(transaction?.total || 0)
                                }}</span
                            >
                        </div>
                    </div>

                    <!-- Cashier -->
                    <div
                        class="rounded-lg p-3 text-center text-xs"
                        :style="{
                            backgroundColor: 'var(--pos-bg-primary)',
                            color: 'var(--pos-text-primary)',
                        }"
                    >
                        Kasir: {{ transaction?.cashier_name }}
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="border-t p-4"
                    :style="{
                        backgroundColor: 'var(--pos-bg-primary)'
                    }"
                >
                    <button
                        class="w-full rounded-xl py-3 text-sm font-bold text-white transition-all hover:opacity-90 active:scale-95"
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
    </Teleport>
</template>

<script setup lang="ts">
import type { Transaction } from '@/types/pos';

const props = defineProps<{
    modelValue: boolean;
    transaction?: Transaction | null;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void;
}>();

function close() {
    emit('update:modelValue', false);
}

function formatPrice(price: number): string {
    if (typeof price !== 'number' || isNaN(price)) {
        return 'Rp 0';
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(price);
}
</script>

<style scoped>
/* Print-specific styles */
@media print {
    .receipt-modal {
        position: static !important;
        inset: auto !important;
        display: block !important;
        z-index: auto !important;
        max-width: none !important;
        width: 100% !important;
        margin: 0 auto !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        animation: none !important;
    }

    .receipt-content {
        max-width: 100% !important;
        margin: 0 auto !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }

    button[aria-label='Print receipt'],
    .receipt-modal > div > div:last-child {
        display: none !important;
    }

    body * {
        visibility: hidden !important;
    }

    .receipt-modal,
    .receipt-modal * {
        visibility: visible !important;
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
        padding: 20px !important;
    }
}

@media print and (max-width: 768px) {
    .receipt-body {
        padding: 15px !important;
    }
}
</style>
