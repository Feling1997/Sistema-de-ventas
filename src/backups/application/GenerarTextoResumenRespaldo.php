<?php

declare(strict_types=1);

namespace Ventas\Backups\Application;

use Ventas\Backups\Domain\Repositorios\BackupRepository;

final class GenerarTextoResumenRespaldo
{
    public function __construct(private BackupRepository $repository)
    {
    }

    public function ejecutar(array $resumen): string
    {
        $resultado = $this->repository->generarTextoResumen($resumen);

        return $resultado;
    }
}
