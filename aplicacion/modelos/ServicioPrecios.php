<?php

require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class ServicioPrecios {
    public static function normalizar_moneda_costo(string $moneda): string {
        return strtoupper(trim($moneda)) === "USD" ? "USD" : "ARS";
    }

    public static function cotizacion_dolar(): float {
        return max(0.0001, parsear_numero_form(config("productos_cotizacion_dolar", "1"), 1));
    }

    public static function costo_en_pesos(float $costo_origen, string $moneda): float {
        $costo_origen = max(0, $costo_origen);
        return self::normalizar_moneda_costo($moneda) === "USD"
            ? $costo_origen * self::cotizacion_dolar()
            : $costo_origen;
    }

    public static function recalcular_precios_productos_por_stock(PDO $pdo, int $id_stock): bool {
        try {
            $st = $pdo->prepare("SELECT precio_costo FROM stock WHERE id = ? LIMIT 1");
            $st->execute([$id_stock]);
            $stock = $st->fetch();
            if (!$stock)
                return false;

            $precio_costo = (float)$stock["precio_costo"];
            $upd = $pdo->prepare("UPDATE productos SET precio_final = (? * factor_conversion) * (1 + (ganancia / 100)) WHERE id_stock = ?");
            $ok = $upd->execute([$precio_costo, $id_stock]);
            self::recalcular_listas_por_stock($pdo, $id_stock, $precio_costo);
            return $ok;
        } catch (Throwable $e) {
            registrar_log("ServicioPrecios::recalcular_precios_productos_por_stock", $e->getMessage());
            return false;
        }
    }

    public static function recalcular_listas_por_stock(PDO $pdo, int $id_stock, float $precio_costo): void {
        try {
            $pdo->prepare("UPDATE producto_precios pp
                           INNER JOIN productos p ON p.id = pp.id_producto
                           INNER JOIN listas_precios l ON l.id = pp.id_lista
                           SET pp.precio = CASE
                               WHEN LOWER(TRIM(l.nombre)) = 'costo' THEN ? * p.factor_conversion
                               ELSE (? * p.factor_conversion) * (1 + (pp.porcentaje / 100))
                           END
                           WHERE p.id_stock = ?")->execute([$precio_costo, $precio_costo, $id_stock]);
        } catch (Throwable $e) {
            registrar_log("ServicioPrecios::recalcular_listas_por_stock", $e->getMessage());
        }
    }

    public static function recalcular_costos_por_cotizacion(PDO $pdo): int {
        try {
            $ids = $pdo->query("SELECT id FROM stock WHERE moneda_costo = 'USD'")->fetchAll(PDO::FETCH_COLUMN);
            $cotizacion = self::cotizacion_dolar();
            $pdo->prepare("UPDATE stock SET precio_costo = costo_origen * ? WHERE moneda_costo = 'USD'")->execute([$cotizacion]);
            foreach ($ids as $id)
                self::recalcular_precios_productos_por_stock($pdo, (int)$id);
            registrar_operacion("stock.cotizacion_dolar.recalcular", [
                "cotizacion" => $cotizacion,
                "stocks_actualizados" => count($ids),
            ]);
            return count($ids);
        } catch (Throwable $e) {
            registrar_log("ServicioPrecios::recalcular_costos_por_cotizacion", $e->getMessage());
            registrar_operacion("stock.cotizacion_dolar.error", ["error" => $e->getMessage()]);
            return 0;
        }
    }
}
