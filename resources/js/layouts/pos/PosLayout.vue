<template>
  <div class="pos-layout h-screen overflow-hidden bg-white">
    <SidebarProvider class="h-screen overflow-hidden" :default-open="false">
      <PosSidebar />
      <SidebarInset>
        <PosHeader
          v-if="cashier"
          :cashier-name="cashier.name"
          :transaction-id="transactionId"
          :current-time="currentTime"
        />
        <div :key="page.url" class="flex flex-col flex-1 min-h-0 overflow-hidden">
          <slot />
        </div>
      </SidebarInset>
      <Toaster />
    </SidebarProvider>
  </div>
</template>

<script setup lang="ts">
import PosSidebar from '@/components/pos/PosSidebar.vue'
import PosHeader from '@/components/pos/PosHeader.vue'
import { usePage } from '@inertiajs/vue3'
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar'
import { Toaster } from '@/components/ui/sonner'

type Props = {
  cashier?: {
    id: string
    name: string
    email: string
  }
  transactionId?: string
  currentTime?: string
}

const props = withDefaults(defineProps<Props>(), {
  cashier: undefined,
  transactionId: '',
  currentTime: '',
})

const page = usePage()
</script>

<style scoped>
/* Ensure POS layout uses light background independent of global theme */
.pos-layout {
  background-color: #ffffff;
}
</style>
