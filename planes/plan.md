# Plan de desarrollo — Inventario y compras de Fredy Racing

## 1. Objetivo

Construir por fases el módulo de inventario y abastecimiento de Fredy Racing con:

- CRUD de categorías y productos.
- Vista de detalle del producto con stock, proveedores e historial.
- CRUD de proveedores.
- Catálogo de productos ofrecidos por cada proveedor.
- Consultas de disponibilidad y solicitudes de cotización.
- Pedidos de compra a proveedores.
- Recepciones totales o parciales que actualicen el inventario.
- Historial de compras y movimientos de stock.
- Autorización mediante Spatie Permission.
- Imágenes y documentos mediante Spatie Media Library.
- Interfaz responsive para computadoras, tablets y celulares.

## 2. Decisiones funcionales

### 2.1 Flujo de abastecimiento

El flujo se divide en etapas distintas:

1. Se detecta un producto con stock bajo o agotado.
2. Se consulta qué proveedores ofrecen el producto.
3. Opcionalmente se envía una consulta de disponibilidad o cotización.
4. Se crea un pedido de compra en estado `Borrador`.
5. El pedido se envía y posteriormente se confirma con el proveedor.
6. Cuando llegan los productos se registra una recepción total o parcial.
7. Cada recepción crea movimientos de inventario y aumenta el stock.
8. Al recibir todas las cantidades, el pedido pasa al historial como `Recibido`.

Crear o enviar un pedido no aumentará el stock. El stock solo cambia cuando se registra una recepción.

### 2.2 Eliminación de registros

- Categorías, productos y proveedores sin uso podrán eliminarse.
- Si ya tienen movimientos, consultas o pedidos relacionados, se desactivarán en lugar de eliminarse.
- Pedidos enviados, confirmados o recibidos no podrán eliminarse.
- Las recepciones y movimientos de inventario serán registros auditables e inmutables. Las correcciones se harán mediante un movimiento de ajuste.

### 2.3 Moneda y cantidades

- Moneda inicial: soles peruanos (`PEN`, símbolo `S/`).
- Los importes nunca se calcularán con números flotantes.
- Costos unitarios admitirán hasta cuatro decimales y los totales dos decimales.
- Las cantidades admitirán decimales para productos medidos en litros, metros o kilogramos.
- El impuesto se guardará en el pedido para conservar el valor histórico, aunque cambie la configuración futura.

## 3. Identidad visual

La estructura del boceto se conservará, pero no su color azul/morado.

| Uso              | Color Fredy Racing | Aplicación                                   |
| ---------------- | ------------------ | -------------------------------------------- |
| Base             | Negro              | Sidebar, cabeceras, fondos de alto contraste |
| Acción principal | Amarillo           | Botones principales, foco, selección activa  |
| Alerta/peligro   | Rojo               | Stock agotado, cancelaciones y errores       |
| Éxito            | Verde              | Disponible, confirmado y recibido            |
| Superficie       | Blanco             | Formularios, tarjetas y tablas en tema claro |

Reglas de interfaz:

- No comunicar un estado únicamente mediante color; incluir texto e icono.
- Contraste legible en temas claro y oscuro.
- Computadora: tablas completas y panel lateral de detalle.
- Tablet: tabla simplificada o tarjetas de ancho flexible.
- Celular: tarjetas, filtros en panel desplegable y formularios en una columna.
- Acciones destructivas siempre requieren confirmación.
- Estados vacíos, carga, error y permisos insuficientes tendrán vistas explícitas.

## 4. Modelo de datos propuesto

Los nombres internos estarán en inglés y la interfaz en español, siguiendo las convenciones actuales de Laravel.

### 4.1 Catálogo e inventario

#### `product_categories`

- `id`
- `name`
- `slug`
- `type`: herramienta, repuesto, material, consumible o lubricante
- `description`, nullable
- `is_active`
- timestamps

#### `products`

