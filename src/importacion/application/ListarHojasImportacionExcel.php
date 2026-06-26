<?php

declare(strict_types=1);

namespace Ventas\Importacion\Application;

use Ventas\Importacion\Domain\Repositorios\ImportacionExcelRepository;

final class ListarHojasImportacionExcel
{
    public function __construct(private readonly ImportacionExcelRepository $repository)
    {
    }

    public function ejecutar(string $ruta): array
    {
        $resultado = $this->repository->hojas($ruta);

        return $resultado;
    }
}
