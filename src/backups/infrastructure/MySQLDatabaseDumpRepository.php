<?php

declare(strict_types=1);

namespace Ventas\Backups\Infrastructure;

use PDO;
use Ventas\Backups\Domain\Repositorios\DatabaseDumpRepository;

final class MySQLDatabaseDumpRepository implements DatabaseDumpRepository
{
    public function __construct(private ?PDO $pdo)
    {
    }

    public function generarDump(): string
    {
        $resultado = "-- No se pudo conectar a MySQL.\n";

        if ($this->pdo instanceof PDO) {
            $resultado = "-- Respaldo MySQL sistema_ventas\n";
            $resultado .= "-- Generado: " . date("Y-m-d H:i:s") . "\n\n";
            $resultado .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            $tablas = $this->pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($tablas as $tabla) {
                $tabla = (string)$tabla;
                $tablaEscapada = str_replace("`", "``", $tabla);
                $create = $this->pdo->query("SHOW CREATE TABLE `" . $tablaEscapada . "`")->fetch(PDO::FETCH_ASSOC);
                $ddl = (string)($create["Create Table"] ?? array_values($create)[1] ?? "");
                $resultado .= "DROP TABLE IF EXISTS `" . $tablaEscapada . "`;\n";
                $resultado .= $ddl . ";\n\n";

                $stmt = $this->pdo->query("SELECT * FROM `" . $tablaEscapada . "`");
                while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $columnas = array_map(fn ($c): string => "`" . str_replace("`", "``", (string)$c) . "`", array_keys($fila));
                    $valores = array_map(fn ($v): string => $v === null ? "NULL" : $this->pdo->quote((string)$v), array_values($fila));
                    $resultado .= "INSERT INTO `" . $tablaEscapada . "` (" . implode(",", $columnas) . ") VALUES (" . implode(",", $valores) . ");\n";
                }
                $resultado .= "\n";
            }
            $resultado .= "SET FOREIGN_KEY_CHECKS=1;\n";
        }

        return $resultado;
    }
}
