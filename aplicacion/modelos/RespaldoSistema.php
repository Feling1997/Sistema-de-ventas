<?php

require_once __DIR__ . "/../../configuraciones/base_datos.php";
require_once __DIR__ . "/../../configuraciones/ayudas.php";

class RespaldoSistema {
    public static function generar(): array {
        if (!class_exists("PharData"))
            return ["ok" => false, "mensaje" => "PHP no tiene PharData disponible para crear respaldos."];

        $base = realpath(__DIR__ . "/../..");
        if ($base === false)
            return ["ok" => false, "mensaje" => "No se pudo ubicar la carpeta del sistema."];

        $carpeta = $base . DIRECTORY_SEPARATOR . "respaldos";
        if (!is_dir($carpeta) && !@mkdir($carpeta, 0777, true))
            return ["ok" => false, "mensaje" => "No se pudo crear la carpeta de respaldos."];

        $marca = date("Ymd_His");
        $nombre = "respaldo_ventas_reparaciones_" . $marca;
        $tar = $carpeta . DIRECTORY_SEPARATOR . $nombre . ".tar";
        $gz = $tar . ".gz";
        @unlink($tar);
        @unlink($gz);

        try {
            $archivo = new PharData($tar);
            $resumen = self::resumen();
            $archivo->addFromString("LEEME_RESPALDO.txt", self::textoResumen($resumen));
            $archivo->addFromString("ventas_mysql.sql", self::dumpMysql());
            $archivo->addFromString("resumen.json", json_encode($resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $archivo->addFromString("estructura_respaldo.txt", self::estructuraRespaldo());

            self::agregarCarpeta($archivo, $base, "almacenamiento");
            self::agregarCarpeta($archivo, $base, "configuraciones");
            self::agregarCarpeta($archivo, $base, "aplicacion");
            self::agregarCarpeta($archivo, $base, "publico");
            self::agregarArchivo($archivo, $base, "reparaciones_python/reparaciones.db");
            self::agregarArchivo($archivo, $base, "reparaciones_python/comercio_config.json");
            self::agregarArchivo($archivo, $base, "reparaciones_python/app.py");
            self::agregarArchivo($archivo, $base, "reparaciones_python/database.py");
            self::agregarArchivo($archivo, $base, "reparaciones_python/modelos.py");
            self::agregarArchivo($archivo, $base, "reparaciones_python/repositorio.py");
            self::agregarArchivo($archivo, $base, "reparaciones_python/tickets.py");
            self::agregarArchivo($archivo, $base, "reparaciones_python/ui.py");
            self::agregarArchivo($archivo, $base, "reparaciones_python/web_app.py");
            self::agregarCarpeta($archivo, $base, "reparaciones_python/tickets");
            self::agregarArchivo($archivo, $base, "composer.json");
            self::agregarArchivo($archivo, $base, "composer.lock");

            $archivo->compress(Phar::GZ);
            unset($archivo);
            @unlink($tar);

            if (!is_file($gz))
                return ["ok" => false, "mensaje" => "No se pudo comprimir el respaldo."];

            return [
                "ok" => true,
                "ruta" => $gz,
                "nombre" => basename($gz),
                "mensaje" => "Respaldo generado correctamente.",
            ];
        } catch (Throwable $e) {
            registrar_log("RespaldoSistema", $e->getMessage());
            @unlink($tar);
            @unlink($gz);
            return ["ok" => false, "mensaje" => "No se pudo generar el respaldo: " . $e->getMessage()];
        }
    }

    public static function copiarA(string $origen, string $destino): array {
        if (!is_file($origen))
            return ["ok" => false, "mensaje" => "No se encontro el respaldo generado."];
        $destino = trim($destino);
        if ($destino === "")
            return ["ok" => false, "mensaje" => "Indica una carpeta de destino."];
        $destino = rtrim($destino, "\\/");
        if (!is_dir($destino) && !@mkdir($destino, 0777, true))
            return ["ok" => false, "mensaje" => "No se pudo crear o acceder a la carpeta destino."];
        $final = $destino . DIRECTORY_SEPARATOR . basename($origen);
        if (!@copy($origen, $final))
            return ["ok" => false, "mensaje" => "No se pudo copiar el respaldo. Revisa permisos o que el pendrive este conectado."];
        return ["ok" => true, "mensaje" => "Respaldo copiado correctamente.", "ruta" => $final];
    }

    private static function dumpMysql(): string {
        $pdo = obtener_pdo();
        if (!$pdo)
            return "-- No se pudo conectar a MySQL.\n";

        $salida = "-- Respaldo MySQL sistema_ventas\n";
        $salida .= "-- Generado: " . date("Y-m-d H:i:s") . "\n\n";
        $salida .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        $tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tablas as $tabla) {
            $tabla = (string)$tabla;
            $create = $pdo->query("SHOW CREATE TABLE `" . str_replace("`", "``", $tabla) . "`")->fetch(PDO::FETCH_ASSOC);
            $ddl = (string)($create["Create Table"] ?? array_values($create)[1] ?? "");
            $salida .= "DROP TABLE IF EXISTS `" . str_replace("`", "``", $tabla) . "`;\n";
            $salida .= $ddl . ";\n\n";

            $stmt = $pdo->query("SELECT * FROM `" . str_replace("`", "``", $tabla) . "`");
            while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $columnas = array_map(fn($c) => "`" . str_replace("`", "``", (string)$c) . "`", array_keys($fila));
                $valores = array_map(fn($v) => $v === null ? "NULL" : $pdo->quote((string)$v), array_values($fila));
                $salida .= "INSERT INTO `" . str_replace("`", "``", $tabla) . "` (" . implode(",", $columnas) . ") VALUES (" . implode(",", $valores) . ");\n";
            }
            $salida .= "\n";
        }
        $salida .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $salida;
    }