- `id`
- `product_category_id`
- `sku`, único
- `barcode`, EAN-13 único y escaneable; se genera automáticamente a partir del `id` si no se indica
- `name`
- `description`, nullable
- `brand`, nullable
- `unit`: unidad, litro, kilogramo, metro, juego, caja, etc.
- `minimum_stock`
- `current_stock`
- `location`, nullable
- `last_purchase_cost`, nullable
- `sale_price`, nullable — precio de venta al público (módulo de tienda)
- `is_active`
- timestamps

`current_stock` será el saldo de lectura rápida. Cada cambio deberá estar respaldado por un movimiento de inventario y ambos se actualizarán dentro de una transacción.

#### `inventory_movements`

- `id`
- `product_id`
- `user_id`
- `type`: saldo inicial, recepción, ajuste de entrada, ajuste de salida, consumo de taller o devolución
- `quantity`
- `stock_before`
- `stock_after`
- referencia opcional al documento que originó el movimiento
- `reason`, nullable
- `occurred_at`
- timestamps

### 4.2 Proveedores

#### `suppliers`

- `id`
- `tax_id` o RUC, único
- `business_name`
- `trade_name`, nullable
- `contact_name`, nullable
- `phone`, nullable
- `secondary_phone`, nullable
- `email`, nullable
- `address`, nullable
- `district`, `province`, nullable
- `notes`, nullable
- `is_active`
- timestamps

#### `product_supplier`

Relaciona los productos con los proveedores que pueden suministrarlos:

- `product_id`
- `supplier_id`
- `supplier_sku`, nullable
- `last_unit_cost`, nullable
- `lead_time_days`, nullable
- `availability_status`: desconocido, disponible, limitado o no disponible
- `available_quantity`, nullable
- `last_checked_at`, nullable
- `is_preferred`
- timestamps

La combinación `product_id + supplier_id` será única.

### 4.3 Consultas y cotizaciones

#### `supplier_inquiries`

- `id`
- `number`, único y legible
- `supplier_id`
- `requested_by`
- `status`: borrador, enviada, respondida, cerrada o cancelada
- `requested_at`, nullable
- `responded_at`, nullable
- `valid_until`, nullable
- `notes`, nullable
- timestamps

#### `supplier_inquiry_items`

- `supplier_inquiry_id`
- `product_id`
- `quantity_requested`
- `is_available`, nullable
- `quantity_available`, nullable
- `quoted_unit_cost`, nullable
- `supplier_notes`, nullable

Una consulta respondida podrá convertirse en pedido conservando los precios y cantidades aceptadas.

### 4.4 Pedidos y recepciones

#### `purchase_orders`

- `id`
- `number`, único y legible
- `supplier_id`
- `supplier_inquiry_id`, nullable
- `created_by`
- `approved_by`, nullable
- `status`: borrador, enviado, confirmado, recibido parcialmente, recibido o cancelado
- `currency`, inicialmente `PEN`
- `ordered_at`, nullable
- `expected_at`, nullable
- `received_at`, nullable
- `subtotal`, `tax`, `total`
- `notes`, nullable
- `cancellation_reason`, nullable
- timestamps

#### `purchase_order_items`

- `purchase_order_id`
- `product_id`
- nombre, SKU y unidad copiados como valores históricos
- `quantity_ordered`
- `quantity_received`
- `unit_cost`
- `subtotal`

#### `purchase_receipts`

- `id`
- `number`, único
- `purchase_order_id`
- `received_by`
- `received_at`
- `supplier_document_number`, nullable
- `notes`, nullable
- timestamps

#### `purchase_receipt_items`

- `purchase_receipt_id`
- `purchase_order_item_id`
- `product_id`
- `quantity_received`
- `unit_cost`

La recepción se procesará en una transacción con bloqueo de las filas de producto. No se podrá recibir más de la cantidad pendiente.

## 5. Archivos con Media Library

