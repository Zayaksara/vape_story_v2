<template>
  <div class="admin-layout flex h-screen overflow-hidden">
    <!-- Sliding Sidebar (push, not overlay) -->
    <aside
      class="admin-sidebar flex-shrink-0 flex flex-col border-r"
      :class="['transition-all duration-300 ease-in-out', isMenuOpen ? 'w-64' : 'w-0']"
      :style="{
        backgroundColor: 'var(--pos-bg-primary)',
        borderColor: 'var(--pos-border)',
      }"
    >
      <div class="flex flex-col h-full w-64 overflow-hidden">
        <!-- Sidebar Header / Brand -->
        <div
          class="flex items-center gap-3 px-4 py-2.5 border-b"
          :style="{ borderColor: 'var(--pos-border)' }"
        >
          <div
            class="flex h-9 w-9 items-center justify-center rounded-lg"
            :style="{
              backgroundColor: 'var(--pos-brand-light)',
              color: 'var(--pos-brand-primary)',
            }"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7M3 7l9-4 9 4M3 7h18"/>
            </svg>
          </div>
          <div class="min-w-0">
            <p class="text-sm font-semibold truncate" :style="{ color: 'var(--pos-text-primary)' }">
              Story Vape
            </p>
            <p class="text-xs truncate" :style="{ color: 'var(--pos-text-muted)' }">
              Admin Panel
            </p>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-2 py-3 space-y-1">
          <Link
            v-for="item in menuItems"
            :key="item.label"
            :href="item.href"
            class="admin-nav-item flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium cursor-pointer"
            :class="{ 'is-active': isActive(item.href) }"
          >
            <component :is="item.icon" class="h-4 w-4 shrink-0" />
            <span class="truncate">{{ item.label }}</span>
          </Link>
        </nav>

        <!-- Bottom: Profile / Logout -->
        <div
          class="border-t px-2 py-3 space-y-1"
          :style="{ borderColor: 'var(--pos-border)' }"
        >
          <Link
            href="/settings/profile"
            class="admin-nav-item flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium cursor-pointer"
            :class="{ 'is-active': isActive('/settings/profile') }"
          >
            <UserCog class="h-4 w-4 shrink-0" />
            <span class="truncate">Pengaturan Profil</span>
          </Link>
          <Link
            href="/logout"
            method="post"
            as="button"
            class="admin-nav-item admin-nav-danger flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm font-medium cursor-pointer text-left"
          >
            <LogOut class="h-4 w-4 shrink-0" />
            <span class="truncate">Logout</span>
          </Link>
        </div>
      </div>
    </aside>

    <!-- Main column -->
    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
      <!-- Admin Header -->
      <header
        class="flex items-center justify-between px-4 py-2.5 border-b"
        :style="{
          backgroundColor: 'var(--pos-bg-primary)',
          borderColor: 'var(--pos-border)',
        }"
      >
        <div class="flex items-center space-x-3">
          <!-- Hamburger toggle -->
          <button
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-md cursor-pointer transition-colors"
            :style="{
              backgroundColor: isMenuOpen ? 'var(--pos-brand-light)' : 'transparent',
              color: isMenuOpen ? 'var(--pos-brand-primary)' : 'var(--pos-text-secondary)',
            }"
            :aria-expanded="isMenuOpen"
            aria-label="Toggle navigation menu"
            @click="toggleMenu"
          >
            <img
              src="/images/icon/icon-humberger-menu.svg"
              alt=""
              class="h-4 w-4"
              :style="{
                filter: isMenuOpen
                  ? 'invert(58%) sepia(73%) saturate(421%) hue-rotate(132deg) brightness(92%) contrast(89%)'
                  : 'invert(22%) sepia(13%) saturate(1095%) hue-rotate(178deg) brightness(94%) contrast(89%)',
              }"
            />
          </button>
          <h1 class="text-lg font-semibold" :style="{ color: 'var(--pos-text-primary)' }">
            {{ title }}
          </h1>
        </div>
        <div class="flex items-center space-x-4">
          <div class="relative">
            <button
              class="flex items-center space-x-2 rounded-full p-1 cursor-pointer transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
              :style="{
                backgroundColor: 'var(--pos-bg-secondary)',
                color: 'var(--pos-text-secondary)',
                '--tw-ring-color': 'var(--pos-brand-primary)',
              }"
            >
              <span class="hidden md:block text-sm font-medium">Welcome, {{ user.name }}</span>
            </button>
          </div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main
        class="flex-1 overflow-y-auto p-6"
        :style="{
          backgroundColor: 'var(--pos-bg-secondary)',
          color: 'var(--pos-text-primary)',
        }"
      >
        <slot />
      </main>
    </div>

    <Toaster />
  </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3'
