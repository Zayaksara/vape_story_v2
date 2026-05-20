<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import CustomAuthSplitLayout from '@/layouts/auth/CustomAuthSplitLayout.vue';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const {
    title: titleProp,
    description: descriptionProp,
    status,
    canResetPassword,
    canRegister,
} = defineProps<{
    title?: string;
    description?: string;
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const page = usePage();
const storeName = computed(() => (page.props.storeName as string | undefined) ?? 'Story Vape');
const storeLogo = computed(() => (page.props.storeLogo as string | null | undefined) ?? null);
const storeTagline = computed(() => (page.props.storeTagline as string | null | undefined) ?? null);
const storeAddress = computed(() => (page.props.storeAddress as string | null | undefined) ?? null);
const storePhone = computed(() => (page.props.storePhone as string | null | undefined) ?? null);

const computedTitle = computed(() => titleProp ?? `Selamat datang di ${storeName.value}`);
const computedDescription = computed(() => descriptionProp ?? storeTagline.value ?? 'Masuk untuk mulai transaksi.');
</script>

<template>
    <CustomAuthSplitLayout :title="computedTitle" :description="computedDescription">
        <Head title="Log in" />

        <div v-if="storeLogo" class="mb-4 flex items-center gap-3">
            <img
                :src="storeLogo"
                :alt="`Logo ${storeName}`"
                class="h-12 w-12 rounded-lg object-contain"
            />
            <span class="text-sm font-semibold text-foreground">{{ storeName }}</span>
        </div>

        <div
            v-if="status"
            class="mb-4 rounded-md bg-destructive/10 p-3 text-sm text-destructive"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="name@company.com"
                    class="h-11"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm text-muted-foreground hover:text-foreground"
                        :tabindex="5"
                    >
                        Lupa password?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Masukkan password"
                    class="h-11"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <Label for="remember" class="flex cursor-pointer items-center gap-2">
                    <Checkbox id="remember" name="remember" :tabindex="3" />
                    <span class="text-sm text-muted-foreground">Ingat saya</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-2 h-11 w-full"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" class="mr-2 size-4 animate-spin" />
                Masuk
            </Button>

            <div v-if="canRegister" class="text-center text-sm text-muted-foreground">
                Belum punya akun? Hubungi admin toko.
            </div>
        </Form>

        <div
            v-if="storeAddress || storePhone"
            class="mt-8 border-t pt-4 text-xs leading-relaxed text-muted-foreground"
        >
            <p v-if="storeAddress" class="truncate" :title="storeAddress">{{ storeAddress }}</p>
            <p v-if="storePhone">{{ storePhone }}</p>
        </div>
    </CustomAuthSplitLayout>
</template>
