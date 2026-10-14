const key = (user, branch) => `cash-session:${user}:${branch}`;
export function rememberCashSession(user, branch, id) {
    localStorage.setItem(key(user, branch), JSON.stringify(id ?? null));
}
export function rememberedCashSession(user, branch, fallback = null) {
    const stored = localStorage.getItem(key(user, branch));
    return stored === null ? fallback : JSON.parse(stored);
}
