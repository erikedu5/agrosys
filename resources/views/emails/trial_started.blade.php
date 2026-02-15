<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AgroSys - Trial activo</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8fafc; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
        <h2 style="margin: 0 0 12px 0;">Bienvenido a AgroSys</h2>

        <p style="margin: 0 0 12px 0;">
            Hola {{ $user->name }}, tu prueba gratuita ya esta activa para la empresa <b>{{ $empresa->nombre }}</b>.
        </p>

        @if($empresa->trial_ends_at)
            <p style="margin: 0 0 12px 0;">
                Tu prueba termina el: <b>{{ $empresa->trial_ends_at->format('Y-m-d H:i') }}</b>.
            </p>
        @endif

        <p style="margin: 0 0 18px 0;">
            No necesitas tarjeta para iniciar. Te enviaremos recordatorios antes de que venza el periodo de prueba.
        </p>

        <p style="margin: 0 0 8px 0;">
            Inicia sesion aqui:
            <a href="{{ route('login') }}">{{ route('login') }}</a>
        </p>
        <p style="margin: 0;">
            Administrar suscripcion:
            <a href="{{ route('subscription.show') }}">{{ route('subscription.show') }}</a>
        </p>
    </div>
</body>
</html>

