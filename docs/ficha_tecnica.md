# Ficha tecnica operativa

Ultima actualizacion: 2026-04-30

Este documento describe como usar y mantener las funciones principales del sistema. Cada cambio funcional nuevo debe agregarse aca con fecha, alcance y pasos de uso.

## Alcance

El sistema permite gestionar usuarios, clientes, stock, productos, ventas, reparaciones, comprobantes PDF y preparacion para facturacion fiscal ARCA/AFIP mediante cola.

La autorizacion fiscal definitiva no la decide el sistema local. El sistema valida datos minimos antes de confirmar una venta, pero la API o ARCA puede aprobar o rechazar segun CUIT, punto de venta, certificado, condicion fiscal, tipo de comprobante y normativa vigente.

## Acceso

1. Abrir el sistema desde el navegador.
2. Ingresar usuario y clave.
3. El menu superior muestra los modulos habilitados para el rol.
4. El boton `Barra` permite elegir que modulos se ven arriba.

## Clientes

### Crear cliente

1. Ir a `Clientes`.
2. Presionar `+ Nuevo`.
3. Cargar `Nombre`.
4. Cargar datos fiscales si el cliente puede recibir factura:
   - `Tipo documento`: DNI, CUIT, CUIL o Pasaporte.
   - `CUIT / DNI`: cargar sin guiones.
   - `Condicion IVA`: Consumidor Final, Responsable Inscripto, Monotributista, Exento o No Responsable.
   - `Email fiscal`: opcional.
5. Cargar telefono y direccion si corresponde.
6. Guardar.

### Editar cliente

1. Ir a `Clientes`.
2. Buscar el cliente.
3. Presionar `Editar`.
4. Actualizar datos comerciales o fiscales.
5. Guardar.

### Regla para Factura A

Para emitir Factura A, el cliente debe tener:

- `Tipo documento`: CUIT.
- CUIT de 11 digitos.
- `Condicion IVA`: Responsable Inscripto.

Si estos datos no estan completos, el sistema no confirma la venta como Factura A. Esta validacion evita rechazos previsibles antes de enviar a la API fiscal.

## Stock

### Crear item de stock

1. Ir a `Stock`.
2. Crear el item base con nombre, unidad, cantidad y costo.
3. Guardar.

### Editar stock

1. Ir a `Stock`.
2. Buscar el item.
3. Editar cantidad, costo o estado.
4. Guardar.

El stock se descuenta al confirmar una venta. Si no hay cantidad suficiente, la venta no se confirma.

## Productos

### Crear producto

1. Ir a `Productos`.
2. Crear producto con nombre y codigo de barras.
3. Asociarlo a stock si corresponde.
4. Definir factor de conversion.
5. Definir ganancia y precio final.
6. Guardar.

### Factor de conversion

El factor define cuanto stock consume una unidad vendida del producto. Ejemplo: si el producto consume 0.5 kg por unidad, el factor debe ser `0.5`.

## Nueva venta

### Seleccionar tipo de comprobante

En la parte superior de `Nueva venta` aparece un bloque de comprobante oficial con letra grande:

- `Factura A`: para cliente Responsable Inscripto con CUIT.
- `Factura B`: para consumidor final, monotributista o exento.
- `Factura C`: para emisor monotributista, si corresponde a la configuracion fiscal del negocio.
- `Nota de credito A/B/C`: corrige o anula un comprobante fiscal autorizado anterior.
- `Nota de debito A/B/C`: incrementa o ajusta un comprobante fiscal autorizado anterior.
- `Presupuesto`: documento interno no fiscal.

La letra elegida se mantiene al agregar productos y se envia al confirmar el comprobante.

Las notas de credito/debito estan visibles como tipos legales, pero el sistema bloquea su emision si no existe referencia al comprobante autorizado que se corrige. No deben emitirse como una venta comun.

El presupuesto no descuenta stock y no se envia a ARCA.

### Seleccionar cliente

1. En `Nueva venta`, presionar `Seleccionar cliente`.
2. Usar el buscador del panel.
3. Hacer clic en el cliente correcto.
4. El encabezado de la venta muestra el cliente seleccionado.

