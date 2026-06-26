<?php

declare(strict_types=1);

namespace Ventas\Importacion\Domain\Repositorios;

interface ImportacionExcelRepository
{
    public function hojas(string $ruta): array;

    public function analizar(string $ruta, int $indiceHoja): array;
}
