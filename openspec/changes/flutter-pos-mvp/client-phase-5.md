# Fase 5 — interfaz y preparación del piloto

## Alcance de esta entrega

Configuración acordada: impresora térmica Nextep, papel de **80 mm**, conexión
**USB**. El modelo exacto y los dispositivos del piloto siguen sin definir.

El cliente Flutter adopta la interfaz actual de la web: logo incluido como
asset offline, encabezado blanco, menú de tarjetas, colores azul/verde,
catálogo de tarjetas, carrito lateral en escritorio y panel inferior en móvil.
Productos y clientes usan tablas en escritorio y tarjetas en móvil. El flujo
de cobro conserva efectivo, cambio, descuentos e inicial de crédito; el carrito
se mantiene al navegar. F3 enfoca búsqueda, F4 abre cobro y un código exacto
añade el producto. El resumen del día corresponde únicamente al contexto y
dispositivo locales, e identifica pendientes y conflictos para conciliación.

La impresión usa el diálogo del sistema con formato inicial de 80 × 297 mm,
paginas adicionales para tickets largos y el tamaño que devuelva el driver.
No se conecta directamente a USB ni envía comandos ESC/POS. Reimprimir abre
el ticket persistido; imprimir o cancelar no registra otra venta.

## Preparación de impresora USB

1. Identificar modelo Nextep y obtener su driver para el sistema del piloto.
2. Instalar la impresora USB y configurar papel de 80 mm en sus preferencias.
3. Imprimir una página de prueba del sistema antes de abrir AgroSys.
4. Registrar una venta de contado sin red, abrir su ticket y elegir la cola USB.
5. Confirmar ancho, márgenes, acentos, importes, avance y corte. El corte depende
   del driver; no se ha implementado un comando propio.
6. Repetir con crédito, abono inicial y ticket largo; cancelar una impresión,
   desconectar USB y reimprimir. Verificar que se conserva el mismo UUID y que
   no aparece una venta adicional.
7. Reconectar red y comparar efectivo, adeudo e inventario con el servidor.

La integración USB mediante cola del sistema está preparada para escritorio.
La conexión USB directa de Android/iOS requiere verificar el modelo, soporte
del fabricante y un adaptador específico; esta entrega no la implementa.

## Actualización y distribución

Versión del cliente: `1.0.0+5`. Mantener los identificadores de aplicación y el
directorio de datos de versiones previas. No desinstalar ni borrar SQLite al
actualizar con ventas pendientes. Antes del piloto: registrar UUID/estado de
la cola, instalar la actualización, abrir sin red, reimprimir y sincronizar;
comprobar que no se duplicaron ventas, stock, adeudos ni iniciales.

Comandos de builds internos y workflow existentes en `clients/agrosys_pos/README.md`.
Android debug es sólo para QA; la configuración de release aún usa firma de
desarrollo. macOS no está notarizado; iOS físico requiere provisioning.
Windows requiere un host compatible. No hay publicación en tiendas.

## Evidencia y criterios pendientes

Verificación local: suite completa de **68 pruebas aprobadas**, `flutter analyze`
sin incidencias y `openspec validate flutter-pos-mvp --strict` aprobado. Tras
añadir cobertura del PDF térmico, las 14 pruebas de persistencia se ejecutaron
de nuevo y aprobaron.

Build interno macOS release generado correctamente en
`clients/agrosys_pos/build/macos/Build/Products/Release/agrosys_pos.app`
(54.8 MB). No está notarizado ni validado con la impresora física. Los otros
targets no se recompilaron en esta entrega de interfaz.

Pruebas de venta cubren contado/crédito a 360 y 1200 px, navegación con carrito,
cobro, ticket e historial. La prueba de ticket largo genera PDF normal y
térmico con 200 nombres extensos. La suite previa cubre migración, reapertura,
seguridad, persistencia atómica y recuperación de sincronización.

Esto no sustituye la matriz AC-01–24 en dispositivos físicos. Permanecen
abiertos: impresión física, medición p95 de cierre en equipos del piloto,
actualización real con cierre forzado, Windows, firma de distribución y piloto
de sucursal con conciliación diaria. No archivar OpenSpec hasta su aceptación.
