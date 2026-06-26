<?php

declare(strict_types=1);

namespace Ventas\Precios\Application;

use Throwable;
use Ventas\Precios\Domain\Repositorios\PrecioRepository;

final class RecalcularPreciosProductosPorStock
{
    public function __construct(private readonly PrecioRepository $repository)
    {
    }

    public function ejecutar(int $idStock): bool
    {
        $resultado = false;

        try {
            $precioCosto = $this->repository->obtenerPrecioCostoStock($idStock);

            if ($precioCosto !== null) {
                $resultado = $this->repository->actualizarPreciosProductosPorStock($idStock, $precioCosto);
                $this->repository->recalcularListasPorStock($idStock, $precioCosto);
            }
        } catch (Throwable $exception) {
            registrar_log("Precios::recalcular_precios_productos_por_stock", $exception->getMessage());
            $resultado = false;
        }

        return $resultado;
    }
}