| Modelo    | Colección     | Regla                                                       |
| --------- | ------------- | ----------------------------------------------------------- |
| Producto  | `images`      | Varias imágenes JPG, PNG o WebP; una marcada como principal |
| Proveedor | `logo`        | Un solo archivo de imagen                                   |
| Consulta  | `attachments` | Cotizaciones y documentos PDF o imagen                      |
| Pedido    | `attachments` | Orden enviada, factura o documentación relacionada          |
| Recepción | `attachments` | Guía, factura o evidencia de entrega                        |

Los archivos se validarán por MIME y tamaño. Las conversiones de miniatura se añadirán únicamente cuando el servidor tenga disponible un driver de imágenes compatible.

## 6. CRUD y pantallas

### 6.1 Categorías

- Listar, buscar y filtrar por tipo/estado.
- Crear.
- Editar.
- Activar o desactivar.
- Eliminar solo cuando no tenga productos.

### 6.2 Productos e inventario

- Listado con búsqueda por nombre, SKU, marca o descripción.
- Filtros por categoría, tipo y estado de stock.
- Crear y editar producto.
- Activar o desactivar producto.
- Detalle con información general, galería, stock, proveedores e historial.
- Ajuste manual de stock con motivo obligatorio.
- Acceso directo para consultar proveedor o crear pedido.
- Indicadores: disponible, stock bajo y agotado.

### 6.3 Proveedores

- Listado en tarjetas o tabla según el tamaño de pantalla.
- Buscar por razón social, RUC, contacto o producto ofrecido.
- Crear, ver, editar, activar y desactivar.
- Detalle con datos de contacto, catálogo, consultas, pedidos abiertos e historial.
- Asociar o retirar productos del catálogo del proveedor.
- Registrar disponibilidad, último precio y plazo de entrega.

### 6.4 Consultas a proveedores

- Crear desde un producto o desde el perfil del proveedor.
- Agregar varios productos y cantidades.
- Editar mientras sea borrador.
- Marcar como enviada.
- Registrar respuesta, disponibilidad, cantidades y precios.
- Convertir la respuesta en pedido.
- Cerrar o cancelar.

En la primera versión la comunicación será registrada manualmente. El envío real por correo o WhatsApp queda preparado como integración futura.

### 6.5 Pedidos de compra

- Listar pedidos activos con filtros por proveedor, estado, producto y fecha.
- Crear desde inventario, proveedor o consulta respondida.
- Editar productos y cantidades solo en borrador.
- Enviar, confirmar o cancelar según transición válida.
- Ver detalle con totales, documentos y cantidades pendientes.
- Registrar una o varias recepciones.
- Imprimir o descargar una representación del pedido en una fase posterior.

### 6.6 Historial de compras

- Vista separada enfocada en pedidos recibidos y cancelados.
- Filtros por número, proveedor, producto, estado y rango de fechas.
- Resumen de monto, unidades y frecuencia de compra por proveedor.
- Detalle de cada pedido y sus recepciones.
- Acceso al historial desde el producto y desde el proveedor.
- Los registros históricos no se editarán directamente.

## 7. Rutas y organización del código

Se mantendrá la estructura estándar actual de Laravel:

- Modelos en `app/Models`.
- Controladores resource en `app/Http/Controllers`.
- Form Requests en `app/Http/Requests`.
- Policies en `app/Policies`.
- Enums de estados en `app/Enums`.
- Actions solo para operaciones transaccionales como recibir un pedido o ajustar stock.
- Páginas Inertia en `resources/js/pages`.
- Componentes reutilizables en `resources/js/components`.

Rutas principales propuestas:

