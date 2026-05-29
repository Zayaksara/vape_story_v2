<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

// ESC/POS service UUIDs umum dipakai printer thermal Bluetooth (Codeshop CM-T58BL, dll)
const KNOWN_SERVICES = [
    '000018f0-0000-1000-8000-00805f9b34fb', // service umum printer thermal BLE
    '0000ff00-0000-1000-8000-00805f9b34fb',
    '49535343-fe7d-4ae5-8fa9-9fafd205e455', // ISSC / Microchip
    'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
]

const KNOWN_CHARACTERISTICS = [
    '00002af1-0000-1000-8000-00805f9b34fb',
    '0000ff02-0000-1000-8000-00805f9b34fb',
    '49535343-8841-43f4-a8d4-ecbe34729bb3',
    'bef8d6c4-9fbf-4af7-9f8c-3e3a5a3b3c3d',
]

type LogLevel = 'info' | 'success' | 'error'
interface LogEntry { time: string; level: LogLevel; message: string }

const device = ref<BluetoothDevice | null>(null)
const characteristic = ref<BluetoothRemoteGATTCharacteristic | null>(null)
const connecting = ref(false)
const printing = ref(false)
const logs = ref<LogEntry[]>([])
const customText = ref('Halo World')

const supported = typeof navigator !== 'undefined' && !!(navigator as any).bluetooth

function log(message: string, level: LogLevel = 'info') {
    const time = new Date().toLocaleTimeString()
    logs.value.unshift({ time, level, message })
}

async function findWritableCharacteristic(server: BluetoothRemoteGATTServer) {
    const services = await server.getPrimaryServices()
    log(`Ditemukan ${services.length} service`)

    for (const service of services) {
        const chars = await service.getCharacteristics()
        for (const c of chars) {
            if (c.properties.write || c.properties.writeWithoutResponse) {
                log(`Pakai characteristic ${c.uuid} pada service ${service.uuid}`, 'success')
                return c
            }
        }
    }
    throw new Error('Tidak ada characteristic writable pada printer')
}

async function connect() {
    if (!supported) {
        log('Browser tidak mendukung Web Bluetooth. Pakai Chrome/Edge di Android atau desktop.', 'error')
        return
    }

    connecting.value = true
    try {
        log('Membuka dialog pemilihan perangkat Bluetooth…')
        const dev = await (navigator as any).bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: KNOWN_SERVICES,
        }) as BluetoothDevice

        log(`Perangkat dipilih: ${dev.name ?? '(tanpa nama)'} [${dev.id}]`, 'success')
        device.value = dev

        dev.addEventListener('gattserverdisconnected', () => {
            log('Printer terputus.', 'error')
            characteristic.value = null
        })

        log('Menghubungkan GATT…')
        const server = await dev.gatt!.connect()
        log('GATT terhubung. Mencari characteristic writable…', 'success')

        characteristic.value = await findWritableCharacteristic(server)
        log('Siap mencetak.', 'success')
    } catch (err: any) {
        log(`Gagal: ${err?.message ?? err}`, 'error')
    } finally {
        connecting.value = false
    }
}

function disconnect() {
    if (device.value?.gatt?.connected) {
        device.value.gatt.disconnect()
    }
    device.value = null
    characteristic.value = null
    log('Disconnect.')
}

// Bangun byte ESC/POS sederhana
function buildEscPos(text: string): Uint8Array {
    const enc = new TextEncoder()
    const ESC = 0x1b
    const GS = 0x1d
    const LF = 0x0a

    const parts: number[] = []
    // Init
    parts.push(ESC, 0x40)
    // Align center
    parts.push(ESC, 0x61, 0x01)
    // Double height + width
    parts.push(GS, 0x21, 0x11)
    // Text
    for (const b of enc.encode(text)) parts.push(b)
    parts.push(LF)
    // Reset size
    parts.push(GS, 0x21, 0x00)
    // Align left
    parts.push(ESC, 0x61, 0x00)
    // Info line
    for (const b of enc.encode(`Test ${new Date().toLocaleString()}`)) parts.push(b)
    parts.push(LF, LF, LF, LF)
    // Feed & cut (partial)
    parts.push(GS, 0x56, 0x42, 0x00)

    return new Uint8Array(parts)
}

