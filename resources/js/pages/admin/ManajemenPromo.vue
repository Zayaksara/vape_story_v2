<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import {
  Search, X, Plus, Pencil, Trash2, Eye,
  Tag, CheckCircle2, Clock, XCircle, Percent,
  CalendarDays, Package, Gift,
} from 'lucide-vue-next'

import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import {
  Select, SelectContent, SelectItem,
  SelectTrigger, SelectValue,
} from '@/components/ui/select'
import {
  Table, TableBody, TableCell, TableHead,
  TableHeader, TableRow,
} from '@/components/ui/table'
import {
  Sheet, SheetContent, SheetHeader,
  SheetTitle, SheetDescription, SheetFooter,
} from '@/components/ui/sheet'
import {
  Dialog, DialogContent, DialogHeader,
  DialogTitle, DialogDescription, DialogFooter,
} from '@/components/ui/dialog'
import {
  Tooltip, TooltipContent,
  TooltipProvider, TooltipTrigger,
} from '@/components/ui/tooltip'

import AdminLayout from '@/layouts/admin/AdminLayout.vue'

defineOptions({
  layout: (h: any, page: any) => h(AdminLayout, {}, () => page),
})

// ── Types ─────────────────────────────────────────────────────────────────────

type PromoType = 'percentage' | 'fixed' | 'bogo'
type PromoStatus = 'active' | 'scheduled' | 'expired' | 'inactive'
type TargetType = 'all' | 'specific'

interface PromoProduct {
  id: number | string
  name: string
  sku?: string | null
}

interface Promo {
  id: number | string
  code: string
  name: string
  description?: string
  type: PromoType
  value: number
  min_purchase: number
  max_discount: number | null
  usage_limit: number | null
  used_count: number
  start_date: string // YYYY-MM-DD
  end_date: string   // YYYY-MM-DD
  is_active: boolean
  target: TargetType
  product_ids: (number | string)[]
}

interface Props {
  promos?: Promo[]
  products?: PromoProduct[]
}
const props = withDefaults(defineProps<Props>(), {
  promos: () => [],
  products: () => [],
})

// ── State ─────────────────────────────────────────────────────────────────────

const promos = computed<Promo[]>(() => props.promos ?? [])
const isSubmitting = ref(false)

const search = ref('')
const statusFilter = ref<PromoStatus | 'all'>('all')
const typeFilter = ref<PromoType | 'all'>('all')

const selectedPromo = ref<Promo | null>(null)
const detailOpen = ref(false)

const formOpen = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const form = ref<Promo>(emptyPromo())

const deleteOpen = ref(false)
const promoToDelete = ref<Promo | null>(null)

function emptyPromo(): Promo {
  const today = new Date().toISOString().slice(0, 10)
  return {
    id: 0,
    code: '',
    name: '',
    description: '',
    type: 'percentage',
    value: 0,
    min_purchase: 0,
    max_discount: null,
    usage_limit: null,
    used_count: 0,
    start_date: today,
    end_date: today,
    is_active: true,
    target: 'all',
    product_ids: [],
  }
}

// ── Derived ───────────────────────────────────────────────────────────────────

function getStatus(p: Promo): PromoStatus {
  if (!p.is_active) return 'inactive'
  const today = new Date().toISOString().slice(0, 10)
  if (p.start_date > today) return 'scheduled'
  if (p.end_date < today)   return 'expired'
  return 'active'
}

const enrichedPromos = computed(() =>
  promos.value.map(p => ({ ...p, status: getStatus(p) })),
)

const hasActiveFilters = computed(() =>
  !!(search.value || statusFilter.value !== 'all' || typeFilter.value !== 'all'),
)

const filteredPromos = computed(() => {
  return enrichedPromos.value.filter(p => {
    const q = search.value.trim().toLowerCase()
    const matchSearch = !q ||
      p.code.toLowerCase().includes(q) ||
      p.name.toLowerCase().includes(q)
    const matchStatus = statusFilter.value === 'all' || p.status === statusFilter.value
    const matchType   = typeFilter.value === 'all' || p.type === typeFilter.value
    return matchSearch && matchStatus && matchType
  })
})

