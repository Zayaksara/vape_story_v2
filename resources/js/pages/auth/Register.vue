<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import CustomAuthSplitLayout from '@/layouts/auth/CustomAuthSplitLayout.vue';
import { login } from '@/routes';
import { store } from '@/routes/register';

const {
    title = 'Create Account',
    description = 'Enter your details to get started',
} = defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <CustomAuthSplitLayout :title="title" :description="description">
        <Head title="Register" />

        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-5"
        >
            <!-- Name Field -->
            <div class="field-control">
                <Label for="name" class="text-sm font-medium text-gray-300"
                    >Full Name</Label
                >
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Enter your full name"
                    class="field-control-input mt-1 h-11 w-full rounded-lg border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-gray-400 focus:ring-1 focus:ring-gray-400 focus:outline-none"
                />
                <InputError :message="errors.name" class="error" />
            </div>

            <!-- Email Field -->
            <div class="field-control">
                <Label for="email" class="text-sm font-medium text-gray-300"
                    >Email</Label
                >
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="name@company.com"
                    class="field-control-input mt-1 h-11 w-full rounded-lg border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-gray-400 focus:ring-1 focus:ring-gray-400 focus:outline-none"
                />
                <InputError :message="errors.email" class="error" />
            </div>

            <!-- Role Field -->
            <div class="field-control">
                <Label for="role" class="text-sm font-medium text-gray-300"
                    >Role</Label
                >
                <Select name="role" required :tabindex="3">
                    <SelectTrigger class="mt-1 h-11 w-full rounded-lg border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-gray-400 focus:ring-1 focus:ring-gray-400 focus:outline-none">
                        <SelectValue placeholder="Select your role" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="cashier">Cashier</SelectItem>
                        <SelectItem value="admin">Admin</SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.role" class="error" />
            </div>

            <!-- Password Field -->
            <div class="field-control">
                <Label for="password" class="text-sm font-medium text-gray-300"
                    >Password</Label
                >
                <PasswordInput
                    id="password"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Create a password"
                    class="field-control-input mt-1 h-11 w-full rounded-lg border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-gray-400 focus:ring-1 focus:ring-gray-400 focus:outline-none"
                />
                <InputError :message="errors.password" class="error" />
            </div>

            <!-- Confirm Password Field -->
            <div class="field-control">
                <Label
                    for="password_confirmation"
                    class="text-sm font-medium text-gray-300"
                    >Confirm Password</Label
                >
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="5"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm your password"
                    class="field-control-input mt-1 h-11 w-full rounded-lg border border-gray-600 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-400 focus:border-gray-400 focus:ring-1 focus:ring-gray-400 focus:outline-none"
                />
                <InputError
                    :message="errors.password_confirmation"
                    class="error"
                />
            </div>

            <!-- Submit Button -->
            <Button
                type="submit"
                class="submit-button gradient-brand hover:gradient-brand-hover mt-2 h-11 w-full rounded-lg px-4 py-2 text-sm font-semibold text-white focus:outline-none"
                tabindex="6"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" class="mr-2 size-4 animate-spin" />
                Create account
            </Button>

            <!-- Login Link -->
            <div class="text-center text-sm text-gray-400">
                Already have an account?
                <TextLink
                    :href="login()"
                    class="font-medium text-white hover:underline"
                    :tabindex="7"
                    >Sign in</TextLink
                >
            </div>
        </Form>
    </CustomAuthSplitLayout>
</template>
