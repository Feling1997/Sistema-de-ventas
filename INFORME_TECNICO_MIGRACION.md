# Informe tecnico del proyecto VENTAS

Documento preparado para que otra IA pueda analizar el proyecto y planificar una migracion a Arquitectura Hexagonal, Laravel y una base de datos unificada.

## 1. Estructura completa del proyecto

Arbol funcional del codigo fuente. Las carpetas `vendor/`, `python_runtime/`, `build_*`, backups y artefactos `.exe/.zip` existen en el proyecto, pero no se expanden aqui porque son dependencias o empaquetados generados muy grandes.

```text
C:\xampp82\htdocs\VENTAS
├── almacenamiento
│   ├── configuracion_sistema.json
│   ├── logs/
│   ├── pdf/
│   ├── preferencias_menu/
│   └── tickets/
├── aplicacion
│   ├── controladores
│   │   ├── ControladorAuth.php
│   │   ├── ControladorClientes.php
│   │   ├── ControladorConfiguracion.php
│   │   ├── ControladorConfiguraciones.php
│   │   ├── ControladorCuentasCorrientes.php
│   │   ├── ControladorExportaciones.php
│   │   ├── ControladorImportacion.php
│   │   ├── ControladorListasPrecios.php
│   │   ├── ControladorProductos.php
│   │   ├── ControladorReparaciones.php
│   │   ├── ControladorStock.php
│   │   ├── ControladorUsuarios.php
│   │   └── ControladorVentas.php
│   ├── modelos
│   │   ├── BackblazeB2.php
│   │   ├── Cliente.php
│   │   ├── Configuracion.php
│   │   ├── ConfiguracionSistema.php
│   │   ├── CuentaCorriente.php
│   │   ├── FacturaFiscal.php
│   │   ├── ListaPrecio.php
│   │   ├── ModeloImportacion.php
│   │   ├── Presupuesto.php
│   │   ├── Producto.php
│   │   ├── RespaldoSistema.php
│   │   ├── ServicioFacturacionFiscal.php
│   │   ├── ServicioPrecios.php
│   │   ├── Stock.php
│   │   ├── UnidadMedida.php
│   │   ├── Usuario.php
│   │   └── Venta.php
│   └── vistas
│       ├── auth/
│       ├── clientes/
│       ├── configuracion/
│       ├── configuraciones/
│       ├── cuentas_corrientes/
│       ├── exportaciones/
│       ├── importacion/
│       ├── listas_precios/
│       ├── parciales/
│       ├── productos/
│       ├── stock/
│       ├── usuarios/
│       └── ventas/
├── configuraciones
│   ├── arca.php
│   ├── ayudas.php
│   ├── base_datos.php
│   ├── csrf.php
│   └── seguridad.php
├── docs
│   ├── architecture.md
│   ├── database.md
│   ├── deployment.md
│   ├── development.md
│   ├── ejecutar_ventas_reparaciones_xampp.md
│   ├── ficha_tecnica.md
│   ├── sql_clientes_fiscal.sql
│   ├── sql_facturacion_fiscal.sql
│   ├── sql_presupuestos.sql
│   ├── sql_productos_stock_opcional.sql
│   ├── sql_reparaciones.sql
│   └── user_manual.md
├── publico
│   ├── index.php
│   ├── hash.php
│   └── assets
│       ├── css/app.css
│       └── img/*.jpeg, *.png
├── reparaciones_python
│   ├── app.py
│   ├── database.py
│   ├── modelos.py
│   ├── repositorio.py
│   ├── tickets.py
│   ├── ui.py
│   ├── web_app.py
│   ├── reparaciones.db
│   ├── comercio_config.json
│   ├── CONTROL REPARACIONES.exe
│   └── tickets/
├── scripts
├── vendor
├── composer.json
├── composer.lock
└── instalacion_schema_base.sql
```

## 2. Tecnologias utilizadas

