<?php

declare(strict_types=1);

namespace Ventas\Backups\Application;

use Ventas\Backups\Domain\Repositorios\BackblazeStorageRepository;

final class SubirRespaldoBackblaze
{
    public function __construct(private BackblazeStorageRepository $repository)
    {
    }

    public function ejecutar(string $ruta, array $config): array
    {
        $resultado = $this->repository->subir($ruta, $config);

        return $resultado;
    }
}
