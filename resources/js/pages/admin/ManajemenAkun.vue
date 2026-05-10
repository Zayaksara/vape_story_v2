<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import {
  Search, X, Plus, Pencil, Trash2, Eye,
  Users, ShieldCheck, UserCog, BadgeCheck, BadgeX, Mail, KeyRound,
} from 'lucide-vue-next'

import { Input } from '@/components/ui/input'
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

type RoleValue = 'admin' | 'cashier'

interface RoleOption { value: RoleValue; label: string }

interface UserRow {
  id: string
  name: string
  email: string
  role: RoleValue
  email_verified_at: string | null
  created_at: string | null
}

interface UserForm {
  id: string
  name: string
  email: string
  role: RoleValue
  password: string
  password_confirmation: string
  verified: boolean
}

interface Props {
  users?: UserRow[]
  roles?: RoleOption[]
}
const props = withDefaults(defineProps<Props>(), {
  users: () => [],
  roles: () => [
    { value: 'admin',   label: 'Admin' },
    { value: 'cashier', label: 'Cashier' },
  ],
})

const page = usePage()
const currentUserId = computed<string | null>(() => (page.props.auth as any)?.user?.id ?? null)

// ── State ─────────────────────────────────────────────────────────────────────

const users = computed<UserRow[]>(() => props.users ?? [])
const isSubmitting = ref(false)

const search = ref('')
const roleFilter = ref<RoleValue | 'all'>('all')
const statusFilter = ref<'all' | 'verified' | 'unverified'>('all')

const selectedUser = ref<UserRow | null>(null)
const detailOpen = ref(false)

const formOpen = ref(false)
const formMode = ref<'create' | 'edit'>('create')
const form = ref<UserForm>(emptyForm())

const deleteOpen = ref(false)
const userToDelete = ref<UserRow | null>(null)

function emptyForm(): UserForm {
  return {
    id: '',
    name: '',
    email: '',
    role: 'cashier',
    password: '',
    password_confirmation: '',
    verified: true,
  }
}

// ── Derived ───────────────────────────────────────────────────────────────────

const hasActiveFilters = computed(() =>
  !!(search.value || roleFilter.value !== 'all' || statusFilter.value !== 'all'),
)

const filteredUsers = computed(() => {
  return users.value.filter(u => {
    const q = search.value.trim().toLowerCase()
    const matchSearch = !q ||
      u.name.toLowerCase().includes(q) ||
      u.email.toLowerCase().includes(q)
    const matchRole = roleFilter.value === 'all' || u.role === roleFilter.value
    const matchStatus = statusFilter.value === 'all'
      || (statusFilter.value === 'verified'   && !!u.email_verified_at)
      || (statusFilter.value === 'unverified' &&  !u.email_verified_at)
    return matchSearch && matchRole && matchStatus
  })
})

const stats = computed(() => {
  const all = users.value
  return {
    total:    all.length,
    admin:    all.filter(u => u.role === 'admin').length,
    cashier:  all.filter(u => u.role === 'cashier').length,
    verified: all.filter(u => !!u.email_verified_at).length,
  }
})

// ── Formatters ────────────────────────────────────────────────────────────────

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  const d = new Date(iso)
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

function roleLabel(r: RoleValue): string {
  return props.roles.find(x => x.value === r)?.label ?? r
}

function roleStyle(r: RoleValue): { bg: string; color: string } {
  return r === 'admin'
    ? { bg: 'var(--pos-brand-light)', color: 'var(--pos-brand-primary)' }
    : { bg: '#fef3c7',                color: '#b45309' }
}

function initials(name: string): string {
  return name.split(' ').slice(0, 2).map(w => w[0] ?? '').join('').toUpperCase()
}

// ── CRUD actions ──────────────────────────────────────────────────────────────

function openDetail(u: UserRow) {
  selectedUser.value = u
  detailOpen.value = true
}

function openCreate() {
  formMode.value = 'create'
  form.value = emptyForm()
  formOpen.value = true
}

function openEdit(u: UserRow) {
  formMode.value = 'edit'
  form.value = {
    id: u.id,
    name: u.name,
    email: u.email,
    role: u.role,
    password: '',
    password_confirmation: '',
    verified: !!u.email_verified_at,
  }
  formOpen.value = true
}