- PHP: aplicacion web principal, MVC casero.
- Python: modulo separado de reparaciones, con interfaz web local y posible interfaz Tkinter.
- JavaScript: interaccion en vistas PHP y aplicacion web embebida de reparaciones.
- MySQL/MariaDB: base principal `sistema_ventas`.
- SQLite: `reparaciones_python/reparaciones.db`.
- TXT: tickets de reparaciones en `reparaciones_python/tickets/*.txt`, logs y documentacion.
- JSON: `almacenamiento/configuracion_sistema.json`, `reparaciones_python/comercio_config.json`, preferencias de menu.
- Bootstrap/Bootstrap Icons: se usan clases `bi-*` y estilos de UI.
- CSS propio: `publico/assets/css/app.css`.
- Composer: gestor de dependencias PHP.

Dependencias directas en `composer.json`:

```json
{
  "require": {
    "dompdf/dompdf": "^3.1",
    "phpoffice/phpspreadsheet": "^5.7"
  }
}
```

Dependencias instaladas/transitivas detectadas:

- `dompdf/dompdf`
- `phpoffice/phpspreadsheet`
- `maennchen/zipstream-php`
- `markbaker/complex`
- `markbaker/matrix`
- `psr/simple-cache`
- `sabberworm/php-css-parser`
- `thecodingmachine/safe`
- `masterminds/html5`

## 3. Arquitectura actual

La arquitectura actual es MVC manual, sin framework.

### Rutas

El punto de entrada es `publico/index.php`.

Las rutas funcionan con parametros GET:

```text
index.php?c=ventas&a=nueva
index.php?c=productos&a=index
index.php?c=reparaciones&a=index
```

- `c`: controlador.
- `a`: accion/metodo publico.

`publico/index.php` contiene un mapa manual de controladores:

```php
$mapa=[
  "auth" => ["archivo" => ".../ControladorAuth.php", "clase" => "ControladorAuth"],
  "usuarios" => ["archivo" => ".../ControladorUsuarios.php", "clase" => "ControladorUsuarios"],
  "clientes" => ["archivo" => ".../ControladorClientes.php", "clase" => "ControladorClientes"],
  "stock" => ["archivo" => ".../ControladorStock.php", "clase" => "ControladorStock"],
  "productos" => ["archivo" => ".../ControladorProductos.php", "clase" => "ControladorProductos"],
  "ventas" => ["archivo" => ".../ControladorVentas.php", "clase" => "ControladorVentas"],
  "reparaciones" => ["archivo" => ".../ControladorReparaciones.php", "clase" => "ControladorReparaciones"]
];
```

### Controladores

Los controladores:

- Validan sesion y roles.
- Leen `GET` y `POST`.
- Validan CSRF en formularios.
- Llaman modelos estaticos.
- Incluyen vistas PHP.

Controladores principales:

- `ControladorVentas.php`: venta nueva, carrito, confirmar, tickets, PDF, presupuestos.
- `ControladorProductos.php`: CRUD productos, listas, unidades, exportacion.
- `ControladorStock.php`: CRUD stock, alertas, faltantes, reportes.
- `ControladorClientes.php`: CRUD clientes.
- `ControladorReparaciones.php`: inicia servidor Python local y lo incrusta en iframe.

### Modelos

Los modelos mezclan:

- Acceso a datos.
- Validaciones.
- Reglas de negocio.
- Migraciones dinamicas de columnas/tablas.

Todos usan `PDO` desde `obtener_pdo()`.

Ejemplos:

- `Venta::confirmar_venta()` crea venta, detalle y descuenta stock en transaccion.
- `Producto` calcula precios y valida codigos.
- `Stock` administra inventario, costos y alertas.
- `Usuario` maneja hash de passwords y permisos.
- `ConfiguracionSistema` sincroniza configuracion entre JSON y base de datos.

### Vistas

Las vistas estan en `aplicacion/vistas`.

Parciales principales:

- `aplicacion/vistas/parciales/encabezado.php`
- `aplicacion/vistas/parciales/menu.php`
- `aplicacion/vistas/parciales/alertas.php`
- `aplicacion/vistas/parciales/pie.php`

### Flujo completo de una solicitud

