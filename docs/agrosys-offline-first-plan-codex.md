# Agrosys: misma PWA con modo offline temporal
## Plan técnico para Codex

**Stack:** Laravel + Inertia.js + Vue 3 + Tailwind CSS  
**Decisión:** mantener una sola PWA. No crear otra SPA, otra entrada de Vite ni otra pantalla de punto de venta.

---

## 1. Objetivo

Agrosys funcionará normalmente en línea. Únicamente durante cortes de internet activará un modo degradado que:

- Mantiene abierta la misma aplicación.
- Conserva el mismo layout y la pantalla actual de venta.
- Bloquea los módulos que requieren Laravel.
- Permite consultar datos previamente sincronizados.
- Permite registrar ventas autorizadas localmente.
- Guarda las operaciones en una cola local.
- Sincroniza automáticamente al regresar la conexión.
- Reactiva la aplicación completa después de confirmar que Laravel está disponible.

```text
ONLINE
Vue/Inertia → Laravel → base central

OFFLINE TEMPORAL
Vue/Inertia ya cargado → IndexedDB → Outbox

RECUPERACIÓN
Outbox → Laravel → confirmación → pull de cambios → ONLINE
```

---

## 2. Restricciones

Codex debe respetar estas decisiones:

1. No crear otra SPA.
2. No crear otra entrada de Vite para el POS.
3. No duplicar componentes ni páginas de venta.
4. No cambiar el dominio o URL del POS.
5. Mantener Inertia y el layout actual.
6. Agregar el modo offline de forma transversal.
7. Usar IndexedDB para datos de negocio.
8. No usar `localStorage` para ventas, pagos, inventario u Outbox.
9. No guardar contraseñas localmente.
10. No sincronizar cantidades absolutas de inventario.
11. Toda venta debe tener un `operationId`, incluso si se registra online.
12. Los reintentos deben ser idempotentes.

---

## 3. Estados de la aplicación

```typescript
export type ConnectivityMode =
  | "online"
  | "offline"
  | "recovering"
  | "sync_error";
```

### `online`

- Todos los módulos funcionan.
- Los datos se consultan normalmente en Laravel.
- Se sincronizan operaciones pendientes en segundo plano.

### `offline`

- Se muestra un banner permanente.
- Se bloquean rutas y acciones incompatibles.
- Se usan datos de IndexedDB.
- Las ventas se agregan a Outbox.

### `recovering`

Se detectó red, pero todavía no se confirma que Laravel funcione.

- Ejecutar health check.
- Enviar Outbox.
- Descargar cambios.
- No habilitar todavía todos los módulos.

### `sync_error`

Existe conexión, pero una o más operaciones requieren revisión.

- Mantener operaciones locales.
- Mostrar errores y reintentos.
- Nunca eliminar ventas silenciosamente.

Máquina de estados:

```text
ONLINE ── pérdida de conexión ──► OFFLINE
  ▲                                  │
  │                                  │ red detectada
  │                                  ▼
  └── sincronización completa ── RECOVERING
                                      │
                                      └── error ──► SYNC_ERROR
```

---

## 4. Funciones disponibles offline

### Disponibles en el MVP

- Abrir la PWA previamente instalada.
- Mantener una sesión previamente validada.
- Consultar catálogo local.
- Buscar por nombre, SKU y código de barras.
- Consultar último precio sincronizado.
- Consultar stock local estimado.
- Registrar venta.
- Registrar partidas.
- Registrar pago en efectivo.
- Imprimir ticket provisional.
- Reimprimir venta local.
- Consultar ventas pendientes.
- Ejecutar sincronización.
- Cancelar una venta aún no sincronizada mediante evento compensatorio.

### Bloqueadas offline

- Reportes.
- Compras.
- Proveedores.
- Usuarios.
- Configuración.
- Ajustes manuales de inventario.
- Cambios de precio.
- Facturación CFDI.
- Cancelación de ventas ya sincronizadas.
- Terminal bancaria.
- Cambio de sucursal.
- Operaciones que requieran validación en tiempo real.

---

## 5. Capacidades offline centralizadas

No llenar los componentes con comprobaciones directas de `navigator.onLine`.

