<?php

declare(strict_types=1);

namespace Ventas\Backups\Application;

use Ventas\Backups\Domain\Repositorios\FilesystemRespaldoRepository;

final class CopiarRespaldoLocal
{
    public function __construct(private FilesystemRespaldoRepository $repository)
    {
    }

    public function ejecutar(string $origen, string $destino): array
    {
        $resultado = $this->repository->copiarA($origen, $destino);

        return $resultado;
    }
}
