<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Application;

use Ventas\Configuracion\Domain\Repositorios\ArchivoConfiguracionRepository;

final class GuardarArchivoConfiguracion
{
    public function __construct(private readonly ArchivoConfiguracionRepository $archivoConfiguracionRepository)
    {
    }

    public function ejecutar(string $campo, string $actual, string $nombreBase): string
    {
        $resultado = $this->archivoConfiguracionRepository->guardarArchivo($campo, $actual, $nombreBase);

        return $resultado;
    }
}
