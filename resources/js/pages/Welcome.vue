<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { login } from '@/routes';

defineProps<{
    canRegister?: boolean;
}>();

const page = usePage();
const storeName = computed(() => (page.props.storeName as string | undefined) ?? 'Story Vape');
const storeLogo = computed(() => (page.props.storeLogo as string | null | undefined) ?? '/storage/images/logo.png');
const storeTagline = computed(() => (page.props.storeTagline as string | null | undefined) ?? 'Selamat datang');
const carouselImages = computed<string[]>(() => (page.props.storeCarouselImages as string[] | undefined) ?? []);

const SPLASH_DURATION = 1500;

const showSplash = ref(true);
const logoLoaded = ref(false);
const fadeOut = ref(false);
const isMounted = ref(false);
const bgImage = computed<string | null>(() => {
    if (carouselImages.value.length === 0) return null;
    return carouselImages.value[Math.floor(Math.random() * carouselImages.value.length)];
});

let splashTimer: ReturnType<typeof setTimeout> | null = null;
let fadeTimer: ReturnType<typeof setTimeout> | null = null;

function handleSplashComplete() {
    fadeOut.value = true;
    fadeTimer = setTimeout(() => {
        showSplash.value = false;
        router.visit(login());
    }, 600);
}

onMounted(() => {
    isMounted.value = true;

    const img = new Image();
    img.src = storeLogo.value;
    img.onload = () => {
        logoLoaded.value = true;
    };
    img.onerror = () => {
        logoLoaded.value = true; // tetap lanjut walaupun logo gagal
    };

    splashTimer = setTimeout(handleSplashComplete, SPLASH_DURATION);
});

onUnmounted(() => {
    if (splashTimer) clearTimeout(splashTimer);
    if (fadeTimer) clearTimeout(fadeTimer);
});
</script>

<template>
    <Head :title="storeName" />

    <Transition
        enter-active-class="transition-opacity duration-700"
        leave-active-class="transition-opacity duration-500"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="showSplash"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden bg-gradient-to-br from-gray-900 via-gray-800 to-black"
        >
            <!-- Carousel background (kalau owner sudah upload) -->
            <div v-if="isMounted && bgImage" class="absolute inset-0">
                <img
                    :src="bgImage"
                    alt=""
                    class="absolute inset-0 h-full w-full scale-110 object-cover blur-sm"
                />
                <div class="absolute inset-0 bg-black/70"></div>
            </div>

            <div class="relative z-10 flex flex-col items-center justify-center">
                <div
                    class="relative transform transition-all duration-700 ease-out"
                    :class="logoLoaded ? 'scale-100 opacity-100' : 'scale-75 opacity-0'"
                >
                    <div
                        class="absolute -inset-8 animate-pulse rounded-full bg-gradient-to-r from-blue-600 to-purple-600 opacity-20 blur-2xl"
                    ></div>

                    <img
                        v-if="logoLoaded"
                        :src="storeLogo"
                        :alt="`Logo ${storeName}`"
                        class="floating-logo relative z-10 h-56 w-56 object-contain md:h-72 md:w-72"
                    />

                    <div
                        v-else
                        class="flex h-56 w-56 items-center justify-center md:h-72 md:w-72"
                    >
                        <div class="spinner h-16 w-16 rounded-full border-4 border-blue-500 border-t-transparent"></div>
                    </div>
                </div>

                <div
                    class="mt-6 transform text-center transition-all delay-200 duration-700"
                    :class="logoLoaded ? 'translate-y-0 opacity-100' : 'translate-y-4 opacity-0'"
                >
                    <h1 class="text-3xl font-bold tracking-wide text-white md:text-4xl">
                        {{ storeName }}
                    </h1>
                    <p class="mt-2 text-sm text-gray-300 md:text-base">
                        {{ storeTagline }}
                    </p>
                </div>

                <div class="mt-6 h-1 w-40 overflow-hidden rounded-full bg-gray-700 md:w-56">
                    <div
                        class="progress-bar h-full rounded-full bg-gradient-to-r from-blue-500 to-purple-500"
                    ></div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-12px); }
}

@keyframes progress {
    0% { width: 0%; }
    100% { width: 100%; }
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.floating-logo {
    animation: float 3s ease-in-out infinite;
}

.progress-bar {
    animation: progress 1.5s linear forwards;
}

.spinner {
    animation: spin 1s linear infinite;
}
</style>
