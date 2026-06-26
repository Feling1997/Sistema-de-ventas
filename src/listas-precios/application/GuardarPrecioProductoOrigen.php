<?php

declare(strict_types=1);

namespace Ventas\ListasPrecios\Application;

use Ventas\ListasPrecios\Domain\Repositorios\ListaPrecioRepository;

final class GuardarPrecioProductoOrigen
{
    public function __construct(private readonly ListaPrecioRepository $listaPrecioRepository)
    {
    }

    public function ejecutar(int $idProducto, int $idLista, float $porcentaje, float $precio, string $origen = 'manual'): bool
    {
        $resultado = $this->listaPrecioRepository->guardarPrecioProductoOrigen($idProducto, $idLista, $porcentaje, $precio, $origen);

        return $resultado;
    }
}
