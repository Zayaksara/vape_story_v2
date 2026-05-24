<script setup lang="ts">
import { ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Save } from 'lucide-vue-next'
import AdminLayout from '@/layouts/admin/AdminLayout.vue'

defineOptions({
  layout: (h: any, page: any) => h(AdminLayout, {}, () => page),
})

interface OpeningPayload {
  id: number | null
  as_of_date: string | null
  cash: number; bank: number; inventory_value: number; fixed_assets: number
  accounts_payable: number; other_liabilities: number
  equity: number; retained_earnings: number; notes: string | null
}

const props = defineProps<{ opening: OpeningPayload }>()

const form = useForm({
  as_of_date:        props.opening.as_of_date ?? new Date().toISOString().slice(0, 10),
  cash:              props.opening.cash,
  bank:              props.opening.bank,
  inventory_value:   props.opening.inventory_value,
  fixed_assets:      props.opening.fixed_assets,
  accounts_payable:  props.opening.accounts_payable,
  other_liabilities: props.opening.other_liabilities,
  equity:            props.opening.equity,
  retained_earnings: props.opening.retained_earnings,
  notes:             props.opening.notes ?? '',
})

const flash = ref<string>('')

function submit() {
  form.post('/admin/__audit/opening-balance', {
    preserveScroll: true,
    onSuccess: () => { flash.value = 'Saldo awal tersimpan.' },
  })
}

function fmt(n: number): string {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0)
}

const totalAssets = () =>
  Number(form.cash) + Number(form.bank) + Number(form.inventory_value) + Number(form.fixed_assets)

const totalLiabEquity = () =>
  Number(form.accounts_payable) + Number(form.other_liabilities) +
  Number(form.equity) + Number(form.retained_earnings)

const diff = () => totalAssets() - totalLiabEquity()
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-4 p-6">
    <button class="flex items-center gap-1.5 text-sm font-medium" style="color:#64748b;" @click="router.get('/admin/__audit')">
      <ArrowLeft class="h-4 w-4" /> Kembali ke Audit
    </button>

    <div class="rounded-lg border bg-white p-5" style="border-color:#e2e8f0;">
      <h1 class="text-base font-bold" style="color:#0f172a;">Saldo Awal (Opening Balance)</h1>
      <p class="text-xs" style="color:#64748b;">
        Masukkan saldo dari pembukuan terakhir sebelum sistem ini dipakai. Sistem akan menggunakan ini sebagai
        titik awal Neraca. Semua transaksi penjualan setelah tanggal cutoff dihitung otomatis.
      </p>

      <div v-if="flash" class="mt-3 rounded-md p-2 text-xs font-semibold" style="background:#ecfdf5; color:#047857;">
        {{ flash }}
      </div>

      <form class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
        <div class="md:col-span-2">
          <label class="text-xs font-semibold" style="color:#475569;">Tanggal Cutoff Pembukuan Lama</label>
          <input v-model="form.as_of_date" type="date" required
                 class="mt-1 h-9 w-full rounded-md border px-2 text-sm"
                 style="border-color:#e2e8f0;" />
        </div>

        <div class="md:col-span-2 mt-2 text-xs font-bold uppercase tracking-wide" style="color:#0d9488;">Aset</div>
        <div><label class="text-xs" style="color:#475569;">Kas (tunai)</label>
          <input v-model.number="form.cash" type="number" min="0" class="mt-1 h-9 w-full rounded-md border px-2 text-sm tabular-nums" style="border-color:#e2e8f0;" /></div>
        <div><label class="text-xs" style="color:#475569;">Bank / E-Wallet / QRIS</label>
          <input v-model.number="form.bank" type="number" min="0" class="mt-1 h-9 w-full rounded-md border px-2 text-sm tabular-nums" style="border-color:#e2e8f0;" /></div>
        <div><label class="text-xs" style="color:#475569;">Nilai Persediaan (referensi)</label>
          <input v-model.number="form.inventory_value" type="number" min="0" class="mt-1 h-9 w-full rounded-md border px-2 text-sm tabular-nums" style="border-color:#e2e8f0;" /></div>
        <div><label class="text-xs" style="color:#475569;">Aset Tetap (etalase, peralatan)</label>
          <input v-model.number="form.fixed_assets" type="number" min="0" class="mt-1 h-9 w-full rounded-md border px-2 text-sm tabular-nums" style="border-color:#e2e8f0;" /></div>

        <div class="md:col-span-2 mt-2 text-xs font-bold uppercase tracking-wide" style="color:#0d9488;">Kewajiban</div>
        <div><label class="text-xs" style="color:#475569;">Hutang Usaha (ke supplier)</label>
          <input v-model.number="form.accounts_payable" type="number" min="0" class="mt-1 h-9 w-full rounded-md border px-2 text-sm tabular-nums" style="border-color:#e2e8f0;" /></div>
        <div><label class="text-xs" style="color:#475569;">Hutang Lain (pinjaman, dll)</label>
          <input v-model.number="form.other_liabilities" type="number" min="0" class="mt-1 h-9 w-full rounded-md border px-2 text-sm tabular-nums" style="border-color:#e2e8f0;" /></div>

        <div class="md:col-span-2 mt-2 text-xs font-bold uppercase tracking-wide" style="color:#0d9488;">Ekuitas</div>
        <div><label class="text-xs" style="color:#475569;">Modal Pemilik</label>
          <input v-model.number="form.equity" type="number" min="0" class="mt-1 h-9 w-full rounded-md border px-2 text-sm tabular-nums" style="border-color:#e2e8f0;" /></div>
        <div><label class="text-xs" style="color:#475569;">Laba Ditahan</label>
          <input v-model.number="form.retained_earnings" type="number" class="mt-1 h-9 w-full rounded-md border px-2 text-sm tabular-nums" style="border-color:#e2e8f0;" /></div>

        <div class="md:col-span-2">
          <label class="text-xs" style="color:#475569;">Catatan</label>
          <textarea v-model="form.notes" rows="2" class="mt-1 w-full rounded-md border px-2 py-1.5 text-sm" style="border-color:#e2e8f0;" />
        </div>

        <!-- Live check -->
        <div class="md:col-span-2 mt-2 rounded-md border p-3 text-xs" style="border-color:#e2e8f0; background:#f8fafc;">
          <div class="flex justify-between"><span style="color:#475569;">Total Aset</span><span class="font-semibold tabular-nums">{{ fmt(totalAssets()) }}</span></div>
          <div class="flex justify-between"><span style="color:#475569;">Total Kewajiban + Ekuitas</span><span class="font-semibold tabular-nums">{{ fmt(totalLiabEquity()) }}</span></div>
          <div class="mt-1 flex justify-between border-t pt-1" style="border-color:#e2e8f0;">
            <span class="font-bold" :style="{ color: Math.abs(diff()) < 1 ? '#047857' : '#be123c' }">Selisih</span>
            <span class="font-bold tabular-nums" :style="{ color: Math.abs(diff()) < 1 ? '#047857' : '#be123c' }">{{ fmt(diff()) }}</span>
          </div>
          <p v-if="Math.abs(diff()) >= 1" class="mt-1 text-[10px] italic" style="color:#be123c;">
            Selisih harus 0 agar Neraca seimbang. Sesuaikan Modal atau Laba Ditahan.
          </p>
        </div>

        <div class="md:col-span-2 flex justify-end">
          <button type="submit" :disabled="form.processing"
                  class="flex items-center gap-1.5 rounded-md px-4 py-2 text-xs font-semibold text-white"
                  style="background:#14b8a6;">
            <Save class="h-3.5 w-3.5" /> Simpan Saldo Awal
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
