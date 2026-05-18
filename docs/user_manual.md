# Manual de Usuario

> Nota: la ficha tecnica operativa actualizada esta en `docs/ficha_tecnica.md`. Cada modificacion funcional nueva debe documentarse ahi.

## Introducción

El Sistema de Ventas es una aplicación web para gestionar ventas, inventario y clientes de manera eficiente.

## Inicio de Sesión

1. Acceder a la URL del sistema
2. Ingresar usuario y contraseña
3. Hacer clic en "Iniciar Sesión"

### Roles de Usuario
- **Admin**: Acceso completo a todas las funciones
- **Usuario**: Acceso limitado según permisos

## Módulos del Sistema

### Gestión de Usuarios
- **Ver Usuarios**: Lista todos los usuarios activos
- **Crear Usuario**: Agregar nuevo usuario con rol asignado
- **Editar Usuario**: Modificar información de usuario existente
- **Desactivar Usuario**: Desactivar cuenta sin eliminar

### Gestión de Clientes
- **Ver Clientes**: Lista de clientes registrados
- **Crear Cliente**: Agregar nuevo cliente con información de contacto
- **Editar Cliente**: Actualizar datos del cliente
- **Desactivar Cliente**: Desactivar cliente

### Gestión de Stock
- **Ver Stock**: Inventario disponible
- **Crear Ítem**: Agregar nuevo producto al inventario
- **Editar Ítem**: Modificar cantidad, precio, etc.
- **Ver Productos**: Productos asociados a un ítem de stock

### Gestión de Productos
- **Ver Productos**: Lista de productos para venta
- **Crear Producto**: Agregar producto con código de barras
- **Editar Producto**: Modificar precio, ganancia, etc.
- **Desactivar Producto**: Quitar de catálogo

### Gestión de Ventas
- **Nueva Venta**: Crear venta seleccionando cliente y productos
- **Ver Ventas**: Historial de ventas realizadas
- **Ver Detalle**: Detalle de productos en una venta
- **Generar PDF**: Imprimir recibo de venta

## Flujo de Trabajo Típico

### Configuración Inicial
1. Crear usuarios del sistema
2. Registrar clientes
3. Configurar stock inicial
4. Crear productos

### Proceso de Venta
1. Seleccionar cliente
2. Agregar productos al carrito
3. Aplicar descuentos si corresponde
4. Confirmar venta
5. Generar e imprimir recibo

## Funcionalidades Avanzadas

### Códigos de Barras
- Cada producto tiene un código único
- Validación de unicidad
- Búsqueda por código

### Cálculo Automático
- Precio final = (precio costo × factor) × (1 + ganancia/100)
- Subtotal = cantidad × precio unitario - descuento
- Total = suma de subtotales

### Reportes
- Historial de ventas por fecha
- Ventas por cliente
- Inventario disponible

## Seguridad

### Protección de Datos
- Contraseñas encriptadas
- Validación de formularios
- Protección CSRF
- Sanitización de entrada/salida

### Sesiones
- Tiempo límite de inactividad
- Cierre automático de sesión
- Prevención de acceso no autorizado

## Solución de Problemas

### Problemas Comunes

#### No puedo iniciar sesión
- Verificar usuario y contraseña
- Confirmar que la cuenta esté activa
- Intentar restablecer contraseña

#### Error al guardar
- Verificar conexión a base de datos
- Comprobar permisos de usuario
- Revisar campos obligatorios

#### PDF no se genera
- Verificar permisos de escritura en `almacenamiento/pdf/`
- Comprobar instalación de DomPDF
- Revisar logs de error

### Soporte Técnico
Para soporte técnico, contactar al administrador del sistema con:
- Descripción del problema
- Pasos para reproducirlo
- Capturas de pantalla si aplica
- Información del navegador y sistema operativo

## Atajos de Teclado

- `Ctrl + S`: Guardar formulario
- `F5`: Recargar página
- `Esc`: Cancelar operación

## Glosario

- **Stock**: Inventario de materia prima o productos
- **Producto**: Artículo para venta con precio definido
- **Factor de Conversión**: Relación entre stock y producto
- **Ganancia**: Porcentaje de margen sobre el costo
- **Subtotal**: Total por ítem antes de descuentos
- **Total**: Monto final de la venta
