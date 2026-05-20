<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ImagePlus } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import SettingsRoleLayout from '@/layouts/SettingsRoleLayout.vue';

type StoreSetting = {
    id: number;
    name: string;
    address: string | null;
    phone: string | null;
    tagline: string | null;
    logo_path: string | null;
    receipt_header: string | null;
    receipt_footer: string | null;
    show_logo_on_receipt: boolean;
};

const props = defineProps<{
    store: StoreSetting;
}>();

defineOptions({
    layout: SettingsRoleLayout,
});

const form = useForm({
    _method: 'patch',
    name: props.store.name ?? '',
    address: props.store.address ?? '',
    phone: props.store.phone ?? '',
    tagline: props.store.tagline ?? '',
    receipt_header: props.store.receipt_header ?? '',
    receipt_footer: props.store.receipt_footer ?? '',
    show_logo_on_receipt: !!props.store.show_logo_on_receipt,
    logo: null as File | null,
});

function submit() {
    form.post('/settings/store', {
        forceFormData: true,
        preserveScroll: true,
    });
}

function onLogoChange(e: Event) {
    form.logo = (e.target as HTMLInputElement).files?.[0] ?? null;
}
</script>

<template>
    <Head title="Pengaturan Toko" />

    <h1 class="sr-only">Pengaturan Toko</h1>

    <section class="space-y-6">
        <Heading
            title="Identitas Toko"
            description="Informasi ini ditampilkan di header struk dan halaman login."
        />

        <form class="space-y-5" @submit.prevent="submit">
            <div class="grid gap-2">
                <Label for="store-name">Nama toko</Label>
                <Input id="store-name" v-model="form.name" placeholder="cth. Story Vape" required />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="store-address">Alamat</Label>
                <Input id="store-address" v-model="form.address" placeholder="Jl. ..." />
                <InputError :message="form.errors.address" />
            </div>

            <div class="grid gap-2">
                <Label for="store-phone">Nomor telepon</Label>
                <Input id="store-phone" v-model="form.phone" placeholder="08xx-xxxx-xxxx" />
                <InputError :message="form.errors.phone" />
            </div>

            <div class="grid gap-2">
                <Label for="store-tagline">Tagline</Label>
                <Input
                    id="store-tagline"
                    v-model="form.tagline"
                    maxlength="100"
                    placeholder="cth. Vape Premium Sejak 2020"
                />
                <p class="text-xs text-muted-foreground">Muncul di halaman login sebagai sambutan.</p>
                <InputError :message="form.errors.tagline" />
            </div>

            <div class="grid gap-2">
                <Label for="store-logo">Logo</Label>
                <div class="flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted">
                        <img
                            v-if="store.logo_path"
                            :src="`/storage/${store.logo_path}`"
                            alt="Logo toko"
                            class="h-full w-full object-contain"
                        />
                        <ImagePlus v-else class="h-6 w-6 text-muted-foreground" />
                    </div>
                    <Input id="store-logo" type="file" accept="image/*" class="cursor-pointer" @change="onLogoChange" />
                </div>
                <p class="text-xs text-muted-foreground">PNG/JPG, maks 2 MB.</p>
                <InputError :message="form.errors.logo" />
            </div>

            <Separator />

            <Heading
                variant="small"
                title="Kustom Struk"
                description="Teks opsional di atas dan bawah struk pembayaran."
            />

            <div class="grid gap-2">
                <Label for="receipt-header">Header struk</Label>
                <textarea
                    id="receipt-header"
                    v-model="form.receipt_header"
                    rows="2"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    placeholder="cth. Story Vape — Jl. Mawar No. 1"
                />
                <InputError :message="form.errors.receipt_header" />
            </div>

            <div class="grid gap-2">
                <Label for="receipt-footer">Footer struk</Label>
                <textarea
                    id="receipt-footer"
                    v-model="form.receipt_footer"
                    rows="2"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    placeholder="cth. Terima kasih telah berbelanja!"
                />
                <InputError :message="form.errors.receipt_footer" />
            </div>

            <label class="flex items-center gap-2 text-sm text-foreground">
                <input
                    v-model="form.show_logo_on_receipt"
                    type="checkbox"
                    class="h-4 w-4 rounded border-input text-primary focus:ring-ring"
                />
                Tampilkan logo di struk
            </label>

            <div class="flex justify-end pt-2">
                <Button :disabled="form.processing">Simpan perubahan</Button>
            </div>
        </form>
    </section>
</template>
