<?php

declare(strict_types=1);

namespace Ventas\Backups\Application;

use Ventas\Backups\Domain\Repositorios\BackblazeStorageRepository;

final class VerificarBackblazeConfigurado
{
    public function __construct(private BackblazeStorageRepository $repository)
    {
    }

    public function ejecutar(array $config): bool
    {
        $resultado = $this->repository->configurado($config);

        return $resultado;
    }
}
