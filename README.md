<<<<<<< HEAD
# Sistema de Ventas

## Descripción

Este es un sistema de gestión de ventas desarrollado en PHP utilizando una arquitectura MVC (Modelo-Vista-Controlador). El sistema permite gestionar usuarios, clientes, productos, stock y ventas. Incluye funcionalidades de autenticación, generación de PDFs y una interfaz web responsiva.

## Características Principales

- **Gestión de Usuarios**: Registro, login, roles (admin, usuario).
- **Gestión de Clientes**: CRUD de clientes.
- **Gestión de Productos**: CRUD de productos con códigos de barras.
- **Gestión de Stock**: Control de inventario por unidades.
- **Gestión de Ventas**: Registro de ventas y generación de PDFs.
- **Seguridad**: Protección CSRF, sesiones seguras.
- **Interfaz**: Bootstrap para diseño responsivo.

## Tecnologías Utilizadas

- **Lenguaje**: PHP 7.4+
- **Base de Datos**: MySQL
- **Framework**: Ninguno (MVC personalizado)
- **Librerías**:
  - DomPDF para generación de PDFs
  - Composer para gestión de dependencias
- **Frontend**: HTML, CSS (Bootstrap), JavaScript

## Estructura del Proyecto

```
VENTAS/
├── aplicacion/
│   ├── controladores/     # Controladores MVC
│   ├── modelos/          # Modelos de datos
│   └── vistas/           # Vistas HTML/PHP
├── configuraciones/      # Configuraciones del sistema
├── publico/              # Punto de entrada público
├── almacenamiento/        # Archivos generados (logs, PDFs)
├── vendor/               # Dependencias de Composer
└── docs/                 # Documentación técnica
```

## Instalación

1. **Requisitos Previos**:
   - PHP 7.4 o superior
   - MySQL 5.7+
   - Composer
   - Servidor web (Apache/Nginx) o XAMPP

2. **Clonación del Repositorio**:
   ```bash
   git clone <url-del-repositorio>
   cd VENTAS
   ```

3. **Instalación de Dependencias**:
   ```bash
   composer install
   ```

4. **Configuración de la Base de Datos**:
   - Crear base de datos `sistema_ventas` en MySQL
   - Ejecutar el script SQL para crear tablas (ver docs/database.md)
   - Configurar credenciales en `configuraciones/base_datos.php`

5. **Configuración del Servidor**:
   - Apuntar el document root a `publico/`
   - Asegurar permisos de escritura en `almacenamiento/`

## Uso

- Acceder a `http://localhost/index.php`
- Login con usuario admin
- Navegar por los módulos: Usuarios, Clientes, Productos, Stock, Ventas

## Documentación Técnica

- **[Arquitectura](docs/architecture.md)**: Detalles del patrón MVC y estructura del sistema
- **[Base de Datos](docs/database.md)**: Esquema completo y relaciones
- **[Despliegue](docs/deployment.md)**: Guía para instalar en producción
- **[Manual de Usuario](docs/user_manual.md)**: Guía para usuarios finales
- **[Guía de Desarrollo](docs/development.md)**: Información para desarrolladores

## Contribución

1. Fork el proyecto
2. Crear rama feature
3. Commit cambios
4. Push y crear PR

## Licencia

[Especificar licencia]

## Contacto

[Información de contacto]
# SISTEMA-DE-VENTAS-PHP

