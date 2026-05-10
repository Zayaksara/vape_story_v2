<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { login } from '@/routes';

const props = withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const showSplash = ref(true);
const logoLoaded = ref(false);
const fadeOut = ref(false);

const handleSplashComplete = () => {
    fadeOut.value = true;
    setTimeout(() => {
        showSplash.value = false;
        router.visit(login());
    }, 800);
};

onMounted(() => {
    const img = new Image();
    img.src = '/storage/images/logo.png';
    img.onload = () => {
        logoLoaded.value = true;
    };

    setTimeout(() => {
        handleSplashComplete();
    }, 4000);
});
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <!-- Splash Screen -->
    <Transition
        enter-active-class="transition-opacity duration-1000"
        leave-active-class="transition-opacity duration-800"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="showSplash"
            class="fixed inset-0 z-50 flex items-center justify-center bg-gradient-to-br from-gray-900 via-gray-800 to-black"
        >
            <div class="relative flex flex-col items-center justify-center">
                <!-- Animated Logo Container -->
                <div
                    class="relative transform transition-all duration-1000 ease-out"
                    :class="
                        logoLoaded
                            ? 'scale-100 opacity-100'
                            : 'scale-50 opacity-0'
                    "
                >
                    <div
                        class="absolute -inset-8 animate-pulse rounded-full bg-gradient-to-r from-blue-600 to-purple-600 opacity-20 blur-2xl"
                    ></div>

                    <img
                        v-if="logoLoaded"
                        src="/storage/images/logo.png"
                        alt="Logo"
                        class="floating-logo relative z-10 h-72 w-72 object-contain md:h-96 md:w-96"
                    />

                    <!-- Loading Spinner -->
                    <div
                        v-if="!logoLoaded"
                        class="flex h-72 w-72 items-center justify-center md:h-96 md:w-96"
                    >
                        <div
                            class="spinner h-20 w-20 rounded-full border-4 border-blue-500 border-t-transparent"
                        ></div>
                    </div>
                </div>

                <div
                    class="mt-8 transform transition-all delay-300 duration-1000"
                    :class="
                        logoLoaded
                            ? 'translate-y-0 opacity-100'
                            : 'translate-y-4 opacity-0'
                    "
                >
                    <h1
                        class="fade-in-up text-3xl font-bold tracking-wide text-white md:text-4xl"
                    >
                        Vape Story
                    </h1>
                    <p
                        class="fade-in-up-delay mt-2 text-center text-sm text-gray-400 md:text-base"
                    >
                        Sabar yaaaa...
                    </p>
                </div>

                <div
                    class="mt-8 h-1 w-48 overflow-hidden rounded-full bg-gray-700 md:w-64"
                >
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
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-20px);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes progress {
    0% {
        width: 0%;
    }
    100% {
        width: 100%;
    }
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

.floating-logo {
    animation: float 3s ease-in-out infinite;
}

.fade-in-up {
    animation: fadeInUp 0.8s ease-out forwards;
}

.fade-in-up-delay {
    animation: fadeInUp 0.8s ease-out 0.5s forwards;
    opacity: 0;
}

.progress-bar {
    animation: progress 4s linear forwards;
}

.spinner {
    animation: spin 1s linear infinite;
}
</style>
