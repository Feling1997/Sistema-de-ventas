<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/Venta.php";
require_once __DIR__ . "/Cliente.php";
require_once __DIR__ . "/ServicioFacturacionFiscal.php";
require_once __DIR__ . "/ConfiguracionSistema.php";

class FacturaFiscal {
    public static function tipos_comprobante(): array {
        return [
            98 => ["letra" => "X", "texto" => "Factura X", "operacion" => "factura_x", "fiscal" => false, "requisito" => "Comprobante interno: descuenta stock y no se envia a AFIP"],
            1 => ["letra" => "A", "texto" => "Factura A", "operacion" => "factura", "fiscal" => true, "requisito" => "Cliente Responsable Inscripto con CUIT"],
            6 => ["letra" => "B", "texto" => "Factura B", "operacion" => "factura", "fiscal" => true, "requisito" => "Consumidor Final, monotributista o exento"],
            11 => ["letra" => "C", "texto" => "Factura C", "operacion" => "factura", "fiscal" => true, "requisito" => "Emisor monotributista"],
            19 => ["letra" => "E", "texto" => "Factura E", "operacion" => "exportacion", "fiscal" => true, "requisito" => "Exportacion: requiere pais, CUIT pais, moneda, incoterms y datos aduaneros si corresponden"],
            3 => ["letra" => "A", "texto" => "Nota de credito A", "operacion" => "nota_credito", "fiscal" => true, "requisito" => "Debe referenciar una Factura A autorizada"],
            8 => ["letra" => "B", "texto" => "Nota de credito B", "operacion" => "nota_credito", "fiscal" => true, "requisito" => "Debe referenciar una Factura B autorizada"],
            13 => ["letra" => "C", "texto" => "Nota de credito C", "operacion" => "nota_credito", "fiscal" => true, "requisito" => "Debe referenciar una Factura C autorizada"],
            21 => ["letra" => "E", "texto" => "Nota de credito E", "operacion" => "nota_credito_exportacion", "fiscal" => true, "requisito" => "Debe referenciar una Factura E autorizada"],
            2 => ["letra" => "A", "texto" => "Nota de debito A", "operacion" => "nota_debito", "fiscal" => true, "requisito" => "Debe referenciar una Factura A autorizada"],
            7 => ["letra" => "B", "texto" => "Nota de debito B", "operacion" => "nota_debito", "fiscal" => true, "requisito" => "Debe referenciar una Factura B autorizada"],
            12 => ["letra" => "C", "texto" => "Nota de debito C", "operacion" => "nota_debito", "fiscal" => true, "requisito" => "Debe referenciar una Factura C autorizada"],
            20 => ["letra" => "E", "texto" => "Nota de debito E", "operacion" => "nota_debito_exportacion", "fiscal" => true, "requisito" => "Debe referenciar una Factura E autorizada"],
            99 => ["letra" => "X", "texto" => "Presupuesto", "operacion" => "presupuesto", "fiscal" => false, "requisito" => "Documento no valido como factura, no descuenta stock"],
        ];
    }

    public static function tipo_comprobante(int $codigo): array {
        $tipos = self::tipos_comprobante();
        if (!isset($tipos[$codigo]))
            $codigo = 98;
        $tipo = $tipos[$codigo];
        $tipo["codigo"] = $codigo;
        return $tipo;
    }

    public static function validar_cliente_para_comprobante(int $tipo_comprobante, array $cliente): string {
        $tipo = self::tipo_comprobante($tipo_comprobante);
        $letra = (string)($tipo["letra"] ?? "");
        if ($letra === "A")
            return Cliente::validar_datos_factura_a($cliente);
        return "";
    }

