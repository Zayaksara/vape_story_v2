<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import PosLayout from '@/layouts/pos/PosLayout.vue';
import SettingsSubLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();
const isAdmin = computed(() => {
    const user = page.props.auth?.user as { role?: string } | undefined;
    return user?.role === 'admin';
});
</script>

<template>
    <component :is="isAdmin ? AdminLayout : PosLayout">
        <SettingsSubLayout>
            <slot />
        </SettingsSubLayout>
    </component>
</template>
