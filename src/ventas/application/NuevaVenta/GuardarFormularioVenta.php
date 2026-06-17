<?php

declare(strict_types=1);

namespace Ventas\Ventas\Application\NuevaVenta;

use Ventas\Ventas\Domain\NuevaVenta\Repositorios\FormularioVentaRepository;

final class GuardarFormularioVenta
{
    public function __construct(private readonly FormularioVentaRepository $formularioVentaRepository)
    {
    }

    public function ejecutar(array $datos): void
    {
        $this->formularioVentaRepository->guardar($datos);
    }
}
