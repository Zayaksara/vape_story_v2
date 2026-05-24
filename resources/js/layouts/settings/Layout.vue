<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ShieldCheck, Store, UserCog, type LucideIcon } from 'lucide-vue-next';
import { computed } from 'vue';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
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
                            'group relative flex items-start gap-3 rounded-lg border px-3 py-2.5 transition-colors',
                            isCurrentOrParentUrl(item.href)
                                ? 'border-border bg-muted/70 text-foreground'
                                : 'border-transparent text-muted-foreground hover:border-border hover:bg-muted/40 hover:text-foreground',
                        ]"
                    >
                        <span
                            v-if="isCurrentOrParentUrl(item.href)"
                            class="absolute inset-y-2 left-0 w-1 rounded-r-full bg-primary"
                            aria-hidden="true"
                        />
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
                <div class="settings-card rounded-xl border bg-card text-card-foreground shadow-sm">
                    <div class="space-y-8 p-5 sm:p-6">
                        <slot />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Tombol aksi dalam form Settings: bedakan dari kartu & sidebar aktif
   supaya tidak "nyatu" dengan latar di layout POS yang serba-putih. */
.settings-card :deep(button[type='submit']),
.settings-card :deep(button[data-test$='-button']),
.settings-card :deep([data-slot='button'][type='submit']) {
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08), 0 2px 6px rgba(20, 184, 166, 0.18);
    font-weight: 600;
}

.settings-card :deep(button[type='submit']:hover),
.settings-card :deep(button[data-test$='-button']:hover) {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(15, 23, 42, 0.1), 0 6px 14px rgba(20, 184, 166, 0.22);
}

.settings-card :deep(button[type='submit']:active),
.settings-card :deep(button[data-test$='-button']:active) {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.1);
}

/* Buang efek hover saat disabled (processing). */
.settings-card :deep(button[disabled]) {
    transform: none !important;
    box-shadow: none !important;
}
</style>