    private static function resumen(): array {
        $resumen = [
            "generado" => date("Y-m-d H:i:s"),
            "ventas_mysql" => [],
            "reparaciones_sqlite" => [],
        ];

        $pdo = obtener_pdo();
        if ($pdo) {
            foreach ($pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $tabla) {
                $tabla = (string)$tabla;
                $stmt = $pdo->query("SELECT COUNT(*) FROM `" . str_replace("`", "``", $tabla) . "`");
                $resumen["ventas_mysql"][$tabla] = (int)$stmt->fetchColumn();
            }
        }

        $sqlite = __DIR__ . "/../../reparaciones_python/reparaciones.db";
        if (is_file($sqlite)) {
            try {
                $db = new PDO("sqlite:" . $sqlite);
                $tablas = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tablas as $tabla) {
                    $tabla = (string)$tabla;
                    $stmt = $db->query("SELECT COUNT(*) FROM \"" . str_replace("\"", "\"\"", $tabla) . "\"");
                    $resumen["reparaciones_sqlite"][$tabla] = (int)$stmt->fetchColumn();
                }
            } catch (Throwable $e) {
                $resumen["reparaciones_sqlite_error"] = $e->getMessage();
            }
        }

        return $resumen;
    }

    private static function textoResumen(array $resumen): string {
        $lineas = [];
        $lineas[] = "Respaldo de Ventas y Reparaciones";
        $lineas[] = "Generado: " . (string)($resumen["generado"] ?? "");
        $lineas[] = "";
        $lineas[] = "Contenido importante:";
        $lineas[] = "- ventas_mysql.sql: base MySQL de Ventas.";
        $lineas[] = "- reparaciones_python/reparaciones.db: base SQLite de Reparaciones.";
        $lineas[] = "- configuraciones y datos del comercio.";
        $lineas[] = "- tickets de Reparaciones e imagenes usadas por el sistema.";
        $lineas[] = "- archivos del programa necesarios para reconstruir la instalacion.";
        $lineas[] = "";
        $lineas[] = "Resumen MySQL:";
        foreach (($resumen["ventas_mysql"] ?? []) as $tabla => $cantidad)
            $lineas[] = "- " . $tabla . ": " . $cantidad;
        $lineas[] = "";
        $lineas[] = "Resumen Reparaciones:";
        foreach (($resumen["reparaciones_sqlite"] ?? []) as $tabla => $cantidad)
            $lineas[] = "- " . $tabla . ": " . $cantidad;
        return implode("\n", $lineas) . "\n";
    }

    private static function estructuraRespaldo(): string {
        return "Este respaldo contiene datos y archivos esenciales del sistema.\n"
            . "Para recuperar Ventas: restaurar ventas_mysql.sql en MySQL y copiar configuraciones/almacenamiento/publico.\n"
            . "Para recuperar Reparaciones: copiar reparaciones_python/reparaciones.db, comercio_config.json y tickets.\n";
    }

    private static function agregarArchivo(PharData $archivo, string $base, string $relativo): void {
        $ruta = $base . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativo);
        if (is_file($ruta))
            $archivo->addFile($ruta, $relativo);
    }

    private static function agregarCarpeta(PharData $archivo, string $base, string $relativo): void {
        $ruta = $base . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativo);
        if (!is_dir($ruta))
            return;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ruta, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $item) {
            if (!$item->isFile())
                continue;
            $real = $item->getPathname();
            $local = str_replace(DIRECTORY_SEPARATOR, "/", substr($real, strlen($base) + 1));
            $archivo->addFile($real, $local);
        }
    }
}
