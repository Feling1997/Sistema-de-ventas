<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/Stock.php";
require_once __DIR__ . "/ListaPrecio.php";

class Producto {
    private static bool $tablas_base_aseguradas = false;

    private static function error_tabla_rota(string $mensaje): bool {
        $mensaje = strtolower($mensaje);
        return str_contains($mensaje, "doesn't exist in engine")
            || str_contains($mensaje, "does not exist in engine")
            || str_contains($mensaje, "table") && str_contains($mensaje, "doesn't exist")
            || str_contains($mensaje, "1932")
            || str_contains($mensaje, "marked as crashed")
            || str_contains($mensaje, "aria recovery failed");
    }

    private static function descartar_tabla_si_rota(PDO $pdo, string $tabla): void {
        try {
            $pdo->query("SELECT 1 FROM `$tabla` LIMIT 1");
        } catch (Throwable $e) {
            if (self::error_tabla_rota($e->getMessage())) {
                registrar_operacion("producto.tabla_rota.recrear", [
                    "tabla" => $tabla,
                    "error" => $e->getMessage(),
                ]);
                try {
                    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
                    $pdo->exec("DROP TABLE IF EXISTS `$tabla`");
                    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
                } catch (Throwable $drop) {
                    registrar_log("Producto::descartar_tabla_si_rota", $tabla . " " . $drop->getMessage());
                    try { $pdo->exec("SET FOREIGN_KEY_CHECKS=1"); } catch (Throwable $ignore) {}
                }
            }
        }
    }

