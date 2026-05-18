-- Permite vender productos sin asociarlos a stock.
-- Ejecutar solo si tu tabla productos tiene id_stock como NOT NULL.

ALTER TABLE productos MODIFY id_stock INT NULL;
