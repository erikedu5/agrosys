# Plan De Implementacion: Suscripciones (Trial 30 Dias) - AgroSys

Fecha: 2026-02-10

## Objetivo
Habilitar un esquema SaaS por suscripcion para AgroSys (POS + inventario), con:
- 30 dias de prueba gratis para nuevas empresas.
- Metodo de pago opcional durante la prueba.
- Alertas in-app y por correo antes de que venza la prueba.
- Cobro recurrente via pasarela (fase 1: Stripe).
- Pantallas UX/UI para planes, alta (onboarding) y administracion de suscripcion.
- Terminos y condiciones + aviso de privacidad.
- Factura (CFDI) opcional: solo capturar datos/solicitud (emision se integra en fase posterior).

## Alcance (MVP)
- Pagina publica de planes (`/planes`) y CTAs en home (`/`).
- Onboarding en una sola pagina con 2 pasos (collapses):
  - Paso 1: crear cuenta admin + empresa + sucursal matriz (inicia trial de 30 dias).
  - Paso 2: pago opcional (Stripe Checkout para crear suscripcion y/o dejar tarjeta capturada en el flujo de checkout).
- Pantalla "Mi suscripcion" (`/cuenta/suscripcion`) para:
  - ver estado (trial/activa/vencida) y dias restantes,
  - iniciar checkout de Stripe,
  - abrir portal de facturacion de Stripe (cuando exista suscripcion) para actualizar tarjeta/cancelar.
- Middleware para bloquear el uso cuando el trial termine y no haya suscripcion activa (permitiendo acceder a pantallas de suscripcion/perfil).
- Alertas:
  - Banner dentro del sistema (cuando falten 7/3/1 dias).
  - Correos automaticos (7/3/1 dias antes y el dia que vence).
- Terminos y Privacidad publicos + registro de aceptacion versionada en DB.

## Planes
Todos incluyen 30 dias gratis.

### A) AgroSys Campo
- $459.00 MXN / mes
- 1 dispositivo
- 1 sucursal

### B) AgroSys Esencial
- $999.00 MXN / mes
- Hasta 2 dispositivos por sucursal
- Modulo bitacora agricola (ingenieros) (feature-flag)
- Maximo 3 sucursales
- 10% de descuento por pago anual

### C) AgroSys Expert
- $1299.00 MXN / mes
- Hasta 3 dispositivos por sucursal
- Bitacora ilimitada (feature-flag)
- Maximo 5 sucursales
- Soporte 12h, 6 dias
- 15% de descuento por pago anual

Notas:
- En el MVP se implementa enforcement de:
  - max sucursales (contra `empresas.numero_sucursales`)
  - max "dispositivos" como "usuarios activos por sucursal" excluyendo `adminEmpresa`/`superAdmin` (ajustable)

## Decisiones Tecnicas
- Framework: Laravel 11 + Jetstream + Inertia (Vue 3).
- Modelo billable (suscripcion): `Empresa` (no `User`).
- Pasarela: Stripe Billing via Laravel Cashier (fase 1).
- Trial sin tarjeta:
  - Se guarda localmente en `empresas.trial_ends_at`.
  - El cliente puede "Suscribirse" en cualquier momento; si aun esta en trial, la suscripcion en Stripe se crea con `trial_end` alineado al fin del trial.

## Datos / DB (resumen)
Tabla `empresas` (nuevos campos):
- `plan_code`, `plan_cycle`
- `trial_ends_at` (Cashier compatible)
- `numero_dispositivos_por_sucursal`
- Datos facturacion (opcional): `billing_*` + `billing_requires_invoice`
- Aceptacion legal: `terms_*`, `privacy_*`
- Tracking de recordatorios: `trial_reminder_*_sent_at`

Tablas Cashier:
- `subscriptions`
- `subscription_items`

## Rutas (propuestas)
Publicas:
- `GET /` (Welcome) - CTAs
- `GET /planes` - lista de planes
- `GET /suscribirse` - onboarding (Paso 1 + Paso 2)
- `POST /suscribirse` - crea empresa + sucursal + admin y loguea
- `GET /legal/terminos`
- `GET /legal/privacidad`

Autenticadas:
- `GET /cuenta/suscripcion`
- `POST /cuenta/suscripcion/checkout` (Stripe)
- `POST /cuenta/suscripcion/portal` (Stripe Billing Portal)

Webhook:
- `POST /stripe/webhook` (Cashier)

## UX/UI (lineamientos)
- Planes con cards comparables, toggle Mensual/Anual y copy claro:
  - "30 dias gratis"
  - "Sin tarjeta para iniciar"
  - "Recibe recordatorios antes de vencer"
- Onboarding con collapses:
  - Paso 1 siempre visible primero (registro).
  - Paso 2 habilitado tras crear cuenta (pago opcional).
- Dentro del sistema:
  - Banner persistente con CTA "Suscribirme" cuando falten <= 7 dias.
  - Paywall limpio cuando el trial venza (solo deja navegar a Suscripcion/Perfil/Salir).

## Correos
Plantillas MVP:
- Bienvenida / inicio de trial
- Recordatorio trial (7/3/1 dias)
- Trial vencido (bloqueo)
- Suscripcion activada (cuando se confirme via webhook)

Implementacion:
- Comando programado diario (scheduler) para recordatorios.
- Mails en cola (si `QUEUE_CONNECTION` lo permite).

## Factura (opcional)
En el MVP:
- Capturar datos fiscales (RFC, razon social, CP, regimen, uso CFDI, email) y bandera `billing_requires_invoice`.
- No emitir CFDI automaticamente (se deja preparado para integrar un proveedor de facturacion para la suscripcion).

## Variables De Entorno (Stripe)
Requeridas:
- `STRIPE_KEY`
- `STRIPE_SECRET`
- `STRIPE_WEBHOOK_SECRET`

Price IDs (ejemplos):
- `STRIPE_PRICE_CAMPO_MONTHLY`
- `STRIPE_PRICE_ESENCIAL_MONTHLY`
- `STRIPE_PRICE_ESENCIAL_YEARLY`
- `STRIPE_PRICE_EXPERT_MONTHLY`
- `STRIPE_PRICE_EXPERT_YEARLY`

## Criterios De Aceptacion (MVP)
- Un usuario nuevo puede elegir plan, crear empresa y entrar al sistema con 30 dias de trial sin tarjeta.
- El sistema muestra banner y envia correo cuando falten 7/3/1 dias.
- Al vencer el trial (sin suscripcion), el sistema bloquea el uso y redirige a "Mi suscripcion".
- El admin puede iniciar checkout de Stripe para activar suscripcion.
- Cambios de estado via webhook actualizan el estado/UX (suscripcion activa/inactiva).
- Se puede ver y aceptar Terminos y Privacidad; la aceptacion queda registrada.

