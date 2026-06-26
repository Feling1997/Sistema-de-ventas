<?php

declare(strict_types=1);

namespace Ventas\UnidadesMedida\Application;

use Ventas\UnidadesMedida\Domain\Repositorios\UnidadMedidaRepository;

final class AsegurarUnidadMedidaDesdeFormulario
{
    public function __construct(private readonly UnidadMedidaRepository $unidadMedidaRepository)
    {
    }

    public function ejecutar(string $unidad, array $datos): string
    {
        return $this->unidadMedidaRepository->asegurarDesdeFormulario($unidad, $datos);
    }
}
