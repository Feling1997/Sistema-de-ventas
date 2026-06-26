<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Application;

use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;

final class GuardarConfiguracion
{
    public function __construct(private readonly ConfiguracionRepository $configuracionRepository)
    {
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function ejecutar(array $datos): bool
    {
        $resultado = $this->configuracionRepository->guardar($datos);

        return $resultado;
    }
}
