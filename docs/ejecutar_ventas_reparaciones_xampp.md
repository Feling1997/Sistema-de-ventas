# Ejecutar Ventas y Reparaciones en XAMPP

Ubicacion del sistema:

```text
C:\xampp82\htdocs\VENTAS
```

## Ventas

1. Abrir XAMPP.
2. Iniciar Apache y MySQL.
3. Entrar en el navegador:

```text
http://localhost/VENTAS/publico/index.php
```

En `Nueva venta`, el selector de comprobante incluye:

- `Factura A` y `Factura B`: comprobantes fiscales, generan cola AFIP/ARCA.
- `Factura X`: comprobante interno, descuenta stock y no se envia a AFIP.
- `Presupuesto`: no descuenta stock.

La imagen de cabecera del ticket se carga desde `Config`.

## Reparaciones

Reparaciones queda separado como aplicacion Python en:

```text
C:\xampp82\htdocs\VENTAS\reparaciones_python
```

Desde Ventas, usar el boton fijo `Reparaciones` de la barra superior. El boton ejecuta:

```text
http://localhost/VENTAS/publico/index.php?c=reparaciones&a=index
```

Ese puente PHP inicia el servidor Python local y lo muestra dentro del mismo sistema. Internamente usa:

```text
http://127.0.0.1:8765/
```

Tambien se puede iniciar manualmente con doble click en:

```text
C:\xampp82\htdocs\VENTAS\reparaciones_python\iniciar_web_reparaciones.bat
```

Ventas y Reparaciones siguen separados:

- Ventas: PHP + MySQL.
- Reparaciones: Python + SQLite local `reparaciones.db`.
- Unidos por la barra superior fija `Ventas / Reparaciones`, boton y tecla rapida configurables.
