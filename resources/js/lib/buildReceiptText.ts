import type { Transaction } from '@/types/pos';

export type ReceiptStore = {
    name?: string | null;
    address?: string | null;
    phone?: string | null;
};

export type ReceiptTextOptions = {
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

const DEFAULTS: Required<ReceiptTextOptions> = {
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

export type BuildReceiptTextArgs = {
    store: ReceiptStore;
    transaction: Transaction;
    paperWidth?: 58 | 80;
    customer?: string | null;
    taxPercent?: number;
    options?: Partial<ReceiptTextOptions>;
    footerText?: string | null;
};

export type ReceiptSections = {
    /** Baris nama toko — DICETAK BESAR & BOLD oleh ESC/POS layer. */
    storeNameLines: string[];
    /** Sisa struk setelah nama toko (alamat, telp, garis, body, footer). */
    bodyLines: string[];
};

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
    if (Number.isNaN(d.getTime())) return '';
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

export function buildReceiptText(args: BuildReceiptTextArgs): string {
    const { storeNameLines, bodyLines } = buildReceiptSections(args);
    return [...storeNameLines, ...bodyLines].join('\n');
}

export function buildReceiptSections({
    store,
    transaction,
    paperWidth = 58,
    customer = null,
    taxPercent = 0,
    options = {},
    footerText = null,
}: BuildReceiptTextArgs): ReceiptSections {
    const width = paperWidth === 80 ? 48 : 32;
    const opt = { ...DEFAULTS, ...options };

    const repeat = (ch: string) => ch.repeat(width);
    const center = (s: string): string => {
        const t = s.length > width ? s.slice(0, width) : s;
        const pad = Math.floor((width - t.length) / 2);
        return ' '.repeat(Math.max(0, pad)) + t;
    };
    const lr = (left: string, right: string): string => {
        let l = left;
        const r = right;
        const max = width - r.length - 1;
        if (l.length > max) l = l.slice(0, Math.max(0, max));
        const space = Math.max(1, width - l.length - r.length);
        return l + ' '.repeat(space) + r;
    };
    const kv = (label: string, value: string): string => `${label.padEnd(6, ' ')}: ${value}`;
    const wrapCenter = (s: string): string[] => {
        const out: string[] = [];
        for (const raw of s.split('\n')) {
            if (raw.length <= width) { out.push(center(raw)); continue; }
            let cur = '';
            for (const w of raw.split(/\s+/)) {
                if ((cur + (cur ? ' ' : '') + w).length > width) {
                    if (cur) out.push(center(cur));
                    cur = w;
                } else {
                    cur = cur ? cur + ' ' + w : w;
                }
            }
            if (cur) out.push(center(cur));
        }
        return out;
    };

    const storeNameLines: string[] = [];
    const lines: string[] = [];

    // ── HEADER ──────────────────────────────────────────────
    if (opt.show_store_name && store.name) {
        // Nama toko dipisah → diberi style besar+bold di ESC/POS layer.
        // Wrap di-hitung ulang dengan width/2 karena double-width memakan 2x ruang
        // tiap karakter di printer (centering ditangani ESC a 1, bukan spasi).
        const nameWidth = Math.max(8, Math.floor(width / 2));
        const raw = store.name.toUpperCase();
        if (raw.length <= nameWidth) {
            storeNameLines.push(raw);
        } else {
            let cur = '';
            for (const w of raw.split(/\s+/)) {
                if ((cur + (cur ? ' ' : '') + w).length > nameWidth) {
                    if (cur) storeNameLines.push(cur);
                    cur = w;
                } else {
                    cur = cur ? cur + ' ' + w : w;
                }
            }
            if (cur) storeNameLines.push(cur);
        }
    }

    // Sub-header lain tetap di body (ukuran normal, center via spasi).
    if (opt.show_address && store.address) {
        for (const l of wrapCenter(store.address)) lines.push(l);
    }
    if (opt.show_phone && store.phone) {
        for (const l of wrapCenter(store.phone)) lines.push(l);
    }

    lines.push(repeat('='));

    // ── INFO TRX ────────────────────────────────────────────
    if (opt.show_invoice_number) lines.push(kv('No', transaction.invoice_number ?? ''));
    if (opt.show_datetime) lines.push(kv('Tgl', fmtDateTime(transaction.created_at)));
    if (opt.show_cashier) lines.push(kv('Kasir', transaction.cashier_name ?? ''));
    if (customer) lines.push(kv('Plgn', customer));

    lines.push(repeat('-'));

    // ── ITEMS ───────────────────────────────────────────────
    for (const it of transaction.items ?? []) {
        const name = it.product?.name ?? '-';
        lines.push(name);
        const left = opt.show_item_unit_line
            ? `  ${it.quantity} x ${rupiah(it.product?.price)}`
            : `  x${it.quantity}`;
        lines.push(lr(left, rupiah(it.subtotal)));
    }

    lines.push(repeat('-'));

    // ── RINGKASAN ───────────────────────────────────────────
    if (opt.show_subtotal) lines.push(lr('Subtotal', rupiah(transaction.subtotal)));
    if (opt.show_discount_row && (transaction.discount_amount ?? 0) > 0) {
        lines.push(lr('Diskon', `-${rupiah(transaction.discount_amount)}`));
    }
    if (taxPercent > 0 && (transaction.tax_amount ?? 0) > 0) {
        lines.push(lr(`PPN ${taxPercent}%`, rupiah(transaction.tax_amount)));
    }
    lines.push(lr('TOTAL', rupiah(transaction.total)));
    lines.push('');

    // ── PEMBAYARAN ──────────────────────────────────────────
    if (opt.show_payment_method) {
        const method = transaction.payment_method;
        const methodLabel = METHOD_LABEL[method] ?? String(method ?? '-').toUpperCase();
        lines.push(lr('METODE', methodLabel));

        const detail = transaction.payment_detail ?? {};
        const status = detail.status && detail.status.trim() !== '' ? detail.status : 'LUNAS';

        if (method === 'cash') {
            const received = transaction.cash_received ?? 0;
            const change = Math.max(0, transaction.change ?? received - transaction.total);
            if (opt.show_cash_received) lines.push(lr('Tunai', rupiah(received)));
            if (opt.show_change) lines.push(lr('Kembalian', rupiah(change)));
        } else if (method === 'qris') {
            if (detail.merchant_id) lines.push(lr('Merchant', detail.merchant_id));
            if (detail.ref) lines.push(lr('Ref', detail.ref));
            lines.push(lr('Dibayar', rupiah(transaction.total)));
            lines.push(lr('Status', status));
        } else if (method === 'bank_transfer') {
            if (detail.bank_name) lines.push(lr('Bank', detail.bank_name));
            if (detail.account_number) lines.push(lr('Rek', detail.account_number));
            if (detail.ref) lines.push(lr('Ref', detail.ref));
            lines.push(lr('Dibayar', rupiah(transaction.total)));
            lines.push(lr('Status', status));
        } else if (method === 'e_wallet') {
            if (detail.wallet_name) lines.push(lr('Wallet', detail.wallet_name));
            if (detail.ref) lines.push(lr('Ref', detail.ref));
            lines.push(lr('Dibayar', rupiah(transaction.total)));
            lines.push(lr('Status', status));
        }
    }

    lines.push(repeat('='));

    // ── FOOTER (dari store: receipt_footer) ─────────────────
    if (opt.show_footer_text && footerText) {
        lines.push(''); // margin atas footer
        for (const l of wrapCenter(footerText)) lines.push(l);
    }
    lines.push(''); // margin bawah 1 baris

    return { storeNameLines, bodyLines: lines };
}
