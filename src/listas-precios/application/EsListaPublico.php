<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

final class EsListaPublico
{
    public function ejecutar(array $lista): bool
    {
        $nombre = strtolower(trim((string) ($lista['nombre'] ?? '')));
        $resultado = $nombre === 'publico' || $nombre === 'pÃºblico';

        return $resultado;
    }
}