function buildPayload(f: UserForm) {
  const payload: Record<string, any> = {
    name:     f.name.trim(),
    email:    f.email.trim().toLowerCase(),
    role:     f.role,
    verified: !!f.verified,
  }
  if (f.password) {
    payload.password = f.password
    payload.password_confirmation = f.password_confirmation
  }
  return payload
}

function saveForm() {
  if (!form.value.name.trim() || !form.value.email.trim()) {
    toast.error('Nama dan email wajib diisi')
    return
  }
  if (formMode.value === 'create' && !form.value.password) {
    toast.error('Password wajib diisi untuk akun baru')
    return
  }
  if (form.value.password && form.value.password !== form.value.password_confirmation) {
    toast.error('Konfirmasi password tidak cocok')
    return
  }

  isSubmitting.value = true
  const payload = buildPayload(form.value)

  const opts = {
    preserveScroll: true,
    onSuccess: () => {
      formOpen.value = false
      toast.success(formMode.value === 'create' ? 'Akun berhasil dibuat' : 'Akun berhasil diperbarui')
    },
    onError: (errors: Record<string, string>) => {
      const first = Object.values(errors)[0]
      if (first) toast.error(first)
    },
    onFinish: () => { isSubmitting.value = false },
  }

  if (formMode.value === 'create') {
    router.post('/admin/users', payload, opts)
  } else {
    router.put(`/admin/users/${form.value.id}`, payload, opts)
  }
}

function confirmDelete(u: UserRow) {
  if (u.id === currentUserId.value) {
    toast.error('Anda tidak dapat menghapus akun sendiri')
    return
  }
  userToDelete.value = u
  deleteOpen.value = true
}

function executeDelete() {
  if (!userToDelete.value) return
  const id = userToDelete.value.id
  router.delete(`/admin/users/${id}`, {
    preserveScroll: true,
    onSuccess: () => {
      toast.success('Akun berhasil dihapus')
    },
    onError: (errors: Record<string, string>) => {
      const first = Object.values(errors)[0]
      if (first) toast.error(first)
    },
    onFinish: () => {
      deleteOpen.value = false
      userToDelete.value = null
    },
  })
}

function resetFilters() {
  search.value = ''
  roleFilter.value = 'all'
  statusFilter.value = 'all'
}
</script>

