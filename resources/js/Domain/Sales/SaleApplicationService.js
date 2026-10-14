import axios from 'axios';
import { offlineSaleRepository } from '@/Offline/repositories/OfflineSaleRepository';
import { nextDeviceSequence } from '@/Offline/services/device';

const isAmbiguousNetworkFailure = error => !error.response || [502, 503, 504].includes(error.response.status) || error.code === 'ECONNABORTED';

export class SaleApplicationService {
    constructor(connectivity) { this.connectivity = connectivity; }

    async complete(command) {
        const normalized = { ...command, saleId: command.saleId ?? crypto.randomUUID(), operationId: command.operationId ?? crypto.randomUUID(), occurredAt: command.occurredAt ?? new Date().toISOString() };
        if (!this.connectivity.isUsableOnline) return offlineSaleRepository.storePending(normalized);
        normalized.sequence = normalized.sequence ?? await nextDeviceSequence();

        try {
            const result = await this.send(normalized);
            await offlineSaleRepository.cacheConfirmed(normalized, result);
            return result;
        } catch (error) {
            if (!isAmbiguousNetworkFailure(error)) throw error;
            const existing = await this.findOperation(normalized.operationId).catch(() => null);
            if (existing?.result) {
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