| URI                                | Nombre lógico               |
| ---------------------------------- | --------------------------- |
| `/inventario`                      | `inventory.products.index`  |
| `/inventario/productos/{product}`  | `inventory.products.show`   |
| `/inventario/movimientos`          | `inventory.movements.index` |
| `/proveedores`                     | `suppliers.index`           |
| `/proveedores/{supplier}`          | `suppliers.show`            |
| `/compras/consultas`               | `purchases.inquiries.index` |
| `/compras/pedidos`                 | `purchases.orders.index`    |
| `/compras/pedidos/{purchaseOrder}` | `purchases.orders.show`     |
| `/compras/historial`               | `purchases.history.index`   |
| `/reportes`                        | `reports.index`             |
| `/reportes/compras`                | `reports.purchases.index`   |
| `/reportes/consumo`                | `reports.consumption.index` |
| `/reportes/reposicion`             | `reports.replenishment.index` |

Las rutas estarán protegidas por `auth`, `verified` y permisos. La autorización de cada registro se resolverá mediante Policies; ocultar un botón en Vue no reemplazará la autorización del servidor.

## 8. Permisos y roles

Permisos propuestos:

- `categorias.ver`, `categorias.gestionar`
- `inventario.ver`, `inventario.gestionar`, `inventario.ajustar`
- `proveedores.ver`, `proveedores.gestionar`
- `consultas-proveedor.ver`, `consultas-proveedor.gestionar`
- `pedidos-compra.ver`, `pedidos-compra.crear`, `pedidos-compra.aprobar`
- `pedidos-compra.recibir`, `pedidos-compra.cancelar`
- `historial-compras.ver`
- `reportes.ver`

Matriz inicial:

| Capacidad                        | Administrador | Recepción | Mecánico |
| -------------------------------- | ------------- | --------- | -------- |
| Gestionar categorías y productos | Sí            | Sí        | No       |
| Ver inventario                   | Sí            | Sí        | Sí       |
| Ajustar stock                    | Sí            | Sí        | No       |
| Gestionar proveedores            | Sí            | Sí        | No       |
| Consultar disponibilidad         | Sí            | Sí        | No       |
| Crear pedidos                    | Sí            | Sí        | No       |
| Aprobar o cancelar pedidos       | Sí            | No        | No       |
| Recibir pedidos                  | Sí            | Sí        | No       |
| Ver historial de compras         | Sí            | Sí        | No       |
| Ver reportes                     | Sí            | Sí        | No       |

El Administrador conservará acceso total mediante el `Gate::before` existente. Las reglas del negocio comprobarán permisos, no nombres de roles.

## 9. Fases de implementación

### Fase 0 — Base ya disponible

- Login privado sin registro público.
- Roles iniciales con Spatie Permission.
- Media Library instalada.
- Layout responsive y paleta Fredy Racing.

### Fase 1 — Fundamentos del dominio

**Estado: completada el 9 de septiembre de 2026.**

Entregables:

- [x] Enums de tipos, unidades y estados.
- [x] Migraciones y modelos de categorías, productos, proveedores y relación producto-proveedor.
- [x] Relaciones Eloquent, factories y seeders de demostración.
- [x] Nuevos permisos y actualización idempotente de roles.
- [x] Policies y navegación visible según permisos.

Termina cuando las migraciones, relaciones, permisos y pruebas de autorización pasan.

### Fase 2 — CRUD de proveedores

**Estado: completada el 9 de septiembre de 2026.**

Entregables:

- [x] Listado responsive con búsqueda y filtros.
- [x] Alta, detalle, edición y desactivación.
- [x] Logo y archivos del proveedor.
- [x] Pestañas preparadas para catálogo, consultas e historial.

Termina cuando el CRUD funciona en móvil, tablet y computadora, y usuarios sin permiso reciben `403`.

### Fase 3 — CRUD de inventario y productos

**Estado: completada el 9 de septiembre de 2026.**

Entregables:

- [x] CRUD de categorías y productos.
- [x] Listado responsive basado en el boceto y la paleta Fredy Racing.
- [x] Detalle lateral en escritorio y pantalla completa en móvil.
- [x] Galería del producto.
- [x] Ajustes de stock e historial de movimientos.
- [x] Indicadores y filtro de stock bajo/agotado.

