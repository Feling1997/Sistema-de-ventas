<?php

declare(strict_types=1);

namespace Ventas\Sistema\Application;

use Illuminate\Support\Facades\File;
use PDO;
use Throwable;

final class CrearBackupSistema
{
    /**
     * @return array<string, mixed>
     */
    public function ejecutar(): array
    {
        $fecha = date('Ymd_His');
        $directorioBase = base_path('../backups');
        $directorio = $directorioBase . DIRECTORY_SEPARATOR . 'sistema_' . $fecha;
        File::ensureDirectoryExists($directorio);
        $bases = [];

        foreach (['sistema_core', 'sistema_ventas', 'sistema_reparaciones'] as $base) {
            $bases[$base] = $this->respaldarBase($base, $directorio);
        }

        $manifiesto = [
            'fecha' => $fecha,
            'version' => (string) config('sistema.version'),
            'directorio' => $directorio,
            'bases' => $bases,
        ];
        $rutaManifiesto = $directorioBase . DIRECTORY_SEPARATOR . $fecha . '.json';
        File::put($rutaManifiesto, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $manifiesto['manifiesto'] = $rutaManifiesto;

        return $manifiesto;
    }

    /**
     * @return array<string, mixed>
     */
    private function respaldarBase(string $base, string $directorio): array
    {
        $estado = 'OK';
        $mensaje = 'respaldo generado';
        $tablas = 0;
        $filas = 0;
        $archivo = $directorio . DIRECTORY_SEPARATOR . $base . '.sql';

        try {
            if (!$this->baseExiste($base)) {
                $estado = 'WARNING';
                $mensaje = 'base no existe';
                $archivo = null;
            } else {
                $filas = $this->generarDump($base, $archivo, $tablas);
            }
        } catch (Throwable $throwable) {
            $estado = 'ERROR';
            $mensaje = $throwable->getMessage();
            $archivo = null;
        }

        return [
            'estado' => $estado,
            'mensaje' => $mensaje,
            'archivo' => $archivo,
            'tablas' => $tablas,
            'filas' => $filas,
        ];
    }

    private function generarDump(string $base, string $archivo, int &$tablas): int
    {
        $pdo = $this->pdoBase($base);
        $filas = 0;
        File::put($archivo, "-- Backup {$base}\n-- Fecha " . date('c') . "\n\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        $consultaTablas = $pdo->query('SHOW TABLES');

        while (($tabla = $consultaTablas->fetchColumn()) !== false) {
            $nombreTabla = (string) $tabla;
            $tablas++;
            $this->agregarTabla($pdo, $archivo, $nombreTabla, $filas);
        }

        File::append($archivo, "\nSET FOREIGN_KEY_CHECKS=1;\n");

        return $filas;
    }

    private function agregarTabla(PDO $pdo, string $archivo, string $tabla, int &$filas): void
    {
        $identificador = $this->identificador($tabla);
        $crear = $pdo->query('SHOW CREATE TABLE ' . $identificador)->fetch(PDO::FETCH_ASSOC);
        $sqlCrear = is_array($crear) && isset($crear['Create Table']) ? (string) $crear['Create Table'] : '';
        File::append($archivo, 'DROP TABLE IF EXISTS ' . $identificador . ";\n" . $sqlCrear . ";\n\n");
        $consulta = $pdo->query('SELECT * FROM ' . $identificador);

        while (($fila = $consulta->fetch(PDO::FETCH_ASSOC)) !== false) {
            $columnas = array_map(fn (string $columna): string => $this->identificador($columna), array_keys($fila));
            $valores = array_map(fn (mixed $valor): string => $this->valorSql($pdo, $valor), array_values($fila));
            File::append($archivo, 'INSERT INTO ' . $identificador . ' (' . implode(', ', $columnas) . ') VALUES (' . implode(', ', $valores) . ");\n");
            $filas++;
        }

        File::append($archivo, "\n");
    }

    private function baseExiste(string $base): bool
    {
        $sentencia = $this->pdoServidor()->prepare('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?');
        $sentencia->execute([$base]);
        $existe = (bool) $sentencia->fetchColumn();

        return $existe;
    }

    private function pdoServidor(): PDO
    {
        $host = (string) env('DB_HOST', '127.0.0.1');
        $port = (string) env('DB_PORT', '3306');
        $usuario = (string) env('DB_USERNAME', 'root');
        $clave = (string) env('DB_PASSWORD', '');
        $pdo = new PDO('mysql:host=' . $host . ';port=' . $port . ';charset=utf8mb4', $usuario, $clave, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        return $pdo;
    }

    private function pdoBase(string $base): PDO
    {
        $host = (string) env('DB_HOST', '127.0.0.1');
        $port = (string) env('DB_PORT', '3306');
        $usuario = (string) env('DB_USERNAME', 'root');
        $clave = (string) env('DB_PASSWORD', '');
        $pdo = new PDO('mysql:host=' . $host . ';port=' . $port . ';dbname=' . $base . ';charset=utf8mb4', $usuario, $clave, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        return $pdo;
    }

    private function identificador(string $valor): string
    {
        $identificador = '`' . str_replace('`', '``', $valor) . '`';

        return $identificador;
    }

    private function valorSql(PDO $pdo, mixed $valor): string
    {
        $sql = 'NULL';

        if ($valor !== null) {
            $sql = $pdo->quote((string) $valor);
        }

        return $sql;
    }
}
