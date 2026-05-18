# Arquitectura del Sistema

## Visión General

El Sistema de Ventas es una aplicación web PHP que implementa el patrón de diseño Modelo-Vista-Controlador (MVC) para separar la lógica de negocio, la presentación y el control de flujo.

## Arquitectura MVC

### Modelo (Model)
- Ubicación: `aplicacion/modelos/`
- Responsabilidades:
  - Acceso a datos
  - Lógica de negocio
  - Validaciones
- Clases principales:
  - `Usuario.php`: Gestión de usuarios
  - `Cliente.php`: Gestión de clientes
  - `Producto.php`: Gestión de productos
  - `Stock.php`: Gestión de inventario
  - `Venta.php`: Gestión de ventas

### Vista (View)
- Ubicación: `aplicacion/vistas/`
- Responsabilidades:
  - Presentación de datos
  - Interfaz de usuario
- Estructura:
  - `parciales/`: Componentes reutilizables (header, menu, footer)
  - Módulos específicos: `auth/`, `usuarios/`, `clientes/`, etc.

### Controlador (Controller)
- Ubicación: `aplicacion/controladores/`
- Responsabilidades:
  - Procesar peticiones HTTP
  - Coordinar Modelo y Vista
  - Manejar lógica de aplicación
- Controladores:
  - `ControladorAuth.php`: Autenticación
  - `ControladorUsuarios.php`: CRUD usuarios
  - `ControladorClientes.php`: CRUD clientes
  - `ControladorProductos.php`: CRUD productos
  - `ControladorStock.php`: Gestión stock
  - `ControladorVentas.php`: Gestión ventas

## Arquitectura General

```
Cliente HTTP
    ↓
publico/index.php (Front Controller)
    ↓
Routing (c=controller&a=action)
    ↓
Controlador específico
    ↙        ↘
Modelo     Vista
    ↖        ↗
Base de Datos    HTML/CSS/JS
```

## Capas de Configuración

- `configuraciones/base_datos.php`: Conexión PDO a MySQL
- `configuraciones/seguridad.php`: Funciones de seguridad y sesiones
- `configuraciones/csrf.php`: Protección CSRF
- `configuraciones/ayudas.php`: Funciones auxiliares

## Flujo de una Petición

1. Usuario accede a `index.php?c=ventas&a=lista`
2. `index.php` carga configuraciones y mapea controlador
3. Instancia `ControladorVentas` y ejecuta método `lista()`
4. Controlador obtiene datos del modelo `Venta`
5. Controlador incluye vista `ventas/lista.php`
6. Vista renderiza HTML con datos

## Seguridad

- Autenticación basada en sesiones
- Protección CSRF en formularios
- Validación de entrada
- Hashing de contraseñas con `password_verify()`
- Sanitización de salida con `htmlspecialchars()`

## Dependencias Externas

- **DomPDF**: Generación de PDFs para reportes
- **Composer**: Gestión de dependencias PHP

## Escalabilidad

- Separación clara de responsabilidades
- Código modular y reutilizable
- Fácil extensión de módulos
- Posibilidad de migrar a framework (Laravel, Symfony)