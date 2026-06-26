<?php

declare(strict_types=1);

namespace Ventas\Backups\Application;

use Ventas\Backups\Domain\Repositorios\DatabaseDumpRepository;

final class GenerarDumpMysqlRespaldo
{
    public function __construct(private DatabaseDumpRepository $repository)
    {
    }

    public function ejecutar(): string
    {
        $resultado = $this->repository->generarDump();

        return $resultado;
    }
}