```typescript
export type OfflineCapability =
  | "catalog.read"
  | "product.search"
  | "stock.read_estimated"
  | "sale.create"
  | "sale.cancel_unsynced"
  | "sale.print_local_ticket"
  | "sync.view"
  | "sync.retry";

export const offlineCapabilities: Record<OfflineCapability, boolean> = {
  "catalog.read": true,
  "product.search": true,
  "stock.read_estimated": true,
  "sale.create": true,
  "sale.cancel_unsynced": true,
  "sale.print_local_ticket": true,
  "sync.view": true,
  "sync.retry": true,
};
```

Regla de autorización offline:

```text
permiso normal del usuario
+
capacidad offline habilitada
+
sesión offline vigente
+
dispositivo autorizado
```

---

## 6. Bloqueo del menú y las rutas

Crear una política central:

```typescript
export interface RouteOfflinePolicy {
  routeName: string;
  availableOffline: boolean;
  requiredCapability?: OfflineCapability;
}
```

Ejemplo:

```typescript
export const routeOfflinePolicies: RouteOfflinePolicy[] = [
  {
    routeName: "sales.create",
    availableOffline: true,
    requiredCapability: "sale.create",
  },
  {
    routeName: "products.index",
    availableOffline: true,
    requiredCapability: "catalog.read",
  },
  {
    routeName: "reports.index",
    availableOffline: false,
  },
  {
    routeName: "users.index",
    availableOffline: false,
  },
];
```

Cuando la aplicación esté offline:

- Evitar requests Inertia para rutas bloqueadas.
- Mantener la vista actual o regresar al POS.
- Mostrar “Esta función requiere conexión”.
- Deshabilitar enlaces y botones incompatibles.
- Mantener visibles el POS, catálogo local, ventas pendientes y sincronización.

El menú puede mostrar:

```text
✓ Punto de venta
✓ Productos, consulta local
✓ Ventas pendientes
✓ Sincronización
✕ Compras — requiere conexión
✕ Reportes — requiere conexión
✕ Usuarios — requiere conexión
```

---

## 7. Estructura dentro de la aplicación actual

```text
resources/js/
├── app.js
├── Pages/
├── Layouts/
├── Components/
│   ├── OfflineBanner.vue
│   ├── RequiresConnection.vue
│   └── SyncStatus.vue
├── Stores/
│   ├── connectivity.ts
│   ├── offlineSession.ts
│   └── sync.ts
├── Offline/
│   ├── database/
│   │   ├── db.ts
│   │   ├── schema.ts
│   │   └── migrations/
│   ├── capabilities/
│   ├── guards/
│   ├── repositories/
│   ├── services/
│   └── sync/
└── Domain/
    └── Sales/
        ├── SaleApplicationService.ts
        ├── OnlineSaleGateway.ts
        ├── OfflineSaleRepository.ts
        └── contracts.ts
```

No crear:

```text
resources/js/pos/main.ts
```

ni una segunda aplicación.

---

## 8. Repositorios híbridos

Los componentes no deben decidir directamente entre API e IndexedDB.

```typescript
export interface ProductRepository {
  search(query: string): Promise<Product[]>;
  findByBarcode(barcode: string): Promise<Product | null>;
  findById(id: string): Promise<Product | null>;
}
```

```typescript
export class HybridProductRepository implements ProductRepository {
  constructor(
    private readonly connectivity: ConnectivityService,
    private readonly online: OnlineProductRepository,
    private readonly offline: OfflineProductRepository,
  ) {}

  async search(query: string): Promise<Product[]> {
    if (this.connectivity.isUsableOnline()) {
      const products = await this.online.search(query);
      await this.offline.cacheProducts(products);
      return products;
    }

    return this.offline.search(query);
  }
}
```

Aplicar el mismo patrón para:

- Productos.
- Precios.
- Stock.
- Clientes.
- Ventas.
- Pagos.
- Sincronización.

---

## 9. Flujo único de venta

Debe existir un solo servicio de aplicación:

```typescript
export interface CompleteSaleCommand {
  operationId: string;
  saleId: string;
  branchId: string;
  deviceId: string;
  userId: string;
  customerId: string | null;
  items: SaleItemCommand[];
  payments: PaymentCommand[];
  occurredAt: string;
}
```

