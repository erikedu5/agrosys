# Reemplazo de sesiones al alcanzar el número de dispositivos

El número de dispositivos del plan por sucursal es un umbral de confirmación para todas las empresas. Al activar otro dispositivo y alcanzar el umbral, la API responde HTTP 409 con `DEVICE_SESSION_CONFIRMATION_REQUIRED`. No modifica ni desconecta equipos antes de la confirmación.

Web y Flutter muestran «Cerrar las demás sesiones y entrar». Tras la confirmación explícita, la activación se reintenta con `close_other_sessions: true`. Se cierran todos los demás accesos del mismo usuario, incluidos los de otras sucursales y los navegadores web. Los demás usuarios permanecen conectados, incluso si sus dispositivos mantienen la sucursal por encima del umbral. La sesión que confirmó permanece válida; repetir la activación no vuelve a cerrar sesiones.

Los tokens POS anteriores se eliminan. Las sesiones web anteriores se invalidan mediante una versión persistente del usuario, comprobada en cada petición, y se rota la credencial «recordarme». La marca `session_closed_at` distingue el reemplazo de la revocación administrativa: el equipo puede volver a iniciar sesión y recuperar el mismo identificador y sus pendientes. La confirmación no concede autorización manual ni anula una revocación administrativa.

Los equipos sin conexión detectan el cierre al contactar al servidor; no se pueden desconectar inmediatamente. No se elimina el almacenamiento local, ni las operaciones, secuencias, ventas pendientes o caja. Conviene sincronizar antes de confirmar. Después de volver a iniciar sesión en el contexto original se pueden enviar las ventas pendientes con su autorización original y sin duplicarlas.

## Despliegue

Publicar el backend y el build web, y aplicar `2026_10_13_020000_add_device_session_replacement.php` mediante `php artisan migrate --force`. Reiniciar los procesos persistentes que sirvan PHP si corresponde. La migración inicializa la versión en cero para conservar las sesiones actuales.

Recompilar y distribuir Flutter para Windows y macOS con la configuración de producción. Los instaladores anteriores no contienen el diálogo de confirmación y mostrarán el mensaje del servidor hasta actualizarse. Este cambio se verificó en código y pruebas locales; no se publica automáticamente en producción.
