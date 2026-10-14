# Inventario nativo: eliminación y reseteo

Implementado sobre la consulta y las altas/ediciones de las fases anteriores.

- `adminEmpresa` y `superAdmin` reciben `product.delete` e `inventory.reset` en su autorización firmada. Los demás roles no muestran estos botones y el servidor rechaza las solicitudes. El bloqueo empresarial se aplica a administradores de empresa; superAdmin conserva la excepción existente.
- Ambas acciones requieren conexión, consulta actual al servidor y una casilla de confirmación. El reseteo muestra producto, presentación, sucursal y cantidad confirmada; afecta únicamente a esa sucursal y crea un movimiento `reseteo_cero` con usuario y cantidades anterior/nueva.
- La eliminación afecta al catálogo de la empresa mediante borrado lógico. Conserva ventas y movimientos. Se rechaza cuando la última existencia es positiva en cualquier sucursal de la empresa, incluidas sucursales desactivadas. La pantalla enumera las sucursales con stock.
- La consulta previa entrega versión del producto y revisión de existencias. La escritura vuelve a comprobar ambas dentro de una transacción. Un conflicto exige consultar y confirmar de nuevo.
- Una respuesta perdida conserva la solicitud ya confirmada por el usuario en SQLite. El reintento envía exactamente el mismo identificador y contenido. El servidor devuelve el resultado original antes de consultar el producto: ni una eliminación previa ni entradas posteriores al reseteo vuelven a ejecutar la acción.
- Después de guardar se actualiza el catálogo. Si falla esa actualización, la operación permanece confirmada y la app avisa que aún debe sincronizarse.

## Integración con web y movimientos centrales

Web y API comparten `InventoryManagement` para eliminar y resetear. La ruta web de reseteo acepta también `superAdmin`, como ya mostraba su botón. Compras, devoluciones y recepción de transferencias bloquean los productos antes de actualizar existencias, coordinándose con altas, ventas y acciones destructivas. Los formularios antiguos que intentan incorporar stock a un producto ya eliminado se rechazan.

El contrato documenta POST `inventory/{productId}/preview`, `/reset` y `/delete`, con permisos, confirmación y revisiones requeridas.

## Verificación

Las pruebas cubren matriz de roles, bloqueo empresarial, aislamiento de empresa, stock en otra sucursal/desactivada, historial conservado, confirmación, cambios concurrentes detectados por revisión, repetición exacta tras perder respuesta y reiniciar la app, conexión y pantallas de 360 y 1200 píxeles.

Las pruebas de API utilizan SQLite. La verificación de contención real en MySQL requiere la base aislada del procedimiento en `backend-phase-1.md`; no se utiliza la base operativa para esas pruebas. Compras y transferencias como módulos nativos quedan para fases posteriores.
