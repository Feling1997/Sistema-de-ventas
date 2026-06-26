<?php

declare(strict_types=1);

namespace Ventas\Precios\Application;

final class ObtenerCotizacionDolar
{
    public function ejecutar(): float
    {
        $resultado = max(0.0001, parsear_numero_form(config("productos_cotizacion_dolar", "1"), 1));

        return $resultado;
    }
}
