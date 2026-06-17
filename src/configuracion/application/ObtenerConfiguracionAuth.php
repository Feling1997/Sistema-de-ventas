<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Application;

use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;

final class ObtenerConfiguracionAuth
{
    public function __construct(private readonly ConfiguracionRepository $configuracionRepository)
    {
    }

    public function ejecutar(): array
    {
        $configuracion = $this->configuracionRepository->obtenerAuth();

        return $configuracion;
    }
}
