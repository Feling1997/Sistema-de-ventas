<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

final class EsListaCosto
{
    public function ejecutar(array $lista): bool
    {
        $resultado = strtolower(trim((string) ($lista['nombre'] ?? ''))) === 'costo';

        return $resultado;
    }
}