const stats = computed(() => {
  const all = enrichedPromos.value
  return {
    total:     all.length,
    active:    all.filter(p => p.status === 'active').length,
    scheduled: all.filter(p => p.status === 'scheduled').length,
    expired:   all.filter(p => p.status === 'expired').length,
  }
})

// ── Formatters ────────────────────────────────────────────────────────────────

function formatPrice(n: number): string {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
  }).format(n)
}

function formatDate(ymd: string): string {
  if (!ymd) return '—'
  const d = new Date(ymd + 'T00:00:00')
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function typeLabel(t: PromoType): string {
  return { percentage: 'Persentase', fixed: 'Potongan Tetap', bogo: 'Beli X Dapat Y' }[t]
}

function valueLabel(p: Promo): string {
  if (p.type === 'percentage') return `${p.value}%`
  if (p.type === 'fixed')      return formatPrice(p.value)
  return `Beli 2 Gratis ${p.value}`
}

function statusInfo(s: PromoStatus): { label: string; bg: string; color: string } {
  switch (s) {
    case 'active':    return { label: 'Aktif',       bg: 'var(--pos-bg-success)', color: 'var(--pos-success-text)' }
    case 'scheduled': return { label: 'Akan Datang', bg: 'var(--pos-bg-warning)', color: 'var(--pos-warning-text)' }
    case 'expired':   return { label: 'Berakhir',    bg: 'var(--pos-bg-danger)',  color: 'var(--pos-danger-text)' }
    case 'inactive':  return { label: 'Nonaktif',    bg: '#f1f5f9',               color: 'var(--pos-text-muted)' }
  }
}

function targetLabel(p: Promo): string {
  if (p.target === 'all') return 'Semua Produk'
  return `${p.product_ids.length} Produk Terpilih`
}

// ── CRUD actions (client-side; swap to Inertia later) ─────────────────────────

function openDetail(p: Promo) {
  selectedPromo.value = p
  detailOpen.value = true
}

function openCreate() {
  formMode.value = 'create'
  form.value = emptyPromo()
  formOpen.value = true
}

function openEdit(p: Promo) {
  formMode.value = 'edit'
  form.value = JSON.parse(JSON.stringify(p))
  formOpen.value = true
}

function buildPayload(p: Promo) {
  return {
    code:         p.code.trim().toUpperCase(),
    name:         p.name.trim(),
    description:  p.description ?? null,
    type:         p.type,
    value:        Number(p.value) || 0,
    min_purchase: Number(p.min_purchase) || 0,
    max_discount: p.max_discount === null || p.max_discount === undefined || (p.max_discount as any) === ''
      ? null
      : Number(p.max_discount),
    usage_limit:  p.usage_limit === null || p.usage_limit === undefined || (p.usage_limit as any) === ''
      ? null
      : Number(p.usage_limit),
    start_date:   p.start_date,
    end_date:     p.end_date,
    is_active:    !!p.is_active,
    target:       p.target,
    product_ids:  p.target === 'specific' ? p.product_ids : [],
  }
}

function saveForm() {
  if (!form.value.code.trim() || !form.value.name.trim()) return
  isSubmitting.value = true
  const payload = buildPayload(form.value)

  const opts = {
    preserveScroll: true,
    onSuccess: () => {
      formOpen.value = false
      toast.success(formMode.value === 'create' ? 'Promo berhasil dibuat' : 'Promo berhasil diperbarui')
    },
    onError: (errors: Record<string, string>) => {
      const first = Object.values(errors)[0]
      if (first) toast.error(first)
    },
    onFinish: () => { isSubmitting.value = false },
  }

  if (formMode.value === 'create') {
    router.post('/admin/promotions', payload, opts)
  } else {
    router.put(`/admin/promotions/${form.value.id}`, payload, opts)
  }
}

function confirmDelete(p: Promo) {
  promoToDelete.value = p
  deleteOpen.value = true
}

function executeDelete() {
  if (!promoToDelete.value) return
  const id = promoToDelete.value.id
  router.delete(`/admin/promotions/${id}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Promo berhasil dihapus')
    },
    onFinish: () => {
      deleteOpen.value = false
      promoToDelete.value = null
    },
  })
}

function toggleActive(p: Promo) {
  router.patch(`/admin/promotions/${p.id}/toggle`, {}, {
    preserveScroll: true,
    onError: () => toast.error('Gagal mengubah status promo'),
  })
}

function resetFilters() {
  search.value = ''
  statusFilter.value = 'all'
  typeFilter.value = 'all'
}

function toggleProductTarget(id: number | string) {
  const list = form.value.product_ids
  const i = list.indexOf(id)
  if (i === -1) list.push(id); else list.splice(i, 1)
}
</script>

<template>
  <TooltipProvider>
    <div class="adm-page px-6 py-5">

      <!-- ── Summary Cards ──────────────────────────────────────────────── -->
      <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="flex items-center gap-3 rounded-lg border bg-white p-4" style="border-color: var(--pos-border);">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: var(--pos-brand-light);">
            <Tag class="h-5 w-5" style="color: var(--pos-brand-primary);" />
          </div>
          <div>
            <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
              {{ stats.total }}<span class="text-sm font-semibold"> Promo</span>
            </p>
            <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Total promo</p>
          </div>
        </div>

        <div class="flex items-center gap-3 rounded-lg border bg-white p-4" style="border-color: var(--pos-border);">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: var(--pos-bg-success);">
            <CheckCircle2 class="h-5 w-5" style="color: var(--pos-success-text);" />
          </div>
          <div>
            <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
              {{ stats.active }}<span class="text-sm font-semibold"> Aktif</span>
            </p>
            <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Sedang berjalan</p>
          </div>
        </div>

        <div class="flex items-center gap-3 rounded-lg border bg-white p-4" style="border-color: var(--pos-border);">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: var(--pos-bg-warning);">
            <Clock class="h-5 w-5" style="color: var(--pos-warning-text);" />
          </div>
          <div>
            <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
              {{ stats.scheduled }}<span class="text-sm font-semibold"> Promo</span>
            </p>
            <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Akan datang</p>
          </div>
        </div>

        <div class="flex items-center gap-3 rounded-lg border bg-white p-4" style="border-color: var(--pos-border);">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: var(--pos-bg-danger);">
            <XCircle class="h-5 w-5" style="color: var(--pos-danger-text);" />
          </div>
          <div>
            <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
              {{ stats.expired }}<span class="text-sm font-semibold"> Promo</span>
            </p>
            <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Sudah berakhir</p>
          </div>
        </div>
      </div>

      <!-- ── Table Card ──────────────────────────────────────────────────── -->
      <div class="overflow-hidden rounded-lg border bg-white" style="border-color: var(--pos-border); box-shadow: var(--pos-shadow);">

        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-2 border-b px-4 py-3" style="border-color: var(--pos-border); background: #f8fafc;">
          <div class="flex items-center overflow-hidden rounded-md border" style="border-color: var(--pos-border);">
            <div class="relative">
              <Search class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2" style="color: var(--pos-text-muted);" />
              <input
                v-model="search"
                type="text"
                placeholder="Cari kode atau nama promo…"
                class="h-8 border-0 pl-8 pr-8 text-xs outline-none"
                style="color: var(--pos-text-secondary); width: 240px; background: #fff;"
              />
              <button
                v-if="search"
                class="absolute right-2 top-1/2 -translate-y-1/2 cursor-pointer"
                style="color: var(--pos-text-muted);"
                @click="search = ''"
              >
                <X class="h-3.5 w-3.5" />
              </button>
            </div>
          </div>

          <Select v-model="statusFilter">
            <SelectTrigger class="h-8 w-36 border text-xs cursor-pointer" style="border-color: var(--pos-border);">
              <SelectValue placeholder="Semua Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Semua Status</SelectItem>
              <SelectItem value="active">Aktif</SelectItem>
              <SelectItem value="scheduled">Akan Datang</SelectItem>
              <SelectItem value="expired">Berakhir</SelectItem>
              <SelectItem value="inactive">Nonaktif</SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="typeFilter">
            <SelectTrigger class="h-8 w-40 border text-xs cursor-pointer" style="border-color: var(--pos-border);">
              <SelectValue placeholder="Semua Tipe" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Semua Tipe</SelectItem>
              <SelectItem value="percentage">Persentase</SelectItem>
              <SelectItem value="fixed">Potongan Tetap</SelectItem>
              <SelectItem value="bogo">Beli X Dapat Y</SelectItem>
            </SelectContent>
          </Select>

          <span
            v-if="hasActiveFilters"
            class="cursor-pointer rounded-full px-2 py-0.5 text-xs font-semibold"
            style="background: var(--pos-brand-light); color: var(--pos-brand-primary);"
            @click="resetFilters"
          >
            Filter Aktif ✕
          </span>

          <div class="ml-auto">
            <button
              class="flex cursor-pointer items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition hover:opacity-90"
              style="background: var(--pos-brand-primary); color: #fff;"
              @click="openCreate"
            >
              <Plus class="h-3.5 w-3.5" />
              Tambah Promo
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="w-full max-w-full overflow-x-auto pb-2">
          <Table class="min-w-[1100px]">
            <TableHeader>
              <TableRow style="background: #f1f5f9;">
                <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Kode</TableHead>
                <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Nama Promo</TableHead>
                <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Tipe</TableHead>
                <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Nilai</TableHead>
                <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Target</TableHead>
                <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Periode</TableHead>
                <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Pemakaian</TableHead>
                <TableHead class="text-xs font-bold uppercase tracking-wide text-center" style="color: var(--pos-text-muted);">Status</TableHead>
                <TableHead class="w-24 pr-4 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Aksi</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <template v-if="filteredPromos.length">
                <TableRow
                  v-for="promo in filteredPromos"
                  :key="promo.id"
                  class="group cursor-pointer transition-colors hover:bg-[var(--pos-bg-accent)]"
                  style="border-color: var(--pos-border);"
                  @click="openDetail(promo)"
                >
                  <TableCell>
                    <span class="font-mono text-xs font-semibold" style="color: var(--pos-brand-primary);">{{ promo.code }}</span>
                  </TableCell>
                  <TableCell>
                    <div class="flex flex-col">
                      <span class="text-sm font-semibold" style="color: var(--pos-text-secondary);">{{ promo.name }}</span>
                      <span v-if="promo.description" class="text-xs truncate max-w-[260px]" style="color: var(--pos-text-muted);">
                        {{ promo.description }}
                      </span>
                    </div>
                  </TableCell>
                  <TableCell>
                    <span
                      class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                      style="background: var(--pos-brand-light); color: var(--pos-brand-primary);"
                    >
                      <Percent v-if="promo.type === 'percentage'" class="h-3 w-3" />
                      <Tag     v-else-if="promo.type === 'fixed'"  class="h-3 w-3" />
                      <Gift    v-else class="h-3 w-3" />
                      {{ typeLabel(promo.type) }}
                    </span>
                  </TableCell>
                  <TableCell>
                    <span class="text-sm font-bold tabular-nums" style="color: var(--pos-text-secondary);">
                      {{ valueLabel(promo) }}
                    </span>
                  </TableCell>
                  <TableCell>
                    <span class="text-xs" style="color: var(--pos-text-secondary);">{{ targetLabel(promo) }}</span>
                  </TableCell>
                  <TableCell>
                    <div class="flex items-center gap-1 text-xs" style="color: var(--pos-text-secondary);">
                      <CalendarDays class="h-3 w-3" style="color: var(--pos-text-muted);" />
                      {{ formatDate(promo.start_date) }} – {{ formatDate(promo.end_date) }}
                    </div>
                  </TableCell>
                  <TableCell>
                    <span class="text-xs tabular-nums" style="color: var(--pos-text-secondary);">
                      {{ promo.used_count }}<span style="color: var(--pos-text-muted);"> / {{ promo.usage_limit ?? '∞' }}</span>
                    </span>
                  </TableCell>
                  <TableCell class="text-center" @click.stop>
                    <button
                      type="button"
                      class="cursor-pointer rounded-full px-2.5 py-0.5 text-xs font-semibold"
                      :style="{
                        background: statusInfo(promo.status).bg,
                        color: statusInfo(promo.status).color,
                      }"
                      :title="promo.is_active ? 'Klik untuk menonaktifkan' : 'Klik untuk mengaktifkan'"
                      @click="toggleActive(promo)"
                    >
                      {{ statusInfo(promo.status).label }}
                    </button>
                  </TableCell>
                  <TableCell class="pr-4" @click.stop>
                    <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                      <Tooltip>
                        <TooltipTrigger as-child>
                          <button class="cursor-pointer rounded p-1" style="color: var(--pos-brand-primary);" @click="openDetail(promo)">
                            <Eye class="h-4 w-4" />
                          </button>
                        </TooltipTrigger>
                        <TooltipContent>Lihat detail</TooltipContent>
                      </Tooltip>
                      <Tooltip>
                        <TooltipTrigger as-child>
                          <button class="cursor-pointer rounded p-1" style="color: var(--pos-text-muted);" @click="openEdit(promo)">
                            <Pencil class="h-4 w-4" />
                          </button>
                        </TooltipTrigger>
                        <TooltipContent>Edit promo</TooltipContent>
                      </Tooltip>
                      <Tooltip>
                        <TooltipTrigger as-child>
                          <button class="cursor-pointer rounded p-1" style="color: var(--pos-danger-text);" @click="confirmDelete(promo)">
                            <Trash2 class="h-4 w-4" />
                          </button>
                        </TooltipTrigger>
                        <TooltipContent>Hapus promo</TooltipContent>
                      </Tooltip>
                    </div>
                  </TableCell>
                </TableRow>
              </template>

              <TableRow v-else>
                <TableCell colspan="9" class="py-16 text-center">
                  <Tag class="mx-auto mb-2 h-10 w-10" style="color: var(--pos-text-muted); opacity: 0.3;" />
                  <p class="text-sm font-medium" style="color: var(--pos-text-muted);">Belum ada promo</p>
                  <p class="mt-1 text-xs" style="color: var(--pos-text-light);">Klik "Tambah Promo" untuk membuat promo pertama</p>
                  <button
                    v-if="hasActiveFilters"
                    class="mt-3 cursor-pointer rounded-lg px-4 py-1.5 text-xs font-semibold"
                    style="background: var(--pos-brand-primary); color: #fff;"
                    @click="resetFilters"
                  >
                    Hapus semua filter
                  </button>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>

        <div class="px-4 py-3 border-t" style="border-color: var(--pos-border); background: #f8fafc;">
          <p class="text-xs" style="color: var(--pos-text-muted);">
            Menampilkan
            <strong style="color: var(--pos-text-secondary);">{{ filteredPromos.length }}</strong>
            dari
            <strong style="color: var(--pos-text-secondary);">{{ promos.length }}</strong>
            promo
          </p>
        </div>
      </div>

      <!-- ── Detail Sheet ─────────────────────────────────────────────────── -->
      <Sheet v-model:open="detailOpen">
        <SheetContent class="adm-sheet w-full overflow-y-auto p-5 sm:max-w-md sm:p-6">
          <SheetHeader>
            <SheetTitle>Detail Promo</SheetTitle>
            <SheetDescription>Informasi lengkap promo & penggunaannya</SheetDescription>
          </SheetHeader>

          <div v-if="selectedPromo" class="mt-5 flex flex-col gap-4">
            <div class="rounded-xl p-5 text-center" style="background: var(--pos-brand-light);">
              <p class="text-xs font-medium uppercase tracking-wider" style="color: var(--pos-brand-primary);">Kode Promo</p>
              <p class="mt-1 font-mono text-2xl font-bold tracking-wider" style="color: var(--pos-brand-dark);">{{ selectedPromo.code }}</p>
              <p class="mt-1 text-sm font-semibold" style="color: var(--pos-text-secondary);">{{ valueLabel(selectedPromo) }} OFF</p>
            </div>

            <div class="space-y-1">
              <h2 class="text-base font-bold" style="color: var(--pos-text-secondary);">{{ selectedPromo.name }}</h2>
              <p v-if="selectedPromo.description" class="text-sm" style="color: var(--pos-text-muted);">
                {{ selectedPromo.description }}
              </p>
            </div>

            <Separator />

            <div class="flex flex-col divide-y rounded-lg border" style="border-color: var(--pos-border);">
              <div v-for="(row, i) in [
                { label: 'Tipe',          value: typeLabel(selectedPromo.type) },
                { label: 'Nilai',         value: valueLabel(selectedPromo) },
                { label: 'Min. Pembelian', value: selectedPromo.min_purchase > 0 ? formatPrice(selectedPromo.min_purchase) : 'Tidak ada' },
                { label: 'Maks. Diskon',  value: selectedPromo.max_discount ? formatPrice(selectedPromo.max_discount) : 'Tidak dibatasi' },
                { label: 'Periode',       value: `${formatDate(selectedPromo.start_date)} – ${formatDate(selectedPromo.end_date)}` },
                { label: 'Target',        value: targetLabel(selectedPromo) },
                { label: 'Kuota',         value: selectedPromo.usage_limit ? `${selectedPromo.used_count} / ${selectedPromo.usage_limit}` : `${selectedPromo.used_count} / Tanpa Batas` },
              ]" :key="i" class="flex items-center justify-between px-4 py-2.5">
                <span class="text-xs" style="color: var(--pos-text-muted);">{{ row.label }}</span>
                <span class="text-sm font-semibold text-right" style="color: var(--pos-text-secondary);">{{ row.value }}</span>
              </div>
            </div>

            <div class="flex gap-2 pt-1">
              <button
                class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold"
                style="background: var(--pos-brand-primary); color: #fff;"
                @click="detailOpen = false; openEdit(selectedPromo)"
              >
                <Pencil class="h-3.5 w-3.5" /> Edit
              </button>
              <button
                class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold"
                style="border-color: var(--pos-danger-text); color: var(--pos-danger-text);"
                @click="detailOpen = false; confirmDelete(selectedPromo)"
              >
                <Trash2 class="h-3.5 w-3.5" /> Hapus
              </button>
            </div>
          </div>
        </SheetContent>
      </Sheet>

      <!-- ── Form Sheet (Create / Edit) ───────────────────────────────────── -->
      <Sheet v-model:open="formOpen">
        <SheetContent class="adm-sheet w-full overflow-y-auto p-5 sm:max-w-lg sm:p-6">
          <SheetHeader>
            <SheetTitle>{{ formMode === 'create' ? 'Tambah Promo Baru' : 'Edit Promo' }}</SheetTitle>
            <SheetDescription>
              {{ formMode === 'create' ? 'Buat promo / diskon baru untuk produk' : 'Perbarui informasi promo' }}
            </SheetDescription>
          </SheetHeader>

          <form class="mt-5 space-y-4" @submit.prevent="saveForm">
            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1.5">
                <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Kode Promo *</label>
                <Input v-model="form.code" placeholder="WELCOME10" class="h-9 font-mono uppercase" required />
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Status</label>
                <Select :model-value="form.is_active ? 'active' : 'inactive'" @update:model-value="(v: any) => form.is_active = v === 'active'">
                  <SelectTrigger class="h-9 cursor-pointer">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">Aktif</SelectItem>
                    <SelectItem value="inactive">Nonaktif</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Nama Promo *</label>
              <Input v-model="form.name" placeholder="Diskon Pendatang Baru" class="h-9" required />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Deskripsi</label>
              <textarea
                v-model="form.description"
                rows="2"
                placeholder="Ringkasan singkat promo…"
                class="w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-2"
                :style="{ borderColor: 'var(--pos-border)' }"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1.5">
                <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Tipe Diskon *</label>
                <Select v-model="form.type">
                  <SelectTrigger class="h-9 cursor-pointer"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="percentage">Persentase (%)</SelectItem>
                    <SelectItem value="fixed">Potongan Tetap (Rp)</SelectItem>
                    <SelectItem value="bogo">Beli X Dapat Y</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">
                  Nilai *
                  <span style="color: var(--pos-text-muted);">
                    ({{ form.type === 'percentage' ? '%' : form.type === 'bogo' ? 'jumlah gratis' : 'Rupiah' }})
                  </span>
                </label>
                <Input v-model.number="form.value" type="number" min="0" class="h-9 tabular-nums" required />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1.5">
                <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Min. Pembelian</label>
                <Input v-model.number="form.min_purchase" type="number" min="0" placeholder="0" class="h-9 tabular-nums" />
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Maks. Diskon</label>
                <Input
                  :model-value="form.max_discount ?? ''"
                  type="number"
                  min="0"
                  placeholder="Tanpa batas"
                  class="h-9 tabular-nums"
                  @update:model-value="(v: any) => form.max_discount = v === '' ? null : Number(v)"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1.5">
                <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Tanggal Mulai *</label>
                <Input v-model="form.start_date" type="date" class="h-9" required />
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Tanggal Berakhir *</label>
                <Input v-model="form.end_date" type="date" :min="form.start_date" class="h-9" required />
              </div>
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Kuota Pemakaian</label>
              <Input
                :model-value="form.usage_limit ?? ''"
                type="number"
                min="0"
                placeholder="Kosongkan = tanpa batas"
                class="h-9 tabular-nums"
                @update:model-value="(v: any) => form.usage_limit = v === '' ? null : Number(v)"
              />
            </div>

            <Separator />

            <div class="space-y-2">
              <label class="text-xs font-semibold" style="color: var(--pos-text-secondary);">Target Produk *</label>
              <div class="flex gap-2">
                <button
                  type="button"
                  class="flex-1 cursor-pointer rounded-md border px-3 py-2 text-xs font-semibold transition"
                  :style="form.target === 'all'
                    ? 'background: var(--pos-brand-primary); color: #fff; border-color: var(--pos-brand-primary);'
                    : 'border-color: var(--pos-border); color: var(--pos-text-secondary); background: #fff;'"
                  @click="form.target = 'all'; form.product_ids = []"
                >
                  Semua Produk
                </button>
                <button
                  type="button"
                  class="flex-1 cursor-pointer rounded-md border px-3 py-2 text-xs font-semibold transition"
                  :style="form.target === 'specific'
                    ? 'background: var(--pos-brand-primary); color: #fff; border-color: var(--pos-brand-primary);'
                    : 'border-color: var(--pos-border); color: var(--pos-text-secondary); background: #fff;'"
                  @click="form.target = 'specific'"
                >
                  Produk Tertentu
                </button>
              </div>

              <div
                v-if="form.target === 'specific'"
                class="max-h-48 overflow-y-auto rounded-md border p-2"
                style="border-color: var(--pos-border);"
              >
                <p v-if="!props.products.length" class="px-2 py-3 text-center text-xs" style="color: var(--pos-text-muted);">
                  <Package class="mx-auto mb-1 h-4 w-4" />
                  Daftar produk akan tersedia setelah backend terhubung
                </p>
                <label
                  v-for="product in props.products"
                  :key="product.id"
                  class="flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-xs hover:bg-[var(--pos-bg-secondary)]"
                >
                  <input
                    type="checkbox"
                    :checked="form.product_ids.includes(product.id)"
                    class="cursor-pointer accent-[var(--pos-brand-primary)]"
                    @change="toggleProductTarget(product.id)"
                  />
                  <span style="color: var(--pos-text-secondary);">{{ product.name }}</span>
                  <span v-if="product.sku" class="ml-auto font-mono text-[10px]" style="color: var(--pos-text-muted);">{{ product.sku }}</span>
                </label>
              </div>
            </div>
          </form>

          <SheetFooter class="mt-5 flex-row justify-end gap-2">
            <button
              class="cursor-pointer rounded-md border px-4 py-2 text-xs font-semibold"
              style="border-color: var(--pos-border); color: var(--pos-text-secondary); background: #fff;"
              @click="formOpen = false"
            >
              Batal
            </button>
            <button
              class="cursor-pointer rounded-md px-4 py-2 text-xs font-semibold"
              style="background: var(--pos-brand-primary); color: #fff;"
              @click="saveForm"
            >
              {{ formMode === 'create' ? 'Simpan Promo' : 'Perbarui' }}
            </button>
          </SheetFooter>
        </SheetContent>
      </Sheet>

      <!-- ── Delete confirmation ──────────────────────────────────────────── -->
      <Dialog v-model:open="deleteOpen">
        <DialogContent class="adm-sheet sm:max-w-md">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
              <Trash2 class="h-5 w-5" style="color: var(--pos-danger-text);" />
              Hapus Promo
            </DialogTitle>
            <DialogDescription>
              Yakin ingin menghapus promo
              <strong>{{ promoToDelete?.name }}</strong>
              (kode <span class="font-mono">{{ promoToDelete?.code }}</span>)?
              Tindakan ini tidak dapat dibatalkan.
            </DialogDescription>
          </DialogHeader>
          <DialogFooter class="gap-2 sm:justify-end">
            <button
              class="cursor-pointer rounded-md border px-4 py-2 text-xs font-semibold"
              style="border-color: var(--pos-border); color: var(--pos-text-secondary); background: #fff;"
              @click="deleteOpen = false"
            >
              Batal
            </button>
            <button
              class="cursor-pointer rounded-md px-4 py-2 text-xs font-semibold"
              style="background: var(--pos-danger-text); color: #fff;"
              @click="executeDelete"
            >
              Hapus
            </button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

    </div>
  </TooltipProvider>
</template>

<style scoped>
.adm-page {
  --pos-bg-primary: #ffffff;
  --pos-bg-secondary: #f9fafb;
  --pos-bg-accent: #ccfbf1;
  --pos-bg-danger: #fee2e2;
  --pos-bg-warning: #fef3c7;
  --pos-bg-success: #dcfce7;
  --pos-border: #e5e7eb;
  --pos-border-strong: #d1d5db;
  --pos-text-primary: #1e293b;
  --pos-text-secondary: #334155;
  --pos-text-muted: #6b7280;
  --pos-text-light: #9ca3af;
  --pos-brand-primary: #14b8a6;
  --pos-brand-hover: #0f9488;
  --pos-brand-light: #ecfeff;
  --pos-brand-dark: #0d9488;
  --pos-success-text: #16a34a;
  --pos-warning-text: #d97706;
  --pos-danger-text: #dc2626;
  --pos-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
  background: var(--pos-bg-secondary);
  color: var(--pos-text-primary);
}

textarea:focus,
input:focus {
  border-color: var(--pos-brand-primary);
}
</style>

<style>
/* Sheet/Dialog di-teleport ke <body>, di luar scope .adm-page.
   Re-deklarasi token POS + paksa kontras: bg terang, text gelap. */
.adm-sheet {
  --pos-bg-primary: #ffffff;
  --pos-bg-secondary: #f9fafb;
  --pos-bg-accent: #ccfbf1;
  --pos-bg-danger: #fee2e2;
  --pos-bg-warning: #fef3c7;
  --pos-bg-success: #dcfce7;
  --pos-border: #e5e7eb;
  --pos-border-strong: #d1d5db;
  --pos-text-primary: #0f172a;
  --pos-text-secondary: #1e293b;
  --pos-text-muted: #64748b;
  --pos-text-light: #94a3b8;
  --pos-brand-primary: #14b8a6;
  --pos-brand-hover: #0f9488;
  --pos-brand-light: #ecfeff;
  --pos-brand-dark: #0d9488;
  --pos-success-text: #16a34a;
  --pos-warning-text: #d97706;
  --pos-danger-text: #dc2626;

  background: #ffffff !important;
  color: var(--pos-text-secondary);
}

.adm-sheet [data-slot='sheet-title'],
.adm-sheet [data-slot='dialog-title'] {
  color: var(--pos-text-primary);
  font-weight: 700;
}

.adm-sheet [data-slot='sheet-description'],
.adm-sheet [data-slot='dialog-description'] {
  color: var(--pos-text-muted);
}

.adm-sheet label {
  color: var(--pos-text-secondary);
}

.adm-sheet input,
.adm-sheet textarea,
.adm-sheet select {
  background: #ffffff;
  color: var(--pos-text-primary);
  border-color: var(--pos-border);
}

.adm-sheet input::placeholder,
.adm-sheet textarea::placeholder {
  color: var(--pos-text-light);
}

.adm-sheet input:focus,
.adm-sheet textarea:focus,
.adm-sheet select:focus {
  border-color: var(--pos-brand-primary);
  outline: 2px solid var(--pos-brand-light);
  outline-offset: 1px;
}

.adm-sheet hr,
.adm-sheet [role='separator'] {
  border-color: var(--pos-border);
  background: var(--pos-border);
}
</style>