1. Navegador llama `publico/index.php?c=ventas&a=nueva`.
2. `index.php` carga ayudas, base de datos, seguridad y CSRF.
3. Lee `c` y `a`.
4. Valida permisos del modulo.
5. Busca controlador en el mapa manual.
6. Carga archivo del controlador.
7. Instancia la clase.
8. Ejecuta el metodo publico solicitado.
9. El controlador usa modelos.
10. El modelo consulta MySQL con PDO.
11. El controlador incluye vistas.
12. La vista renderiza HTML/CSS/JS.

## 4. Base de datos

Configuracion principal:

```php
$host="127.0.0.1";
$bd="sistema_ventas";
$usuario="root";
$clave="";
```

Fuente principal de esquema: `instalacion_schema_base.sql`.

### Tablas MySQL

#### clientes

PK: `id`. Unique: `uq_cliente_dni`.

Campos: `id`, `nombre`, `dni`, `tipo_documento`, `condicion_iva`, `email`, `id_lista_precio`, `telefono`, `direccion`, `creado_en`.

#### configuraciones

PK: `id`. Unique: `clave`.

Campos: `id`, `clave`, `valor`, `tipo`, `grupo`.

#### cuentas_corrientes

PK: `id`.

Campos: `id`, `id_cliente`, `id_venta`, `concepto`, `total`, `saldo`, `estado`, `creado_en`.

Relaciones logicas: `id_cliente` con `clientes.id`, `id_venta` con `ventas.id`, aunque no todas estan declaradas como FK.

#### cuentas_corrientes_alertas_lecturas

PK: `id_usuario`.

Campos: `id_usuario`, `leido_hasta`, `actualizado_en`.

#### cuentas_corrientes_cuotas

PK: `id`.

Campos: `id`, `id_cuenta`, `numero`, `vencimiento`, `monto`, `pagado`, `estado`, `pagado_en`.

#### cuentas_corrientes_recibos

PK: `id`.

Campos: `id`, `id_cuenta`, `id_cliente`, `tipo`, `fecha`, `monto`, `forma_pago`, `observacion`.

#### presupuestos

PK: `id`.

Campos: `id`, `fecha`, `id_cliente`, `id_usuario`, `total`, `estado`, `creado_en`.

#### detalle_presupuesto

PK: `id`.

Campos: `id`, `id_presupuesto`, `id_producto`, `producto_nombre`, `cantidad`, `precio_unit`, `descuento`, `subtotal`.

#### ventas

PK: `id`.

FK:

- `id_cliente` -> `clientes.id`
- `id_usuario` -> `usuarios.id`

Campos: `id`, `fecha`, `id_cliente`, `id_usuario`, `total`, `creado_en`.

#### detalle_venta

PK: `id`.

FK:

- `id_venta` -> `ventas.id`
- `id_producto` -> `productos.id`

Campos: `id`, `id_venta`, `id_producto`, `cantidad`, `precio_unit`, `costo_unit`, `descuento`, `subtotal`.

#### productos

PK: `id`. Unique: `cod_barras`.

FK:

- `id_stock` -> `stock.id`
- `id_asociado` -> `stock.id`

Campos: `id`, `nombre`, `cod_barras`, `id_stock`, `id_asociado`, `factor_conversion`, `ganancia`, `precio_final`, `activo`, `creado_en`.

#### producto_precios

PK compuesta: `id_producto`, `id_lista`.

Campos: `id_producto`, `id_lista`, `porcentaje`, `precio`.

Relaciones logicas: `id_producto` con `productos.id`, `id_lista` con `listas_precios.id`.

#### listas_precios

PK: `id`.

Campos: `id`, `nombre`, `activo`, `creado_en`.

#### historial_precios

PK: `id`.

Campos: `id`, `id_producto`, `id_lista`, `precio_anterior`, `precio_nuevo`, `origen`, `creado_en`.

#### stock

PK: `id`.

Campos: `id`, `nombre`, `unidad`, `tipo_stock`, `cantidad`, `stock_minimo`, `stock_maximo`, `precio_costo`, `moneda_costo`, `costo_origen`, `activo`, `creado_en`.

#### stock_alertas_leidas

PK: `id`. Unique: `id_producto`, `usuario`.