Resultado común:

```typescript
export interface SaleResult {
  saleId: string;
  operationId: string;
  status: "confirmed" | "pending_sync" | "conflict";
  localFolio: string;
  serverFolio: string | null;
}
```

### Online

```text
1. Generar saleId y operationId en cliente.
2. Enviar a Laravel.
3. Laravel procesa idempotentemente.
4. Guardar confirmación local.
5. Actualizar datos locales.
```

### Offline

```text
1. Conservar los mismos saleId y operationId.
2. Guardar venta, partidas y pagos en IndexedDB.
3. Crear movimientos locales de inventario.
4. Agregar operación a Outbox.
5. Imprimir ticket provisional.
```

### Timeout o respuesta perdida

Un timeout no demuestra que Laravel no guardó la venta.

```text
1. Conservar el mismo operationId.
2. No generar una segunda venta.
3. Consultar el estado de operationId cuando sea posible.
4. Reintentar con el mismo identificador.
5. Laravel devuelve el resultado original si ya fue procesada.
```

Pseudocódigo:

```typescript
async function completeSale(
  command: CompleteSaleCommand
): Promise<SaleResult> {
  const normalized = {
    ...command,
    saleId: command.saleId ?? generateUuidV7(),
    operationId: command.operationId ?? generateUuidV7(),
  };

  if (!connectivityService.isUsableOnline()) {
    return offlineSaleRepository.storePending(normalized);
  }

  try {
    return await onlineSaleGateway.complete(normalized);
  } catch (error) {
    if (!isAmbiguousNetworkFailure(error)) {
      throw error;
    }

    const existing =
      await onlineSaleGateway.tryFindByOperationId(
        normalized.operationId
      );

    if (existing) {
      return existing;
    }

    return offlineSaleRepository.storePending(normalized);
  }
}
```

---

## 10. Estados locales de venta

```typescript
export type LocalSaleStatus =
  | "draft"
  | "sending"
  | "pending_sync"
  | "confirmed"
  | "conflict"
  | "cancelled_local"
  | "cancelled_confirmed";
```

```text
draft
 ├─ online ─► sending ─► confirmed
 │                 └──► pending_sync
 └─ offline ──────────► pending_sync

pending_sync
 ├─ sincronización ───► confirmed
 ├─ conflicto ────────► conflict
 └─ cancelación ──────► cancelled_local
```

---

## 11. IndexedDB

Utilizar Dexie o una abstracción equivalente.

Tablas:

```text
products
product_prices
stock_snapshots
customers
sales
sale_items
payments
inventory_movements
outbox
sync_state
offline_session
app_metadata
```

Esquema conceptual:

```typescript
db.version(1).stores({
  products:
    "id, serverId, sku, barcode, name, active, updatedAtServer",
  productPrices:
    "id, productId, branchId, [productId+branchId], updatedAtServer",
  stockSnapshots:
    "id, productId, branchId, [productId+branchId], syncedAt",
  customers:
    "id, serverId, branchId, name, syncStatus, updatedAt",
  sales:
    "id, serverId, operationId, branchId, deviceId, userId, localFolio, status, occurredAt",
  saleItems:
    "id, saleId, productId, [saleId+productId]",
  payments:
    "id, saleId, method, status, occurredAt",
  inventoryMovements:
    "id, operationId, branchId, productId, referenceId, syncStatus, occurredAt",
  outbox:
    "operationId, aggregateType, aggregateId, eventType, sequence, status, nextAttemptAt, createdAt",
  syncState: "id",
  offlineSession:
    "id, userId, deviceId, branchId, offlineExpiresAt",
  appMetadata: "key",
});
```

La venta offline debe guardarse en una sola transacción local:

```text
venta
+ partidas
+ pagos
+ movimientos
+ actualización de stock estimado
+ Outbox
```

La impresión ocurre después del commit local.

---

## 12. Inventario

No enviar:

```json
{
  "product_id": "345",
  "stock": 20
}
```

Enviar movimientos:

```json
{
  "movement_id": "uuid",
  "product_id": "345",
  "quantity_delta": -2,
  "reason": "sale",
  "reference_id": "sale-id"
}
```

