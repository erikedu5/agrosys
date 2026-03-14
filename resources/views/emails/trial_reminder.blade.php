<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AgroSys - Recordatorio de trial</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8fafc; padding: 24px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
        <h2 style="margin: 0 0 12px 0;">Recordatorio de prueba</h2>

        <p style="margin: 0 0 12px 0;">
            Hola {{ $user->name }}, tu prueba gratuita de AgroSys para <b>{{ $empresa->nombre }}</b> esta por terminar.
        </p>

        @if($empresa->trial_ends_at)
            <p style="margin: 0 0 12px 0;">
                Fecha de termino: <b>{{ $empresa->trial_ends_at->format('Y-m-d H:i') }}</b>
            </p>
        @endif

        <p style="margin: 0 0 18px 0;">
            Para evitar interrupciones, activa tu suscripcion desde:
            <a href="{{ route('subscription.show') }}">{{ route('subscription.show') }}</a>
        </p>

        <p style="margin: 0; color: #64748b; font-size: 12px;">
            Si ya activaste tu suscripcion, ignora este mensaje.
        </p>
    </div>
</body>
</html>