Termina cuando cada cambio de stock deja un movimiento auditable y el saldo coincide.

### Fase 4 — Catálogo y consultas a proveedores

**Estado: completada el 9 de septiembre de 2026.**

Entregables:

- [x] Asociación de productos con proveedores.
- [x] Disponibilidad, precio y plazo de entrega.
- [x] CRUD y flujo de consultas/cotizaciones.
- [x] Conversión de una respuesta en borrador de pedido.

Termina cuando se puede seleccionar un producto, consultar proveedores y registrar su respuesta sin alterar stock.

### Fase 5 — Pedidos de compra

Entregables:

- [x] CRUD de pedidos mientras estén en borrador.
- [x] Numeración automática.
- [x] Cálculo validado de subtotal, impuesto y total.
- [x] Flujo de envío, confirmación y cancelación.
- [x] Listado de pedidos activos y detalle responsive.
- [x] Adjuntos del pedido.
- [x] Acceso contextual para crear pedidos desde el inventario.
- [x] Controles de formulario adaptados a modo claro y oscuro.
- [x] Escaneo de código de barras con la cámara para agregar líneas al pedido
      (`@zxing/library`, endpoint `inventory.products.barcode.lookup`).

Termina cuando las transiciones inválidas están bloqueadas y los totales se recalculan en el servidor.

### Fase 6 — Recepciones e historial

Entregables:

- [x] Recepciones parciales y totales.
- [x] Actualización transaccional del stock.
- [x] Movimientos automáticos de entrada.
- [x] Cierre automático del pedido al completar cantidades.
- [x] Historial global, por proveedor y por producto.
- [x] Adjuntos de factura o guía.
- [x] Protección idempotente contra recepciones duplicadas.
- [x] Recepciones y líneas históricas inmutables.
- [x] Escaneo de código de barras para sumar cantidades recibidas línea por línea.
- [x] Costo unitario editable al recibir: se muestra el costo del pedido, se avisa si
      cambió y el valor recibido actualiza `last_purchase_cost` del producto y el
      `last_unit_cost` del catálogo del proveedor.

Termina cuando no es posible duplicar una recepción, exceder cantidades ni desalinear stock y movimientos.

### Fase 7 — Reportes y endurecimiento

**Estado: pendiente.**

Objetivo: cerrar el módulo con reportes de gestión de compras e inventario, exportaciones,
y una pasada final de rendimiento, accesibilidad y seguridad sobre datos representativos.
No se introducen nuevas entidades de dominio: los reportes se construyen sobre las tablas
existentes (`purchase_orders`, `purchase_order_items`, `purchase_receipts`,
`purchase_receipt_items`, `inventory_movements`, `products`, `product_categories`,
`suppliers`, `product_supplier`).

#### 7.1 Permisos y navegación

- [ ] Reutilizar el permiso `reportes.ver` ya sembrado en `RolePermissionSeeder`.
- [ ] Confirmar la matriz: Administrador y Recepción ven reportes; Mecánico no.
- [ ] Añadir el grupo «Reportes» a `AppSidebar.vue`, visible solo con `reportes.ver`.
- [ ] `ReportPolicy` o `Gate::define('reportes.ver', …)` para un punto único de autorización;
      cada controlador de reporte llama a `authorize('reportes.ver')`.
- [ ] Pruebas de que un usuario sin `reportes.ver` recibe `403` en cada ruta de reporte
      y no ve el enlace en la navegación.

#### 7.2 Reportes

Cada reporte es una página Inertia de solo lectura con filtros en la URL (query string),
estados de carga, vacío y error, y diseño responsive (tabla en escritorio, tarjetas en móvil).
Los cálculos monetarios se hacen en enteros/decimales en el servidor, nunca en el cliente.

