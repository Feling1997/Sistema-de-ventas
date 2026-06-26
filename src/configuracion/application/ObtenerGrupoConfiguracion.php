<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Application;

use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;

final class ObtenerGrupoConfiguracion
{
    public function __construct(private readonly ConfiguracionRepository $configuracionRepository)
    {
    }

    /**
     * @return array<string, string>
     */
    public function ejecutar(string $grupo): array
    {
        $configuracion = $this->configuracionRepository->obtenerGrupo($grupo);

        return $configuracion;
    }
}
