<?php

declare(strict_types=1);

namespace Ventas\Dominio\Presupuestos\Repositorios;

use Ventas\Dominio\Presupuestos\Entidades\DetallePresupuesto;
use Ventas\Dominio\Presupuestos\Entidades\Presupuesto;

interface PresupuestoRepository
{
    public function buscarPorId(int $id): ?Presupuesto;

    /**
     * @return array<int, DetallePresupuesto>
     */
    public function obtenerDetalle(int $idPresupuesto): array;
}
