# Ficha tecnica operativa

Ultima actualizacion: 2026-05-22

Este documento describe como usar y mantener las funciones principales del sistema. Cada cambio funcional nuevo debe agregarse aca con fecha, alcance y pasos de uso.

## Alcance

El sistema permite gestionar usuarios, clientes, stock, productos, ventas, reparaciones, comprobantes PDF y preparacion para facturacion fiscal ARCA/AFIP mediante cola.

La autorizacion fiscal definitiva no la decide el sistema local. El sistema valida datos minimos antes de confirmar una venta, pero la API o ARCA puede aprobar o rechazar segun CUIT, punto de venta, certificado, condicion fiscal, tipo de comprobante y normativa vigente.

## Ordenamiento global inteligente

Actualizado: 2026-05-21

Las listas principales abren ordenadas desde backend con `ORDER BY` validado por whitelist:

- Productos y Stock: `nombre ASC`.
- Clientes: `nombre ASC`.
- Usuarios: `usuario ASC`.
- Ventas: `fecha DESC`.
- Listas de precios: `nombre ASC`.
- Cuenta corriente: vencimiento ascendente por defecto.
- Importaciones: preview ordenado por fila ascendente, con ordenamiento backend sobre el arreglo analizado.
- Reparaciones: el modulo PHP abre el panel embebido de Python; el API local `/api/reparaciones` tambien recibe `orden` y `direccion` y aplica `ORDER BY` sobre SQLite con whitelist.

Los encabezados clickeables aceptan `orden` y `direccion` por GET. El primer estado visible es ascendente, el segundo descendente y el tercero vuelve al orden por defecto. La ultima preferencia queda guardada en `sessionStorage` por modulo. DataTables queda solo para paginacion/estructura visual y no reordena datos en navegador.

## Acceso

## Instalador Ventas/Reparaciones

Actualizado: 2026-05-26

El instalador `Instalador_Ventas_Reparaciones.exe` incluye el runtime portable necesario para Ventas y Reparaciones: Apache/PHP/MySQL embebidos y Python portable para Reparaciones. La PC cliente no necesita instalar XAMPP, PHP, MySQL ni Python por separado.

Reglas de instalacion:

- Instala el sistema en `C:\Ventas y Reparaciones`.
- Crea el acceso directo `Ventas y Reparaciones` en Escritorio y Menu Inicio.
- Si ya existe una instalacion anterior en `C:\xampp82`, preserva `mysql\data`, `almacenamiento/`, `reparaciones_python/reparaciones.db`, `comercio_config.json` y tickets.
- Si ya existe una instalacion anterior en `C:\Ventas y Reparaciones`, preserva los mismos datos antes de reemplazar archivos del sistema.
- Si el cliente venia usando Reparaciones separado en `C:\Reparaciones\reparaciones_python`, migra `reparaciones.db`, `comercio_config.json` y `tickets/` al nuevo sistema integrado.
- Si es una PC nueva, crea la base `sistema_ventas` limpia, solo con estructura de tablas.
- El paquete nuevo no incluye datos comerciales de ejemplo: nombre, razon social, CUIT, domicilio, ARCA, Backblaze y configuracion de Reparaciones arrancan vacios.
- La configuracion inicial usa `auth_modo = sin_login` para poder entrar y cargar los datos reales del comercio desde `Config > Comercio`.

## Navegacion y configuracion

Actualizado: 2026-05-26

Desde `Config > Sistema` se puede elegir si `Configuracion` queda separada de `Ventas` o integrada al flujo de Ventas.

- Activado: al entrar a `Config`, el sistema mantiene el contexto de Configuracion y no muestra la barra de modulos de Ventas como seccion activa.
- Desactivado: Configuracion se comporta integrada a Ventas como en versiones anteriores.

## Backups manuales y automaticos

Actualizado: 2026-05-26

El backup se configura desde `Config > Backup`.