Modelo local:

```typescript
export interface LocalStockSnapshot {
  productId: string;
  branchId: string;
  serverQuantity: number;
  localPendingDelta: number;
  estimatedQuantity: number;
  syncedAt: string;
}
```

```text
estimatedQuantity =
serverQuantity + localPendingDelta
```

Mostrar:

```text
Existencia estimada: 8
Última existencia central: 10
Operaciones locales: -2
Última sincronización: 20/07/2026 18:10
```

---

## 13. Outbox

```typescript
export interface OutboxOperation {
  operationId: string;
  aggregateType: "sale" | "customer" | "payment";
  aggregateId: string;
  eventType: string;
  payload: unknown;
  sequence: number;
  status:
    | "pending"
    | "processing"
    | "confirmed"
    | "failed"
    | "blocked";
  attempts: number;
  nextAttemptAt: string | null;
  lastErrorCode: string | null;
  lastErrorMessage: string | null;
  createdAt: string;
  updatedAt: string;
}
```

Reglas:

- No eliminar antes de recibir confirmación.
- Recuperar operaciones `processing` abandonadas.
- Mantener secuencia por dispositivo.
- Enviar lotes.
- Aplicar backoff y jitter.
- Mantener conflictos visibles.
- Permitir reintento manual.

---

## 14. Sincronización

Disparadores:

- Al abrir Agrosys.
- Al iniciar sesión.
- Al recuperar red.
- Cada 30–60 segundos en línea.
- Después de una venta.
- Al recuperar foco.
- Al presionar “Sincronizar”.
- Antes del cierre de caja.

Orden:

```text
1. Health check.
2. Validar o renovar sesión.
3. Push de Outbox.
4. Pull incremental.
5. Aplicar cambios en IndexedDB.
6. Recalcular stock estimado.
7. Mostrar resultados.
8. Cambiar a online.
```

No confiar únicamente en:

```typescript
navigator.onLine
```

Al evento `online`, pasar primero a `recovering` y validar Laravel.

---

## 15. API

Prefijo sugerido:

```text
/api/v1/offline
```

Endpoints:

```http
GET  /api/v1/offline/health
GET  /api/v1/offline/bootstrap
POST /api/v1/offline/sync/push
GET  /api/v1/offline/sync/pull?cursor=12345
GET  /api/v1/offline/operations/{operationId}
POST /api/v1/offline/device/heartbeat
```

### Push

```json
{
  "device_id": "uuid",
  "branch_id": "4",
  "operations": [
    {
      "operation_id": "uuid",
      "aggregate_type": "sale",
      "aggregate_id": "uuid",
      "event_type": "SALE_COMPLETED",
      "sequence": 157,
      "occurred_at": "2026-07-20T18:15:00-06:00",
      "payload": {}
    }
  ]
}
```

Resultados:

```text
confirmed
duplicate
conflict
rejected
retry
blocked
```

---

## 16. Idempotencia Laravel

Usar:

```http
Idempotency-Key: <operation-id>
```

Crear restricción:

```sql
UNIQUE (operation_id)
```

Regla:

```text
Primera solicitud:
procesar y almacenar resultado.

Solicitud repetida:
devolver el mismo resultado.

Nunca:
crear otra venta.
```

El endpoint:

```http
GET /api/v1/offline/operations/{operationId}
```

debe permitir comprobar ventas con respuesta perdida.

---

## 17. PWA y Service Worker

Cachear:

- HTML principal.
- JS.
- CSS.
- Manifest.
- Iconos.
- Layout.
- Componentes requeridos por POS.
- Pantalla de sincronización.
- Pantalla “requiere conexión”.

Estrategias:

```text
Assets versionados:
Cache First

HTML principal:
Network First con fallback

Bootstrap:
Network Only + guardar explícitamente en IndexedDB

Push:
Network Only

Pull:
Network Only

Imágenes opcionales:
Stale While Revalidate
```

El Service Worker no sustituye IndexedDB ni contiene la lógica principal de venta.

---

## 18. Autenticación offline

El usuario debe haberse autenticado en línea.

Credencial firmada:

