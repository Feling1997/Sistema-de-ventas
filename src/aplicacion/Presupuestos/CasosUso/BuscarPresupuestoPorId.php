<?php

declare(strict_types=1);

namespace Ventas\Aplicacion\Presupuestos\CasosUso;

use Ventas\Dominio\Presupuestos\Entidades\Presupuesto;
use Ventas\Dominio\Presupuestos\Repositorios\PresupuestoRepository;

final class BuscarPresupuestoPorId
{
    public function __construct(private readonly PresupuestoRepository $presupuestoRepository)
    {
    }

    public function ejecutar(int $id): ?Presupuesto
    {
        return $this->presupuestoRepository->buscarPorId($id);
    }
}