- `Examinar y guardar en esta PC`: abre el dialogo real de guardar archivo del navegador para elegir carpeta, disco extraible o nombre del respaldo en el momento.
- `Hacer backup ahora`: permite escribir una carpeta de la PC, pendrive o disco externo y, si Backblaze B2 esta configurado, subir la misma copia tambien a la nube.
- `Respaldo local automatico`: permite escribir una carpeta fija para copias programadas, por ejemplo `E:\Respaldos`, o usar `Examinar carpeta para backup automatico` para elegir una carpeta desde Chrome/Edge. Por seguridad del navegador, esa seleccion muestra el nombre de la carpeta pero no la ruta completa.
- `Backblaze B2`: guarda credenciales, bucket y carpeta remota para subir respaldos a Backblaze.
- `Automatico`: define frecuencia, hora y cuantos minutos antes se muestra el aviso.
- `Destinos automaticos`: permite marcar dos destinos a la vez: carpeta local/disco externo y Backblaze B2.

Si una unidad externa no esta conectada, no hay permisos de escritura o Backblaze falla, el sistema muestra un aviso y deja el error en el panel `Estado`. El backup automatico se dispara con el sistema abierto en el navegador; para comercios que cierran a una hora fija, configurar la hora del aviso cinco minutos antes del cierre.

El archivo generado termina en `.tar.gz`. No es un programa ejecutable y no abre el sistema al hacer doble clic. Se abre con 7-Zip, WinRAR o una herramienta compatible para inspeccionar su contenido.

Cada respaldo guarda:

- `ventas_mysql.sql`: copia completa de las tablas MySQL de Ventas.
- `almacenamiento/`: configuracion local, tickets, PDFs, logos procesados, logs y archivos guardados por el sistema.
- `configuraciones/`: archivos PHP de configuracion.
- `aplicacion/`: controladores, modelos y vistas PHP.
- `publico/`: assets publicos, CSS, imagenes e index.
- `reparaciones_python/reparaciones.db`: base SQLite de Reparaciones.
- `reparaciones_python/tickets/`: tickets generados por Reparaciones.
- `LEEME_RESPALDO.txt`, `estructura_respaldo.txt` y `resumen.json`: resumen, conteos de tablas e instrucciones.

Para recuperar en una PC nueva:

1. Ejecutar `Instalador_Ventas_Reparaciones.exe`.
2. Abrir una vez desde el acceso directo `Ventas y Reparaciones` y cerrar.
3. Restaurar desde el panel de Backup o, de forma manual, copiar `almacenamiento/`, `configuraciones/`, `publico/` y `reparaciones_python/` sobre `C:\Ventas y Reparaciones\htdocs\VENTAS`.
4. Si se restaura manualmente la base de Ventas, importar `ventas_mysql.sql` con el MySQL portable de `C:\Ventas y Reparaciones\mysql\bin\mysql.exe`.
5. Revisar `configuraciones/base_datos.php` solo si se cambio usuario, clave o nombre de base.
6. Entrar al sistema y verificar clientes, productos, stock, ventas, usuarios y reparaciones.

## Estadísticas Simplificadas

El sistema utiliza Vistas SQL para centralizar cálculos y evitar campos duplicados:

1. **Top Ventas**: Los productos más exitosos (`vista_top_productos`).
2. **Top Clientes**: Quiénes compran más seguido (`vista_top_clientes`).
3. **Stock Alerta**: Reporte rápido de ítems por agotarse (`vista_resumen_stock`).
3. **Exportación**: El menú de exportación ahora incluye automáticamente la Lista 1 (Público) y Lista 2 (Mayorista).

Para que estas estadísticas sean precisas, las ventas deben estar confirmadas. Los presupuestos no afectan estas vistas.

---

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
2. Crear el item base con nombre, unidad, tipo de stock, cantidad y costo.
3. Usar `Tipo de stock = General` para materia prima o stock base reutilizable como `Yerba (kg)`, `Azucar (kg)`, `Cable (m)`, `Aceite (l)` o `Tela (m)`.
4. Usar `Tipo de stock = Propio` para stocks internos de productos independientes. Estos no aparecen en el selector de stock general de Productos.
5. Guardar.

### Editar stock

1. Ir a `Stock`.
2. Buscar el item.
3. Editar cantidad, costo o estado.
4. Guardar.

El stock se descuenta al confirmar una venta. Si no hay cantidad suficiente, la venta no se confirma.

### Alertas de stock minimo

El sistema muestra alertas cuando un producto activo con `stock propio` queda con `stock_actual <= stock_minimo`. No se incluyen stocks generales asociados ni productos inactivos.