```json
{
  "user_id": "18",
  "device_id": "uuid",
  "branch_id": "4",
  "permissions": ["pos.sell"],
  "issued_at": 1753060000,
  "offline_expires_at": 1753664800
}
```

Reglas:

- No guardar contraseña.
- No cambiar sucursal offline.
- Vigencia de 3 a 7 días.
- Renovar al volver online.
- Al expirar, bloquear nuevas ventas.
- Permitir ver y sincronizar pendientes.
- Aplicar revocaciones al recuperar conexión.

---

## 19. UI

### Banner offline

```text
Sin conexión
Agrosys opera en modo limitado.
Las ventas se sincronizarán automáticamente.
Última sincronización: 20/07/2026 18:10
Pendientes: 4
```

### Recuperación

```text
Conexión detectada.
Verificando Agrosys y sincronizando 4 operaciones...
```

### Resultado

```text
Sincronización completada.
4 ventas confirmadas.
```

o:

```text
Sincronización parcial.
3 ventas confirmadas.
1 requiere revisión.
```

### Ticket provisional

Debe incluir:

```text
Folio local
Dispositivo
Sucursal
Fecha local
VENTA PENDIENTE DE SINCRONIZACIÓN
```

Al confirmarse, guardar el folio central.

---

## 20. Errores

### Tratar como problema temporal

- Sin conexión.
- DNS.
- Timeout.
- HTTP 502, 503 o 504.

### No tratar como offline

- HTTP 400.
- HTTP 401.
- HTTP 403.
- Validación de negocio.
- Permiso denegado.

Un 401 o 403 no debe convertirse automáticamente en una venta offline.

---

## 21. Fases de implementación

### Fase 0 — análisis

- Mapear venta actual.
- Identificar props Inertia.
- Identificar requests.
- Identificar modelos.
- Identificar permisos.
- Identificar impresión.
- Definir rutas y capacidades offline.

### Fase 1 — misma PWA

- Manifest.
- Service Worker.
- ConnectivityStore.
- Health check.
- Estados de conexión.
- Banner.
- Guardas.
- Bloqueo de menú.

Criterios:

- Es la misma aplicación.
- No existe otra entrada frontend.
- El layout se conserva.
- Los módulos incompatibles se bloquean sin requests.

### Fase 2 — catálogo local

- IndexedDB.
- Bootstrap.
- Productos.
- Precios.
- Stock.
- Repositorios híbridos.
- Búsqueda local.

### Fase 3 — venta temporal offline

- Servicio unificado.
- `saleId` y `operationId` en cliente.
- Persistencia transaccional.
- Outbox.
- Movimientos.
- Ticket provisional.

### Fase 4 — sincronización

- Push.
- Estado por `operationId`.
- Idempotencia.
- Folio central.
- Recuperación de timeouts.

### Fase 5 — pull incremental

- Cursor.
- Productos.
- Precios.
- Stock.
- Permisos.
- Confirmaciones.

### Fase 6 — conflictos

- Precio cambiado.
- Producto desactivado.
- Stock negativo.
- Cliente duplicado.
- Dispositivo revocado.
- Bandeja y resolución auditada.

### Fase 7 — piloto

- Una sucursal.
- Una caja.
- Cortes controlados.
- Validación de ventas.
- Expansión gradual.

---

## 22. Pruebas obligatorias

### Corte de conexión

```text
1. Abrir Agrosys.
2. Entrar al POS.
3. Cortar internet.
4. Confirmar banner.
5. Confirmar módulos bloqueados.
6. Confirmar POS disponible.
```

### Venta offline

```text
1. Cargar catálogo.
2. Cortar internet.
3. Registrar venta.
4. Imprimir ticket.
5. Cerrar navegador.
6. Abrir PWA.
7. Confirmar venta pendiente.
```

### Recuperación

```text
1. Tener cinco ventas pendientes.
2. Recuperar internet.
3. Entrar a recovering.
4. Sincronizar.
5. Confirmar cinco ventas.
6. Reactivar módulos.
```

### Respuesta perdida

```text
1. Enviar venta online.
2. Laravel realiza commit.
3. Cortar la respuesta.
4. Reintentar con el mismo operationId.
5. Confirmar una sola venta.
```

