# Despliegue a Produccion: Suscripciones (Stripe + Manual)

Base de esta guia:

- `923bf252a96adc0890b5e6566a47db7a29a97e28` - `feat: stripe`
- `0cb7d0e8845d5544f57156cfa0c0034f54d3aa0a` - `feat: plan de suscripcion`

## 1) Alcance funcional incluido

- Planes publicos y onboarding de suscripcion.
- Suscripcion con Stripe (Laravel Cashier).
- Trial de 30 dias con recordatorios por correo.
- Control de acceso por estado de suscripcion.
- Panel superadmin para vigencia manual (legacy, fuera de Stripe).
- Gestion hibrida (Stripe + manual) y UX de suscripcion.

## 2) Requisitos previos

- PHP/Laravel en produccion funcionando.
- SSL activo (https) para webhooks de Stripe.
- Base de datos respaldada antes de migrar.
- Cron del sistema habilitado.
- Correo SMTP configurado (se envian recordatorios de trial).

## 3) Variables `.env` requeridas

Usa como base `.env.example` y define al menos:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

STRIPE_KEY=pk_live_xxx
STRIPE_SECRET=sk_live_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx

CASHIER_CURRENCY=mxn
CASHIER_CURRENCY_LOCALE=es_MX
SUBSCRIPTIONS_USE_DATABASE=true

TERMS_VERSION=2026-02-10
PRIVACY_VERSION=2026-02-10
```

Opcional (si quieres mapear Price IDs fijos de Stripe):

```env
STRIPE_PRICE_CAMPO_MONTHLY=
STRIPE_PRICE_CAMPO_YEARLY=
STRIPE_PRICE_ESENCIAL_MONTHLY=
STRIPE_PRICE_ESENCIAL_YEARLY=
STRIPE_PRICE_EXPERT_MONTHLY=
STRIPE_PRICE_EXPERT_YEARLY=
```

Seeder de superadmin (opcional):

```env
SUPERADMIN_NAME="Super Administrador"
SUPERADMIN_EMAIL=superadmin@tu-dominio.com
SUPERADMIN_PASSWORD=<CAMBIAR_PASSWORD>
```

## 4) Configuracion Stripe (produccion)

1. Crear endpoint webhook en Stripe Dashboard:
   - URL: `https://tu-dominio.com/stripe/webhook`
2. Copiar `Signing secret` y ponerlo en `STRIPE_WEBHOOK_SECRET`.
3. Validar rutas Cashier:

```bash
php artisan route:list --name=cashier
```

Debe mostrar:

- `POST stripe/webhook`
- `GET stripe/payment/{id}`

## 5) Pasos de despliegue

### 5.1 Publicacion de codigo

```bash
git fetch --all
git checkout feat/paypal-integration
git pull
```

### 5.2 Dependencias y frontend

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 5.3 Modo mantenimiento (recomendado)

```bash
php artisan down
```

### 5.4 Migraciones y seed opcional

```bash
php artisan migrate --force
php artisan db:seed --class=SuperAdminSeeder --force
```

### 5.5 Cache de produccion

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5.6 Levantar aplicacion

```bash
php artisan up
```

## 6) Cron y recordatorios de trial

Agrega en crontab (usuario del servidor web):

```cron
* * * * * cd /ruta/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

El comando agendado ejecuta:

- `subscriptions:send-trial-reminders` (diario 09:00)

Prueba manual:

```bash
php artisan subscriptions:send-trial-reminders --dry-run
```

## 7) Validaciones post-despliegue

1. `/planes`
- Usuario invitado: muestra trial y boton `Iniciar prueba gratis`.
- Usuario autenticado con historial: no muestra copy de trial, muestra plan activo o `Gestionar suscripcion`.

2. `/suscribirse?step=2`
- Con historial previo: no mostrar copy de trial/beneficios de prueba.
- Sin historial: mostrar trial normalmente.

3. `/cuenta/suscripcion`
- Si activacion manual: no mostrar `Abrir portal de facturacion`.
- Si Stripe activa: mostrar boton de portal.

4. Panel superadmin
- `GET /empresa/suscripciones/panel`
- Validar alta/edicion de vigencia manual y plan.
- Regla: inicio manual no puede ser fecha futura.

5. Webhook Stripe
- Revisar en dashboard eventos `2xx`.
- Verificar actualizacion de plan/ciclo cuando aplica.

## 8) SQL de verificacion rapida

```sql
SELECT code, name, monthly_price_mxn, yearly_price_mxn, is_active
FROM subscription_plans
ORDER BY sort_order, id;
```

```sql
SELECT id, nombre, plan_code, plan_cycle, trial_ends_at,
       manual_subscription_starts_at, manual_subscription_ends_at, manual_subscription_blocked
FROM empresas
ORDER BY id DESC
LIMIT 20;
```

## 9) Rollback (si algo falla)

1. Regresar a commit/tag estable anterior.
2. Limpiar y recachear config/rutas/views.
3. Restaurar backup de DB si el problema es de datos.
4. Evitar rollback de migraciones en caliente sin backup validado.

---

Checklist minimo antes de anunciar release:

- [ ] Webhook Stripe en `https://tu-dominio.com/stripe/webhook`
- [ ] `STRIPE_WEBHOOK_SECRET` correcto en `.env`
- [ ] `php artisan migrate --force` ejecutado
- [ ] `npm run build` ejecutado
- [ ] Cron `schedule:run` activo
- [ ] Pruebas manuales de `/planes`, `/suscribirse?step=2`, `/cuenta/suscripcion`
