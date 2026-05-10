<script setup lang="ts">
import { computed, ref } from 'vue'
import { Upload, X, Plus } from 'lucide-vue-next'
import axios from '@/lib/axios'
import { toast } from 'vue-sonner'
import CurrencyInput from '@/components/admin/CurrencyInput.vue'

interface Category { id: string; name: string }
interface Brand    { id: string; name: string }

/**
 * Shared product form fields for Create.vue & Edit.vue.
 * The parent owns the Inertia `useForm` instance and passes it in;
 * we bind v-model directly to its fields so changes propagate.
 */
const props = defineProps<{
  form: any
  categories: Category[]
  brands: Brand[]
  mode?: 'create' | 'edit'
  initialImageUrl?: string | null
}>()

const emit = defineEmits<{
  'category-added': [category: Category]
}>()

// ── Image ─────────────────────────────────────────────────────────────────────

const imagePreview = ref<string | null>(props.initialImageUrl ?? null)

function onImageChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  props.form.image = file
  imagePreview.value = URL.createObjectURL(file)
}

function removeImage() {
  props.form.image = null
  imagePreview.value = null
}

// ── Size dropdown (15 / 30 / 60 / custom) ─────────────────────────────────────

const SIZE_PRESETS = ['15', '30', '60'] as const

const sizeMode = computed({
  get(): string {
    const v = String(props.form.size_ml ?? '')
    if (!v) return ''
    return (SIZE_PRESETS as readonly string[]).includes(v) ? v : 'custom'
  },
  set(v: string) {
    if (v === 'custom') {
      if ((SIZE_PRESETS as readonly string[]).includes(String(props.form.size_ml ?? ''))) {
        props.form.size_ml = ''
      }
    } else {
      props.form.size_ml = v
    }
  },
})

// ── Category create modal ────────────────────────────────────────────────────

const categoryModalOpen = ref(false)
const newCategoryName = ref('')
const newCategoryDesc = ref('')
const creatingCategory = ref(false)

function openCategoryModal() {
  newCategoryName.value = ''
  newCategoryDesc.value = ''
  categoryModalOpen.value = true
}

async function submitNewCategory() {
  if (!newCategoryName.value.trim()) {
    toast.error('Nama kategori wajib diisi')
    return
  }

  creatingCategory.value = true
  try {
    const { data } = await axios.post<Category>('/admin/categories', {
      name: newCategoryName.value.trim(),
      description: newCategoryDesc.value.trim() || null,
    })
    emit('category-added', data)
    props.form.category_id = data.id
    categoryModalOpen.value = false
    toast.success(`Kategori "${data.name}" dibuat`)
  } catch (err: any) {
    const msg = err?.response?.data?.errors?.name?.[0]
      ?? err?.response?.data?.message
      ?? 'Gagal membuat kategori'
    toast.error(msg)
  } finally {
    creatingCategory.value = false
  }
}
</script>

