<?php

declare(strict_types=1);

namespace Ventas\Backups\Application;

use Ventas\Backups\Domain\Repositorios\FilesystemRespaldoRepository;

final class GenerarRespaldoSistema
{
    public function __construct(private FilesystemRespaldoRepository $repository)
    {
    }

    public function ejecutar(): array
    {
        $resultado = $this->repository->generar();

        return $resultado;
    }
}