    public static function crear_pendiente_para_venta(int $id_venta, string $tipo_operacion = "factura", int $tipo_comprobante = 6): bool {
        $pdo = obtener_pdo();
        if ($pdo === null || $id_venta <= 0)
            return false;
        try {
            $payload = self::construir_payload_venta($id_venta, $tipo_operacion, $tipo_comprobante);
            if ($payload === [])
                return false;
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!is_string($json))
                return false;

            $config = self::configuracion();
            $proveedor = (string)($config["proveedor"] ?? "api_rest");
            $punto_venta = (int)($config["empresa"]["punto_venta"] ?? 1);

            $sql = "INSERT INTO fiscal_comprobantes
                        (id_venta, tipo_operacion, estado, proveedor, punto_venta, tipo_comprobante, payload_json)
                    VALUES (?, ?, 'PENDIENTE', ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE payload_json = VALUES(payload_json), actualizado_en = CURRENT_TIMESTAMP";
            $st = $pdo->prepare($sql);
            $st->execute([$id_venta, $tipo_operacion, $proveedor, $punto_venta, $tipo_comprobante, $json]);

            $id_comprobante = self::obtener_id_por_venta($id_venta);
            if ($id_comprobante <= 0)
                return false;

            $sqlCola = "INSERT INTO fiscal_cola (id_comprobante, estado)
                        SELECT ?, 'PENDIENTE'
                        WHERE NOT EXISTS (
                            SELECT 1 FROM fiscal_cola
                            WHERE id_comprobante = ? AND estado IN ('PENDIENTE','EN_PROCESO')
                        )";
            $stCola = $pdo->prepare($sqlCola);
            $stCola->execute([$id_comprobante, $id_comprobante]);
            return true;
        } catch (Throwable $e) {
            registrar_log("FacturaFiscal::crear_pendiente_para_venta", $e->getMessage());
            return false;
        }
    }

    public static function estado_por_ventas(array $ids_venta): array {
        $estados = [];
        $ids = array_values(array_unique(array_filter(array_map("intval", $ids_venta), fn($id) => $id > 0)));
        if (count($ids) === 0)
            return $estados;
        $pdo = obtener_pdo();
        if ($pdo === null)
            return $estados;
        try {
            $placeholders = implode(", ", array_fill(0, count($ids), "?"));
            $sql = "SELECT id_venta, estado, cae, numero_comprobante, ultimo_error
                    FROM fiscal_comprobantes
                    WHERE id_venta IN ($placeholders)";
            $st = $pdo->prepare($sql);
            $st->execute($ids);
            foreach ($st->fetchAll() as $fila)
                $estados[(int)$fila["id_venta"]] = $fila;
        } catch (Throwable $e) {
            registrar_log("FacturaFiscal::estado_por_ventas", $e->getMessage());
        }
        return $estados;
    }

    public static function procesar_cola(int $limite = 10): array {
        $resumen = ["procesados" => 0, "aprobados" => 0, "pendientes" => 0, "errores" => 0];
        $pdo = obtener_pdo();
        if ($pdo === null)
            return $resumen;
        try {
            $sql = "SELECT q.id AS id_cola, c.id AS id_comprobante, c.payload_json
                    FROM fiscal_cola q
                    INNER JOIN fiscal_comprobantes c ON c.id = q.id_comprobante
                    WHERE q.estado IN ('PENDIENTE','ERROR')
                      AND (q.proximo_intento IS NULL OR q.proximo_intento <= NOW())
                      AND c.estado IN ('PENDIENTE','ERROR')
                    ORDER BY q.creado_en ASC
                    LIMIT ?";
            $st = $pdo->prepare($sql);
            $st->bindValue(1, max(1, $limite), PDO::PARAM_INT);
            $st->execute();
            $items = $st->fetchAll();
            $servicio = new ServicioFacturacionFiscal();
            foreach ($items as $item) {
                $resumen["procesados"]++;
                self::marcar_en_proceso((int)$item["id_cola"], (int)$item["id_comprobante"]);
                $payload = json_decode((string)$item["payload_json"], true);
                if (!is_array($payload))
                    $payload = [];
                $respuesta = $servicio->emitir($payload);
                if (($respuesta["ok"] ?? false) === true) {
                    self::marcar_aprobado((int)$item["id_cola"], (int)$item["id_comprobante"], $respuesta);
                    $resumen["aprobados"]++;
                } else {
                    self::marcar_error((int)$item["id_cola"], (int)$item["id_comprobante"], $respuesta);
                    if (($respuesta["transitorio"] ?? true) === true)
                        $resumen["pendientes"]++;
                    else
                        $resumen["errores"]++;
                }
            }
        } catch (Throwable $e) {
            registrar_log("FacturaFiscal::procesar_cola", $e->getMessage());
        }
        return $resumen;
    }

