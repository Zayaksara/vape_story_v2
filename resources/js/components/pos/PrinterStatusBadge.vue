<script setup lang="ts">
import { onMounted } from 'vue';
import { Printer, PrinterCheck, AlertCircle } from 'lucide-vue-next';
import { usePrinter } from '@/composables/usePrinter';

const printer = usePrinter();

async function handleClick() {
    if (printer.ready.value) return;
    // Coba auto-connect dulu (no dialog). Kalau tetap gagal, fallback ke pairing.
    const ok = await printer.tryAutoConnect();
    if (!ok) await printer.pair();
}

onMounted(() => {
    if (printer.supported && !printer.ready.value) {
        printer.tryAutoConnect().catch(() => { /* silent */ });
    }
});
</script>

<template>
    <button
        type="button"
        class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-xs transition hover:bg-muted"
        :title="printer.lastMessage.value || (printer.ready.value ? 'Printer siap' : 'Klik untuk hubungkan printer')"
        @click="handleClick"
    >
        <span
            class="relative flex h-6 w-6 items-center justify-center rounded-full"
            :class="{
                'bg-green-100 text-green-700': printer.ready.value,
                'bg-yellow-100 text-yellow-700': printer.status.value === 'connecting',
                'bg-red-100 text-red-700': printer.status.value === 'error',
                'bg-gray-100 text-gray-600': !printer.ready.value && printer.status.value === 'idle',
            }"
        >
            <PrinterCheck v-if="printer.ready.value" class="h-3.5 w-3.5" />
            <AlertCircle v-else-if="printer.status.value === 'error'" class="h-3.5 w-3.5" />
            <Printer v-else class="h-3.5 w-3.5" />
        </span>
        <span class="min-w-0 flex-1 truncate text-left">
            <span v-if="printer.ready.value" class="font-medium">{{ printer.deviceName.value || 'Printer' }}</span>
            <span v-else-if="printer.status.value === 'connecting'">Menghubungkan…</span>
            <span v-else-if="printer.status.value === 'error'" class="text-red-600">Printer offline</span>
            <span v-else>Hubungkan printer</span>
        </span>
    </button>
</template>
