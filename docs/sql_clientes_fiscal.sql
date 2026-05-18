-- Datos fiscales de clientes para emitir comprobantes ARCA/AFIP.
-- Ejecutar una vez si no se usa la migracion automatica del modelo Cliente.

ALTER TABLE clientes
    ADD COLUMN tipo_documento VARCHAR(20) NOT NULL DEFAULT 'DNI' AFTER dni,
    ADD COLUMN condicion_iva VARCHAR(40) NOT NULL DEFAULT 'Consumidor Final' AFTER tipo_documento,
    ADD COLUMN email VARCHAR(120) NULL AFTER condicion_iva;
