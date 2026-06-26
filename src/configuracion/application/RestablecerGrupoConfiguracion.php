<?php

declare(strict_types=1);

namespace Ventas\Configuracion\Application;

use Ventas\Configuracion\Domain\Repositorios\ConfiguracionRepository;

final class RestablecerGrupoConfiguracion
{
    public function __construct(private readonly ConfiguracionRepository $configuracionRepository)
    {
    }

    public function ejecutar(string $grupo): bool
    {
        $resultado = $this->configuracionRepository->restablecerGrupo($grupo);

        return $resultado;
    }
}
