-- Script para crear la tabla de reparaciones
-- Ejecutar en MySQL/MariaDB

CREATE TABLE IF NOT EXISTS reparaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    cliente_nombre VARCHAR(150) NOT NULL,
    cliente_telefono VARCHAR(30) DEFAULT '',
    marca VARCHAR(50) DEFAULT '',
    modelo VARCHAR(50) DEFAULT '',
    imei VARCHAR(30) DEFAULT '',
    falla TEXT,
    diagnostico TEXT,
    estado ENUM('PENDIENTE', 'EN_REPARACION', 'ESP_REPUESTOS', 'REPARADO', 'ENTREGADO', 'CANCELADO') DEFAULT 'PENDIENTE',
    precio DECIMAL(10,2) DEFAULT 0,
    fecha_ingreso DATE NOT NULL,
    fecha_entrega DATE DEFAULT NULL,
    observaciones TEXT,
    id_usuario INT DEFAULT 0,
    activo TINYINT(1) DEFAULT 1,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_codigo (codigo),
    INDEX idx_fecha_ingreso (fecha_ingreso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;