Campos: `id`, `id_producto`, `fecha_lectura`, `usuario`, `cantidad_leida`, `stock_minimo_leido`.

#### unidades_medida

PK: `id`. Unique: `abreviatura`.

Campos: `id`, `nombre`, `abreviatura`, `tipo`, `decimales`, `activo`, `creado_en`.

#### usuarios

PK: `id`. Unique: `usuario`.

Campos: `id`, `usuario`, `clave`, `rol`, `activo`, `creado_en`, `permisos`.

Roles: `ADMIN`, `VENDEDOR`.

#### reparaciones

PK: `id`. Unique: `codigo`.

Campos: `id`, `codigo`, `cliente_nombre`, `cliente_telefono`, `marca`, `modelo`, `imei`, `falla`, `diagnostico`, `garantia`, `estado`, `precio`, `fecha_ingreso`, `fecha_entrega`, `observaciones`, `id_usuario`, `activo`, `creado_en`.

Importante: existe tabla MySQL para reparaciones, pero el modulo Python actual guarda principalmente en SQLite.

#### fiscal_comprobantes

PK: `id`.

FK:

- `id_venta` -> `ventas.id`

Campos: `id`, `id_venta`, `tipo_operacion`, `estado`, `proveedor`, `punto_venta`, `tipo_comprobante`, `numero_comprobante`, `cae`, `cae_vencimiento`, `payload_json`, `respuesta_json`, `ultimo_error`, `intentos`, `proximo_intento`, `creado_en`, `actualizado_en`.

#### fiscal_cola

PK: `id`.

FK:

- `id_comprobante` -> `fiscal_comprobantes.id`

Campos: `id`, `id_comprobante`, `estado`, `intentos`, `ultimo_error`, `proximo_intento`, `creado_en`, `actualizado_en`.

## 5. Sistema de ventas

Modulos existentes:

- Autenticacion.
- Usuarios y permisos.
- Clientes.
- Stock.
- Productos.
- Listas de precios.
- Ventas.
- Presupuestos.
- Cuentas corrientes.
- Exportaciones/reportes.
- Importacion Excel.
- Configuracion.
- Facturacion fiscal.
- Backups.
- Reparaciones embebidas.

Funcionalidades principales:

- Venta con carrito en sesion.
- Busqueda de productos.
- Aplicacion de listas de precios.
- Confirmacion transaccional de venta.
- Descuento de stock.
- Generacion de ticket/PDF.
- Presupuestos.
- Reportes CSV/PDF/HTML.
- Importacion de articulos Excel.
- Control de stock bajo.
- Configuracion visual, comercio, backup y seguridad.

Dependencias internas:

- `ControladorVentas` usa `Venta`, `Cliente`, `Producto`, `ListaPrecio`, `Presupuesto`, `FacturaFiscal`, `ConfiguracionSistema`.
- `Venta` usa tablas `productos`, `stock`, `clientes`, `usuarios`, `ventas`, `detalle_venta`.
- Exportaciones usa `dompdf` y `phpspreadsheet`.

## 6. Sistema de reparaciones

Persistencia actual:

- SQLite: `reparaciones_python/reparaciones.db`.
- JSON:
  - `reparaciones_python/comercio_config.json`
  - `almacenamiento/configuracion_sistema.json`
- TXT:
  - `reparaciones_python/tickets/REP-*.txt`
- MySQL preparado pero no usado como fuente principal:
  - tabla `reparaciones` en `instalacion_schema_base.sql`.

Archivos involucrados:

- `reparaciones_python/database.py`
- `reparaciones_python/repositorio.py`
- `reparaciones_python/modelos.py`
- `reparaciones_python/web_app.py`
- `reparaciones_python/tickets.py`
- `reparaciones_python/ui.py`
- `aplicacion/controladores/ControladorReparaciones.php`

Flujo completo:

