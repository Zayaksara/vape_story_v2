<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Package, FileText, RotateCcw } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
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
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
</template>