import { Toaster } from '@/components/ui/sonner'
import { ref, computed, onMounted } from 'vue'
import {
  LayoutDashboard,
  Package,
  BarChart3,
  Receipt,
  Users,
  Tag,
  UserCog,
  LogOut,
  ShoppingBasket,
} from 'lucide-vue-next'

const page = usePage()
const props = page.props as Record<string, any>
const title = ref(props.title ?? 'Admin Panel')
const user = ref(props.user ?? page.props.auth?.user ?? { name: 'Admin' })

const isMenuOpen = ref(true)
function toggleMenu() {
  isMenuOpen.value = !isMenuOpen.value
}

const menuItems = [
  { label: 'Dashboard', href: '/admin/dashboard', icon: LayoutDashboard },
  { label: 'Manajemen Produk', href: '/admin/products', icon: Package },
  { label: 'Laporan Penjualan', href: '/admin/reports/sales', icon: BarChart3 },
  { label: 'History Pembayaran', href: '/admin/transactions/today', icon: Receipt },
  { label: 'Kelola Akun', href: '/admin/users', icon: Users },
  { label: 'Promo & Diskon', href: '/admin/promotions', icon: Tag },
  { label: 'Point of Sale', href: '/POS/dashboard', icon: ShoppingBasket },
]

const currentUrl = computed(() => page.url || '')
function isActive(href: string): boolean {
  return currentUrl.value === href || currentUrl.value.startsWith(href + '/')
}

onMounted(() => {
  title.value = props.title ?? 'Admin Panel'
  user.value = props.user ?? page.props.auth?.user ?? { name: 'Admin' }
})
</script>

<style scoped>
.admin-layout {
  --pos-bg-primary: #ffffff;
  --pos-bg-secondary: #f9fafb;
  --pos-bg-accent: #ccfbf1;
  --pos-border: #e5e7eb;
  --pos-border-strong: #d1d5db;
  --pos-text-primary: #1e293b;
  --pos-text-secondary: #334155;
  --pos-text-muted: #6b7280;
  --pos-brand-primary: #14b8a6;
  --pos-brand-hover: #0f9488;
  --pos-brand-light: #ecfeff;
  --pos-danger-text: #dc2626;
  --pos-danger-bg: #fee2e2;
  --pos-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);

  background-color: var(--pos-bg-secondary);
  color: var(--pos-text-primary);
}

.admin-sidebar {
  overflow: hidden;
  white-space: nowrap;
}

.admin-nav-item {
  color: var(--pos-text-secondary);
  transition: background-color 150ms ease, color 150ms ease;
  text-decoration: none;
}

.admin-nav-item:hover {
  background-color: var(--pos-brand-light);
  color: var(--pos-brand-primary);
}

.admin-nav-item.is-active {
  background-color: var(--pos-brand-light);
  color: var(--pos-brand-primary);
  font-weight: 600;
}

.admin-nav-danger {
  color: var(--pos-danger-text);
  background-color: transparent;
  border: 0;
}

.admin-nav-danger:hover {
  background-color: var(--pos-danger-bg);
  color: var(--pos-danger-text);
}

@media (prefers-reduced-motion: reduce) {
  .admin-sidebar {
    transition: none !important;
  }
}
</style>
