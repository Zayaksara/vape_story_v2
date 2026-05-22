import { createInertiaApp } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import PosLayout from '@/layouts/pos/PosLayout.vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

    createInertiaApp({
        title: (title) => (title ? `${title} - ${appName}` : appName),
layout: (name) => {
            switch (true) {
                case name === 'Welcome':
                    return null;
                case name.startsWith('auth/'):
                    return AuthLayout;
                case name.startsWith('settings/'):
                    return [AppLayout, SettingsLayout];
                case name.startsWith('POS/'):
                    return PosLayout;
                case name.startsWith('admin/'):
                    return AdminLayout;
                default:
                    return AppLayout;
            }
        },
        progress: {
            color: '#4B5563',
        },
    });

// Pastikan aplikasi selalu memakai light mode...
if (typeof document !== 'undefined') {
    document.documentElement.classList.remove('dark');
}

// This will listen for flash toast data from the server...
initializeFlashToast();
