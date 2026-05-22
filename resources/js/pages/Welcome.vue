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
const storeTagline = computed(() => (page.props.storeTagline as string | null | undefined) ?? 'premium vape sejak 2020');

const SPLASH_DURATION = 1800;

const showSplash = ref(true);
const fadeOut = ref(false);
const isMounted = ref(false);

let splashTimer: ReturnType<typeof setTimeout> | null = null;
let fadeTimer: ReturnType<typeof setTimeout> | null = null;

// Pecah brand name jadi karakter agar bisa stagger reveal
const brandChars = computed(() => Array.from(storeName.value.toUpperCase()));

function handleSplashComplete() {
    fadeOut.value = true;
    fadeTimer = setTimeout(() => {
        showSplash.value = false;
        router.visit(login());
    }, 500);
}

onMounted(() => {
    isMounted.value = true;
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
        enter-active-class="transition-opacity duration-500 ease-out"
        leave-active-class="transition-opacity duration-500 ease-in"
        enter-from-class="opacity-0"
        leave-to-class="opacity-0"
    >
        <div
            v-if="showSplash"
            class="splash-root fixed inset-0 z-50 flex flex-col items-center justify-center"
        >
            <!-- Vignette halus di tepi (bukan gradient warna, hanya kontras) -->
            <div class="vignette pointer-events-none absolute inset-0" />

            <div class="relative z-10 flex flex-col items-center" :class="{ 'is-mounted': isMounted }">
                <!-- Logo: clip-path reveal dari bawah ke atas -->
                <div class="logo-wrapper">
                    <img
                        :src="storeLogo"
                        :alt="`Logo ${storeName}`"
                        class="logo h-60 w-60 object-contain md:h-72 md:w-72"
                        draggable="false"
                    />
                </div>

                <!-- Brand name: letter-by-letter reveal -->
                <h1 class="brand mt-20 flex select-none">
                    <span
                        v-for="(ch, i) in brandChars"
                        :key="i"
                        class="brand-char"
                        :style="{ animationDelay: `${300 + i * 55}ms` }"
                    >{{ ch === ' ' ? ' ' : ch }}</span>
                </h1>

                <!-- Tagline -->
                <p class="tagline mt-9">{{ storeTagline }}</p>

                <!-- Progress underline tipis -->
                <div class="progress-track mt-28">
                    <div class="progress-fill" />
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.splash-root {
    background: #0a0a0a;
    color: #f5f5f4;
}

/* Vignette ringan supaya pusat tampak lebih fokus, tanpa gradient warna */
.vignette {
    background: radial-gradient(
        ellipse at center,
        transparent 0%,
        transparent 55%,
        rgba(0, 0, 0, 0.55) 100%
    );
}

/* ── Logo: clip-path reveal (Razer-style mask wipe) ───────────────────────── */
.logo-wrapper {
    overflow: hidden;
    line-height: 0;
}

.logo {
    opacity: 0;
    clip-path: inset(100% 0 0 0);
    transform: translateY(8px);
    will-change: clip-path, opacity, transform;
}

.is-mounted .logo {
    animation: logo-reveal 900ms cubic-bezier(0.65, 0, 0.35, 1) 100ms forwards;
}

@keyframes logo-reveal {
    0% {
        opacity: 0;
        clip-path: inset(100% 0 0 0);
        transform: translateY(8px);
    }
    60% {
        opacity: 1;
    }
    100% {
        opacity: 1;
        clip-path: inset(0 0 0 0);
        transform: translateY(0);
    }
}

/* ── Brand name: letter stagger ───────────────────────────────────────────── */
.brand {
    font-family: 'Inter', system-ui, sans-serif;
    font-weight: 600;
    font-size: 2.10rem;
    letter-spacing: 0.55em;
    color: #fafaf9;
    padding-left: 0.55em;
}

.brand-char {
    display: inline-block;
    opacity: 0;
    transform: translateY(6px);
}

.is-mounted .brand-char {
    animation: char-reveal 600ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
    /* animation-delay di-pass via inline style per karakter (stagger) */
}

@keyframes char-reveal {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ── Tagline: simple fade-in setelah brand ────────────────────────────────── */
.tagline {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 1.4rem;
    font-weight: 300;
    letter-spacing: 0.18em;
    text-transform: lowercase;
    color: #a8a29e;
    opacity: 0;
}

.is-mounted .tagline {
    animation: fade-up 700ms cubic-bezier(0.22, 1, 0.36, 1) 1100ms forwards;
}

@keyframes fade-up {
    from {
        opacity: 0;
        transform: translateY(4px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ── Progress underline tipis ─────────────────────────────────────────────── */
.progress-track {
    position: relative;
    height: 3px;
    width: 360px;
    background: rgba(245, 245, 244, 0.08);
    overflow: hidden;
}

.progress-fill {
    position: absolute;
    inset: 0 100% 0 0;
    background: #fafaf9;
    transform-origin: left center;
    opacity: 0;
}

.is-mounted .progress-fill {
    animation: progress-grow 1300ms cubic-bezier(0.65, 0, 0.35, 1) 400ms forwards;
}

@keyframes progress-grow {
    0% {
        right: 100%;
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    100% {
        right: 0%;
        opacity: 1;
    }
}
</style>
