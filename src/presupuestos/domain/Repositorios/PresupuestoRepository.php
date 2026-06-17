<?php

declare(strict_types=1);

namespace Ventas\Presupuestos\Domain\Repositorios;

use Ventas\Presupuestos\Domain\Entidades\DetallePresupuesto;
use Ventas\Presupuestos\Domain\Entidades\Presupuesto;

interface PresupuestoRepository
{
    public function buscarPorId(int $id): ?Presupuesto;

    /**
     * @return array<int, DetallePresupuesto>
     */
    public function obtenerDetalle(int $idPresupuesto): array;
}
