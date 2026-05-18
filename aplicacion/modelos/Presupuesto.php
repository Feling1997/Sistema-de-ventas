<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/Venta.php";

class Presupuesto {
    public static function asegurar_tablas(PDO $pdo): void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS presupuestos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            id_cliente INT NOT NULL,
            id_usuario INT NOT NULL,
            total DECIMAL(10,2) NOT NULL DEFAULT 0,
            estado VARCHAR(20) NOT NULL DEFAULT 'ABIERTO',
            creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_presupuestos_cliente (id_cliente),
            KEY idx_presupuestos_fecha (fecha)
        )");
        $pdo->exec("CREATE TABLE IF NOT EXISTS detalle_presupuesto (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_presupuesto INT NOT NULL,
            id_producto INT NOT NULL,
            producto_nombre VARCHAR(150) NOT NULL,
            cantidad DECIMAL(10,3) NOT NULL,
            precio_unit DECIMAL(10,2) NOT NULL,
            descuento DECIMAL(10,2) NOT NULL DEFAULT 0,
            subtotal DECIMAL(10,2) NOT NULL,
            KEY idx_detalle_presupuesto (id_presupuesto)
        )");
    }

    public static function confirmar(int $id_cliente, int $id_usuario, array $carrito): array {
        $res = ["ok" => false, "id_presupuesto" => 0, "error" => ""];
        $pdo = obtener_pdo();
        if ($pdo === null) {
            $res["error"] = "Sin conexion a base de datos.";
            return $res;
        }
        try {
            self::asegurar_tablas($pdo);
            if (!is_array($carrito) || count($carrito) === 0) {
                $res["error"] = "El presupuesto no tiene items.";
                return $res;
            }
            if ($id_cliente <= 0)
                $id_cliente = 1;
            $pdo->beginTransaction();
            $total = 0.0;
            foreach ($carrito as $it)
                $total += Venta::calcular_subtotal((float)($it["cantidad"] ?? 0), (float)($it["precio_unit"] ?? 0), (float)($it["descuento"] ?? 0));
            $st = $pdo->prepare("INSERT INTO presupuestos (id_cliente, id_usuario, total) VALUES (?, ?, ?)");
            $st->execute([$id_cliente, $id_usuario, $total]);
            $id_presupuesto = (int)$pdo->lastInsertId();
            $stDet = $pdo->prepare("INSERT INTO detalle_presupuesto (id_presupuesto, id_producto, producto_nombre, cantidad, precio_unit, descuento, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($carrito as $it) {
                $cantidad = (float)($it["cantidad"] ?? 0);
                $precio_unit = (float)($it["precio_unit"] ?? 0);
                $descuento = (float)($it["descuento"] ?? 0);
                $subtotal = Venta::calcular_subtotal($cantidad, $precio_unit, $descuento);
                $stDet->execute([
                    $id_presupuesto,
                    (int)($it["id_producto"] ?? 0),
                    (string)($it["nombre"] ?? ""),
                    $cantidad,
                    $precio_unit,
                    $descuento,
                    $subtotal
                ]);
            }
            $pdo->commit();
            $res["ok"] = true;
            $res["id_presupuesto"] = $id_presupuesto;
        } catch (Throwable $e) {
            if ($pdo->inTransaction())
                $pdo->rollBack();
            $res["error"] = "Error al confirmar presupuesto: " . $e->getMessage();
            registrar_log("Presupuesto::confirmar", $e->getMessage());
        }
        return $res;
    }

    public static function buscar(int $id_presupuesto): ?array {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return null;
        self::asegurar_tablas($pdo);
        $st = $pdo->prepare("SELECT p.id, p.fecha, p.total, c.nombre AS cliente_nombre, u.usuario AS usuario_nombre FROM presupuestos p INNER JOIN clientes c ON c.id = p.id_cliente INNER JOIN usuarios u ON u.id = p.id_usuario WHERE p.id = ? LIMIT 1");
        $st->execute([$id_presupuesto]);
        $fila = $st->fetch();
        return $fila ?: null;
    }

    public static function obtener_detalle(int $id_presupuesto): array {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return [];
        self::asegurar_tablas($pdo);
        $st = $pdo->prepare("SELECT * FROM detalle_presupuesto WHERE id_presupuesto = ? ORDER BY id ASC");
        $st->execute([$id_presupuesto]);
        $rows = $st->fetchAll();
        return is_array($rows) ? $rows : [];
    }
}
