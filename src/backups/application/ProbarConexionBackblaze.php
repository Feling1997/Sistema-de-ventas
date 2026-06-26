<?php

declare(strict_types=1);

namespace Ventas\Backups\Application;

use Ventas\Backups\Domain\Repositorios\BackblazeStorageRepository;

final class ProbarConexionBackblaze
{
    public function __construct(private BackblazeStorageRepository $repository)
    {
    }

    public function ejecutar(array $config): array
    {
        $resultado = $this->repository->probar($config);

        return $resultado;
    }
}
