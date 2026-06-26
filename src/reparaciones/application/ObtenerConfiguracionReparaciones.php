<?php

declare(strict_types=1);

namespace Ventas\Reparaciones\Application;

use Ventas\Reparaciones\Domain\Repositorios\ConfiguracionReparacionesRepository;

final class ObtenerConfiguracionReparaciones
{
    public function __construct(private readonly ConfiguracionReparacionesRepository $configuracion)
    {
    }

    /**
     * @return array<string, string>
     */
    public function ejecutar(): array
    {
        $datos = $this->configuracion->obtenerTodo();

        return $datos;
    }
}
