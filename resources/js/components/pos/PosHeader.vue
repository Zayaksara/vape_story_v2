<template>
<header class="pos__header sticky top-0 z-30 flex h-14 items-center gap-3 border-b px-4 shadow-sm"
:style="{
borderBottomColor: 'var(--pos-border)',
backgroundColor: 'var(--pos-bg-primary)'
}">
    <!-- Logo / Store name with hamburger menu -->
    <div class="flex items-center gap-3">
      <!-- Hamburger menu button -->
      <button
        class="flex h-9 w-9 items-center justify-center rounded-lg hover:bg-gray-100 transition-colors"
        aria-label="Toggle sidebar"
        @click="toggleSidebar"
      >
        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
             :style="{ color: 'var(--pos-brand-primary)' }">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>

      <!-- Logo -->
      <div class="flex h-8 w-8 items-center justify-center">
        <img
          src="/storage/images/logo.png"
          alt="Story Vape Logo"
          class="h-full w-full object-contain"
        />
      </div>

      <div>
        <h1 class="text-sm font-bold leading-none"
            :style="{ color: 'var(--pos-text-primary)' }">
          Vape Story
        </h1>
      </div>
    </div>

    <!-- Transaction ID -->
    <div class="ml-auto flex items-center gap-2">
      <Badge variant="outline" class="text-xs font-mono">
        {{ transactionId }}
      </Badge>
      <span class="text-xs"
            :style="{ color: 'var(--pos-text-light)' }">
        {{ currentTime }}
      </span>
      <span class="text-base font-medium"
            :style="{ color: 'var(--pos-text-primary)' }">
        Selamat datang, {{ cashierName }}
      </span>
    </div>
  </header>
</template>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { useSidebar } from '@/components/ui/sidebar'

defineProps<{
  cashierName: string
  transactionId: string
  currentTime: string
}>()

const { toggleSidebar } = useSidebar()
</script>
