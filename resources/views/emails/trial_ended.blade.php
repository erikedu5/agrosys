<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AgroSys - Trial finalizado</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8fafc; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
        <h2 style="margin: 0 0 12px 0;">Tu prueba ha terminado</h2>

        <p style="margin: 0 0 12px 0;">
            Hola {{ $user->name }}, el periodo de prueba de AgroSys para <b>{{ $empresa->nombre }}</b> ha terminado.
        </p>

        <p style="margin: 0 0 18px 0;">
            Para reactivar el acceso, activa tu suscripcion aqui:
            <a href="{{ route('subscription.show') }}">{{ route('subscription.show') }}</a>
        </p>

        <p style="margin: 0; color: #64748b; font-size: 12px;">
            Si necesitas ayuda, responde a este correo o contacta soporte.
        </p>
    </div>
</body>
</html>