1. Usuario abre modulo Reparaciones desde PHP.
2. `ControladorReparaciones` valida sesion y rol.
3. Lanza `CONTROL REPARACIONES.exe` o `pythonw.exe web_app.py --no-browser`.
4. PHP renderiza un iframe apuntando a `http://127.0.0.1:8765/`.
5. `web_app.py` levanta `ThreadingHTTPServer`.
6. Al iniciar llama `inicializar_base()`.
7. `database.py` crea tabla SQLite `reparaciones` si no existe.
8. JS del HTML llama endpoints:
   - `GET /api/reparaciones`
   - `POST /api/reparaciones`
   - `PUT /api/reparaciones/{id}`
   - `DELETE /api/reparaciones/{id}`
   - `GET /ticket/{id}`
9. `repositorio.py` ejecuta SQL SQLite.
10. `tickets.py` arma ticket HTML/TXT usando configuracion JSON.

Tabla SQLite:

```sql
CREATE TABLE IF NOT EXISTS reparaciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo TEXT NOT NULL UNIQUE,
    cliente_nombre TEXT NOT NULL,
    cliente_telefono TEXT DEFAULT '',
    marca TEXT DEFAULT '',
    modelo TEXT DEFAULT '',
    imei TEXT DEFAULT '',
    falla TEXT DEFAULT '',
    diagnostico TEXT DEFAULT '',
    garantia TEXT DEFAULT '',
    estado TEXT DEFAULT 'PENDIENTE',
    precio REAL DEFAULT 0,
    fecha_ingreso TEXT NOT NULL,
    fecha_entrega TEXT DEFAULT '',
    observaciones TEXT DEFAULT '',
    activo INTEGER DEFAULT 1,
    creado_en TEXT DEFAULT CURRENT_TIMESTAMP
)
```

## 7. Seguridad

Login:

- `ControladorAuth::login()`.
- Usuarios en tabla `usuarios`.
- Valida `usuario`, `clave`, `activo`.
- Usa `password_verify()`.

Sesiones:

- `configuraciones/seguridad.php`.
- Guarda `$_SESSION["usuario_logueado"]`.
- Permite modo `sin_login` por configuracion.
- Si no hay usuarios creados, habilita usuario local `Sin login` como `ADMIN`.

Roles:

- `ADMIN`
- `VENDEDOR`

Permisos:

- Columna `usuarios.permisos`.
- Formato JSON.
- Validacion por modulo en `usuario_puede_modulo()`.

CSRF:

- `configuraciones/csrf.php`.
- Token en sesion con `random_bytes(16)`.
- Validacion con `hash_equals()`.
- Presente en formularios PHP importantes.

Hash de contrasenas:

- Creacion: `password_hash($clave, PASSWORD_DEFAULT)`.
- Login: `password_verify()`.

Riesgos:

- Modo `sin_login` puede dejar acceso administrativo.
- Usuario automatico `Sin login` con rol `ADMIN`.
- Reparaciones Python no tiene autenticacion propia; confia en localhost/iframe.
- Algunos deletes usan GET.
- Credenciales MySQL root sin clave.
- Migraciones dinamicas en runtime mezcladas con logica de modelo.

## 8. Puntos criticos

Codigo duplicado o acoplado:

- `ControladorConfiguracion.php` y `ControladorConfiguraciones.php` se solapan.
- `Configuracion`, `ConfiguracionSistema`, JSON legacy y tabla `configuraciones` conviven.
- `Stock` y `ServicioPrecios` duplican calculo/normalizacion de costos.
- Reparaciones tiene esquema SQLite y esquema MySQL paralelo.

Problemas de arquitectura:

- Front controller manual con mapa estatico.
- Controladores grandes, especialmente `ControladorVentas` y `ControladorExportaciones`.
- Modelos estaticos mezclan SQL, reglas, validacion y migracion.
- Vistas PHP tienen logica de presentacion y JS inline.
- No hay capa de aplicacion/casos de uso.
- No hay repositorios por interfaz.
- Reparaciones corre como aplicacion separada Python con servidor propio.

Problemas de seguridad:

- `auth_modo=sin_login`.
- Usuario automatico `Sin login` con rol `ADMIN`.
- MySQL root sin contrasena.
- Reparaciones expone API HTTP local sin token.
- Sin rate limit/login lockout real.
- Algunos modulos aceptan rutas/acciones por GET.
- Backups y rutas locales requieren revision estricta de permisos.

Dependencias innecesarias o revisables:

