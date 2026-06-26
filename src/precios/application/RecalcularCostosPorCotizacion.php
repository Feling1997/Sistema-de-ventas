<?php

declare(strict_types=1);

namespace Ventas\Precios\Application;

use Throwable;
use Ventas\Precios\Domain\Repositorios\PrecioRepository;

final class RecalcularCostosPorCotizacion
{
    public function __construct(
        private readonly PrecioRepository $repository,
        private readonly ObtenerCotizacionDolar $obtenerCotizacionDolar,
        private readonly RecalcularPreciosProductosPorStock $recalcularPreciosProductosPorStock
    ) {
    }

    public function ejecutar(): int
    {
        $resultado = 0;

        try {
            $ids = $this->repository->obtenerStocksEnDolares();
            $cotizacion = $this->obtenerCotizacionDolar->ejecutar();
            $this->repository->actualizarCostosPorCotizacion($cotizacion);

            foreach ($ids as $id) {
                $this->recalcularPreciosProductosPorStock->ejecutar((int) $id);
            }

            registrar_operacion("stock.cotizacion_dolar.recalcular", [
                "cotizacion" => $cotizacion,
                "stocks_actualizados" => count($ids),
            ]);
            $resultado = count($ids);
        } catch (Throwable $exception) {
            registrar_log("Precios::recalcular_costos_por_cotizacion", $exception->getMessage());
            registrar_operacion("stock.cotizacion_dolar.error", ["error" => $exception->getMessage()]);
            $resultado = 0;
        }

        return $resultado;
    }
}
