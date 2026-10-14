import { openOfflineDatabase, requestAsPromise, runTransaction } from '../database/db';
import { getOfflineContext } from './context';

const LEGACY_KEY = 'deviceId';
const keyFor = (context) => (context ? `deviceId:${context.userId}:${context.branchId}` : LEGACY_KEY);

async function readKey(key) {
    const database = await openOfflineDatabase();
    const record = await requestAsPromise(database.transaction('appMetadata', 'readonly').objectStore('appMetadata').get(key));
    return record?.value ?? null;
}

async function writeKey(key, value) {
    await runTransaction(['appMetadata'], 'readwrite', transaction => transaction.objectStore('appMetadata').put({ key, value }));
}

/**
 * Identificador del dispositivo para el usuario y la sucursal actuales.
 * La primera vez que se usa un contexto se reutiliza el identificador anterior
 * (de antes de separar por contexto) para no perder su registro en el servidor;
 * si el servidor responde que pertenece a otro contexto, se rota con rotateDeviceId().
 */
export async function getOrCreateDeviceId(context = getOfflineContext()) {
    const key = keyFor(context);
    const existing = await readKey(key);
    if (existing) return existing;
    const value = (await readKey(LEGACY_KEY)) ?? crypto.randomUUID();
    await writeKey(key, value);
    return value;
}

export async function rotateDeviceId(context = getOfflineContext()) {
    const value = crypto.randomUUID();
    await writeKey(keyFor(context), value);
    return value;
}

export const isDeviceContextMismatch = (error) => error?.response?.status === 409
    && error.response?.data?.code === 'DEVICE_CONTEXT_MISMATCH';

/**
 * Ejecuta una llamada que necesita el identificador del dispositivo; si el servidor
 * dice que ese identificador está registrado para otro usuario o sucursal, genera
 * uno nuevo para el contexto actual y reintenta una vez.
 */
export async function withDeviceId(callback, context = getOfflineContext()) {
    try {
        return await callback(await getOrCreateDeviceId(context));
    } catch (error) {
        if (!isDeviceContextMismatch(error)) throw error;
        return callback(await rotateDeviceId(context));
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
