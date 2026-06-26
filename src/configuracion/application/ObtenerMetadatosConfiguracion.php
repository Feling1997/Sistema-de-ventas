<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Application;

use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;

final class ObtenerMetadatosConfiguracion
{
    public function __construct(private readonly ConfiguracionRepository $configuracionRepository)
    {
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function ejecutar(): array
    {
        $metadatos = $this->configuracionRepository->obtenerMetadatos();

        return $metadatos;
    }
}
