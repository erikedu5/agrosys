// Usuario y sucursal con los que trabaja la app en este momento. El servidor
// registra cada dispositivo offline para un solo usuario y sucursal, así que el
// identificador del dispositivo se guarda por contexto.
let current = null;

export function setOfflineContext({ userId, branchId } = {}) {
    current = userId && branchId ? { userId: String(userId), branchId: String(branchId) } : null;
}

export function getOfflineContext() {
    return current;
}