<template>
  <TooltipProvider>
    <div class="adm-page px-6 py-5">

      <!-- ── Summary Cards ──────────────────────────────────────────────── -->
      <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="flex items-center gap-3 rounded-lg border bg-white p-4" style="border-color: var(--pos-border);">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: var(--pos-brand-light);">
            <Users class="h-5 w-5" style="color: var(--pos-brand-primary);" />
          </div>
          <div>
            <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
              {{ stats.total }}<span class="text-sm font-semibold"> Akun</span>
            </p>
            <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Total pengguna</p>
          </div>
        </div>

        <div class="flex items-center gap-3 rounded-lg border bg-white p-4" style="border-color: var(--pos-border);">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: var(--pos-brand-light);">
            <ShieldCheck class="h-5 w-5" style="color: var(--pos-brand-primary);" />
          </div>
          <div>
            <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
              {{ stats.admin }}<span class="text-sm font-semibold"> Admin</span>
            </p>
            <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Hak akses penuh</p>
          </div>
        </div>

        <div class="flex items-center gap-3 rounded-lg border bg-white p-4" style="border-color: var(--pos-border);">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: #fef3c7;">
            <UserCog class="h-5 w-5" style="color: #b45309;" />
          </div>
          <div>
            <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
              {{ stats.cashier }}<span class="text-sm font-semibold"> Cashier</span>
            </p>
            <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Akses POS</p>
          </div>
        </div>

        <div class="flex items-center gap-3 rounded-lg border bg-white p-4" style="border-color: var(--pos-border);">
          <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg" style="background: var(--pos-bg-success);">
            <BadgeCheck class="h-5 w-5" style="color: var(--pos-success-text);" />
          </div>
          <div>
            <p class="text-2xl font-bold leading-none" style="color: var(--pos-text-secondary);">
              {{ stats.verified }}<span class="text-sm font-semibold"> Verified</span>
            </p>
            <p class="mt-0.5 text-xs" style="color: var(--pos-text-muted);">Email terverifikasi</p>
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
                placeholder="Cari nama atau email…"
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

          <Select v-model="roleFilter">
            <SelectTrigger class="h-8 w-36 border text-xs cursor-pointer" style="border-color: var(--pos-border);">
              <SelectValue placeholder="Semua Role" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Semua Role</SelectItem>
              <SelectItem v-for="r in props.roles" :key="r.value" :value="r.value">
                {{ r.label }}
              </SelectItem>
            </SelectContent>
          </Select>

          <Select v-model="statusFilter">
            <SelectTrigger class="h-8 w-40 border text-xs cursor-pointer" style="border-color: var(--pos-border);">
              <SelectValue placeholder="Semua Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">Semua Status</SelectItem>
              <SelectItem value="verified">Terverifikasi</SelectItem>
              <SelectItem value="unverified">Belum Verifikasi</SelectItem>
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
              Tambah Akun
            </button>
          </div>
        </div>

        <!-- Table -->
        <div class="w-full max-w-full overflow-x-auto pb-2">
          <Table class="min-w-[900px]">
            <TableHeader>
              <TableRow style="background: #f1f5f9;">
                <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Pengguna</TableHead>
                <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Email</TableHead>
                <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Role</TableHead>
                <TableHead class="text-xs font-bold uppercase tracking-wide text-center" style="color: var(--pos-text-muted);">Status Email</TableHead>
                <TableHead class="text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Bergabung</TableHead>
                <TableHead class="w-24 pr-4 text-xs font-bold uppercase tracking-wide" style="color: var(--pos-text-muted);">Aksi</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              <template v-if="filteredUsers.length">
                <TableRow
                  v-for="user in filteredUsers"
                  :key="user.id"
                  class="group cursor-pointer transition-colors hover:bg-[var(--pos-bg-accent)]"
                  style="border-color: var(--pos-border);"
                  @click="openDetail(user)"
                >
                  <TableCell>
                    <div class="flex items-center gap-3">
                      <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                        style="background: var(--pos-brand-light); color: var(--pos-brand-primary);"
                      >
                        {{ initials(user.name) }}
                      </div>
                      <div class="flex flex-col">
                        <span class="text-sm font-semibold" style="color: var(--pos-text-secondary);">{{ user.name }}</span>
                        <span v-if="user.id === currentUserId" class="text-[10px] font-semibold uppercase tracking-wide" style="color: var(--pos-brand-primary);">
                          (Anda)
                        </span>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <span class="text-sm" style="color: var(--pos-text-secondary);">{{ user.email }}</span>
                  </TableCell>
                  <TableCell>
                    <span
                      class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold capitalize"
                      :style="{ background: roleStyle(user.role).bg, color: roleStyle(user.role).color }"
                    >
                      <ShieldCheck v-if="user.role === 'admin'" class="h-3 w-3" />
                      <UserCog v-else class="h-3 w-3" />
                      {{ roleLabel(user.role) }}
                    </span>
                  </TableCell>
                  <TableCell class="text-center">
                    <span
                      v-if="user.email_verified_at"
                      class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                      style="background: var(--pos-bg-success); color: var(--pos-success-text);"
                    >
                      <BadgeCheck class="h-3 w-3" />
                      Verified
                    </span>
                    <span
                      v-else
                      class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                      style="background: var(--pos-bg-warning); color: var(--pos-warning-text);"
                    >
                      <BadgeX class="h-3 w-3" />
                      Belum
                    </span>
                  </TableCell>
                  <TableCell>
                    <span class="text-xs" style="color: var(--pos-text-secondary);">{{ formatDate(user.created_at) }}</span>
                  </TableCell>
                  <TableCell class="pr-4" @click.stop>
                    <div class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100">
                      <Tooltip>
                        <TooltipTrigger as-child>
                          <button class="cursor-pointer rounded p-1" style="color: var(--pos-brand-primary);" @click="openDetail(user)">
                            <Eye class="h-4 w-4" />
                          </button>
                        </TooltipTrigger>
                        <TooltipContent>Lihat detail</TooltipContent>
                      </Tooltip>
                      <Tooltip>
                        <TooltipTrigger as-child>
                          <button class="cursor-pointer rounded p-1" style="color: var(--pos-text-muted);" @click="openEdit(user)">
                            <Pencil class="h-4 w-4" />
                          </button>
                        </TooltipTrigger>
                        <TooltipContent>Edit akun</TooltipContent>
                      </Tooltip>
                      <Tooltip>
                        <TooltipTrigger as-child>
                          <button
                            class="cursor-pointer rounded p-1 disabled:opacity-30 disabled:cursor-not-allowed"
                            :style="{ color: 'var(--pos-danger-text)' }"
                            :disabled="user.id === currentUserId"
                            @click="confirmDelete(user)"
                          >
                            <Trash2 class="h-4 w-4" />
                          </button>
                        </TooltipTrigger>
                        <TooltipContent>{{ user.id === currentUserId ? 'Tidak bisa hapus diri sendiri' : 'Hapus akun' }}</TooltipContent>
                      </Tooltip>
                    </div>
                  </TableCell>
                </TableRow>
              </template>

              <TableRow v-else>
                <TableCell colspan="6" class="py-16 text-center">
                  <Users class="mx-auto mb-2 h-10 w-10" style="color: var(--pos-text-muted); opacity: 0.3;" />
                  <p class="text-sm font-medium" style="color: var(--pos-text-muted);">Belum ada akun</p>
                  <p class="mt-1 text-xs" style="color: var(--pos-text-light);">Klik "Tambah Akun" untuk membuat akun baru</p>
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
            <strong style="color: var(--pos-text-secondary);">{{ filteredUsers.length }}</strong>
            dari
            <strong style="color: var(--pos-text-secondary);">{{ users.length }}</strong>
            akun
          </p>
        </div>
      </div>

      <!-- ── Detail Sheet ─────────────────────────────────────────────────── -->
      <Sheet v-model:open="detailOpen">
        <SheetContent class="adm-sheet w-full overflow-y-auto p-5 sm:max-w-md sm:p-6">
          <SheetHeader>
            <SheetTitle>Detail Akun</SheetTitle>
            <SheetDescription>Informasi lengkap pengguna</SheetDescription>
          </SheetHeader>

          <div v-if="selectedUser" class="mt-5 flex flex-col gap-4">
            <div class="flex flex-col items-center gap-3 rounded-xl p-5 text-center" style="background: var(--pos-brand-light);">
              <div
                class="flex h-16 w-16 items-center justify-center rounded-full text-xl font-bold"
                style="background: #fff; color: var(--pos-brand-primary);"
              >
                {{ initials(selectedUser.name) }}
              </div>
              <div>
                <p class="text-base font-bold" style="color: var(--pos-brand-dark);">{{ selectedUser.name }}</p>
                <p class="text-sm" style="color: var(--pos-text-secondary);">{{ selectedUser.email }}</p>
              </div>
              <span
                class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold capitalize"
                :style="{ background: '#fff', color: roleStyle(selectedUser.role).color }"
              >
                <ShieldCheck v-if="selectedUser.role === 'admin'" class="h-3 w-3" />
                <UserCog v-else class="h-3 w-3" />
                {{ roleLabel(selectedUser.role) }}
              </span>
            </div>

            <Separator />

            <div class="flex flex-col divide-y rounded-lg border" style="border-color: var(--pos-border);">
              <div v-for="(row, i) in [
                { label: 'Nama',         value: selectedUser.name },
                { label: 'Email',        value: selectedUser.email },
                { label: 'Role',         value: roleLabel(selectedUser.role) },
                { label: 'Status Email', value: selectedUser.email_verified_at ? 'Terverifikasi' : 'Belum Diverifikasi' },
                { label: 'Bergabung',    value: formatDate(selectedUser.created_at) },
                { label: 'ID',           value: selectedUser.id },
              ]" :key="i" class="flex items-center justify-between px-4 py-2.5">
                <span class="text-xs" style="color: var(--pos-text-muted);">{{ row.label }}</span>
                <span class="text-sm font-semibold text-right truncate max-w-[60%]" style="color: var(--pos-text-secondary);" :title="String(row.value)">
                  {{ row.value }}
                </span>
              </div>
            </div>

            <div class="flex gap-2 pt-1">
              <button
                class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-semibold"
                style="background: var(--pos-brand-primary); color: #fff;"
                @click="detailOpen = false; openEdit(selectedUser)"
              >
                <Pencil class="h-3.5 w-3.5" /> Edit
              </button>
              <button
                class="flex flex-1 cursor-pointer items-center justify-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold disabled:opacity-40 disabled:cursor-not-allowed"
                style="border-color: var(--pos-danger-text); color: var(--pos-danger-text);"
                :disabled="selectedUser.id === currentUserId"
                @click="detailOpen = false; confirmDelete(selectedUser)"
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
            <SheetTitle>{{ formMode === 'create' ? 'Tambah Akun Baru' : 'Edit Akun' }}</SheetTitle>
            <SheetDescription>
              {{ formMode === 'create'
                ? 'Buat akun pengguna baru dengan role yang sesuai'
                : 'Perbarui informasi akun pengguna' }}
            </SheetDescription>
          </SheetHeader>

          <form class="mt-5 space-y-4" @submit.prevent="saveForm">
            <div class="space-y-1.5">
              <label class="text-xs font-semibold">Nama Lengkap *</label>
              <Input v-model="form.name" placeholder="Budi Santoso" class="h-9" required />
            </div>

            <div class="space-y-1.5">
              <label class="text-xs font-semibold">Email *</label>
              <div class="relative">
                <Mail class="pointer-events-none absolute left-2.5 top-1/2 h-4 w-4 -translate-y-1/2" style="color: var(--pos-text-muted);" />
                <Input v-model="form.email" type="email" placeholder="user@example.com" class="h-9 pl-8" required />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1.5">
                <label class="text-xs font-semibold">Role *</label>
                <Select v-model="form.role">
                  <SelectTrigger class="h-9 cursor-pointer"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="r in props.roles" :key="r.value" :value="r.value">{{ r.label }}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div class="space-y-1.5">
                <label class="text-xs font-semibold">Status Email</label>
                <Select :model-value="form.verified ? '1' : '0'" @update:model-value="(v: any) => form.verified = v === '1'">
                  <SelectTrigger class="h-9 cursor-pointer"><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="1">Tandai Terverifikasi</SelectItem>
                    <SelectItem value="0">Belum Diverifikasi</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <Separator />

            <div class="rounded-md border p-3" style="border-color: var(--pos-border); background: #f8fafc;">
              <div class="flex items-center gap-2 mb-3">
                <KeyRound class="h-4 w-4" style="color: var(--pos-brand-primary);" />
                <span class="text-xs font-semibold">{{ formMode === 'create' ? 'Password Akun' : 'Ubah Password (opsional)' }}</span>
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                  <label class="text-xs font-semibold">
                    Password {{ formMode === 'create' ? '*' : '' }}
                  </label>
                  <Input
                    v-model="form.password"
                    type="password"
                    :placeholder="formMode === 'edit' ? 'Kosongkan jika tidak diubah' : 'Min. 8 karakter'"
                    class="h-9"
                    :required="formMode === 'create'"
                    autocomplete="new-password"
                  />
                </div>
                <div class="space-y-1.5">
                  <label class="text-xs font-semibold">Konfirmasi Password</label>
                  <Input
                    v-model="form.password_confirmation"
                    type="password"
                    placeholder="Ulangi password"
                    class="h-9"
                    :required="!!form.password"
                    autocomplete="new-password"
                  />
                </div>
              </div>
              <p v-if="formMode === 'edit'" class="mt-2 text-[11px]" style="color: var(--pos-text-muted);">
                Kosongkan kedua kolom jika password tidak ingin diubah.
              </p>
            </div>
          </form>

          <SheetFooter class="mt-5 flex-row justify-end gap-2">
            <button
              type="button"
              class="cursor-pointer rounded-md border px-4 py-2 text-xs font-semibold"
              style="border-color: var(--pos-border); color: var(--pos-text-secondary); background: #fff;"
              @click="formOpen = false"
            >
              Batal
            </button>
            <button
              type="button"
              class="cursor-pointer rounded-md px-4 py-2 text-xs font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
              style="background: var(--pos-brand-primary); color: #fff;"
              :disabled="isSubmitting"
              @click="saveForm"
            >
              {{ isSubmitting ? 'Menyimpan…' : (formMode === 'create' ? 'Simpan Akun' : 'Perbarui') }}
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
              Hapus Akun
            </DialogTitle>
            <DialogDescription>
              Yakin ingin menghapus akun
              <strong>{{ userToDelete?.name }}</strong>
              (<span class="font-mono">{{ userToDelete?.email }}</span>)?
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
