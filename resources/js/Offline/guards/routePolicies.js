import { hasOfflineCapability } from '../capabilities';

const policies = [
    { pattern: /^\/dashboard\/?$/, availableOffline: true, capability: 'catalog.read' },
    { pattern: /^\/venta\/?$/, availableOffline: true, capability: 'catalog.read' },
    { pattern: /^\/buscar-precio\/?$/, availableOffline: true, capability: 'product.search' },
    { pattern: /^\/api\/v1\/offline\//, availableOffline: true },
];

export const routeOfflinePolicies = Object.freeze(policies);

export function canVisitOffline(url) {
    const parsed = new URL(url, window.location.origin);
    const policy = policies.find((candidate) => candidate.pattern.test(parsed.pathname));

    if (!policy?.availableOffline) return false;
    return !policy.capability || hasOfflineCapability(policy.capability);
}
