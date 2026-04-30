<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import CustomAuthSplitLayout from '@/layouts/auth/CustomAuthSplitLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

const {
    status,
    title = 'Email Verification',
    description = 'Please verify your email address to continue',
} = defineProps<{
    status?: string;
    title?: string;
    description?: string;
}>();
</script>

<template>
    <CustomAuthSplitLayout :title="title" :description="description">
        <Head title="Email Verification" />

        <div class="flex flex-col gap-5">
            <div class="text-center">
                <div class="text-sm text-gray-400">
                    Before proceeding, please check your email for a verification link.
                </div>

                <div v-if="status" class="mt-4 text-sm font-medium text-green-400">
                    {{ status }}
                </div>
            </div>

            <Form
                v-bind="send.form()"
                :reset-on-success="false"
                v-slot="{ processing }"
                class="flex flex-col gap-3"
            >
                <Button
                    type="submit"
                    class="w-full rounded-lg px-4 py-2 text-sm font-semibold text-white bg-gray-700 hover:bg-gray-600 focus:outline-none"
                    :disabled="processing"
                >
                    <Spinner v-if="processing" class="mr-2 size-4 animate-spin" />
                    Resend Verification Email
                </Button>
            </Form>

            <div class="text-center text-sm text-gray-400">
                <Form
                    v-bind="logout.form()"
                    v-slot="{ processing }"
                    class="inline"
                >
                    <Button
                        type="submit"
                        class="rounded-lg px-4 py-2 text-sm font-semibold text-white bg-gray-700 hover:bg-gray-600 focus:outline-none"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" class="mr-2 size-4 animate-spin" />
                        Log Out
                    </Button>
                </Form>
            </div>
        </div>
    </CustomAuthSplitLayout>
</template>
