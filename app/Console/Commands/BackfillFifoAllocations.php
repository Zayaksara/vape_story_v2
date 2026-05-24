<?php

namespace App\Console\Commands;

use App\Models\Batch;
use App\Models\SaleItem;
use App\Models\SaleItemBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillFifoAllocations extends Command
{
    protected $signature = 'fifo:backfill
                            {--dry : Hanya tampilkan apa yg akan di-backfill, tanpa menulis ke DB}
                            {--force : Lewati konfirmasi}';

    protected $description = 'Buat alokasi sintetis sale_item_batches untuk sale legacy (sebelum FIFO aktif). Pakai AVG cost batch produk saat itu sebagai approximasi HPP.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');

        // Cari semua sale_items yang BELUM punya alokasi.
        $orphanItems = SaleItem::query()
            ->with('sale')
            ->whereDoesntHave('allocations')
            ->get();

        if ($orphanItems->isEmpty()) {
            $this->info('Tidak ada sale_item legacy. Semua sale sudah punya alokasi FIFO.');
            return self::SUCCESS;
        }

        $this->warn(sprintf(
            'Ditemukan %d sale_item legacy (dari %d sale). %s',
            $orphanItems->count(),
            $orphanItems->pluck('sale_id')->unique()->count(),
            $dry ? '[DRY-RUN — tidak menulis ke DB]' : ''
        ));

        if (! $dry && ! $this->option('force')) {
            if (! $this->confirm('Lanjutkan backfill? Alokasi sintetis akan dibuat dengan flag is_synthetic=true.')) {
                $this->line('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        $bar = $this->output->createProgressBar($orphanItems->count());
        $bar->start();

        $created = 0;
        $skipped = 0;
        $rowsForReport = [];

        $work = function () use ($orphanItems, $dry, $bar, &$created, &$skipped, &$rowsForReport) {
            foreach ($orphanItems as $item) {
                $saleCreatedAt = $item->sale?->created_at ?? now();

                // Cari AVG cost batch produk yg dibuat <= tanggal sale.
                // Kalau tidak ada (sale lebih lama dari batch terlama), pakai AVG semua batch produk.
                $avgCost = (float) Batch::query()
                    ->where('product_id', $item->product_id)
                    ->where('created_at', '<=', $saleCreatedAt)
                    ->avg('cost_price');

                if ($avgCost <= 0) {
                    $avgCost = (float) Batch::query()
                        ->where('product_id', $item->product_id)
                        ->avg('cost_price');
                }

                // Cari salah satu batch produk sebagai "tempat parkir" untuk FK batch_id.
                // Wajib ada karena kolom batch_id NOT NULL FK ke batches.
                $referenceBatch = Batch::query()
                    ->where('product_id', $item->product_id)
                    ->orderBy('created_at')
                    ->first();

                if (! $referenceBatch || $avgCost <= 0) {
                    $skipped++;
                    $bar->advance();
                    $rowsForReport[] = [
                        'sale_item_id' => $item->id,
                        'product_id'   => (string) $item->product_id,
                        'status'       => 'SKIP — tidak ada batch referensi / avg cost 0',
                    ];
                    continue;
                }

                // unit_price dari saleItem (item.total/qty mendekati harga aktual yg dibayar).
                $unitPrice = (int) $item->quantity > 0
                    ? round((float) $item->total / (int) $item->quantity, 2)
                    : (float) $item->unit_price;

                if (! $dry) {
                    SaleItemBatch::create([
                        'sale_item_id'      => $item->id,
                        'batch_id'          => $referenceBatch->id,
                        'quantity'          => (int) $item->quantity,
                        'unit_cost'         => round($avgCost, 2),
                        'unit_price'        => $unitPrice,
                        'is_promo'          => false,
                        'returned_quantity' => 0,
                        'is_synthetic'      => true,
                    ]);
                }

                $created++;
                $rowsForReport[] = [
                    'sale_item_id' => $item->id,
                    'product_id'   => (string) $item->product_id,
                    'qty'          => $item->quantity,
                    'unit_cost'    => round($avgCost, 2),
                    'unit_price'   => $unitPrice,
                ];
                $bar->advance();
            }
        };

        if ($dry) {
            // Dry-run: jalan tanpa membuka transaksi. Karena $dry=true di loop sudah
            // mencegah SaleItemBatch::create, tidak ada efek samping di DB.
            $work();
        } else {
            DB::transaction($work, attempts: 1);
        }

        $bar->finish();
        $this->newLine(2);

        $this->info(sprintf(
            '%s %d alokasi sintetis. %d di-skip.',
            $dry ? '[DRY] Akan dibuat' : 'Berhasil dibuat',
            $created,
            $skipped,
        ));

        if ($skipped > 0) {
            $this->warn('Item yg di-skip kemungkinan: produk sudah dihapus, atau tidak ada batch sama sekali.');
        }

        if ($this->getOutput()->isVerbose()) {
            $this->table(
                ['sale_item_id', 'product_id', 'qty / status', 'unit_cost', 'unit_price'],
                array_map(fn ($r) => [
                    $r['sale_item_id'],
                    substr($r['product_id'] ?? '-', 0, 8),
                    $r['qty'] ?? ($r['status'] ?? '-'),
                    $r['unit_cost'] ?? '-',
                    $r['unit_price'] ?? '-',
                ], $rowsForReport),
            );
        } else {
            $this->line('Tip: jalankan dengan -v untuk detail per item.');
        }

        return self::SUCCESS;
    }
}
