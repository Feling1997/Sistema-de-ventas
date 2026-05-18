CREATE TABLE IF NOT EXISTS presupuestos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_cliente INT NOT NULL,
    id_usuario INT NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado VARCHAR(20) NOT NULL DEFAULT 'ABIERTO',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_presupuestos_cliente (id_cliente),
    KEY idx_presupuestos_fecha (fecha)
);

CREATE TABLE IF NOT EXISTS detalle_presupuesto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_presupuesto INT NOT NULL,
    id_producto INT NOT NULL,
    producto_nombre VARCHAR(150) NOT NULL,
    cantidad DECIMAL(10,3) NOT NULL,
    precio_unit DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,
    KEY idx_detalle_presupuesto (id_presupuesto)
);
