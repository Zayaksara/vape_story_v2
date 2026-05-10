<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
    defaultSidebarOpen?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
    defaultSidebarOpen: true,
});

const isOpen = computed(() => {
    if (props.defaultSidebarOpen) {
        return usePage().props.sidebarOpen ?? true;
    }

    return false;
});
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else :default-open="isOpen" class="flex min-h-screen flex-col">
        <slot />
    </SidebarProvider>
</template>
