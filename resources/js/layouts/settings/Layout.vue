<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Palette, ShieldCheck, Store, UserCog, type LucideIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';

type SettingsNavItem = {
    title: string;
    description: string;
    href: string;
    icon: LucideIcon;
};

const page = usePage();
const isAdmin = computed(() => {
    const user = page.props.auth?.user as { role?: string } | undefined;
    return user?.role === 'admin';
});

const sidebarNavItems = computed<SettingsNavItem[]>(() => {
    const items: SettingsNavItem[] = [
        { title: 'Profil', description: 'Nama & email akun', href: editProfile(), icon: UserCog },
        { title: 'Keamanan', description: 'Password & 2FA', href: editSecurity(), icon: ShieldCheck },
        { title: 'Tampilan', description: 'Tema aplikasi', href: editAppearance(), icon: Palette },
    ];
    if (isAdmin.value) {
        items.push({ title: 'Toko', description: 'Identitas & struk', href: '/settings/store', icon: Store });
    }
    return items;
});

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="w-full p-4 sm:p-6">
        <header class="mb-6">
            <h1 class="text-xl font-semibold tracking-tight text-foreground sm:text-2xl">
                Pengaturan
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Kelola profil, keamanan, dan preferensi akun Anda.
            </p>
        </header>

        <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
            <aside class="w-full lg:w-64 lg:shrink-0">
                <nav class="flex flex-col gap-1" aria-label="Settings">
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        :class="[
                            'group flex items-start gap-3 rounded-lg border px-3 py-2.5 transition-colors',
                            isCurrentOrParentUrl(item.href)
                                ? 'border-primary/30 bg-primary/10 text-foreground shadow-sm'
                                : 'border-transparent text-muted-foreground hover:border-border hover:bg-muted hover:text-foreground',
                        ]"
                    >
                        <component
                            :is="item.icon"
                            :class="[
                                'mt-0.5 h-4 w-4 shrink-0',
                                isCurrentOrParentUrl(item.href)
                                    ? 'text-primary'
                                    : 'text-muted-foreground group-hover:text-foreground',
                            ]"
                        />
                        <span class="flex min-w-0 flex-col gap-0.5">
                            <span class="text-sm font-medium leading-tight text-foreground">{{ item.title }}</span>
                            <span
                                :class="[
                                    'text-xs leading-tight',
                                    isCurrentOrParentUrl(item.href) ? 'text-foreground/70' : 'text-muted-foreground',
                                ]"
                            >{{ item.description }}</span>
                        </span>
                    </Link>
                </nav>
            </aside>

            <Separator class="lg:hidden" />

            <div class="flex-1 min-w-0">
                <div class="rounded-xl border bg-card text-card-foreground shadow-sm">
                    <div class="space-y-8 p-5 sm:p-6">
                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
