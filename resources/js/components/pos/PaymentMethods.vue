<template>
    <div class="payment-methods grid grid-cols-2 gap-3">
        <button
            v-for="method in methods"
            :key="method.key"
            type="button"
            class="payment-button group relative flex flex-col items-center gap-2.5 rounded-2xl border-2 bg-background p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
            :class="{
                'border-primary bg-primary/5 ring-primary':
                    modelValue === method.key,
                'border-border bg-background hover:border-border/60 hover:bg-muted/30':
                    modelValue !== method.key,
                'cursor-not-allowed opacity-60 hover:translate-y-0 hover:shadow-none':
                    disabled,
                'ring-2': modelValue === method.key,
            }"
            :disabled="disabled"
            @click="$emit('update:modelValue', method.key)"
            :aria-pressed="modelValue === method.key"
            :aria-label="`Pembayaran ${method.label}`"
        >
            <!-- Icon Container -->
            <div
            class="flex h-14 w-14 items-center justify-center rounded-xl transition-all duration-200"
            :class="{
            'bg-primary/10': modelValue === method.key,
            'bg-muted': modelValue !== method.key,
            }"
            >
                <svg
                    class="h-7 w-7 transition-all duration-200"
                    :class="
                        modelValue === method.key
                            ? 'text-primary'
                            : 'text-muted-foreground'
                    "
                    :style="
                        modelValue === method.key ? { color: method.color } : {}
                    "
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    :aria-hidden="true"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.75"
                        :d="getIconPath(method.key)"
                    />
                </svg>
            </div>

            <!-- Label -->
            <span
                class="text-sm font-semibold transition-colors duration-200"
                :class="{
                    'text-primary': modelValue === method.key,
                    'text-foreground': modelValue !== method.key,
                }"
                :style="
                    modelValue === method.key ? { color: method.color } : {}
                "
            >
                {{ method.label }}
            </span>

            <!-- Selection Indicator -->
            <div
                class="absolute -top-1 -right-1 hidden h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold text-white transition-all duration-200"
                :class="modelValue === method.key ? 'flex' : 'hidden'"
                :style="{ backgroundColor: method.color }"
            >
                <svg
                    class="h-3 w-3"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="3"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
            </div>
        </button>
    </div>
</template>

<script setup lang="ts">
import type { PaymentMethod } from '@/types/pos';

const props = defineProps<{
    modelValue: PaymentMethod;
    disabled?: boolean;
}>();

defineEmits<{
    (e: 'update:modelValue', method: PaymentMethod): void;
}>();

const methods = [
    {
        key: 'cash' as PaymentMethod,
        label: 'Cash',
        color: '#10B981',
    },
    {
        key: 'debit' as PaymentMethod,
        label: 'Debit',
        color: '#3B82F6',
    },
    {
        key: 'qris' as PaymentMethod,
        label: 'QRIS',
        color: '#8B5CF6',
    },
    {
        key: 'ewallet' as PaymentMethod,
        label: 'E-Wallet',
        color: '#F59E0B',
    },
];

function getIconPath(method: PaymentMethod): string {
    switch (method) {
        case 'cash':
            return 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z';
        case 'debit':
            return 'M2 5h20v14a2 2 0 01-2 2H4a2 2 0 01-2-2V7a2 2 0 012-2zM2 10h20';
        case 'qris':
            return 'M3 3h7v7H3V3zm11 0h7v7h-7V3zM3 14h7v7H3v-7zm11 0h7v7h-7v-7z';
        case 'ewallet':
            return 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z';
        default:
            return '';
    }
}

</script>
