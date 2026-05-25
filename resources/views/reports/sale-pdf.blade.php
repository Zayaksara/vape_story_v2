@php
    $rp  = fn ($n) => 'Rp ' . number_format((float) $n, 0, ',', '.');
    $num = fn ($n) => number_format((float) $n, 0, ',', '.');
    $fmtDateTime = function ($iso) {
        if (! $iso) return '—';
        try { return \Illuminate\Support\Carbon::parse($iso)->isoFormat('D MMM YYYY · HH:mm'); }
        catch (\Throwable $e) { return $iso; }
    };
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: sans-serif; }
        body { color: #111827; font-size: 9.5px; }

        .doc-header { text-align: center; border-bottom: 2px solid #0f766e; padding-bottom: 8px; margin-bottom: 12px; }
        .doc-title { font-size: 15px; font-weight: bold; color: #0f766e; letter-spacing: 0.5px; text-transform: uppercase; margin: 0; }
        .doc-subtitle { font-size: 11px; font-weight: bold; margin: 3px 0 0; }
        .doc-meta { font-size: 8.5px; color: #4b5563; margin: 2px 0 0; }
        .doc-address { font-size: 8.5px; color: #4b5563; margin: 3px auto 0; max-width: 70%; }

        .summary { width: 100%; margin-bottom: 12px; border-collapse: collapse; }
        .summary td { width: 25%; border: 1px solid #d1d5db; background: #f0fdfa; padding: 6px 8px; text-align: center; }
        .summary .label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.4px; color: #6b7280; }
        .summary .value { font-size: 11px; font-weight: bold; color: #111827; padding-top: 2px; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #0f766e; color: #fff; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.3px; padding: 5px 6px; border: 0.5px solid #115e59; }
        table.data td { padding: 4px 6px; border: 0.5px solid #d1d5db; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f9fafb; }
        .right { text-align: right; }
        .center { text-align: center; }
        .mono { font-family: monospace; font-size: 8.5px; }
        .muted { color: #6b7280; }
        .neg { color: #dc2626; }
        .pos { color: #16a34a; }
        .empty { text-align: center; color: #6b7280; padding: 24px 6px; }
        .reason { font-style: italic; color: #6b7280; font-size: 8px; }
    </style>
</head>
<body>
    <div class="doc-header">
        <p class="doc-title">{{ $title }}</p>
        <p class="doc-subtitle">Laporan {{ $tab_label }} · {{ $period_label }}</p>
        <p class="doc-meta">Dicetak: {{ $generated_at }}</p>
        <p class="doc-address">{{ $address }}</p>
    </div>

    <table class="summary">
        <tr>
            <td><div class="label">Total Revenue</div><div class="value">{{ $rp($summary['total_revenue']) }}</div></td>
            <td><div class="label">Total Profit</div><div class="value">{{ $rp($summary['total_profit']) }}</div></td>
            <td><div class="label">Item Terjual</div><div class="value">{{ $num($summary['total_items']) }}</div></td>
            <td><div class="label">Total Transaksi</div><div class="value">{{ $num($summary['total_transactions']) }}</div></td>
        </tr>
    </table>

    @if ($tab === 'product')
        <table class="data">
            <thead>
                <tr>
                    <th style="width:11%">Kode</th>
                    <th style="width:27%">Nama Produk</th>
                    <th style="width:14%">Kategori</th>
                    <th style="width:12%">Merek</th>
                    <th class="right" style="width:8%">Qty</th>
                    <th class="right" style="width:11%">Revenue</th>
                    <th class="right" style="width:11%">Profit</th>
                    <th class="right" style="width:6%">Stok</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td class="mono">{{ $r['code'] }}</td>
                        <td>{{ $r['name'] }}</td>
                        <td class="muted">{{ $r['category'] ?? '—' }}</td>
                        <td class="muted">{{ $r['brand'] ?? '—' }}</td>
                        <td class="right">{{ $num($r['qty']) }}</td>
                        <td class="right">{{ $rp($r['revenue']) }}</td>
                        <td class="right {{ $r['profit'] < 0 ? 'neg' : 'pos' }}">{{ $rp($r['profit']) }}</td>
                        <td class="right">{{ $num($r['stock']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">Tidak ada data produk untuk periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($tab === 'brand')
        <table class="data">
            <thead>
                <tr>
                    <th style="width:36%">Merek</th>
                    <th class="right" style="width:14%">Qty</th>
                    <th class="right" style="width:18%">Revenue</th>
                    <th class="right" style="width:18%">Profit</th>
                    <th class="right" style="width:14%">Stok</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td>{{ $r['name'] }}</td>
                        <td class="right">{{ $num($r['qty']) }}</td>
                        <td class="right">{{ $rp($r['revenue']) }}</td>
                        <td class="right {{ $r['profit'] < 0 ? 'neg' : 'pos' }}">{{ $rp($r['profit']) }}</td>
                        <td class="right">{{ $num($r['stock']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Tidak ada data merek untuk periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($tab === 'payment')
        <table class="data">
            <thead>
                <tr>
                    <th style="width:34%">Metode</th>
                    <th class="right" style="width:20%">Transaksi</th>
                    <th class="right" style="width:26%">Revenue</th>
                    <th class="right" style="width:20%">Persentase</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td>{{ $r['label'] }}</td>
                        <td class="right">{{ $num($r['transactions']) }}</td>
                        <td class="right">{{ $rp($r['revenue']) }}</td>
                        <td class="right">{{ $r['percentage'] }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Tidak ada transaksi untuk periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($tab === 'stock')
        <table class="data">
            <thead>
                <tr>
                    <th class="right" style="width:5%">#</th>
                    <th style="width:14%">Kode</th>
                    <th style="width:25%">Produk</th>
                    <th style="width:14%">Kategori</th>
                    <th class="right" style="width:11%">Qty Terjual</th>
                    <th class="right" style="width:16%">Revenue</th>
                    <th class="right" style="width:15%">Stok Sisa</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $i => $r)
                    <tr>
                        <td class="right">{{ $i + 1 }}</td>
                        <td class="mono">{{ $r['code'] }}</td>
                        <td>{{ $r['name'] }}</td>
                        <td class="muted">{{ $r['category'] ?? '—' }}</td>
                        <td class="right">{{ $num($r['qty_sold']) }}</td>
                        <td class="right">{{ $rp($r['revenue']) }}</td>
                        <td class="right">{{ $r['stock_remaining'] === 0 ? 'HABIS' : $num($r['stock_remaining']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="empty">Tidak ada penjualan untuk periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($tab === 'returns')
        <table class="data">
            <thead>
                <tr>
                    <th style="width:12%">No. Return</th>
                    <th style="width:14%">Tanggal</th>
                    <th style="width:12%">Transaksi</th>
                    <th style="width:13%">Kasir</th>
                    <th style="width:23%">Barang &amp; Alasan</th>
                    <th class="right" style="width:7%">Qty</th>
                    <th class="right" style="width:11%">Nilai</th>
                    <th class="center" style="width:8%">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $r)
                    <tr>
                        <td class="mono">{{ $r['return_number'] }}</td>
                        <td>{{ $fmtDateTime($r['created_at']) }}</td>
                        <td class="mono">{{ $r['invoice_number'] }}</td>
                        <td>{{ $r['cashier_name'] }}</td>
                        <td>
                            @foreach ($r['items'] as $it)
                                <div>{{ $it['quantity'] }}× {{ $it['product_name'] }}</div>
                            @endforeach
                            <div class="reason">Alasan: {{ $r['reason'] }}</div>
                        </td>
                        <td class="right">{{ $num($r['total_qty']) }}</td>
                        <td class="right neg">{{ $rp($r['total_value']) }}</td>
                        <td class="center">{{ $r['status'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="empty">Tidak ada transaksi return pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
