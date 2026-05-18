<?php

require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class Stock{
    public static function asegurar_columnas_minmax(): void {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        try {
            $st = $pdo->prepare("SHOW COLUMNS FROM stock LIKE ?");
            $st->execute(["stock_minimo"]);
            if (!$st->fetch())
                $pdo->exec("ALTER TABLE stock ADD COLUMN stock_minimo DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER cantidad");
            $st = $pdo->prepare("SHOW COLUMNS FROM stock LIKE ?");
            $st->execute(["stock_maximo"]);
            if (!$st->fetch())
                $pdo->exec("ALTER TABLE stock ADD COLUMN stock_maximo DECIMAL(14,3) NOT NULL DEFAULT 0 AFTER stock_minimo");
        } catch (Throwable $e) {
            registrar_log("Stock::asegurar_columnas_minmax", $e->getMessage());
        }
    }
    
    public static function listar_todos():array{
        self::asegurar_columnas_minmax();
        $lista=[];
        $pdo=obtener_pdo();
        if($pdo!==null){
            try{
                $sql = "SELECT id, nombre, unidad, cantidad, stock_minimo, stock_maximo, precio_costo, activo, creado_en FROM stock ORDER BY id DESC";
                $st=$pdo->prepare($sql);
                $st->execute();
                $rows=$st->fetchAll();
                if(is_array($rows))
                    $lista=$rows;
            }catch(Throwable $e){
                registrar_log("Stock::listar_todos ",$e->getMessage());
            }
        }
        return $lista;
    }

    public static function buscar_por_id(int $id): ?array {
        self::asegurar_columnas_minmax();
        $fila = null;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id, nombre, unidad, cantidad, stock_minimo, stock_maximo, precio_costo, activo, creado_en FROM stock WHERE id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $r = $st->fetch();
                if ($r)
                    $fila = $r;
            } catch (Throwable $e) {
                registrar_log("Stock::buscar_por_id", $e->getMessage());
            }
        }
        return $fila;
    }

    public static function crear(string $nombre, string $unidad, float $cantidad, float $precio_costo, int $activo, float $stock_minimo = 0, float $stock_maximo = 0): bool {
        self::asegurar_columnas_minmax();
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "INSERT INTO stock (nombre, unidad, cantidad, stock_minimo, stock_maximo, precio_costo, activo) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$nombre, $unidad, $cantidad, $stock_minimo, $stock_maximo, $precio_costo, $activo]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::crear", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function crear_retornar_id(string $nombre, string $unidad, float $cantidad, float $precio_costo, int $activo, float $stock_minimo = 0, float $stock_maximo = 0): int {
        self::asegurar_columnas_minmax();
        $id = 0;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "INSERT INTO stock (nombre, unidad, cantidad, stock_minimo, stock_maximo, precio_costo, activo) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $st = $pdo->prepare($sql);
                if ($st->execute([$nombre, $unidad, $cantidad, $stock_minimo, $stock_maximo, $precio_costo, $activo]))
                    $id = (int)$pdo->lastInsertId();
            } catch (Throwable $e) {
                registrar_log("Stock::crear_retornar_id", $e->getMessage());
            }
        }
        return $id;
    }

    public static function actualizar(int $id, string $nombre, string $unidad, float $cantidad, float $precio_costo, int $activo, float $stock_minimo = 0, float $stock_maximo = 0): bool {
        self::asegurar_columnas_minmax();
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "UPDATE stock SET nombre = ?, unidad = ?, cantidad = ?, stock_minimo = ?, stock_maximo = ?, precio_costo = ?, activo = ? WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$nombre, $unidad, $cantidad, $stock_minimo, $stock_maximo, $precio_costo, $activo, $id]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::actualizar", $e->getMessage());
            }
        }

        return $ok;
    }

    public static function sumar_cantidad(int $id, float $cantidad): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null && $id > 0) {
            try {
                if ($cantidad < 0)
                    $cantidad = 0;
                $sql = "UPDATE stock SET cantidad = cantidad + ? WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$cantidad, $id]);
            } catch (Throwable $e) {
                registrar_log("Stock::sumar_cantidad", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function contar_productos_asociados(int $id_stock): int {
        $cantidad = 0;
        $pdo = obtener_pdo();
        if ($pdo !== null && $id_stock > 0) {
            try {
                $sql = "SELECT COUNT(*) AS total FROM productos WHERE id_stock = ?";
                $st = $pdo->prepare($sql);
                $st->execute([$id_stock]);
                $r = $st->fetch();
                if ($r)
                    $cantidad = (int)$r["total"];
            } catch (Throwable $e) {
                registrar_log("Stock::contar_productos_asociados", $e->getMessage());
            }
        }
        return $cantidad;
    }

    public static function esta_asociado_a_productos(int $id_stock): bool {
        $rel = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id FROM productos WHERE id_stock = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_stock]);
                $r = $st->fetch();
                if ($r)
                    $rel = true;
            } catch (Throwable $e) {
                registrar_log("Stock::esta_asociado_a_productos", $e->getMessage());
            }
        }
        return $rel;
    }

    public static function eliminar(int $id): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "DELETE FROM stock WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$id]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::eliminar", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function recalcular_precios_productos_por_stock(int $id_stock): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sqlStock = "SELECT precio_costo FROM stock WHERE id = ? LIMIT 1";
                $st1 = $pdo->prepare($sqlStock);
                $st1->execute([$id_stock]);
                $s = $st1->fetch();
                if ($s) {
                    $precio_costo = (float)$s["precio_costo"];
                    $sqlUpd = "UPDATE productos SET precio_final = ( ? * factor_conversion ) * (1 + (ganancia/100)) WHERE id_stock = ?";
                    $st2 = $pdo->prepare($sqlUpd);
                    $ok = $st2->execute([$precio_costo, $id_stock]);
                } else 
                    $ok = false;
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Stock::recalcular_precios_productos_por_stock", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function listar_faltantes(bool $solo_minimo = true): array {
        self::asegurar_columnas_minmax();
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $where = $solo_minimo ? "WHERE activo = 1 AND stock_minimo > 0 AND cantidad <= stock_minimo" : "WHERE activo = 1";
                $sql = "SELECT id, nombre, unidad, cantidad, stock_minimo, stock_maximo, precio_costo,
                               CASE
                                 WHEN stock_maximo > 0 AND stock_maximo > cantidad THEN stock_maximo - cantidad
                                 WHEN stock_minimo > 0 AND stock_minimo > cantidad THEN stock_minimo - cantidad
                                 ELSE 0
                               END AS cantidad_sugerida
                        FROM stock
                        $where
                        ORDER BY (cantidad <= stock_minimo AND stock_minimo > 0) DESC, nombre ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("Stock::listar_faltantes", $e->getMessage());
            }
        }
        return $lista;
    }
}
