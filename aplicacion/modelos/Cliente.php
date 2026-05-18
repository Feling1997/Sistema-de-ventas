<?php

require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";
require_once __DIR__ . "/ListaPrecio.php";

class Cliente {

    public static function listar_todos(): array {
        $lista = [];
        $pdo = obtener_pdo();

        if ($pdo !== null) {
            try {
                self::asegurar_columnas_fiscales($pdo);
                $sql = "SELECT c.id, c.nombre, c.dni, c.tipo_documento, c.condicion_iva, c.email, c.id_lista_precio, lp.nombre AS lista_precio_nombre, c.telefono, c.direccion, c.creado_en FROM clientes c LEFT JOIN listas_precios lp ON lp.id = c.id_lista_precio ORDER BY c.id DESC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("Cliente::listar_todos", $e->getMessage());
            }
        }

        return $lista;
    }

    public static function buscar_por_id(int $id): ?array {
        $fila = null;
        $pdo = obtener_pdo();

        if ($pdo !== null) {
            try {
                self::asegurar_columnas_fiscales($pdo);
                $sql = "SELECT c.id, c.nombre, c.dni, c.tipo_documento, c.condicion_iva, c.email, c.id_lista_precio, lp.nombre AS lista_precio_nombre, c.telefono, c.direccion, c.creado_en
                        FROM clientes c
                        LEFT JOIN listas_precios lp ON lp.id = c.id_lista_precio
                        WHERE c.id = ?
                        LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id]);
                $r = $st->fetch();
                if ($r)
                    $fila = $r;
            } catch (Throwable $e) {
                registrar_log("Cliente::buscar_por_id", $e->getMessage());
            }
        }

        return $fila;
    }


    public static function dni_existe(string $dni, int $excepto_id = 0): bool {
        $existe = false;
        $pdo = obtener_pdo();
        $dni_limpio = trim($dni);
        if ($pdo !== null && $dni_limpio !== "") {
            try {
                self::asegurar_columnas_fiscales($pdo);
                $sql = "SELECT id FROM clientes WHERE dni = ? AND id <> ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$dni_limpio, $excepto_id]);
                $r = $st->fetch();
                if ($r)
                    $existe = true;
            } catch (Throwable $e) {
                registrar_log("Cliente::dni_existe", $e->getMessage());
            }
        }
        return $existe;
    }

    public static function crear(string $nombre, ?string $dni, ?string $telefono, ?string $direccion, string $tipo_documento = "DNI", string $condicion_iva = "Consumidor Final", ?string $email = null, ?int $id_lista_precio = null): bool {
        $ok = false;
        $pdo = obtener_pdo();

        if ($pdo !== null) {
            try {
                self::asegurar_columnas_fiscales($pdo);
                $sql = "INSERT INTO clientes (nombre, dni, telefono, direccion, tipo_documento, condicion_iva, email, id_lista_precio) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$nombre, $dni, $telefono, $direccion, $tipo_documento, $condicion_iva, $email, $id_lista_precio]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Cliente::crear", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function actualizar(int $id, string $nombre, ?string $dni, ?string $telefono, ?string $direccion, string $tipo_documento = "DNI", string $condicion_iva = "Consumidor Final", ?string $email = null, ?int $id_lista_precio = null): bool {
        $ok = false;
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                self::asegurar_columnas_fiscales($pdo);
                $sql = "UPDATE clientes SET nombre = ?, dni = ?, telefono = ?, direccion = ?, tipo_documento = ?, condicion_iva = ?, email = ?, id_lista_precio = ? WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$nombre, $dni, $telefono, $direccion, $tipo_documento, $condicion_iva, $email, $id_lista_precio, $id]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Cliente::actualizar", $e->getMessage());
            }
        }
        return $ok;
    }


    public static function esta_relacionado_con_ventas(int $id_cliente): bool {
        $rel = false;
        $pdo = obtener_pdo();

        if ($pdo !== null) {
            try {
                $sql = "SELECT id FROM ventas WHERE id_cliente = ? LIMIT 1";
                $st = $pdo->prepare($sql);
                $st->execute([$id_cliente]);
                $r = $st->fetch();
                if ($r)
                    $rel = true;
            } catch (Throwable $e) {
                registrar_log("Cliente::esta_relacionado_con_ventas", $e->getMessage());
            }
        }
        return $rel;
    }

    public static function eliminar(int $id): bool {
        $ok = false;
        $pdo = obtener_pdo();

        if ($pdo !== null) {
            try {
                $sql = "DELETE FROM clientes WHERE id = ?";
                $st = $pdo->prepare($sql);
                $ok = $st->execute([$id]);
            } catch (Throwable $e) {
                $ok = false;
                registrar_log("Cliente::eliminar", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function validar_datos_factura_a(array $cliente): string {
        $error = "";
        $tipo_documento = strtoupper(trim((string)($cliente["tipo_documento"] ?? "")));
        $documento = preg_replace('/\D+/', '', (string)($cliente["dni"] ?? ""));
        $condicion_iva = trim((string)($cliente["condicion_iva"] ?? ""));
        if ($tipo_documento !== "CUIT")
            $error = "Para Factura A el cliente debe tener tipo de documento CUIT.";
        else if (strlen($documento) !== 11)
            $error = "Para Factura A cargá un CUIT válido de 11 dígitos.";
        else if ($condicion_iva !== "Responsable Inscripto")
            $error = "Para Factura A el cliente debe ser Responsable Inscripto.";
        return $error;
    }

    public static function asegurar_columnas_fiscales(PDO $pdo): void {
        self::asegurar_columna($pdo, "tipo_documento", "ALTER TABLE clientes ADD COLUMN tipo_documento VARCHAR(20) NOT NULL DEFAULT 'DNI' AFTER dni");
        self::asegurar_columna($pdo, "condicion_iva", "ALTER TABLE clientes ADD COLUMN condicion_iva VARCHAR(40) NOT NULL DEFAULT 'Consumidor Final' AFTER tipo_documento");
        self::asegurar_columna($pdo, "email", "ALTER TABLE clientes ADD COLUMN email VARCHAR(120) NULL AFTER condicion_iva");
        ListaPrecio::asegurar_tablas();
    }

    private static function asegurar_columna(PDO $pdo, string $columna, string $sqlAlter): void {
        $st = $pdo->prepare("SHOW COLUMNS FROM clientes LIKE ?");
        $st->execute([$columna]);
        if (!$st->fetch())
            $pdo->exec($sqlAlter);
    }
}
