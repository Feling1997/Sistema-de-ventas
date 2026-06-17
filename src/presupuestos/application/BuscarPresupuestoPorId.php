<?php

declare(strict_types=1);

namespace Ventas\Presupuestos\Application;

use Ventas\Presupuestos\Domain\Entidades\Presupuesto;
use Ventas\Presupuestos\Domain\Repositorios\PresupuestoRepository;

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