- `thecodingmachine/safe` entra como dependencia transitiva del parser CSS, pero no parece usarse directamente.
- `dompdf` y `phpspreadsheet` si tienen uso funcional.
- Builds, `.exe`, runtime Python y backups no deberian vivir mezclados con el codigo fuente principal.

## 9. Plan de migracion

### Fase 1: Arquitectura Hexagonal

Objetivo: separar dominio, casos de uso e infraestructura sin cambiar la UI al principio.

1. Definir dominios:
   - Ventas.
   - Productos.
   - Stock.
   - Clientes.
   - Cuentas corrientes.
   - Reparaciones.
   - Configuracion.
2. Crear puertos:
   - `VentaRepository`
   - `ProductoRepository`
   - `StockRepository`
   - `ReparacionRepository`
   - `ConfiguracionRepository`
3. Extraer casos de uso:
   - `ConfirmarVenta`
   - `AgregarProductoAlCarrito`
   - `ActualizarPrecioProducto`
   - `RegistrarReparacion`
   - `EntregarReparacion`
4. Dejar controladores actuales como adaptadores HTTP temporales.
5. Mover SQL a repositorios concretos.
6. Eliminar migraciones dinamicas desde modelos y convertirlas en scripts versionados.

### Fase 2: Migrar reparaciones a base de datos

Objetivo: dejar SQLite y centralizar en MySQL.

1. Congelar escritura en SQLite.
2. Crear migracion MySQL definitiva para `reparaciones`.
3. Mapear campos SQLite -> MySQL.
4. Importar `reparaciones.db`.
5. Adaptar Python o reemplazar su repositorio por API PHP/MySQL.
6. Agregar `id_cliente` opcional para vincular clientes.
7. Agregar historial de estados si se necesita trazabilidad.

### Fase 3: Unificar ventas y reparaciones

Objetivo: modelo unico de negocio.

1. Vincular reparacion con cliente existente.
2. Permitir venta/facturacion desde reparacion.
3. Crear tabla puente si corresponde:
   - `reparacion_ventas`
   - o campo `id_venta` en `reparaciones`.
4. Unificar tickets y configuracion.
5. Unificar backups.
6. Reemplazar servidor Python por modulo web interno o API unica.

### Fase 4: Migrar a Laravel

Objetivo: framework mantenible con rutas, migraciones y seguridad estandar.

1. Crear proyecto Laravel.
2. Migrar tablas con migrations.
3. Crear modelos Eloquent:
   - `User`
   - `Cliente`
   - `Producto`
   - `Stock`
   - `Venta`
   - `DetalleVenta`
   - `Reparacion`
4. Crear Services/Application Actions.
5. Crear Form Requests para validacion.
6. Crear Policies/Gates para permisos.
7. Migrar vistas a Blade o frontend separado.
8. Reemplazar sesiones/carrito manual por servicios Laravel.
9. Migrar generacion de PDFs y exportaciones.
10. Agregar tests de casos criticos.

## 10. Archivos mas importantes

### `publico/index.php`

```php
<?php

require_once __DIR__ . "/../configuraciones/ayudas.php";
require_once __DIR__ . "/../configuraciones/base_datos.php";
require_once __DIR__ . "/../configuraciones/seguridad.php";
require_once __DIR__ . "/../configuraciones/csrf.php";

$c=obtener_get("c","auth");
$a=obtener_get("a","login");

$mapa=[
  "auth" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorAuth.php", "clase" => "ControladorAuth"],
  "usuarios" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorUsuarios.php", "clase" => "ControladorUsuarios"],
  "clientes" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorClientes.php", "clase" => "ControladorClientes"],
  "stock" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorStock.php", "clase" => "ControladorStock"],
  "productos" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorProductos.php", "clase" => "ControladorProductos"],
  "importacion" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorImportacion.php", "clase" => "ControladorImportacion"],
  "listas_precios" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorListasPrecios.php", "clase" => "ControladorListasPrecios"],
  "exportaciones" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorExportaciones.php", "clase" => "ControladorExportaciones"],
  "cuentas_corrientes" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorCuentasCorrientes.php", "clase" => "ControladorCuentasCorrientes"],
  "ventas" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorVentas.php", "clase" => "ControladorVentas"],
  "reparaciones" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorReparaciones.php", "clase" => "ControladorReparaciones"],
  "configuracion" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorConfiguracion.php", "clase" => "ControladorConfiguracion"],
  "configuraciones" => ["archivo" => __DIR__ . "/../aplicacion/controladores/ControladorConfiguraciones.php", "clase" => "ControladorConfiguraciones"],
];

if(isset($mapa[$c])){
    $archivo=$mapa[$c]["archivo"];
    $clase=$mapa[$c]["clase"];
}

if($archivo!=="" && file_exists($archivo)){
    require_once $archivo;
    $ctrl=new $clase();
    if(method_exists($ctrl,$a))
        $ctrl->$a();
    else
        echo "Accion no encontrada";
}else{
    echo "Controlador no encontrado";
}
```

