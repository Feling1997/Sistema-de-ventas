<?php

declare(strict_types=1);

namespace Ventas\Importacion\Application;

use Ventas\Importacion\Domain\Repositorios\ImportacionExcelRepository;

final class AnalizarImportacionProductos
{
    public function __construct(private readonly ImportacionExcelRepository $repository)
    {
    }

    public function ejecutar(string $ruta, int $indiceHoja): array
    {
        $resultado = $this->repository->analizar($ruta, $indiceHoja);

        return $resultado;
    }
}