### Ruta bloqueada

```text
1. Activar modo offline.
2. Intentar abrir reportes.
3. Confirmar que no se dispara request Inertia.
4. Mostrar “Requiere conexión”.
```

### Sesión vencida

```text
1. Expirar credencial offline.
2. Bloquear nuevas ventas.
3. Mantener visibles las pendientes.
4. Permitir sincronización al recuperar conexión.
```

---

## 23. Feature flags

```text
offline_mode_enabled
offline_catalog_enabled
offline_sales_enabled
offline_sync_enabled
offline_customers_enabled
offline_conflicts_enabled
```

Aplicables por:

- Empresa.
- Sucursal.
- Dispositivo.
- Ambiente.

---

## 24. Prompt maestro para Codex

```text
Analiza el repositorio actual de Agrosys e implementa un modo offline temporal
dentro de la misma aplicación Laravel + Inertia + Vue.

No crees otra SPA, otra entrada de Vite, otro dominio ni otra versión del POS.

La aplicación debe funcionar normalmente en línea. Cuando se pierda la
conexión, debe activar un modo degradado que:

- mantenga el mismo layout;
- reutilice la pantalla actual de venta;
- muestre un banner permanente;
- bloquee módulos incompatibles;
- evite requests Inertia a rutas no disponibles;
- consulte catálogo, precios y stock desde IndexedDB;
- registre ventas localmente;
- genere una operación Outbox;
- imprima ticket provisional;
- sincronice al recuperar conexión.

Implementa los estados:

- online;
- offline;
- recovering;
- sync_error.

No confíes solamente en navigator.onLine. Al detectar red, ejecuta un health
check a Laravel antes de cambiar a online.

La venta debe utilizar un único SaleApplicationService para online y offline.

Toda venta debe generar en el cliente:

- saleId;
- operationId.

Esto también aplica a ventas online. Laravel debe procesar operationId de
manera idempotente.

Si una llamada falla por timeout o pérdida de respuesta:

- no generes otro operationId;
- consulta el estado de la operación;
- reintenta con el mismo identificador;
- evita ventas duplicadas.

No sincronices stock absoluto. Usa movimientos de inventario y una proyección
local estimada.

No borres operaciones locales antes de recibir confirmación.

No almacenes contraseñas en IndexedDB.

Antes de modificar código:

1. Identifica el flujo actual de venta.
2. Identifica las props Inertia.
3. Identifica requests HTTP directos.
4. Identifica componentes reutilizables.
5. Identifica modelos y tablas.
6. Propón capacidades offline.
7. Propón rutas bloqueadas.
8. Documenta cambios mínimos.

Primera tarea:

A. Crear un análisis del repositorio.
B. Agregar PWA a la aplicación actual.
C. Crear ConnectivityStore.
D. Agregar health check real.
E. Crear OfflineBanner.
F. Crear guardas de navegación.
G. Bloquear módulos incompatibles.
H. Crear IndexedDB versionada.
I. Descargar catálogo inicial.
J. Permitir consulta local de productos.
K. Agregar pruebas.

Todavía no implementes ventas offline hasta que esta primera fase tenga pruebas
y pueda validarse manualmente.

Para cada cambio:

- explica el diseño;
- lista archivos modificados;
- agrega pruebas;
- ejecuta tests y linters;
- reporta riesgos;
- entrega instrucciones de validación.
```

---

## 25. Criterios finales

- Agrosys sigue siendo una sola PWA.
- No existe un frontend separado.
- La operación online continúa igual.
- El modo offline aparece solo durante interrupciones.
- Los módulos incompatibles quedan bloqueados.
- Se reutiliza la pantalla actual de venta.
- Las ventas sobreviven cierres y recargas.
- Al volver internet se sincronizan.
- Los reintentos no duplican ventas.
- El stock se maneja mediante movimientos.
- Los conflictos permanecen visibles.
- La aplicación no confunde conexión de red con disponibilidad de Laravel.

---

## 26. Prioridades

```text
No perder ventas.
No duplicar ventas.
No crear dos aplicaciones.
No ocultar conflictos.
No sobrescribir inventario.
```