Para Factura A no usar `Consumidor Final`; se debe elegir un cliente con CUIT y condicion Responsable Inscripto.

### Agregar productos

1. Buscar producto por nombre o codigo de barras.
2. Seleccionar producto.
3. Cargar cantidad.
4. Cargar descuento si corresponde.
5. Presionar `Agregar producto`.

El sistema valida que el producto este activo y que haya stock suficiente.

### Confirmar venta

1. Revisar cliente, tipo de factura, productos, cantidades y total.
2. Presionar `Confirmar venta`.
3. El sistema registra la venta localmente.
4. Se genera PDF si DomPDF esta disponible.
5. Se crea un comprobante fiscal pendiente en cola.

La venta no debe depender de que ARCA o la API fiscal respondan en el momento.

### Generar presupuesto

1. En `Tipo de comprobante`, elegir `Presupuesto`.
2. Seleccionar cliente.
3. Agregar productos.
4. Presionar `Generar presupuesto`.
5. El sistema guarda el presupuesto y genera PDF.

El presupuesto no descuenta stock, no crea factura fiscal y no se envia a ARCA.

## Ventas

### Ver historial

1. Ir a `Ventas`.
2. Filtrar por fecha, cliente, total u otros campos.
3. Revisar PDF desde el boton `Ver PDF`.
4. Revisar estado fiscal en la columna `Fiscal`.

### Estados fiscales

- `PENDIENTE`: comprobante creado localmente y esperando envio o reintento.
- `EN_PROCESO`: comprobante tomado por el procesador de cola.
- `APROBADO`: API fiscal devolvio autorizacion.
- `RECHAZADO`: API fiscal rechazo por regla de negocio o datos invalidos.
- `ERROR`: hubo error tecnico y requiere reintento o revision.
- `SIN COLA`: venta anterior o venta sin registro fiscal asociado.

## Cola fiscal ARCA/AFIP

### Configuracion

Archivo: `configuraciones/arca.php`

Campos principales:

- `habilitado`: poner `true` cuando la integracion este lista.
- `modo`: `homologacion` o `produccion`.
- `proveedor`: actualmente preparado como `api_rest`.
- `api_rest.endpoint`: URL de la API fiscal.
- `api_rest.token`: token de autenticacion si la API lo requiere.
- `empresa.cuit`: CUIT emisor.
- `empresa.punto_venta`: punto de venta autorizado.
- `comprobante_defecto`: valores base para comprobantes.

### Procesar cola manualmente

Ejecutar desde la raiz del proyecto:

```powershell
php scripts\procesar_cola_fiscal.php 10
```

El numero indica la cantidad maxima de comprobantes a procesar.

### Reintentos

Si la API no responde, devuelve timeout o hay error transitorio, el comprobante queda pendiente para reintento. El sistema de ventas no se detiene por ese error.

## Base de datos

### Tablas fiscales

Script:

```powershell
& "C:\xampp82\mysql\bin\mysql.exe" -u root sistema_ventas -e "source docs/sql_facturacion_fiscal.sql"
```

### Campos fiscales de clientes

Script:

```powershell
& "C:\xampp82\mysql\bin\mysql.exe" -u root sistema_ventas -e "source docs/sql_clientes_fiscal.sql"
```

El modelo `Cliente` tambien intenta crear automaticamente las columnas fiscales si faltan y la base esta disponible.

### Tablas de presupuestos

Script:

```powershell
& "C:\xampp82\mysql\bin\mysql.exe" -u root sistema_ventas -e "source docs/sql_presupuestos.sql"
```

## PDF

Al confirmar venta, el sistema genera `almacenamiento/pdf/venta_ID.pdf` en formato comandera de 80 mm.

El PDF de venta muestra:

- Razon social, CUIT, condicion IVA, domicilio, Ingresos Brutos e inicio de actividades del emisor.
- Letra y tipo de comprobante.
- Codigo identificatorio del tipo de comprobante.
- Leyenda `ORIGINAL` o la copia configurada.
- Punto de venta y numero cuando la API fiscal lo haya devuelto.
- Fecha, cliente, documento, condicion IVA y domicilio del receptor.
- Remito vinculado si se configuro.
- Items, cantidades, precio, descuento y subtotal.
- Total.
- IVA discriminado para comprobantes A.
- Nota de IVA contenido para comprobantes B.
- Nota sin IVA discriminado para comprobantes C.
- CAE y vencimiento si la API/ARCA ya autorizo el comprobante.
- QR si la API fiscal devuelve `qr_base64` o `qr_url`.
- Leyenda de CAE pendiente si todavia no fue autorizado.

