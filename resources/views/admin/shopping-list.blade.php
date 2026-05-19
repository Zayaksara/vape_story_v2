<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Belanja - Vape Story</title>
    
    <style>
        /* Hilangkan header/footer browser (tanggal/URL/page#) saat Ctrl+P.
           margin: 0 di @page mengeliminasi area utk header/footer otomatis browser.
           Padding konten dipindah ke body @media print. */
        @page {
            size: A4;
            margin: 0;
        }

        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1e293b;
            margin: 0;
            padding: 18px;
            font-size: 12px;
            line-height: 1.5;
            background: #f9fafb;
        }
        .sheet {
            background: #fff;
            max-width: 900px;
            margin: 0 auto;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(15,23,42,0.08);
        }
        .header {
            border-bottom: 3px solid #14b8a6;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #0d9488;
            font-size: 22px;
        }
        .header .meta {
            color: #6b7280;
            font-size: 11px;
            margin-top: 4px;
        }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
            padding: 10px 12px;
            background: #ecfeff;
            border: 1px solid #99f6e4;
            border-radius: 8px;
        }
        .btn {
            cursor: pointer;
            border: none;
            border-radius: 6px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: opacity 0.15s;
        }
        .btn:hover { opacity: 0.9; }
        .btn-primary { background: #14b8a6; color: #fff; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-secondary { background: #fff; color: #334155; border: 1px solid #e5e7eb; }
        .btn-danger { background: #fee2e2; color: #dc2626; padding: 4px 8px; font-size: 11px; }
        .btn-small { padding: 4px 8px; font-size: 11px; }

        .section {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .section-title {
            background: #ecfeff;
            color: #0d9488;
            padding: 8px 12px;
            font-size: 14px;
            font-weight: bold;
            border-left: 4px solid #14b8a6;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .section-title .count {
            background: #14b8a6;
            color: #fff;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-left: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        thead tr { background: #f9fafb; }
        th {
            text-align: left;
            padding: 8px 10px;
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
            letter-spacing: 0.05em;
        }
        td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
            color: #334155;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .code {
            font-family: 'Courier New', monospace;
            color: #6b7280;
            font-size: 10px;
        }

        /* Editable inputs */
        .qty-input {
            width: 70px;
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 11px;
            text-align: right;
            font-family: inherit;
            color: #334155;
        }
        .qty-input:focus {
            outline: 2px solid #14b8a6;
            border-color: #14b8a6;
        }
        .text-input {
            width: 100%;
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 11px;
            font-family: inherit;
            color: #334155;
        }
        .text-input:focus {
            outline: 2px solid #14b8a6;
            border-color: #14b8a6;
        }

        .empty {
            padding: 20px;
            text-align: center;
            color: #9ca3af;
            font-style: italic;
        }
        .filter-info {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 16px;
            font-size: 11px;
            color: #334155;
        }
        .filter-info strong { color: #0d9488; }

        .finalized .toolbar,
        .finalized .section-title .count,
        .finalized .filter-info { display: none; }

        /* ============ MODAL ============ */
        #modalBackdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(2px);
            z-index: 99;
        }
        #addProductModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.25);
            width: 92%;
            max-width: 560px;
            max-height: 80vh;
            z-index: 100;
            flex-direction: column;
            overflow: hidden;
        }
        #addProductModal.open,
        #modalBackdrop.open { display: flex; }
        .modal-header {
            padding: 14px 18px;
            background: #ecfeff;
            border-bottom: 1px solid #99f6e4;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
            color: #0d9488;
            font-size: 14px;
            font-weight: bold;
        }
        .modal-close {
            cursor: pointer;
            border: none;
            background: transparent;
            font-size: 20px;
            color: #6b7280;
            padding: 0 4px;
        }
        .modal-search {
            padding: 12px 18px;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-search input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 12px;
            font-family: inherit;
        }
        .modal-search input:focus {
            outline: 2px solid #14b8a6;
            border-color: #14b8a6;
        }
        .modal-list {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }
        .product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            border-radius: 6px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.1s;
        }
        .product-item:hover { background: #f0fdfa; }
        .product-item .info { flex: 1; }
        .product-item .name {
            font-weight: 600;
            color: #1e293b;
            font-size: 12px;
            margin-bottom: 2px;
        }
        .product-item .meta {
            font-size: 10px;
            color: #6b7280;
        }
        .product-item .stock-badge {
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 8px;
            white-space: nowrap;
        }
        .stock-badge.danger { background: #fee2e2; color: #dc2626; }
        .stock-badge.warning { background: #fef3c7; color: #d97706; }
        .stock-badge.success { background: #dcfce7; color: #16a34a; }
        .modal-empty {
            padding: 30px 20px;
            text-align: center;
            color: #9ca3af;
            font-style: italic;
            font-size: 12px;
        }
        .modal-footer {
            padding: 10px 18px;
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            font-size: 11px;
            color: #6b7280;
        }

        /* ============ PRINT STYLES ============ */
        @media print {
            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .sheet {
                box-shadow: none;
                padding: 12mm !important;     /* margin konten manual karena @page margin: 0 */
                max-width: 100%;
                border-radius: 0;
            }
            .no-print,
            .toolbar,
            .add-row-btn,
            .delete-btn,
            #addProductModal,
            #modalBackdrop,
            button { display: none !important; }

            /* Buang border input & jadikan teks polos */
            .qty-input,
            .text-input {
                border: none !important;
                outline: none !important;
                background: transparent !important;
                padding: 0 !important;
                color: #1e293b !important;
                width: auto !important;
            }
            .qty-input { text-align: right; }

            /* Sembunyikan kolom aksi saat print */
            .action-col { display: none !important; }
        }
    </style>
</head>
<body>
<div class="sheet" id="sheet">

    <div class="header">
        <h1>Daftar Belanja - Vape Story</h1>
        <p class="meta">
            Dibuat: {{ $generated_at }}
            @if($period_label)
                · Periode penjualan: <strong>{{ $period_label }}</strong>
            @endif
        </p>
    </div>

    <!-- Toolbar (tidak ikut print, hilang setelah finalisasi) -->
    <div class="toolbar no-print">
        <button class="btn btn-secondary" onclick="openAddModal()">+ Tambah Produk</button>
        <button class="btn btn-secondary" onclick="window.print()">🖨️ Print / Save PDF</button>
        <button class="btn btn-primary" onclick="finalize()">✓ Finalisasi</button>
        <button class="btn btn-secondary" onclick="undoFinalize()" id="undoBtn" style="display:none;">↺ Edit Lagi</button>
    </div>

    <span class="no-print" style="margin-bottom: 10px; display: block; text-align: center; font-size:20px; border: 1px solid #e5e7eb; padding: 10px; border-radius: 5px;">
            💡 <strong>TEKAN CTRL + P</strong> untuk mencetak atau simpan sebagai PDF | <strong>INGETT</strong>
    </span>

    @if(!empty($filter_categories))
        <div class="filter-info no-print">
            <strong>Filter Kategori:</strong> {{ implode(', ', $filter_categories) }}
        </div>
    @endif

    {{-- ============ MODE EDIT (2 section terpisah) ============ --}}
    <div id="editMode">

        @if($include_out_of_stock)
        <div class="section">
            <div class="section-title">
                <span>🚨 Stok Habis (Wajib Restok) <span class="count">{{ count($out_of_stock) }} produk</span></span>
            </div>
            <table id="outTable" data-section="Stok Habis">
                <thead>
                    <tr>
                        <th style="width: 90px;">Kode</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Merek</th>
                        <th class="text-right" style="width: 100px;">Qty Order</th>
                        <th class="action-col" style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($out_of_stock as $row)
                        <tr>
                            <td class="code">{{ $row['code'] }}</td>
                            <td><strong>{{ $row['name'] }}</strong></td>
                            <td>{{ $row['category'] ?? '—' }}</td>
                            <td>{{ $row['brand'] ?? '—' }}</td>
                            <td class="text-right"><input type="number" class="qty-input" min="0" value="1" /></td>
                            <td class="action-col text-center"><button class="btn-danger btn no-print delete-btn" onclick="deleteRow(this)">×</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if(count($out_of_stock) === 0)
                <div class="empty">Tidak ada produk stok habis — gunakan tombol "Tambah Produk" jika perlu.</div>
            @endif
        </div>
        @endif

        @if($include_top_selling)
        <div class="section">
            <div class="section-title">
                <span><i class="fa-light fa-fire"></i> Stok Terlaris (Pertimbangkan Restok) <span class="count">{{ count($top_selling) }} produk</span></span>
            </div>
            <table id="topTable" data-section="Stok Terlaris">
                <thead>
                    <tr>
                        <th style="width: 40px;">#</th>
                        <th style="width: 90px;">Kode</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Brand</th>
                        <th class="text-right">Terjual</th>
                        <th class="text-right">Stok Sisa</th>
                        <th class="text-right" style="width: 100px;">Qty Order</th>
                        <th class="action-col" style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($top_selling as $i => $row)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="code">{{ $row['code'] }}</td>
                            <td><strong>{{ $row['name'] }}</strong></td>
                            <td>{{ $row['category'] ?? '—' }}</td>
                            <td>{{ $row['brand'] ?? '—' }}</td>
                            <td class="text-right">{{ number_format($row['qty_sold'], 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($row['stock_remaining'], 0, ',', '.') }}</td>
                            <td class="text-right"><input type="number" class="qty-input" min="0" value="1" /></td>
                            <td class="action-col text-center"><button class="btn-danger btn no-print delete-btn" onclick="deleteRow(this)">×</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if(count($top_selling) === 0)
                <div class="empty">Tidak ada data — gunakan tombol "Tambah Produk" jika perlu.</div>
            @endif
        </div>
        @endif

        {{-- ============ TAMBAHAN MANUAL (dari modal) ============ --}}
        <div class="section" id="manualSection" style="display: none;">
            <div class="section-title">
                <span>➕ Tambahan Manual <span class="count" id="manualCount">0 produk</span></span>
            </div>
            <table id="manualTable" data-section="Tambahan Manual">
                <thead>
                    <tr>
                        <th style="width: 90px;">Kode</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th class="text-right">Stok Sisa</th>
                        <th class="text-right" style="width: 100px;">Qty Order</th>
                        <th class="action-col" style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    {{-- ============ MODE FINALIZED (1 tabel gabungan) ============ --}}
    <div id="finalMode" style="display:none;">
        <table id="finalTable">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th style="width: 100px;">Kode</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th class="text-right" style="width: 100px;">Qty Order</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

</div>

{{-- ============ MODAL: Tambah Produk ============ --}}
<div id="modalBackdrop" onclick="closeAddModal()"></div>
<div id="addProductModal" role="dialog" aria-modal="true">
    <div class="modal-header">
        <h3>+ Tambah Produk ke Daftar Belanja</h3>
        <button class="modal-close" onclick="closeAddModal()">×</button>
    </div>
    <div class="modal-search">
        <input
            type="text"
            id="productSearch"
            placeholder="Cari produk berdasarkan nama, kode, atau kategori..."
            oninput="filterProducts()"
            autocomplete="off"
        />
    </div>
    <div class="modal-list" id="productList"></div>
    <div class="modal-footer" id="modalFooter">Klik produk untuk menambahkannya ke daftar belanja.</div>
</div>

<script>
    // Semua produk aktif (dari controller)
    const ALL_PRODUCTS = @json($all_products ?? []);

    // Track produk yang sudah ditambahkan ke "Tambahan Manual" agar tidak dobel
    const addedManualCodes = new Set();

    function openAddModal() {
        document.getElementById('modalBackdrop').classList.add('open');
        document.getElementById('addProductModal').classList.add('open');
        document.getElementById('productSearch').value = '';
        renderProductList(ALL_PRODUCTS);
        setTimeout(() => document.getElementById('productSearch').focus(), 50);
    }

    function closeAddModal() {
        document.getElementById('modalBackdrop').classList.remove('open');
        document.getElementById('addProductModal').classList.remove('open');
    }

    function filterProducts() {
        const q = document.getElementById('productSearch').value.toLowerCase().trim();
        if (!q) return renderProductList(ALL_PRODUCTS);
        const filtered = ALL_PRODUCTS.filter(p =>
            (p.name || '').toLowerCase().includes(q) ||
            (p.code || '').toLowerCase().includes(q) ||
            (p.category || '').toLowerCase().includes(q) ||
            (p.brand || '').toLowerCase().includes(q),
        );
        renderProductList(filtered);
    }

    function renderProductList(list) {
        const wrap = document.getElementById('productList');
        if (!list.length) {
            wrap.innerHTML = '<div class="modal-empty">Tidak ada produk ditemukan.</div>';
            return;
        }
        wrap.innerHTML = list.map(p => {
            const s = p.stock || 0;
            const cls = s === 0 ? 'danger' : (s < 10 ? 'warning' : 'success');
            const label = s === 0 ? 'HABIS' : s;
            const isAdded = addedManualCodes.has(p.code);
            return `
                <div class="product-item" onclick="addProductToList('${escapeAttr(p.code)}')">
                    <div class="info">
                        <div class="name">${escapeHtml(p.name)} ${isAdded ? '<span style="color:#16a34a; font-size:10px;">✓ ditambahkan</span>' : ''}</div>
                        <div class="meta">${escapeHtml(p.code || '—')} · ${escapeHtml(p.category || '—')} · ${escapeHtml(p.brand || '—')}</div>
                    </div>
                    <span class="stock-badge ${cls}">${label}</span>
                </div>
            `;
        }).join('');
    }

    function addProductToList(code) {
        const product = ALL_PRODUCTS.find(p => p.code === code);
        if (!product) return;

        // Cegah duplikat di section "Tambahan Manual"
        if (addedManualCodes.has(code)) {
            // toggle: jika sudah ada, fokuskan input qty di row tsb
            const existing = document.querySelector(`#manualTable tr[data-code="${cssEscape(code)}"] .qty-input`);
            if (existing) { existing.focus(); existing.select(); }
            return;
        }
        addedManualCodes.add(code);

        const tbody = document.querySelector('#manualTable tbody');
        const tr = document.createElement('tr');
        tr.setAttribute('data-code', code);
        const s = product.stock || 0;
        const cls = s === 0 ? 'badge-danger' : (s < 10 ? 'badge-warning' : 'badge-success');
        const stockLabel = s === 0 ? 'HABIS' : s;
        tr.innerHTML = `
            <td class="code">${escapeHtml(product.code || '')}</td>
            <td><strong>${escapeHtml(product.name)}</strong></td>
            <td>${escapeHtml(product.category || '—')}</td>
            <td class="text-right"><span class="${cls}" style="padding:2px 8px; border-radius:10px; font-size:10px; font-weight:bold;">${stockLabel}</span></td>
            <td class="text-right"><input type="number" class="qty-input" min="0" value="1" /></td>
            <td class="action-col text-center"><button class="btn-danger btn no-print delete-btn" onclick="deleteManualRow(this, '${escapeAttr(code)}')">×</button></td>
        `;
        tbody.appendChild(tr);

        // Tampilkan section manual & update counter
        document.getElementById('manualSection').style.display = 'block';
        document.getElementById('manualCount').textContent = addedManualCodes.size + ' produk';

        // Refresh list di modal supaya tanda "ditambahkan" muncul
        filterProducts();

        // Auto-focus qty baru
        tr.querySelector('.qty-input').focus();
        tr.querySelector('.qty-input').select();
    }

    function deleteManualRow(btn, code) {
        addedManualCodes.delete(code);
        btn.closest('tr').remove();
        document.getElementById('manualCount').textContent = addedManualCodes.size + ' produk';
        if (addedManualCodes.size === 0) {
            document.getElementById('manualSection').style.display = 'none';
        }
    }

    function cssEscape(s) {
        return String(s).replace(/"/g, '\\"');
    }
    function escapeAttr(s) {
        return String(s ?? '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    // ESC menutup modal
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') closeAddModal();
    });

    // ============ Legacy add row (tidak terpakai lagi, di-keep untuk kompatibilitas) ============
    function addRow(tableId) {
        const tbody = document.querySelector('#' + tableId + ' tbody');
        const colCount = document.querySelectorAll('#' + tableId + ' thead th').length;
        const tr = document.createElement('tr');

        if (tableId === 'outTable') {
            tr.innerHTML = `
                <td><input type="text" class="text-input" placeholder="Kode" /></td>
                <td><input type="text" class="text-input" placeholder="Nama produk" /></td>
                <td><input type="text" class="text-input" placeholder="Kategori" /></td>
                <td><input type="text" class="text-input" placeholder="Merek" /></td>
                <td class="text-right"><input type="number" class="qty-input" min="0" value="1" /></td>
                <td class="action-col text-center"><button class="btn-danger btn no-print delete-btn" onclick="deleteRow(this)">×</button></td>
            `;
        } else {
            tr.innerHTML = `
                <td>+</td>
                <td><input type="text" class="text-input" placeholder="Kode" /></td>
                <td><input type="text" class="text-input" placeholder="Nama produk" /></td>
                <td><input type="text" class="text-input" placeholder="Kategori" /></td>
                <td class="text-right">—</td>
                <td class="text-right">—</td>
                <td class="text-right"><input type="number" class="qty-input" min="0" value="1" /></td>
                <td class="action-col text-center"><button class="btn-danger btn no-print delete-btn" onclick="deleteRow(this)">×</button></td>
            `;
        }
        tbody.appendChild(tr);
        tr.querySelector('input').focus();
    }

    function deleteRow(btn) {
        const tr = btn.closest('tr');
        tr.remove();
    }

    /**
     * Kumpulkan semua baris (qty > 0) dari kedua tabel jadi list:
     * { code, name, category, qty }
     */
    function collectItems() {
        const items = [];
        document.querySelectorAll('#editMode table').forEach(table => {
            const id = table.id;
            const heads = Array.from(table.querySelectorAll('thead th')).map(t => t.textContent.trim().toLowerCase());
            const codeIdx = heads.findIndex(h => h === 'kode');
            const nameIdx = heads.findIndex(h => h.includes('nama'));
            const catIdx = heads.findIndex(h => h.includes('kategori'));

            table.querySelectorAll('tbody tr').forEach(tr => {
                const cells = tr.querySelectorAll('td');
                const qtyInput = tr.querySelector('.qty-input');
                if (!qtyInput) return;
                const qty = parseInt(qtyInput.value, 10) || 0;
                if (qty <= 0) return;

                const readCell = idx => {
                    if (idx < 0 || !cells[idx]) return '';
                    const input = cells[idx].querySelector('input');
                    return (input ? input.value : cells[idx].textContent).trim();
                };

                items.push({
                    code: readCell(codeIdx),
                    name: readCell(nameIdx),
                    category: readCell(catIdx),
                    qty,
                });
            });
        });
        return items;
    }

    function finalize() {
        const items = collectItems();
        if (!items.length) {
            alert('Tidak ada item dengan Qty Order > 0. Isi Qty terlebih dahulu.');
            return;
        }
        const tbody = document.querySelector('#finalTable tbody');
        tbody.innerHTML = '';
        items.forEach((it, i) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${i + 1}</td>
                <td class="code">${escapeHtml(it.code)}</td>
                <td><strong>${escapeHtml(it.name)}</strong></td>
                <td>${escapeHtml(it.category || '—')}</td>
                <td class="text-right"><strong>${it.qty}</strong></td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('editMode').style.display = 'none';
        document.getElementById('finalMode').style.display = 'block';
        document.getElementById('sheet').classList.add('finalized');
        document.getElementById('undoBtn').style.display = 'inline-flex';
    }

    function undoFinalize() {
        document.getElementById('editMode').style.display = 'block';
        document.getElementById('finalMode').style.display = 'none';
        document.getElementById('sheet').classList.remove('finalized');
        document.getElementById('undoBtn').style.display = 'none';
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
        }[c]));
    }
</script>
</body>
</html>
