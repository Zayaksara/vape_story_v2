<script setup lang="ts">
import Carousel from '@/components/Carousel.vue';

defineProps<{
    title?: string;
    description?: string;
}>();

const carouselImages = [
    '/storage/images/carausel1.webp',
    '/storage/images/carausel2.webp',
    '/storage/images/carausel3.webp',
];
</script>

<template>
    <div class="min-h-screen bg-background text-foreground lg:grid lg:min-h-screen lg:grid-cols-[1.3fr_0.5fr]">
        <!-- Left: carousel panel -->
        <div class="relative hidden h-screen flex-col overflow-hidden border-r border-border lg:flex">
            <div class="relative flex-1 px-12 pt-10 pb-12">
                <div class="h-full overflow-hidden rounded-2xl bg-muted">
                    <Carousel :images="carouselImages" />
                </div>
            </div>
        </div>

        <!-- Right: form panel -->
        <div class="flex min-h-screen items-center justify-center px-6 py-10 sm:px-8 lg:px-12">
            <div class="w-full max-w-[380px] space-y-6">
                <div class="space-y-2 text-left">
                    <h1
                        v-if="title"
                        class="text-3xl font-bold tracking-tight text-foreground"
                    >
                        {{ title }}
                    </h1>
                    <p
                        v-if="description"
                        class="max-w-[300px] text-sm leading-6 text-muted-foreground"
                    >
                        {{ description }}
                    </p>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
