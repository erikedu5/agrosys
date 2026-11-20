# Reemplazo de sesiones al alcanzar el número de dispositivos

El número de dispositivos del plan por sucursal es un umbral de reemplazo para todas las empresas. Al activar otro dispositivo y alcanzar el umbral, la API cierra automáticamente los demás accesos del mismo usuario y permite entrar al nuevo equipo. No revoca los dispositivos anteriores ni modifica sesiones de otros usuarios, incluso si estos mantienen la sucursal por encima del umbral.

Web y Flutter no necesitan confirmar ni reintentar la activación. La sesión nueva permanece válida; repetir la activación no vuelve a cerrar sesiones.

Los tokens POS anteriores se eliminan. Las sesiones web anteriores se invalidan mediante una versión persistente del usuario, comprobada en cada petición, y se rota la credencial «recordarme». La marca `session_closed_at` distingue el reemplazo de la revocación administrativa: el equipo puede volver a iniciar sesión y recuperar el mismo identificador y sus pendientes. El reemplazo automático no concede autorización manual ni anula una revocación administrativa.

Los equipos sin conexión detectan el cierre al contactar al servidor; no se pueden desconectar inmediatamente. No se elimina el almacenamiento local, ni las operaciones, secuencias, ventas pendientes o caja. Si es posible, conviene sincronizar antes de abrir una sesión en otro equipo. Después de volver a iniciar sesión en el contexto original se pueden enviar las ventas pendientes con su autorización original y sin duplicarlas.

## Despliegue

Aplicar `2026_10_13_020000_add_device_session_replacement.php` mediante `php artisan migrate --force` si aún no está en producción, publicar el backend actualizado y reiniciar los procesos persistentes que sirvan PHP si corresponde. La migración inicializa la versión en cero para conservar las sesiones actuales. Los clientes Flutter vigentes entrarán sin reintento; conservan el diálogo de confirmación sólo para compatibilidad con un servidor anterior. Este cambio se verificó en código y pruebas locales; no se publica automáticamente en producción.
