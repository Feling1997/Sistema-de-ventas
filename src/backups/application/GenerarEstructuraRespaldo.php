<?php

declare(strict_types=1);

namespace Ventas\Backups\Application;

use Ventas\Backups\Domain\Repositorios\BackupRepository;

final class GenerarEstructuraRespaldo
{
    public function __construct(private BackupRepository $repository)
    {
    }

    public function ejecutar(): string
    {
        $resultado = $this->repository->generarEstructura();

        return $resultado;
    }
}
