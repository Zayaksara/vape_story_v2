<template>
  <div class="pos-layout h-dvh overflow-hidden bg-white">
    <SidebarProvider class="h-dvh overflow-hidden" :default-open="false">
      <PosSidebar />
      <SidebarInset>
        <PosHeader
          :cashier-name="cashierName"
          :transaction-id="transactionId"
          :current-time="currentTime"
        />
        <div class="flex flex-col flex-1 min-h-0 overflow-hidden">
          <slot />
        </div>
      </SidebarInset>
      <Toaster />
    </SidebarProvider>
  </div>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import PosSidebar from '@/components/pos/PosSidebar.vue'
import PosHeader from '@/components/pos/PosHeader.vue'
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
const cashierName = computed(() => {
  if (props.cashier?.name) return props.cashier.name
  const user = page.props.auth?.user as { name?: string } | undefined
  return user?.name ?? 'Kasir'
})
</script>

<style scoped>
/* Ensure POS layout uses light background independent of global theme */
.pos-layout {
  background-color: #ffffff;
}
</style>