    public static function asegurar_tablas_base(): void {
        if (self::$tablas_base_aseguradas)
            return;
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        try {
            self::descartar_tabla_si_rota($pdo, "producto_precios");
            self::descartar_tabla_si_rota($pdo, "productos");
            self::descartar_tabla_si_rota($pdo, "stock");
            self::descartar_tabla_si_rota($pdo, "listas_precios");
            $pdo->exec("CREATE TABLE IF NOT EXISTS stock (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(120) NOT NULL,
                unidad VARCHAR(20) NOT NULL DEFAULT 'u',
                tipo_stock VARCHAR(20) NOT NULL DEFAULT 'general',
                cantidad DECIMAL(12,3) NOT NULL DEFAULT 0,
                stock_minimo DECIMAL(14,3) NOT NULL DEFAULT 0,
                stock_maximo DECIMAL(14,3) NOT NULL DEFAULT 0,
                precio_costo DECIMAL(12,2) NOT NULL DEFAULT 0,
                moneda_costo VARCHAR(3) NOT NULL DEFAULT 'ARS',
                costo_origen DECIMAL(14,4) NOT NULL DEFAULT 0,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_stock_alerta_menu (activo, tipo_stock, cantidad, stock_minimo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS productos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(120) NOT NULL,
                cod_barras VARCHAR(80) NOT NULL,
                id_stock INT NULL,
                id_asociado INT NULL,
                factor_conversion DECIMAL(12,4) NOT NULL DEFAULT 1,
                ganancia DECIMAL(12,2) NOT NULL DEFAULT 0,
                precio_final DECIMAL(12,2) NOT NULL DEFAULT 0,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_cod_barras (cod_barras),
                KEY idx_productos_activo_nombre (activo, nombre),
                KEY idx_productos_activo_id (activo, id),
                KEY idx_productos_stock_activo (id_stock, activo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            asegurar_columna_bd($pdo, "productos", "id_stock", "ALTER TABLE productos ADD COLUMN id_stock INT NULL AFTER cod_barras");
            asegurar_columna_bd($pdo, "productos", "id_asociado", "ALTER TABLE productos ADD COLUMN id_asociado INT NULL AFTER id_stock");
            asegurar_columna_bd($pdo, "productos", "factor_conversion", "ALTER TABLE productos ADD COLUMN factor_conversion DECIMAL(12,4) NOT NULL DEFAULT 1 AFTER id_asociado");
            asegurar_columna_bd($pdo, "productos", "ganancia", "ALTER TABLE productos ADD COLUMN ganancia DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER factor_conversion");
            asegurar_columna_bd($pdo, "productos", "precio_final", "ALTER TABLE productos ADD COLUMN precio_final DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER ganancia");
            asegurar_columna_bd($pdo, "productos", "activo", "ALTER TABLE productos ADD COLUMN activo TINYINT(1) NOT NULL DEFAULT 1 AFTER precio_final");
            asegurar_columna_bd($pdo, "productos", "creado_en", "ALTER TABLE productos ADD COLUMN creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER activo");
            ListaPrecio::asegurar_tablas();
            Stock::asegurar_columnas_minmax();
            self::$tablas_base_aseguradas = true;
        } catch (Throwable $e) {
            registrar_log("Producto::asegurar_tablas_base", $e->getMessage());
            registrar_operacion("producto.tablas_base.error", ["error" => $e->getMessage()]);
        }
    }

    public static function listar_todos(string $orden_sql = "p.nombre ASC"): array {
        self::asegurar_tablas_base();
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT p.id, p.nombre, p.cod_barras, p.id_stock, p.factor_conversion, p.ganancia, p.precio_final, p.activo, p.creado_en,
                               COALESCE(s.nombre, 'Sin stock asociado') AS stock_nombre, s.unidad AS stock_unidad, s.tipo_stock AS stock_tipo_stock,
                               s.cantidad AS stock_cantidad, s.stock_minimo, s.stock_maximo, s.precio_costo AS stock_precio_costo,
                               s.moneda_costo AS stock_moneda_costo, s.costo_origen AS stock_costo_origen
                        FROM productos p
                        LEFT JOIN stock s ON s.id = p.id_stock
                        ORDER BY " . $orden_sql . ", p.id ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("Producto::listar_todos", $e->getMessage());
            }
        }
        return $lista;
    }

    public static function listar_para_exportar(bool $solo_alta = true): array {
        Stock::asegurar_columnas_minmax();
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT p.id, p.nombre, p.cod_barras, COALESCE(s.nombre, '') AS stock_nombre,
                               COALESCE(s.cantidad, 0) AS stock_cantidad, COALESCE(s.unidad, '') AS stock_unidad, COALESCE(s.tipo_stock, '') AS stock_tipo_stock,
                               COALESCE(s.stock_minimo, 0) AS stock_minimo, COALESCE(s.stock_maximo, 0) AS stock_maximo,
                               p.factor_conversion, p.ganancia, p.precio_final, p.activo
                        FROM productos p
                        LEFT JOIN stock s ON s.id = p.id_stock
                        " . ($solo_alta ? "WHERE p.activo = 1" : "") . "
                        ORDER BY p.nombre ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("Producto::listar_en_alta", $e->getMessage());
            }
        }
        return $lista;
    }

    public static function listar_en_alta(): array {
        return self::listar_para_exportar(true);
    }

    public static function buscar_por_id(int $id): ?array {
        Stock::asegurar_columnas_minmax();
        $fila = null;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT p.id, p.nombre, p.cod_barras, p.id_stock, p.factor_conversion, p.ganancia, p.precio_final, p.activo, p.creado_en,
                               s.nombre AS stock_nombre, s.unidad AS stock_unidad, s.tipo_stock AS stock_tipo_stock,
                               s.cantidad AS stock_cantidad, s.stock_minimo, s.stock_maximo, s.precio_costo AS stock_precio_costo,
                               s.moneda_costo AS stock_moneda_costo, s.costo_origen AS stock_costo_origen
                        FROM productos p
                        LEFT JOIN stock s ON s.id = p.id_stock
                        WHERE p.id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $r = $st->fetch();
                if ($r)
                    $fila = $r;
            } catch (Throwable $e) {
                registrar_log("Producto::buscar_por_id", $e->getMessage());
            }
        }
        return $fila;
    }

    public static function cod_barras_existe(string $cod_barras, int $excepto_id = 0): bool {
        $existe = false;
        $pdo = obtener_pdo();
        $cb = trim($cod_barras);
        if ($pdo !== null && $cb !== "") {
            try {
                $sql = "SELECT id FROM productos WHERE cod_barras = ? AND id <> ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$cb, $excepto_id]);
                $r = $st->fetch();
                if ($r)
                    $existe = true;
            } catch (Throwable $e) {
                registrar_log("Producto::cod_barras_existe", $e->getMessage());
            }
        }
        return $existe;
    }

    public static function obtener_precio_costo_stock(int $id_stock): ?float {
        $precio = null;
        $pdo = obtener_pdo();
        if ($pdo !== null && $id_stock > 0) {
            try {
                $sql = "SELECT precio_costo FROM stock WHERE id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_stock]);
                $r = $st->fetch();
                if ($r)
                    $precio = (float)$r["precio_costo"];
            } catch (Throwable $e) {
                registrar_log("Producto::obtener_precio_costo_stock", $e->getMessage());
            }
        }
        return $precio;
    }

    public static function stock_existe(int $id_stock): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null && $id_stock > 0) {
            try {
                $sql = "SELECT id FROM stock WHERE id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_stock]);
                $r = $st->fetch();
                if ($r)
                    $ok = true;
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Producto::stock_existe", $e->getMessage());
            }
        }
        return $ok;
    }


    public static function calcular_precio_final(float $precio_costo, float $factor_conversion, float $ganancia): float {
        $precio = 0.0;
        if ($precio_costo < 0)
            $precio_costo = 0;
        if ($factor_conversion < 0)
            $factor_conversion = 0;
        $precio = ($precio_costo * $factor_conversion) * (1 + ($ganancia / 100));
        return $precio;
    }

    public static function crear(string $nombre, string $cod_barras, ?int $id_stock, float $factor_conversion, float $ganancia, float $precio_final, int $activo): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "INSERT INTO productos (nombre, cod_barras, id_stock, id_asociado, factor_conversion, ganancia, precio_final, activo) VALUES (?, ?, ?, NULL, ?, ?, ?, ?)";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$nombre, $cod_barras, $id_stock, $factor_conversion, $ganancia, $precio_final, $activo]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Producto::crear", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function crear_retornar_id(string $nombre, string $cod_barras, ?int $id_stock, float $factor_conversion, float $ganancia, float $precio_final, int $activo): int {
        $id = 0;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "INSERT INTO productos (nombre, cod_barras, id_stock, id_asociado, factor_conversion, ganancia, precio_final, activo) VALUES (?, ?, ?, NULL, ?, ?, ?, ?)";
                $st = $pdo->prepare($sql);
                if ($st->execute([$nombre, $cod_barras, $id_stock, $factor_conversion, $ganancia, $precio_final, $activo]))
                    $id = (int)$pdo->lastInsertId();
            } catch (Throwable $e) {
                registrar_log("Producto::crear_retornar_id", $e->getMessage());
            }
        }
        return $id;
    }


    public static function actualizar(int $id, string $nombre, string $cod_barras, ?int $id_stock, float $factor_conversion, float $ganancia, float $precio_final, int $activo): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "UPDATE productos
                        SET nombre = ?, cod_barras = ?, id_stock = ?, id_asociado = NULL,
                            factor_conversion = ?, ganancia = ?, precio_final = ?, activo = ?
                        WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$nombre, $cod_barras, $id_stock, $factor_conversion, $ganancia, $precio_final, $activo, $id]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Producto::actualizar", $e->getMessage());
            }
        }
        return $ok;
    }


    public static function esta_en_detalle_venta(int $id_producto): bool {
        $rel = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id FROM detalle_venta WHERE id_producto = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_producto]);
                $r = $st->fetch();
                if ($r)
                    $rel = true;
            } catch (Throwable $e) {
                registrar_log("Producto::esta_en_detalle_venta", $e->getMessage());
            }
        }
        return $rel;
    }

    public static function eliminar(int $id): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "DELETE FROM productos WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$id]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Producto::eliminar", $e->getMessage());
            }
        }
        return $ok;
    }
}