En el menu superior, `Stock` muestra un badge con la cantidad de alertas pendientes. Si hay alertas nuevas o empeoradas, el badge es rojo con animacion suave. Si todas fueron marcadas como leidas pero el stock sigue bajo, el badge queda gris.

La vista `Stock` muestra un panel superior `Productos con stock bajo` con producto, stock actual, stock minimo, estado y ultimo movimiento. En la tabla principal de stock, los productos propios activos con `stock_actual <= stock_minimo` se resaltan con fondo rojo suave, borde rojo, icono de alerta y badge rojo.

- `Critico`: stock igual a cero.
- `Bajo`: stock menor o igual al minimo.
- `Normal`: stock por encima del minimo.

Cada producto puede marcarse como leido desde el panel. Esa accion crea o actualiza un registro en `stock_alertas_leidas` con usuario, fecha de lectura y snapshot de cantidad/minimo. Marcar como leido no corrige ni elimina la alerta real: solo oculta la notificacion pendiente. Si el stock baja mas que la cantidad leida o sube el minimo, vuelve a alertar.

El panel permite filtrar `Solo stock bajo`, `Solo criticos` y `Mostrar leidos`. Cuando hay una alerta pendiente, la vista Stock muestra un toast `Stock bajo` con el primer producto pendiente.

### Unidades de medida

El sistema usa la tabla `unidades_medida` para controlar nombre, abreviatura, tipo, decimales y estado. Se cargan automaticamente unidades base:

- Peso: `kg` con 3 decimales y `g`.
- Volumen: `l` con 3 decimales y `ml`.
- Longitud: `m` con 2 decimales y `cm`.
- Cantidad: `u`, `cj`, `pack` y `doc`, sin decimales.

En Stock y Productos, el campo Unidad es un selector. El desplegable muestra las unidades con formato legible, por ejemplo `Unidad (u)`, `Kilogramo (kg)`, `Gramo (g)`, `Litro (l)`, `Mililitro (ml)`, `Metro (m)`, `Centimetro (cm)`, `Caja (cj)`, `Docena (doc)` y `Pack`. Al quedar seleccionada, la unidad se resume con la abreviatura capitalizada, por ejemplo `Kg`, `Cm` o `Ml`, manteniendo como valor real la abreviatura en minusculas.

La ultima opcion del selector es `Otro...`. Al elegirla se muestra un input simple `Nueva unidad`; al guardar el formulario, el sistema capitaliza visualmente el nombre, crea la unidad en `unidades_medida` si no existe y la usa para el producto o stock. No se usa modal para este flujo.

## Productos

### Crear producto

1. Ir a `Productos`.
2. Crear producto con nombre y codigo de barras.
3. Elegir el tipo de stock:
   - `Stock propio`: para productos que manejan su propia cantidad, unidad y costo, por ejemplo `Coca Cola 2L`.
   - `Asociar stock general`: para productos que descuentan de un stock principal, por ejemplo `Yerba 500gr` que consume `0.5 Kg` de `Yerba`.
4. Si usa stock propio, cargar `Stock`, `Stock minimo`, `Stock maximo`, `Unidad` y `Costo`.
5. Si usa stock general, seleccionar el stock asociado y cargar `Consumo por venta`.
6. Definir ganancia y precio final.
7. Guardar.

### Modos de stock en productos

La ficha de producto separa visualmente los dos modos para no mezclar campos:

- `Stock propio`: muestra solo `Stock`, `Stock minimo`, `Stock maximo`, `Unidad` y `Costo`. No muestra `Agregar stock`; el campo `Stock` representa el stock inicial o actual del producto.
- `Asociar stock general`: muestra solo stocks con `tipo_stock = general` y `activo = 1`, mas el stock disponible, costo actual y unidad en solo lectura. No permite editar cantidad ni costo del stock general desde Productos.

Cuando un producto usa stock general, cada venta descuenta siempre del stock principal seleccionado usando el campo `Consumo por venta`. Ejemplo: si `Poroto Negro 500gr` consume `0.500 Kg`, cada unidad vendida resta `0.500` del stock general `Poroto Negro (Kg)`.