async function writeChunks(c: BluetoothRemoteGATTCharacteristic, data: Uint8Array, chunkSize = 180) {
    for (let i = 0; i < data.length; i += chunkSize) {
        const chunk = data.slice(i, i + chunkSize)
        if (c.properties.writeWithoutResponse) {
            await c.writeValueWithoutResponse(chunk)
        } else {
            await c.writeValue(chunk)
        }
    }
}

async function printTest() {
    if (!characteristic.value) {
        log('Belum terhubung ke printer.', 'error')
        return
    }
    printing.value = true
    try {
        const bytes = buildEscPos(customText.value || 'Halo World')
        log(`Mengirim ${bytes.length} byte…`)
        await writeChunks(characteristic.value, bytes)
        log('Selesai mencetak.', 'success')
    } catch (err: any) {
        log(`Cetak gagal: ${err?.message ?? err}`, 'error')
    } finally {
        printing.value = false
    }
}
</script>

<template>
    <Head title="Printer Test" />
    <div class="mx-auto max-w-2xl space-y-6 p-6">
        <header>
            <h1 class="text-2xl font-bold">Printer Test — Codeshop CM-T58BL</h1>
            <p class="text-sm text-gray-500">Web Bluetooth · ESC/POS</p>
        </header>

        <div v-if="!supported" class="rounded border border-red-300 bg-red-50 p-3 text-sm text-red-700">
            Browser ini tidak mendukung Web Bluetooth. Gunakan Chrome / Edge di Android atau desktop, dan akses via HTTPS / localhost.
        </div>

        <section class="space-y-3 rounded border bg-white p-4">
            <div class="flex flex-wrap items-center gap-2">
                <button
                    class="rounded bg-blue-600 px-4 py-2 text-white disabled:opacity-50"
                    :disabled="connecting || !!characteristic"
                    @click="connect"
                >
                    {{ connecting ? 'Mencari…' : 'Cari & Hubungkan Printer' }}
                </button>
                <button
                    v-if="device"
                    class="rounded border px-4 py-2"
                    @click="disconnect"
                >
                    Disconnect
                </button>
            </div>

            <div class="text-sm">
                <div>Status:
                    <span v-if="characteristic" class="font-semibold text-green-600">Terhubung</span>
                    <span v-else-if="device" class="font-semibold text-yellow-600">Dipilih, belum siap</span>
                    <span v-else class="font-semibold text-gray-500">Belum terhubung</span>
                </div>
                <div v-if="device">Perangkat: <span class="font-mono">{{ device.name ?? '(tanpa nama)' }}</span></div>
            </div>
        </section>

        <section class="space-y-3 rounded border bg-white p-4">
            <label class="block text-sm font-medium">Teks yang dicetak</label>
            <input
                v-model="customText"
                type="text"
                class="w-full rounded border px-3 py-2"
                placeholder="Halo World"
            >
            <button
                class="rounded bg-green-600 px-4 py-2 text-white disabled:opacity-50"
                :disabled="!characteristic || printing"
                @click="printTest"
            >
                {{ printing ? 'Mencetak…' : 'Cetak Test' }}
            </button>
        </section>

        <section class="rounded border bg-white p-4">
            <h2 class="mb-2 text-sm font-semibold">Log</h2>
            <div class="max-h-72 overflow-auto rounded bg-gray-900 p-3 font-mono text-xs text-gray-100">
                <div v-if="!logs.length" class="text-gray-500">(belum ada log)</div>
                <div
                    v-for="(l, i) in logs"
                    :key="i"
                    :class="{
                        'text-green-400': l.level === 'success',
                        'text-red-400': l.level === 'error',
                    }"
                >
                    [{{ l.time }}] {{ l.message }}
                </div>
            </div>
        </section>
    </div>
</template>
