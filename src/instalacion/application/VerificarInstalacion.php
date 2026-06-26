<?php

declare(strict_types=1);

namespace Ventas\Instalacion\Application;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use PDO;
use Throwable;

final class VerificarInstalacion
{
    /**
     * @return array<string, mixed>
     */
    public function ejecutar(bool $preparar = false): array
    {
        $resultado = [
            'php' => $this->php(),
            'extensiones' => $this->extensiones(),
            'mariadb' => $this->mariadb(),
            'storage' => $this->storage(),
            'bases' => $preparar ? $this->crearBases() : $this->bases(),
            'migraciones' => $preparar ? $this->migrar() : ['estado' => 'PENDIENTE', 'mensaje' => 'No ejecutado'],
            'modo' => config('reparaciones.modo', 'laravel'),
        ];

        return $resultado;
    }

    /**
     * @return array<string, mixed>
     */
    private function php(): array
    {
        $datos = [
            'estado' => version_compare(PHP_VERSION, '8.2.0', '>=') ? 'OK' : 'WARNING',
            'version' => PHP_VERSION,
        ];

        return $datos;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function extensiones(): array
    {
        $requeridas = ['pdo_mysql', 'mbstring', 'gd', 'fileinfo'];
        $datos = [];

        foreach ($requeridas as $extension) {
            $datos[$extension] = [
                'estado' => extension_loaded($extension) ? 'OK' : 'ERROR',
            ];
        }

        return $datos;
    }

    /**
     * @return array<string, string>
     */
    private function mariadb(): array
    {
        $estado = 'OK';
        $mensaje = 'conexion disponible';

        try {
            $this->pdoServidor();
        } catch (Throwable $throwable) {
            $estado = 'ERROR';
            $mensaje = $throwable->getMessage();
        }

        return ['estado' => $estado, 'mensaje' => $mensaje];
    }

    /**
     * @return array<string, string>
     */
    private function storage(): array
    {
        $ruta = storage_path('app');
        $estado = File::isWritable($ruta) ? 'OK' : 'ERROR';

        return ['estado' => $estado, 'ruta' => $ruta];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function bases(): array
    {
        $bases = ['sistema_core', 'sistema_ventas', 'sistema_reparaciones'];
        $datos = [];

        foreach ($bases as $base) {
            $datos[$base] = ['estado' => $this->baseExiste($base) ? 'OK' : 'PENDIENTE'];
        }

        return $datos;
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function crearBases(): array
    {
        $bases = ['sistema_core', 'sistema_ventas', 'sistema_reparaciones'];
        $datos = [];

        foreach ($bases as $base) {
            $existia = $this->baseExiste($base);
            $this->pdoServidor()->exec('CREATE DATABASE IF NOT EXISTS `' . $base . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $datos[$base] = ['estado' => 'OK', 'mensaje' => $existia ? 'base ya existia' : 'base creada'];
        }

        return $datos;
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

    /**
     * @return array<string, mixed>
     */
    private function migrar(): array
    {
        $pathsCore = ['database/migrations/2026_06_20_000001_create_core_contactos_table.php'];
        $pathsReparaciones = [
            'database/migrations/2026_06_21_000001_create_reparaciones_estados_table.php',
            'database/migrations/2026_06_21_000002_create_reparaciones_equipos_table.php',
            'database/migrations/2026_06_21_000003_create_reparaciones_table.php',
            'database/migrations/2026_06_21_000004_create_reparaciones_adjuntos_table.php',
            'database/migrations/2026_06_21_000005_create_reparaciones_tickets_table.php',
            'database/migrations/2026_06_22_000001_create_configuracion_reparaciones_table.php',
            'database/migrations/2026_06_22_000002_create_reparaciones_auditoria_table.php',
            'database/migrations/2026_06_22_000003_add_production_indexes_reparaciones.php',
            'database/migrations/2026_06_22_000004_add_miniatura_to_reparaciones_adjuntos_table.php',
        ];
        $core = $this->migrarPaths('sistema_core', $pathsCore);
        $reparaciones = $this->migrarPaths('sistema_reparaciones', $pathsReparaciones);
        $datos = ['estado' => 'OK', 'core' => $core, 'reparaciones' => $reparaciones];

        return $datos;
    }

    /**
     * @param array<int, string> $paths
     * @return array<int, array<string, mixed>>
     */
    private function migrarPaths(string $database, array $paths): array
    {
        $datos = [];

        foreach ($paths as $path) {
            $codigo = Artisan::call('migrate', ['--database' => $database, '--path' => $path, '--force' => true]);
            $datos[] = ['path' => $path, 'codigo' => $codigo];
        }

        return $datos;
    }

}