| URI | Nombre lógico | Contenido |
| --- | --- | --- |
| `/reportes` | `reports.index` | Panel con accesos y KPIs del periodo actual |
| `/reportes/compras` | `reports.purchases.index` | Compras por proveedor, producto, categoría y periodo |
| `/reportes/consumo` | `reports.consumption.index` | Productos de mayor consumo y rotación |
| `/reportes/reposicion` | `reports.replenishment.index` | Stock bajo/agotado y sugerencia de reposición |

- [ ] **Panel `reports.index`**: totales del periodo (monto comprado, pedidos recibidos,
      unidades ingresadas, número de proveedores activos), comparativa con el periodo anterior
      y enlaces a los reportes de detalle.
- [ ] **Compras `reports.purchases.index`**:
  - Filtros: rango de fechas (por defecto últimos 30 días), proveedor, categoría, producto, estado.
  - Agrupación conmutable por proveedor, por producto o por categoría.
  - Columnas: unidades, subtotal, impuesto, total, número de pedidos, ticket promedio.
  - Base de datos: solo pedidos `recibido` y `recibido parcialmente`, por `received_at`;
    los cancelados se cuentan aparte.
  - Fila de totales y detalle expandible por pedido/recepción.
- [ ] **Consumo `reports.consumption.index`**:
  - Basado en `inventory_movements` de tipo consumo de taller, ajuste de salida y devolución.
  - Filtros: rango de fechas, categoría, producto.
  - Columnas: cantidad consumida, número de movimientos, stock actual, cobertura estimada en días.
  - Orden por cantidad consumida descendente; top N configurable.
- [ ] **Reposición `reports.replenishment.index`**:
  - Productos activos con `current_stock <= minimum_stock`, separando bajo y agotado.
  - Muestra proveedor preferido, último costo, plazo de entrega y cantidad sugerida
    (`minimum_stock - current_stock`, redondeada a la unidad del producto).
  - Acción contextual «Crear pedido» que preselecciona producto y proveedor preferido
    (reutiliza el flujo de Fase 5, sin lógica nueva de stock).
  - Indicadores con texto e icono, no solo color.

#### 7.3 Exportación e impresión

- [ ] Confirmar formato requerido con el negocio antes de implementar (CSV y/o PDF).
- [ ] Exportación CSV en streaming (`response()->streamDownload`) para cada reporte,
      respetando los filtros activos; separador y codificación compatibles con Excel en español.
- [ ] Vista imprimible (`?print=1` o ruta `/reportes/*/imprimir`) con estilos `@media print`,
      cabecera con logo, rango de fechas y filtros aplicados.
- [ ] PDF solo si el negocio lo pide; en ese caso evaluar `spatie/laravel-pdf` o
      `barryvdh/laravel-dompdf` y registrar la decisión de dependencia en el plan.
- [ ] Pruebas de que la exportación incluye los mismos registros que la vista y respeta permisos.

#### 7.4 Optimización de consultas e índices

- [ ] Seeder de volumen (`--class=DemoLargeDataSeeder` o factory masivo) con datos
      representativos: ~5k productos, ~200 proveedores, ~10k pedidos, ~30k movimientos.
- [ ] Medir con `DB::listen` / Laravel Debugbar las consultas de cada listado y reporte;
      registrar el antes/después.
- [ ] Añadir índices faltantes por migración: `inventory_movements (product_id, occurred_at)`,
      `purchase_orders (supplier_id, status, received_at)`,
      `purchase_order_items (product_id)`, `purchase_receipts (purchase_order_id, received_at)`,
      `products (is_active, product_category_id)`, `product_supplier (supplier_id, is_preferred)`.
- [ ] Eliminar N+1 con `with`, `withCount` y `select` acotado; paginar todo listado no agregado.
- [ ] Cachear agregados del panel por periodo con TTL corto e invalidación al recibir un pedido
      o registrar un movimiento.
- [ ] Confirmar que ningún reporte carga colecciones completas en memoria para sumar.

#### 7.5 Endurecimiento final