Un comprobante fiscal es legalmente completo recien cuando ARCA/API devuelve CAE, numero, vencimiento y datos de QR. Antes de eso, el PDF queda como representacion pendiente y no debe tratarse como factura fiscal autorizada.

Al generar presupuesto, el sistema genera `almacenamiento/pdf/presupuesto_ID.pdf` en formato comandera de 80 mm con letra `X` y leyenda `DOCUMENTO NO VALIDO COMO FACTURA`.

Si falla:

1. Verificar que exista `vendor/autoload.php`.
2. Verificar permisos de escritura en `almacenamiento/pdf`.
3. Revisar `almacenamiento/logs/app.log`.

## Logs

Archivo principal:

```text
almacenamiento/logs/app.log
```

Se registran errores de base de datos, PDF, facturacion fiscal y excepciones de modelos.

## Checklist Para Cambios Futuros

Cada vez que se modifique el sistema:

1. Actualizar esta ficha tecnica.
2. Indicar fecha del cambio.
3. Describir que cambio funcional se agrego.
4. Explicar como se usa.
5. Explicar si requiere migracion SQL, configuracion o tarea manual.
6. Validar sintaxis PHP con `php -l` en archivos tocados.

## Historial De Cambios Documentados

### 2026-04-30 - Facturacion fiscal y cola ARCA/AFIP

- Se agrego configuracion fiscal en `configuraciones/arca.php`.
- Se agrego cola fiscal en `fiscal_comprobantes` y `fiscal_cola`.
- Se agrego procesador `scripts/procesar_cola_fiscal.php`.
- Las ventas quedan registradas localmente aunque la API fiscal falle.

### 2026-04-30 - Datos fiscales de clientes

- Se agregaron `tipo_documento`, `condicion_iva` y `email`.
- Se agrego validacion minima para Factura A.
- Se actualizo el alta y edicion de clientes.

### 2026-04-30 - Tipo de factura en nueva venta

- Se agrego selector superior de Factura A/B/C.
- Se muestra letra grande tipo comprobante oficial.
- Se mantiene el tipo elegido al agregar productos.
- Se envia el tipo elegido a la cola fiscal al confirmar venta.

### 2026-04-30 - Selector de clientes en venta

- Se reemplazo el selector deformado por un panel de opciones clickeables.
- El panel permite buscar y seleccionar cliente sin romper el layout.

### 2026-04-30 - Comprobantes legales ampliados

- Se agregaron opciones de Factura A/B/C, Nota de Credito A/B/C, Nota de Debito A/B/C y Presupuesto.
- Se agrego modelo `Presupuesto`.
- Se agregaron tablas `presupuestos` y `detalle_presupuesto`.
- El presupuesto genera PDF, no descuenta stock y no se envia a ARCA.
- Las notas de credito/debito quedan bloqueadas hasta implementar referencia obligatoria al comprobante fiscal autorizado.

### 2026-04-30 - Encabezado de comprobante compacto

- Se redujo la altura del bloque superior de tipo de comprobante.
- Se alinearon tipo de comprobante, cliente y total en una misma grilla compacta.
- La regla del comprobante queda en una linea con recorte visual para evitar desorden.

### 2026-04-30 - PDF fiscal en formato comandera 80 mm

- Se cambio el PDF de ventas a ancho 80 mm.
- Se agregaron datos fiscales del emisor y receptor.
- Se agrego codigo identificatorio del tipo de comprobante, leyenda de copia y remito vinculado.
- Se agrego desglose de IVA para comprobantes A.
- Se agrego leyenda de CAE pendiente cuando aun no hay autorizacion fiscal.
- Se dejo preparado el PDF para imprimir QR cuando la API entregue `qr_base64` o `qr_url`.
- Se cambio el presupuesto a letra `X` con leyenda `DOCUMENTO NO VALIDO COMO FACTURA`.
