import axios from 'axios';
import { offlineSaleRepository } from '@/Offline/repositories/OfflineSaleRepository';
import { rememberCashSession, rememberedCashSession } from '@/Offline/services/cashSession';
import { nextDeviceSequence } from '@/Offline/services/device';

const isAmbiguousNetworkFailure = error => !error.result && (!error.response || [502, 503, 504].includes(error.response.status) || error.code === 'ECONNABORTED');
const isConfirmed = result => (result?.originalStatus ?? result?.status) === 'confirmed' && result?.serverSaleId != null;

export class SaleApplicationService {
    constructor(connectivity) { this.connectivity = connectivity; }

    async complete(command) {
        const normalized = { ...command, saleId: command.saleId ?? crypto.randomUUID(), operationId: command.operationId ?? crypto.randomUUID(), occurredAt: command.occurredAt ?? new Date().toISOString() };
        if (localStorage.getItem(`cash-request:${command.userId}:${command.branchId}`)) throw new Error('Confirma la solicitud pendiente de caja antes de vender.');
        normalized.cashSessionId = rememberedCashSession(command.userId, command.branchId, command.cashSessionId ?? null);
        if (!this.connectivity.isUsableOnline) {
            if (!normalized.cashSessionId) throw new Error('Abre la caja antes de realizar una venta.');
            return offlineSaleRepository.storePending(normalized);
        }
        try {
            const { data } = await axios.post('/caja/status', {}, { timeout: 10000 });
            normalized.cashSessionId = data.current?.id ?? null;
            rememberCashSession(command.userId, command.branchId, normalized.cashSessionId);
        } catch (error) {
            if (!isAmbiguousNetworkFailure(error)) throw error;
        }
        if (!normalized.cashSessionId) throw new Error('Abre la caja antes de realizar una venta.');
        normalized.sequence = normalized.sequence ?? await nextDeviceSequence();

        try {
            const result = await this.send(normalized);
            if (!isConfirmed(result)) return offlineSaleRepository.storePending(normalized);
            await offlineSaleRepository.cacheConfirmed(normalized, result);
            return result;
        } catch (error) {
            if (!isAmbiguousNetworkFailure(error)) throw error;
            const existing = await this.findOperation(normalized.operationId).catch(() => null);
            if (existing?.status === 'confirmed' && isConfirmed(existing.result)) {
                await offlineSaleRepository.cacheConfirmed(normalized, existing.result);
                return existing.result;
            }
            return offlineSaleRepository.storePending(normalized);
        }
    }

    async send(command) {
        const response = await axios.post('/api/v1/offline/sync/push', { device_id: command.deviceId, branch_id: command.branchId, operations: [{ operation_id: command.operationId, aggregate_type: 'sale', aggregate_id: command.saleId, event_type: 'SALE_COMPLETED', sequence: command.sequence ?? Date.now(), occurred_at: command.occurredAt, payload: command }] }, { headers: { 'Idempotency-Key': command.operationId, Accept: 'application/json' }, timeout: 20000 });
        const result = response.data.results[0];
        if (['conflict', 'rejected', 'blocked'].includes(result.status)) throw Object.assign(new Error(result.message), { code: result.errorCode, result });
        return result;
    }

    async findOperation(operationId) {
        const { data } = await axios.get(`/api/v1/offline/operations/${operationId}`, { timeout: 5000, headers: { Accept: 'application/json' } });
        return data;
    }
}