<template>
  <!-- Image upload -->
  <div>
    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Foto Produk</label>
    <div v-if="imagePreview" class="relative mb-2 h-40 w-40 overflow-hidden rounded-lg border" style="border-color: var(--pos-border);">
      <img :src="imagePreview" class="h-full w-full object-cover" />
      <button type="button" class="absolute right-1.5 top-1.5 rounded-full bg-white p-0.5 shadow" @click="removeImage">
        <X class="h-3.5 w-3.5 text-red-500" />
      </button>
    </div>
    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-dashed px-4 py-3 text-xs font-medium transition hover:bg-gray-50" style="border-color: var(--pos-border); color: var(--pos-text-muted);">
      <Upload class="h-4 w-4" />
      {{ imagePreview ? 'Ganti gambar' : 'Pilih gambar' }} (jpg, png, webp – maks 2MB)
      <input type="file" class="hidden" accept="image/jpeg,image/png,image/jpg,image/webp" @change="onImageChange" />
    </label>
    <p v-if="form.errors.image" class="mt-1 text-xs text-red-500">{{ form.errors.image }}</p>
  </div>

  <!-- Code + Name -->
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Kode Produk</label>
      <input
        v-model="form.code"
        type="text"
        placeholder="Auto-generate jika kosong"
        class="w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
        style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
      />
      <p v-if="form.errors.code" class="mt-1 text-xs text-red-500">{{ form.errors.code }}</p>
    </div>
    <div>
      <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Nama Produk <span class="text-red-500">*</span></label>
      <input
        v-model="form.name"
        type="text"
        placeholder="Nama produk"
        required
        class="w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
        style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
      />
      <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
    </div>
  </div>

  <!-- Category + Brand -->
  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Kategori <span class="text-red-500">*</span></label>
      <div class="flex items-stretch gap-2">
        <select
          v-model="form.category_id"
          required
          class="flex-1 rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
          style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
        >
          <option value="">Pilih kategori</option>
          <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
        </select>
        <button
          type="button"
          class="flex shrink-0 cursor-pointer items-center gap-1 rounded-md px-3 text-xs font-semibold transition hover:opacity-90"
          style="background: var(--pos-brand-primary); color: #fff;"
          title="Tambah kategori baru"
          @click="openCategoryModal"
        >
          <Plus class="h-3.5 w-3.5" />
        </button>
      </div>
      <p v-if="form.errors.category_id" class="mt-1 text-xs text-red-500">{{ form.errors.category_id }}</p>
    </div>
    <div>
      <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Brand</label>
      <select
        v-model="form.brand_id"
        class="w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
        style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
      >
        <option value="">Pilih brand</option>
        <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
      </select>
      <p v-if="form.errors.brand_id" class="mt-1 text-xs text-red-500">{{ form.errors.brand_id }}</p>
    </div>
  </div>

  <!-- Harga Dasar -->
  <div>
    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Harga Dasar <span class="text-red-500">*</span></label>
    <CurrencyInput v-model="form.base_price" required placeholder="0" />
    <p v-if="form.errors.base_price" class="mt-1 text-xs text-red-500">{{ form.errors.base_price }}</p>
  </div>

  <!-- Flavor + Nicotine + Size -->
  <div class="grid grid-cols-3 gap-4">
    <div>
      <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Flavor</label>
      <input
        v-model="form.flavor"
        type="text"
        placeholder="Mango, Mint…"
        class="w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
        style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
      />
    </div>
    <div>
      <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Nikotin (mg)</label>
      <input
        v-model="form.nicotine_strength"
        type="number"
        min="0"
        step="0.1"
        placeholder="3"
        class="w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
        style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
      />
    </div>
    <div>
      <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Ukuran (ml)</label>
      <select
        v-model="sizeMode"
        class="w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
        style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
      >
        <option value="">Pilih ukuran</option>
        <option value="15">15 ml</option>
        <option value="30">30 ml</option>
        <option value="60">60 ml</option>
        <option value="custom">Lainnya…</option>
      </select>
      <input
        v-if="sizeMode === 'custom'"
        v-model="form.size_ml"
        type="number"
        min="0"
        step="0.1"
        placeholder="cth: 100"
        class="mt-2 w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
        style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
      />
    </div>
  </div>

  <!-- Description -->
  <div>
    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Deskripsi</label>
    <textarea
      v-model="form.description"
      rows="3"
      placeholder="Deskripsi produk (opsional)"
      class="w-full resize-none rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
      style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
    />
  </div>

  <!-- Active toggle -->
  <div class="flex items-center gap-3">
    <input id="is_active" v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded" />
    <label for="is_active" class="text-sm font-medium" style="color: var(--pos-text-secondary);">Produk aktif (tampil di POS)</label>
  </div>

  <!-- ── Category create modal ────────────────────────────────────────────── -->
  <Teleport to="body">
    <div
      v-if="categoryModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
      role="dialog"
      aria-modal="true"
    >
      <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="categoryModalOpen = false" />
      <div
        class="relative z-10 w-full max-w-sm overflow-hidden rounded-2xl shadow-2xl animate-in fade-in zoom-in-95 duration-200"
        style="background: #fff;"
      >
        <div
          class="flex items-center justify-between border-b px-5 py-4"
          style="border-color: var(--pos-border); background: var(--pos-brand-light);"
        >
          <div>
            <h3 class="text-sm font-bold" style="color: var(--pos-brand-dark);">Kategori Baru</h3>
            <p class="text-[11px]" style="color: var(--pos-text-secondary);">Tambah kategori produk</p>
          </div>
          <button
            class="cursor-pointer rounded-full p-1.5 transition-colors hover:bg-white/60"
            style="color: var(--pos-text-muted);"
            @click="categoryModalOpen = false"
          >
            <X class="h-4 w-4" />
          </button>
        </div>

        <form class="space-y-4 p-5" @submit.prevent="submitNewCategory">
          <div>
            <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Nama Kategori <span class="text-red-500">*</span></label>
            <input
              v-model="newCategoryName"
              type="text"
              required
              autofocus
              placeholder="cth: Liquid Salt Nic"
              class="w-full rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
              style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
            />
          </div>
          <div>
            <label class="mb-1.5 block text-xs font-semibold" style="color: var(--pos-text-muted);">Deskripsi (opsional)</label>
            <textarea
              v-model="newCategoryDesc"
              rows="2"
              placeholder="Keterangan singkat"
              class="w-full resize-none rounded-md border px-3 py-2 text-sm outline-none transition focus:ring-2"
              style="border-color: var(--pos-border); color: var(--pos-text-secondary);"
            />
          </div>

          <div class="flex justify-end gap-2 border-t pt-3" style="border-color: var(--pos-border);">
            <button
              type="button"
              class="cursor-pointer rounded-md border px-4 py-2 text-xs font-semibold"
              style="border-color: var(--pos-border); color: var(--pos-text-secondary); background: #fff;"
              @click="categoryModalOpen = false"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="creatingCategory"
              class="cursor-pointer rounded-md px-4 py-2 text-xs font-semibold disabled:opacity-60"
              style="background: var(--pos-brand-primary); color: #fff;"
            >
              {{ creatingCategory ? 'Menyimpan…' : 'Simpan Kategori' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </Teleport>
</template>