### Configuracion de base de datos

Archivo: `configuraciones/base_datos.php`

```php
<?php

require_once __DIR__."/ayudas.php";

function obtener_pdo(): ?PDO{
    static $pdo=null;
    if($pdo===null){
        $host="127.0.0.1";
        $bd="sistema_ventas";
        $usuario="root";
        $clave="";
        $dsn="mysql:host=$host;dbname=$bd;charset=utf8mb4";
        try{
            $pdo=new PDO($dsn,$usuario,$clave,[
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 2
            ]);
        }catch(Throwable $e){
            $pdo=null;
            registrar_log("BD",$e->getMessage());
        }
    }
    return $pdo;
}
```

### Controladores principales

- `aplicacion/controladores/ControladorVentas.php`: ventas, carrito, tickets, PDF, presupuestos.
- `aplicacion/controladores/ControladorProductos.php`: CRUD productos, exportacion, precios.
- `aplicacion/controladores/ControladorStock.php`: stock, alertas, faltantes.
- `aplicacion/controladores/ControladorClientes.php`: CRUD clientes.
- `aplicacion/controladores/ControladorReparaciones.php`: puente PHP -> servidor Python local.

### Modelos principales

- `aplicacion/modelos/Venta.php`: confirma ventas con transaccion y descuenta stock.
- `aplicacion/modelos/Producto.php`: productos y precios.
- `aplicacion/modelos/Stock.php`: inventario, costos, alertas.
- `aplicacion/modelos/Cliente.php`: clientes y datos fiscales.
- `aplicacion/modelos/Usuario.php`: usuarios, permisos y hash.
- `aplicacion/modelos/ConfiguracionSistema.php`: configuracion DB/JSON.

### Archivos del modulo reparaciones

- `reparaciones_python/database.py`: conexion SQLite y creacion de tabla.
- `reparaciones_python/repositorio.py`: CRUD SQLite.
- `reparaciones_python/modelos.py`: validaciones y estados.
- `reparaciones_python/web_app.py`: servidor HTTP local y UI HTML/JS.
- `reparaciones_python/tickets.py`: generacion de tickets.
- `reparaciones_python/ui.py`: interfaz Tkinter alternativa.
- `reparaciones_python/reparaciones.db`: base SQLite real.

## Nota para la IA que continue el analisis

Antes de planificar la migracion definitiva, leer completos estos archivos:

- `publico/index.php`
- `configuraciones/base_datos.php`
- `configuraciones/seguridad.php`
- `configuraciones/csrf.php`
- `configuraciones/ayudas.php`
- `aplicacion/controladores/ControladorVentas.php`
- `aplicacion/controladores/ControladorProductos.php`
- `aplicacion/controladores/ControladorStock.php`
- `aplicacion/controladores/ControladorReparaciones.php`
- `aplicacion/modelos/Venta.php`
- `aplicacion/modelos/Producto.php`
- `aplicacion/modelos/Stock.php`
- `aplicacion/modelos/Usuario.php`
- `aplicacion/modelos/ConfiguracionSistema.php`
- `reparaciones_python/database.py`
- `reparaciones_python/repositorio.py`
- `reparaciones_python/web_app.py`
- `instalacion_schema_base.sql`

