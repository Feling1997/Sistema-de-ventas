<?php
require_once __DIR__ . "/../modelos/Stock.php";
require_once __DIR__ . "/../modelos/ListaPrecio.php";
require_once __DIR__ . "/../modelos/Venta.php";
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/seguridad.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class ControladorExportaciones {
    private function permiso(): bool {
        if (!require_login()) {
            flash_error("Tenes que iniciar sesion.");
            redirigir("index.php?c=auth&a=login");
            return false;
        }
        if (!require_rol(["ADMIN", "VENDEDOR"])) {
            flash_error("No tenes permisos para exportaciones.");
            redirigir("index.php?c=ventas&a=lista");
            return false;
        }
        return true;
    }

    public function index(): void {
        if ($this->permiso()) {
            $listas = ListaPrecio::listar(true);
            $stocks = Stock::listar_todos();
            include __DIR__ . "/../vistas/parciales/encabezado.php";
            include __DIR__ . "/../vistas/exportaciones/index.php";
            include __DIR__ . "/../vistas/parciales/pie.php";
        }
    }

    public function ir(): void {
        if ($this->permiso()) {
            $reporte = strtolower((string)obtener_get("reporte", ""));
            $formato = $this->formato();
            if ($reporte === "stock_actual")
                redirigir("index.php?c=stock&a=exportar&formato=" . urlencode($formato));
            else if ($reporte === "pedido_minimo")
                redirigir("index.php?c=stock&a=exportar_faltantes&solo_minimo=1&formato=" . urlencode($formato));
            else if ($reporte === "faltantes_completo")
                redirigir("index.php?c=stock&a=exportar_faltantes&solo_minimo=0&formato=" . urlencode($formato));
            else if ($reporte === "productos_stock") {
                $id = max(0, (int)obtener_get("id_stock", 0));
                $id_lista = max(0, (int)obtener_get("id_lista_precio", 0));
                redirigir("index.php?c=stock&a=exportar_productos&id=" . $id . "&id_lista_precio=" . $id_lista . "&formato=" . urlencode($formato));
            } else if ($reporte === "articulos") {
                $alcance = (string)obtener_get("alcance", "alta") === "todos" ? "todos" : "alta";
                $id_lista = max(0, (int)obtener_get("id_lista_precio", 0));
                redirigir("index.php?c=productos&a=exportar_altas&alcance=" . $alcance . "&id_lista_precio=" . $id_lista . "&formato=" . urlencode($formato));
            } else if ($reporte === "lista_precios") {
                $id_lista = max(0, (int)obtener_get("id_lista_precio", 0));
                redirigir("index.php?c=listas_precios&a=exportar&id=" . $id_lista . "&formato=" . urlencode($formato));
            } else {
                flash_error("Selecciona un reporte para exportar.");
                redirigir("index.php?c=exportaciones&a=index");
            }
        }
    }

    public function estadisticas(): void {
        if ($this->permiso()) {
            $tipo = strtolower((string)obtener_get("tipo", "resumen"));
            $formato = $this->formato();
            $desde = trim((string)obtener_get("fecha_desde", ""));
            $hasta = trim((string)obtener_get("fecha_hasta", ""));
            $subtitulo = $this->subtitulo_periodo($desde, $hasta);
            if ($tipo === "productos")
                $this->exportar_productos_vendidos($desde, $hasta, $formato, $subtitulo);
            else if ($tipo === "articulos_detalle")
                $this->exportar_articulos_detalle($desde, $hasta, $formato, $subtitulo);
            else if ($tipo === "mensuales")
                $this->exportar_ventas_mensuales($desde, $hasta, $formato, $subtitulo);
            else if ($tipo === "tickets")
                $this->exportar_tickets($desde, $hasta, $formato, $subtitulo);
            else if ($tipo === "stock_ventas")
                $this->exportar_stock_ventas($desde, $hasta, $formato, $subtitulo);
            else if ($tipo === "diarias")
                $this->exportar_ventas_diarias($desde, $hasta, $formato, $subtitulo);
            else
                $this->exportar_resumen($desde, $hasta, $formato, $subtitulo);
        }
    }

    private function exportar_resumen(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $resumen = Venta::obtener_resumen_periodo($desde, $hasta);
        $filas = "<tr><td>Ventas realizadas</td><td class='num'>" . (int)$resumen["cantidad_ventas"] . "</td></tr>";
        $filas .= "<tr><td>Total vendido</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($resumen["total_vendido"] ?? 0)) . "</td></tr>";
        $filas .= "<tr><td>Ganancia estimada</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($resumen["ganancia"] ?? 0)) . "</td></tr>";
        $rows = [
            ["Concepto" => "Ventas realizadas", "Valor" => (string)(int)$resumen["cantidad_ventas"]],
            ["Concepto" => "Total vendido", "Valor" => numero_para_mostrar($resumen["total_vendido"] ?? 0, 2)],
            ["Concepto" => "Ganancia estimada", "Valor" => numero_para_mostrar($resumen["ganancia"] ?? 0, 2)],
        ];
        $this->responder("estadisticas_resumen", "Resumen estadistico", $subtitulo, ["Concepto", "Valor"], $filas, 2, $rows, $formato);
    }

    private function exportar_ventas_diarias(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $rows = $this->ventas_diarias($desde, $hasta);
        $filas = "";
        foreach ($rows as $r)
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["fecha"]) . "</td><td class='num'>" . (int)$r["ventas"] . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td></tr>";
        $this->responder("ventas_diarias", "Ventas por dia", $subtitulo, ["Fecha", "Ventas", "Total"], $filas, 3, $rows, $formato);
    }

    private function exportar_productos_vendidos(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $rows = $this->productos_vendidos($desde, $hasta);
        $filas = "";
        foreach ($rows as $r)
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["producto"]) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["cantidad"] ?? 0, 3)) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td></tr>";
        $this->responder("productos_vendidos", "Productos vendidos", $subtitulo, ["Producto", "Cantidad", "Total"], $filas, 3, $rows, $formato);
    }

    private function exportar_articulos_detalle(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $rows = $this->articulos_detalle($desde, $hasta);
        $filas = "";
        $csv = [];
        foreach ($rows as $r) {
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["producto"]) . "</td><td>" . htmlspecialchars((string)$r["codigo"]) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["unidades"] ?? 0, 3)) . "</td><td class='num'>" . (int)($r["ventas"] ?? 0) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["costo"] ?? 0)) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["ganancia"] ?? 0)) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["margen"] ?? 0, 2)) . "%</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["participacion"] ?? 0, 2)) . "%</td><td class='num'>" . htmlspecialchars(stock_para_mostrar($r["stock_actual"] ?? 0, 3)) . "</td></tr>";
            $csv[] = [
                "Articulo" => (string)$r["producto"],
                "Codigo" => (string)$r["codigo"],
                "Unidades" => numero_para_mostrar($r["unidades"] ?? 0, 3),
                "Ventas" => (string)(int)($r["ventas"] ?? 0),
                "Total" => numero_para_mostrar($r["total"] ?? 0, 2),
                "Costo" => numero_para_mostrar($r["costo"] ?? 0, 2),
                "Ganancia" => numero_para_mostrar($r["ganancia"] ?? 0, 2),
                "Margen" => numero_para_mostrar($r["margen"] ?? 0, 2),
                "% total" => numero_para_mostrar($r["participacion"] ?? 0, 2),
                "Stock" => stock_para_mostrar($r["stock_actual"] ?? 0, 3),
            ];
        }
        $headers = ["Articulo", "Codigo", "Unidades", "Ventas", "Total", "Costo", "Ganancia", "Margen", "% total", "Stock"];
        $this->responder("ventas_por_articulo_detalle", "Ventas por articulos detallado", $subtitulo, $headers, $filas, 10, $csv, $formato);
    }

    private function exportar_ventas_mensuales(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $rows = $this->ventas_mensuales($desde, $hasta);
        $filas = "";
        foreach ($rows as $r)
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["periodo"]) . "</td><td class='num'>" . (int)$r["ventas"] . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["unidades"] ?? 0, 3)) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["ticket_promedio"] ?? 0)) . "</td></tr>";
        $this->responder("ventas_mensuales", "Ventas por mes", $subtitulo, ["Mes", "Ventas", "Unidades", "Total", "Ticket promedio"], $filas, 5, $rows, $formato);
    }

    private function exportar_tickets(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $rows = $this->tickets_resumen($desde, $hasta);
        $filas = "";
        foreach ($rows as $r)
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["rango"]) . "</td><td class='num'>" . (int)$r["ventas"] . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["participacion"] ?? 0, 2)) . "%</td></tr>";
        $this->responder("tickets_por_rango", "Tickets por rango de importe", $subtitulo, ["Rango", "Ventas", "Total", "% ventas"], $filas, 4, $rows, $formato);
    }

    private function exportar_stock_ventas(string $desde, string $hasta, string $formato, string $subtitulo): void {
        $rows = $this->stock_ventas($desde, $hasta);
        $filas = "";
        foreach ($rows as $r)
            $filas .= "<tr><td>" . htmlspecialchars((string)$r["stock"]) . "</td><td class='num'>" . htmlspecialchars(numero_para_mostrar($r["vendido"] ?? 0, 3)) . "</td><td class='num'>" . htmlspecialchars(stock_para_mostrar($r["stock_actual"] ?? 0, 3)) . "</td><td>" . htmlspecialchars((string)$r["unidad"]) . "</td><td class='num'>" . htmlspecialchars(moneda_para_mostrar($r["total"] ?? 0)) . "</td></tr>";
        $this->responder("stock_vs_ventas", "Stock vendido y existencia actual", $subtitulo, ["Stock", "Vendido", "Actual", "Unidad", "Total vendido"], $filas, 5, $rows, $formato);
    }

    private function ventas_diarias(string $desde, string $hasta): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                Venta::asegurar_columnas_detalle();
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "v.fecha");
                $sql = "SELECT DATE(v.fecha) AS fecha, COUNT(*) AS ventas, COALESCE(SUM(v.total), 0) AS total FROM ventas v";
                if ($where !== "")
                    $sql .= " WHERE " . $where;
                $sql .= " GROUP BY DATE(v.fecha) ORDER BY fecha DESC";
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $lista = $st->fetchAll() ?: [];
            } catch (Throwable $e) {
                registrar_log("Exportaciones::ventas_diarias", $e->getMessage());
            }
        }
        return $lista;
    }

    private function productos_vendidos(string $desde, string $hasta): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "v.fecha");
                $sql = "SELECT p.nombre AS producto, COALESCE(SUM(d.cantidad), 0) AS cantidad, COALESCE(SUM(d.subtotal), 0) AS total
                        FROM detalle_venta d
                        INNER JOIN ventas v ON v.id = d.id_venta
                        INNER JOIN productos p ON p.id = d.id_producto";
                if ($where !== "")
                    $sql .= " WHERE " . $where;
                $sql .= " GROUP BY p.id, p.nombre ORDER BY total DESC, producto ASC";
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $lista = $st->fetchAll() ?: [];
            } catch (Throwable $e) {
                registrar_log("Exportaciones::productos_vendidos", $e->getMessage());
            }
        }
        return $lista;
    }

    private function articulos_detalle(string $desde, string $hasta): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "v.fecha");
                $where_sql = $where !== "" ? " WHERE " . $where : "";
                $sql_total = "SELECT COALESCE(SUM(d.subtotal), 0) AS total FROM detalle_venta d INNER JOIN ventas v ON v.id = d.id_venta" . $where_sql;
                $st_total = $pdo->prepare($sql_total);
                $st_total->execute($params);
                $total_general = (float)(($st_total->fetch()["total"] ?? 0));

                $sql = "SELECT p.nombre AS producto, p.cod_barras AS codigo,
                               COALESCE(SUM(d.cantidad), 0) AS unidades,
                               COUNT(DISTINCT v.id) AS ventas,
                               COALESCE(SUM(d.subtotal), 0) AS total,
                               COALESCE(SUM(COALESCE(NULLIF(d.costo_unit, 0), COALESCE(s.precio_costo, 0) * p.factor_conversion, 0) * d.cantidad), 0) AS costo,
                               COALESCE(SUM(d.subtotal - (COALESCE(NULLIF(d.costo_unit, 0), COALESCE(s.precio_costo, 0) * p.factor_conversion, 0) * d.cantidad)), 0) AS ganancia,
                               COALESCE(s.cantidad, 0) AS stock_actual
                        FROM detalle_venta d
                        INNER JOIN ventas v ON v.id = d.id_venta
                        INNER JOIN productos p ON p.id = d.id_producto
                        LEFT JOIN stock s ON s.id = p.id_stock";
                if ($where !== "")
                    $sql .= " WHERE " . $where;
                $sql .= " GROUP BY p.id, p.nombre, p.cod_barras, s.cantidad ORDER BY total DESC, unidades DESC";
                $st = $pdo->prepare($sql);
                $st->execute($params);
                foreach (($st->fetchAll() ?: []) as $row) {
                    $total = (float)($row["total"] ?? 0);
                    $ganancia = (float)($row["ganancia"] ?? 0);
                    $row["margen"] = $total > 0 ? ($ganancia / $total) * 100 : 0;
                    $row["participacion"] = $total_general > 0 ? ($total / $total_general) * 100 : 0;
                    $lista[] = $row;
                }
            } catch (Throwable $e) {
                registrar_log("Exportaciones::articulos_detalle", $e->getMessage());
            }
        }
        return $lista;
    }

    private function ventas_mensuales(string $desde, string $hasta): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "v.fecha");
                $sql = "SELECT DATE_FORMAT(v.fecha, '%Y-%m') AS periodo,
                               COUNT(DISTINCT v.id) AS ventas,
                               COALESCE(SUM(v.total), 0) AS total,
                               COALESCE(SUM(v.total) / NULLIF(COUNT(DISTINCT v.id), 0), 0) AS ticket_promedio,
                               COALESCE((
                                   SELECT SUM(d.cantidad)
                                   FROM detalle_venta d
                                   INNER JOIN ventas v2 ON v2.id = d.id_venta
                                   WHERE DATE_FORMAT(v2.fecha, '%Y-%m') = DATE_FORMAT(v.fecha, '%Y-%m')
                               ), 0) AS unidades
                        FROM ventas v";
                if ($where !== "")
                    $sql .= " WHERE " . $where;
                $sql .= " GROUP BY DATE_FORMAT(v.fecha, '%Y-%m') ORDER BY periodo DESC";
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $lista = $st->fetchAll() ?: [];
            } catch (Throwable $e) {
                registrar_log("Exportaciones::ventas_mensuales", $e->getMessage());
            }
        }
        return $lista;
    }

    private function tickets_resumen(string $desde, string $hasta): array {
        $rangos = [
            ["rango" => "Hasta 10.000", "min" => 0, "max" => 10000],
            ["rango" => "10.001 a 50.000", "min" => 10000.01, "max" => 50000],
            ["rango" => "50.001 a 100.000", "min" => 50000.01, "max" => 100000],
            ["rango" => "Mas de 100.000", "min" => 100000.01, "max" => null],
        ];
        $lista = [];
        $ventas_total = 0;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "fecha");
                $sql_total = "SELECT COUNT(*) AS total FROM ventas" . ($where !== "" ? " WHERE " . $where : "");
                $st_total = $pdo->prepare($sql_total);
                $st_total->execute($params);
                $ventas_total = (int)(($st_total->fetch()["total"] ?? 0));

                foreach ($rangos as $rango) {
                    $params_rango = $params;
                    $condiciones = [];
                    if ($where !== "")
                        $condiciones[] = $where;
                    $condiciones[] = "total >= ?";
                    $params_rango[] = $rango["min"];
                    if ($rango["max"] !== null) {
                        $condiciones[] = "total <= ?";
                        $params_rango[] = $rango["max"];
                    }
                    $sql = "SELECT COUNT(*) AS ventas, COALESCE(SUM(total), 0) AS total FROM ventas WHERE " . implode(" AND ", $condiciones);
                    $st = $pdo->prepare($sql);
                    $st->execute($params_rango);
                    $row = $st->fetch() ?: [];
                    $ventas = (int)($row["ventas"] ?? 0);
                    $lista[] = [
                        "rango" => $rango["rango"],
                        "ventas" => $ventas,
                        "total" => (float)($row["total"] ?? 0),
                        "participacion" => $ventas_total > 0 ? ($ventas / $ventas_total) * 100 : 0,
                    ];
                }
            } catch (Throwable $e) {
                registrar_log("Exportaciones::tickets_resumen", $e->getMessage());
            }
        }
        return $lista;
    }

    private function stock_ventas(string $desde, string $hasta): array {
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $params = [];
                $where = $this->where_periodo($desde, $hasta, $params, "v.fecha");
                $sql = "SELECT COALESCE(s.nombre, 'Sin stock') AS stock,
                               COALESCE(SUM(d.cantidad * p.factor_conversion), 0) AS vendido,
                               COALESCE(s.cantidad, 0) AS stock_actual,
                               COALESCE(s.unidad, '') AS unidad,
                               COALESCE(SUM(d.subtotal), 0) AS total
                        FROM detalle_venta d
                        INNER JOIN ventas v ON v.id = d.id_venta
                        INNER JOIN productos p ON p.id = d.id_producto
                        LEFT JOIN stock s ON s.id = p.id_stock";
                if ($where !== "")
                    $sql .= " WHERE " . $where;
                $sql .= " GROUP BY s.id, s.nombre, s.cantidad, s.unidad ORDER BY vendido DESC";
                $st = $pdo->prepare($sql);
                $st->execute($params);
                $lista = $st->fetchAll() ?: [];
            } catch (Throwable $e) {
                registrar_log("Exportaciones::stock_ventas", $e->getMessage());
            }
        }
        return $lista;
    }

    private function responder(string $archivo, string $titulo, string $subtitulo, array $headers, string $filas, int $colspan, array $rows, string $formato): void {
        if ($formato === "csv") {
            header("Content-Type: text/csv; charset=utf-8");
            header("Content-Disposition: attachment; filename=" . $archivo . "_" . date("Ymd_His") . ".csv");
            $out = fopen("php://output", "w");
            if ($out !== false) {
                fprintf($out, "\xEF\xBB\xBF");
                fputcsv($out, $headers, ";");
                foreach ($rows as $row)
                    fputcsv($out, array_values($row), ";");
                fclose($out);
            }
            return;
        }
        $html = reporte_html_tabla($titulo, $subtitulo, $headers, $filas, $colspan);
        if ($formato === "xls" || $formato === "excel") {
            header("Content-Type: application/vnd.ms-excel; charset=utf-8");
            header("Content-Disposition: attachment; filename=" . $archivo . "_" . date("Ymd_His") . ".xls");
            echo $html;
            return;
        }
        if ($formato === "pdf") {
            $autoload = __DIR__ . "/../../vendor/autoload.php";
            if (file_exists($autoload)) {
                require_once $autoload;
                $dompdf = new \Dompdf\Dompdf();
                $dompdf->loadHtml($html, "UTF-8");
                $dompdf->setPaper("A4", "portrait");
                $dompdf->render();
                header("Content-Type: application/pdf");
                header("Content-Disposition: attachment; filename=" . $archivo . "_" . date("Ymd_His") . ".pdf");
                echo $dompdf->output();
                return;
            }
        }
        echo $html;
    }

    private function formato(): string {
        $formato = strtolower((string)obtener_get("formato", "html"));
        return in_array($formato, ["html", "pdf", "xls", "excel", "csv"], true) ? $formato : "html";
    }

    private function subtitulo_periodo(string $desde, string $hasta): string {
        if ($desde === "" && $hasta === "")
            return "Todos los periodos registrados";
        if ($desde !== "" && $hasta !== "")
            return "Periodo " . $desde . " a " . $hasta;
        if ($desde !== "")
            return "Desde " . $desde;
        return "Hasta " . $hasta;
    }

    private function where_periodo(string $desde, string $hasta, array &$params, string $campo): string {
        $where = [];
        if ($desde !== "") {
            $where[] = "$campo >= ?";
            $params[] = $desde . " 00:00:00";
        }
        if ($hasta !== "") {
            $where[] = "$campo <= ?";
            $params[] = $hasta . " 23:59:59";
        }
        return implode(" AND ", $where);
    }
}
