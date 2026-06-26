<?php

declare(strict_types=1);

namespace Ventas\Backups\Application;

use Ventas\Backups\Domain\Repositorios\BackupRepository;

final class GenerarResumenRespaldo
{
    public function __construct(private BackupRepository $repository)
    {
    }

    public function ejecutar(): array
    {
        $resultado = $this->repository->generarResumen();

        return $resultado;
    }
}
