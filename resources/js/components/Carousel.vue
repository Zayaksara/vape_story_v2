<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const props = withDefaults(
    defineProps<{
        images: string[];
        interval?: number;
    }>(),
    {
        interval: 3000,
    },
);

const currentIndex = ref(0);
let intervalId: ReturnType<typeof setInterval> | null = null;

const nextSlide = () => {
    if (!props.images.length) {
        return;
    }

    currentIndex.value = (currentIndex.value + 1) % props.images.length;
};

const goToSlide = (index: number) => {
    currentIndex.value = index;
};

const startInterval = () => {
    if (props.images.length <= 1) {
        return;
    }

    intervalId = setInterval(nextSlide, props.interval);
};

const stopInterval = () => {
    if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
    }
};

onMounted(() => {
    startInterval();
});

onUnmounted(() => {
    stopInterval();
});
</script>

<template>
    <div class="relative h-full w-full overflow-hidden">
        <div class="relative h-full w-full">
            <img
                v-for="(image, index) in images"
                :key="image"
                :src="image"
                :alt="`Slide ${index + 1}`"
                class="absolute inset-0 h-full w-full object-cover transition-opacity duration-700"
                :class="index === currentIndex ? 'opacity-100' : 'opacity-0'"
            />
        </div>

        <div
            v-if="images.length > 1"
            class="absolute bottom-6 left-1/2 z-10 flex -translate-x-1/2 gap-2"
        >
            <button
                v-for="(image, index) in images"
                :key="index"
                type="button"
                @click="goToSlide(index)"
                :class="[
                    'h-2.5 w-2.5 rounded-full transition-all duration-300',
                    index === currentIndex
                        ? 'scale-125 bg-white'
                        : 'bg-white/40 hover:bg-white/70',
                ]"
                :aria-label="`Go to slide ${index + 1}`"
            />
        </div>
    </div>
</template>