- [ ] **Seguridad**: repaso de todas las Policies; `php artisan route:list` para verificar
      que cada ruta tiene `auth`, `verified` y autorización; revisión de asignación masiva
      (`$fillable`), de `FormRequest` en cada escritura y de exposición de datos en props Inertia.
- [ ] Ejecutar `/security-review` sobre la rama y resolver hallazgos.
- [ ] **Accesibilidad**: foco visible, navegación por teclado en tablas y menús, roles ARIA
      en diálogos, contraste AA en tema claro y oscuro, textos alternativos en imágenes,
      anuncio de estados de carga y error.
- [ ] **Responsive**: verificación manual de cada pantalla del módulo de 360 px a escritorio,
      incluidas las nuevas de reportes.
- [ ] **Estados**: confirmar que toda vista tiene carga, vacío, error y permiso insuficiente.
- [ ] **Consistencia de datos**: comando `inventory:check` que compara `current_stock` con la
      suma de `inventory_movements` y reporta desalineaciones.

#### 7.6 Pruebas y cierre de calidad

- [ ] Feature tests de cada reporte: filtros, agrupaciones, totales, permisos, formato de exportación.
- [ ] Pruebas de que los reportes solo consideran pedidos recibidos y no alteran ningún dato.
- [ ] Pruebas del comando de consistencia de stock.
- [ ] `php artisan test` (suite completa) en verde.
- [ ] `vendor/bin/pint`, `vendor/bin/phpstan analyse`, `npm run lint`,
      `vue-tsc --noEmit` (TypeScript) y `npm run build` sin errores.
- [ ] Actualizar este plan marcando la Fase 7 como completada con la fecha.

Termina cuando los reportes reflejan los pedidos recibidos sin modificar datos, la exportación
respeta filtros y permisos, los listados y reportes rinden con datos de volumen, y la suite
completa, PHPStan, TypeScript, lint y build pasan.

Con esta fase se cierra el módulo de inventario y compras para su presentación al cliente. Los
reportes de taller, caja y clientes se añaden más adelante en la **Fase 13** de
[`implementation_plan_mecanica.md`](implementation_plan_mecanica.md), que reutiliza estas mismas
pantallas y permisos.

## 10. Estrategia de pruebas

Cada fase incluirá pruebas antes de considerarse terminada:

- Feature tests de listado, creación, edición y desactivación.
- Validación de campos obligatorios, valores límite y duplicados.
- Pruebas de autenticación y permisos insuficientes.
- Pruebas de archivos aceptados/rechazados.
- Pruebas de filtros y búsquedas.
- Pruebas de transición de estados.
- Pruebas transaccionales de recepción y movimientos de stock.
- Pruebas de entregas parciales y cantidades excedidas.
- Verificación de que pedidos y consultas no alteran inventario.
- Build, TypeScript, lint, Pint y PHPStan en cada fase.

## 11. Criterios generales de terminado

Una fase se considera completa cuando:

- El comportamiento acordado funciona de extremo a extremo.
- No se expone ninguna acción sin autorización del servidor.
- El diseño funciona desde 360 px hasta escritorio.
- La vista tiene estados de carga, vacío, error y éxito.
- Los importes y existencias mantienen consistencia.
- No se agregan dependencias sin necesidad y aprobación.
- Las pruebas relacionadas pasan y el proyecto compila.

## 12. Fuera del alcance inicial

- Envío automático real por WhatsApp o correo.
- Facturación electrónica con SUNAT.
- Cuentas por pagar y conciliación bancaria.
- Integración directa con sistemas de proveedores.
- Aplicación móvil nativa.

> **Actualización (10 de septiembre de 2026):** las **ventas a clientes** y el
> **control de caja** sí se implementaron como el módulo de tienda descrito en la
> **Fase 10** de [`implementation_plan_mecanica.md`](implementation_plan_mecanica.md).
> El resto sigue fuera del alcance por ahora.

Estas funciones podrán añadirse después sin cambiar el flujo central de inventario, pedidos y recepciones.
