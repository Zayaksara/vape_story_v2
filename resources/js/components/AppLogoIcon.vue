<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!--
        Logo Vape Story — siluet pod device dengan kepulan uap.
        Single-color (currentColor) supaya tetap nyatu dengan tema teal/putih.
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 42"
        :class="className"
        v-bind="$attrs"
        aria-label="Vape Story"
    >
        <!-- Pod body (badan vape) -->
        <rect
            x="14"
            y="14"
            width="12"
            height="22"
            rx="3"
            fill="currentColor"
        />

        <!-- Mouthpiece (corong atas) -->
        <rect
            x="16.5"
            y="9"
            width="7"
            height="6"
            rx="1.5"
            fill="currentColor"
        />

        <!-- Indicator LED di badan -->
        <circle cx="20" cy="22" r="1.4" fill="currentColor" opacity="0.35" />

        <!-- Kepulan uap kiri-atas -->
        <path
            d="M9 7c0-1.5 1.2-2.5 2.6-2.5 1 0 1.6.5 2 1.1.6-.8 1.6-1.3 2.7-1.3 1.6 0 2.8 1.2 2.8 2.7 0 .5-.1.9-.3 1.3"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            fill="none"
            opacity="0.85"
        />

        <!-- Kepulan uap kanan-atas (lebih kecil) -->
        <path
            d="M27 4.5c.5-.6 1.3-.9 2.1-.8 1.3.1 2.2 1.1 2.1 2.3"
            stroke="currentColor"
            stroke-width="1.4"
            stroke-linecap="round"
            fill="none"
            opacity="0.7"
        />
    </svg>
</template>
