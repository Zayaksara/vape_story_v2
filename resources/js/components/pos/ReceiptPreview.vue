<script setup lang="ts">
import { computed } from 'vue';
import type { Transaction } from '@/types/pos';

export type ReceiptOptions = {
    show_logo: boolean;
    show_store_name: boolean;
    show_address: boolean;
    show_phone: boolean;
    show_status_badge: boolean;
    show_datetime: boolean;
    show_invoice_number: boolean;
    show_transaction_id: boolean;
    show_item_unit_line: boolean;
    show_subtotal: boolean;
    show_discount_row: boolean;
    show_payment_method: boolean;
    show_cash_received: boolean;
    show_change: boolean;
    show_cashier: boolean;
    show_header_text: boolean;
    show_footer_text: boolean;
};

const DEFAULT_OPTIONS: ReceiptOptions = {
    show_logo: false,
    show_store_name: true,
    show_address: true,
    show_phone: true,
    show_status_badge: true,
    show_datetime: true,
    show_invoice_number: true,
    show_transaction_id: true,
    show_item_unit_line: true,
    show_subtotal: true,
    show_discount_row: true,
    show_payment_method: true,
    show_cash_received: true,
    show_change: true,
    show_cashier: true,
    show_header_text: true,
    show_footer_text: true,
};

const props = withDefaults(defineProps<{
    storeName?: string | null;
    storeLogo?: string | null;
    storeAddress?: string | null;
    storePhone?: string | null;
    receiptHeader?: string | null;
    receiptFooter?: string | null;
    showLogoOnReceipt?: boolean;
    transaction?: Transaction | null;
    invoiceFallback?: string;
    options?: Partial<ReceiptOptions>;
}>(), {
    storeName: '',
    storeLogo: null,
    storeAddress: null,
    storePhone: null,
    receiptHeader: null,
    receiptFooter: null,
    showLogoOnReceipt: false,
    transaction: null,
    invoiceFallback: '—',
    options: () => ({}),
});

const opt = computed<ReceiptOptions>(() => ({ ...DEFAULT_OPTIONS, ...(props.options ?? {}) }));

// `show_logo` di options menang; kalau key tidak ada di options, pakai prop legacy.
const showLogoEffective = computed(() => {
    if (props.options && Object.prototype.hasOwnProperty.call(props.options, 'show_logo')) {
        return Boolean(props.options.show_logo);
    }
    return Boolean(props.showLogoOnReceipt);
});

// Header info section visible kalau ada minimal satu sub-info aktif & ada datanya
const showHeaderInfoBlock = computed(() =>
    (opt.value.show_store_name && !!props.storeName) ||
    (opt.value.show_address && !!props.storeAddress) ||
    (opt.value.show_phone && !!props.storePhone) ||
    (opt.value.show_header_text && !!props.receiptHeader) ||
    (showLogoEffective.value && !!props.storeLogo),
);

const isCash = computed(() => props.transaction?.payment_method === 'cash');

function formatPrice(price: number | undefined | null): string {
    const v = typeof price === 'number' && !Number.isNaN(price) ? price : 0;
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(v);
}

