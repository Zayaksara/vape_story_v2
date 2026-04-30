<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import Carousel from '@/components/Carousel.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();

// Path gambar di storage/app/public/images/
const carouselImages = [
    '/storage/images/carausel1.webp',
    '/storage/images/carausel2.webp',
    '/storage/images/carausel3.webp',
];
</script>

<template>
    <div
        class="min-h-screen bg-(--pos-bg-secondary) lg:grid lg:min-h-screen lg:grid-cols-[1.3fr_0.5fr]"
    >
        <!-- Left side - Carousel (lebih panjang) -->
        <div
            class="relative hidden h-screen flex-col overflow-hidden border-r -(--pos-text-primary) lg:flex"
        >
            <div class="relative flex-1 px-12 pt-10 pb-12">
                <div class="h-full overflow-hidden rounded-2xl bg-zinc-800/40">
                    <Carousel :images="carouselImages" />
                </div>
            </div>
        </div>

        <!-- Right side - Form (lebih pendek) -->
        <div
            class="flex min-h-screen items-center justify-center px-6 py-10 sm:px-8 lg:px-12"
        >
            <div class="w-full max-w-[380px] space-y-6">
                <div class="space-y-2 text-left">
                    <h1
                        v-if="title"
                        class="text-3xl font-bold tracking-tight text-white"
                    >
                        {{ title }}
                    </h1>

                    <p
                        v-if="description"
                        class="max-w-[300px] text-sm leading-6 text-zinc-400"
                    >
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
