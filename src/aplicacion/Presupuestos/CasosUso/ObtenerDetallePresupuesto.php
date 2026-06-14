<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Presupuestos\CasosUso;

use Ventas\Dominio\Presupuestos\Repositorios\PresupuestoRepository;

final class ObtenerDetallePresupuesto
{
    public function __construct(private readonly PresupuestoRepository $presupuestoRepository)
    {
    }

    public function ejecutar(int $idPresupuesto): array
    {
        return $this->presupuestoRepository->obtenerDetalle($idPresupuesto);
    }
}