function formatTransactionDateTime(dt?: string | null): string {
    if (!dt) return '-';
    const parsed = new Date(dt);
    if (Number.isNaN(parsed.getTime())) return '-';
    return parsed.toLocaleDateString('id-ID') + ' ' + parsed.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function resolveInvoice(t: Transaction | null | undefined, fallback: string): string {
    if (t?.invoice_number) return t.invoice_number;
    if (t?.id) return `INV-${String(t.id).slice(-8).toUpperCase()}`;
    return fallback;
}
</script>

<template>
    <div class="receipt-body px-5 py-5" :style="{ backgroundColor: 'var(--pos-bg-primary, #ffffff)' }">
        <!-- ── Header (logo + nama + alamat + telp + header text) ── -->
        <div v-if="showHeaderInfoBlock" class="space-y-1 text-center">
            <img
                v-if="showLogoEffective && storeLogo"
                :src="storeLogo"
                :alt="`Logo ${storeName}`"
                class="mx-auto mb-2 h-12 w-12 rounded object-contain"
            />
            <p
                v-if="opt.show_store_name && storeName"
                class="text-sm font-bold leading-tight"
                :style="{ color: 'var(--pos-text-primary)' }"
            >
                {{ storeName }}
            </p>
            <p
                v-if="opt.show_address && storeAddress"
                class="text-[10px] leading-snug"
                :style="{ color: 'var(--pos-text-primary)' }"
            >
                {{ storeAddress }}
            </p>
            <p
                v-if="opt.show_phone && storePhone"
                class="text-[10px] leading-snug"
                :style="{ color: 'var(--pos-text-primary)' }"
            >
                {{ storePhone }}
            </p>
            <p
                v-if="opt.show_header_text && receiptHeader"
                class="whitespace-pre-line pt-1 text-[10px] leading-snug"
                :style="{ color: 'var(--pos-text-primary)' }"
            >
                {{ receiptHeader }}
            </p>
        </div>

        <!-- ── Status banner (garis tipis + LUNAS) ── -->
        <div
            v-if="opt.show_status_badge"
            class="mt-4 flex items-center gap-3"
            :style="{ color: 'var(--pos-text-primary)' }"
        >
            <span class="h-px flex-1" :style="{ backgroundColor: 'var(--pos-border)' }" />
            <span class="text-xs font-semibold uppercase tracking-[0.2em]">Lunas</span>
            <span class="h-px flex-1" :style="{ backgroundColor: 'var(--pos-border)' }" />
        </div>

        <!-- ── Meta (tanggal + invoice + trx id) ── -->
        <div
            v-if="opt.show_datetime || opt.show_invoice_number || opt.show_transaction_id"
            class="mt-3 space-y-0.5 text-center"
        >
            <p
                v-if="opt.show_datetime"
                class="text-xs"
                :style="{ color: 'var(--pos-text-primary)' }"
            >
                {{ formatTransactionDateTime(transaction?.created_at) }}
            </p>
            <p
                v-if="opt.show_invoice_number"
                class="font-mono text-xs"
                :style="{ color: 'var(--pos-text-primary)' }"
            >
                Invoice: {{ resolveInvoice(transaction, invoiceFallback) }}
            </p>
            <p
                v-if="opt.show_transaction_id"
                class="font-mono text-xs"
                :style="{ color: 'var(--pos-text-primary)' }"
            >
                No. Transaksi: {{ transaction?.id ?? '-' }}
            </p>
        </div>

        <!-- ── Items ── -->
        <div
            class="mt-4 space-y-2 py-3"
            :style="{
                borderTop: '1px solid var(--pos-border)',
                borderBottom: '1px solid var(--pos-border)',
            }"
        >
            <div
                v-for="(item, idx) in transaction?.items || []"
                :key="(item as any).product?.id ?? idx"
                class="flex items-start gap-2"
            >
                <div
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded text-[10px]"
                    :style="{ backgroundColor: 'var(--pos-bg-secondary)', color: 'var(--pos-text-secondary)' }"
                >
                    {{ (item as any).quantity }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-medium" :style="{ color: 'var(--pos-text-primary)' }">
                        {{ (item as any).product?.name ?? '-' }}
                    </p>
                    <p
                        v-if="opt.show_item_unit_line"
                        class="text-[10px]"
                        :style="{ color: 'var(--pos-text-muted, #6b7280)' }"
                    >
                        {{ formatPrice((item as any).product?.price) }} × {{ (item as any).quantity }}
                    </p>
                </div>
                <span class="shrink-0 text-xs font-medium" :style="{ color: 'var(--pos-text-primary)' }">
                    {{ formatPrice((item as any).subtotal) }}
                </span>
            </div>
        </div>

        <!-- ── Summary (subtotal/diskon/metode/bayar/kembali/TOTAL) ── -->
        <div class="mt-3 space-y-1.5">
            <div v-if="opt.show_subtotal" class="flex justify-between text-xs">
                <span :style="{ color: 'var(--pos-text-primary)' }">Subtotal</span>
                <span :style="{ color: 'var(--pos-text-primary)' }">{{ formatPrice(transaction?.subtotal) }}</span>
            </div>
            <div
                v-if="opt.show_discount_row && (transaction?.discount_amount || 0) > 0"
                class="flex justify-between text-xs"
            >
                <span :style="{ color: 'var(--pos-text-primary)' }">Diskon</span>
                <span :style="{ color: 'var(--pos-danger-text)' }">
                    -{{ formatPrice(transaction?.discount_amount) }}
                </span>
            </div>
            <div v-if="opt.show_payment_method" class="flex justify-between text-xs">
                <span :style="{ color: 'var(--pos-text-primary)' }">Metode</span>
                <span class="capitalize" :style="{ color: 'var(--pos-text-primary)' }">
                    {{ transaction?.payment_method ?? '-' }}
                </span>
            </div>
            <div v-if="opt.show_cash_received && isCash" class="flex justify-between text-xs">
                <span :style="{ color: 'var(--pos-text-primary)' }">Dibayar</span>
                <span :style="{ color: 'var(--pos-text-primary)' }">{{ formatPrice(transaction?.cash_received) }}</span>
            </div>
            <div v-if="opt.show_change && isCash" class="flex justify-between text-xs">
                <span :style="{ color: 'var(--pos-text-primary)' }">Kembalian</span>
                <span :style="{ color: 'var(--pos-text-primary)' }">{{ formatPrice(transaction?.change) }}</span>
            </div>

            <div
                class="my-2 border-t"
                :style="{ borderTopColor: 'var(--pos-border)' }"
            />

            <div class="flex items-center justify-between">
                <span class="text-sm font-medium" :style="{ color: 'var(--pos-text-primary)' }">Total</span>
                <span class="text-lg font-bold" :style="{ color: 'var(--pos-brand-primary)' }">
                    {{ formatPrice(transaction?.total) }}
                </span>
            </div>
        </div>

        <!-- ── Cashier ── -->
        <div
            v-if="opt.show_cashier"
            class="mt-3 rounded-lg p-2.5 text-center text-xs"
            :style="{
                backgroundColor: 'var(--pos-bg-secondary, #f3f4f6)',
                color: 'var(--pos-text-primary)',
            }"
        >
            Kasir: {{ transaction?.cashier_name ?? '-' }}
        </div>

        <!-- ── Footer text ── -->
        <p
            v-if="opt.show_footer_text && receiptFooter"
            class="mt-3 whitespace-pre-line text-center text-[10px] leading-snug"
            :style="{ color: 'var(--pos-text-primary)' }"
        >
            {{ receiptFooter }}
        </p>
    </div>
</template>
