import { runTransaction } from '../database/db';
import { getOfflineContext } from './context';

const LEGACY_KEY = 'deviceId';
const keyFor = (context) => (context ? `deviceId:${context.userId}:${context.branchId}` : LEGACY_KEY);

async function resolveDeviceId(context, rotate = false, rejectedId = null) {
    const key = keyFor(context);
    let value;
    // Bootstrap and activation (including other tabs) must share one identity.
    // Keep reading and writing inside the same serialized IndexedDB transaction.
    await runTransaction(['appMetadata'], 'readwrite', transaction => {
        const store = transaction.objectStore('appMetadata');
        const request = store.get(key);
        request.onsuccess = () => {
            const existing = request.result?.value;
            if (existing && (!rotate || (rejectedId && existing !== rejectedId))) {
                value = existing;
            } else if (rotate) {
                value = crypto.randomUUID();
                store.put({ key, value });
            } else {
                const legacy = store.get(LEGACY_KEY);
                legacy.onsuccess = () => {
                    value = legacy.result?.value ?? crypto.randomUUID();
                    store.put({ key, value });
                };
            }
        };
    });
    return value;
}

/**
 * Identificador del dispositivo para el usuario y la sucursal actuales.
 * La primera vez que se usa un contexto se reutiliza el identificador anterior
 * (de antes de separar por contexto) para no perder su registro en el servidor;
 * si el servidor responde que pertenece a otro contexto, se rota con rotateDeviceId().
 */
export async function getOrCreateDeviceId(context = getOfflineContext()) {
    return resolveDeviceId(context);
}

export async function rotateDeviceId(context = getOfflineContext(), rejectedId = null) {
    return resolveDeviceId(context, true, rejectedId);
}

export const isDeviceContextMismatch = (error) => error?.response?.status === 409
    && error.response?.data?.code === 'DEVICE_CONTEXT_MISMATCH';

/**
 * Ejecuta una llamada que necesita el identificador del dispositivo; si el servidor
 * dice que ese identificador está registrado para otro usuario o sucursal, genera
 * uno nuevo para el contexto actual y reintenta una vez.
 */
export async function withDeviceId(callback, context = getOfflineContext()) {
    const deviceId = await getOrCreateDeviceId(context);
    try {
        return await callback(deviceId);
    } catch (error) {
        if (!isDeviceContextMismatch(error)) throw error;
        return callback(await rotateDeviceId(context, deviceId));
    }
}

export async function nextDeviceSequence() {
    const result = { value: 0 };
    await runTransaction(['syncState'], 'readwrite', transaction => {
        const request = transaction.objectStore('syncState').get('device-sequence');
        request.onsuccess = () => {
            result.value = Number(request.result?.value ?? 0) + 1;
            transaction.objectStore('syncState').put({ id: 'device-sequence', value: result.value });
        };
    });
    return result.value;
}
