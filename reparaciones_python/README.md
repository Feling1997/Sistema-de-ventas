# Sistema local de reparaciones en Python

Programa independiente para gestionar reparaciones, basado en el modulo de reparaciones del sistema PHP.

## Requisitos

- No requiere instalar Python si se usa el paquete completo con la carpeta `python_runtime`.
- Si el paquete no trae `python_runtime`, entonces necesita Python 3.10 o superior.
- No necesita instalar paquetes externos.
- Usa SQLite local en el archivo `reparaciones.db`.
- Funciona sin internet despues de tener Python instalado.
- La version web abre `http://127.0.0.1:8765`, que es una direccion local de la misma PC.

## Instalar en otra PC

Forma recomendada:

1. Enviar `Instalador_Reparaciones.exe`.
2. Abrirlo con doble click en la otra PC.
3. Esperar que termine la instalacion.
4. Usar el acceso directo `Reparaciones` que queda en el Escritorio.

El instalador completo incluye Python en `python_runtime`, asi que no hace falta instalar Python aparte.

Forma manual:

1. Copiar la carpeta completa `reparaciones_python` a la otra PC.
2. Abrir `INSTALAR_REPARACIONES.bat` con doble click.
3. Usar el acceso directo `Reparaciones`.

El instalador no modifica la base de datos. Los datos quedan guardados en `reparaciones.db`, dentro de esta misma carpeta.

## Uso sin internet

El sistema no necesita conexion a internet para cargar, guardar, buscar, editar, eliminar ni generar tickets.

Si el paquete incluye `python_runtime`, tambien funciona en una PC sin Python instalado y sin internet.

## Ejecutar

Version web local recomendada:

```powershell
python web_app.py
```

Tambien podes abrir `iniciar_web_reparaciones.bat` con doble click. Esta version abre el sistema en el navegador y permite una interfaz mas moderna que Tkinter.

Version Tkinter:

Desde esta carpeta:

```powershell
python app.py
```

Tambien podes abrir `iniciar_reparaciones.bat` con doble click.

## Funciones incluidas

- Alta de reparaciones.
- Edicion de reparaciones.
- Eliminacion logica.
- Listado local.
- Menu lateral con accesos rapidos.
- Buscador por codigo, cliente, telefono, marca, modelo, falla o garantia.
- Filtros por estado para ver pendientes y entregados.
- Tarjetas de resumen con cantidades.
- Panel de detalle de la reparacion seleccionada.
- Estados: `PENDIENTE`, `ENTREGADO`.
- Ticket en texto dentro de la carpeta `tickets`.
- Configuracion opcional del comercio en `comercio_config.json`.

## Requisito de programacion

El codigo evita `exit`, `quit` y cortes abruptos. Las funciones que devuelven valores usan un solo `return` al final de la funcion.