    private static function construir_payload_venta(int $id_venta, string $tipo_operacion, int $tipo_comprobante): array {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return [];
        Cliente::asegurar_columnas_fiscales($pdo);
        $sql = "SELECT v.id, v.fecha, v.total, c.nombre AS cliente_nombre, c.dni AS cliente_documento,
                       c.tipo_documento, c.condicion_iva, c.email
                FROM ventas v
                INNER JOIN clientes c ON c.id = v.id_cliente
                WHERE v.id = ? LIMIT 1";
        $st = $pdo->prepare($sql);
        $st->execute([$id_venta]);
        $venta = $st->fetch();
        if (!$venta)
            return [];
        $items = Venta::obtener_detalle($id_venta);
        $config = self::configuracion();
        $comprobante = $config["comprobante_defecto"] ?? [];
        $comprobante["tipo"] = $tipo_comprobante;
        return [
            "tipo_operacion" => $tipo_operacion,
            "venta" => [
                "id" => (int)$venta["id"],
                "fecha" => (string)$venta["fecha"],
                "total" => (float)$venta["total"],
            ],
            "emisor" => $config["empresa"] ?? [],
            "comprobante" => $comprobante,
            "receptor" => [
                "nombre" => (string)$venta["cliente_nombre"],
                "documento" => (string)($venta["cliente_documento"] ?? ""),
                "tipo_documento" => (string)($venta["tipo_documento"] ?? "DNI"),
                "condicion_iva" => (string)($venta["condicion_iva"] ?? ""),
                "email" => (string)($venta["email"] ?? ""),
            ],
            "items" => array_map(function(array $item): array {
                return [
                    "producto" => (string)$item["producto_nombre"],
                    "cantidad" => (float)$item["cantidad"],
                    "precio_unitario" => (float)$item["precio_unit"],
                    "descuento" => (float)$item["descuento"],
                    "subtotal" => (float)$item["subtotal"],
                ];
            }, $items),
        ];
    }

    private static function obtener_id_por_venta(int $id_venta): int {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return 0;
        $st = $pdo->prepare("SELECT id FROM fiscal_comprobantes WHERE id_venta = ? LIMIT 1");
        $st->execute([$id_venta]);
        $fila = $st->fetch();
        return $fila ? (int)$fila["id"] : 0;
    }

    private static function marcar_en_proceso(int $id_cola, int $id_comprobante): void {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        $pdo->prepare("UPDATE fiscal_cola SET estado = 'EN_PROCESO', actualizado_en = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id_cola]);
        $pdo->prepare("UPDATE fiscal_comprobantes SET estado = 'EN_PROCESO', actualizado_en = CURRENT_TIMESTAMP WHERE id = ?")->execute([$id_comprobante]);
    }

    private static function marcar_aprobado(int $id_cola, int $id_comprobante, array $respuesta): void {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        $json = json_encode($respuesta["respuesta"] ?? $respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $pdo->prepare("UPDATE fiscal_comprobantes
                       SET estado = 'APROBADO', cae = ?, cae_vencimiento = NULLIF(?, ''), numero_comprobante = NULLIF(?, 0), respuesta_json = ?, ultimo_error = NULL
                       WHERE id = ?")
            ->execute([
                (string)($respuesta["cae"] ?? ""),
                (string)($respuesta["cae_vencimiento"] ?? ""),
                (int)($respuesta["numero_comprobante"] ?? 0),
                $json,
                $id_comprobante,
            ]);
        $pdo->prepare("UPDATE fiscal_cola SET estado = 'FINALIZADO', ultimo_error = NULL WHERE id = ?")->execute([$id_cola]);
    }

    private static function marcar_error(int $id_cola, int $id_comprobante, array $respuesta): void {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        $error = (string)($respuesta["error"] ?? "Error fiscal no especificado.");
        $estado_comprobante = (($respuesta["transitorio"] ?? true) === true) ? "PENDIENTE" : "RECHAZADO";
        $estado_cola = (($respuesta["transitorio"] ?? true) === true) ? "ERROR" : "FINALIZADO";
        $proximo = (($respuesta["transitorio"] ?? true) === true) ? date("Y-m-d H:i:s", time() + 300) : null;
        $json = json_encode($respuesta["respuesta"] ?? $respuesta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $pdo->prepare("UPDATE fiscal_comprobantes
                       SET estado = ?, ultimo_error = ?, respuesta_json = ?, intentos = intentos + 1, proximo_intento = ?
                       WHERE id = ?")
            ->execute([$estado_comprobante, $error, $json, $proximo, $id_comprobante]);
        $pdo->prepare("UPDATE fiscal_cola
                       SET estado = ?, ultimo_error = ?, intentos = intentos + 1, proximo_intento = ?
                       WHERE id = ?")
            ->execute([$estado_cola, $error, $proximo, $id_cola]);
    }

    private static function configuracion(): array {
        return ConfiguracionSistema::obtener_configuracion_fiscal();
    }
}
