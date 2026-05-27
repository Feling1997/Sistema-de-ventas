<?php
require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class UnidadMedida {
    public static function asegurar_tabla(): void {
        $pdo = obtener_pdo();
        if ($pdo === null)
            return;
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS unidades_medida (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(80) NOT NULL,
                abreviatura VARCHAR(20) NOT NULL,
                tipo VARCHAR(30) NOT NULL DEFAULT 'cantidad',
                decimales TINYINT UNSIGNED NOT NULL DEFAULT 0,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_unidades_abreviatura (abreviatura)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $base = [
                ["Kilogramo", "kg", "peso", 3],
                ["Gramo", "g", "peso", 0],
                ["Litro", "l", "volumen", 3],
                ["Mililitro", "ml", "volumen", 0],
                ["Metro", "m", "longitud", 2],
                ["Centimetro", "cm", "longitud", 0],
                ["Unidad", "u", "cantidad", 0],
                ["Caja", "cj", "cantidad", 0],
                ["Pack", "pack", "cantidad", 0],
                ["Docena", "doc", "cantidad", 0],
            ];
            $st = $pdo->prepare("INSERT INTO unidades_medida (nombre, abreviatura, tipo, decimales, activo)
                                 VALUES (?, ?, ?, ?, 1)
                                 ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo = VALUES(tipo), decimales = VALUES(decimales), activo = 1");
            foreach ($base as $u)
                $st->execute($u);
        } catch (Throwable $e) {
            registrar_log("UnidadMedida::asegurar_tabla", $e->getMessage());
        }
    }

    public static function listar(bool $solo_activas = true): array {
        self::asegurar_tabla();
        $lista = [];
        $pdo = obtener_pdo();
        if ($pdo !== null) {
            try {
                $sql = "SELECT id, nombre, abreviatura, tipo, decimales, activo FROM unidades_medida";
                if ($solo_activas)
                    $sql .= " WHERE activo = 1";
                $orden = "FIELD(abreviatura, 'kg', 'g', 'l', 'ml', 'm', 'cm', 'cj', 'doc', 'pack', 'u')";
                $sql .= " ORDER BY CASE WHEN $orden = 0 THEN 1 ELSE 0 END, $orden, nombre ASC";
                $st = $pdo->prepare($sql);
                $st->execute();
                $rows = $st->fetchAll();
                if (is_array($rows))
                    $lista = $rows;
            } catch (Throwable $e) {
                registrar_log("UnidadMedida::listar", $e->getMessage());
            }
        }
        return $lista;
    }

    public static function buscar_por_abreviatura(string $abreviatura): ?array {
        self::asegurar_tabla();
        $fila = null;
        $abbr = self::normalizar_abreviatura($abreviatura);
        $pdo = obtener_pdo();
        if ($pdo !== null && $abbr !== "") {
            try {
                $st = $pdo->prepare("SELECT id, nombre, abreviatura, tipo, decimales, activo FROM unidades_medida WHERE abreviatura = ? LIMIT 1");
                $st->execute([$abbr]);
                $r = $st->fetch();
                if ($r)
                    $fila = $r;
            } catch (Throwable $e) {
                registrar_log("UnidadMedida::buscar_por_abreviatura", $e->getMessage());
            }
        }
        return $fila;
    }

    public static function crear(string $nombre, string $abreviatura, string $tipo, int $decimales, int $activo = 1): bool {
        self::asegurar_tabla();
        $ok = false;
        $nombre = trim($nombre);
        $abreviatura = self::normalizar_abreviatura($abreviatura);
        $tipo = self::normalizar_tipo($tipo);
        $decimales = max(0, min(4, $decimales));
        $pdo = obtener_pdo();
        if ($pdo !== null && !texto_invalido($nombre) && $abreviatura !== "") {
            try {
                $st = $pdo->prepare("INSERT INTO unidades_medida (nombre, abreviatura, tipo, decimales, activo)
                                     VALUES (?, ?, ?, ?, ?)
                                     ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo = VALUES(tipo), decimales = VALUES(decimales), activo = VALUES(activo)");
                $ok = $st->execute([$nombre, $abreviatura, $tipo, $decimales, $activo]);
            } catch (Throwable $e) {
                registrar_log("UnidadMedida::crear", $e->getMessage());
            }
        }
        return $ok;
    }

    public static function crear_sin_duplicar(string $nombre, string $abreviatura, string $tipo, int $decimales, int $activo = 1): ?array {
        self::asegurar_tabla();
        $fila = null;
        $nombre = trim($nombre);
        $abreviatura = self::normalizar_abreviatura($abreviatura);
        $tipo = self::normalizar_tipo($tipo);
        $decimales = max(0, min(3, $decimales));
        $pdo = obtener_pdo();
        if ($pdo !== null && !texto_invalido($nombre) && $abreviatura !== "" && self::buscar_por_abreviatura($abreviatura) === null) {
            try {
                $st = $pdo->prepare("INSERT INTO unidades_medida (nombre, abreviatura, tipo, decimales, activo) VALUES (?, ?, ?, ?, ?)");
                if ($st->execute([$nombre, $abreviatura, $tipo, $decimales, $activo]))
                    $fila = self::buscar_por_abreviatura($abreviatura);
            } catch (Throwable $e) {
                registrar_log("UnidadMedida::crear_sin_duplicar", $e->getMessage());
            }
        }
        return $fila;
    }

    public static function asegurar_desde_form(string $unidad, array $datos): string {
        self::asegurar_tabla();
        $abbr = self::normalizar_abreviatura($unidad);
        if ($abbr === "")
            $abbr = "u";
        if (self::buscar_por_abreviatura($abbr) === null) {
            $nombre = trim((string)($datos["nombre"] ?? ""));
            $tipo = (string)($datos["tipo"] ?? "cantidad");
            $decimales = (int)($datos["decimales"] ?? 0);
            if (!texto_invalido($nombre))
                self::crear($nombre, $abbr, $tipo, $decimales, 1);
        }
        return $abbr;
    }

    public static function decimales(string $abreviatura, int $defecto = 3): int {
        $fila = self::buscar_por_abreviatura($abreviatura);
        if ($fila !== null)
            return (int)$fila["decimales"];
        return $defecto;
    }

    private static function normalizar_abreviatura(string $abreviatura): string {
        return strtolower(trim($abreviatura));
    }

    private static function normalizar_tipo(string $tipo): string {
        $tipo = strtolower(trim($tipo));
        return in_array($tipo, ["peso", "volumen", "longitud", "cantidad"], true) ? $tipo : "cantidad";
    }
}
