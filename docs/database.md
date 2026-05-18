# Esquema de Base de Datos

## Visión General

La base de datos utiliza MySQL con codificación UTF8MB4. El esquema consta de las siguientes tablas principales:

## Tablas

### usuarios
Almacena información de usuarios del sistema.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT AUTO_INCREMENT PRIMARY KEY | ID único del usuario |
| usuario | VARCHAR(50) UNIQUE | Nombre de usuario |
| clave | VARCHAR(255) | Contraseña hasheada |
| rol | ENUM('admin','usuario') | Rol del usuario |
| activo | TINYINT(1) DEFAULT 1 | Estado activo/inactivo |
| creado_en | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Fecha de creación |

### clientes
Información de clientes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT AUTO_INCREMENT PRIMARY KEY | ID único del cliente |
| nombre | VARCHAR(100) | Nombre del cliente |
| email | VARCHAR(100) | Correo electrónico |
| telefono | VARCHAR(20) | Número de teléfono |
| direccion | TEXT | Dirección |
| activo | TINYINT(1) DEFAULT 1 | Estado activo/inactivo |
| creado_en | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Fecha de creación |

### stock
Control de inventario.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT AUTO_INCREMENT PRIMARY KEY | ID único del stock |
| nombre | VARCHAR(100) | Nombre del ítem |
| unidad | VARCHAR(20) | Unidad de medida (kg, unidades, etc.) |
| cantidad | DECIMAL(10,2) | Cantidad disponible |
| precio_costo | DECIMAL(10,2) | Precio de costo |
| activo | TINYINT(1) DEFAULT 1 | Estado activo/inactivo |
| creado_en | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Fecha de creación |

### productos
Productos para venta.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT AUTO_INCREMENT PRIMARY KEY | ID único del producto |
| nombre | VARCHAR(100) | Nombre del producto |
| cod_barras | VARCHAR(50) UNIQUE | Código de barras |
| id_stock | INT | FK a stock.id |
| factor_conversion | DECIMAL(10,2) | Factor de conversión |
| ganancia | DECIMAL(5,2) | Porcentaje de ganancia |
| precio_final | DECIMAL(10,2) | Precio de venta |
| activo | TINYINT(1) DEFAULT 1 | Estado activo/inactivo |
| creado_en | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Fecha de creación |

**Foreign Key**: `id_stock` REFERENCES `stock(id)`

### ventas
Registro de ventas realizadas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT AUTO_INCREMENT PRIMARY KEY | ID único de la venta |
| fecha | DATETIME | Fecha y hora de la venta |
| total | DECIMAL(10,2) | Total de la venta |
| id_cliente | INT | FK a clientes.id |
| id_usuario | INT | FK a usuarios.id |
| creado_en | TIMESTAMP DEFAULT CURRENT_TIMESTAMP | Fecha de creación |

**Foreign Keys**:
- `id_cliente` REFERENCES `clientes(id)`
- `id_usuario` REFERENCES `usuarios(id)`

### detalle_venta
Detalle de productos en cada venta.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT AUTO_INCREMENT PRIMARY KEY | ID único del detalle |
| id_venta | INT | FK a ventas.id |
| id_producto | INT | FK a productos.id |
| cantidad | DECIMAL(10,2) | Cantidad vendida |
| precio_unit | DECIMAL(10,2) | Precio unitario |
| descuento | DECIMAL(10,2) DEFAULT 0 | Descuento aplicado |
| subtotal | DECIMAL(10,2) | Subtotal del ítem |

**Foreign Keys**:
- `id_venta` REFERENCES `ventas(id)`
- `id_producto` REFERENCES `productos(id)`

## Relaciones

```
usuarios (1) ────┐
                 │
                 ├───► ventas ◄─── clientes (1)
                 │
                 └───► detalle_venta ◄─── productos ◄─── stock (1)
```

## Script de Creación

```sql
-- Crear base de datos
CREATE DATABASE sistema_ventas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usar base de datos
USE sistema_ventas;

-- Tabla usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    clave VARCHAR(255) NOT NULL,
    rol ENUM('admin','usuario') DEFAULT 'usuario',
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20),
    direccion TEXT,
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla stock
CREATE TABLE stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    unidad VARCHAR(20) NOT NULL,
    cantidad DECIMAL(10,2) DEFAULT 0,
    precio_costo DECIMAL(10,2) DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    cod_barras VARCHAR(50) UNIQUE,
    id_stock INT,
    factor_conversion DECIMAL(10,2) DEFAULT 1,
    ganancia DECIMAL(5,2) DEFAULT 0,
    precio_final DECIMAL(10,2) DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_stock) REFERENCES stock(id)
);

-- Tabla ventas
CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    id_cliente INT,
    id_usuario INT NOT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

-- Tabla detalle_venta
CREATE TABLE detalle_venta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    precio_unit DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (id_venta) REFERENCES ventas(id),
    FOREIGN KEY (id_producto) REFERENCES productos(id)
);

-- Usuario admin por defecto
INSERT INTO usuarios (usuario, clave, rol) VALUES ('admin', '$2y$10$example.hash.here', 'admin');
```

## Notas

- Todos los campos `activo` permiten desactivar registros sin eliminarlos
- Los timestamps se generan automáticamente
- Las claves foráneas mantienen integridad referencial
- Los tipos DECIMAL aseguran precisión en cálculos monetarios