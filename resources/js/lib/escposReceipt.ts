import type { Transaction } from '@/types/pos';
import { buildReceiptSections, type ReceiptTextOptions } from '@/lib/buildReceiptText';

export type ReceiptStoreInfo = {
    name?: string | null;
    address?: string | null;
    phone?: string | null;
};

export type BuildReceiptArgs = {
    store: ReceiptStoreInfo;
    transaction: Transaction;
    options?: Partial<ReceiptTextOptions>;
    paperWidth?: 58 | 80;
    customer?: string | null;
    taxPercent?: number;
    footerText?: string | null;
    logoUrl?: string | null;
};

const ESC = 0x1b;
const GS = 0x1d;
const LF = 0x0a;

// Lebar printable dots (kira-kira) — 58mm ≈ 384, 80mm ≈ 576.
// Pakai nilai konservatif agar logo tidak overshoot.
const LOGO_MAX_DOTS: Record<58 | 80, number> = { 58: 240, 80: 360 };

function loadImage(url: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = (e) => reject(e instanceof Error ? e : new Error('Gagal memuat logo'));
        img.src = url;
    });
}

async function encodeLogoBytes(url: string, paperWidth: 58 | 80): Promise<number[] | null> {
    try {
        const img = await loadImage(url);
        if (!img.width || !img.height) return null;

        const maxDots = LOGO_MAX_DOTS[paperWidth];
        const scale = Math.min(1, maxDots / img.width);
        let w = Math.max(8, Math.floor(img.width * scale));
        w = w - (w % 8); // wajib kelipatan 8
        const h = Math.max(1, Math.floor(img.height * (w / img.width)));

        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        if (!ctx) return null;
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
        const data = ctx.getImageData(0, 0, w, h).data;

        const bytesPerRow = w / 8;
        const out: number[] = [];

        // Center align
        out.push(ESC, 0x61, 0x01);

        // GS v 0 m xL xH yL yH
        out.push(
            GS, 0x76, 0x30, 0x00,
            bytesPerRow & 0xff, (bytesPerRow >> 8) & 0xff,
            h & 0xff, (h >> 8) & 0xff,
        );

        for (let y = 0; y < h; y++) {
            for (let xb = 0; xb < bytesPerRow; xb++) {
                let byte = 0;
                for (let bit = 0; bit < 8; bit++) {
                    const x = xb * 8 + bit;
                    const i = (y * w + x) * 4;
                    const r = data[i];
                    const g = data[i + 1];
                    const b = data[i + 2];
                    const a = data[i + 3] / 255;
                    // Luminance over white background (handle alpha)
                    const lum = (0.299 * r + 0.587 * g + 0.114 * b) * a + 255 * (1 - a);
                    if (lum < 160) byte |= 1 << (7 - bit);
                }
                out.push(byte);
            }
        }

        // Reset left align + sedikit ruang setelah logo
        out.push(LF);
        out.push(ESC, 0x61, 0x00);

        return out;
    } catch {
        return null;
    }
}

export async function buildReceiptBytes({
    store,
    transaction,
    paperWidth = 58,
    customer = null,
    taxPercent = 0,
    options = {},
    footerText = null,
    logoUrl = null,
}: BuildReceiptArgs): Promise<Uint8Array> {
    const { storeNameLines, bodyLines } = buildReceiptSections({
        store,
        transaction,
        paperWidth,
        customer,
        taxPercent,
        options,
        footerText,
    });

    const enc = new TextEncoder();
    const parts: number[] = [];
    const push = (...b: number[]) => parts.push(...b);
    const pushText = (s: string) => { for (const b of enc.encode(s)) parts.push(b); };

    push(ESC, 0x40); // Init

    // Logo (opsional) — raster bitmap di atas teks
    if (options.show_logo && logoUrl) {
        const logoBytes = await encodeLogoBytes(logoUrl, paperWidth);
        if (logoBytes) parts.push(...logoBytes);
    }

    // ── Nama toko: BOLD + double-height + double-width, center align ─
    if (storeNameLines.length > 0) {
        push(ESC, 0x61, 0x01); // Center
        push(ESC, 0x45, 0x01); // Bold ON
        push(GS, 0x21, 0x11);  // double-width + double-height (≈ 2x ≥ permintaan "1,5x")
        for (const line of storeNameLines) {
            pushText(line);
            parts.push(LF);
        }
        push(GS, 0x21, 0x00);  // ukuran normal
        push(ESC, 0x45, 0x00); // Bold OFF
        // Margin 0,5 baris — kecilkan line-spacing lalu kirim 1 LF, kemudian reset
        push(ESC, 0x33, 0x10); // ESC 3 n (16/180 inch ≈ setengah baris)
        parts.push(LF);
        push(ESC, 0x32);       // reset default line spacing
        push(ESC, 0x61, 0x00); // kembali left align untuk body
    }

    // Body (alamat, telp, garis, info, item, dst.)
    pushText(bodyLines.join('\n'));
    parts.push(LF, LF, LF);

    push(GS, 0x56, 0x42, 0x00); // Partial cut

    return new Uint8Array(parts);
}
