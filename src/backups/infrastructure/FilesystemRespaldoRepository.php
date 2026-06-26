<?php

declare(strict_types=1);

namespace Ventas\Backups\Infrastructure;

use FilesystemIterator;
use Phar;
use PharData;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use Ventas\Backups\Domain\Repositorios\BackupRepository;
use Ventas\Backups\Domain\Repositorios\DatabaseDumpRepository;
use Ventas\Backups\Domain\Repositorios\FilesystemRespaldoRepository as FilesystemRespaldoRepositoryContract;

final class FilesystemRespaldoRepository implements FilesystemRespaldoRepositoryContract
{
    public function __construct(
        private BackupRepository $backupRepository,
        private DatabaseDumpRepository $databaseDumpRepository
    )
    {
    }

    public function generar(): array
    {
        $resultado = ["ok" => false, "mensaje" => "PHP no tiene PharData disponible para crear respaldos."];
        $tar = "";
        $gz = "";

        if (class_exists("PharData")) {
            $base = realpath(__DIR__ . "/../../..");
            if ($base === false) {
                $resultado = ["ok" => false, "mensaje" => "No se pudo ubicar la carpeta del sistema."];
            } else {
                $carpeta = $base . DIRECTORY_SEPARATOR . "respaldos";
                if (!is_dir($carpeta) && !@mkdir($carpeta, 0777, true)) {
                    $resultado = ["ok" => false, "mensaje" => "No se pudo crear la carpeta de respaldos."];
                } else {
                    $marca = date("Ymd_His");
                    $nombre = "respaldo_ventas_reparaciones_" . $marca;
                    $tar = $carpeta . DIRECTORY_SEPARATOR . $nombre . ".tar";
                    $gz = $tar . ".gz";
                    @unlink($tar);
                    @unlink($gz);

                    try {
                        $archivo = new PharData($tar);
                        $resumen = $this->backupRepository->generarResumen();
                        $archivo->addFromString("LEEME_RESPALDO.txt", $this->backupRepository->generarTextoResumen($resumen));
                        $archivo->addFromString("ventas_mysql.sql", $this->databaseDumpRepository->generarDump());
                        $archivo->addFromString("resumen.json", (string)json_encode($resumen, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        $archivo->addFromString("estructura_respaldo.txt", $this->backupRepository->generarEstructura());

                        $this->agregarCarpeta($archivo, $base, "almacenamiento");
                        $this->agregarCarpeta($archivo, $base, "configuraciones");
                        $this->agregarCarpeta($archivo, $base, "aplicacion");
                        $this->agregarCarpeta($archivo, $base, "publico");
                        $this->agregarArchivo($archivo, $base, "reparaciones_python/reparaciones.db");
                        $this->agregarArchivo($archivo, $base, "reparaciones_python/comercio_config.json");
                        $this->agregarArchivo($archivo, $base, "reparaciones_python/app.py");
                        $this->agregarArchivo($archivo, $base, "reparaciones_python/database.py");
                        $this->agregarArchivo($archivo, $base, "reparaciones_python/modelos.py");
                        $this->agregarArchivo($archivo, $base, "reparaciones_python/repositorio.py");
                        $this->agregarArchivo($archivo, $base, "reparaciones_python/tickets.py");
                        $this->agregarArchivo($archivo, $base, "reparaciones_python/ui.py");
                        $this->agregarArchivo($archivo, $base, "reparaciones_python/web_app.py");
                        $this->agregarCarpeta($archivo, $base, "reparaciones_python/tickets");
                        $this->agregarArchivo($archivo, $base, "composer.json");
                        $this->agregarArchivo($archivo, $base, "composer.lock");

                        $archivo->compress(Phar::GZ);
                        unset($archivo);
                        @unlink($tar);

                        if (!is_file($gz)) {
                            $resultado = ["ok" => false, "mensaje" => "No se pudo comprimir el respaldo."];
                        } else {
                            $resultado = [
                                "ok" => true,
                                "ruta" => $gz,
                                "nombre" => basename($gz),
                                "mensaje" => "Respaldo generado correctamente.",
                            ];
                        }
                    } catch (Throwable $e) {
                        if (function_exists("registrar_log")) {
                            registrar_log("RespaldoSistema", $e->getMessage());
                        }
                        @unlink($tar);
                        @unlink($gz);
                        $resultado = ["ok" => false, "mensaje" => "No se pudo generar el respaldo: " . $e->getMessage()];
                    }
                }
            }
        }

        return $resultado;
    }

    public function copiarA(string $origen, string $destino): array
    {
        $resultado = ["ok" => false, "mensaje" => "No se encontro el respaldo generado."];

        if (is_file($origen)) {
            $destino = trim($destino);
            if ($destino === "") {
                $resultado = ["ok" => false, "mensaje" => "Indica una carpeta de destino."];
            } else {
                $destino = rtrim($destino, "\\/");
                if (!is_dir($destino) && !@mkdir($destino, 0777, true)) {
                    $resultado = ["ok" => false, "mensaje" => "No se pudo crear o acceder a la carpeta destino."];
                } else {
                    $final = $destino . DIRECTORY_SEPARATOR . basename($origen);
                    if (!@copy($origen, $final)) {
                        $resultado = ["ok" => false, "mensaje" => "No se pudo copiar el respaldo. Revisa permisos o que el pendrive este conectado."];
                    } else {
                        $resultado = ["ok" => true, "mensaje" => "Respaldo copiado correctamente.", "ruta" => $final];
                    }
                }
            }
        }

        return $resultado;
    }

    public function agregarArchivo(PharData $archivo, string $base, string $relativo): void
    {
        $ruta = $base . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativo);
        if (is_file($ruta)) {
            $archivo->addFile($ruta, $relativo);
        }
    }

    public function agregarCarpeta(PharData $archivo, string $base, string $relativo): void
    {
        $ruta = $base . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativo);
        if (is_dir($ruta)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ruta, FilesystemIterator::SKIP_DOTS));
            foreach ($it as $item) {
                if ($item->isFile()) {
                    $real = $item->getPathname();
                    $local = str_replace(DIRECTORY_SEPARATOR, "/", substr($real, strlen($base) + 1));
                    $archivo->addFile($real, $local);
                }
            }
        }
    }
}
