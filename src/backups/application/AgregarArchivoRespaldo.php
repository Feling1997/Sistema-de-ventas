<?php

declare(strict_types=1);

namespace Ventas\Backups\Application;

use PharData;
use Ventas\Backups\Domain\Repositorios\FilesystemRespaldoRepository;

final class AgregarArchivoRespaldo
{
    public function __construct(private FilesystemRespaldoRepository $repository)
    {
    }

    public function ejecutar(PharData $archivo, string $base, string $relativo): void
    {
        $this->repository->agregarArchivo($archivo, $base, $relativo);
    }
}
