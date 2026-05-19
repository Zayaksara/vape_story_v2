<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>

<style>
/* Print styles for report pages - hide admin UI elements */
@media print {
    body[data-report-print='true'] .app-header,
    body[data-report-print='true'] header,
    body[data-report-print='true'] [data-sidebar='sidebar'],
    body[data-report-print='true'] .sidebar,
    body[data-report-print='true'] .toaster.group,
    body[data-report-print='true'] #phpdebugbar,
    body[data-report-print='true'] .phpdebugbar,
    body[data-report-print='true'] .phpdebugbar-openhandler {
        display: none !important;
    }

    body[data-report-print='true'] .app-layout,
    body[data-report-print='true'] [data-slot='sidebar-inset'] {
        overflow: visible !important;
        height: auto !important;
        min-height: 0 !important;
    }

    @page {
        margin: 12mm;
    }
}
</style>
