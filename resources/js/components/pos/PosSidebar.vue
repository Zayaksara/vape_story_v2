<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LayoutGrid, Package, FileText, RotateCcw, ShieldCheck } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import PrinterStatusBadge from '@/components/pos/PrinterStatusBadge.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/components/ui/sidebar';
import pos from '@/routes/pos';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    { title: 'Dashboard POS', href: pos.dashboard.index.url(), icon: LayoutGrid },
    { title: 'Katalog Produk', href: pos.products.index.url(), icon: Package },
    { title: 'Riwayat Transaksi', href: pos.transactions.today.url(), icon: FileText },
    { title: 'Pengembalian Barang', href: '/pos/returns', icon: RotateCcw },
];

const page = usePage();
const isAdmin = computed(() => {
    const role = (page.props.auth as any)?.user?.role;
    return role && role !== 'cashier';
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link
                            :href="pos.dashboard.index.url()"
                            :preserve-state="false"
                            :preserve-scroll="false"
                        >
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup>
                <SidebarGroupLabel>Menu Utama</SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in mainNavItems" :key="item.title">
                            <SidebarMenuButton as-child>
                                <Link
                                    :href="item.href"
                                    :preserve-state="false"
                                    :preserve-scroll="false"
                                >
                                    <component :is="item.icon" />
                                    <span>{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
            <SidebarGroup v-if="isAdmin">
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton as-child>
                                <Link
                                    href="/admin/dashboard"
                                    :preserve-state="false"
                                    :preserve-scroll="false"
                                >
                                    <ShieldCheck />
                                    <span>Kembali ke Admin</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <PrinterStatusBadge />
            <NavUser />
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
</template>
