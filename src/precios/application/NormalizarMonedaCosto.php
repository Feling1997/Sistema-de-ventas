<?php

declare(strict_types=1);

namespace Ventas\Precios\Application;

final class NormalizarMonedaCosto
{
    public function ejecutar(string $moneda): string
    {
        $resultado = strtoupper(trim($moneda)) === "USD" ? "USD" : "ARS";

        return $resultado;
    }
}
