<?php

declare(strict_types=1);

namespace Ventas\Precios\Application;

final class CalcularCostoEnPesos
{
    public function __construct(
        private readonly NormalizarMonedaCosto $normalizarMonedaCosto,
        private readonly ObtenerCotizacionDolar $obtenerCotizacionDolar
    ) {
    }

    public function ejecutar(float $costoOrigen, string $moneda): float
    {
        $costoOrigen = max(0, $costoOrigen);
        $resultado = $this->normalizarMonedaCosto->ejecutar($moneda) === "USD"
            ? $costoOrigen * $this->obtenerCotizacionDolar->ejecutar()
            : $costoOrigen;

        return $resultado;
    }
}
