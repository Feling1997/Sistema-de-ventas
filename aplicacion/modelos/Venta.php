<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
class Venta {
    public static function asegurar_usuario_sistema(PDO $pdo, int $id_usuario): int {
        if ($id_usuario > 0) {
            $st = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? LIMIT 1");
            $st->execute([$id_usuario]);
            if ($st->fetch())
                return $id_usuario;
        }
        $pdo->exec("SET @old_sql_mode = @@SESSION.sql_mode");
        $pdo->exec("SET SESSION sql_mode = CONCAT(@@SESSION.sql_mode, ',NO_AUTO_VALUE_ON_ZERO')");
        $pdo->prepare("INSERT INTO usuarios (id, usuario, clave, rol, activo)
                       VALUES (0, 'Sin login', '', 'ADMIN', 1)
                       ON DUPLICATE KEY UPDATE usuario = VALUES(usuario), rol = VALUES(rol), activo = 1")->execute();
        $pdo->exec("SET SESSION sql_mode = @old_sql_mode");
        return 0;
    }

    public static function asegurar_columnas_detalle(): void {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        try {
            $st = $pdo->prepare("SHOW COLUMNS FROM detalle_venta LIKE ?");
            $st->execute(["costo_unit"]);
            if (!$st->fetch())
                $pdo->exec("ALTER TABLE detalle_venta ADD COLUMN costo_unit DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER precio_unit");
        } catch (Throwable $e) {
            registrar_log("Venta::asegurar_columnas_detalle", $e->getMessage());
        }
    }

    public static function listar_ventas(): array {
        return self::listar_ventas_periodo("", "");
    }

    public static function listar_ventas_periodo(string $fecha_desde, string $fecha_hasta, string $orden_sql = "v.fecha DESC"): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT v.id, v.fecha, v.total, c.nombre AS cliente_nombre, u.usuario AS usuario_nombre
                        FROM ventas v
                        INNER JOIN clientes c ON c.id = v.id_cliente
                        INNER JOIN usuarios u ON u.id = v.id_usuario";
                $params = [];
                $where = self::construir_where_periodo($fecha_desde, $fecha_hasta, $params, "v.fecha");
                if ($where !== "") {
                    $sql .= " WHERE " . $where;
                }
                $sql .= " ORDER BY " . $orden_sql . ", v.id DESC";
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("Venta::listar_ventas_periodo", $e->getMessage());
            }
        }
        return $lista;
    }

    public static function obtener_resumen_periodo(string $fecha_desde, string $fecha_hasta): array {
        $resumen = [
            "cantidad_ventas" => 0,
            "total_vendido" => 0.0,
            "ganancia" => 0.0
        ];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                self::asegurar_columnas_detalle();
                $params = [];
                $where = self::construir_where_periodo($fecha_desde, $fecha_hasta, $params, "v.fecha");
                $sql = "SELECT 
                            COUNT(DISTINCT v.id) AS cantidad_ventas,
                            COALESCE(SUM(d.subtotal), 0) AS total_vendido,
                            COALESCE(SUM(
                                d.subtotal - (COALESCE(NULLIF(d.costo_unit, 0), COALESCE(s.precio_costo, 0) * p.factor_conversion, 0) * d.cantidad)
                            ), 0) AS ganancia
                        FROM ventas v
                        INNER JOIN detalle_venta d ON d.id_venta = v.id
                        INNER JOIN productos p ON p.id = d.id_producto
                        LEFT JOIN stock s ON s.id = p.id_stock";
                if ($where !== "") {
                    $sql .= " WHERE " . $where;
                }
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $fila = $st->fetch();
                if ($fila) {
                    $resumen["cantidad_ventas"] = (int)($fila["cantidad_ventas"] ?? 0);
                    $resumen["total_vendido"] = (float)($fila["total_vendido"] ?? 0);
                    $resumen["ganancia"] = (float)($fila["ganancia"] ?? 0);
                }
            } catch (Throwable $e) {
                registrar_log("Venta::obtener_resumen_periodo", $e->getMessage());
            }
        }
        return $resumen;
    }

    public static function obtener_ganancia_por_ids(array $ids_venta): float {
        $ganancia = 0.0;
        $ids = [];
        foreach ($ids_venta as $id_venta) {
            $id = (int)$id_venta;
            if ($id > 0)
                $ids[] = $id;
        }
        $ids = array_values(array_unique($ids));
        if (count($ids) === 0)
            return 0.0;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                self::asegurar_columnas_detalle();
                $placeholders = implode(", ", array_fill(0, count($ids), "?"));
                $sql = "SELECT COALESCE(SUM(
                            d.subtotal - (COALESCE(NULLIF(d.costo_unit, 0), COALESCE(s.precio_costo, 0) * p.factor_conversion, 0) * d.cantidad)
                        ), 0) AS ganancia
                        FROM detalle_venta d
                        INNER JOIN productos p ON p.id = d.id_producto
                        LEFT JOIN stock s ON s.id = p.id_stock
                        WHERE d.id_venta IN ($placeholders)";
                $st = $pdo->prepare($sql);
                $st->execute($ids);
                $fila = $st->fetch();
                if ($fila)
                    $ganancia = (float)($fila["ganancia"] ?? 0);
            } catch (Throwable $e) {
                registrar_log("Venta::obtener_ganancia_por_ids", $e->getMessage());
            }
        }
        return $ganancia;
    }

    public static function obtener_detalle(int $id_venta): array {
        $items = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                self::asegurar_columnas_detalle();
                $sql = "SELECT d.id, d.id_producto, p.nombre AS producto_nombre, d.cantidad, d.precio_unit, d.costo_unit, d.descuento, d.subtotal FROM detalle_venta d INNER JOIN productos p ON p.id = d.id_producto WHERE d.id_venta = ? ORDER BY d.id ASC";
                $st = $pdo->prepare($sql);
                $st->execute([$id_venta]);
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $items = $rows;
            } catch (Throwable $e) {
                registrar_log("Venta::obtener_detalle", $e->getMessage());
            }
        }
        return $items;
    }

    public static function obtener_producto_para_venta(int $id_producto): ?array {
        $fila = null;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id, nombre, precio_final, id_stock, id_asociado, factor_conversion, activo FROM productos WHERE id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_producto]);
                $r = $st->fetch();
                if ($r)
                    $fila = $r;
            } catch (Throwable $e) {
                registrar_log("Venta::obtener_producto_para_venta", $e->getMessage());
            }
        }
        return $fila;
    }

    public static function obtener_id_stock_consumo(array $producto): ?int {
        $id = null;
        $id_stock = $producto["id_stock"] ?? null;
        $id_asociado = $producto["id_asociado"] ?? null;
        if ($id_stock !== null) {
            $id_stock = (int)$id_stock;
            if ($id_stock > 0)
                $id = $id_stock;
        }
        if ($id === null && $id_asociado !== null) {
            $id_asociado = (int)$id_asociado;
            if ($id_asociado > 0)
                $id = $id_asociado;
        }
        return $id;
    }

    public static function obtener_stock_por_id(int $id_stock): ?array {
        $fila = null;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id, nombre, unidad, cantidad, precio_costo, activo FROM stock WHERE id = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_stock]);
                $r = $st->fetch();
                if ($r)
                    $fila = $r;
            } catch (Throwable $e) {
                registrar_log("Venta::obtener_stock_por_id", $e->getMessage());
            }
        }
        return $fila;
    }

    public static function calcular_consumo_stock(float $cantidad_producto, float $factor_conversion): float {
        $consumo = 0.0;
        if ($cantidad_producto < 0) {
            $cantidad_producto = 0;
        }
        if ($factor_conversion < 0)
            $factor_conversion = 0;
        $consumo = $cantidad_producto * $factor_conversion;
        return $consumo;
    }


    public static function calcular_subtotal(float $cantidad, float $precio_unit, float $descuento): float {
        $sub = 0.0;
        if ($cantidad < 0) { $cantidad = 0; }
        if ($precio_unit < 0) { $precio_unit = 0; }
        if ($descuento < 0) { $descuento = 0; }
        if ($descuento > 100) { $descuento = 100; }
        $bruto = ($cantidad * $precio_unit);
        $monto_desc = ($bruto * $descuento) / 100;
        $sub = $bruto - $monto_desc;
        if ($sub < 0) { $sub = 0; }
        return $sub;
    }

    public static function confirmar_venta(int $id_cliente, int $id_usuario, array $carrito): array {
        $res = ["ok" => false, "id_venta" => 0, "error" => ""];
        $pdo = obtener_pdo();
        if ($pdo === null)
            $res["error"] = "Sin conexión a base de datos.";
        else {
            try {
                self::asegurar_columnas_detalle();
                $controlar_stock = (string)(config_sistema_simple()["controlar_stock_ventas"] ?? "1") === "1";
                $pdo->beginTransaction();
                if (!is_array($carrito) || count($carrito) === 0)
                    $res["error"] = "El carrito está vacío.";
                else {
                    if ($id_cliente <= 0) { $id_cliente = 1; }
                    $id_usuario = self::asegurar_usuario_sistema($pdo, $id_usuario);
                    $total = 0.0;
                    foreach ($carrito as $it) {
                        $cantidad = (float)($it["cantidad"] ?? 0);
                        $precio_unit = (float)($it["precio_unit"] ?? 0);
                        $descuento = (float)($it["descuento"] ?? 0);
                        $sub = self::calcular_subtotal($cantidad, $precio_unit, $descuento);
                        $total += $sub;
                    }
                    $sqlV = "INSERT INTO ventas (id_cliente, id_usuario, total) VALUES (?, ?, ?)";
                    $stV = $pdo->prepare($sqlV);
                    $okV = $stV->execute([$id_cliente, $id_usuario, $total]);
                    if (!$okV)
                        $res["error"] = "No se pudo crear la venta.";
                    else {
                        $id_venta = (int)$pdo->lastInsertId();
                        foreach ($carrito as $it) {
                            $id_producto = (int)($it["id_producto"] ?? 0);
                            $cantidad = (float)($it["cantidad"] ?? 0);
                            $precio_unit = (float)($it["precio_unit"] ?? 0);
                            $descuento = (float)($it["descuento"] ?? 0);
                            if ($id_producto <= 0 || $cantidad <= 0)
                                throw new Exception("Item inválido en carrito.");
                            $prod = null;
                            $sqlP = "SELECT p.id, p.nombre, p.precio_final, p.id_stock, p.id_asociado, p.factor_conversion, p.activo, COALESCE(s.precio_costo, 0) AS precio_costo_stock FROM productos p LEFT JOIN stock s ON s.id = p.id_stock WHERE p.id = ? LIMIT 1";
                            $stP = $pdo->prepare($sqlP);
                            $stP->execute([$id_producto]);
                            $prod = $stP->fetch();
                            if (!$prod || (int)$prod["activo"] !== 1)
                                throw new Exception("Producto no disponible.");
                            $id_stock_consumo = self::obtener_id_stock_consumo($prod);
                            $factor = (float)$prod["factor_conversion"];
                            if ($factor < 0) { $factor = 0; }
                            if ($id_stock_consumo !== null) {
                                $sqlS = "SELECT id, cantidad FROM stock WHERE id = ? LIMIT 1";
                                $stS = $pdo->prepare($sqlS);
                                $stS->execute([$id_stock_consumo]);
                                $stock = $stS->fetch();
                                if (!$stock)
                                    throw new Exception("Stock no encontrado para el producto.");
                                $cantidad_stock_actual = (float)$stock["cantidad"];
                                $consumo = self::calcular_consumo_stock($cantidad, $factor);
                                if ($controlar_stock && $consumo > $cantidad_stock_actual + 0.0000001)
                                    throw new Exception("Stock insuficiente para el producto ID $id_producto.");
                                $sqlDesc = "UPDATE stock SET cantidad = cantidad - ? WHERE id = ?";
                                $stD = $pdo->prepare($sqlDesc);
                                $okD = $stD->execute([$consumo, $id_stock_consumo]);
                                if (!$okD)
                                    throw new Exception("No se pudo descontar stock.");
                            }
                            $subtotal = self::calcular_subtotal($cantidad, $precio_unit, $descuento);
                            $costo_unit = max(0, (float)($prod["precio_costo_stock"] ?? 0)) * $factor;
                            $sqlDet = "INSERT INTO detalle_venta (id_venta, id_producto, cantidad, precio_unit, costo_unit, descuento, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)";
                            $stDet = $pdo->prepare($sqlDet);
                            $okDet = $stDet->execute([$id_venta, $id_producto, $cantidad, $precio_unit, $costo_unit, $descuento, $subtotal]);
                            if (!$okDet)
                                throw new Exception("No se pudo insertar detalle.");
                        }
                        $pdo->commit();
                        $res["ok"] = true;
                        $res["id_venta"] = $id_venta;
                    }
                }
                if ($res["ok"] === false) {
                    if ($pdo->inTransaction())
                        $pdo->rollBack();
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction())
                    $pdo->rollBack();
                $res["ok"] = false;
                $res["error"] = "Error al confirmar venta: " . $e->getMessage();
                registrar_log("Venta::confirmar_venta", $e->getMessage());
            }
        }
        return $res;
    }

    private static function construir_where_periodo(string $fecha_desde, string $fecha_hasta, array &$params, string $campo_fecha): string {
        $where = [];
        $desde = trim($fecha_desde);
        $hasta = trim($fecha_hasta);
        if ($desde !== "") {
            $where[] = "$campo_fecha >= ?";
            $params[] = $desde . " 00:00:00";
        }
        if ($hasta !== "") {
            $where[] = "$campo_fecha <= ?";
            $params[] = $hasta . " 23:59:59";
        }
        return implode(" AND ", $where);
    }
}