El campo `Consumo por venta` acepta enteros y decimales con punto o coma: `1`, `1,5`, `1.5`, `0,250`, `0.250` o `0.001`. En el formulario HTML usa `step="any"` para no bloquear valores validos como `1`. En PHP se valida que, cuando el producto usa stock general, el consumo convertido sea mayor a cero.

El campo `Costo` del formulario principal es el costo base real del producto o stock. Se carga como importe limpio, por ejemplo `1500,00`, sin flechas de input numerico. La lista `Costo` no se muestra como tarjeta en Productos para evitar duplicacion visual; internamente se sigue alimentando desde ese costo base para que las listas de precios calculen porcentajes, margenes y precios desde la misma referencia.

Los stocks propios no aparecen en `Asociar stock general`. Ejemplos como `Coca Cola 2L`, `Mouse Logitech`, `Notebook` o `Arroz 1kg cerrado` deben quedar como stock propio y no como materia prima reutilizable.

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
& "C:\Ventas y Reparaciones\mysql\bin\mysql.exe" -u root sistema_ventas -e "source docs/sql_facturacion_fiscal.sql"
```

### Campos fiscales de clientes

Script:

```powershell
& "C:\Ventas y Reparaciones\mysql\bin\mysql.exe" -u root sistema_ventas -e "source docs/sql_clientes_fiscal.sql"
```

El modelo `Cliente` tambien intenta crear automaticamente las columnas fiscales si faltan y la base esta disponible.

### Tablas de presupuestos

Script:

```powershell
& "C:\Ventas y Reparaciones\mysql\bin\mysql.exe" -u root sistema_ventas -e "source docs/sql_presupuestos.sql"
```

## PDF

Al confirmar venta, el sistema genera `almacenamiento/pdf/venta_ID.pdf` en formato comandera de 80 mm.

El PDF de venta muestra:

- Razon social, CUIT, condicion IVA, domicilio, Ingresos Brutos e inicio de actividades del emisor.
- Letra y tipo de comprobante.

### Configuracion de comercio e impresion

Desde `Configuracion > Comercio` se cargan los datos principales del negocio: nombre, razon social, CUIT, IVA, direccion, localidad, provincia, telefono, WhatsApp y email. Esos datos son la fuente unica para navbar, tickets, PDF, reportes y vistas previas.

Desde `Configuracion > Apariencia`, `Logo sistema` define el icono visual del marco principal y la vista previa de configuracion. Al guardar, el formulario muestra estado de guardado y el archivo se copia a `almacenamiento/uploads/`.

Desde `Configuracion > Impresion` solo se controla el comportamiento de impresion: formato 58 mm, 80 mm o A4, fuente, tamano, mensaje de pie, logo de ticket, optimizacion termica y opcion `La imagen contiene datos del comercio`.

Si `La imagen contiene datos del comercio` esta activa, el ticket no vuelve a imprimir nombre, razon social, CUIT, direccion, localidad, telefono ni punto de venta en la cabecera. En ese modo muestra el logo procesado, productos, total y mensaje de pie.

El logo de ticket se procesa con `procesar_logo_ticket_termico_hd(ruta, ancho_ticket, modo_termico)`: se adapta a 384 px para 58 mm o 576 px para 80 mm, se trabaja internamente a 4x, se elimina fondo oscuro/gris/degradado, se remueven contornos y cajas rectangulares conectadas al borde, se convierte a blanco y negro, se refuerzan trazos finos y se centra sin deformar la proporcion.

Las copias procesadas se guardan en `almacenamiento/tickets/logos_procesados/` con sufijo `_termico_hd_58.png` o `_termico_hd_80.png`. Si `Optimizar logo termico` esta activo, la vista previa, el ticket impreso y el PDF usan esa misma imagen procesada. Si no esta activo, usan el archivo original.

La vista previa de `Configuracion > Impresion` no pinta el archivo original del logo. El navegador procesa la imagen con Canvas, elimina fondo/caja, muestra ese PNG limpio y lo guarda mediante `guardar_logo_ticket_procesado` en la carpeta de logos procesados. El ticket impreso y el PDF leen esa misma copia procesada. Por regla operativa: lo que se ve en el preview debe coincidir con lo que sale por comandera termica.
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
