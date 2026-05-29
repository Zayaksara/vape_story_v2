import { computed, ref } from 'vue';

const STORAGE_KEY = 'pos.printer.deviceId';
const STORAGE_NAME_KEY = 'pos.printer.deviceName';

const KNOWN_SERVICES = [
    '000018f0-0000-1000-8000-00805f9b34fb',
    '0000ff00-0000-1000-8000-00805f9b34fb',
    '49535343-fe7d-4ae5-8fa9-9fafd205e455',
    'e7810a71-73ae-499d-8c15-faa9aef0c3f2',
];

type Status = 'idle' | 'connecting' | 'ready' | 'error';

// Singleton state (module-level) — shared across all components using this composable
const device = ref<any>(null);
const characteristic = ref<any>(null);
const status = ref<Status>('idle');
const lastMessage = ref<string>('');
const printing = ref(false);

const supported = typeof navigator !== 'undefined' && !!(navigator as any).bluetooth;
const ready = computed(() => !!characteristic.value);
const deviceName = computed(() => device.value?.name ?? null);

function getSavedDeviceName(): string | null {
    try { return localStorage.getItem(STORAGE_NAME_KEY); } catch { return null; }
}
function getInitialSavedDeviceId(): string | null {
    try { return typeof window !== 'undefined' ? localStorage.getItem(STORAGE_KEY) : null; } catch { return null; }
}
const savedDeviceName = ref<string | null>(typeof window !== 'undefined' ? getSavedDeviceName() : null);
const savedDeviceId = ref<string | null>(getInitialSavedDeviceId());
const hasSavedDevice = computed(() => !!savedDeviceId.value);

function setMsg(s: Status, msg = '') {
    status.value = s;
    if (msg) lastMessage.value = msg;
}

function persistDeviceId(id: string | null) {
    try {
        if (id) localStorage.setItem(STORAGE_KEY, id);
        else localStorage.removeItem(STORAGE_KEY);
    } catch { /* ignore */ }
    savedDeviceId.value = id;
}

function persistDeviceName(name: string | null) {
    try {
        if (name) localStorage.setItem(STORAGE_NAME_KEY, name);
        else localStorage.removeItem(STORAGE_NAME_KEY);
    } catch { /* ignore */ }
    savedDeviceName.value = name;
}

function getSavedDeviceId(): string | null {
    try { return localStorage.getItem(STORAGE_KEY); } catch { return null; }
}

async function findWritableChar(server: any) {
    const services = await server.getPrimaryServices();
    for (const service of services) {
        const chars = await service.getCharacteristics();
        for (const c of chars) {
            if (c.properties.write || c.properties.writeWithoutResponse) return c;
        }
    }
    throw new Error('Tidak ada characteristic writable di printer');
}

function attachDisconnectHandler(dev: any) {
    dev.addEventListener('gattserverdisconnected', () => {
        characteristic.value = null;
        setMsg('error', 'Printer terputus. Klik Reconnect untuk hubungkan ulang.');
    });
}

async function connectToDevice(dev: any) {
    device.value = dev;
    attachDisconnectHandler(dev);
    const server = await dev.gatt!.connect();
    characteristic.value = await findWritableChar(server);
    persistDeviceId(dev.id);
    persistDeviceName(dev.name ?? null);
    setMsg('ready', `Terhubung ke ${dev.name ?? '(tanpa nama)'}`);
}

async function pair() {
    if (!supported) { setMsg('error', 'Browser tidak mendukung Web Bluetooth.'); return false; }
    setMsg('connecting', 'Membuka dialog pemilihan printer…');
    try {
        const dev = await (navigator as any).bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: KNOWN_SERVICES,
        });
        await connectToDevice(dev);
        return true;
    } catch (err: any) {
        setMsg('error', `Gagal: ${err?.message ?? err}`);
        return false;
    }
}

/**
 * Coba reconnect tanpa dialog ke device yg sudah pernah di-pair.
 * Butuh `getDevices()` (Chrome modern) dan user gesture untuk gatt.connect().
 * Return true jika berhasil.
 */
async function tryAutoConnect(): Promise<boolean> {
    if (!supported) return false;
    if (ready.value) return true;
    const bt = (navigator as any).bluetooth;
    if (typeof bt.getDevices !== 'function') return false;

    setMsg('connecting', 'Mencari printer tersimpan…');
    try {
        const savedId = getSavedDeviceId();
        const devices: any[] = await bt.getDevices();
        const match = savedId ? devices.find(d => d.id === savedId) : devices[0];
        if (!match) { setMsg('idle', 'Belum ada printer tersimpan.'); return false; }
        await connectToDevice(match);
        return true;
    } catch (err: any) {
        setMsg('error', `Auto-connect gagal: ${err?.message ?? err}`);
        return false;
    }
}

function disconnect() {
    if (device.value?.gatt?.connected) device.value.gatt.disconnect();
    device.value = null;
    characteristic.value = null;
    persistDeviceId(null);
    persistDeviceName(null);
    setMsg('idle', 'Disconnect.');
}

async function printBytes(data: Uint8Array, chunkSize = 180) {
    if (!characteristic.value) throw new Error('Printer belum terhubung');
    printing.value = true;
    try {
        const c = characteristic.value;
        for (let i = 0; i < data.length; i += chunkSize) {
            const chunk = data.slice(i, i + chunkSize);
            if (c.properties.writeWithoutResponse) await c.writeValueWithoutResponse(chunk);
            else await c.writeValue(chunk);
        }
    } finally {
        printing.value = false;
    }
}

export function usePrinter() {
    return {
        // state
        device, characteristic, status, lastMessage, printing,
        ready, deviceName, supported,
        savedDeviceName, hasSavedDevice,
        // actions
        pair, tryAutoConnect, disconnect, printBytes,
    };
}
