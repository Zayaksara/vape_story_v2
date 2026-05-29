<script setup lang="ts">
import { computed } from 'vue';
import type { Transaction } from '@/types/pos';

export type ReceiptOptions = {
    show_logo?: boolean;
    show_store_name?: boolean;
    show_address?: boolean;
    show_phone?: boolean;
    show_datetime?: boolean;
    show_invoice_number?: boolean;
    show_cashier?: boolean;
    show_item_unit_line?: boolean;
    show_subtotal?: boolean;
    show_discount_row?: boolean;
    show_payment_method?: boolean;
    show_cash_received?: boolean;
    show_change?: boolean;
    show_footer_text?: boolean;
};

const DEFAULTS: Required<ReceiptOptions> = {
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

const props = withDefaults(defineProps<{
    storeName?: string | null;
    storeAddress?: string | null;
    storePhone?: string | null;
    storeLogo?: string | null;
    transaction?: Transaction | null;
    invoiceFallback?: string;
    paperWidth?: 58 | 80;
    customer?: string | null;
    taxPercent?: number;
    options?: Partial<ReceiptOptions>;
    footerText?: string | null;
}>(), {
    storeName: '',
    storeAddress: null,
    storePhone: null,
    storeLogo: null,
    transaction: null,
    invoiceFallback: '—',
    paperWidth: 58,
    customer: null,
    taxPercent: 0,
    options: () => ({}),
    footerText: null,
});

const opt = computed<Required<ReceiptOptions>>(() => ({ ...DEFAULTS, ...(props.options ?? {}) }));

const METHOD_LABEL: Record<string, string> = {
    cash: 'TUNAI',
    qris: 'QRIS',
    bank_transfer: 'BANK TRANSFER',
    e_wallet: 'E-WALLET',
};

function rupiah(n: number | null | undefined): string {
    const v = typeof n === 'number' && !Number.isNaN(n) ? n : 0;
    return new Intl.NumberFormat('id-ID').format(v);
}

function fmtDateTime(iso?: string | null): string {
    const d = iso ? new Date(iso) : new Date();
    if (Number.isNaN(d.getTime())) return '-';
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const tx = computed<Transaction | null>(() => props.transaction);
const items = computed(() => tx.value?.items ?? []);
const invoice = computed(() => tx.value?.invoice_number || props.invoiceFallback);
const method = computed(() => tx.value?.payment_method);
const methodLabel = computed(() => METHOD_LABEL[method.value ?? ''] ?? String(method.value ?? '-').toUpperCase());

const detail = computed(() => tx.value?.payment_detail ?? {});
const status = computed(() => {
    const s = detail.value?.status;
    return s && s.trim() !== '' ? s : 'LUNAS';
});
const change = computed(() => {
    const t = tx.value;
    if (!t) return 0;
    if (typeof t.change === 'number') return Math.max(0, t.change);
    return Math.max(0, (t.cash_received ?? 0) - (t.total ?? 0));
});

const widthClass = computed(() => (props.paperWidth === 80 ? 'max-w-[320px]' : 'max-w-[260px]'));

const showHeaderBlock = computed(() =>
    (opt.value.show_logo && !!props.storeLogo)
    || (opt.value.show_store_name && !!props.storeName)
    || (opt.value.show_address && !!props.storeAddress)
    || (opt.value.show_phone && !!props.storePhone),
);

const showInfoBlock = computed(() =>
    opt.value.show_invoice_number
    || opt.value.show_datetime
    || opt.value.show_cashier
    || !!props.customer,
);
</script>

<template>
    <div
        class="receipt-body mx-auto w-full px-4 font-mono text-[12px] leading-snug"
        :class="widthClass"
        :style="{
            backgroundColor: 'var(--pos-bg-primary, #ffffff)',
            color: 'var(--pos-text-primary, #111)',
        }"
    >

        <!-- ── HEADER ─────────────────────────────────────────── -->
        <div v-if="showHeaderBlock" class="space-y-0.5 text-center">
            <img
                v-if="opt.show_logo && storeLogo"
                :src="storeLogo"
                alt="Logo toko"
                class="mx-auto mb-2 h-14 w-14 object-contain"
            />
            <p v-if="opt.show_store_name && storeName" class="mb-1.5 break-words text-[18px] font-extrabold uppercase leading-tight tracking-wide">{{ storeName }}</p>
            <p v-if="opt.show_address && storeAddress" class="whitespace-normal break-words text-[11px]">{{ storeAddress }}</p>
            <p v-if="opt.show_phone && storePhone" class="break-words text-[11px]">{{ storePhone }}</p>
        </div>

        <div
            v-if="showHeaderBlock"
            class="my-2 border-t-2 border-dashed"
            :style="{ borderColor: 'var(--pos-border, #999)' }"
        />

        <!-- ── INFO TRX ──────────────────────────────────────── -->
        <div v-if="showInfoBlock" class="space-y-0.5 text-[11px]">
            <div v-if="opt.show_invoice_number" class="flex">
                <span class="w-12 shrink-0">No</span><span>: {{ invoice }}</span>
            </div>
            <div v-if="opt.show_datetime" class="flex">
                <span class="w-12 shrink-0">Tgl</span><span>: {{ fmtDateTime(tx?.created_at) }}</span>
            </div>
            <div v-if="opt.show_cashier" class="flex">
                <span class="w-12 shrink-0">Kasir</span><span>: {{ tx?.cashier_name ?? '-' }}</span>
            </div>
            <div v-if="customer" class="flex">
                <span class="w-12 shrink-0">Plgn</span><span>: {{ customer }}</span>
            </div>
        </div>

        <div
            v-if="showInfoBlock"
            class="my-2 border-t border-dashed"
            :style="{ borderColor: 'var(--pos-border, #999)' }"
        />

        <!-- ── ITEMS ─────────────────────────────────────────── -->
        <div class="space-y-1.5 text-[11px]">
            <div v-for="(it, idx) in items" :key="(it as any).product?.id ?? idx">
                <p class="break-words">{{ (it as any).product?.name ?? '-' }}</p>
                <div class="flex justify-between pl-2">
                    <span v-if="opt.show_item_unit_line">
                        {{ (it as any).quantity }} x {{ rupiah((it as any).product?.price) }}
                    </span>
                    <span v-else>x{{ (it as any).quantity }}</span>
                    <span>{{ rupiah((it as any).subtotal) }}</span>
                </div>
            </div>
        </div>

        <div class="my-2 border-t border-dashed" :style="{ borderColor: 'var(--pos-border, #999)' }" />

        <!-- ── RINGKASAN ─────────────────────────────────────── -->
        <div class="space-y-0.5 text-[11px]">
            <div v-if="opt.show_subtotal" class="flex justify-between">
                <span>Subtotal</span><span>{{ rupiah(tx?.subtotal) }}</span>
            </div>
            <div v-if="opt.show_discount_row && (tx?.discount_amount ?? 0) > 0" class="flex justify-between">
                <span>Diskon</span><span>-{{ rupiah(tx?.discount_amount) }}</span>
            </div>
            <div v-if="taxPercent > 0 && (tx?.tax_amount ?? 0) > 0" class="flex justify-between">
                <span>PPN {{ taxPercent }}%</span><span>{{ rupiah(tx?.tax_amount) }}</span>
            </div>
            <div class="mt-1 flex justify-between text-sm font-bold">
                <span>TOTAL</span><span>{{ rupiah(tx?.total) }}</span>
            </div>
        </div>

        <!-- ── PEMBAYARAN ────────────────────────────────────── -->
        <div v-if="opt.show_payment_method" class="mt-3 space-y-0.5 text-[11px]">
            <div class="flex justify-between font-semibold">
                <span>METODE</span><span>{{ methodLabel }}</span>
            </div>

            <template v-if="method === 'cash'">
                <div v-if="opt.show_cash_received" class="flex justify-between">
                    <span>Tunai</span><span>{{ rupiah(tx?.cash_received) }}</span>
                </div>
                <div v-if="opt.show_change" class="flex justify-between">
                    <span>Kembalian</span><span>{{ rupiah(change) }}</span>
                </div>
            </template>

            <template v-else-if="method === 'qris'">
                <div v-if="detail.merchant_id" class="flex justify-between">
                    <span>Merchant</span><span>{{ detail.merchant_id }}</span>
                </div>
                <div v-if="detail.ref" class="flex justify-between">
                    <span>Ref</span><span>{{ detail.ref }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Dibayar</span><span>{{ rupiah(tx?.total) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Status</span><span>{{ status }}</span>
                </div>
            </template>

            <template v-else-if="method === 'bank_transfer'">
                <div v-if="detail.bank_name" class="flex justify-between">
                    <span>Bank</span><span>{{ detail.bank_name }}</span>
                </div>
                <div v-if="detail.account_number" class="flex justify-between">
                    <span>Rek</span><span>{{ detail.account_number }}</span>
                </div>
                <div v-if="detail.ref" class="flex justify-between">
                    <span>Ref</span><span>{{ detail.ref }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Dibayar</span><span>{{ rupiah(tx?.total) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Status</span><span>{{ status }}</span>
                </div>
            </template>

            <template v-else-if="method === 'e_wallet'">
                <div v-if="detail.wallet_name" class="flex justify-between">
                    <span>Wallet</span><span>{{ detail.wallet_name }}</span>
                </div>
                <div v-if="detail.ref" class="flex justify-between">
                    <span>Ref</span><span>{{ detail.ref }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Dibayar</span><span>{{ rupiah(tx?.total) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Status</span><span>{{ status }}</span>
                </div>
            </template>
        </div>

        <template v-if="opt.show_footer_text && footerText">
            <div class="my-2 border-t-2 border-dashed" :style="{ borderColor: 'var(--pos-border, #999)' }" />
            <!-- margin atas footer 1 baris -->
            <div aria-hidden="true" class="h-5" />
            <p class="whitespace-pre-line text-center text-[11px]">{{ footerText }}</p>
        </template>

        <!-- margin bawah 1 baris (sinkron dengan teks struk) -->
        <div aria-hidden="true" class="h-5" />
    </div>
</template>
