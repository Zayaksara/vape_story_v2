<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
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
    title = 'Welcome to Vape Story',
    description = 'Login dulu masbro, biar bisa nikmatin semua fitur yang ada',
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
</script>

<template>
    <CustomAuthSplitLayout :title="title" :description="description">
        <Head title="Log in" />

        <div
            v-if="status"
            class="mb-4 rounded-md bg-red-500/10 p-3 text-sm text-red-500"
        >
            {{ status }}
        </div>

        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <!-- Email Field -->
            <div class="field-control">
                <Label for="email" class="text-sm font-medium text-gray-300"
                    >Email</Label
                >
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="name@company.com"
                    class="field-control-input mt-1 h-11 w-full rounded-lg border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-gray-400 focus:ring-1 focus:ring-gray-400 focus:outline-none"
                />
                <InputError :message="errors.email" class="error" />
            </div>

            <!-- Password Field -->
            <div class="field-control">
                <div class="flex items-center justify-between">
                    <Label
                        for="password"
                        class="text-sm font-medium text-gray-300"
                        >Password</Label
                    >
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm text-gray-400 hover:text-white"
                        :tabindex="5"
                    >
                        Forgot password?
                    </TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    :tabindex="2"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    class="field-control-input mt-1 h-11 w-full rounded-lg border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-gray-400 focus:ring-1 focus:ring-gray-400 focus:outline-none"
                />
                <InputError :message="errors.password" class="error" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between">
                <Label
                    for="remember"
                    class="flex cursor-pointer items-center gap-2"
                >
                    <Checkbox
                        id="remember"
                        name="remember"
                        :tabindex="3"
                        class="border-gray-600"
                    />
                    <span class="text-sm text-gray-400">Remember me</span>
                </Label>
            </div>

            <!-- Submit Button -->
            <Button
                type="submit"
                class="submit-button gradient-brand hover:gradient-brand-hover mt-2 h-11 w-full rounded-lg px-4 py-2 text-sm font-semibold text-white focus:outline-none"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" class="mr-2 size-4 animate-spin" />
                Sign in
            </Button>

            <!-- Register Link -->
            <div class="text-center text-sm text-gray-400" v-if="canRegister">
                Don't have an account?
                <TextLink
                    :href="register()"
                    :tabindex="5"
                    class="font-medium text-white hover:underline"
                >
                    Sign up
                </TextLink>
            </div>
        </Form>
    </CustomAuthSplitLayout>
</template>
