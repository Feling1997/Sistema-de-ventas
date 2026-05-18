-- Tablas para facturacion fiscal ARCA/AFIP.
-- Ejecutar una vez sobre la base `sistema_ventas`.

CREATE TABLE IF NOT EXISTS fiscal_comprobantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    tipo_operacion ENUM('factura','presupuesto') NOT NULL DEFAULT 'factura',
    estado ENUM('PENDIENTE','EN_PROCESO','APROBADO','RECHAZADO','ERROR') NOT NULL DEFAULT 'PENDIENTE',
    proveedor VARCHAR(30) NOT NULL DEFAULT 'api_rest',
    punto_venta INT NULL,
    tipo_comprobante INT NULL,
    numero_comprobante BIGINT NULL,
    cae VARCHAR(30) NULL,
    cae_vencimiento DATE NULL,
    payload_json LONGTEXT NOT NULL,
    respuesta_json LONGTEXT NULL,
    ultimo_error TEXT NULL,
    intentos INT NOT NULL DEFAULT 0,
    proximo_intento DATETIME NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fiscal_comprobantes_venta (id_venta),
    KEY idx_fiscal_estado (estado),
    CONSTRAINT fk_fiscal_comprobantes_venta FOREIGN KEY (id_venta) REFERENCES ventas(id)
);

CREATE TABLE IF NOT EXISTS fiscal_cola (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_comprobante INT NOT NULL,
    estado ENUM('PENDIENTE','EN_PROCESO','FINALIZADO','ERROR') NOT NULL DEFAULT 'PENDIENTE',
    intentos INT NOT NULL DEFAULT 0,
    ultimo_error TEXT NULL,
    proximo_intento DATETIME NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_fiscal_cola_estado (estado, proximo_intento),
    CONSTRAINT fk_fiscal_cola_comprobante FOREIGN KEY (id_comprobante) REFERENCES fiscal_comprobantes(id)
);
